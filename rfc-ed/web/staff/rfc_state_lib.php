<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
# $Id: rfc_state_lib.php,v 2.14 2020/11/11 01:03:17 priyanka Exp $
# $Id: rfc_state_lib.php,v 2.14 2020/11/11 01:03:17 priyanka Exp $
# $Id: rfc_state_lib.php,v 2.14 2020/11/11 01:03:17 priyanka Exp $

/* November 2020 : Modified the script to use PDO prepared statements - PN                                 */

include_once("ams_util_lib.php");

#$debug_rsl = true; // Add debug messages to HTML output
$debug_rsl = false; // Add debug messages to HTML output

$rsl_error = false; // Module global error flag

$template_dir = "/home/rfc-ed/Templates/RFCstate-msgs/";

function edit_needs_announcement($old_fields, $new_fields)
{
     global $debug_rsl;

     if ($debug_rsl) {
          print("<h4>send_state_msg_form()</h4><pre>");
          print_r($old_fields);
          print_r($new_fields);
          print("</pre>");
     }

 /*    if ($old_fields['state'] <> $new_fields['state']) {
          switch ($new_fields['state']) {
          case "PUB":
          case "AUTH48":
          case "WITHDRAWN":
               break;           // return false for these regardless
          default:
               return true;
          }
     }
*/

   #Get the combined state from state_id,IANA flag, ref flag and generation number
   $old_state = get_statename_from_array($old_fields);
   $new_state = get_statename_from_array($new_fields);

#   if ($old_fields['state_id'] <> $new_fields['state_id']) {
   if ($old_state <> $new_state) {
          switch ($new_fields['state_id']) {
          case "14":
          case "2":
          case "19":
          case "23":
               break;           // return false for these regardless
          default:
               return true;
          }
     }

     return false;
}

function send_new_add_msg_form($new_fields) {
     global $debug_rsl;

     if ($debug_rsl) {
          print("<h4>send_state_msg_form()</h4><pre>");
          print_r($new_fields);
          print("</pre>");
     }

     print "\n<p class=\"notice\">You have added a new draft to the queue. An [RFC State] message will " . 
           " automatically be sent to the authors unless you click NO MESSAGE below.</p>\n";

     print "\n<div class=\"simple_center\">\n";
     print "<form action=\"rfc_state_message.php\" method=\"post\">\n";
     foreach ($new_fields as $name => $value ) {
	if ( $value ) 
	    print "<input name=\"" . htmlspecialchars($name) . "\" value=\"" . htmlspecialchars($value) . 
		"\" type=\"hidden\"/>\n";
     }
     print "<input name=\"submit\" " .  "value=\"GO AHEAD, SEND IT\" " . 
          "type=\"submit\"/>\n";
     print "<input name=\"submit\" " .  "value=\"NO MESSAGE\" " . 
          "type=\"submit\"/>\n";
     print "</form>\n</div>\n";
}

function send_state_msg_form($old_fields, $new_fields) {
     global $debug_rsl;

     if ($debug_rsl) {
          print("<h4>send_state_msg_form()</h4><pre>");
          print_r($old_fields);
          print_r($new_fields);
          print("</pre>");
     }

    $old_state = get_state_from_array($old_fields);
    $new_state = get_state_from_array($new_fields);

/*     print "\n<p class=\"notice\">you have changed the state of this draft from " .
           $old_fields['state'] . " to " . $new_fields['state'] . 
*/
     print "\n<p class=\"notice\">you have changed the state of this draft from " .
           $old_state . " to " . $new_state . 
           ". An [RFC State] message will automatically be sent to the authors unless " . 
           "you click NO MESSAGE below.</p>\n";
     print "<form action=\"rfc_state_message.php\" method=\"post\">\n";
/*     print "<input name=\"old_state\" value=\"" . htmlspecialchars($old_fields['state']) . 
           "\" type=\"hidden\" />\n";
*/
     print "<input name=\"old_state\" value=\"" . htmlspecialchars($old_state) . 
           "\" type=\"hidden\" />\n";

     foreach ($new_fields as $name => $value ) {
	if ( $value ) 
	    print "<input name=\"$name\" value=\"" . htmlspecialchars($value) . 
		"\" type=\"hidden\"/>\n";
     }
     print "\n<div class=\"simple_center\">\n";
     print "<input name=\"submit\" " .  "value=\"GO AHEAD, SEND IT\" " . 
          "type=\"submit\"/>\n";
     print "<input name=\"submit\" " .  "value=\"NO MESSAGE\" " . 
          "type=\"submit\"/>\n";
     print "</form>\n</div>\n";
}

/*
 * This function goes to the database to get the author email addresses. It
 * isn't necessary as this data is in the $_POST variable.
 */
function retrieve_author_emails($internal_key) {
   global $pdo;

   $query = "SELECT email FROM `index` WHERE internal_key=:internal_key";
  
   try {  
       $stmt = $pdo->prepare($query);
       $stmt->bindParam('internal_key',$internal_key);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : retrieve_author_emails", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   } 
   
    
   $row = $stmt->fetch(PDO::FETCH_ASSOC);
   $addresses = $row['email'];
   
   if ($addresses === false) {
          die ("Query \"" . $query . "\" returned an empty set");
   }
   return $addresses;
}

function retrieve_cc_emails() {
     $cc_list = null;
     $source = $_POST['source'];
     $doc_shepherd = $_POST['doc_shepherd'];
     $iesg_contact = $_POST['iesg_contact'];

#Newly added code to handle IRTF groups
     
     $ssp_id = get_wg_ssp_id($_POST['draft']);
     //If the ssp_id = 4 then just send mail to irtf-chair@ietf.org irrespective of
     //source
     if ($ssp_id == 4){
     	          $cc_list .= "irtf-chair@ietf.org";
     }else {
   	     switch ($source) {
	     case "IETF - NON WORKING GROUP":
		  break; 	 // don't add anyone
	     case "INDEPENDENT":
		  $cc_list .= "rfc-ise@rfc-editor.org";
	          break;                
	     case "IAB":
	          $cc_list .= "iab@ietf.org";
        	  break;
	     case "IESG":
	          $cc_list .= "iesg@ietf.org";
        	  break;
	     case "IRTF":
        	  $cc_list .= "irtf-chair@ietf.org";
	          break;
	     default:
          // for working groups, lookup in the database
        	  $cc_list .= retrieve_working_group_emails($source);
	          break;
     	     }
     }
#End of new code    
     // append the RFC Editor address  before returning
     if ($cc_list == null) {
          $cc_list = "rfc-editor@rfc-editor.org";
     } else {
          $cc_list .= ", rfc-editor@rfc-editor.org";
     }

     // append doc_schepherd and iesg_contact if available 
     if (!empty($doc_shepherd)) {
          $cc_list .= ", $doc_shepherd";
     }
     if (!empty($iesg_contact)) {
          $cc_list .= ", $iesg_contact";
     }

     return $cc_list;
}

/*
 * For Work Group-related documents, it is necessary to go to the database
 * to get the strings needed to generate the working group addresses.
 * (That needs to change...)
 */
function retrieve_working_group_emails($source) {
   global $rsl_error;
   global $debug_rsl;
   global $pdo;

     
   $wgQuery = "SELECT wg_acronym, wg_chair_name, area_director_name  FROM `working_group`, `area` WHERE `wg_name` = :source AND area.area_name=working_group.area_name";
   
   try { 
       $stmt = $pdo->prepare($wgQuery);
       $stmt->bindParam('source',$source);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : retrieve_working_emails", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   } 

   $wgLine = $stmt->fetch(PDO::FETCH_ASSOC);
   

   if ($num_of_rows > 0) {
       $wgname = $wgLine['wg_acronym'];
       $wg_addrs = $wgname."-ads@ietf.org, " . $wgname."-chairs@ietf.org";

       if ($debug_rsl) {
           print "<h5>retrieve_working_group_emails</h5>\n<pre>\n";
           print "\$source="; var_dump($source);
           print "\n<br />\$wgQuery="; var_dump($wgQuery);
           print "\n<br />\$wgLine=";
           print_r($wgLine);
           print "\n<br />\$wgname="; var_dump($wgname);
           print "\n<br />\$wg_addrs="; var_dump($wg_addrs);
           print "</pre>";
       }
       return $wg_addrs;
   }
   return null;
}
function select_template_edit($old_state,$new_state) {
     global $template_dir;
#     $template = $template_dir . "RFCstate.any:any";

     $template = $template_dir;	
     switch ($old_state){
	case "PENDING":
          $template .= "RFCstate.none:any";
          break;
	default:
	  $template .= "RFCstate.any:any";
          break;
     }	

     return $template;
}

function select_template_add($state) {
     global $template_dir;
     $template = $template_dir;
     switch ($state) 
     {
     case "ISR":
          $template .= "RFCstate.none:ISR";
          break;
     default:
          $template .= "RFCstate.none:any";
          break;
     }
     return $template;
}

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

function send_mail($to,$subject,$body,$headers) {
     // For reasons that are unclear (lost in the innards of SMTP mail
     // transport agents) some MTAs transform the body to
     // double-spaced if there's a CR in the data.
     $body_stripped = preg_replace("/\r/", "", $body);
     $header_stripped = preg_replace("/\r/", "", $headers);
     $mail_sent = ams_mail($to,$subject,$body_stripped,$header_stripped);


     if ($mail_sent === false) { // Report the error and dump key variables
          print("<h3>EMail Error</h3><p class=\"error\">Error sending email to $to. Dump of variables follows</p>");
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
function send_rfc_state_msg_edit() {
     global $rsl_error;
     global $debug_rsl;

#XXX
     $state_id = $_POST['state_id'];

     if ($state_id){
	$state_name = get_statename($state_id);	
     }
      if ($_POST['iana']){
	  $iana = '*A';	
      }
      else {
	  $iana = '';
      }	
      if ($_POST['ref_flag']){
	  $ref_flag = '*R';	
      }
      else {
	  $ref_flag = '';
      }	
     
      if (!($_POST['generation_number'])){
  	  $generation_number = '';
      }else{
	      $generation_number = $_POST['generation_number'];
              $generation_number = "(".$generation_number."G)";
       }

     $state = "$state_name$iana$ref_flag$generation_number"; 

#     $template = select_template_edit($_POST['old_state'],$_POST['state']);

     $template = select_template_edit($_POST['old_state'],$state);
#XXX
     $tplate_f = fopen($template,'r');

     if ($tplate_f == false) {
          print('<p class="error">Failed to open template file "' . $template. '". ');
          $errnum = posix_get_last_error();
          print("Error was ($errnum) " . posix_strerror($errnum) . ".");
          return false;         // bail out of routine!
     }

     $body = extract_template_text($tplate_f);
     fclose($tplate_f);


     if ($_POST['old_state'] != 'PENDING') {
	     // For any:any, these make the substitutions.
	     $body = preg_replace("/DRAFT_NAME/", $_POST['draft'],$body);
	     $body = preg_replace("/OLD_STATE/",$_POST['old_state'],$body);
	#     $body = preg_replace("/NEW_STATE/",$_POST['state'],$body);
	     $body = preg_replace("/NEW_STATE/",$state,$body);

	     $body = preg_replace("/DRAFT_WITH_NO_VERSION_NUMBER/",
				  substr($_POST['draft'],0,(strlen($_POST['draft'])-3)),
				  $body);

	     $subject = "[RFC State] <" . $_POST['draft'] ."> has changed to $state state";
     } else {
	     // For none:any, these make the substitutions. For none:ISR, nothing is changed.
	     $body = preg_replace("/DRAFT_NAME/", $_POST['draft'],$body);
	     $body = preg_replace("/DRAFT_WITH_NO_VERSION_NUMBER/",
				  substr($_POST['draft'],0,(strlen($_POST['draft'])-3)),
				  $body);

	     $subject = "[RFC State] <" . $_POST['draft'] . "> has been added to the RFC Editor database";

     }	

     $to = $_POST['e_mail'];
     $cc =  retrieve_cc_emails();
     $headers  = "From: rfc-editor@rfc-editor.org\n";
     $headers .= "Cc: " . $cc . "\n";

     if ($debug_rsl) {
          print "<h3>send_rfc_state_msg_edit</h3>\n";
          print "\$template = "; var_dump($template); print "<br />\n";
          print "\$to = "; var_dump($to); print "<br />\n";
          print "\$cc = "; var_dump($cc); print "<br />\n";
          print "\$subject =  "; var_dump(htmlspecialchars($subject)); print "<br />\n";
          print "\$headers =  "; var_dump(htmlspecialchars($headers)); print "<br />\n";
          print "\$body<br />\n<pre>\n";
          print_r(htmlspecialchars($body));
          print "\n</pre>\n";
     }

     return !($rsl_error) ?  send_mail($to,$subject,$body,$headers) : false;

}

function send_rfc_state_msg_add() {
     global $rsl_error;
     global $debug_rsl;

     $state_id = $_POST['state_id'];

     if ($state_id){
	$state_name = get_statename($state_id);	
     }
      if ($_POST['iana']){
	  $iana = '*A';	
      }
      else {
	  $iana = '';
      }	
      if ($_POST['ref_flag']){
	  $ref_flag = '*R';	
      }
      else {
	  $ref_flag = '';
      }	
     
      if (!($_POST['generation_number'])){
  	  $generation_number = '';
      }else{
	      $generation_number = $_POST['generation_number'];
              $generation_number = "(".$generation_number."G)";
       }

     $state = "$state_name$iana$ref_flag$generation_number"; 

     $template = select_template_add($state);

     $tplate_f = fopen($template,'r');

     if ($tplate_f == false) {
          print('<p class="error">Failed to open template file "' . $template. '". ');
          $errnum = posix_get_last_error();
          print("Error was ($errnum) " . posix_strerror($errnum) . ".");
          return false;         // bail out of routine!
     }

     $body = extract_template_text($tplate_f);
     fclose($tplate_f);

     // For none:any, these make the substitutions. For none:ISR, nothing is changed.
     $body = preg_replace("/DRAFT_NAME/", $_POST['draft'],$body);
     $body = preg_replace("/DRAFT_WITH_NO_VERSION_NUMBER/",
                          substr($_POST['draft'],0,(strlen($_POST['draft'])-3)),
                          $body);

     $subject = "[RFC State] <" . $_POST['draft'] . "> has been added to the RFC Editor database";
     $to = $_POST['e_mail'];
     $cc =  retrieve_cc_emails();
     $headers  = "From: rfc-editor@rfc-editor.org\n";
     $headers .= "Cc: " . $cc . "\n";



     if ($debug_rsl) {
          print "<h3>send_rfc_state_msg_add</h3>\n";
          print "\$template = "; var_dump($tplate_f); print "<br />\n";
          print "\$to = "; var_dump($to); print "<br />\n";
          print "\$cc = "; var_dump($cc); print "<br />\n";
          print "\$subject =  "; var_dump(htmlspecialchars($subject)); print "<br />\n";
          print "\$headers =  "; var_dump(htmlspecialchars($headers)); print "<br />\n";
          print "\$body<br />\n<pre>\n";
          print_r(htmlspecialchars($body));
          print "\n</pre>\n";
     }

     return !($rsl_error) ?  send_mail($to,$subject,$body,$headers) : false;
}

function send_rfc_state_msg()  {
     global $debug_rsl;

     if ($debug_rsl) {
          print "<h4>\$_POST</h4><pre>";
          print_r($_POST);
          print "</pre>";
     }
     
     $internal_key = $_POST['internal_key'];
     if ($internal_key) {
          $status = send_rfc_state_msg_edit();
     } else {
          $status = send_rfc_state_msg_add();
     }
     return $status; // controls where error messages stay on screen.
}



/******************************************************************************************************/
/*Gets the state with all the fields that makes whole as a                                            */ 
/*STATE(state_id-name,iana_flag,ref_flag,generation)						      */
/*This function is no more in use								      */	
/******************************************************************************************************/
function get_statename_from_array($record){
      global $debug_edit_lib;

      $state_id = $record['state_id'];
      $state_name = get_statename($state_id);
      if ($record['iana']){
	  $iana = '*A';	
      }
      else {
	  $iana = '';
      }	
      if ($record['ref_flag']){
	  $ref_flag = '*R';	
      }
      else {
	  $ref_flag = '';
      }	
     
      if (!($record['generation_number'])){
  	  $generation_number = '';
      }else{
	      $generation_number = "(".$record['generation_number']."G)";
       }

      $combine_state = "$state_name$iana$ref_flag$generation_number";
      
      if ($debug_edit_lib === true) {
	print("<h3>get_state_from_array</h3>");
	print("&nbsp;query=");     var_dump($query);      print("<br />");
	print("&nbsp;combine_state=");     var_dump($combine_state);      print("<br />");
      }
     
      return $combine_state;
}

/******************************************************************************************************/
/*Gets the state from the passed state id                                                             */ 
/******************************************************************************************************/
function get_statename($state_id){
   global $pdo;
 
   $query = "SELECT state_name FROM `states` where state_id = :state_id";
     
   try { 
       $stmt = $pdo->prepare($query);
       $stmt->bindParam('state_id',$state_id);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : get_statename", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   } 

   while ($row_state_id = $stmt->fetch(PDO::FETCH_ASSOC)){
       $state_name = $row_state_id['state_name']; 
   }
   return $state_name;
}

function get_wg_ssp_id($draftname) {
   global $debug_rsl;
   global $pdo;

   $query =
       "SELECT w.ssp_id
        FROM `index` i, working_group w
        WHERE w.wg_name = i.source AND i.draft=:draftname";
    
   if ($debug === true) {
       print("<pre>get_wg_ssp_id: $query\n</pre>");
   }
   
   try { 
       $stmt = $pdo->prepare($query);
       $stmt->bindParam('draftname',$draftname);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : get_wg_ssp_id", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   } 
     
   $wg_ssp_id = $stmt->fetch(PDO::FETCH_ASSOC);
   if ($debug_rsl === true) {
       print("<pre>get_wg_ssp_id: \$wg_email=\n");
       print_r($wg_ssp_id);
       print("\n</pre>");
   }
   return $wg_ssp_id['ssp_id'];
}


?>
