<?php
  /* Copyright The IETF Trust 2020 All Rights Reserved */
  /* $Id: errata_update.php,v 1.4 2021/06/17 00:47:33 priyanka Exp $ */
  /* June 2021 : Modified the script for server upgrade - PN                            */
session_start();
include("errata_headers.php");
include("errata_lib.php");

// Include WordPress
define('WP_USE_THEMES', false);
//require('/a/wordpress/wp-blog-header.php');
require_once ('/a/wordpress/wp-load.php');

$debug_update = false;

/*Stripping the Wordpress additional slashes from GLOBAL vaiables*/
$_POST = array_map( 'stripslashes_deep', $_POST);
$_GET = array_map( 'stripslashes_deep', $_GET);
$_COOKIE    = array_map( 'stripslashes_deep', $_COOKIE );
$_REQUEST   = array_map( 'stripslashes_deep', $_REQUEST );

if ($debug_update === true) {
// NOTE: This debug output breaks the later forward/redirect
     print("\$_POST<br /><pre>");
     print_r($_POST);
     print("</pre>");
     print("\$_GET (should be empty)<br /><pre>");
     print_r($_GET);
     print("</pre>");
}


/*This function represents the form which decides to send or not send email 
  when changes are done to Errata with Super User access */
function send_errata_update_form($in_fields)
{

global $debug_update;

     if ($debug_update) {
          print("<h4>send_errata_update_form()</h4><pre>");
          print_r($in_fields);
          print("</pre>");
     }

     print "\n<p>You have modified an erratum. Click below to send (or not send) " .
           " an errata notification message to the relevant parties.</p>\n";

     print "\n<div class=\"simple_center\">\n";
     print "<form action=\"errata_edit_mail_select.php\" method=\"post\">\n";

     foreach ($in_fields as $key => $value) {
          if ($key != 'submit') {
               print('  <input type="hidden" name="' . htmlspecialchars($key) . '" value="' .
                     htmlspecialchars($value,ENT_QUOTES) . '" />' . "\n");
          }
     }

     print "<input name=\"submit\" " .  "value=\"GO AHEAD, SEND IT\" " .
          "type=\"submit\"/>\n";
     print "<input name=\"submit\" " .  "value=\"NO MESSAGE\" " .
          "type=\"submit\"/>\n";
     print "</form>\n</div>\n";

}



$errata_status_code = $_POST['errata_status_code'];

switch ($errata_status_ids[$errata_status_code]) {
     case STATUS_REJECTED:/*REJECTED*/

              //handle rejection reason

             /*The page format for rejection type*/

              errata_edit_header("RFC Errata Rejection","rfcerrata","scripts/validate_rejection.js","errata_edit_mail_select.php");

              add_action('wp_head','add_errata_reject');

              //Add java script function
              function add_errata_reject(){

                 print "<script src=\"scripts/validate_rejection.js\" type=\"text/javascript\">\n</script>";
              }
              //Add title specifying the dynamic page 
              add_filter( 'wp_title', 'wp_title_errata_reject', 10, 3 );

              function wp_title_errata_reject( $title, $sep, $seplocation ) {
                 return 'RFC Errata Rejection &raquo; RFC Editor';
              }
              //Add body class constant to avoid error404 body class for Wordpress outside page

              function add_body_class_function($classes){

                 return array ('errata_reject');
              }

              add_action('body_class','add_body_class_function');


              get_header();
              get_sidebar('left');

?>
                   <div id="content" class="narrowcolumn">
<?php
                   print '<h1 class="post_title">Reject Errata Report for ' . htmlspecialchars($_POST['rfcid']) . '</h1>';
?>
                   <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
                   <div class="singlepost entry">

<?php
                   print("<p>Please provide a reason why this report is being rejected.</p>\n");
                   print("<p>The reason will be used used in a notification email to the relevant parties.</p>\n");
                   report_header($_POST['rfcid'],$_POST['title'],$_POST['pub-date']);
                   print("<table style=\"background: inherit;\">\n<tr>\n");
                   print("<td>\n");
                   verify_reject_form($_POST);
                   print("\n</td>");
                   print("\n</tr></table>\n");
                   page_footer();

                   break;
     default  :/*VERIFIED/HELD/REPORTED*/
         //Call the database update function  
         list($status,$status_change) = update_errata_superuser_record($_POST);

         if ($debug_update === true) {
            print("<pre>\$status=");
            var_dump($status);
            print ("<pre>\$status_change=");
            var_dump($status_change); 
            print("\n</pre>\n");
         }

         if (is_bool($status) && $status === true) {
            //Add title specifying the dynamic page 
            add_filter( 'wp_title', 'wp_title_errata_update', 10, 3 );

            function wp_title_errata_update( $title, $sep, $seplocation ) {
               return 'Errata Update &raquo; RFC Editor';
            }

            //Add body class constant to avoid error404 body class for Wordpress outside page

            function add_body_class_function($classes){

               return array ('errata_update');
            }

            add_action('body_class','add_body_class_function');
            get_header();
            get_sidebar('left');
?>
<div id="content" class="narrowcolumn">
     <h1 class="post_title">Errata Update Notification</h1>
     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">
<?php

           $_SESSION['rfc'] = substr($_POST['doc-id'],3);
           $_SESSION['include_verified'] ='yes';

           if ($status_change == TRUE) {//Show notification email when there is change in the errata status
               $_SESSION['super_user'] = 'yes'; 
               send_errata_update_form($_POST);
           } else {
?>
           <p>The report has been edited, but its status remains the same. (A notification email has not been sent.) </p>  

<?php
            print("<p><a href=\"errata_edit.php\">Search again</a> for errata.</p>\n");
           }
            page_footer();

         } else {
           // The return type should be a string with an error message.
           //page_header("Database Error","rfcerrata","NONE","errata_edit.php");
     
           //Add title specifying the dynamic page 
               add_filter( 'wp_title', 'wp_title_database_error', 10, 3 );

               function wp_title_database_error( $title, $sep, $seplocation ) {
                  return 'Database Error &raquo; RFC Editor';
               }

           //Add body class constant to avoid error404 body class for Wordpress outside page

               function add_body_class_function($classes){

                  return array ('database_error');
               }

               add_action('body_class','add_body_class_function');


               get_header();
               get_sidebar('left');
?>
<div id="content" class="narrowcolumn">
     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">
<?php


                print('<p class="error">' . htmlspecialchars($status) . "</p>\n");
                page_footer();
          }

          break;
}
?>
