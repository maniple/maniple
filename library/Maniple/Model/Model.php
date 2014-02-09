<?php

class Maniple_Model_Model
{
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
        $property = '_' . self::toCamelCase($key);
        if (property_exists($this, $property)) {
            return $property;
        }
        throw new InvalidArgumentException(sprintf('Invalid property: %s', $key));
    } // }}}

    /**
     * @param  string $key
     * @param  mixed $value
     * @return void
     * @throws InvalidArgumentException
     */
    public function __set($key, $value) // {{{
    {
        $key = self::toCamelCase($key);

        $setter = 'set' . $key;
        if (method_exists($this, $setter)) {
            return $this->{$setter}($value);        
        }

        $property = '_' . $key;
        if (property_exists($this, $property)) {
            $this->{$property} = $value;
            return;
        }

        throw new InvalidArgumentException(sprintf('Invalid property: %s', $key));
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
