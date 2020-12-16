<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
session_start();
include("errata_headers.php");
include("errata_lib.php");
include("errata_authen_lib.php");

session_authenticate();

$debug_edit_complete = false;

// Include WordPress
define('WP_USE_THEMES', false);
//require('/a/wordpress/wp-blog-header.php');
require_once ('/a/wordpress/wp-load.php');

/*Stripping the Wordpress additional slashes from GLOBAL vaiables*/
$_POST = array_map( 'stripslashes_deep', $_POST);
$_GET = array_map( 'stripslashes_deep', $_GET);
$_COOKIE    = array_map( 'stripslashes_deep', $_COOKIE );
$_REQUEST   = array_map( 'stripslashes_deep', $_REQUEST );


if ($debug_edit_complete === true) {
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

$message = "The report has been edited, but its status remains the same. (A notification email has not been sent.) ";
if (isset($_SESSION['message'])) {
     $message = $_SESSION['message'];
     unset($_SESSION['message']);
}
//errata_edit_header("Action Complete","rfcerrata","NONE","verify_errata.php");

//Add title specifying the dynamic page 
add_filter( 'wp_title', 'wp_title_errata_edit_complete', 10, 3 );

function wp_title_errata_edit_complete( $title, $sep, $seplocation ) {
    return 'Errata Edit Complete &raquo; RFC Editor';
}

//Add body class constant to avoid error404 body class for Wordpress outside page

function add_body_class_function($classes){

return array ('errata_edit_complete');
}

add_action('body_class','add_body_class_function');


get_header();
get_sidebar(left);



     print<<<END
<div id="content" class="narrowcolumn">
<h1 class="post_title">Action Complete</h1>
<div class="post-127 page type-page status-publish hentry post clear" id="post-127">
<div class="singlepost entry">
END;

errata_edit_header("Action Complete","rfcerrata","NONE","errata_edit.php");

    print<<<END
<p>
$message
</p>

<table>
  <tr>
      <td>[<a href="errata_edit_select.php">Review more reports for the same RFC</a>]</td>
      <td>[<a href="errata_edit.php">Search again</a> for errata.]</td>
  </tr>
</table>
END;

errata_edit_footer();
?>

