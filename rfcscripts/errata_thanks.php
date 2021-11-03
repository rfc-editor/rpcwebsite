<?php
  /* Copyright The IETF Trust 2020 All Rights Reserved */
  /* $Id: errata_thanks.php,v 1.3 2021/06/17 00:47:33 priyanka Exp $ */
  /* June 2021 : Modified the script for server upgrade - PN                            */
session_start();
$debug_thanks = false;
include("errata_headers.php");
#http_redirect(); // if we're on a HTTPS connection, goto HTTP
include("errata_lib.php");
// Include WordPress
define('WP_USE_THEMES', false);
//require('/a/wordpress/wp-blog-header.php');
require_once ('/a/wordpress/wp-load.php');

#page_header("Thank You for Your Report","rfcerrata","NONE");
#errata_nav();

/*Stripping the Wordpress additional slashes from GLOBAL vaiables*/
$_POST = array_map( 'stripslashes_deep', $_POST);
$_GET = array_map( 'stripslashes_deep', $_GET);
$_COOKIE    = array_map( 'stripslashes_deep', $_COOKIE );
$_REQUEST   = array_map( 'stripslashes_deep', $_REQUEST );

//Add title specifying the dynamic page 
add_filter( 'wp_title', 'wp_title_errata_thanks', 10, 3 );

function wp_title_errata_thanks( $title, $sep, $seplocation ) {
    return 'Thank you for your Report &raquo; RFC Editor';
}

//Add body class constant to avoid error404 body class for Wordpress outside page

function add_body_class_function($classes){

return array ('errata');
}

add_action('body_class','add_body_class_function');


get_header();
get_sidebar('left');
?>
<div id="content" class="narrowcolumn">
     <h1 class="post_title">Thank You</h1>

     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">
<?php
if ($debug_thanks === true) {
// NOTE: This debug output breaks the later forward/redirect
     print("\$_POST<br /><pre>");
     print_r($_POST);
     print("</pre>");
     print("\$_GET<br /><pre>");
     print_r($_GET);
     print("</pre>");
     print("\$_SESSION<br /><pre>");
     print_r($_SESSION);
     print("</pre>");
}

     print<<<END
<p>
Thank you for reporting errata. You will receive email from 
rfc-editor@rfc-editor.org that begins the verification process.
</p>
<hr />
END;
if (array_key_exists('reload_message',$_SESSION) &&
    isset($_SESSION['reload_message'])) {
     print($_SESSION['reload_message']);
     unset($_SESSION['reload_message']);
}
page_footer();

$_SESSION['report_count'] = 0;  /* Reset the count after showing this page. */

?>
