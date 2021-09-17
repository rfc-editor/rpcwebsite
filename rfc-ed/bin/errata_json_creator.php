#!/usr/bin/php
<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
/***************************************************************************************/
/*Script Name : errata_json_creator.php                                               */
/*Description : The script gets the desired field from the errata database table and   */
/*              creates the json file with the output using the json_encode function   */
/*December 2018 : Created the script - PN                                              */
/***************************************************************************************/
include('dbi.php');

$debug_element = false;

$json_data = get_data();
print $json_data;

/*************************************************************************/
/*Function : get_data gets the data from the errata table */
/*************************************************************************/

function get_data(){

global $debug_element;

    $query = 'SELECT e.`errata_id` , e.`doc-id` , `errata_status_code` , `errata_type_code` , `section` , `orig_text` , `correct_text` , e.`notes` , `submit_date` , `submitter_name` , `verifier_id` , `verifier_name` , `update_date` FROM `errata` e , `index` i ,`errata_type_codes` t , `errata_status_codes` s WHERE e.`doc-id` = i.`doc-id` AND e.type_id=t.errata_type_id AND e.status_id=s.errata_status_id';

    $db_link = open_db();

    if (($result = mysqli_query($db_link,$query))){

         $errata_data = array();
         while ( $line = mysqli_fetch_assoc($result)) {

                          $errata_data[] = $line;  
         }

    }    
    else { 
       rfc_fail("Query \"$query\" failed: " . mysqli_error($db_link));
     }

      /*Get the Numeric fields from the data as Numeric*/ 
       $json = json_encode($errata_data,JSON_NUMERIC_CHECK);
       if ($debug_element === true) {
            print "/******************The JSON DATA IS AS FOLLOWS ************************/\n\n";
           print $json;
        }
  
    mysqli_free_result($result);
    close_db();
    return $json;

}

?>


