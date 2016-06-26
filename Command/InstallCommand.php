<?php

/*
 * This file is part of the EcommitUtilBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\UtilBundle\Command;

use Ecommit\UtilBundle\Event\UpdateEvents;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCommand extends AbstractUpdateCommand
{
    protected function configure()
    {
        $this
            ->setName('ecommit:install')
            ->setDescription('Install the application');
    }

    /**
     * {@inheritdoc}
     */
    protected function getBeforeEvent()
    {
        return UpdateEvents::PRE_INSTALL;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAfterEvent()
    {
        return UpdateEvents::POST_INSTALL;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->start($input, $output)) {
            return;
        }

        $this->updateDoctrineSchema($output);
        $this->loadDoctrineFixtures($output);
        $this->addDoctrineMigrations($output);
        $this->dumpJsTranslations($output);
        $this->installAssets($output);
        $this->dumpAssetic($output);
        $this->createFMElfinderDir($output);

        $this->finish($input, $output);
    }
}
