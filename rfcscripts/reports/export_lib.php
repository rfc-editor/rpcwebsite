<?php
 /* Copyright The IETF Trust 2020 All Rights Reserved */
 /* $Id: export_lib.php,v 1.1.1.1 2016/02/08 21:03:51 priyanka Exp $ */
include("reports_config.php");
$debug_exl = false;
$output    = array();
$status    = null;

$formatters = array('format_queue.pl' => 'queue', 'format_queue2.pl' => 'queue2');

function db_state_changes() {
     global $debug_exl;
     global $output, $status, $cmd_base;

     print("<h2>Database State Changes</h2>");

     $missref_cmd = "/usr/bin/perl $cmd_base/missref.pl $cmd_base/database_information.txt";
     if ($debug_exl) {
          print("<p>exec($missref_cmd)</p>");
     }
     exec($missref_cmd, $output, $status);

     if ($status == 0) {
          print("<p>Successful!</p>");
     } else {
          print("<h3 class=\"error\">ERROR!</h3>");
          print("<p>Script missref.pl returned non-zero status ${status}: \""  . posix_strerror($status));
          print("\"! Output was:</p>");
          foreach ($output as $line) {
               print($line . "<br />");
          }
     }
     return ($status == 0);
}

function update_queue_html() {
     global $debug_exl;
     global $output, $status, $cmd_base, $www_path, $formatters;

     print("<h2>Updating Queue HTML</h2>");

     foreach ($formatters as $script => $outfile) {
          $format_html = "/usr/bin/perl $cmd_base/$script html $www_path/$outfile.html $cmd_base/database_information.txt";
          if ($debug_exl) {
               print("<p>exec($format_html)</p>");
          }
          exec($format_html, $output, $status);
          if ($status == 0) {
               print("<p>Successful execution of $script!</p>");
          } else {
               print("<h3 class=\"error\">ERROR!</h3>");
               print("<p>Script $script returned non-zero status ${status}: \"" . posix_strerror($status));
               print("\" for HTML export! Output was:</p>");
               foreach ($output as $line) {
                    print($line . "<br />");
               }
          }
     }
     return ($status == 0);
}

function update_queue_xml() {
     global $debug_exl;
     global $output, $status, $cmd_base, $www_path, $formatters;

     print("<h2>Updating Queue XML</h2>");
     foreach ($formatters as $script => $outfile) {
          $format_xml = "/usr/bin/perl $cmd_base/$script xml  $www_path/$outfile.xml $cmd_base/database_information.txt";
          if ($debug_exl) {
               print("<p>exec($format_xml)</p>");
          }
          exec($format_xml, $output, $status);

          if ($status == 0) {
               print("<p>Successful execution of $script!</p>");
          } else {
               print("<h3 class=\"error\">ERROR!</h3>");
               print("<p>Script $script returned non-zero status ${status}: \"" .  posix_strerror($status));
               print("\" for XML export! Output was:</p>");
               foreach ($output as $line) {
                    print($line . "<br />");
               }
          }
     }
     return ($status == 0);
}
?>
