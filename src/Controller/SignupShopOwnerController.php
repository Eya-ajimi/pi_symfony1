<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\ShopOwnerRegistrationType;
use App\Enums\Role;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class SignupShopOwnerController extends AbstractController
{
    #[Route('/signup/shop-owner', name: 'app_signup_shop_owner')]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = new Utilisateur();
        $form = $this->createForm(ShopOwnerRegistrationType::class, $user);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Configuration spécifique shop owner
            $user->setRole(Role::SHOPOWNER);
            $user->setDateInscription(new \DateTime());
            $user->setStatut('actif');
            $user->setPoints(0);
            $user->setNombreDeGain(0);
            $user->setAdresse('');
            $user->setTelephone('');
            $user->setPrenom(''); // Champ obligatoire mais non utilisé pour shop owner
            
            // Hashage du mot de passe
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );
            $user->setPassword($hashedPassword);
            
            $em->persist($user);
            $em->flush();
            
            $this->addFlash('success', 'Votre inscription a été enregistrée avec succès !');
            return $this->redirectToRoute('app_login');
        }
        
        return $this->render('aziz/auth/signupshopowner.html.twig', [
            'form' => $form->createView()
        ]);
    }
}