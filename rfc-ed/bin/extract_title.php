#!/usr/bin/php
<?php
/***************************************************************************************/
/*Script Name : extract_title.php                                                      */
/*Description : The script gets input values as XML file - rfcxxxx.xml file and        */
/*              rfc number xxxx. Then gets the title value using simplexml_load_file   */
/*              gets the desired title value from it. Next arranges the string in      */
/*              the proper format applicable to MySQL database and HTML pages by adding*/
/*              the line brakes and paragraphs at the proper position. Finally the     */
/*              script updates the title value in the database.                        */
/*              Result can be tested by checking the info page                         */
/*March 2019 : Created the script - PN                                                 */
/*October 2019 : Modified the base dir from /in-notes/ to /in-notes/authors - PN      */
/*October 2019 : Modified to use dbi.php instead if db.php - PN                       */
/*December 2019 : Modified to remove the 69 limit for a line in the title  - PN       */
/*April 2020 : * Fixing the script for UTF-8 character "&nbsy;", "&nbsp;" and         */
/*               "&wj;" in the title - PN                                             */
/*February 2021 : Modified the split character for title paragraph - PN               */
/***************************************************************************************/

include('dbi.php');

$base_dir = '/a/inc-work/';
// check for all required arguments
// first argument is always name of script!
if ($argc != 3) {
    die("Usage: extract_title.php <xml-file-name>\n");
}



$debug_element = false;
$title_element = "";
$rfc_number = "";

// remove first argument
array_shift($argv);

// get and use remaining arguments
$xml_in_file = $argv[0];
$rfc_number = $argv[1];
$file_path = $base_dir.$xml_in_file;



if ( $debug_element === true) {
    print "**********The input values are for extract_title.php are************\n";
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
    
   /*Getting the title element from the XML file*/

    foreach ($xml->front->title as $t) {
        $t = (string)$t;
        $title_element .= $t."TTTT";
    }
}else {
      print "Error in XML file loading.See log for details.";
}

$title_element = trim($title_element);

if ( $debug_element === true) {
    print "************The title is as following********\n";
    print $title_element;
    print "\n\n************The title ending         ********\n";
}


/*Now divide the title into lines and again form the title by removing white spaces*/
$new_line = "";
$new_title = "";

foreach(preg_split("/((\r?\n)|(\r\n?))/", $title_element) as $line){
    $line = trim($line);
    $new_line = $line;
    $new_title .= " ".$new_line;
}

/*Split the title into paragraphs now using the filler SECT*/
$title_paragraph = preg_split('/TTTT/',$new_title);
array_pop($title_paragraph);

/*Get the title line display*/
$title_count = count($title_paragraph);
$flat_string = "";

for($row= 0; $row < $title_count; $row++){
     $title_paragraph[$row]= ltrim($title_paragraph[$row]);
     #$newtext = wordwrap($title_paragraph[$row], 69, "\n", true);//Initial requirement was to have line break at 69 so still keeping the code.
     #$flat_string .= $newtext ."\r\n\r\n"; 
     $flat_string .= $title_paragraph[$row]."\r\n\r\n"; 
}

$mod_flat_string = rtrim($flat_string);

if ( $debug_element === true) {
print "\n***************The final formatted title*************/"."\n";
print $mod_flat_string;
print "\n***************The final formatted title*************/"."\n";
}

//Add the modified title string to database

if ($mod_flat_string){
    $title_status = add_title($mod_flat_string,$rfc_number);
     if ($title_status){
       print "\n Updated the title for RFC$rfc_number in the database\n";
     }else {
       print("\n <p class=\"error\">Failed updating the title for RFC$rfc_number. See log for details.</p>\n");
     }
}


/*************************************************************************/
/*Function : add_title adds the title value for */
/*************************************************************************/
function add_title($in_title,$rfc_in){

global $debug_element;

 /* Connecting, selecting database */


 $status = open_db();

 $in_title = mysqli_real_escape_string($status,$in_title);
 $query =   "UPDATE `index` SET `title` = '$in_title' WHERE `DOC-ID`= 'RFC";
 $query .= sprintf("%04d",$rfc_in) . "'";
   



     if ( $debug_element === true) {
                        print("*****add_title*****\n");
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
