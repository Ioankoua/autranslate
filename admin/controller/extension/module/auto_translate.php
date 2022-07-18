<?php
require_once(DIR_SYSTEM . 'library/equotix/auto_translate/equotix.php');

class ControllerExtensionModuleAutoTranslate extends Equotix {
    protected $version = '2.2.0';
    protected $code = 'auto_translate';
    protected $extension = 'Auto Translate';
    protected $extension_id = '3';
    protected $purchase_url = 'auto-translate';
    protected $purchase_id = '21222';
    protected $error = array();

    public function index() {
        $this->load->language('extension/module/auto_translate');

        $this->document->setTitle(strip_tags($this->language->get('heading_title')));

        $data['heading_title'] = $this->language->get('heading_title');

        $data['entry_key'] = $this->language->get('entry_key');
        $data['entry_language'] = $this->language->get('entry_language');
        $data['entry_type'] = $this->language->get('entry_type');
        $data['entry_start'] = $this->language->get('entry_start');

        $data['text_edit'] = $this->language->get('text_edit');
        $data['text_mass_translate'] = $this->language->get('text_mass_translate');
        $data['text_translating'] = $this->language->get('text_translating');

        $data['button_update'] = $this->language->get('button_update');
        $data['button_translate'] = $this->language->get('button_translate');

        $data['tab_general'] = $this->language->get('tab_general');

        $data['button_cancel'] = $this->language->get('button_cancel');

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_module'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/auto_translate', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['auto_translate_key'] = $this->config->get('module_auto_translate_key');
        $data['auto_translate_region'] = $this->config->get('module_auto_translate_region');

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        $this->load->model('localisation/language');

        $languages = $this->model_localisation_language->getLanguages();

        $data['languages'] = array();

        foreach ($languages as $language) {
            if ($language['language_id'] != $this->config->get('config_language_id')) {
                $data['languages'][] = $language;
            }
        }
        
        $data['regions'] = array(
            'global',
            'australiaeast',
            'brazilsouth',
            'canadacentral',
            'centralindia',
            'centralus',
            'centraluseuap',
            'eastasia',
            'eastus',
            'eastus2',
            'francecentral',
            'japaneast',
            'japanwest',
            'koreacentral',
            'northcentralus',
            'northeurope',
            'southcentralus',
            'southeastasia',
            'uksouth',
            'westcentralus',
            'westeurope',
            'westus',
            'westus2',
            'southafricanorth'
        );

        $data['types'] = array(
            'banner',
            'category',
            'information',
            'product',
            'option',
            'option_value',
            'attribute',
            'attribute_group',
            'product_attribute'
        );

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->generateOutput('extension/module/auto_translate', $data);
    }

    public function install() {
        if (!$this->user->hasPermission('modify', 'extension/extension/module')) {
            return;
        }

        $this->load->model('setting/setting');

        $data = array(
            'module_auto_translate_status' => true
        );

        $this->model_setting_setting->editSetting('module_auto_translate', $data);

        $this->load->model('setting/event');

        $this->model_setting_event->addEvent('module_auto_translate', 'admin/view/common/header/after', 'extension/module/auto_translate/eventPostCommonHeader');

        if (isset($this->request->get['demo'])) {
            $this->load->model('localisation/language');

            $data = array(
                'name'       => 'Chinese',
                'code'       => 'zh',
                'locale'     => '',
                'directory'  => 'zh',
                'filename'   => 'chinese.php',
                'image'      => 'cn.png',
                'status'     => '0',
                'sort_order' => '1'
            );

            $this->model_localisation_language->addLanguage($data);

            @mkdir(DIR_LANGUAGE . 'zh/');

            if (file_exists(DIR_LANGUAGE . 'en-gb/en-gb.png')) {
                @copy(DIR_LANGUAGE . 'en-gb/en-gb.png', DIR_LANGUAGE . 'zh/zh.png');
            } else {
                @copy(DIR_LANGUAGE . 'english/english.php', DIR_LANGUAGE . 'zh/chinese.php');
            }
        }
    }
    
    public function uninstall() {
        if (!$this->user->hasPermission('modify', 'extension/extension/module')) {
            return;
        }
        
        $this->load->model('setting/event');

        $this->model_setting_event->deleteEventByCode('module_auto_translate');
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/auto_translate') || !$this->validated()) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        return !$this->error;
    }
    
    public function eventPostCommonHeader($route, &$data, &$output) {
        if ($this->user->isLogged()) {
            $this->load->model('localisation/language');
            
            $languages = $this->model_localisation_language->getLanguages();
            
            $language_ids = array();
            
            foreach ($languages as $language) {
                if ($language['language_id'] != $this->config->get('config_language_id')) {
                    $language_ids[] = $language['language_id'];
                }
            }
            
            $auto_translate = 'view/javascript/auto_translate.js.php?version=' . VERSION . '&language_ids=' . implode('_', $language_ids) . '&default_id=' . $this->config->get('config_language_id') . '&user_token=' . $this->session->data['user_token'];
            
            $html = '<script type="text/javascript" src="' . $auto_translate . '"></script>';
            $html .= '<style type="text/css">
            .auto-translate {
                padding: 4px;
                background: #aaaaaa;
                color: #ffffff;
                text-decoration: none;
                border-radius: 2px;
                vertical-align: top;
                margin-left: 2px;
                display: inline-block;
                font-weight: bold;
                cursor: pointer;
            }
            .auto-translate:hover {
                background: #0099ff;
                color: #ffffff;
            }
            </style>';
            
            $output = str_replace('</head>', $html . '</head>', $output);
        }
    }

    public function update() {
        if ($this->validate()) {
            $this->load->model('setting/setting');
            
            $this->request->post['module_auto_translate_status'] = true;
           
            $this->model_setting_setting->editSetting('module_auto_translate', $this->request->post);

            $this->response->setOutput(json_encode(array()));
        }
    }

    public function translate() {
        $json = array();

        if (!$this->validated()) {
            $this->response->setOutput(json_encode($json));

            return;
        }

        $this->load->model('localisation/language');
        $this->load->model('extension/module/auto_translate');

        $translate = true;

        if (isset($this->request->post['text'])) {
            $text = html_entity_decode($this->request->post['text'], ENT_QUOTES);
        } else {
            $translate = false;
        }

        if (isset($this->request->post['to'])) {
            $to_language_id = (int)$this->request->post['to'];
        } else {
            $translate = false;
        }

        if ($translate && $text && $to_language_id) {
            $from_language = $this->model_localisation_language->getLanguage($this->config->get('config_language_id'));
            $to_language = $this->model_localisation_language->getLanguage($to_language_id);
            
            $json['text'] = $this->model_extension_module_auto_translate->translate($from_language['code'], $to_language['code'], $text);
        }

        $this->response->setOutput(json_encode($json));
    }

    public function mass_translate() {
        $json = array();

        if (!$this->validated()) {
            $this->response->setOutput(json_encode($json));

            return;
        }

        $this->load->language('extension/module/auto_translate');

        $this->load->model('localisation/language');
        $this->load->model('extension/module/auto_translate');

        if (isset($this->request->post['language_id'])) {
            $to_language_id = (int)$this->request->post['language_id'];
        }

        if (isset($this->request->post['type'])) {
            $type = $this->request->post['type'];
        }

        if (isset($this->request->get['start'])) {
            $start = (int)$this->request->get['start'];
        } else {
            $start = 0;
        }

        $from_language = $this->model_localisation_language->getLanguage($this->config->get('config_language_id'));
        $to_language = $this->model_localisation_language->getLanguage($to_language_id);

        $total = $this->model_extension_module_auto_translate->getTotal($type, $from_language);

        $this->model_extension_module_auto_translate->update($type, $from_language['code'], $to_language['code'], $start);

        if (($start + 10) < $total) {
            $json['next'] = 'index.php?route=extension/module/auto_translate/mass_translate&user_token=' . $this->session->data['user_token'] . '&start=' . ($start + 10);

            $json['logs'] = sprintf($this->language->get('text_logs'), $start + 10, $total);
        } else {
            $json['logs'] = sprintf($this->language->get('text_logs'), $total, $total);
            $json['done'] = $this->language->get('text_done');
        }

        $this->response->setOutput(json_encode($json));
    }

    public function view_catalog_product_form_after(&$route, &$data, &$output) {
        $this->load->model('extension/module/auto_translate');
        $mydata['url'] = $this->url->link('extension/module/auto_translate/save_product&user_token=' . $this->session->data['user_token']);
        $from_lang_id =  $this->model_extension_module_auto_translate->getLanguageIdByCode('en-gb');
        $to_lang_id =  $this->model_extension_module_auto_translate->getLanguageIdByCode('uk-ua');
        $mydata['from_lang_id'] = $from_lang_id['language_id'];
        $mydata['to_lang_id'] = $to_lang_id['language_id'];

        $button = $this->load->view('extension/module/auto_translate/button_translate_product', $mydata);
        $html_dom = new d_simple_html_dom();
        $html_dom->load((string)$output, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT);
        $html_dom->find('.page-header .pull-right', 0)->innertext = $button . $html_dom->find('.page-header .pull-right', 0)->innertext;
        $output = (string)$html_dom;
    }

    public function save_product() {
        $this->load->model('extension/module/auto_translate');
        $json = $this->model_extension_module_auto_translate->translateOneProduct('en-gb', 'uk-ua', $this->request->post);
        $this->response->setOutput(json_encode($json));
    }

    public function view_catalog_option_form_after(&$route, &$data, &$output) {
        $this->load->model('extension/d_opencart_patch/url');
        $this->load->model('extension/module/auto_translate');
        $mydata['url'] = $this->model_extension_d_opencart_patch_url->link('extension/module/auto_translate/save_option&user_token=' . $this->session->data['user_token']);
        $from_lang_id =  $this->model_extension_module_auto_translate->getLanguageIdByCode('en-gb');
        $to_lang_id =  $this->model_extension_module_auto_translate->getLanguageIdByCode('uk-ua');
        $mydata['max_option'] = $this->getMaxCountOption();
        $mydata['from_lang_id'] = $from_lang_id['language_id'];
        $mydata['to_lang_id'] = $to_lang_id['language_id'];
        $button = $this->load->view('extension/module/auto_translate/button_translate_option', $mydata);
        $html_dom = new d_simple_html_dom();
        $html_dom->load((string)$output, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT);
        $html_dom->find('.page-header .pull-right', 0)->innertext = $button . $html_dom->find('.page-header .pull-right', 0)->innertext;
        $output = (string)$html_dom;
    }

    public function getMaxCountOption() {
        $this->load->model('extension/module/auto_translate');
        $option = $this->model_extension_module_auto_translate->getOption();
        $max = count($option);
        for ($i=0; $i < $max; $i++) { 
            $arr[] = $option[$i]['option_id'];
        }
        $count_option = array_count_values($arr);
        return max($count_option);
    }

    public function save_option() {
        $this->load->model('extension/module/auto_translate');
        $json = $this->model_extension_module_auto_translate->translateOption('en-gb', 'uk-ua', $this->request->post);
        $this->response->setOutput(json_encode($json));
    }
}