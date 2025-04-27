<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Enum\Role;
use App\Form\ShopOwnerRegistrationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class SignupShopOwnerController extends AbstractController
{
    #[Route('/signup/shop-owner', name: 'app_signup_shop_owner')]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        MailerInterface $mailer,
        SessionInterface $session
    ): Response {
        $user = new Utilisateur();
        $form = $this->createForm(ShopOwnerRegistrationType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Stocker les données du formulaire dans la session
            $session->set('pending_shopowner_data', [
                'nom' => $user->getNom(),
                'email' => $user->getEmail(),
                'categorie_id' => $user->getCategorie()?->getIdCategorie(),  // 👈 correction ici
                'description' => $user->getDescription(),
                'plainPassword' => $form->get('plainPassword')->getData()
            ]);
            

            $verificationCode = random_int(100000, 999999);
            $session->set('verification_code_shopowner', $verificationCode);

            // Envoyer l'email avec le code
            $emailMessage = (new Email())
                ->from('Innomall.esprit@gmail.com')
                ->to($user->getEmail())
                ->subject('Votre code de vérification')
                ->text("Votre code de vérification est : $verificationCode");

            $mailer->send($emailMessage);

            return $this->redirectToRoute('app_signup_shop_owner_verify');
        }

        return $this->render('auth/signupshopowner.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/signup/shop-owner/verify', name: 'app_signup_shop_owner_verify')]
    public function verify(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $session = $request->getSession();
        $pendingData = $session->get('pending_shopowner_data');
        $sessionCode = $session->get('verification_code_shopowner');

        if (!$pendingData) {
            return $this->redirectToRoute('app_signup_shop_owner');
        }

        if ($request->isMethod('POST')) {
            $enteredCode = $request->request->get('verification_code');

            if ($enteredCode == $sessionCode) {
                $user = new Utilisateur();
                $user->setNom($pendingData['nom']);
                $user->setEmail($pendingData['email']);
                $user->setRole(Role::SHOPOWNER);
                $user->setDateInscription(new \DateTimeImmutable());
                $user->setStatut('actif');
                $user->setPoints(0);
                $user->setNombreDeGain(0);
                $user->setDescription($pendingData['description']);
                $user->setAdresse('');
                $user->setTelephone('');
                $user->setPrenom('');

                // Remettre la catégorie
                if ($pendingData['categorie_id']) {
                    $categorie = $em->getRepository(\App\Entity\Categorie::class)->find($pendingData['categorie_id']);
                    if ($categorie) {
                        $user->setCategorie($categorie);
                    }
                }

                $hashedPassword = $passwordHasher->hashPassword($user, $pendingData['plainPassword']);
                $user->setPassword($hashedPassword);

                $em->persist($user);
                $em->flush();

               // $this->addFlash('success', 'Votre compte commerçant a été créé avec succès !');

                $session->remove('pending_shopowner_data');
                $session->remove('verification_code_shopowner');

                return $this->redirectToRoute('app_login');
            } else {
                $this->addFlash('error', 'Code de vérification incorrect.');
            }
        }

        return $this->render('verify_code.html.twig');
    }
}
