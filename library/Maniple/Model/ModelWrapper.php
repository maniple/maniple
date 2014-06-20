<?php

/**
 * This is a generic container for models that allows additional variables
 * to be passed along with the model.
 */
class Maniple_Model_ModelWrapper
{
    protected $_model;

    protected $_extra;

    public function __construct($model, $extras = null)
    {
        $this->_model = (object) $model;

        if ($extras) {
            $this->assign($extras);
        }
    }

    public function assign($key, $value = null)
    {
        if (is_array($key) || $key instanceof Traversable) {
            foreach ($key as $k => $val) {
                $this->_extra[$k] = $val;
            }
        } else {
            $this->_extra[(string) $key] = $value;
        }
        return $this;
    }

    public function __set($key, $value)
    {
        return $this->assign($key, $value);
    }

    public function __get($key)
    {
        if (isset($this->_model->{$key})) {
            return $this->_model->{$key};
        }
        if (isset($this->_extra[$key])) {
            return $this->_extra[$key];
        }
        return null;
    }

    public function __isset($key)
    {
        return isset($this->_extra[$key]) || isset($this->_model->{$key});
    }

    public function __unset($key)
    {
        if (isset($this->_extra[$key])) {
            unset($this->_extra[$key]);
        }
    }
}
