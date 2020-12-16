<?php
  /* Copyright The IETF Trust 2020 All Rights Reserved */
  /* $Id: errata.php,v 1.3 2019/09/10 16:52:06 priyanka Exp $ */
  /* April 2017 Updates : Added the redirect link for Errata Id and RFC number - PN*/
session_start();
$_SESSION['report_count'] = 0;  /* Count errata reports submitted this session */

include("errata_headers.php");
#http_redirect(); // if we're on a HTTPS connection, goto HTTP
include("errata_search_lib.php");


// Include WordPress
define('WP_USE_THEMES', false);
//require('/a/wordpress/wp-blog-header.php');
require_once ('/a/wordpress/wp-load.php');

#page_header("RFC Errata","rfcerrata","scripts/validate_rfcsearch.js","errata.php");

add_action('wp_head','add_errata_page_script');

//Add java script function
function add_errata_page_script(){


print "<script src=\"/scripts/validate_rfcsearch.js\" type=\"text/javascript\">\n</script>";

}


//Add title specifying the dynamic page 
add_filter( 'wp_title', 'wp_title_rfc_errata', 10, 3 );

function wp_title_rfc_errata( $title, $sep, $seplocation ) {
    return 'RFC Errata &raquo; RFC Editor';
}

//Add body class constant to avoid error404 body class for Wordpress outside page

function add_body_class_function($classes){

return array ('errata');
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
#errata_nav();
print<<<END
<p>Published RFCs never change.  Although every published RFC has been submitted to 
careful proofreading by the RFC Editor and the author(s), errors do sometimes go 
undetected. Use the form on this page to query the errata database for entries related 
to an RFC. Errata are for the RFCs as available from rfc-editor.org. Search results 
from the <a href="search/rfc_search.php">RFC search page</a> will include hyperlinks 
to any corresponding errata entries.
</p>
END;

create_adv_search_form();

/*Global variables from config.php*/
global $status_type_errata;
global $how_to_report;
global $how_to_verify;
global $ietf_base;
global $errata_processing_html;
global $materials;
global $draft_errata_process;

print<<<END
<h2 id="reportnew">Report New Errata</h2>
<p>
If you have new errata to report, use this form to first check that a report is not already in the system.
If your issue is not listed, you will be able to submit a new report from that
screen.  [Note: To report an error in an existing erratum,
please <a href="mailto:rfc-editor@rfc-editor.org">contact the RFC Editor</a>.]

</p>
<p>
The HTML files for RFCs 1 through 8649 were generated from the text file using rfc2html; errors may be 
reported to the maintainer of rfc2html or <a href="mailto:rfc-editor@rfc-editor.org">RFC Editor</a>
</p>
<form method="post" id="report" action="/errata_search.php" onsubmit="return validateRFCNewErrataForm(this);">
  <table>
    <tr>
     <td colspan="2">
        <label for="rfc"><strong>Enter RFC Number:</strong></label>
     </td>
     <td>
        <input type="text" name="rfc" size="4" maxlength="4">
     </td>
     <td>
        <input type="submit" name="create" value="Report New Errata">
     </td>
    </tr>
  </table>
</form>

<h2>More Information</h2>
<ul>
  <li><a href="$status_type_errata">Status and Type Descriptions for RFC Errata</a></li>
  <li><a href="$how_to_report">How to report errata</a></li>
  <li>For verifiers: <a href="$how_to_verify">How to verify errata</a></li>
  <li>IESG Statement on <a href="$ietf_base$errata_processing_html">
"IESG Processing of RFC Errata for the IETF Stream"</a></li>
  <li>
<a href="$materials$draft_errata_process" target="_blank">
RFC Editor Proposal for Handling RFC Errata</a>
  </li>
</ul> 

END;

page_footer();
?>
