<?php

class Maniple_Tool_Provider_Db_DumpMysql
{
    public static function run(array $config, $path)
    {
        exec('mysqldump --version ' . (Zefram_Os::isWindows() ? '2>nul' : '2>/dev/null'), $output, $return);

        if ($return) {
            echo 'mysqldump not found.', PHP_EOL;
            return;

        } else {
            echo 'Using ' . trim(implode(PHP_EOL, $output)), PHP_EOL;
        }

        Zefram_Os::setEnv('MYSQL_PWD', $config['params']['password']);
    }
}
