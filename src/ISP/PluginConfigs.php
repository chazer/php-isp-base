<?php
/**
 * Configs.php
 *
 * @author: Aleksandr Chazov <develop@chazer.ru>
 * @created: 26.09.15 16:41
 */

namespace ISP;

use ISP\Configs\Configs;
use ISP\Interfaces\PluginAwareInterface;

class PluginConfigs extends Configs implements PluginAwareInterface
{
    use PluginAware;

    protected $params = [
        'debug' => 'On',
    ];

    /**
     * @return bool
     */
    public function isDebugMode()
    {
        return $this->getBoolParam('debug', false);
    }

    /**
     * @return array
     */
    public function getEnvVariables()
    {
        return (array) $this->getParam('env');
    }
}
