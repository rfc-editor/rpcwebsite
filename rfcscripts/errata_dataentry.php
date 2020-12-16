<?php
  /* Copyright The IETF Trust 2020 All Rights Reserved */
  /* $Id: errata_dataentry.php,v 1.1.1.1 2016/02/08 21:03:50 priyanka Exp $ */
session_start();
include("errata_authen_lib.php");
session_authenticate();

include("errata_headers.php");
include("errata_lib.php");

// Include WordPress
define('WP_USE_THEMES', false);
//require('/a/wordpress/wp-blog-header.php');
require_once ('/a/wordpress/wp-load.php');

$debug_entry = false;
$verifier_id = RFC_ED_VERIFIER_ID;


/* BEGIN Code to add the Javascript, Body class name and Title name as per WP*/
     add_action('wp_head','add_errata_dataentry_script');
//Add java script function
function add_errata_dataentry_script(){
    print "<script src=\"scripts/errata_report_edits.js\" type=\"text/javascript\">\n</script>";

    }
//Add title specifying the dynamic page 
add_filter( 'wp_title', 'wp_errata_data_entry', 10, 3 );
function wp_errata_data_entry( $title, $sep, $seplocation ) {
	return 'RFC Errata Data Entry &raquo; RFC Editor';
}
//Add body class constant to avoid error404 body class for Wordpress outside page
add_action('body_class','add_body_class_function');
  function add_body_class_function($classes){
	return array ('data_entry');
 }

/*Add Wordpress header and Sidebar*/
get_header();
get_sidebar(left);


errata_edit_header("RFC Errata Data Entry","rfcerrata","scripts/errata_report_edits.js","errata_edit.php");

/*Stripping the Wordpress additional slashes from GLOBAL vaiables*/
$_POST = array_map( 'stripslashes_deep', $_POST);
$_GET = array_map( 'stripslashes_deep', $_GET);
$_COOKIE    = array_map( 'stripslashes_deep', $_COOKIE );
$_REQUEST   = array_map( 'stripslashes_deep', $_REQUEST );


if ($debug_entry === true) {
     print("\$verifier_id=$verifier_id ("); var_dump($verifier_id); print(")<br />\n");
     print("\$_POST (usual way to pass data)<br /><pre>");
     print_r($_POST);
     print("</pre>");
     print("\$_GET (should be empty)<br /><pre>");
     print_r($_GET);
     print("</pre>");
     print("\$_COOKIE (might be empty)<br /><pre>");
     print_r($_COOKIE);
     print("</pre>");
     print("\$_SESSION<br /><pre>");
     print_r($_SESSION);
     print("</pre>");
}

$rfcnum             = array_key_exists('rfcnum',$_POST) ? substr($_POST['rfcnum'],0,MAX_RFC_DIGIT) : null;
$submit_date        = array_key_exists('submit_date',$_POST) ? substr($_POST['submit_date'],0,MAX_DATE) : null;
$submitter_name     = array_key_exists('submitter_name',$_POST) ? substr($_POST['submitter_name'],0,MAX_TEXT) : null;
$submitter_email    = array_key_exists('submitter_email',$_POST) ? substr($_POST['submitter_email'],0,MAX_TEXT) : null;
$verifier_name      = array_key_exists('verifier_name',$_POST) ? substr($_POST['verifier_name'],0,MAX_TEXT) : null;
$verifier_email     = array_key_exists('verifier_email',$_POST) ? substr($_POST['verifier_email'],0,MAX_TEXT) : null;
$posted_date        = array_key_exists('posted_date',$_POST) ? substr($_POST['posted_date'],0,MAX_DATE) : null;
$errata_type_code   = array_key_exists('errata_type_code', $_POST) ? substr($_POST['errata_type_code'],0,MAX_TYPE) : null;
$section            = array_key_exists('section',$_POST) ? $_POST['section'] : null;
$orig_text          = array_key_exists('orig_text',$_POST) ? $_POST['orig_text'] : null;
$correct_text       = array_key_exists('correct_text',$_POST) ? $_POST['correct_text'] : null;
$notes              = array_key_exists('notes', $_POST) ? $_POST['notes'] : null;
$pub_date           = array_key_exists('pub-date',$_POST) ? substr($_POST['pub-date'],0,MAX_DATE) : null;
$title              = array_key_exists('title', $_POST) ? $_POST['title'] : null;
$errata_status_code = array_key_exists('errata_status_code', $_POST) ? substr($_POST['errata_status_code'],0,MAX_STATUS) : "Reported";


print<<<END
<div id="content" class="narrowcolumn">
     <h1 class="post_title">Errata Data Entry</h1>

     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">

END;

print<<<END

<div id="errata_form" style="position: relative;">
<form name="errata_dataentry" action="errata_dataentry_confirm.php"
       method="post" onsubmit="return validateDataEntryFields(this);">
   <input type="hidden" name="verifier_id" value="$verifier_id" />
   <table>
END;
text_input('RFC Number','rfcnum',$rfcnum,false,false,MAX_RFC_DIGIT,MAX_RFC_DIGIT);
text_input('Reported by', 'submitter_name', $submitter_name);
text_input('Reporter Email','submitter_email',$submitter_email,false,false,120,40);
text_input('Report Date','submit_date',$submit_date,false,false,10,10);
select_input('Status', 'errata_status_code', $verify_status_codes, $errata_status_code, false);
text_input('Verifier', 'verifier_name', $verifier_name);
text_input('Verifier Email','verifier_email',$verifier_email,false,false,MAX_EMAIL,40);
text_input('Verify Date','posted_date',$posted_date,false,false,MAX_DATE,MAX_DATE);
select_input('Type', 'errata_type_code', $errata_types, $errata_type_code, false);
text_input('Section', 'section', $section, false, false, MAX_SECTION, MAX_TEXT);

textarea_input("Original Text", "orig_text", $orig_text);
textarea_input("Corrected Text", "correct_text", $correct_text);
textarea_input("Notes", "notes", $notes, false, 5);

print<<<END
      <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>
           <input type="submit" name="submit" value="Preview" />
        </td>
      </tr>
    </table>
</form>
</div>

END;

page_footer();
?>
