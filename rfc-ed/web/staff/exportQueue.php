<?php
  /* Copyright The IETF Trust 2020 All Rights Reserved */
  /* $Id: exportQueue.php,v 2.2 2012/03/13 18:15:49 priyanka Exp $ */
$debug = false;
include("export_lib.php");
print<<<END
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>Export Queue</title>
	<meta name="robots" content="noindex, nofollow">
	<meta name="robots" content="noarchive">
        <link rel="stylesheet" type="text/css" href="./style/main.css">
</head>
<body>
<hr>
<a href="index.php"><img class="header" src="./RFCedlogo.jpg" alt="RFC ED header" border="0" width="220"/></a>
<hr>
END;

if ($debug) {
     print("\$_POST<br><pre>");
     print_r($_POST);
     print("</pre>");
}
#XXX missref.pl call removed
#$stat1 = db_state_changes();
print("<br />");
$stat2 = update_queue_html();
print("<br />");
$stat3 = update_queue_xml();

if ($debug) {
     print("<p>Debug Mode: Close window manually.</p>");
} else {
#XXX missref.pl call removed
#     if ($stat1 && $stat2 && $stat3) {
#XXX missref.pl call removed
     if ($stat2 && $stat3) {
          print("<script type=\"text/javascript\">\n"); // close the window
          print("<!-- \n");
          print("window.close();\n");
          print("-->\n");
          print("</script>\n");
     } else {
          print "<p class=\"error\">One or more export_lib routines returned a error condition.</p>";
     }
}
print("</body></html>");
?>
