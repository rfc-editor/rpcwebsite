#!/usr/bin/php
<?php
/***************************************************************************************/
/*Script Name : extract_abstract.php                                                   */
/*Description : The script gets input values as XML file - rfcxxxx.xml file and        */
/*              rfc number xxxx. Then gets the abstract value using simplexml_load_file*/
/*              gets the desired abstract value from it. Next arranges the string in   */
/*              the proper format applicable to MySQL database and HTML pages by adding*/
/*              the line brakes and paragraphs at the proper position. Finally the     */
/*              script updates the abstract value in the database.                     */
/*              Result can be tested by checking the info page                         */
/*January 2019 : Created the script - PN                                              */
/*October 2019 : Modified the base dir from /in-notes/ to /in-notes/authors - PN      */
/*October 2019 : Modified to use dbi.php instead if db.php - PN                       */
/*April 2020 : * Fixing the script for UTF-8 character "&nbsy;", "&nbsp;" and         */
/*               "&wj;" in the abstract - PN                                          */
/**************************************************************************************/

include('dbi.php');

$base_dir = '/a/inc-work/';
// check for all required arguments
// first argument is always name of script!
if ($argc != 3) {
    die("Usage: extract_abstract.php <xml-file-name>\n");
}



$debug_element = false;
$abstract_element = "";
$rfc_number = "";

// remove first argument
array_shift($argv);

// get and use remaining arguments
$xml_in_file = $argv[0];
$rfc_number = $argv[1];
$file_path = $base_dir.$xml_in_file;



if ( $debug_element === true) {
    print "**********The input values are for extract_abstract.php are************\n";
    print "File name is $xml_in_file"."\n";
    print "File location is $file_path"."\n";
    print "The RFC Number is $rfc_number"."\n";
    print "***********************************************************************\n";
}

//Get teh HTML entities in the title 
//From xml2rfc the defines entities are &nbsp; &nbhy; and &wj;
//Replaced it with the actual values we do not want UTF-8 values for 
//entities in the field title inside database.


if ($flat_string = file_get_contents($file_path)){

   $search = array('&nbsp;','&nbhy;','&wj;');
   $replace = array(' ', '-' ,'');
   $clean_string = str_replace($search,$replace,$flat_string);

   /*Load the XML string*/
   $xml = simplexml_load_string($clean_string);
    
/*Getting the abtract element from the XML file*/

    foreach ($xml->front->abstract->t as $t) {
        $t = (string)$t;
        $abstract_element .= $t."SECT";
    }
}else {
      print "Error in XML file loading.See log for details.";
}

if ( $debug_element === true) {
    print "************The abstarct is as following********\n";
    print $abstract_element;
    print "\n\n************The abstarct ending         ********\n";
}

/*Now divide the abstract into lines and again form the abstract by removing white spaces*/
$new_line = "";
$new_abstract = "";

foreach(preg_split("/((\r?\n)|(\r\n?))/", $abstract_element) as $line){
    $line = trim($line);
    $new_line = $line; 
    $new_abstract .= " ".$new_line;
} 

if ( $debug_element === true) {
    print "\n************The new abstarct is as following********\n";
    print $new_abstract;
    print "\nn************The new abstarct ending         ********\n";
}

/*Split the abstract into paragraphs now using the filler SECT*/
$abstract_paragraph = preg_split('/SECT/',$new_abstract);

array_pop($abstract_paragraph);

/*Get the paragraphed abstract into 69 character line display*/
$abstract_count = count($abstract_paragraph);
$flat_string = "";

for($row= 0; $row < $abstract_count; $row++){
     $abstract_paragraph[$row]= ltrim($abstract_paragraph[$row]);
     $newtext = wordwrap($abstract_paragraph[$row], 69, "\n", true);
     $flat_string .= $newtext ."\r\n\r\n"; 
}

$mod_flat_string = rtrim($flat_string);
if ( $debug_element === true) {
print "\n***************The final formatted abstarct*************/"."\n";
print $mod_flat_string;
print "\n***************The final formatted abstarct*************/"."\n";
}

if ($mod_flat_string){
    $abstract_status = add_abstract($mod_flat_string,$rfc_number);
     if ($abstract_status){
       print "\n Updated the abstract for RFC$rfc_number in the database\n";
     }else {
       print("\n <p class=\"error\">Failed updating the abstract for RFC$rfc_number. See log for details.</p>\n");
     }
}


/*************************************************************************/
/*Function : add_abstract adds the abtract value for */
/*************************************************************************/
function add_abstract($in_abstract,$rfc_in){

global $debug_element;

 /* Connecting, selecting database */

 $status = open_db();

 $in_abstract = mysqli_real_escape_string($status,$in_abstract);
   
 $query =   "UPDATE `index` SET `abstract` = '$in_abstract' WHERE `DOC-ID`= 'RFC";
 $query .= sprintf("%04d",$rfc_in) . "'";


     if ( $debug_element === true) {
                        print("*****add_abstract*****\n");
                        print("Query\n");
                        print_r($query);

     }


#     print "Status is $status";
     if (is_string($status)) {
          // problem opening connection, abort now!
          return $status;
     }

    if (mysqli_query($status,$query)){
        $update_status = 1;
     } else{
        $update_status = 0;
     }

     close_db();

     return $update_status;
  
}


?>
