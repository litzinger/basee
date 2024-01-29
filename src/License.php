<?php

namespace Litzinger\Basee;

/**
 * @package     ExpressionEngine
 * @category    Basee
 * @author      Brian Litzinger
 * @copyright   Copyright (c) 2022 - BoldMinded, LLC
 * @link        https://github.com/litzinger/basee
 * @license     MIT
 */

class License
{
    const DEBUG = false;

    const STATUSES = [
        'invalid',
        'update_available',
        'expired',
        'expiring_soon',
    ];

    const BANNER_MESSAGE = 'Your license is available at <a href="%s">boldminded.com</a>, or <a href="https://expressionengine.com">expressionengine.com</a>. If you purchased from expressionengine.com, be sure to visit <a href="https://boldminded.com/claim">boldminded.com/claim</a> to add the license to your account.';

    /**
     * @var string
     */
    private $licenseCheckUrl = '';

    /**
     * @var string
     */
    private $addonShortName = '';

    /**
     * @var string
     */
    private $addonName = '';

    /**
     * @var array
     */
    private $payload = [];

    /**
     * @var string
     */
    public static $licenseAccountUrl = 'https://boldminded.com/account/licenses';

    /**
     * @param string $licenseCheckUrl
     * @param string $addonShortName
     * @param array  $payload
     * @param string $licenseAccountUrl
     */
    public function __construct(string $licenseCheckUrl, string $addonShortName = '', array $payload = [], string $licenseAccountUrl = '')
    {
        $this->licenseCheckUrl = $licenseCheckUrl;
        $this->addonShortName = $addonShortName;

        if (!empty($payload)) {
            $this->addonName = $payload['a'];
            $this->payload = $payload;
        }

        if ($licenseAccountUrl) {
            self::$licenseAccountUrl = $licenseAccountUrl;
        }
    }

    /**
     * @param array $payload
     * @return array
     */
    public function checkLicense($payload = [])
    {
        try
        {
            $response = ee('Curl')->post($this->licenseCheckUrl, [
                'CURLOPT_CONNECTTIMEOUT' => 3,
                'CURLOPT_HTTPHEADER' => ['Cache-Control: no-cache'],
                'CURLOPT_RETURNTRANSFER' => true,
                'payload' => $payload['payload'],
            ])->exec();

            $response = json_decode($response, true);
        }
        catch (\Exception $e)
        {
            // don't scare the user with whatever random error, but store it for debugging
            $response['exception'] = $e->getMessage();
        }

        return $response;
    }

    /**
     * @return string
     */
    public function validate()
    {
        $ping = new Ping($this->addonShortName . '_last_ping', 2400);

        // Has it been more than 1 hour since the last ping? If so get an update.
        if (self::DEBUG || $ping->shouldPing()) {
            $response = $this->checkLicense([
                'payload' => base64_encode(json_encode($this->payload))
            ]);

            // In the event it throws an exception, just consider it a valid license,
            // otherwise a customer's site may not work and we don't want to deal with the support.
            // Use case for this is when the SSL cert randomly expired on license.boldminded.com
            if (isset($response['exception'])) {
                $response = [
                    'status' => 'valid',
                ];
            }

            if (self::DEBUG) {
                $response['status'] = 'invalid';
            }

            $ping->updateLastPing($response['status']);

            if (
                $response !== null && isset($response['status']) &&
                (!$this->payload['l'] || in_array($response['status'], self::STATUSES))
            ) {
                return $this->displayValidationMessage($response['status']);
            }
        }

        // If we have an invalid status, and we're between pings, we still need to display a potential invalid status
        $lastPingStatus = $ping->getLastPingStatus();

        if (in_array($lastPingStatus, self::STATUSES)) {
            return $this->displayValidationMessage($lastPingStatus);
        }

        return '';
    }

    /**
     * @param string $status
     * @return string
     */
    public function displayValidationMessage(string $status)
    {
        $scripts = [];

        if ($status === 'invalid') {
            $scripts[] = self::getInvalidNotice($this->addonShortName, $this->addonName, self::$licenseAccountUrl, $status);
        }

        if ($status === 'update_available') {
            $scripts[] = self::getUpdateAvailableNotice($this->addonShortName, self::$licenseAccountUrl, $status);
        }

        if ($status === 'expired') {
            $scripts[] = self::getExpiredNotice($this->addonShortName, self::$licenseAccountUrl, $status);
        }

        if ($status === 'expiring_soon') {
            $scripts[] = self::getExpiringSoonNotice($this->addonShortName, self::$licenseAccountUrl, $status);
        }

        if (isset(ee()->cp)) {
            ee()->cp->add_to_foot('<script type="text/javascript">$(function(){'. preg_replace("/\s+/", " ", implode('', $scripts)) .'});</script>');

            return '';
        }

        return preg_replace("/\s+/", " ", implode('', $scripts));
    }

    /**
     * @param string $addonShortName
     * @param string $addonName
     * @param string $licenseAccountUrl
     * @param string $status
     * @return string
     */
    public static function getInvalidNotice(string $addonShortName, string $addonName, string $licenseAccountUrl, string $status = '')
    {
        return '$(\'div[data-addon="'. $addonShortName .'"]\').append(\''. self::getRibbon('Unlicensed', $status) .'\');
                $(\'.global-alerts\').append(\'<div class="app-notice-license app-notice app-notice--banner app-notice---error" style="display: flex;"><div class="app-notice__tag"><span class="app-notice__icon"></span></div><div class="app-notice__content"><p>Unlicensed Add-on: <b>'. $addonName .'</b> does not have a valid license.</p><p>'. sprintf(self::BANNER_MESSAGE, $licenseAccountUrl) .'</p></p></div><a href="#" class="app-notice__controls js-notice-dismiss"><span class="app-notice__dismiss"></span><span class="hidden">close</span></a></div>\');';
    }

    /**
     * @param string $addonShortName
     * @param string $addonName
     * @param string $licenseAccountUrl
     * @param string $status
     * @return string
     */
    public static function getExpiredTrialNotice(string $addonShortName, string $addonName, string $licenseAccountUrl, string $status = 'expired')
    {
        return '$(\'div[data-addon="'. $addonShortName .'"]\').append(\''. self::getRibbon('Unlicensed', $status) .'\');
                $(\'.global-alerts\').append(\'<div class="app-notice-license app-notice app-notice--banner app-notice---error" style="display: flex;"><div class="app-notice__tag"><span class="app-notice__icon"></span></div><div class="app-notice__content"><p>Trial Expired: <b>'. $addonName .'</b>. You will need to purchase a full license from '. sprintf('<a href="%s">boldminded.com</a>', $licenseAccountUrl) .' to continue using '. $addonName .'.</p></div><a href="#" class="app-notice__controls js-notice-dismiss"><span class="app-notice__dismiss"></span><span class="hidden">close</span></a></div>\');';
    }

    /**
     * @param string $addonShortName
     * @param string $licenseAccountUrl
     * @param string $status
     * @return string
     */
    public static function getUpdateAvailableNotice(string $addonShortName, string $licenseAccountUrl, string $status = 'update_available')
    {
        return '$(\'div[data-addon="'. $addonShortName .'"]\').append(\''. self::getRibbon('Update Available', $status) .'\');
                if (window.location.href.indexOf(\''. $addonShortName .'\') !== -1) {
                    $(\'body.add-on-layout .main-nav__title\').css(\'position\', \'relative\').append(\'<a style="display:inline-block;vertical-align:middle;margin-left:15px;border: 2px solid #39d;background-color:#fff;font-weight:bold;color: #39d;padding: 2px 10px 1px 10px;border-radius: 5px;font-size: 12px;vertical-align: middle;" href="'. $licenseAccountUrl .'" target="_blank">Update Available</a>\').children(\'h1\').css({ \'display\': \'inline-block\', \'vertical-align\': \'middle\' });
                };';
    }

    /**
     * @param string $addonShortName
     * @param string $licenseAccountUrl
     * @param string $status
     * @return string
     */
    public static function getExpiredNotice(string $addonShortName, string $licenseAccountUrl, string $status = '')
    {
        return '$(\'div[data-addon="'. $addonShortName .'"]\').append(\''. self::getRibbon('Expired', $status) .'\');
                if (window.location.href.indexOf(\''. $addonShortName .'\') !== -1) {
                    $(\'body.add-on-layout .main-nav__title\').css(\'position\', \'relative\').append(\'<a style="display:inline-block;vertical-align:middle;margin-left:15px;background-color:#e82;font-weight:bold;color: #fff;padding: 2px 10px 1px 10px;border-radius: 5px;font-size: 12px;vertical-align: middle;" href="'. $licenseAccountUrl .'" target="_blank">License Expired</a>\').children(\'h1\').css({ \'display\':\'inline-block\', \'vertical-align\':\'middle\' });
                }';
    }

    /**
     * @param string $addonShortName
     * @param string $licenseAccountUrl
     * @param string $status
     * @return string
     */
    public static function getExpiringSoonNotice(string $addonShortName, string $licenseAccountUrl, string $status = '')
    {
        return '$(\'div[data-addon="'. $addonShortName .'"]\').append(\''. self::getRibbon('Expiring Soon', $status) .'\');
                if (window.location.href.indexOf(\''. $addonShortName .'\') !== -1) {
                    $(\'body.add-on-layout .main-nav__title\').css(\'position\', \'relative\').append(\'<a style="display:inline-block;vertical-align:middle;margin-left:15px;background-color:#e82;font-weight:bold;color: #fff;padding: 2px 10px 1px 10px;border-radius: 5px;font-size: 12px;vertical-align: middle;" href="'. $licenseAccountUrl .'" target="_blank">License Expiring Soon</a>\').children(\'h1\').css({ \'display\':\'inline-block\', \'vertical-align\':\'middle\' });
                }';
    }

    /**
     * @param string $message
     * @param string $status
     * @return string
     */
    public static function getRibbon(string $message, string $status)
    {
        $fontSize = strlen($status) > 7 ? ' style="font-size: 45%;"' : '';
        return '<div class="corner-ribbon-wrap"><div class="corner-ribbon top-left '. $status .' shadow" ' . $fontSize . '>'. $message .'</div></div>';
    }
}
