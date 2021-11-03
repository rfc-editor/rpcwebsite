<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
# $Id: sendEMail.php,v 2.9 2021/06/23 05:11:48 priyanka Exp $
/* November 2020 : Modified the script to use PDO prepared statements - PN            */
/* June 2021 : Modified the script for server upgrade - PN                            */
  $debug = false;
?>
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Send EMail</title>
	<meta name="robots" content="noindex, nofollow">
	<meta name="robots" content="noarchive">
        <link rel="stylesheet" type="text/css" href="./style/main.css">
	<script type="text/javascript">
		function submitForm()
		{
			document.mailForm.action="sendEMail.php";
			document.mailForm.flag1.value="1";
                        // Don't know (yet) why att doesn't get into the POST.
                        document.mailForm.filepath.value = document.mailForm.att.value;
			document.mailForm.submit();
			
		}
	</script>
</head>
<body bgcolor="#ffffff">
<hr>
<a href="index.php"><img class="header" src="./RFCedlogo.jpg" alt="RFC ED header" border="0" width="220"/></a>
<hr>
<?php
include("db_connect.php");
include_once("ams_util_lib.php");
include_once("display_adjust.php");

$flag1 = $_POST["flag1"];
$draftName = $_POST["draftName"];
if ($flag1 <> "1")
{
	echo "<h3 align=\"center\"> Send EMail </h3>";

        global $pdo;
	/* Performing SQL query */
	$query = "SELECT * FROM `index` where `DRAFT`=:draftName";

        try {
            $stmt = $pdo->prepare($query);
            $stmt->bindParam('draftName',$draftName);
            $stmt->execute();
            $num_of_rows = $stmt->rowCount();
       } catch (PDOException $pe){
            error_log("Error processing : sendEMail.php", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
       }

	$line = $stmt->fetch(PDO::FETCH_ASSOC);

	$wgQuery = "SELECT WG_CHAIR_NAME, WG_CHAIR_EMAIL, AREA_DIRECTOR_NAME, AREA_DIRECTOR_EMAIL FROM `working_group`, `area` where `WG_NAME` = :source and area.AREA_NAME=working_group.AREA_NAME";
        try {
            $stmt = $pdo->prepare($wgQuery);
            $source = $line['SOURCE'];
            $stmt->bindParam('source',$source);
            $stmt->execute();
            $wg_rows = $stmt->rowCount();
       } catch (PDOException $pe){
            error_log("Error processing : sendEMail.php WG query", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
       }

	$wgLine = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($wgLine === false) {
             print("<p class=\"error\">Failed to retreive working_group data. SQL query was \"$wgQuery\".</p>");
        }

        if ($debug === true) {
             print_r($query);  print("<br />");
             print_r($wgQuery);  print("<br />");
             print_r($line);   print("<br />");
             print_r($wgLine); print("<br />");
        }
        
   $title = $line['TITLE'];
   #$words = preg_split('/[^a-zA-Z\'"-]+/', $line['TITLE'], -1, PREG_SPLIT_NO_EMPTY);
   $words = preg_split("/[\s]+/", $line['TITLE'], -1, PREG_SPLIT_NO_EMPTY);

   $i=1;$title1='';
   foreach($words as $word) {
     if(($i % 6) == 0)
       {
	 $title1=$title1."\n                         ".$word." ";
       }
     else
       {
	 $title1=$title1.$word." ";
       }
      $i++;
      
   }

 $author = $line['AUTHORS'];
   list($e1, $e2, $e3, $e4, $e5,$e6, $e7, $e8, $e9, $e10) =
   preg_split("/\,/", $author, 10);
   if($e2 == '')
   {
     $author1=$e1;
   }
   else
   {
     $author1=$e1.", ".$e2;
   }
   $e=$author1;
   if($e3 != '' || $e4 != '')
   {
     if($e4 == '')
       {
	 $author2=", \n                        ".$e3;
       }
     else
       {
	 $author2=", \n                        ".$e3.", ".$e4;
       }
     $e=$e.$author2;
   }
   if($e5 != '' || $e6 != '')
   {
     if($e6 == '')
     {
       $author3=", \n                        ".$e5;
     }
     else
     {
       $author3=", \n                        ".$e5.", ".$e6;
     }
     $e=$e.$author3;
   }
   if($e7 != '' || $e8 != '')
   {
     if($e8 == '')
     {
       $author4=", \n                        ".$e7;
     }
     else
     {
       $author4=", \n                        ".$e7.", ".$e8;
     }
     $e=$e.$author4;
   }
   if($e9 != '' || $e10 != '')
   {
     if($e10 == '')
     {
       $author5=", \n                        ".$e9;
     }
     else
     {
       $author5=", \n                        ".$e9.", ".$e10;
     }
    
     $e=$e.$author5;
   }

        // build short variables
	$wg = $wgLine['WG_CHAIR_NAME'];
	$wg = str_replace("\n","",$wg);
        $wg_chair_email = $wgLine['WG_CHAIR_EMAIL'];
        $ad_email = $wgLine['AREA_DIRECTOR_EMAIL'];
        $doc_shepherd = $line['DOC_SHEPHERD'];
        $iesg_contact = $line['IESG_CONTACT'];
	$area=$wgLine['AREA_DIRECTOR_NAME'];
        $area=str_replace("\n","",$area);
	$rfcstr = str_replace($line["TYPE"], "", $line["DOC-ID"]);
	$idstring = "blah";

	//idstring="<`date +%y%m%d%H%M%S`.RFC@RFC-EDITOR.ORG>"

        // build cc list
        $cclist = "rfc-editor@rfc-editor.org";

        //Add rfc-ise@rfc-editor.org to cc list
	if ($line['SOURCE'] == 'INDEPENDENT'){
	        $cclist .= ", rfc-ise@rfc-editor.org"; 
	}

        if (!empty($wg_chair_email)) {
            $cclist .= ", $wg_chair_email";
        }
        if (!empty($ad_email)) {
	    if ($ad_email != 'rfc-editor@rfc-editor.org'){
	            $cclist .= ", $ad_email";
	    }	
        }
        if (!empty($doc_shepherd)) {
            $cclist .= ", $doc_shepherd";
        }
        if (!empty($iesg_contact)) {
            $cclist .= ", $iesg_contact";
        }

        $body = ""; 
        $body .=  "\n";
        $body .=  "\n";
        $body .=  "Title                  : ".$line['TITLE']."\n";
        $body .=  "Author(s)              : ".$e."\n";
        $body .=  "Working Group Chair(s) : ".$wg."\n";
        $body .=  "Area Director(s)       : ".$area."\n";
        ?>


             <form name="mailForm" action="sendEMail.php" method="post" enctype="multipart/form-data">
              <table  bgcolor="#669966" align="center" border=1>
              <tr>
                    <td align="right">
                       <b>To:</b></td><td><input type="text" name="to" value="<?php echo $line['EMAIL'];?>" size="100" >
                    </td>
              </tr>
              <tr>
                    <td align="right"><b>Cc:</b></td>
                    <td><input type="text" name="cc" value="<?php echo $cclist; ?>" size="100" >
                    </td>
              </tr>
              <tr>	
              <td align="right"><b>Subject:</b></td>
                    <td><input type="text" name="subject" value="<?php echo $line['DRAFT'].".txt";?>" size="100"></td>
              </tr>
              <tr>
                    <td align="right">
                      <b>Attachment:</b>
                    </td>
                    <td>
                       <input type="file" name="att" size="50">
                       <input type="hidden" name="filepath">
                    </td>
              </tr>
              <tr>
                    <td colspan="2">&nbsp;</td>
              </tr>
              <tr align="center">
                    <td colspan = 2>
                       <textarea name="body" rows="25" cols="75"><?php echo $body; ?></textarea>
                    </td>
              </tr>
 	      <tr>
                   <td colspan="2">
                       <input type="button" value="Send EMail" onClick="javascript:submitForm();" >
                       <input type="hidden" name="flag1" value="">
                   </td>
              </tr>
	     </table>
            </form>

	<?php

}
else //Form has been Submitted
{
     $file = NULL;
     $to = $_POST["to"];
     $cc = $_POST["cc"];
     $filepath = $_POST["filepath"];
     $filename = NULL;
     $att_size = 0;
     $subject = $_POST["subject"];
     $body = $_POST["body"];

     if ($debug === true) {
          print("<pre>");
          print_r($_POST);
          print("</pre>");
          print("<h3>Email Fields</h3>");
          print("<p>\$filepath = ");  var_dump($filepath);     print("<br />");
          print("\$to      = "); var_dump($to);      print("<br />");
          print("\$subject = "); var_dump($subject); print("<br />");
          print("\$body    = "); var_dump($body);    print("<br />");
          print("\$file    = "); var_dump($file);    print("<br />");
          print("</p>");
     }

     if($filepath != NULL)
     {
          if ($debug === true) { print("<h5>Reading file $filepath</h5>"); }
          $fp = fopen( $filepath, "r");
          $att_size = filesize($filepath);
          $file = fread( $fp, $att_size );
          $filename = substr($filepath,(strrpos($filepath,"/")+1));
     }

/*
Encode The Data For Transition using base64_encode();
And get a 32-character hexadecimal number 
*/
     if ($file != NULL) {
          if ($debug === true) { print("<h5>chunk_split file $filepath</h5>"); }
          $file = chunk_split(base64_encode($file));
          $num = md5( time() );
     }
/*
Define the main message headers
*/
	// INSERT FROM HERE 
        $hdr = "From: rfc-editor@rfc-editor.org \n";
        $hdr .= "Cc: ".$cc."\n";

        if ($file != NULL) {
             $hdr .= "MIME-Version: 1.0\n";
             $hdr .= "Content-Type: multipart/mixed; ";
             $hdr .= "boundary=$num\n";
             $hdr .= "--$num\n";
/*
Define message section
*/
             $hdr .= "Content-Type: text/plain\n";
             $hdr .= "Content-Transfer-Encoding: 7bit\n\n";
             //$hdr .= "$comments\n";
             $hdr .= "$body\n";
             $hdr .= "--$num\n";
/*
Define the attachment section
*/
             $hdr .= "Content-Type: application/octet-stream; name=$filename\n";
             $hdr .= "Content-Transfer-Encoding: base64\n";
             $hdr .= "Content-Disposition: attachment; ";
             $hdr .= "filename=$filename\n\n";
             $hdr .= "$file\n";
             $hdr .= "--$num--";
        }
/*
Send the email
*/
//        $hdr .= "\n"; // aggressively mark end of header
        if ($debug === true) {
             print("<p><pre>");
             print("\$hdr: \n"); var_dump($hdr);
             print("</pre></p>");
        }
        // For reasons that are unclear (lost in the innards of SMTP mail
        // transport  agents) some MTAs transform the body to
        // double-spaced if there's a CR in the data.
        $nbody = preg_replace("/\r/", "", $body);
        //$mail_sent = mail( $to, $subject, $nbody, preg_replace("/\r/","", $hdr));
        $mail_sent = ams_mail( $to, $subject, $nbody, preg_replace("/\r/","", $hdr));

/*
Close the attachment
*/
	if($file != NULL) {
             fclose( $fp );
        }

        if ($mail_sent === false || $debug === true) { // Report the error and dump key variables
             if ($mail_sent === false) {
                 print("<h3>EMail Error</h3><p>Error sending email to $to. Dump of variables follows</p>");
             } else {
                 print("<h3>Variable Last Status</h3><p>Dump of variables follows</p>");
             }
             print("<p><pre>");
             print("\$hdr: \n"); var_dump($hdr);
             print("</pre></p>");
             print("<p>\$filepath = ");  var_dump($filepath);     print("<br />");
             print("\$to      = "); var_dump($to);      print("<br />");
             print("\$cc      = "); var_dump($cc);      print("<br />");
             print("\$subject = "); var_dump($subject); print("<br />");
             print("</p>");
             print("<p><pre>");
             print("\$body    = "); var_dump($body);
             print("\$nbody   =" ); var_dump($nbody);
             print("</pre></p>");
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
