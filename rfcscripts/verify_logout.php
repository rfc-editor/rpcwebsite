<?php
  /* Copyright The IETF Trust 2020 All Rights Reserved */
  /* $Id: verify_logout.php,v 1.3 2021/06/17 00:47:33 priyanka Exp $ */
  /* June 2021 : Modified the script for server upgrade - PN                            */
session_start();
include("errata_headers.php");

// Include WordPress
define('WP_USE_THEMES', false);
//require('/a/wordpress/wp-blog-header.php');
require_once ('/a/wordpress/wp-load.php');


unset($_SESSION['verifier_name']); // prevent "Logout" link
errata_edit_header("RFC Errata","rfcerrata", "NONE","verify_login.php");

//Add title specifying the dynamic page 
add_filter( 'wp_title', 'wp_title_verify_logout', 10, 3 );

function wp_title_verify_logout( $title, $sep, $seplocation ) {
    return 'RFC Errata &raquo; RFC Editor';
}

//Add body class constant to avoid error404 body class for Wordpress outside page

function add_body_class_function($classes){

return array ('verify_logout');
}

add_action('body_class','add_body_class_function');


get_header();
get_sidebar('left');



$message = "";

     print<<<END
<div id="content" class="narrowcolumn">
     <h1 class="post_title">Errata Verification Logout</h1>

     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">
END;

if (isset($_SESSION["message"]))  {
     $message .= $_SESSION["message"];
     unset($_SESSION["message"]);
}

print("<p>Thank you  for using the Errata Verification Application.</p>\n");
print("<p>Please send any comments on this process to the 
      <a href=\"mailto:rfc-editor@rfc-editor.org\">RFC Editor</a>.</p>");
print("$message\n");
print("<p>Click <a href=\"verify_login.php\">here</a> to log in.</p>\n");
//errata_edit_footer(); // don't want old session data displayed.
page_footer();
session_destroy();
?>
