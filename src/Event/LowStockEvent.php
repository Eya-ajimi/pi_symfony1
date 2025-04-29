<?php
namespace App\Event;

use App\Entity\Produit;

class LowStockEvent
{
    public const NAME = 'product.low_stock';

    public function __construct(
        public Produit $product,
        public int $remainingStock
    ) {
    }
}