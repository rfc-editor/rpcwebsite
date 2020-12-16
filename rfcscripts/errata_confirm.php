<?php
  /* Copyright The IETF Trust 2020 All Rights Reserved */
  /* $Id: errata_confirm.php,v 1.1.1.1 2016/02/08 21:03:50 priyanka Exp $ */
session_start();
include("errata_headers.php");
http_redirect(); // if we're on a HTTPS connection, goto HTTP
include("errata_lib.php");

$debug_ersub = false;

page_header("Submit New RFC Errata","rfcerrata","NONE","errata.php");

/*Stripping the Wordpress additional slashes from GLOBAL vaiables*/
$_POST      = array_map( 'stripslashes_deep', $_POST);
$_GET       = array_map( 'stripslashes_deep', $_GET);
$_COOKIE    = array_map( 'stripslashes_deep', $_COOKIE );
$_REQUEST   = array_map( 'stripslashes_deep', $_REQUEST );

if ($debug_ersub === true) {
     print("\$_POST<br /><pre>");
     print_r($_POST);
     print("</pre>");
     print("\$_GET (should be empty)<br /><pre>");
     print_r($_GET);
     print("</pre>");
}
if (array_key_exists('rfcid',$_POST)) {
     $rfcid    = substr($_POST['rfcid'],0,MAX_RFC);
     $doc_id   = substr($_POST['doc-id'],0,MAX_RFC);
     $pub_date = substr($_POST['pub-date'],0,MAX_DATE);
     $title    = substr($_POST['title'],0,MAX_TITLE);
     print "<h1 class='maintitle'>Report Errata for " . htmlspecialchars($rfcid) . "</h1>
<p>This is how the record would look after adding it to the database.
If it is correct, you may confirm the report or confirm the report and report
more errata for this RFC. If there is an error in the report, you
may select the edit button to continue editing this report.
</p>
<hr />";

     report_header($rfcid,$title,$pub_date);
     display_record($_POST);
     print("<table>\n<tr>\n");
     print("<td>\n");
     edit_report_again_form($_POST);
     print("\n</td>\n");
     print("<td>\n");
     insert_report_form($_POST);
     print("\n</td>\n");
     print("<td>\n");
     insert_report_form($_POST, true);
     print("\n</td>\n");
     print("<td>\n");
     print<<<END
<form name="abort" action="errata.php" method="post">
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
