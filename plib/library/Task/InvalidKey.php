<?php

class Modules_SafednsPlesk_Task_InvalidKey extends pm_LongTask_Task
{
    const UID = 'invalid-key';
    public $trackProgress = false;

    public function run()
    {
        throw new pm_Exception('You can reset it your MyUKFast dashboard');
    }

    public function statusMessage()
    {
        switch ($this->getStatus()) {
//            case static::STATUS_RUNNING:
//                return pm_Locale::lmsg('taskProgressMessage');
//                return ('Simulating A Failing task');
//            case static::STATUS_DONE:
//                return ('Failed Simulation of A Failing task');
            case static::STATUS_ERROR:
                return 'Invalid API Key';
//            case static::STATUS_NOT_STARTED:
//                return pm_Locale::lmsg('taskPingError', ['id' => $this->getId()]);
        }
        return '';
    }

    public function onError(Exception $e)
    {
        $this->setParam('onError', 1);
    }
}
