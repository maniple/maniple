<?php

define('DS', DIRECTORY_SEPARATOR);
define('WD', getcwd());

// search for vendor/autoload.php in all parent directories
$dir = dirname(__FILE__);
$autoload = null;
while ($dir !== ($parent = dirname($dir))) {
    if (is_file($autoload = $dir . '/vendor/autoload.php')) {
        define('VENDOR_DIR', $dir . DS . 'vendor');
        require_once $autoload;
        break;
    }
    $dir = $parent;
}
if ($autoload === null) {
    echo "Unable to locate autoloader, please reinstall this application.";
    exit(1);
}

$args = $_SERVER['argv'];
if (count($args) < 2) {
    echo "maniple [action] [args...]\n";
    exit(1);
}

if ($_SERVER['argv'][1] === 'help') {
    $_SERVER['argv'][1] = '?';
}

$action = $args[1];
$action_args = array_slice($args, 2);

define('APPLICATION_ENV',  (is_file('appenv') ? trim(file_get_contents('appenv')) : 'production'));
define('APPLICATION_PATH', getcwd() . '/application');

try {
    if ($action === 'install' && !count($action_args)) {
        maniple_install(isset($args[2]) ? $args[2] : '/');
    } else switch ($action) {
        case 'init':
            // maniple init
            maniple_init(isset($args[2]) ? $args[2] : null);
            break;

        case 'vendor-update':
            // maniple vendor-update [vendor_path]
            maniple_vendor_update(isset($args[2]) ? $args[2] : null);
            break;

        case 'set-baseurl':
            // maniple set-baseurl [base_url]
            break;

        case 'db:dump':
            maniple_db_dump(@$args[2]);
            break;

        case 'create-module':
            throw new Exception('create-module action has been removed. Use \'maniple module create\' instead');

        default:
            $console = new Maniple_Tool_Client_Console(array(
                'commandName'   => 'maniple',
                'helpHeader'    => false,
                'classesToLoad' => 'Maniple_Tool_Provider_Manifest',
                'application'   => array(
                    'class'       => 'Zefram_Application',
                    'environment' => APPLICATION_ENV,
                    'config'      => APPLICATION_PATH . '/configs/application.config.php',
                ),
            ));
            $console->dispatch();

            break;
    }
} catch (Exception $e) {
    echo "[ FAIL ] ", $e->getMessage(), "\n";
}

echo "\n";

function maniple_install($basePath)
{
    maniple_init();

    if (is_file('maniple.json')) {
        $config = (array) json_decode(file_get_contents('maniple.json'), true);
        if (isset($config['modules'])) {
            echo '[ WARN ] Reference modules with composer, maniple.json file is deprecated', "\n";
        }
    }
    foreach (scandir(APPLICATION_PATH . '/modules') as $dir) {
        if (substr($dir, 0, 1) === '.') {
            continue;
        }
        $dirPath = APPLICATION_PATH . '/modules/' . $dir;
        if (!is_dir($dirPath)) {
            continue;
        }
        if (is_file($dirPath . '/Bootstrap.php') // ZF1 style module
            || is_file($dirPath . '/module/Bootstrap.php')
            || is_file($dirPath . '/Module.php') // ZF2 style module
        ) {
            Maniple_Tool_Provider_Module_Setup::run($dir);
        }
    }

    // setup .htaccess
    if (!is_file('.htaccess')) {
        $basePath = '/' . trim($basePath, '/');
        $htaccess = file_get_contents('.htaccess.in');
        $htaccess = str_replace(array('@REWRITE_BASE@/', '@REWRITE_BASE@'), $basePath, $htaccess);
        file_put_contents('.htaccess', $htaccess);
        echo '[ DONE ] Written htaccess file', "\n";
    }
}

function set_baseurl($baseUrl)
{

}

function maniple_init($baseDir = null) {
    if ($baseDir !== null) {
        if (!is_dir($baseDir)) {
            echo "not a directory: ", $baseDir, "\n";
            exit(1);
        }
        $baseDir = realpath($baseDir);
    } else {
        $baseDir = getcwd();
    }

    $dirs = array(
        'application/configs' => false,
        'application/modules' => false,
        'public' => false,
    );

    $dataRoot = 'data';
    $dataDirs = array(
        'cache',
        'logs',
        'mail',
        'sessions',
        'temp',
        'twig',
        'storage',
    );

    if (file_exists('maniple.json')) {
        $overrides = (array) json_decode(file_get_contents('maniple.json'), true);
        if (isset($overrides['data']['root'])) {
            $dataRoot = $overrides['data']['root'];
        }

        if (isset($overrides['data']['directories'])) {
            $dataDirs = array_merge($dataDirs, $overrides['data']['directories']);
        }
    }

    $dirs[$dataRoot] = false;
    foreach ($dataDirs as $dataDir) {
        $dirs[$dataRoot . '/' . $dataDir] = true;
    }

    foreach ($dirs as $name => $writable) {
        $dirpath = $baseDir . DS . $name;
        echo substr(str_pad($name . ' ', 70, '.'), -70);
        if (!file_exists($dirpath)) {
            if (@mkdir($dirpath, 0755, true)) {
                echo ' [  OK  ]', "\n";
            } else {
                echo ' [ FAIL ]', "\n";
            }
        } else {
            echo  ' [  OK  ]', "\n";
        }
        @chmod($dirpath, $writable ? 0777 : 0755);
    }

    $index_path = $baseDir . '/public/index.php';
    if (!is_file($index_path)) {
        file_put_contents($index_path, maniple_generate_index());
    }
}

function maniple_vendor_update($vendor_path = null) {
    $cwd = getcwd(); // absolute
    if (null === $vendor_path) {
        $vendor_path = $cwd . DS . 'vendor';
    }
    if (!is_dir($vendor_path)) {
        echo 'vendor directory not found: ', $vendor_path, "\n";
        exit(1);
    }
    $vendor_path = realpath($vendor_path);

    foreach (scandir($vendor_path) as $dir) {
        $dir_path = $vendor_path . DS . $dir;
        if (substr($dir, 0, 1) === '.' || is_file($dir_path)) {
            continue;
        }
        chdir($dir_path);
        if (is_dir('.git')) {
            echo "Updating GIT repository: ", $dir, "\n";
            echo `git pull`;
            echo `git submodule foreach git pull`; // requires Git 1.6.1+
            echo "\n";
        } elseif (is_dir('.svn')) {
            echo "Updating SVN repository: ", $dir, "\n";
            echo `svn update`;
            echo "\n";
        }
    }
    chdir($cwd);
}

function maniple_unlink($path, $nomove = false)
{
    if ($nomove) {
        $tmppath = $path;
    } else {
        $tmp = WD . '/.maniple/' . getmypid();
        if (!is_dir($tmp)) {
            mkdir($tmp, 0755, true);
        }
        $tmppath = $tmp . '/' . md5(mt_rand() . microtime(true));
        rename($path, $tmppath);
    }
    return @unlink(realpath($tmppath)); // under Windows unlink does not indicate removal,
    // but rather if delete operation is pending
}

function maniple_rm($path, $nomove = false)
{
    if (is_link($path)) {
        return maniple_unlink($path, $nomove);
    }
    if (is_dir($path)) { // is_dir resolves symlinks
        $dh = opendir($path);
        while ($de = readdir($dh)) {
            if ($de == '.' || $de == '..') {
                continue;
            }
            $p = $path . '/' . $de;
            if (is_dir($p)) {
                maniple_rm($p, $nomove);
            } else {
                maniple_unlink($p, $nomove);
            }
        }
        closedir($dh);
        return rmdir($path);
    }
    if (file_exists($path)) {
        return maniple_unlink($path, $nomove);
    }
    return false;
}

function maniple_generate_index()
{
    return "<?php

define('APPLICATION_PATH', realpath('../application'));
defined('APPLICATION_ENV') ||
    define('APPLICATION_ENV', getenv('APPLICATION_ENV')
        ? getenv('APPLICATION_ENV')
        : (is_file(APPLICATION_PATH . '/.env')
            ? trim(file_get_contents(APPLICATION_PATH . '/.env'))
            : 'development'
        )
    );
define('VENDOR_PATH', realpath(APPLICATION_PATH . '/../vendor'));

require_once VENDOR_PATH . '/autoload.php';

\$application = new Zefram_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.php'
);
\$application->bootstrap()->run();
";
}

function maniple_db_dump($output)
{
    $config = require './application/configs/config.php';

    $host = $config['db']['params']['host'];
    $dbname = $config['db']['params']['dbname'];
    $username = $config['db']['params']['username'];
    $password = $config['db']['params']['password'];

    if (empty($output)) {
        $output = 'data/db_dumps';
        if (!is_dir($output)) {
            mkdir($output, 0777, true);
        }
    }

    if (is_dir($output)) {
        $output = sprintf('%s/%s-%s.sql', rtrim($output, '/'), $dbname, date('Ymd-His'));
    }

    switch ($config['db']['adapter']) {
        case 'PDO_MYSQL':
            $cnf = Zefram_Os::getTempDir() . '/' . md5(mt_rand()) . '.cnf';
            file_put_contents($cnf, "[client]\nhost=$host\nuser=$username\npassword=$password\n[mysqldump]\ncomplete-insert\ndefault-character-set=utf8\nset-charset\n");

            $outputEscaped = escapeshellarg($output);
            $result = system("mysqldump --defaults-extra-file=$cnf --no-create-db=TRUE $dbname > $outputEscaped", $retval);
            unlink($cnf);

            if (!$retval) {
                echo '[  OK  ] Output written to: ', $output, "\n";
            } else {
                echo '[ FAIL ] Dumping database failed.', "\n", $result;
            }
            break;

        default:
            die('Unsupported database adapter');
    }
    exit;
}
