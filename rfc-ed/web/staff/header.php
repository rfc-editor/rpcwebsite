<?php
  /* Copyright The IETF Trust 2020 All Rights Reserved */
  /* $Id: header.php,v 2.18 2023/02/24 23:44:54 priyanka Exp $ */
  /*October 2018 : Added a link for Area Assignment Page*/
  /*May 2021 : Modified link for Procedures*/
  /*February 2023 : Added link for operational notes page*/
include_once("ams_util_lib.php");

$dev_mode = get_dev_mode();

$lsi  = strrpos($_SERVER['PHP_SELF'], "/");
$sub  = substr($_SERVER['PHP_SELF'], 0,$lsi);
if ($_SERVER['HTTPS']=="on") {
    $base = "https://";
} else {
    $base  = "http://";
}
$errata_host = $base . "www.rfc-editor.org";
$base .= $_SERVER['SERVER_NAME'];
$base .= $sub;


function rfc_header($title, $script_extra="NONE", $style="./style/main.css") {
  global $base;
  global $errata_host;
  global $sub;
  global $lsi;

  print <<<END
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>RFC Editor: $title</title>
    <link rel="stylesheet" href="$style" type="text/css"/>
END;

    // if this page has a special script, load it.
    if ( strcmp($script_extra,"NONE") != 0 ) {
?>
     <script src="<?php echo $script_extra ?>" type="text/javascript">\n</script>
<?php    
    }

    // if dev_mode load custom stylesheet
    if ($dev_mode) {
    print<<<END

    <link rel="stylesheet" href="style/ams.css" type="text/css"/>

END;
}

    print '
  </head>
  <body>
    <a href="index.php"><img class="header" src="./RFCedlogo.jpg" alt="RFC ED header" border="0" width="220"/></a>
    <ul class="rfc_fcns">
      <li><a href="' . htmlspecialchars($base) . '/add_draft.php">Add Document</a></li>
      <li><a href="' . htmlspecialchars($base) . '/list_drafts.php">Find Documents</a></li>
      <li><a href="' . htmlspecialchars($base) . '/index_controls.php">Publish/Create Mail</a></li>
      <li><a href="' . htmlspecialchars($errata_host) . '/verify_login.php" target="_blank">Errata</a></li>
      <li><a href="' . htmlspecialchars($base) . '/current_queue.php" target="_blank">Current Queue</a></li>
      <li><a href="' . htmlspecialchars($base) . '/track_by_editor.php" target="_blank">Tracking by Editor</a></li>
      <li><a href="' . htmlspecialchars($base) . '/auth48_edit.php">AUTH48 Tracking</a></li>
      <li><a href="' . htmlspecialchars($base) . '/op_notes_queue.php">Queue Notes</a></li>
      <li><a href="' . htmlspecialchars($base) . '/wiki/doku.php?id=v3_procedures_manual" target="_blank">Procedures</a></li>
    </ul>
    <hr/>';
}

function rfc_footer() {
         print "<span style=\"text-align: left; margin-left: 0;\">" . htmlspecialchars($_SERVER["SERVER_NAME"]) . ".</span>";
     print "</body>\n</html>\n"; 
}

function rfc_fail($str) {
    print "<p class=\"warning\">$str</p>\n";
    rfc_footer();
    exit(0);
}

?>
