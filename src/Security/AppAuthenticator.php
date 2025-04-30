<?php
namespace App\Security;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
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
        private LoggerInterface $logger
    ) {}

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->urlGenerator->generate(self::LOGIN_ROUTE));
    }

    public function authenticate(Request $request): Passport
    {
        $formData    = $request->request->all('login_form');
        $email       = $formData['email'] ?? '';
        $password    = $formData['mot_de_passe'] ?? '';
        $csrfToken   = $request->request->get('_csrf_token');
        $recaptcha   = $request->request->get('g-recaptcha-response', '');

        // ── Vérification reCAPTCHA ─────────────────────────────────────────────────
        $secret = $_ENV['RECAPTCHA_SECRET_KEY'];
        $resp = file_get_contents(
            'https://www.google.com/recaptcha/api/siteverify?'
            .'secret='.urlencode($secret)
            .'&response='.urlencode($recaptcha)
        );
        $data = json_decode($resp);
        if (empty($data) || !($data->success ?? false)) {
            // cette exception sera interceptée dans onAuthenticationFailure()
            throw new CustomUserMessageAuthenticationException('Veuillez valider le reCAPTCHA.');
        }
        // ────────────────────────────────────────────────────────────────────────────

        if (empty($email) || empty($password)) {
            throw new CustomUserMessageAuthenticationException('Veuillez remplir tous les champs.');
        }

        return new Passport(
            new UserBadge($email, function($userIdentifier) {
                $user = $this->userRepository->findOneBy(['email'=>$userIdentifier]);
                if (!$user) {
                    throw new CustomUserMessageAuthenticationException('Adresse email inconnue.');
                }
                if (!$user->isActive()) {
                    throw new CustomUserMessageAuthenticationException('Votre compte est désactivé.');
                }
                return $user;
            }),
            new PasswordCredentials($password),
            [ new CsrfTokenBadge('authenticate', $csrfToken) ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        $role = $user instanceof Utilisateur ? $user->getRole()->value : null;

        if ($role === 'ADMIN')     return new RedirectResponse($this->urlGenerator->generate('app_admin_dashboard1'));
        if ($role === 'SHOPOWNER') return new RedirectResponse($this->urlGenerator->generate('dashboard'));
        if ($role === 'CLIENT')    return new RedirectResponse($this->urlGenerator->generate('app_home_page'));

        return new RedirectResponse($this->urlGenerator->generate(self::LOGIN_ROUTE));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $message = $exception->getMessage();

        // Si c'est l'erreur reCAPTCHA, on la range dans 'recaptcha_error'
        if ($message === 'Veuillez valider le reCAPTCHA.') {
            $request->getSession()->getFlashBag()->add('recaptcha_error', $message);
        } else {
            // sinon (email/mot de passe), on la range dans 'error'
            $request->getSession()->getFlashBag()->add('error', $message);
        }

        // Redirige vers la page de login pour ré-afficher le formulaire + flashs
        return new RedirectResponse($this->getLoginUrl($request));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
