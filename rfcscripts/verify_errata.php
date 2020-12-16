<?php
  /* Copyright The IETF Trust 2020 All Rights Reserved */
  /* $Id: verify_errata.php,v 1.1.1.1 2016/02/08 21:03:50 priyanka Exp $ */
session_start();
include("errata_headers.php");
include("errata_search_lib.php");
include("errata_mail_lib.php");
include("errata_authen_lib.php");
include("config.php");

$debug_ve = false;



// Include WordPress
define('WP_USE_THEMES', false);
//require('/a/wordpress/wp-blog-header.php');
require_once ('/a/wordpress/wp-load.php');


session_authenticate();

/*Stripping the Wordpress additional slashes from GLOBAL vaiables*/
$_POST = array_map( 'stripslashes_deep', $_POST);
$_GET = array_map( 'stripslashes_deep', $_GET);
$_COOKIE    = array_map( 'stripslashes_deep', $_COOKIE );
$_REQUEST   = array_map( 'stripslashes_deep', $_REQUEST );

function more_information() {
     global $document_root;
     print<<<END

<h2>More Information</h2>
<ul>
  <li><a href="/how-to-verify/">How to verify errata</a></li>
  <li><a href="$document_root/materials/draft-rfc-editor-errata-process-02.txt" target="_blank">
Overview of the process</a></li>
</ul>

END;

}

function verify_help_boxes(){
/*
 * These hidden block elements contain the text used for help for form fields.
 * The mapping of help text to form element is {form_element_name}_help.
 */
     print<<<END
<p id="helpbox" class="helpbox">
     Text (innerHTML) from associated help elements inserted here at runtime.
</p>
<p id="section_text_help" style="visibility: hidden; display:none;">
     Identify the section with the possible error by giving its number
 <i>i.e.</i> 1.2.3, without the word "Section". If the issue recurs 
throughout the document, use the keyword <b>GLOBAL</b>. Due to oddities in formating legacy
records, some records use "99" to mark section text for special processing. Do not remove the
marker without moving the text from the "Section" field to an appropriate field.
</p>
<div id="errata_type_code_select_help" style="visibility: hidden; display:none;">
<p>The types of errata are:</p>
<dl>
     <dt>Technical</dt><dd>Issues with the technical correctness of the document.</dd>
     <dt>Editorial</dt><dd>Issues with the language of the document.</dd>
</dl>
</div>
<p id="notes_textarea_help" style="visibility: hidden; display:none;">
Use this area for any explanatory notes or rationale for the suggested correction.
</p>

END;
}

function create_search_page() {
     global $errata_subsets_by_id;
     if (array_key_exists('doc-id',$_SESSION) && isset($_SESSION['doc-id'])) {
          unset($_SESSION['doc-id']);
     }
     if (array_key_exists('rfc',$_SESSION) && isset($_SESSION['rfc'])) {
          unset($_SESSION['rfc']);
     }
     errata_edit_header("RFC Errata Verification","rfcerrata","scripts/validate_rfcsearch.js","verify_errata.php");
     
     $criteria = array('rec_status' => $errata_subsets_by_id[STATUS_REPORTED]);
     create_adv_search_form($criteria, "verify_errata_select.php", true);
     more_information();

}



/*
** Begin page output **
 */


if ($debug_ve === true) {
     print("\n<!--\n");
     print("\$_POST (may be empty)<br /><pre>");
     print_r($_POST);
     print("</pre>");
     print("\$_GET (should be empty)<br /><pre>");
     print_r($_GET);
     print("</pre>");
     print("\$_SESSION (should NOT be empty)<br /><pre>");
     print_r($_SESSION);
     print("</pre>");
     print("\n-->\n");
}

if (array_key_exists('submit',$_POST)) {

/* BEGIN Code to add the Javascript, Body class name and Title name as per WP*/
     add_action('wp_head','add_verify_errata_script');
//Add java script function
function add_verify_errata_script(){
    print "<script src=\"scripts/errata_report_edits.js\" type=\"text/javascript\">\n</script>";

    }
    //Add title specifying the dynamic page 
    add_filter( 'wp_title', 'wp_title_verify_errata', 10, 3 );
function wp_title_verify_errata( $title, $sep, $seplocation ) {
    return 'RFC Errata Verification &raquo; RFC Editor';
}

    //Add body class constant to avoid error404 body class for Wordpress outside page
    add_action('body_class','add_body_class_function');
function add_body_class_function($classes){

return array ('verify_errata');
}
/* END Code to add the Javascript, Body class name and Title name as per WP*/

 /*Add Wordpress header and Sidebar*/
get_header();
get_sidebar(left);

     switch ($_POST['submit']) {
     case 'Duplicate':
/*Add DIV use with wordpress for content*/
?>
          <div id="content" class="narrowcolumn">
          <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
          <div class="singlepost entry">

<?php
          errata_edit_header("RFC Errata Verification","rfcerrata","scripts/errata_report_edits.js","verify_errata.php");
          $status=duplicate_report($_POST['errata_id']);
          if ($status === true) {
               $data = retrieve_most_recent_insert($_POST['doc-id']);
               if (is_string($data)) {
                    error_log($data);
                    print("<p class=\"error\">The application has experienced an error. Please try again later.</p>\n");
               } else {
                    $data['title'] = substr($_POST['title'], 0, MAX_TITLE);
                    $data['pub-date'] = substr($_POST['pub-date'], 0, MAX_DATE);
                    $data['rfcid'] = substr($_POST['doc-id'], 0, MAX_RFC);
                    print("<h1 class='maintitle'>Edit Errata Report for " . htmlspecialchars($data['rfcid']) . "</h1>\n");
                    print("<p>Errata ID: " . htmlspecialchars($data['errata_id']) . "</p>\n");
                    verify_record_form($data);
               }
          } else {
               error_log($status);
               print("<p class=\"error\">The application has encountered an error. Please try again later</p>");
          }
          break;
     case 'Edit':
     case 'Edit Again':
     case 'Preview':
?>     
          <div id="content" class="narrowcolumn">
          <h1 class="post_title">Errata Edit Report  for <?php print htmlspecialchars($_POST['rfcid']);?></h1>
         <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
          <div class="singlepost entry">
<?php
          errata_edit_header("RFC Errata Verification","rfcerrata","scripts/errata_report_edits.js","verify_errata.php");
// We have POST form data. Determine which style and proceed
          print("<p>Errata ID: " . htmlspecialchars($_POST['errata_id']) . "</p>\n");
          verify_record_form($_POST);
          break;
     case 'Post':
          $status = update_errata_record($_POST);
          if (is_bool($status) && $status === true) {
               $errata_status_code = $_POST['errata_status_code'];
               if ($errata_status_ids[$errata_status_code] == STATUS_VERIFIED) {
               generate_verify_msg($_POST);
               } else if ($errata_status_ids[$errata_status_code] == STATUS_REJECTED) {
                    generate_reject_msg($_POST);
               }
               $_SESSION['rfc'] = substr($_POST['doc-id'],3,MAX_RFC);
               //header("Location: http://{$_SERVER['SERVER_NAME']}/verify_complete.php");
               ams_redirect("verify_complete.php");
               exit;
          }
          break;
     default:
// This should not happen...
          error_log("verify_errata.php entered default clause of switch");
          
          create_search_page();
          break;
     }
} else { // default action


/* BEGIN Code to add the Javascript, Body class name and Title name as per WP*/
     add_action('wp_head','add_verify_errata_script');
//Add java script function
function add_verify_errata_script(){
    print "<script src=\"scripts/validate_rfcsearch.js\" type=\"text/javascript\">\n</script>";

    }


    //Add title specifying the dynamic page 
    add_filter( 'wp_title', 'wp_title_verify_errata', 10, 3 );
function wp_title_verify_errata( $title, $sep, $seplocation ) {
    return 'RFC Errata Verification &raquo; RFC Editor';
}

    //Add body class constant to avoid error404 body class for Wordpress outside page
    add_action('body_class','add_body_class_function');
function add_body_class_function($classes){

return array ('verify_errata');
}
/* END Code to add the Javascript, Body class name and Title name as per WP*/

/*Add Wordpress header and Sidebar*/
get_header();
get_sidebar(left);

/*Add DIV use with wordpress for content*/
     print<<<END
<div id="content" class="narrowcolumn">
     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">
END;

     create_search_page();


}

/*Footer area make calls to WP footer*/
errata_edit_footer();
?>
