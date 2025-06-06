<?php

namespace Litzinger\Basee;

/**
 * Utility functions to assist in making add-ons work in EE3, EE4, and EE5

 * @package     ExpressionEngine
 * @category    Basee
 * @author      Brian Litzinger
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
     * @return boolean
     */
    public static function isEE7(): bool
    {
        return self::majorVersion() === 7;
    }

    /**
     * @return boolean
     */
    public static function isGteEE7(): bool
    {
        return self::majorVersion() >= 7;
    }

    /**
     * @return boolean
     */
    public static function isLtEE7(): bool
    {
        return self::majorVersion() < 7;
    }

    /**
     * @return boolean
     */
    public static function isEE8(): bool
    {
        return self::majorVersion() === 8;
    }

    /**
     * @return boolean
     */
    public static function isGteEE8(): bool
    {
        return self::majorVersion() >= 8;
    }

    /**
     * @return boolean
     */
    public static function isLtEE8(): bool
    {
        return self::majorVersion() < 8;
    }

    /**
     * @return int
     */
    public static function majorVersion(): int
    {
        $version = ee()->config->config['app_version'] ?? 0;

        // If an add-on using this is present when EE is installed, it won't find the version.
        if (\defined('INSTALLER') && INSTALLER === true) {
            return 0;
        }

        if (defined('APP_VER')) {
            $version = APP_VER;
        }

        if (!$version) {
            throw new \Exception('Unable to determine ExpressionEngine version.');
        }

        return (int) explode('.', $version)[0];
    }

    /**
     * @return int
     */
    public static function phpMajorVersion(): int
    {
        return (int) explode('.', PHP_VERSION)[0];
    }

    /**
     * @return float
     */
    public static function phpVersion(): float
    {
        return PHP_VERSION;
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
     * @return array|string[]
     */
    public static function userRoleIds(): array
    {
        if (self::isLtEE6()) {
            return [self::userData('role_id')];
        }

        if (isset(ee()->session) && ee()->session->getMember()) {
            return ee()->session->getMember()->getAllRoles()->pluck('role_id');
        }

        return [];
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
            'cloning' => '6.2.5',
            'revisionAlert' => '6.4',
            'newFileManager' => '7.1',
            'fieldNameHints' => '7.4',
            'categoryPermissions' => '7.4',
            'generators' => '7.5',
            'formValidation' => '7.5',
        ];

        if ($featureName === 'pro') {
            return defined('IS_PRO') && IS_PRO;
        }

        if ($featureName === 'msm') {
            return bool_config_item('multiple_sites_enabled');
        }

        if (array_key_exists($featureName, $features)) {
            return version_compare(APP_VER, $features[$featureName], '>=');
        }

        return false;
    }

    /**
     * @param string $addonName
     * @return bool
     */
    public static function isAddonInstalled(string $addonName): bool
    {
        $addon = ee('Addon')->get($addonName);

        return $addon && $addon->isInstalled();
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
     * @param string $tagdata
     * @param string $var
     * @param string $prefix
     * @return array
     */
    public static function parseTagPair(string $tagdata, string $var, string $prefix = ''): array
    {
        if (!isset(ee()->api_channel_fields)) {
            ee()->load->library('api');
            ee()->legacy_api->instantiate('channel_fields');
        }

        $pairs = ee()->api_channel_fields->get_pair_field($tagdata, $var, $prefix);

        $found = [];

        foreach ($pairs as $pair) {
            $found[] = [
                'modifier' => $pair[0],
                'content' => $pair[1],
                'params' => $pair[2],
                'chunk' => $pair[3],
            ];
        }

        return $found;
    }

    /**
     * set_data() is only available in EE 7.2.1+ for Coilpack support.
     * Variables passed to setTemplateData() will be available in Twig or Blade based templates.
     *
     * @param array $data
     * @return void
     */
    public static function setTemplateData(array $data = [])
    {
        if (isset(ee()->TMPL) && method_exists(ee()->TMPL, 'set_data')) {
            ee()->TMPL->set_data($data);
        }
    }

    /**
     * If Coilpack is installed, provide non-prefixed version that is available at {{ global.$key }},
     * otherwise it is only available at {{ global.global.$key }} since Coilpack automagically turns
     * global vars with : acting as a namespace/prefix into nested arrays.
     *
     * @param array $data
     * @return void
     */
    public static function setGlobalData(array $data = [])
    {
        foreach ($data as $key => $value) {
            $key = str_replace('global:', '', $key);

            if (isset(ee()->TMPL) && method_exists(ee()->TMPL, 'set_data')) {
                ee()->config->_global_vars[$key] = $value;
            }

            ee()->config->_global_vars['global:' . $key] = $value;
        }
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

        return boolval(preg_match('#cp\/(addons\/install)\/' . $addonName .'#', $path, $matches));
    }

    /**
     * @param string $addonName
     * @return bool
     */
    public static function isUpdatingAddon(string $addonName = ''): bool
    {
        $path = implode('/', ee()->uri->segments);

        // Updating any add-on?
        if (!$addonName) {
            return boolval(preg_match('#cp\/(addons\/update)/#', $path, $matches));
        }

        return boolval(preg_match('#cp\/(addons\/update)\/'. $addonName .'#', $path, $matches));
    }

    /**
     * @param string $addonName
     * @return bool
     */
    public static function isUninstallingAddon(string $addonName): bool
    {
        $path = implode('/', ee()->uri->segments);

        // EE6+
        if (self::isGteEE6()) {
            return preg_match('#cp\/(addons\/remove)\/' . $addonName .'#', $path, $matches);
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

    /**
     * Could be any bulk edit request on an entry or collection of entries.
     *
     * @return bool
     */
    public static function isBulkEditRequest(): bool
    {
        $uri = ee()->uri->uri_string;
        return $uri && preg_match("/^cp\/publish\/bulk-edit/", $uri) && !empty(ee()->input->get_post('entry_ids'));
    }

    /**
     * A bulk edit request to add or remove categories for an entry.
     *
     * @return bool
     */
    public static function isBulkEditCategoryRequest(): bool
    {
        $uri = ee()->uri->uri_string;
        return $uri && preg_match("/^cp\/publish\/bulk-edit\/categories\/save-/", $uri) && !empty(ee()->input->get_post('entry_ids'));
    }

    /**
     * @deprecated
     *
     * @return bool
     */
    public static function isSequentialEditRequest(): bool
    {
        return self::isModalEntryFormRequest();
    }

    /**
     * Can be true if it's a Relationship slide out editing form, a sequential/multi edit form from the cp/publish/edit
     * entries list, or a front-edit request when editing a single field on the front-end outside the CP.
     *
     * @return bool
     */
    public static function isModalEntryFormRequest(): bool
    {
        return !empty(ee()->input->get('entry_ids')) && ee()->input->get('modal_form') === 'y';
    }

    /**
     * Specifically when editing on the front-end outside of the CP. After a specific field is updated it'll refresh
     * the page to the return value. This is the only place the return param nad modal_form param exist together.
     *
     * @return bool
     */
    public static function isFrontEditRequest(): bool
    {
        // This is not a native EE query string param. Add-on deva can use this as a short cut.
        if (ee()->input->get('is_frontedit') === 'y') {
            return true;
        }

        return !empty(ee()->input->get('entry_ids')) &&
            ee()->input->get('modal_form') === 'y' &&
            ee()->input->get('field_id') &&
            ee()->input->get('return')
            ;
    }

    public static function isCloningRequest(): bool
    {
        return defined('CLONING_MODE') && CLONING_MODE === true;
    }

    public static function isActionRequest(): bool
    {
        return REQ === 'ACTION';
    }

    public static function isLivePreviewRequest(): bool
    {
        // EE6+ Live Preview request support
        if (self::isGteEE6() && self::isActionRequest()) {
            $action = ee('Model')->get('Action')
                ->filter('class', 'Channel')
                ->filter('method', 'live_preview')
                ->first();

            return $action->action_id && (int) ee()->input->get('ACT') === $action->action_id;
        }

        // Legacy/EE5 Live Preview request support
        // Before we attempt to call the ee:LivePreview service lets make sure its available.
        $isLivePreviewAvailable = self::isFeatureAvailable('livePreview');
        $isLivePreviewRequestUri = preg_grep('/cp\/publish\/preview\/(\d+)/', array_keys($_REQUEST));

        return $isLivePreviewAvailable && !empty($isLivePreviewRequestUri);
    }

    /**
     * @return bool
     */
    public static function isRevisionSaveRequest(): bool
    {
        // Native EE way of checking if user is saving a Revision
        if (ee()->input->get('version') && !empty($_POST)) {
            return true;
        }

        // Hidden field specific to Bloqs, but is basically deprecated due to above
        return boolval(ee()->input->post('version_number'));
    }

    /**
     * @return bool
     */
    public static function isRevisionViewRequest(): bool
    {
        return ee()->input->get('version');
    }

    /**
     * @return bool
     */
    public static function isEntryPublishRequest(): bool
    {
        return preg_match('/cp\/publish\/edit\/entry/', ee()->uri->uri_string) || preg_match('/cp\/publish\/create/', ee()->uri->uri_string);
    }

    /**
     * @return int
     */
    public static function getEntryIdFromRequest(): int
    {
        if (preg_match('/cp\/publish\/edit\/entry/', ee()->uri->uri_string) && ee()->uri->segment(5)) {
            return (int) ee()->uri->segment(5);
        }

        return 0;
    }
}
