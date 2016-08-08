<?php

namespace Basee\Update;

/**
 * @package     ExpressionEngine
 * @category    Basee
 * @author      Brian Litzinger
 * @copyright   Copyright (c) 2016 - Brian Litzinger
 * @link        https://github.com/litzinger/basee
 * @license     MIT
 */

abstract class AbstractUpdate
{
    /**
     * @var array
     */
    private $hookTemplate = array();

    /**
     * @param array $hooks
     */
    protected function addHooks($hooks = array())
    {
        if (empty($hooks)) {
            return;
        }

        $this->validateHookTemplate();

        foreach($hooks as $hook) {
            $hook = array_merge($this->getHookTemplate(), $hook);

            /** @var \CI_DB_result $query */
            $query = ee()->db->get_where('extensions', array(
                'hook' => $hook['hook'],
                'class' => $hook['class']
            ));

            if($query->num_rows() == 0) {
                ee()->db->insert('extensions', $hook);
            }
        }
    }

    /**
     * @param $class
     * @param array $hooks
     */
    protected function removeHooks($class, $hooks = array())
    {
        if (empty($hooks)) {
            return;
        }

        ee()->db
            ->where_in('hook', $hooks)
            ->where('class', $class)
            ->delete('extensions');
    }

    /**
     * @return array
     */
    public function getHookTemplate()
    {
        return $this->hookTemplate;
    }

    /**
     * @param array $hookTemplate
     * @return $this
     */
    public function setHookTemplate($hookTemplate)
    {
        $this->hookTemplate = $hookTemplate;

        $this->validateHookTemplate();

        return $this;
    }

    /**
     * @throws \Exception
     */
    private function validateHookTemplate()
    {
        $diff = array_diff(array_keys(
            $this->getHookTemplate()),
            array('class', 'settings', 'priority', 'version', 'enabled')
        );

        if (!empty($diff)) {
            throw new \Exception('Hook template keys are missing.');
        }
    }
}
