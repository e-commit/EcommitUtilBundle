<?php

/*
 * This file is part of the EcommitUtilBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\UtilBundle\Helper;

use Symfony\Component\DependencyInjection\ContainerInterface;

class UtilHelper
{
    /**
     * ContainerInterface and not directly services: Because some services need request
     * (e.g.: "templating.helper.assets") and throw InactiveScopeException
     * 
     * @var ContainerInterface 
     */
    protected $container = null;
    
    /**
     * Constructor
     * 
     * @param ContainerInterface $container 
     */
    public function __construct(ContainerInterface $container) 
    {
        $this->container = $container;
    }
    
    /**
     * Gets a service.
     * WARNING ! Use this method only is required. NOT ABUSE.
     * 
     * @See ContainerInterface:get
     */
    public function get($id, $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE)
    {
        return $this->container->get($id, $invalidBehavior);
    }
    
    /**
     * Constructs an html tag
     * 
     * @param string $name Tag name
     * @param array $options Tag options
     * @param string $content Content
     * @param bool $open True to leave tag open
     * @return string 
     */
    public function tag($name, Array $options = array(), $content = null, $open = false)
    {
        if (!$name)
        {
            return '';
        }
        $options_html = '';
        foreach ($options as $key => $value)
        {
            $options_html .= ' '.$key.'="'.\htmlspecialchars($value, \ENT_COMPAT).'"';
        }
        if($content !== null)
        {
            return '<'.$name.$options_html.'>'.$content.'</'.$name.'>';
        }
        else
        {
            return '<'.$name.$options_html.(($open) ? '>' : ' />');
        }
    }
    
    /**
     * Escapes text for Javascript
     * 
     * @param string $javascript Input Text
     * @return string 
     */
    public function escape_javascript($javascript = '')
    {
      $javascript = preg_replace('/\r\n|\n|\r/', "\\n", $javascript);
      $javascript = preg_replace('/(["\'])/', '\\\\\1', $javascript);

      return $javascript;
    }
    
    /**
     * Translates text
     * 
     * @param string $text Input text
     * @return string 
     */
    public function translate($text)
    {
        $translator = $this->container->get('translator', ContainerInterface::NULL_ON_INVALID_REFERENCE);
        if(is_null($translator))
        {
            return $text;
        }
        return $translator->trans($text);
    }
    
    /**
     * Returns the public path.
     *
     * Absolute paths (i.e. http://...) are returned unmodified.
     *
     * @param string $path        A public path
     * @param string $packageName The name of the asset package to use
     *
     * @return string A public path which takes into account the base path and URL path
     */
    public function getAssetUrl($path, $packageName = null)
    {
        return $this->container->get('templating.helper.assets')->getUrl($path, $packageName);
    }
    
    public function table($values, $size, $table_options = array(),  $tr_options = array(), $td_options = array())
    {
        $count_values = \count($values);
        if(!is_array($values) || $count_values == 0)
        {
            return '';
        }
        if($count_values % $size > 0)
        {
            for($i=0; $i < ($size - $count_values % $size); $i++)
            {
                $values[] = '';
            }
        }
        
        $table_content = '';
        foreach(\array_chunk($values, $size) as $tr)
        {
            $tr_content = '';
            foreach($tr as $td)
            {
                $tr_content .= $this->tag('td', $td_options, $td);
            }
            $table_content .= $this->tag('tr', $tr_options, $tr_content);
        }
        return $this->tag('table', $table_options, $table_content);
    }
}