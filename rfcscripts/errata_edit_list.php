<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
session_start();
include("errata_headers.php");

// Include WordPress
define('WP_USE_THEMES', false);
//require('/a/wordpress/wp-blog-header.php');
require_once ('/a/wordpress/wp-load.php');

//Add title specifying the dynamic page 
add_filter( 'wp_title', 'wp_errata_edit_list', 10, 3 );
function wp_errata_edit_list( $title, $sep, $seplocation ) {
	return 'RFC Errata &raquo; RFC Editor';
}
//Add body class constant to avoid error404 body class for Wordpress outside page
add_action('body_class','add_body_class_function');
  function add_body_class_function($classes){
	return array ('errata_edit_list');
 }

/*Add Wordpress header and Sidebar*/
get_header();
get_sidebar(left);



errata_edit_header("RFC Errata","rfcerrata","NONE","errata.php");
//errata_nav();


print<<<END
<div id="content" class="narrowcolumn">
     <h1 class="post_title">Edit Errata for a particular RFC</h1>
     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">
     <br>
END;

print<<<END

<form method="post" action="errata_edit_select.php">
  <table>
    <tr>
     <td colspan="2">
        <label for="rfc"><strong>Enter RFC Number:</strong></label>
     </td>
     <td>
        <input type="text" name="rfc" size="10" maxlength="4" />
     </td>
      <td>
        <input type="submit" name="query"  value="Find Verified Errata">
      </td>
    </tr>
  </table>
</form>

<h2>More Information</h2>
<p>
See these links for more information on how to report errata and an overview of the process.
</p>
<ul>
  <li>How to report errata.</li>
  <li>Overview of the process</li>
</ul>
END;

page_footer();
?>
