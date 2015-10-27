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

    protected function initParams()
    {
        parent::initParams();
        $this->addParam('Debug', self::TYPE_BOOL, false);
        $this->addParam('Env', self::TYPE_STR_LIST);
    }

    /**
     * @return bool
     */
    public function isDebugMode()
    {
        return $this->getParam('debug');
    }

    /**
     * @return array
     */
    public function getEnvVariables()
    {
        return (array) $this->getParam('env');
    }
}
