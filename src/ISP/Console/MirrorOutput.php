<?php
/**
 * MirrorOutput.php
 *
 * @author: Aleksandr Chazov <develop@chazer.ru>
 * @created: 27.09.15 2:30
 */

namespace ISP\Console;

use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class MirrorOutput
 *
 * Write to multiple outputs
 *
 * @package Plugin\Console
 */
class MirrorOutput implements OutputInterface
{
    /**
     * @var OutputInterface[]
     */
    private $outputs = [];

    private $verbosity;

    private $decorated;

    private $formatter;

    public static function injectErrorOutput(ConsoleOutputInterface $consoleOutput, OutputInterface $output)
    {
        $err = $consoleOutput->getErrorOutput();
        $logMirror = new MirrorOutput([$output, $err]);
        $logMirror->setFormatter($err->getFormatter());
        $logMirror->setVerbosity($err->getVerbosity());
        $consoleOutput->setErrorOutput($logMirror);
    }

    /**
     * @param OutputInterface[] $outputs
     * @param OutputFormatterInterface $formatter
     */
    function __construct($outputs, OutputFormatterInterface $formatter = null)
    {
        $this->outputs = $outputs;
        $this->setFormatter($formatter ?: new OutputFormatter());
    }

    public function write($messages, $newline = false, $type = self::OUTPUT_NORMAL)
    {
        foreach ($this->outputs as $o) {
            $o->write($messages, $newline, $type);
        }
    }

    public function writeln($messages, $type = self::OUTPUT_NORMAL)
    {
        foreach ($this->outputs as $o) {
            $o->writeln($messages, $type);
        }
    }

    public function setVerbosity($level)
    {
        $this->verbosity = (int) $level;
        foreach ($this->outputs as $o) {
            $o->setVerbosity($this->verbosity);
        }
    }

    public function getVerbosity()
    {
        return $this->verbosity;
    }

    public function setDecorated($decorated)
    {
        $this->decorated = $decorated;
        foreach ($this->outputs as $o) {
            $o->setDecorated($decorated);
        }
    }

    public function isDecorated()
    {
        return $this->decorated;
    }

    public function setFormatter(OutputFormatterInterface $formatter)
    {
        $this->formatter = $formatter;
        foreach ($this->outputs as $o) {
            $o->setFormatter($formatter);
        }
    }

    public function getFormatter()
    {
        return $this->formatter;
    }
}
