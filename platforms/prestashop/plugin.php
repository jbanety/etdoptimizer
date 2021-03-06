<?php
/**
 * @package      ETD Optimizer
 *
 * @version      2.7.0
 * @copyright    Copyright (C) 2012-2017 ETD Solutions. Tous droits réservés.
 * @license      Apache Version 2 (https://raw.githubusercontent.com/jbanety/etdoptimizer/master/LICENSE.md)
 * @author       ETD Solutions http://www.etd-solutions.com
 **/

if (!defined('_CAN_LOAD_FILES_')) exit;

define('PARAM_REQUIREJS', 'ETDOPTIMIZER_REQUIREJS');
define('PARAM_JQUERY', 'ETDOPTIMIZER_JQUERY');
define('PARAM_JQUERY_NOCONFLICT', 'ETDOPTIMIZER_JQUERY_NOCONFLICT');
define('PARAM_MODERNIZR', 'ETDOPTIMIZER_MODERNIZR');
define('PARAM_JS_EXCLUDE', 'ETDOPTIMIZER_JS_EXCLUDE');
define('PARAM_CSS_EXCLUDE', 'ETDOPTIMIZER_CSS_EXCLUDE');
define('PARAM_VIEWPORT', 'ETDOPTIMIZER_VIEWPORT');
define('PARAM_MOOTOOLS_LEGACY', '');
define('PARAM_IS_MOBILE', 'ETDOPTIMIZER_IS_MOBILE');
define('PARAM_MOBILE_ENABLED', 'ETDOPTIMIZER_MOBILE_ENABLED');
define('PARAM_MOBILE_URI', 'ETDOPTIMIZER_MOBILE_URI');
define('PARAM_MOBILE_TABLETS', 'ETDOPTIMIZER_MOBILE_TABLETS');
define('PARAM_MOBILE_REDIRECT', 'ETDOPTIMIZER_MOBILE_REDIRECT');
define('PARAM_MOBILE_TEMPLATE', 'ETDOPTIMIZER_MOBILE_TEMPLATE');
define('PARAM_MINIFY', 'ETDOPTIMIZER_MINIFY');
define('PARAM_GOOGLE_FONTS', 'ETDOPTIMIZER_GOOGLE_FONTS');
define('PARAM_DOMREADY', 'ETDOPTIMIZER_DOMREADY');

/**
 * Module pour optimiser le rendu HTML des pages.
 */
class EtdOptimizer extends Module {

    public static $stylesheets = array();
    public static $css = array();
    public static $js = array();
    public static $scripts = array();
    public static $custom = array();
    public static $meta = array();

    /**
     * @var EtdOptimizerHelper $helper
     */
    public static $helper;

    public function __construct() {

        $this->name = 'etdoptimizer';
        $this->tab = 'others';
        $this->version = '2.7.0';
        $this->author = 'ETD Solutions';
        $this->need_instance = 1;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('ETD Optimizer');
        $this->description = $this->l('Optimize html code rendering.');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

        if (!isset(self::$helper)) {
            self::$helper = new EtdOptimizerHelper(
                $this->loadParams(),
                $this->local_path,
                $this->_path."vendor",
                _PS_BASE_URL_,
                _PS_THEME_DIR_,
                _THEME_DIR_,
                _PS_ROOT_DIR_,
	            _PS_MODE_DEV_
            );
        }
    }

    public function install() {

        Configuration::updateGlobalValue('ETDOPTIMIZER_REQUIREJS', 0);
        Configuration::updateGlobalValue('ETDOPTIMIZER_JQUERY', 1);
        Configuration::updateGlobalValue('ETDOPTIMIZER_JQUERY_NOCONFLICT', 0);
        Configuration::updateGlobalValue('ETDOPTIMIZER_MODERNIZR', 1);
        Configuration::updateGlobalValue('ETDOPTIMIZER_MINIFY', 1);
        Configuration::updateGlobalValue('ETDOPTIMIZER_JS_EXCLUDE', 'tools.js, jquery.easing.js');
        Configuration::updateGlobalValue('ETDOPTIMIZER_CSS_EXCLUDE', '');
        Configuration::updateGlobalValue('ETDOPTIMIZER_MOBILE_ENABLED', 0);
        Configuration::updateGlobalValue('ETDOPTIMIZER_MOBILE_URI', '');
        Configuration::updateGlobalValue('ETDOPTIMIZER_MOBILE_TABLETS', 0);
        Configuration::updateGlobalValue('ETDOPTIMIZER_MOBILE_REDIRECT', 0);
        Configuration::updateGlobalValue('ETDOPTIMIZER_MOBILE_TEMPLATE', '');
        Configuration::updateGlobalValue('ETDOPTIMIZER_VIEWPORT', 'width=device-width, initial-scale=1.0');
        Configuration::updateGlobalValue('ETDOPTIMIZER_GOOGLE_FONTS', '');
        Configuration::updateGlobalValue('ETDOPTIMIZER_DOMREADY', 0);

        // Copie de l'override de la classe Controller
        if (file_exists(_PS_ROOT_DIR_ . "/override/classes/controller/Controller.php")) {
            $this->displayError($this->l("Un override existe déjà pour Controller.php"));
        } else {
            copy($this->local_path . "platforms/prestashop/overrides/Controller.php", _PS_ROOT_DIR_ . "/override/classes/controller/Controller.php");
            if (file_exists(_PS_ROOT_DIR_.'/cache/class_index.php')) {
                unlink(_PS_ROOT_DIR_.'/cache/class_index.php');
            }
        }

        return (parent::install() && $this->registerHook('actionDispatcher') && $this->registerHook('actionEtdOptimizerAddJS') && $this->registerHook('actionEtdOptimizerAddCSS') && $this->registerHook('actionEtdOptimizerAddScript') && $this->registerHook('actionEtdOptimizerAddStylesheet') && $this->registerHook('actionEtdOptimizerAddCustom'));

    }

    public function uninstall() {

        foreach ($this->getConfigFields() as $field) {
            Configuration::deleteByName($field);
        }

        return parent::uninstall();
    }

    public function hookActionDispatcher() {

        // On ajoute le dossier des plugins Smarty du plugin.
        $this->context->smarty->addPluginsDir($this->local_path."platforms/prestashop/smarty");

        // On ajoute le dossier des plugins Smarty du template.
        $this->context->smarty->addPluginsDir(_PS_THEME_DIR_ . "smarty");

    }

    public function hookActionEtdOptimizerAddCustom($params) {

        self::addCustom($params['custom']);

    }

    public function hookActionEtdOptimizerAddJS($params) {

        self::addScriptDeclaration($params['js']);

    }

    public function hookActionEtdOptimizerAddScript($params) {

        self::addScript($params['src']);

    }

    public function hookActionEtdOptimizerAddCSS($params) {

        self::addStyleDeclaration($params['css']);

    }

    public function hookActionEtdOptimizerAddStylesheet($params) {

        self::addStylesheet($params['src']);

    }

    public static function addStylesheet($src, $media = 'all', $priority = null) {

        self::$stylesheets[$src] = [
        	'media'   => $media,
	        'options' => isset($priority) ? ['priority' => $priority] : []
        ];

    }

    public static function addStyleDeclaration($content, $type = 'text/css') {

        if (!isset(self::$css[strtolower($type)])) {
            self::$css[strtolower($type)] = $content;
        } else {
            self::$css[strtolower($type)] .= chr(13) . $content;
        }

    }

    public static function addScript($src, $async = false, $defer = false, $priority = null) {

        self::$scripts[$src] = [
            'async' => $async,
            'defer' => $defer,
            'options' => isset($priority) ? ['priority' => $priority] : []
        ];

    }

    public static function addScriptDeclaration($content, $type = 'text/javascript') {

        if (!isset(self::$js[strtolower($type)])) {
            self::$js[strtolower($type)] = $content;
        } else {
            self::$js[strtolower($type)] .= chr(13) . $content;
        }

    }

    public static function addMeta($name, $content) {

        self::$meta[$name] = $content;

    }

    public static function addCustom($html) {

        self::$custom[] = trim($html);

    }

    protected function loadParams() {

        return Configuration::getMultiple($this->getConfigFields(), null, 0, 0);

    }

    /** ADMINISTRATION **/

    public function getContent()  {

        $html = '';

        if (Tools::isSubmit('submitSettings'))  {

            Configuration::updateGlobalValue('ETDOPTIMIZER_REQUIREJS', (int)Tools::getValue('ETDOPTIMIZER_REQUIREJS'));
            Configuration::updateGlobalValue('ETDOPTIMIZER_JQUERY', (int)Tools::getValue('ETDOPTIMIZER_JQUERY'));
            Configuration::updateGlobalValue('ETDOPTIMIZER_JQUERY_NOCONFLICT', (int)Tools::getValue('ETDOPTIMIZER_JQUERY_NOCONFLICT'));
            Configuration::updateGlobalValue('ETDOPTIMIZER_MODERNIZR', (int)Tools::getValue('ETDOPTIMIZER_MODERNIZR'));
            Configuration::updateGlobalValue('ETDOPTIMIZER_MINIFY', (int)Tools::getValue('ETDOPTIMIZER_MINIFY'));
            Configuration::updateGlobalValue('ETDOPTIMIZER_JS_EXCLUDE', Tools::getValue('ETDOPTIMIZER_JS_EXCLUDE'));
            Configuration::updateGlobalValue('ETDOPTIMIZER_CSS_EXCLUDE', Tools::getValue('ETDOPTIMIZER_CSS_EXCLUDE'));
            Configuration::updateGlobalValue('ETDOPTIMIZER_MOBILE_ENABLED', (int)Tools::getValue('ETDOPTIMIZER_MOBILE_ENABLED'));
            Configuration::updateGlobalValue('ETDOPTIMIZER_MOBILE_URI', Tools::getValue('ETDOPTIMIZER_MOBILE_URI'));
            Configuration::updateGlobalValue('ETDOPTIMIZER_MOBILE_TABLETS', (int)Tools::getValue('ETDOPTIMIZER_MOBILE_TABLETS'));
            Configuration::updateGlobalValue('ETDOPTIMIZER_MOBILE_REDIRECT', (int)Tools::getValue('ETDOPTIMIZER_MOBILE_REDIRECT'));
            Configuration::updateGlobalValue('ETDOPTIMIZER_MOBILE_TEMPLATE', Tools::getValue('ETDOPTIMIZER_MOBILE_TEMPLATE'));
            Configuration::updateGlobalValue('ETDOPTIMIZER_VIEWPORT', Tools::getValue('ETDOPTIMIZER_VIEWPORT'));
            Configuration::updateGlobalValue('ETDOPTIMIZER_GOOGLE_FONTS', Tools::getValue('ETDOPTIMIZER_GOOGLE_FONTS'));
            Configuration::updateGlobalValue('ETDOPTIMIZER_DOMREADY', (int)Tools::getValue('ETDOPTIMIZER_DOMREADY'));

            $html .= $this->displayConfirmation($this->l('Configuration updated'));

        }

        $html .= $this->renderForm();

        return $html;
    }

    public function renderForm()
    {
        $fields_form_1 = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('In-Page'),
                    'icon' => 'icon-file-text'
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Inclure RequireJS'),
                        'name' => 'ETDOPTIMIZER_REQUIREJS',
                        'desc' => 'Inclure RequireJS pour les scripts asynchrones AMD',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Inclure jQuery'),
                        'name' => 'ETDOPTIMIZER_JQUERY',
                        'desc' => 'Inclure la librairie JavaScript jQuery',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
	                    'type' => 'switch',
	                    'label' => $this->l('noConflict jQuery'),
	                    'name' => 'ETDOPTIMIZER_JQUERY_NOCONFLICT',
	                    'desc' => 'JQuery renonce au contrôle de la variable $',
	                    'values' => array(
		                    array(
			                    'id' => 'active_on',
			                    'value' => 1,
			                    'label' => $this->l('Enabled')
		                    ),
		                    array(
			                    'id' => 'active_off',
			                    'value' => 0,
			                    'label' => $this->l('Disabled')
		                    )
	                    )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Inclure Modernizr'),
                        'name' => 'ETDOPTIMIZER_MODERNIZR',
                        'desc' => 'Inclure Modernizr pour gérer HTML5/CSS3 pour les anciens navigateurs.',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Minifier le JS et CSS inline'),
                        'name' => 'ETDOPTIMIZER_MINIFY',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Fichiers JS à exclure'),
                        'name' => 'ETDOPTIMIZER_JS_EXCLUDE',
                        'desc' => $this->l('Liste séparée par virgule des noms de fichiers .js à exclure.')
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Fichiers CSS à exclure'),
                        'name' => 'ETDOPTIMIZER_CSS_EXCLUDE',
                        'desc' => $this->l('Liste séparée par virgule des noms de fichiers .css à exclure.')
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                )
            ),
        );

        $fields_form_2 = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Mobile'),
                    'icon' => 'icon-mobile'
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Détection mobile'),
                        'name' => 'ETDOPTIMIZER_MOBILE_ENABLED',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Inclure les tablettes'),
                        'name' => 'ETDOPTIMIZER_MOBILE_TABLETS',
                        'desc' => $this->l('Inclure les tablettes comme périphériques mobiles'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Redirection'),
                        'name' => 'ETDOPTIMIZER_MOBILE_REDIRECT',
                        'desc' => $this->l('Rediriger le visiteur vers un site mobile'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('URL mobile'),
                        'name' => 'ETDOPTIMIZER_MOBILE_URI',
                        'desc' => $this->l('URL vers laquelle rediriger les mobiles')
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                )
            ),
        );

        $fields_form_3 = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Avancé'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Tag Viewport'),
                        'name' => 'ETDOPTIMIZER_VIEWPORT',
                        'desc' => $this->l('Valeur de la balise META Viewport')
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Google Fonts'),
                        'name' => 'ETDOPTIMIZER_GOOGLE_FONTS',
                        'desc' => $this->l('Police Google Fonts à importer. (format: Bree+Serif|Great+Vibes')
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Inclure domReady'),
                        'name' => 'ETDOPTIMIZER_DOMREADY',
                        'desc' => 'Inclure domReady dans les chemins RequireJS',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                )
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form = array();

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitSettings';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form_1, $fields_form_2, $fields_form_3));
    }

    public function getConfigValues() {

        $ret = array();

        foreach ($this->getConfigFields() as $field) {
            $ret[$field] = Tools::getValue($field, Configuration::getGlobalValue($field));
        }

        return $ret;
    }

    protected function getConfigFields() {

        return array(
            'ETDOPTIMIZER_REQUIREJS',
            'ETDOPTIMIZER_JQUERY',
            'ETDOPTIMIZER_JQUERY_NOCONFLICT',
            'ETDOPTIMIZER_MODERNIZR',
            'ETDOPTIMIZER_MINIFY',
            'ETDOPTIMIZER_JS_EXCLUDE',
            'ETDOPTIMIZER_CSS_EXCLUDE',
            'ETDOPTIMIZER_MOBILE_ENABLED',
            'ETDOPTIMIZER_MOBILE_URI',
            'ETDOPTIMIZER_MOBILE_TABLETS',
            'ETDOPTIMIZER_MOBILE_REDIRECT',
            'ETDOPTIMIZER_MOBILE_TEMPLATE',
            'ETDOPTIMIZER_VIEWPORT',
            'ETDOPTIMIZER_GOOGLE_FONTS',
            'ETDOPTIMIZER_DOMREADY'
        );

    }

}
