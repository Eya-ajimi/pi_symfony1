<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Enum\Role;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class VerifyCodeController extends AbstractController
{
    #[Route('/verify-code', name: 'app_verify_code')]
    public function verify(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $session = $request->getSession();

        if ($request->isMethod('POST')) {
            $enteredCode = $request->request->get('verification_code');
            $sessionCode = $session->get('verification_code');
            $pendingUserData = $session->get('pending_user');

            if ($enteredCode == $sessionCode && $pendingUserData) {
                // 🔥 Maintenant on INSÈRE l'utilisateur
                $user = new Utilisateur();
                $user->setNom($pendingUserData['nom']);
                $user->setPrenom($pendingUserData['prenom']);
                $user->setEmail($pendingUserData['email']);
                $user->setAdresse($pendingUserData['adresse']);
                $user->setTelephone($pendingUserData['telephone']);
                $user->setRole(Role::CLIENT); // Ou autre si tu ajoutes role dans formulaire
                $user->setPassword(
                    $passwordHasher->hashPassword($user, $pendingUserData['plainPassword'])
                );

                $entityManager->persist($user);
                $entityManager->flush();

                // ✅ Nettoyer la session
                $session->remove('verification_code');
                $session->remove('pending_user');

                $this->addFlash('success', 'Votre compte a été créé avec succès !');
                return $this->redirectToRoute('app_login');
            } else {
                $this->addFlash('error', 'Code incorrect, veuillez réessayer.');
            }
        }

        return $this->render('verify_code.html.twig');
    }
}
