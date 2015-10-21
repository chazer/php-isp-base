<?php
/**
 * DummyCommand.php
 *
 * @author: Aleksandr Chazov <develop@chazer.ru>
 * @created: 14.10.15 13:23
 */

namespace ISP\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DummyCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output)
    {
    }
}
