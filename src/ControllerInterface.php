<?php

namespace Litzinger\Basee;

/**
 * @package     ExpressionEngine
 * @category    Basee
 * @author      Brian Litzinger
 * @link        https://github.com/litzinger/basee
 * @license     MIT
 */

interface ControllerInterface
{
    /**
     * @param $page
     * @return string
     */
    public function render($page = '');
}
