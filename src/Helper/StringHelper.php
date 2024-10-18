<?php

namespace Litzinger\Basee\Helper;

/**
 * @package     ExpressionEngine
 * @category    Basee
 * @author      Brian Litzinger
 * @link        https://github.com/litzinger/basee
 * @license     MIT
 */

class StringHelper
{
    /**
     * @param $string
     * @param null|string $separator
     * @return string
     */
    public static function slugify($string, $separator = null)
    {
        if (!$separator) {
            $separator = ee()->config->item('word_separator') == 'dash' ? '-' : '_';
        }

        return preg_replace('/[^a-zA-Z0-9]/', $separator, strtolower($string));
    }

    /**
     * @param $word
     * @return string
     */
    public static function classify($word)
    {
        return str_replace(' ', '', ucwords(str_replace(array('-', '_'), ' ', $word)));
    }

    /**
     * @param $word
     * @return string
     */
    public static function titleCase($word)
    {
        return ucwords(str_replace(array('-', '_'), ' ', $word));
    }

    /**
     * @param $word
     * @return string
     */
    public static function camelize($word)
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace(array('-', '_'), ' ', $word))));
    }

    /**
     * @param $word
     * @return string
     */
    public function decamelize($word)
    {
        return ltrim(strtolower(preg_replace('/[A-Z]/', '_$0', $word)), '_');
    }

    /**
     * Prefix a string with a defined character. Return string if it already has prefix.
     *
     * @param string $str
     * @param string $prefix
     * @return string
     */
    public static function addPrefix($str, $prefix = '/')
    {
        if ($str != '' && $str[0] !== $prefix) {
            return $prefix.$str;
        }

        return $str;
    }

    /**
     * Suffix a string with a defined character. Return string if it already has suffix.
     *
     * @param string $str
     * @param string $suffix
     * @return string
     */
    public static function addSuffix($str, $suffix = '/')
    {
        if ($str != '' && substr($str, -1, 1) !== $suffix) {
            return $str.$suffix;
        }

        return $str;
    }
}
