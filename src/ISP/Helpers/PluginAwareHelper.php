<?php
/**
 * PluginAwareHelper.php
 *
 * @author: chazer
 * @created: 10.11.15 18:58
 */

namespace ISP\Helpers;

use ISP\Interfaces\PluginAwareInterface;
use ISP\PluginBase;

class PluginAwareHelper
{
    public static function setPlugin(PluginBase $plugin, $value)
    {
        $array = is_array($value) ? $value : [&$value];
        foreach ($array as &$v) {
            if ($v instanceof PluginAwareInterface) {
                $v->setPlugin($plugin);
            }
        }
    }
}
