<?php

class Maniple_Tool_Provider_Db_DumpPgsql
{
    public static function run(array $config, $path)
    {
        exec('pg_dump --version ' . (Zefram_Os::isWindows() ? '2>nul' : '2>/dev/null'), $output, $return);

        if ($return) {
            echo 'pg_dump not found.', PHP_EOL;
            return;

        } else {
            echo 'Using ' . trim(implode(PHP_EOL, $output)), PHP_EOL;
        }

        Zefram_Os::setEnv('PGPASSWORD', $config['params']['password']);
    }
}
