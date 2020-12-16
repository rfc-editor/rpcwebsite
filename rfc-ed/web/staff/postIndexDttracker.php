<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
$post_debug = false;
$cmd_base = "/www/staff";
$script = "rfcrss.php";
$atom_script = "rfcatom.php"; 

$format_rss = "$cmd_base/rfcrss.php > /a/www/rfc/htdocs/rfcrss.xml";
$format_atom = "$cmd_base/rfcatom.php > /a/www/rfc/htdocs/rfcatom.xml"

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>HTTP POST INDEX DATATRACKER</title>
	<meta name="robots" content="noindex, nofollow">
	<meta name="robots" content="noarchive">
        <link rel="stylesheet" type="text/css" href="./style/main.css">
</head>
<body>
<hr>
<a href="index.php"><img class="header" src="./RFCedlogo.jpg" alt="RFC ED header" border="0" width="220"/></a>
<hr>
<?php
     if ($post_debug === true) {
          print("\$_POST<br><pre>");
          print_r($_POST);
          print("</pre>");
     }
?>
<?php

 $ch = curl_init();
 curl_setopt($ch, CURLOPT_URL, "https://datatracker.ietf.org/sync/rfceditor/notify/index/");


 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 curl_setopt ($ch, CURLOPT_POST, true);

 curl_setopt ($ch, CURLOPT_POSTFIELDS, "username=rfcedsync&password=^L8$9@qVY4gQ");

 $output = curl_exec ($ch);
 $info = curl_getinfo($ch);
 curl_close ($ch);

// print_r($info);
//print $output;

if ($output == 'OK'){
        print "<h2>Posting rfc-index.xml to Datatracker... Done.</h2>";

}else {
	print "<h2>Posting rfc-index.xml to Datatracker... Not Done.</h2>";
	print $output;

}

/*if ($post_debug === true) {
     print("<p>Debug Mode: Close window manually.</p>");
} else {
     print("<script type=\"text/javascript\">\n"); // close the window
     print("<!-- \n");
     print("window.close();\n");
     print("-->\n");
     print("</script>\n");
*/
?>
   
<b>Updating RSS</b>

<?php
	  exec($format_rss, $output, $status);

          if ($status == 0) {
               print("<p>Successful execution of $script!</p>");
          } else {
               print("<h3 class=\"error\">ERROR!</h3>");
               print("<p>Script $script returned non-zero status ${status}: \"" .  posix_strerror($status));
               print("\" for RSS feed! Output was:</p>");
               foreach ($output as $line) {
                    print($line . "<br />");
               }
          }


?>
<b>Updating Atom</b>

<?php
	  exec($format_atom, $output, $status);

          if ($status == 0) {
               print("<p>Successful execution of $atom_script!</p>");
          } else {
               print("<h3 class=\"error\">ERROR!</h3>");
               print("<p>Script $atom_script returned non-zero status ${status}: \"" .  posix_strerror($status));
               print("\" for RSS feed! Output was:</p>");
               foreach ($output as $line) {
                    print($line . "<br />");
               }
          }

?>
</body>
</html>
