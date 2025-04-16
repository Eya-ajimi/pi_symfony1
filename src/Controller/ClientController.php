<?php 
namespace App\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ClientController extends AbstractController
{
    #[Route('/client/dashboard', name: 'client_dashboard', methods: ['GET', 'POST'])]
    public function dashboard(Request $request, ManagerRegistry $doctrine, UserPasswordHasherInterface $passwordHasher): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CLIENT');
        $user = $this->getUser();
        
        if ($request->isMethod('POST')) {
            $nom       = $request->request->get('nom');
            $prenom    = $request->request->get('prenom');
            $email     = $request->request->get('email');
            $adresse   = $request->request->get('adresse');
            $telephone = $request->request->get('telephone');
            $password  = $request->request->get('password');
            
            if ($nom !== null)       { $user->setNom($nom); }
            if ($prenom !== null)    { $user->setPrenom($prenom); }
            if ($email !== null)     { $user->setEmail($email); }
            if ($adresse !== null)   { $user->setAdresse($adresse); }
            if ($telephone !== null) { $user->setTelephone($telephone); }

            if ($password && $password !== '******') {
                $encodedPassword = $passwordHasher->hashPassword($user, $password);
                $user->setPassword($encodedPassword);
            }

            $entityManager = $doctrine->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Votre profil a été mis à jour.');
            return $this->redirectToRoute('client_dashboard');
        }
        
        return $this->render('client.html.twig', [
            'user' => $user,
        ]);
    }
}
