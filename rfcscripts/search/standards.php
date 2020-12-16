<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
include("standards_headers.php");
#include("rfc_search_lib.php");
include("standards_detail.php");


$criteria = array();
//page_header("Official Internet Protocol Standards","js/jquery.js","js/validate_rfcsearch.js","","css/rfcsearch_new.css","");
// Include WordPress
define('WP_USE_THEMES', false);
//require('/a/wordpress/wp-blog-header.php');
require_once ('/a/wordpress/wp-load.php');


//Add title specifying the dynamic page 
add_filter( 'wp_title', 'wp_title_standards_page', 10, 3 );

function wp_title_standards_page( $title, $sep, $seplocation ) {
    return 'Official Internet Protocol Standards &raquo; RFC Editor';
}

//Add body class constant to avoid error404 body class for Wordpress outside page

function add_body_class_function($classes){

return array ('standards');
}

add_action('body_class','add_body_class_function');

get_header();
create_search_view();

//get_footer();
page_footer();

?>
