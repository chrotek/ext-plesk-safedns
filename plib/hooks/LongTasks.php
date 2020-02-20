<?php

//class Modules_SafednsPlesk_LongTasks extends pm_Hook_LongTasks
class Modules_SafednsPlesk_LongTasks extends pm_Hook_LongTasks
{
    public function getLongTasks()
    {
        pm_Log::info('getLongTasks.');
//        return [new Modules_SafednsPlesk_Task_Succeed(),
//            new Modules_SafednsPlesk_Task_Fail(),
//        ];
        return [new Modules_SafednsPlesk_Task_Succeed(),
            new Modules_SafednsPlesk_Task_Fail(),
            new Modules_SafednsPlesk_Task_TaskLocked(),
            new Modules_SafednsPlesk_Task_InvalidKey(),
            new Modules_SafednsPlesk_Task_SynchroniseAllDomains(),
            new Modules_SafednsPlesk_Task_SynchroniseADomain(), 
            new Modules_SafednsPlesk_Task_DeleteAllDomains(),
            new Modules_SafednsPlesk_Task_DeleteADomain(),
            new Modules_SafednsPlesk_Task_TestApiKey(),
        ];
    }
}
// Modules_SafeDNSAPIV1_LongTasks
