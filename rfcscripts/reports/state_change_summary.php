#!/usr/bin/php

<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
include('db.php');
include('reports_config.php');

$debug_sumchg = false;
global  $state_change_monthly_path;

/*********************************************************************************************
 1.Get the dates
 2.Returns the an array of dates for all the database values 
*********************************************************************************************/

$run_year = date('Y');
$run_month = date('m');
$run_day = date('d');
$run_date = date('Y-m-d');

/************************************************************************/
/* FOR YEAR 2012 INITIAL COUNTER HAS TO SET VALUE FROM LEGACY DATA      */
/* THIS WILL BE MODIFIED TO ZERO FROM 2013                              */

if ($run_year == '2012'){
	$year_beg_date = "$run_year-03-01";
}else {
	$year_beg_date = "$run_year-01-01";
}
#$year_beg_date = "$run_year-01-01";

/************************************************************************/

$month_state_html=fopen("$state_change_monthly_path$run_year.html",'w');


$html_header="<html><head><title>State Changes by Month ($run_year)</title><meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859\"><link href=\"statistics.css\" rel=\"stylesheet\" type=\"text/css\" /></head><body>";


$html_body = "<body>";
$html_mainContent = get_body();
$html_footer = "</body></html>";

if (! $month_state_html) die("Error opening file");
fwrite($month_state_html,$html_header);
fwrite($month_state_html,$html_mainContent);
fwrite($month_state_html,$html_footer);
fclose($month_state_html);

#eader('Location: $HOST_NAME/$DIR_PROCEEDING/'.$meeting_number.'/acknowledgement.html');

function get_body(){

 global $run_year;
 $month_data = "";
 $month_data = "";
 $body_text = "<h1 class=\"simple_center\">State Changes by Month ($run_year)</h1>\n";
/* $body_text .= "<p class=\"simple_center\"> This report summarizes document movement through the RFC Editor queue. It shows the number of documents and related page counts moved into any given state during the month. (For data from before March 2012, click <a href=\"legacydata.txt\">here.</a>)</p>";*/
 $body_text .= get_sum_stats();
 $body_text .= "</table>";
 
 return $body_text;
}
/***********************************************************************************************************************
 FUNCTION : get_sum_stats 
 1. Gets the Monthlt state wise summary counts
**********************************************************************************************************************/
function get_sum_stats(){

global $debug_sumchg;
global $year_beg_date;
global $run_year;


$run_prev = $run_year - 1;
$run_next = $run_year + 1;
/*$print_sum_stat = "<table border=\"0\" width=\"70%\"><tr><td><table width=\"70%\" border=\"0\"><tr><th></th><th>Doc Count</th><th>Page Count</th><th>Pgs/Doc</th></tr>";*/

if ($run_year == '2012'){
$print_sum_stat = "<table border=\"0\" width=\"70%\"><tr class=\"simple_left\"><td> This report summarizes document movement through the <a href=\"/current_queue.php\">RFC Editor queue.</a> It shows the number of documents and related page counts moved into any given state during the month. (For data from before March 2012, click <a href=\"legacydata.txt\">here.</a>)</td></tr><tr><td><table width=\"70%\" border=\"0\"><tr><td><a href=\"state_change_$run_next.html\">See Next Year</a></td></tr></table></td></tr><tr><td><table width=\"70%\" border=\"0\"><tr><th></th><th>Doc Count</th><th>Page Count</th><th>Pgs/Doc</th></tr>";
}else {
$print_sum_stat = "<table border=\"0\" width=\"70%\"><tr class=\"simple_left\"><td> This report summarizes document movement through the <a href=\"/current_queue.php\">RFC Editor queue.</a> It shows the number of documents and related page counts moved into any given state during the month.</td></tr><tr><td><table width=\"70%\" border=\"0\"><tr><td class=\"simple_left\"><a href=\"state_change_$run_prev.html\">See Previous Year</a></td><td><a href=\"state_change_$run_next.html\">See Next Year</a></td></tr></table></td></tr><tr><td><table width=\"70%\" border=\"0\"><tr><th></th><th>Doc Count</th><th>Page Count</th><th>Pgs/Doc</th></tr>";


}

global $debug_sumchg;
global $db_link;

$state_order = array('EDIT','RFC-EDITOR','AUTH48','MISSREF','PUB','DNP');



$state_array = array();
$state_query = 'Select `state_id`, `state_name` from states where state_id in(\'4\',\'17\',\'2\',\'22\',\'14\',\'3\')';


$db_link = open_db();

# Pull up the query and just implode it into a table.
if ( ($state_result = mysqli_query($db_link,$state_query))) {
        while ( $state_line = mysqli_fetch_row($state_result)) {
            array_push($state_array, $state_line);
        }
}  

if ($debug_sumchg === true) {
	print("state_change_sum.php :: States query :\n");
	print_r($state_array); print("\n");
	print("\n");
}


/* Call the function to calculate the DATES
*  We are using dates YEARWISE MONTHWISE SORTED */

$stats_date_array = get_dates();
if ($debug_sumchg === true) {
        print("state_change_sum.php :: DATE ARRAY :\n");
	print_r($stats_date_array); print("\n");
	print("\n");
}


$stats_date_count = count($stats_date_array);

$date_print = "";

$year_print = "";
$cur_year = "";

/************************************************************************/
/* FOR YEAR 2012 INITIAL COUNTER HAS TO SET VALUE FROM LEGACY DATA      */
/* THIS WILL BE MODIFIED TO ZERO FROM 2013                              */
/*
/***************************************************************************/



 if ($run_year == '2012'){
	 $cum_EDIT_count = 53;
	 $cum_RFC_EDITOR_count = 50;
	 $cum_AUTH48_count = 67;
	 $cum_MISSREF_count = 0;   
	 $cum_PUB_count = 63;
	 $cum_DNP_count = 6;

	 $cum_EDIT_pagecount = 1103;
	 $cum_RFC_EDITOR_pagecount = 1416;
	 $cum_AUTH48_pagecount = 2083;
	 $cum_MISSREF_pagecount = 0;   
	 $cum_PUB_pagecount = 1400;
	 $cum_DNP_pagecount = 93;
 }else {

	 $cum_EDIT_count = 0;
	 $cum_RFC_EDITOR_count = 0;
	 $cum_AUTH48_count = 0;
	 $cum_MISSREF_count = 0;   
	 $cum_PUB_count = 0;
	 $cum_DNP_count = 0;

	 $cum_EDIT_pagecount = 0;
	 $cum_RFC_EDITOR_pagecount = 0;
	 $cum_AUTH48_pagecount = 0;
	 $cum_MISSREF_pagecount = 0;   
	 $cum_PUB_pagecount = 0;
	 $cum_DNP_pagecount = 0;

 }

/***************************************************************************/

$result_array = array();

for ( $row = 0; $row < $stats_date_count ; $row++){
    for ($column = 0; $column < 2 ; $column++){
         $start_date = $stats_date_array[$row][0];
         $end_date = $stats_date_array[$row][1];
         $report_date = $stats_date_array[$row][2];
         $report_month = date('M',strtotime($start_date));
         $report_year = date('Y',strtotime($start_date));

    }//End for ($column = 0; $column < 2 ; $column++){

  
   
    $date_print = "<tr><td class=\"rfctext\"colspan=\"4\">$report_year $report_month</td></tr>";

    $ind_state_data = "";
    
    
/* 1.Call the state order and for enery state then call the query 
*/

    foreach ($state_order as $state) { //Need to maintain default report order 
	foreach ($state_array as $state_line) {
             $pattern = "/^$state(\*A|\*R)*$/";
             if (preg_match($pattern, $state_line[1])) {
                 $st = $state_line[1];
                 $st_id = $state_line[0];
                 $EDIT_count = 0;
                 $RFC_EDITOR_count = 0;
                 $AUTH48_count = 0;
                 $MISSREF_count = 0;   
                 $PUB_count = 0;
                 $DNP_count = 0;

                 $EDIT_pagecount = 0;
                 $RFC_EDITOR_pagecount = 0;
                 $AUTH48_pagecount = 0;
                 $MISSREF_pagecount = 0;
                 $PUB_pagecount = 0;
                 $DNP_pagecount = 0;

   
		$stats_query = "SELECT DISTINCT (sh.internal_dockey), i.`PAGE-COUNT` FROM `index` i, `state_history` sh WHERE i.internal_key = sh.internal_dockey AND sh.`state_id` = '$st_id' AND sh.`in_date` >= '$start_date' AND sh.`in_date` <= '$end_date' AND i.`doc-id` like 'RFC%'";

 	          if ($debug_sumchg === true) {
			print("state_change_sum.php :: Statistics query :\n");
			print "$stats_query"; 
                        print("\n");
			print("\n");
		  }


		  if (($stats_result = mysqli_query($db_link,$stats_query))){
                         $num_of_rows = mysqli_num_rows($stats_result);
                         if ($num_of_rows == 0) {
                             switch($st_id){
                                    case '4':
                                            $state_name = get_statename($st_id);    
					    $ind_state_data .= "<tr><td class=\"simple_left\"> $state_name </td><td> $EDIT_count </td><td> $EDIT_pagecount </td><td>0</td></tr>";
                                            break;
                                    case '17':
                                            $state_name = get_statename($st_id);    
					    $ind_state_data .= "<tr><td class=\"simple_left\">$state_name</td><td> $RFC_EDITOR_count </td><td>  $RFC_EDITOR_pagecount </td><td>0</td></tr>";
                                            break;
                                    case '2':
                                            $state_name = get_statename($st_id);    
					    $ind_state_data .= "<tr><td class=\"simple_left\">  $state_name </td><td> $AUTH48_count </td><td>  $AUTH48_pagecount  </td><td>0</td></tr>";
                                            break; 
                                    case '22':
                                            $state_name = get_statename($st_id);    
					    $ind_state_data .= "<tr><td class=\"simple_left\">  $state_name </td><td> $MISSREF_count </td><td> $MISSREF_pagecount </td><td>0</td></tr>";
                                             break;
                                    case '14':
                                            $state_name = get_statename($st_id);    
					    $ind_state_data .= "<tr><td class=\"simple_left\">  $state_name </td><td> $PUB_count </td><td> $PUB_pagecount </td><td>0</td></tr>";
                                             break;
                                    case '3':
                                            $state_name = get_statename($st_id);    
					    $ind_state_data .= "<tr><td class=\"simple_left\"> $state_name </td><td> $DNP_count </td><td> $DNP_pagecount </td><td>0</td></tr>";
                                             break;
                                    default:   
                                           break; 
                             }//End of switc
                         }//End of num_of_rows == 0 
                         if ($num_of_rows > 0){
	                 	while ($stats_line = mysqli_fetch_row($stats_result)){
                                     switch($st_id){
                                            case '4':
	                                            $EDIT_count = $EDIT_count + 1;
                                                    $EDIT_pagecount = $EDIT_pagecount + $stats_line[1];
                                                    break;
                                            case '17':
                                                     $RFC_EDITOR_count = $RFC_EDITOR_count + 1; 
						     $RFC_EDITOR_pagecount = $RFC_EDITOR_pagecount + $stats_line[1];	
                                                     break;
                                            case '2':
                                                     $AUTH48_count = $AUTH48_count + 1;
                                                     $AUTH48_pagecount = $AUTH48_pagecount + $stats_line[1];
                                                     break;
                                            case '22':
                                                     $MISSREF_count = $MISSREF_count + 1;
                                                     $MISSREF_pagecount = $MISSREF_pagecount + $stats_line[1];
                                                     break;                           
                                            case '14':
                                                     $PUB_count = $PUB_count + 1;
                                                     $PUB_pagecount = $PUB_pagecount + $stats_line[1];
                                                     break;
                                            case '3':
                                                     $DNP_count = $DNP_count + 1;   
                                                     $DNP_pagecount = $DNP_pagecount + $stats_line[1];
                                                     break;  
                                            default:
                                                    break;
                                     }//End switch
                                }//End of while ($stats_line = mysqli_fetch_row($stats_result)){
                         }//End of if ($num_of_rows > 0){
                  }//End of if (($stats_result = mysqli_query($stats_query))){
             } //End if (preg_match($pattern, $state_line[1]))
        }// foreach ($state_array as $state_line) 

        list($YY,$MM,$DD) = explode('-',$start_date);

        if ($EDIT_count > 0) {
            $pgs_doc_EDIT = get_pages_doc($EDIT_count,$EDIT_pagecount); 
            $state_name = get_statename(4);    
	    $ind_state_data .= "<tr><td class=\"simple_left\">$state_name</td><td> $EDIT_count </td><td> $EDIT_pagecount </td><td>$pgs_doc_EDIT</td></tr>";
        }
        if ($RFC_EDITOR_count > 0) {
            $pgs_doc_RFC_EDITOR =  get_pages_doc($RFC_EDITOR_count,$RFC_EDITOR_pagecount);
            $state_name = get_statename(17);    
            $ind_state_data .= "<tr><td class=\"simple_left\"> $state_name </td><td> $RFC_EDITOR_count </td><td> $RFC_EDITOR_pagecount </td><td>$pgs_doc_RFC_EDITOR</td></tr>";
	}
        if ($AUTH48_count > 0) {
            $pgs_doc_AUTH48 = get_pages_doc($AUTH48_count,$AUTH48_pagecount);
            $state_name = get_statename(2);    
	    $ind_state_data .= "<tr><td class=\"simple_left\"> $state_name </td><td> $AUTH48_count </td><td> $AUTH48_pagecount </td><td>$pgs_doc_AUTH48</td></tr>";
               }
        if ($MISSREF_count > 0) {
            $pgs_doc_MISSREF = get_pages_doc($MISSREF_count,$MISSREF_pagecount); 
            $state_name = get_statename(22);    
	    $ind_state_data .= "<tr><td class=\"simple_left\"> $state_name </td><td> $MISSREF_count </td><td> $MISSREF_pagecount </td><td>$pgs_doc_MISSREF</td></tr>";
               }
        if ($PUB_count > 0) {
            $pgs_doc_PUB = get_pages_doc($PUB_count,$PUB_pagecount); 
            $state_name = get_statename(14);    
	    $ind_state_data .= "<tr><td class=\"simple_left\"> $state_name </td><td> $PUB_count </td><td> $PUB_pagecount </td><td>$pgs_doc_PUB</td></tr>";
               }

        if ($DNP_count > 0) {
            $pgs_doc_DNP = get_pages_doc($DNP_count,$DNP_pagecount);
            $state_name = get_statename(3);    
	    $ind_state_data .= "<tr><td class=\"simple_left\"> $state_name </td><td> $DNP_count  </td><td> $DNP_pagecount </td><td>$pgs_doc_DNP</td></tr>";
               }

        if ($report_year != $cur_year) {
	         $before_year = $cur_year;
	         $cur_year = $report_year;
       	       	 $after_year = $cur_year;
                 if ($before_year != "") {
                    
                     $year_print .= "<tr rowspan=\"2\"><td colspan=\"4\">&nbsp;</td></tr>";   
		     $year_print .= "<tr><td class=\"rfctext\" colspan=\"4\">STATISTICS FOR YEAR TO DATE : $before_year</td></tr>";
                     $year_print .= "<tr><td class=\"simple_left\">State</td><td >Docs</td><td>Pages</td><td>Pgs/Doc</td></tr>";  
	             $cum_pgs_doc_EDIT = get_pages_doc($cum_EDIT_count,$cum_EDIT_pagecount); 
                     $state_name = get_statename(4);    
                     $year_print .= "<tr><td class=\"simple_left\">$state_name</td><td>$cum_EDIT_count</td><td>$cum_EDIT_pagecount</td><td>$cum_pgs_doc_EDIT</td></tr>";
                     $cum_pgs_doc_RFC_EDITOR = get_pages_doc($cum_RFC_EDITOR_count,$cum_RFC_EDITOR_pagecount);  
                     $state_name = get_statename(17);    
                     $year_print .= "<tr><td class=\"simple_left\">$state_name</td><td>$cum_RFC_EDITOR_count</td><td>$cum_RFC_EDITOR_pagecount</td><td>$cum_pgs_doc_RFC_EDITOR</td></tr>";    

                     $cum_pgs_doc_AUTH48 = get_pages_doc($cum_AUTH48_count,$cum_AUTH48_pagecount);  
                     $state_name = get_statename(2);    
                     $year_print .= "<tr><td class=\"simple_left\">$state_name</td><td>$cum_AUTH48_count</td><td>$cum_AUTH48_pagecount</td><td>$cum_pgs_doc_AUTH48</td></tr>";    
                     $cum_pgs_doc_MISSREF = get_pages_doc($cum_MISSREF_count,$cum_MISSREF_pagecount);
                     $state_name = get_statename(22);    
                     $year_print .= "<tr><td class=\"simple_left\">$state_name</td><td>$cum_MISSREF_count</td><td>$cum_MISSREF_pagecount</td><td>$cum_pgs_doc_MISSREF</td></tr>";
                     $cum_pgs_doc_PUB = get_pages_doc($cum_PUB_count,$cum_PUB_pagecount);
                     $state_name = get_statename(14);    
                     $year_print .= "<tr><td class=\"simple_left\">$state_name</td><td>$cum_PUB_count</td><td>$cum_PUB_pagecount</td><td>$cum_pgs_doc_PUB</td></tr>";
                     $cum_pgs_doc_DNP = get_pages_doc($cum_DNP_count,$cum_DNP_pagecount);  
                     $state_name = get_statename(3);    
                     $year_print .= "<tr><td class=\"simple_left\">$state_name</td><td>$cum_DNP_count</td><td>$cum_DNP_pagecount</td><td>$cum_pgs_doc_DNP</td></tr>\n";
                     $year_print .= "<tr rowspan=\"2\"><td colspan=\"4\">&nbsp;</td></tr>";   

/************************************************************************/
/* FOR YEAR 2012 INITIAL COUNTER HAS TO SET VALUE FROM LEGACY DATA      */
/* THIS WILL BE MODIFIED TO ZERO FROM 2013                              */
/***************************************************************************/

		     if($run_year == '2012'){
			     $cum_EDIT_count = 53;
		 	     $cum_RFC_EDITOR_count = 50;
                	     $cum_AUTH48_count = 67;
	 		     $cum_MISSREF_count = 0;   
        	             $cum_PUB_count = 63;
                	     $cum_DNP_count = 6;

	                     $cum_EDIT_pagecount = 1103;
        	             $cum_RFC_EDITOR_pagecount = 1416;
                	     $cum_AUTH48_pagecount = 2083;
	                     $cum_MISSREF_pagecount = 0;   
        	             $cum_PUB_pagecount = 1400;
                	     $cum_DNP_pagecount = 93;
                     } else {
			     $cum_EDIT_count = 0;
			     $cum_RFC_EDITOR_count = 0;
			     $cum_AUTH48_count = 0;
			     $cum_MISSREF_count = 0;   
			     $cum_PUB_count = 0;
			     $cum_DNP_count = 0;

			     $cum_EDIT_pagecount = 0;
        	             $cum_RFC_EDITOR_pagecount = 0;
			     $cum_AUTH48_pagecount = 0;
			     $cum_MISSREF_pagecount = 0;   
			     $cum_PUB_pagecount = 0;
			     $cum_DNP_pagecount = 0;
		     }

                  }
            }

            $cum_EDIT_count = $cum_EDIT_count + $EDIT_count;
            $cum_RFC_EDITOR_count = $cum_RFC_EDITOR_count + $RFC_EDITOR_count;
            $cum_AUTH48_count = $cum_AUTH48_count + $AUTH48_count;
            $cum_MISSREF_count = $cum_MISSREF_count + $MISSREF_count;   
            $cum_PUB_count = $cum_PUB_count + $PUB_count;
            $cum_DNP_count = $cum_DNP_count + $DNP_count;

            $cum_EDIT_pagecount = $cum_EDIT_pagecount + $EDIT_pagecount;
            $cum_RFC_EDITOR_pagecount = $cum_RFC_EDITOR_pagecount + $RFC_EDITOR_pagecount;
            $cum_AUTH48_pagecount = $cum_AUTH48_pagecount + $AUTH48_pagecount;
            $cum_MISSREF_pagecount = $cum_MISSREF_pagecount + $MISSREF_pagecount;   
            $cum_PUB_pagecount = $cum_PUB_pagecount + $PUB_pagecount;
            $cum_DNP_pagecount = $cum_DNP_pagecount + $DNP_pagecount;
   
 
    }//End of   foreach ($state_order as $state)

      if ($year_print){
          $print_sum_stat .= "$year_print $date_print $ind_state_data";
          $year_print = "";
      }	
      else {
	  if (($row +1) == $stats_date_count){/*For current Year*/
                     $year_print .= "<tr rowspan=\"2\"><td colspan=\"4\">&nbsp;</td></tr>";   
		     $year_print .= "<tr><td class=\"rfctext\" colspan=\"4\">STATISTICS FOR YEAR TO DATE : $report_year</td></tr>";
                     $year_print .= "<tr><td class=\"simple_left\">State</td><td >Docs</td><td>Pages</td><td>Pgs/Doc</td></tr>";  
      	             $cum_pgs_doc_EDIT = get_pages_doc($cum_EDIT_count,$cum_EDIT_pagecount); 
                     $state_name = get_statename(4);    
                     $year_print .= "<tr><td class=\"simple_left\">$state_name</td><td>$cum_EDIT_count</td><td>$cum_EDIT_pagecount</td><td>$cum_pgs_doc_EDIT</td></tr>";
                     $cum_pgs_doc_RFC_EDITOR = get_pages_doc($cum_RFC_EDITOR_count,$cum_RFC_EDITOR_pagecount);  
                     $state_name = get_statename(17);    
                     $year_print .= "<tr><td class=\"simple_left\">$state_name</td><td>$cum_RFC_EDITOR_count</td><td>$cum_RFC_EDITOR_pagecount</td><td>$cum_pgs_doc_RFC_EDITOR</td></tr>";    

                     $cum_pgs_doc_AUTH48 = get_pages_doc($cum_AUTH48_count,$cum_AUTH48_pagecount);  
                     $state_name = get_statename(2);    
                     $year_print .= "<tr><td class=\"simple_left\">$state_name</td><td>$cum_AUTH48_count</td><td>$cum_AUTH48_pagecount</td><td>$cum_pgs_doc_AUTH48</td></tr>";    
                     $cum_pgs_doc_MISSREF = get_pages_doc($cum_MISSREF_count,$cum_MISSREF_pagecount);
                     $state_name = get_statename(22);    
                     $year_print .= "<tr><td class=\"simple_left\">$state_name</td><td>$cum_MISSREF_count</td><td>$cum_MISSREF_pagecount</td><td>$cum_pgs_doc_MISSREF</td></tr>";
                     $cum_pgs_doc_PUB = get_pages_doc($cum_PUB_count,$cum_PUB_pagecount);
                     $state_name = get_statename(14);    
                     $year_print .= "<tr><td class=\"simple_left\">$state_name</td><td>$cum_PUB_count</td><td>$cum_PUB_pagecount</td><td>$cum_pgs_doc_PUB</td></tr>";
                     $cum_pgs_doc_DNP = get_pages_doc($cum_DNP_count,$cum_DNP_pagecount);  
                     $state_name = get_statename(3);    
                     $year_print .= "<tr><td class=\"simple_left\">$state_name</td><td>$cum_DNP_count</td><td>$cum_DNP_pagecount</td><td>$cum_pgs_doc_DNP</td></tr>\n";
                     $year_print .= "<tr rowspan=\"2\"><td colspan=\"4\">&nbsp;</td></tr>";   




               $print_sum_stat .= "$date_print $ind_state_data $year_print";
	  }else{
        	$print_sum_stat .= "$date_print $ind_state_data";

	   }
      }


 }//End for ( $row = 0; $row < $state_date_count ; $row++){

mysqli_free_result($state_result);
close_db();


$print_sum_stat .= "</table></td></tr>";
return $print_sum_stat;
}

/*********************************************************************************************
FUNCTION : get_dates()
 1.This function gets the distinct dates from the statistics table to get the monthwise data
 2.Then gets the first day and last day of the distinct month
 3.Gets the First date and Last date of the month 
 4.Repeat the process for all the dates in the statistics database table
 5.Returns the an array of dates for all the database values 
*********************************************************************************************/
function get_dates() {

    global $debug_sumchg;
    global $year_beg_date;
    global $db_link;
    $date_old = "";
    $date_new = "";
    $date_array = array();
    $report_date_array = array();

#    open_db();

#    $date_query = "select DISTINCT(`report_date`),`week_of_year` from `statistics` order by `report_date`, `week_of_year` ";
#    $date_query = "select DISTINCT(`report_date`),`week_of_year` from `statistics` where `report_date` > '$year_beg_date' order by `report_date`, `week_of_year` ";
    $date_query = "SELECT DISTINCT (`in_date`)FROM `state_history` WHERE `in_date` > '$year_beg_date' ORDER BY `in_date`"; 

    if (($date_result = mysqli_query($db_link,$date_query))){
         while ($date_line = mysqli_fetch_row($date_result)){
                array_push($date_array,$date_line);
         }//End of while
    }//End of if $date_result

    if ($debug_sumchg){
        print "The original array \n";
        print_r($date_array);
    }

    
    foreach ($date_array as $date_line) {
    	$entry_date = $date_line[0];
    	list($y_date,$m_date,$d_date) = explode('-',$entry_date);
    	$date_new = "$y_date$m_date";
   
    	if ($date_new != $date_old){
        	$date_old = $date_new;
	        $last_day = date('t',strtotime($entry_date));
	        $last_date = "$y_date-$m_date-$last_day";
	        $first_date = "$y_date-$m_date-01";
	        $temp_array = array($first_date,$last_date,$entry_date);
	        array_push($report_date_array,$temp_array);        
        
	        if ($debug_sumchg){
        	    print "LAST : $last_date FIRST: $first_date ORIGINAL: $entry_date\n";
        	}
    	}

    	if ($debug_sumchg){
	    	print "YEAR : $y_date MONTH : $m_date DAY :$d_date\n";
	    	print "NEW:$date_new OLD: $date_old\n";
    	}
    }

    
    return $report_date_array;
}

/*********************************************************************************************
FUNCTION : get_pages_doc()
 1.This function gets the average value of pages per documents.
 2.If input document is zero then it retuens the zero.
*********************************************************************************************/
function get_pages_doc($in_docs,$in_pages){

if ($in_docs != 0){

#   print "In docs $in_docs\n";
#   print "In pages $in_pages\n";

    $in_pages = $in_pages;
    $in_docs = $in_docs;

    $pgs_per_doc = ($in_pages / $in_docs);

    $pgs_per_doc = round($pgs_per_doc);

}
else {
   $pgs_per_doc = 0;
}
 return $pgs_per_doc;  
   
}


/******************************************************************************************************/
/*Function : get_statename()                                                                          */ 
/*Gets the state from the passed state id                                                             */ 
/******************************************************************************************************/

function get_statename($state_id){
 global $db_link;
 $query = "SELECT state_name FROM `states` where state_id = '$state_id'";
     open_db();
     if ($result = mysqli_query($db_link,$query)){
	     while ($row_state_id = mysqli_fetch_assoc($result)){
		     $state_name = $row_state_id['state_name']; 
     	     }
     } else {
          rfc_fail("Database error in get_state_name:" . mysqli_error($db_link));
     }
     close_db(); 

 return $state_name;
}

?>
