<?php

namespace Basee;

/**
 * Utility functions to assist in making add-ons work in EE3, EE4, and EE5

 * @package     ExpressionEngine
 * @category    Basee
 * @author      Brian Litzinger
 * @copyright   Copyright (c) 2019 - BoldMinded, LLC
 * @link        https://github.com/litzinger/basee
 * @license     MIT
 */

class App
{
    public static function eeVersion()
    {
        return APP_VER;
    }

    /**
     * @return boolean
     */
    public static function isEE3(): bool
    {
        return self::majorVersion() === 3;
    }

    /**
     * @return boolean
     */
    public static function isEE4(): bool
    {
        return self::majorVersion() === 4;
    }

    /**
     * @return boolean
     */
    public static function isGteEE4(): bool
    {
        return self::majorVersion() >= 4;
    }

    /**
     * @return boolean
     */
    public static function isLtEE4(): bool
    {
        return self::majorVersion() < 4;
    }

    /**
     * @return boolean
     */
    public static function isEE5(): bool
    {
        return self::majorVersion() === 5;
    }

    /**
     * @return boolean
     */
    public static function isGteEE5(): bool
    {
        return self::majorVersion() >= 5;
    }

    /**
     * @return boolean
     */
    public static function isLtEE5(): bool
    {
        return self::majorVersion() < 5;
    }

    /**
     * @return boolean
     */
    public static function isEE6(): bool
    {
        return self::majorVersion() === 6;
    }

    /**
     * @return boolean
     */
    public static function isGteEE6(): bool
    {
        return self::majorVersion() >= 6;
    }

    /**
     * @return boolean
     */
    public static function isLtEE6(): bool
    {
        return self::majorVersion() < 6;
    }

    /**
     * @return int
     */
    public static function majorVersion(): int
    {
        return (int) explode('.', APP_VER)[0];
    }

    /**
     * EE4 ditched the .box class which wraps most form views.
     *
     * @return string
     */
    public static function viewBoxClass(): string
    {
        return self::isEE3() ? 'box' : '';
    }

    /**
     * @return string
     */
    public static function viewFolder(): string
    {
        return self::isEE3() ? 'ee3/' : 'ee4/';
    }

    /**
     * @return string
     */
    public static function roleIdAttributeName(): string
    {
        return self::isLtEE6() ? 'group_id' : 'role_id';
    }

    /**
     * @param string $attributeName
     * @return mixed
     */
    public static function userData(string $attributeName)
    {
        if ($attributeName === 'role_id' && self::isLtEE6()) {
            $attributeName = 'group_id';
        }

        return ee()->session->userdata[$attributeName] ?? '';
    }

    /**
     * Use a collection of native EE features and the version
     * in which they were released and possibly change functionality
     * of this add-on based on the current EE version.
     *
     * @param string $featureName
     * @return bool
     */
    public static function isFeatureAvailable(string $featureName): bool
    {
        $features = [
            'livePreview' => '4.1',
            'batchEdit' => '4.1',
            'sequentialEditing' => '4.2',
            'createRelationship' => '4.2',
            'fileGrid' => '5.1',
            'entryEditList' => '6.0', // Improved entry edit list screen
            'jumpMenu' => '6.0',
        ];

        if (array_key_exists($featureName, $features)) {
            return version_compare(APP_VER, $features[$featureName], '>=');
        }

        return false;
    }

    /**
     * @param string $fieldName
     * @return string
     */
    public static function getFieldTableName(string $fieldName): string
    {
        $db = ee('db');

        if (self::isEE3()) {
            return $db->dbprefix . 'channel_data';
        }

        $fieldId = str_replace('field_id_', '', $fieldName);
        $db->data_cache = [];
        $fieldTableExists = $db->table_exists('channel_data_field_'. $fieldId);

        // Must be a legacy field in EE4
        if (!$fieldTableExists) {
            return $db->dbprefix . 'channel_data';
        }

        // Looks like a new field in EE4
        return $db->dbprefix.'channel_data_field_' . $fieldId;
    }

    /**
     * @param string $string
     * @return string
     */
    public static function makeUrlTitle(string $string): string
    {
        if (self::isEE3()) {
            ee()->load->helper('url_helper');
            return url_title($string);
        }

        return (string) ee('Format')->make('Text', $string)->urlSlug();
    }

    /**
     * @param string $args
     * @return array
     */
    public static function parseTagParameters(string $args, array $defaults = []): array
    {
        if (self::isEE3()) {
            return ee()->functions->assign_parameters($args, $defaults);
        }

        return ee('Variables/Parser')->parseTagParameters($args, $defaults);
    }

    /**
     * @param string $var
     * @param string $prefix
     * @return array
     */
    public static function parseVariableProperties(string $var, string $prefix = ''): array
    {
        if (self::isEE3()) {
            ee()->load->library('api');
            ee()->legacy_api->instantiate('channel_fields');
            return ee()->api_channel_fields->get_single_field($var, $prefix);
        }

        return ee('Variables/Parser')->parseVariableProperties($var, $prefix);
    }

    /**
     * @param string    $name
     * @param int|null  $siteId
     * @return mixed|string
     */
    public static function config($name, $siteId = null)
    {
        if ($siteId === null) {
            return ee()->config->item($name);
        }

        if (self::isLtEE6()) {
            $prefs = ee('db')
                ->select('site_system_preferences')
                ->where('site_id', $siteId)
                ->get('sites')
                ->row('site_system_preferences');

            $prefs = unserialize(base64_decode($prefs));

            if (isset($prefs[$name])) {
                return parse_config_variables($prefs[$name]);
            }

            return null;
        }

        /** @var \CI_DB_result $pref */
        $pref = ee('db')
            ->select('value')
            ->where('site_id', $siteId)
            ->where('key', $name)
            ->get('config');

        if ($pref->num_rows() !== 1) {
            return null;
        }

        return parse_config_variables($pref->row('value'));
    }

    /**
     * @param string $name
     * @param null $siteId
     * @return mixed|string
     */
    public static function configSlashed(string $name, $siteId = null)
    {
        $value = self::config($name, $siteId);

        if ($value != '' && substr($value, -1) != '/') {
            $value .= '/';
        }

        if (defined('EE_APPPATH')) {
            $value = str_replace(APPPATH, EE_APPPATH, $value);
        }

        return $value;
    }

    /**
     * @param string $addonName
     * @return bool
     */
    public static function isInstallingAddon(string $addonName): bool
    {
        $path = implode('/', ee()->uri->segments);

        return boolval(preg_match('#cp\/(addons\/install)\/'. $addonName .'#', $path, $matches));
    }

    /**
     * @param string $addonName
     * @return bool
     */
    public static function isUninstallingAddon(string $addonName): bool
    {
        $path = implode('/', ee()->uri->segments);

        // EE6+
        if (preg_match('#cp\/(addons\/remove)\/'. $addonName .'#', $path, $matches)) {
            return true;
        }

        // Legacy
        return (isset($_POST['bulk_action']) && $_POST['bulk_action'] === 'remove' &&
            isset($_POST['selection']) && !empty($_POST['selection']) &&
            in_array($addonName, $_POST['selection'])
        );
    }

    /**
     * Ajax request isn't really installing or uninstalling, but if an add-on is making an ajax request in the
     * background, and the timing of a license check happens to be triggered from the ajax request, it can cause
     * the request to fail and may be hard to debug.
     *
     * @param $addonName
     * @return bool
     */
    public static function isInstallingOrUninstallingAddon(string $addonName): bool
    {
        return (AJAX_REQUEST || self::isInstallingAddon($addonName) || self::isUninstallingAddon($addonName));
    }
}
