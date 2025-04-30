<?php

namespace App\Controller;
use App\Enums\Role;
use App\Entity\Utilisateur;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class SignupUserController extends AbstractController
{
    #[Route('/signup/User', name: 'app_signup_user')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = new Utilisateur();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // 🔥 NE PAS ENREGISTRER dans la BDD ici

            // ✅ Générer le code de vérification
            $verificationCode = random_int(100000, 999999);

            // ✅ Stocker TOUT dans la session
            $session = $request->getSession();
            $session->set('pending_user', [
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'email' => $user->getEmail(),
                'adresse' => $user->getAdresse(),
                'telephone' => $user->getTelephone(),
                'plainPassword' => $form->get('plainPassword')->getData(), // stocké temporairement
            ]);
            $session->set('verification_code', $verificationCode);

            // ✅ Envoyer le code WhatsApp
            $client = new Client();
            $params = [
                'token' => 'uhust0rndctegdvd',
                'to' => '+216' . $user->getTelephone(),
                'body' => 'Votre code de vérification est : ' . $verificationCode,
                'priority' => '1',
            ];
            $headers = [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ];
            $options = ['form_params' => $params];
            $requestWhatsapp = new GuzzleRequest('POST', 'https://api.ultramsg.com/instance109011/messages/chat', $headers);

            try {
                $client->sendAsync($requestWhatsapp, $options)->wait();
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur WhatsApp : ' . $e->getMessage());
                return $this->redirectToRoute('app_signup_user');
            }

            return $this->redirectToRoute('app_verify_code');
        }

        return $this->render('aziz/auth/signupuser.html.twig', [
            'registrationForm' => $form->createView()
        ]);
    }
}