<?php

/*
 * This file is part of the EcommitUtilBundle package.
 *
 * (c) Hubert LECORCHE <hlecorche@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\UtilBundle\Helper\Twig;

use Twig_Extension;
use Twig_Function_Method;
use Ecommit\UtilBundle\Helper\UtilHelper;
use Twig_Environment;

class UtilExtension extends Twig_Extension
{
    protected $util_helper;
    
    
    /**
     * Constructor
     * 
     * @param UtilHelper $util_helper 
     */
    public function __construct(UtilHelper $util_helper)
    {
        $this->util_helper = $util_helper;
    }
    
    /**
    * Returns the name of the extension.
    *
    * @return string The extension name
    */
    public function getName()
    {
        return 'ecommit_util';
    }
    
    /**
    * Returns a list of global functions to add to the existing list.
    *
    * @return array An array of global functions
    */
    public function getFunctions()
    {
        return array(
            'util_table' => new Twig_Function_Method($this, 'table', array('is_safe' => array('all'))),
        );
    }
    
    /**
     * Twig function: "util_table"
     *  
     * @see UtilHelper:table
     */
    public function table($values, $size, $table_options = array(),  $tr_options = array(), $td_options = array())
    {
        return $this->util_helper->table($values, $size, $table_options, $tr_options, $td_options);
    }
}