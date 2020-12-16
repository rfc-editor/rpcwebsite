<?php
  /* Copyright The IETF Trust 2020 All Rights Reserved                 */
  /* $Id: errata_mail_lib.php,v 1.6 2020/06/17 22:51:38 priyanka Exp $ */
  /* May 2017 Updates : Removed rfcid from function generate_rfc_errata_search_query - PN*/ 
  /* June 2020 Updates : Added check for 'None' in the email address to avoild sending email to None - PN*/
// Set dev_mode 
include_once("ams_util_lib.php");
include("config.php");

$dev_mode = get_dev_mode();

define("REPORT_MSG",1);
define("REJECT_MSG",2);
define("VERIFY_MSG",3);
define("DEFER_MSG", 4);

$debug_mlib = false;

$dev_address = 'arusso@amsl.com, priyanka@amsl.com';
#$dev_address = 'priyanka@amsl.com';


$ack_subj   = "[ERRATA_TYPE Errata Reported] RFCXXXX (REC_KEY)";
$rej_subj   = "[Errata Rejected] RFCXXXX (REC_KEY)";
$ver_subj   = "[Errata Verified] RFCXXXX (REC_KEY)";
$defer_subj = "[Errata Held for Document Update] RFCXXXX (REC_KEY)";

$is_superuser=false;


if (array_key_exists('super_user',$_POST)) {
     $is_superuser = true;
} elseif (isset($_SESSION['super_user'])) {
     if ($_SESSION['super_user'] == 'yes') {
          $is_superuser = true;
          unset($_SESSION['super_user']);
     }
}


/*
 * Generate a report acknowledgement.
 */
function generate_report_ack($form_data) {
     global $ack_subj, $ack_template;
     $form_data['errata_id'] = get_most_recent_errata_id($form_data['doc-id']);
     return generate_message($form_data,$ack_subj, $ack_template, REPORT_MSG);
}

/*
 * Generate a rejection message
 */
function generate_reject_msg($form_data) {
     global $rej_subj, $rej_template;
     return generate_message($form_data,$rej_subj, $rej_template, REJECT_MSG);
}

/*
 * Generate a verification message
 */
function generate_verify_msg($form_data) {
     global $ver_subj, $ver_template;
     return generate_message($form_data,$ver_subj, $ver_template, VERIFY_MSG);
}

/*
 * Generate a deferred notice message.
 */
function generate_hold_msg($form_data) {
     global $defer_subj, $defer_template;
     return generate_message($form_data, $defer_subj, $defer_template, DEFER_MSG);
}

/*
 * Use static and dynamic data to generate an email about an errata report.
 */
function generate_message($form_data, $subj_template, $msg_template, $style) {
     global $debug_mlib, $dev_mode, $dev_address;
     global $is_superuser;
     $db_data = get_extra_email_data($form_data['doc-id']);
     if (is_string($db_data)) {
          // Error return from get_extra_email_data!
          return $db_data;
     }
     // Load the template for the message body.
     $tfile = fopen($msg_template,"r");
     if ($tfile == false) {
          $emsg = "Failed to open template file " . $msg_template . "\n";
          $errnum = posix_get_last_error();
          $emsg .= "Error was ($errnum) " . posix_strerror($errnum) . ".";
          return $emsg;
     }
     $body  = extract_msg_template($tfile);
     if ($body == null) {
          return "Malformed template \"$msg_template.\" No template text found.";
     }
     fclose($tfile);

     if ($debug_mlib === true) {
          print("<b>\$db_data</b>\n<pre>\n");
          print_r($db_data);
          print("\n</pre>\n");
     }

     $to = null;
     $headers = "From: RFC Errata System <rfc-editor@rfc-editor.org>\n";

     $subject = preg_replace('/RFCXXXX/',$form_data['doc-id'], $subj_template);

     // Substitute for markers in the template
     if ($form_data['section'] != null && 
         (strpos($form_data['section'], "99")) === false) {
          $body = preg_replace('/RFCSECTION/', $form_data['section'], $body);
     } else {
          switch(strlen(trim($form_data['section']))) {
          case 0:               // a null section entry
          case 2:               // a bare 99 so remove element
               $body = preg_replace('/RFCSECTION/', null, $body);
               $body = preg_replace('/Section\: \n/', null, $body);
               break;
          default:              // use text after the 99 marker
                                // without the "Section:" header
               $body = preg_replace('/Section\: /', null, $body);
               $body = preg_replace('/RFCSECTION/', 
                                    substr($form_data['section'],2),
                                    $body);
               break;
          }
     }
     $body = preg_replace('/RFCXXXX/',$form_data['doc-id'], $body);
     $body = preg_replace('/RFCTITLE/',trim($form_data['title']), $body);
     if ($db_data['draft'] != null && (strlen(trim($db_data['draft']))) > 0) {
          $body = preg_replace('/RFCDRAFT/',$db_data['draft'], $body);
     } else {
          $body = preg_replace('/RFCDRAFT/','no draft string recorded', $body);
     }
     $body = preg_replace('/SUBMITTER_NAME/', $form_data['submitter_name'], $body);
     $body = preg_replace('/SUBMITTER_EMAIL/', $form_data['submitter_email'], $body);
     $body = preg_replace('/ERRATA_TYPE/', $form_data['errata_type_code'], $body);
     $body = preg_replace('/RFCORIGINAL_TEXT/', $form_data['orig_text'], $body);
     $body = preg_replace('/RFCCORRECTED_TEXT/', $form_data['correct_text'], $body);
     $body = preg_replace('/RFCNOTES_TEXT/', $form_data['notes'], $body);
     $body = preg_replace('/RFCAUTHORS/', trim($db_data['authors']), $body);
     $body = preg_replace('/RFCSTATUS/', $db_data['pub-status'], $body);
     $body = preg_replace('/RFCPUBDATE/', format_date($form_data['pub-date']), $body);
     $body = preg_replace('/RFCSOURCE/', $db_data['source'], $body);
     $body = preg_replace('/RFCAREA/', $db_data['area_name'], $body);
     $body = preg_replace('/RFCSTREAM/', $db_data['stream_name'], $body);
    

     if ($form_data['verifier_name']){ 
        if ($form_data['verifier_name'] == 'RFC Editor'){
           $body = preg_replace('/RFCSSP/',' ', $body);
        }else{
            $body = preg_replace('/RFCSSP/', '('.$db_data['ssp_name'].')', $body);
        }   
     } else {
          $body = preg_replace('/RFCSSP/',' ', $body);
     }

     switch($style) {
     case REJECT_MSG:
     case VERIFY_MSG:
     case DEFER_MSG:
          $subject = preg_replace('/REC_KEY/',$form_data['errata_id'], $subject);
          $body = preg_replace('/VERIFIER_NAME/', $form_data['verifier_name'], $body);
          $body = preg_replace('/SUBMIT_DATE/', $form_data['submit_date'], $body);
          $body = preg_replace('/ERRATA_STATUS/', $form_data['errata_status_code'], $body);

          $use_wg_email = true;
          // For verification and rejection messages, make To & Cc like this:
          // To: SUBMITTER, AUTHORS
          // Cc: VERIFIER, SSP (, and RFC-EDITOR)
          $to = $form_data['submitter_email'];
          if ($db_data['email'] != null && (strlen(trim($db_data['email']))) > 0) {
               //Checking for 'None' email address and removing it.
               $email_array = array_map('trim',explode(',',$db_data['email']));
               $out_email_string = '';
               $email_count = count($email_array);
               if (in_array("none",$email_array)){
                   $email_without_none = array_diff($email_array,array('none'));
                   print_r($email_without_none);
                   $out_email_string = implode(',',$email_without_none);
                }
                if ($out_email_string){
                   $to .= ", " . $out_email_string;
                } else {
                   $to .= ", " . $db_data['email'];
                }
          }
          $headers .= "Cc: " . $form_data['verifier_email'] . ", " . $db_data['ssp_email']; // Add Cc
          // Add the working group list address if recorded and WG "open"
          if ($use_wg_email && $db_data['wg_email'] != null && $db_data['wg_status'] == 'open') {
               $headers .= ", " . $db_data['wg_email'];
          }
          break;
     case REPORT_MSG:
          // This only changes REPORT acks; others do not have the TYPE template
          $subject = preg_replace('/ERRATA_TYPE/',$form_data['errata_type_code'], $subject);
          $subject = preg_replace('/REC_KEY/',$form_data['errata_id'], $subject);
          $additional_to = null;
          $use_wg_email = true;
          switch ($db_data['area_director_email']) {
          case 'rfc-editor@rfc-editor.org':
               $additional_to = $db_data['ssp_email']; // Legacy and N/A areas
               $use_wg_email = false;
               break;
          default:
               $additional_to = trim($db_data['area_director_email']); // Area Directors Only
               if ($db_data['wg_chair_email'] != null) {
                    $additional_to .= ", " . $db_data['wg_chair_email'];
               }
               break;
          }
          if ($db_data['email'] != null && (strlen(trim($db_data['email']))) > 0) {
               //Checking for 'None' email address and removing it.
               $email_array = array_map('trim',explode(',',$db_data['email']));
               $out_email_string = '';
               $email_count = count($email_array);
               if (in_array("none", $email_array)){
                   $email_without_none = array_diff($email_array,array('none'));
                   $out_email_string = implode(',',$email_without_none);
                }
                if ($out_email_string){
                   $to = $out_email_string;
                }else {
                   $to = trim($db_data['email']); // Authors
               }
               $to .= ", " . $additional_to;
          } else {
               $to  = $additional_to; // SSP
          }
          // For report acknowledgments, make To & Cc like this:
          // To: AUTHORS, SSP or ADs
          // Cc: SUBMITTER (, and RFC-EDITOR)
          $headers .=  "Cc: " . $form_data['submitter_email']; // Start Cc: of header
          // Add the working group list address if recorded and WG "open"
          if ($use_wg_email && $db_data['wg_email'] != null && $db_data['wg_status'] == 'open') {
               $headers .= ", " . $db_data['wg_email'];
          }
          break;
     default:
          error_log("generate_msg() called with unknown style: " . $style);
          break;
     }
     // Tag on the RFC Editor address if the SSP isn't the RFC Editor
     if ((strcasecmp($db_data['ssp_email'], "rfc-editor@rfc-editor.org")) != 0) {
          $headers .= ", rfc-editor@rfc-editor.org";
     }
     $headers .= "\n"; // Always terminate line with a newline
     $headers .= "Content-Type: text/plain; charset=UTF-8";
     $headers .= "\n"; // Always terminate line with a newline

     $body = preg_replace('/QUERY_URL/', generate_rfc_errata_search_query($form_data['errata_id']),$body);

     if (!($is_superuser)){
        $body .= 'Verifying Party     : '.$db_data['ssp_name'];
     }

     if ($debug_mlib === true) {
          print("<p><pre>");
          print("\$headers: \n"); var_dump($headers);
          print("</pre></p>");
          print("<p>");
          print("\$to      = "); var_dump($to);      print("<br />");
          print("\$subject = "); var_dump($subject); print("<br />");
          print("</p>");
          print("<p><pre>");
          print("\$body  = "); var_dump($body);
          print("</pre></p>");
     }
     
     if ($dev_mode) {
          // ** begin development test only **//
          $body .= "\n--------\nThis is how the production header would look:\n";
          $body .= "To: " . $to . "\n";
          $body .= "Subject: " . $subject . "\n";
          $body .= $headers;
          // ** development test only **//

          $tmpname = tempnam("/tmp","errata_mail_");
          $fio = fopen($tmpname,"w");
          if ($fio === false) {
               print("<p>Failed to open $tmpname.</p>\n");
          } else {
               print("<p>Writing mail file to $tmpname.</p>\n");
               fwrite($fio, "To: " . $to . "\n");
               fwrite($fio, "Subject: " . $subject . "\n");
               fwrite($fio, $headers . "\n\n");
               fwrite($fio, $body);
               fclose($fio);
               chmod($tmpname, 0644);
          }

          $dev_hdr = "From: RFC Errata System <rfc-editor@rfc-editor.org>\n";
          $dev_hdr .= "Content-Type: text/plain; charset=UTF-8\n";
          $to      = $dev_address; // overwrite generated string
     }

     if ($dev_mode) {
          $mail_sent = mail($to,$subject,$body,$dev_hdr);
     } else {
          $mail_sent = mail($to,$subject,$body,$headers);
     }

     if ($mail_sent == false) {
          error_log("generate_report_ack: failed to send email: " .
                    "To: $to Subject: $subject Headers: $headers");
          return false;
     }
     return true;
}

/*
 * Reads lines from the file until it matches the "TEXT:" marker. With the 
 * file pointer after that line, the rest of the file is return as a string
 * for addtional, in-memory, processing.
 */
function extract_msg_template($handle) {
     global $debug_mlib;

     if ($handle) {
          while (!feof($handle)) {
               $line = fgets($handle, 120);
               if ($debug_mlib === true)  print $line;
               if (preg_match("/^TEXT:/",$line) == 1) {
                    break;
               }
          }
          $text = stream_get_contents($handle);
          return $text;
     } 
     return null;
}

?>
