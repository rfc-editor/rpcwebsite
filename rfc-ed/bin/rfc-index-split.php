#!/usr/bin/php
<?php

/**********************************************************************/
/* Script Name : rfc-index-split.php                                  */
/* Input file  : rfc-index.html                                       */
/* Output file : rfc-index-100a.html                                  */
/* Description : The script splits the large index file into small    */
/*               file. The o/p shows the first 100 RFCs in ascending  */
/*               order. And does modify the o/p text to link it to    */
/*               input file.                                          */                                        
/**********************************************************************/


$input_file_path = "rfc-index.html"; 
$output_file_path = "rfc-index-100a.html"; 


$new_content = "";
$new_line = "";
$line_counter = 0;


$handle_in = fopen($input_file_path, "r");
$handle_out = fopen($output_file_path, "w");

if (! $handle_in) die("Error opening Read file");
if (! $handle_out) die("Error opening Read file");

if ($handle_in) {
      while (($line = fgets($handle_in)) !== false) {
          //Get the line count
         ++$line_counter;
            //To print the counter
           //print $line_counter;

         /*Stopping to print entries when we are at 100 RFCs*/ 
          if ($line_counter == '2190'){
               break;
          } else {
	      /*Changing the Text in the file to show this file has only 100 RFCs*/
              if (preg_match('/ This file/',$line)){
                 $new_line = "This page shows the first 100 RFCs published. Click <a href=\"/rfc-index.html\">Show All</a> to get the full list (ascending). RFCs are listed in this format:";
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

      $new_content .= '</table><p><a href="/rfc-index.html">Show All</a></p>';
      //$new_content .= '</table>';

  fclose($handle_in);
}
//To print the content
//print $new_content;

if ($handle_out) {
    fwrite($handle_out,$new_content);
    fclose($handle_out);
}


