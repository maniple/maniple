<?php

class Maniple_Model
{
    const CAMELIZE   = 0;
    const UNDERSCORE = 1;

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
            $this->_setProperty($key, $value, false);
        }
        return $this;
    } // }}}

    /**
     * @param  int $keyTransform
     * @return array
     * @throws InvalidArgumentException
     */
    public function toArray($keyTransform = self::CAMELIZE) // {{{
    {
        $array = array();

        switch ($keyTransform) {
            case self::UNDERSCORE:
                $transform = array(__CLASS__, 'underscore');
                break;

            case self::CAMELIZE:
                $transform = array(__CLASS__, 'camelize');
                break;

            default:
                throw new InvalidArgumentException(sprintf(
                    'Unrecognized key transform value (%s)', $keyTransform
                ));
        }

        foreach (get_object_vars($this) as $property => $value) {
            // include only properties starting with an underscore,
            // remove the underscore before retrieving property value
            if ('_' !== $property[0]) {
                continue;
            }

            $property = substr($property, 1);
            $key = call_user_func($transform, $property);
            $array[$key] = $this->_getProperty($property, false);
        }

        return $array;
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
        $key = self::camelize($key);

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

        if ($throw) {
            throw new InvalidArgumentException(sprintf('Invalid property: %s', $key));
        }

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
        $key = self::camelize($key);

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

        return null;
    } // }}}

    /**
     * @param  string $key
     * @return bool
     */
    public function has($key) // {{{
    {
        return property_exists($this, '_' . self::camelize($key));
    } // }}}

    /**
     * @param  string $key
     * @return bool
     */
    public function __isset($key) // {{{
    {
        return isset($this->{'_' . self::camelize($key)});
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
    public static function camelize($str) // {{{
    {
        if (is_array($str)) {
            return strtoupper($str[1]);
        }
        return preg_replace_callback(
            '/_(\w)/', array(__CLASS__, __FUNCTION__), (string) $str
        );
    } // }}}

    /**
     * Transform given string to underscore separated notation.
     *
     * @param  string $str
     * @return string
     */
    public static function underscore($str) // {{{
    {
        if (is_array($str)) {
            return $str[1][0] . '_' . strtolower($str[1][1]);
        }
        return preg_replace_callback(
            '/([a-z][A-Z])/', array(__CLASS__, __FUNCTION__), (string) $str
        );
    } // }}}
}
