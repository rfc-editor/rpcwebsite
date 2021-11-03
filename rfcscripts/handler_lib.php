<?php
/*******************************************************************************************/
/* Copyright The IETF Trust 2020 All Rights Reserved                                       */
/*Script Name : handler_lib.php                                                            */
/*Description : The script gets the input values as display(rfc|bcp|std|fyi), then number  */
/*              XXXX and type as HTML                                                      */
/*              Gets the desired path for the corresponding file.                          */
/*January 2019 : Created the script - PN                                                   */
/*May 2019     : Added script to production under Publication format changes Phase I       */
/*               - PN                                                                      */
/*May 2019     : Modified the script to handle the case where document is not publised     */
/*              .html file does not exist - PN                                             */
/*February 2020: Modified the script added the case to query the index table based on the  */
/*               display type provided by the user - PN                                    */
/* November 2020 : Modified the script to use PDO prepared statements - PN                 */
/* June 2021 : Modified the script for server upgrade - PN                                 */
/*******************************************************************************************/

include('handler_config.php');
$debug_handler_lib= FALSE;

/***************************************************************************************/
/* get_subdirectory : Gets the exact location for the given file.                      */
/***************************************************************************************/
function get_subdirectory ($num,$display,$type){

global $debug_handler_lib;
global $info_doc_base;

$output_file;


   if ($debug_handler_lib){
       print "<br>Inside the get_subdirectory<br>";
       print "The test num is ".$num."<br>";
       print "The test display is ".$display."<br>";
       print "The test type is ".$type."<br>";
   }

   if ($display != 'rfc'){
       $destination_dir = $info_doc_base."/".$display."/";
   }else {
       $destination_dir = $info_doc_base."/";
   }


   if ($debug_handler_lib){
       print "Destination directory is".$destination_dir."<br>";
   }

//The final file depending upon the type entered
       $output_file = $destination_dir.$display.$num.'.html';

   if ($debug_handler_lib){
      print "The o/p file is".$output_file;
   }

   return $output_file;
}
/***************************************************************************************/
/* get_subdirectory : Check if the document is Published or not.                       */
/***************************************************************************************/
function check_doc_status ($in_num,$display,$type){
global $debug_handler_lib,$pdo;

   if ($debug_handler_lib){
       print "<br>Inside the check_doc_status<br>";
       print "The test num is ".$in_num."<br>";
       print "The test display is ".$display."<br>";
       print "The test type is ".$type."<br>";
   }

   $doc_id = sprintf("%s%04d",$display,$in_num);
   
   try{
   #$query = "SELECT `DOC-ID`,`DATE_RECEIVED`,`FORMAT`,`state_id` from `index` where `DOC-ID` ='$doc_id' and `state_id` = '14'";
       $query = "SELECT `DOC-ID`,`DATE_RECEIVED`,`FORMAT`,`state_id` from `index` where `state_id` = :state_id";
  


   /*Decide the query for searching the Published document based on the display value enrtetred by the user in the input*/
   /*Possible display values : bcp/std/fyi/ien/rfc */

       switch ($display) {
           case "bcp":
           case "std":
           case "fyi":
           case "ien":
                      if ($debug_handler_lib){
                         print "This is case $display";
                      }
                      $query .= " AND `SEE-ALSO` =:dod_id";
                      break;
           case  "rfc":
                      if ($debug_handler_lib){
                         print "This is case rfc $type";
                      }
                      $query .= " AND `DOC-ID`=:doc_id";
                      break;
           default:
                  break;

   }
       $stmt= $pdo->prepare($query);
       $stmt->bindValue('state_id',14);
       $stmt->bindParam('doc_id',$doc_id);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
     } catch (PDOException $pe){
           error_log("Error processing : check_doc_status", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));

     }

    
   if ($debug_handler_lib === TRUE) {
       print("<pre>\n");
       var_dump($query);
       print("</pre>\n");
   }

    if ($num_of_rows > 0){
       $row_count = $num_of_rows;
    }

    return($row_count);

}
?>
