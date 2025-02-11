<?php

declare(strict_types=1);

namespace Setono\SyliusBulkEditPlugin\Event;

use Sylius\Component\Product\Model\ProductVariantInterface;

final class ProductVariantsUpdatedEvent
{
    /** @var array<array-key, ProductVariantInterface> */
    public array $productVariants;

    /**
     * @param array<array-key, ProductVariantInterface> $productVariants
     */
    public function __construct(array $productVariants)
    {
        $this->productVariants = $productVariants;
    }
}
