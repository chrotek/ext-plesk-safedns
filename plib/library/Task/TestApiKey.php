<?php
   // Modules_SafednsPlesk_Task_SynchroniseDomains
//class Modules_SafednsPlesk_Task_Succeed extends pm_LongTask_Task
class Modules_SafednsPlesk_Task_TestApiKey extends pm_LongTask_Task
{
    const UID = 'test-api-key';
    public $trackProgress = true;
    private $sleep = 3;
    private static $progressText = 'Progress is ';
    //public $domainName=var_dump(pm_Settings::get('selectedDomainDelete'));

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


//        return $result;
        pm_Settings::set('apiKeyTestResponseCode',$responsecode);
//        return $responsecode;
    }

    public function run()
    {
        $api_url="https://api.ukfast.io/safedns/v1";
        ob_start();
        pm_Settings::set('taskLock','locked');
        echo "Testing API Key \n";
        $this->SafeDNS_API_Test('GET',$api_url."/zones?per_page=50",false);
        $apiTestResponse=pm_Settings::get('apiKeyTestResponseCode');

        if(strcasecmp($apiTestResponse, '401') == 0){
//            if(strcasecmp($responsecode, '204') != 0){
            echo "\nResponse code : ".$apiTestResponse."\n";
            pm_Settings::set('validKey',null);
            $logfile='/testlog/safednsapi-tasks.log';
            $contents = ob_get_flush();
            file_put_contents($logfile,$contents);
            throw new pm_Exception('API Key is not Valid. Response Code '.$apiTestResponse);
        }

        if(strcasecmp($apiTestResponse, '200') == 0){
            pm_Settings::set('validKey','true');
        } else {
//            if(strcasecmp($responsecode, '204') != 0){
            echo "\nResponse code : ".$apiTestResponse."\n";
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
//                return pm_Locale::lmsg('Syncronising All Domain');
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
