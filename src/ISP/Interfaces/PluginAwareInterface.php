<?php
/**
 * PluginAwareInterface.php
 *
 * @author: Aleksandr Chazov <develop@chazer.ru>
 * @created: 21.10.15 22:25
 */

namespace ISP\Interfaces;

use ISP\PluginBase;

interface PluginAwareInterface
{
    /**
     * @param PluginBase $plugin
     */
    public function setPlugin($plugin);

    /**
     * @return PluginBase
     */
    public function getPlugin();
}
