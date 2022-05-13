<?php
  /* Copyright The IETF Trust 2020 All Rights Reserved               */
  /* $Id: errata_report.php,v 1.12 2022/04/12 01:07:33 priyanka Exp $ */
  //
  //       2010/04/14 rcross: added server-side validations.  errata_confirm.php has been 
  //                          absorbed into this script so we can validate and hand off to 
  //                          errata_insert.php
  // v1.12 2010/01/29 rcross: added handling for special characters in title
 /*  March 2018 Update - Added Security Math problem question to avoid bot form submission - PN*/
 /*  August 2018 Update - Added Publication format for errata - PN*/
 /* May 2020 Updates : Replaced function Unserialize/ serialize from the script with JSON_DECODE/JSON_ENCODE as per ZX security review - PN */
 /* June 2020 Updates : Modified the wordings and hidden variable names Math problem to handle bot problem - PN */
 /* January 2021 : Made Original and corrected text and Notes required in Errata report form - PN */
 /* June 2021 : Modified the script for server upgrade - PN                            */
 /* October 2021 : Modified the script to increase maxlength for section - PN                            */
 /* April 2022 : Removed the Math problem and added Google re-Captcha to handle bot submission - PN */
session_start();
include("errata_headers.php");
include("errata_lib.php");


// Include WordPress
define('WP_USE_THEMES', false);
//require('/a/wordpress/wp-blog-header.php');
require_once ('/a/wordpress/wp-load.php');

//Add title specifying the dynamic page 
add_filter( 'wp_title', 'wp_title_rfc_errata_report', 10, 3 );

function wp_title_rfc_errata_report( $title, $sep, $seplocation ) {
    return 'Submit New RFC Errata &raquo; RFC Editor';
}

//Add body class constant to avoid error404 body class for Wordpress outside page

function add_body_class_function($classes){

return array ('newerratareport');
}

add_action('body_class','add_body_class_function');

/*Function required to use the google re-Captcha has to present before closing the html head tag*/
function add_google_captcha_js() {
    echo '<script src=
        "https://www.google.com/recaptcha/api.js" async defer>
    </script>';

}


/*Stripping the Wordpress additional slashes from GLOBAL vaiables*/
$_POST = array_map( 'stripslashes_deep', $_POST);
$_GET = array_map( 'stripslashes_deep', $_GET);
$_COOKIE    = array_map( 'stripslashes_deep', $_COOKIE );
$_REQUEST   = array_map( 'stripslashes_deep', $_REQUEST );

$debug_ersub = false;

/*Format possible values*/
$format_list = array('TEXT','PDF','HTML');
/*.----------------------------------------------------------------------*/


function display_confirm(){
    page_header("Submit New RFC Errata","rfcerrata","NONE","errata.php");
    //Add title specifying the dynamic page 
    add_filter( 'wp_title', 'wp_title_rfc_errata_report', 10, 3 );
    add_action('body_class','add_body_class_function');
    //Adding google reCaptcha ajax call
    add_action('wp_head',add_google_captcha_js);

    /*Get the wordpress header and sidebar*/
    get_header();
    get_sidebar('left');


    if (array_key_exists('rfcid',$_POST)) {
	 $rfcid    = substr($_POST['rfcid'],0,MAX_RFC);
	 $doc_id   = substr($_POST['doc-id'],0,MAX_RFC);
	 $pub_date = substr($_POST['pub-date'],0,MAX_DATE);
	 $title    = substr($_POST['title'],0,MAX_TITLE);
	 print '
<div id="content" class="narrowcolumn">
     <h1 class="post_title">Report Errata for ' . htmlspecialchars($rfcid) . '</h1>
     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">
<p>This is how the record would look after adding it to the database.
If it is correct, please submit the report. (Or, you can submit it and report 
more errata for the same RFC.) If there is an error in the report, you
may select the edit button to continue editing this report.
</p>
<hr />';

         report_header($rfcid,$title,$pub_date);
         display_record($_POST);
         print("<table>\n<tr>\n");
         print("<td><p>");
         edit_report_again_form($_POST);
         print("</p></td>\n");
         print("<td><p>");
         print<<<END
<form name="abort" action="errata.php" method="post">
    <input type="submit" name="cancel" value="Cancel">
</form>
END;
         print("</p></td>\n");
         print("\n</tr></table>\n");

         print "<div class=\"special_hr\"></div>";

         print '<form action="errata_insert.php" method="post">';
         print("<table border='0'>\n<tr>\n");
         print("<td>\n");
?>
         <td><p>
         <!-- PLEASE NOTE AT ANY TIME RECAPTCHA DIV TAG HAS TO BE INSIDE FORM TAG AND IF THERE IS A TABLE TAG
              IT HAS TO BE INSIDE THE FORM SO THE SEQUENCE HAS TO BE FORM-TABLE-DIV for reCatcha -->

         <!-- div to show reCAPTCHA -->
         <div class="g-recaptcha" style="transform: scale(0.77);  transform-origin: 0 0;"   
                data-sitekey="6Lc-ruweAAAAAHsRP4qOeVN0qeWe8zRkuy9i-Vj4">
          </div>
        </p></td>
<?php
         insert_report_captcha_form($_POST);
         print("<td>");
         print<<<END
<input type="submit" name="submit" value="Submit this Report" />
                <input type="hidden" name="next" value="thanks" />
END;

         print("</td>\n");
         print("\n</tr></table>\n");
         print "</form>";
    
    } else {
	 print("<p>There's nothing for me to do here!</p>");
    }
    page_footer();
}

if ($debug_ersub === true) {
     print("\$_POST (usual way to pass data)<br /><pre>");
     print_r($_POST);
     print("</pre>");
     print("\$_GET (should be empty)<br /><pre>");
     print_r($_GET);
     print("</pre>");
     print("\$_COOKIE (might be empty)<br /><pre>");
     print_r($_COOKIE);
     print("</pre>");
     print("\$_SESSION (might be empty)<br /><pre>");
     print_r($_SESSION);
     print("</pre>");
}

if (array_key_exists('rfcid',$_POST)) {
#     print_r($_POST);
     $rfcid            = substr($_POST['rfcid'],0,MAX_RFC);
     $submit_date      = array_key_exists('submit_date',$_POST) ? substr($_POST['submit_date'],0,MAX_DATE) : null;
     $submitter_name   = substr($_POST['submitter_name'],0,MAX_TEXT);
     $submitter_email  = substr($_POST['submitter_email'],0,MAX_TEXT);
   
     $errata_type_code = substr($_POST['errata_type_code'],0,MAX_TYPE);
     $section          = $_POST['section'];
     $orig_text        = $_POST['orig_text'];
     $correct_text     = $_POST['correct_text'];
     $notes            = $_POST['notes'];
     $pub_date         = substr($_POST['pub-date'],0,MAX_DATE);
     $title            = $_POST['title'];
  
     $format_array = array();
  
     if (array_key_exists('format',$_POST)){
            $format_array = json_decode($_POST['format']);
             if (empty($format_array)){
                foreach ($_POST['format'] as $key => $value){
                  array_push($format_array,$value);
                }
             }   
     }

} else if (isset($_SESSION['rfcid'])) {
     $rfcid           = $_SESSION['rfcid'];
     $submitter_name  = $_SESSION['submitter_name'];
     $submitter_email = $_SESSION['submitter_email'];
     $title           = $_SESSION['title'];
     $pub_date        = $_SESSION['pub-date'];
}

// Server-Side Validation --------------------
$action = htmlentities($_SERVER['PHP_SELF']); 
$errmsg = '';

// check for 'doc-id' to see if we're getting submission from form on this page
// if submit="Edit this Errata Report" than we are re-editing
if (isset($_POST['doc-id']) && ($_POST['submit'] != "Edit this Errata Report")) {
    if (trim($submitter_name) == '')
    {
        $errmsg = "ERROR: Please enter your name<br>";
    }

    if (trim($submitter_email) == '')
    {
        $errmsg = $errmsg . "ERROR: Please enter your email<br>";
    } else if (preg_match('/^[^@\s]+@([-a-z0-9]+\.)+[a-z]{2,}$/i', $submitter_email) != 1){
            $errmsg = $errmsg . "ERROR: Please enter a valid email<br>";
    }
            

    // errata_type_code is limited to 9 characters
    if (trim($errata_type_code) == 'Select On')
    {
        $errmsg = $errmsg . "ERROR: Please select a valid type<br>";
    }

    if (trim($section) == '')
    {
        $errmsg = $errmsg . "ERROR: Please enter a section number<br>";
    }

    if (trim($orig_text) == '')
    {
        $errmsg = $errmsg . "ERROR: Please enter original text<br>";
    }
 
    if (trim($correct_text) == '')
    {
        $errmsg = $errmsg . "ERROR: Please enter corrected text<br>";
    }

    if (trim($notes) == '')
    {
        $errmsg = $errmsg . "ERROR: Please enter notes<br>";
    }

    if (empty($errmsg)) {
           display_confirm();
           exit;
    } 
}

// End Validations ---------------------------

// display header
#page_header("Submit New RFC Errata","rfcerrata","scripts/errata_report_edits.js","errata.php");

add_action('wp_head','add_submit_new_errata_script');

//Add java script function
function add_submit_new_errata_script(){


print "<script src=\"scripts/errata_report_edits.js\" type=\"text/javascript\">\n</script>";

}


// display error messages
#print("<p class=\"error\">$errmsg</p>");

/*
if (array_key_exists('last_corrected',$_SESSION) && 
    isset($_SESSION['last_corrected'])) {
     unset($_SESSION['last_corrected']);
}
*/
if ($debug_ersub === true) {
     print("<p>\n");
     print("\$rfcid="); var_dump($rfcid);
     print("</p>\n");
}

/*Get the wordpress header and sidebar*/
get_header();
get_sidebar('left');

// display error messages
print("<p class=\"error\">$errmsg</p>");

if ($rfcid == null) {
     print("<p>No data to act upon.</p>\n");
} else {
     print '
     <div id="content" class="narrowcolumn">
     <h1 class="post_title">Report Errata for ' . htmlspecialchars($rfcid) . '</h1>
     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">';

//show_db_host();
     print<<<END
<br>
<form name="errata_report" id="errata_report" action="$action"
       method="post" onSubmit="return validateReportFields(document.errata_report);">
   <table align="center" class="erratasearch" border="0">
END;

     text_input('Date','submit_date',($submit_date==null?date('Y-m-d'):$submit_date),true,false,10,10);
     text_input('Your name', 'submitter_name', $submitter_name);
     //text_input('Email','submitter_email',$submitter_email,false,false,120,40);
     text_email_input('Your email address','submitter_email',$submitter_email,false,false,120,40);


     if (array_key_exists('format',$_POST)){
            $format_array = json_decode($_POST['format']);
#TODO
#             print "Inside the unseralize loop";
             if (empty($format_array)){
                foreach ($_POST['format'] as $formats){
                  $format_array[] = $formats;
#                  print "Inside array push $formats";
                }
             }   
     }

#     print('The format array is:');
#     print_r($format_array);

#     print('Get from POST value');
#     print_r($format_array);

     $diff_list = array_diff($format_list,$format_array);
    
#     print('Not present in the format array');
#     print_r($diff_list);

#TODO
    # if (empty($_POST['format'])){
     list($rfc_name,$rfc_number,$length) = split_rfc($rfcid); 
     
     if ($rfc_number >= '8650'){
     if (empty($format_array)){
//              print ('<tr><td>Publication format:</td><td><table align="left" class="erratasearch" border="0"><tr><td><input type="checkbox" id="all" onClick="pubFormatSelect();">All</td><td><input type="checkbox" name="format[]" id="text" value="TEXT">TEXT</td><td><input type="checkbox" name="format[]" id="pdf" value="PDF">PDF</td><td><input type="checkbox" id="html" name="format[]" value="HTML">HTML</td></tr></table></td></tr>');
               print ('<tr><td>Publication format:</td><td><table align="left" class="erratasearch" border="0"><tr><td><input type="checkbox" name="format[]" id="text" value="TEXT">TEXT</td><td><input type="checkbox" name="format[]" id="pdf" value="PDF">PDF</td><td><input type="checkbox" id="html" name="format[]" value="HTML">HTML</td></tr></table></td></tr>');
   } else {
               print ('<tr><td>Publication format:</td><td><table align="left" class="erratasearch" border="0">');
               #print ('<tr><td><input type="checkbox" id="all" onClick="pubFormatSelect();">All</td>');
               print ('<tr>');
            
              foreach ($format_list as $key => $value){
                    if ($value == 'TEXT'){ 
                        if (in_array($value,$diff_list)){
                            print ('<td><input type="checkbox" name="format[]" id="text" value=' . htmlspecialchars($value) . '>TEXT</td>');
                        }else {
                            print ('<td><input type="checkbox" name="format[]" id="text"  checked value=' . htmlspecialchars($value) . '>TEXT</td>');
                        }
                    }elseif ($value == 'PDF'){ 
                        if(in_array($value,$diff_list)){
                            print ('<td><input type="checkbox" name="format[]" id="pdf" value=' . htmlspecialchars($value) . '>PDF</td>');
                        }else {
                            print ('<td><input type="checkbox" name="format[]" id="pdf" checked value=' . htmlspecialchars($value) . '>PDF</td>');
                        }
                    }elseif ($value == 'HTML'){ 
                        if(in_array($value,$diff_list)){
                           print ('<td><input type="checkbox" name="format[]" id="html" value=' . htmlspecialchars($value) . '>HTML</td>');
                        }else {
                           print ('<td><input type="checkbox" name="format[]" id="html" checked value=' . htmlspecialchars($value) . '>HTML</td>');
                        } 
                    }
                }

               print ('</table></td></tr>'); 
     }
     }
    select_input('Type', 'errata_type_code', $errata_types, $errata_type_code, true);
     print ('<tr><td></td><td><p style="font-style: italic;">where <b>Technical</b> is an error in the technical content (This includes 
        changes to the usage of RFC 2119 keywords.) and <b>Editorial</b> is a spelling, 
        grammar, punctuation, or syntax error that does not affect the technical meaning.</p> </td></tr>');
     #select_input('Type', 'errata_type_code', $errata_types, $errata_type_code, false);

     text_input('Section', 'section', $section, false, true, 20, 20,'Enter number or GLOBAL');
     #text_input('Section', 'section', $section, false, false, 12, 12);
     textarea_input("Original Text", "orig_text", $orig_text, false, 10, 80, 'No','Yes');
     textarea_input("Corrected Text", "correct_text", $correct_text, false, 10, 80, 'No','Yes');
     textarea_input("Notes", "notes", $notes, true, 5,80,'Enter any explanatory notes or rationale for the suggested correction.');
     #textarea_input("Notes", "notes", $notes, false, 5);

     print '
      <tr>
        <td>
           <input type="hidden" name="rfcid" value="' . htmlspecialchars($rfcid) . '" />&nbsp;
           <input type="hidden" name="doc-id" value="' . htmlspecialchars($rfcid) . '" />
           <input type="hidden" name="pub-date" value="' . htmlspecialchars($pub_date) . '" />
           <input type="hidden" name="title" value="' . htmlspecialchars($title) . '" />
        </td>
        <td>
           <input type="submit" name="submit" value="Preview" />
        </td>
      </tr>
    </table>
</form>
<p>Back to the <a href="errata.php">errata page</a>.</p>';
   //  help_boxes(); // writes hidden text used by help mouse-overs
     
  page_footer();
}
?>
