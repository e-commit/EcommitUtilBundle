<?php

/*
 * This file is part of the EcommitUtilBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Ecommit\UtilBundle\Command;

use Doctrine\Common\Util\Inflector;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class GenerateEntitiesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('ecommit:generate:entities')
            ->setDescription('Generate entities')
            ->addArgument('path', InputArgument::REQUIRED, 'Path in src folder (namespace or filename)')
            ->addOption('fix', 'f')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputPath = $input->getArgument('path');
        $fix = $input->getOption('fix');
        if (empty($inputPath)) {
            throw new \Exception('Missing path argument');
        }

        $path = $this->getContainer()->get('kernel')->getRootDir().'/../src/'.$inputPath;

        if (is_dir(realpath($path))) {
            //User input: Folder
            $this->generateDir($path, $fix, $output);
        } elseif (file_exists(realpath($path))) {
            //User input: File
            if (preg_match('/(.*\\/)(.+\\.php)/', $path, $matches)) {
                $filename = $matches[2];
            } else {
                throw new \Exception('File not found '.$path);
            }
            $this->generateFile($path, $filename, $fix, $output);
        } else {
            //User input not found: We test with .php extension
            $path = $path.'.php';
            if (file_exists(realpath($path)) && !is_dir(realpath($path))) {
                //File found
                if (preg_match('/(.*\\/)(.+\\.php)/', $path, $matches)) {
                    $filename = $matches[2];
                } else {
                    throw new \Exception('File not found '.$path);
                }
                $this->generateFile($path, $filename, $fix, $output);
            } else {
                //User input not found
                throw new \Exception('File not found '.$path);
            }
        }
    }

    protected function generateDir($dir, $fix, $output)
    {
        $finder = new Finder();
        $finder->files()->in($dir);

        foreach ($finder as $file) {
            $path = $file->getRealpath();
            $filename = $file->getFilename ();

            $this->generateFile($path, $filename, $fix, $output);
        }
    }

    protected function generateFile($path, $filename, $fix, $output)
    {
        //Only entitues (not repositories)
        if (preg_match('/(abstract)|(trait)|(interface)|(repository)/i', $filename)) {
            return;
        }

        if (!$this->testIfProcess($path)) {
            return;
        }

        if ($fix) {
            $this->fixConstrutor($path);
            $this->fixInverseSide($path);

            return;
        }

        //Pre process
        $this->preGenerate($path);

        //Process
        $srcePath = realpath($this->getContainer()->get('kernel')->getRootDir().'/../src/').'/';
        $classPath = realpath($path);
        $classPath = str_replace($srcePath, '', $classPath);
        $classPath = str_replace('.php', '', $classPath);

        $command = $this->getApplication()->find('doctrine:generate:entities');
        $arguments = array(
            'command' => 'doctrine:generate:entities',
            'name' => $classPath,
            '--no-backup' => true,
        );
        $input = new ArrayInput($arguments);
        $command->run($input, $output);

        //Post process
        $this->postGenerate($path);
    }

    protected function testIfProcess($path)
    {
        $content = file_get_contents($path);
        $content = str_replace("\r", "", $content);

        $pattern = '/@IgnoreGenerateEntities/iU';

        return !preg_match($pattern, $content);
    }

    protected function preGenerate($path)
    {
        $content = file_get_contents($path);

        $content = str_replace("\r", "", $content);

        //Delete comments in "extends" and "impements" (if found)
        $pattern = '/(class\s+[a-z0-9_-]+?)\\/\\*(.+)\\*\\/\\{/iUs';
        $content = preg_replace($pattern, "$1$2{", $content);

        //Comment "extends" and "impements"
        $pattern = '/(class\s+[a-z0-9_-]+?)(.*)\\{/iUs';
        $content = preg_replace($pattern, "$1/*$2*/{", $content);

        //Delete getters / setters
        $pattern = \sprintf('/(%s)(.*)\\}/is', $this->getHeaderGetterSetterPattern());
        if (!preg_match($pattern, $content)) {
            throw new \Exception('Header not found '.$path);
        }
        $content = preg_replace($pattern, '$1}', $content);

        file_put_contents($path, $content);
    }

    protected function postGenerate($path)
    {
        $content = file_get_contents($path);

        $content = str_replace("\r", "", $content);

        //Delete comments in "extends" and "impements"
        $pattern = '/(class\s+[a-z0-9_-]+?)\\/\\*(.+)\\*\\/\\{/iUs';
        $content = preg_replace($pattern, "$1$2{", $content);

        //Add "=null" in setters when foreign key is used
        $patternGettersSetters = \sprintf('/(%s)(.*)\\}/is', $this->getHeaderGetterSetterPattern());
        if (!preg_match($patternGettersSetters, $content, $matches)) {
            throw new \Exception('Header not found '.$path);
        }
        $gettersSetters = $matches[2];
        if (!empty($gettersSetters)) {
            $pattern = '/(function set[A-Z].+\\(.+ \\$[a-zA-Z_-]+)\\)/U';
            $gettersSetters = preg_replace($pattern, '$1 = null)', $gettersSetters);
            $content = preg_replace($patternGettersSetters, '$1'.$gettersSetters.'}', $content);
        }

        file_put_contents($path, $content);
    }

    protected function getHeaderGetterSetterPattern()
    {
        $stars = str_replace('*', '\\*', '* ***************************************');

        return '\\*        Getters \\/ Setters\n\s+\\*\n\s+'.$stars.'\n\s+\\*\\/\n';
    }

    protected function getFqcn($content, $path)
    {
        if (!preg_match('/class\s+([a-zA-Z_0-9-_]+)/im', $content, $matches)) {
            throw new \Exception('Class not found '.$path);
        }
        $class = $matches[1];
        if (!preg_match('/namespace\s+(.+);\n/im', $content, $matches)) {
            throw new \Exception('Namespace not found '.$path);
        }
        $namespace = $matches[1];

        return $namespace."\\".$class;
    }

    protected function fixConstrutor($path)
    {
        $content = file_get_contents($path);

        $content = str_replace("\r", "", $content);

        $fqcn = $this->getFqcn($content, $path);
        $class = new \ReflectionClass($fqcn);

        if (!$class->hasMethod('__construct')) {
            return;
        }

        $parent = $class->getParentClass();
        if (!$parent || !$parent->hasMethod('__construct')) {
            return;
        }

        $patternGettersSetters = \sprintf('/(%s)(.*)\\}/is', $this->getHeaderGetterSetterPattern());
        if (!preg_match($patternGettersSetters, $content, $matches)) {
            throw new \Exception('Header not found '.$path);
        }
        $gettersSetters = $matches[2];
        if (!empty($gettersSetters)) {
            $pattern = '/function\s+\_\_construct\s*\\(\\)\s*\{/Uim';
            $replace = "function __construct()\n    {\n        parent::__construct();\n";
            $gettersSetters = preg_replace($pattern, $replace, $gettersSetters);
            $content = preg_replace($patternGettersSetters, '$1'.$gettersSetters.'}', $content);

            file_put_contents($path, $content);
        }
    }

    protected function fixInverseSide($path)
    {
        $content = file_get_contents($path);

        $content = str_replace("\r", "", $content);

        $fqcn = $this->getFqcn($content, $path);

        $patternGettersSetters = \sprintf('/(%s)(.*)\\}/is', $this->getHeaderGetterSetterPattern());
        if (!preg_match($patternGettersSetters, $content, $matches)) {
            throw new \Exception('Header not found '.$path);
        }
        $gettersSetters = $matches[2];

        //Synchronization Own/Inverse sides
        $em = $this->getContainer()->get('doctrine')->getManager();
        $classMetadata = $em->getClassMetadata($fqcn);
        $associations = $classMetadata->associationMappings;

        foreach ($associations as $association) {
            //Synchronization Inverse -> Owning (OneToMany)
            if ($association['type'] == ClassMetadata::ONE_TO_MANY && $association['mappedBy']) {
                //Add
                $origin = \sprintf('$this->%s[] = $%s;', $association['fieldName'], $this->getVariableName('add', $association['fieldName']));
                $replace = \sprintf('$%s->%s($this);', $this->getVariableName('add', $association['fieldName']), $this->getMethodName('set', $association['mappedBy']))."\n";
                $replace .= \sprintf('        $this->%s->add($%s);', $association['fieldName'], $this->getVariableName('add', $association['fieldName']));
                $gettersSetters = preg_replace('/'.preg_quote($origin).'/', $replace, $gettersSetters);

                //Remove
                $origin = \sprintf('$this->%s->removeElement($%s);', $association['fieldName'], $this->getVariableName('remove', $association['fieldName']));
                $replace = '$0'."\n";
                $replace .= \sprintf('        $%s->%s(null);', $this->getVariableName('remove', $association['fieldName']), $this->getMethodName('set', $association['mappedBy']));
                $gettersSetters = preg_replace('/'.preg_quote($origin).'/', $replace, $gettersSetters);
            }

            //No synchronization for Owning -> Inverse (ManyToOne)

            //Synchronization ManyToMany Inverse -> Owning
            if ($association['type'] == ClassMetadata::MANY_TO_MANY && $association['mappedBy']) {
                //Add
                $origin = \sprintf('$this->%s[] = $%s;', $association['fieldName'], $this->getVariableName('add', $association['fieldName']));
                $replace = \sprintf('$%s->%s($this);', $this->getVariableName('add', $association['fieldName']), $this->getMethodName('add', $association['mappedBy']))."\n";
                $replace .= \sprintf('        $this->%s->add($%s);', $association['fieldName'], $this->getVariableName('add', $association['fieldName']));
                $gettersSetters = preg_replace('/'.preg_quote($origin).'/', $replace, $gettersSetters);

                //Remove
                $origin = \sprintf('$this->%s->removeElement($%s);', $association['fieldName'], $this->getVariableName('remove', $association['fieldName']));
                $replace = "$0\n";
                $replace .= \sprintf('        $%s->%s($this);', $this->getVariableName('remove', $association['fieldName']), $this->getMethodName('remove', $association['mappedBy']));
                $gettersSetters = preg_replace('/'.preg_quote($origin).'/', $replace, $gettersSetters);
            }

            //Synchronization ManyToMany Owning -> Inverse
            if ($association['type'] == ClassMetadata::MANY_TO_MANY && $association['inversedBy']) {
                //Add
                $origin = \sprintf('$this->%s[] = $%s;', $association['fieldName'], $this->getVariableName('add', $association['fieldName']));
                $replace = \sprintf('if (!$this->%s->contains($%s)) {', $association['fieldName'], $this->getVariableName('add', $association['fieldName']))."\n";
                $replace .= \sprintf('            $this->%s->add($%s);', $association['fieldName'], $this->getVariableName('add', $association['fieldName']))."\n";
                $replace .= "        }";
                $gettersSetters = preg_replace('/'.preg_quote($origin).'/', $replace, $gettersSetters);

                //Remove
                $origin = \sprintf('$this->%s->removeElement($%s);', $association['fieldName'], $this->getVariableName('remove', $association['fieldName']));
                $replace = \sprintf('if ($this->%s->contains($%s)) {', $association['fieldName'], $this->getVariableName('remove', $association['fieldName']))."\n";
                $replace .= \sprintf('            $this->%s->removeElement($%s);', $association['fieldName'], $this->getVariableName('remove', $association['fieldName']))."\n";
                $replace .= "        }";
                $gettersSetters = preg_replace('/'.preg_quote($origin).'/', $replace, $gettersSetters);
            }

            $content = preg_replace($patternGettersSetters, '$1'.$gettersSetters.'}', $content);

            file_put_contents($path, $content);
        }

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
