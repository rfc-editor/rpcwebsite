<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
$debug_display = false;
/***********************************************************************************************************/
/* Script name : display_adjust.php 
/* Description : This script holds the functions to get the display adjustment
/* November 2020 : Modified the script to use PDO prepared statements - PN                                 */
/***********************************************************************************************************/

function utf8_adjust($in_value){

   global $debug_display;

   $out_value = utf8_decode($in_value);

   if ($debug_display === TRUE) {
       print("\n<!-- debug_display value in_vale for utf8_adjust : $in_value and outvalue : $out_value\n");
   }

  return $out_value;

}

/**************************************************************************************/
/*Split rfc functions splits the given RFC/Subseries number in three parts            */
/*So input RFC number with RFCXXXX will be printed as RFC XXXX and 7                  */
/**************************************************************************************/
function split_rfc($in_value){
         global $debug_display;

         $length = strlen($in_value);

         if ($debug_display === TRUE) {
              print("\n<!-- debug_display value in_vale for split_rfc : $in_value \n");
          }


         if ($length == 9){
          $out_name = substr($in_value,0,'-6');
          $out_number = substr($in_value,'-5');
        }elseif ($length == 8){
         $out_name = substr($in_value,0,'-4');
         $out_number = substr($in_value,'-4');
        }elseif ($length == 7){
         $out_name = substr($in_value,0,'-4');
         $out_number = substr($in_value,'-4');
        }elseif ($length == 6){
         $out_name = substr($in_value,0,'-3');
         $out_number = substr($in_value,'-3');
        }elseif ($length == 5){
         $out_name = substr($in_value,0,'-2');
         $out_number = substr($in_value,'-2');
        }elseif ($length == 4){
         $out_name = substr($in_value,0,'-1');
         $out_number = substr($in_value,'-1');
        }

         if ($debug_display){
                 print "Out name is :".$out_name."\n";
             print "Out number is :".$out_number."\n";
             print "Length is :".$length."\n";
         }

         return array($out_name, $out_number, $length);
}


/**************************************************************************************/
/*Format rfc functions formats the given RFC/Subseries number in two parts              */
/*So input RFC number with RFCXXXX will be printed as RFC XXXX                        */
/**************************************************************************************/
function format_rfc($in_name, $in_number){
   
   $formatted_rfc_number = sprintf('%s %d',$in_name,$in_number);
        
    if ($debug_display === TRUE) {
              print("\n<!-- debug_display value in_vale for format_rfc: $in_name and $in_number and out value is $formatted_rfc_number \n");
    }

    return $formatted_rfc_number;
}

/****************************************************************************************************/
/*Function : check_PUBeditor_display()*
  This function shows if the auth48-done state has record with PUB editor                           */
/****************************************************************************************************/
function check_PUBeditor_display($state_id){

   global $debug_display;
   global $pdo;

   $rfc_PUBeditor_array = array();
   $rfc_PUBeditor_header_array = array();


   $rfc_PUBeditor_query = "SELECT i.`DOC-ID` FROM `index` i, `editor_assignments` e, `states` s WHERE i.`internal_key` = e.`doc_key`  AND i.`state_id` = s.`state_id` AND e.`role_key` = :role_key AND i.`state_id` = :state_id and e.`initials` != :initials";



   try {
       $stmt = $pdo->prepare($rfc_PUBeditor_query);
       $stmt->bindValue('role_key',4);
       $stmt->bindParam('state_id',$state_id);
       $stmt->bindValue('initials','XX');
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : display_adjust", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   # Pull up the query and just implode it into a table.
   while ( $rfc_PUBeditor_line = $stmt->fetch(PDO::FETCH_NUM)) {
       array_push($rfc_PUBeditor_array, $rfc_PUBeditor_line);
   }

   if ($debug_display === true) {
       print("display_adjust.php :: AUTH48-DONE RFC's list query :\n");
       print_r($rfc_PUBeditor_query); print("\n");
       print("\n");
   }

   foreach ($rfc_PUBeditor_array as $rfc_PUBeditor_line){
       array_push($rfc_PUBeditor_header_array,$rfc_PUBeditor_line[0]);
				  
   }
   return $rfc_PUBeditor_header_array;
}

?>
