<?php

namespace Basee;

use FilesystemIterator;

/**
 * @package     ExpressionEngine
 * @category    Basee
 * @author      Brian Litzinger
 * @copyright   Copyright (c) 2016 - Brian Litzinger
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
    private $updateFiles = array();

    public function __construct() {}

    /**
     * @param int $currentVersion
     * @return $this
     * @throws \Exception
     */
    public function fetchUpdates($currentVersion = 0)
    {
        if (!$this->filePath) {
            throw new \Exception('$filePath not defined.');
        }

        $nextUpdate = false;
        $remainingUpdates = 0;

        if (!is_readable($this->filePath)) {
            return false;
        }

        $files = new FilesystemIterator($this->filePath);

        foreach ($files as $file) {
            $fileName = $file->getFilename();

            if (preg_match('/^up_0*(\d+)_0*(\d+)_0*(\d+).php$/', $fileName, $m)) {
                $fileVersion = "{$m[1]}.{$m[2]}.{$m[3]}";

                if (version_compare($fileVersion, $currentVersion, '>')) {
                    $remainingUpdates++;

                    if (!$nextUpdate || version_compare($fileVersion, $nextUpdate, '<')) {
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
            $update = new Update();
            $update->doUpdate();
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
        return new Update();
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
}
