<?php


namespace App\Controller\backend;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\UtilisateurRepository;
use App\Repository\ProduitRepository;
use App\Repository\CategorieRepository;
use App\Repository\DiscountRepository;
use App\Repository\ScheduleRepository;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Utilisateur;
use App\Entity\Message;
use App\Repository\MessageRepository;
use App\Form\MessageType;
final class ShopsadminController extends AbstractController
{
    #[Route('/admin/shopsadmin', name: 'app_shopsadmin')]
    public function shopsAdmin(
        UtilisateurRepository $utilisateurRepository,
        ProduitRepository $produitRepository,
        CategorieRepository $categorieRepository,
        EventRepository $eventRepository,
        DiscountRepository $discountRepository,
        ScheduleRepository $scheduleRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $shops = $utilisateurRepository->findByRole('SHOPOWNER');
        $currentDate = new \DateTimeImmutable();
        $currentDay = strtolower($currentDate->format('l')); // e.g. 'monday'
        $currentTime = $currentDate->format('H:i:s');

        // Initialize arrays
        $shopProductCounts = [];
        $shopCategories = [];
        $hasCurrentEvent = [];
        $hasCurrentDiscount = [];
        $currentDiscountPercentages = [];
        $shopOpenStatus = [];

        foreach ($shops as $shop) {
            $shopId = $shop->getId();

            // Auto-update payment status if overdue
            if ($shop->isPaymentOverdue()) {
                $shop->setIsPaid(false);
                $entityManager->persist($shop);
            }

            // Count products
            $shopProductCounts[$shopId] = $produitRepository->countProductsForShop($shop);

            // Get category name
            $shopCategories[$shopId] = $shop->getCategorie() ? $shop->getCategorie()->getNom() : 'N/A';

            // Check for current events
            $hasCurrentEvent[$shopId] = false;
            $events = $eventRepository->findBy(['organisateur' => $shop]);

            foreach ($events as $event) {
                $startDate = $event->getDateDebut();
                $endDate = $event->getDateFin();

                if (
                    $startDate && $endDate &&
                    $currentDate >= $startDate &&
                    $currentDate <= $endDate
                ) {
                    $hasCurrentEvent[$shopId] = true;
                    break;
                }
            }

            // Check for current discounts
            $hasCurrentDiscount[$shopId] = false;
            $currentDiscountPercentages[$shopId] = null;
            $discounts = $discountRepository->findBy(['shop' => $shop]);

            foreach ($discounts as $discount) {
                $discountStart = $discount->getStartDate();
                $discountEnd = $discount->getEndDate();

                if (
                    $discountStart && $discountEnd &&
                    $currentDate >= $discountStart &&
                    $currentDate <= $discountEnd
                ) {
                    $hasCurrentDiscount[$shopId] = true;
                    $currentDiscountPercentages[$shopId] = $discount->getDiscountPercentage();
                    break;
                }
            }

            // Check if shop is currently open
            $shopOpenStatus[$shopId] = false;
            $todaySchedule = $scheduleRepository->findOneBy([
                'shop' => $shop,
                'dayOfWeek' => strtoupper($currentDay)
            ]);

            if ($todaySchedule) {
                $openingTime = $todaySchedule->getOpeningTime()->format('H:i:s');
                $closingTime = $todaySchedule->getClosingTime()->format('H:i:s');
                $shopOpenStatus[$shopId] = ($currentTime >= $openingTime && $currentTime <= $closingTime);
            }
        }

        // Flush any changes to payment status
        $entityManager->flush();

        return $this->render('backend/shopsadmin.html.twig', [
            'shops' => $shops,
            'shop_product_counts' => $shopProductCounts,
            'shopcategories' => $shopCategories,
            'has_current_event' => $hasCurrentEvent,
            'has_current_discount' => $hasCurrentDiscount,
            'current_discount_percentages' => $currentDiscountPercentages,
            'shop_open_status' => $shopOpenStatus
        ]);
    }

    #[Route('/admin/shopsadmin/{id}/pay', name: 'app_shopsadmin_pay', methods: ['POST'])]
    public function markAsPaid(
        int $id,
        UtilisateurRepository $utilisateurRepository,
        EntityManagerInterface $entityManager,
        Request $request
    ): JsonResponse {
        // Verify CSRF token
        file_put_contents('payment.log', "Payment attempt for shop $id at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
        $submittedToken = $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid('authenticate', $submittedToken)) {
            return $this->json(['success' => false, 'message' => 'Invalid CSRF token'], 403);
        }

        $shop = $utilisateurRepository->find($id);

        if (!$shop) {
            return $this->json(['success' => false, 'message' => 'Shop not found'], 404);
        }

        try {
            $shop->setIsPaid(true);
            $shop->setLastPaymentDate(new \DateTime()); // Set to current time
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Payment successful',
                'payment_date' => $shop->getLastPaymentDate()->format('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error updating payment status: ' . $e->getMessage()
            ], 500);
        }
    }


    #[Route('/admin/contact-shop-owner/{id}', name: 'admin_contact_shop_owner', methods: ['GET', 'POST'])]
    public function contactShopOwner(
        Utilisateur $shopOwner,
        Request $request,
        EntityManagerInterface $entityManager,
        MessageRepository $messageRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $admin = $this->getUser();

        // Get all admin-visible messages (both to all admins and direct messages)
        $messages = $messageRepository->findBy(
            ['recipient' => null, 'isToAllAdmins' => true],
            ['createdAt' => 'DESC']
        );

        $message = new Message();
        $message->setSender($admin);

        // Set to all admins by default (NULL recipient and isToAllAdmins=true)
        $message->setRecipient(null);
        $message->setIsToAllAdmins(true);
        $message->setCreatedAt(new \DateTime());

        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Ensure these values are set (in case form modifies them)
            $message->setRecipient(null);
            $message->setIsToAllAdmins(true);
            $message->setIsRead(false);

            $entityManager->persist($message);
            $entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'status' => 'success',
                    'message' => $message->getContent(),
                    'createdAt' => $message->getCreatedAt()->format('Y-m-d H:i:s')
                ]);
            }

            return $this->redirectToRoute('admin_contact_shop_owner', ['id' => $shopOwner->getId()]);
        }

        return $this->render('backend/ContactShopOwner.html.twig', [
            'shopOwner' => $shopOwner,
            'messages' => $messages,
            'form' => $form->createView(),
            'admin' => $admin
        ]);
    }
}