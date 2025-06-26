<?php

/***********************************************************************************************************/
/*Script Name : op_notes_queue.php                                                                         */
/*Description : The script is used to add/edit the operational notes for documents in queue.               */
/* February 2023 : Created the script - PN                                                                 */
/* February 2023 : Modified the sort order and plcaeholder text- PN                                        */
/***********************************************************************************************************/
session_start();
include('header.php');
include('db_connect.php');
include('state_history_lib.php');
include('cluster_support_lib.php');

$debug760 = false;
#$debug760 = true;

rfc_header("OP Notes Queue");
$orderby;
$sort;

#$posted_op_notes = $_POST['op_notes'] ;
#print "The post value is $posted_op_notes";

#print_r($_POST);
$new_notes = "";

if ( $_POST['save'] == "Save notes" ){
        $new_notes = $_POST['op_notes'];
        $old_notes = $_POST['old_notes'];      
        if ($old_notes != $new_notes) {
            update_notes($_POST['internal_key'],$new_notes);
 #                     print "Notes updated";
        }
}



       $orderby = "ORDER BY generation_number, WEEKS_IN_Q DESC";

print "<table>\n";
print "<tr>\n";
print "<th>CE/PE/RE/PUB</th>";
print "<th>Curr state</a></th>";
print "<th>Weeks in state</a></th>";
print "<th>Weeks in q</a></th>";
print "<th>RET</a></th>";
print "<th>File type</th>";
print "<th>Draft name</a></th>";
print "<th>Cluster</a></th>";
print "<th>Pages</a></th>";
print "<th>Submitted</a></th>";
print "</tr>\n";


$report_order = array('EDIT','RFC-EDITOR','AUTH','AUTH48','AUTH48-DONE','REF','NR','ISR','TO','ISR-AUTH','IESG','IANA','TI','MISSREF');



/*Query is using the state look up table states to get the state_id */
# NOTE: we're selecting page-count twice, once to act as a placeholder column for cluster id, which is not
# available from this sql query

$query = 'SELECT  CONCAT(c.initials,"/",p.initials,"/",r.initials,"/",pub.initials)as "CE/PE/RE/PUB",
        CONCAT( s.state_name,CASE WHEN i.iana_flag = "1"THEN "*A"ELSE ""END ,CASE WHEN i.ref_flag = "1"THEN "*R"ELSE ""END ) AS "CURR_STATE",
        i.internal_key,
        ROUND( (TO_DAYS( CURDATE( ) ) - ( TO_DAYS( i.date_received ) ) ) /7, 1) AS "WEEKS_IN_Q",
	i.xml_file AS "FILE_TYPE",
        i.draft AS "DRAFT_NAME",
        i.`page-count` AS "CLUSTER",
        i.`page-count` AS "PAGES",
        i.date_received AS "SUBMITTED",
        i.generation_number
        FROM `primary_ed` p, `copy_ed` c, `rfced` r, `pub_ed` pub,  `index` i, `states` s
        WHERE i.draft IS NOT NULL
        AND i.internal_key = p.doc_key
        AND i.internal_key = c.doc_key
	AND i.internal_key = r.doc_key
        AND i.internal_key = pub.doc_key
        AND i.state_id = s.state_id
        AND i.state_id != :state_id_14
        AND i.state_id != :state_id_3
        AND i.state_id != :state_id_19';
       

$query .= " $orderby";
$stmt = $pdo->prepare($query);
$stmt->bindValue('state_id_14',14);
$stmt->bindValue('state_id_3',3);
$stmt->bindValue('state_id_19',19);
$stmt->execute();
#print "$query\n";
$count = $stmt->rowCount();
# display title
echo "<h3 class=\"simple_center\">Current Queue with Operational Notes</h3>";

# Pull up the query and just implode it into a table.
if ($count > 0 ) {
    print "<p>Found " .htmlspecialchars($count) .  " records</p>\n";

    $i=0;
    $result_array = array();
    $wks = array();
    $cl_array = array();
    # build result array
    while ( $line = $stmt->fetch(PDO::FETCH_NUM)) {
        array_push($result_array, $line);
    }
    if ($debug760 === true) {
	print("<h4>line</h4><pre>");
	print_r($result_array); print("\n");
	print("</pre>");
    }
#print_r($result_array);
  if (!(isset($_GET['sortkey']))){ // CASE 1: Initial Display of the queue
    foreach ($report_order as $state) { //Need to maintain default report order if any sorting is not applied by the user
	foreach ($result_array as $line) {
            $pattern = "/^$state(\*A|\*R)*$/";
            if (preg_match($pattern, $line[1])) {
		# lookup cluster 
		$cid = get_cluster_id($pdo,$line[5]);
                if ($cid != "0") {
                    $line[6] = $cid;
                    $line[6]= '<a href="/cluster_info.php?cid='.htmlspecialchars($line[6]).'">'.htmlspecialchars($line[6]).'</a>';
                } else {
                    $line[6] = "";
                }
                #Get the RET time 
                $line[10] = get_rfced_time($line[2],$line[5]);
              
                #Get int_key
                $internal_key = $line[2];  
		#Calculate the Weeks in state data from state history table depending upon the passed internal_key ,state name and draft value
		$line[2] = get_weeks_in_state($line[1],$line[2],$line[5]);

                $line[4] = get_file_type($line[4]);
                #Add the generation number to "CURRENT STATE" for display
		if ($line[9] == "1"){
                    $line[1] = $line[1]."(1G)";
                }else if ($line[9] == "2"){
                    $line[1] = $line[1]."(2G)";
                 }else if ($line[9] == "3"){
                    $line[1] = $line[1]."(3G)";
                  }
                # make link of draft name
                $save_draft = $line[5];
                $line[5] = "<a href=\"edit_draft.php?draft=$line[5]\">$line[5]</a>";
                #Get the operationa;_notes from the op_table

		$class = ($i++ % 2) ? "odd" : "even";
		print "<tr class=\"$class\"><td>\n";
	                print "$line[0]</td>";
                        print "<td>$line[1]</td>";
                        print "<td>$line[2]</td>";
                        print "<td>$line[3]</td>";
                        print "<td>$line[10]</td>";
                        print "<td>$line[4]</td>";
                        print "<td>$line[5]</td>";
                        print "<td>$line[6]</td>";
                        print "<td>$line[7]</td>";
	                print "<td>$line[8]";
		print "</td></tr>\n";
                #Get the operationa;_notes from the op_table

                $op_notes = get_op_notes($internal_key);
                #print "Op notes from db $op_notes";
                #$op_notes = "TEST NOTES";

              #print_r($line); 
                print "<form action=\"op_notes_queue.php\" method=\"post\">";
                print "<tr><td><b>Operational Notes :</b></td> <td colspan=\"8\"><textarea cols=\"100\" rows=\"5\" name=\"op_notes\" placeholder=\"Enter notes for $save_draft\">$op_notes</textarea></td><td><button type=\"submit\" name=\"save\" value=\"Save notes\">Save Notes</td></tr>\n";

                print "<input type=\"hidden\" name=\"old_notes\" value=\"$op_notes\" />";
                print "<input type=\"hidden\" name=\"internal_key\" value=$internal_key />";

                print "</form>";
               }
	}
    }//End of foreach report_order
 }
   print "</table>\n";
}//End of if count > 0 



/**************************************************************************************************************************************/
/*Function   : get_op_notes                                                                                                           */
/*Description : This function featches the notes value from the op_notes tables based on the passed internal_key value for the draft  */
/**************************************************************************************************************************************/
function get_op_notes($internal_key){
global $debug760;
global $pdo;
$db_op_notes = "";
 #print "Are we here : $internal_key"; 
   $op_notes_query = "SELECT `op_notes` from `op_notes` WHERE `op_notes`.op_int_key = :internal_key";

   try {
       $stmt = $pdo->prepare($op_notes_query);
       $stmt->bindParam('internal_key',$internal_key);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : get_op_notes", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   if ($debug_760 === true) {
      print "My query is $query"."<br>";
   }
  
    
   while ($op_notes_line = $stmt->fetch(PDO::FETCH_ASSOC)){
       $db_op_notes = $op_notes_line['op_notes'];
   }   

   if ($debug760 === true) {
       print("<pre>get_op_notes: $db_op_notes");
       print("\n</pre>");
   }   
   
   return $db_op_notes;


}

/**************************************************************************************************************************************/
/*Function   : get_op_notes                                                                                                           */
/*Description : This function featches the notes value from the op_notes tables based on the passed internal_key value for the draft  */
/**************************************************************************************************************************************/
function update_notes($internal_key,$new_notes){
global $debug760;
global $pdo;

$new_notes = trim($new_notes);

$select_notes_query = "SELECT `op_notes` from `op_notes` WHERE `op_notes`.op_int_key = :internal_key";
try {
   $stmt = $pdo->prepare($select_notes_query);
   $stmt->bindParam('internal_key',$internal_key);
   $stmt->execute();
   $num_of_notes_rows = $stmt->rowCount();
} catch (PDOException $pe){
   error_log("Error processing : update_notes select query", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
}

if ($num_of_notes_rows > 0 ) { #Go for update op_notes 
   $update_notes = "UPDATE op_notes SET op_notes=:new_notes WHERE op_int_key=:internal_key";
   if ($debug760 === TRUE) {
       print("\n<!-- update_notes: update='$update_notes'\n-->\n");
   }
   try {
       $stmt = $pdo->prepare($update_notes);
       $stmt->bindParam('new_notes',$new_notes);
       $stmt->bindParam('internal_key',$internal_key);
       $stmt->execute();
       $update_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : update_notes update query", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
   if ($update_rows > 0) {
       if ($debug760 === TRUE){
         print("\n<!-- Notes Updated in op_notes table \n-->\n");
       }
   } 
} else { #Go for insert to op_notes
   $insert_notes = "INSERT `op_notes` (op_int_key,op_notes) VALUES (:internal_key,:new_notes)";
    try {
       $stmt = $pdo->prepare($insert_notes);
       $stmt->bindParam('internal_key',$internal_key);
       $stmt->bindParam('new_notes',$new_notes);
       $stmt->execute();
       $insert_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : update_notes insert query", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
}

}


/**************************************************************************************************************************************/
/*Function     : get_file_type                                                                                                        */
/*Descriptioin : This function takes 1 inputs, and decides the file type depending upon the i/p value                                 */
/**************************************************************************************************************************************/

function get_file_type($in_value){
global $debug760;

$out_value = "";

   switch($in_value){
      case 0:
              $out_value = '';
              break;


      case 1:
              $out_value = 'X';
              break;


      case 2 : 
              $out_value = 'N';
              break;


      case 3 : 
             $out_value = 'X?';
              break;


      case 4 :
              $out_value = 'AA';
              break;


      case 5 :
              $out_value = 'v3';
              break;

      case 6 :
              $out_value = 'NO';
              break;

      case 7 :
              $out_value = 'in_prog';
              break;
      
      case 8 : 
              $out_value = 'id2xml';
              break;
      
      default :
              $out_value = '';
              break;   

   }

  return $out_value;
}



rfc_footer();

?>
