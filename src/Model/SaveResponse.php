<?php

namespace Basee\Model;

if ( !defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @package     ExpressionEngine
 * @category    Basee
 * @author      Brian Litzinger
 * @copyright   Copyright (c) 2016-2019 - Brian Litzinger
 * @link        https://github.com/litzinger/basee
 * @license     MIT
 */

class SaveResponse
{
    /**
     * @var int
     */
    private $entityId;

    /**
     * @var array
     */
    private $messageParameters;

    /**
     * @var array
     */
    private $saveRedirectOptions;

    /**
     * @var string
     */
    private $saveSuccessUrl;

    /**
     * @var string
     */
    private $saveSuccessTitle;

    /**
     * @var string
     */
    private $saveSuccessBody;

    /**
     * @var string
     */
    private $saveErrorTitle;

    /**
     * @var string
     */
    private $saveErrorBody;

    /**
     * @return int
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * @param int $entityId
     * @return $this
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * @return array
     */
    public function getMessageParameters()
    {
        return $this->messageParameters;
    }

    /**
     * @param array $messageParameters
     * @return $this
     */
    public function setMessageParameters(array $messageParameters = [])
    {
        $this->messageParameters = $messageParameters;

        return $this;
    }

    /**
     * @return string
     */
    public function getSaveSuccessUrl()
    {
        return $this->saveSuccessUrl;
    }

    /**
     * @param string $saveSuccessUrl
     * @return $this
     */
    public function setSaveSuccessUrl($saveSuccessUrl)
    {
        $this->saveSuccessUrl = $saveSuccessUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getSaveSuccessTitle()
    {
        return $this->saveSuccessTitle;
    }

    /**
     * @param string $saveSuccessTitle
     * @return $this
     */
    public function setSaveSuccessTitle($saveSuccessTitle)
    {
        $this->saveSuccessTitle = $saveSuccessTitle;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSaveErrorTitle()
    {
        return $this->saveErrorTitle;
    }

    /**
     * @param mixed $saveErrorTitle
     * @return $this
     */
    public function setSaveErrorTitle($saveErrorTitle)
    {
        $this->saveErrorTitle = $saveErrorTitle;

        return $this;
    }

    /**
     * @return string
     */
    public function getSaveErrorBody()
    {
        return $this->saveErrorBody;
    }

    /**
     * @param string $saveErrorBody
     * @return $this
     */
    public function setSaveErrorBody($saveErrorBody)
    {
        $this->saveErrorBody = $saveErrorBody;

        return $this;
    }

    /**
     * @param string $saveSuccessBody
     * @return $this
     */
    public function setSaveSuccessBody($saveSuccessBody)
    {
        $this->saveSuccessBody = $saveSuccessBody;

        return $this;
    }

    /**
     * @return string
     */
    public function getSaveSuccessBody()
    {
        return $this->saveSuccessBody;
    }

    /**
     * @return array
     */
    public function getSaveRedirectOptions()
    {
        return $this->saveRedirectOptions;
    }

    /**
     * @param array $saveRedirectOptions
     * @return $this
     */
    public function setSaveRedirectOptions(array $saveRedirectOptions = [])
    {
        $this->saveRedirectOptions = $saveRedirectOptions;

        return $this;
    }
}
