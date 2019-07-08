<?php

/**
 * Provides assets for {@link Maniple_Assets_AssetManager} service.
 *
 * @version 2019-02-11
 */
class Maniple_Assets_AssetRegistry
{
    /**
     * @var array
     */
    protected $_manifest = array();

    /**
     * Add module assets manifest to manifest registry.
     *
     * @param array $manifest    Mapping between asset paths and asset hashes
     * @param string $moduleName
     * @return $this
     */
    public function addManifest(array $manifest, $moduleName)
    {
        $this->_manifest[$moduleName] = array_merge(
            isset($this->_manifest[$moduleName]) ? $this->_manifest[$moduleName] : array(),
            array_map('strval', $manifest)
        );
        return $this;
    }

    /**
     * @param string $path
     * @param string $moduleName
     * @return string|null
     */
    public function getAssetHash($path, $moduleName)
    {
        $path = trim($path, '/');

        if (isset($this->_manifest[$moduleName][$path])) {
            return $this->_manifest[$moduleName][$path];
        }

        return null;
    }
}
