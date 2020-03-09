<?php
class Modules_SafednsPlesk_Task_DeleteADomain extends pm_LongTask_Task
{
    const UID = 'delete-a-domain';
    public $trackProgress = true;
    private $sleep = 3;
    private static $progressText = 'Progress is ';

    public function safedns_write_log($log_msg) {
        $log_filename = "/var/log/plesk/ext-plesk-safedns";
        $log_timestamp= date("d-m-Y_H:i:s");
        $log_prepend = $log_timestamp." | Delete A Domain | ";
        if (!file_exists($log_filename)) {
            // create directory/folder uploads.
            mkdir($log_filename, 0770, true);
        }
        $log_file_data = $log_filename.'/ext-plesk-safedns-' . date('d-M-Y') . '.log';
        // if you don't add `FILE_APPEND`, the file will be erased each time you add a log
        file_put_contents($log_file_data, $log_prepend . $log_msg . "\n", FILE_APPEND);
    }


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
                $this->safedns_write_log("Delete Successful, Response code ".$responsecode."\n");
            } elseif(strcasecmp($responsecode, '404') == 0){
                    $this->safedns_write_log("Zone does not exist on SafeDNS. ".$responsecode."\n");
            } else {
                $this->safedns_write_log("Issue deleting data. Response code ".$responsecode."\n");
            }
        } else {
            if(!$result) {
                $this->safedns_write_log("API Sent no Data back. Response code :".$responsecode."\n");
                die("API Sent no Data back. Response code :".$responsecode."n");
            }
        }
        curl_close($curl);
        return $result;
    }

    public function run()
    {
        $this->safedns_write_log("Starting Task - Delete A Zone");
        $plesk_domain=pm_Settings::get('selectedDomainDelete');
        $api_url="https://api.ukfast.io/safedns/v1";
        pm_Settings::set('taskLock','locked');
        pm_Settings::set('taskCurrentDeleteDomain',$plesk_domain);
        $this->updateProgress($currentPercent);
        $currentPercent=($currentPercent+$actionPercent);
        $this->safedns_write_log("Deleting Zone: ".$plesk_domain);
        $this->SafeDNS_API_Call('DELETE',$api_url."/zones/".$plesk_domain,false);
    }

    public function statusMessage()
    {
        $domainName=pm_Settings::get('selectedDomainDelete');
        pm_Log::info('Start method statusMessage. ID: ' . $this->getId() . ' with status: ' . $this->getStatus());
        switch ($this->getStatus()) {
            case static::STATUS_RUNNING:
                pm_Settings::set('taskLock','locked');
                return ("Running Task: Delete $domainName From SafeDNS");
            case static::STATUS_DONE:
                return ("Successful Task: Delete $domainName from SafeDNS");
            case static::STATUS_ERROR:
                return ("Task Error: Delete $domainName");
            case static::STATUS_NOT_STARTED:
                return ("Tasl Not Started: Delete $domainName");
        }
        return '';
    }

    public function onDone()
    {
        // Unlock the forms
        pm_Settings::set('taskLock',null);
    }
}
