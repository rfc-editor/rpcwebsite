<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
/*Apr 2020: Added state_id = 14 check to ensure counts are correct*/ 
error_reporting(E_ALL ^ E_NOTICE);
if (!function_exists("GetSQLValueString")) {
function GetSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "") 
{
  if (PHP_VERSION < 6) { $theValue = get_magic_quotes_gpc() ? stripslashes($theValue) : $theValue; }
  $theValue = function_exists("mysql_real_escape_string") ? mysql_real_escape_string($theValue) : mysql_escape_string($theValue);
  switch ($theType) {
    case "text":
      $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
      break;    
    case "long":
    case "int":
      $theValue = ($theValue != "") ? intval($theValue) : "NULL";
      break;
    case "double":
      $theValue = ($theValue != "") ? doubleval($theValue) : "NULL";
      break;
    case "date":
      $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
      break;
    case "defined":
      $theValue = ($theValue != "") ? $theDefinedValue : $theNotDefinedValue;
      break;
  }
  return $theValue; } }

$dataname = "rfcdev";
$username = "rfcdev";
$password = "dev";
$idata = array();
$cy=intval($_GET['y']); if ($cy<2013) die();
$cm=intval($_GET['m']); if (($cm<1)||($cm>12)) die() ;
$ny = $cy; $nm = ( $cm + 1 ) % 13 + 1; if($nm==1) $ny++; else $nm--;
if($cm<10) $cm = "0".$cm;
if($nm<10) $nm = "0".$nm;
$sf = $cy."-".$cm."-01";
$ef = $ny."-".$nm."-01";
$mysqli = new mysqli("localhost",$username,$password,$dataname);
if ($mysqli->connect_errno) { echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error; die(); }
?>

<!--html>
<head>
    <title>Publications - Detailed View</title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859" />
    <link rel="stylesheet" href="subpub_stats.css" type="text/css" />
</head>
<body-->
<?php
// Include WordPress
define('WP_USE_THEMES', false);
//require('/a/wordpress/wp-blog-header.php');
require_once ('/a/wordpress/wp-load.php');

//Add title specifying the dynamic page 
add_filter( 'wp_title', 'wp_title_subpub_pub', 10, 3 );

function wp_title_subpub_pub( $title, $sep, $seplocation ) {
    return 'Publications - Detailed View &raquo; RFC Editor';
}

//Add body class constant to avoid error404 body class for Wordpress outside page

function add_body_class_function($classes){

return array ('pubdetailed');
}

add_action('body_class','add_body_class_function');


get_header();

?>

<div id="content" class="nosidebar">
     <h1 class="post_title">Publications - Detailed View</h1>
     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">


<!--table border="0" cellPadding="0" cellSpacing="0" width="677">
<tr><td height="87" valign="top" width="677"><hr><img src="/header_queue.gif"></td></tr>
<tr><td height="54" vAlign="top" width="677"><hr><h2 align="center">Publications - Detailed View</h2-->
<p align="center">
<a href="subpub_stats.php">Return to Main Page</a> - 
<a href="/about/subpub_stats">About This Page</a>
</p>

<?php 
echo "<h3 align=\"center\">".date("F",mktime(0,0,0,$cm,10))." ".$cy."</h4>\n";

$pct=0;
$q1="SELECT SUM(`page-count`) as pct FROM `index` WHERE `pub-date` >='".$sf."' AND `pub-date` <'".$ef."' AND `state_id` = '14' order by `pub-date`;";
$r1 = mysqli_query($mysqli, $q1) or die(mysqli_error($mysqli));
while ($row = $r1->fetch_assoc()) { $pct=intval($row['pct']); }
mysqli_free_result($r1);

$q1="SELECT `doc-id`, `draft`, `source`, `pub-status`, `page-count`, `date_received`, `pub-date` FROM `index` WHERE `pub-date` >='".$sf."' AND `pub-date` <'".$ef."' AND `doc-id` like 'RFC%' AND `state_id` = '14' order by `pub-date`;";
$r1 = mysqli_query($mysqli, $q1) or die(mysqli_error($mysqli));
$x1 = mysqli_num_rows($r1);
$r1->data_seek(0);
$dc=0;

echo "<p align=\"center\"> Total documents: $x1 - Total pages: $pct</p>\n";
if($x1>0) {
	echo "<table class=\"statictable\"><tr><th>RFC Number</th><th>Draft</th><th>Stream or WG Name</th><th>Status</th><th>Pages</th><th>Submitted</th></tr>\n";
	while ($row = $r1->fetch_assoc()) {
		if($dc%2==0) { echo "<tr class=\"evenleft\">"; }
		else { echo "<tr class=\"oddleft\">"; }
		$dc+=1;
		echo "<td>".$row['doc-id']."</td>";
		echo "<td>".$row['draft']."</td>";
		echo "<td>".$row['source']."</td>";
		echo "<td>".$row['pub-status']."</td>";
		echo "<td align=\"right\">".$row['page-count']."&nbsp;</td>";
		echo "<td>".$row['date_received']."</td>";
		echo "</tr>\n";
		}
	echo "</table>\n";
	}
mysqli_free_result($r1);
echo "<hr>\n";

$q1="SELECT `doc-id`, `draft`, `source`, `pub-status`, `page-count`, `date_received`, `in_date` FROM `state_history`, `index` WHERE `internal_key`=`internal_dockey` AND state_history.state_id=19 and `in_date` >='".$sf."' AND `in_date` <'".$ef."' order by `in_date`;";
$r1 = mysqli_query($mysqli, $q1) or die(mysqli_error($mysqli));
$x1 = mysqli_num_rows($r1);
$r1->data_seek(0);
$dc=0;

echo "<p align=\"center\">Withdrawn drafts: $x1</p>";

if($x1>0) {
	echo "<table class=\"statictable\"><tr><th>Draft</th><th>Stream or WG Name</th><th>Status</th><th>Pages</th><th>Submitted</th><th>Withdrawn</th></tr>\n";
	while ($row = $r1->fetch_assoc()) {
		if($dc%2==0) { echo "<tr class=\"evenleft\">"; }
		else { echo "<tr class=\"oddleft\">"; }
		$dc+=1;
		echo "<td>".$row['draft']."</td>";
		echo "<td>".$row['source']."</td>";
		echo "<td>".$row['pub-status']."</td>";
		echo "<td align=\"right\">".$row['page-count']."&nbsp;</td>";
		echo "<td>".$row['date_received']."</td>";
		echo "<td>".$row['in_date']."</td>";
		echo "</tr>\n";
		}
	echo "</table>\n";
	}
mysqli_free_result($r1);

?>
<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
<!--/td></tr></tbody></table></body></html-->
    </div>
  </div>
</div>
<?php
    print "<br>";
    print "<br>";
get_footer();


?>


