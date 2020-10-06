<?php

declare(strict_types=1);

namespace Setono\SyliusBulkEditPlugin\Doctrine\ORM;

use function assert;
use Doctrine\ORM\EntityRepository;

/**
 * @mixin EntityRepository
 */
trait ProductRepositoryTrait
{
    public function findByIds(array $ids): array
    {
        assert($this instanceof EntityRepository);

        return $this->createQueryBuilder('o')
            ->andWhere('o.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult()
        ;
    }
}
