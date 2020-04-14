<?php

/**
 * Resource injector
 *
 * @version 2019-06-29 / 2019-02-28
 */
class Maniple_Di_Injector
{
    const className = __CLASS__;

    /**
     * @var object
     */
    protected $_container;

    /**
     * @var array
     */
    protected $_metadata;

    /**
     * @param object $container
     */
    public function __construct($container)
    {
        if (function_exists('opcache_get_status')) {
            $opcache = opcache_get_status(false);
            if ($opcache && $opcache['opcache_enabled'] && !ini_get('opcache.save_comments')) {
                throw new RuntimeException('Support for annotations is disabled. Injector requires that \'opcache.save_comments\' ini setting is enabled');
            }
        }

        if (!is_object($container)) {
            throw new InvalidArgumentException(
                sprintf('Container must be an object, %s provided', gettype($container))
            );
        }

        $this->_container = $container;
    }

    /**
     * @param string $class
     * @param array $overrides
     * @return object
     */
    public function newInstance($class, array $overrides = array())
    {
        $ref = new ReflectionClass($class);
        if ($ref->hasMethod('__construct')) {
            $meta = $this->_extractMethodMetadata($ref, '__construct');
            $instanceArgs = array();
            foreach ($meta as $paramName => $paramInfo) {
                $resourceName = $paramInfo['resourceName'];
                if (array_key_exists($resourceName, $overrides)) {
                    $instanceArgs[] = $overrides[$resourceName];
                } else {
                    $instanceArgs[] = $this->_container->{$resourceName};
                }
            }
            $instance = $ref->newInstanceArgs($instanceArgs);
        } else {
            $instance = $ref->newInstance();
        }

        $this->inject($instance);

        return $instance;
    }

    /**
     * @param object $object
     * @return object
     */
    public function inject($object)
    {
        if (!is_object($object)) {
            throw new InvalidArgumentException(sprintf(
                'Expected object for property injection, %s was provided instead',
                gettype($object)
            ));
        }

        $metadata = $this->_getMetadata($object);

        if (empty($metadata)) {
            return;
        }

        $ref = new ReflectionClass($object);

        foreach ($metadata as $name => $desc) {
            $dep = isset($this->_container->{$desc['resourceName']})
                ? $this->_container->{$desc['resourceName']}
                : null;

            if ($dep === null) {
                if (!$desc['nullable']) {
                    throw new InvalidArgumentException('Resource not found: ' . $desc['resourceName']);
                }

            } elseif ($desc['varType']) {
                $match = false;
                foreach ($desc['varType'] as $varType) {
                    if ($dep instanceof $varType) {
                        $match = true;
                        break;
                    }
                }
                if (!$match) {
                    throw new InvalidArgumentException(sprintf(
                        'Invalid type of \'%s\' resource: %s, expected %s',
                        $desc['resourceName'],
                        is_object($dep) ? get_class($dep) : gettype($dep),
                        join('|', $desc['varType'])
                    ));
                }
            }

            $prop = $ref->getProperty($name);
            $prop->setAccessible(true);
            $prop->setValue($object, $dep);
        }

        return $object;
    }

    protected function _getMetadata($object)
    {
        $objectClass = get_class($object);

        if (!isset($this->_metadata[$objectClass]['properties'])) {
            $metadata = array();
            $ref = new ReflectionClass($object);
            foreach ($ref->getProperties() as $prop) {
                $comment = $prop->getDocComment();

                if (!$comment) {
                    continue;
                }

                $resourceName = null;

                // @Inject('resourceName')
                if (preg_match('/@Inject\((?P<resourceName>[^)]+)\)/', $comment, $match)) {
                    $resourceName = $match['resourceName'];
                    $resourceName = trim($resourceName, '\'"');
                }

                $nullable = false;
                $varType = array();

                if (preg_match('/@var\s+(?P<varType>[\S]+)/', $comment, $match)) {
                    foreach (explode('|', $match['varType']) as $type) {
                        if (strtolower($type) === 'null') {
                            $nullable = true;
                        } else {
                            $varType[] = $type;
                        }
                    }
                }

                // If '@Inject' or '@Inject()' use type specified in @var as resource name
                if (empty($resourceName) && preg_match('/@Inject(\s|\(\))/', $comment)) {
                    if (count($varType) > 1) {
                        throw new InvalidArgumentException(sprintf(
                            'Only a single not null type is allowed on injected property %s in class %s',
                            $prop->getName(),
                            $ref->getName()
                        ));
                    }
                    $resourceName = reset($varType);
                }

                if (empty($resourceName)) {
                    continue;
                }

                $metadata[$prop->getName()] = compact('resourceName', 'varType', 'nullable');
            }

            return $this->_metadata[$objectClass]['properties'] = $metadata;
        }

        return $this->_metadata[$objectClass]['properties'];
    }

    protected function _extractMethodMetadata(ReflectionClass $refClass, $method)
    {
        if (!isset($this->_metadata[$refClass->getName()][$method])) {
            $refMethod = $refClass->getMethod($method);
            $metadata = array();

            foreach ($refMethod->getParameters() as $param) {
                $metadata[$param->getName()] = array(
                    'resourceName' => $param->getClass()->getName(),
                    'varType'      => $param->getClass()->getName(),
                    'nullable'     => $param->allowsNull(),
                );
            }

            return $this->_metadata[$refClass->getName()][$method] = $metadata;
        }

        return $this->_metadata[$refClass->getName()][$method];
    }
}
