<?php
namespace App\Security;

use App\Entity\Utilisateur;
use App\Enums\Role;
use App\Repository\UtilisateurRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Psr\Log\LoggerInterface;

class AppAuthenticator extends AbstractLoginFormAuthenticator implements AuthenticationEntryPointInterface
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private UtilisateurRepository $userRepository,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private LoggerInterface $logger
    ) {}

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->urlGenerator->generate(self::LOGIN_ROUTE));
    }
    public function authenticate(Request $request): Passport
    {
        $formData = $request->request->all('login_form');
        $email = $formData['email'] ?? '';
        $password = $formData['mot_de_passe'] ?? '';
        $csrfToken = $request->request->get('_csrf_token');

        $this->logger->info('Login attempt', [
            'email' => $email,
            'has_password' => !empty($password),
            'csrf_token' => $csrfToken,
            'form_data' => $formData
        ]);

        if (empty($email) || empty($password)) {
            $this->logger->error('Missing credentials', [
                'email_empty' => empty($email),
                'password_empty' => empty($password),
                'form_data' => $formData
            ]);
            throw new CustomUserMessageAuthenticationException('Veuillez remplir tous les champs.');
        }

        return new Passport(
            new UserBadge($email, function($userIdentifier) {
                $this->logger->info('Looking for user', ['email' => $userIdentifier]);
                $user = $this->userRepository->findOneBy(['email' => $userIdentifier]);
                
                if (!$user) {
                    $this->logger->error('User not found', ['email' => $userIdentifier]);
                    throw new CustomUserMessageAuthenticationException('Identifiants incorrects.');
                }

                $this->logger->info('User found with role', [
                    'email' => $user->getEmail(),
                    'role' => $user->getRole()->value,
                    'role_name' => $user->getRole()->name,
                    'status' => $user->getStatut()
                ]);

                if (!$user->isActive()) {
                    $this->logger->error('User account is not active', ['email' => $userIdentifier]);
                    throw new CustomUserMessageAuthenticationException('Votre compte est désactivé.');
                }

                return $user;
            }),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        
        if (!$user instanceof Utilisateur) {
            $this->logger->error('Invalid user type', [
                'user_type' => gettype($user),
                'user_class' => is_object($user) ? get_class($user) : 'not an object'
            ]);
            return new RedirectResponse($this->urlGenerator->generate('app_login'));
        }
    
        $roleValue = $user->getRole()->value;
        
        // Debug output
        $this->logger->critical('DEBUG - Authentication success details', [
            'user_email' => $user->getEmail(),
            'role_value' => $roleValue,
            'returned_roles' => $user->getRoles(),
        ]);

        // Check role value directly
        if ($roleValue === 'ADMIN') {
            return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
        } elseif ($roleValue === 'SHOPOWNER') {
            return new RedirectResponse($this->urlGenerator->generate('shopowner_dashboard'));
        } elseif ($roleValue === 'CLIENT') {
            return new RedirectResponse($this->urlGenerator->generate('app_home_page'));
        }

        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $this->logger->error('Authentication failed', [
            'exception' => $exception->getMessage(),
            'email' => $request->request->get('email'),
            'trace' => $exception->getTraceAsString()
        ]);

        return parent::onAuthenticationFailure($request, $exception);
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}