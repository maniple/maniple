<?php

class Maniple_Tool_Provider_Db_DumpMysql
{
    public static function run(array $config, $path)
    {
        exec('mysqldump --version ' . (Zefram_Os::isWindows() ? '2>nul' : '2>/dev/null'), $output, $return);

        if ($return) {
            throw new Zend_Tool_Framework_Provider_Exception('mysqldump not found.');

        } else {
            echo 'Using ' . trim(implode(PHP_EOL, $output)), PHP_EOL;
        }

        Zefram_Os::setEnv('MYSQL_PWD', $config['params']['password']);

        $dbName = $config['params']['dbname'];
        $sqlOutput = $path . '/' . sprintf('%s/%s/%s-%s.sql', date('Y'), date('m'), $dbName, date('Ymd-His'));

        @mkdir(dirname($sqlOutput), 0755, true);

        exec('mysqldump --no-create-db --skip-extended-insert ' . escapeshellarg($dbName) . ' > ' . escapeshellarg($sqlOutput), $output, $error);

        if ($error) {
            throw new Zend_Tool_Framework_Provider_Exception(implode("\n", $output));
        }

        return $sqlOutput;
    }
}
