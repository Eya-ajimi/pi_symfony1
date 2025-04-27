<?php

namespace App\Security;

use App\Entity\Utilisateur;
use App\Enum\Role;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class GoogleAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $em,
        private RouterInterface $router
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function() use ($accessToken, $client, $request) {
                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);
                $email = $googleUser->getEmail();

                // 1. Vérifier si l'utilisateur existe déjà
                $user = $this->em->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);
                
                if ($user) {
                    return $user;
                }

                // 2. Créer un nouvel utilisateur
                $user = new Utilisateur();
                $user->setEmail($email);
                $user->setPrenom($googleUser->getFirstName() ?? '');
                $user->setNom($googleUser->getLastName() ?? '');
                
                // 3. Définir le rôle depuis la session
                $role = $request->getSession()->get('google_auth_role', Role::CLIENT->value);
                $user->setRole(Role::from($role));
                
                // 4. Définir les valeurs par défaut
                $user->setPassword('');
                $user->setAdresse('');
                $user->setTelephone('');
                $user->setStatut('actif');

                // 5. Sauvegarder l'utilisateur
                $this->em->persist($user);
                $this->em->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?RedirectResponse
    {
        /** @var Utilisateur $user */
        $user = $token->getUser();
        
        // Redirection en fonction du rôle
        return match ($user->getRole()) {
            Role::SHOPOWNER => new RedirectResponse($this->router->generate('shopowner_dashboard')),
            Role::CLIENT => new RedirectResponse($this->router->generate('client_dashboard')),
            default => new RedirectResponse($this->router->generate('app_home')),
        };
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?RedirectResponse
    {
        return new RedirectResponse($this->router->generate('app_login'));
    }

    public function start(Request $request, AuthenticationException $authException = null): RedirectResponse
    {
        return new RedirectResponse($this->router->generate('app_login'));
    }
}