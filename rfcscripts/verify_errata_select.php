<?php
  /* Copyright The IETF Trust 2020 All Rights Reserved */
  /* $Id: verify_errata_select.php,v 1.1.1.1 2016/02/08 21:03:50 priyanka Exp $ */
session_start();
include("errata_headers.php");
include("errata_search_lib.php");
include("errata_authen_lib.php");

session_authenticate();

$debug_vsel = false;

/*Stripping the Wordpress additional slashes from GLOBAL vaiables*/
$_POST = array_map( 'stripslashes_deep', $_POST);
$_GET = array_map( 'stripslashes_deep', $_GET);
$_COOKIE    = array_map( 'stripslashes_deep', $_COOKIE );
$_REQUEST   = array_map( 'stripslashes_deep', $_REQUEST );

// Include WordPress
define('WP_USE_THEMES', false);
//require('/a/wordpress/wp-blog-header.php');
require_once ('/a/wordpress/wp-load.php');

//errata_edit_header("RFC Errata Verification Selection","rfcerrata","scripts/validate_rfcsearch.js","verify_errata.php");

add_action('wp_head','add_verify_errata_select_script');

//Add java script function
function add_verify_errata_select_script(){


print "<script src=\"scripts/validate_rfcsearch.js\" type=\"text/javascript\">\n</script>";

}

//Add title specifying the dynamic page 
add_filter( 'wp_title', 'wp_title_verify_login', 10, 3 );

function wp_title_verify_login( $title, $sep, $seplocation ) {
    return 'RFC Errata Verification Selection &raquo; RFC Editor';
}

//Add body class constant to avoid error404 body class for Wordpress outside page

function add_body_class_function($classes){

return array ('verify_errata_select');
}

add_action('body_class','add_body_class_function');


get_header();
get_sidebar(left);


if ($debug_vsel === true) {
     print("<!--\n");
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
     print("\n-->\n");
}

$rfc=null;
$eid=null;
$unverified_only = true;
// Advanced Search
$rec_status=$errata_subsets_by_id[STATUS_REPORTED];
$area_acronym=null;
$errata_type=null;
$wg_acronym=null;
$submitter_name=null;
$stream_name=null;
$submit_date=null;
$presentation='table';

$criteria = array(); // Use associative array to hold arguments for query
$criteria['presentation'] = $presentation; // assign a default value

if (array_key_exists('rfc',$_POST)) {
     $rfc = substr($_POST['rfc'],0,MAX_RFC_DIGIT);
} else if (isset($_SESSION['rfc'])) {
     $rfc = $_SESSION['rfc'];   // use it
     unset($_SESSION['rfc']);   // and remove it
} else if (array_key_exists('rfc',$_GET)) {
     $rfc = substr($_GET['rfc'],0,MAX_RFC_DIGIT);
}
if (array_key_exists('eid',$_POST)) {
     $eid = substr($_POST['eid'], 0, MAX_EID_DIGIT);
} else if (array_key_exists('eid',$_GET)) {
     $eid = substr($_GET['eid'], 0, MAX_EID_DIGIT);
}

if ($rfc != null && is_numeric($rfc)) $criteria['rfc'] = $rfc;
if ($eid != null && is_numeric($eid)) $criteria['eid'] = $eid;

// These variables are for Advanced Search; these are only supported through
// the GET interface.
if (array_key_exists('rec_status',$_GET)) {
     $rec_status = substr($_GET['rec_status'],0, MAX_SUBSET_CODE);
     if (array_key_exists($rec_status, $errata_subsets_by_id)) {
          $rec_status=$errata_subsets_by_id[$rec_status];
     }
} else {
     $rec_status = $errata_subsets_by_id[STATUS_REPORTED];
}
$criteria['rec_status'] = $rec_status;

if (array_key_exists('area_acronym',$_GET)) {
     $area_acronym = strtolower(substr($_GET['area_acronym'], 0, MAX_ACRONYM));
     if ($area_acronym != null) $criteria['area_acronym'] = $area_acronym;
}

if (array_key_exists('errata_type',$_GET)) {
     $errata_type = strtolower(substr($_GET['errata_type'], 0, MAX_TYPE));
     if ($errata_type != null) $criteria['errata_type'] = $errata_type;
}

if (array_key_exists('wg_acronym',$_GET)) {
     $wg_acronym = substr($_GET['wg_acronym'], 0, MAX_ACRONYM);
     if ($wg_acronym != null) $criteria['wg_acronym'] = $wg_acronym;
}

if (array_key_exists('submitter_name',$_GET)) {
     $submitter_name = substr($_GET['submitter_name'], 0, MAX_NAME);
     if ($submitter_name != null) $criteria['submitter_name'] = $submitter_name;
}

if (array_key_exists('stream_name',$_GET)) {
     $stream_name = substr($_GET['stream_name'], 0, MAX_NAME);
     if ($stream_name != null) $criteria['stream_name'] = $stream_name;
}

if (array_key_exists('submit_date',$_GET)) {
     $submit_date = strtolower(substr($_GET['submit_date'], 0, MAX_DATE));
     if ($submit_date != null) $criteria['submit_date'] = $submit_date;
}

if ($debug_vsel === true) {
     print("<!--\n");
     print("\$rfc="); var_dump($rfc);
     print('<br />');
     print('$eid=');var_dump($eid);
     print('<br />');
     print('$rec_status='); var_dump($rec_status);
     print('<br />');
     print('$area_acronym='); var_dump($area_acronym);
     print('<br />');
     print('$errata_type='); var_dump($errata_type);
     print('<br />');
     print('$wg_acronym='); var_dump($wg_acronym);
     print('<br />');
     print('$submitter_name='); var_dump($submitter_name);
     print('<br />');
     print('$stream_name='); var_dump($stream_name);
     print('<br />');
     print('$submit_date='); var_dump($submit_date);
     print('<br />');
     print('$presentation='); var_dump($presentation);
     print('<br />');
     print('<pre>$criteria='); var_dump($criteria);
     print("</pre>\n");
     print("\n-->\n");
}

     print<<<END
<div id="content" class="narrowcolumn">
     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">
END;

errata_edit_header("RFC Errata Verification Selection","rfcerrata","scripts/validate_rfcsearch.js","verify_errata.php");

if ($eid != null) {
     $rfc = get_rfc_num($eid);
     if ($rfc === false) {
          error_log($_SESSION['errata_lib_error']);
          unset($_SESSION['errata_lib_error']);
     } else if ($rfc == 0) {
          print("<p class=\"warning\">No errata with id " . htmlspecialchars($eid) . "</p>\n");
     } else {
          if ((access_is_allowed($rfc, $_SESSION['ssp_id']) && is_reported_status($eid)) ||
              is_privileged_user()) {
               $status = errata_edit_lookup($rfc, false, VERIFY_FORMS, ($eid != null), $eid);
               if (is_string($status)) {
                    error_log($status);
                    print('<p class="warning">The application has encountered an error. Please try again later.</p>');
               }
          } else {
               access_denied();
          }
     }
} else {
     create_adv_search_form($criteria,"verify_errata_select.php", true);
     $criteria['edit_eid_link'] = true;
     $status = errata_adv_lookup($criteria);
     if (is_string($status)) {
          error_log($status);
          print('<p class="warning">The application has encountered an error. Please try again later.</p>');
     }
}

print("<p><a href=\"verify_errata.php\">Search again</a> for more reported errata</p>\n");
errata_edit_footer();
?>
