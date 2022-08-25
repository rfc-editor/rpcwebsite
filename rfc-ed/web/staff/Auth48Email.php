<?php
  /* Copyright The IETF Trust 2020 All Rights Reserved */
  /* $Id: Auth48Email.php,v 2.16 2022/08/16 22:33:36 priyanka Exp $ */
/* November 2020 : Modified the script to use PDO prepared statements - PN                                 */
/* May 2022 : Modified the script to add auth48archive@rfc-editor.org to CC field and modify the subject line for Auth48 Email - PN   */
/* July 2022 : Modified the script to take care of Non Empty DOC_SHEPHERD and IESG_CONTACT values which are causing failure at archieve at mailarchive.ietf.org - PN */
/* August 2022 : Modified the script for Editorial Stream - PN   */

$debug_a48 = false;
#$templateDir = "/nfs/jade/rfc-ed/Templates/AUTH48-Msgs/";
$templateDir = "/home/rfc-ed/Templates/AUTH48-Msgs/";

# Using the passed code for a template, build the full path to 
# the template file.
function getTemplateFilename($template) 
{
     global $templateDir;

     switch ($template) {
     case "nroff":
          $templateFile = $templateDir . "auth48_nroff.txt";
          break;
     case "xml":
          $templateFile = $templateDir . "auth48_xml.txt";
          break;
     default:
          $templateFile = "UNRECOGNIZED TEMPLATE CODE";
          break;
     }
     return $templateFile;
}

/*
 * Reads lines from the file until it matches the "TEXT:" marker. With the 
 * file pointer after that line, the rest of the file is return as a string
 * for addtional, in-memory, processing.
 */
function extract_msg_template($handle) {
     global $debug_a48;
     if ($handle) {
          if ($debug_a48) print("<!--\n");
          while (!feof($handle)) {
               $line = fgets($handle, 120);
               if ($debug_a48 === true)  print $line;
               if (preg_match("/^TEXT:/",$line) == 1) {
                    break;
               }
          }
          if ($debug_a48) print("\n-->\n");
          $text = stream_get_contents($handle);
          return $text;
     } 
     return null;
}

# Query the database for working group data
function getWorkingGroupRow($workGroup) {
   global $debug_a48;
   global $pdo;
 
   $wgQuery = 
       "SELECT WG_ACRONYM, WG_CHAIR_NAME, AREA_DIRECTOR_NAME,ssp_id  
       FROM `working_group`, `area`
       WHERE `WG_NAME` = :workGroup 
       AND area.AREA_NAME=working_group.AREA_NAME";

   try {
       $stmt = $pdo->prepare($wgQuery);
       $stmt->bindParam('workGroup',$workGroup);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   }catch (PDOException $pe){
       error_log("Error processing : getWorkingGroupRow", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
 
   $wgLine = $stmt->fetch(PDO::FETCH_ASSOC);

   if ($debug_a48 === true) {
       print("<h5>Selecting from WORKING_GROUP, AREA Tables</h5>");
       print("<pre>");
       print("\$wgQuery = "); var_dump($wgQuery); print("<br>");
       print("\$wgLine  = "); var_dump($wgLine); print("<br>");
       print("</pre>");
   }
   if ($wgLine === false) {
       print("<p class=\"error\">Failed to retreive WORKING_GROUP data. SQL query was \"$wgQuery\".</p>");
       return null;
   } else {
       return $wgLine;
   }
}

function debug_auth48_dump() {
     global $headers, $to, $cc, $subject, $body, $db_status;

     print("<p><pre>");
     print("\$db_status: "); var_dump($db_status);
     print("\n\$headers: \n"); var_dump($headers);
     print("</pre></p>");
     print("<p>");
     print("\$to      = "); var_dump($to);      print("<br />");
     print("\$cc      = "); var_dump($cc);      print("<br />");
     print("\$subject = "); var_dump($subject); print("<br />");
     print("</p>");
     print("<p><pre>");
     print("\$b  = "); var_dump($body);
     print("</pre></p>");
}

?>
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>AUTH48 EMail</title>
        <meta name="robots" content="noindex, nofollow">
        <meta name="robots" content="noarchive">
        <link rel="stylesheet" type="text/css" href="./style/main.css">
        <script type="text/javascript">
                function submitForm()
                {
                        document.mailForm.action="Auth48Email.php";
                        document.mailForm.flag1.value="1";
                        document.mailForm.submit();
                        
                }
        </script>
</head>
<body bgcolor="#ffffff">
<hr>
<a href="index.php"><img class="header" src="./RFCedlogo.jpg" alt="RFC ED header" border="0" width="220"/></a>
<hr>

<?php
$flag1 = $_POST["flag1"];
$draftName = trim($_POST["draftName"]);
$template = trim($_POST['template']);

if ($debug_a48 === true) {
     print("\$_POST<br><pre>");
     print_r($_POST);
     print_r($draftName);
     print_r($template);
     print_r($flag1);
     print("</pre>");
}

include("db_connect.php");
include("editor_lib.php");
include_once("ams_util_lib.php");
include_once("display_adjust.php");

if ($flag1 <> "1")
{
   echo "<h3 class=\"simple_center\"> Auth48 EMail </h3>";

   global $pdo;
   $query = "SELECT * FROM `index` WHERE `draft`= :draftName";
   try {
       $stmt = $pdo->prepare($query);
       $stmt->bindParam('draftName',$draftName);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   }catch (PDOException $pe){
       error_log("Error processing : Auth48 email", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
 
   $record = $stmt->fetch(PDO::FETCH_ASSOC);
 
   /*Check if the draft has DOC-ID or not*/
   if (($record['DOC-ID'] != "") AND ($record['DOC-ID'] != 'RFC')) {
       if ($debug_a48 === true) {
           print("<h4>Database Select Phase</h4>");
           print("<pre>");
           print("\$query = "); var_dump($query); print("<br>");
           print("\$record  = "); var_dump($record);  print("<br>");
           print("</pre>");
       }

  /* If the working group is IAB, emails of all state changes are 
  sent  to iab@ietf.org.

  If the working group is IESG, emails of all state changes are sent 
  to iesg@ietf.org. 

  If the working group is IRTF, emails of all state changes are sent 
  to irsg@irtf.org. (Updated 4/13/11.)

  If the working group is IETF-NON WORKING GROUP, INDEPENDENT, or
  RFC-EDITOR, the AREA_DIRECTOR_NAMES and WG_CHAIR_NAMES will be NULL. */
       $cc_ads = null;
       $cc_chairs = null;
       switch ($record['SOURCE']) {
           case "IAB":
               $cc_chairs= "iab@ietf.org";
               break;
           case "IESG":
               $cc_chairs= "iesg@ietf.org";
               break;
           case "IRTF":
               $cc_chairs= "irsg@irtf.org";
               break;
           case "INDEPENDENT":
               $cc_chairs= "rfc-ise@rfc-editor.org";
               break;
           case "Editorial":
               $cc_chairs='rsab@rfc-editor.org';
               break;
           case "IETF - NON WORKING GROUP":
           case "rfc-editor":
               break; // leave $cc_ads and $cc_chairs null
           default:
               $wgLine = getWorkingGroupRow($record['SOURCE']);
               $ssp_id = $wgLine['ssp_id'];
               if ($ssp_id != 4){ 
	           $wgname = $wgLine['WG_ACRONYM'];
        	   $adnames =$wgLine['AREA_DIRECTOR_NAME'];
	           $wgchairs = $wgLine['WG_CHAIR_NAME'];
             /*<wgname>-ads@ietf.org is the email addresses for area
               directors of the working group acronym <$wgname> */
        	   $cc_ads = $wgname."-ads@ietf.org";
	           $cc_chairs = $wgname."-chairs@ietf.org";
               } else {/*If ssp_id = 4 ie group is from IRTF streamIf ssp_id = 4 ie group is from IRTF stream  */
		   $cc_chairs= "irsg@irtf.org";
	       }
               break;
       }
       if ($debug_a48 === true) {
           print("<h4>Assignments of CC</h4>");
           print("<pre>");
           print("\$cc_ads     = "); var_dump($cc_ads); print("<br>");
           print("\$cc_chairs  = "); var_dump($cc_chairs); print("<br>");
           print("\$wgLine     = "); var_dump($wgLine); print("<br>");
           print("\$wgname     = "); var_dump($wgname); print("<br>");
           print("</pre>");
       }

  /*Deepa : added formatting details to this script so that only 2 authors,
 2 email addresses are displayed in each line. Also formatted the title so that it appears with correct indentation - 20 spaces.*/

       $title = $record['TITLE'];
       $words = preg_split("/[\s]+/", $record['TITLE'], -1, PREG_SPLIT_NO_EMPTY);
       $i=1;$title1='';
       foreach($words as $word) {
           if(($i % 5) == 0){
               $title1=$title1."\n                         ".$word." ";
           } else {
               $title1=$title1.$word." ";
           }
           $i++;
       }
       $authors = $record['AUTHORS'];
       $today = date("Y/m/d");       
       $draft_name_txt = strtolower($record['DRAFT']) . ".txt";
       $pe = get_primary_editor($record['internal_key']);
       $sub="AUTH48: RFC-to-be ". substr($record['DOC-ID'],3) . " <".$record['DRAFT']."> for your review";       
 
       $Auth48Template = getTemplateFilename($template);
       $Auth48File = fopen($Auth48Template, 'r');
       if ($Auth48File === false) {
           print('<p class="error">Failed to open template file "' . $Auth48Template. '". ');
           $errnum = posix_get_last_error();
           print("Error was ($errnum) " . posix_strerror($errnum) . ".");
           exit; // stop processing now
       }
       $body = extract_msg_template($Auth48File); // Load template text into a string.
       fclose($Auth48File);
       if ($debug_a48) {
           print("<h3>Body Before Substitutions</h3>\n<pre>$body</pre><br />\n");
       }
       //Do the substitutions
       $body = preg_replace('/CURRDATE/', $today, $body);
       $body = preg_replace('/LCXXXX/', strtolower($record['DOC-ID']), $body);
       $body = preg_replace('/RFCXXXX/', $record['DOC-ID'], $body);
       $body = preg_replace('/RFCTITLE/', $record['TITLE'], $body);
       $body = preg_replace('/RFCDRAFT/', $record['DRAFT'], $body);
       $body = preg_replace('/RFCAUTHORS/', $record['AUTHORS'], $body);
       $body = preg_replace('/WGCHAIRNAMES/', $wgchairs, $body);
       $body = preg_replace('/ADNAMES/', $adnames, $body);

       $to = $record['EMAIL'];
       $cc = "rfc-editor@rfc-editor.org";
       if ($cc_ads) { $cc .= ", " . $cc_ads; } 
       if  ($cc_chairs) { $cc .= ", " . $cc_chairs; }
       $record['DOC_SHEPHERD'] = trim($record['DOC_SHEPHERD']);
       if ($record['DOC_SHEPHERD'] != NULL || $record['DOC_SHEPHERD'] != '') {
           $cc .= ", " . $record['DOC_SHEPHERD'];
       }
       $record['IESG_CONTACT'] = trim($record['IESG_CONTACT']);
       if ($record['IESG_CONTACT'] != NULL || $record['IESG_CONTACT'] != '') {

           $cc .= ", " . $record['IESG_CONTACT'];
       }
       //Add auth48archive@rfc-editor.org to CC 
       $cc .= ", auth48archive@rfc-editor.org";

       if ($debug_a48) {
           print("<h3>Body After Substitutions</h3>\n<pre>$body</pre><br />\n");
       }

       ?>

       <form name="mailForm" action="Auth48Email.php" method="post" enctype="multipart/form-data">
       <table  bgcolor="#669966" align="center" border=1>
          <tr>
              <td align="right">
                  <b>To:</b>
              </td>
              <td>
                  <input type="text" name="to" value="<?php echo $to;?>" size="100" >
              </td>
          </tr>
          <tr>
              <td align="right">
                  <b>Cc:</b>
              </td>
              <td>
                  <input type="text" name="cc" value="<?php echo $cc; ?>" size="100" >
              </td>
          </tr>
          <tr>  
              <td align="right">
                  <b>Subject:</b>
              </td>
              <td>
                  <input type="text" name="subject" value="<?php echo $sub;?>" size="100">
              </td>
          </tr>
          <tr>
              <td colspan="2">&nbsp;</td>
          </tr>
          <tr align="center">
              <td colspan = 2>
                   <textarea name="body" rows="25" cols="75"><?php echo $body; ?></textarea>
          </tr>
          <tr>
              <td colspan="2">
                   <input type="hidden" name="flag1" value="">
<?php
      print <<<END
                   <input type="hidden" name="docid" value="{$record['DOC-ID']}">
                   <input type="hidden" name="authors" value="{$record['AUTHORS']}">

END;
?>
                   <input type="button" value="Send Auth48 EMail" onClick="javascript:submitForm();" >
              </td>
          </tr>
        </table>
        </form>

<?php

 } else 
    {/*If the record does not have the document id */
?>
        <table  bgcolor="#669966" align="center" border=0>
          <tr align="left">
             <td colspan = 2>
		<span class="warning">Warning : DOC-ID is missing for draft </span>
             </td>
          </tr> 
          <tr align="left">
             <td colspan = 2>
		 Cannot create Auth48Email.Please edit the draft to add the DOC-ID.
             </td>
          </tr>
          <tr align="left">
             <td colspan = 2>
               <a href="/staff/edit_draft.php?draft=<?php print $record['DRAFT']; ?>"><?php print $record['DRAFT'] ;?></a>
             </td>
          </tr>

<?php

   }/*End else clause for empty doc-id*/
}
else //Form has been Submitted
{
     include("auth48_lib.php");

     $to = $_POST["to"];
     $cc = $_POST["cc"];
     $subject = $_POST["subject"];
     $body = $_POST["body"];
     $authors = $_POST['authors'];
     $docid = $_POST['docid'];

     if ($debug_a48 === true) {
          print("<h4>Post Submit</h4>");
          print("<p>");
          print("\$docid = "); var_dump($docid); print("<br>");
          print("\$authors = "); var_dump($authors); print("<br>");
          print("\$to = "); var_dump($to); print("<br>");
          print("\$cc = "); var_dump($cc); print("<br>");
          print("\$subject= "); var_dump($subject); print("<br>");
          print("\$body = "); var_dump($body); print("<br>");
          print("</p>");
     }

  $headers = "From: rfc-editor@rfc-editor.org\n";
  if ($cc != NULL) {
       $headers .= "Cc: ".$cc."\n";
  }
  // For reasons that are unclear (lost in the innards of SMTP mail
  // transport  agents) some MTAs transform the body to
  // double-spaced if there's a CR in the data.
#  $b=preg_replace("/\r/","",stripslashes($body));
  $b=preg_replace("/\r/","",($body));

  $mail_sent = ams_mail($to,$subject,$b,$headers);

  if ($mail_sent === true) {
       $db_status = initialize_auth48($docid, $authors);
  }

  if ($mail_sent === false || $db_status === false || $debug_a48 === true) {
       if ( $debug_a48 === true) { 
            print("<h3>Variable Last Status</h3><p>Dump of variables follows</p>");
            debug_auth48_dump();
       } elseif ($mail_sent === false) {  // Report the error and dump key variables
            print("<h3>EMail Error</h3><p class='error'>Error sending email to $to. Dump of variables follows</p>");
            debug_auth48_dump();
       } elseif ($db_status === false) {
            print("<h3>Database Error</h3><p class='error'>Initialization of AUTH48 database tables failed!</p>");
       }
   } else {                // if no errors
       print("<script type=\"text/javascript\">\n"); // close the window
       print("<!-- \n");
       print("window.close();\n");
       print("-->\n");
       print("</script>\n");
  }
}
?>
</body>
</html>
