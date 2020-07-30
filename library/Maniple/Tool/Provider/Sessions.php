<?php

class Maniple_Tool_Provider_Sessions extends Maniple_Tool_Provider_Abstract
{
    const className = __CLASS__;

    /**
     * Clear session files older than the specified lifetime.
     *
     * @param int $maxLifetime
     * @return int
     * @throws Zend_Application_Bootstrap_Exception
     */
    public function clearAction($maxLifetime = 3600)
    {
        $maxLifetime = (int) $maxLifetime;

        if ($maxLifetime <= 0) {
            throw new InvalidArgumentException('Invalid argument: $maxLifetime value must be greater than 0');
        }

        $php = defined('PHP_BINARY') ? PHP_BINARY : 'php';
        exec($php . ' --version ' . (Zefram_Os::isWindows() ? '2>nul' : '2>/dev/null'), $output, $return);

        if ($return) {
            echo 'PHP CLI not found.', PHP_EOL;
            return 1;

        }

        /** @var Zend_Application_Resource_Session $session */
        $session = $this->_getApplication()->getBootstrap()->getPluginResource('Session');
        $options = $session->getOptions();

        $cmd = sprintf(
            '%s -d session.gc_probability=1 -d session.gc_divisor=1 -d session.gc_maxlifetime=%d',
            $php,
            $maxLifetime
        );

        if (isset($options['save_path']) && ($savePath = realpath($options['save_path']))) {
            $cmd .= ' -d session.save_path=' . escapeshellarg($savePath);
        }

        $cmd .= ' -r "session_start(); session_destroy();"';

        echo sprintf('Session max lifetime: %d %s', $maxLifetime, $maxLifetime === 1 ? 'second' : 'seconds'), PHP_EOL;
        echo sprintf('PHP binary: %s', reset($output)), PHP_EOL;
        echo PHP_EOL;
        echo $cmd, PHP_EOL;

        exec($cmd, $output, $result);

        echo PHP_EOL;
        echo $result ? 'Failed' : 'Success', PHP_EOL;
    }
}
