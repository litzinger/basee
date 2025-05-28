<?php

namespace Litzinger\Basee;

/**
 * @package     ExpressionEngine
 * @category    Basee
 * @author      Brian Litzinger
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
    /**
     * @param string $status
     * @return string
     */
    public function displayValidationMessage(string $status)
    {
        $scripts = [];

        if ($status === 'update_available') {
            $scripts[] = self::getUpdateAvailableNotice($this->addonShortName, self::$licenseAccountUrl, $status);
        }

        if (isset(ee()->cp)) {
            ee()->cp->add_to_foot('<script type="text/javascript">$(function(){' . \preg_replace("/\\s+/", " ", \implode('', $scripts)) . '});</script>');
            return '';
        }

        return \preg_replace("/\\s+/", " ", \implode('', $scripts));
    }

    /**
     * @param string $addonShortName
     * @param string $licenseAccountUrl
     * @param string $status
     * @return string
     */
    public static function getUpdateAvailableNotice(string $addonShortName, string $licenseAccountUrl)
    {
        return '$(\'div[data-addon="' . $addonShortName . '"] .add-on-card__text\').append(\'<p class="license-status-badge license-status-update_available"><b>Update Available</b></p>\');
                if (window.location.href.indexOf(\'' . $addonShortName . '\') !== -1 && $(\'body.add-on-layout .main-nav__title .license-status-badge\').length === 0) {
                    $(\'body.add-on-layout .main-nav__title\').css(\'position\', \'relative\').append(\'<a class="license-status-badge license-status-update_available" href="' . $licenseAccountUrl . '" target="_blank">Update Available</a>\').children(\'h1\').css({ \'display\': \'inline-block\', \'vertical-align\': \'middle\' });
                };';
    }
}
