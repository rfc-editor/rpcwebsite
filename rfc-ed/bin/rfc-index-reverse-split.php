#!/usr/bin/php
<?php

/**********************************************************************/
/* Script Name : rfc-index-reverse-split.php                          */
/* Input file  : rfc-index2.html                                      */
/* Output file : rfc-index-100d.html                                  */
/* Description : The script splits the large index2 file into small   */
/*               file. The o/p shows the 100 RFCs in descending       */
/*               order. And does modify the o/p text to link it to    */
/*               input file.                                          */                                        
/**********************************************************************/

$input_file_path = "rfc-index2.html"; 
$output_file_path = "rfc-index-100d.html"; 


$new_content = "";
$new_line = "";
$line_counter = 0;
$total_rfc_count = 1;
$rfc_count = 1;

$handle_in = fopen($input_file_path, "r");
$handle_out = fopen($output_file_path, "w");

if (! $handle_in) die("Error opening Read file");
if (! $handle_out) die("Error opening Read file");

if ($handle_in) {
      while (($line = fgets($handle_in)) !== false) {
         if (preg_match('/tr valign="top"/',$line)) {
             ++$total_rfc_count;
         }
     }
  fclose($handle_in);
}

$total_rfc_count = htmlspecialchars(json_encode($total_rfc_count));


$handle_in = fopen($input_file_path, "r");


if ($handle_in) {
      while (($line = fgets($handle_in)) !== false) {
          //Get the line count
         ++$line_counter;
            //To print the counter
           //print $line_counter;

          if (preg_match('/tr valign="top"/',$line)) {

              ++$rfc_count;

          } 
         
          //To print the rfc count and line number
          //print "The RFC count is $rfc_count \n";
          //print "The line is $line \n";  
        
	  /*Stopping to print entries when we are at 100 RFCs*/ 
	  if ($rfc_count == '101'){
               break;
          } else {
	      /*Changing the Text in the file to show this file has only 100 RFCs*/
              if (preg_match('/ This file/',$line)){
                 $new_line = "This page shows the last 100 RFCs published. Click <a href=\"/rfc-index2.html\">Show All</a> to get the full list (descending). RFCs are listed in this format:";
              } elseif (preg_match('/<h2>Key to Citations/',$line)){
                      $new_line = "<h2>Key to Entries</h2>";
              }
                else {
                  $new_line = $line;
              }
          }     

          //To print the line
          //print $new_line;
          
         $new_content .= $new_line;
      }

      $new_content .= '</table><p><a href="/rfc-index2.html">Show All</a></p>';
      //$new_content .= '</table>';

  fclose($handle_in);
}
//To print the content
//print $new_content;

if ($handle_out) {
    fwrite($handle_out,$new_content);
    fclose($handle_out);
}


