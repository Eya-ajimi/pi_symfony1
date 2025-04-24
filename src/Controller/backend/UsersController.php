<?php
namespace App\Controller\backend;

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
final class UsersController extends AbstractController{
    #[Route('/admin/usersgestion', name: 'admin_dashboard')]
    public function dashboard(UtilisateurRepository $utilisateurRepo): Response
    {

        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $utilisateurs = $utilisateurRepo->findAll();

        // Statistiques
        $nombreTotal = count($utilisateurs);
        $nombreActifs = count(array_filter($utilisateurs, fn($u) => $u->isActive()));
        $nombreInactifs = $nombreTotal - $nombreActifs;

        $nombreClients = count(array_filter($utilisateurs, fn($u) => $u->getRole() === Role::CLIENT));
        $nombreCommercants = count(array_filter($utilisateurs, fn($u) => $u->getRole() === Role::SHOPOWNER));
        $nombreAdmins = count(array_filter($utilisateurs, fn($u) => $u->getRole() === Role::ADMIN));

        return $this->render('backend/users.html.twig', [
            'utilisateurs' => $utilisateurs,
            'nombreTotal' => $nombreTotal,
            'nombreActifs' => $nombreActifs,
            'nombreInactifs' => $nombreInactifs,
            'nombreClients' => $nombreClients,
            'nombreCommercants' => $nombreCommercants,
            'nombreAdmins' => $nombreAdmins,
        ]);
    }

    #[Route('/admin/usersgestion/delete/{id}', name: 'admin_delete_user', methods: ['POST'])]
    public function deleteUser(Utilisateur $utilisateur, EntityManagerInterface $em, Request $request): Response
    {
        $submittedToken = $request->request->get('_token');

        if ($this->isCsrfTokenValid('delete' . $utilisateur->getId(), $submittedToken)) {
            $em->remove($utilisateur);
            $em->flush();

            $this->addFlash('success', 'Utilisateur supprimé avec succès');
        }

        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/admin/usersgestion/edit/{id}', name: 'app_user_edit')]
    public function editUser(
        Request $request,
        Utilisateur $utilisateur,
        EntityManagerInterface $em,
        CategorieRepository $categorieRepository
    ): Response
    {
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
        //dd($utilisateur);
        $formBuilder = $this->createFormBuilder($utilisateur);

        $formBuilder->add('email', EmailType::class, [
            'label' => 'Email',
            'attr' => ['class' => 'form-control'],
            'data' => $userData['email']
        ]);

        if ($utilisateur->getRole() === Role::SHOPOWNER) {
            $formBuilder->add('nom', TextType::class, [
                'label' => 'Nom du magasin',
                'attr' => ['class' => 'form-control'],
                'data' => $userData['nom']
            ]);

            $formBuilder->add('categorie', ChoiceType::class, [
                'label' => 'Catégorie',
                'attr' => ['class' => 'form-select'],
                'required' => false,
                'choices' => $categorieRepository->findAll(),
                'choice_label' => fn(Categorie $categorie) => $categorie->getNom(),
                'choice_value' => fn(?Categorie $categorie) => $categorie ? $categorie->getIdCategorie() : '',
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

        $formBuilder->add('save', SubmitType::class, [
            'label' => 'Enregistrer',
            'attr' => ['class' => 'btn btn-primary mt-3 btn-fixed']
        ]);

        $form = $formBuilder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();//sobeha fi base .nezid objet jedid persist .upadte bela persiste
            $this->addFlash('success', 'Utilisateur modifié avec succès');
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('aziz/admin/edit_user.html.twig', [
            'form' => $form->createView(),
            'utilisateur' => $utilisateur
        ]);
    }
}

