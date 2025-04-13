<?php
// src/Controller/ReclamationController.php
namespace App\Controller;

use App\Entity\Reclamation;
use App\Entity\Utilisateur;
use App\Form\ReclamationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReclamationController extends AbstractController
{
    #[Route('/reclamation', name: 'app_reclamation')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $reclamation = new Reclamation();
        $form = $this->createForm(ReclamationType::class, $reclamation);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Set default values if needed
            if (empty($reclamation->getCommentaire())) {
                $reclamation->setCommentaire('');
            }
            
            // Set static user ID 9
            $utilisateur = $entityManager->getRepository(Utilisateur::class)->find(9);
            if (!$utilisateur) {
                throw $this->createNotFoundException('User with ID 9 not found');
            }
            $reclamation->setUtilisateur($utilisateur);
            
            // Set status
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
}