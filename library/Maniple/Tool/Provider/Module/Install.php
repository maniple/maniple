<?php

class Maniple_Tool_Provider_Module_Install
{
    /**
     * @param string $moduleName
     */
    public static function run($moduleName)
    {
        $modulePath = is_dir($moduleName) ? $moduleName : "application/modules/{$moduleName}";

        if (!is_dir($modulePath)) {
            throw new Exception('Module directory not found: ' . $moduleName);
        }

        $moduleName = basename($moduleName);

        echo "\n\n";
        echo "[Installing module]\n";
        echo "  path: ", $modulePath, "\n";
        echo "  name: ", $moduleName, "\n";
        echo "\n";

        if (file_exists($modulePath . '/composer.json')) {
            $gitignorePath = "application/modules/.gitignore";
            if (file_exists($gitignorePath)) {
                $gitignore = file($gitignorePath);
                $gitignore = array_map('trim', $gitignore);
            } else {
                $gitignore = array();
            }

            if (!in_array($moduleName, $gitignore)) {
                $gitignore[] = $moduleName;
                sort($gitignore);
                file_put_contents($gitignorePath, implode("\n", $gitignore) . "\n");
            }
        }

        self::_installPublicAssets($modulePath);

        if (file_exists($modulePath . '/package.json')) {
            self::_installNpmDependencies($modulePath);
        }

        if (file_exists($modulePath . '/bower.json')) {
            self::_installBowerDependencies($modulePath);
        }
    }

    protected static function _installNpmDependencies($modulePath)
    {
        $process = new Zefram_System_Process(array('npm', '--version'));
        $process->run();
        $npm = trim($process->getOutput());

        if (!$npm) {
            throw new Exception('No NPM binary found');
        }

        $wd = getcwd();

        chdir($modulePath);
        echo system('npm install', $retval), "\n";

        chdir($wd);
    }

    protected static function _installPublicAssets($modulePath)
    {
        $moduleConfigPath = $modulePath . '/module.json';

        if (!file_exists($moduleConfigPath)) {
            $moduleConfig = array();
        } else {
            $moduleConfig = (array) @json_decode(file_get_contents($moduleConfigPath), true);
            echo "Warning: Module {$modulePath} uses module.json config\n";
            print_R($moduleConfig);
        }

        $assetsDir = null;
        if (is_dir($modulePath . '/public')) {
            $assetsDir = $modulePath . '/public';
        } elseif (is_dir($modulePath . '/assets')) {
            $assetsDir = $modulePath . '/assets';
        }

        if (empty($assetsDir) || !is_dir($assetsDir)) {
            echo '[info] No assets found in module ', basename($modulePath), "\n";
            return;
        }

        $assetsDirName = empty($moduleConfig['assetsDirName']) ? null : $moduleConfig['assetsDirName'];

        if (!$assetsDirName) {
            $assetsDirName = basename($modulePath);
        }

        if (!$assetsDirName) {
            throw new Exception('Invalid assets dir name: ' . $assetsDirName);
        }

        echo "Linking public assets: $assetsDirName\n";
        @mkdir('public/modules', 0755, true);
        @mkdir('public/assets', 0755, true);

        foreach (array('public/modules', 'public/assets') as $assetsBaseDir) {
            $assetsTargetDir = $assetsBaseDir . '/' . $assetsDirName;
            if (file_exists($assetsTargetDir)) {
                echo '[info] Module assets dir already exists, removing: ', $assetsTargetDir, "\n";
                unlink($assetsTargetDir);
            }
            maniple_symlink(realpath($assetsDir), $assetsTargetDir);
        }
    }

    protected static $_installedBowerComponents;

    protected static function _installBowerDependencies()
    {
        if (self::$_installedBowerComponents === null) {
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

                self::$_installedBowerComponents[$name] = dirname($bower_json);
            }
        }

        $bower = json_decode(file_get_contents($path . '/bower.json'), true);
        if (isset($bower['dependencies'])) {
            foreach ((array)$bower['dependencies'] as $package => $version) {
                if (isset(self::$_installedBowerComponents[$package])) {
                    printf('[bower] Package already installed %s\n', $package);
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
                    echo 'package dir: ' . $package_dir, ' (', $spec, ')\n';
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

                        self::$_installedBowerComponents[$package] = $package_dir;
                    } else {
                        throw new Exception('Failed to install package from bower: ' . $spec);
                    }
                }
            }
        }
    }
}

function maniple_module_install($path, $symlink = true)
{

    $module_name = empty($module_config['name']) ? basename($path) : basename($module_config['name']);

    $module_dir_name = empty($module_config['moduleDirName']) ? $module_name : basename($module_config['moduleDirName']);

    $module_path = $path;

    if (is_dir($module_path)) {
        @mkdir('application/modules', 0644, true);
        if ($symlink) {
            if (realpath($module_path) !== realpath("application/modules/{$module_dir_name}")) {
                echo 'Linking module ...\n';
                maniple_symlink(realpath($module_path), "application/modules/{$module_dir_name}");
            }
        }
    } else {
        echo "[ FAIL ] Module directory not found: $module_path\n";
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

    printf('[bower] Installing %s\n', $package);
    $cmd = sprintf(
        '%s install %s 2>&1',
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
        `rmdir '$dest' 2>nul`;
        `mklink /J '$dest' '$source'`;
    } else {
        `rm '$dest' 2>/dev/null`;
        `ln -s '$source' '$dest'`;
    }
}
