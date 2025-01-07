<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
/*July 2017 : Added dfonseca@amsl.com to Autosystem recipient. - PN*/
/*Oct 2018  : Removed dfonseca@amsl.com from Autosystem recipient. - PN*/
/*Apr 2019  : Added csmiley@amsl.com to Autosystem recipient - PN*/
/*May 2022  : Added apaloma@amsl.com to Autosystem recipient - PN*/
/*Dec 2023  : Added starrant@amsl.com to Autosystem recipient - PN*/

$debug_json = FALSE;
$template_dir = "/a/home/rfc-ed/Templates/JSONInput-msgs/";


/*********************************************************************************************/
/*Function :  send_published_mail is called from JSON parser script when the draft passed    */
/*            from the datatracker is already published                                      */
/*********************************************************************************************/

function send_published_mail($in_draft){

global $debug_json;

$type = "PUB";
$template = select_template($type);


$tplate_f = fopen($template,'r');

if ($tplate_f == false) {
	print('<p class="error">Failed to open template file "' . $template. '". ');
	$errnum = posix_get_last_error();
	print("Error was ($errnum) " . posix_strerror($errnum) . ".");
	return false;         // bail out of routine!
  }

$body = extract_template_text($tplate_f);
fclose($tplate_f);


// For duplicate, these make the substitutions.
$body = preg_replace("/DRAFT_NAME/", $in_draft,$body);

$subject = "needs review: status change? $in_draft";
$to = "sginoza@staff.rfc-editor.org,arusso@staff.rfc-editor.org,lbartholomew@staff.rfc-editor.org,kmoore@staff.rfc-editor.org,apaloma@staff.rfc-editor.org,starrant@staff.rfc-editor.org";
$headers  = "From: autosystem@rfc-editor.org\n";

if ($debug_json) {
	  print "<h3>send_published_mail</h3>\n";
	  print "\$template = "; var_dump($template); print "<br />\n";
	  print "\$to = "; var_dump($to); print "<br />\n";
#		  print "\$cc = "; var_dump($cc); print "<br />\n";
	  print "\$subject =  "; var_dump(htmlspecialchars($subject)); print "<br />\n";
	  print "\$headers =  "; var_dump(htmlspecialchars($headers)); print "<br />\n";
	  print "\$body<br />\n<pre>\n";
	  print_r(htmlspecialchars($body));
	  print "\n</pre>\n";
}

       return !($rsl_error) ?  send_mail($to,$subject,$body,$headers) : false;

}

/*********************************************************************************************/
/*Function :  select_template function selects the appropriate template from template dir    */
/*            based on the input type                                                        */
/*********************************************************************************************/
function select_template($in_type){

       global $debug_json;
       global $template_dir;
       $template = $template_dir;


       switch($in_type){

           case "PUB": 
			$template .= "PUBDraft_msg.txt";			
			break;
	   case "DUPLICATE":
			    $template .= "DUPLICATEDraft_msg.txt";
			    break;
	   case "VERSION":
			   $template .= "VERSIONDraft_msg.txt";
		           break;	
	   case "NEW": 
			$template .= "INSERTDraft_msg.txt";
			break;
	   default:
			break;

       }//End switch	

       return $template;
}

/***********************************************************************************************/
/*Function :  extract_template_text function extracts the template contents based on the handle*/
/***********************************************************************************************/
function extract_template_text($handle) {
       global $debug_rsl;

       if ($handle) {
          if ($debug_rsl)  print "<pre>";
          while (!feof($handle)) {
               $line = fgets($handle, 120);
               if ($debug_rsl)  print $line;
               if (preg_match("/^TEXT:/",$line) == 1) {
                    break;
               }
          }
          $text = stream_get_contents($handle);
          if ($debug_rsl) print "</pre>";
          return $text;
       } else {
          print "<p class=\"error\">extract_template_text called with no file handle!</p>";
       }
     return null;
}

/***********************************************************************************************/
/*Function :  send_mail function sends the mail using the headers, addresses and body passed by*/
/*            the calling function                                                             */
/***********************************************************************************************/
function send_mail($to,$subject,$body,$headers) {
     // For reasons that are unclear (lost in the innards of SMTP mail
     // transport agents) some MTAs transform the body to
     // double-spaced if there's a CR in the data.
     $body_stripped = preg_replace("/\r/", "", $body);
     $header_stripped = preg_replace("/\r/", "", $headers);
     $mail_sent = mail($to,$subject,$body_stripped,$header_stripped);


     if ($mail_sent === false) { // Report the error and dump key variables
          print("<h3>EMail Error</h3><p class=\"error\">Error sending email to " . htmlspecialchars($to) . ". Dump of variables follows</p>");
          print("<p><pre>");
          print("\$header_stripped: \n"); var_dump($headers);
          print("</pre></p>");
          print("<p>");
          print("\$to      = "); var_dump($to);      print("<br />");
          print("\$subject = "); var_dump($subject); print("<br />");
          print("</p>");
          print("<p><pre>");
          print("\$body_stripped    = "); var_dump($body);
          print("</pre></p>");
          return false;         // Keep on screen
     } else {
          return true;          // if no errors
     }


}

/*********************************************************************************************/
/*Function :  send_duplicate_mail is called from JSON parser script when the draft passed    */
/*            from the datatracker is present in the queue                                   */
/*********************************************************************************************/
function send_duplicate_mail($in_draft){

global $debug_json;

$type = "DUPLICATE";
$template = select_template($type);


$tplate_f = fopen($template,'r');

if ($tplate_f == false) {
	print('<p class="error">Failed to open template file "' . $template. '". ');
	$errnum = posix_get_last_error();
	print("Error was ($errnum) " . posix_strerror($errnum) . ".");
	return false;         // bail out of routine!
  }

$body = extract_template_text($tplate_f);
fclose($tplate_f);


// For duplicate, these make the substitutions.
$body = preg_replace("/DRAFT_NAME/", $in_draft,$body);

$subject = "needs review: already in queue $in_draft";
$to = "sginoza@amsl.com,arusso@amsl.com,lbartholomew@amsl.com,kmoore@amsl.com,csmiley@amsl.com,apaloma@amsl.com,starrant@amsl.com";
$headers  = "From: autosystem@rfc-editor.org\n";


if ($debug_json) {
	  print "<h3>send_duplicate_mail</h3>\n";
	  print "\$template = "; var_dump($template); print "<br />\n";
	  print "\$to = "; var_dump($to); print "<br />\n";
#		  print "\$cc = "; var_dump($cc); print "<br />\n";
	  print "\$subject =  "; var_dump(htmlspecialchars($subject)); print "<br />\n";
	  print "\$headers =  "; var_dump(htmlspecialchars($headers)); print "<br />\n";
	  print "\$body<br />\n<pre>\n";
	  print_r(htmlspecialchars($body));
	  print "\n</pre>\n";
}

       return !($rsl_error) ?  send_mail($to,$subject,$body,$headers) : false;

}

/***************************************************************************************************/
/*Function :  send_version_change_mail is called from JSON parser script when the version of draft */
/*	      passed from the datatracker exist in the queue                                       */
/***************************************************************************************************/
function send_version_change_mail($in_draft,$rev){

global $debug_json;

$type = "VERSION";
$template = select_template($type);


$tplate_f = fopen($template,'r');

if ($tplate_f == false) {
	print('<p class="error">Failed to open template file "' . $template. '". ');
	$errnum = posix_get_last_error();
	print("Error was ($errnum) " . posix_strerror($errnum) . ".");
	return false;         // bail out of routine!
  }

$body = extract_template_text($tplate_f);
fclose($tplate_f);


// For duplicate, these make the substitutions.
$body = preg_replace("/DRAFT_NAME/", $in_draft,$body);
$body = preg_replace("/REV/",$rev,$body);

$subject = "needs review: new version? $in_draft";
$to = "sginoza@amsl.com,arusso@amsl.com,lbartholomew@amsl.com,kmoore@amsl.com,csmiley@amsl.com,apaloma@amsl.com,starrant@amsl.com";
$headers  = "From: autosystem@rfc-editor.org\n";

if ($debug_json) {
	  print "<h3>send_version_change_mail</h3>\n";
	  print "\$template = "; var_dump($template); print "<br />\n";
	  print "\$to = "; var_dump($to); print "<br />\n";
#		  print "\$cc = "; var_dump($cc); print "<br />\n";
	  print "\$subject =  "; var_dump(htmlspecialchars($subject)); print "<br />\n";
	  print "\$headers =  "; var_dump(htmlspecialchars($headers)); print "<br />\n";
	  print "\$body<br />\n<pre>\n";
	  print_r(htmlspecialchars($body));
	  print "\n</pre>\n";
}

       return !($rsl_error) ?  send_mail($to,$subject,$body,$headers) : false;


}

/***************************************************************************************************/
/*Function :  send_new_draft_mail is called from JSON parser script when the draft  passed from    */
/*            the datatracker added to the queue                                                   */
/***************************************************************************************************/
function send_new_draft_mail($in_draft){

global $debug_json;

$type = "NEW";
$template = select_template($type);


$tplate_f = fopen($template,'r');

if ($tplate_f == false) {
	print('<p class="error">Failed to open template file "' . $template. '". ');
	$errnum = posix_get_last_error();
	print("Error was ($errnum) " . posix_strerror($errnum) . ".");
	return false;         // bail out of routine!
  }

$body = extract_template_text($tplate_f);
fclose($tplate_f);

/*Get the draft name*/
$draft = substr($in_draft,0,-3);//Get the draft name

// For duplicate, these make the substitutions.
$body = preg_replace("/DRAFT_NAME/", $in_draft,$body);
$body = preg_replace("/DRAFT/",$draft,$body);

$subject = "needs review: row added for $in_draft";
$to = "sginoza@amsl.com,arusso@amsl.com,lbartholomew@amsl.com,kmoore@amsl.com,csmiley@amsl.com,apaloma@amsl.com,starrant@amsl.com";
$headers  = "From: autosystem@rfc-editor.org\n";

if ($debug_json) {
	  print "<h3>send_new_draft_mail</h3>\n";
	  print "\$template = "; var_dump($template); print "<br />\n";
	  print "\$to = "; var_dump($to); print "<br />\n";
#		  print "\$cc = "; var_dump($cc); print "<br />\n";
	  print "\$subject =  "; var_dump(htmlspecialchars($subject)); print "<br />\n";
	  print "\$headers =  "; var_dump(htmlspecialchars($headers)); print "<br />\n";
	  print "\$body<br />\n<pre>\n";
	  print_r(htmlspecialchars($body));
	  print "\n</pre>\n";
}

       return !($rsl_error) ?  send_mail($to,$subject,$body,$headers) : false;


}	

?>
