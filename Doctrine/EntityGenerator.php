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

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Util\Inflector;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Ecommit\UtilBundle\Annotations\IgnoreGenerateEntities;
use Symfony\Bridge\Doctrine\PropertyInfo\DoctrineExtractor;
use Twig\Environment;

class EntityGenerator
{
    /**
     * @var Registry
     */
    protected $doctrine;

    /**
     * @var Environment
     */
    protected $twig;

    /**
     * GenerateEntity constructor.
     *
     * @param Registry|null $doctrine
     * @param Environment   $twig
     */
    public function __construct(Registry $doctrine = null, Environment $twig)
    {
        $this->doctrine = $doctrine;
        $this->twig = $twig;
    }

    /**
     * @param string $class
     */
    public function generate($class)
    {
        if (null === $this->doctrine) {
            throw new \Exception('Doctrine is required');
        }

        $metadataFactory = $this->doctrine->getManager()->getMetadataFactory();
        if (!$metadataFactory->hasMetadataFor($class)) {
            return false;
        }
        $metadataClass = $metadataFactory->getMetadataFor($class);
        $reflectionClass = $metadataClass->getReflectionClass();
        //Ony entities
        if ($reflectionClass->isAbstract() || $reflectionClass->isInterface() || $reflectionClass->isTrait()) {
            return false;
        }

        $reader = new AnnotationReader();
        $annotation = $reader->getClassAnnotation($reflectionClass, IgnoreGenerateEntities::class);
        if ($annotation) {
            return false;
        }
        $annotation = $reader->getClassAnnotation($reflectionClass, MappedSuperclass::class);
        if ($annotation) {
            return false;
        }

        //Search block
        $fileContent = $this->clearFileContent(file_get_contents($reflectionClass->getFileName()));
        $blockStart = $this->getTemplateContent('block_start');
        $blockEnd = $this->getTemplateContent('block_end');
        $pattern = \sprintf('/^(?P<beforeBlock>.*)(?P<startBlock>%s)(?P<block>.*)(?P<endBlock>%s)(?P<afterBlock>.*)/is', preg_quote($blockStart, '/'), preg_quote($blockEnd, '/'));
        if (!preg_match($pattern, $fileContent, $contentParts)) {
            throw new \Exception('Block not found');
        }

        //Get properties
        $doctrineExtractor = new DoctrineExtractor($metadataFactory);
        $properties = $doctrineExtractor->getProperties($class);

        //Process
        $entityGeneratorResult = new EntityGeneratorResult($reflectionClass, $metadataClass, $contentParts);
        foreach ($properties as $property) {
            if (!$this->propertyIsDefinedInClass($entityGeneratorResult, $property)) {
                continue;
            }

            if ($reflectionClass->hasProperty($property) && $reflectionClass->getProperty($property)->isPublic()) {
                continue;
            }

            if ($metadataClass->hasField($property)) {
                $this->processField($entityGeneratorResult, $metadataClass->fieldMappings[$property]);
            } elseif ($metadataClass->hasAssociation($property)) {
                $this->processAssociation($entityGeneratorResult, $metadataClass->associationMappings[$property]);
            }
        }

        //Remove old content and add new content
        $newBlockContent = '';
        if (count($entityGeneratorResult->linesInConstructor) > 0) {
            $newBlockContent .= "\n" . $this->getTemplateContent('constructor', [
                'lines' => $entityGeneratorResult->linesInConstructor,
            ]);
        }
        foreach ($entityGeneratorResult->methods as $method) {
            $newBlockContent .= "\n" . $method;
        }
        $newFileContent = preg_replace($pattern, \sprintf('$1$2%s$4$5', $newBlockContent), $fileContent);
        file_put_contents($reflectionClass->getFileName(), $newFileContent);

        return true;
    }

    protected function processField(EntityGeneratorResult $entityGeneratorResult, $fieldMapping)
    {
        $fieldName = $fieldMapping['fieldName'];
        $doctrineType = $fieldMapping['type'];
        $variableType = $doctrineType ? $this->getPhpTypeFromDoctrineType($doctrineType) : null;
        $generateGetter = false;
        if (!isset($fieldMapping['id']) || !$fieldMapping['id'] || $entityGeneratorResult->metadataClass->generatorType == ClassMetadataInfo::GENERATOR_TYPE_NONE) {
            $generateGetter = true;
        }

        $methodName = $this->getMethodName('set', $fieldName);
        if ($generateGetter && !$this->methodIsDefinedInClass($entityGeneratorResult, $methodName)) {
            $entityGeneratorResult->methods[$methodName] = $this->getTemplateContent('field/default_set', [
                'fieldName' => $fieldName,
                'variableName' => $this->getVariableName('set', $fieldName),
                'variableType' => $variableType,
                'variableTypeHint' => '',
                'methodName' => $methodName,
                'entity' => $entityGeneratorResult->reflectionClass->getShortName(),
            ]);
        }

        $methodName = $this->getMethodName('get', $fieldName);
        if (!$this->methodIsDefinedInClass($entityGeneratorResult, $methodName)) {
            $entityGeneratorResult->methods[$methodName] = $this->getTemplateContent('field/default_get', [
                'fieldName' => $fieldName,
                'variableName' => $this->getVariableName('get', $fieldName),
                'variableType' => $variableType,
                'methodName' => $methodName,
            ]);
        }
    }

    protected function processAssociation(EntityGeneratorResult $entityGeneratorResult, $associationMapping)
    {
        if ($associationMapping['type'] & ClassMetadataInfo::TO_ONE) {
            $this->processAssociationToOne($entityGeneratorResult, $associationMapping);
        } elseif ($associationMapping['type'] & ClassMetadataInfo::TO_MANY) {
            $this->processAssociationToMany($entityGeneratorResult, $associationMapping);
        }
    }

    protected function processAssociationToOne(EntityGeneratorResult $entityGeneratorResult, $associationMapping)
    {
        $fieldName = $associationMapping['fieldName'];
        $targetEntity = '\\' . $associationMapping['targetEntity'];

        $methodName = $this->getMethodName('set', $fieldName);
        if (!$this->methodIsDefinedInClass($entityGeneratorResult, $methodName)) {
            $entityGeneratorResult->methods[$methodName] = $this->getTemplateContent('to_one/default_set', [
                'fieldName' => $fieldName,
                'variableName' => $this->getVariableName('set', $fieldName),
                'variableType' => $targetEntity,
                'variableTypeHint' => $targetEntity,
                'methodName' => $methodName,
                'entity' => $entityGeneratorResult->reflectionClass->getShortName(),
            ]);
        }

        $methodName = $this->getMethodName('get', $fieldName);
        if (!$this->methodIsDefinedInClass($entityGeneratorResult, $methodName)) {
            $entityGeneratorResult->methods[$methodName] = $this->getTemplateContent('to_one/default_get', [
                'fieldName' => $fieldName,
                'variableName' => $this->getVariableName('get', $fieldName),
                'variableType' => $targetEntity,
                'methodName' => $methodName,
            ]);
        }
    }

    protected function processAssociationToMany(EntityGeneratorResult $entityGeneratorResult, $associationMapping)
    {
        $fieldName = $associationMapping['fieldName'];
        $targetEntity = '\\' . $associationMapping['targetEntity'];

        if ($associationMapping['type'] & ClassMetadataInfo::ONE_TO_MANY && $associationMapping['mappedBy']) {
            $methodName = $this->getMethodName('add', $fieldName);
            if (!$this->methodIsDefinedInClass($entityGeneratorResult, $methodName)) {
                $entityGeneratorResult->methods[$methodName] = $this->getTemplateContent('to_many/one_to_many_reverse_add', [
                    'fieldName' => $fieldName,
                    'variableName' => $this->getVariableName('add', $fieldName),
                    'variableType' => $targetEntity,
                    'variableTypeHint' => $targetEntity,
                    'methodName' => $methodName,
                    'foreignMethodName' => $this->getMethodName('set', $associationMapping['mappedBy']),
                    'entity' => $entityGeneratorResult->reflectionClass->getShortName(),
                ]);

                $methodName = $this->getMethodName('remove', $fieldName);
                if (!$this->methodIsDefinedInClass($entityGeneratorResult, $methodName)) {
                    $entityGeneratorResult->methods[$methodName] = $this->getTemplateContent('to_many/one_to_many_reverse_remove', [
                        'fieldName' => $fieldName,
                        'variableName' => $this->getVariableName('remove', $fieldName),
                        'variableType' => $targetEntity,
                        'variableTypeHint' => $targetEntity,
                        'methodName' => $methodName,
                        'foreignMethodName' => $this->getMethodName('set', $associationMapping['mappedBy']),
                    ]);
                }
            }
        } elseif ($associationMapping['type'] & ClassMetadataInfo::MANY_TO_MANY && $associationMapping['mappedBy']) {
            $methodName = $this->getMethodName('add', $fieldName);
            $entityGeneratorResult->methods[$methodName] = $this->getTemplateContent('to_many/many_to_many_reverse_add', [
                'fieldName' => $fieldName,
                'variableName' => $this->getVariableName('add', $fieldName),
                'variableType' => $targetEntity,
                'variableTypeHint' => $targetEntity,
                'methodName' => $methodName,
                'foreignMethodName' => $this->getMethodName('add', $associationMapping['mappedBy']),
                'entity' => $entityGeneratorResult->reflectionClass->getShortName(),
            ]);

            $methodName = $this->getMethodName('remove', $fieldName);
            if (!$this->methodIsDefinedInClass($entityGeneratorResult, $methodName)) {
                $entityGeneratorResult->methods[$methodName] = $this->getTemplateContent('to_many/many_to_many_reverse_remove', [
                    'fieldName' => $fieldName,
                    'variableName' => $this->getVariableName('remove', $fieldName),
                    'variableType' => $targetEntity,
                    'variableTypeHint' => $targetEntity,
                    'methodName' => $methodName,
                    'foreignMethodName' => $this->getMethodName('remove', $associationMapping['mappedBy']),
                ]);
            }
        } elseif ($associationMapping['type'] & ClassMetadataInfo::MANY_TO_MANY && $associationMapping['inversedBy']) {
            $methodName = $this->getMethodName('add', $fieldName);
            $entityGeneratorResult->methods[$methodName] = $this->getTemplateContent('to_many/many_to_many_owning_add', [
                'fieldName' => $fieldName,
                'variableName' => $this->getVariableName('add', $fieldName),
                'variableType' => $targetEntity,
                'variableTypeHint' => $targetEntity,
                'methodName' => $methodName,
                'entity' => $entityGeneratorResult->reflectionClass->getShortName(),
            ]);

            $methodName = $this->getMethodName('remove', $fieldName);
            if (!$this->methodIsDefinedInClass($entityGeneratorResult, $methodName)) {
                $entityGeneratorResult->methods[$methodName] = $this->getTemplateContent('to_many/many_to_many_owning_remove', [
                    'fieldName' => $fieldName,
                    'variableName' => $this->getVariableName('remove', $fieldName),
                    'variableType' => $targetEntity,
                    'variableTypeHint' => $targetEntity,
                    'methodName' => $methodName,
                ]);
            }
        } else {
            $methodName = $this->getMethodName('add', $fieldName);
            if (!$this->methodIsDefinedInClass($entityGeneratorResult, $methodName)) {
                $entityGeneratorResult->methods[$methodName] = $this->getTemplateContent('to_many/default_add', [
                    'fieldName' => $fieldName,
                    'variableName' => $this->getVariableName('add', $fieldName),
                    'variableType' => $targetEntity,
                    'variableTypeHint' => $targetEntity,
                    'methodName' => $methodName,
                    'entity' => $entityGeneratorResult->reflectionClass->getShortName(),
                ]);
            }

            $methodName = $this->getMethodName('remove', $fieldName);
            if (!$this->methodIsDefinedInClass($entityGeneratorResult, $methodName)) {
                $entityGeneratorResult->methods[$methodName] = $this->getTemplateContent('to_many/default_remove', [
                    'fieldName' => $fieldName,
                    'variableName' => $this->getVariableName('remove', $fieldName),
                    'variableType' => $targetEntity,
                    'variableTypeHint' => $targetEntity,
                    'methodName' => $methodName,
                ]);
            }
        }

        $methodName = $this->getMethodName('get', $fieldName);
        if (!$this->methodIsDefinedInClass($entityGeneratorResult, $methodName)) {
            $entityGeneratorResult->methods[$methodName] = $this->getTemplateContent('to_many/default_get', [
                'fieldName' => $fieldName,
                'variableName' => $this->getVariableName('get', $fieldName),
                'methodName' => $methodName,
            ]);
        }

        $entityGeneratorResult->linesInConstructor[] = \sprintf('$this->%s = new \Doctrine\Common\Collections\ArrayCollection();', $this->getVariableName('set', $fieldName));
    }

    protected function propertyIsDefinedInClass(EntityGeneratorResult $entityGeneratorResult, $property)
    {
        $testClass = $entityGeneratorResult->reflectionClass;
        while ($parentReflectionClass = $testClass->getParentClass()) {
            if ($parentReflectionClass->hasProperty($property)) {
                return false;
            }
            $testClass = $parentReflectionClass;
        }
        foreach ($entityGeneratorResult->reflectionClass->getTraits() as $traitReflectionClass) {
            if ($traitReflectionClass->hasProperty($property)) {
                return false;
            }
        }

        return true;
    }

    protected function methodIsDefinedInClass(EntityGeneratorResult $entityGeneratorResult, $method)
    {
        if (preg_match(\sprintf('/function %s\(/is', preg_quote($method)), $entityGeneratorResult->contentParts['beforeBlock'])) {
            return true;
        }
        if (preg_match(\sprintf('/function %s\(/is', preg_quote($method)), $entityGeneratorResult->contentParts['afterBlock'])) {
            return true;
        }

        return false;
    }

    protected function getTemplateContent($templateName, $options = [])
    {
        $templatePath = \sprintf('@EcommitUtil/entity_generator/%s.txt.twig', $templateName);
        $content = $this->twig->render($templatePath, $options);

        return $this->clearFileContent($content);
    }

    protected function clearFileContent($content)
    {
        $content = str_replace("\r", "", $content);

        return $content;
    }

    protected function getPhpTypeFromDoctrineType($type)
    {
        $doctrineToPhpTypes = [
            Type::DATETIMETZ    => '\DateTime',
            Type::DATETIME      => '\DateTime',
            Type::DATE          => '\DateTime',
            Type::TIME          => '\DateTime',
            Type::OBJECT        => '\stdClass',
            Type::BIGINT        => 'int',
            Type::SMALLINT      => 'int',
            Type::INTEGER       => 'int',
            Type::TEXT          => 'string',
            Type::BLOB          => 'string',
            Type::DECIMAL       => 'string',
            Type::JSON_ARRAY    => 'array',
            Type::SIMPLE_ARRAY  => 'array',
            Type::BOOLEAN       => 'bool',
        ];

        if (array_key_exists($type, $doctrineToPhpTypes)) {
            return $doctrineToPhpTypes[$type];
        }

        return $type;
    }

    protected function getMethodName($type, $fieldName)
    {
        $methodName = $type . Inflector::classify($fieldName);
        if (in_array($type, array('add', 'remove'))) {
            $methodName = Inflector::singularize($methodName);
        }

        return $methodName;
    }

    protected function getVariableName($type, $fieldName)
    {
        $variableName = Inflector::camelize($fieldName);
        if (in_array($type, array('add', 'remove'))) {
            $variableName = Inflector::singularize($variableName);
        }

        return $variableName;
    }
}
