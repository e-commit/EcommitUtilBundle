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
use Symfony\Component\Filesystem\Filesystem;

/**
 * @deprecated Deprecated since version 2.4.
 */
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
        trigger_error('InstallCommand is deprecated since version 2.4.', E_USER_DEPRECATED);

        if (!$this->start($input, $output)) {
            return -1;
        }

        //Check lock
        $fs = new Filesystem();
        $lockPath = $this->getInstallLockPath();
        if ($lockPath && $fs->exists($lockPath)) {
            $output->writeln('<error>Aborting - Application already installed</error>');

            return 2;
        }

        $this->updateDoctrineSchema($output);
        $this->loadDoctrineFixtures($output);
        $this->addDoctrineMigrations($output);
        $this->dumpJsTranslations($output);
        $this->installAssets($output);
        $this->dumpAssetic($output);
        $this->createFMElfinderDir($output);
        $this->addInstallLockFile($output);

        $this->finish($input, $output);

        return 0;
    }
}
