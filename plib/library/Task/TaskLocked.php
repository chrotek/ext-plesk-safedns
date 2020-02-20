<?php

class Modules_SafednsPlesk_Task_TaskLocked extends pm_LongTask_Task
{
    const UID = 'task-locked';
    public $trackProgress = false;
    private $sleep = 5;

    public function run()
    {
        throw new pm_Exception('Please try again when it has completed.');
    }

    public function statusMessage()
    {
        pm_Log::info('Start method statusMessage. ID: ' . $this->getId());
        switch ($this->getStatus()) {
//            case static::STATUS_RUNNING:
//                return pm_Locale::lmsg('taskProgressMessage');
//                return ('Simulating A Failing task');
//            case static::STATUS_DONE:
//                return ('Failed Simulation of A Failing task');
            case static::STATUS_ERROR:
                return 'Another task is running.';
//            case static::STATUS_NOT_STARTED:
//                return pm_Locale::lmsg('taskPingError', ['id' => $this->getId()]);
        }
        return '';
    }

    public function onError(Exception $e)
    {
        pm_Log::info('Start method onError');
        $this->setParam('onError', 1);
    }
}
