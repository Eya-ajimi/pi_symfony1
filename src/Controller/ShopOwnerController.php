<?php
namespace App\Controller;

use App\Repository\CategorieRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ShopOwnerController extends AbstractController
{
    #[Route('/shopowner/dashboard', name: 'shopowner_dashboard', methods: ['GET', 'POST'])]
    public function dashboard(
        Request $request,
        ManagerRegistry $doctrine,
        UserPasswordHasherInterface $passwordHasher,
        CategorieRepository $categorieRepo
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_SHOPOWNER');
        $user = $this->getUser();
        $categories = $categorieRepo->findAll();

        if ($request->isMethod('POST')) {
            $nom         = $request->request->get('nom');
            $email       = $request->request->get('email');
            $telephone   = $request->request->get('telephone');
            $categorie   = $request->request->get('categorie');
            $description = $request->request->get('description');
            $password    = $request->request->get('password');

            if ($nom !== null)         { $user->setNom($nom); }
            if ($email !== null)       { $user->setEmail($email); }
            if ($telephone !== null)   { $user->setTelephone($telephone); }
            if ($categorie !== null)   { $user->setCategorie($categorie); }
            if ($description !== null) { $user->setDescription($description); }

            if ($password && $password !== '******') {
                $encodedPassword = $passwordHasher->hashPassword($user, $password);
                $user->setPassword($encodedPassword);
            }

            $entityManager = $doctrine->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Le profil commerçant a été mis à jour.');
            return $this->redirectToRoute('shopowner_dashboard');
        }

        return $this->render('shopowner.html.twig', [
            'user' => $user,
            'categories' => $categories,
        ]);
    }
}
