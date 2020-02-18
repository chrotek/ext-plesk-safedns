<?php

class Modules_SafednsPlesk_Task_Fail extends pm_LongTask_Task
{
    const UID = 'fail';
    public $trackProgress = false;
    private $sleep = 5;

    public function run()
    {
        pm_Log::info('Start method Run for Fail.');
        pm_Log::info('domain name is ' . $this->getParam('domainName', 'none'));
        sleep($this->sleep);
        $this->updateProgress(30);
        sleep($this->sleep);
        throw new pm_Exception('ERROR error');
        $this->updateProgress(60);
        sleep($this->sleep);
        $this->updateProgress(90);
    }

    public function statusMessage()
    {
        pm_Log::info('Start method statusMessage. ID: ' . $this->getId());
        switch ($this->getStatus()) {
            case static::STATUS_RUNNING:
//                return pm_Locale::lmsg('taskProgressMessage');
                return ('Simulating A Failing task');
            case static::STATUS_DONE:
                return ('Failed Simulation of A Failing task');
            case static::STATUS_ERROR:
                return pm_Locale::lmsg('taskError', ['id' => $this->getId()]);
            case static::STATUS_NOT_STARTED:
                return pm_Locale::lmsg('taskPingError', ['id' => $this->getId()]);
        }
        return '';
    }

    public function onError(Exception $e)
    {
        pm_Log::info('Start method onError');
        $this->setParam('onError', 1);
    }
}
