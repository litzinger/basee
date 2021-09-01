<?php

namespace Basee;

class Parser
{
    /**
     * @var string
     */
    private $template = '';

    /**
     * @var array
     */
    private $variables = [];

    /**
     * @var array
     */
    private $scopedVariables = [];

    /**
     * @param string $template
     */
    public function __construct(string $template)
    {
        $this->template = $template;
    }

    /**
     * @return array
     */
    public function getVariables(): array
    {
        return $this->variables;
    }

    /**
     * @param array $variables
     * @return $this
     */
    public function setVariables(array $variables): Parser
    {
        $this->variables = $variables;

        return $this;
    }

    /**
     * @return array
     */
    public function getScopedVariables(): array
    {
        return $this->scopedVariables;
    }

    /**
     * @param array $scopedVariables
     * @return $this
     */
    public function setScopedVariables(array $scopedVariables): Parser
    {
        $this->scopedVariables = $scopedVariables;

        return $this;
    }

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * Borrowed directly from EE Core's Template class. This is how it parses layout:set variables, but with
     * slight modifications we can use it to parse any {[namespace]:set:[command] name="foo" value="bar"} variable.
     *
     * @param string $namespace
     * @param string $action
     * @return $this
     */
    public function findVariables(string $namespace, string $action = 'set'): Parser
    {
        // Don't collide with native EE variables
        if ($namespace === 'layout') {
            show_error('The "layout:" namespace is reserved.');
        }

        // Find the first open tag
        $tagOpen = LD . sprintf('%s:%s', $namespace, $action);
        $tagClose = LD . sprintf('/%s:%s', $namespace, $action) . RD;

        $tagOpenLength = strlen($tagOpen);
        $tagCloseLength = strlen($tagClose);

        $pos = strpos($this->template, $tagOpen);

        // As long as we have opening tags we need to continue looking
        while ($pos !== false) {
            $tag = ee('Variables/Parser')->getFullTag($this->template, substr($this->template, $pos, $tagOpenLength));
            $params = ee('Variables/Parser')->parseTagParameters(substr($tag, $tagOpenLength));
            $scope = $params['scope'] ?? null;

            // suss out if this was layout:set, layout:set:append, or layout:set:prepend
            // first remove the parameters from the full tag so we can split by :
            $argsString = trim((preg_match("/\s+.*/", $tag, $matches))) ? $matches[0] : '';
            $setVar = trim(str_replace($argsString, '', $tag), '{}');
            $setVarPars = explode(':', $setVar);
            $command = array_pop($setVarPars);

            $closingTag = LD . sprintf('/%s:', $namespace) . (($command == 'set') ? 'set' : 'set:' . $command) . RD;
            $tagCloseLength = strlen($closingTag);

            // If there is a closing tag and it's before the next open, then this will be treated as a tag pair.
            $next = strpos($this->template, $tagOpen, $pos + $tagOpenLength);
            $close = strpos($this->template, $closingTag, $pos + $tagOpenLength);

            if ($close && (! $next || $close < $next)) {
                // we have a pair
                $start = $pos + strlen($tag);
                $value = substr($this->template, $start, $close - $start);
                $replace_len = $close + $tagCloseLength - $pos;
            } else {
                $value = $params['value'] ?? '';
                $replace_len = strlen($tag);
            }

            // Remove the setter from the template
            $this->template = substr_replace($this->template, '', $pos, $replace_len);

            switch ($command) {
                case 'append':
                    if ($scope) {
                        $this->scopedVariables[$scope][$params['name']][] = $value;
                    } else {
                        $this->variables[$params['name']][] = $value;
                    }

                    break;
                case 'prepend':
                    if (!isset($this->variables[$params['name']])) {
                        $this->variables[$params['name']] = [];
                    }

                    if ($scope) {
                        array_unshift($this->scopedVariables[$scope][$params['name']], $value);
                    } else {
                        array_unshift($this->variables[$params['name']], $value);
                    }

                    break;
                case 'set':
                    if ($scope) {
                        $this->scopedVariables[$scope][$params['name']] = $value;
                    } else {
                        $this->variables[$params['name']] = $value;
                    }
                    // EE Core didn't want to break here, not sure why.
                    break;
                default:
                    break;
            }

            $pos = $next;

            if ($pos !== false) {
                // Adjust for the substr_replace
                $pos -= $replace_len;
            }
        }

        return $this;
    }

    /**
     * Parse Layout variables
     *
     * Also sets the $layout_conditionals class property, which is used to handle conditionals
     * for early parsed variables in one sweep
     *
     * @param  string $str The template/string to parse
     * @param  array $layout_vars Layout variables to parser, 'variable_name' => 'content'
     * @return string The parsed template/string
     */
    public function parseVariables(string $namespace = '', string $scope = ''): Parser
    {
        $namespace = $namespace ? sprintf('%s:', $namespace) : '';
        $variablesCollection = $scope ? $this->scopedVariables[$scope] : $this->variables;

        foreach ($variablesCollection as $key => $val) {
            if (is_array($val)) {
                $total_items = count($val);
                $variables = [];

                foreach ($val as $idx => $item) {
                    $variables[] = [
                        'index' => $idx,
                        'count' => $idx + 1,
                        'reverse_count' => $total_items - $idx,
                        'total_results' => $total_items,
                        'value' => $item,
                    ];
                }

                $this->template = str_replace($namespace . $key, $variables, $this->template);

                // catch-all, if a layout array is used as a single variable, output the last one in
                if (strpos($this->template, $namespace . $key) !== false) {
                    $this->template = str_replace($namespace . $key, $item, $this->template);
                }
            } else {
                $this->template = str_replace($namespace . $key, $val, $this->template);
            }
        }

        // parse index-specified items, e.g.: {[namespace]:titles index='4'}
        if (strpos($this->template, LD . 'layout:') !== false) {
            // prototype:
            // array (size=1)
            //   0 =>
            //     array (size=4)
            //       0 => string '{[namespace]:titles index='4'}' (length=25)
            //       1 => string 'titles' (length=6)
            //       2 => string ''' (length=1)
            //       3 => string '4' (length=1)
            preg_match_all("/" . LD . $namespace . "([^\s]+?)\s+index\s*=\s*(\042|\047)([^\\2]*?)\\2\s*" . RD . "/si", $this->template, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                if (isset($this->variables[$match[1]])) {
                    $value = (isset($this->variables[$match[1]][$match[3]])) ? $this->variables[$match[1]][$match[3]] : '';
                    $this->template = str_replace($match[0], $value, $this->template);
                }
                // check for :modifers
                elseif (($prefix_pos = strpos($match[1], ':')) !== false) {
                    $var = substr($match[1], 0, $prefix_pos);

                    if (isset($this->variables[$var])) {
                        // need to rewrite the variable internally, or multiple modified index='' vars will all have the same value
                        // {layout:titles[3]:length index='3'}
                        $idx = '[' . $match[3] . ']';
                        $rewritten_tag = substr_replace($match[0], $var . $idx, 8, $prefix_pos);
                        $this->template = str_replace($match[0], $rewritten_tag, $this->template);

                        $modifiedVariables[$namespace . $var . $idx] = (isset($this->variables[$var][$match[3]])) ? $this->variables[$var][$match[3]] : '';
                    }
                }
            }

            if (!empty($modifiedVariables)) {
                $this->template = ee('Variables/Parser')->parseModifiedVariables($this->template, $modifiedVariables);
            }
        }

        return $this;
    }
}
