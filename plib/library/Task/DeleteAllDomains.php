<?php
   // Modules_SafednsPlesk_Task_SynchroniseDomains
//class Modules_SafednsPlesk_Task_Succeed extends pm_LongTask_Task
class Modules_SafednsPlesk_Task_DeleteAllDomains extends pm_LongTask_Task
{
    const UID = 'delete-all-domains';
    public $trackProgress = true;
    private $sleep = 1;
    private static $progressText = 'Progress is ';

    public function SafeDNS_API_Call($method, $url, $data){
        $curl = curl_init();
        switch ($method){
            case "POST":
                curl_setopt($curl, CURLOPT_POST, 1);
                if ($data)
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                    break;
            case "GET":
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
                if ($data)
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                    break;
            case "PATCH":
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PATCH");
                if ($data)
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                    break;
            case "DELETE":
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
                if ($data)
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                    break;
            default:
                if ($data)
                    $url = sprintf("%s?%s", $url, http_build_query($data));
        }
        // OPTIONS:
        $api_key=pm_Settings::get('api_key');
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Authorization: $api_key",
            'Content-Type: application/json',
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        // EXECUTE:
        $result = curl_exec($curl);
        $responsecode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if(strcasecmp($method, 'DELETE') == 0){
            if(strcasecmp($responsecode, '204') == 0){
                echo "Delete Successful, Response code ".$responsecode."\n";
            } else {
                echo "Issue deleting data. Response code ".$responsecode."\n";
            }
        } else {
            if(!$result){die("API Sent no Data back. Response code :".$responsecode."n");}
        }
        if(strcasecmp($responsecode, '200') != 0){
            if(strcasecmp($responsecode, '204') != 0){
                echo "\nResponse code : ".$responsecode."\n";
            }
        }
        // echo "Response code : ".$responsecode."n";
        // TODO - If response code not 200 , handle
        curl_close($curl);


        return $result;
    }

    public function safedns_write_log($log_msg)
    {
        $log_filename = "/var/log/plesk/ext-plesk-safedns";
        $log_timestamp= date("d-m-Y_H:i:s")
        // DEINFING LOG LINE
        if (!file_exists($log_filename)) 
        {
            // create directory/folder uploads.
            mkdir($log_filename, 0777, true);
        }
        $log_file_data = $log_filename.'/ext-plesk-safedns-' . date('d-M-Y') . '.log';
        // if you don't add `FILE_APPEND`, the file will be erased each time you add a log
        file_put_contents($log_file_data, $log_msg . "\n", FILE_APPEND);
    } 
    public function run()
    {
        $this->wh_log("this is my log message");
        $api_url="https://api.ukfast.io/safedns/v1";
        ob_start();
        pm_Settings::set('taskLock','locked');
        $enabledDomains = [];
        $domInfo = $this->getDomainInfo();
        $list = $domInfo->webspace->get->result;
        if ($list->status = 'ok') {
            // Create an array of domains that are enabled
            foreach ($list as $domain) {
                if (isset($domain->data->gen_info->name)) {
                    $plesk_domain=$domain->data->gen_info->name;

//                  If domain is enabled , add to enabled_domains array
                    $zoneSettingsX=pm_Settings::get('zoneSettings-'.$plesk_domain);
                    $zoneSettings=explode("|",$zoneSettingsX);
                    // If current domain has enabled set to true
                    if (strcmp($zoneSettings[0], 'True') == 0) {
                        $enabledDomains[] = (string)$plesk_domain;
                    }
                }
            }
            // Calculate how much % each action is worth. Set % to 0.
            $pleskDomainCount = count($enabledDomains);
            $actionPercent=(100/$pleskDomainCount);
            $currentPercent=0;
            // Iterate over enabled domains array
            foreach ($enabledDomains as $plesk_domain) {
                pm_Settings::set('taskCurrentDeleteDomain',$plesk_domain);
                $this->updateProgress($currentPercent);
                $currentPercent=($currentPercent+$actionPercent);
                echo "DEL ZONE API CALL : $api_url/zones/$plesk_domain,false";
                $this->SafeDNS_API_Call('DELETE',$api_url."/zones/".$plesk_domain,false);
                
            }
        $logfile='/testlog/safednsapi-tasks.log';
        $contents = ob_get_flush();
        file_put_contents($logfile,$contents);
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
