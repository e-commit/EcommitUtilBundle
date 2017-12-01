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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearApcuCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('ecommit:apcu:clear')
            ->setDescription('Clear APCU cache')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->getContainer()->getParameter('ecommit_util.clear_apcu.url')) {
            $output->writeln('<error>ecommit_util.clear_apcu.url is not defined</error>');
        }

        $output->writeln('Clearing APCU');

        $requestOptions = [
            'http_errors' => true,
        ];
        if ($this->getContainer()->getParameter('ecommit_util.clear_apcu.username') && $this->getContainer()->getParameter('ecommit_util.clear_apcu.password')) {
            $requestOptions['auth'] = [$this->getContainer()->getParameter('ecommit_util.clear_apcu.username'), $this->getContainer()->getParameter('ecommit_util.clear_apcu.password')];
        }

        $client = new \GuzzleHttp\Client();
        $client->request('GET', $this->getContainer()->getParameter('ecommit_util.clear_apcu.url'), $requestOptions);

        $output->writeln('<info>APCU was successfully cleared.</info>');
    }
}
