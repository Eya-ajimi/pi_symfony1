<?php
// src/Controller/ReclamationController.php
namespace App\Controller;

use App\Entity\Reclamation;
use App\Entity\Utilisateur;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\NotBlank;

class ReclamationController extends AbstractController
{
    #[Route('/reclamation', name: 'app_reclamation')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $reclamation = new Reclamation();
        
        // Create form using createFormBuilder
        $form = $this->createFormBuilder($reclamation)
            ->add('description', TextareaType::class, [
                'label' => 'Complaint Details',
                'constraints' => [
                    new NotBlank([
                        'message' => 'The description field cannot be empty'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Please describe your complaint in detail...',
                    'rows' => 5
                ]
            ])
            ->add('nomshop', TextType::class, [
                'label' => 'Shop Name',
                'constraints' => [
                    new NotBlank([
                        'message' => 'The shop name field cannot be empty'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter the shop name'
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Submit Complaint',
                'attr' => ['class' => 'btn-submit']
            ])
            ->getForm();
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Set default values if needed
            if (empty($reclamation->getCommentaire())) {
                $reclamation->setCommentaire('');
            }
            
            // Set static user ID 7
            $utilisateur = $entityManager->getRepository(Utilisateur::class)->find(7);
            if (!$utilisateur) {
                throw $this->createNotFoundException('User with ID 7 not found');
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