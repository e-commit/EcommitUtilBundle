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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeployCommand extends Command
{
    protected $projectDir;

    public function __construct($projectDir)
    {
        $this->projectDir = $projectDir;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('ecommit:deploy')
            ->setDescription('Deploy the application with RSYNC and SSH')
            ->addArgument('server', InputArgument::REQUIRED, 'Server name')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Execute the command');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Parse the rsync.ini file
        $rsyncIniFile = $this->projectDir . '/config/rsync.ini';
        if (!file_exists($rsyncIniFile)) {
            $output->writeln('<error>File sync.ini does not exist</error>');

            return 2;
        }

        $result = parse_ini_file($rsyncIniFile, true);
        if (false === $result || array() === $result) {
            $output->writeln(sprintf('<error>File %s: Bad format</error>', $rsyncIniFile));

            return 2;
        }

        // Check if the server exists
        $server_name = $input->getArgument('server');
        if (!array_key_exists($server_name, $result)) {
            $output->writeln(sprintf('<error>Server %s does not exist</error>', $server_name));

            return 2;
        }
        $server_infos = $result[$server_name];

        // Verify if the options are present
        $options = array('host', 'port', 'user', 'dir');
        foreach ($options as $option) {
            if (!array_key_exists($option, $server_infos)) {
                $output->writeln(sprintf('<error>Missing %s option</error>', $option));

                return 2;
            }
        }

        // Directory of destination
        if (substr($server_infos['dir'], -1) != '/') {
            $server_infos['dir'] .= '/';
        }

        // Server
        $server_string = sprintf('%s@%s:%s', $server_infos['user'], $server_infos['host'], $server_infos['dir']);

        // SSH query
        $ssh = sprintf('"ssh -p%s"', $server_infos['port']);

        // RSYNC parameters
        $parameters = '-azC --force --delete --progress';

        // RSYNC exclude option
        if (file_exists($this->projectDir . '/config/rsync_exclude.txt')) {
            $parameters .= sprintf(' --exclude-from=config/rsync_exclude.txt');
        } else {
            $dialog = $this->getHelperSet()->get('dialog');
            if (!$dialog->askConfirmation(
                $output,
                '<question>Continue without rsync_exclude file?</question>',
                false
            )
            ) {
                return -1;
            }
        }

        // RSYNC dry-run option
        $dryRun = $input->getOption('force') ? '' : '--dry-run';

        // Command
        $command = sprintf(
            'rsync %s %s -e %s ./ %s',
            $dryRun,
            $parameters,
            $ssh,
            $server_string
        );

        $output->writeln(\passthru($command));

        return 0;
    }

}
