<?php

// src/Controller/ReclamationController.php
namespace App\Controller;

use App\Entity\Reclamation;
use App\Form\ReplyType;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReclamationController extends AbstractController
{
    #[Route('/reclamation', name: 'app_reclamation')]
    public function index(
        ReclamationRepository $reclamationRepository,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        // Get all reclamations ordered by status
        $reclamations = $reclamationRepository->findAllOrderedByStatus();

        // Handle form submission
        if ($request->isMethod('POST') && $request->request->has('reply')) {
            $replyData = $request->request->all()['reply'];
            
            // Validate the data
            if (isset($replyData['id']) && isset($replyData['commentaire'])) {
                // Find the reclamation
                $reclamation = $reclamationRepository->find((int) $replyData['id']);
                
                if ($reclamation) {
                    $reclamation->setCommentaire($replyData['commentaire']);
                    $reclamation->setStatut('traite');
                    $em->flush();
                    
                    $this->addFlash('success', 'Reclamation updated successfully!');
                    return $this->redirectToRoute('app_reclamation');
                }
                
                $this->addFlash('error', 'Reclamation not found!');
            } else {
                $this->addFlash('error', 'Invalid form data!');
            }
        }

        // Create empty form for the view
        $replyForm = $this->createForm(ReplyType::class);

        return $this->render('backend/reclamation.html.twig', [
            'reclamations' => $reclamations,
            'replyForm' => $replyForm->createView(),
        ]);
    }
}