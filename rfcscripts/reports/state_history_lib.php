<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
# --------------------------------------------------------------------------- #
# These functions support the additional fields representing state history
# data
# --------------------------------------------------------------------------- #

function get_weeks_in_state($state,$internal_key,$draft_name){

$debug_shl = false;
$weeks_in_state = "";
#$data_array = get_weeks_state_array('3658');
$data_array = get_weeks_state_array($internal_key,$debug_shl);

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
        	}else if ($data_array[$row][$column] == $state){
		    $weeks_in_state = $data_array[$row][3];
		    break;		
		}

	}else {
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
# These functions support the additional fields representing state history
# data
# --------------------------------------------------------------------------- #

function get_weeks_state_array($internal_key,$debug_shl){
 
#   $query = "SELECT st.`state_name` , sh.`in_date`,sh.`version_number` FROM states st, state_history sh WHERE st.state_id = sh.state_id AND sh.internal_dockey = '$internal_key' ORDER BY in_date";


#Changed the query because now state look up table does bot hold the values for IANA flag and REF flag#

   $query = "SELECT st.`state_name` , sh.`iana_flag` , sh.`ref_flag` , sh.`in_date` , sh.`version_number`, sh.`generation_number` FROM states st, state_history sh WHERE st.state_id = sh.state_id AND sh.internal_dockey = '$internal_key' ORDER BY in_date";


   $db_link = open_db();
   $result = mysqli_query($db_link,$query);

#Define all the data structures
   $result_array = array();
   $date_array = array();
   $state_date_array = array();
   $temp_array = array();
   $flat_array = array();

   $num_of_rows = mysqli_num_rows($result);
   
   if ($num_of_rows > 0){

      while ($row = mysqli_fetch_row($result)){

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

                }
                else {  
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

    }
   else
   {
        return $state_date_array;
 
   }	
   
   close_db(); 
}
?>


