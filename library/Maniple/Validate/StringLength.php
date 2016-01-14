<?php

class Maniple_Validate_StringLength extends Zend_Validate_StringLength
{
    protected $_messageTemplates = array(
        self::INVALID   => "Invalid type given. String expected",
        self::TOO_SHORT => "Value is less than %min% characters long",
        self::TOO_LONG  => "Value is more than %max% characters long",
    );

    /**
     * @var Maniple_StringUtils_StringLength_Adapter
     */
    protected $_adapter;

    public function __construct(array $options)
    {
        parent::__construct($options);

        if (array_key_exists('adapter', $options)) {
            $this->setAdapter($options['adapter']);
        }

        if (isset($options['messages'])) {
            $this->setMessages($options['messages']);
        }
    }

    /**
     * @param Maniple_StringUtils_StringLength_Adapter|null $adapter
     * @return $this
     */
    public function setAdapter($adapter = null)
    {
        if ($adapter !== null
            && !$adapter instanceof Maniple_StringUtils_StringLength_Adapter
        ) {
            if (class_exists($adapter)) {
                $adapterClass = $adapter;
            } else {
                $adapterClass = 'Maniple_StringUtils_StringLength_' . ucfirst($adapter);
            }
            $adapter = new $adapterClass();
        }
        $this->_adapter = $adapter;
        return $this;
    }

    /**
     * @return Maniple_StringUtils_StringLength_Adapter
     */
    public function getAdapter()
    {
        if ($this->_adapter === null) {
            $this->_adapter = new Maniple_StringUtils_StringLength_Standard();
        }
        return $this->_adapter;
    }

    public function isValid($value)
    {
        if (!is_string($value)) {
            $this->_error(self::INVALID);
            return false;
        }

        $this->_setValue($value);

        $adapter = $this->getAdapter();
        $adapter->setEncoding($this->_encoding);

        $length = $adapter->getLength($value);

        if ($length < $this->_min) {
            $this->_error(self::TOO_SHORT);
        }

        if (null !== $this->_max && $this->_max < $length) {
            $this->_error(self::TOO_LONG);
        }

        if (count($this->_messages)) {
            return false;
        } else {
            return true;
        }
    }
}
