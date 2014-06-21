<?php

/**
 * This is a generic container for models that allows additional variables
 * to be passed along with the model.
 *
 * @version 2014-06-21
 * @author xemlock
 */
class Maniple_Model_ModelWrapper
{
    /**
     * @var object
     */
    protected $_model;

    /**
     * @var array
     */
    protected $_extras;

    /**
     * @param  object $model
     * @param  array|Traversable $extras
     */
    public function __construct($model, $extras = null)
    {
        $this->_model = (object) $model;

        if ($extras) {
            $this->addExtras($extras);
        }
    }

    /**
     * @param  array|Traversable $extras
     * @return Maniple_Model_ModelWrapper
     */
    public function addExtras($extras)
    {
        foreach ($extras as $key => $value) {
            $this->addExtra($key, $value);
        }
        return $this;
    }

    /**
     * @param  string $key
     * @param  mixed $value
     * @return Maniple_Model_ModelWrapper
     */
    public function addExtra($key, $value)
    {
        $this->_extras[(string) $key] = $value;
        return $this;
    }

    /**
     * Proxy to {@link addExtra()}.
     *
     * @param  string $key
     * @param  mixed $value
     */
    public function __set($key, $value)
    {
        return $this->addExtra($key, $value);
    }

    /**
     * @param  string $key
     * @return mixed
     */
    public function __get($key)
    {
        if (isset($this->_extras[$key])) {
            return $this->_extras[$key];
        }
        if (isset($this->_model->{$key})) {
            return $this->_model->{$key};
        }
        return null;
    }

    /**
     * @param  string $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->_extras[$key]) || isset($this->_model->{$key});
    }

    /**
     * @param  string $key
     * @return void
     */
    public function __unset($key)
    {
        if (isset($this->_extras[$key])) {
            unset($this->_extras[$key]);
        }
    }
}
