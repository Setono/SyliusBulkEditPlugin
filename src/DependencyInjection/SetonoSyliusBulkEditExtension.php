<?php

declare(strict_types=1);

namespace Setono\SyliusBulkEditPlugin\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class SetonoSyliusBulkEditExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @psalm-suppress PossiblyNullArgument */
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $container->setParameter('setono_sylius_bulk_edit.option', $config['option']);

        $loader->load('services.xml');
    }
}
