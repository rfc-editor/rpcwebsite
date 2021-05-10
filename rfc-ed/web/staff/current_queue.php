<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
/***********************************************************************************************************/
/*Script Name : current_queue.php                                                                          */
/*Description : The script shows the documents present in the current queue with the relevant data such as */
/*              Editor details, Curr State, Weeks in state, Weeks in queue, RET, XML File, Draft Name ,    */
/*              Cluster, Pages, Submitted Date                                                             */
/*May 2017    : Modified to add the file type changes  - PN                                                */
/*June 2017   : Added one more criteria for file type with no.6 and value No file. Which will display as   */
/*              NO  - PN                                                                                   */
/*April 2017   : Added more file type criteria :id2xml in progress (in_prog),id2xml complete (id2xml)-PN   */
/*Dec 2018     : Added the tool adjustment state TI -PN                                                    */
/* November 2020 : Modified the script to use PDO prepared statements - PN                                 */
/***********************************************************************************************************/
# $Id: current_queue.php,v 2.17 2021/05/10 21:47:30 priyanka Exp $
session_start();
include('header.php');
include('db_connect.php');
include('state_history_lib.php');
include('cluster_support_lib.php');

$debug760 = false;
#$debug760 = true;

rfc_header("current queue");
$orderby;
$sort;


if (isset($_GET['sortkey'])){
        $sortkey = $_GET['sortkey'];
        $orderby = getorderby($sortkey);
}
else {
       $orderby = "ORDER BY generation_number, WEEKS_IN_Q";
}



function getorderby($sortkey){

  switch($sortkey)
  {
        case 1:
                $orderby = "ORDER BY p.initials";
                break;
        case 2:
                $orderby = "ORDER BY generation_number, CURR_STATE";
                break;
        case 4:
                $orderby = "ORDER BY WEEKS_IN_Q";
                break;
        case 5:
                $orderby = "ORDER BY DRAFT_NAME";
                break;
        case 7:
                $orderby = "ORDER BY PAGES+0";
                break;
        case 8:
                $orderby = "ORDER BY SUBMITTED";
                break;


        default:
                break;

  }
  return $orderby;

}


if (isset($_GET['sorting'])){
    $sorting = $_GET['sorting'];
}
else {
    $sorting = 'ASC';
}


switch($sorting){
 case "ASC":
         $sort = 'DESC';
         break;
 case "DESC":
         $sort = 'ASC';
         break;
 default:
         break;
}


print "<table>\n";
print "<tr>\n";
print "<th>CE/PE/RE/PUB</th>";
print "<th><a href=\"current_queue.php?sortkey=2&sorting=$sort\">Curr state</a></th>";
print "<th><a href=\"current_queue.php?sortkey=3&sorting=$sort\">Weeks in state</a></th>";
print "<th><a href=\"current_queue.php?sortkey=4&sorting=$sort\">Weeks in q</a></th>";
print "<th><a href=\"current_queue.php?sortkey=9&sorting=$sort\">RET</a></th>";
print "<th>File type</th>";
print "<th><a href=\"current_queue.php?sortkey=5&sorting=$sort\">Draft name</a></th>";
print "<th> <a href=\"current_queue.php?sortkey=6&sorting=$sort\">Cluster</a></th>";
print "<th><a href=\"current_queue.php?sortkey=7&sorting=$sort\">Pages</a></th>";
print "<th><a href=\"current_queue.php?sortkey=8&sorting=$sort\">Submitted</a></th>";
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
       


        if (($sortkey != 3) and ($sortkey != 6) and ($sortkey != 9)){
                $query .= " $orderby $sort";
        } else {
                $query .= " $orderby";

        }

$stmt = $pdo->prepare($query);
$stmt->bindValue('state_id_14',14);
$stmt->bindValue('state_id_3',3);
$stmt->bindValue('state_id_19',19);
$stmt->execute();
#print "$query\n";
$count = $stmt->rowCount();
# display title
echo "<h3 class=\"simple_center\">Current Queue</h3>";

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
                $line[5] = "<a href=\"edit_draft.php?draft=$line[5]\">$line[5]</a>";
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
            }
	}
    }//End of foreach report_order
 } else {
         if (($_GET['sortkey'] != 3) and ($_GET['sortkey'] != 6) and ($_GET['sortkey'] != 9)) {// CASE 2: When sorting is based on one of the values from Database directly.
	      foreach ($result_array as $line) {//When sorting is applied no need to maintan the order
		$cid = get_cluster_id($pdo,$line[5]);
                if ($cid != "0") {
                    $line[6] = $cid;
                    $line[6]= '<a href="/cluster_info.php?cid='.htmlspecialchars($line[6]).'">'.htmlspecialchars($line[6]).'</a>';
                } else {
                    $line[6] = "";
                }
                #Get the RET time 
                $line[10] = get_rfced_time($line[2],$line[5]);
    
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
                $line[5] = "<a href=\"edit_draft.php?draft=$line[5]\">$line[5]</a>";
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
              }
	 }//End if CASE 2
	 if ($_GET['sortkey'] == 3) // CASE 3: When sorting is based on Weeks in state value.
   	 {
	      foreach ($result_array as $line) {//When sorting is applied no need to maintan the order
			$cid = get_cluster_id($pdo,$line[5]);
	                if ($cid != "0") {
	                    $line[6] = $cid;
                            $line[6]= '<a href="/cluster_info.php?cid='.htmlspecialchars($line[6]).'">'.htmlspecialchars($line[6]).'</a>';
	                } else {
	                    $line[6] = "";
        	        }
                        #Get the RET time
                        $line[10] = get_rfced_time($line[2],$line[5]);

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
	                # Push the array to weeks in state array that cab be used for sort input
	                # We need to sort this array as week_in_state value for that use function subval
	                array_push($wks, $line);
             }//End of foreach
	      $new_wks = subval_sort($wks,'2',$sort);
              foreach ($new_wks as $sort_line){
	                # make link of draft name
	                $sort_line[5] = "<a href=\"edit_draft.php?draft=$sort_line[5]\">$sort_line[5]</a>";
			$class = ($i++ % 2) ? "odd" : "even";
			print "<tr class=\"$class\"><td>\n";
	                print "$sort_line[0]</td>";
                        print "<td>$sort_line[1]</td>";
                        print "<td>$sort_line[2]</td>";
                        print "<td>$sort_line[3]</td>";
                        print "<td>$sort_line[10]</td>";
                        print "<td>$sort_line[4]</td>";
                        print "<td>$sort_line[5]</td>";
                        print "<td>$sort_line[6]</td>";
	                print "<td>$sort_line[7]</td>";
                        print "<td>$sort_line[8]";

			print "</td></tr>\n";
              }//End of foreach 
   	 }//End if CASE 3
   	 if ($_GET['sortkey'] == 6) // CASE 4: When sorting is based on Cluster value.
   	 {
	      foreach ($result_array as $line) {//When sorting is applied no need to maintan the order
			$cid = get_cluster_id($pdo,$line[5]);
	                if ($cid != "0") {
	                    $line[6] = $cid;
	                } else {
	                    $line[6] = "";
        	        }
                        /*We need to sort cluster_id further so remove 'C' from it*/  
                        $line[6] = substr($line[6],1);
                        #Get the RET time 
                        $line[10] = get_rfced_time($line[2],$line[5]);
		
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
	                # Push the array to weeks in state array that cab be used for sort input
	                # We need to sort this array as week_in_state value for that use function subval
	                array_push($cl_array, $line);
             }//End of foreach
	      $new_cl_array = subval_sort($cl_array,'6',$sort);
              foreach ($new_cl_array as $sort_line){
	                # make link of draft name
	                $sort_line[5] = "<a href=\"edit_draft.php?draft=$sort_line[5]\">$sort_line[5]</a>";
			$class = ($i++ % 2) ? "odd" : "even";
			print "<tr class=\"$class\"><td>\n";
	                print "$sort_line[0]</td>";
                        print "<td>$sort_line[1]</td>";
                        print "<td>$sort_line[2]</td>";
                        print "<td>$sort_line[3]</td>";
                        print "<td>$sort_line[10]</td>";
                        print "<td>$sort_line[4]</td>";
	                print "<td>$sort_line[5]</td>";
                        if ($sort_line[6]){  
                                $sort_line[6]= '<a href="/cluster_info.php?cid=C'.htmlspecialchars($sort_line[6]).'">C'.htmlspecialchars($sort_line[6]).'</a>';
	                        print "<td>$sort_line[6]</td>";
 			}else {
	                        print "<td>$sort_line[6]</td>";
			}
                        print "<td>$sort_line[7]</td>";
                        print "<td>$sort_line[8]";
			print "</td></tr>\n";
              }//End of foreach 

   	 }//End if CASE 4
   	 if ($_GET['sortkey'] == 9) // CASE 5: When sorting is based on Editor Time.
   	 {
	      foreach ($result_array as $line) {//When sorting is applied no need to maintan the order
			$cid = get_cluster_id($pdo,$line[5]);
	                if ($cid != "0") {
	                    $line[6] = $cid;
	                } else {
	                    $line[6] = "";
        	        }
                        /*We need to sort cluster_id further so remove 'C' from it*/  
                        $line[6] = substr($line[6],1);
                        #Get the RET time 
                        $line[10] = get_rfced_time($line[2],$line[5]);

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
	                # Push the array to weeks in state array that cab be used for sort input
	                # We need to sort this array as week_in_state value for that use function subval
	                array_push($cl_array, $line);
             }//End of foreach
	      $new_cl_array = subval_sort($cl_array,'10',$sort);
              foreach ($new_cl_array as $sort_line){
	                # make link of draft name
	                $sort_line[5] = "<a href=\"edit_draft.php?draft=$sort_line[5]\">$sort_line[5]</a>";
			$class = ($i++ % 2) ? "odd" : "even";
			print "<tr class=\"$class\"><td>\n";
	                print "$sort_line[0]</td>";
                        print "<td>$sort_line[1]</td>";
                        print "<td>$sort_line[2]</td>";
                        print "<td>$sort_line[3]</td>";
                        print "<td>$sort_line[10]</td>";
                        print "<td>$sort_line[4]</td>";
	                print "<td>$sort_line[5]</td>";
                        if ($sort_line[6]){  
                                $sort_line[6]= '<a href="/cluster_info.php?cid=C'.htmlspecialchars($sort_line[6]).'">C'.htmlspecialchars($sort_line[6]).'</a>';
                                print "<td>$sort_line[6]</td>";

			}else {
	                        print "<td>$sort_line[6]</td>";
			}
                        print "<td>$sort_line[7]</td>";
                        print "<td>$sort_line[8]";
			print "</td></tr>\n";
              }//End of foreach 

   	 }//End if CASE 4




   }// ENd of !orderby
    print "</table>\n";
}//End of if count > 0 

/****************************************************************************************************************************************/
/*Function    : subval_sort                                                                                                             */
/*Description : This function takes 3 inputs, a multidimenstional array, the key from muti array by which we want the sort and sorting  */
/*              order "DESC" or "ASC"                                                                                                   */
/*              It gives the resulting array in the proper sorting format as specified in the inout                                     */
/****************************************************************************************************************************************/
function subval_sort( $a, $subkey, $order)
{
        foreach( $a as $k=>$v )
                $b[$k] = strtolower( $v[$subkey] );
                if( $order == 'DESC' )
                        arsort( $b );
                else
                asort( $b );
                foreach( $b as $k=>$v )
                $c[] = $a[$k];
                return $c;
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
