<?php
  /* Copyright The IETF Trust 2020 All Rights Reserved */
  /* $Id: exportINDEX.php,v 2.10 2024/05/01 19:02:49 priyanka Exp $ */
  /* May 2024 : Removed call to scripts make_bcp_ref.pl and make_std_ref.pl(Text entires for BCPs and STDs) */
  /*            Removed call to scripts parse-bcpref.py and parse-std.ref.py (Building citation files for BCPs and STDs)*/
$ei_debug = false;
$output   = array();
$status   = null;
//  Choose a different location is the HTTP host isn't the production server.
$cmd_base = "/home/rfc-ed/bin"; // production
$generate_cmd = "${cmd_base}/generate_INDEX_from_db.pl";
$xml_cmd = "${cmd_base}/xmlIndexDaily.sh";
$after_INDEX_cmd = "${cmd_base}/after_INDEX_updated.sh";
$xml_reference_cmd = "${cmd_base}/xmlRFCref.php";

$ref_cmd_base =  "/a/home/rfc-ed/bin";
$make_ref_cmd = "/usr/bin/perl $ref_cmd_base/make_ref.pl $ref_cmd_base/database_information.txt > /a/ftp/in-notes/rfc-ref.txt.new";


$parse_ref_cmd = "/usr/bin/python3 $ref_cmd_base/parse-rfcref.py";


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>Update INDEX</title>
	<meta name="robots" content="noindex, nofollow">
	<meta name="robots" content="noarchive">
        <link rel="stylesheet" type="text/css" href="./style/main.css">
</head>
<body>
<hr>
<a href="index.php"><img class="header" src="./RFCedlogo.jpg" alt="RFC ED header" border="0" width="220"/></a>
<hr>
<?php
     if ($ei_debug === true) {
          print("\$_POST<br><pre>");
          print_r($_POST);
          print("</pre>");
     }
?>
<b>Updating INDEX....</b>
<?php
if ($ei_debug === true) {
     print("<p>$generate_cmd</p>");
     print("<p>$xml_cmd</p>");
}
exec($generate_cmd, $output, $status);
if ($status != 0) {
     print("<h1 class=\"error\">ERROR!</h1>");
     print("<p>Script $generate_cmd returned non-zero status ${status}! Output was:</p>");
     foreach ($output as $line) {
          print($line . "<br />");
     }
     print("<p class=\"error\">End of $generate_cmd error text output.</p>");
     if ($ei_debug !== true) { exit(0); }
}
?>
<b>Updating rfc-index.xml....</b>
<?php
exec($xml_cmd, $output, $status);
if ($status != 0) {
     print("<h1 class=\"error\">ERROR!</h1>");
     print("<p>Script $xml_cmd returned non-zero status ${status}! Output was:</p>");
     foreach ($output as $line) {
          print($line . "<br />");
     }
     print("<p class=\"error\">End of $xml_cmd error text output.</p>");
     if ($ei_debug !== true) { exit(0); }
}

/*if ($ei_debug === true) {
     print("<p>Debug Mode: Close window manually.</p>");
} else {
     print("<script type=\"text/javascript\">\n"); // close the window
     print("<!-- \n");
     print("window.close();\n");
     print("-->\n");
     print("</script>\n");
}
*/
?>
<b>Running after_INDEX_updated.sh....</b>
<?php
exec($after_INDEX_cmd, $output, $status);
if ($status != 0) {
     print("<h1 class=\"error\">ERROR!</h1>");
     print("<p>Script $after_INDEX_cmd returned non-zero status ${status}! Output was:</p>");
     foreach ($output as $line) {
          print($line . "<br />");
     }
     print("<p class=\"error\">End of $after_INDEX_cmd error text output.</p>");
     if ($ei_debug !== true) { exit(0); }
}else {
     foreach ($output as $line) {
          print($line . "<br />");
     }
}
$output_xml_ref = array();
?>
<br>
<b>Running XMLized Reference entries for RFCs</b>
<?php
exec($xml_reference_cmd, $output_xml_ref, $status);
if ($status != 0) {
     print("<h1 class=\"error\">ERROR!</h1>");
     print("<p>Script $$xml_reference_cmd returned non-zero status ${status}! Output was:</p>");
     foreach ($output_xml_ref as $line) {
          print($line . "<br />");
     }
     print("<p class=\"error\">End of  $xml_reference_cmd error text output.</p>");
     if ($ei_debug !== true) { exit(0); }
}else {
     foreach ($output_xml_ref as $line) {
          print($line . "<br />");
     }
}

$output_make_ref = array(); 
?>
<br>
<b>Running Text Reference entries for RFCs</b>
<?php
exec($make_ref_cmd, $output_make_ref, $status);
if ($status != 0) {
     print("<h1 class=\"error\">ERROR!</h1>");
     print("<p>Script $make_ref_cmd returned non-zero status ${status}! Output was:</p>");
     foreach ($output_make_ref as $line) {
          print($line . "<br />");
     }
     print("<p class=\"error\">End of  $make_ref_cmd error text output.</p>");
     if ($ei_debug !== true) { exit(0); }
}else {
     foreach ($output_make_ref as $line) {
          print($line . "<br />");
     }
}

$output_rfc_ref = array(); 
?>
<br>
<b>Building citation files for RFCs</b>
<?php
exec($parse_ref_cmd ,$output_rfc_ref, $status);
if ($status != 0) {
     print("<h1 class=\"error\">ERROR!</h1>");
     print("<p>Script $parse_ref_cmd returned non-zero status ${status}! Output was:</p>");
     foreach ($output_rfc_ref as $line) {
          print($line . "<br />");
     }
     print("<p class=\"error\">End of $parse_ref_cmd error text output.</p>");
     if ($ei_debug !== true) { exit(0); }
}else {
     foreach ( $output_rfc_ref as $line) {
          print($line . "<br />");
     }
}

?>

</body>
</html>
