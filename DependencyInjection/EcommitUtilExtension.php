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

use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('commands.xml');

        $container->setParameter('ecommit_util.clear_apcu.url', $config['clear_apcu']['url']);
        $container->setParameter('ecommit_util.clear_apcu.username', $config['clear_apcu']['username']);
        $container->setParameter('ecommit_util.clear_apcu.password', $config['clear_apcu']['password']);

        $container->setParameter('ecommit_util.install_lock_file', $config['install_lock_file']);

        foreach ($config['cache'] as $name => $options) {
            $serviceName = sprintf('ecommit_cache_%s', $name);
            $container
                ->setDefinition($serviceName, new ChildDefinition('ecommit_util.cache'))
                ->replaceArgument(0, $options)
                ->setPublic(true);
        }
    }
}
