<?php
  /* Copyright The IETF Trust 2020 All Rights Reserved */
  /* $Id: errata_edit_select.php,v 1.1.1.1 2016/02/08 21:03:50 priyanka Exp $ */
session_start();
include("errata_headers.php");
include("errata_lib.php");
include("errata_authen_lib.php");

session_authenticate();

$debug_esel = false;

// Include WordPress
define('WP_USE_THEMES', false);
//require('/a/wordpress/wp-blog-header.php');
require_once ('/a/wordpress/wp-load.php');

//Add title specifying the dynamic page 
add_filter( 'wp_title', 'wp_errata_edit_select', 10, 3 );
function wp_errata_edit_select( $title, $sep, $seplocation ) {
	return 'Errata Edit Selection &raquo; RFC Editor';
}
//Add body class constant to avoid error404 body class for Wordpress outside page
add_action('body_class','add_body_class_function');
  function add_body_class_function($classes){
	return array ('errata_edit_selection');
 }

/*Add Wordpress header and Sidebar*/
get_header();
get_sidebar(left);


errata_edit_header("RFC Errata Edit Selection","rfcerrata","NONE","errata_edit.php");

/*Stripping the Wordpress additional slashes from GLOBAL vaiables*/
$_POST = array_map( 'stripslashes_deep', $_POST);
$_GET = array_map( 'stripslashes_deep', $_GET);
$_COOKIE    = array_map( 'stripslashes_deep', $_COOKIE );
$_REQUEST   = array_map( 'stripslashes_deep', $_REQUEST );


if ($debug_esel === true) {
     print("\$_POST<br /><pre>");
     print_r($_POST);
     print("</pre>");
     print("\$_GET<br /><pre>");
     print_r($_GET);
     print("</pre>");
     print("\$_COOKIE<br /><pre>");
     print_r($_COOKIE);
     print("</pre>");
     print("\$_SESSION<br /><pre>");
     print_r($_SESSION);
     print("</pre>");
}

$rfc=null;
$unverified_only = true;
if (array_key_exists('rfc',$_POST)) {
     $rfc = substr($_POST['rfc'],0,MAX_RFC);
} else if (isset($_SESSION['rfc'])) {
     $rfc = $_SESSION['rfc'];
     unset($_SESSION['rfc']);
}
if (array_key_exists('include_verified',$_POST)) {
     $unverified_only = false;
} else if (isset($_SESSION['include_verified'])) {
     if ($_SESSION['include_verified'] == 'yes') {
          $unverified_only = false;
          unset($_SESSION['include_verified']);
     }
}

if ($debug_esel === true) {
     print("\$rfc="); var_dump($rfc);
     print('<br />');
     print("\$unverified_only="); var_dump($unverified_only);
     print('<br />');
}

print<<<END
<div id="content" class="narrowcolumn">
     <h1 class="post_title"></h1>

     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">

END;

if ($rfc == null) {
     print<<<END
<p class="warning">Cannot call this page without an RFC number.</p>
END;
} else {
     $status = errata_edit_lookup($rfc,$unverified_only); // return string if error
     if (is_string($status)) {
          error_log($status);
          print('<p class="warning">The application has encountered an error. Please try again later.</p>');
     }
     print("<p><a href=\"errata_edit.php\">Search again</a> for more reported errata.</p>\n");
}
errata_edit_footer();
?>
