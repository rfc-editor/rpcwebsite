<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
# $Id: publishDraft.php,v 2.4 2020/11/11 01:03:17 priyanka Exp $
/* November 2020 : Modified the script to use PDO prepared statements - PN                                 */
$debug_pd = false;
session_start();
include('header.php');
include('db_connect.php');
include('auth48_lib.php');

rfc_header("publish draft");

function set_state_history($draft,$in_date){
   global $pdo;

   //Get the draft key
   $query = "SELECT internal_key FROM `index` WHERE draft=:draft";
   try {
       $stmt = $pdo->prepare($query);
       $stmt->bindParam('draft',$draft);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : send_state_history ", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
   $count = $num_of_rows;
   if ($count == 1) {
       $row = $stmt->fetch(PDO::FETCH_ASSOC);
       $internal_key = $row['internal_key'];
   }
   $version_number = substr($draft,-2); //Get the version number	

   //Insert into state_history table
   $insert = "insert into `state_history` (internal_dockey,state_id,in_date,version_number,iana_flag,ref_flag,generation_number) values (:internal_key,:state_id,:in_date,:version_number,:iana_flag,:ref_flag,:generation_number)";
   try {
       $stmt = $pdo->prepare($insert);
       $stmt->bindParam('internal_key',$internal_key);
       $stmt->bindValue('state_id','14');
       $stmt->bindParam('in_date',$in_date);
       $stmt->bindParam('version_number',$version_number);
       $stmt->bindValue('iana_flag','0');
       $stmt->bindValue('ref_flag','0');
       $stmt->bindValue('generation_number','0');
       $stmt->execute();
       $insert_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing :  send_state_history insert to state history", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
  
   if ($insert_rows > 0){
       $status = 1; 
   } else {
       $status = 0;    
   }		
   return $status;

}

if ($debug_pd === true) {
     print("<h4>_POST</h4>");
     print("<pre>");
     print_r($_POST);
     print("</pre>");
}
$draftName = $_POST['draftName'];
if($draftName != "" && $draftName != NULL) {
     $today = date("Y-m-d");      

  global $pdo;
/*update the STATE & PUB-DATE fieldS of INDEX table to PUB */
 $update_query = "UPDATE `index` SET `state_id`=:state_id,`ref_flag`=:ref_flag,`iana_flag`=:iana_flag,`generation_number`=:generation_number,`PUB-DATE`= :pub_date WHERE `DRAFT`= :draftName";

 try {
       $stmt = $pdo->prepare($update_query);
       $stmt->bindValue('state_id','14');
       $stmt->bindValue('ref_flag','0');
       $stmt->bindValue('iana_flag','0');
       $stmt->bindValue('generation_number','0');
       $stmt->bindParam('pub_date',$today);
       $stmt->bindValue('draftName',$draftName);
       $stmt->execute();
       $update_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing :  Update Query Publish Draft", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
 
   if ($debug_pd === true) {
       print("<h5>database update statement</h5>");
       print("\$update_query = "); var_dump($update_query); print("<br />");
   }
     
   if ($update_rows > 0){
       if (set_state_history($draftName,$today)){//Recording the state change in state history table
       } else {
	   print("\n <p class=\"error\">State History Update failed. See log for details.</p>\n");
       }
       $a48_id = get_a48_id_by_draft($draftName);
       if ($a48_id !== false) {
           $a48_status = update_status($a48_id, 'completed');
           if ($a48_status === false) {
               print("\n<p class=\"error\">Update of AUTH48 failed.  See log for details.</p>\n");
           }
       }
       if (!$debug_pd) {
           print("<script type=\"text/javascript\">\n");
           print("<!-- \n");
           print("window.close();\n");
           print("-->\n");
           print("</script>\n");
       }
   }
} else {
     print("<p class=\"error\">No draft given to update!</p>");
}
rfc_footer();
?>
