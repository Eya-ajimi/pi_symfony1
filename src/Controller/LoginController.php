<?php

namespace App\Controller;

use App\Form\LoginFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // Last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        // Create the login form
        $form = $this->createForm(LoginFormType::class, [
            'email' => $lastUsername,
        ]);

        // Stocker le rôle dans la session si présent dans l'URL
        if ($role = $request->query->get('role')) {
            $request->getSession()->set('google_auth_role', $role);
        }

        // Vérification du reCAPTCHA
        if ($request->isMethod('POST')) {
            $recaptchaResponse = $request->request->get('g-recaptcha-response');
            $secret = '6LcmgCYrAAAAAJRRQ4XTqDrQsJKVsQGNhL4uAb5X';
            $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $secret . '&response=' . $recaptchaResponse);
            $responseData = json_decode($verifyResponse);

            if (!$responseData->success) {
                $this->addFlash('error', 'Veuillez valider le reCAPTCHA.');
            }
        }

        return $this->render('auth/login.html.twig', [
            'loginForm' => $form->createView(),
            'error' => $error
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method will be intercepted by the logout key on your firewall.');
    }
}
