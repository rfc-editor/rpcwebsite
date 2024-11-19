#!/usr/bin/php
<?php
/***************************************************************************************/
/*Script Name : get_index_doc.php                                                      */
/*Description : The script gets the list of published RFC doc-ids from the database    */
/*              table index. This list is used by index_json_creator.php as i/p to     */
/*              generate json files for respective doc-ids.                            */
/*June 2019   : Created the script - PN                                                */
/***************************************************************************************/
include('dbi.php');
$debug_element = false;

#Get the doc-ids from index table
$index_doc_array = get_index_data();
#print_r($index_doc_array);

$result_array = array();
$errata_url = "";

#Get the actual data for every doc-id
$doc_count=count($index_doc_array);

for ($i=0;$i<$doc_count;$i++){
     $doc_id = $index_doc_array[$i][0];
     $doc_id = strtolower($doc_id);
    print "$doc_id \n";
} 
/*************************************************************************/
/*Function : get_index_data gets the valid doc-ids from the index table */
/*************************************************************************/
function get_index_data(){
    global $debug_element;
    $index_doc_id_data = array();
    $query = 'SELECT i.`doc-id` FROM `index` i WHERE i.`state_id` = "14" AND i.`doc-id` LIKE "RFC%" ORDER BY i.`doc-id`';
    $db_link = open_db();
    if (($result = mysqli_query($db_link,$query))){
        while ( $line = mysqli_fetch_row($result)) {
              array_push($index_doc_id_data ,$line);  
         #      $index_doc_id_data[] = $line;
         }
    }

    mysqli_free_result($result);
    close_db();
    return $index_doc_id_data;

}



