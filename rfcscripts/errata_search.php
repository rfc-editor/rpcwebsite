<?php
  /* Copyright The IETF Trust 2020 All Rights Reserved */
  /* $Id: errata_search.php,v 1.2 2017/05/01 23:01:48 priyanka Exp $ */
  /* April 2017 Updates : Added the redirect link for Errata Id and RFC number - PN */

session_start();
include("errata_headers.php");
#http_redirect(); // if we're on a HTTPS connection, goto HTTP
include("errata_search_lib.php");

$debug_ersea = false;

/*Stripping the Wordpress additional slashes from GLOBAL vaiables*/
$_POST = array_map( 'stripslashes_deep', $_POST);
$_GET = array_map( 'stripslashes_deep', $_GET);
$_COOKIE    = array_map( 'stripslashes_deep', $_COOKIE );
$_REQUEST   = array_map( 'stripslashes_deep', $_REQUEST );

// Include WordPress
define('WP_USE_THEMES', false);
//require('/a/wordpress/wp-blog-header.php');
require_once ('/a/wordpress/wp-load.php');

#page_header("RFC Errata Report","rfcerrata","scripts/validate_rfcsearch.js","errata.php");

add_action('wp_head','add_errata_report_script');

//Add java script function
function add_errata_report_script(){


print "<script src=\"/scripts/validate_rfcsearch.js\" type=\"text/javascript\">\n</script>";

}


//Add title specifying the dynamic page 
add_filter( 'wp_title', 'wp_title_rfc_errata_report', 10, 3 );

function wp_title_rfc_errata_report( $title, $sep, $seplocation ) {
    return 'RFC Errata Report &raquo; RFC Editor';
}

//Add body class constant to avoid error404 body class for Wordpress outside page

function add_body_class_function($classes){

return array ('errata report');
}

add_action('body_class','add_body_class_function');


get_header();
get_sidebar(left);
?>
<div id="content" class="narrowcolumn">
     <h1 class="post_title">RFC Errata</h1>

     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">

<?php
if ($debug_ersea === true) {
     if (isset($_POST)) {
          print("\$_POST<br /><pre>");
          print_r($_POST);
          print("</pre>");
     }
     if (isset($_GET)) {
          print("\$_GET<br /><pre>");
          print_r($_GET);
          print("</pre>");
     }
}
// These next three variables control how the script responds to a query.
// $rfc holds the digits of the RFC doc-id field (data is truncated to
// limit number of digits to the allowed maximum). 
$rfc=null;
$eid=null;
$errata_report=false;
// Advanced Search
$rec_status=$errata_subsets_by_id[STATUS_ALL];
$area_acronym=null;
$errata_type=null;
$wg_acronym=null;
$submitter_name=null;
$stream_name=null;
$submit_date=null;
$presentation='records';

/*
    [rfc] => 
    [eid] => 
    [rec_status] => 15
    [area_acronym] => 
    [errata_type] => 
    [wg_acronym] => 
    [submitter_name] => 
    [stream_name] => 
    [submit_date] => 
    [presentation] => table

 */
$criteria = array(); // Use associative array to hold arguments for query
$criteria['presentation'] = $presentation; // assign a default value

if (array_key_exists('rfc',$_POST)) {
     $rfc = substr($_POST['rfc'],0,MAX_RFC_DIGIT);
}

if (array_key_exists('create',$_POST)) {
     $errata_report = true;
}
// If POST was empty, $rfc & $eid could still be null so check again and set if
// available.
if ($rfc == null && array_key_exists('rfc',$_GET)) {
     $rfc = substr($_GET['rfc'],0,MAX_RFC_DIGIT);
}

if ($eid == null && array_key_exists('eid',$_GET)) {
     $eid = substr($_GET['eid'],0,MAX_EID_DIGIT);
}  

if ($rfc != null && is_numeric($rfc)) $criteria['rfc'] = $rfc;

// These variables are for Advanced Search; these are only supported through
// the GET interface.
if (array_key_exists('rec_status',$_GET)) {
     $rec_status = substr($_GET['rec_status'],0, MAX_SUBSET_CODE);
     if (array_key_exists($rec_status, $errata_subsets_by_id)) {
          $rec_status=$errata_subsets_by_id[$rec_status];
     }
} else {
     $rec_status = $errata_subsets_by_id[STATUS_ALL];
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

if (array_key_exists('presentation',$_GET)) {
     $presentation = strtolower(substr($_GET['presentation'], 0, MAX_PRESENTATION));
     $criteria['presentation'] = $presentation;
}

if ($debug_ersea === true) {
     print("\$rfc="); var_dump($rfc);
     print('<br />');
     print("\$errata_report=");var_dump($errata_report);
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
}

if ($eid != null)  {
// We have a key for one specific report so ignore all other arguments and
// present that report.
     $eidonly = array('eid' => $eid, 'presentation' => 'records');
//     $eidonly['eid'] = $eid;
     create_adv_search_form($eidonly);
     $status = errata_lookup_by_id($eid);
     if (is_string($status)) {
          error_log($status);
          print('<p class="warning">The application has encountered an error. Please try again later.</p>');
     }
} else if ($errata_report && $rfc != null) {
// We have a request to show all of the errata for an RFC so that the user can
// check existing reports before submitting a new one. Ignore other arguments
// and show those reports with the "Report New Errata" link.

// Title and publication date are stored in the rfchead associative
// array if the call completes successfully. If the return type is a
// string, then the call encountered an error and the string contains
// an error message.
     $rfchead = errata_lookup($rfc, 'all');
     if (is_string($rfchead)) {
          error_log($rfchead);
          print('<p class="warning">The application has encountered an error. Please try again later.</p>');
     } else {
	  if ($rfchead === false){
          }else {
          $rfcid = sprintf("RFC%04d",$rfc); // make sure 4 digits in id
          // special handling for titles that contain special characters
          $title = $rfchead['title'];
          print '<form method="post" action="errata_report.php">';
	  print '<input type="hidden" name="rfcid" value="' . htmlspecialchars($rfcid) . '" />';
          print '<input type="hidden" name="pub-date" value="' . htmlspecialchars($rfchead['pub-date']) . '" />';
          print '<input type="hidden" name="title" value="' . htmlspecialchars($title) . '" />';
          print '<input type="submit" name="submit" value="' . htmlspecialchars("Report New Errata for RFC$rfc") . '" />';
          print '</form>';
          print '<p>Search for <a href="errata.php">errata for other RFCs</a>.</p>';
     }
     }
} else {
// This is the default selection. Pass all arguments into routine to display results.
     create_adv_search_form($criteria);
     $rfchead = errata_adv_lookup($criteria);
     if (is_string($rfchead)) {
          error_log($rfchead);
          print('<p class="warning">The application has encountered an error. Please try again later.</p>');
     }
}


print<<<END

<p><a href="/errata.php#reportnew">Report New Errata</a></p>
END;

page_footer();
?>
