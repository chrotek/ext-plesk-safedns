<?php
echo "------\n";

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
    echo "Listing Plesk Records for";echo $plesk_domain;echo "\n";

    $plesk_records_for_domain_x = (shell_exec('plesk bin dns --info '.$plesk_domain.'| grep -v "SUCCESS: Getting information for Domain"'));


//    echo "Type: ";
//    echo var_dump(gettype($plesk_records_for_domainx));
    $plesk_records_for_domain_y = explode("\n",$plesk_records_for_domain_x);
    echo var_dump(count($plesk_records_for_domain_y));
    echo "\n";
// for($i = 0; $i < 10000; ++$i) {}
    $ii=0;
    for($i = 0; $i < (count($plesk_records_for_domain_y)+1); ++$i){
        if (isset($plesk_records_for_domain_y[$i])) {
            if (!empty($plesk_records_for_domain_y[$i])) {
                echo "\n|||||||||||||||||||||||||||||||\n";
                $exploded_plesk_records_for_domain_y=explode(" ",$plesk_records_for_domain_y[$i]);
                echo $ii.": ";
                echo "Full Record: ".$plesk_records_for_domain_y[$i]."\n";
                echo "Elements in array: ";
                echo count($exploded_plesk_records_for_domain_y)."\n";
                echo "Exploded0 Name ; ";
                $plesk_record_name=$exploded_plesk_records_for_domain_y[0];
                echo $plesk_record_name;
                // SPF Records have spaces in the content, so we can't just break the record down based on spaces.
                // Instead, we'll remove elements from the array as we go, and then when just the content is left, we'll convert the array back to a string.
                unset($exploded_plesk_records_for_domain_y[0]);
                echo "\nExploded1 Type ; ";
                $plesk_record_type=$exploded_plesk_records_for_domain_y[1];
                unset($exploded_plesk_records_for_domain_y[1]);
                echo $plesk_record_type;
                if (strcmp($plesk_record_type, 'MX') == 0) {
                    echo "\n This is an MX Record";
                    $plesk_record_priority=$exploded_plesk_records_for_domain_y[2];
                    unset($exploded_plesk_records_for_domain_y[2]);                 
                }else {
                    $plesk_record_priority="NULL";
                }
                echo "\nPriority: ".$plesk_record_priority;
                // We should only have the content left in the array, so implode it.
                echo "\nContent ; ";
                $plesk_record_content=implode(" ",$exploded_plesk_records_for_domain_y);
                echo $plesk_record_content;
                // If we need an index, $ii can be used. $i is not valid anymore, because we've ignored the blank keys
                ++$ii;
            }
        }
    }
 
//    foreach 
    echo "\n------------------------\n------------------------\n------------------------\n";

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



get_plesk_domains();
foreach ($plesk_domains as $plesk_domain){
    get_plesk_records_for_domain($plesk_domain);
}







//   FOR DOMAINS IN PLESK

    //   FOR X IN RECORDS IN CURRENT DOMAIN

    //        ech var_dump($current_domain_arrayx);
//get_plesk_domains();
//echo var_dump($plesk_domains);
?>
