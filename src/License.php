<?php

namespace Basee;

class License
{
    const DEBUG = false;

    const STATUSES = [
        'invalid',
        'update_available',
        'expired',
    ];

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
    private $licenseAccountUrl = 'https://boldminded.com/account/licenses';

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
            $this->licenseAccountUrl = $licenseAccountUrl;
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
        // @todo get appropriate styles and markup for EE 5
        if (App::isLtEE6()) {
            return '';
        }

        $scripts = [];

        if ($status === 'invalid') {
            $scripts[] = self::getInvalidNotice($this->addonShortName, $this->addonName, $this->licenseAccountUrl);
        }

        if ($status === 'update_available') {
            $scripts[] = self::getUpdateAvailableNotice($this->addonShortName, $this->licenseAccountUrl);
        }

        if ($status === 'expired') {
            $scripts[] = self::getExpiredNotice($this->addonShortName, $this->licenseAccountUrl);
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
     * @return string
     */
    public static function getInvalidNotice(string $addonShortName, string $addonName, string $licenseAccountUrl)
    {
        return '$(\'div[data-addon="'. $addonShortName .'"]\').append(\''. self::getRibbon('Unlicensed', 'red') .'\');
                $(\'.global-alerts\').append(\'<div class="app-notice-license app-notice app-notice--banner app-notice---error" style="display: flex;"><div class="app-notice__tag"><span class="app-notice__icon"></span></div><div class="app-notice__content"><p>Unlicensed Add-on: <b>'. $addonName .'</b> does not have a valid license. <a href="'. $licenseAccountUrl .'" target="_blank">More Info</a></p></div><a href="#" class="app-notice__controls js-notice-dismiss"><span class="app-notice__dismiss"></span><span class="hidden">close</span></a></div>\');';
    }

    /**
     * @param string $addonShortName
     * @param string $licenseAccountUrl
     * @return string
     */
    public static function getUpdateAvailableNotice(string $addonShortName, string $licenseAccountUrl)
    {
        return '$(\'div[data-addon="'. $addonShortName .'"]\').append(\''. self::getRibbon('Update Available', 'blue') .'\');
                if (window.location.href.indexOf(\''. $addonShortName .'\') !== -1) {
                    $(\'body.add-on-layout .main-nav__title\').css(\'position\', \'relative\').append(\'<a style="display:inline-block;vertical-align:middle;margin-left:15px;border: 2px solid #39d;background-color:#fff;font-weight:bold;color: #39d;padding: 2px 10px 1px 10px;border-radius: 5px;font-size: 12px;vertical-align: middle;" href="'. $licenseAccountUrl .'" target="_blank">Update Available</a>\').children(\'h1\').css({ \'display\': \'inline-block\', \'vertical-align\': \'middle\' });
                };';
    }

    /**
     * @param string $addonShortName
     * @param string $licenseAccountUrl
     * @return string
     */
    public static function getExpiredNotice(string $addonShortName, string $licenseAccountUrl)
    {
        return '$(\'div[data-addon="'. $addonShortName .'"]\').append(\''. self::getRibbon('Expired', 'orange') .'\');
                if (window.location.href.indexOf(\''. $addonShortName .'\') !== -1) {
                    $(\'body.add-on-layout .main-nav__title\').css(\'position\', \'relative\').append(\'<a style="display:inline-block;vertical-align:middle;margin-left:15px;background-color:#e82;font-weight:bold;color: #fff;padding: 2px 10px 1px 10px;border-radius: 5px;font-size: 12px;vertical-align: middle;" href="'. $licenseAccountUrl .'" target="_blank">License Expired</a>\').children(\'h1\').css({ \'display\':\'inline-block\', \'vertical-align\':\'middle\' });
                }';
    }

    /**
     * @param string $message
     * @param string $color
     * @return string
     */
    public static function getRibbon(string $message, string $color)
    {
        return '<div style="position: absolute; overflow: hidden; top: 0; left: 0; width: 100px; height: 100%;"><div class="corner-ribbon top-left '. $color .' shadow">'. $message .'</div></div>';
    }
}
