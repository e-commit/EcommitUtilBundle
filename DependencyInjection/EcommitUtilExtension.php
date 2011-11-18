<?php

/*
 * This file is part of the EcommitUtilBundle package.
 *
 * (c) Hubert LECORCHE <hlecorche@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\UtilBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class EcommitUtilExtension extends Extension 
{
    
    /**
     * Loads a specific configuration.
     *
     * @param array            $config    An array of configuration values
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
        $templates = array();
        foreach ($configs as $config)
        {
            if(!empty($config['cache']['templates']))
            {
                $templates = array_merge($templates, $config['cache']['templates']);
            }
        }
        
        foreach($templates as $name => $template)
        {
            $container->findDefinition('ecommit_cache.manager')->addMethodCall('setCacheTemplate', array($name, $template));
        }
    }
}
