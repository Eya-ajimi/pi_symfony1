<?php


namespace App\Controller\backend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Atm;
use App\Form\AtmType;
use App\Repository\AtmRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;




final class AtmsController extends AbstractController{
    #[Route('/admin/atms', name: 'app_atms')]
    public function dashboard(
        Request $request,
        EntityManagerInterface $em,
        AtmRepository $atmRepository
    ): Response {
        $atm = new Atm();
        $form = $this->createForm(AtmType::class, $atm);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $existing = $atmRepository->findOneBy(['bankName' => $atm->getBankName()]);
                if ($existing) {
                    // Add validation error directly to the field
                    $form->get('bankName')->addError(new FormError('An ATM with this bank name already exists.'));
                } else {
                    $em->persist($atm);
                    $em->flush();

                    $this->addFlash('success', 'ATM added successfully!');
                    return $this->redirectToRoute('app_atms');
                }
            }
        }

        $atms = $atmRepository->findAll();

        return $this->render('backend/atms.html.twig', [
            'atmForm' => $form->createView(),
            'atms' => $atms,
        ]);
    }


    #[Route('/admin/atm/edit/{id}', name: 'edit_atm')]
public function editAtm(
    int $id,
    Request $request,
    EntityManagerInterface $em,
    AtmRepository $atmRepository
): Response {
    $atm = $atmRepository->find($id);
    if (!$atm) {
        throw $this->createNotFoundException('ATM not found');
    }

    $form = $this->createForm(AtmType::class, $atm);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->flush();
        $this->addFlash('success', 'ATM updated successfully!');
        return $this->redirectToRoute('app_atms');
    }

    $atms = $atmRepository->findAll();

    return $this->render('backend/atms.html.twig', [
        'atmForm' => $form->createView(),
        'atms' => $atms,
        'editingAtmId' => $atm->getId(), // FIXED: use the correct key
    ]);
    
}

#[Route('/admin/atm/delete/{id}', name: 'delete_atm')]
public function deleteAtm(
    int $id,
    EntityManagerInterface $em,
    AtmRepository $atmRepository
): Response {
    $atm = $atmRepository->find($id);
    if (!$atm) {
        throw $this->createNotFoundException('ATM not found');
    }

    $em->remove($atm);
    $em->flush();
    $this->addFlash('success', 'ATM deleted successfully!');

    return $this->redirectToRoute('app_atms');
}

}
