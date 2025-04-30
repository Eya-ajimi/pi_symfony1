<?php
// src/Controller/ClientController.php

namespace App\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class ClientController extends AbstractController
{
    #[Route('/client/dashboard', name: 'client_dashboard', methods: ['GET', 'POST'])]
    public function dashboard(
        Request $request,
        ManagerRegistry $doctrine,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_CLIENT');
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            // --- champs texte -------------------------------------------------
            $user->setNom($request->request->get('nom', $user->getNom()));
            $user->setPrenom($request->request->get('prenom', $user->getPrenom()));
            $user->setEmail($request->request->get('email', $user->getEmail()));
            $user->setAdresse($request->request->get('adresse', $user->getAdresse()));
            $user->setTelephone($request->request->get('telephone', $user->getTelephone()));

            // --- mot de passe (ne change que si saisi) ------------------------
            $newPassword = trim((string) $request->request->get('password', ''));
            if ($newPassword !== '') {
                $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            }

            // --- upload image --------------------------------------------------
            $pictureFile = $request->files->get('profilePicture');
            if ($pictureFile) {
                $originalName = pathinfo($pictureFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeName     = $slugger->slug($originalName);
                // on ne dépend plus de fileinfo :
                $ext          = $pictureFile->getClientOriginalExtension() ?: 'bin';
                $newName      = $safeName . '-' . uniqid() . '.' . $ext;

                $uploadDir = $this->getParameter('profile_pictures_directory');
                if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
                    throw new \RuntimeException(sprintf('Impossible de créer "%s".', $uploadDir));
                }

                $pictureFile->move($uploadDir, $newName);
                $user->setProfilePicture($newName);
            }

            $doctrine->getManager()->flush();
            $this->addFlash('success', 'Votre profil a été mis à jour.');
            return $this->redirectToRoute('client_dashboard');
        }

        return $this->render('aziz/client.html.twig', [
            'user' => $user,
        ]);
    }
}
