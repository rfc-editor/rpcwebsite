<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
# $Id: add_draft.php,v 2.2 2020/11/11 01:42:49 priyanka Exp $
#November 2020 : Modified the script to use PDO prepared statements - PN
session_start();
include('header.php');
include('db_connect.php');
include('edit_lib.php');
//include('export_lib.php');
rfc_header("add draft","validate.js");

# Move the form submission into the fields array
foreach ($field_names as $name) {
    if ($_POST[$name]) $fields[$name] = $_POST[$name];
    elseif ($field_defaults[$name]) $fields[$name] = $field_defaults[$name];
}


# XXX: This is *super aggrivating.  Authors can have ", Ed." after their name, 
# so ","is not really a good delimeter.  This is a super hack:  Change the 
# commas that are part of the ", Ed." "token" into semi-colons, do the split 
# and restore the "token."
hack_overloaded_fields();
foreach ($list_split as $name => $pat ) 
    $fields[$name] = canonicalize_list($fields[$name], $pat);
unhack_overloaded_fields();

$submit = $_POST['submit'] ? $_POST['submit'] : "first";
$dest="$base/add_draft.php";

# Turn the list of field errors into a map of fields with errors
foreach (explode(",", $_POST['field_errors']) as $f) 
    $field_errors[$f] = 1;

//print "<body>\n";
switch ($submit) {
case "first":
case "re-edit":
//    get_wgs(true); // only get open working groups
    get_wgs();
    edit_document_form($dest);
    break;
case "submit":
    get_editors();
    $err = check_fields();
    if ( preg_match("/^Error:/", $err ) )
	error_page($err, $dest);
    elseif ( preg_match("/^Warning:/", $err ) )
	warn_page($err, $dest);
    else 
	commit_page($dest);
    break;
case "commit":
    add_draft();
    break;
}

rfc_footer();
?>
