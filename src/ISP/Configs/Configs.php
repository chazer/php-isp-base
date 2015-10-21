<?php
/**
 * Configs.php
 *
 * @author: Aleksandr Chazov <develop@chazer.ru>
 * @created: 27.09.15 16:59
 */

namespace ISP\Configs;

use Exception;

class Configs
{
    /**
     * @var string
     */
    protected $file;

    /**
     * @var string
     */
    protected $format;

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @var FileReaderInterface[]
     */
    private $readers = [];

    /**
     * @param string $file
     * @param string $format json|ini|yaml|xml
     */
    function __construct($file, $format = 'conf')
    {
        $this->file = $file;
        $this->format = $format;
        $this->registerFormat('conf', new ConfFileReader());
        $this->registerFormat('json', new JsonFileReader());
    }

    /**
     * @param string $name format name
     * @param FileReaderInterface $reader
     * @return $this
     */
    public function registerFormat($name, FileReaderInterface $reader)
    {
        $this->readers[strtolower($name)] = $reader;
        return $this;
    }

    /**
     * @return FileReaderInterface
     * @throws \Exception
     */
    protected function getReader()
    {
        if (isset($this->readers[$this->format])) {
            return $this->readers[$this->format];
        }
        throw new Exception('Unsupported file format: ' . $this->format);
    }

    /**
     * @return bool
     */
    public function load()
    {
        if (!is_file($this->file)) {
            $saved = $this->save();
            if ($saved)
                return false;
        }

        $success = $this->getReader()->load($this->file, $config);

        if ($success && is_array($config)) {
            foreach ($config as $param => $value) {
                $this->setParam($param, $value);
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function save()
    {
        $success = $this->getReader()->save($this->file, $this->params);
        return $success;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getParam($name, $default = null)
    {
        $name = $this->normalizeParamName($name);
        return isset($this->params[$name]) ? $this->params[$name] : $default;
    }

    public function setParam($name, $value)
    {
        $name = $this->normalizeParamName($name);
        $this->params[$name] = $value;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    protected function normalizeParamName($name)
    {
        return ucfirst(strtolower($name));
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return bool
     */
    public function getBoolParam($name, $default = null)
    {
        if (isset($this->params[$name])) {
            return $this->toBoolean($this->params[$name]);
        } else {
            return $this->toBoolean($default);
        }
    }

    /**
     * Convert string value to boolean
     *
     * @param $value
     * @return bool
     */
    public function toBoolean($value)
    {
        if (in_array($value, [0, false, null, '', 'off', 'no', 'n'])) {
            return false;
        }
        if (in_array($value, [1, true, 'on', 'yes', 'y'])) {
            return true;
        }
        return false;
    }
}
