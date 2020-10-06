<?php

declare(strict_types=1);

namespace Setono\SyliusBulkEditPlugin\Repository;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface as BaseProductRepositoryInterface;

interface ProductRepositoryInterface extends BaseProductRepositoryInterface
{
    /**
     * todo create better repository method that fetches the variants AND translations at the same time
     *
     * @return ProductInterface[]
     */
    public function findByIds(array $ids): array;
}
