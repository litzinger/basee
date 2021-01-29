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
        $this->addonName = $payload['a'];
        $this->payload = $payload;

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

        if (self::DEBUG || $ping->shouldPing()) {
            $ping->updateLastPing();

            $response = $this->checkLicense([
                'payload' => base64_encode(json_encode($this->payload))
            ]);

            if (self::DEBUG) {
                $response['status'] = 'invalid';
            }

            if (
                $response !== null && isset($response['status']) &&
                (!$this->payload['l'] || in_array($response['status'], self::STATUSES))
            ) {
                return $this->displayValidationMessage($response['status']);
            }
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
            $scripts[] = '
                $(\'div[data-addon="'. $this->addonShortName .'"]\').css(\'overflow\', \'hidden\').append(\'<div class="corner-ribbon top-left red shadow">Unlicensed</div>\');
                $(\'.global-alerts\').append(\'<div class="app-notice-license app-notice app-notice--banner app-notice---error" style="display: flex;"><div class="app-notice__tag"><span class="app-notice__icon"></span></div><div class="app-notice__content"><p>Unlicensed Add-on: <b>'. $this->addonName .'</b> does not have a valid license. <a href="'. $this->licenseAccountUrl .'" target="_blank">More Info</a></p></div><a href="#" class="app-notice__controls js-notice-dismiss"><span class="app-notice__dismiss"></span><span class="hidden">close</span></a></div>\');
            ';
        }

        if ($status === 'update_available') {
            $scripts[] = '
                $(\'div[data-addon="'. $this->addonShortName .'"]\').css(\'overflow\', \'hidden\').append(\'<div class="corner-ribbon top-left blue shadow" style="font-size:9px;">Update Available</div>\');
                if (window.location.href.indexOf(\''. $this->addonShortName .'\') !== -1) {
                    $(\'body.add-on-layout .main-nav__title\').css(\'position\', \'relative\').append(\'<a style="display:inline-block;vertical-align:middle;margin-left:15px;border: 2px solid #39d;background-color:#fff;font-weight:bold;color: #39d;padding: 2px 10px 1px 10px;border-radius: 5px;font-size: 12px;vertical-align: middle;" href="'. $this->licenseAccountUrl .'" target="_blank">Update Available</a>\').children(\'h1\').css({ \'display\': \'inline-block\', \'vertical-align\': \'middle\' });
                };
            ';
        }

        if ($status === 'expired') {
            $scripts[] = '$(\'div[data-addon="'. $this->addonShortName .'"]\').css(\'overflow\', \'hidden\').append(\'<div class="corner-ribbon top-left orange shadow">Expired</div>\');
                if (window.location.href.indexOf(\''. $this->addonShortName .'\') !== -1) {
                    $(\'body.add-on-layout .main-nav__title\').css(\'position\', \'relative\').append(\'<a style="display:inline-block;vertical-align:middle;margin-left:15px;background-color:#e82;font-weight:bold;color: #fff;padding: 2px 10px 1px 10px;border-radius: 5px;font-size: 12px;vertical-align: middle;" href="'. $this->licenseAccountUrl .'" target="_blank">License Expired</a>\').children(\'h1\').css({ \'display\':\'inline-block\', \'vertical-align\':\'middle\' });
                }
            ';
        }

        if (isset(ee()->cp)) {
            ee()->cp->add_to_foot('<script type="text/javascript">$(function(){'. preg_replace("/\s+/", " ", implode('', $scripts)) .'});</script>');

            return '';
        }

        return preg_replace("/\s+/", " ", implode('', $scripts));
    }
}
