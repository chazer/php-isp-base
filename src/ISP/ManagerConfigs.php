<?php
/**
 * ManagerConfigs.php
 *
 * @author: Aleksandr Chazov <develop@chazer.ru>
 * @created: 24.09.15 21:58
 */

namespace ISP;

use ISP\Configs\Configs;
use ISP\Interfaces\PluginAwareInterface;

class ManagerConfigs extends Configs implements PluginAwareInterface
{
    use PluginAware;

    function __construct($file)
    {
        $this->file = $file;
        parent::__construct($file, 'conf');
    }

    protected function initParams()
    {
        parent::initParams();
        $this->addParam('DBName', $this->getPlugin()->getManagerName());
        $this->addParam('DBHost', 'localhost');
        $this->addParam('DBUser', 'root');
        $this->addParam('DBPassword', null);
        $this->addParam('DBSocket', null);
    }

    public function getMySqlDSN()
    {
        $db = $this->getParam('DBName');
        $host = $this->getParam('DBHost');
        $user = $this->getParam('DBUser');
        $pass = $this->getParam('DBPassword');
        $unix = $this->getParam('DBSocket');

        if ($unix && file_exists($unix)) {
            $dsn = "mysql:unix_socket={$unix};dbname={$db}";
        } else {
            $dsn = "mysql:host={$host};port=3306;dbname={$db}";
        }
        return [$dsn, $user, $pass];
    }
}
