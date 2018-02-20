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
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateEntitiesCommand extends ContainerAwareCommand
{
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
        if ($input->getOption('fix')) {
            $output->writeln('<error><Fix option is deprecated since version 2.4.</error>');
            trigger_error('Fix option is deprecated since version 2.4.', E_USER_DEPRECATED);
        }

        $path = $input->getArgument('path');
        $path = str_replace('/', '\\', $path);

        $metadataFactory = $this->getContainer()->get('doctrine')->getManager()->getMetadataFactory();
        $generator = $this->getContainer()->get('ecommit_util.entity_generator');

        /** @var ClassMetadata $metadata */
        foreach ($metadataFactory->getAllMetadata() as $metadata) {
            $className = $metadata->getReflectionClass()->getName();
            if (!preg_match(\sprintf('/^%s/', preg_quote($path)), $className)) {
                continue;
            }

            $output->writeln(\sprintf('> %s', $className));
            if ($generator->generate($className)) {
                $output->writeln('    <info>OK</info>');
            } else {
                $output->writeln('    <error>Ignored</error>');
            }
        }
    }
}
