<?php
/**
 * CGIWrapper.php
 *
 * @author: Aleksandr Chazov <develop@chazer.ru>
 * @created: 24.09.15 14:01
 */

namespace ISP\Commands;

use ISP\Interfaces\PluginAwareInterface;
use ISP\PluginAware;
use ISP\PluginBase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class CGIWrapper extends Command implements PluginAwareInterface
{
    use PluginAware;

    protected function configure()
    {
        $this->setName(PluginBase::CMD_CGI);
    }

    protected function getInitialScriptFile()
    {
        $stack = debug_backtrace();
        $firstFrame = $stack[count($stack) - 1];
        $initialFile = $firstFrame['file'];
        return $initialFile;
    }

    protected function runCGI(InputInterface $input, OutputInterface $output, $script = null, $env = null)
    {
        $env || $env = [];

        isset($script) && $env[] = 'SCRIPT_FILENAME=' . $script;

        if (is_array($env)) {
            foreach ($env as $e) {
                putenv($e);
            }
        }

        if ('POST' === getenv('REQUEST_METHOD')) {
            $input = file_get_contents('php://stdin', 'r');
        } else {
            $input = '';
        }

        if ('GET' === getenv('REQUEST_METHOD')) {
            putenv('CONTENT_LENGTH=0');
        }

        $process = new Process('php-cgi', null, null, $input);

        $exitCode = $process->run(function ($type, $buffer) use (&$counter, $output) {
            if (Process::ERR === $type) {
                if ($output instanceof ConsoleOutputInterface) {
                    $output->getErrorOutput()->write($buffer);
                } else {
                    $output->write($buffer);
                }
            } else {
                $output->write($buffer, false, OutputInterface::OUTPUT_RAW);
            }
        });

        is_resource($input) && fclose($input);
        return $exitCode;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $env = $this->getPlugin()
            ->getConfigs()
            ->getEnvVariables();

        return $this->runCGI(
            $input,
            $output,
            $this->getInitialScriptFile(),
            $env
        );
    }
}
