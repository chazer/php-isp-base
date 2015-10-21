<?php
/**
 * JsonFileReader.php
 *
 * @author: Aleksandr Chazov <develop@chazer.ru>
 * @created: 27.09.15 16:47
 */

namespace ISP\Configs;

class JsonFileReader implements FileReaderInterface
{
    public function save($file, $data)
    {
        $content = json_encode($data, JSON_PRETTY_PRINT);
        return false !== file_put_contents($file, $content);
    }

    public function load($file, &$data)
    {
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        return false !== $data;
    }
}
