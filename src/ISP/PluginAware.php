<?php
/**
 * PluginAware.php
 *
 * @author: chazer
 * @created: 22.10.15 1:20
 */

namespace ISP;

trait PluginAware
{
    /**
     * @var PluginBase
     */
    private $plugin;

    /**
     * @param PluginBase $plugin
     */
    public function setPlugin($plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @return PluginBase
     */
    public function getPlugin()
    {
        return $this->plugin;
    }
}
