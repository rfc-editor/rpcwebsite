<?php
/*******************************************************************************************/
/* Copyright The IETF Trust 2020 All Rights Reserved                                       */
/* The Script to process the requested document from the hidden directory srtucture        */
/* to end user. The script hides the underline directory structure from the user.          */
/*                                                                                         */
/* Note: The initial deployment of the script uses an Apache rewrite rule:                 */
/*                                                                                         */
/*       RewriteRule ^/rfc/rfc([0-9]+)$     /rfchandler.php?rfc=$1&type=$2                 */ 
/*                                                                                         */
/* The rule has the web server call the script for the given URL pattern; the              */
/* script is not called directly.                                                          */
/*                                                                                         */
/*November 2017 : Initial script related to V3 directory structure - PN                    */
/*January 2019 : Created the script - PN                                                   */
/*April 2019   : Script is modify to server only for HTML files for Phase I and directory  */
/*               related handler will go in Phase II - PN                                  */
/*May 2019     : Added script to production under Publication format changes Phase I       */
/*               - PN                                                                      */
/*May 2019     : Modified the script to handle the case where document is not publised     */
/*               .html file does not exist - PN                                            */
/*******************************************************************************************/

include('handler_lib.php');
include('format_html_header.php');
 
$debug_handler= FALSE;


define("MAX_RFCID",5); // Trim the input string to just five digits.
define("MAX_TYPE",6); // Trim the input string to just five digits.

/*Program begins*/
/*Get the input*/
if (isset($_GET) && array_key_exists('rfc',$_GET)) {
     $in_num = substr($_GET['rfc'], 0, MAX_RFCID);
     $display = 'rfc';
}
elseif (isset($_GET) && (!(array_key_exists('rfc',$_GET)))){
	if (array_key_exists('bcp',$_GET)) {
	     $in_num = substr($_GET['bcp'], 0, MAX_RFCID);
	     $display = 'bcp';	
        }
 	if (array_key_exists('std',$_GET)) {
	     $in_num = substr($_GET['std'], 0, MAX_RFCID);
	     $display = 'std';	
        }
 	if (array_key_exists('fyi',$_GET)) {
	     $in_num = substr($_GET['fyi'], 0, MAX_RFCID);
	     $display = 'fyi';	
        }

}

if (isset($_GET) && array_key_exists('type',$_GET)) {
     $type = substr($_GET['type'], 0, MAX_TYPE);
}

if ($debug_handler === TRUE) {
     if (isset($_POST)) {
          print("\$_POST<br /><pre>");
          print_r($_POST);
          print("</pre>");
     }
     if (isset($_GET)) {
          print("\$_GET<br /><pre>");
          print_r($_GET);
          print("</pre>");
     }
}

//Check if this RFC document present in the database as a Published document ie state_id = 14
  $rfc_count = check_doc_status($in_num,$display,$type);

  if ($rfc_count > 0) {
     /*Decide the sub-directory and check if file exists and print header and file*/
     $output_file = get_subdirectory($in_num,$display,$type);
       if (file_exists($output_file)){
            if ($in_num <= '8649') {
                $html_header = get_html_header($in_num,$display,$type);
                print $html_header;
                include($output_file); 
            }else {
/*                header('Content-Description: File Transfer');
                header('Content-Type: text/html');
                header('Content-Disposition: inline; filename="'.basename($output_file).'"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($output_file));
                readfile($output_file);
                //exit();
*/
                include($output_file);
            }
       }else {
           $html_error_header = get_error_header($in_num,$display,$type);
           print $html_error_header; 
           print "<br>"; 
           $display = strtoupper($display);
           print "<h1>HTML file does not exist</h1>";
 
       }
  }else { //  When the document is present in the database as un-published document no need to display HTML instead display this.
           $html_error_header = get_error_header($in_num,$display,$type);
           print $html_error_header;
           $display = strtoupper($display);
           print "<br>"; 
           print "<h1>HTML file does not exist</h1>";
  }
 
 
?>
