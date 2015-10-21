<?php
/**
 * ConfFileReader.php
 *
 * @author: Aleksandr Chazov <develop@chazer.ru>
 * @created: 27.09.15 16:57
 */

namespace ISP\Configs;

class ConfFileReader implements FileReaderInterface
{
    public function save($file, $data)
    {
        $temp = tempnam(dirname($file), basename($file));
        if (false === ($h = fopen($temp, 'w'))) {
            return false;
        }
        foreach ($data as $param => $value) {
            $value = (array) $value;
            foreach ($value as $v) {
                fwrite($h, $param . ' ' . $v . "\n");
            }
        }
        fclose($h);
        return rename($temp, $file);
    }

    public function load($file, &$data)
    {
        $params = [];

        if (!is_file($file))
            return false;

        if ($h = fopen($file, 'r')) {
            while (false !== ($line = fgets($h))) {
                $line = trim($line);
                if (strlen($line) == 0)
                    continue;
                if (in_array($line[0], ['#']))
                    continue;
                list($name, $value) = array_pad(array_map('trim', explode(' ', ltrim($line), 2)), 2, null);
                if (isset($params[$name])) {
                    $params[$name] = (array) $params[$name];
                    $params[$name][] = $value;
                } else {
                    $params[$name] = $value;
                }
            }
            fclose($h);
            $data = $params;
            return true;
        }
        return false;
    }
}
