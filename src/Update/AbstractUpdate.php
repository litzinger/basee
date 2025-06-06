<?php

namespace Litzinger\Basee\Update;

/**
 * @package     ExpressionEngine
 * @category    Basee
 * @author      Brian Litzinger
 * @link        https://github.com/litzinger/basee
 * @license     MIT
 */

abstract class AbstractUpdate
{
    /**
     * @var array
     */
    private $hookTemplate = [];

    /**
     * @param array $hooks
     */
    protected function addHooks($hooks = [])
    {
        if (empty($hooks)) {
            return;
        }

        $this->validateHookTemplate();

        foreach($hooks as $hook) {
            $hook = array_merge($this->getHookTemplate(), $hook);

            /** @var \CI_DB_result $query */
            $query = ee()->db->get_where('extensions', [
                'hook' => $hook['hook'],
                'class' => $hook['class'],
                'priority' => $hook['priority'],
            ]);

            if($query->num_rows() == 0) {
                ee()->db->insert('extensions', $hook);
            }
        }
    }

    /**
     * @param array $actions
     * @throws \Exception
     */
    protected function addActions($actions = [])
    {
        if (empty($actions)) {
            return;
        }

        foreach($actions as $action) {
            if (!isset($action['method']) || !isset($action['class'])) {
                throw new \Exception('Action keys are missing.');
            }

            /** @var \CI_DB_result $query */
            $query = ee()->db->get_where('actions', [
                'method' => $action['method'],
                'class' => $action['class'],
            ]);

            if($query->num_rows() == 0) {
                ee()->db->insert('actions', $action);
            }
        }
    }

    /**
     * @param $class
     * @param array $hooks
     */
    protected function removeHooks($class, $hooks = [])
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
     * @param $class
     * @param array $methods
     */
    protected function removeActions($class, $methods = [])
    {
        if (empty($methods)) {
            return;
        }

        ee()->db
            ->where_in('method', $methods)
            ->where('class', $class)
            ->delete('actions');
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
