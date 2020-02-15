<?php


/**
 * This is the SafeDNS Integration Script
 *
 * Documentation Link
 */

// Delete function branch

/**
 * Read zone script from stdin
 *
 *[
 * {
 *  "command": "(update|delete)",
 *  "zone": {
 *      "name": "domain.tld.",
 *      "displayName": "domain.tld.",
 *      "soa": {
 *          "email": "email@address",
 *          "status": 0,
 *          "type": "master",
 *          "ttl": 86400,
 *          "refresh": 10800,
 *          "retry": 3600,
 *          "expire": 604800,
 *          "minimum": 10800,
 *          "serial": 123123123,
 *          "serial_format": "UNIXTIMESTAMP"
 *      },
 *      "rr": [{
 *          "host": "www.domain.tld.",
 *          "displayHost": "www.domain.tld.",
 *          "type": "CNAME",
 *          "displayValue": "domain.tld.",
 *          "opt": "",
 *          "value": "domain.tld."
 *      }]
 * }, {
 *  "command": "(createPTRs|deletePTRs)",
 *  "ptr": {
 *      "ip_address": "1.2.3.4",
 *      "hostname": "domain.tld"}
 * }
 *]
 */

/*

$data = json_decode(file_get_contents('php://stdin'));
//Example:
//[
//    {"command": "update", "zone": {"name": "domain.tld.", "displayName": "domain.tld.", "soa": {"email": "amihailov@parallels.com", "status": 0, "type": "master", "ttl": 86400, "refresh": 10800, "retry": 3600, "expire": 604800, "minimum": 10800, "serial": 1363228965, "serial_format": "UNIXTIMESTAMP"}, "rr": [
//        {"host": "www.domain.tld.", "displayHost": "www.domain.tld.", "type": "CNAME", "displayValue": "domain.tld.", "opt": "", "value": "domain.tld."},
//        {"host": "1.2.3.4", "displayHost": "1.2.3.4", "type": "PTR", "displayValue": "domain.tld.", "opt": "24", "value": "domain.tld."},
//        {"host": "domain.tld.", "displayHost": "domain.tld.", "type": "TXT", "displayValue": "v=spf1 +a +mx -all", "opt": "", "value": "v=spf1 +a +mx -all"},
//        {"host": "ftp.domain.tld.", "displayHost": "ftp.domain.tld.", "type": "CNAME", "displayValue": "domain.tld.", "opt": "", "value": "domain.tld."},
//        {"host": "ipv4.domain.tld.", "displayHost": "ipv4.domain.tld.", "type": "A", "displayValue": "1.2.3.4", "opt": "", "value": "1.2.3.4"},
//        {"host": "mail.domain.tld.", "displayHost": "mail.domain.tld.", "type": "A", "displayValue": "1.2.3.4", "opt": "", "value": "1.2.3.4"},
//        {"host": "domain.tld.", "displayHost": "domain.tld.", "type": "MX", "displayValue": "mail.domain.tld.", "opt": "10", "value": "mail.domain.tld."},
//        {"host": "webmail.domain.tld.", "displayHost": "webmail.domain.tld.", "type": "A", "displayValue": "1.2.3.4", "opt": "", "value": "1.2.3.4"},
//        {"host": "domain.tld.", "displayHost": "domain.tld.", "type": "A", "displayValue": "1.2.3.4", "opt": "", "value": "1.2.3.4"},
//        {"host": "ns.domain.tld.", "displayHost": "ns.domain.tld.", "type": "A", "displayValue": "1.2.3.4", "opt": "", "value": "1.2.3.4"}
//    ]}},
//    {"command": "createPTRs", "ptr": {"ip_address": "1.2.3.4", "hostname": "domain.tld"}},
//    {"command": "createPTRs", "ptr": {"ip_address": "2002:5bcc:18fd:000c:0001:0002:0003:0004", "hostname": "domain.tld"}}
//]

/*

To do list


CURRENT LIMITS
- We can only handle 50 Domains, and 50 records per domain.
      To resolve this limitation, we need to write code to handle multiple pages in the safedns responses.
      This should go into the functions request_safedns_zones and request_safedns_record_for_zone , in leiu of the current url parameter "?per_page=50"

- Plesk's extensions API doesn't seem to support getting records for a zone. 
      Instead, we have to use shell_exec and the plesk bin command.
      If someone eading this does know how or manange to work it out, let me know!

- SOA Records aren't supported, because they usually aren't modifiable in safedns
      

*/

//fclose(STDOUT);
//fclose(STDERR);
//$STDOUT = fopen('safednsapi-plesk-application.log', 'wb');
//$STDERR = fopen('safednsapi-plesk-error.log', 'wb');
//$data = json_decode(file_get_contents('php://stdin'),true);
//$data = json_decode(file_get_contents('example-data.json'),true);

$api_url="https://api.ukfast.io/safedns/v1";
$safedns_domains=array();
$safedns_records_array='NULL';


function SafeDNS_API_Call($method, $url, $data){
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
   curl_setopt($curl, CURLOPT_URL, $url);
   curl_setopt($curl, CURLOPT_HTTPHEADER, array(
      'Authorization: oJAs5AIifPxZ28pTPSC8x2uRV2EPrTgO',
      'Content-Type: application/json',
   ));
   curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
   curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
   // EXECUTE:
   $result = curl_exec($curl);
   $responsecode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
   if(strcasecmp($method, 'DELETE') == 0){
       if(strcasecmp($responsecode, '204') == 0){
           echo "Delete Successful, Response code ".$responsecode;
       } else {
           echo "Issue deleting data. Response code ".$responsecode;
       }

   } else {
       if(!$result){die("API Sent no Data back. Response code :".$responsecode."\n");}
   }
   if(strcasecmp($responsecode, '200') != 0){
       echo "Response code : ".$responsecode."\n";
   }
//   echo "Response code : ".$responsecode."\n";
// TODO - If response code not 200 , handle
   curl_close($curl);


   return $result;
}




function request_safedns_zones($api_url){
    $get_data = SafeDNS_API_Call('GET',$api_url."/zones?per_page=50",false);
    $response = json_decode($get_data, true);
    $data = $response;
    $safedns_domains=array();
    global $safedns_domains;

    $datax = explode(",",json_encode($data));

    foreach ($datax as $val) {
        if (strpos($val, 'name') !== false){
            $exploded=explode(":",$val);
            $domainx=end($exploded);
            $domain=str_replace('"','',$domainx);
//            echo "Domain: ".$domain."\n";
            $safedns_domains[] = $domain;

        }
    }
    return $safedns_domains;
}

function request_safedns_record_for_zone($api_url,$zone_name){
    $get_data = SafeDNS_API_Call('GET',$api_url."/zones/".$zone_name."/records?per_page=50",false);
    $response = json_decode($get_data, true);
    $data = $response;
//    echo "request_safedns_record_for_zone $zone_name. \n Data: \n".$data;
    global $safedns_records_array;
    $safedns_records_array = array();
//    echo var_dump($data);
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
//    return $safedns_records_array;

}

function check_create_zone($api_url,$safedns_domains,$input_zone){

    if (in_array($input_zone, $safedns_domains))
      {}
    else
      {
      echo "Creating Zone: ".$input_zone."\n";
      // CREATE ZONE
      $postdata = array(
          'name' => $input_zone,
      );
      SafeDNS_API_Call('POST',$api_url."/zones/", json_encode($postdata));
      }
}

function create_record($api_url,$zone_name,$record_name,$record_type,$record_content,$record_priority){
    echo "Creating ".$record_type." Record: ".rtrim($record_name, ".")." with content ".$record_content." on zone ".$zone_name."\n";

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
//        'ttl' => $record_ttl
    );
    }
    SafeDNS_API_Call('POST',$api_url."/zones/".$zone_name."/records", json_encode($postdata));
}



function find_matching_record_safedns($api_url,$zone_name,$record_name,$record_type,$record_content,$record_opt,$safedns_records_array){
// Check the record exists in zone exactly as specified. If yes return the Safedns ID Number and True, if No just return False
//    echo "Checking if ".$record_type." Record: ".rtrim($record_name, ".")." EXISTS with content ".rtrim($record_content, ".")." on zone ".$zone_name."\n";
    $testResult = 'NoMatch';
    $recordID = 'Null';
    global $test_result_array;
//    echo "\n\nComparing Records\nPlesk\n";
//    echo "Plesk Domain: $plesk_domain \n Plesk Record Name: $record_name \n Plesk Record Type: $record_type \n Plesk Record Content: $record_content \n Plesk Record Priority: $plesk_record_opt\n\n";
//    echo " Safedns Array: "; echo var_dump($safedns_records_array)." \n ";

//    echo "ARRAY123".var_dump($safedns_records_array)."\n";
    foreach ($safedns_records_array as $safedns_recordx) {
        $safedns_record=explode(",",$safedns_recordx);
//        echo var_dump($safedns_record)."\n";
//        echo "SAFEDNSRecord123 \n";
//        echo var_dump($safedns_record)."\n";
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
//            echo "TYPE MATCHED\n";
//            echo "NameCheck\n";
//            echo $safedns_record[1]."\n";
//            echo $record_name."\n";
            // Find match for Record Name
            if(strcasecmp(rtrim($safedns_record[1],"."), rtrim($record_name,".")) == 0){
//                echo "NAME MATCHED\n";
                // Record has matched Type and Name
                $testResult = 'TypeNameMatch';
                $recordID = $safedns_record[0];
                // If TXT Record, add quotes for safedns reqs, and Find Match for Record Content
                if(strcasecmp($safedns_record[2] , 'TXT') == 0){
                    if(strcasecmp(rtrim($safedns_record[3], ".") , '"'.rtrim($record_content, ".").'"') == 0){
//                        echo "MX Matched";
                        $testResult = 'FullMatch';
                        $recordID = $safedns_record[0];
                        break;
                    }
                }
                // Else, Find Match for Record Content
//                echo "Looking for content match";
                if(strcasecmp(rtrim($safedns_record[3], ".") , rtrim($record_content, ".")) == 0){
                    // If MX Record, Also check priority
                    if(strcasecmp($safedns_record[2] , 'MX') == 0){
//                        echo "MX Check";
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
                    // Record has perfectly matched
                    //$testResult = 'FullMatch';
                    //$recordID = $safedns_record[0];
                }

            }
        }
    }
    $test_result_array=(array('testResult' => $testResult, 'recordID' => $recordID));
}


// Delete records from safedns, if deleted in plesk
function delete_plesk_missing_record_from_safedns($api_url,$zone_name,$pleskrecords,$safedns_records_array) {
        $safedns_records_arrayx=json_encode($safedns_records_array);
        if (strcasecmp($safedns_records_arrayx, 'NULL') == 0) {
//            echo "Records Array DOESNT Exist! Retrieving.\n";
            global $safedns_records_array;
            request_safedns_record_for_zone($api_url,$zone_name);
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
 //               echo "Delete CHECK NAME: ".$plesk_record_name."\n";
 //               echo "Delete CHECK TYPE: ".$plesk_record_type."\n";
 //               echo "Delete CHECK PRIORITY: ".$plesk_record_priority."\n";
 //               echo "Delete CHECK CONTENT: ".$plesk_record_content."\n";
                if (strcasecmp($safedns_record[1], rtrim($plesk_record_name, ".")) == 0) {
//                    echo "Delete function - Matched name";
                    if (strcasecmp($safedns_record[2], $plesk_record_type) == 0) {
//                        echo "Delete function - Matched type";
                        $testresult= "NameTypeMatch";
          //              break;
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
                    echo "Deleting Record from SafeDNS, It no longer exists in plesk:\n : id- ".$safedns_record[0]."zone- ".var_dump($zone_name)." name- ".$safedns_record[1]." type- ".$safedns_record[2]." content- ".$safedns_record[3]."\n";
                    SafeDNS_API_Call('DELETE',$api_url."/zones/".$zone_name."/records/".$safedns_record[0],false);
                    $deleted_record=True;
                }




            }
            // If No Match , Delete from SafeDNS

        }
        if (!$deleted_record) {
            echo "No Records Deleted\n";
        }
}




function delete_matched_record_safedns($api_url,$zone_name,$record_name,$record_type,$record_content,$safedns_records_array) {
    if (strcasecmp(var_dump($safedns_records_array), 'NULL') == 0) {
        echo "Records Array DOESNT Exist! Retrieving.\n";
        global $safedns_records_array;
        request_safedns_record_for_zone($api_url,$zone_name);
    }
    global $test_result_array;
    find_matching_record_safedns($api_url,$zone_name,$record_name,$record_type,$record_content,$safedns_records_array);

//    if ($test_result_array['testResult']) {
    if (strcasecmp($test_result_array['testResult'], 'FullMatch') == 0) {
        echo "Deleting Record from SafeDNS : id- ".$test_result_array['recordID']."zone- ".$zone_name." name- ".$record_name." type- ".$record_type." content- ".$record_content."\n";

        // DELETE the record
        SafeDNS_API_Call('DELETE',$api_url."/zones/".$zone_name."/records/".$test_result_array['recordID'],false);
    }


//    if (!$test_result_array['testResult']) {
    if (strcasecmp($test_result_array['testResult'], 'PartialMatch') == 0) {
        echo "Not deleting record from SafeDNS, as it doesn't fully match Plesk : zone- ".$zone_name." name- ".$record_name." type- ".$record_type." content- ".$record_content."\n";
    }
    if (strcasecmp($test_result_array['testResult'], 'NoMatch') == 0) {
        echo "Not deleting record from SafeDNS, as no fields matched : zone- ".$zone_name." name- ".$record_name." type- ".$record_type." content- ".$record_content."\n";
    }

}

//}




//*********************************************************************************************************

function get_plesk_domains() {
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
//    echo "------------------------------------------------------------------------------------ \n";
//    return $plesk_domains;
}

function get_plesk_records_for_domain($plesk_domain) {
    // TO DO - Do this with plesk's extensions API instead
 //   echo "Listing Plesk Records for";echo $plesk_domain;echo "\n";

    $plesk_records_for_domain_x = (shell_exec('plesk bin dns --info '.$plesk_domain.'| grep -v "SUCCESS: Getting information for Domain"'));
    global $plesk_domain_records_array;
    $plesk_domain_records_array=[];

//    echo "Type: ";
//    echo var_dump(gettype($plesk_records_for_domainx));
    $plesk_records_for_domain_y = explode("\n",$plesk_records_for_domain_x);
// for($i = 0; $i < 10000; ++$i) {}
    $ii=0;
    for($i = 0; $i < (count($plesk_records_for_domain_y)+1); ++$i){
        if (isset($plesk_records_for_domain_y[$i])) {
            if (!empty($plesk_records_for_domain_y[$i])) {
//                echo "\n|||||||||||||||||||||||||||||||\n";
                $exploded_plesk_records_for_domain_y=explode(" ",$plesk_records_for_domain_y[$i]);
//                echo $ii.": ";
//                echo "Full Record: ".$plesk_records_for_domain_y[$i]."\n";
//                echo "Elements in array: ";
//                echo count($exploded_plesk_records_for_domain_y)."\n";
//                echo "Exploded0 Name ; ";
                $plesk_record_name=$exploded_plesk_records_for_domain_y[0];
//                echo $plesk_record_name;
                // SPF Records have spaces in the content, so we can't just break the record down based on spaces.
                // Instead, we'll remove elements from the array as we go, and then when just the content is left, we'll convert the array back to a string.
                unset($exploded_plesk_records_for_domain_y[0]);
//                echo "\nExploded1 Type ; ";
                $plesk_record_type=$exploded_plesk_records_for_domain_y[1];
                unset($exploded_plesk_records_for_domain_y[1]);
//                echo $plesk_record_type;
                if (strcmp($plesk_record_type, 'MX') == 0) {
//                    echo "\n This is an MX Record";
                    $plesk_record_priority=$exploded_plesk_records_for_domain_y[2];
                    unset($exploded_plesk_records_for_domain_y[2]);                 
                }else {
                    $plesk_record_priority="0";
                }
//                echo "\nPriority: ".$plesk_record_priority;
                // We should only have the content left in the array, so implode it.
//                echo "\nContent ; ";
                $plesk_record_contentx=implode(" ",$exploded_plesk_records_for_domain_y);
//                echo $plesk_record_content;
                // If we need an index, $ii can be used. $i is not valid anymore, because we've ignored the blank keys
                ++$ii;
                $plesk_record_content=ltrim($plesk_record_contentx," ");
                $plesk_domain_records_array[]=($plesk_record_name.",".$plesk_record_type.",".$plesk_record_priority.",".$plesk_record_content);
            }
        }
    }
    return  $plesk_domain_records_array;
//    foreach 
//    echo "\n------------------------\n------------------------\n------------------------\n";

//    foreach ($plesk_records_for_domainx as $current_plesk_record) {
//        echo "RECORD:  ";
//        echo $current_plesk_record;
//    // <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
//    }


//    echo var_dump(array($plesk_records_for_domain));
//    echo "\n";


//    $plesk_records_for_domain = $plesk_records_for_domainx.split('/n');
//    foreach ($plesk_records_for_domainx as $current_plesk_record) {
//        echo "RECORD:  ";
//        echo $current_plesk_record;
//        echo "-------- \n";
//    }
//    var_dump(ob_get_clean());
//    echo var_dump($plesk_records_for_domain);
}

// AFTER FUNCTIONS ------------------------------------------------------------------------------------------

request_safedns_zones($api_url);
// For records in plesk zone, create/update in safedns:
get_plesk_domains();
foreach ($plesk_domains as $plesk_domain){
    echo "----- Syncronising zone $plesk_domain with safedns -----\n";
    check_create_zone($api_url,$safedns_domains,$plesk_domain);
    $record_changed=False;
    $rrCount=0;
    
    // For records in plesk zone, create/update in safedns:
    get_plesk_records_for_domain($plesk_domain);
//    echo "Domain= ".$plesk_domain."\n";

    $safedns_records_arrayx=json_encode($safedns_records_array);
//    if (strcasecmp($safedns_records_arrayx, 'NULL') == 0) {
//        echo "Records Array DOESNT Exist! Retrieving.\n";
//        global $safedns_records_array;
//        request_safedns_record_for_zone($api_url,$plesk_domain);
//    }
    global $safedns_records_array;
    request_safedns_record_for_zone($api_url,$plesk_domain);
//    echo "SAFEDNS RECORDS ARRAY\n$api_url\n";
//    echo var_dump($safedns_records_array);
    foreach ($plesk_domain_records_array as $plesk_domain_current_record) {
//        echo "Current Record: ".var_dump($plesk_domain_current_record);
        $plesk_domain_current_record_array=explode(",",$plesk_domain_current_record);
//        echo "Current Record Array: ";echo var_dump($plesk_domain_current_record_array);
        $plesk_record_name=rtrim($plesk_domain_current_record_array[0], ".");
        $plesk_record_type=rtrim($plesk_domain_current_record_array[1], ".");
        $plesk_record_priority=rtrim($plesk_domain_current_record_array[2], ".");
        $plesk_record_content=rtrim($plesk_domain_current_record_array[3], ".");

//        echo "Name: "; echo $plesk_record_name;
//        echo "\nType: "; echo $plesk_record_type;
//        echo "\nContent: "; echo $plesk_record_content;
//        echo "\nPriority: "; echo $plesk_record_priority;

        global $test_result_array;
   
//        echo "\n\nComparing Records\nPlesk\n";
//        echo "Plesk Domain: $plesk_domain \n Plesk Record Name: $plesk_record_name \n Plesk Record Type: $plesk_record_type \n Plesk Record Content: $plesk_record_content \n Plesk Record Priority: $plesk_record_priority\n\n";
//        echo " Safedns Array: "; echo var_dump($safedns_records_array)." \n ";
               



        global $test_result_array;
        find_matching_record_safedns($api_url,$plesk_domain,$plesk_record_name,$plesk_record_type,$plesk_record_content,$plesk_record_priority,$safedns_records_array);

//        echo "Test result is: ".var_dump($test_result_array);
        if (strcasecmp($test_result_array['testResult'], 'FullMatch') == 0) {
//            echo "Record already Exists, No changes nescessary : id- ".$test_result_array['recordID']."zone- ".$plesk_domain." name- ".$plesk_record_name." type- ".$plesk_record_type." content- ".$plesk_record_content."\n";
;
            //continue 2;

    //     - Check if record is present in safedns , but content has changed. (Match NAME and TYPE)
        } elseif (strcasecmp($test_result_array['testResult'], 'TypeNameMatch') == 0) {
            echo "Record already Exists, but content needs to be changed : id- ".$test_result_array['recordID']."zone- ".$plesk_domain." name- ".$plesk_record_name." type- ".$plesk_record_type." content- ".$plesk_record_content."\n";
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
            $record_changed=True;
//            echo "PATCHING RECORD WITH: \nURL:$api_url/zones/$plesk_domain/records/".$test_result_array['recordID'];
//            echo "\nPostData:\n"; echo var_dump($postdata);
            SafeDNS_API_Call('PATCH',$api_url."/zones/".$plesk_domain."/records/".$test_result_array['recordID'], json_encode($postdata));
            //continue 2;
        } elseif (strcasecmp($test_result_array['testResult'], 'IncompatibleType') == 0) {
//            echo "SAFEDNS API Doesn't support ".$variablerr['type']." Records.\n";
        } elseif (strcasecmp($test_result_array['testResult'], 'NoMatch') == 0) {
            // If no records match. Create the record
            create_record($api_url,$plesk_domain,$plesk_record_name,$plesk_record_type,$plesk_record_content,$plesk_record_priority);
            $record_changed=True;
        } else {
            echo "ERROR. testResult was ".$test_result_array['testResult']." and the script doesn't know how to handle that\n";
        }
        $rrCount++;
    //    echo "\n";
    }
    if (!$record_changed) {
        echo "No Records Created or Updated\n";
    }
    //     - For records in safedns, If record is not present in plesk, delete it from SafeDNS.
//    echo "Checking if anything needs to be deleted from safedns \n";
    delete_plesk_missing_record_from_safedns($api_url,$plesk_domain,$plesk_domain_records_array,$safedns_records_array);
    //     -


    //}
//    $plesk_domain_records_arrayx=explode(",",$plesk_domain_records_array);
//    echo var_dump($plesk_domain_records_arrayx);
//    echo "Name: ".var_dump($plesk_domain_records_arrayx[1])."\n";

}







//   FOR DOMAINS IN PLESK

    //   FOR X IN RECORDS IN CURRENT DOMAIN

    //        ech var_dump($current_domain_arrayx);
//get_plesk_domains();
//echo var_dump($plesk_domains);
?>
