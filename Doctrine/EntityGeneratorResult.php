<?php

/*
 * This file is part of the EcommitUtilBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\UtilBundle\Doctrine;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

class EntityGeneratorResult
{
    /**
     * @var \ReflectionClass
     */
    public $reflectionClass;

    /**
     * @var ClassMetadataInfo
     */
    public $metadataClass;

    /**
     * @var array
     */
    public $contentParts = [];

    public $linesInConstructor = [];

    public $methods = [];

    /**
     * EntityGeneratorResult constructor.
     *
     * @param \ReflectionClass  $reflectionClass
     * @param ClassMetadataInfo $metadataClass
     * @param array             $contentParts
     */
    public function __construct(\ReflectionClass $reflectionClass, ClassMetadataInfo $metadataClass, array $contentParts)
    {
        $this->reflectionClass = $reflectionClass;
        $this->metadataClass = $metadataClass;
        $this->contentParts = $contentParts;
    }
}
