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
//        pm_Log::info('Start method Run for Succeed.');
//        pm_Log::info('p2 is ' . $this->getParam('p2'));
//        pm_Log::info('p3 is ' . $this->getParam('p3'));
//        pm_Log::info('domain name is ' . $this->getParam('domainName', 'none'));
        $this->updateProgress(0);
        pm_Log::info(self::$progressText . $this->getProgress());
        sleep($this->sleep);
        $this->updateProgress(20);
        pm_Log::info(self::$progressText . $this->getProgress());
        sleep($this->sleep);
        $this->updateProgress(40);
        pm_Log::info(self::$progressText . $this->getProgress());
        sleep($this->sleep);
        $this->updateProgress(60);
        pm_Log::info(self::$progressText . $this->getProgress());
        pm_Log::info('Status after 60% progress: ' . $this->getStatus());
        sleep($this->sleep);
    }

    public function statusMessage()
    {
        pm_Log::info('Start method statusMessage. ID: ' . $this->getId() . ' with status: ' . $this->getStatus());
        switch ($this->getStatus()) {
            case static::STATUS_RUNNING:
//                return pm_Locale::lmsg('Syncronising All Domain');
                pm_Settings::set('taskLock','locked');
                return ('Running Task: Delete All Plesk Domains From SafeDNS');
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
        // Unlock the forms
        pm_Settings::set('taskLock',null);
    }

/*    public function onStart()
    {
        pm_Log::info('Start method onStart');
        pm_Log::info('p1 is ' . $this->getParam('p1'));
        $this->setParam('onStart', 1);
    }

    public function onDone()
    {
        pm_Log::info('Start method onDone');
        $this->setParam('onDone', 1);
        pm_Log::info('End method onDone');
        pm_Log::info('Status: ' . $this->getStatus());
    }*/
}
