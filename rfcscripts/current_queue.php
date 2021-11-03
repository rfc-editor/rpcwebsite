<?php
/***********************************************************************************************************/
/* Copyright The IETF Trust 2020 All Rights Reserved                                                       */
/*Script Name : current_queue.php                                                                          */
/*Description : The script shows the documents present in the current queue with the relevant data such as */
/*              Editor details, Curr State, Weeks in state, Weeks in queue, RET, XML File, Draft Name ,    */
/*              Cluster, Pages, Submitted Date                                                             */
/*Dec 2018     : Added the tool adjustment state TI -PN                                                    */
/* November 2020 : Modified the script to use PDO prepared statements - PN                                 */
/* June 2021 : Modified the script for server upgrade - PN                                                 */
/***********************************************************************************************************/
session_start();
include('cluster_support_lib.php');
include('state_history_lib.php');
include('config.php');
include('db_connect.php');

$debug760 = false;
$orderby;
$sort;
$sql;

// Include WordPress
define('WP_USE_THEMES', false);
//require('/a/wordpress/wp-blog-header.php');
require_once ('/a/wordpress/wp-load.php');


//Add title specifying the dynamic page 
add_filter( 'wp_title', 'wp_title_current_queue', 10, 3 );

function wp_title_current_queue( $title, $sep, $seplocation ) {
    return 'Current Queue &raquo; RFC Editor';
}

//Add body class constant to avoid error404 body class for Wordpress outside page

function add_body_class_function($classes){

return array ('currentqueue');
}

add_action('body_class','add_body_class_function');


get_header();
get_sidebar('left');


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
	        $orderby = "ORDER BY generation_number, CURR_STATE";
		break;
	case 3:
		$orderby = "ORDER BY WEEKS_IN_Q";
		break;
	case 4:
		$orderby = "ORDER BY DRAFT_NAME";
		break;
	case 6:
		$orderby = "ORDER BY PAGES+0";
		break;
	case 7:
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



?>
<div id="content" class="narrowcolumn">
     <h1 class="post_title">Publication Queue</h1>

     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">
     <p><a href="/about/queue/">[About this page]</a>&nbsp;&nbsp;<a href="/reports/CurrQstats.txt">[Summary statistics]</a>&nbsp;&nbsp;<a href="/all_clusters.php">[List of all active clusters]</a></p>
<?php
#print "<table class=\"format_status\">\n";
print '<div class="scrolltable">';
print "<table class=\"dynamictable\">\n";
print "<tr>\n";
print "<th><a href=\"current_queue.php?sortkey=1&sorting=$sort\">Current state</a></th>";
print "<th><a href=\"current_queue.php?sortkey=2&sorting=$sort\">Weeks in state</a></th>";
print "<th><a href=\"current_queue.php?sortkey=3&sorting=$sort\">Weeks in queue</a></th>";
print "<th><a href=\"current_queue.php?sortkey=4&sorting=$sort\">Draft name (Authors)</a></th>";
print "<th> <a href=\"current_queue.php?sortkey=5&sorting=$sort\">Cluster</a></th>";
print "<th><a href=\"current_queue.php?sortkey=6&sorting=$sort\">Pages</a></th>";
print "<th><a href=\"current_queue.php?sortkey=7&sorting=$sort\">Submitted</a></th>";
print "</tr>\n";



$report_order = array('EDIT','RFC-EDITOR','AUTH','AUTH48','AUTH48-DONE','MISSREF','REF','NR','ISR','TO','ISR-AUTH','IESG','IANA','TI');

//Query build using PDO prepared statement

        $sql = "SELECT CONCAT( s.state_name,CASE WHEN i.iana_flag = \"1\"THEN \"*A\"ELSE \"\"END ,CASE WHEN i.ref_flag = \"1\"THEN \"*R\"ELSE \"\"END ) AS \"CURR_STATE\", 
        i.internal_key, 
        ROUND( (TO_DAYS( CURDATE( ) ) - ( TO_DAYS( i.date_received ) ) ) /7, 1) AS \"WEEKS_IN_Q\", 
        i.draft AS \"DRAFT_NAME\", 
        i.`page-count` AS \"CLUSTER\", 
        i.`page-count` AS \"PAGES\", 
        i.`date_received` AS \"SUBMITTED\",
        i.generation_number,
        i.`state_id`,
        i.`DOC-ID`,
        i.AUTHORS FROM editor_assignments e , `index` i , states s WHERE i.draft IS NOT NULL
        AND e.role_key = :role_key
        AND e.doc_key = i.internal_key
        AND i.state_id = s.state_id
        AND i.state_id != :state_id_14
        AND i.state_id != :state_id_3
        AND i.state_id != :state_id_19";
        
        
        if (($sortkey != 2) and ($sortkey != 5)){
                $sql .= " $orderby $sort";
        } else {
                $sql .= " $orderby";

        }

   
        $stmt = $pdo->prepare($sql);

        $role_key = 1;
        $state_id_14 = 14;
        $state_id_3 = 3;
        $state_id_19 = 19;

        $stmt->bindParam('role_key',$role_key);
        $stmt->bindParam('state_id_14',$state_id_14);
        $stmt->bindParam('state_id_3',$state_id_3);
        $stmt->bindParam('state_id_19',$state_id_19);
        $stmt->execute();

        $result_array = array();
        while($row = $stmt->fetch(PDO::FETCH_NUM)) {
           array_push($result_array, $row);
        }
        
        #Get the result cont 
        $count = $stmt->rowCount();

        print "<p>Found " .htmlspecialchars( $count) .  " records</p>\n";
       $i=0;
       $wks = array();
       $cl_array = array();

       if ($debug760 === true) {
   	  print("<h4>line</h4><pre>");
	  print_r($result_array); print("\n");
	  print("</pre>");
       }
 
   if (!(isset($_GET['sortkey']))){ // CASE 1: Initial Display of the queue 
    foreach ($report_order as $state) { //Need to maintain default report order if any sorting is not applied by the user
	foreach ($result_array as $line) {
            $pattern = "/^$state(\*A|\*R)*$/";
            if (preg_match($pattern, $line[0])) {

		# lookup cluster 
		$cid = get_cluster_id($pdo,$line[3]);

                if ($cid != "0") {
                    $line[4] = $cid;
                } else {
                    $line[4] = "";
                }

		#Calculate the Weeks in state data from state history table depending upon the passed internal_key ,state name and draft value
		$line[1] = get_weeks_in_state($pdo,$line[0],$line[1],$line[3]);
                #Combine state name with generation number
                if ($line[7] == "1"){
		    $line[0] = $line[0]."(1G)";	
		}else if ($line[7] == "2"){
		    $line[0] = $line[0]."(2G)";	
                 }else if ($line[7] == "3"){
		    $line[0] = $line[0]."(3G)";	
                  }   
                # Strip the version and get the draft name  
                $draft_base = strip_number($line[3]);  
                $line[3] = "<a href=".$datatracker_baselink.$draft_base.">".$line[3]."</a>";
                $line [4]= "<a href=\"cluster_info.php?cid=$line[4]\">$line[4]</a>";   
       		# Here's the actual drafts output, with classes for styling.
		$class = ($i++ % 2) ? "odd" : "even";
                
		#Get the link for the document if document is in AUTH48 state
		$line[9] = strtolower($line[9]);
                if (($line[8] == '2') || ($line[8] == '23')) {
                    if ($cid != "0"){
                       $line[0] = "<a href=\"/auth48/$cid\">$line[0]</a>";
                    }else {
                      $line[0] = "<a href=\"/auth48/$line[9]\">$line[0]</a>";
                    }
                }
	
                $line[10] = get_formatted_authors($line[10]);	
                #$line[10] = utf8_adjust($line[10]);	
                print "<tr class=\"$class\"><td>\n";
                print "<div id=\"$draft_base\"></div>$line[0]</td>";
                print "<td>$line[1]</td>";
                print "<td>$line[2]</td>";
                print "<td><b>$line[3] </b><br> $line[10]</td>";     
                print "<td>$line[4]</td>";
                print "<td>$line[5]</td>";
                print "<td>$line[6]";
		print "</td></tr>\n";
            }
	}
	print "<tr></tr>\n";
    }//End of foreach report_order
  } else { 
	 if (($_GET['sortkey'] != 2) and ($_GET['sortkey'] != 5)) {// CASE 2: When sorting is based on one of the values from Database directly. 
	 foreach ($result_array as $line) {//When sorting is applied no need to maintan the order
		# lookup cluster 
		$cid = get_cluster_id($pdo,$line[3]);

                if ($cid != "0") {
                    $line[4] = $cid;
                } else {
                    $line[4] = "";
                }

		#Calculate the Weeks in state data from state history table depending upon the passed internal_key ,state name and draft value
		$line[1] = get_weeks_in_state($pdo,$line[0],$line[1],$line[3]);
                #Combine state name with generation number
                if ($line[7] == "1"){
		    $line[0] = $line[0]."(1G)";	
		}else if ($line[7] == "2"){
		    $line[0] = $line[0]."(2G)";	
                 }else if ($line[7] == "3"){
		    $line[0] = $line[0]."(3G)";	
                  }   
                # Strip the version and get the draft name  
                $draft_base = strip_number($line[3]);  
                $line[3] = "<a href=".$datatracker_baselink.$draft_base.">".$line[3]."</a>";
                $line [4]= "<a href=\"cluster_info.php?cid=$line[4]\">$line[4]</a>";   
       		# Here's the actual drafts output, with classes for styling.
		$class = ($i++ % 2) ? "odd" : "even";
                
                #Get the link for the document if document is in AUTH48 state
		$line[9] = strtolower($line[9]);
                if (($line[8] == '2') || ($line[8] == '23')) {
                    if ($cid != "0"){
                       $line[0] = "<a href=\"/auth48/$cid\">$line[0]</a>";
                    }else {
                      $line[0] = "<a href=\"/auth48/$line[9]\">$line[0]</a>";
                    }
                }
               $line[10] = get_formatted_authors($line[10]);	
                #$line[10] = utf8_adjust($line[10]);	
		print "<tr class=\"$class\"><td>\n";
                print "<div id=\"$draft_base\"></div>$line[0]</td>";
                print "<td>$line[1]</td>";
                print "<td>$line[2]</td>";
                print "<td><b>$line[3]</b><br> $line[10]</td>";
                print "<td>$line[4]</td>";
                print "<td>$line[5]</td>";
                print "<td>$line[6]";
		print "</td></tr>\n";
	}
	print "<tr></tr>\n";
        } 
	 if ($_GET['sortkey'] == 2) // CASE 2: When sorting is based on Weeks in state value. 
               {
		 foreach ($result_array as $line) {//When sorting is applied no need to maintan the order
		# lookup cluster 
			$cid = get_cluster_id($pdo,$line[3]);
	                if ($cid != "0") {
	                    $line[4] = $cid;
	                } else {
        	            $line[4] = "";
	                }
		#Calculate the Weeks in state data from state history table depending upon the passed internal_key ,state name and draft value
			$line[1] = get_weeks_in_state($pdo,$line[0],$line[1],$line[3]);
                #Combine state name with generation number
        	        if ($line[7] == "1"){
			    $line[0] = $line[0]."(1G)";	
			}else if ($line[7] == "2"){
			    $line[0] = $line[0]."(2G)";	
	                 }else if ($line[7] == "3"){
			    $line[0] = $line[0]."(3G)";	
	                  }   
                # Push the array to weeks in state array that cab be used for sort input 
                # We need to sort this array as week_in_state value for that use function subval   
                         array_push($wks, $line);
                      } 
		   $new_wks = subval_sort($wks,'1',$sort);	
                   foreach ($new_wks as $sort_line){
		        $sorted_cid = "";
                        $sorted_cid = $sort_line[4];
	                # Strip the version and get the draft name  
	                $draft_base = strip_number($sort_line[3]);  
                        $sort_line[3] = "<a href=".$datatracker_baselink.$draft_base.">".$sort_line[3]."</a>";
	                $sort_line [4]= "<a href=\"cluster_info.php?cid=$sort_line[4]\">$sort_line[4]</a>";

                        #Get the link for the document if document is in AUTH48 state
                        $sort_line[9] = strtolower($sort_line[9]);
                	if (($sort_line[8] == '2') || ($sort_line[8] == '23')) {
                    	if ($sorted_cid != ""){
                       		$sort_line[0] = "<a href=\"/auth48/$sorted_cid\">$sort_line[0]</a>";
                    	}else {
                      		$sort_line[0] = "<a href=\"/auth48/$sort_line[9]\">$sort_line[0]</a>";
                    	 }
                	}
                        $sort_line[10] = get_formatted_authors($sort_line[10]);	
                        #$sort_line[10] = utf8_adjust($sort_line[10]);	

	       		# Here's the actual drafts output, with classes for styling.
			$class = ($i++ % 2) ? "odd" : "even";
			print "<tr class=\"$class\"><td>\n";
                        print "<div id=\"$draft_base\"></div>$sort_line[0]</td>";
                        print "<td>$sort_line[1]</td>";
	                print "<td>$sort_line[2]</td>";
	                print "<td><b>$sort_line[3] </b><br> $sort_line[10]</td>";   
                        print "<td>$sort_line[4]</td>";
	                print "<td>$sort_line[5]</td>";
	                print "<td>$sort_line[6]";
			print "</td></tr>\n";
		   }//End of foreach 	

	     }//End of if key 2

	 if ($_GET['sortkey'] == 5) // CASE 2: When sorting is based on Cluster value. 
               {
		 foreach ($result_array as $line) {//When sorting is applied no need to maintan the order
		# lookup cluster 
			$cid = get_cluster_id($pdo,$line[3]);
	                if ($cid != "0") {
	                    $line[4] = $cid;
	                } else {
        	            $line[4] = "";
	                }
                        /*We need to sort cluster_id further so remove 'C' from it*/  
                        $line[4] = substr($line[4],1);

		#Calculate the Weeks in state data from state history table depending upon the passed internal_key ,state name and draft value
			$line[1] = get_weeks_in_state($pdo,$line[0],$line[1],$line[3]);
                #Combine state name with generation number
        	        if ($line[7] == "1"){
			    $line[0] = $line[0]."(1G)";	
			}else if ($line[7] == "2"){
			    $line[0] = $line[0]."(2G)";	
	                 }else if ($line[7] == "3"){
			    $line[0] = $line[0]."(3G)";	
	                  }   
                # Push the array to weeks in state array that cab be used for sort input 
                # We need to sort this array as week_in_state value for that use function subval   
                         array_push($cl_array, $line);
                      } 
		   $new_cl = subval_sort($cl_array,'4',$sort);	
                   foreach ($new_cl as $sort_line){
		        $sorted_cid = "";
	                # Strip the version and get the draft name  
	                $draft_base = strip_number($sort_line[3]);  
                        $sort_line[3] = "<a href=".$datatracker_baselink.$draft_base.">".$sort_line[3]."</a>";
                        if ($sort_line[4]){
		                $sorted_cid = $sort_line[4];
		                $sort_line [4]= "<a href=\"cluster_info.php?cid=C$sort_line[4]\">C$sort_line[4]</a>";   
			}
			else {
		                $sort_line [4]= "<a href=\"cluster_info.php?cid=$sort_line[4]\">$sort_line[4]</a>";   
			}
                        
                        #Get the link for the document if document is in AUTH48 state
                        $sort_line[9] = strtolower($sort_line[9]);
                	if (($sort_line[8] == '2') || ($sort_line[8] == '23')) {
                    	if ($sorted_cid != ""){
                       		$sort_line[0] = "<a href=\"/auth48/C$sorted_cid\">$sort_line[0]</a>";
                    	}else {
                      		$sort_line[0] = "<a href=\"/auth48/$sort_line[9]\">$sort_line[0]</a>";
                    	 }
                	}

                        $sort_line [10] = get_formatted_authors($sort_line[10]);	
                        #$sort_line [10] = utf8_adjust($sort_line[10]);	


	       		# Here's the actual drafts output, with classes for styling.
			$class = ($i++ % 2) ? "odd" : "even";
			print "<tr class=\"$class\"><td>\n";
        	        print "<div id=\"$draft_base\"></div>$sort_line[0]</td>";
                        print "<td>$sort_line[1]</td>";
	                print "<td>$sort_line[2]</td>";
	                print "<td><b>$sort_line[3] </b><br> $sort_line[10]</td>";
                        print "<td>$sort_line[4]</td>";
	                print "<td>$sort_line[5]</td>";
	                print "<td>$sort_line[6]";
			print "</td></tr>\n";
		   }//End of foreach 	

	     }//End of if key 2




  }
 // End of if (!($orderby)){
    print "</table>\n";
    print "</div>";   
?>
    </div>
  </div>
</div>
<?php
    print "<br>";
    print "<br>";
get_footer();

//}


/**************************************************************************************************************************************/
/*Function : subval_sort                                                                                                              */
/*Descriptio : This function takes 3 inputs, a multidimenstional array, the key from muti array by which we want the sort and sorting */
/*	       order "DESC" or "ASC"												      */
/*             It gives the resulting array in the proper sorting format as specified in the inout				      */ 
/**************************************************************************************************************************************/
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
/* Function :  get_formatted_authors                                                                                                  */
/*             Formats the author names in desired format                                                                             */
/**************************************************************************************************************************************/


function get_formatted_authors($in_authors_string){
global $debug760;

       $out_authors_string = "";
       $authors_list = explode(',', $in_authors_string);

       if ($debug760 === true) {
	    print "The exploded array is"."<br>";
            print_r($authors_list);

       }
       foreach ($authors_list as $author){
                $author = trim($author);
                if ($debug760 === true) {
                 print "My author is".$author;
                }  
                $new_author = preg_replace('/\. /', '.&nbsp;', $author);
                if ($out_authors_string != ""){ 
                   $out_authors_string .= ", ".$new_author;
                }else {
		   $out_authors_string .= $new_author;
                }
       }

return $out_authors_string;

}

$pdo = null;
?>

