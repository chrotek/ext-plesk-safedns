<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.


class IndexController extends pm_Controller_Action
{
    protected $_accessLevel = 'admin';
    protected $_api_key = '--------------------';

    public function init()
    {
        parent::init();

        // Init title for all actions
//        $this->view->pageTitle = $this->lmsg('page_title');
        $this->view->pageTitle = 'SafeDNS Plesk Integration';
    }

    public function toolsAction() {
        $status='null';
/*
//        pm_Settings::set('enabled',null); ////////////////////////////////////////////////////////////////////////////////////////////////
        pm_Settings::get('enabled');

//        $originalsetting=pm_Settings::get('enabled');

//        $toggle_link=pm_Settings::set('enabled','true');
        //pm_Settings::set('enabled',null);
	if (pm_Settings::get('enabled')) {
            $status='enabled';
            $originalsetting='true';
            $toggle_link=pm_Settings::set('enabled',null);
        } else {
            $status='disabled';
            $originalsetting=null;
            $toggle_link=pm_Settings::set('enabled','true');
        };
        pm_Settings::set('enabled',$originalsetting);								*/
        $this->view->tools = [
            [
                'icon' => \pm_Context::getBaseUrl() . 'icons/32/refresh.png',
                'title' => 'Sync All',
                'description' => 'Sync all Domains with SafeDNS',
                'link' => "javascript:Modules_Route53_Confirm"
            ], [
                'icon' => \pm_Context::getBaseUrl() . 'icons/32/remove-selected.png',
                'title' => 'Remove All',
                'description' => 'Remove all domains from SafeDNS',
                'link' => pm_Context::getActionUrl('index/sync-all-zones'),
            ], [
                'icon' => \pm_Context::getBaseUrl() . 'icons/32/key.png',
                'title' => 'Set API Key',
                'description' => 'Set/Change API Key',
//                'link' => 'Zform.php',
//                'link' => $this->view->form ,
                'link' => pm_Context::getActionUrl('index/form'),
            ]//,[
             //   'icon' => \pm_Context::getBaseUrl() . 'icons/32/key.png',
             //   'title' => 'Enable/Disable',
             //   'description' => $status,
            //
            //    'link' => $toggle_link,
          //  ]
        
// i       $this->addElement('checkbox', 'enabled', array(
//            'label' => pm_Locale::lmsg('enabledLabel'),
  //          'value' => pm_Settings::get('enabled'),
    //    )),




        ];
        $this->view->tabs = $this->_getTabs();
    }

    private function _getTabs()
    {
        $tabs = [];
        $tabs[] = [
//            'title' => $this->lmsg('indexPageTitle'),
            'title' => 'SafeDNS Integration Configuration',
            'action' => 'index',
        ];
//        if (pm_Settings::get('enabled')) {
//            $tabs[] = [
//                'title' => $this->lmsg('delegationSetTitle'),
//                'action' => 'delegation-set',
//            ];
//            $tabs[] = [
//                'title' => 'fuckyou',
//                'action' => $this->_forward('form'),
//            ];
//            $tabs[] = [
//                'title' => $this->lmsg('toolsTitle'),
//                'action' => 'tools',
//            ];
//        }
        return $tabs;
    }


    public function indexAction()
    {
        // Default action is formAction
        $this->_forward('tools');
    }

    public function formAction()
    {
        // Init form here
        $form = new pm_Form_Simple();    
        // Set the description text
        $this->view->output_description = 'API Key Configuration';

//        $this->addElement('checkbox', 'enabled', array(
//            'label' => pm_Locale::lmsg('enabledLabel'),
//            'value' => pm_Settings::get('enabled'),
//        ));

//        $this->addElement('checkbox', 'enabled', array(
//            'label' => pm_Locale::lmsg('enabledLabel'),
//            'value' => pm_Settings::get('enabled'),
//        ));
////        $form->addElement('text', 'api_key', ['label' => $this->lmsg('form_api_key'), 'value' => pm_Settings::get('api_key'), 'style' => 'width: 40%;']);
        $form->addElement('text', 'api_key', ['label' => 'Please enter API Key', 'value' => pm_Settings::get('api_key'), 'style' => 'width: 40%;']);
        $form->addControlButtons(['cancelLink' => pm_Context::getBaseUrl(),]);

        // Process the form - save the api key and run the installation scripts
        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            if ($form->getValue('api_key')) {
                $this->_api_key = $form->getValue('api_key');
            }

            pm_Settings::set('api_key', $this->_api_key);

//            $this->_status->addMessage('info', $this->lmsg('message_success'));
            $this->_status->addMessage('info', 'API Key Saved');
            $this->_helper->json(['redirect' => pm_Context::getBaseUrl()]);
        }
        

        $this->view->form = $form;        


    }
}
