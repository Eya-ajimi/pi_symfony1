<?php
// src/Controller/ReclamationController.php
namespace App\Controller;

use App\Form\reclamationform;
use App\Entity\Reclamation;
use App\Entity\Utilisateur;
use App\Repository\ReclamationRepository;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\NotBlank;
use App\Form\ReclamationType;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class ReclamationController extends AbstractController
{
    #[Route('/client/reclamation', name: 'app_reclamation')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $reclamation = new Reclamation();
        $form = $this->createForm(ReclamationType::class, $reclamation);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $reclamation->setUtilisateur($this->getUser());
            $reclamation->setStatut('non traite');
            
            $entityManager->persist($reclamation);
            $entityManager->flush();
            
            $this->addFlash('success', 'Complaint submitted successfully!');
            return $this->redirectToRoute('app_reclamation');
        }
        
        return $this->render('reclamation/reclamation.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/admin/reclamation', name: 'app_reclamation_admin')]
    public function indexAdmin(
        ReclamationRepository $reclamationRepository,
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer // Add MailerInterface dependency
    ): Response {
        $reclamations = $reclamationRepository->findAllOrderedByStatus();

        if ($request->isMethod('POST')) {
            $replyData = $request->request->all()['reply'] ?? null;

            if ($replyData && isset($replyData['id']) && isset($replyData['commentaire'])) {
                $reclamation = $reclamationRepository->find((int) $replyData['id']);

                if ($reclamation) {
                    $reclamation->setCommentaire($replyData['commentaire']);
                    $reclamation->setStatut('traite');
                    $em->flush();

                    // Send email notification directly in controller
                    $user = $reclamation->getUtilisateur();
                    if ($user) {
                        $email = (new TemplatedEmail())
                            ->from('Innomall.esprit@gmail.com')
                            ->to($user->getEmail())
                            ->subject('Your reclamation has been answered')
                            ->htmlTemplate('backend/reclamation_reply.html.twig')
                            ->context([
                                'description' => $reclamation->getDescription(),
                                'reply' => $replyData['commentaire'],
                                'recipient_name' => $user->getFullName(),
                            ]);

                        try {
                            $mailer->send($email);
                            $this->addFlash('success', 'Reclamation updated and user notified!');
                        } catch (\Exception $e) {
                            $this->addFlash('warning', 'Reclamation updated but email could not be sent: '.$e->getMessage());
                        }
                    } else {
                        $this->addFlash('success', 'Reclamation updated!');
                    }

                    return $this->redirectToRoute('app_reclamation_admin');
                }

                $this->addFlash('error', 'Reclamation not found!');
            } else {
                $this->addFlash('error', 'Invalid form data!');
            }
        }

        $replyForm = $this->createForm(reclamationform::class);

        return $this->render('backend/reclamation.html.twig', [
            'reclamations' => $reclamations,
            'replyForm' => $replyForm->createView(),
        ]);
    }
}