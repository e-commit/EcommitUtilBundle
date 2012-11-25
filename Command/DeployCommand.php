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

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeployCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
                ->setName('ecommit:deploy')
                ->setDescription('Deploy the application')
                ->addArgument('server', InputArgument::REQUIRED, 'Server name')
                ->addOption('force', null, InputOption::VALUE_NONE, 'Execute the command')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //On parse le fichier rsync.ini
        $rsync_ini_file = $this->getContainer()->getParameter('kernel.root_dir').'/config/rsync.ini';
        if(!file_exists($rsync_ini_file))
        {
            $output->writeln(sprintf('<error>File %s does not exist</error>', $rsync_ini_file));
            return;
        }
        $result = parse_ini_file($rsync_ini_file, true);
        if (false === $result || array() === $result)
        {
            $output->writeln(sprintf('<error>File %s: Bad format</error>', $rsync_ini_file));
            return;
        }
        
        //On regarde si le serveur existe
        $server_name = $input->getArgument('server');
        if(!array_key_exists($server_name, $result))
        {
            $output->writeln(sprintf('<error>Server %s does not exist</error>', $server_name));
            return;
        }
        $server_infos = $result[$server_name];
        
        //On teste si les options sont presentes
        $options = array('host', 'port', 'user', 'dir');
        foreach($options as $option)
        {
            if(!array_key_exists($option, $server_infos))
            {
                $output->writeln(sprintf('<error>Missing %s option</error>', $option));
                return;
            }
        }
        
        //Repertoire de destination
        if (substr($server_infos['dir'], -1) != '/')
        {
            $server_infos['dir'] .= '/';
        }
        
        //Serveur
        $server_string = sprintf('%s@%s:%s', $server_infos['user'], $server_infos['host'], $server_infos['dir']);
        
        //Chaine SSH
        $ssh = sprintf('"ssh -p%s"', $server_infos['port']);

        //Parametres RSYNC
        $parameters = '-azC --force --delete --progress';
        
        //Rsync-exclude
        $rsync_exclude_file = $this->getContainer()->getParameter('kernel.root_dir').'/config/rsync_exclude.txt';
        if(file_exists($rsync_exclude_file))
        {
            $parameters .= sprintf(' --exclude-from=app/config/rsync_exclude.txt');
        }
        else
        {
            $dialog = $this->getHelperSet()->get('dialog');
            if (!$dialog->askConfirmation($output, '<question>Continue without rsync_exclude file?</question>', false))
            {
                return;
            }
        }
        
        //dry-run
        $dryRun = $input->getOption('force') ? '' : '--dry-run';
        
        //Commande
        $command = sprintf('rsync %s %s -e %s ./ %s', 
                $dryRun, 
                $parameters,
                $ssh,
                $server_string);
        
        $output->writeln(\passthru($command));
    }

}