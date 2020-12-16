#!/usr/bin/php
<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
/**************************************************************************************/
/*Name : rfcatom.php                                                                   */
/*       This script generates a Atom feed in XML using PHP/MYSQL setup. IT uses the   */
/*       existing dtabase table information to create the feed data. The script is    */
/*       from the website when user Posts rfc-index.xml to datatracker. User can      */
/*       subscribe to feed and stay updated about RFC editor website                  */
/* November 2020 : Modified the script to use PDO prepared statements - PN            */
/**************************************************************************************/

// Connect to your MySQL Database
include ('db_connect.php');

header('Content-type: text/xml');
//Build the RSS XML output
$build = '<?xml version="1.0" encoding="utf-8"?>
         <feed xmlns="http://www.w3.org/2005/Atom">
	<title>Recent RFCs</title>
	<subtitle>Recently published RFCs</subtitle>
	<link href="https://www.rfc-editor.org/" rel="self"'.'/>';


$date3339 = date3339();

$build .='<updated>'.$date3339.'</updated>
	<author>
	    <name>RFC Editor</name>
	</author>
	<id>https://www.rfc-editor.org</id>';
 
//Query the database for the latest Published doc
$query = "SELECT `DOC-ID`, `TITLE`,`ABSTRACT`,`PUB-DATE` FROM `index` WHERE `state_id` in (:state_id) order by `PUB-DATE` desc limit :limit_no";
try {
       $stmt = $pdo->prepare($query);
       $stmt->bindValue('state_id',14);
       $stmt->bindValue('limit_no',15);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   }catch (PDOException $pe){
       error_log("Error processing : rfcatom.php", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }


while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
	$pub_date = $row['PUB-DATE'];
	$pub_date_formatted = date3339(strtotime($pub_date));

	list($rfc_name,$rfc_number,$length) = split_rfc($row['DOC-ID']);
	$formatted_rfc_number = sprintf('%s %d',$rfc_name,$rfc_number);
	$row['DOC-ID'] = strtolower($row['DOC-ID']);


	$build .= "\t\t\t".'<entry>
		<title>'.htmlentities($formatted_rfc_number) .': '. htmlentities($row['TITLE']).'</title>
		<link type="text/html" href="https://www.rfc-editor.org/info/'. $row['DOC-ID'] .'"/>
		<id>https://www.rfc-editor.org/info/'. $row['DOC-ID'] .'</id>

		<updated>'.htmlentities($pub_date_formatted).'</updated>
		<summary>'.htmlentities($row['ABSTRACT']).'</summary>
	</entry>'."\r\n";

}

$build .= "\t\t".'</feed>';

print $build;

/**************************************************************************************/
/*Generates the tiestamp specified in RFC 3339 format                                 */
/**************************************************************************************/
function date3339($timestamp=0) {
	if (!$timestamp) {
		$timestamp = time();
	}
	
	$date = date('Y-m-d\TH:i:s', $timestamp);

	$matches = array();
	if (preg_match('/^([\-+])(\d{2})(\d{2})$/', date('O', $timestamp), $matches)) {
		$date .= $matches[1].$matches[2].':'.$matches[3];
	} else {
		$date .= 'Z';
	}

	return $date;

}
/**************************************************************************************/
/*Split rfc functions splits the given RFC/Subseries number in two parts*/
/**************************************************************************************/
function split_rfc($in_value){

	 global $debug_rsl;
	 $length = strlen($in_value);

	 $out_name = "";
	 $out_number = "";

	if ($length == 9){
         $out_name = substr($in_value,0,'-6');
         $out_number = substr($in_value,'-5');
	}
	elseif ($length == 8){
         $out_name = substr($in_value,0,'-4');
         $out_number = substr($in_value,'-4');
	}elseif ($length == 7){
         $out_name = substr($in_value,0,'-4');
         $out_number = substr($in_value,'-4');
	}elseif ($length == 6){
         $out_name = substr($in_value,0,'-3');
         $out_number = substr($in_value,'-3');
	}elseif ($length == 5){
         $out_name = substr($in_value,0,'-2');
         $out_number = substr($in_value,'-2');
	}elseif ($length == 4){
         $out_name = substr($in_value,0,'-1');
         $out_number = substr($in_value,'-1');
	}
		
	 if ($debug_rsl){
		 print "Out name is :".$out_name."\n";
	     print "Out number is :".$out_number."\n";
  	     print "Length is :".$length."\n";
	 }
	 
	 return array($out_name, $out_number, $length);
	}
	
?>
