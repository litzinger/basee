<?php

namespace Basee;

/**
 * @package     ExpressionEngine
 * @category    Basee
 * @author      Brian Litzinger
 * @copyright   Copyright (c) 2016-2019 - Brian Litzinger
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
