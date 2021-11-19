<?php

declare(strict_types=1);

namespace Vinium\SyliusUp2PayPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('vinium_sylius_up2pay_plugin');
        $rootNode = $treeBuilder->getRootNode();

        return $treeBuilder;
    }
}
