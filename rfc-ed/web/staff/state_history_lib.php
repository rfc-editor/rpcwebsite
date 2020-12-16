<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
# --------------------------------------------------------------------------- #
# These functions support the additional fields representing state history
# data
/* November 2020 : Modified the script to use PDO prepared statements - PN    */
# --------------------------------------------------------------------------- #
$debug_shl = false;


# --------------------------------------------------------------------------- #
# function : get_weeks_in_state
#            This function takes state_name, intetnal key and draft name as 
#            required input data and get the weeks in state
# --------------------------------------------------------------------------- #

function get_weeks_in_state($state,$internal_key,$draft_name){

global $debug_shl;
$weeks_in_state = "";

if ($debug_shl === true) {
   
   print "<h4>**********Weeks in state calculation begins here********</h4>";
   print "Input values $state  $internal_key $draft_name "."<br>";
}

$data_array = get_weeks_state_array($internal_key);
$version = substr($draft_name,-2); //Get the version number	
#Check if version number is digit or not 
if(!(is_numeric($version))){
   $version_draft = substr($draft_name,0,-4); //Get the version number	
   $version = substr($version_draft,-2);
} 


if ($debug_shl === true) {
   print("<h4>Result Array</h4><pre>");
   print_r($data_array); print("\n");
   print ("</pre>");
}

$data_count = count($data_array);	

for ($row = 0; $row < $data_count ; $row++){
   for ($column = 0; $column < 5; $column++){
       if ($data_array[$row][4]){ //If version is specifies in state_history table
	   if (($data_array[$row][$column] == $state) AND ($data_array[$row][4] == $version )) {
	       $weeks_in_state = $data_array[$row][3];  
	       break;	
           } else if ($data_array[$row][$column] == $state){
	       $weeks_in_state = $data_array[$row][3];
	       break;		
           }
       } else {
	   if (($data_array[$row][$column] == $state)) {
	       $weeks_in_state = $data_array[$row][3];  
	       break;	
           }     
       }
   }
} 
if ($debug_shl === true) {
   print("<h4>Weeks in state</h4><pre>");
   print_r($weeks_in_state); print("\n");
   print "Passed version:$version\n";
   print ("</pre>");
}

return $weeks_in_state;

}


# --------------------------------------------------------------------------- #
# function : get_editor_weeks_in_state
#            This function takes state_name, intetnal key and draft name as 
#            required input data and calculates the weeks in state for editor
#            time ie EDIT and RFC-EDITOR state. 
# --------------------------------------------------------------------------- #

function get_editor_weeks_in_state($state,$internal_key,$draft_name,$id){

global $debug_shl;
$weeks_in_state = "";

if ($debug_shl === true) {
   print "<h4>**********Editor weeks in state calculation begins here********</h4>";
   print "Input values $state  $internal_key $draft_name $id"."<br>";
}

$data_array = get_editor_weeks_state_array($internal_key);
$version = substr($draft_name,-2); //Get the version number	
#Check if version number is digit or not 
if(!(is_numeric($version))){
   $version_draft = substr($draft_name,0,-4); //Get the version number	
   $version = substr($version_draft,-2);
} 

if ($debug_shl === true) {
   print("<h4>Result Array</h4><pre>");
   print_r($data_array); print("\n");
   print ("</pre>");
}

$data_count = count($data_array);	

for ($row = 0; $row < $data_count ; $row++){
   for ($column = 0; $column < 6; $column++){
       if ($data_array[$row][4]){ //If version is specifies in state_history table
	   if (($data_array[$row][6] == $id) AND ($data_array[$row][4] == $version )) {
	       $weeks_in_state = $data_array[$row][3];  
	       break;	
           } else if ($data_array[$row][6] == $id){
	       $weeks_in_state = $data_array[$row][3];
	       break;		
           }
       } else {
	   if (($data_array[$row][$column] == $state)) {
	       $weeks_in_state = $data_array[$row][3];  
	       break;	
           }     
       }
   }
} 

 if ($debug_shl === true) {
   print("<h4>Weeks in state</h4><pre>");
   print_r($weeks_in_state); print("\n");
   print "Passed version:$version\n";
   print ("</pre>");
}

return $weeks_in_state;

}


# --------------------------------------------------------------------------- #
# These function calculates the array used to get the editor weeks support    #
# data. It used the state_history table column "id" to identify the exact     #
# match                                                                       #     
# --------------------------------------------------------------------------- #

function get_editor_weeks_state_array($internal_key){

global $debug_shl; 
global $pdo;

if ($debug_shl === true) {
  print "Inside function get_editor_weeks_state_array  $internal_key"."<br>";
}

#Changed the query because now state look up table does bot hold the values for IANA flag and REF flag#
try {
   $query = "SELECT st.`state_name` , sh.`iana_flag` , sh.`ref_flag` , sh.`in_date` , sh.`version_number`, sh.`generation_number`, sh.`id` FROM states st, state_history sh WHERE st.state_id = sh.state_id AND sh.internal_dockey = :internal_key ORDER BY in_date";
  
   $stmt = $pdo->prepare($query);
   $stmt->bindParam('internal_key',$internal_key);
   $stmt->execute();
   } catch (PDOException $pe){
       error_log("Error processing : get_editor_weeks_state_array", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
if ($debug_shl === true) {
   print "My query is $query"."<br>";
}
  

#Define all the data structures
   $result_array = array();
   $date_array = array();
   $state_date_array = array();
   $temp_array = array();
   $flat_array = array();

   $num_of_rows = $stmt->rowCount();
   
   if ($num_of_rows > 0){
       while ($row = $stmt->fetch(PDO::FETCH_NUM)){
           array_push($date_array,$row[3]);
           array_push($result_array,$row);
       }
       $count = count($date_array);	
       for ($record = 0; $record < $count ; $record++){
           for ($column = 0; $column < 7; $column++){
#               print "Original array is".$result_array[$record][$column]."<br>";
               $flat_array .=  "#".$result_array[$record][$column];
           }
           $date_index = $record + 1;
#               print "Date array is".($date_array[$date_index])."<br>";
           if ($date_index == $count){
#               		$flat_array .= "#".$date_array[$date_index];
               $flat_array .= "#";
           } else {  
       	       $flat_array .= "#".$date_array[$date_index].":";
           }
       } 
       $array_pieces = explode(":",$flat_array);
       if ($debug_shl === true) {
  	   print("<h4>Original Arrays[Editor Time]</h4><pre>");
	   print_r($result_array); print("\n");
	   print_r($date_array); print("\n");
           print("<h4>Flat array[Editor Time]</h4>\n"); 
	   print_r($flat_array); print("\n");
	   print_r($array_pieces); print("\n");
	   print("</pre>");
       }
       $piece_count = count($array_pieces);
       for ($i = 0; $i < $piece_count; $i++){
#            list($extra, $state_name, $in_date, $version_number, $out_date) = explode("#", $array_pieces[$i]);
           list($extra, $state_name, $iana_flag, $ref_flag, $in_date, $version_number, $generation_number,$id,$out_date) = explode("#", $array_pieces[$i]);
	   if ($iana_flag){
	       $state_name = $state_name."*A";	
	   }	
           if ($ref_flag){
	       $state_name = $state_name."*R";
	   }
	   if ($out_date != ""){
	       $time_in_date = strtotime($in_date);
	       $time_out_date = strtotime($out_date);
               $interval = (abs($time_out_date - $time_in_date)/60/60/24);
               $interval = round(($interval/7),1);
                //Logic to add .0 in front of round number
               $str_interval = (string)$interval;
	       $pos = strpos($str_interval,'.');
	       if($pos === false) {
		   $interval = $str_interval.".0";	
	       }
               if ($debug_shl === true) {
                   print("<h4>Date Ranges and Interval[Editor time]</h4><pre>");
                   print "In date".$time_in_date."<br>";  
                   print "Out_date".$time_out_date."<br>";  
                   print "Interval".$interval."<br>";  
		   print "Version number".$version_number."<br>";  
                   print "</pre>"; 
                }
           }//This is the cuurent state which does not have a out date
           else{
	       $time_in_date = strtotime($in_date);
	       $out_date = Date('Y-m-d');
	       $time_out_date = strtotime($out_date);
               $interval = (abs($time_out_date - $time_in_date)/60/60/24);
               $interval = round(($interval/7),1);
               //Logic to add .0 in front of round number
               $str_interval = (string)$interval;
	       $pos = strpos($str_interval,'.');
	       if($pos === false) {
		   $interval = $str_interval.".0";	
	       }
               if ($debug_shl === true) {
                   print("<h4>Date Ranges and Interval</h4><pre>");
                   print "In date ".$time_in_date."<br>";  
                   print "Out_date ".$time_out_date."<br>";  
                   print "Interval ".$interval."<br>";  
                   print "</pre>"; 
               }            
           }
	   $temp_array = array($state_name,$in_date,$out_date,$interval,$version_number,$generation_number,$id);
           array_push($state_date_array,$temp_array);     
       }
       return $state_date_array;
   }
   else
   {
       return $state_date_array;
 
   }	
}

# --------------------------------------------------------------------------- #
# These functions support the additional fields representing state history
# data
# --------------------------------------------------------------------------- #

function get_weeks_state_array($internal_key){

global $debug_shl;
global $pdo; 

if ($debug_shl === true) {
  print "Inside function get_weeks_state_array  $internal_key"."<br>";
}

#Changed the query because now state look up table does bot hold the values for IANA flag and REF flag#
try {
   $query = "SELECT st.`state_name` , sh.`iana_flag` , sh.`ref_flag` , sh.`in_date` , sh.`version_number`, sh.`generation_number` FROM states st, state_history sh WHERE st.state_id = sh.state_id AND sh.internal_dockey = :internal_key ORDER BY in_date";
  
   $stmt = $pdo->prepare($query);
   $stmt->bindParam('internal_key',$internal_key);
   $stmt->execute();

  } catch (PDOException $pe){
       error_log("Error processing : get_weeks_state_array", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
  }

if ($debug_shl === true) {
   print "My query is $query"."<br>";
}

#Define all the data structures
$result_array = array();
$date_array = array();
$state_date_array = array();
$temp_array = array();
$flat_array = array();

$num_of_rows = $stmt->rowCount();
   
if ($num_of_rows > 0){
   while ($row = $stmt->fetch(PDO::FETCH_NUM)){
       array_push($date_array,$row[3]);
       array_push($result_array,$row);
   }
   $count = count($date_array);	
   for ($record = 0; $record < $count ; $record++){
       for ($column = 0; $column < 6; $column++){
#               print "Original array is".$result_array[$record][$column]."<br>";
           $flat_array .=  "#".$result_array[$record][$column];
       }
       $date_index = $record + 1;
#               print "Date array is".($date_array[$date_index])."<br>";
       if ($date_index == $count)
	   {
#               		$flat_array .= "#".$date_array[$date_index];
               $flat_array .= "#";
           }  else {  
       	       $flat_array .= "#".$date_array[$date_index].":";
           }
   } 
   $array_pieces = explode(":",$flat_array);
   if ($debug_shl === true) {
       print("<h4>Original Arrays</h4><pre>");
       print_r($result_array); print("\n");
       print_r($date_array); print("\n");
       print("<h4>Flat array</h4>\n"); 
       print_r($flat_array); print("\n");
       print_r($array_pieces); print("\n");
       print("</pre>");
   }
   $piece_count = count($array_pieces);
   for ($i = 0; $i < $piece_count; $i++){
#            list($extra, $state_name, $in_date, $version_number, $out_date) = explode("#", $array_pieces[$i]);
       list($extra, $state_name, $iana_flag, $ref_flag, $in_date, $version_number, $generation_number,$out_date) = explode("#", $array_pieces[$i]);
       if ($iana_flag){
           $state_name = $state_name."*A";	
       }	
       if ($ref_flag){
           $state_name = $state_name."*R";
       }
       if ($out_date != ""){
	   $time_in_date = strtotime($in_date);
	   $time_out_date = strtotime($out_date);
	   $interval = (abs($time_out_date - $time_in_date)/60/60/24);
	   $interval = round(($interval/7),1);

	   //Logic to add .0 in front of round number
	   $str_interval = (string)$interval;
	   $pos = strpos($str_interval,'.');
	   if($pos === false) {
	       $interval = $str_interval.".0";	
	   }
           if ($debug_shl === true) {
	       print("<h4>Date Ranges and Interval</h4><pre>");
	       print "In date".$time_in_date."<br>";  
	       print "Out_date".$time_out_date."<br>";  
	       print "Interval".$interval."<br>";  
	       print "Version number".$version_number."<br>";  
	       print "</pre>"; 
	   }
       }//This is the cuurent state which does not have a out date
          else{
	   $time_in_date = strtotime($in_date);
	   $out_date = Date('Y-m-d');
	   $time_out_date = strtotime($out_date);
	   $interval = (abs($time_out_date - $time_in_date)/60/60/24);
	   $interval = round(($interval/7),1);
	   //Logic to add .0 in front of round number
	   $str_interval = (string)$interval;
	   $pos = strpos($str_interval,'.');
	   if($pos === false) {
	       $interval = $str_interval.".0";	
	   }

	   if ($debug_shl === true) {
	       print("<h4>Date Ranges and Interval</h4><pre>");
	       print "Interval".$interval."<br>";  
	       print "</pre>"; 
	   }            
       }
       $temp_array = array($state_name,$in_date,$out_date,$interval,$version_number,$generation_number);
       array_push($state_date_array,$temp_array);     
   }
   return $state_date_array;
} else {
   return $state_date_array;
}	

}

# --------------------------------------------------------------------------- #
# function : get_rfced_time
#            This function in_key and draft_name and calculates the RET aka   #
#            editor time for that draft using weeks in state logic.            #
# --------------------------------------------------------------------------- #
function get_rfced_time($in_key,$draft_name){

global $debug_shl;
global $pdo;

$edit_wks_in_state = 0.0;
$editor_wks_in_state = 0.0;

/*   $in_key = $in_line[2];
   $draft_name = $in_line[5];
*/ 

$params = [":in_key" => $in_key];
$state_ids = [4,17];
$in = "";
foreach ($state_ids as $i => $item)
{
   $key = ":state_id".$i;
   $in .= "$key,";
   $in_params[$key] = $item; // collecting values into key-value array
}
$in = rtrim($in,",");

try { 
   $rfced_data_query = "SELECT (sh.internal_dockey), 
                        sh.state_id,
                        CONCAT( st.state_name,CASE WHEN sh.iana_flag = '1' THEN '*A'ELSE ''END ,CASE WHEN sh.ref_flag = '1'THEN '*R'ELSE ''END ) AS 'CURR_STATE',
                        sh.in_date, 
                        sh.id
                        FROM `index` i, `state_history` sh , `states` st
                        where i.internal_key = sh.internal_dockey 
                        AND st.state_id = sh.state_id
                        AND sh.internal_dockey = :in_key 
                        AND sh.state_id IN ($in)
                        order by sh.in_date";

   $stmt = $pdo->prepare($rfced_data_query);
   $stmt->execute(array_merge($params,$in_params)); // just merge two arrays
   $num_of_rows = $stmt->rowCount();
} catch (PDOException $pe){
   error_log("Error processing : get_rfced_time", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
}

if ($debug_shl === true) {
   print "The rfced time query is $rfced_data_query"."<br>";
}

if ($num_of_rows > 0) {
   while($rfced_data_line = $stmt->fetch(PDO::FETCH_NUM)){
       switch($rfced_data_line[1]){
           case '4':
               $wks_in_state = get_editor_weeks_in_state($rfced_data_line[2],$in_key,$draft_name,$rfced_data_line[4]);
               $edit_wks_in_state = $edit_wks_in_state +  $wks_in_state;
               break;
           case '17':
               $wks_in_state = get_editor_weeks_in_state($rfced_data_line[2],$in_key,$draft_name,$rfced_data_line[4]);
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

if ($debug_shl === true) {
   print "<h4>Editor times are as below</h4>";
   print "The edit weeks in state is $edit_wks_in_state"."<br>";
   print "The editor weeks in state  is $editor_wks_in_state"."<br>";
   print "The rfced time is $rfc_ed_time"."<br>";
}

$rfc_ed_time = add_zero($rfc_ed_time);

return $rfc_ed_time;
}

/**************************************************************************************************************/
/*function add_zero adds decimal if the number does not conatins a decimal point*/
/**************************************************************************************************************/

function add_zero($in_number){
$str_interval = (string)$in_number;
$pos = strpos($str_interval,'.');
if($pos === false) {
  $in_number = $str_interval.".0";
}
 return $in_number;
}




?>


