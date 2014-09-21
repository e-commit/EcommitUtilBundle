<?php

/*
 * This file is part of the EcommitUtilBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\UtilBundle\Twig;

use Twig_Extension;
use Ecommit\UtilBundle\Helper\UtilHelper;
use Twig_SimpleFunction;

class UtilExtension extends Twig_Extension
{
    protected $utilHelper;


    /**
     * Constructor
     *
     * @param UtilHelper $utilHelper
     */
    public function __construct(UtilHelper $utilHelper)
    {
        $this->utilHelper = $utilHelper;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'ecommit_util_extension';
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions()
    {
        return array(
            new Twig_SimpleFunction(
                'ecommit_util_table',
                array($this, 'table'),
                array('is_safe' => array('all'))
            ),
        );
    }

    /**
     * Twig function: "ecommit_util_table"
     *
     * @see UtilHelper
     */
    public function table($values, $size, $tableOptions = array(), $trOptions = array(), $tdOptions = array())
    {
        return $this->utilHelper->table($values, $size, $tableOptions, $trOptions, $tdOptions);
    }
}
