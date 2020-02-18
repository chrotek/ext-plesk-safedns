<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.


class IndexController extends pm_Controller_Action
{
    private $taskManager = NULL;
    protected $_accessLevel = 'admin';
    protected $_api_key = '--------------------';
    public function init()
    {
        parent::init();
        if (is_null($this->taskManager)) {
            $this->taskManager = new pm_LongTask_Manager();
        }

        // Init title for all actions
//        $this->view->pageTitle = $this->lmsg('page_title');
        $this->view->pageTitle = 'SafeDNS Plesk Integration';
    }

    public function addTaskAction()
    {
        $domainId = $this->getParam('domainId', -1);
//        $type = $this->getParam('type', 'succeed');
        $domain = $domainId != -1 ? new pm_Domain($domainId) : null;
        $type = $this->getParam('type', 'succeed');
        pm_Log::info("Create '{$type}' task and set params");
//        $task = $type === 'succeed'
//            ? new Modules_SafednsPlesk_Task_Succeed()
//            : new Modules_SafednsPlesk_Task_Fail();
        if ($type == 'succeed') {
            $task=new Modules_SafednsPlesk_Task_Succeed();
        } elseif ($type == 'fail') {
            $task=new Modules_SafednsPlesk_Task_Fail();
        } elseif ($type == 'synchronise-all-domains') {
            $task=new Modules_SafednsPlesk_Task_SynchroniseAllDomains();
        } elseif ($type == 'synchronise-a-domain') {
            $task=new Modules_SafednsPlesk_Task_SynchroniseADomain();
        } elseif ($type == 'delete-all-domains') {
            $task=new Modules_SafednsPlesk_Task_DeleteAllDomains();
        } elseif ($type == 'delete-a-domain') {
            $task=new Modules_SafednsPlesk_Task_DeleteADomain();
        }
//        $task->setParams([
//            'p1' => 1,
//            'p2' => 2,
//        ]);
//        $task->setParam('p3', 3);

        if (isset($domain)) {
            $task->setParam('domainName', $domain->getName());
        }
        $this->taskManager->start($task, $domain);

        $this->_redirect('index/tools');
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

        if (pm_Settings::get('taskLock')) {
            $taskLockStatus="taskLock is locked";
        } else {
            $taskLockStatus="taskLock is null";
        }        

        $this->view->tools = [
            [
                'icon' => \pm_Context::getBaseUrl() . 'icons/32/key.png',
                'title' => 'Set API Key',
                'description' => 'Set/Change API Key',
                'link' => pm_Context::getActionUrl('index/apikeyform'),
            ], [
                'icon' => \pm_Context::getBaseUrl() . 'icons/32/refresh.png',
                'title' => 'Sync All',
                'description' => 'Sync all Domains with SafeDNS',
                'link' => pm_Context::getActionUrl('index', 'add-task') . '/type/synchronise-all-domains',
            ], [
                'icon' => \pm_Context::getBaseUrl() . 'icons/32/refresh.png',
                'title' => 'Sync Domain',
                'description' => 'Sync a specific domains with SafeDNS',
                'link' => pm_Context::getActionUrl('index/syncadomain'),
            ],[
                'icon' => \pm_Context::getBaseUrl() . 'icons/32/redalert.png',
                'title' => 'Delete All',
                'description' => 'Deletes all Plesk domains from SafeDNS',
                'link' => pm_Context::getActionUrl('index', 'add-task') . '/type/delete-all-domains',
            ], [
                'icon' => \pm_Context::getBaseUrl() . 'icons/32/redalert.png',
                'title' => 'Delete Domain',
                'description' => 'Deletes a specific domain from SafeDNS',
                'link' => pm_Context::getActionUrl('index/deleteadomain'),
            ], [
                'icon' => \pm_Context::getBaseUrl() . 'icons/32/orange-square-cross.png',
                'title' => 'Clear all tasks',
                'description' => 'Clear tasks in all states',
                'link' => pm_Context::getActionUrl('index', 'cancel-all-task'),
            ], [
                'icon' => \pm_Context::getBaseUrl() . 'icons/32/green-ssl-padlock-ticked.png',
                'title' => 'DEBUG taskLock Status',
                'description' => $taskLockStatus,
                'link' => pm_Context::getActionUrl('index'),
            ]  











             //,[
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

    public function apikeyformAction()

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

    public function syncadomainAction()

    {

        $form = new pm_Form_Simple();
        if (pm_Settings::get('taskLock')) {
            $this->_forward('tools');
            $this->_status->addMessage('warning', 'Please wait for current task to finish');
            $this->view->form = $form;                    
        }

        // Set the description text
        $this->view->output_description = 'Synchronise a Domain';

        $domInfo = $this->getDomainInfo();
        $list = $domInfo->webspace->get->result;

//        $domainSelector[-1] = 'Global';

        if ($list->status = 'ok') {
            foreach ($list as $domain) {
                if (isset($domain->data->gen_info->name)) {
                    $domainSelector[strval($domain->data->gen_info->name)] = strval($domain->data->gen_info->name);
                }
            }
        }
        $form = new Modules_SafednsPlesk_Form_CreateForm();
        $form->addElement('select', 'selectedDomain', [
            'label' => 'Select domain',
            'multiOptions' => $domainSelector,
        ]);
//        $form->addControlButtons(['cancelLink' => pm_Context::getBaseUrl(),]);
        $form->addControlButtons(['sendTitle' => 'Synchronise Domain' ,'cancelLink' => pm_Context::getBaseUrl(),]);


        // Process the form - syncronise records for a specific domain
        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
//            pm_Settings::set('selectedDomainSychronise', "NO DOMAIN SELECTED");
            pm_Settings::set('selectedDomainSychronise', $form->getValue('selectedDomain'));
            $this->_status->addMessage('info', "Requested Domain Sync ".pm_Settings::get('selectedDomainSychronise'));
            $this->_helper->json(['redirect' => (pm_Context::getActionUrl('index', 'add-task') . '/type/synchronise-a-domain')]);
        }
        $this->view->form = $form;
    }
    public function deleteadomainAction()

    {
        // Init form here
        $form = new pm_Form_Simple();
        if (pm_Settings::get('taskLock')) {
            $this->_forward('tools');
            $this->_status->addMessage('warning', 'Please wait for current task to finish');
            $this->view->form = $form;
        }

        // Set the description text
        $this->view->output_description = 'Delete a Domain';

        $domInfo = $this->getDomainInfo();
        $list = $domInfo->webspace->get->result;

//        $domainSelector[-1] = 'Global';

        if ($list->status = 'ok') {
            foreach ($list as $domain) {
                if (isset($domain->data->gen_info->name)) {
                    $domainSelector[strval($domain->data->gen_info->name)] = strval($domain->data->gen_info->name);
                }
            }
        }
        $form = new Modules_SafednsPlesk_Form_CreateForm();
        $form->addElement('select', 'selectedDomain', [
            'label' => 'Select domain',
            'multiOptions' => $domainSelector,
        ]);
//        $form->addElement('SimpleText', 'text', [
//            'value' => 'Selected domain: ' . $form->getValue('$domain'),
//        ]);

//        $form->addControlButtons(['cancelLink' => pm_Context::getBaseUrl(),]);
        $form->addControlButtons(['sendTitle' => 'Delete Domain' ,'cancelLink' => pm_Context::getBaseUrl(),]);


        // Process the form - syncronise records for a specific domain
        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            //pm_Settings::set('selectedDomain', "NO DOMAIN SELECTED");
            pm_Settings::set('selectedDomainDelete', $form->getValue('selectedDomain'));
            $this->_status->addMessage('info', "Requested Domain Delete ".pm_Settings::get('selectedDomainDelete'));
//            }
            $this->_helper->json(['redirect' => (pm_Context::getActionUrl('index', 'add-task') . '/type/delete-a-domain')]);
        }
        $this->view->form = $form;
    }


    public function cancelAllAction()
    {
        $tasks = $this->taskManager->getTasks(['task_succeed']);
        $i = count($tasks) - 1;
        while ($i >= 0) {
            $this->taskManager->cancel($tasks[$i]);
            $i--;
        }
        pm_Settings::set('taskLock',null);
        $this->_status->addMessage('info', "cancelAll ");

        $this->_redirect('index/tools');
    }

    public function cancelAllTaskAction()
    {
        $this->taskManager->cancelAllTasks();
        pm_Settings::set('taskLock',null);
        $this->_status->addMessage('info', "cancelAllTask ");
        $this->_redirect('index/tools');
    }


    public function getDomainInfo()
    {
        $requestGet = <<<APICALL

        <webspace>
           <get>
            <filter>
            </filter>
             <dataset>
             <gen_info/>
             </dataset>
           </get>
        </webspace>

APICALL;
        $responseGet = pm_ApiRpc::getService()->call($requestGet);
        return $responseGet;
    }

}
