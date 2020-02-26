<?php
class Modules_SafednsPlesk_EventListener implements EventListener
{
//    ob_start();
    public function filterActions()
    {
        return [
            'domain_dns_update',
        ];
    }

    public function handleEvent($objectType, $objectId, $action, $oldValues, $newValues)
    {
        ob_start();
        echo "TEST\n";

        switch ($action) {
            case 'domain_dns_update' :
                // a domain's dns zone was updated ,sync all enabled zones
                // there's no api way to find out what domain it was, we'd have to parse the action log
                if (is_null($this->taskManager)) {
                    $this->taskManager = new pm_LongTask_Manager();
                }

                $task=new Modules_SafednsPlesk_Task_SynchroniseAllDomains();
                $this->taskManager->start($task, $domain);
                break;
        }
        $logfile='/testlog/safednsapi-tasks.log';
        $contents = ob_get_flush();
        file_put_contents($logfile,$contents);

    }
//    $logfile='/testlog/safednsapi-tasks.log';
//    $contents = ob_get_flush();
//    file_put_contents($logfile,$contents);
}
return new Modules_SafednsPlesk_EventListener();
?>
