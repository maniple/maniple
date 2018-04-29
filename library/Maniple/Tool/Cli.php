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

$action = $args[1];
$action_args = array_slice($args, 2);

define('APPLICATION_ENV',  (is_file('appenv') ? trim(file_get_contents('appenv')) : 'production'));
define('APPLICATION_PATH', realpath('application'));

try {
    switch ($action) {
        case 'init':
            // maniple init
            maniple_init(isset($args[2]) ? $args[2] : null);
            break;

        case 'vendor-update':
            // maniple vendor-update [vendor_path]
            maniple_vendor_update(isset($args[2]) ? $args[2] : null);
            break;

        case 'module-install':
            // maniple module-install [module_path]
            if (empty($args[2])) {
                echo "maniple module-install: module_path required\n";
                exit(1);
            }
            maniple_module_install($args[2]);
            break;

        case 'set-baseurl':
            // maniple set-baseurl [base_url]
            break;

        case 'install':
            // maniple install [base_url]
            maniple_install(isset($args[2]) ? $args[2] : '/');
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
    foreach (scandir('application/modules') as $dir) {
        if (substr($dir, 0, 1) === '.') {
            continue;
        }
        $dirPath = 'application/modules/' . $dir;
        if (!is_dir($dirPath)) {
            continue;
        }
        if (is_file($dirPath . '/Bootstrap.php') // ZF1 style module
            || is_file($dirPath . '/module/Bootstrap.php')
            || is_file($dirPath . '/Module.php') // ZF2 style module
        ) {
            maniple_module_install($dirPath);
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

    maniple_generate_configs($baseDir . '/application/configs');
}

function maniple_generate_configs($dir) {
    $configs = array(
        'development' => array(
            'phpSettings' => array(
                'display_startup_errors' => true,
                'display_errors' => true,
            ),
            'resources' => array(
                'frontController' => array(
                    'params' => array(
                        'displayExceptions' => true,
                    ),
                ),
            ),
        ),
        'production' => array(
            'phpSettings' => array(
                'display_startup_errors' => false,
                'display_errors' => false,
            ),
            'resources' => array(
                'frontController' => array(
                    'params' => array(
                        'displayExceptions' => false,
                    ),
                ),
            ),
        ),
    );
    foreach ($configs as $env => $config) {
        $path = $dir . '/application.' . $env . '.php';
        if (!is_file($path)) {
            echo 'Creating application config file ', basename($path), ' ... ';

            $configString = var_export($config, true);

            // use 4 space indent
            $indentWidth = 4;
            $configString = preg_replace_callback('/\n(\s+)/', function (array $match) use ($indentWidth) {
                $indentDepth = strlen($match[1]) / 2;
                $indent = str_repeat(' ', $indentWidth);
                return "\n" . str_repeat($indent, $indentDepth);
            }, $configString);

            // remove space between 'array' and '('
            $configString = preg_replace('/array\s*\(/', 'array(', $configString);

            // place array( at the same level as =>
            $configString = preg_replace('/=>\s*array\(/', '=> array(', $configString);

            file_put_contents($path, "<?php\n\nreturn " . $configString . ";\n");
            echo 'done.', "\n";
        }
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

function maniple_module_install($path, $symlink = true)
{
    $path = str_replace('\\', '/', $path);

    if (strpos($path, '/') === false) {
        // module name given, use default location
        $path = 'application/modules/' . $path;
    }

    if (!file_exists($path)) {
        printf("[ ERROR ] Module directory not found: %s\n", $path);
        return false;
    }

    $module_config_path = $path . '/module.json';

    if (!file_exists($module_config_path)) {
        $module_config = array();
    } else {
        $module_config = (array) @json_decode(file_get_contents($module_config_path), true);
        print_R($module_config);
    }

    $module_name = empty($module_config['name']) ? basename($path) : basename($module_config['name']);

    $module_dir_name = empty($module_config['moduleDirName']) ? $module_name : basename($module_config['moduleDirName']);

    $module_path = $path;

    if (is_dir($module_path)) {
        @mkdir('application/modules', 0644, true);
        if ($symlink) {
            if (realpath($module_path) !== realpath("application/modules/{$module_dir_name}")) {
                echo "Linking module ...\n";
                maniple_symlink(realpath($module_path), "application/modules/{$module_dir_name}");
            }
        }
    } else {
        echo "[ FAIL ] Module directory not found: $module_path\n";
    }


    echo "\n\n";
    echo "[Installing module]\n";
    echo "  path: ", $module_path, "\n";
    echo "  name: ", $module_name, "\n";
    echo "  moduleDirName: ", $module_dir_name, " (deprecated)\n";
    echo "\n";


    $assets_dir_name = empty($module_config['assetsDirName']) ? $module_dir_name : basename($module_config['assetsDirName']);

    $publicAssetsFound = false;
    $publicAssetsDirs = array('resource/public', 'resources/public', 'public', 'assets');
    foreach ($publicAssetsDirs as $publicAssetsDir) {
        $assets_path = $path . '/' . $publicAssetsDir;

        if (is_dir($assets_path)) {
            echo "Linking public assets\n";
            @mkdir('public/modules', 0755, true);
            maniple_symlink(realpath($assets_path), "public/modules/{$assets_dir_name}");

            // deprecated
            @mkdir('public/assets', 0755, true);
            maniple_symlink(realpath($assets_path), "public/assets/{$assets_dir_name}");

            $publicAssetsFound = true;
            break;
        }
    }
    if (!$publicAssetsFound) {
        echo "[assets] public directory not found\n";
    }

    if (is_file($path . '/bower.json')) {
        static $installed_components;

        if ($installed_components === null) {
            foreach (glob('public/bower_components/*') as $component) {
                if (!is_dir($component)) {
                    continue;
                }

                // .bower.json is always generated when installing a Bower package. It
                // is a clone of bower.json with additional properties used internally.
                // Also for non-registry packages there will be .bower.json, but no bower.json.
                // Source: https://github.com/bower/bower/issues/1174
                $bower_json = $component . '/.bower.json';
                $bower = json_decode(file_get_contents($bower_json), true);
                $name = isset($bower['name']) ? $bower['name'] : null;

                if (empty($name)) {
                    $bower2_json = dirname($bower_json) . '/bower.json';
                    if (file_exists($bower2_json)) {
                        $bower = json_decode(file_get_contents($bower2_json), true);
                    }
                    $name = isset($bower['name']) ? $bower['name'] : null;
                }

                if (empty($name)) {
                    $name = basename(dirname($bower_json));
                }

                $installed_components[$name] = dirname($bower_json);
            }
        }

        $bower = json_decode(file_get_contents($path . '/bower.json'), true);
        if (isset($bower['dependencies'])) {
            foreach ((array) $bower['dependencies'] as $package => $version) {
                if (isset($installed_components[$package])) {
                    printf("[bower] Package already installed %s\n", $package);
                } else {
                    if (preg_match('#^[a-z]+://#i', $version)
                        || preg_match('#^[-_a-z0-9]+/[-_a-z0-9]+[\#]?#i', $version)
                    ) {
                        $spec = "$package=$version"; // url or login/repo
                    } else {
                        $spec = "$package=$package#$version";
                    }
                    bower_install($spec);
                    $package_dir = 'public/bower_components/' . $package;
                    echo 'package dir: ' . $package_dir, " (", $spec, ")\n";
                    if (file_exists($package_dir . '/.bower.json')) {
                        $json = json_decode(file_get_contents($package_dir . '/.bower.json'), true);

                        // bowerphp fails to properly create .bower.json for components from GitHub
                        if (!isset($json['name'])) {
                            $json = array_merge(
                                array('name' => $package),
                                $json
                            );
                            file_put_contents(
                                $package_dir . '/.bower.json',
                                Zefram_Json::encode($json, array(
                                    'prettyPrint' => true,
                                    'unescapedSlashes' => true,
                                    'unescapedUnicode' => true,
                                ))
                            );
                        }

                        $installed_components[$package] = $package_dir;
                    } else {
                        throw new Exception('Failed to install package from bower, ' . $spec);
                    }
                }
            }
        }
    }
}

/**
 * Detect the available version of Bower
 *
 * @return string
 */
function bower_binary() {
    $process = new Zefram_System_Process(array('bower', '--version'));
    $process->run();
    return trim($process->getOutput());
}

/**
 * Install Bower package
 *
 * @param string $package
 */
function bower_install($package) {
    static $bower_available = null;

    if ($bower_available === null) {
        $bower_available = bower_binary();
        if ($bower_available) {
            echo '[bower] Using Bower version ', $bower_available, "\n";
        } else {
            echo '[bower] Bower not detected, using bowerphp', "\n";
        }
    }

    printf("[bower] Installing %s\n", $package);
    $cmd = sprintf(
        "%s install %s 2>&1",
        $bower_available
            ? 'bower'
            : VENDOR_DIR . DS . 'bin' . DS . 'bowerphp',
        escapeshellarg($package)
    );
    echo $cmd, "\n";
    echo system($cmd, $retval), "\n";

    return $retval;
}

function maniple_symlink($source, $dest)
{
    echo "[symlink] $dest -> $source\n";
    // don't rely on symlink as it fails on Windows
    // Warning: symlink(): Cannot create symlink, error code(1314)
    if (Zefram_Os::isWindows()) {
        `rmdir "$dest" 2>nul`;
        `mklink /J "$dest" "$source"`;
    } else {
        `rm "$dest" 2>/dev/null`;
        `ln -s "$source" "$dest"`;
    }
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
