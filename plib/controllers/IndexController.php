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
        $this->view->pageTitle = 'SafeDNS Plesk Integration';
    }

    public function addTaskAction()
    {
     //   $this->cancelAllTaskAction();
        $domainId = $this->getParam('domainId', -1);
        $domainName = $this->getParam('domain');
//        $type = $this->getParam('type', 'succeed');
        $domain = $domainId != -1 ? new pm_Domain($domainId) : null;
        $type = $this->getParam('type', 'succeed');
        pm_Log::info("Create '{$type}' task and set params");
//        $task = $type === 'succeed'
//            ? new Modules_SafednsPlesk_Task_Succeed()
//            : new Modules_SafednsPlesk_Task_Fail();

//        $this->cancelAllTaskAction();

        // Fail if a task is already running
        if (pm_Settings::get('taskLock')) {
            $task=new Modules_SafednsPlesk_Task_TaskLocked();
        } else {
            // Clear other taks
            $this->taskManager->cancelAllTasks();
            if ($type == 'test-api-key') {
                $task=new Modules_SafednsPlesk_Task_TestApiKey();
            } elseif (!pm_Settings::get('validKey')) {
                $task=new Modules_SafednsPlesk_Task_InvalidKey();
            } elseif ($type == 'succeed') {
                $task=new Modules_SafednsPlesk_Task_Succeed();
            } elseif ($type == 'fail') {
                $task=new Modules_SafednsPlesk_Task_Fail();
            } elseif ($type == 'synchronise-all-domains') {
                $task=new Modules_SafednsPlesk_Task_SynchroniseAllDomains();
            } elseif ($type == 'synchronise-a-domain') {
                pm_Settings::set('selectedDomainSychronise', $domainName);
                $task=new Modules_SafednsPlesk_Task_SynchroniseADomain();
            } elseif ($type == 'delete-all-domains') {
                $task=new Modules_SafednsPlesk_Task_DeleteAllDomains();
            } elseif ($type == 'delete-a-domain') {
                pm_Settings::set('selectedDomainDelete', $domainName);
                $task=new Modules_SafednsPlesk_Task_DeleteADomain();
            }
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
//        $this->cancelAllAction();
        $this->_redirect(pm_settings::get('previousLocation'));
    }


    public function toolsAction() {
        $status='null';
        pm_settings::set('previousLocation','index/tools');

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
        // Init form here
/*        if (pm_Settings::get('taskLock')) {
            $form = new pm_Form_Simple();
            $this->_redirect('index');
            $this->_status->addMessage('warning', 'Please wait for current task to finish');
            $this->view->form = $form;
        }

        if (pm_Settings::get('taskLock')) {
            $taskLockStatus="taskLock is locked";
        } else {
            $taskLockStatus="taskLock is null";
        }        
*/
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
            ]

        ];
        $this->view->tabs = $this->_getTabs();
    }

    private function _getTabs()
    {
        $tabs = [];
        $tabs[] = [
            'title' => 'Welcome Page',
            'action' => 'welcome',
        ];
        $tabs[] = [
            'title' => 'Manage DNS Zones',
            'action' => 'manageZones',
        ];
        $tabs[] = [
            'title' => 'Tools & Settings',
            'action' => 'tools',
        ];

        return $tabs;
    }


    public function indexAction()
    {
        // Default action is formAction
        $this->_forward('welcome');


    }
    public function welcomeAction() {

        $form = new pm_Form_Simple();
        $form->addElement('SimpleText', 'text', [
            'value' => 'Count of global failed tasks:<br> 
                        Next line of text',
        ]);
        $this->view->form = $form;
        $this->view->tabs = $this->_getTabs();
    }

    public function managezonesAction() {
        $list = $this->_getZoneList();
        // List object for pm_View_Helper_RenderList
        $this->view->tabs = $this->_getTabs();
        $this->view->list = $list;
    }

    private function _getZoneList() {
        $data = [];
        $blueRefreshIcon= 'modules/safedns-plesk/icons/32/refresh.png';
        $redPowerIcon= 'modules/safedns-plesk/icons/32/power-red.png';
        $bluePowerIcon= 'modules/safedns-plesk/icons/32/power-blue.png';
        $redCrossIcon= 'modules/safedns-plesk/icons/32/cross-red.png';
        $greenTickIcon= 'modules/safedns-plesk/icons/32/tick-green.png';
        $redBinIcon= 'modules/safedns-plesk/icons/32/red-bin.png';
        $domInfo = $this->getDomainInfo();
        $pleskDomainList = $domInfo->webspace->get->result;
        if ($pleskDomainlist->status = 'ok') {
            // Calculate how much % each action is worth. Set % to 0.
            $pleskDomainCount = count($pleskDomainList);
            $actionPercent=(100/$pleskDomainCount);
            $currentPercent=0;
            foreach ($pleskDomainList as $domain) {
                if (isset($domain->data->gen_info->name)) {
                    $plesk_domain=(string)$domain->data->gen_info->name;
                    if (pm_Settings::get('zoneSettings-'.$plesk_domain)) {
                        $zoneSettingsX=pm_Settings::get('zoneSettings-'.$plesk_domain);
                        $zoneSettings=explode("|",$zoneSettingsX);
                        // If current domain has enabled set to true

                        if (strcmp($zoneSettings[0], 'True') == 0) {
                            $domainEnabledIcon=$bluePowerIcon;
                            $domainEnabledStatus=True;
                            $domainEnabledText='Y';
                            $newEnabledSetting='False';
                            $syncNowLink=pm_Context::getActionUrl('index','add-task').'/type/synchronise-a-domain/domain/'.$plesk_domain;
                            $toggleAutosyncLink=pm_Context::getActionUrl('index','toggle-autosync-zone').'/domain/'.$plesk_domain.'/new-autosync-setting/'.$newAutosyncSetting;
                            $deleteDomainLink=pm_Context::getActionUrl('index','add-task').'/type/delete-a-domain/domain/'.$plesk_domain;
                        } else {
                            $domainEnabledIcon=$redPowerIcon;
                            $domainEnabledStatus=False;
                            $domainEnabledText='N';
                            $newEnabledSetting='True'; 
                            $syncNowLink=pm_Context::getActionUrl('index','synchronise-disabled-zone-fail/domain/').$plesk_domain;
                            $toggleAutosyncLink=pm_Context::getActionUrl('index','autosync-disabled-zone-fail/domain/').$plesk_domain;
                            $deleteDomainLink=pm_Context::getActionUrl('index','delete-disabled-zone-fail/domain/').$plesk_domain;
                        };
                        // Load LastSync time. Currently being misused as a debug function.
                        $lastSync=$zoneSettings[1];
                        // If current domaind has autosync enabled
                        if (strcmp($zoneSettings[2], 'True') == 0) {
                            $autosyncEnabledIcon=$greenTickIcon;
                            $autosyncEnabledStatus='False';
                            $autosyncEnabledText='Y';
                            $newAutosyncSetting='False';
                        } else {
                            $autosyncEnabledIcon=$redCrossIcon;
                            $autosyncEnabledStatus='False';
                            $autosyncEnabledText='N';
                            $newAutosyncSetting='True';
                        };
 
                        // Block manual sync if domain is not enabled

                    } else {
                        // Save Domain with Default Settings
                        $domainEnabledIcon=$redPowerIcon;
                        $domainEnabledStatus='False';
                        $lastSync='Never';
                        $domainEnabledText='N';
                        $autosyncEnabledIcon=$redCrossIcon;
                        $autosyncEnabledStatus='False';
                        $autosyncEnabledText='N';
                        
                        $newEnabledSetting='True';
                        $newAutosyncSetting='True';                        
                        $zoneSettingsX=array($domainEnabledStatus,$lastSync,$autosyncEnabledStatus);
                        $zoneSettings=implode("|",$zoneSettingsX);
                        pm_Settings::set('zoneSettings-'.$plesk_domain,$zoneSettings);
                    }
                    $data[] = [
                        'column-1-domain' => '<a href="#">' . $plesk_domain . '</a>',
                        'column-2-enabled' => '<a href="'.pm_Context::getActionUrl('index','toggle-enable-zone').'/domain/'.$plesk_domain.'/new-enabled-setting/'.$newEnabledSetting.'"><img alt="'.$domainEnabledText.'" src="/'.$domainEnabledIcon.'" /> </a>',      
                        'column-3-syncnow' => '<a href="'.$syncNowLink.'"><img alt="Sync Now" src="/'.$blueRefreshIcon.'" /> </a>',
                        'column-4-lastsync' => '<p>'.$lastSync.'</p>',
                        'column-5-autosync' => '<a href="'.$toggleAutosyncLink.'"><img alt="'.$autosyncEnabledText.'" src="/'.$autosyncEnabledIcon.'" /> </a>',
                        'column-6-deletedomain' => '<a href="'.$deleteDomainLink.'"><img alt="Delete Domain" src="/'.$redBinIcon.'" /> </a>',
                        'column-7-debug' => '<p>'.$zoneSettingsX.'</p>',
                    ];
                    
                    $options = [
                        'defaultSortField' => 'column-1',
                        'defaultSortDirection' => pm_View_List_Simple::SORT_DIR_DOWN,
                    ];

                }
            }

        $list = new pm_View_List_Simple($this->view, $this->_request);
        $list->setData($data);
        $list->setColumns([
            'column-1-domain' => [
                'title' => 'Domain',
                'noEscape' => true,
                'searchable' => true,
                'sortable' => false,

            ],
            'column-2-enabled' => [
                'title' => 'Enabled',
                'noEscape' => true,
                'sortable' => false,
            ],            
            'column-3-syncnow' => [
                'title' => 'Sync Now',
                'noEscape' => true,
                'sortable' => false,
            ],
            'column-4-lastsync' => [
                'title' => 'Last Synchronise',
                'noEscape' => true,
                'sortable' => false,
            ],
            'column-5-autosync' => [
                'title' => 'Automatic Sync <br> (on zone change)',
                'noEscape' => true,
                'sortable' => false,
            ],
            'column-6-deletedomain' => [
                'title' => 'Delete Zone <br> (From SafeDNS)',
                'noEscape' => true,
                'sortable' => false,
            ],
            'column-7-debug' => [
                'title' => 'Debug. (Settings array)',
                'noEscape' => true,
                'sortable' => false,
            ]

        ]);
        pm_settings::set('previousLocation','index/manageZones');
        $list->setTools([[
                'title' => 'Default Reset',
                'description' => 'Reset all settings.',
                'link' => pm_Context::getActionUrl('index', 'reset-zones'),


            ],
        ]);

        $list->setDataUrl(['action' => 'list-data']);
        return $list;
        }
    }
    public function listDataAction() {
        $list = $this->_getZoneList();
        // Json data from pm_View_List_Simple
        $this->_helper->json($list->fetchData());
    }
    public function synchroniseDisabledZoneFailAction() {
        $domainx = $this->getParam('domain');
        $this->_status->addMessage('warning', "Please enable service for $domainx before attempting to synchronise.");        
        $this->_redirect('index/manageZones');
    }
    public function deleteDisabledZoneFailAction() {
        $domainx = $this->getParam('domain');
        $this->_status->addMessage('warning', "Please enable service for $domainx before attempting to delete zone.");
        $this->_redirect('index/manageZones');
    }
    public function autosyncDisabledZoneFailAction() {
        $domainx = $this->getParam('domain');
        $this->_status->addMessage('warning', "Please enable service for $domainx before attempting to enable Autosync.");
        $this->_redirect('index/manageZones');
    }

    public function toggleEnableZoneAction() {
        // Load the domain from url parameter
        $domainx = $this->getParam('domain');

        // Load the new setting from the next url parameter
        $newEnabledSetting = $this->getParam('new-enabled-setting');

        // Retrieve Stored Settings Array for domain     
        $zoneSettingsX=pm_Settings::get('zoneSettings-'.$domainx);

        // Explode the array's stored data from string to array
        $zoneSettings=explode("|",$zoneSettingsX);

        // Create new Array with changed setting
        $newZoneSettingsX=array($newEnabledSetting,$zoneSettings[1],$zoneSettings[2]);

        // Implode the array with new data, from array to string
        $newZoneSettings=implode("|",$newZoneSettingsX);
        var_dump($newZoneSettings);

        // Save the modified string to Plesk key value storage
        pm_Settings::set('zoneSettings-'.$domainx,$newZoneSettings);
        
        // Notification
        $this->_status->addMessage('info', "enableZoneAction domain:$domainx new setting:$newEnabledSetting");

        // Redirect to manageZones
        $this->_redirect('index/manageZones');

    }
    public function toggleAutosyncZoneAction() {
        // Load the domain from url parameter
        $domainx = $this->getParam('domain');

        // Load the new setting from the next url parameter
        $newAutosyncSetting = $this->getParam('new-autosync-setting');

        // Retrieve Stored Settings Array for domain
        $zoneSettingsX=pm_Settings::get('zoneSettings-'.$domainx);

        // Explode the array's stored data from string to array
        $zoneSettings=explode("|",$zoneSettingsX);

        // Create new Array with changed setting
        $newZoneSettingsX=array($zoneSettings[0],$zoneSettings[1],$newAutosyncSetting);

        // Implode the array with new data, from array to string
        $newZoneSettings=implode("|",$newZoneSettingsX);
        var_dump($newZoneSettings);

        // Save the modified string to Plesk key value storage
        pm_Settings::set('zoneSettings-'.$domainx,$newZoneSettings);

        // Notification
        $this->_status->addMessage('info', "enableAutosyncAction domain:$domainx new setting:$newEnabledSetting");

        // Redirect to manageZones
        $this->_redirect('index/manageZones');


    }
 
    public function resetZonesAction() {
        $domInfo = $this->getDomainInfo();
        $pleskDomainList = $domInfo->webspace->get->result;
        if ($pleskDomainlist->status = 'ok') {
            // Calculate how much % each action is worth. Set % to 0.
            $pleskDomainCount = count($pleskDomainList);
            $actionPercent=(100/$pleskDomainCount);
            $currentPercent=0;
            foreach ($pleskDomainList as $domain) {
                if (isset($domain->data->gen_info->name)) {
                    $plesk_domain=(string)$domain->data->gen_info->name;
                    pm_Settings::set('zoneSettings-'.$plesk_domain,null);

                }
            }
        }
        $this->_redirect('index/manageZones');
   
    }
    
    public function apikeyformAction() {
        // Init form here
        $form = new pm_Form_Simple();    
        // Set the description text
        $this->view->output_description = 'API Key Configuration';
        $form->addElement('text', 'api_key', ['label' => 'Please enter API Key', 'value' => pm_Settings::get('api_key'), 'style' => 'width: 40%;']);
        $form->addControlButtons(['cancelLink' => pm_Context::getBaseUrl(),]);

        // Process the form - save the api key and run the installation scripts
        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            if ($form->getValue('api_key')) {
                $this->_api_key = $form->getValue('api_key');
            }

            pm_Settings::set('api_key', $this->_api_key);
            $this->_status->addMessage('info', 'API Key Saved');
            $this->_helper->json(['redirect' => (pm_Context::getActionUrl('index', 'add-task') . '/type/test-api-key')]);
        }
        $this->view->form = $form;
    }

    public function syncadomainAction() {

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
            $this->_helper->json(['redirect' => (pm_Context::getActionUrl('index', 'add-task') . '/type/synchronise-a-domain/domain/'.pm_Settings::get('selectedDomainSychronise'))]);
        }
        $this->view->form = $form;
    }

    public function deleteadomainAction() {
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

//            pm_Settings::set('selectedDomainDelete', $form->getValue('selectedDomain'));
            $domainToDelete=$form->getValue('selectedDomain');
            $this->_status->addMessage('info', "Requested Domain Delete ".$domainToDelete);
//            }
//            $this->_helper->json(['redirect' => (pm_Context::getActionUrl('index', 'add-task') . '/type/delete-a-domain/domain/'.$domainToDelete)]);
            $this->_helper->json(['redirect' => (pm_Context::getActionUrl('index', 'add-task') . '/type/delete-a-domain/domain/'.pm_Settings::get('selectedDomainDelete'))]);

        }
        $this->view->form = $form;
    }
//$this->cancelAllAction();
    public function cancelAction()
    {
        pm_Log::info('Try get tasks');
        $tasks = $this->taskManager->getTasks(['task-synchronisealldomains']);
        $i = count($tasks) - 1;
        while ($i >= 0) {
            echo "Tasks \n";
            if ($tasks[$i]->getStatus() == pm_LongTask_Task::STATUS_DONE) {
                $this->taskManager->cancel($tasks[$i]);
                break;
            }
            $i--;
        }
        $this->_redirect('index/tools');

    }
    public function cancelDoneTaskAction()
    {
        pm_Log::info('Try get tasks');
        $tasks = $this->taskManager->getTasks(['task']);
        $i = count($tasks) - 1;
        while ($i >= 0) {
            if ($tasks[$i]->getStatus() != pm_LongTask_Task::STATUS_DONE) {
                $this->taskManager->cancel($tasks[$i]);
                break;
            }
            $i--;
        }
        $this->_redirect('index/index');
    }

    public function cancelAllTaskAction()
    {
        $this->taskManager->cancelAllTasks();
        pm_Settings::set('taskLock',null);
        $this->_status->addMessage('info', "cancelAllTask ");
        $this->_redirect(pm_settings::get('previousLocation'));
//        $this->_redirect('index/tools');
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
