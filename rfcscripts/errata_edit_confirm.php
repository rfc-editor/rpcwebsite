<?php
  /* Copyright The IETF Trust 2020 All Rights Reserved */
  /* $Id: errata_edit_confirm.php,v 1.1.1.1 2016/02/08 21:03:50 priyanka Exp $ */
session_start();
include("errata_headers.php");
include("errata_lib.php");
include("errata_authen_lib.php");

session_authenticate();

$debug_e_confirm = false;

// Include WordPress
define('WP_USE_THEMES', false);
//require('/a/wordpress/wp-blog-header.php');
require_once ('/a/wordpress/wp-load.php');

//Add title specifying the dynamic page 
add_filter( 'wp_title', 'wp_errata_edit_confirm', 10, 3 );
function wp_errata_edit_confirm( $title, $sep, $seplocation ) {
	return 'Confirm RFC Errata Edit &raquo; RFC Editor';
}
//Add body class constant to avoid error404 body class for Wordpress outside page
add_action('body_class','add_body_class_function');
  function add_body_class_function($classes){
	return array ('errata_edit_confirm');
 }

/*Add Wordpress header and Sidebar*/
get_header();
get_sidebar(left);


errata_edit_header("Confirm RFC Errata Edit","rfcerrata","NONE","errata_edit.php");

/*Stripping the Wordpress additional slashes from GLOBAL vaiables*/
$_POST = array_map( 'stripslashes_deep', $_POST);
$_GET = array_map( 'stripslashes_deep', $_GET);
$_COOKIE    = array_map( 'stripslashes_deep', $_COOKIE );
$_REQUEST   = array_map( 'stripslashes_deep', $_REQUEST );

if ($debug_e_confirm === true) {
     print("\$_POST<br /><pre>");
     print_r($_POST);
     print("</pre>");
     print("\$_GET (should be empty)<br /><pre>");
     print_r($_GET);
     print("</pre>");
}

print<<<END
<div id="content" class="narrowcolumn">
     <h1 class="post_title"></h1>

     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">

END;

if (array_key_exists('rfcid',$_POST)) {
     $rfcid    = $_POST['rfcid'];
     $doc_id   = $_POST['doc-id'];
     $pub_date = $_POST['pub-date'];
     $title    = $_POST['title'];
     print '
<h1 class="maintitle">Report Errata for ' . htmlspecialchars($rfcid) . '</h1>
<p>This is how the record would look after updating the report in the database.
If it is correct, you may confirm the edit. If there is an error in the report, you
may select the edit button to continue editing this record.
</p>
<hr />';

     report_header($rfcid,$title,$pub_date);
     display_record($_POST);
     print("<table>\n<tr>\n");
     print("<td>\n");
     update_record_form($_POST);
     print("\n</td>\n");
     print("<td>\n");
     edit_errata_record_again($_POST);
     print("\n</td>\n");
     print("\n</tr></table>\n");

     errata_edit_footer();
} else {
     print("<p>There's nothing for me to do here!</p>");
}
?>
