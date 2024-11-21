#!/usr/bin/php
<?php
/************************************************************************************************/
/* Script Name     : monthly_rfc.php  - PN                                                      */
/*                   The script outputs a list of HTML links for RFCs published each month and  */
/*                   sends email to the specified email address with the list. Runs through     */
/*                   cron each month.                                                           */
/* May 2024        : Added state_id in the publised RFCs query     - PN                         */
/************************************************************************************************/

include_once("db_connect.php");

$date = new DateTime();

/*Get the first date, last date and current month*/
$date->modify("last day of previous month");

/*Format the last day date*/
$last_dt = $date->format("Y-m-d"); 

/*Now the month in the above statement is already changed to previous month so consider
 that as current month and perform next date operation*/

$date->modify("first day of this month");

/*Format the first day date*/
$first_dt = $date->format("Y-m-d"); // 2022-09-30 Friday

/*Format the this month*/
$this_month = $date->format('F, Y');

/*Get the data from the database*/
$rfcs_published_list = get_published_rfcs($first_dt,$last_dt);

/*Send the mail with the list*/		
send_publish_list($rfcs_published_list,$this_month);


/************************************************************************************************/
/* get_published_rfcs :  This function get the list of published RFC's for the given month      */
/************************************************************************************************/

function get_published_rfcs($start_date,$end_date) {

global $pdo;

$rfc_list = array();

$rfc_const = 'https://www.rfc-editor.org/rfc/';

$select = "SELECT LOWER(`DOC-ID`) FROM `index` where `PUB-DATE` BETWEEN :start_date AND :end_date AND `state_id` =:state_id";

try{
    $stmt = $pdo->prepare($select);
    $stmt->bindParam('start_date',$start_date);
    $stmt->bindParam('end_date',$end_date);
    $stmt->bindValue('state_id',14);
    $stmt->execute();
    $rfcs_published = $stmt->rowCount();
} catch (PDOException $pe){ 
       error_log("Error processing :  get_published_rfcs", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
}

    while ($row = $stmt->fetch(PDO::FETCH_NUM)){
           $row[0] = $rfc_const.$row[0].'.html';
          $rfc_list = array_merge($rfc_list,$row);
    }
     return $rfc_list;

}


/************************************************************************************************/
/* send_publish_list  :  This function formats the message part with the list information       */
/************************************************************************************************/

function send_publish_list($pub_array,$cur_month){
    $print_data = "";

    foreach ($pub_array as $line) {

            $print_data .= "  "."$line"."\n";
    }

    $body = "RFCs published for $cur_month \n\n".$print_data;

    $subject = "TEST : Monthly list of RFCs";
        
    $to = "rsce@rfc-editor.org";
    $headers= "Content-Type: text/plain; charset=UTF-8\n";
    $headers  .= "From: rfc-editor@rfc-editor.org\n";
     
    send_monthly_mail($to,$subject,$body,$headers) ;

}

/************************************************************************************************/
/* send_monthly_mail  :  This sends email with the formatted message                            */
/************************************************************************************************/


function send_monthly_mail($to,$subject,$body,$headers) {
    $body_stripped = preg_replace("/\r/", "", $body);
    $header_stripped = preg_replace("/\r/", "", $headers);
    return (mail($to, $subject, $body_stripped, $header_stripped));
}


?>
