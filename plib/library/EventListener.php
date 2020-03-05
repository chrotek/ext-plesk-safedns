<?php
class Modules_SafednsPlesk_EventListener implements EventListener
{

    public function safedns_write_log($log_msg) {
        $log_filename = "/var/log/plesk/ext-plesk-safedns";
        $log_timestamp= date("d-m-Y_H:i:s");
        $log_prepend = $log_timestamp." | ";
        if (!file_exists($log_filename)) {
            // create directory/folder uploads.
            mkdir($log_filename, 0770, true);
        }
        $log_file_data = $log_filename.'/ext-plesk-safedns-' . date('d-M-Y') . '.log';
        // if you don't add `FILE_APPEND`, the file will be erased each time you add a log
        file_put_contents($log_file_data, $log_prepend . $log_msg . "\n", FILE_APPEND);
    }


    public function filterActions()
    {
        return [
            'domain_dns_update',
        ];
    }

    public function handleEvent($objectType, $objectId, $action, $oldValues, $newValues)
    {
        
        $mynewValues=implode("|",$newValues);
        $newValuesArray=explode("|",$mynewValues);
        $updatedZone=$newValuesArray[0];
        // Retrieve Stored Settings Array for domain
        $zoneSettingsX=pm_Settings::get('zoneSettings-'.$updatedZone);
        // Explode the array's stored data from string to array
        $zoneSettings=explode("|",$zoneSettingsX);
        if (strcmp($zoneSettings[0], 'True') == 0) {
            if (strcmp($zoneSettings[2], 'True') == 0) {
                $this->safedns_write_log("$updatedZone's DNS Was updated in Plesk, Synchronising with SafeDNS");
                switch ($action) {
                    case 'domain_dns_update' :
                        // a domain's dns zone was updated ,sync all enabled zones
                        if (is_null($this->taskManager)) {
                            $this->taskManager = new pm_LongTask_Manager();
                        }
                        $this->safedns_write_log("$updatedZone's DNS Was updated in Plesk, Synchronising with SafeDNS");
                        pm_Settings::set('selectedDomainSychronise', $updatedZone);
                        $task=new Modules_SafednsPlesk_Task_SynchroniseADomain();
                        $this->taskManager->start($task, $domain);
                        // Clear completed tasks
                        $tasks = $this->taskManager->getTasks(['task_synchroniseadomain']);
                        $i = count($tasks) - 1;
                        while ($i >= 0) {
                            if ($tasks[$i]->getStatus() == pm_LongTask_Task::STATUS_DONE) {
                                $this->taskManager->cancel($tasks[$i]);
                            }
                            $i--;
                        } 
                        break;
                }
            }
        }
    }
}
return new Modules_SafednsPlesk_EventListener();
?>
