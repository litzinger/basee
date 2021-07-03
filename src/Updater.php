<?php

namespace Basee;

use Basee\Update\AbstractUpdate;
use FilesystemIterator;
use RecursiveDirectoryIterator;

/**
 * @package     ExpressionEngine
 * @category    Basee
 * @author      Brian Litzinger
 * @copyright   Copyright (c) 2019 - BoldMinded, LLC
 * @link        https://github.com/litzinger/basee
 * @license     MIT
 */

class Updater
{
    /**
     * @var string
     */
    private $filePath;

    /**
     * @var array
     */
    private $hookTemplate = array();

    /**
     * @var array
     */
    private $updateFiles = array();

    public function __construct() {}

    /**
     * Check to see if the installed version is less than what is reported by the add-on package.
     * E.g. the user added new files. Optionally redirect to the Add-ons page to encourage an upgrade.
     * Call this function in an ft or mod file constructor to prevent the user from seeing php errors
     * because the new version might contain db changes before they've been executed via the upgrade.
     *
     * @param string $addonName
     * @param bool   $redirect
     * @return bool
     */
    public function shouldUpdate(string $addonName = '', bool $redirect = false): bool
    {
        $addon = ee('App')->get($addonName);
        $installed = ee()->addons->get_installed('modules');

        if (isset($installed[$addonName]['module_version']) &&
            version_compare($installed[$addonName]['module_version'], $addon->getVersion(), '<')
        ) {
            if ($redirect) {
                ee()->functions->redirect(ee('CP/URL')->make('addons'));
            }

            return true;
        }

        return false;
    }

    /**
     * @param string $currentVersion
     * @param bool $fetchAll
     * @return $this
     * @throws \Exception
     */
    public function fetchUpdates($currentVersion = '0', $fetchAll = false)
    {
        if (!$this->filePath) {
            throw new \Exception('$filePath not defined.');
        }

        $nextUpdate = false;
        $remainingUpdates = 0;

        if (!is_readable($this->filePath)) {
            throw new \Exception($this->filePath . ' is not readable or does not exist.');
        }

        $files = iterator_to_array(
            new RecursiveDirectoryIterator($this->filePath,
            FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS),
            true
        );

        ksort($files);

        foreach ($files as $file) {
            $fileName = $file->getFilename();

            if (preg_match('/^up_0*(\d+)_0*(\d+)_0*(\d+).php$/', $fileName, $m)) {
                $fileVersion = "{$m[1]}.{$m[2]}.{$m[3]}";

                if (version_compare($fileVersion, $currentVersion, '>')) {
                    $remainingUpdates++;

                    if ($fetchAll === true || !$nextUpdate || version_compare($fileVersion, $nextUpdate, '>')) {
                        $nextUpdate = $fileVersion;
                        $nextUpdateFile = substr($fileName, 3, -4);

                        $this->updateFiles[] = $nextUpdateFile;
                    }
                }
            }
        }

        return $this;
    }

    public function runUpdates()
    {
        foreach ($this->updateFiles as $file) {
            require_once $this->filePath . '/up_'.$file.'.php';
            $className = 'Update_' . $file;
            /** @var AbstractUpdate $update */
            $update = new $className();
            $update
                ->setHookTemplate($this->getHookTemplate())
                ->doUpdate();
        }
    }

    /**
     * Get a single update class to test individual update routines.
     *
     * @param string $file
     * @return Update
     * @throws \Exception
     */
    public function getUpdate($file = '')
    {
        $filePath = $this->filePath . '/up_'.$file.'.php';

        if (!$file || !file_exists($filePath)) {
            throw new \Exception('Requested update file '. $file .'not found.');
        }

        require_once $filePath;
        $className = 'Update_' . $file;

        return new $className();
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * @param string $filePath
     * @return $this
     */
    public function setFilePath($filePath)
    {
        $this->filePath = $filePath;

        return $this;
    }

    /**
     * @return array
     */
    public function getHookTemplate()
    {
        return $this->hookTemplate;
    }

    /**
     * @param array $hookTemplate
     * @return $this
     */
    public function setHookTemplate($hookTemplate)
    {
        $this->hookTemplate = $hookTemplate;

        return $this;
    }
}
