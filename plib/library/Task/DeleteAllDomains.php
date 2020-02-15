<?php
   // Modules_SafednsPlesk_Task_SynchroniseDomains
//class Modules_SafednsPlesk_Task_Succeed extends pm_LongTask_Task
class Modules_SafednsPlesk_Task_DeleteAllDomains extends pm_LongTask_Task
{
    const UID = 'delete-all-domains';
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
                    pm_Settings::set('taskCurrentDeleteDomain',$pleskDomain);
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
                $taskCurrentDeleteDomain=pm_Settings::get('taskCurrentDeleteDomain');
                return "Deleting $taskCurrentDeleteDomain";
            case static::STATUS_DONE:
                return ('Successful Task: Delete all Domains From SafeDNS');
            case static::STATUS_ERROR:
                return ('Task Error: Delete all Domains From SafeDNS');
            case static::STATUS_NOT_STARTED:
                return ('Task Not Started - Delete all Domains From SafeDNS');
        }
        return '';
    }
    public function onDone()
    {
        pm_Settings::set('taskCurrentDeleteDomain',null);
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


}
