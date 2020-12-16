<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
include("errata_headers.php");
include("errata_lib.php");

// Include WordPress
define('WP_USE_THEMES', false);
//require('/a/wordpress/wp-blog-header.php');
require_once ('/a/wordpress/wp-load.php');

/*page_header("RFC Errata Listing");
errata_nav();
*/

//Add title specifying the dynamic page 
add_filter( 'wp_title', 'wp_title_errata_list', 10, 3 );

function wp_title_errata_list( $title, $sep, $seplocation ) {
    return 'RFC Errata Listing &raquo; RFC Editor';
}

//Add body class constant to avoid error404 body class for Wordpress outside page

function add_body_class_function($classes){

return array ('errata_list');
}

add_action('body_class','add_body_class_function');


get_header();
get_sidebar(left);
?>
<div id="content" class="narrowcolumn">
     <h1 class="post_title">Errata Listing</h1>
     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">
<?php


print <<<END

<p>Published RFCs never change.
Although every published RFC has been submitted to
careful proofreading by the RFC Editor and the author(s),
errors do sometimes go undetected.</p>

<p>Note that this list of errata may include both verified and
unverified errata. Those marked "Verified" have been approved by the
authors or the relevant party (e.g., the IESG for IETF
documents). Those marked "Reported" have not yet been verified.</p>

<p>To report errata,  please use the <a href="errata.php">Errata Query
Form</a>.</p>
<hr>
END;

errata_list();

page_footer();
?>
