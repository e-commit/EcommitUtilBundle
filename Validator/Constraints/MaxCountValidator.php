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
use Symfony\Component\Validator\ConstraintValidator;

class MaxCountValidator extends ConstraintValidator
{
    /**
     * Checks if the passed value is valid.
     *
     * @param mixed      $value      The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     *
     * @return Boolean Whether or not the value is valid
     *
     * @api
     */
    public function validate($value, Constraint $constraint)
    {
        if(empty($value))
        {
            return;
        }
        
        if(!is_array($value))
        {
            $this->context->addViolation($constraint->invalidMessage);
            return;
        }
        
        if(count($value) > $constraint->limit)
        {
            $this->context->addViolation($constraint->message, array('{{ limit }}' => $constraint->limit));
            return;
        }
        
        return;
    }
}
