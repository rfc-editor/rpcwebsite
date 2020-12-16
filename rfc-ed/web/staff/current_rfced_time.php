<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
/* November 2020 : Modified the script to use PDO prepared statements - PN                                 */

include('db_connect.php');
include('state_history_lib.php');

$debug_current = false;

/*********************************************************************************************
 1.Get the dates
 2.Returns the an array of dates for all the database values 
*********************************************************************************************/


$today=date("Y-m-d");
$month_beg = date("Y-m-01");
$run_month_full = date("F") ;
$run_year = date("Y");
$today_full = date("F j, Y");  


$html_header="<html><head><title>RFC Editor Time Current</title><meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859\"><link href=\"/staff/style/statistics.css\" rel=\"stylesheet\" type=\"text/css\" /></head><body>";


$html_body = "<body>";
$html_mainContent = get_body();
$html_footer = "</body></html>";

print "$html_header";
print "$html_mainContent";
print "$html_footer";

/*********************************************************************************************************/
/* Function : get_body()                                                                                 */
/*            This function gets the body for the report                                                 */
/*********************************************************************************************************/

function get_body(){

 global $today;
 global $month_beg;
 global $run_year;
 global $run_month_full;
 global $today_full;

 $rfced_data = "";
 $rfced_data = "";

 $body_text = "<h2 class=\"simple_center\">RFC Editor Time for the current month </h2>\n";
 $body_text .= "<h2 class=\"simple_center\"> as of $today_full</h2>\n";
 $rfced_time_count = get_rfced_data();
 $body_text .= "<h3 class=\"simple_left\">Total published: $rfced_time_count</h3>\n";
 $body_text .= "<table width=\"60%\" class=\"simple_left\"><tr><th>DOC-ID</th><th>Draft</th><th>Weeks in EDIT</th><th>Weeks in RFC-EDITOR</th><th>\"RFC-ED Time\"</th>"; 
 $body_text .= get_sorted_data();
 $body_text .= "</table>";
 drop_temp_table(); 
 return $body_text;
}

/**********************************************************************************************************/
/*Function : get_body_data 										  */
/*           Gets the RFCED time for the drafts Published in the last month				  */
/*	     RFCED time = EDIT+ RFC_EDITOR Time by a draft						  */
/**********************************************************************************************************/
function get_rfced_data(){
global $debug_current;
global $pdo;
global $today;
global $month_beg;


$print_data = "";
$rfced_array = array();
$rfced_query = "SELECT i.`DOC-ID`, i.`internal_key`, i.`DRAFT`, i.`state_id`, i.`PUB-date` from `index` i where  i.state_id = :state_id and i.`pub-date` BETWEEN :month_beg AND :today and i.`DOC-ID` LIKE :rfc ORDER BY `DOC-ID`";

try {
   $stmt = $pdo->prepare($rfced_query);
   $stmt->bindValue('state_id',14);
   $stmt->bindParam('month_beg',$month_beg);
   $stmt->bindParam('today',$today);
   $rfc = 'RFC%';
   $stmt->bindParam('rfc',$rfc);
   $stmt->execute();
   $num_of_rows = $stmt->rowCount();
} catch (PDOException $pe){
       error_log("Error processing : get_rfced_data", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
}


#print "$rfced_query\n";
if ( $num_of_rows > 0) {
   while ( $rfced_line = $stmt->fetch(PDO::FETCH_NUM)) {
       array_push($rfced_array, $rfced_line);
   }
}  


if ($debug_current === true) {
    print("<h4>data</h4><pre>");
    print_r($rfced_array); print("\n");
    print("</pre>");
    print "\n";
}


/*Create temp table to store Data*/
$temp_rfced_query = "CREATE TABLE temp_rfced_cur_data (
doc_id varchar(10),
draft varchar(200),
wks_in_edit decimal(10,1),
wks_in_editor decimal(10,1),
rfc_ed_time decimal(10,1))";

try {
   $create_stmt = $pdo->prepare($temp_rfced_query);
   $create_stmt->execute();
   if ($debug_current === true) {
       print("<h4>Table created</h4><pre>");
       print("</pre>");
       print "\n";
   }
} catch (PDOException $pe){
       error_log("Error processing : temp_rfced_cur_data", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
}

$rfced_count = count($rfced_array);
for ($row = 0 ; $row < $rfced_count; $row++){
   for ($column = 0; $column < 4; $column++){
       $doc_id = $rfced_array[$row][0];
       $internal_key = $rfced_array[$row][1];
       $draft_name = $rfced_array[$row][2];
       $pub_state_id = $rfced_array[$row][3];
       $pub_date = $rfced_array[$row][4];
   }

   $line_data = "";
   $edit_wks_in_state = 0.0;
   $editor_wks_in_state = 0.0;


   $params = [":internal_key" => $internal_key];
   $state_ids = [4,17];
   $in = "";
   foreach ($state_ids as $i => $item)
   {
       $key = ":state_id".$i;
       $in .= "$key,";
       $in_params[$key] = $item; // collecting values into key-value array
   }
   $in = rtrim($in,","); 

   $rfced_data_query = "SELECT DISTINCT(sh.internal_dockey), 
       sh.state_id,
       CONCAT( st.state_name,CASE WHEN sh.iana_flag = '1' THEN '*A'ELSE ''END ,CASE WHEN sh.ref_flag = '1'THEN '*R'ELSE ''END ) AS 'CURR_STATE',
       sh.in_date
       FROM `index` i, `state_history` sh , `states` st
       where i.internal_key = sh.internal_dockey 
       AND st.state_id = sh.state_id
       AND sh.internal_dockey = :internal_key
       AND sh.state_id IN ($in)
       order by sh.in_date";

   try {        
       $data_stmt = $pdo->prepare($rfced_data_query);
       $data_stmt->execute(array_merge($params,$in_params)); // just merge two arrays
       $rfced_num_of_rows = $data_stmt->rowCount();
   } catch (PDOException $pe){
         error_log("Error processing : rfced_data_query", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   $num_of_rows =  $rfced_num_of_rows;               
   if ($num_of_rows > 0) {

       while($rfced_data_line = $data_stmt->fetch(PDO::FETCH_NUM)){ 
           switch($rfced_data_line[1]){
               case '4':
                   $wks_in_state = get_weeks_in_state($rfced_data_line[2],$internal_key,$draft_name);
                   $edit_wks_in_state = $edit_wks_in_state +  $wks_in_state;
		   break;
               case '17':
                   $wks_in_state = get_weeks_in_state($rfced_data_line[2],$internal_key,$draft_name);
                   $editor_wks_in_state = $editor_wks_in_state + $wks_in_state;
                   break;
               default:
                   break;
           }
       }#End of while
 
   }# End of             if ($num_of_rows > 0) 

   $edit_wks_in_state = add_zero($edit_wks_in_state);
   $editor_wks_in_state = add_zero($editor_wks_in_state);
         
   $rfc_ed_time = $edit_wks_in_state + $editor_wks_in_state;
   $rfc_ed_time = add_zero($rfc_ed_time);


   $insert_query = "INSERT INTO `temp_rfced_cur_data` (doc_id,draft,wks_in_edit,wks_in_editor,rfc_ed_time) VALUES (:doc_id,:draft_name,:edit_wks_in_state,:editor_wks_in_state,:rfc_ed_time)";

   try {
       $insert_stmt = $pdo->prepare($insert_query);
       $insert_stmt->bindParam('doc_id',$doc_id);
       $insert_stmt->bindParam('draft_name',$draft_name);
       $insert_stmt->bindParam('edit_wks_in_state',$edit_wks_in_state);
       $insert_stmt->bindParam('editor_wks_in_state',$editor_wks_in_state);
       $insert_stmt->bindParam('rfc_ed_time',$rfc_ed_time);
       $insert_stmt->execute();
       $num_of_rows = $insert_stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : insert query", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
   if ($debug_current === true) {
       print("<h4>The query is</h4><pre>");
       print "<h4>Query :$insert_query</h4>";
       print("</pre>");
       print "\n";
   }

}#End of for ($row = 0 ; $row < $rfced_count; $row++){

#return $print_data;
return $rfced_count;

} /*End of function get_rfced_data*/

/**************************************************************************************************************/
/*function get_sorted_data gets the rfc-ed data in the rfc-ed time sorted format*/
/**************************************************************************************************************/
function get_sorted_data(){
global $debug_current;
global $pdo;
global $run_month;
global $run_year;

$print_data = "";
$line_data = "";

$rfced_sort_query = "SELECT `doc_id`, `draft`, `wks_in_edit`, `wks_in_editor`, `rfc_ed_time` from `temp_rfced_cur_data` ORDER BY `rfc_ed_time`";
try {
   $stmt = $pdo->query($rfced_sort_query);
   $num_of_rows = $stmt->rowCount();
} catch (PDOException $pe){
       error_log("Error processing : get_sorted_data", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
}
 
$num_of_sort = $num_of_rows;
if ($num_of_sort > 0) {
   while($rfced_sort_line = $stmt->fetch(PDO::FETCH_NUM)){ 
       $line_data = "<tr class=\"simple_left\"><td>$rfced_sort_line[0]</td><td>$rfced_sort_line[1]</td><td>$rfced_sort_line[2]</td><td>$rfced_sort_line[3]</td><td>$rfced_sort_line[4]</td></tr>";
       $print_data .= $line_data;
   }#End of while  
}#End of if ($num_of_rows > 0) {
return $print_data;
}#ENd of get_sorted_data function




/**************************************************************************************************************/
/*function drop_temp_table drops the temp table*/
/**************************************************************************************************************/
function  drop_temp_table(){
global $debug_current;
global $pdo;

$drop_query = "DROP TABLE `temp_rfced_cur_data`";
try {
   $pdo->exec($drop_query);
   if ($debug_current === true) {
       print("<h4>Table dropped</h4><pre>");
       print("</pre>");
       print "\n";
   }
} catch (PDOException $pe){
       error_log("Error processing : drop_temp_table", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
}

if ($debug_current === true) {
   print("<h4>The query is</h4><pre>");
   print "<h4>Query :$drop_query</h4>";
   print("</pre>");
   print "\n";
}

}

?>

