<?php

class Maniple_Model
{
    /**
     * @param  array $data OPTIONAL
     * @return void
     */
    public function __construct(array $data = null) // {{{
    {
        if ($data) {
            $this->setFromArray($data);
        }
    } // }}}

    /**
     * @param  array $data
     * @return Maniple_Model
     */
    public function setFromArray(array $data) // {{{
    {
        foreach ($data as $key => $value) {
            $this->__set($key, $value, false);
        }
        return $this;
    } // }}}

    /**
     * @param  string $key
     * @param  mixed $value
     * @param  bool $throw OPTIONAL
     * @return Maniple_Model
     * @throws InvalidArgumentException
     */
    protected function _setProperty($key, $value, $throw = true) // {{{
    {
        $key = self::toCamelCase($key);

        $setter = 'set' . $key;
        if (method_exists($this, $setter)) {
            $this->{$setter}($value);
            return $this;
        }

        $property = '_' . $key;
        if (property_exists($this, $property)) {
            $this->{$property} = $value;
            return $this;
        }

        throw new InvalidArgumentException(sprintf('Invalid property: %s', $key));

        return false;
    } // }}}

    /**
     * @param  string $key
     * @param  bool $throw OPTIONAL
     * @return mixed
     * @throws InvalidArgumentException
     */
    protected function _getProperty($key, $throw = true) // {{{
    {
        $key = self::toCamelCase($key);

        $getter = 'get' . $key;
        if (method_exists($this, $getter)) {
            return $this->{$getter}();
        }

        $property = '_' . $key;
        if (property_exists($this, $property)) {
            return $this->{$property};
        }

        if ($throw) {
            throw new InvalidArgumentException(sprintf('Invalid property: %s', $key));
        }
    } // }}}

    /**
     * @param  string $key
     * @return bool
     */
    public function has($key) // {{{
    {
        return property_exists($this, '_' . self::toCamelCase($key));
    } // }}}

    /**
     * @param  string $key
     * @return bool
     */
    public function __isset($key) // {{{
    {
        return isset($this->{'_' . self::toCamelCase($key)});
    } // }}}

    /**
     * @param  string $key
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function __get($key) // {{{
    {
        return $this->_getProperty($key, true);
    } // }}}

    /**
     * @param  string $key
     * @param  mixed $value
     * @return void
     * @throws InvalidArgumentException
     */
    public function __set($key, $value) // {{{
    {
        return $this->_setProperty($key, $value, true);
    } // }}}

    /**
     * Transform given string to camel-case.
     *
     * @param  string $str
     * @return string
     */
    public static function toCamelCase($str) // {{{
    {
        if (is_array($str) && isset($str[1])) {
            return strtoupper($str[1]);
        }
        return preg_replace_callback(
            '/_(\w)/', array(__CLASS__, __FUNCTION__), (string) $str
        );
    } // }}}
}
