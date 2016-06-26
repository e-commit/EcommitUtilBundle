<?php
/**
 * This file is part of the EcommitUtilBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\UtilBundle\Event;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\Event;

class UpdateEvent extends Event
{
    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    protected $cancelDefaultBehavior = false;

    /**
     * UpdateEvent constructor.
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * @return InputInterface
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @return boolean
     */
    public function getCancelDefaultBehavior()
    {
        return $this->cancelDefaultBehavior;
    }

    /**
     * @param boolean $cancelDefaultBehavior
     */
    public function setCancelDefaultBehavior($cancelDefaultBehavior)
    {
        $this->cancelDefaultBehavior = $cancelDefaultBehavior;
    }
}
