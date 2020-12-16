<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
session_start();
include("errata_headers.php");
include("errata_lib.php");
include("errata_authen_lib.php");

session_authenticate();

// Include WordPress
define('WP_USE_THEMES', false);
//require('/a/wordpress/wp-blog-header.php');
require_once ('/a/wordpress/wp-load.php');

#$message = "Processing of the report is complete.";
$message = "The report has been edited, but its status remains the same. (A notification email has not been sent.) ";
if (isset($_SESSION['message'])) {
     $message = $_SESSION['message'];
     unset($_SESSION['message']);
}
//errata_edit_header("Action Complete","rfcerrata","NONE","verify_errata.php");

//Add title specifying the dynamic page 
add_filter( 'wp_title', 'wp_title_verify_complete', 10, 3 );

function wp_title_verify_complete( $title, $sep, $seplocation ) {
    return 'Action Complete &raquo; RFC Editor';
}

//Add body class constant to avoid error404 body class for Wordpress outside page

function add_body_class_function($classes){

return array ('verify_complete');
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

errata_edit_header("Action Complete","rfcerrata","NONE","verify_errata.php");

    print<<<END
<p>
$message
</p>

<table>
  <tr>
      <td>[<a href="verify_errata_select.php">Review more reports for the same RFC</a>]</td>
      <td>[<a href="verify_errata.php">Look up more errata</a>]</td>
  </tr>
</table>
END;

errata_edit_footer();
?>
