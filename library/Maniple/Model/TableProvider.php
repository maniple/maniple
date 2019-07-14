<?php

/**
 * Helper class for injecting Db tables into container resources. Requires
 * {@link Zefram_Db} resource to be present in the container.
 *
 * Configuration example:
 *
 * <pre>
 * 'UsersTable' => array(
 *     'callback' => 'Maniple_Model_TableProvider::getTable',
 *     'args' => 'UsersTableClassName',
 * ),
 * </pre>
 */
class Maniple_Model_TableProvider
{
    /**
     * @param string $tableName
     * @param Maniple_Di_Container $container
     * @return Zefram_Db_Table|Zend_Db_Table_Abstract
     * @throws Zend_Application_Exception
     */
    public static function getTable($tableName, Maniple_Di_Container $container)
    {
        /** @var Zefram_Db $db */
        $db = $container->getResource('Zefram_Db');
        return $db->getTable($tableName);
    }
}
