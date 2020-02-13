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
                pm_Settings::set('taskLock','locked');
                return ('Running Task: Sync All Domains With SafeDNS');
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
        // Unlock the forms
        pm_Settings::set('taskLock',null);
    }

/*    public function onStart()
    {
        /* The selected domain settings should not be changed while a task is running,
           We'll set taskLock and use this to lock the forms. */
/*        pm_Settings::set('taskLock','locked');
    }
*/
}
