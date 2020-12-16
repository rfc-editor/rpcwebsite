#!/usr/bin/php
<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
/*********************************************************************************************
 1.Get the dates
 2.Returns the an array of dates for all the database values 
*********************************************************************************************/

$start_year = 2012;
$start_month = date("m", strtotime("april"));

$run_year = date("Y", strtotime("-1 month") ) ;
$run_month = date("m", strtotime("-1 month") ) ;
$run_month_full = date("F", strtotime("-1 month") ) ;


/*$rfced_time_html=fopen("/a/home/priyanka/devp/rfc-new/index.html",'w');*/
$rfced_time_html=fopen("/a/www/staff/rfced_report/index.html",'w');

$html_header="<html><head><title>RFC Editor Time </title><meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859\"><link href=\"/staff/style/statistics.css\" rel=\"stylesheet\" type=\"text/css\" /></head><body>";


$html_body = "<body>";
$html_mainContent = getContent();

$html_footer = "</body></html>";

if (! $rfced_time_html) die("Error opening file");
fwrite($rfced_time_html,$html_header);
fwrite($rfced_time_html,$html_mainContent);
fwrite($rfced_time_html,$html_footer);
fclose($rfced_time_html);


function getContent(){

$htmlContent = "";

global $start_year;
global $start_month;

global $run_year;
global $run_month;
global $run_month_full;


$months = array("January" => "01","February" => "02","March" => "03","April" => "04","May" => "05","June" => "06","July" => "07","August" => "08","September" => "09","October" => 10,"November" => 11,"December" => 12); // Sets up the months into an associative array.


$htmlContent .= "<h2 class=\"simple_center\">RFC Editor Time</h2>";
$htmlContent .= "<a href=\"https://www.rfc-editor.org/staff/current_rfced_time.php\">View the Current Month</a>";
$htmlContent .= "<h3>Archived</h3>";
$htmlContent .= "<h2>$start_year</h2>";
$htmlContent .= "<ul>";
$htmlContent .= "<li><a href=\"rfced_time04$start_year.html\">April</a></li>";
$htmlContent .= "<li><a href=\"rfced_time05$start_year.html\">May</a></li>";
$htmlContent .= "<li><a href=\"rfced_time06$start_year.html\">June</a></li>";
$htmlContent .= "<li><a href=\"rfced_time07$start_year.html\">July</a></li>";
$htmlContent .= "<li><a href=\"rfced_time08$start_year.html\">August</a></li>";
$htmlContent .= "<li><a href=\"rfced_time09$start_year.html\">September</a></li>";
$htmlContent .= "<li><a href=\"rfced_time10$start_year.html\">October</a></li>";
$htmlContent .= "<li><a href=\"rfced_time11$start_year.html\">November</a></li>";
$htmlContent .= "<li><a href=\"rfced_time12$start_year.html\">December</a></li>";
$htmlContent .= "</ul>";

$diff = $run_year - $start_year;

//print "The diff is $diff";

for ($i=1; $i <= $diff; $i++){
     $year_content = "";
     $month_content = "";
     $next_year = $start_year + $i;
     $year_content .= "<h2>$next_year</h2>";
     $month_content = "<ul>";   

     foreach ($months as $key => $value){
         $month_content .= "<li><a href=\"rfced_time$value$next_year.html\">$key</a></li>";
         if (($key == $run_month_full) && ($next_year == $run_year)){
#            $month_content .= "Same Key $key for run month $run_month_full";
	    break;
	}  

     }   
     $month_content .= "</ul>"; 	
     $htmlContent .= "$year_content"."$month_content"; 
}


return $htmlContent;
}

?>
