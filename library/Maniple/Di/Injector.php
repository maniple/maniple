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
        if (!is_object($container)) {
            throw new Exception(
                sprintf('Container must be an object, %s provided', gettype($container))
            );
        }

        $this->_container = $container;
    }

    /**
     * @param object $object
     */
    public function inject($object)
    {
        if (!is_object($object)) {
            throw new InvalidArgumentException();
        }

        $metadata = $this->_getMetadata($object);

        if (empty($metadata)) {
            return;
        }

        $ref = new ReflectionClass($object);

        foreach ($metadata as $name => $desc) {
            $dep = $this->_container->{$desc['resourceName']};
            if (empty($dep)) {
                throw new Exception('Resource not found: ' . $desc['resourceName']);
            }
            if ($desc['varType'] && !($dep instanceof $desc['varType'])) {
                throw new Exception(sprintf(
                    'Invalid type of \'%s\' resource: %s, expected %s',
                    $desc['resourceName'],
                    is_object($dep) ? get_class($dep) : gettype($dep),
                    $desc['varType']
                ));
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

        if (!isset($this->_metadata[$objectClass])) {
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

                if (preg_match('/@var\s+(?P<varType>[\S]+)/', $comment, $match)) {
                    $varType = $match['varType'];
                } else {
                    $varType = null;
                }

                // If '@Inject' or '@Inject()' use type specified in @var as resource name
                if (empty($resourceName) && preg_match('/@Inject(\s|\(\))/', $comment)) {
                    $resourceName = $varType;
                }

                if (empty($resourceName)) {
                    continue;
                }

                $metadata[$prop->getName()] = compact('resourceName', 'varType');
            }

            $this->_metadata[$objectClass] = $metadata;
        }

        return $this->_metadata[$objectClass];
    }
}
