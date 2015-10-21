<?php
/**
 * FileReaderInterface.php
 *
 * @author: Aleksandr Chazov <develop@chazer.ru>
 * @created: 27.09.15 16:50
 */

namespace ISP\Configs;

interface FileReaderInterface
{
    /**
     * Save config to file
     *
     * @param string $file config filename
     * @param array $data config array
     * @return bool
     */
    public function save($file, $data);

    /**
     * @param string $file config filename
     * @param array $data config array
     * @return bool
     */
    public function load($file, &$data);
}
