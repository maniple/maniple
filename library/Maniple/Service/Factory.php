<?php

use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * ZF2 style factory for legacy resources
 */
class Maniple_Service_Factory implements AbstractFactoryInterface
{
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $config = $serviceLocator->has('Config') ? $serviceLocator->get('Config') : array();
        $configKey = $this->getConfigKey($requestedName);

        if (empty($config[$configKey])) {
            return false;
        }

        $serviceConfig = $config[$configKey];

        // if non-empty 'plugin' key is set it means that bootstrap resource plugin
        // should be used instead

        return is_string($serviceConfig)
            || (isset($serviceConfig['class']) && empty($serviceConfig['plugin']));
    }

    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $config = $serviceLocator->has('Config') ? $serviceLocator->get('Config') : array();

        if (empty($config[$requestedName])) {
            throw new Exception('Empty resource config');
        }

        $configKey = $this->getConfigKey($requestedName);
        $resourceConfig = $config[$configKey];

        if (is_string($resourceConfig)) {
            if (substr($resourceConfig, 0, 9) === 'resource:') {
                $resourceConfig = substr($resourceConfig, 9);
            }
            return $serviceLocator->get($resourceConfig);
        }

        if (!is_array($resourceConfig) || !isset($resourceConfig['class'])) {
            throw new Exception('Invalid resource config: not array or missing class');
        }

        return $this->_createInstance($serviceLocator, $resourceConfig);
    }

    public function getConfigKey($serviceName)
    {
        return strtolower($serviceName);
    }

    /**
     * @return array
     */
    protected function _prepareParams(ServiceLocatorInterface $serviceLocator, $params) // {{{
    {
        if (is_object($params) && method_exists($params, 'toArray')) {
            $params = $params->toArray();
        }

        $params = (array) $params;

        foreach ($params as $key => $value) {
            if (is_string($value) && !strncasecmp($value, 'resource:', 9)) {
                $params[$key] = $serviceLocator->get(substr($value, 9));
            }
            // recursively replace arrays with 'class' key with instances of
            // matching classes
            if (is_array($value)) {
                if (isset($value['class'])) {
                    $params[$key] = $this->_createInstance($serviceLocator, $value);
                } else {
                    $params[$key] = $this->_prepareParams($serviceLocator, $value);
                }
            }
        }

        return $params;
    } // }}}

    /**
     * Create an instance of a given class and setup its parameters.
     *
     * @param  string $class
     * @param  array $params OPTIONAL
     * @return object
     * @throws Exception
     */
    protected function _createInstance(ServiceLocatorInterface $serviceLocator, array $description) // {{{
    {
        if (empty($description['class'])) {
            throw new Exception('No class name found in description');
        }

        $class = $description['class'];
        $params = null;

        if (isset($description['params'])) {
            $params = $this->_prepareParams($serviceLocator, $description['params']);
        }

        // instantiate object, pass 'args' to constructor
        $args = null;
        if (isset($description['args'])) {
            $args = $this->_prepareParams($serviceLocator, $description['args']);
        }

        if ($args) {
            $ref = new ReflectionClass($class);
            if ($ref->hasMethod('__construct')) {
                $instance = $ref->newInstanceArgs($args);
            } else {
                $instance = $ref->newInstance();
            }
        } else {
            $instance = new $class();
        }

        // this is now deprecated. Params will be passed to constructor
        foreach ((array) $params as $key => $value) {
            $methods = array(
                'set' . str_replace('_', '', $key),
                'set' . $key
            );
            foreach ($methods as $method) {
                if (method_exists($instance, $method)) {
                    $instance->{$method}($value);
                    break;
                }
            }
        }

        // Set options using setter methods, try camel-cased versions
        // first, then underscored. Because PHP is case-insensitive when
        // it comes to function names, there is no need to appy some fancy
        // underscore-to-camel-case filter. Removing all underscore is
        // sufficient.
        if (isset($description['options'])) {
            $options = $this->_prepareParams($serviceLocator, $description['options']);

            foreach ($options as $key => $value) {
                $methods = array(
                    'set' . str_replace('_', '', $key),
                    'set' . $key
                );
                foreach ($methods as $method) {
                    if (method_exists($instance, $method)) {
                        $instance->{$method}($value);
                        break;
                    }
                }
            }
        }

        // invoke arbitrary methods
        if (isset($description['invoke'])) {
            foreach ($description['invoke'] as $invoke) {
                if (!is_array($invoke)) {
                    throw new Zefram_Application_ResourceContainer_Exception('Invoke value must be an array');
                }
                $method = array_shift($invoke);
                $args = (array) array_shift($invoke);
                call_user_func_array(array($instance, $method), $args);
            }
        }

        return $instance;
    } // }}}
}
