<?php

namespace Basee;

/**
 * Utility functions to assist in making add-ons work in EE3, EE4, and EE5

 * @package     ExpressionEngine
 * @category    Basee
 * @author      Brian Litzinger
 * @copyright   Copyright (c) 2016-2018 - Brian Litzinger
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
    public static function isEE3()
    {
        return self::majorVersion() === 3;
    }

    /**
     * @return boolean
     */
    public static function isEE4()
    {
        return self::majorVersion() === 4;
    }

    /**
     * @return boolean
     */
    public static function isGteEE4()
    {
        return self::majorVersion() >= 4;
    }

    /**
     * @return boolean
     */
    public static function isEE5()
    {
        return self::majorVersion() === 5;
    }

    /**
     * @return boolean
     */
    public static function isGteEE5()
    {
        return self::majorVersion() >= 5;
    }

    /**
     * @return int
     */
    public static function majorVersion()
    {
        return (int) explode('.', APP_VER)[0];
    }

    /**
     * EE4 ditched the .box class which wraps most form views.
     *
     * @return string
     */
    public static function viewBoxClass()
    {
        return self::isEE3() ? 'box' : '';
    }

    /**
     * @return string
     */
    public static function viewFolder()
    {
        return self::isEE3() ? 'ee3/' : 'ee4/';
    }

    /**
     * Use a collection of native EE features and the version
     * in which they were released and possibly change functionality
     * of this add-on based on the current EE version.
     *
     * @param $featureName
     * @return bool
     */
    public static function isFeatureAvailable($featureName)
    {
        $features = [
            'livePreview' => '4.1',
            'batchEdit' => '4.1',
            'sequentialEditing' => '4.2',
            'createRelationship' => '4.2',
        ];

        if (array_key_exists($featureName, $features)) {
            return version_compare(APP_VER, $features[$featureName], '>=');
        }

        return false;
    }

    /**
     * @param $fieldName
     * @return string
     */
    public static function getFieldTableName($fieldName)
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
     * @param $string
     * @return string
     */
    public static function makeUrlTitle($string)
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
    public static function parseTagParameters($args, $defaults = [])
    {
        if (self::isEE3()) {
            return ee()->functions->assign_parameters($args, $defaults);
        }

        return ee('Variables/Parser')->parseTagParameters($args, $defaults);
    }

    /**
     * @param $var
     * @param string $prefix
     * @return mixed
     */
    public static function parseVariableProperties($var, $prefix = '')
    {
        if (self::isEE3()) {
            ee()->load->library('api');
            ee()->legacy_api->instantiate('channel_fields');
            return ee()->api_channel_fields->get_single_field($var, $prefix);
        }

        return ee('Variables/Parser')->parseVariableProperties($var, $prefix);
    }
}
