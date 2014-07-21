<?php

/**
 * @version 2014-07-21
 */
class Maniple_Application_Resource_LazyResource
    extends Maniple_Application_Resource_ResourceAbstract
{
    /**
     * @var array
     */
    protected $_options = array(
        'class'   => null,
        'params'  => null,
        'options' => null,
    );

    /**
     * Sets lazy resource configuration.
     *
     * @param  array $options
     * @return Maniple_Application_Resource_LazyResource
     */
    public function setOptions(array $options)
    {
        $this->_options = array_merge(
            $this->_options,
            array_intersect_key($options, $this->_options)
        );
        return $this;
    }

    /**
     * Returns an instantiation data for a given resource.
     *
     * @return array
     * @throws Exception
     */
    public function init()
    {
        if (empty($this->_options['class'])) {
            throw new Exception('Class name of a lazy resource must not be empty upon initialization');
        }
        return $this->_options;
    }
}
