<?php

class Maniple_Injector
{
    /**
     * @var Zefram_Application_ResourceContainer
     */
    protected $_container;

    /**
     * @var array
     */
    protected $_metadata;

    /**
     * @param Zefram_Application_ResourceContainer $container
     */
    public function __construct(Maniple_Application_ResourceContainer $container)
    {
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
                throw new Exception('Invalid resource type: ' . $desc['resourceName'] . ' ' . $desc['varType']);
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

                if (preg_match('/@Inject\((?P<resourceName>[^)]+)\)/', $comment, $match)) {
                    $resourceName = $match['resourceName'];
                    $resourceName = trim($resourceName, '\'"');
                }

                if (empty($resourceName)) {
                    continue;
                }

                if (preg_match('/@var\s+(?P<varType>[\S]+)/', $comment, $match)) {
                    $varType = $match['varType'];
                } else {
                    $varType = null;
                }

                $metadata[$prop->getName()] = compact('resourceName', 'varType');
            }

            $this->_metadata[$objectClass] = $metadata;
        }

        return $this->_metadata[$objectClass];
    }
}
