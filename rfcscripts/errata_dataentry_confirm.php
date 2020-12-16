<?php
  /* Copyright The IETF Trust 2020 All Rights Reserved */
  /* $Id: errata_dataentry_confirm.php,v 1.1.1.1 2016/02/08 21:03:50 priyanka Exp $ */
session_start();
include("errata_authen_lib.php");
session_authenticate();

include("errata_headers.php");
include("errata_lib.php");

$debug_deconfirm = false;

// Include WordPress
define('WP_USE_THEMES', false);
//require('/a/wordpress/wp-blog-header.php');
require_once ('/a/wordpress/wp-load.php');

//Add title specifying the dynamic page 
add_filter( 'wp_title', 'wp_errata_data_entry_confirm', 10, 3 );
function wp_errata_data_entry_confirm( $title, $sep, $seplocation ) {
	return 'Errata Data Entry Confirm &raquo; RFC Editor';
}
//Add body class constant to avoid error404 body class for Wordpress outside page
add_action('body_class','add_body_class_function');
  function add_body_class_function($classes){
	return array ('data_entry_confirm');
 }

/*Add Wordpress header and Sidebar*/
get_header();
get_sidebar(left);




page_header("Errata Data Entry Confirm","rfcerrata","NONE","errata_dataentry.php");

/*Stripping the Wordpress additional slashes from GLOBAL vaiables*/
$_POST = array_map( 'stripslashes_deep', $_POST);
$_GET = array_map( 'stripslashes_deep', $_GET);
$_COOKIE    = array_map( 'stripslashes_deep', $_COOKIE );
$_REQUEST   = array_map( 'stripslashes_deep', $_REQUEST );


if ($debug_deconfirm === true) {
     print("\$_POST<br /><pre>");
     print_r($_POST);
     print("</pre>");
     print("\$_GET (should be empty)<br /><pre>");
     print_r($_GET);
     print("</pre>");
     print("\$_SESSION<br /><pre>");
     print_r($_SESSION);
     print("</pre>");
}



print<<<END
<div id="content" class="narrowcolumn">
     <h1 class="post_title"></h1>

     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">

END;

if (array_key_exists('rfcnum',$_POST)) {
     $rfcnum  = substr($_POST['rfcnum'],0,MAX_RFC_DIGIT);
     $_POST['doc-id'] = sprintf("RFC%04d",$rfcnum); // Shared code uses both...
     $_POST['rfcid']  = sprintf("RFC%04d",$rfcnum); // ...no time to cleanup...
     $rfchead = get_title_pub_date($rfcnum);
     if ($rfchead === false) {
          print<<<END

     <p class="error">{$_SESSION['errata_lib_error']}</p>
END;
      page_footer();

          exit;
     }
     print<<<END
          <h1 class='maintitle'>Add Errata for {$_POST['rfcid']}</h1>
<p>This is how the record would look after adding it to the database.
If it is correct, you may confrim the report or confirm the report and report
more errata for this RFC. If there is an error in the report, you
may select the edit button to continue editing this report.
</p>
<hr />
END;

     report_header($_POST['doc-id'],$rfchead['title'],$rfchead['pub-date']);
     display_record($_POST);
     print("<table>\n<tr>\n");
     print("<td>\n");
     edit_report_again_form($_POST, "errata_dataentry.php");
     print("\n</td>\n");
     print("<td>\n");
     insert_report_form($_POST, false, "errata_dataentry_insert.php");
     print("\n</td>\n");
     print("<td>\n");
     print<<<END
<form name="abort" action="errata_dataentry.php" method="post">
    <input type="submit" name="cancel" value="Cancel">
</form>
END;
     print("\n</td>\n");

     print("\n</tr></table>\n");

} else {
     print("<p>There's nothing for me to do here!</p>");
}

page_footer();

?>
