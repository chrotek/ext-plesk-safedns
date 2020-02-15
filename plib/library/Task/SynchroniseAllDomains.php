<?php
   // Modules_SafednsPlesk_Task_SynchroniseDomains
//class Modules_SafednsPlesk_Task_Succeed extends pm_LongTask_Task
class Modules_SafednsPlesk_Task_SynchroniseAllDomains extends pm_LongTask_Task
{
    const UID = 'synchronise-all-domains';
    public $trackProgress = true;
    private $sleep = 1;
    private static $progressText = 'Progress is ';
    public function run()
    {
        pm_Settings::set('taskLock','locked');
        $domInfo = $this->getDomainInfo();
        $list = $domInfo->webspace->get->result;
        if ($list->status = 'ok') {
            // Calculate how much % each action is worth. Set % to 0.
            $pleskDomainCount = count($list);
            $actionPercent=(100/$pleskDomainCount);
            $currentPercent=0;
            foreach ($list as $domain) {
                if (isset($domain->data->gen_info->name)) {
                    $pleskDomain=$domain->data->gen_info->name;
                    pm_Settings::set('taskCurrentDomain',$pleskDomain);
                    $this->updateProgress($currentPercent);
                    sleep($this->sleep);
                    $currentPercent=($currentPercent+$actionPercent);                     
                }
                $this->updateProgress(100);
            }
        }


        
    }

    public function statusMessage()
    {
        pm_Log::info('Start method statusMessage. ID: ' . $this->getId() . ' with status: ' . $this->getStatus());
        switch ($this->getStatus()) {
            case static::STATUS_RUNNING:
                pm_Settings::set('taskLock','locked');
//                return ('Running Task: Sync All Domains With SafeDNS');
                $taskCurrentDomain=pm_Settings::get('taskCurrentDomain');
                return "Synchronising $taskCurrentDomain";
            case static::STATUS_DONE:
                return ('Successful Task: Sync All Domains With SafeDNS');
            case static::STATUS_ERROR:
                return ('Task Error: Sync All Domains With SafeDNS');

            case static::STATUS_NOT_STARTED:
                return ('Task Not Started: Sync All Domains');

        }
        return '';
    }
    public function onDone()
    {
        pm_Settings::set('taskCurrentDomain',null);

        // Unlock the forms
        pm_Settings::set('taskLock',null);
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

/*    public function onStart()
    {
        /* The selected domain settings should not be changed while a task is running,
           We'll set taskLock and use this to lock the forms. */
/*        pm_Settings::set('taskLock','locked');
    }
*/
}
