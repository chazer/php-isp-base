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

    public function getMySqlDSN()
    {
        $db = $this->getParam('DBName', $this->getPlugin()->getManagerName());
        $host = $this->getParam('DBHost', 'localhost');
        $user = $this->getParam('DBUser', 'root');
        $pass = $this->getParam('DBPassword', null);
        $unix = $this->getParam('DBSocket', null);

        if ($unix && file_exists($unix)) {
            $dsn = "mysql:unix_socket={$unix};dbname={$db}";
        } else {
            $dsn = "mysql:host={$host};port=3306;dbname={$db}";
        }
        return [$dsn, $user, $pass];
    }
}
