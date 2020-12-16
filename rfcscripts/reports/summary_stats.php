#!/usr/bin/php
<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
/*
 * Script generates the Current Queue Summary and sends email to assigned parties.
 * The email generated has the status Monthly or weekly depending upon the report type
 * specified from summary_stats.sh. The script runs through cron. 
 * January 2020 : Added the state TI to report order , state -order and removed states ISR, 
 *                ISR-AUTH and TO from state order  - PN
 * August 2020: Modified the To email address from 
                OLD: iad@ietf.org
                NEW: exec-director@ietf.org -PN
*/

include('db.php');
include('edit_lib.php');
include('state_history_lib.php');
include('reports_config.php');


// check for all required arguments
// first argument is always name of script!
if ($argc != 2) {
    die("Usage: summary_stats.php <report-type>\n");
}

// remove first argument
array_shift($argv);

// get and use remaining arguments
$report_type = $argv[0];


$debug760 = false;
$print_data = ""; 
$head_data = "";
$store_data = "";
$old_state = "";
$weeks_in_state = "";
global $db_link;

$head_data .= "Note: To view this report at any time, see ". $current_queue_path ."\n";
$head_data .= "Below is a snapshot.\n\n";

#$head_data = "===============================================================================\n";
$head_data .="   Current       Weeks in    Weeks          Draft name                                       Cluster    Pages     Submitted\n";
$head_data .="   state         state       in q\n\n";   
#$head_data .= "===============================================================================\n";

$report_order = array('EDIT','RFC-EDITOR','AUTH48','AUTH48-DONE','AUTH','IANA','IESG','REF','MISSREF','ISR','ISR-AUTH','TO','TI');


//Query format using the state look up table and iana_flag and ref_flag to get the CURR_STATE value

  	$query = 'SELECT CONCAT( s.state_name,CASE WHEN i.iana_flag = "1"THEN "*A"ELSE ""END ,CASE WHEN i.ref_flag = "1"THEN "*R"ELSE ""END ) AS "CURR_STATE", 
	i.internal_key, 
	ROUND( (TO_DAYS( CURDATE( ) ) - ( TO_DAYS( i.date_received ) ) ) /7, 1) AS "WEEKS_IN_Q", 
	i.draft AS "DRAFT_NAME", 
	i.`page-count` AS "CLUSTER", 
	i.`page-count` AS "PAGES", 
	i.date_received AS "SUBMITTED",
	i.generation_number,
        i.state_id
	FROM editor_assignments e, `index` i, `states` s
	WHERE i.draft IS NOT NULL
	AND e.role_key =1
	AND e.doc_key = i.internal_key
	AND i.state_id = s.state_id
	AND i.state_id != "14"
	AND i.state_id != "3"
	AND i.state_id != "19"
        ORDER BY generation_number, WEEKS_IN_Q DESC';

#print "$query\n";

$db_link = open_db();
# Pull up the query and just implode it into a table.
if ( ($result = mysqli_query($db_link,$query))) {
    $i=0;
    $result_array = array();
    $wks = array();
    $cl_array = array();
    # build result array
    while ( $line = mysqli_fetch_row($result)) {
        array_push($result_array, $line);
    }
    if ($debug760 === true) {
	print("Current Queue :\n");
	print_r($result_array); print("\n");
	print("\n");
    }

    $print_data = ""; 
    foreach ($report_order as $state) { //Need to maintain default report order if any sorting is not applied by the user
	foreach ($result_array as $line) {
            $pad = "";
            $pattern = "/^$state(\*A|\*R)*$/";
            if (preg_match($pattern, $line[0])) {

                 $internal_key = $line[1];
        #         $state_id = $line[8]; 
        #         $generation_number = $line[7];
        #         $input_date = $line[6];          
		# lookup cluster 
		$cid = get_cluster_id($line[3]);

                if ($cid != "0") {
                    $line[4] = $cid;
                } else {
                    $line[4] = "";
                }

		#Calculate the Weeks in state data from state history table depending upon the passed internal_key ,state name and draft value
		$line[1] = get_weeks_in_state($line[0],$line[1],$line[3]);
                $weeks_in_state = $line[1];
                       #Combine state name with generation number
                if ($line[7] == "1"){
		    $line[0] = $line[0]."(1G)";	
		}else if ($line[7] == "2"){
		    $line[0] = $line[0]."(2G)";	
                 }else if ($line[7] == "3"){
		    $line[0] = $line[0]."(3G)";	
                  }   
                # Strip the version and get the draft name 
 
                
                $state_data_pad = get_print_data_pad($line[0],15); //Get the padding length from function
                $print_data .= "    "."$line[0]"."$state_data_pad";
                
                  
                $wks_data_pad = get_print_data_pad($line[1],5); //Get the padding length from function
                $print_data .= "$wks_data_pad"."$line[1]";

                $wks_q_data_pad = get_print_data_pad($line[2],10); //Get the padding length from function
                $print_data .= "$wks_q_data_pad"."$line[2]"; 

                $draft_data_pad = get_print_data_pad($line[3],55); //Get the padding length from function
                $print_data .= "  "."$line[3]"."$draft_data_pad"; 


                $cluster_data_pad = get_print_data_pad($line[4],10); //Get the padding length from function
                $print_data .= "  "."$line[4]"."$cluster_data_pad"; 

                $pages_data_pad = get_print_data_pad($line[5],10); //Get the padding length from function
                $print_data .= "  "."$line[5]"."$pages_data_pad"; 

                $print_data .= "$line[6]"."\n"; 


                $report_date = date('Y-m-d');
                $week_of_the_year = date('W');
      


		$store_data_status = store_data($internal_key,$weeks_in_state,$report_date,$week_of_the_year);

        	 if ($store_data_status){ //insert statistics data  
	 	 } else {
                	 rfc_fail("Insert failed on Statistics data:" . mysqli_error($db_link));
           	   }
            }
	}
#	print "<tr></tr>\n";
    }//End of foreach report_order

$head_data .= $print_data;
$head_data .= "\n\n";
$head_data .= get_queue_summary($report_date);
$head_data .= "\n\n";
$head_data .= "####   Cluster Definitions   ####\n\n";
$head_data .= $all_clusters_path; 


if ($report_type == "w"){

	send_weekly_message($head_data);
}elseif ($report_type == "m"){

	send_monthly_message($head_data);

} 

#print $head_data;
}
else rfc_fail("Query \"$query\" failed: " . mysqli_error($db_link));



mysqli_free_result($result);
close_db();

/*##################################################################
#Stores the data in the database table#
###################################################################*/
function store_data($internal_key,$weeks_in_state,$report_date,$week_of_the_year){
   global $debug760;
   global $db_link;
    $select_stat = "SELECT * FROM `statistics` where `internal_key` = '$internal_key' and `weeks_in_state` = '$weeks_in_state' and `report_date` = '$report_date' and `week_of_year` = '$week_of_the_year'";

       $result_stat = mysqli_query($db_link,$select_stat);

       $num_stats = mysqli_num_rows($result_stat);


/* TESTING
    print "Key : $internal_key Weeks in state $weeks_in_state report date $report_date week_of_year:$week_of_the_year\n";
    print "Num stats : $num_stats\n";
     print "$select_stat";
*/
    if ($debug760 === true) {
	      print("Num Stats :\n");
	      print_r($num_stats); print("\n");
	      print("\n");
          }


    if ($num_stats != "0"){        
        $status = 1;
    }elseif ($num_stats == "0") 
    { 
             $insert_query = "INSERT INTO `statistics` (internal_key,weeks_in_state,report_date,week_of_year) VALUES ('$internal_key','$weeks_in_state','$report_date','$week_of_the_year') ";
  

          if ($debug760 === true) {
	      print("store_data query :\n");
	      print_r($insert_query); print("\n");
	      print("\n");
          }

          if (mysqli_query($db_link,$insert_query)){
              $status = 1; 
          } else{
              $status = 0;    
          }	
    }	
    return $status;
}


/*##################################################################
Gets the queue summary
###################################################################*/
function get_queue_summary($report_date){

   global $debug760;

   global $CurrQstats;

   global $db_link;

   $queue_summary = "";
   $queue_summary = "#### RFC Editor Queue Summary:  ".date('Y-m-d')." ####\n\n";

   $queue_summary .= "    State             total #       total #      Median Wks    Average Wks\n";
   $queue_summary .= "                      docs          pages        in state      in state\n"; 


   $state_order = array('EDIT','RFC-EDITOR','AUTH48','AUTH48-DONE','AUTH','IANA','IESG','REF','MISSREF','TI');

   $state_array = array();

  
   $total_st_docs = 0;
   $total_st_pages = 0;

   $total_g_docs = 0;
   $total_g_pages = 0;

   $g1_count = 0;
   $g2_count = 0;
   $g3_count = 0;

   $state_query ='Select `state_id`, `state_name` from states';
    open_db();
# Pull up the query and just implode it into a table.
   if ( ($state_result = mysqli_query($db_link,$state_query))) {
        while ( $state_line = mysqli_fetch_row($state_result)) {
            array_push($state_array, $state_line);
   }

   if ($debug760 === true) {
	print("get_queue_summary:: States query :\n");
	print_r($state_array); print("\n");
	print("\n");
   }

#    print "Doc count    Page Count\n";
#        $queue_summary = "Doc count    Page Count\n";

        foreach ($state_order as $state) { //Need to maintain default report order 
	    foreach ($state_array as $state_line) {
                $pattern = "/^$state(\*A|\*R)*$/";
                if (preg_match($pattern, $state_line[1])) {

                    $st = $state_line[1];
                    $st_id = $state_line[0];
                    $stat_data_array = array();
                    $g1_array = array();
                    $g2_array = array();
                    $g3_array = array();

                    $stat_page_count = 0;
                    $g1_page_count = 0;
                    $g2_page_count = 0;
                    $g3_page_count = 0;

                          
                    $stats_query = "select i.internal_key, s.state_name, i.`generation_number`,s.state_id, i.`PAGE-COUNT`, st.`weeks_in_state`, st.`report_date`, st.`week_of_year`  from `index` i, `states` s , `statistics` st where i.state_id = s.state_id and i.internal_key = st.internal_key and i.`state_id` = '$st_id' and st.`report_date` = '$report_date'";


                   
                    if (($stats_result = mysqli_query($db_link,$stats_query))){
                         $num_of_rows = mysqli_num_rows($stats_result); 
                         if ($num_of_rows > 0){
	                 	while ($stats_line = mysqli_fetch_row($stats_result)){
	                           switch($st_id){
		                           case '22':                                         
                              	                if ($stats_line[2] == '1'){
                                	           array_push($g1_array,$stats_line[5]); 
                                        	   $g1_page_count = $g1_page_count + $stats_line[4];
	                                        }
        	                                elseif ($stats_line[2] == '2'){
                	                           array_push($g2_array,$stats_line[5]); 
                        	                   $g2_page_count = $g2_page_count + $stats_line[4];
                                	        }elseif ($stats_line[2] == '3') {  
                                        	   array_push($g3_array,$stats_line[5]); 
	                                           $g3_page_count = $g3_page_count + $stats_line[4];
        	                                 }
        		                        break;
                        	           default:
	                                         array_push($stat_data_array,$stats_line[5]);
        	                                 $stat_page_count = $stat_page_count + $stats_line[4];     
        		                         break;
                        	   }//End switch  
	                         }//End while
                         }//End if num_of_rows   
                    }//End stats_result query
                    else  { rfc_fail("Status Query \"$stats_query\" failed: " . mysqli_error($db_link)); }
                }//End if preg_match
            }//End 	foreach ($state_array as $state_line) {
/******************************************************************************************/

	        if ($st_id != '22') {
                    if ($num_of_rows > 0){   

                        $doc_count = count($stat_data_array);
                        $avg_stat_wks = calculate_average($stat_data_array,$doc_count);
                        $med_stat_wks = calculate_median($stat_data_array,$doc_count);

                     }else 
                      {
                        
                        $doc_count = 0;  
                        $avg_stat_wks = "0.0";
                        $med_stat_wks = "0.0";
                      } 
                        
                        $med_wks_data_pad = get_print_data_pad($med_stat_wks,15);
                        $med_wks_data =  "$med_wks_data_pad"."$med_stat_wks";  

                        $avg_wks_data_pad = get_print_data_pad($avg_stat_wks,15);
                        $avg_wks_data =  "$avg_wks_data_pad"."$avg_stat_wks";  
                          
			$state_data_pad = get_print_data_pad($st,15); //Get the padding length from function
                	$state_data = "    "."$st"."$state_data_pad";
                        

                        $doc_data_pad = get_print_data_pad($doc_count,10);
                        $doc_data =  "$doc_data_pad"."$doc_count";  

                        $page_data_pad = get_print_data_pad($stat_page_count,15);
                        $page_data =  "$page_data_pad"."$stat_page_count";  


			$queue_summary .= "$state_data"."$doc_data"."$page_data"."$med_wks_data$avg_wks_data\n";

		        $total_st_docs = $total_st_docs + $doc_count;
                        $total_st_pages = $total_st_pages + $stat_page_count;  

                        if ($debug760 === true) {
      	                    print("Weeks in state for other states : $st_id\n");
	                    print_r($stat_data_array); print("\n");
	                    print("\n");
                        }

	        }else {
                    if ($num_of_rows > 0){
                     
	               $g1_count = count($g1_array);   
                       $avg_g1_wks = calculate_average($g1_array,$g1_count);
                       $med_g1_wks = calculate_median($g1_array,$g1_count);

	               $g2_count = count($g2_array);
                       $avg_g2_wks = calculate_average($g2_array,$g2_count);
                       $med_g2_wks = calculate_median($g2_array,$g2_count);

        	       $g3_count = count($g3_array);  
                       $avg_g3_wks = calculate_average($g3_array,$g3_count);
                       $med_g3_wks = calculate_median($g3_array,$g3_count);
                    
                    }else{  
                       $avg_g1_wks = 0;
                       $med_g1_wks = 0;

	               $avg_g2_wks = 0;
                       $med_g2_wks = 0;

        	       $avg_g3_wks = 0;
                       $med_g3_wks = 0;
                     }  


                        $st_1 = $st."(1G)";   
			$state_data_pad = get_print_data_pad($st_1,15); //Get the padding length from function
                	$state_data1 = "    "."$st_1"."$state_data_pad";

                        $doc1_data_pad = get_print_data_pad($g1_count,10);
                        $g1_doc_data =  "$doc1_data_pad"."$g1_count";  

                        $page1_data_pad = get_print_data_pad($g1_page_count,15);
                        $page1_data =  "$page1_data_pad"."$g1_page_count";  

                        $med_g1_wks_data_pad = get_print_data_pad($med_g1_wks,15);
                        $med_g1_wks_data =  "$med_g1_wks_data_pad"."$med_g1_wks";  

                        $avg_g1_wks_data_pad = get_print_data_pad($avg_g1_wks,15);
                        $avg_g1_wks_data =  "$avg_g1_wks_data_pad"."$avg_g1_wks";  

			$queue_summary .= "$state_data1"."$g1_doc_data"."$page1_data"."$med_g1_wks_data$avg_g1_wks_data\n";


                       if ($debug760 === true) {
      	                    print("Weeks in state for MISSREF 1G :\n");
	                    print_r($g1_array); print("\n");
	                    print("\n");
                       }

                        $st_2 = $st."(2G)";   
			$state_data_pad = get_print_data_pad($st_2,15); //Get the padding length from function
                	$state_data2 = "    "."$st_2"."$state_data_pad";

                        $doc2_data_pad = get_print_data_pad($g2_count,10);
                        $g2_doc_data =  "$doc2_data_pad"."$g2_count";  

                        $page2_data_pad = get_print_data_pad($g2_page_count,15);
                        $page2_data =  "$page2_data_pad"."$g2_page_count";  

                        $med_g2_wks_data_pad = get_print_data_pad($med_g2_wks,15);
                        $med_g2_wks_data =  "$med_g2_wks_data_pad"."$med_g2_wks";  

                        $avg_g2_wks_data_pad = get_print_data_pad($avg_g2_wks,15);
                        $avg_g2_wks_data =  "$avg_g2_wks_data_pad"."$avg_g2_wks";  

			$queue_summary .= "$state_data2"."$g2_doc_data"."$page2_data"."$med_g2_wks_data$avg_g2_wks_data\n";

                       if ($debug760 === true) {
      	                    print("Weeks in state for MISSREF 2G :\n");
	                    print_r($g2_array); print("\n");
	                    print("\n");
                       }

                        $st_3 = $st."(3G)";   
			$state_data_pad = get_print_data_pad($st_3,15); //Get the padding length from function
                	$state_data3 = "    "."$st_3"."$state_data_pad";

                        $doc3_data_pad = get_print_data_pad($g3_count,10);
                        $g3_doc_data =  "$doc3_data_pad"."$g3_count";  

                        $page3_data_pad = get_print_data_pad($g3_page_count,15);
                        $page3_data =  "$page3_data_pad"."$g3_page_count";  

                        $med_g3_wks_data_pad = get_print_data_pad($med_g3_wks,15);
                        $med_g3_wks_data =  "$med_g3_wks_data_pad"."$med_g3_wks";  

                        $avg_g3_wks_data_pad = get_print_data_pad($avg_g3_wks,15);
                        $avg_g3_wks_data =  "$avg_g3_wks_data_pad"."$avg_g3_wks";  

			$queue_summary .= "$state_data3"."$g3_doc_data"."$page3_data"."$med_g3_wks_data$avg_g3_wks_data\n";

                       if ($debug760 === true) {
      	                    print("Weeks in state for MISSREF 3G :\n");
	                    print_r($g3_array); print("\n");
	                    print("\n");
                       }

#                       $queue_summary .= "\n\n";
                      $total_g_docs = $total_g_docs + $g1_count + $g2_count + $g3_count;
                      $total_g_pages = $total_g_pages + $g1_page_count + $g2_page_count + $g3_page_count;  

               } //$st_id == 22

               $total_docs = $total_st_docs + $total_g_docs;
               $total_pages = $total_st_pages + $total_g_pages;  

/******************************************************************************************/


        }//End foreach ($state_order as $state)
#    print $queue_summary;
   $queue_summary .= "\n\n";

   $queue_summary .= "Totals:    $total_docs docs    $total_pages pages\n";   



  global $CurrQstats;
  $Handle = fopen($CurrQstats, 'w');



   fwrite($Handle, $queue_summary); 
   fclose($Handle); 

   return $queue_summary;

} //End if
else { rfc_fail("State Query \"$state_query\" failed: " . mysqli_error($db_link));}
#   return $queue_summary;
}



function add_interval($in_val){
         //Logic to add .0 in front of round number
           $str_inval = (string)$in_val;
           $pos = strpos($str_inval,'.');
	   if($pos === false) {
	      $str_inval = $str_inval.".0";	
	   }
           return $str_inval;

}
/*##########################################################
# This function calculates the average value for given array 
  array count 
###########################################################*/

function calculate_average($in_arr,$in_count){
         $arr_sum = array_sum($in_arr);
         if ($in_count != 0){   
	         $avg =  ($arr_sum / $in_count); 	
        	 $avg = round($avg,1);
	 }else{
             $avg = 0;
	 }
         $avg = add_interval($avg);
         return $avg;
}
/*##########################################################
# This function calculates the median value for given array 
  array count 
###########################################################*/

function calculate_median($in_arr,$in_count){
        global $debug760;
        
        if ($in_count > 1){
	        sort($in_arr);
	        if ($debug760 === true) {
	 	    print("Calculate Median :\n");
		    print_r($in_arr); print("\n");
		    print("\n");
        	}

	        $middleval = floor(($in_count-1)/2); // find the middle value, or the lowest middle value
	        if($in_count % 2) { // odd number, middle is the median
        	    $median = $in_arr[$middleval];
		 } else { // even number, calculate avg of 2 medians
		        $low = $in_arr[$middleval];
		        $high = $in_arr[$middleval+1];
	#	        $median = round((($low+$high)/2),2);
		        $median = (($low+$high)/2);
		 }
	         $median = round($median,1); 
         }else{
		$median = 0;
	 }
		
         $median = add_interval($median); 

    return $median;
}

/*###################################################################
# This functions gets the exact display padding length for passed variable
####################################################################*/

function get_print_data_pad($data,$data_max)
{

    $data_display = ""; //Specifies the available display length for passed data
    $print_data_pad = ""; //Specifies the padding needed in front of the data
    $data_length = strlen($data); //Gives the length of the data
    $data_display = ($data_max - $data_length);		                 		
    for ($i=0; $i < $data_display; $i++){
         $print_data_pad .= " "; 
    }	
    return $print_data_pad;
}

/*###################################################################
# This functions prepares the body, subject, to, headers for weekly message
####################################################################*/

function send_weekly_message($body)
{

  $body = "RFC Editor Queue Report\n\n".$body;

  $subject = "RFC Editor - Current Queue Summary";

  $to = "iesg@ietf.org, iab@iab.org, iana@iana.org, exec-director@ietf.org, rfc-editor@rfc-editor.org";
    $headers= "Content-Type: text/plain; charset=UTF-8\n";
     $headers  .= "From: rfc-editor@rfc-editor.org\n";
   #  $headers .= "Cc: " . $cc . "\n";
      send_wkl_mail($to,$subject,$body,$headers) ;
}
/*###################################################################
# This functions prepares the body, subject, to, headers for monthly message
####################################################################*/

function send_monthly_message($body)
{

  $body = "RFC Editor Queue Report\n\n".$body;

  $subject = "RFC Editor - Current Queue Summary";

  $to = "rfc-editor@rfc-editor.org, rfced-ietf@ietf.org";
     $headers= "Content-Type: text/plain; charset=UTF-8\n";
     $headers  .= "From: rfc-editor@rfc-editor.org\n";
   #  $headers .= "Cc: " . $cc . "\n";
      send_wkl_mail($to,$subject,$body,$headers) ;
}

/*###################################################################
# This functions sends the email message using AMS utility
####################################################################*/

function send_wkl_mail($to,$subject,$body,$headers) {
     // For reasons that are unclear (lost in the innards of SMTP mail
     // transport agents) some MTAs transform the body to
     // double-spaced if there's a CR in the data.
     $body_stripped = preg_replace("/\r/", "", $body);
     $header_stripped = preg_replace("/\r/", "", $headers);
    #$mail_sent = ams_mail($to,$subject,$body_stripped,$header_stripped);
     return (mail($to, $subject, $body_stripped, $header_stripped));

}

?>



