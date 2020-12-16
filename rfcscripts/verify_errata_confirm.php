<?php
  /* Copyright The IETF Trust 2020 All Rights Reserved */
  /* $Id: verify_errata_confirm.php,v 1.1.1.1 2016/02/08 21:03:50 priyanka Exp $ */
session_start();
include("errata_headers.php");
include("errata_lib.php");
include("errata_mail_lib.php");
include("errata_authen_lib.php");

session_authenticate();

// Include WordPress
define('WP_USE_THEMES', false);
//require('/a/wordpress/wp-blog-header.php');
require_once ('/a/wordpress/wp-load.php');

/*Stripping the Wordpress additional slashes from GLOBAL vaiables*/
$_POST = array_map( 'stripslashes_deep', $_POST);
$_GET = array_map( 'stripslashes_deep', $_GET);
$_COOKIE    = array_map( 'stripslashes_deep', $_COOKIE );
$_REQUEST   = array_map( 'stripslashes_deep', $_REQUEST );


$debug_v_confirm = false;

if ($debug_v_confirm === true) {
     print("\$_POST<br /><pre>");
     print_r($_POST);
     print("</pre>");
     print("\$_GET (should be empty)<br /><pre>");
     print_r($_GET);
     print("</pre>");
}

if (array_key_exists('submit',$_POST)) {
     $rfcid    = substr($_POST['rfcid'], 0, MAX_RFC);
     $doc_id   = substr($_POST['doc-id'], 0, MAX_RFC);
     $pub_date = substr($_POST['pub-date'], 0, MAX_DATE);
     $title    = $_POST['title'];
     path_selector($_POST);
     errata_edit_footer();
} else {
     //header("Location: http://{$_SERVER['SERVER_NAME']}/verify_errata.php"); // get out of here
     ams_redirect("verify_errata.php");
     exit;
}

?>
