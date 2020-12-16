<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
/***********************************************************************************************************/
/*Script Name : current_discrepancies.php                                                                  */
/*Description : The script shows the documents present in the current queue with state id = "0"            */
/*Feb 2020    : Added script  -PN                                                                          */
/* November 2020 : Modified the script to use PDO prepared statements - PN                                 */
/***********************************************************************************************************/
session_start();
include('header.php');
include('db_connect.php');
$debug_desc = false;

rfc_header("current discrepancies");

$doc_id = "RFC"."%";


try {
   $query = 'SELECT `doc-id` , draft FROM `index` WHERE state_id = "0" AND `doc-id` LIKE "RFC%"';
   $stmt = $pdo->prepare($query);
   $stmt->bindValue('state_id',0);
   $stmt->bindParam('doc_id',$doc_id);
   $stmt->execute();
   }catch (PDOException $pe){
      error_log("Error processing : current discrepancies", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
}


//print "$query\n";

$num_of_rows = $stmt->rowCount();
# Pull up the query and just implode it into a table.
print "<p>Found " . $num_of_rows .  " records</p>\n";

if ($num_of_rows > 0){
   print "<h3>Discrepancies for review</h3>";
   while ( $line = $stmt->fetch(PDO::FETCH_NUM)) {
       print "<p>DOC ID : ". $line[0] . "&nbsp; &nbsp; DRAFT : ". $line[1] ."</p>";
   }
} else {
   print "<p>No records with state id 0</p>";
}


rfc_footer();

?>
