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

class ReclamationController extends AbstractController
{
    #[Route('/client/reclamation', name: 'app_reclamation')]
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
            $utilisateur = $this->getUser();
            if (!$utilisateur) {
                throw $this->createNotFoundException('User not found');
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
    #[Route('/admin/reclamation', name: 'app_reclamation_admin')]
    public function indexAdmin(
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
                    return $this->redirectToRoute('app_reclamation_admin');
                }

                $this->addFlash('error', 'Reclamation not found!');
            } else {
                $this->addFlash('error', 'Invalid form data!');
            }
        }

        // Create empty form for the view
        $replyForm = $this->createForm(reclamationform::class);

        return $this->render('backend/reclamation.html.twig', [
            'reclamations' => $reclamations,
            'replyForm' => $replyForm->createView(),
        ]);
    }
}