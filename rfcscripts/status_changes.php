<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
/* November 2020 : Modified the script to use PDO prepared statements - PN            */
session_start();
include('db_connect.php');
include('config.php');
$debug_stchg = false;
$orderby;
$sort;
$add_orderby;


// Include WordPress
define('WP_USE_THEMES', false);
//require('/a/wordpress/wp-blog-header.php');
require_once ('/a/wordpress/wp-load.php');

//Add title specifying the dynamic page 
add_filter( 'wp_title', 'wp_title_status_changes', 10, 3 );

function wp_title_status_changes( $title, $sep, $seplocation ) {
    return 'RFC Status Changes &raquo; RFC Editor';
}

//Add body class constant to avoid error404 body class for Wordpress outside page

function add_body_class_function($classes){

return array ('status_changes');
}

add_action('body_class','add_body_class_function');


get_header();
get_sidebar(left);



if (isset($_GET['sortkey'])){ 
	$sortkey = $_GET['sortkey'];
	list($orderby,$add_orderby) = getorderby($sortkey);
#	$orderby = getorderby($sortkey);

}
else {
       $orderby = "ORDER BY i.`DOC-ID`";
       #$orderby = "i.`DOC-ID`";
       $add_orderby = ", s.`date_of_change` ASC";

}

function getorderby($sortkey){

  switch($sortkey)
  {
	case 1:
	        #$orderby = "i.`DOC-ID`";
	        $orderby = "ORDER BY i.`DOC-ID`";
		$add_orderby = ", s.`date_of_change` ASC";
		break;
	case 5:
		#$orderby = "s.`date_of_change`";
		$orderby = "ORDER BY s.`date_of_change`";
		$add_orderby = ", i.`DOC-ID` ASC";

		break;
	default:
		break;
				   
  }
return array($orderby,$add_orderby);
#return ($orderby);

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
	 $original_sort = 'ASC';
         break;
 case "DESC":
         $sort = 'ASC';
	 $original_sort = 'DESC';
         break;
 default:
         break;
}

#The final ORDER BY and SORTING CRITERIA
?>

<div id="content" class="narrowcolumn">
     <h1 class="post_title">RFC Status Changes</h1>

     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">
<?php
print "<table class=\"dynamictable\">\n";
print "<tr>\n";
print "<th><a href=\"status_changes.php?sortkey=1&sorting=$sort\">Number</a></th>";
print "<th>Title</th>";
print "<th>Publication Status</th>";
print "<th>Status</th>";
print "<th><a href=\"status_changes.php?sortkey=5&sorting=$sort\">Date Of Change</a></th>";
print "</tr>\n";



#print "$query";


$query = 'SELECT i.`DOC-ID` , i.`title` , i.`pub-status` , i.`status` , s.`date_of_change` , s.`URL_of_change`,
		DATE_FORMAT(s.`date_of_change`,"%M %Y") as month_year
	   FROM `status_changes` s, `index` i
	   WHERE s.`dockey` = i.`internal_key`';
$query .= " $orderby $original_sort $add_orderby"; 
$stmt = $pdo->prepare($query);
$stmt->execute();
$result_array = array();
# build result array
while($row = $stmt->fetch(PDO::FETCH_NUM)) {
 array_push($result_array, $row);
}

if ($debug_stchg === true) {
   print("<h4>line</h4><pre>");
   print_r($result_array); print("\n");
   print("</pre>");
}

$count = $stmt->rowCount();

# display title
echo "<p>This page lists the RFCs whose statuses have changed since publication.</p>";  
echo "<p>The process for how documents' statuses are updated has changed over time. The information below is based on:</p>";
echo "<ul>";
echo "<li> Document or Protocol Actions requesting a status change</li>";
echo "<li> RFCs that requested the status change</li>";
echo "<li> Private communication between the RFC Editor team and/or third parties (earlier years)*</li>";
echo "<li> Defined RFC Editor process*</li>";
echo "</ul>";
echo"<p>Links to the Document or Protocol Action or the RFC are provided where possible.</p>";  
print "<p><b>*Note:</b> At one time, the RFC Editor had the ability to move documents to Historic or Informational as the party responsible for the RFC Editor Index.  The RFC Editor received and sought input regarding status changes and updates were made as needed.  \"Official Internet Protocol\" documents and \"IANA Assigned Numbers\" documents were moved to Historic when the obsoleting documents were released as part of the publication process.</p>";


# Implode result into a table.
if  ($count > 0) {
    print "<p>Found " . $count .  " records</p>\n";

    $i=0;
    $wks = array();
    $cl_array = array();

    foreach ($result_array as $line) {
		$class = ($i++ % 2) ? "odd" : "even";
		$http_in_info_base_url = $document_root.'/info/';
	        list($rfc_name,$rfc_number,$length) = split_rfc($line[0]);
		$formatted_rfc_number = sprintf('%s&nbsp;%d',$rfc_name,$rfc_number);
		$rfc_name_link = strtolower($rfc_name);
	        $http_rfc_name = sprintf('%s&nbsp;%d',$rfc_name,$rfc_number);
		$info_link = sprintf('%s%d',$rfc_name_link,$rfc_number);
	        $http_in_info_url = $http_in_info_base_url.$info_link;
		print "<tr class=\"$class\"><td><a href=\"$http_in_info_url\" target=\"_blank\">";
		print "$formatted_rfc_number</a></td>";
		print "<td>$line[1]</td>";
		print "<td>$line[2]</td>";
		print "<td>$line[3]</td>";
		if ($line[4] != '0000-00-00'){
			if ($line[5]){
				print "<td><a href=\"$line[5]\">$line[6]</a></td>";
			}else {
				print "<td>$line[6]</td>";

			}
		}else {
			print "<td></td>";
		}

		print "</td></tr>\n";

    }//End of  foreach ($result_array as $line)


    print "</table>\n";
?>

    </div>
  </div>
</div>
<?php
    print "<br>";
    print "<br>";
get_footer();

}//End of if mysqli_query($result)
else {
	rfc_fail("Query failed status changes ");
     }	
?>
<?php
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
