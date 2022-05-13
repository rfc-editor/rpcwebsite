<?php
  /* Copyright The IETF Trust 2020 All Rights Reserved */
  /* $Id: errata_insert.php,v 1.7 2022/05/10 22:46:45 priyanka Exp $ */
  /* June 2021 : Modified the script for server upgrade - PN                            */
  /* January 2022 : Modified the script to block few submitter emailss - PN            */
  /* April 2022 : Removed the Math problem and added Google re-Captcha to handle bot submission - PN */
  /* May 2022 : Modified the script to block submitter emails - PN            */
session_start();
include("errata_headers.php");
include("errata_lib.php");
include("errata_mail_lib.php");

// Include WordPress
define('WP_USE_THEMES', false);
//require('/a/wordpress/wp-blog-header.php');
require_once ('/a/wordpress/wp-load.php');

/*Stripping the Wordpress additional slashes from GLOBAL vaiables*/
$_POST = array_map( 'stripslashes_deep', $_POST);
$_GET = array_map( 'stripslashes_deep', $_GET);
$_COOKIE    = array_map( 'stripslashes_deep', $_COOKIE );
$_REQUEST   = array_map( 'stripslashes_deep', $_REQUEST );

$debug_insert = false;

if ($debug_insert === true) {
// NOTE: This debug output breaks the later forward/redirect
     print("\$_POST<br /><pre>");
     print_r($_POST);
     print("</pre>");
     print("\$_GET (should be empty)<br /><pre>");
     print_r($_GET);
     print("</pre>");
     print("\$_SESSION (might be empty)<br /><pre>");
     print_r($_SESSION);
     print("</pre>");
}

$report_crc32 =  crc32($_POST['errata_type_code'] .
                       $_POST['section'] . 
                       $_POST['orig_text'] .
                       $_POST['correct_text'] .
                       $_POST['notes']);

if (array_key_exists('report_history',$_SESSION) && 
    isset($_SESSION['report_history'])) {
     foreach ($_SESSION['report_history'] as $report_hash) {
          if ($report_hash == $report_crc32) {
               $_SESSION['reload_message'] = '<p>Looks like you already submitted this one!</p>' . "\n";
               //header('Location: errata_thanks.php'); /* forward to thank you page */
               ams_redirect("errata_thanks.php");
               exit;
          }
     }
} else {
     $_SESSION['report_history'] = array(); // create array
}

$captcha_error = "";
$bloack_email_list = array('dryan1008@aol.com','poonhlam6@gmail.com','jamesmrice2020@gmail.com','mannya23@live.com','mannyman4208@gmail.com','mannyman123442@yahoo.com','zininoosai@gmail.com');

if (array_key_exists('next',$_POST)) {
   if(!isset($_POST['g-recaptcha-response']) || empty($_POST['g-recaptcha-response'])) {
       $captcha_error = 1;
   } else {
              $secret_key = '6Lc-ruweAAAAAIX1frp3hg6NhKoBu7iz7j8t_nlh';
              $ch = curl_init();
              //Requesting google reCaptcha call
              curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify?secret='.$secret_key.'&response='.$_POST['g-recaptcha-response']);
              curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
              $response = curl_exec($ch);
              curl_close($ch);
              $response = json_decode($response);

              if($response->success) {
              // What happens when the CAPTCHA was entered correctly
                  if (in_array($_POST['submitter_email'],$bloack_email_list)){#Check if blocked email address
                       # Do nothing as we need to block the address 
                  } else {
                      $next = $_POST['next'];
                      $status = insert_errata_report($_POST); #Enter the report to database
                  }
              } else {
                  $captcha_error = 1;
              }
    }
}

if ($debug_insert === true) {
     print("<pre>\$status=");
     var_dump($status);
     print("\n\$next=");
     var_dump($next);
     printf("\$report_crc32=%u",$report_crc32);
     print("\n</pre>\n");
}

if (is_bool($status) && $status === true) {
     $_SESSION['report_history'][] = $report_crc32; // Remember for Reload
     $_SESSION['report_count'] += 1;
     $status = generate_report_ack($_POST);
     if (is_bool($status) && $status === true) {
               if ($debug_insert === false) {
                    //header('Location: errata_thanks.php'); /* forward to thank you page */
                    ams_redirect("errata_thanks.php");
                    exit;
               }
     } else {
        //Add title specifying the dynamic page 
        add_filter( 'wp_title', 'wp_errata_insert', 10, 3 );
        function wp_errata_insert( $title, $sep, $seplocation ) {
                return 'Application Error &raquo; RFC Editor';
        }
        //Add body class constant to avoid error404 body class for Wordpress outside page
        add_action('body_class','add_body_class_function');
          function add_body_class_function($classes){
                return array ('application_error');
          }

	/* END Code to add the Javascript, Body class name and Title name as per WP*/

 	/*Add Wordpress header and Sidebar*/
	get_header();
	get_sidebar('left');

	/*Add DIV use with wordpress for content*/
?>
     
<div id="content" class="narrowcolumn">
     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">
<?php 
          page_header("Application Error", "rfcerrata","NONE","errata.php");
          error_log($status);
          if (isset($captcha_error)) {
              print ('<p class="error"> reCAPTHCA verification failed, please try again.</p>' ."\n");
          } else {  
              print('<p class="error">The application has encountered an error. Please try again later.</p>' . "\n");
          }   
          print("<p><a href=\"errata.php\">Search again</a> for errata</p>\n");
          page_footer();
     }
} else {

        //Add title specifying the dynamic page 
        add_filter( 'wp_title', 'wp_errata_insert', 10, 3 );
        function wp_errata_insert( $title, $sep, $seplocation ) {
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
	get_sidebar('left');

	/*Add DIV use with wordpress for content*/
?>
     
<div id="content" class="narrowcolumn">
     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">
<?php 


     // The return type should be a string with an error message.
     page_header("Database Error","rfcerrata","NONE","errata.php");
     error_log($status);
     if (isset($captcha_error)) {
              print ('<p class="error"> reCAPTHCA verification failed, please try again.</p>' ."\n");
          } else {
              print('<p class="error">The application has encountered an error. Please try again later.</p>' . "\n");
     }
     print("<p><a href=\"errata.php\">Search again</a> for errata</p>\n");
     page_footer();
}

?>
