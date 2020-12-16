#!/usr/bin/php
<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
/**************************************************************************************/
/*Name : rfcrss.php                                                                   */
/*       This script generates a RSS feed in XML using PHP/MYSQL setup. IT uses the   */
/*       existing dtabase table information to create the feed data. The script is    */
/*       from the website when user Posts rfc-index.xml to datatracker. User can      */
/*       subscribe to feed and stay updated about RFC editor website                  */
/* November 2020 : Modified the script to use PDO prepared statements - PN            */
/**************************************************************************************/

// Connect to your MySQL Database
include ('db_connect.php');
//Open database
//Build the RSS XML output
$build = '<?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0">
	<channel>
		<title>Recent RFCs</title>
		<link>https://www.rfc-editor.org/</link>
		<description>Recently published RFCs</description>
		<language>en-us</language>
		<lastBuildDate>'.date('D, d M Y H:i:s O').'</lastBuildDate>'."\r\n";
//Query the database for the latest Published doc
$query = "SELECT `DOC-ID`, `TITLE`,`ABSTRACT`,`PUB-DATE` FROM `index` WHERE `state_id` in (:state_id) order by `PUB-DATE` desc limit :limit_no";
try {
       $stmt = $pdo->prepare($query);
       $stmt->bindValue('state_id',14);
       $stmt->bindValue('limit_no',15);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   }catch (PDOException $pe){
       error_log("Error processing : rfcrss.php", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        list($rfc_name,$rfc_number,$length) = split_rfc($row['DOC-ID']);
        $formatted_rfc_number = sprintf('%s %d',$rfc_name,$rfc_number);
	$row['DOC-ID'] = strtolower($row['DOC-ID']);

	$build .= "\t\t\t".'<item>
		<title>'.htmlentities($formatted_rfc_number) .': '. htmlentities($row['TITLE']).'</title>
		<link>https://www.rfc-editor.org/info/'. $row['DOC-ID'] .'</link>
		<description>'.htmlentities($row['ABSTRACT']).'</description>
	</item>'."\r\n";

}

$build .= "\t\t".'</channel>
</rss>';

print $build;

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
