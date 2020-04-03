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

use Basee\Model\SaveResponse;
use Closure;
use EllisLab\ExpressionEngine\Service\Sidebar\FolderItem;
use EllisLab\ExpressionEngine\Service\Sidebar\FolderList;
use EllisLab\ExpressionEngine\Service\Sidebar\Sidebar;
use EllisLab\ExpressionEngine\Service\View\View;

/**
 * This controller assumes all module pages are following the same segment pattern:
 *
 * seg1   seg2     seg3
 * addons/settings/ADD-ON-NAME/ is the root of all controller actions and is enforced by EE.
 *                             seg4     seg5         seg6     seg7
 *                             [module]/[controller]/[action]/[entityId]
 */
abstract class Controller
{
    /**
     * @var array
     */
    private $vars = [];

    /**
     * @var string
     */
    private $baseUrl = 'addons/settings';

    /**
     * @var bool
     */
    private $bypassValidation = false;

    /**
     * @var null
     */
    private $moduleName = null;

    /**
     * @var null
     */
    private $controllerName = null;

    /**
     * @var null
     */
    private $actionName = null;

    /**
     * @var null
     */
    private $entityId = null;

    /**
     * @var null|string
     */
    private $viewFile = null;

    /**
     * @var string
     */
    private $addonName = '';

    /**
     * @var string
     */
    private $page = '';

    /**
     * @var array
     */
    private $sidebarMenu = [];

    /**
     * @var array
     */
    private $hiddenInSidebarMenu = [];

    /**
     * @var Closure
     */
    private $saveCallback;

    /**
     * @var Closure
     */
    private $childrenCallback;

    /**
     * Constructor
     */
    protected function __construct()
    {
        ee()->lang->loadfile('settings');
        ee()->load->library('form_validation');

        // Determine the base url. 3rd segment will
        // always be the name of the add-on.
        if (isset(ee()->uri->rsegments[3])) {
            $this->baseUrl .= '/'. ee()->uri->rsegments[3];
            $this->addonName = ee()->uri->rsegments[3];
        }

        $viewPath = [];

        if (isset(ee()->uri->rsegments[4])) {
            $this->controllerName = ee()->uri->rsegments[4];
            $viewPath[] = $this->controllerName;
        }

        if (isset(ee()->uri->rsegments[5])) {
            $this->actionName = ee()->uri->rsegments[5];
            $viewPath[] = $this->actionName;
        } else {
            $this->actionName = 'index';
            $viewPath[] = 'index';
        }

        if (isset(ee()->uri->rsegments[6])) {
            $this->entityId = ee()->uri->rsegments[6];
        }

        $this->viewFile = implode('/', $viewPath);

        $this->vars = [
            'base_url' => $this->createPageUrl(),
            'save_btn_text' => 'btn_save_settings',
            'save_btn_text_working' => 'btn_saving',
        ];
    }

    /**
     * @return $this
     */
    protected function loadModuleStyles()
    {
        ee()->cp->add_to_head(sprintf('
            <link href="%s%s/styles/module.css" rel="stylesheet" />',
            URL_THIRD_THEMES,
            $this->addonName
        ));

        return $this;
    }

    /**
     * @return string
     */
    protected function moduleHomeUrl()
    {
        return $this->createPageUrl();
    }

    /**
     * @param string $page
     * @param array $params
     * @return \EllisLab\ExpressionEngine\Library\CP\URL
     */
    protected function createPageUrl($page = '', array $params = [])
    {
        $baseUrl = $this->baseUrl.'/';

        // Cheesy way of linking to native EE pages instead of the add-on's.
        if (substr($page, 0, 4) == '[ee]') {
            $baseUrl = '';
            $page = str_replace('[ee]', '', $page);
        }

        return ee('CP/URL')->make(reduce_double_slashes($baseUrl.$page), $params);
    }

    /**
     * Hack method to append the entity id to the last uri segment of a CP url.
     * If CP Session Type is set to Session ID then &S=xxxxx is added to the URL.
     * So we need to append the entity id to the end of the uri, not after the session id.
     *
     * @param string $url
     * @param int $entityId
     * @return mixed|string
     */
    protected function addEntityToUrl($url, $entityId)
    {
        if (strpos($url, '&') !== false) {
            $url = str_replace('&', '/'. $entityId .'&', $url);
        } else {
            $url = $url . '/' . $entityId;
        }

        return $url;
    }


    /**
     * @return string
     */
    protected function currentPageUrl()
    {
        return $this->createPageUrl($this->getPage());
    }

    /**
     * @param null $activePath
     * @return $this
     */
    protected function generateSidebar($activePath = null)
    {
        /** @var Sidebar $sidebar */
        $sidebar = ee('CP/Sidebar')->make();

        $hiddenInSidebarMenu = $this->getHiddenInSidebarMenu();

        foreach ($this->getSidebarMenu() as $section) {
            // Don't render nav option if the user shouldn't see the pages
            // @todo generalize, add callback?
            //if (
            //    ($section['requiresAdmin'] && !$this->canAdminPublisher()) ||
            //    ($section['requiresFullVersion'] === true && PUBLISHER_LITE === true)
            //) {
            //    continue;
            //}

            $sectionUrl = isset($section['url']) ? $section['url'] : null;
            $headingUrl = $sectionUrl ? $this->createPageUrl($sectionUrl) : null;

            $heading = $sidebar->addHeader(lang($section['title']), $headingUrl);

            if (isset($section['button']) && is_array($section['button'])) {
                $heading->withButton(lang($section['button']['title']), $this->createPageUrl($section['button']['url']));
            }

            if ($sectionUrl == $activePath) {
                $heading->isActive();
            }

            $seg4 = isset(ee()->uri->rsegments[4]) ? ee()->uri->rsegments[4] : '';
            $seg5 = isset(ee()->uri->rsegments[5]) ? ee()->uri->rsegments[5] : '';

            if ($sectionUrl == $seg4 || $sectionUrl == $seg4.'/'.$seg5)
            {
                $children = $this->getChildrenCallback();
                if (is_callable($children)) {
                    $children = $children();
                } else {
                    if (!$children) {
                        $children = $section['children'];
                    }
                }

                if (!empty($children)) {
                    if (isset($section['folderName'])) {
                        $list = $heading->addFolderList($section['folderName']);
                    } else {
                        $list = $heading->addBasicList();
                    }
                }

                foreach ($children as $langKey => $child) {
                    if (in_array($langKey, $hiddenInSidebarMenu)) {
                        continue;
                    }

                    $url = $child;
                    $activeActions = null;

                    if (is_array($child) && isset($child['url'])) {
                        $url = $child['url'];
                    }

                    /** @var FolderItem $item */
                    $item = $list->addItem(lang($langKey), $this->createPageUrl($url));

                    if ($url == $activePath) {
                        $item->isActive();
                    }

                    if ($list instanceof FolderList) {
                        if (isset($child['manageUrl'])) {
                            $item->withEditUrl($this->createPageUrl($child['manageUrl']));
                        }

                        if (isset($child['cannotRemove']) && $child['cannotRemove'] === true) {
                            $item->cannotRemove();
                        }
                    }

                    // For removal modals. EE automatically renders the modal to the page, just setting values.
                    // Requires the m-link.click bit in publisher.cp.js though.
                    //if (isset($child['entityId'])) {
                    //    $list->withRemovalKey('entityId');
                    //    $item->identifiedBy($child['entityId']);
                    //    if (isset($child['deleteUrl'])) {
                    //        $list->withRemoveUrl($this->createPageUrl($child['deleteUrl']));
                    //    }
                    //}
                    //if (isset($child['title'])) {
                    //    $item->withRemoveConfirmation($child['title']);
                    //}
                }
            }
        }

        return $this;
    }

    /**
     * @param array $rules
     */
    protected function setValidationRules(array $rules = [])
    {
        foreach ($rules as $fieldName => $rule) {
            ee()->form_validation->set_rules($fieldName, $fieldName, $rule);
        }
    }

    /**
     * @return $this
     */
    protected function handleSubmit()
    {
        $vars = $this->getVars();
        ee()->form_validation->validateNonTextInputs($vars['sections']);

        if (ee()->form_validation->run() !== false || ($this->bypassValidation() && !empty($_POST)))
        {
            $callback = $this->getSaveCallback();
            /** @var SaveResponse $callbackResponse */
            $callbackResponse = $callback($vars['sections']);
            if ($callbackResponse) {
                $alert = ee('CP/Alert');
                $alert
                    ->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle($callbackResponse->getSaveSuccessTitle())
                    ->addToBody(vsprintf($callbackResponse->getSaveSuccessBody(), $callbackResponse->getMessageParameters()))
                    ->defer();

                // If the callback returns an integer we're assuming its the ID of the entity just saved.
                // Segment 7, or the last segment in the path will be the entity ID, in the case of saving
                // a new entity it'll be 0. So, replace it with the proper ID so the page reloads correctly.
                if ($callbackResponse->getSaveSuccessUrl()) {
                    $this->setPage($callbackResponse->getSaveSuccessUrl());
                } else if ($callbackResponse->getEntityId()) {
                    $entityId = $callbackResponse->getEntityId();
                    $this->setEntityId($entityId);
                    $page = $this->getPage();
                    $segments = explode('/', $page);
                    array_pop($segments);
                    $segments[] = $entityId;
                    $page = implode('/', $segments);
                    $this->setPage($page);
                }

                $redirectOptions = $callbackResponse->getSaveRedirectOptions();
                $postButtonSubmitName = ee('Request')->post('submit');

                if ($redirectOptions && array_key_exists($postButtonSubmitName, $redirectOptions)) {
                    ee()->functions->redirect($redirectOptions[$postButtonSubmitName]);
                }

                ee()->functions->redirect($this->currentPageUrl());
            }

            ee()->functions->redirect($this->moduleHomeUrl());
        }
        elseif (ee()->form_validation->errors_exist())
        {
            $errors = [];
            foreach (ee()->form_validation->_error_array as $key => $value) {
                $label = $this->findFieldTitle($vars['sections'], $key);
                $errors[] = ($label ? $label : $key) .': '. $value;
            }

            $alert = ee('CP/Alert');
            $alert
                ->makeInline('shared-form')
                ->asIssue()
                ->withTitle('Please correct the following errors.')
                ->addToBody($errors)
                ->defer();

            ee()->functions->redirect($this->currentPageUrl());
        }

        return $this;
    }

    /**
     * @param array $sections
     * @param string $needle
     * @return string|null
     */
    private function findFieldTitle(array $sections = [], $needle = '')
    {
        // Make sure we're getting only the fields we asked for
        foreach ($sections as $settings) {
            foreach ($settings as $setting) {
                foreach ($setting['fields'] as $field_name => $field) {
                    if ($field_name === $needle) {
                        return $setting['title'];
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param string $view
     * @return array
     */
    protected function parseView($view = null)
    {
        if (!$view) {
            $view = $this->viewFile;
        }

        if (substr($view, 0 ,3) === 'ee:') {
            $viewFile = $view;
        } else {
            $viewFile = sprintf('%s:%s', $this->addonName, $view);
        }

        /** @var View $view */
        $view = ee('View')->make($viewFile);
        $vars = $this->getVars();

        // Send all views some special things
        $vars['boxClass'] = App::viewBoxClass();

        $params = [
            'body' => $view->render($vars),
            'breadcrumb' => [
                $this->moduleHomeUrl()->compile() => lang(sprintf('%s_module_name', $this->addonName))
            ],
        ];

        if (isset($vars['breadcrumbs'])) {
            $params['breadcrumb'] = array_merge($params['breadcrumb'], $vars['breadcrumbs']);
        }

        if (isset($vars['breadcrumbActiveTitle'])) {
            $params['heading'] = $vars['breadcrumbActiveTitle'];
        }

        return $params;
    }

    /**
     * @param array $fieldOptions
     * @param array $values
     * @return array
     */
    protected function buildSettingsFields(array $fieldOptions = [], $values = [])
    {
        $fields = [];

        foreach($fieldOptions as $key => $options) {
            $value = isset($values[$key]) ? $values[$key] : $options['value'];

            $field = [
                'title' => $options['title'],
                'fields' => [
                    $key => [
                        'type' => $options['type'],
                        'value' => $value,
                    ]
                ]
            ];

            if (isset($options['desc']) && $options['desc'] != '') {
                $field['desc'] = $options['desc'];
            }

            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * All controller actions should return an array of view vars.
     *
     * @return array
     */
    protected function callControllerAction()
    {
        if ($actionName = $this->getActionName()) {
            $actionName = \Basee\Helper\StringHelper::camelize($actionName.'-action');
            return $this->$actionName();
        }

        return [];
    }

    /**
     * See if the current user can access the requested settings page
     *
     * @return mixed
     */
    protected function authorize()
    {
        return $this;
    }

    /**
     * @param string $page
     * @return $this
     */
    protected function setPage($page = '')
    {
        $this->page = $page;
        // Also set the vars that will be passed used in the view's form
        $this->vars['base_url'] = $this->createPageUrl($page);

        return $this;
    }

    protected function getPage()
    {
        return $this->page;
    }

    /**
     * @param $vars
     * @return $this
     */
    protected function setVars($vars)
    {
        $this->vars = array_merge($this->vars, $vars);

        return $this;
    }

    /**
     * @return array
     */
    protected function getVars()
    {
        return $this->vars;
    }

    /**
     * @return Closure
     */
    public function getSaveCallback()
    {
        return $this->saveCallback;
    }

    /**
     * @param Closure $saveCallback
     * @return $this
     */
    public function setSaveCallback($saveCallback)
    {
        $this->saveCallback = $saveCallback;

        return $this;
    }

    /**
     * @return Closure
     */
    public function getChildrenCallback()
    {
        return $this->childrenCallback;
    }

    /**
     * @param Closure $childrenCallback
     * @return $this
     */
    public function setChildrenCallback($childrenCallback)
    {
        $this->childrenCallback = $childrenCallback;

        return $this;
    }

    /**
     * @return array
     */
    public function getSidebarMenu()
    {
        return $this->sidebarMenu;
    }

    /**
     * @param array $sidebarMenu
     * @return $this
     */
    public function setSidebarMenu($sidebarMenu)
    {
        $this->sidebarMenu = $sidebarMenu;

        return $this;
    }

    /**
     * @return array
     */
    public function getHiddenInSidebarMenu()
    {
        return $this->hiddenInSidebarMenu;
    }

    /**
     * @param array $hiddenInSidebarMenu
     * @return $this
     */
    public function setHiddenInSidebarMenu($hiddenInSidebarMenu)
    {
        $this->hiddenInSidebarMenu = $hiddenInSidebarMenu;

        return $this;
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @param string $baseUrl
     * @return $this
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    /**
     * @return null
     */
    public function getModuleName()
    {
        return $this->moduleName;
    }

    /**
     * @param null $moduleName
     * @return $this
     */
    public function setModuleName($moduleName)
    {
        $this->moduleName = $moduleName;

        return $this;
    }

    /**
     * @return null
     */
    public function getControllerName()
    {
        return $this->controllerName;
    }

    /**
     * @param null $controllerName
     * @return $this
     */
    public function setControllerName($controllerName)
    {
        $this->controllerName = $controllerName;

        return $this;
    }

    /**
     * @return null
     */
    public function getActionName()
    {
        return $this->actionName;
    }

    /**
     * @param null $actionName
     * @return $this
     */
    public function setActionName($actionName)
    {
        $this->actionName = $actionName;

        return $this;
    }

    /**
     * @return null
     */
    public function getEntityId()
    {
        return (int) $this->entityId;
    }

    /**
     * @param null $entityId
     * @return $this
     */
    public function setEntityId($entityId)
    {
        $this->entityId = (int) $entityId;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getViewFile()
    {
        return $this->viewFile;
    }

    /**
     * @param null|string $viewFile
     * @return $this
     */
    public function setViewFile($viewFile)
    {
        $this->viewFile = $viewFile;

        return $this;
    }

    /**
     * @return bool
     */
    public function bypassValidation()
    {
        return $this->bypassValidation;
    }

    /**
     * @param bool $bypassValidation
     */
    public function setBypassValidation($bypassValidation)
    {
        $this->bypassValidation = $bypassValidation;

        return $this;
    }
}
