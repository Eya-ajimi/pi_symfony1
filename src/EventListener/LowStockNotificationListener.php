<?php 
namespace App\EventListener;

use App\Event\LowStockEvent;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class LowStockNotificationListener
{
    public function __construct(private HubInterface $mercureHub) {}

    public function __invoke(LowStockEvent $event): void
    {
        $product = $event->product;
        $update = new Update(
            'notifications',
            json_encode([
                'type' => 'stock_alert',
                'message' => sprintf(
                    'Low stock: %s (%d left)',
                    $product->getNom(),
                    $event->remainingStock
                ),
                'stock' => $event->remainingStock,
                'productId' => $product->getId(),
                'timestamp' => time(),
            ])
        );

        $this->mercureHub->publish($update);
    }
}