<?php
/**
 * This file is part of the EcommitUtilBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\UtilBundle\Util;

class Util
{
    /**
     * @param array $array
     * @return bool
     */
    public static function arrayChildrenAreScalar($array)
    {
        if (!is_array($array)) {
            return false;
        }

        foreach ($array as $child) {
            if (!is_scalar($child)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $array
     * @return array
     */
    public static function filterScalarValues($array)
    {
        return array_filter($array, function($child) {
            return is_scalar($child);
        });
    }
}
