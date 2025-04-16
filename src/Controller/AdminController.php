<?php
/// src/Controller/AdminController.php
namespace App\Controller;

use App\Entity\Utilisateur;
use App\Entity\Categorie;
use App\Enums\Role;
use App\Repository\UtilisateurRepository;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class AdminController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    public function dashboard(UtilisateurRepository $utilisateurRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $utilisateurs = $utilisateurRepo->findAll();
        
        return $this->render('dashboard.html.twig', [
            'utilisateurs' => $utilisateurs
        ]);
    }

    #[Route('/admin/utilisateur/delete/{id}', name: 'admin_delete_user')]
    public function deleteUser(Utilisateur $utilisateur, EntityManagerInterface $em): Response
    {
        $em->remove($utilisateur);
        $em->flush();
        
        $this->addFlash('success', 'Utilisateur supprimé avec succès');
        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/admin/utilisateur/edit/{id}', name: 'app_user_edit')]
    public function editUser(
        Request $request, 
        Utilisateur $utilisateur, 
        EntityManagerInterface $em,
        CategorieRepository $categorieRepository
    ): Response
    {
        // Extract data from the entity before creating the form
        $userData = [
            'email' => $utilisateur->getEmail(),
            'nom' => $utilisateur->getNom(),
            'prenom' => $utilisateur->getPrenom(),
            'adresse' => $utilisateur->getAdresse(),
            'telephone' => $utilisateur->getTelephone(),
            'description' => $utilisateur->getDescription(),
            'categorie' => $utilisateur->getCategorie(),
            'role' => $utilisateur->getRole(),
            'statut' => $utilisateur->getStatut()
        ];
        
        // Create form with entity as data
        $formBuilder = $this->createFormBuilder($utilisateur);
        
        // Common fields for all users
        $formBuilder->add('email', EmailType::class, [
            'label' => 'Email',
            'attr' => ['class' => 'form-control'],
            'data' => $userData['email']
        ]);
        
        // Role-specific fields
        if ($utilisateur->getRole() === Role::SHOPOWNER) {
            // SHOPOWNER fields
            $formBuilder->add('nom', TextType::class, [
                'label' => 'Nom du magasin',
                'attr' => ['class' => 'form-control'],
                'data' => $userData['nom']
            ]);
            
            // Handle categorie as an entity type
            $formBuilder->add('categorie', ChoiceType::class, [
                'label' => 'Catégorie',
                'attr' => ['class' => 'form-select'],
                'required' => false,
                'choices' => $categorieRepository->findAll(),
                'choice_label' => function(Categorie $categorie) {
                    return $categorie->getNom();
                },
                'choice_value' => function(?Categorie $categorie) {
                    return $categorie ? $categorie->getIdCategorie() : '';
                },
                'placeholder' => 'Choisir une catégorie',
                'data' => $userData['categorie']
            ]);
            
            $formBuilder->add('description', TextType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'form-control'],
                'required' => false,
                'data' => $userData['description']
            ]);
            
        } else {
            // CLIENT fields
            $formBuilder->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => ['class' => 'form-control'],
                'data' => $userData['nom']
            ]);
            
            $formBuilder->add('prenom', TextType::class, [
                'label' => 'Prénom', 
                'attr' => ['class' => 'form-control'],
                'data' => $userData['prenom']
            ]);
            
            $formBuilder->add('adresse', TextType::class, [
                'label' => 'Adresse',
                'attr' => ['class' => 'form-control'],
                'data' => $userData['adresse']
            ]);
            
            $formBuilder->add('telephone', TextType::class, [
                'label' => 'Téléphone',
                'attr' => ['class' => 'form-control'],
                'data' => $userData['telephone']
            ]);
        }
        
        // Add role and status fields for admins only
        if ($this->isGranted('ROLE_ADMIN')) {
            $formBuilder->add('role', ChoiceType::class, [
                'label' => 'Rôle',
                'choices' => [
                    'Client' => Role::CLIENT,
                    'Commerçant' => Role::SHOPOWNER,
                    'Administrateur' => Role::ADMIN
                ],
                'attr' => ['class' => 'form-select'],
                'data' => $userData['role']
            ]);
            
            $formBuilder->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Actif' => 'actif',
                    'Inactif' => 'inactif'
                ],
                'attr' => ['class' => 'form-select'],
                'data' => $userData['statut']
            ]);
        }
        
        // Add save button
        $formBuilder->add('save', SubmitType::class, [
            'label' => 'Enregistrer',
            'attr' => ['class' => 'btn btn-primary mt-3']
        ]);
        
        $form = $formBuilder->getForm();
        
        // Handle form submission
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Utilisateur modifié avec succès');
            return $this->redirectToRoute('admin_dashboard');
        }
        
        return $this->render('admin/edit_user.html.twig', [
            'form' => $form->createView(),
            'utilisateur' => $utilisateur
        ]);
    }
}