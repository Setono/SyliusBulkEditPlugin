<?php

declare(strict_types=1);

namespace Setono\SyliusBulkEditPlugin\Event;

use Sylius\Component\Product\Model\ProductInterface;

final class ProductsUpdatedEvent
{
    /** @var array<array-key, ProductInterface> */
    public array $products;

    /**
     * @param array<array-key, ProductInterface> $products
     */
    public function __construct(array $products)
    {
        $this->products = $products;
    }
}
