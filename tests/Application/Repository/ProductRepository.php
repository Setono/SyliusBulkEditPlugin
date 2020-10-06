<?php
declare(strict_types=1);

namespace Tests\Setono\SyliusBulkEditPlugin\Application\Repository;

use Setono\SyliusBulkEditPlugin\Doctrine\ORM\ProductRepositoryTrait;
use Setono\SyliusBulkEditPlugin\Repository\ProductRepositoryInterface;
use Sylius\Bundle\CoreBundle\Doctrine\ORM\ProductRepository as BaseProductRepository;

class ProductRepository extends BaseProductRepository implements ProductRepositoryInterface
{
    use ProductRepositoryTrait;
}
