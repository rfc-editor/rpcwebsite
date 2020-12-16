<?php
  /* Copyright The IETF Trust 2020 All Rights Reserved */
  /* $Id: errata_dataentry_insert.php,v 1.2 2016/10/19 17:30:06 priyanka Exp $ */
session_start();
include("errata_authen_lib.php");
session_authenticate();

include("errata_headers.php");
include("errata_lib.php");
include("errata_mail_lib.php");

$debug_insert = false;

/*// Include WordPress
define('WP_USE_THEMES', false);
//require('/a/wordpress/wp-blog-header.php');
require_once ('/a/wordpress/wp-load.php');

//Add title specifying the dynamic page 
add_filter( 'wp_title', 'wp_errata_data_entry_insert', 10, 3 );
function wp_errata_data_entry_insert( $title, $sep, $seplocation ) {
	return 'Errata Data Entry Insert &raquo; RFC Editor';
}
//Add body class constant to avoid error404 body class for Wordpress outside page
add_action('body_class','add_body_class_function');
  function add_body_class_function($classes){
	return array ('data_entry_insert');
 }

/*Add Wordpress header and Sidebar*/
/*get_header();
get_sidebar(left);

*/

/*Stripping the Wordpress additional slashes from GLOBAL vaiables*/
$_POST = array_map( 'stripslashes_deep', $_POST);
$_GET = array_map( 'stripslashes_deep', $_GET);
$_COOKIE    = array_map( 'stripslashes_deep', $_COOKIE );
$_REQUEST   = array_map( 'stripslashes_deep', $_REQUEST );

if ($debug_insert === true) {
// NOTE: This debug output breaks the later forward/redirect
     print("\$_POST<br /><pre>");
     print_r($_POST);
     print("</pre>");
     print("\$_GET (should be empty)<br /><pre>");
     print_r($_GET);
     print("</pre>");
     print("\$_SESSION<br /><pre>");
     print_r($_SESSION);
     print("</pre>");
}


/*
print<<<END
<div id="content" class="narrowcolumn">
     <h1 class="post_title"></h1>

     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">

END;
*/

$status = insert_errata_report($_POST);

if ($debug_insert === true) {
     print("<pre>\$status=");
     var_dump($status);
     print("\n\$next=");
     var_dump($next);
     print("\n</pre>\n");
}

if (is_bool($status) && $status === true) {
/*Commenting the ack mail send option for add errata in Superuser case*/
#     $status = generate_report_ack($_POST);
     //header('Location: errata_dataentry.php'); /* forward to data entry form */
     ams_redirect("errata_edit.php");

     exit;
} else {
     // The return type should be a string with an error message. Because this
     // script is used internally, show the error on the screen as well as
     // log it.
     page_header("Database Error","rfcerrata","NONE","errata_dataentry.php");
     error_log($status);
     print('<p class="error">The application has encountered an error.:<br />');
     print("$status</p>\n");
     page_footer();
}

?>
