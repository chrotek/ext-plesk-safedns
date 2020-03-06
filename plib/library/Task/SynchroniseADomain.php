<?php
   // Modules_SafednsPlesk_Task_SynchroniseDomains
//class Modules_SafednsPlesk_Task_Succeed extends pm_LongTask_Task
class Modules_SafednsPlesk_Task_SynchroniseADomain extends pm_LongTask_Task
{
    const UID = 'synchronise-a-domain';
    public $trackProgress = true;
    private $sleep = 3;
    private static $progressText = 'Progress is ';
    //public $domainName=var_dump(pm_Settings::get('selectedDomain'));


    public function safedns_write_log($log_msg) {
        $log_filename = "/var/log/plesk/ext-plesk-safedns";
        $log_timestamp= date("d-m-Y_H:i:s");
        $log_prepend = $log_timestamp." | Synchronise A Domain | ";
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

//    private function request_safedns_zones($api_url){
//        $get_data = $this->SafeDNS_API_Call('GET',$api_url."/zones?per_page=50",false);
//        $response = json_decode($get_data, true);
//        $data = $response;
//        $safedns_domains=array();
//        global $safedns_domains;
//    
//        $datax = explode(",",json_encode($data));
//    
//        foreach ($datax as $val) {
//            if (strpos($val, 'name') !== false){
//              $exploded=explode(":",$val);
//              $domainx=end($exploded);
//              $domain=str_replace('"','',$domainx);
//              $safedns_domains[] = $domain;
//          }
//      }
//      pm_Settings::set('safedns_all_domains_array',json_encode($safedns_domains));
//  }

    public function request_safedns_zones($api_url){
        $safedns_domains=array();
        global $plesk_domains;
        $plesk_domains=[];
        $plesk_all_domains=pm_Domain::getAllDomains();
        $plesk_domain_count=count($plesk_all_domains);
        echo "\nplesk_domain_count $plesk_domain_count \n";
        $safedns_zone_pages_count=($plesk_domain_count/50);
        $safedns_zone_pages_ceil=ceil($safedns_zone_pages_count);
        echo "\n Pages ;  $safedns_zone_pages_count\n";
        echo "\n CeilP ;  $safedns_zone_pages_ceil\n";
        foreach (range(1, $safedns_zone_pages_ceil) as $page_number) {
            echo "\nPage $page_number\n";
            $get_data = $this->SafeDNS_API_Call('GET',$api_url."/zones?per_page=50&page=".$page_number,false);
            $response = json_decode($get_data, true);
            $data = $response;
            global $safedns_domains;

            $datax = explode(",",json_encode($data));

            foreach ($datax as $val) {
                if (strpos($val, 'name') !== false){
                    $exploded=explode(":",$val);
                    $domainx=end($exploded);
                    $domain=str_replace('"','',$domainx);
                    $safedns_domains[] = $domain;

                }
            }
        }
//        print_r($safedns_domains);
        pm_Settings::set('safedns_all_domains_array',json_encode($safedns_domains));
    }

/*    
    public function request_safedns_record_for_zone($api_url,$zone_name){
        $get_data = $this->SafeDNS_API_Call('GET',$api_url."/zones/".$zone_name."/records?per_page=50",false);
        $response = json_decode($get_data, true);
        $data = $response;
        global $safedns_records_array;
        $safedns_records_array = array();
        foreach ($data['data'] as $val) {
        /* "ID : " .$val['id']."\n";
           "NAME : ".$val['name']."\n";
           "TYPE : ".$val['type']."\n";
           "CONTENT : ".$val['content']."\n";         *//*
            if(strcasecmp($val['type'], 'MX') == 0){
                array_push($safedns_records_array,$val['id'].",".$val['name'].",".$val['type'].",".$val['content'].",".$val['priority']);
            } else {
                array_push($safedns_records_array,$val['id'].",".$val['name'].",".$val['type'].",".$val['content']);
            }
        }
    //    return $safedns_records_array;
    
    }
*/

    public function request_safedns_record_for_zone($api_url,$zone_name){
        global $safedns_records_array;

    //    global $records_array;
        $safedns_records_array = array();
        // Calculate how many pages there will be. 50 records per page
        // TO DO - Do this with plesk's extensions API instead
        $plesk_records_for_domain_x = (shell_exec('plesk bin dns --info '.$zone_name.'| grep -v "SUCCESS: Getting information for Domain"'));
        $plesk_domain_records_array=[];
        $plesk_records_for_domain_y = explode("\n",$plesk_records_for_domain_x);
        $ii=0;
        for($i = 0; $i < (count($plesk_records_for_domain_y)+1); ++$i){
            if (isset($plesk_records_for_domain_y[$i])) {
                if (!empty($plesk_records_for_domain_y[$i])) {
                    ++$ii;
                }
            }
        }
        $pleskRecordCount=$ii;
        pm_Settings::set('syncDomain_pleskRecordCount',$pleskRecordCount);
        $safedns_records_pages_count=($pleskRecordCount/50);
        $safedns_records_pages_ceil=ceil($safedns_records_pages_count);
        foreach (range(1, $safedns_records_pages_ceil) as $page_number) {
    
        ////////////////////////////////
            $get_data = $this->SafeDNS_API_Call('GET',$api_url."/zones/".$zone_name."/records?per_page=50&page=".$page_number,false);
            $response = json_decode($get_data, true);
            $data = $response;
            foreach ($data['data'] as $val) {
        /* echo "ID : " .$val['id']."\n";
           echo "NAME : ".$val['name']."\n";
           echo "TYPE : ".$val['type']."\n";
           echo "CONTENT : ".$val['content']."\n";         */
                if(strcasecmp($val['type'], 'MX') == 0){
                    array_push($safedns_records_array,$val['id'].",".$val['name'].",".$val['type'].",".$val['content'].",".$val['priority']);
                } else {
                    array_push($safedns_records_array,$val['id'].",".$val['name'].",".$val['type'].",".$val['content']);
                }
            }
        }
    //    return $records_array;
    }


    public function check_create_zone($api_url,$safedns_domains,$input_zone){
        $safedns_domains=json_decode(pm_Settings::get('safedns_all_domains_array'));
        if (in_array($input_zone, $safedns_domains))
          {}
        else
          {
//          print_r($safedns_domains);
          $this->safedns_write_log("Creating Zone: ".$input_zone);
          // CREATE ZONE
          $postdata = array(
              'name' => $input_zone,
          );

          $this->SafeDNS_API_Call('POST',$api_url."/zones/", json_encode($postdata));
          }
    }
   
 
    public function create_record($api_url,$zone_name,$record_name,$record_type,$record_content,$record_priority){
        $this->safedns_write_log("Creating ".$record_type." Record: ".rtrim($record_name, ".")." with content ".$record_content." on zone ".$zone_name);    
        if(strcasecmp($record_type, 'MX') == 0){
            $postdata = array(
                'name' => rtrim($record_name, "."),
                'type' => $record_type,
                'content' => rtrim($record_content, "."),
                'priority' => $record_priority
                );
        } elseif(strcasecmp($record_type, 'TXT') == 0) {
            $postdata = array(
                'name' => rtrim($record_name, "."),
                'type' => $record_type,
                'content' => '"'.rtrim($record_content, ".").'"'
                );
        } else {
            $postdata = array(
                'name' => rtrim($record_name, "."),
                'type' => $record_type,
                'content' => rtrim($record_content, ".")
        );
        }
        $this->SafeDNS_API_Call('POST',$api_url."/zones/".$zone_name."/records", json_encode($postdata));
    }
    
    public function find_matching_record_safedns($api_url,$zone_name,$record_name,$record_type,$record_content,$record_opt,$safedns_records_array){
    // Check the record exists in zone exactly as specified. If yes return the Safedns ID Number and True, if No just return False
    //    echo "Checking if ".$record_type." Record: ".rtrim($record_name, ".")." EXISTS with content ".rtrim($record_content, ".")." on zone ".$zone_name."\n";
        $testResult = 'NoMatch';
        $recordID = 'Null';
        global $test_result_array;
        foreach ($safedns_records_array as $safedns_recordx) {
            $safedns_record=explode(",",$safedns_recordx);
            // 0 - ID , 1 - NAME , 2 - TYPE , 3 - CONTENT, 4 - OPT
    
                    // SAFEDNS API Doesn't support certain record types. Set result to IncompatibleType
            if (strcasecmp($record_type , 'PTR') == 0) {
                //echo "SAFEDNS API Doesn't support ".$safedns_record[2]." Records. Please contact support";
    //            echo "Incompatible Type!!!\n";
                $testResult = 'IncompatibleType';
                $recordID = $safedns_record[0];
                break;
                }
            // Find Match for Record Type
            if(strcasecmp($safedns_record[2], $record_type) == 0){
                if(strcasecmp(rtrim($safedns_record[1],"."), rtrim($record_name,".")) == 0){
                    // Record has matched Type and Name
                    $testResult = 'TypeNameMatch';
                    $recordID = $safedns_record[0];
                    // If TXT Record, add quotes for safedns reqs, and Find Match for Record Content
                    if(strcasecmp($safedns_record[2] , 'TXT') == 0){
                        if(strcasecmp(rtrim($safedns_record[3], ".") , '"'.rtrim($record_content, ".").'"') == 0){
                            $testResult = 'FullMatch';
                            $recordID = $safedns_record[0];
                            break;
                        }
                    }
                    // Else, Find Match for Record Content
                    if(strcasecmp(rtrim($safedns_record[3], ".") , rtrim($record_content, ".")) == 0){
                        // If MX Record, Also check priority
                        if(strcasecmp($safedns_record[2] , 'MX') == 0){
                            if(strcasecmp($safedns_record[4] , $record_opt) == 0){
                                $testResult = 'FullMatch';
                                $recordID = $safedns_record[0];
                                break;
                            }
                        // If record type doesn't need extra checks, FullMatch
                        } else {
                            $testResult = 'FullMatch';
                            $recordID = $safedns_record[0];
                            break;
    
                        }
                    }
    
                }
            }
        }
        $test_result_array=(array('testResult' => $testResult, 'recordID' => $recordID));
    }
    
    // Delete records from safedns, if deleted in plesk
    public function delete_plesk_missing_record_from_safedns($api_url,$zone_name,$pleskrecords,$safedns_records_array) {
            $safedns_records_arrayx=json_encode($safedns_records_array);
            if (strcasecmp($safedns_records_arrayx, 'NULL') == 0) {
    //            echo "Records Array DOESNT Exist! Retrieving.\n";
                global $safedns_records_array;
                $this->request_safedns_record_for_zone($api_url,$zone_name);
            }
            // For record in safedns records
            $deleted_record=False;
            foreach ($safedns_records_array as $safedns_recordx) {
                $safedns_record=explode(",",$safedns_recordx);
                $testresult= "NoMatch";
    //            echo " Matching Safedns Record: "; echo var_dump($safedns_record);
                // For record in plesk , if exists on SafeDNS, Match
                foreach ($pleskrecords as $plesk_domain_current_record_arrayx) {
                    $plesk_domain_current_record_array=explode(",",$plesk_domain_current_record_arrayx);
                    $plesk_record_name=rtrim($plesk_domain_current_record_array[0], ".");
                    $plesk_record_type=rtrim($plesk_domain_current_record_array[1], ".");
                    $plesk_record_priority=rtrim($plesk_domain_current_record_array[2], ".");
                    $plesk_record_content=rtrim($plesk_domain_current_record_array[3], ".");
                    if (strcasecmp($safedns_record[1], rtrim($plesk_record_name, ".")) == 0) {
                        if (strcasecmp($safedns_record[2], $plesk_record_type) == 0) {
                            $testresult= "NameTypeMatch";
                        }
                    }
                }
                if (strcasecmp($testresult, "NoMatch") == 0) {
    //           Leave NS and SOA Records alone. Delete anything else
                    if (strcasecmp($safedns_record[2], 'NS') == 0) {
                        //echo "Not deleting NS Record. Unsupported in safedns\n";
                    } elseif (strcasecmp($safedns_record[2], 'SOA') == 0) {
                        //echo "Not deleting SOA Record Unsupported in safedns\n";
                    } else {
                        //echo "Deleting ".$safedns_record[2]." Record ".$safedns_record[1]."from SafeDNS, It no longer exists in plesk:\n : id- ".$safedns_record[0]."zone- ".var_dump($zone_name)." name- ".$safedns_record[1]." type- ".$safedns_record[2]." content- ".$safedns_record[3]."\n";
                        $this->safedns_write_log("Deleting ".$safedns_record[2]." Record ".$safedns_record[1]." from SafeDNS, It no longer exists in plesk. ");

                        pm_Settings::set('recordsDeleted','true');
    
                        $this->SafeDNS_API_Call('DELETE',$api_url."/zones/".$zone_name."/records/".$safedns_record[0],false);
                          $deleted_record=True;
                    }
    
    
    
    
                }
    
            }
    }
    
    public function get_plesk_domains() {
        $plesk_domaindata_array=pm_Domain::getAllDomains();
    
    
        // Get List of domains in Plesk
        global $plesk_domains;
        $plesk_domains=[];
        foreach ($plesk_domaindata_array as $current_domainx){
            $current_domain=(array)$current_domainx;
            foreach ($current_domain as $current_domain_arrayx){
                $current_domain_array = array($current_domain_arrayx);
                $domain_name=$current_domain_arrayx->attr['name'];
                if (isset($domain_name)) {
                    $plesk_domains[]=$domain_name;
                }
            }
    
        }
    }
    
    
    public function get_plesk_records_for_domain($plesk_domain) {
        // TO DO - Do this with plesk's extensions API instead
    
        $plesk_records_for_domain_x = (shell_exec('plesk bin dns --info '.$plesk_domain.'| grep -v "SUCCESS: Getting information for Domain"'));
    
        global $plesk_domain_records_array;
        $plesk_domain_records_array=[];
        $plesk_records_for_domain_y = explode("\n",$plesk_records_for_domain_x);
        $ii=0;
        for($i = 0; $i < (count($plesk_records_for_domain_y)+1); ++$i){
            if (isset($plesk_records_for_domain_y[$i])) {
                if (!empty($plesk_records_for_domain_y[$i])) {
                    $exploded_plesk_records_for_domain_y=explode(" ",$plesk_records_for_domain_y[$i]);
                    $plesk_record_name=$exploded_plesk_records_for_domain_y[0];
                    unset($exploded_plesk_records_for_domain_y[0]);
                    $plesk_record_type=$exploded_plesk_records_for_domain_y[1];
                    unset($exploded_plesk_records_for_domain_y[1]);
                    if (strcmp($plesk_record_type, 'MX') == 0) {
                        $plesk_record_priority=$exploded_plesk_records_for_domain_y[2];
                        unset($exploded_plesk_records_for_domain_y[2]);
                    }else {
                        $plesk_record_priority="0";
                    }
                    // We should only have the content left in the array, so implode it.
                    $plesk_record_contentx=implode(" ",$exploded_plesk_records_for_domain_y);
                    // If we need an index, $ii can be used. $i is not valid anymore, because we've ignored the blank keys
                    ++$ii;
                    $plesk_record_content=ltrim($plesk_record_contentx," ");
                  $plesk_domain_records_array[]=($plesk_record_name.",".$plesk_record_type.",".$plesk_record_priority.",".$plesk_record_content);
                }
            }
        }
       
        pm_Settings::set('plesk_synchronise_all_domain_current_record_array',json_encode($plesk_domain_records_array));
    }


    public function run()
    {
        $plesk_domain=pm_Settings::get('selectedDomainSychronise');
        pm_Settings::set('taskLock','locked');
    
        $api_url="https://api.ukfast.io/safedns/v1";
    
        $this->request_safedns_zones($api_url);
        $safedns_domains=array();
        $safedns_records_array='NULL';
    
        $this->safedns_write_log("Synchronising $plesk_domain ");
        pm_Settings::set('taskCurrentDomain',$plesk_domain);
        pm_Settings::set('recordsChanged',null);
        pm_Settings::set('recordsDeleted',null);
        
        
//        $this->updateProgress($currentPercent);
//        $currentPercent=($currentPercent+$actionPercent);                     
        $this->check_create_zone($api_url,$safedns_domains,$plesk_domain);
        
        $safedns_records_arrayx=json_encode($safedns_records_array);
        $this->get_plesk_records_for_domain($plesk_domain);
        $plesk_domain_records_array=json_decode(pm_Settings::get('plesk_synchronise_all_domain_current_record_array'));

        global $safedns_records_array;
        $this->request_safedns_record_for_zone($api_url,$plesk_domain);
        $pleskRecordCount=pm_Settings::get('syncDomain_pleskRecordCount');
        $this->safedns_write_log("CountTest:$pleskRecordCount:");
        $actionPercent=(100/$pleskRecordCount);
        $currentPercent=0;

        foreach ($plesk_domain_records_array as $plesk_domain_current_record) {
            $plesk_domain_current_record_array=explode(",",$plesk_domain_current_record);
            $plesk_record_name=rtrim($plesk_domain_current_record_array[0], ".");
            $plesk_record_type=rtrim($plesk_domain_current_record_array[1], ".");
            $plesk_record_priority=rtrim($plesk_domain_current_record_array[2], ".");
            $plesk_record_content=rtrim($plesk_domain_current_record_array[3], ".");
            global $test_result_array;
            $this->find_matching_record_safedns($api_url,$plesk_domain,$plesk_record_name,$plesk_record_type,$plesk_record_content,$plesk_record_priority,$safedns_records_array);
            if (strcasecmp($test_result_array['testResult'], 'FullMatch') == 0) {
         //     - Check if record is present in safedns , but content has changed. (Match NAME and TYPE)
            } elseif (strcasecmp($test_result_array['testResult'], 'TypeNameMatch') == 0) {
                $this->safedns_write_log("Record already Exists, but content needs to be changed : id- ".$test_result_array['recordID']."zone- ".$plesk_domain." name- ".$plesk_record_name." type- ".$plesk_record_type." content- ".$plesk_record_content);
                if(strcasecmp($plesk_record_type , 'MX') == 0){
                $postdata = array(
                    'name' => $plesk_record_name,
                    'type' => $plesk_record_type,
                    'content' => $plesk_record_content,
                    'priority' => $plesk_record_priority);
                } else {
                $postdata = array(
                    'name' => $plesk_record_name,
                    'type' => $$plesk_record_type,
                    'content' => $plesk_record_content);
                }
                pm_Settings::set('recordsChanged','true');
                $this->SafeDNS_API_Call('PATCH',$api_url."/zones/".$plesk_domain."/records/".$test_result_array['recordID'], json_encode($postdata));
            } elseif (strcasecmp($test_result_array['testResult'], 'IncompatibleType') == 0) {
            } elseif (strcasecmp($test_result_array['testResult'], 'NoMatch') == 0) {
          // If no records match. Create the record
                $this->create_record($api_url,$plesk_domain,$plesk_record_name,$plesk_record_type,$plesk_record_content,$plesk_record_priority);
                  $record_changed=True;
                 pm_Settings::set('recordsChanged','true');
        
            } else {
                $this->safedns_write_log("ERROR. testResult was ".$test_result_array['testResult']." and the script doesn't know how to handle that");
            }
            $rrCount++;
            $currentPercent=($currentPercent+$actionPercent);
            //$this->safedns_write_log("CurrentPC :$currentPercent");
            //sleep(1);
            $this->updateProgress($currentPercent);
        }
        $this->delete_plesk_missing_record_from_safedns($api_url,$plesk_domain,$plesk_domain_records_array,$safedns_records_array);
         if (!pm_Settings::get('recordsChanged')) {
            if (!pm_Settings::get('recordsDeleted')) {
                $this->safedns_write_log("No Records Created, Update or Deleted");
            } else {
                $this->safedns_write_log("No Records Created or Updated");
            }
        }
        // Update Last Sync Time
        // Load the new setting from the next url parameter
        $timestamp = date("H:i:s d-m-Y");;

        // Retrieve Stored Settings Array for domain
        $zoneSettingsX=pm_Settings::get('zoneSettings-'.$plesk_domain);

        // Explode the array's stored data from string to array
        $zoneSettings=explode("|",$zoneSettingsX);

        // Create new Array with changed setting
        $newZoneSettingsX=array($zoneSettings[0],$timestamp,$zoneSettings[2]);

        // Implode the array with new data, from array to string
        $newZoneSettings=implode("|",$newZoneSettingsX);

        // Save the modified string to Plesk key value storage
        pm_Settings::set('zoneSettings-'.$plesk_domain,$newZoneSettings);

        }
 
    //}




    public function statusMessage()
    {
        $domainName=pm_Settings::get('selectedDomainSychronise');
        pm_Log::info('Start method statusMessage. ID: ' . $this->getId() . ' with status: ' . $this->getStatus());
        switch ($this->getStatus()) {
            case static::STATUS_RUNNING:
//                return pm_Locale::lmsg('Syncronising All Domain');
                pm_Settings::set('taskLock','locked');
                return ("Running Task: Sync $domainName With SafeDNS");
            case static::STATUS_DONE:
                return ("Successful Task: Sync $domainName with SafeDNS");
            case static::STATUS_ERROR:
                return ("Task Error: Sync $domainName with SafeDNS");
            case static::STATUS_NOT_STARTED:
                return ("Task Not Started: Sync $domainName with SafeDNS");
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
