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

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;

class EcommitUtilExtension extends Extension
{

    /**
     * Loads a specific configuration.
     *
     * @param array $config An array of configuration values
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @throws \InvalidArgumentException When provided tag is not defined in this extension
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        /*
         * IMPORTANT: There is no Configuration class. We will build manually 
         * the configuration array, above:
         */
        $caches = array();
        foreach ($configs as $config) {
            if (!empty($config['cache'])) {
                $caches = array_merge($caches, $config['cache']);
            }
        }

        foreach ($caches as $name => $options) {
            $service_name = sprintf('ecommit_cache_%s', $name);
            $container
                ->setDefinition($service_name, new DefinitionDecorator('ecommit_util.cache'))
                ->replaceArgument(0, $options)
                ->setPublic(true);
        }
    }
}
