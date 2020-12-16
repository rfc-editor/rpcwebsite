<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<?php 
  /* Copyright The IETF Trust 2020 All Rights Reserved */
error_reporting(E_ALL ^ E_NOTICE);
require_once("subpub_stats1.php");
?>
<html>
<!--head>
    <title>Submissions and Publications, by Stream and by Status</title>
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
add_filter( 'wp_title', 'wp_title_subpub_stats', 10, 3 );

function wp_title_subpub_stats( $title, $sep, $seplocation ) {
    return 'Submissions and Publications, by Stream and by Status &raquo; RFC Editor';
}

//Add body class constant to avoid error404 body class for Wordpress outside page

function add_body_class_function($classes){

return array ('subpubstats');
}

add_action('body_class','add_body_class_function');


get_header();

?>
<div id="content" class="nosidebar">
     <h1 class="post_title">Submissions and Publications, by Stream and by Status</h1>

     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">


<!--table border="1" cellPadding="0" cellSpacing="0" width="677">
<tr><td height="87" valign="top" width="677"><hr><img src="/header_queue.gif"></td></tr>
<tr><td height="54" vAlign="top" width="677"><hr><h2 align="center">Submissions and Publications, by Stream and by Status</h2-->
<p align="center"><a href="/about/subpub_stats">About This Page</a></p> 

<?php 
$thisyear=intval(date('Y')); 
$thismonth=intval(date('n')); 
for($curyear=$thisyear; $curyear>=2013; $curyear--) {
	echo "<h3 align=\"left\">".$curyear."</h3>";
	for($curmonth=12; $curmonth>=1; $curmonth--) {
		if(($curyear==2013)&&($curmonth<4)) continue;
		if(($curyear==$thisyear)&&($curmonth>$thismonth)) continue;
		Emit();
		}
	}
?>

<p align="left">For data before April 2013, click <a href="/reports/old/queue-stats/queue-stats-index.html">here</a>.</p>

&nbsp;<br />
<!--/td></tr></table>
</body></html-->
    </div>
  </div>
</div>
<?php
    print "<br>";
    print "<br>";
get_footer();


?>


