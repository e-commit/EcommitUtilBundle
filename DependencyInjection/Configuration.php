<?php

/*
 * This file is part of the EcommitUtilBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\UtilBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ecommit_util');

        $rootNode
            ->children()
                ->arrayNode('clear_apcu')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('url')->defaultValue(null)->end()
                        ->scalarNode('username')->defaultValue(null)->end()
                        ->scalarNode('password')->defaultValue(null)->end()
                    ->end()
                ->end()
                ->scalarNode('install_lock_file')->defaultValue('%kernel.project_dir%/var/install.lock')->end()
                ->arrayNode('cache')
                    ->treatNullLike(array())
                    ->useAttributeAsKey('name')
                    ->prototype('variable')
                        ->treatNullLike(array())
                        ->validate()
                            ->ifTrue(function ($v) { return !is_array($v); })
                            ->thenInvalid('The util.cache config %s must be an array.')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}

