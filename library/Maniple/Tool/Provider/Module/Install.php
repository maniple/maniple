<?php

class Maniple_Tool_Provider_Module_Install
{
    /**
     * Adds and installs module from Packagist or GitHub
     *
     * @param string $moduleSpec
     */
    public static function run($moduleSpec)
    {
        if (!preg_match('#^(?P<vendor>[-_a-z0-9]+)/(?P<module>[-_a-z0-9]+)(?P<version>:[-.0-9a-fv]+)?$#', $moduleSpec, $match)) {
            throw new Exception("Invalid module specification: {$moduleSpec}");
        }

        $vendor = $match['vendor'];
        $module = $match['module'];
        $version = isset($match['version']) ? $match['version'] : '';

        echo 'Searching package on Packagist ... ';
        $spec = "{$vendor}/{$module}";
        $result = @file_get_contents("https://packagist.org/search.json?q=${spec}&type=zend1-module");

        try {
            $result = Zefram_Json::decode($result);
        } catch (Zend_Json_Exception $e) {
            $result = array('total' => 0);
        }

        $installable = false;

        if ($result['total'] === 1) {
            echo "found.\n";
            $installable = true;
        } else {
            echo "not found.\n";
        }

        if (!$installable) {
            echo 'Searching package on GitHub ... ';
            $result = @file_get_contents("https://raw.githubusercontent.com/{$spec}/master/composer.json");
            if ($result !== false) {
                echo "found.\n";
                // Add repository to composer.json
                $composer = Zefram_Json::decode(file_get_contents('./composer.json'));

                if (!isset($composer['repositories'])) {
                    $composer['repositories'] = array();
                }

                $found = false;
                foreach ($composer['repositories'] as $repo) {
                    if ($repo['url'] === 'https://github.com/' . $spec) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $repo = 'https://github.com/' . $spec;
                    echo "Adding repository {$repo} to composer.json ... ";
                    $composer['repositories'][] = array(
                        'type' => 'git',
                        'url' => $repo,
                    );
                    $composer['repositories'] = self::tidyComposerRepositories($composer['repositories']);

                    file_put_contents('./composer.json', Zefram_Json::encode($composer, array(
                        'prettyPrint'      => true,
                        'unescapedSlashes' => true,
                        'unescapedUnicode' => true,
                    )));
                    echo " done.\n";
                }
                $installable = true;
                if (!$version) {
                    $version = ':dev-master';

                    // GitHub API requires User-Agent to be provided
                    // https://stackoverflow.com/a/37142247/1086596
                    $context = stream_context_create(array(
                        'http' => array(
                            'method' => 'GET',
                            'header' => array(
                                'User-Agent: ' . __CLASS__,
                            ),
                        ),
                    ));
                    $info = file_get_contents("https://api.github.com/repos/{$spec}/commits/master", false, $context);
                    if ($info !== false) {
                        try {
                            $info = Zefram_Json::decode($info);
                            $version .= '#' . substr($info['sha'], 0, 7);
                        } catch (Exception $e) {}
                    }
                }
            } else {
                echo "not found.\n";
            }
        }

        if (!$installable) {
            throw new Exception("Unable to install module {$spec}");
        }

        echo "Installing package {$spec}{$version} ... \n";
        echo `composer require {$spec}{$version}`;

        echo "Setting up module " . basename($spec) . " ...\n";
        Maniple_Tool_Provider_Module_Setup::run(basename($spec));
    }

    /**
     * @param array $repositories
     * @return array
     */
    public static function tidyComposerRepositories(array $repositories)
    {
        $sortedRepositories = array();
        foreach ($repositories as $repo) {
            if (!is_array($repo) || empty($repo['url'])) {
                continue;
            }
            $repo = array_merge(array(
                'type' => isset($repo['type']) ? $repo['type'] : 'vcs',
                'url' => $repo['url'],
            ), $repo);
            $sortedRepositories[$repo['url']] = $repo;
        }

        ksort($sortedRepositories);

        return array_values($sortedRepositories);
    }
}
