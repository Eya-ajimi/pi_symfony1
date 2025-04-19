<?php
namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Response;

class NoCacheListener
{
    private $security;
    
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        
        // Set strongest possible cache prevention headers
        $response->setPrivate();
        $response->setMaxAge(0);
        $response->setSharedMaxAge(0);
        $response->headers->addCacheControlDirective('no-store', true);
        $response->headers->addCacheControlDirective('must-revalidate', true);
        $response->headers->addCacheControlDirective('no-cache', true);
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '-1');
        
        // Add additional headers to prevent back-button caching
        $response->headers->set('X-Content-Type-Options', 'nosniff');
    }
} 