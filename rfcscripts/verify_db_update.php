<?php
  /* Copyright The IETF Trust 2020 All Rights Reserved */
  /* $Id: verify_db_update.php,v 1.1.1.1 2016/02/08 21:03:50 priyanka Exp $ */
session_start();
include("errata_headers.php");
include("errata_lib.php");
include("errata_authen_lib.php");
include("errata_mail_lib.php");

// Include WordPress
define('WP_USE_THEMES', false);
//require('/a/wordpress/wp-blog-header.php');
require_once ('/a/wordpress/wp-load.php');


session_authenticate();

/*Stripping the Wordpress additional slashes from GLOBAL vaiables*/
$_POST = array_map( 'stripslashes_deep', $_POST);
$_GET = array_map( 'stripslashes_deep', $_GET);
$_COOKIE    = array_map( 'stripslashes_deep', $_COOKIE );
$_REQUEST   = array_map( 'stripslashes_deep', $_REQUEST );

$debug_vdbup = false;

$rel_message = "A notification email has been sent to the relevant parties.";

if ($debug_vdbup === true) {
// NOTE: This debug output breaks the later forward/redirect
     print("\$_POST<br /><pre>");
     print_r($_POST);
     print("</pre>");
     print("\$_GET (should be empty)<br /><pre>");
     print_r($_GET);
     print("</pre>");
}

if (isset($_POST['submit'])) {
     if (isset($_POST['reason'])) {
          $_POST['notes'] .= "\n --VERIFIER NOTES-- \n" . $_POST['reason']; // append reason to notes
          $_SESSION['message'] = 
	       "The report is now marked as <b>Rejected</b>."." $rel_message"; 
          if ($debug_vdbup === true) {
               print("\$_POST<br /><pre>");
               print_r($_POST);
               print("</pre>");
          }
     }
     $status = update_errata_record($_POST);

     if ($debug_vdbup === true) {
          print("<pre>\$status=");
          var_dump($status);
          print("\n</pre>\n");
     }
     if (is_bool($status) && $status === true) {
          $errata_status_code = $_POST['errata_status_code'];
          if ($errata_status_ids[$errata_status_code] == STATUS_VERIFIED) {
               generate_verify_msg($_POST);
	       $_SESSION['message'] = "The report is now marked as <b>Verified</b>."." $rel_message";
          } else if ($errata_status_ids[$errata_status_code] == STATUS_REJECTED) {
               generate_reject_msg($_POST);
          } else if ($errata_status_ids[$errata_status_code] == STATUS_HELD){
	       generate_hold_msg($_POST);
	       $_SESSION['message'] = "The report is now marked as <b>Held for Document Update</b>."." $rel_message";
	  }
          $_SESSION['rfc'] = substr($_POST['doc-id'],3,MAX_RFC);
          //header("Location: http://{$_SERVER['SERVER_NAME']}/verify_complete.php"); /* forward to edit page */
          ams_redirect("verify_complete.php");
          exit;
     } else {
        //Add title specifying the dynamic page 
        add_filter( 'wp_title', 'wp_title_verify_errata', 10, 3 );
        function wp_title_verify_errata( $title, $sep, $seplocation ) {
                return 'Database Error &raquo; RFC Editor';
        }
        //Add body class constant to avoid error404 body class for Wordpress outside page
        add_action('body_class','add_body_class_function');
          function add_body_class_function($classes){
                return array ('database_error');
          }

	/* END Code to add the Javascript, Body class name and Title name as per WP*/

 	/*Add Wordpress header and Sidebar*/
	get_header();
	get_sidebar(left);

	/*Add DIV use with wordpress for content*/
?>
     
<div id="content" class="narrowcolumn">
     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">
<?php 
     // The return type should be a string with an error message.
          errata_edit_header("Database Error","rfcerrata","NONE","verify_errata.php");
          error_log($status);
          print('<p class="warning">The application has encountered an error. Please try again later.</p>');
          print("<p><a href=\"verify_errata.php\">Search again</a> for more reported errata</p>\n");
          errata_edit_footer();
     }
} else {
     //header("Location: http://{$_SERVER['SERVER_NAME']}/verify_errata.php");
     ams_redirect("verify_errata.php");
     exit;
}
?>
