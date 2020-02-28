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
        switch ($action) {
            case 'domain_dns_update' :
                // a domain's dns zone was updated ,sync all enabled zones
                // there's no api way to find out what domain it was, we'd have to parse the action log
                if (is_null($this->taskManager)) {
                    $this->taskManager = new pm_LongTask_Manager();
                }
                $this->safedns_write_log("Plesk DNS Was updated, Synchonising enabled zones with safedns");
                $task=new Modules_SafednsPlesk_Task_AutosyncEnabledDomains();
                $this->taskManager->start($task, $domain);
                break;
        }
    }
}
return new Modules_SafednsPlesk_EventListener();
?>
