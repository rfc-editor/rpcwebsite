<?php
  /* $Id: verify_reject.php,v 1.3 2021/10/08 20:35:42 priyanka Exp $ */
  /* June 2021 : Modified the script for server upgrade - PN                            */
  /* October 2021 : Modified the script for imrpovements  - PN   */  
session_start();
include("errata_headers.php");
include("errata_lib.php");
include("errata_authen_lib.php");

$debug_ve = false;

session_authenticate();

/*Stripping the Wordpress additional slashes from GLOBAL vaiables*/
$_POST = array_map( 'stripslashes_deep', $_POST);
$_GET = array_map( 'stripslashes_deep', $_GET);
$_COOKIE    = array_map( 'stripslashes_deep', $_COOKIE );
$_REQUEST   = array_map( 'stripslashes_deep', $_REQUEST );


// Include WordPress
define('WP_USE_THEMES', false);
//require('/a/wordpress/wp-blog-header.php');
require_once ('/a/wordpress/wp-load.php');

/*
** Begin page output **
 */
errata_edit_header("RFC Errata Rejection","rfcerrata","scripts/validate_rejection.js","verify_errata.php");

add_action('wp_head','add_verify_reject');

//Add java script function
function add_verify_reject(){


print "<script src=\"scripts/validate_rejection.js\" type=\"text/javascript\">\n</script>";

}

//Add title specifying the dynamic page 
add_filter( 'wp_title', 'wp_title_verify_reject', 10, 3 );

function wp_title_verify_reject( $title, $sep, $seplocation ) {
    return 'RFC Errata Rejection &raquo; RFC Editor';
}

//Add body class constant to avoid error404 body class for Wordpress outside page

function add_body_class_function($classes){

return array ('verify_reject');
}

add_action('body_class','add_body_class_function');


get_header();
get_sidebar('left');


if ($debug_ve === true) {
     print("\$_POST<br /><pre>");
     print_r($_POST);
     print("</pre>");
     print("\$_GET<br /><pre>");
     print_r($_GET);
     print("</pre>");
}

//print("<p class=\"warning\">This  system is in beta test! RFC Editor use <i>only</i>!</p>\n");
if (array_key_exists('submit',$_POST)) {
     // We have POST form data. Determine which style and proceed
     if (array_key_exists('rfcid', $_POST)) {
?>
     
<div id="content" class="narrowcolumn">
<?php    
      print '<h1 class="post_title">Reject Errata Report for ' . htmlspecialchars($_POST['rfcid']) . '</h1>';
?>
     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">

<?php
     //     print("<h1 class='maintitle'>Reject Errata Report for " . $_POST['rfcid'] . "</h1>\n");
          print("<p>Please provide a reason why this report is being rejected.</p>\n");
          print("<p>The reason will be used used in a notification email to the relevant parties.</p>\n");
          report_header($_POST['rfcid'],$_POST['title'],$_POST['pub-date']);
          display_record($_POST);
          print("<table style=\"background: inherit;\">\n<tr>\n");
          print("<td>\n");
          verify_reject_form($_POST);
          print("\n</td>\n</tr><tr>");
          print("<td>\n");
          print<<<END
               <form action="verify_errata.php" method="post">
               <input type="submit" name="cancel" value="Cancel" />
               </form>
END;
          print("\n</td>\n");
          print("\n</tr></table>\n");
     } 
} else {

     print<<<END
<div id="content" class="narrowcolumn">
<div class="post-127 page type-page status-publish hentry post clear" id="post-127">
<div class="singlepost entry">
<p class="warning">
Select an RFC to <a href="verify_errata.php">verify errata</a> for before using this page.
</p>

END;
}

errata_edit_footer();
?>
