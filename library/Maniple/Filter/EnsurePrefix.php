<?php

class Maniple_Filter_EnsurePrefix implements Zend_Filter_Interface
{
    /**
     * @var array
     */
    protected static $_defaultOptions = array(
        'prefix'    => '',
        'matchCase' => true,
    );

    /**
     * @var array
     */
    protected $_options;

    /**
     * @param array $options
     */
    public function __construct($options = null)
    {
        $this->_options = self::$_defaultOptions;

        foreach ((array) $options as $key => $value) {
            $method = 'set' . $key;
            if (method_exists($this, $method)) {
                $this->{$method}($value);
            }
        }
    }

    /**
     * @param string|array $prefix
     * @return Maniple_Filter_EnsurePrefix
     */
    public function setPrefix($prefix)
    {
        $this->_options['prefix'] = $prefix;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPrefix()
    {
        return $this->_options['prefix'];
    }

    /**
     * @param $matchCase
     * @return Maniple_Filter_EnsurePrefix
     */
    public function setMatchCase($matchCase)
    {
        $this->_options['matchCase'] = (bool) $matchCase;
        return $this;
    }

    /**
     * @return bool
     */
    public function getMatchCase()
    {
        return $this->_options['matchCase'];
    }

    /**
     * Call {@link filterStatic()} with filter options
     *
     * @param mixed $value
     * @return string
     */
    public function filter($value)
    {
        return self::filterStatic($value, $this->_options);
    }

    /**
     * Ensures the given value starts with prefix
     *
     * If prefix is specified as an array the value will be checked against
     * all given prefixes. If none of the prefixes is matched, then the
     * first prefix will be appended to value.
     *
     * @param $value
     * @param array $options
     * @return string
     */
    public static function filterStatic($value, array $options = null)
    {
        if (!strlen($value)) {
            return $value;
        }

        $options = (array) $options + self::$_defaultOptions;

        $matchCase = (bool) $options['matchCase'];
        $prefixes = (array) $options['prefix'];

        foreach ($prefixes as $prefix) {
            $prefix = (string) $prefix;
            $prefixLen = strlen($prefix);

            if (($matchCase && !strncmp($prefix, $value, $prefixLen)) ||
                (!$matchCase && !strncasecmp($prefix, $value, $prefixLen))
            ) {
                return $value;
            }
        }

        return reset($prefixes) . $value;
    }
}
