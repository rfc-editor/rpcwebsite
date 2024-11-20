#!/usr/bin/php
<?php
/***************************************************************************************/
/*Script Name : index_json_creator.php                                                 */
/*Description : The script generates the .json file for the i/p doc-id with the desired*/
/*              format such as multivalue fields like authors, obsoletes, obsoleted_by */
/*              updates, updated_by, see_also, format as seperate json arrays. Also    */
/*              all the field names are made lower case with "_" instead of "-"        */
/*              The json file location can be specified while calling this script.     */
/* Note       : If this file is modified, please check whether metadata.js requires    */
/*              modification as well.(The JSON files created by index_json_creator.php */
/*              are used as input for metadata.js and metadata.min.js.)                */
/*June 2019   : Created the script - PN                                                */
/*September 2019 : Modified the Pub Date format from yyyy-mm-dd to Month YYYY - PN     */
/*October 2019 : Modified the format so that v3PDF from database is shown as PDF -PN   */
/*October 2019 : Modified the script to handler following things  - PN                 */
/*               1. Removed array display for each json file                           */
/*               2. Make consistent use of empty array for multivalue fields           */
/*April 2021   : Corrected the Pub date for "Not Issued" RFCs - PN                     */
/***************************************************************************************/
include('dbi.php');
#include('dbprod.php');
$debug_element = false;


// check for all required arguments
// first argument is always name of script!
if ($argc != 2) {
    die("Usage: index_json_creator.php <RFCXXXX>\n");
}


// remove first argument
array_shift($argv);

// get and use remaining arguments
$in_doc_id = $argv[0];

$metadata  = array();
$metadata_json = array();

  $metadata = get_index_metadata($in_doc_id); 


  $metadata_json['draft'] = $metadata[0]['draft'];
  $metadata_json['doc_id'] = $metadata[0]['doc_id'];
  $metadata_json['title'] = $metadata[0]['title'];
 
  $metadata[0]['authors']= preg_replace('/, Ed\./', '; Ed.', $metadata[0]['authors']);
  $author_array = get_author_array($metadata[0]['authors']); 
  $metadata_json['authors'] = $author_array;

  $format_array = get_format_array($metadata[0]['format']);
  $metadata_json['format'] = $format_array;


  $metadata_json['page_count'] = $metadata[0]['page_count'];
  $metadata_json['pub_status'] = $metadata[0]['pub_status'];
  $metadata_json['status'] = $metadata[0]['status'];
  $metadata_json['source'] = $metadata[0]['source'];
  $metadata_json['abstract'] = $metadata[0]['abstract'];
  

  $pub_date_string = get_pub_date($metadata[0]['pub_date']);
  $metadata_json['pub_date'] = $pub_date_string;

  $keywords_array = get_data_array($metadata[0]['keywords']);
  $metadata_json['keywords']=$keywords_array;

  $obsoletes_array = get_data_array($metadata[0]['obsoletes']);
  $metadata_json['obsoletes'] = $obsoletes_array;
 
  $obsoleted_by_array = get_data_array($metadata[0]['obsoleted_by']);
  $metadata_json['obsoleted_by'] = $obsoleted_by_array;
 
  $updates_array = get_data_array($metadata[0]['updates']);
  $metadata_json['updates'] = $updates_array;

  $updated_by_array = get_data_array($metadata[0]['updated_by']);
  $metadata_json['updated_by'] = $updated_by_array;

  $see_also_array = get_data_array($metadata[0]['see_also']);
  $metadata_json['see_also'] = $see_also_array;

  $metadata_json['doi'] = $metadata[0]['doi'];

  $errata_count = get_errata_count($in_doc_id);
  if ($errata_count > 0) 
     {  
        $errata_url = 'https://www.rfc-editor.org/errata/'.strtolower($in_doc_id);
        $metadata_json['errata_url'] = $errata_url;
     } else {
        $metadata_json['errata_url'] = null;
     }

if ($debug_element === true) {
   print_r($metadata_json);
}

print json_encode($metadata_json);

/***************************************************************************************/
/*Function : get_author_array converts the i/p comma seperated author string to an array */
/***************************************************************************************/
function get_author_array($in_author_string){

    global $debug_element;
        $authors = explode(',', $in_author_string);
        foreach ($authors as $author) {
            $author = trim($author);
            $author = preg_replace('/; Ed\./', ', Ed.', $author);
            $out_author_array[] = $author;
        }
        if ($debug_element === true) {
            print "/******************The array for Author $in_author_string is************************/\n\n";
            print_r ($out_author_array);
        }
    return $out_author_array;
}

/***************************************************************************************/
/*Function : get_format_array if the i/p format string contains format v3PDF then the  */
/*           function replaces ut to PDF                                               */
/***************************************************************************************/
function get_format_array($in_format_string){

    global $debug_element;
    $out_format_string = "";
        $formats = array_map('trim', explode(',',$in_format_string));
        $num_format=count($formats);
        $count=0;
        foreach ($formats as $format) {
            $format = trim($format);
            if ($format == 'v3PDF'){
               $format = 'PDF';
            }
            $count++;
            if ($count < $num_format) {
               $out_format_string .= $format.",";
            }else {
               $out_format_string .= $format;
            }
        }
        if ($debug_element === true) {
            print "/******************The string for Format  $in_format_string is************************/\n\n";
            print " $out_format_string ";
        }
       $out_format_array  = array_map('trim', explode(',',$out_format_string));
    
        if ($debug_element === true) {
            print "/******************The array for Format $in_format_string is************************/\n\n";
            print_r ($out_format_array);
        }
    return $out_format_array;
}
/********************************************************************************************/
/*Function : get_pub_date converts the i/p pub_dtae in yyyy-mm-dd to Month Year format*/
/********************************************************************************************/
function get_pub_date($in_pub_date_string){

    global $debug_element;
    if (($in_pub_date_string != '') || (!empty($in_pub_date_string))) {
     list($pub_year,$pub_month,$pub_day) = explode('-', $in_pub_date_string);
       if (($pub_month == '04') && ($pub_day == '01')) {
          $out_pub_date = date("1 F Y" ,strtotime($in_pub_date_string));
       } else {
          $out_pub_date = date("F Y" ,strtotime($in_pub_date_string));
       }
    } else {
        $out_pub_date = NULL;
    }
    if ($debug_element === true) {
            print "/******************The Publication date input is  $in_pub_date_string is************************/\n\n";
            print($out_pub_date);
     }
    return $out_pub_date;
}
/********************************************************************************/
/*Function : get_data_array converts the i/p comma seperated string to an array */
/********************************************************************************/
function get_data_array($in_string){

    global $debug_element;
    $searchString = ',';

    if (($in_string != null) || $in_string != ""){
    if( strpos($in_string, $searchString) !== false ) {
       $out_data_array  = array_map('trim', explode(',',$in_string));
   #     $out_data_array  = explode(',',$in_string);
    }else {
        $out_data_array = array($in_string);
    }
    } else {
      $out_data_array = array();
    }
    if ($debug_element === true) {
        print "/******************The array for $in_string is************************/\n\n";
        print_r ($out_data_array);
    }
    return $out_data_array;
}

/*************************************************************************/
/*Function : get_index_data gets the valid doc-ids from the index table */
/*************************************************************************/
function get_index_metadata($in_doc_id){
    global $debug_element;
     $metadata_array = array();

    $index_metadata_query = "SELECT `DRAFT` as `draft`, `DOC-ID` as `doc_id`, `TITLE` as `title`, `AUTHORS` as `authors` , `FORMAT` as `format` , `PAGE-COUNT` as `page_count`, `PUB-STATUS` as `pub_status` , `STATUS` as `status` , `SOURCE` as `source` , `ABSTRACT` as `abstract`, `PUB-DATE` as `pub_date` , `KEYWORDS` as `keywords`, `OBSOLETES` as `obsoletes`, `OBSOLETED-BY` as `obsoleted_by`, `UPDATES` as `updates`, `UPDATED-BY` as `updated_by`, `SEE-ALSO` as `see_also`,`DOI` as `doi` FROM `index` where `doc-id` = '$in_doc_id'";

    $db_link = open_db();
    $metadata_result = mysqli_query($db_link,$index_metadata_query);

    $row = mysqli_fetch_assoc($metadata_result);
    $metadata_array[]=$row;

    if ($debug_element === true) {
          print "/******************The index metadata for $in_doc_id ************************/\n\n";
          print_r ($metadata_array);
    }
 
    close_db();
return $metadata_array;
}     
    
/*************************************************************************/
/*Function : get_errata_data gets the valid doc-ids from the index table */
/*************************************************************************/
function get_errata_count($in_doc_id){
    global $debug_element;
    $errata_array = array();
    $errata_count = 0;    

    $errata_query = "SELECT `errata_id` FROM `errata` where `doc-id` = '$in_doc_id'";

    
    $db_link = open_db();
    $errata_result = mysqli_query($db_link,$errata_query);
    $errata_count = mysqli_num_rows($errata_result);
 
    if ($debug_element === true) {
        print "/******************The errata count for $in_doc_id ************************/\n\n";
        print_r ($errata_count);
    }
    
    close_db();
    return $errata_count;
}     
 

?>
