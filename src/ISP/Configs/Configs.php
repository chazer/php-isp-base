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
    const TYPE_STR = 'string';
    const TYPE_INT = 'int';
    const TYPE_BOOL = 'bool';
    const TYPE_STR_LIST = 'string[]';

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
    private $params = [];

    /**
     * @var FileReaderInterface[]
     */
    private $readers = [];

    private $_init = false;

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

    protected function init()
    {
        if ($this->_init)
            return;
        // Set flag first: important!
        $this->_init = true;

        $this->initParams();
    }

    protected function reset()
    {
        $this->_init = false;
        $this->params = [];
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
     * Register config parameters
     */
    protected function initParams()
    {
        // Example:
        // $this->addParam('DBHost', 'localhost');
        // $this->addParam('DBUser', 'root');
    }

    protected function addParam($name, $type = null, $default = null)
    {
        if (null === $default) {
            // for array types use empty array as default
            self::TYPE_STR_LIST !== $type || $default = [];
        }
        $data = $this->getParamData($name);
        $data['name'] = $name;
        $data['type'] = $type;
        $data['default'] = $default;
        $this->setParamData($name, $data);
    }

    private function arrayValue($array, $key, $default)
    {
        return array_key_exists($key, $array) ? $array[$key] : $default;
    }

    private function getParamData($name)
    {
        $this->init();
        $key = $this->normalizeParamName($name);
        $param = array_key_exists($key, $this->params) ? $this->params[$key] : [];
        $param = array_merge(['name' => $name, 'default' => null], $param);
        return $param;
    }

    private function setParamData($name, $data)
    {
        $key = $this->normalizeParamName($name);
        $this->params[$key] = $data;
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
        $this->reset();
        $this->init();

        if (!is_file($this->file)) {
            $saved = $this->save();
            if ($saved)
                return false;
        }

        $success = $this->getReader()->load($this->file, $config);

        if ($success && is_array($config)) {
            foreach ($config as $param => $value) {
                $this->setParam($param, $this->fromStringForm($param, $value));
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
        $this->init();

        $params = [];
        foreach ($this->params as $name=>$param) {
            $value = $this->arrayValue($param, 'value', null);
            if (null !== $value) {
                $params[$name] = $this->toStringForm($name, $value);
            }
        }
        $success = $this->getReader()->save($this->file, $params);
        return $success;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getParam($name)
    {
        $data = $this->getParamData($name);
        return $this->arrayValue($data, 'value', $data['default']);
    }

    public function setParam($name, $value)
    {
        $data = $this->getParamData($name);
        $data['value'] = $value;
        $this->setParamData($name, $data);
    }

    public function hasParam($name)
    {
        $this->init();
        $name = $this->normalizeParamName($name);
        return array_key_exists($name, $this->params);
    }

    /**
     * @return array
     */
    public function getParams()
    {
        $params = [];
        foreach ($this->params as $name => $data) {
            array_key_exists('value', $data) && $data[$name] = $data['value'];
        }
        return $params;
    }

    private function normalizeParamName($name)
    {
        return strtolower($name);
    }

    /**
     * Convert string value to boolean
     *
     * @param $value
     * @return bool
     */
    public function toBoolean($value)
    {
        $value = is_string($value) ? strtolower($value) : $value;
        if ($value === true) {
            return true;
        }
        if (is_numeric($value) && $value > 0) {
            return true;
        }
        return is_scalar($value) && in_array((string) $value, ['on', 'yes', 'y']);
    }

    protected function toStringForm($param, $value)
    {
        $data = $this->getParamData($param);
        $type = $this->arrayValue($data, 'type', null);
        if (self::TYPE_BOOL === $type) {
            return $value ? 'On' : 'Off';
        }
        return $value;
    }

    protected function fromStringForm($param, $value)
    {
        $data = $this->getParamData($param);
        $type = $this->arrayValue($data, 'type', null);
        switch ($type) {
            case self::TYPE_STR:
                return strval($value);
                break;
            case self::TYPE_BOOL:
                return $this->toBoolean($value);
                break;
            case self::TYPE_INT:
                return intval($value);
                break;
            default:
                return $value;
        }
    }
}
