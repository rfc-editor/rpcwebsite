<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
/*************************************************************************************************/
/*Script Name    :   area_assignement.php                                                        */
/* October 2018  :   Script to add row to area assignment table as per Alice's request -PN       */
/* November 2020 : Modified the script to use PDO prepared statements - PN                       */
/*************************************************************************************************/

include('header.php');
include('db_connect.php');

rfc_header("Area assignment","area_assignment.js");

$debug_area= false;
$area_ass_count = 0;

# The function get_area_acronyms get the list of available areas from the database table
# table area_acronym.
#
function get_area_acronyms(){
   global $pdo;
/* Connecting, selecting database */
   $query =
       "SELECT `area_acronym` FROM `area` WHERE area_acronym IS NOT NULL
        AND area_status = :area_status
        ORDER BY area_acronym";

   try{
       $stmt = $pdo->prepare($query);
       $stmt->bindValue('area_status',open);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
         error_log("Error processing : get_area_acronyms : area_assignment", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
   $acronyms = array();
   while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
          $acronyms = array_merge($acronyms,$row);
   }
return $acronyms;
}

# The function get_area_id get the acronym_id for the particular acronym from the area_acronym
# table.
#
function get_area_id($in_acronym){
   global $pdo;

/* Connecting, selecting database */
   $query =
       "SELECT `area_id` FROM `area` WHERE area_acronym = :in_acronym";
   try{
       $stmt = $pdo->prepare($query);
       $stmt->bindParam('in_acronym',$in_acronym);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
         error_log("Error processing : get_area_id : area_assignment", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
 
   $row = $stmt->fetch(PDO::FETCH_NUM);
   $area_id = $row[0];

return $area_id;

}

# The function get_area_id get the acronym_id for the particular acronym from the area_acronym
# table.
#
function get_internal_key($in_rfcNum){
   global $pdo;

/* Connecting, selecting database */
   $query =
        "SELECT `internal_key` FROM `index` WHERE `DOC-ID` = :doc_id";
   try{
       $stmt = $pdo->prepare($query);
       $doc_id =  sprintf('%s%04d','RFC',$in_rfcNum);
       $stmt->bindParam('doc_id',$doc_id);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
         error_log("Error processing : get_internal_key : area_assignment", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
   
   $row = $stmt->fetch(PDO::FETCH_NUM);
   $internal_key = $row[0];

return $internal_key;

}

# The function add_to_area_assignment adds the row to the table area_assignmet with the input rfc 
# number and acronym id.
#
function add_to_area_assignment($areaid,$internalkey){
   global $debug_area;
   global $pdo;

 /* Connecting, selecting database */
   $query =
       "INSERT INTO `area_assignments` (`fk_area`,`fk_index`) VALUES (:areaid,:internalkey)";
   if ( $debug_area === true) {
       print("<h2>add_to_area_assignment</h2>");
       print("<h3>Query</h3>");
       print_r($query);
   }

  try{
       $stmt = $pdo->prepare($query);
       $stmt->bindParam('areaid',$areaid);
       $stmt->bindParam('internalkey',$internalkey);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
         error_log("Error processing : add_to_area_assignment: area_assignment", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
 
   if ($num_of_rows > 0) {
       $insert_status = 1; 
   } else {
       $insert_status = 0;    
   }          

   return $insert_status;
}


# The function add_to_area_assignment adds the row to the table area_assignmet with the input rfc 
# number and acronym id.
#
function update_area_assignment($areaid,$internalkey){
   global $debug_area;
   global $pdo;
 /* Connecting, selecting database */
   $query =
       "UPDATE `area_assignments` SET `fk_area` = :areaid WHERE `fk_index`= :internalkey";
   if ( $debug_area === true) {
       print("<h2>update_area_assignment</h2>");
       print("<h3>Query</h3>");
       print_r($query);
   }

   try {
       $stmt = $pdo->prepare($query);
       $stmt->bindParam('areaid',$areaid);
       $stmt->bindParam('internalkey',$internalkey);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
         error_log("Error processing : update_area_assignment: area_assignment", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
 
   if ($num_of_rows > 0) {
       $update_status = 1; 
   } else {
       $update_status = 0;    
   }          


     return $update_status;
}


# The function get_area_id get the acronym_id for the particular acronym from the area_acronym
# table.
#
function check_area_assignment($in_key){
   global $pdo;
/* Connecting, selecting database */
   $query =
       "SELECT `fk_area`,`fk_index` FROM `area_assignments` WHERE `fk_index` = :in_key";
   if ( $debug_area === true) {
       print("<h2>check_area_assignment</h2>");
       print("<h3>Query</h3>");
       print_r($query);
   }
   try {
       $stmt = $pdo->prepare($query);
       $stmt->bindParam('in_key',$in_key);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
         error_log("Error processing : check_area_assignment: area_assignment", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   $count = $num_of_rows;
   return $count;
}

if ($debug_area === true) {
    print_r($_POST);
}


if ((isset($_POST))&& (array_key_exists('rfcNum',$_POST))){

    $rfcNum = $_POST['rfcNum'];
    $acronym = $_POST['area_acronym'];
    
    if (($rfcNum == "") AND (array_key_exists('rfcNum',$_POST))){
        print "<p class=\"warning\">Please provide the RFC Number</p>\n";
    }else {
        $area_id =get_area_id($acronym);
        $internal_key = get_internal_key($rfcNum);
        $area_ass_count = check_area_assignment($internal_key);
   
     if ($debug_area === true) {
           print"<b>The values provied here are</b>\n<pre>";
           print_r($_POST);
           print "The rfc num is $rfcNum, acronym is $acronym, and area id is $area_id and internal key is $internal_key and area_assignment_count is $area_ass_count";
           print "</pre>"; 
      }



      if (($area_ass_count > 0) && ($internal_key != "")){

print<<<END
<form id="assign_area" name="area_assign_form" method="post" action="area_assignment.php" >
   <table align="center" border="0">
   <tr><td>Record already exists, would you like to overwrite it?</td><td><input type="submit" name="submit"value="Yes"></td><td><input type="submit" name="submit" value="No"></td></tr>
   </table>
   <input type="hidden" name="area_id" value="$area_id" />
   <input type="hidden" name="internal_key" value="$internal_key">
   </form>
END;


      }else{
            if  (add_to_area_assignment($area_id,$internal_key)){

            }else {
               print("\n <p class=\"error\">Insert to Area Assignments failed. See log for details.</p>\n");
   
            }

      }
        
   }
}
     
    

if ((isset($_POST))&& (array_key_exists('internal_key',$_POST))){

   if($_POST['submit'] == 'Yes' ){
    $area_id = $_POST['area_id'];
    $internal_key = $_POST['internal_key'];
  #  print "Need to rewrite the record";
    $update_status = update_area_assignment($area_id,$internal_key);
    if ($update_status){
       print "Updated the area assignment for " . htmlspecialchars($internal_key) . " to " . htmlspecialchars($area_id);

     }else {

       print("\n <p class=\"error\">Update to Area Assignments failed. See log for details.</p>\n");
     }
   }elseif($_POST['submit'] == 'No'){

    print "Record not rewritten. Please enter other values";

   } 


} 
#Get teh dropdown list of acronyms

 $area_acronyms = get_area_acronyms();


print <<<END

<form id="assign_area" name="area_acronymForm" method="post" action="area_assignment.php" onsubmit="return validateForm(this);" >
<h4 align="center">RFC Number and area assignment</h4></td>
<table class="seach">
 <tbody valign="top">
   <tr>
     <td align="center">
      <label>RFC Number </label><input type="text" value="" name="rfcNum" id="rfcNum" maxlength="4"/>
     </td>
     <td>&nbsp;</td>
     <td align="center">
       <label>Area acronym </label>
     </td>
     <td align="center">
      <select name="area_acronym" id="area_acronym">
     
END;
     foreach ($area_acronyms as $acronym) {
      #         if (strcasecmp($acronym, $criteria['area_acronym']) == 0) {
      #              print("             <option value=\"$acronym\" selected=\"selected\">$acronym</option>\n");
      #         } else {
                    print("             <option value=\"$acronym\">$acronym</option>\n");
      #         }
      }

print <<<END
     </select>
     </td>
     <td align="center">
      <input type="submit" name="submit" value="Add" />
     </td>
   </tr>
 </tbody>

</table>
</form>

END;

rfc_footer();


?>
