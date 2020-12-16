<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
/***********************************************************************************************************/
/*Script Name : index_cotrols.php                                                                          */
/*Description : The script handles the various process for selected draft from the dropdown.The process are*/
/*              Create Announcement, AUTH48Email, Send Email, Publish Draft.                               */
/*              It handles Update INDEX, Update Queue, Post to Datatracker.                                */
/*Sept 2019   : Modified the default radio button selection to XML from nroff -PN                          */
/* November 2020 : Modified the script to use PDO prepared statements - PN                                 */
/***********************************************************************************************************/
#
  /* $Id: index_controls.php,v 2.9 2020/11/11 01:03:17 priyanka Exp $ */
session_start();
include('header.php');
include('db_connect.php');

rfc_header("mail controls","index_controls.js");

$flag="";
print "<table class=\"search\"><tr valign=\"top\"><td colspan=\"2\">";
#
# The action attribute of these forms is reset by the JavaScript routine called by
# the input buttons so that the approprate PHP script is run.
#
print <<<END
  <form id="publishForm" name="publishForm" method="post" action="index_controls.php" target="_blank">
     <select name="draftName">
END;
/* Connecting, selecting database */
global $pdo;

   $query = "SELECT `DRAFT` FROM `index` where `state_id` != :state_id order by `DRAFT`";
   
   try {  
       $stmt = $pdo->prepare($query);
       $stmt->bindValue('state_id',14);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : index_control.php", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   echo "<option>SELECT DRAFT FROM LIST</option>";
   while ($line = $stmt->fetch(PDO::FETCH_ASSOC)) {
       foreach ($line as $colvalue) {
           if($colvalue != '' && $colvalue != "NULL")
           {
              echo "<option>".$colvalue."</option>\n";
           }
         }
     }


print <<<END
</select>
</form>
</td></tr>
<tr valign="top">
<td>
  <input type="button" value="Create Announcement" onClick="javascript:submitForm('announcementForm');" />
</td>
<td>
    <form id="AUTH48Form" name="AUTH48Form" action="index_controls.php" method="post" target="_blank">
       <input type="button" value="AUTH48 Email" onclick="javascript:submitForm('auth48emailForm');" />
       <input type="radio" name="template" value="nroff" />nroff
       <input type="radio" name="template" value="xml" checked="true" />xml
       <input type="hidden" name="draftName" value="draftName" />
     </form>
</td>
</tr>
<tr>
<td>
  <input type="button" value="Send Email" onClick="javascript:submitForm('emailForm');" />
</td>
<td>
  <!--  Publish Draft :  which when clicked changes the state of 
        the selected draft to PUB.-->
  <input type="button" value="Publish Draft" onClick="javascript:submitForm('publishDraftForm');" />
</td>
</tr>
END;
print "</table>";

print <<<END
<form name="updateForm" method="post" action="index_controls.php" target="_blank">
<table class="seach">
<!-- <thead valign="top">
   <tr>
     <th>Force Update of INDEX</th>
     <th>&nbsp;</th>
     <th>Force Update of Curent Queue<br /><abbr>XML</abbr> and <abbr>HTML</abbr>.</th>
   </tr>
 </thead> -->
 <tbody>
   <tr valign="middle">
     <td align="center">
      <input type="button"  class="red" value="Update INDEX and rfc-index.xml" onClick="javascript:submitForm('commitForm');" />
     </td>
     <td>&nbsp;</td>
     <td align="center">
      <input type="button" class="red" value="Update Queue" onClick="javascript:submitForm('qcommitForm');" />
     </td>
     <td>&nbsp;</td>
     <td align="center">
      <input type="button" class="red" value="Post to datatracker & RSS Feed" onClick="javascript:submitForm('postIndexDatatrackerForm');" />
     </td>
   </tr>
 </tbody>
</table>
</form>
END;


print "<br>";

print <<<END
<form name="searchPubForm" method="post" action="makeAnnouncement.php" target="_blank">
<h4 align="center">Search by RFC number to create an announcement</h4></td>
<table class="seach">
 <tbody valign="top">
   <tr valign="middle">
     <td align="center">
      <input type="text" value="" name="rfcNum" maxlength="4"/>
      <input type="hidden" name="flag" value="">
     </td>
     <td align="center">
      <input type="button" value="Search PUB Draft" onClick="javascript:submitForm('searchPubForm');" />
     </td>
   </tr>
 </tbody>

</table>
</form>

END;
 
rfc_footer();
?>
