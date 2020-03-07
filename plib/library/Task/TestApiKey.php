<?php
class Modules_SafednsPlesk_Task_TestApiKey extends pm_LongTask_Task
{
    const UID = 'test-api-key';
    public $trackProgress = true;
    private $sleep = 3;
    private static $progressText = 'Progress is ';
    public function safedns_write_log($log_msg) {
        $log_filename = "/var/log/plesk/ext-plesk-safedns";
        $log_timestamp= date("d-m-Y_H:i:s");
        $log_prepend = $log_timestamp." | ";
        if (!file_exists($log_filename)) {
            // create directory/folder uploads.
            mkdir($log_filename, 0770, true);
        }
        $log_file_data = $log_filename.'/ext-plesk-safedns-' . date('d-M-Y') . '.log';
        // if you don't add `FILE_APPEND`, the file will be erased each time you add a log
        file_put_contents($log_file_data, $log_prepend . $log_msg . "\n", FILE_APPEND);
    }

    public function SafeDNS_API_Test($method, $url, $data){
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
        curl_close($curl);
        pm_Settings::set('apiKeyTestResponseCode',$responsecode);
    }

    public function run()
    {
        $this->safedns_write_log("Starting Task - Sychronise all Zones");
        $api_url="https://api.ukfast.io/safedns/v1";
        pm_Settings::set('taskLock','locked');
        $this->safedns_write_log("Testing API Key");
        $this->SafeDNS_API_Test('GET',$api_url."/zones?per_page=50",false);
        $apiTestResponse=pm_Settings::get('apiKeyTestResponseCode');

        if(strcasecmp($apiTestResponse, '401') == 0){
//            if(strcasecmp($responsecode, '204') != 0){
            $this->safedns_write_log("\nResponse code : ".$apiTestResponse);
            pm_Settings::set('validKey',null);
            $logfile='/testlog/safednsapi-tasks.log';
            $contents = ob_get_flush();
            file_put_contents($logfile,$contents);
            $this->safedns_write_log("API Key is not Valid. Response Code ".$apiTestResponse);
            throw new pm_Exception('API Key is not Valid. Response Code '.$apiTestResponse);
        }

        if(strcasecmp($apiTestResponse, '200') == 0){
            pm_Settings::set('validKey','true');
        } else {
//            if(strcasecmp($responsecode, '204') != 0){
            $this->safedns_write_log("Response code : ".$apiTestResponse);
            $logfile='/testlog/safednsapi-tasks.log';
            $contents = ob_get_flush();
            file_put_contents($logfile,$contents);
            throw new pm_Exception('ERROR. API Response code '.$apiTestResponse);
        }
        
        $logfile='/testlog/safednsapi-tasks.log';
        $contents = ob_get_flush();
        file_put_contents($logfile,$contents);

    }



    public function statusMessage()
    {
        $domainName=pm_Settings::get('selectedDomainDelete');
        pm_Log::info('Start method statusMessage. ID: ' . $this->getId() . ' with status: ' . $this->getStatus());
        switch ($this->getStatus()) {
            case static::STATUS_RUNNING:
                pm_Settings::set('taskLock','locked');
                return ("Testing API Key");
            case static::STATUS_DONE:
                return ("API Key is valid");
            case static::STATUS_ERROR:
                pm_Settings::set('taskLock',null);
                return ("API Key Test Failed");
            case static::STATUS_NOT_STARTED:
                return ("Couldn't start API Key Test");
        }
        return '';
    }

    public function onDone()
    {
        // Unlock the forms
        pm_Settings::set('taskLock',null);
    }
}
