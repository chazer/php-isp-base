<?php
/**
 * PluginBase.php
 *
 * @author: Aleksandr Chazov <develop@chazer.ru>
 * @created: 26.09.15 18:30
 */

namespace ISP;

use ISP\Commands\CGIWrapper;
use ISP\Commands\DummyCommand;
use ISP\Console\MirrorOutput;
use ISP\Interfaces\PluginAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

defined('MGR_NAME') or define('MGR_NAME', 'ispmgr');
defined('MGR_ROOT') or define('MGR_ROOT', getcwd());
define('MGR_CONFIG_FILE', 'etc/' . MGR_NAME . '.conf');
define('PLUGIN_NAME', 'noname');
define('PLUGIN_LOG_FILE', 'var/plugin_' . PLUGIN_NAME . '.log');
define('CONFIG_FILE', 'etc/plugin_' . PLUGIN_NAME . '.conf');
define('CONFIG_FORMAT', 'conf');

class PluginBase
{
    const CMD_BEFORE_INSTALL = 'preinstall';
    const CMD_AFTER_INSTALL = 'postinstall';
    const CMD_BEFORE_UPDATE = 'preupdate';
    const CMD_AFTER_UPDATE = 'postupdate';
    const CMD_BEFORE_UNINSTALL = 'predelete';
    const CMD_CGI = 'run-cgi';

    /** @var PluginConfigs */
    private $configs;

    /** @var string */
    public $configFile;

    /** @var string */
    private $mgrName;


    /** @var ManagerConfigs */
    private $mgrConfigs;

    /** @var string */
    public $mgrConfigFile;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @var boolean
     */
    public $isInstalled;

    /**
     * @var boolean
     */
    public $isCGI;

    private $logOutput;

    /**
     * @var callable
     */
    private $webApplicationRunner;

    /**
     * @var callable
     */
    private $consoleApplication ;

    /**
     * Create new plugin instance
     *
     * @return PluginBase
     */
    public static function createInstance()
    {
        $plugin = new self();
        $plugin->init();
        return $plugin;
    }

    /**
     * Return manager root path
     *
     * @return string
     */
    public function getMgrRoot()
    {
        return rtrim(MGR_ROOT, '/\\') . DIRECTORY_SEPARATOR;
    }

    /**
     * @param string $mgrName
     */
    public function setManagerName($mgrName)
    {
        $this->mgrName = $mgrName;
    }

    /**
     * @return string
     */
    public function getManagerName()
    {
        return $this->mgrName;
    }

    /**
     * Init plugin instance before run
     */
    public function init()
    {
        isset($this->mgrName) || $this->mgrName = MGR_NAME;
        $this->configFile || $this->configFile = CONFIG_FILE;
        $this->mgrConfigFile || $this->mgrConfigFile = MGR_CONFIG_FILE;
        $this->isInstalled = true;
        $this->isCGI = 'cli' !== php_sapi_name();
    }

    /**
     * Get plugin config object
     *
     * @return bool|PluginConfigs
     */
    public function getConfigs()
    {
        if (!$this->isInstalled)
            return false;

        if (isset($this->configs))
            return $this->configs;

        $this->configs = new PluginConfigs($this->getMgrRoot() . $this->configFile, CONFIG_FORMAT);
        $this->processPluginAware($this->configs);
        if (!$this->configs->load()) {
            $this->getLogger()->warning('Plugin config not loaded');
        }
        return $this->configs;
    }

    /**
     * Get manager config object
     *
     * @return false|ManagerConfigs
     */
    public function getMgrConfigs()
    {
        if (!$this->isInstalled)
            return false;

        if (isset($this->mgrConfigs))
            return $this->mgrConfigs;

        $this->mgrConfigs = new ManagerConfigs($this->getMgrRoot() . $this->mgrConfigFile);
        $this->processPluginAware($this->mgrConfigs);
        if (!$this->mgrConfigs->load()) {
            $this->getLogger()->warning('Manager config not loaded');
        }
        return $this->mgrConfigs;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        if (isset($this->logger))
            return $this->logger;

        $output = new StreamOutput(fopen('php://stderr', 'w'));
        $logger = $this->createLogger($output);
        $this->processPluginAware($logger);
        return $this->logger = $logger;
    }

    /**
     * @param LoggerInterface $logger
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->processPluginAware($logger);
        $this->logger = $logger;
        return $this;
    }

    /**
     * @param OutputInterface $output
     * @return ConsoleLogger
     */
    protected function createLogger(OutputInterface $output)
    {
        return new ConsoleLogger($output);
    }

    /**
     * @return resource
     */
    public function openLogStream()
    {
        $logFile = PLUGIN_LOG_FILE;
        $logDir = dirname($logFile);
        is_dir($logDir) || mkdir($logDir, 0777, true);

        return fopen($logFile, 'a');
    }

    /**
     * @return StreamOutput
     */
    public function getLogOutput()
    {
        return $this->logOutput ? : $this->logOutput = new StreamOutput($this->openLogStream());
    }

    /**
     * @param OutputInterface $output
     */
    public function setLogOutput(OutputInterface $output)
    {
        $this->logOutput = $output;
    }

    /**
     * The main method for run plugin
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        isset($_SERVER['argv']) || $_SERVER['argv'] = [];

        $input = $input ? : new ArgvInput();
        $output = $output ? : new ConsoleOutput();

        if (PluginBase::CMD_BEFORE_INSTALL === ($command = $input->getFirstArgument())) {
            $this->isInstalled = false;
        }

        $logOutput = $this->getLogOutput();
        MirrorOutput::injectErrorOutput($output, $logOutput);
        $this->setLogger($this->createLogger($output->getErrorOutput()));

        $isDebug = $this->isInstalled && $this->getConfigs()->isDebugMode();
        $isDebug && $logOutput->setVerbosity(OutputInterface::VERBOSITY_DEBUG);

        if (null !== ($command = $input->getFirstArgument())) {
            $logOutput->writeln('Run command: ' . $command);
        }

        try {
            $exitCode = 0;
            if ($this->isCGI) {
                // Run as CGI, ignore internal commands
                $this->runWebApplication();
            } else {
                $cmd = $input->getFirstArgument();
                if ($this->isInternalCommand($cmd)) {
                    $exitCode = $this->runInternalCommands($input, $output);
                } else {
                    $exitCode = $this->runConsoleApplication($input, $output);
                }
            }
        } catch (\Exception $e) {
            $output->getErrorOutput()->writeln($e->getMessage());
            $exitCode = $e->getCode() ?: 1;
        }

        exit($exitCode);
    }

    /**
     * @param $command
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Exception
     */
    public function runInternal($command, InputInterface $input = null, OutputInterface $output = null)
    {
        if (!$this->isInternalCommand($command)) {
            throw new \Exception('Unsupported command ' . $command);
        }

        isset($_SERVER['argv']) || $_SERVER['argv'] = [];
        if ('cli' === php_sapi_name()) {
            //$app = array_shift($_SERVER['argv']);
            //array_unshift($_SERVER['argv'], $command);
            //$_SERVER['argc'] = array_unshift($_SERVER['argv'], $app);
            $app = isset($_SERVER['argv'][0]) ? $_SERVER['argv'][0] : '';
            $input = new ArgvInput([$app, $command]);
            unset($app);
        }

        $this->run($input, $output);
    }

    /**
     * @return Command[]
     */
    protected function getPluginCommands()
    {
        return [
            new CGIWrapper(self::CMD_CGI),
            new DummyCommand(self::CMD_BEFORE_UPDATE),
            new DummyCommand(self::CMD_AFTER_UPDATE),
            new DummyCommand(self::CMD_BEFORE_INSTALL),
            new DummyCommand(self::CMD_AFTER_INSTALL),
            new DummyCommand(self::CMD_BEFORE_UNINSTALL),
        ];
    }

    /**
     * @param Command[] $array1 Initial array to merge.
     * @param Command[] $array2 [optional]
     * @param Command[] $_ [optional]
     * @return Command[]
     */
    protected function mergeCommandsArray($array1, $array2 = null, $_ = null)
    {
        $result = [];
        foreach (func_get_args() as $array) {
            /** @var Command[] $array */
            foreach ($array as $command) {
                $result[$command->getName()] = $command;
            }
        }
        return array_values($result);
    }

    /**
     * @param string $command
     * @return bool
     */
    private function isInternalCommand($command)
    {
        return in_array($command, [
            self::CMD_CGI,
            self::CMD_BEFORE_UPDATE,
            self::CMD_AFTER_UPDATE,
            self::CMD_BEFORE_INSTALL,
            self::CMD_AFTER_INSTALL,
            self::CMD_BEFORE_UNINSTALL,
        ]);
    }

    /**
     * Configure plugin aware objects
     *
     * @param mixed $value
     */
    protected function processPluginAware($value)
    {
        is_array($value) || $value = [&$value];
        foreach ($value as &$v) {
            if ($v instanceof PluginAwareInterface) {
                $v->setPlugin($this);
            }
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function runInternalCommands(InputInterface $input = null, OutputInterface $output = null)
    {
        $app = new \Symfony\Component\Console\Application();
        $commands = $this->getPluginCommands();
        $this->processPluginAware($commands);
        $app->addCommands($commands);
        $app->setAutoExit(false);
        return $app->run($input, $output);
    }

    /**
     * @param callable|string $runner
     * @throws \Exception
     */
    public function setWebApplicationRunner($runner)
    {
        if (!is_file($runner) && !is_callable($runner))
            throw new \Exception('Callable expected');

        $this->webApplicationRunner = $runner;
    }

    /**
     * @return mixed|null
     * @throws \Exception
     */
    protected function runWebApplication()
    {
        if (isset($this->webApplicationRunner)) {
            if (is_file($this->webApplicationRunner)) {
                ob_start();
                include $this->webApplicationRunner;
                ob_end_flush();
                return null;
            } else {
                $this->processPluginAware($this->webApplicationRunner);
                return call_user_func($this->webApplicationRunner);
            }
        }
        throw new \Exception('Web application runner is not defined');
    }

    /**
     * @param callable|string $runner
     * @throws \Exception
     */
    public function setConsoleApplicationRunner($runner)
    {
        if (!is_file($runner) && !is_callable($runner))
            throw new \Exception('Callable expected');

        $this->consoleApplication = $runner;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int exit code
     * @throws \Exception
     */
    protected function runConsoleApplication(InputInterface $input = null, OutputInterface $output = null)
    {
        if (isset($this->consoleApplication)) {
            if (is_file($this->consoleApplication)) {
                return include $this->consoleApplication;
            } else {
                $this->processPluginAware($this->webApplicationRunner);
                $exitCode = call_user_func($this->consoleApplication, $input, $output);
                return ctype_digit($exitCode) ? (int) $exitCode : 0;
            }
        }
        throw new \Exception('Console application runner is not defined');
    }
}
