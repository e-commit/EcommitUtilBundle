<?php

/**
 * This file is part of the EcommitUtilBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\UtilBundle\Command;

use Doctrine\Bundle\MigrationsBundle\Command\DoctrineCommand;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\OutputWriter;
use Ecommit\UtilBundle\Event\UpdateEvent;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader as DataFixturesLoader;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

abstract class AbstractUpdateCommand  extends ContainerAwareCommand
{
    protected $begin;
    protected $end;
    protected $isInteractive;

    /**
     * @return string
     */
    protected abstract function getBeforeEvent();

    /**
     * @return string
     */
    protected abstract function getAfterEvent();

    protected function start(InputInterface $input, OutputInterface $output)
    {
        $this->isInteractive = $input->isInteractive();
        if ($this->isInteractive) {
            $dialog = $this->getHelperSet()->get('question');
            $question = new ConfirmationQuestion('Continue with this action? [y/n] ', false);
            if (!$dialog->ask($input, $output, $question)) {
                $output->writeln('Aborting !');

                return false;
            }
        }
        $this->begin = \microtime(true);

        //Event
        $event = new UpdateEvent($input, $output);
        $this->getContainer()->get('event_dispatcher')->dispatch($this->getBeforeEvent(), $event);

        if ($event->getCancelDefaultBehavior()) {
            $this->finish($input, $output);

            return false;
        }

        return true;
    }

    protected function finish(InputInterface $input, OutputInterface $output)
    {
        //Event
        $event = new UpdateEvent($input, $output);
        $this->getContainer()->get('event_dispatcher')->dispatch($this->getAfterEvent(), $event);

        $this->end = \microtime(true);
        $duration = \ceil($this->end - $this->begin);
        $output->writeln("\n\n");
        $output->writeln(\sprintf("<info>Process is finished in %s second(s)</info>", $duration));
    }

    protected function updateDoctrineSchema(OutputInterface $output)
    {
        if (!$this->bundleIsRegistred('DoctrineBundle')) {
            return;
        }

        $output->writeln('<comment>Update Doctrine schema...</comment>');

        $command = $this->getApplication()->find('doctrine:schema:update');
        $arguments = array(
            'command' => 'doctrine:schema:update',
            '--force' => true,
        );
        $arrayInput = new ArrayInput($arguments);
        $arrayInput->setInteractive($this->isInteractive);
        $returnCode = $command->run($arrayInput, $output);
        if (0 !== $returnCode) {
            throw new \RuntimeException('Error');
        }
    }

    protected function loadDoctrineFixtures(OutputInterface $output)
    {
        if (!$this->bundleIsRegistred('DoctrineFixturesBundle')) {
            return;
        }

        $loader = new DataFixturesLoader($this->getContainer());
        foreach ($this->getContainer()->get('kernel')->getBundles() as $bundle) {
            $path = $bundle->getPath().'/DataFixtures/ORM';
            if (is_dir($path)) {
                $loader->loadFromDirectory($path);
            }
        }
        if (!$loader->getFixtures()) {
            return;
        }

        $output->writeln('<comment>Load Doctrine fixtures...</comment>');

        $command = $this->getApplication()->find('doctrine:fixtures:load');
        $arguments = array(
            'command' => 'doctrine:fixtures:load',
        );
        $arrayInput = new ArrayInput($arguments);
        $arrayInput->setInteractive($this->isInteractive);
        $returnCode = $command->run($arrayInput, $output);
        if (0 !== $returnCode) {
            throw new \RuntimeException('Error');
        }
    }

    protected function addDoctrineMigrations(OutputInterface $output)
    {
        if (!$this->bundleIsRegistred('DoctrineMigrationsBundle')) {
            return;
        }

        if (0 === count($this->getDotrineMigrations($output))) {
            return;
        }

        $output->writeln('<comment>Load Doctrine migrations...</comment>');

        $command = $this->getApplication()->find('doctrine:migrations:version');
        $arguments = array(
            'command' => 'doctrine:migrations:version',
            '--add' => true,
            '--all' => true,
        );
        $arrayInput = new ArrayInput($arguments);
        $arrayInput->setInteractive($this->isInteractive);
        $returnCode = $command->run($arrayInput, $output);
        if (0 !== $returnCode) {
            throw new \RuntimeException('Error');
        }
    }

    protected function migrateDoctrineMigrations(OutputInterface $output)
    {
        if (!$this->bundleIsRegistred('DoctrineMigrationsBundle')) {
            return;
        }

        if (0 === count($this->getDotrineMigrations($output))) {
            return;
        }

        $output->writeln('<comment>Migrate Doctrine migrations...</comment>');

        $command = $this->getApplication()->find('doctrine:migrations:migrate');
        $arguments = array(
            'command' => 'doctrine:migrations:migrate',
        );
        $arrayInput = new ArrayInput($arguments);
        $arrayInput->setInteractive($this->isInteractive);
        $returnCode = $command->run($arrayInput, $output);
        if (0 !== $returnCode) {
            throw new \RuntimeException('Error');
        }
    }

    protected function dumpJsTranslations(OutputInterface $output)
    {
        if (!$this->bundleIsRegistred('BazingaJsTranslationBundle')) {
            return;
        }

        $output->writeln('<comment>Dump JS translations...</comment>');

        $command = $this->getApplication()->find('bazinga:js-translation:dump');
        $arguments = array(
            'command' => 'bazinga:js-translation:dump',
        );
        $arrayInput = new ArrayInput($arguments);
        $arrayInput->setInteractive($this->isInteractive);
        $returnCode = $command->run($arrayInput, $output);
        if (0 !== $returnCode) {
            throw new \RuntimeException('Error');
        }
    }

    protected function installAssets(OutputInterface $output)
    {
        if (!$this->getContainer()->get('kernel')->getContainer()->get('assets.packages', ContainerInterface::NULL_ON_INVALID_REFERENCE)) {
            return;
        }

        $output->writeln('<comment>Install assets...</comment>');

        $webDir = \realpath($this->getContainer()->get('kernel')->getRootDir() . '/../web');
        $command = $this->getApplication()->find('assets:install');
        $arguments = array(
            'command' => 'assets:install',
            'target' => $webDir,
            '--relative' => true,
        );
        $arrayInput = new ArrayInput($arguments);
        $arrayInput->setInteractive($this->isInteractive);
        $returnCode = $command->run($arrayInput, $output);
        if (0 !== $returnCode) {
            throw new \RuntimeException('Error');
        }
    }

    protected function dumpAssetic(OutputInterface $output)
    {
        if (!$this->bundleIsRegistred('AsseticBundle')) {
            return;
        }

        $output->writeln('<comment>Dump assetic...</comment>');

        $command = $this->getApplication()->find('assetic:dump');
        $arguments = array(
            'command' => 'assetic:dump',
        );
        $arrayInput = new ArrayInput($arguments);
        $arrayInput->setInteractive($this->isInteractive);
        $returnCode = $command->run($arrayInput, $output);
        if (0 !== $returnCode) {
            throw new \RuntimeException('Error');
        }
    }

    protected function createFMElfinderDir(OutputInterface $output)
    {
        if (!$this->bundleIsRegistred('FMElfinderBundle')) {
            return;
        }

        $output->writeln('<comment>Create FMElfinder dir...</comment>');

        $fs = new Filesystem();
        $config = $this->getContainer()->getParameter('fm_elfinder');
        foreach ($config['instances'] as $instance) {
            foreach ($instance['connector']['roots'] as $root) {
                if ('LocalFileSystem' === $root['driver']) {
                    $dir = $this->getContainer()->get('kernel')->getRootDir().'/../web/'.$root['path'];
                    if (!$fs->exists($dir)) {
                        $fs->mkdir($dir, 0777);
                    }
                }
            }
        }
    }

    protected function clearApcu(OutputInterface $output)
    {
        if (!$this->getContainer()->getParameter('ecommit_util.clear_apcu.url')) {
            return;
        }

        $output->writeln('<comment>Clear APCU...</comment>');

        $requestOptions = [
            'http_errors' => true,
        ];
        if ($this->getContainer()->getParameter('ecommit_util.clear_apcu.username') && $this->getContainer()->getParameter('ecommit_util.clear_apcu.password')) {
            $requestOptions['auth'] = [$this->getContainer()->getParameter('ecommit_util.clear_apcu.username'), $this->getContainer()->getParameter('ecommit_util.clear_apcu.password')];
        }

        $client = new \GuzzleHttp\Client();
        $client->request('GET', $this->getContainer()->getParameter('ecommit_util.clear_apcu.url'), $requestOptions);
    }

    /**
     * @param string $bundleName
     * @return bool
     */
    protected function bundleIsRegistred($bundleName)
    {
        return array_key_exists($bundleName, $this->getContainer()->get('kernel')->getBundles());
    }

    /**
     * @param OutputInterface $output
     * @return array
     */
    protected function getDotrineMigrations(OutputInterface $output)
    {
        $outputWriter = new OutputWriter(function($message) use ($output) {
            return $output->writeln($message);
        });

        $configuration = new Configuration($this->getContainer()->get('doctrine.dbal.default_connection'), $outputWriter);
        DoctrineCommand::configureMigrations($this->getContainer(), $configuration);

        return $configuration->getAvailableVersions();
    }
}
