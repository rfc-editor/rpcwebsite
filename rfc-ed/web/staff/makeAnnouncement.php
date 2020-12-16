<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
# $Id: makeAnnouncement.php,v 2.25 2020/11/11 01:03:17 priyanka Exp $
# 07-10-2012 Added function get_wg_ssp_id to indentify IRTF documents
#            using ssp-id.
/*April 2015 : Update INDEX table for DOI values                   */
/*July 2017  : Updated get_draftName function to handle mysqli_fetch_assoc coorectly*/
/*July 2017  : Updated get_draftName function to handle mysqli_fetch_assoc coorectly*/
/*December 2019  : Removed the character count from the announcement email - PN*/
/* November 2020 : Modified the script to use PDO prepared statements - PN                                 */

include("db_connect.php");
include("format_lib.php");
include("edit_lib.php");
include_once("ams_util_lib.php");
include("doi.php");
include("display_adjust.php");

  $debug = false;
?>

<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Send Announcement</title>
	<meta name="robots" content="noindex, nofollow">
	<meta name="robots" content="noarchive">
        <link rel="stylesheet" type="text/css" href="./style/main.css">
	<script type="text/javascript">
        function submitForm()
	{
	  document.mailForm.action="makeAnnouncement.php";
	  document.mailForm.flag.value="1";
	  document.mailForm.submit();
	}
	</script>
</head>
<body bgcolor="#ffffff">
<hr>
<a href="index.php"><img class="header" src="./RFCedlogo.jpg" alt="RFC ED header" border="0" width="220"/></a>
<hr>
<?php
if ($debug === true) {
     print("\$_POST<br><pre>");
     print_r($_POST);
     print("</pre>");
}

/*Check if RFC Number is present in the POST variable*/
/*That indicates the request is from searchPubForm   */
/*Otherwise the request is from announcemnetForm     */
if (array_key_exists('rfcNum',$_POST)){
      $rfcNum = $_POST['rfcNum'];
      $draftName = get_draftName($rfcNum);
}else {
      $draftName = $_POST["draftName"];
}

$flag = $_POST["flag"];

/*Routine to query database to retrieve the Draft Name for the*/
/*RFC Number. If Draft name is empty then */

function get_draftName($in_rfc){
   global $debug;
   global $pdo;

   $in_rfc = sprintf('RFC%04d',$in_rfc);
   print "";  
   $query =
           "SELECT `DRAFT`
            FROM `index` WHERE `DOC-ID` = :in_rfc";
   try {

       $stmt = $pdo->prepare($query);
       $stmt->bindParam('in_rfc',$in_rfc);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   }catch (PDOException $pe){
       error_log("Error processing : get_draftName", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   if ($debug === true) {
       print("<pre>get_draftName: $query\n</pre>");
   }
     
   while ($row_draft_line = $stmt->fetch(PDO::FETCH_ASSOC)){
       $DRAFT = $row_draft_line['DRAFT'];
   }
   if ($debug === true) {
       print("<pre>get_draftName: $DRAFT");
       print("\n</pre>");
   }
   if (($DRAFT == "") or (is_null($DRAFT))){

       $DRAFT = "No draft string present";
   }
   return $DRAFT;
}


/*
 * Routine to query database to retrieve the list address for the working group
 * that created the document being announced.
 *
 * NOTE: Function assumes that the database is opened and closed by caller.
 */
function get_wg_email($in_value,$in_type) {
   global $debug;
   global $pdo;
 
   if ($in_type == 'D'){
       $query =
           "SELECT w.wg_email
           FROM `index` i, working_group w
           WHERE w.wg_name = i.source AND i.draft= :in_value";
   } else {
       $query =
           "SELECT w.wg_email
           FROM `index` i, working_group w
           WHERE w.wg_name = i.source AND `DOC-ID` = :in_value ";
   }

   if ($debug === true) {
          print("<pre>get_wg_email: $query\n</pre>");
   }
    
   try {
       $stmt = $pdo->prepare($query);
       if ($in_type == 'D'){
           $stmt->bindParam('in_value',$in_value);
       } else {
           $in_value = sprintf('RFC%04d',$in_value);
           $stmt->bindParam('in_value',$in_value);
       }
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : get_wg_email", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   $wg_email = $stmt->fetch(PDO::FETCH_ASSOC);
   if ($debug === true) {
       print("<pre>get_wg_email: \$wg_email=\n");
       print_r($wg_email);
       print("\n</pre>");
   }
   return $wg_email['wg_email'];
}



if ($flag <> "1")
{
   $doi_prefix = $doi_prefix."/";
   global $pdo;  
   //Update DOI value to INDEX table
   
   /* Updating INDEX table with DOI */
    if ($draftName <> 'No draft string present'){
           $update_query = "UPDATE `index` SET `DOI`=CONCAT(:doi_prefix,`doc-id`) WHERE `DRAFT`=:draftName";
    }else {
           $update_query = "UPDATE `index` SET `DOI`=CONCAT(:doi_prefix,`doc-id`) WHERE `DOC-ID`=:doc_id";

    } 

   try {
       $stmt = $pdo->prepare($update_query);
       if ($draftName <> 'No draft string present'){
           $stmt->bindParam('doi_prefix',$doi_prefix);
           $stmt->bindParam('draftName',$draftName);
       } else {
           $stmt->bindParam('doi_prefix',$doi_prefix);
           $doc_id = sprintf('RFC%04d',$rfcNum);
           $stmt->bindParam('doc_id',$doc_id);
       }
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : Update DOI", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   if ($num_of_rows > 0){
       if ($debug === true) {
           print("<h5>Update INDEX for DOI sucess</h5>");
           print("\$update_query = "); var_dump($update_query); print("<br />");
       }
   }



   // Input Variables  = $draftName
   echo "<h3 class=\"simple_center\">Make Announcement</h3>";
   
   /* Performing SQL query */
   if ($draftName <> 'No draft string present'){
	   $query = "SELECT * FROM `index` where `DRAFT`= :draftName";
   }else {
	   $query = "SELECT * FROM `index` WHERE `DOC-ID` = :doc_id";
   }

   try {
       $stmt = $pdo->prepare($query);
       if ($draftName <> 'No draft string present'){
           $stmt->bindParam('draftName',$draftName);
       } else {
           $doc_id = sprintf('RFC%04d',$rfcNum);
           $stmt->bindParam('doc_id',$doc_id);
       }
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : Select Index Make Announcement", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

  
   $line = $stmt->fetch(PDO::FETCH_ASSOC);
   if ($debug === true) {
        print("<h4>Database Select Phase</h4>");
        print("<pre>");
        print("\$query = "); var_dump($query); print("\n");
        print("\$line  = "); var_dump($line);  print("\n");
        print("</pre>");
   }

   $title_t = $line['TITLE'];
   $string_s="'\\''i";
   $replacement="'";
   $title = preg_replace($string_s,$replacement,$title_t);
   $words = preg_split("/[\s]+/", $title_t, -1, PREG_SPLIT_NO_EMPTY);

   $type = "";
   if ($draftName <> 'No draft string present'){
	   $type = "D";
	   $wg_email = get_wg_email($draftName,$type);
   }else {
	   $type = "R";
	   $wg_email = get_wg_email($rfcNum,$type);
	}
   /*Deepa : added formatting details to this script so that only 2 authors,
 2 email addresses are displayed in each line. Also formatted the title so that it appears with correct indentation - 20 spaces.*/

   if ($debug === true) {
        print("<h4>Data Mung</h4>");
        print("<p>");
        print("\$title_t = "); var_dump($title_t); print("<br>");
        print("\$string_s = "); var_dump($string_s); print("<br>");
        print("\$title = "); var_dump($title); print("<br>");
        print("\$words = "); var_dump($words); print("<br>");
        print("</p>");
   }

   $i=1;$title1='';
   foreach($words as $word) {
     if(($i % 6) == 0)
       {
	 $title1=$title1."\n                    ".$word." ";
       }
     else
       {
	 $title1=$title1.$word." ";
       }
      $i++;
    }
   $email = $line['EMAIL'];
   list($e1, $e2, $e3, $e4, $e5,$e6, $e7, $e8, $e9, $e10) =
   split(",\s*", $email, 10);
   if($e2 == '')
   {
     $email1=$e1;
   }
   else
   {
     $email1=$e1.", \n                   ".$e2;
   }
   $e=$email1;
   if($e3 != '' || $e4 != '')
   {
     if($e4 == '')
       {
	 $email2=", \n                   ".$e3;
       }
     else
       {
	 $email2=", \n                   ".$e3.", ".$e4;
       }
     $e=$e.$email2;
   }
   if($e5 != '' || $e6 != '')
   {
     if($e6 == '')
     {
       $email3=", \n                   ".$e5;
     }
     else
     {
       $email3=", \n                   ".$e5.", ".$e6;
     }
     $e=$e.$email3;
   }
   if($e7 != '' || $e8 != '')
   {
     if($e8 == '')
     {
       $email4=", \n                   ".$e7;
     }
     else
     {
       $email4=", \n                   ".$e7.", ".$e8;
     }
     $e=$e.$email4;
   }
   if($e9 != '' || $e10 != '')
   {
     if($e10 == '')
     {
       $email5=", \n                   ".$e9;
     }
     else
     {
       $email5=", \n                   ".$e9.", ".$e10;
     }
     $e=$e.$email5;
   }

   $author = $line['AUTHORS'];
   list($f1,$f2,$f3,$f4,$f5,$f6,$f7,$f8,$f9,$f10) =
   split(",\s*", $author, 10);
   if($f2 == '')
   {
     $author1=$f1;
   }
   else
   {
     $author1=$f1.",".$f2;
   }
   $f=$author1;
   if($f3 != '' || $f4 != '')
   {
     if($f4 == '')
       {
	 $author2=",\n                   ".$f3;
       }
     else
       {
	 $author2=",\n                   ".$f3.",".$f4;
       }
     $f=$f.$author2;
   }
   if($f5 != '' || $f6 != '')
   {
     if($f6 == '')
     {
       $author3=",\n                     ".$f5;
     }
     else
     {
       $author3=",\n                     ".$f5.",".$f6;
     }
     $f=$f.$author3;
   }
   if($f7 != '' || $f8 != '')
   {
     if($f8 == '')
     {
       $author4=",\n                     ".$f7;
     }
     else
     {
       $author4=",\n                     ".$f7.",".$f8;
     }
     $f=$f.$author4;
   }
   if($f9 != '' || $f10 != '')
   {
     if($f10 == '')
     {
       $author5=",\n                      ".$f9;
     }
     else
     {
       $author5=",\n                      ".$f9.",".$f10;
     }
    
     $f=$f.$author5;
   }

   $source=$line['SOURCE'];
  
   $ssp_id = get_wg_ssp_id($draftName); 

   if ($ssp_id == 4){
   	  if($source == "IRTF")
   	  {
	     $group="This document is a product of the IRTF.";
   	  }else {
             $group="This document is a product of the ".$line['SOURCE']." of the IRTF.";
   	  }

   }else{
   	if($source == "" || $source == "NULL")
   	{
     	  $group="";
        }
   	else if ($source == "IAB")
   	{
     	  $group = "This document is a product of the Internet Architecture Board.";
   	}
   	else if($source == "IESG")
   	{
     	  $group="This document is a product of the Internet Engineering Steering Group.";
    	}
   	else if($source == "IRTF")
   	{
          $group="This document is a product of the IRTF.";
   	}
   	else if($source == "IETF - NON WORKING GROUP")
   	{
     	  $group="";
   	}
   	else if($source == "INDEPENDENT")
   	{
     	  $group="";
   	}
   	else
   	{
     	  $group="This document is a product of the ".$line['SOURCE']." Working Group of the IETF.";
    	}
   }
  
   $std=$line['PUB-STATUS'];
   if($std == "PROPOSED STANDARD" || $std == "INTERNET STANDARD" || $std == "DRAFT STANDARD" )
   {
     if($std == "PROPOSED STANDARD")
       {
	 $ss="a Proposed Standard";
       }
     else if ($std == "INTERNET STANDARD")
       {
	 $ss="an Internet Standard";
       }
     else
       {
	 $ss = "a Draft Standard";
       }
     $s="This is now ".$ss.".";
   }
   else
   { 
     $s="";
   }

   $status=$line['STATUS'];
   if ($debug === true) {
        print("\$status = "); var_dump($status); print("<br>");
   }
   $subj_use_salso = false;
   switch($status) {
   case "BEST CURRENT PRACTICE":
        $subj_use_salso = true;
        $st="Best Current Practice";
        $to_print = 
"BCP: This document specifies an Internet Best Current Practices for the
Internet Community, and requests discussion and suggestions for 
improvements. Distribution of this memo is unlimited.\n";
        break;

   case "EXPERIMENTAL":
        $st="Experimental";
        $to_print =
"EXPERIMENTAL: This memo defines an Experimental Protocol for the
Internet community.  It does not specify an Internet standard of any
kind. Discussion and suggestions for improvement are requested.
Distribution of this memo is unlimited.\n";
        break;

   case "HISTORIC":
     $st="Historic";
     $to_print=
"HISTORIC: This memo defines a Historic Document for the Internet
community.  It does not specify an Internet standard of any kind.
Distribution of this memo is unlimited.\n";
        break;

   case "INFORMATIONAL":
        $st="Informational";
        $to_print=
"INFORMATIONAL: This memo provides information for the Internet community.
It does not specify an Internet standard of any kind. Distribution of
this memo is unlimited.\n";
        break;

   case "INTERNET STANDARD": 
        $subj_use_salso = true;
        // drop through
   case "PROPOSED STANDARD":
   case "DRAFT STANDARD":
        $st="Standards Track";
        $to_print=
"STANDARDS TRACK: This document specifies an Internet Standards Track
protocol for the Internet community, and requests discussion and suggestions
for improvements.  Please refer to the current edition of the Official
Internet Protocol Standards (https://www.rfc-editor.org/standards) for the 
standardization state and status of this protocol.  Distribution of this 
memo is unlimited.\n";
        break;

   default:
        break; // DO NOTHING
   }

   $obs=trim($line["OBSOLETES"]);
   $updates=trim($line["UPDATES"]);
   $salso=trim($line["SEE-ALSO"]);
   $others_used = 0;

   if ($debug === true) {
        print("\$obs = "); var_dump($obs); print("<br>");
        print("\$updates = "); var_dump($updates); print("<br>");
        print("\$salso = "); var_dump($salso); print("<br>");
   }

    /*Passed the input Obsoletes values through display adjust function*/

   $print_obs_value = "";
   $print_obs_value = format_rfc_display($obs);

   /*Passed the input Updatess values through display adjust function*/

   $print_updates_value = "";
   $print_updates_value = format_rfc_display($updates);

   /*Passed the input Updatess values through display adjust function*/

   $print_salso_value="";
   $print_salso_value= format_rfc_display($salso);


  /*Deepa : If the Obsoletes, Obsoleted-by, Updates, Updated-by, see-also fields are NULL,
     then display "none". Otherwise display only the fields that are not NULL */ 
   if ($obs != "" && $obs != "NULL") {
        $others_used++;
        $upobssalso  = "        Obsoletes:  ".$print_obs_value."\n";
   }
   if ($updates != "" && $updates != "NULL") {
        $others_used++;
        $upobssalso .= "        Updates:    ".$print_updates_value."\n";
   }
   if ($salso != "" && $salso != "NULL") {
        $others_used++;
        $upobssalso .= "        See Also:   ".$print_salso_value."\n";
   }
   if($others_used == 0) {
        # None of the previous checks added anything
        $upobssalso  = "        Updates/Obsoletes/SeeAlso:   None\n";
   }

   /*The database contains date in the YYYY-MM-DD format. The code below changes
     the foramt to display the date in  "Month_name day" format*/
   $pub_date=format_date($line['PUB-DATE']);

   $abs=$line['ABSTRACT'];
   $string_s="'\\''i";
   $replacement="'";
   $act_abs = preg_replace($string_s,$replacement,$abs);
	
   $rfcstr = str_replace($line["TYPE"], "", $line["DOC-ID"]);
   $idstring = "blah";

   /*To handle special characters like quotes, <, > etc in the title*/

   if ($subj_use_salso) {
        $sub = format_id($salso) . ", ";
   }
   $sub .= $line['TYPE']." ".$rfcstr ." on ".htmlspecialchars($line['TITLE']);

   //get stream
   /*Modified the get_stream_name function based on the internal key to get correct data*/
#   $stream = get_stream_name($line["DOC-ID"]);
   $stream = get_stream_name($line["internal_key"]);

   //make INDEPENDENT look nice
   $stream = str_replace("INDEPENDENT", "Independent", $stream);

   if ($debug === true) {
        print("\$doc-id = "); var_dump($line["DOC-ID"]); print("<br>");
        print("\$stream = "); var_dump($stream); print("<br>");
   }

   //idstring="<`date +%y%m%d%H%M%S`.RFC@RFC-EDITOR.ORG>"

   $body = ""; 
   $body .=  "A new Request for Comments is now available in online RFC libraries.";
     

        $body .=  "\n";
	$body .=  "\n";
        if ($subj_use_salso)  // was if (line['SEE-ALSO'] != '')
        {
             $body .=  "        ". format_id($line['SEE-ALSO']);
	}
        $body .=  "        \n";
	$body .=  "        ".$line['TYPE']." ".$rfcstr;
	$body .=  "\n\n";
	$body .=  "        Title:      ".$title1."\n";
	$body .=  "        Author:     ".$f."\n";
	$body .=  "        Status:     ".$st."\n";
        $body .=  "        Stream:     ".$stream."\n";
	$body .=  "        Date:       ".$pub_date."\n";
	$body .=  "        Mailbox:    ".$e;
	$body .=  "\n        Pages:      ".$line['PAGE-COUNT']."\n";
	#$body .=  "        Characters: ".$line['CHAR-COUNT']."\n";
	$body .=  $upobssalso; // indented when constructed
	$body .=  "\n";
        $body .=  "        I-D Tag:    ".$line['DRAFT'].".txt\n";
        $body .=  "\n";
	$body .=  "        URL:        https://www.rfc-editor.org/info/rfc".$rfcstr."\n";
        $body .=  "\n";
        $body .=  "        DOI:        ".$line['DOI']."\n";
        $body .=  "\n";
	$body .=  $act_abs."\n";
	$body .=  "\n";
   if($group !='')
   {
        $body .=  $group."\n\n";
   }
       
   if($s != '')
   {
     $body .=  $s."\n";
   }
        $body .=  "\n";
        $body .=  $to_print."\n";
	if ($ssp_id != 4){
		$body .=  "This announcement is sent to the IETF-Announce and rfc-dist lists.\n";
		$body .=  "To subscribe or unsubscribe, see\n";
		$body .=  "  https://www.ietf.org/mailman/listinfo/ietf-announce\n";
		$body .=  "  https://mailman.rfc-editor.org/mailman/listinfo/rfc-dist\n";
        }else {
        	$body .=  "This announcement is sent to the IETF-Announce, rfc-dist and IRTF-Announce lists.";
		$body .=  "To subscribe or unsubscribe, see\n";
		$body .=  "  https://www.ietf.org/mailman/listinfo/ietf-announce\n";
		$body .=  "  https://mailman.rfc-editor.org/mailman/listinfo/rfc-dist\n";
		$body .=  "  https://www.irtf.org/mailman/listinfo/irtf-announce\n";
        }
	$body .=  "\n";
	$body .=  "For searching the RFC series, see https://www.rfc-editor.org/search\n";
	$body .=  "For downloading RFCs, see https://www.rfc-editor.org/retrieve/bulk\n";
	$body .=  "\n";
	$body .=  "Requests for special distribution should be addressed to either the\n";
	$body .=  "author of the RFC in question, or to rfc-editor@rfc-editor.org.  Unless\n";
	$body .=  "specifically noted otherwise on the RFC itself, all RFCs are for\n";
	$body .=  "unlimited distribution.\n";
	$body .=  "\n";
	$body .=  "\n";
        $body .=  "The RFC Editor Team\n";
	$body .=  "Association Management Solutions, LLC\n";
	$body .=  "\n";
   ?>

	<form name="mailForm" action="makeAnnouncement.php" method="post" enctype="multipart/form-data">
          <table  bgcolor="#d0dce0" align="center" border=1>
            <tr>
              <td align="right"><b>To:</b></td>
              <td><input type="text" name="to" value="ietf-announce@ietf.org, rfc-dist@rfc-editor.org<?php if ($ssp_id == '4') {echo ', irtf-announce@irtf.org'; } ?>" size="100"></td>
	    </tr>
	    <tr>
              <td align="right"><b>Cc:</b></td>
              <td><input type="text" name="cc"
          value="rfc-editor@rfc-editor.org, drafts-update-ref@iana.org<?php if ($wg_email != null) {
          echo ', ' ; echo $wg_email; } ?>" size="100"></td>
	    </tr>
	    <tr>
	      <td align="right"><b>Subject:</b></td>
              <td><input type="text" name="subject" value="<?php echo $sub;?>" size="100"></td>
	    </tr>
	    <tr><td colspan="2">&nbsp;</td></tr>
	    <tr align="center">
	      <td colspan = 2>
                 <textarea name="body" rows="25" cols="75"><?php echo $body; ?></textarea>
              </td>
	    </tr>
	    <tr>
	      <input type="hidden" name="flag" value="">
	      <input type="hidden" name="draftName" value="<?php echo $draftName; ?>" >
              <td colspan="2"><input type="button" value="Send Announcement" onClick="javascript:submitForm();"></td>
            </tr>
	  </table>
	</form>

	<?php

}
else //Form has been Submitted
{
     $to = $_POST["to"];
     $cc = $_POST["cc"];
     $subject = $_POST["subject"];
     $body = $_POST["body"];

     if ($debug === true) {
          print("<h4>Post Submit</h4>");
          print("<p>");
          print("\$to = "); var_dump($to); print("<br>");
          print("\$cc = "); var_dump($cc); print("<br>");
          print("\$subject= "); var_dump($subject); print("<br>");
          print("\$body = "); var_dump($body); print("<br>");
          print("</p>");
     }

     $headers = "From: rfc-editor@rfc-editor.org\n";
     if ($cc != null) {
          $headers .= "Cc: ".$cc."\n";
     }
     $b=stripslashes($body);
     // For reasons that are unclear (lost in the innards of SMTP mail
     // transport  agents) some MTAs transform the body to
     // double-spaced if there's a CR in the data.
     $mail_sent = ams_mail($to,$subject,preg_replace("/\r/","",$b),$headers);
     if ($mail_sent === false || $debug === true ) {
          if ($mail_sent === false ) { // Report the error and dump key variables
               print("<h3>EMail Error</h3><p>Error sending email to $to. Dump of variables follows</p>");
          } else {
               print("<h3>Variable Last Status</h3><p>Dump of variables follows</p>");
          }
          print("<p><pre>");
          print("\$headers: \n"); var_dump($headers);
          print("</pre></p>");
          print("<p>");
          print("\$to      = "); var_dump($to);      print("<br />");
          print("\$cc      = "); var_dump($cc);      print("<br />");
          print("\$subject = "); var_dump($subject); print("<br />");
          print("</p>");
          print("<p><pre>");
          print("\$b    =\n"); var_dump($b);
          print("</pre></p>");
     } else {                // if no errors
          print("<script type=\"text/javascript\">\n"); // close the window
          print("<!-- \n");
          print("window.close();\n");
          print("-->\n");
          print("</script>\n");
     }
}

function format_rfc_display($in_value){

   global $debug;

   $in_rfc_name="";
   $in_rfc_number="";
   $in_length="";
   $out_value= "";
   $in_array_count = 0;
   $in_value_array = explode(",",$in_value);
   $in_array_count = count($in_value_array);
 
   $i = 0;

   foreach ($in_value_array as $key=>$value){
      $i++;
      list($in_rfc_name,$in_rfc_number,$in_length) = split_rfc($value);
      if ($i == $in_array_count){
         $out_value .= format_rfc($in_rfc_name, $in_rfc_number);
      } else{
         $out_value .= format_rfc($in_rfc_name, $in_rfc_number).",";
      }
   }

   if ($debug === true) {
        print("<h4>In value</h4>");
        print $in_value;
        print("<h4>Output RFCs string</h4>");
        print_r($in_value_array);
        print $out_value;
   }

   return $out_value;
}

?>
</body>
</html>
