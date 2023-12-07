<?php
/**
 * Forter Commercetools app
 *
 * @package  Forter Commercetools app
 * @author   Forter (https://www.forter.com/)
 * @author   Developer: Pniel Cohen (Trus)
 */

declare(strict_types=1);

namespace App\Models\Forter;

use App\Helpers\UtilsHelper;

/**
 * ForterAbstractModel model
 */
class ForterAbstractModel implements \JsonSerializable
{
    protected $_data = [];

    public function __construct($data)
    {
        $this->setData($data);
    }

    public function __toString()
    {
        return \json_encode($this->getData());
    }

    public function __get($name)
    {
        if (\method_exists($this, 'get' . ucfirst($name))) {
            return $this->{'get' . ucfirst($name)}();
        }
        if (array_key_exists($name, $this->_data)) {
            return $this->_data[$name];
        }
        return null;
    }

    public function __set($name, $value)
    {
        if (\method_exists($this, 'set' . ucfirst($name))) {
            return $this->{'set' . ucfirst($name)}($value);
        }
        $this->_data[$name] = $value;
    }

    public function __isset($name)
    {
        return isset($this->_data[$name]);
    }

    public function __unset($name)
    {
        unset($this->_data[$name]);
    }

    public function __serialize()
    {
        return $this->getData();
    }

    /**
     * @method getData
     * @return array
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * @method setData
     * @param  array|object   $data
     * @return self           $this
     */
    public function setData($data)
    {
        $this->resetData();
        $this->_data = UtilsHelper::toArrayRecursive($data);
        return $this;
    }

    /**
     * @method resetData
     * @return self  $this
     */
    protected function resetData()
    {
        $this->_data = [];
        return $this;
    }

    public function jsonSerialize()
    {
        return $this->getData();
    }

    /**
     * Get new model instance
     * @param  array    $data
     * @return self  $this
     */
    public static function getInstance($data)
    {
        return new static($data);
    }
}
