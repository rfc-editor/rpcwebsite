<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
# $Id: edit_draft.php,v 2.2 2020/11/11 01:42:49 priyanka Exp $
#November 2020 : Modified the script to use PDO prepared statements - PN
session_start();
include('header.php');
include('db_connect.php');
include('edit_lib.php');
rfc_header("edit draft","validate.js");


if ($_SERVER['QUERY_STRING'] ) {
    if ( preg_match("/([^\=]+)=([^\&]+)/", $_SERVER['QUERY_STRING'], $match)) {
	$keyfield = $match[1];
	$key = $match[2];
    }
}
elseif ( $_POST['submit'] == "edit draft" ) {
    $keyfield = $_POST['keyfield'];
    $key = $_POST[$keyfield];
}

if ( $keyfield ) {
    $fields = pull_from_db($key, 0, $keyfield);
    $fields['keyfield'] = $keyfield;
}

# Move the form submission into the fields array
foreach ($field_names as $name) {
     if (array_key_exists($name, $_POST)) $fields[$name] = $_POST[$name];
}

# Keep track of how we found the draft. (NB this keeps track when the keyfield 
# is not a direct parameter of some kind, especially error forms, etc.)
if (array_key_exists('keyfield', $_POST)) $fields['keyfield'] = $_POST['keyfield'];


# A DB pull failed.   If there are no matches or the query failed, print an 
# error and clear the keyfield so the request form comes up again.  If multiple 
# drafts matched, set $submit so the switch below will put up a page asking the 
# user to select one.
    if ( array_key_exists('error', $fields) ) { 
    if ( preg_match("/not unique/", $fields['error'])) {
	$submit = "multiple hits";
    }
    else {
	$fields[$fields['keyfield']] = "";
	print "<p class=\"error\">" . htmlspecialchars($fields['error']) . 
	    "</p>\n";
    }
}

$read_only = array('internal_key' => 1, 'cid' => 1);

# XXX: This is *super aggrivating.  Authors can have ", Ed." after their name, 
# so ","is not really a good delimeter.  This is a super hack:  Change the 
# commas that are part of the ", Ed." "token" into semi-colons, do the split 
# and restore the "token."
hack_overloaded_fields();
foreach ($list_split as $name => $pat ) 
    $fields[$name] = canonicalize_list($fields[$name], $pat);
unhack_overloaded_fields();

# If submit hasn't been set above, set it from the POST data.  No POST data 
# means we're just starting up.
if ( !$submit ) {
    if ($fields[$fields['keyfield']] ) 
         $submit = array_key_exists('submit',$_POST) ? $_POST['submit'] : "first";
    else 
	$submit="nodraft";
}

$dest="$base/edit_draft.php";

switch ($submit) {
case "first":
case "re-edit":
case "edit draft":
    get_wgs();
    edit_document_form($dest);
    break;
case "submit":
    get_editors(false); // Get past and present editors
    $err = check_fields();
    if ( preg_match("/^Error:/", $err ) )
	error_page($err, $dest);
    elseif ( preg_match("/^Warning:/", $err ) )
	warn_page($err, $dest);
    else 
	commit_page($dest);
    break;
case "commit":
    commit_edit_draft();
    break;
case "multiple hits":
    $links = matching_links($key, $keyfield);
    print "<p>Your request matched the following, pick one or use the form " . 
	"below to try another query.</p>\n";
    print "<table class=\"edit_hits\">\n";
    foreach ($links as $l) 
	print "<tr><td>" . htmlspecialchars($l) . "</td></tr>\n";
    print "</table>\n";
    print "<hr/>\n";
    # drop thru
case "nodraft":
    print '
<form method="post" action="' . htmlspecialchars($dest) . '">
    <table>
	<tr>
	    <td><label for="draft">Draft Name:</label></td>
	    <td><input type="text" name="draft" id="draft" value=""/></td>
	</tr>
	<tr>
	    <td colspan="2">
		<input type="hidden" name="submit" value="edit draft"/> 
	    </td>
	</tr>
    </table>
    <input type="hidden" name="keyfield" value="draft"/> 
</form>
<div class="center">
or
</div>
<form method="post" action="' . htmlspecialchars($dest) . '">
    <table>
	<tr>
	    <td><label for="doc_id">Document ID:</label></td>
	    <td><input type="text" name="doc_id" id="doc_id" value=""/></td>
	</tr>
	<tr>
	    <td colspan="2">
		<input type="hidden" name="submit" value="edit draft"/> 
	    </td>
	</tr>
    </table>
    <input type="hidden" name="keyfield" value="doc_id"/> 
</form>';
    break;
}

rfc_footer();
?>
