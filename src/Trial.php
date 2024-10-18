<?php

namespace Litzinger\Basee;

/**
 * @package     ExpressionEngine
 * @category    Basee
 * @author      Brian Litzinger
 * @link        https://github.com/litzinger/basee
 * @license     MIT
 */

class Trial
{
    /**
     * @var string
     */
    private $installedDate;

    /**
     * @var string
     */
    private $messageTitle;

    /**
     * @var string
     */
    private $messageBody;

    /**
     * @var bool
     */
    private $trialEnabled = false;

    /**
     * @return bool
     */
    public function isTrialExpired()
    {
        if ($this->isTrialEnabled() === false) {
            return false;
        }

        $installedDate = $this->getInstalledDate();

        if ($installedDate && $installedDate < strtotime('-30 days')) {
            return true;
        }

        return false;
    }

    public function showTrialExpiredAlert()
    {
        $alert = ee('CP/Alert');
        $alert
            ->makeStandard()
            ->asImportant()
            ->withTitle($this->getMessageTitle())
            ->addToBody($this->getMessageBody())
            ->now();
    }

    public function showTrialExpiredInline()
    {
        if (REQ === 'CP') {
            $alert = ee('CP/Alert');
            return $alert
                ->makeInline()
                ->asImportant()
                ->withTitle($this->getMessageTitle())
                ->addToBody($this->getMessageBody())
                ->render();
        } else {
            return '<div class="alert inline warn">
                <h3>'. $this->getMessageTitle() .'</h3>
                <p>'. $this->getMessageBody() .'</p>
            </div>';
        }
    }

    /**
     * @return mixed
     */
    public function getInstalledDate()
    {
        return $this->installedDate;
    }

    /**
     * @param mixed $installedDate
     */
    public function setInstalledDate($installedDate)
    {
        $this->installedDate = $installedDate;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessageTitle()
    {
        return $this->messageTitle;
    }

    /**
     * @param string $messageTitle
     */
    public function setMessageTitle($messageTitle)
    {
        $this->messageTitle = $messageTitle;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessageBody()
    {
        return $this->messageBody;
    }

    /**
     * @param string $messageBody
     */
    public function setMessageBody($messageBody)
    {
        $this->messageBody = $messageBody;

        return $this;
    }

    /**
     * @return bool
     */
    public function isTrialEnabled()
    {
        return $this->trialEnabled;
    }

    /**
     * @param bool $trialEnabled
     */
    public function setTrialEnabled($trialEnabled)
    {
        $this->trialEnabled = $trialEnabled;

        return $this;
    }
}
