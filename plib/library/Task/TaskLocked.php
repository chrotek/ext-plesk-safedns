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
            case static::STATUS_ERROR:
                return 'Another task is running.';
        }
        return '';
    }

    public function onError(Exception $e)
    {
        pm_Log::info('Start method onError');
        $this->setParam('onError', 1);
    }
}
