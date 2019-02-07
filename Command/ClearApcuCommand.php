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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearApcuCommand extends Command
{
    protected $url;
    protected $username;
    protected $password;

    public function __construct($url, $username, $password)
    {
        $this->url = $url;
        $this->username = $username;
        $this->password = $password;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('ecommit:apcu:clear')
            ->setDescription('Clear APCU cache')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->url) {
            $output->writeln('<error>ecommit_util.clear_apcu.url is not defined</error>');

            return;
        }

        $output->writeln('Clearing APCU');

        $requestOptions = [
            'http_errors' => true,
        ];
        if ($this->username && $this->password) {
            $requestOptions['auth'] = [$this->username, $this->password];
        }

        $client = new \GuzzleHttp\Client();
        $client->request('GET', $this->url, $requestOptions);

        $output->writeln('<info>APCU was successfully cleared.</info>');
    }
}
