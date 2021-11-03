<?php
  /* Copyright The IETF Trust 2020 All Rights Reserved */
  /* $Id: errata_edit.php,v 1.3 2021/06/17 00:47:33 priyanka Exp $ */
  /* June 2021 : Modified the script for server upgrade - PN                            */
session_start();
include("errata_headers.php");
include("errata_lib.php");
include("errata_authen_lib.php");
include_once("ams_util_lib.php");

$debug_eed = false;

// Include WordPress
define('WP_USE_THEMES', false);
//require('/a/wordpress/wp-blog-header.php');
require_once ('/a/wordpress/wp-load.php');

/*Stripping the Wordpress additional slashes from GLOBAL vaiables*/
$_POST = array_map( 'stripslashes_deep', $_POST);
$_GET = array_map( 'stripslashes_deep', $_GET);
$_COOKIE    = array_map( 'stripslashes_deep', $_COOKIE );
$_REQUEST   = array_map( 'stripslashes_deep', $_REQUEST );


session_authenticate();
if (!is_privileged_user()) {
     // header("Location: http://{$_SERVER['SERVER_NAME']}/verify_errata.php"); // redirect to verify page
     ams_redirect("verify_errata.php");
     exit;
}

function edit_form() {
     print '
<h2>Edit Errata for a particular RFC</h2>
<form method="post" id="query_by_doc" action="errata_edit_select.php" onsubmit="return validateRFCEditSearchForm(this);">
  <table>
    <tr>
     <td colspan="2">
        <label for="rfc">Enter RFC Number:</label>
     </td>
     <td>
        <input type="text" name="rfc" size="4" maxlength="4" />
     </td>
      <td>
        <input type="submit" name="query"  value="Find Errata to Edit" />
      </td>
    </tr>
    <tr>
      <td colspan="2" align="right">
        <input type="checkbox" name="include_verified" value="yes" checked />
        <label for="get_all">Include Verified Errata</label>
      </td>
    </tr>
  </table>
</form>

<h2>Get Errata by Errata ID</h2>

<form method="post" id="query_by_eid" action="errata_edit.php" onsubmit="return validateErrataId(this);">
  <table>
    <tr>
     <td colspan="2">
        <label for="eid"><strong>Enter Errata ID:</strong></label>
     </td>
     <td>
        <input type="text" name="eid" size="4" maxlength="4" />
     </td>
      <td>
        <input type="submit" name="query"  value="Get Erratum" />
      </td>
    </tr>
  </table>
</form>

<h2>Alternate Screens</h2>
<p>Select a different screen to run:</p>
<ul>
  <li><a href="errata_dataentry.php">Data entry screen</a></li>
  <li><a href="verify_errata.php">Verifier screen</a></li>
</ul>
<h2>More Information</h2>
<p>
See these links for more information on how to verify errata and for 
a general overview of the process.
</p>
<ul>
  <li><a href="how_to_verify.html">How to verify errata</a></li>
  <li><a href="' . htmlspecialchars($document_root . $draft_rfc_editor_errata_process) . '" target="_blank"> 
Overview of the process</a></li>
</ul>

<script type="text/javascript">
<!--
          document.forms.query_by_doc.rfc.focus();
//-->
</script>';
}

//<li><a href="http://www.rfc-editor.org/draft-rfc-editor-errata-process-02.txt" target="_blank">
/*
** Begin page output **
 */
//errata_edit_header("RFC Errata Edit","rfcerrata","scripts/validate_rfcsearch.js","errata_edit.php");

/* BEGIN Code to add the Javascript, Body class name and Title name as per WP*/
     add_action('wp_head','add_errata_edit_script');
//Add java script function
function add_errata_edit_script(){
    print "<script src=\"scripts/validate_rfcsearch.js\" type=\"text/javascript\">\n</script>";

    }
    //Add title specifying the dynamic page 
    add_filter( 'wp_title', 'wp_title_errata_edit', 10, 3 );
function wp_title_errata_edit( $title, $sep, $seplocation ) {
    return 'RFC Errata Edit &raquo; RFC Editor';
}

    //Add body class constant to avoid error404 body class for Wordpress outside page
    add_action('body_class','add_body_class_function');
function add_body_class_function($classes){

return array ('errata_edit');
}
/* END Code to add the Javascript, Body class name and Title name as per WP*/

 /*Add Wordpress header and Sidebar*/
get_header();
get_sidebar('left');

/*Add DIV use with wordpress for content*/


if ($debug_eed === true) {
     print("\$_POST<br /><pre>");
     print_r($_POST);
     print("</pre>");
     print("\$_GET<br /><pre>");
     print_r($_GET);
     print("</pre>");
}
?>
<div id="content" class="narrowcolumn">
     
<?php
// We have POST form data. Determine which style and proceed
if (array_key_exists('rfcid', $_POST)) {
    // print("<h1 class='maintitle'>Edit Errata Report for " . $_POST['rfcid'] . "</h1>\n");
     print ("<h1 class='post_title'>Edit Errata Report for   " . htmlspecialchars($_POST['rfcid']) .  "</h1>");    
?>
     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">
<?php     
     print("<p>Errata ID: " . htmlspecialchars($_POST['errata_id']) . "</p>\n");
          edit_full_record_form($_POST);
} else if (array_key_exists('errata_id', $_POST)) {
?>
     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">
<?php
     $status=duplicate_report($_POST['errata_id']);
     if ( $status === true) {
          $data = retrieve_most_recent_insert($_POST['doc-id']);
          if (is_string($data)) {
               print("<p class=\"error\">" . $data . "</p>");
          } else {
               $data['title'] = substr($_POST['title'],0,MAX_TITLE);
               $data['pub-date'] = substr($_POST['pub-date'],0,MAX_DATE);
               $data['rfcid'] = substr($_POST['doc-id'],0,MAX_RFC);
               edit_full_record_form($data);
          }
     } else {
          print("<p class=\"error\">" . $status . "</p>");
     }
} else if (array_key_exists('eid', $_POST)) {
?>
<?php
     $eid = substr($_POST['eid'],0,MAX_EID_DIGIT);
     $data = retrieve_all_field_data($eid);
     if (!(is_string($data))) {
?>
     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">
<?php
          print ("<h1 class='post_title'>Edit Errata Report for   " . htmlspecialchars($data['rfcid']) .  "</h1>"); 
          //print("<h1 class='maintitle'>Edit Errata Report for " . $data['rfcid'] . "</h1>\n");
          print("<p>Errata ID: " . htmlspecialchars($data['errata_id']) . "</p>\n");
          edit_full_record_form($data);
     } else {
          print("<p class='error'>" . htmlspecialchars($data) . "</p>");
     }
} else {
?>
     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">
<?php

     errata_edit_header("RFC Errata Edit","rfcerrata","scripts/validate_rfcsearch.js","errata_edit.php");
     edit_form();
}

errata_edit_footer();
?>
