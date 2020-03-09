<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.


class IndexController extends pm_Controller_Action
{
    private $taskManager = NULL;
    protected $_accessLevel = 'admin';
//    private $_api_key = (pm_Settings::get('api_key'));
    public function init()
    {
        parent::init();
        if (is_null($this->taskManager)) {
            $this->taskManager = new pm_LongTask_Manager();
        }
        $this->view->pageTitle = 'UKFast SafeDNS Plesk Integration';
    }

    public function safedns_write_log($log_msg) {
        $log_filename = "/var/log/plesk/ext-plesk-safedns";
        $log_timestamp= date("d-m-Y_H:i:s");
        $log_prepend = $log_timestamp." | IndexController (GUI) | ";
        if (!file_exists($log_filename)) {
            // create directory/folder uploads.
            mkdir($log_filename, 0770, true);
        }
        $log_file_data = $log_filename.'/ext-plesk-safedns-' . date('d-M-Y') . '.log';
        // if you don't add `FILE_APPEND`, the file will be erased each time you add a log
        file_put_contents($log_file_data, $log_prepend . $log_msg . "\n", FILE_APPEND);
    }

    public function addTaskAction()
    {
        $domainId = $this->getParam('domainId', -1);
        $domainName = $this->getParam('domain');
        $domain = $domainId != -1 ? new pm_Domain($domainId) : null;
        $type = $this->getParam('type', 'succeed');
        pm_Log::info("Create '{$type}' task and set params");

        // Fail if a task is already running
        if (pm_Settings::get('taskLock')) {
            $task=new Modules_SafednsPlesk_Task_TaskLocked();
        } else {
            // Clear other taks
            $this->taskManager->cancelAllTasks();
            // Set task Type based on Params passed
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

        if (isset($domain)) {
            $task->setParam('domainName', $domain->getName());
        }
        $this->taskManager->start($task, $domain);
        $this->_redirect(pm_settings::get('previousLocation'));
    }


    public function toolsAction() {
        $status='null';
        pm_settings::set('previousLocation','index/tools');
	if (pm_Settings::get('enabled')) {
            $status='enabled';
            $originalsetting='true';
            $toggle_link=pm_Settings::set('enabled',null);
        } else {
            $status='disabled';
            $originalsetting=null;
            $toggle_link=pm_Settings::set('enabled','true');
        };
        pm_Settings::set('enabled',$originalsetting);					
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
        // If no specific page was requested, Default to Welcome 
        $this->_forward('welcome');


    }
    public function welcomeAction() {

        $form = new pm_Form_Simple();
        pm_settings::set('previousLocation','index/welcome');
        if (!pm_Settings::get('setup_IP')) {
            $form->addElement('SimpleText', 'text', [
                'value' => "This extension allows you to manage your SafeDNS Zones from inside plesk."
            ]);
            $form->addElement('SimpleText', 'text2', [
                'value' => "There are some settings you need to check before use, to make sure the DNS records will be valid."
            ]);
            $form->addElement('SimpleText', 'text3', [
                'value' => "First, make sure the server's public IP address is set in Plesk."
            ]);
            $form->addElement('SimpleText', 'text4', [
                'value' => "Please go to Tools & Settings > IP Addresses, and make sure there is a Public IP Address set."
            ]);
            $form->addElement('SimpleText', 'text5', [
                'value' => "Then click Next below."
            ]);
            $form->addControlButtons(['sendTitle' => 'Next','cancelHidden' => true,'hideLegend'=>true]);
            if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
                pm_Settings::set('setup_IP', "complete");
                $this->_helper->json(['redirect' => (pm_Context::getActionUrl('index', 'welcome'))]);
            }
        } elseif (!pm_Settings::get('hostnameChecked')) {
             $form->addElement('SimpleText', 'checkhostname', [
                 'value' => "Next, check the hostname is correct"
             ]);
             $form->addElement('SimpleText', 'checkhostname2', [
                 'value' => "Please go to Tools & Settings > Server Settings, and check the hostname."
             ]);
             $form->addElement('SimpleText', 'checkhostname3', [
                 'value' => "The full hostname must also resolve to the server's public IP address. If it does not , go to SafeDNS and create the A record"
             ]);
             $form->addControlButtons(['sendTitle' => 'Next','cancelHidden' => true,'hideLegend'=>true]);
                 if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
                     pm_Settings::set('hostnameChecked','true');
                 $this->_helper->json(['redirect' => (pm_Context::getActionUrl('index', 'welcome'))]);
                 }
        } elseif (!pm_Settings::get('nameserverChanged')) {
             $form->addElement('SimpleText', 'changens', [
                 'value' => "Next, the nameservers in Plesk's dns zones must be set to ukfast."
             ]);
             $form->addElement('SimpleText', 'changens2', [
                 'value' => "Please go to Tools & Settings > General Settings > DNS Zone Template, and change the NS records to:"
             ]);
             $form->addElement('SimpleText', 'changens3', [
                 'value' => "ns0.ukfast.net"
             ]);
             $form->addElement('SimpleText', 'changens4', [
                 'value' => "ns1.ukfast.net"
             ]);
             $form->addElement('SimpleText', 'changens5', [
                 'value' => "Then, click 'Apply DNS Template Changes' , and select 'Apply the changes to all zones.'   "
             ]);
             $form->addElement('SimpleText', 'changens6', [
                 'value' => "Optional: If any of your domains use an external DNS service, you can update the DNS for individual domains in Home > Domains"
             ]);
             $form->addElement('SimpleText', 'changens7', [
                 'value' => "Finally, Click the Complete button below"
             ]);
             $form->addControlButtons(['sendTitle' => 'Complete','cancelHidden' => true,'hideLegend'=>true]);
                 if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
                     pm_Settings::set('nameserverChanged','true');
                     pm_Settings::set('setupCompleted','true');
                 $this->_helper->json(['redirect' => (pm_Context::getActionUrl('index', 'welcome'))]);
                 }
        } elseif (!pm_Settings::get('validKey')) {
             $form->addElement('SimpleText', 'enterkey', [
                 'value' => "Finally, enter an API Key below and click Save."
             ]);
             $form->addElement('SimpleText', 'enterkey2', [
                 'value' => "If you need to create or reset an API Key, you can do this in your MyUKFast account, under 'API Applications'."
             ]);
             $form->addElement('SimpleText', 'enterkey4', [
                 'value' => "The key should have Read & Write Access to SafeDNS, and DDoSX."
             ]);
             $form->addElement('SimpleText', 'enterkey5', [
                 'value' => "NOTE: DDoSX Will be implemented in a future release."
             ]);
             $form->addElement('text', 'api_key', ['label' => 'Please enter API Key', 'value' => pm_Settings::get('api_key'), 'style' => 'width: 40%;']);
             $form->addControlButtons(['sendTitle' => 'Save','cancelHidden' => true,'hideLegend'=>true]);
             $form->addElement('SimpleText', 'enterkey6', [
                 'value' => 'When you get the "API Key is Valid" notification, Setup is complete and you can go to the Manage DNS Zones tab to configure some domains.'
             ]);
             if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
                 if ($form->getValue('api_key')) {
                     $this->_api_key = $form->getValue('api_key');
                 }
                 pm_Settings::set('api_key', $this->_api_key);
                 $this->_status->addMessage('info', 'API Key Saved');
                 pm_settings::set('previousLocation','index/welcome');
                 $this->_helper->json(['redirect' => (pm_Context::getActionUrl('index', 'add-task') . '/type/test-api-key')]);
             }
//            } else {
//                $form->addElement('SimpleText', 'validkey', [
//                    'value' => "Great! The key is valid."
//                ]);
        } elseif (pm_Settings::get('validKey')) {
            $form->addElement('SimpleText', 'validkey2', [
                'value' => "Setup is Complete, You can now go to the Manage DNS Zones tab to configure some domains."
            ]);
            $form->addControlButtons(['sendTitle' => 'Go to Manage DNS Zones','cancelHidden' => true,'hideLegend'=>true]);
            if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
                $this->_helper->json(['redirect' => (pm_Context::getActionUrl('index', 'manageZones'))]);
          //  }
            }
        }
        //}
        //}
        $this->view->form = $form;
        $this->view->tabs = $this->_getTabs();
    }

    public function managezonesAction() {
        if (pm_Settings::get('mz_show_help')) {
            echo "<h3>Manage DNS Zones Help.</h3>";
            echo "To perform any operation on a SafeDNS Zone from Plesk, it must first be enabled<br><br>
                  <h5>Sync Now</h5> 
                  This will push an enabled zone to SafeDNS.<br>
                  If a record already exists, it will be updated to match plesk.<br>
                  If a record exists on SafeDNS but has been removed, (or didn't exist) on Plesk, it will be deleted<br><br>
                  <h5>Automatic Sync</h6>
                  When Plesk's internal DNS is updated, the domains that have this enabled, will be automatically Sychronised with SafeDNS.<br>
                  <hr>";  
            }
        $list = $this->_getZoneList();
        $this->view->tabs = $this->_getTabs();
        $this->view->list = $list;
    }

    private function _getZoneList() {
        $data = [];
        $blueRefreshIcon= 'modules/safedns-plesk/icons/32/refresh.png';
        $redPowerIcon= 'modules/safedns-plesk/icons/64/Red-Offswitch.png';
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
                            $domainEnabledIcon=$greenTickIcon;
                            $domainEnabledStatus=True;
                            $domainEnabledText='Y';
                            $newEnabledSetting='False';
                            $syncNowLink=pm_Context::getActionUrl('index','add-task').'/type/synchronise-a-domain/domain/'.$plesk_domain;
                            $deleteDomainLink=pm_Context::getActionUrl('index','add-task').'/type/delete-a-domain/domain/'.$plesk_domain;
                            $toggleAutosyncLink=pm_Context::getActionUrl('index','toggle-autosync-zone').'/domain/'.$plesk_domain.'/new-autosync-setting/'.$newAutosyncSetting;
                        } else {
                            $domainEnabledIcon=$redCrossIcon;
                            $domainEnabledStatus=False;
                            $domainEnabledText='N';
                            $newEnabledSetting='True'; 
                            $syncNowLink=pm_Context::getActionUrl('index','synchronise-disabled-zone-fail/domain/').$plesk_domain;
                            $toggleAutosyncLink=pm_Context::getActionUrl('index','autosync-disabled-zone-fail/domain/').$plesk_domain;
                            $deleteDomainLink=pm_Context::getActionUrl('index','delete-disabled-zone-fail/domain/').$plesk_domain;
                            $toggleAutosyncLink=pm_Context::getActionUrl('index','autosync-disabled-zone-fail');
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
                       // $toggleAutosyncLink=pm_Context::getActionUrl('index','toggle-autosync-zone').'/domain/'.$plesk_domain.'/new-autosync-setting/'.$newAutosyncSetting;

                        // Block manual sync if domain is not enabled

                    } else {
                        // Save Domain with Default Settings
                        $domainEnabledIcon=$redCrossIcon;
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
            //],
            ]
//            'column-7-debug' => [
//                'title' => 'Debug. (Settings array)',
//                'noEscape' => true,
//                'sortable' => false,
//            ]

        ]);
        pm_settings::set('previousLocation','index/manageZones');
        if (pm_Settings::get('mz_show_help')) {
            $mz_help_title='Hide Help';
            $mz_help_descr='Hides Help Text';
            $mz_help_link=pm_Context::getActionUrl('index', 'mz-help/new-help-setting/Hide');
        } elseif (!pm_Settings::get('mz_show_help')) {
            $mz_help_title='Show Help';
            $mz_help_descr='Shows Help Text';
            $mz_help_link=pm_Context::getActionUrl('index', 'mz-help/new-help-setting/Show');

        }

        $list->setTools([[
                'title' => 'Reset to Default',
                'description' => 'Reset all settings.',
                'link' => pm_Context::getActionUrl('index', 'reset-zones'),
            ],[ 
                'title' => 'Enable All Domains',
                'description' => 'Enable All Domains',
                'link' => pm_Context::getActionUrl('index', 'mz-enable-all-domains/new-enabled-setting/True'),
            ],[
                'title' => 'Disable All Domains',
                'description' => 'Enable All Domains',
                'link' => pm_Context::getActionUrl('index', 'mz-enable-all-domains/new-enabled-setting/False'),
            ],[
                'title' => 'Enable All Autosync',
                'description' => 'Enable All Autosync',
                'link' => pm_Context::getActionUrl('index', 'mz-enable-all-autosync/new-enabled-setting/True'),
            ],[
                'title' => 'Disable All Autosync',
                'description' => 'Disable All Autosync',
                'link' => pm_Context::getActionUrl('index', 'mz-enable-all-autosync/new-enabled-setting/False'),
            ],[
                'title' => 'Sync All Enabled Domains',
                'description' => 'Sync All Enabled Domains',
                'link' => pm_Context::getActionUrl('index', 'add-task') . '/type/synchronise-all-domains',
            ],[
                'title' => $mz_help_title,
                'description' => $mz_help_desc,
                'link' => $mz_help_link,
            ],
        ]);

        $list->setDataUrl(['action' => 'list-data']);
        return $list;
        }
    }
    public function mzEnableAllDomainsAction() {
        $domInfo = $this->getDomainInfo();
        $pleskDomainList = $domInfo->webspace->get->result;
        if ($pleskDomainlist->status = 'ok') {
            // Calculate how much % each action is worth. Set % to 0.
            foreach ($pleskDomainList as $domain) {
                if (isset($domain->data->gen_info->name)) {
                    $plesk_domain=(string)$domain->data->gen_info->name;
                    // Load the new setting from the next url parameter
                    $newEnabledSetting = $this->getParam('new-enabled-setting');
                    // Retrieve Stored Settings Array for domain
                    $zoneSettingsX=pm_Settings::get('zoneSettings-'.$plesk_domain);
                    // Explode the array's stored data from string to array
                    $zoneSettings=explode("|",$zoneSettingsX);

                    // If zone is being disabled, also disable autosync
                    if (strcmp($newEnabledSetting, 'False') == 0) {
                        $newZoneSettingsX=array($newEnabledSetting,$zoneSettings[1],$newEnabledSetting);
                    } else {
                        // Create new Array with changed setting
                        $newZoneSettingsX=array($newEnabledSetting,$zoneSettings[1],$zoneSettings[2]);
                    }


                    // Implode the array with new data, from array to string
                    $newZoneSettings=implode("|",$newZoneSettingsX);
                    // Save the modified string to Plesk key value storage
                    pm_Settings::set('zoneSettings-'.$plesk_domain,$newZoneSettings);
                }
            }
        }
        $this->_redirect('index/manageZones');
        
    }
    public function mzEnableAllAutosyncAction() {
        $domInfo = $this->getDomainInfo();
        $pleskDomainList = $domInfo->webspace->get->result;
        if ($pleskDomainlist->status = 'ok') {
            // Calculate how much % each action is worth. Set % to 0.
            foreach ($pleskDomainList as $domain) {
                if (isset($domain->data->gen_info->name)) {
                    $plesk_domain=(string)$domain->data->gen_info->name;
                    // Retrieve Stored Settings Array for domain
                    $zoneSettingsX=pm_Settings::get('zoneSettings-'.$plesk_domain);
                    // Explode the array's stored data from string to array
                    $zoneSettings=explode("|",$zoneSettingsX);
                    if (strcmp($zoneSettings[0], 'True') == 0) {
                        $newEnabledSetting = $this->getParam('new-enabled-setting');
                    } else {
                        $this->_status->addMessage('warning', "Not all domains are enabled, soyou can't enable autosync for them.");
                        $newEnabledSetting = $zoneSettings[2];
                    }
                     // Create new Array with changed setting
                    $newZoneSettingsX=array($zoneSettings[0],$zoneSettings[1],$newEnabledSetting);
                    // Implode the array with new data, from array to string
                    $newZoneSettings=implode("|",$newZoneSettingsX);
 
                    // Save the modified string to Plesk key value storage
                    pm_Settings::set('zoneSettings-'.$plesk_domain,$newZoneSettings);
                }
            }
        }
        $this->_redirect('index/manageZones');
    }
    public function mzHelpAction() {
        $helpSettingParam = $this->getParam('new-help-setting');
        // Save the modified string to Plesk key value storage
        if (strcmp($helpSettingParam, 'Show') == 0) {
           pm_Settings::set('mz_show_help','Show');
           $this->_status->addMessage('info', "Help text is at the bottom of the page"); 
        } elseif (strcmp($helpSettingParam, 'Hide') == 0) {
            pm_Settings::set('mz_show_help',null);
        }
        //$this->_status->addMessage('warning', "Show Hide Help $helpSettingParam");
        $this->_redirect('index/manageZones');
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
        
        // If zone is being disabled, also disable autosync
        if (strcmp($newEnabledSetting, 'False') == 0) {
            $newZoneSettingsX=array($newEnabledSetting,$zoneSettings[1],$newEnabledSetting);
        } else {
            // Create new Array with changed setting
            $newZoneSettingsX=array($newEnabledSetting,$zoneSettings[1],$zoneSettings[2]);
        }
        // Implode the array with new data, from array to string
        $newZoneSettings=implode("|",$newZoneSettingsX);
//        var_dump($newZoneSettings);

        // Save the modified string to Plesk key value storage
        pm_Settings::set('zoneSettings-'.$domainx,$newZoneSettings);
        
        // Notification
        //$this->_status->addMessage('info', "enableZoneAction domain:$domainx new setting:$newEnabledSetting");

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
        //$this->_status->addMessage('info', "enableAutosyncAction domain:$domainx new setting:$newEnabledSetting");

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
        $this->view->output_description = 'Synchronise a Domain';
        $domInfo = $this->getDomainInfo();
        $list = $domInfo->webspace->get->result;

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
        $form->addControlButtons(['sendTitle' => 'Delete Domain' ,'cancelLink' => pm_Context::getBaseUrl(),]);

        // Process the form - syncronise records for a specific domain
        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            $domainToDelete=$form->getValue('selectedDomain');
            $this->_status->addMessage('info', "Requested Domain Delete ".$domainToDelete);
            $this->_helper->json(['redirect' => (pm_Context::getActionUrl('index', 'add-task') . '/type/delete-a-domain/domain/'.pm_Settings::get('selectedDomainDelete'))]);
        }
        $this->view->form = $form;
    }

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
        $tasks = $this->taskManager->getTasks(['task_synchroniseadomain']);
        $i = count($tasks) - 1;
        while ($i >= 0) {
            $this->safedns_write_log("\ncanceldonetask\n");
            $this->safedns_write_log($tasks[$i]->getStatus);
            if ($tasks[$i]->getStatus() == pm_LongTask_Task::STATUS_DONE) {
                $this->taskManager->cancel($tasks[$i]);
            }
            $i--;
        }
        $this->safedns_write_log("canceldonetask-END");
        //$this->_status->addMessage('info', "cancelDoneTaskAction");

        $this->_redirect('index/index');
    }

    public function cancelAllTaskAction()
    {
        $this->taskManager->cancelAllTasks();
        pm_Settings::set('taskLock',null);
        //$this->_status->addMessage('info', "cancelAllTask ");
        $this->_redirect(pm_settings::get('previousLocation'));
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
