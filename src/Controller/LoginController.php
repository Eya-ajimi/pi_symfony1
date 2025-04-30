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
        $user = $this->getUser();
        if($this->getUser()){
            $roleValue = $user->getRole()->value;
            if ($roleValue === 'ADMIN') {
                return $this->redirectToRoute('app_admin_dashboard1');
            } elseif ($roleValue === 'SHOPOWNER') {
                return $this->redirectToRoute('dashboard');
               
            } else {
                return $this->redirectToRoute('app_home_page');
            }
        }
        else{
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        $form = $this->createForm(LoginFormType::class, [
            'email' => $lastUsername,
        ]);

        // Récupérer le rôle pour login avec Google
        if ($role = $request->query->get('role')) {
            $request->getSession()->set('google_auth_role', $role);
        }

        // Vérification manuelle de reCAPTCHA si formulaire POST
    

        return $this->render('aziz/auth/login.html.twig', [
            'loginForm' => $form->createView(),
            'error' => $error,
        ]);
    }
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method will be intercepted by the logout key on your firewall.');
    }
}