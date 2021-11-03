<?php
  /* Copyright The IETF Trust 2020 All Rights Reserved */
  /* $Id: errata_edit_mail_select.php,v 1.3 2021/06/17 00:55:26 priyanka Exp $ */
  /* June 2021 : Modified the script for server upgrade - PN                            */
session_start();
include("errata_headers.php");
include("errata_lib.php");
include ("errata_mail_lib.php");

// Include WordPress
define('WP_USE_THEMES', false);
//require('/a/wordpress/wp-blog-header.php');
require_once ('/a/wordpress/wp-load.php');

$debug_edit_mail = false;


$rel_message = "A notification email has been sent to the relevant parties.";


/*Stripping the Wordpress additional slashes from GLOBAL vaiables*/
$_POST = array_map( 'stripslashes_deep', $_POST);
$_GET = array_map( 'stripslashes_deep', $_GET);
$_COOKIE    = array_map( 'stripslashes_deep', $_COOKIE );
$_REQUEST   = array_map( 'stripslashes_deep', $_REQUEST );


if ($debug_edit_mail === true) {
// NOTE: This debug output breaks the later forward/redirect
     print("\$_POST<br /><pre>");
     print_r($_POST);
     print("</pre>");
     print("\$_GET (should be empty)<br /><pre>");
     print_r($_GET);
     print("</pre>");
}

$submit = $_POST['submit'];

if ($submit) {
  switch ($submit) {
      case "GO AHEAD, SEND IT": //Go to mail send 
         $errata_status_code = $_POST['errata_status_code'];
         if ($errata_status_ids[$errata_status_code] == STATUS_VERIFIED) {
             generate_verify_msg($_POST);
             $_SESSION['message'] = "The report is now marked as <b>Verified</b>."." $rel_message";
         } else if ($errata_status_ids[$errata_status_code] == STATUS_REJECTED) {
             generate_reject_msg($_POST);
             $_SESSION['message'] = "The report is now marked as <b>Rejected</b>."." $rel_message";
         } else if ($errata_status_ids[$errata_status_code] == STATUS_HELD){
              generate_hold_msg($_POST);
              $_SESSION['message'] = "The report is now marked as <b>Held for Document Update</b>."." $rel_message";
         }else if ($errata_status_ids[$errata_status_code] == STATUS_REPORTED){
              generate_report_ack($_POST);
              $_SESSION['message'] = "The report is now marked as <b>Reported</b>."." $rel_message";
         }
         $_SESSION['rfc'] = substr($_POST['doc-id'],3,MAX_RFC);
         $_SESSION['include_verified'] ='yes';
         ams_redirect("errata_edit_complete.php");
         exit;
         break;
      case "NO MESSAGE": //Go to errata select page from where you started
      default:
        /*If no message has been selected then go back to errata edit page*/
         $_SESSION['rfc'] = substr($_POST['doc-id'],3,MAX_RFC);
         $_SESSION['include_verified'] ='yes';
         ams_redirect("errata_edit_select.php");
         exit;
         break;
  }/*End switch statement*/
}else{

     // The return type should be a string with an error message.
     
     //Add title specifying the dynamic page 
     add_filter( 'wp_title', 'wp_title_message_select_error', 10, 3 );

    function wp_title_message_select_error( $title, $sep, $seplocation ) {
    return 'Message Select &raquo; RFC Editor';
    }

    //Add body class constant to avoid error404 body class for Wordpress outside page

    function add_body_class_function($classes){

       return array ('edit_mail_confirm');
    }

    add_action('body_class','add_body_class_function');


    get_header();
    get_sidebar('left');
?>
<div id="content" class="narrowcolumn">
     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">
<?php


     print('<p class="error">Message Send Error "</p>\n');
     page_footer();

}

?>
