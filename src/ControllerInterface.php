<?php

namespace Litzinger\Basee;

/**
 * @package     ExpressionEngine
 * @category    Basee
 * @author      Brian Litzinger
 * @copyright   Copyright (c) 2022 - BoldMinded, LLC
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
