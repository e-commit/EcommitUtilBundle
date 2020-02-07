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

/**
 * @deprecated Deprecated since version 2.4.
 */
class UpgradeCommand extends AbstractUpdateCommand
{
    protected function configure()
    {
        $this
            ->setName('ecommit:upgrade')
            ->setDescription('Upgrade the application');
    }

    /**
     * {@inheritdoc}
     */
    protected function getBeforeEvent()
    {
        return UpdateEvents::PRE_UPGRADE;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAfterEvent()
    {
        return UpdateEvents::POST_UPGRADE;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        trigger_error('UpgradeCommand is deprecated since version 2.4.', E_USER_DEPRECATED);

        if (!$this->start($input, $output)) {
            return -1;
        }

        $this->clearApcu($output);
        $this->migrateDoctrineMigrations($output);
        $this->dumpJsTranslations($output);
        $this->installAssets($output);
        $this->dumpAssetic($output);
        $this->createFMElfinderDir($output);
        $this->addInstallLockFile($output);

        $this->finish($input, $output);

        return 0;
    }
}
