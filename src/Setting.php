<?php

namespace Litzinger\Basee;

/**
 * @package     ExpressionEngine
 * @category    Basee
 * @author      Brian Litzinger
 * @link        https://github.com/litzinger/basee
 * @license     MIT
 */

class Setting
{
    protected static $_primary_key = 'id';

    /**
     * Merged array of settings used internally.
     *
     * @var array
     */
    private $settings = [];

    /**
     * @var string
     */
    private $tableName;

    /**
     * Default settings that apply to each site. If an MSM install site_id will be N.
     *
     * @var array
     */
    private $defaultSettings = [];

    /**
     * Settings that apply to all sites. If an MSM install, site_id will be 0
     *
     * @var array
     */
    private $globalSettings = [
        'installed_date' => '',
        'installed_version' => '',
        'installed_build' => '',
        'license' => '',
    ];

    /**
     * Load the settings immediately
     */
    public function __construct($tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * @param array $post
     * @return bool|array
     */
    public function save(array $post = [])
    {
        $errors = [];

        foreach ($this->getAllSettings() as $key => $value) {
            // Since settings are split up in separate pages, make sure
            // we're only updating settings that we've posted values for
            // not all of the default settings. Otherwise things get overwritten.
            if (!isset($post[$key])) {
                continue;
            }

            $where = ['key' => $key];

            // If set, use the posted value, otherwise use default value.
            // This is mostly to handle the checkbox groups that don't post
            // anything if nothing is selected.
            $value = isset($post[$key]) ? $post[$key] : $value;

            if (is_array($value)) {
                $value = json_encode($value);
                $data['type'] = 'json';
            } elseif (in_array($value, ['yes', 'no', 'y', 'n'])) {
                $value = ($value == 'yes' || $value == 'y') ? 'yes' : 'no';
                $data['type'] = 'boolean';
            } else {
                $data['type'] = 'string';
            }

            $where['site_id'] = ee()->config->item('site_id');
            $data['site_id'] = ee()->config->item('site_id');
            $data['key'] = $key;
            $data['val'] = $value;

            if ($data['site_id'] !== 0 && in_array($key, $this->getGlobalSettings())) {
                $data['site_id'] = 0;
                $where['site_id'] = 0;
            }

            /** @var \CI_DB_result $query */
            $result = $this->insertOrUpdate($this->tableName, $data, $where);

            if (!$result) {
                $errors[] = $data;
            }
        }

        if (!empty($errors)) {
            return $errors;
        }

        return true;
    }

    /**
     * Getter for all settings
     *
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @param string    $key
     * @param mixed     $value
     * @param int       $siteId
     * @return $this
     */
    public function set(string $key, $value, int $siteId = 1)
    {
        if ($key && $value) {
            $this->settings[$siteId][$key] = $value;
        }

        return $this;
    }

    /**
     * @param $key
     * @param null $siteId
     * @return bool|mixed|null
     */
    public function get($key, $siteId = null)
    {
        if (empty($this->settings)) {
            $this->load();
        }

        // Still empty? Probably an add-on upgrade that didn't use Basee for its original install.
        if (empty($this->settings)) {
            return false;
        }

        $siteId = $siteId ?: ee()->config->item('site_id');
        $settings = $this->settings[$siteId];

        return $settings[$key] ?? false;
    }

    /**
     * @param $siteId
     * @return array
     */
    public function getAll($siteId = null): array
    {
        $siteId = $siteId ?: ee()->config->item('site_id');

        return $this->settings[$siteId] ?? [];
    }

    /**
     * Load the module settings.
     *
     * @return void
     */
    public function load()
    {
        $db = ee('db');
        $dbSettings = [];
        $globalSettings = [];
        $settingTypes = [];

        if (!$db->table_exists($this->tableName)) {
            return;
        }

        /** @var \CI_DB_result $settingsQuery */
        $settingsQuery = $db->get($this->tableName);

        foreach ($settingsQuery->result() as $row) {
            // json_decode on non-json strings returns null.
            if ($row->type == 'json') {
                $dbSettings[$row->site_id][$row->key] = json_decode($row->val, true);
                // If somehow the setting was saved as [""] filter it out
                $dbSettings[$row->site_id][$row->key] = array_filter(
                    $dbSettings[$row->site_id][$row->key],
                    function ($item) {
                        return $item !== '';
                    }
                    );
            } else {
                $dbSettings[$row->site_id][$row->key] = $row->val;
            }

            $settingTypes[$row->key] = $row->type;
        }

        /** @var \CI_DB_result $globalSettingsQuery */
        $globalSettingsQuery = $db->get_where($this->tableName, ['site_id' => 0]);
        foreach ($globalSettingsQuery->result() as $row) {
            $globalSettings[$row->key] = $row->val;
        }

        /** @var \CI_DB_result $sitesQuery */
        $sitesQuery = $db->get('sites');

        foreach ($sitesQuery->result() as $row) {
            $this->settings[$row->site_id] = !empty($dbSettings[$row->site_id]) ? array_merge($this->defaultSettings, $dbSettings[$row->site_id]) : $this->defaultSettings;
            $this->settings[$row->site_id] = array_replace($this->settings[$row->site_id], $globalSettings);
        }

        // Change yes/no values to true/false
        array_walk_recursive($this->settings, [$this, 'filterSettings']);

        foreach ($this->settings as $siteId => $settings) {
            $this->settings[$siteId]['__TYPES__'] = $settingTypes;
        }
    }

    /**
     * @return array
     */
    public function getDefaultSettings()
    {
        return $this->defaultSettings;
    }

    /**
     * @param array $defaultSettings
     */
    public function setDefaultSettings($defaultSettings)
    {
        $this->defaultSettings = $defaultSettings;

        return $this;
    }

    /**
     * @return array
     */
    public function getGlobalSettings()
    {
        return $this->globalSettings;
    }

    /**
     * @param array $globalSettings
     */
    public function setGlobalSettings($globalSettings)
    {
        $this->globalSettings = $globalSettings;

        return $this;
    }

    /**
     * This should be called "getAllAvailableSettings" or "getAllSettingOptions" because
     * it does not return the actual saved settings values, just the keys and default values.
     * Use getAll() to get all active/saved settings.
     *
     * @return array
     */
    public function getAllSettings()
    {
        if (!empty($this->getGlobalSettings())) {
            return array_replace($this->getDefaultSettings(), $this->getGlobalSettings());
        }

        return $this->getDefaultSettings();
    }

    /**
     * Transform yes/no values into true/false
     *
     * @param array value by reference
     * @param string key
     * @return void
     */
    private function filterSettings(&$value)
    {
        if (in_array($value, ['yes', 'no', 'y', 'n'])) {
            $value = $value === 'yes' || $value === 'y';
        }
    }

    /**
     * @param        $table
     * @param        $data
     * @param        $where
     * @param string $primary_key
     * @return bool
     */
    private function insertOrUpdate($table, $data, $where, $primary_key = 'id')
    {
        $db = ee('db');

        /** @var \CI_DB_result $query */
        $query = $db->get_where($table, $where);

        // No records were found, so insert
        if ($query->num_rows() == 0) {
            $db->insert($table, $data);
            return $db->insert_id();
        }
        // Update existing record
        elseif ($query->num_rows() == 1) {
            $db->where($where)->update($table, $data);
            return $db->select($primary_key)->from($table)->where($where)->get()->row($primary_key);
        }

        return false;
    }

    public function createTable()
    {
        $db = ee('db');

        // Required for installation to work. It caches the tables and does not see the newly added tables.
        $db->data_cache = [];

        if ($db->table_exists($this->tableName)) {
            return;
        }

        ee()->load->dbforge();
        ee()->dbforge->add_field([
            'id'        => ['type' => 'int', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'site_id'   => ['type' => 'int', 'constraint' => 4, 'unsigned' => true, 'default' => 1],
            'key'       => ['type' => 'varchar', 'constraint' => 32],
            'val'       => ['type' => 'text'],
            'type'      => ['type' => 'char', 'constraint' => 7]
        ]);

        ee()->dbforge->add_key('id', true);
        ee()->dbforge->create_table($this->tableName);
    }

    public function dropTable()
    {
        ee()->load->dbforge();
        ee()->dbforge->drop_table($this->tableName);
    }
}
