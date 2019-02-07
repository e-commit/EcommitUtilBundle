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

use Doctrine\ORM\Mapping\ClassMetadata;
use Ecommit\UtilBundle\Doctrine\EntityGenerator;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateEntitiesCommand extends Command
{
    /**
     * @var EntityGenerator
     */
    protected $entityGenerator;

    /**
     * @var Registry
     */
    protected $doctrine;

    public function __construct(EntityGenerator $entityGenerator, RegistryInterface $doctrine = null)
    {
        $this->entityGenerator = $entityGenerator;
        $this->doctrine = $doctrine;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('ecommit:generate:entities')
            ->setDescription('Generate entities')
            ->addArgument('path', InputArgument::REQUIRED, 'Class or namepace')
            ->addOption('fix', 'f')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (null === $this->doctrine) {
            throw new \Exception('Doctrine is required');
        }

        if ($input->getOption('fix')) {
            $output->writeln('<error><Fix option is deprecated since version 2.4.</error>');
            trigger_error('Fix option is deprecated since version 2.4.', E_USER_DEPRECATED);
        }

        $path = $input->getArgument('path');
        $path = str_replace('/', '\\', $path);

        $metadataFactory = $this->doctrine->getManager()->getMetadataFactory();

        /** @var ClassMetadata $metadata */
        foreach ($metadataFactory->getAllMetadata() as $metadata) {
            $className = $metadata->getReflectionClass()->getName();
            if (!preg_match(\sprintf('/^%s/', preg_quote($path)), $className)) {
                continue;
            }

            $output->writeln(\sprintf('> %s', $className));
            if ($this->entityGenerator->generate($className)) {
                $output->writeln('    <info>OK</info>');
            } else {
                $output->writeln('    <error>Ignored</error>');
            }
        }
    }
}
