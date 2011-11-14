<?php

/*
 * This file is part of the EcommitUtilBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\UtilBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class MaxCount extends Constraint
{
	public $message = 'You must select at most {{ limit }} choices';
    public $invalidMessage = 'This value should be an array';
    public $limit;

    /**
     * {@inheritDoc}
     */
    public function getDefaultOption()
    {
        return 'limit';
    }

    /**
     * {@inheritDoc}
     */
    public function getRequiredOptions()
    {
        return array('limit');
    }
}
