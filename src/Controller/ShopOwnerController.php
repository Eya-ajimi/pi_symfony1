<?php
namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ShopOwnerController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    #[Route('/shopowner/dashboard', name: 'shopowner_dashboard')]
    public function dashboard(): Response
    {
        $user = $this->getUser();
        $this->logger->info('Accessing shopowner dashboard', [
            'user' => $user ? $user->getUserIdentifier() : 'anonymous',
            'roles' => $user ? $user->getRoles() : [],
            'raw_role' => $user ? $user->getRole()->value : null
        ]);

        $this->denyAccessUnlessGranted('ROLE_SHOPOWNER');
        return $this->render('shopowner.html.twig');
    }
}