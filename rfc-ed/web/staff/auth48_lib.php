<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
/***********************************************************************************************************/
/*Script Name : auth48_lib.php                                                                             */
/*Description : The script handles the functionality for Auth48 processing                                 */
/*May 2017    : Modified to add the file type changes  PN                                                  */
/* November 2020 : Modified the script to use PDO prepared statements - PN                                 */
/* February 2021 : Added the changes for awaiting_ad_approval flag - PN                                    */
/* June 2021 : Modified the script for server upgrade - PN                                                 */
/* September 2023 : Modified the script for display improvement - PN                                       */
/* October 2023 : Modified the link for internet-drafts - PN                                               */
/* November 2023 : Modified the script to show for AD for IETF NON WG  - PN                                */
/***********************************************************************************************************/
#
# $Id: auth48_lib.php,v 2.28 2023/11/22 23:27:10 priyanka Exp $
# Routines to support processing of the AUTH48 state
include_once("db_connect.php");
include_once("cluster_support_lib.php");
include_once("display_adjust.php");

$debug_a48_lib = false;

# Constants to limit input string lengths
define("MAX_RFC_DIGIT", 4);
define("MAX_APPROVER_NAME", 60);
define("MAX_APPROVED_ENUM", 3);
define("MAX_STATUS_ENUM", 9);
define("MAX_DATE_STRING", 10);

$approved_enums = array('yes' => 'yes', 'no' => "no");
$status_enums = array('active' => 'active', 'completed' => 'completed');

##############################################################################
# Routines used to initialize the rows for an AUTH48 process. Called from    #
# The Auth48Email.php script after the AUTH48 message is sent.               #
##############################################################################

# Function used to initialize the auth48s and approvers table entries.
function initialize_auth48($docid, $authors) {
     global $debug_a48_lib;
     $status = FALSE;

     if (($a48_id = get_a48_id($docid)) !== FALSE) {
          switch ($a48_id) {
          case 0:
               # The record did not exist so create it.
               $a48_id = create_auth48_row($docid);
               if ($a48_id !== FALSE) {
                    # Add the authors as initial set of approvals required.
                    $status = create_author_rows($a48_id, $authors);
               }
               break;

          default:
               # Found a record so just return a48_id as if newly created.
               $status = TRUE;
               break;
          }
     }
     return ($status !== FALSE ? $a48_id : $status);
}

# Add a new row to the auth48s table. Return the a48_id key for new row.
function create_auth48_row($docid) {
   global $debug_a48_lib;
   global $pdo;
   
   $status = FALSE;
   $insert="INSERT INTO auth48s (`doc-id`) VALUES (:docid)";

   if ($debug_a48_lib === TRUE) {
       print("\n<!-- create_auth48_row: insert=$insert\n-->\n");
   }
   try {
       $stmt = $pdo->prepare($insert);
       $stmt->bindParam('docid',$docid);
       $stmt->execute();
       $insert_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : create_auth48_row  add to state history", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   if ($insert_rows > 0) {
       $status = TRUE;
   }
   $a48_id = get_a48_id($docid);
   return ($status === TRUE ? $a48_id : FALSE);
}

# Lookup the a48_id for a document using the doc-id.
# Returns: integer $a48_id if found or 0 for not found.
#          boolean FALSE on database error
function get_a48_id($docid) {
   global $debug_a48_lib;
   global $pdo;

   $query= "SELECT a48_id FROM auth48s WHERE `doc-id`=:docid";

   if ($debug_a48_lib === TRUE) {
       print("\n<!-- get_a48_id: query=$query\n-->\n");
   }
   try {
       $stmt = $pdo->prepare($query);
       $stmt->bindParam('docid',$docid);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
        error_log("Error processing : get_a48_id", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
   switch ($num_of_rows) {
       case 0:
           $a48_id = '0';
           break;
       case 1:
           $row = $stmt->fetch(PDO::FETCH_NUM);
           $a48_id = $row[0];
           break;
       default:
           $row = $stmt->fetch(PDO::FETCH_NUM);
           $a48_id = $row[0];
           error_log ("WARNING: get_a48_id returned multiple rows for draft '$docid'");
           break;
   }
   if ($debug_a48_lib === TRUE) {
       print("\n<!-- get_a48_id: returning id '$a48_id'\n-->\n");
   }
   return $a48_id;
}




# Retrieve the a48_id using the draft string (called by publishDraft.php).
function get_a48_id_by_draft($draft) {
   global $debug_a48_lib;
   global $pdo;

   $query= "SELECT a48_id 
       FROM `index` i, auth48s a 
       WHERE i.`doc-id` = a.`doc-id` AND i.draft=:draft";

   if ($debug_a48_lib === TRUE) {
       print("\n<!-- get_a48_id_by_draft: query=$query\n-->\n");
   }
   try {
       $stmt = $pdo->prepare($query);
       $stmt->bindParam('draft',$draft);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
        error_log("Error processing : get_a48_id_by_draft", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
   switch ($num_of_rows) {
       case 0:
           $a48_id = '0';
           break;
       case 1:
           $row = $stmt->fetch(PDO::FETCH_NUM);
           $a48_id = $row[0];
           break;
       default:
           $row = $stmt->fetch(PDO::FETCH_NUM);
           $a48_id = $row[0];
           error_log ("WARNING: get_a48_id_by_draft returned multiple rows for draft '$draft'");
           break;
   }
   if ($debug_a48_lib === TRUE) {
       print("\n<!-- get_a48_id_by_draft: returning id '$a48_id'\n-->\n");
   }

   return $a48_id;
}

#Update the awaiting_ad_approval_flag
function update_awaiting_ad_approval($a48_id,$awaiting_ad_approval){
   global $debug_a48_lib;
   global $pdo;

   $status = FALSE;
   $update = "UPDATE auth48s SET awaiting_ad_approval=:awaiting_ad_approval WHERE a48_id=:a48_id";
   if ($debug_a48_lib === TRUE) {
       print("\n<!-- update_notes: update='$update'\n-->\n");
   }
   try {
       $stmt = $pdo->prepare($update);
       $stmt->bindParam('awaiting_ad_approval',$awaiting_ad_approval);
       $stmt->bindParam('a48_id',$a48_id);
       $stmt->execute();
       $change_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : update_awaitibg_ad_approval", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
   if ($change_rows) {
       $status = TRUE;
   } 

}


# Set the value for the approved column. This routine uses the a48_id and name
# as key into table.
function set_approval($a48_id, $name, $value) {
   global $debug_a48_lib, $approved_enums, $pdo;

   $status = FALSE;

   if (array_key_exists($value, $approved_enums)) {
       $update = "UPDATE approvals SET approved=:value WHERE a48_id=:a48_id AND name=:name";

       if ($debug_a48_lib === TRUE) {
           print("\n<!-- set_approval: update=$update\n-->\n");
       }
        try {
           $stmt = $pdo->prepare($update);
           $stmt->bindParam('value',$value);
           $stmt->bindParam('a48_id',$a48_id);
           $stmt->bindParam('name',$name);
           $stmt->execute();
           $change_rows = $stmt->rowCount();
       } catch (PDOException $pe){
           error_log("Error processing : set_approval", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
       }

       if ($change_rows > 0) {
           $status = update_approved_date_name($a48_id, $name, date("Y-m-d"));
       } 
   }
   return $status;
}



# Set the value of the approved column. This routine use the index into
# the approvals table to locate the record. Called with updating an existing
# approver row.
function update_approved($app_id, $app_value) {
   global $debug_a48_lib, $approved_enums, $pdo;

   $status = FALSE;
   if (array_key_exists($app_value, $approved_enums)) {
       $update = "UPDATE approvals SET approved=:app_value WHERE app_id=:app_id";
       if ($debug_a48_lib === TRUE) {
           print("\n<!-- update_approved: update='$update'\n-->\n");
       }
       try {
           $stmt = $pdo->prepare($update);
           $stmt->bindParam('app_value',$app_value);
           $stmt->bindParam('app_id',$app_id);
           $stmt->execute();
           $change_rows = $stmt->rowCount();
       } catch (PDOException $pe){
           error_log("Error processing : update_approved", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
       }
 
       if ($change_rows > 0) {
           $status = update_approved_date_app_id($app_id, date("Y-m-d"));
       } 
   }
   return $status;
}


# Clear the approved date using app_id for key
function clear_approved_date($app_id) {
   global $debug_a48_lib;
   global $pdo;

   $status = FALSE;

   $update = "UPDATE approvals SET approved_date=:date WHERE app_id=:app_id";

   if ($debug_a48_lib === TRUE) {
       print("\n<!-- clear_approved_date: update=$update\n-->\n");
   }

   try {
       $stmt = $pdo->prepare($update);
       $date = NULL;
       $stmt->bindParam('date',$date);
       $stmt->bindParam('app_id',$app_id);
       $stmt->execute();
       $change_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : clear_approved_date", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
 
   if ($change_rows > 0) {
       $status = TRUE;
   }
   return $status;
}


# Update the approved date using app_id for key
function update_approved_date_app_id($app_id, $date) {
   global $debug_a48_lib;
   global $pdo;

   $status = FALSE;

   $update = "UPDATE approvals SET approved_date=:date WHERE app_id=:app_id";

   if ($debug_a48_lib === TRUE) {
       print("\n<!-- set_approved_date_app_id: update=$update\n-->\n");
   }

   try {
       $stmt = $pdo->prepare($update);
       $stmt->bindParam('date',$date);
       $stmt->bindParam('app_id',$app_id);
       $stmt->execute();
       $change_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : update_approved_date_app_id", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
 
   if ($change_rows) {
       $status = TRUE;
   } 

   return $status;
}



# Update the approved date using a48_id and name for key
function update_approved_date_name($a48_id, $name, $date) {
   global $debug_a48_lib;
   global $pdo;

   $status = FALSE;

   $update = "UPDATE approvals SET approved_date=:date 
       WHERE a48_id=:a48_id AND name=:name";

   if ($debug_a48_lib === TRUE) {
       print("\n<!-- set_approved_date_name: update=$update\n-->\n");
   }

   try {
       $stmt = $pdo->prepare($update);
       $stmt->bindParam('date',$date);
       $stmt->bindParam('a48_id',$a48_id);
       $stmt->bindParam('name',$name);
       $stmt->execute();
       $change_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : update_approved_date_name", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
 
   if ($change_rows > 0) {
       $status = TRUE;
   } 

   return $status;
}


# Update the NOTES column of the auth48s table
function update_notes($a48_id, $notes) {
   global $debug_a48_lib;
   global $pdo;

   $status = FALSE;
   $update = "UPDATE auth48s SET notes=:notes WHERE a48_id=:a48_id";
   if ($debug_a48_lib === TRUE) {
       print("\n<!-- update_notes: update='$update'\n-->\n");
   }
   try {
       $stmt = $pdo->prepare($update);
       $stmt->bindParam('notes',$notes);
       $stmt->bindParam('a48_id',$a48_id);
       $stmt->execute();
       $change_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : update_notes", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
   if ($change_rows) {
       $status = TRUE;
   } 
   return $status;   
}



# Update the STATUS column of the auth48s table
function update_status($a48_id, $stat_value) {
   global $debug_a48_lib, $status_enums, $pdo;

   $status = FALSE;
   if (array_key_exists($stat_value, $status_enums)) {
       $update = "UPDATE auth48s SET status=:stat_value, completion_date=:completion_date WHERE a48_id=:a48_id";
       if ($debug_a48_lib === TRUE) {
           print("\n<!-- update_status: update='$update'\n-->\n");
       }
       try {
           $stmt = $pdo->prepare($update);
           switch ($stat_value) {
               case 'completed':
                   $stmt->bindParam('stat_value',$stat_value);
                   $completion_date = date('Y-m-d H:i:s');
                   $stmt->bindParam('completion_date',$completion_date);
                   $stmt->bindParam('a48_id',$a48_id);
                   break;
               case 'active':
                   $stmt->bindParam('stat_value',$stat_value);
                   $completion_date = NULL;
                   $stmt->bindParam('completion_date',$completion_date);
                   $stmt->bindParam('a48_id',$a48_id);
                   break;
               default:
                   error_log("update_status: Unrecognized status value: $stat_val");
                   return $status;
                   break;
           }
           $stmt->execute();
           $update_rows = $stmt->rowCount();
       } catch (PDOException $pe){
           error_log("Error processing : update_status", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
       }
        
       if ($update_rows > 0) {
           $status = TRUE;
       } 
   }
   return $status;   
}
# Decide whether the update for the name is a real change or a blank
# line requesting that that record be deleted.
function update_name($app_id, $name) {
     global $debug_a48_lib;

     $status = FALSE;

     if (strlen(trim($name)) > 0) {
          $status = change_name($app_id, $name);
     } else {
          $status = delete_approver($app_id);
     }

     return $status;
}

# Update a name column in the approvals table.
function change_name($app_id, $name) {
   global $debug_a48_lib;
   global $pdo;

   $status = FALSE;

   $update = "UPDATE approvals SET name=:name WHERE app_id=:app_id";
   if ($debug_a48_lib === TRUE) {
       print("\n<!-- change_name: update='$update'\n-->\n");
   }
   try {
       $stmt = $pdo->prepare($update);
       $stmt->bindParam('name',$name);
       $stmt->bindParam('app_id',$app_id);
       $stmt->execute();
       $change_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : change_name", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
 
   if ($change_rows > 0) {
          $status = TRUE;
   } 
   return $status;
}



# Delete from the approvals table one approver
function delete_approver($app_id) {
   global $debug_a48_lib;
   global $pdo;

   $status = FALSE;

   $update = "DELETE FROM approvals WHERE app_id=:app_id";

   if ($debug_a48_lib === TRUE) {
       print("\n<!-- delete_approver: update='$update'\n-->\n");
   }

   try {
       $stmt = $pdo->prepare($update);
       $stmt->bindParam('app_id',$app_id);
       $stmt->execute();
       $delete_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : delete_approvar", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
 
   if ($delete_rows > 0){
       $status = TRUE;
   }

   return $status;
}

# Insert a new row into the approvals table. (This routine can also be called when
# processing updates from the form in the auth48_edit.php script.)
function add_approver($a48_id, $approver) {
   global $debug_a48_lib;
   global $pdo;

   $status = FALSE;
   $insert = "INSERT INTO approvals (a48_id, name) VALUES (:a48_id, :approver)";

   if ($debug_a48_lib === TRUE) {
          print("\n<!-- add_approver: insert=$insert\n-->\n");
   }
   try {
       $stmt = $pdo->prepare($insert);
       $stmt->bindParam('a48_id',$a48_id);
       $stmt->bindParam('approver',$approver);
       $stmt->execute();
       $insert_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : add_approvar", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
 
   if ($insert_rows > 0) {
          $status = TRUE;
   }     
   return $status;
}
function create_author_rows($a48_id, $authors) {
     global $debug_a48_lib;

     # hack from <edit_lib,hack_overloaded_fields>
     $authors =  preg_replace('/, Ed\./', '; Ed.', $authors);
     $author_array = preg_split("/, +/",$authors);
     foreach ($author_array as $author) {
          $status = add_approver($a48_id, preg_replace('/; Ed\./', ', Ed.',$author));
          if ($status === FALSE) {
               break;
          }
     }
     return $status;
}


#This function changes the state of the document from AUTH48 --> AUTH48-DONE or vice-a-versa
#in the index table.
#An entry is made to state history table to log the sttae change.
function update_state($doc_id,$state_id){
   global $debug_a48_lib;
   global $pdo;

   $update = "UPDATE `index` SET `state_id`=:state_id WHERE `DOC-ID`=:doc_id";

   if ($debug_a48_lib === TRUE) {
       print("\n<!-- update_states: update='$update'\n-->\n");
   }
   try {
       $stmt = $pdo->prepare($update);
       $stmt->bindParam('state_id',$state_id);
       $stmt->bindParam('doc_id',$doc_id);
       $stmt->execute();
       $update_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : Update state", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
   
   if ($update_rows > 0) {
       $history_status = create_history_row($doc_id);          
       if ($history_status === FALSE){
           $status = FALSE;  
           #break; 
       } else {   
	   $status = TRUE;
       }  
   } 
   return $status;   
}




#This function adds a row to state history table to capture the 
#state changes made during AUTH48 tracking
function create_history_row($doc_id){
   global $debug_a48_lib;
   global $pdo;

   $query= "SELECT `internal_key`,`DRAFT`,`DOC-ID`,`ref_flag`,`state_id`,`iana_flag`,`generation_number` 
        FROM `index` i
        WHERE i.`doc-id` = :doc_id";

   if ($debug_a48_lib === TRUE) {
       print("\n<!-- create_history_row: query=$query\n-->\n");
   }

   try {
       $stmt = $pdo->prepare($query);
       $stmt->bindParam('doc_id',$doc_id);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   }catch (PDOException $pe){
       error_log("Error processing : create_history_row", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   $count = $num_of_rows;
   switch($count) {
       case 0:
           print("<p style=\"margin-left: 25%;\">No AUTH48 processing record found for document ID RFC$docnum.</p>");
           break;
       default:               
           $row = $stmt->fetch(PDO::FETCH_ASSOC);
           $key = $row['internal_key'];
           $state_id = $row['state_id'];    
           $in_date = date('Y-m-d');
           $draft = $row['DRAFT'];
           $version_number = substr($draft,-2);
           $iana_flag = $row['iana_flag'];
           $ref_flag = $row['ref_flag'];
           $generation_number = $row['generation_number'];

           $insert = "INSERT INTO `state_history` (internal_dockey,state_id,in_date,iana_flag,ref_flag,version_number,generation_number) VALUES (:key,:state_id,:in_date,:iana_flag,:ref_flag,:version_number,:generation_number) ";

           try {
               $stmt = $pdo->prepare($insert);
               $stmt->bindParam('key',$key);
               $stmt->bindParam('state_id',$state_id);
               $stmt->bindParam('in_date',$in_date);
               $stmt->bindParam('iana_flag',$iana_flag);
               $stmt->bindParam('ref_flag',$ref_flag);
               $stmt->bindParam('version_number',$version_number);
               $stmt->bindParam('generation_number',$generation_number);
               $stmt->execute();
               $insert_rows = $stmt->rowCount();
           } catch (PDOException $pe){
               error_log("Error processing : create_history_row  add to state history", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
           }

	   if ($debug_a48_lib === TRUE) {
	       print("\n<!-- update_states: update='$insert'\n-->\n");
     	   }
	   if ($insert_rows > 0) {
               $history_status = TRUE;
	   } else { 
	       $history_status = FALSE;
           }

   }
   return  $history_status;
}

##############################################################################
# Routines processing the update form from auth48_edit.php
##############################################################################

# Creates a form for the user to input the document to track through the
# AUTH48 process.
function query_form() {
     print <<<END

      <div>
        <form name="auth48_query" action="auth48_edit.php" method="post" enctype="multipart/form-data">
          <table border="0">
            <tr>
              <td>RFC Number:</td>
              <td><input type="text" name="docnum" size="4" /></td>
            </tr>
            <tr>
              <td colspan="2"><input type="submit" name="lookup" value="Lookup Document" />
            </tr>
          </table>
        </form>
      </div> 

END;
}

function display_AUTH48DONE(){
      
 print <<<END

      <div>
          <table border="0">
            <tr>
              <td>AUTH48-DONE entries:</td>
            <tr>
          </table>
      </div> 

END;

}
# Creates the results and update form for a document
function approvals_display_form($docnum) {
     global $debug_a48_lib;

#     print("<h2 style=\"display: table; margin-right: auto; margin-left: auto;\">AUTH48 for RFC $docnum</h2>");

     $a48_row = auth48_lookup($docnum);
     if ($a48_row !== FALSE){
	 
         $draft_name = $a48_row['draft'];
         $state_name = get_state_name($a48_row['state_id']);
         $ad_name = get_ad_name($draft_name);
       
         if ($a48_row !== FALSE){
         # Get the iesg_contact from index table
         if ($ad_name == 'Not Applicable'){
            #Get the source and check if it is IETF NON WORKING GROUP            
            $source = get_source_name($a48_row['doc-id']);
            if ($source == 'IETF - NON WORKING GROUP'){
                $iesg_contact = get_iesg_contact($a48_row['doc-id']);
                $ad_name = '[see below]';
            } else {
                $iesg_contact = 'Not Applicable';
            }
         } else {
          $iesg_contact = get_iesg_contact($a48_row['doc-id']);
         }
 
         print("<h2 style=\"display: table; margin-right: auto; margin-left: auto;\">$state_name for RFC $docnum <br>(<a href=\"https://www.rfc-editor.org/staff/edit_draft.php?draft=$draft_name\">$draft_name</a>)</h2>");
         print ("<table style=\"width: 850px; margin: auto\"; >");
         print ("<tr><td>");
         //To display special area director names
         print ("<label class=\"a48\">Area Directors: </label>".$ad_name);
         print ("</td></tr></table>");       
#         print("<h3>Area Directors : $ad_name</h3>");

     }

     if ($debug_a48_lib === TRUE) {
          print("\n<!-- retrieved auth48s row \$a48_row for RFC$docnum:\n");
          var_dump($a48_row);
          print("\n-->\n");
     }

        print ("<table style=\"width: 850px; margin: auto\"; >");
        print ("<tr><td>");
        print ("<label class=\"a48\">Responsible AD: </label>".$iesg_contact);
        print ("</td></tr></table>");       
     }


     if ($a48_row !== FALSE) {
          approvals_form_open($a48_row);
          approvals_lookup($docnum);
          new_approver_row();
          print<<<END
     <tr><td colspan="4" style="background: rgb(215,215,245);"><hr /></td></tr>

END;
          approvals_form_notes($a48_row['notes']);
          approvals_form_submit();
          display_cluster_message($a48_row['draft']);
          approvals_files_row($a48_row['draft'], $docnum);
	  document_external_link($docnum);
          approvals_form_close();
     }
}


function auth48_lookup($docnum) {
   global $debug_a48_lib;
   global $pdo;
   $row = FALSE; # reset to associative array on success

   $doc_id = sprintf('RFC%04d',$docnum);
   $query = "SELECT a.a48_id, a.`doc-id`, a.status, a.start_date, 
       a.completion_date, a.notes, i.draft,i.state_id
       FROM auth48s a, `index` i
       WHERE i.`doc-id` = a.`doc-id` AND a.`doc-id`=:doc_id";


   if ($debug_a48_lib === TRUE) {
       print("\n<!-- approvals_lookup: query=$query\n-->\n");
   }

   try {
       $stmt = $pdo->prepare($query);
       $stmt->bindParam('doc_id',$doc_id);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   }catch (PDOException $pe){
       error_log("Error processing : auth48_lookup", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   $count = $num_of_rows;
   switch($count) {
       case 0:
           print("<p style=\"margin-left: 25%;\">No AUTH48 processing record found for document ID RFC$docnum.</p>");
           break;
       default:               
           $row = $stmt->fetch(PDO::FETCH_ASSOC);
           $pstatus = ucfirst($row['status']);
   }

   return $row;
}

# Produce a table of all AUTH48 docs, columns=editor 
function show_auth48_list($state_id , $editor_role='1') {
    global $debug_a48_lib;
    global $pdo;

    $query = "
        SELECT e.initials, i.`doc-id`
        FROM `index` i, editor_assignments e 
        WHERE i.state_id = :state_id
        AND e.initials != :initials
        AND e.role_key = :editor_role
        AND i.internal_key = e.doc_key
        ORDER BY e.initials";

    if ($debug_a48_lib === TRUE) {
        print("\n<!-- auth48_list: query=$query\n-->\n");
    }

    try {
       $stmt = $pdo->prepare($query);
       $stmt->bindParam('state_id',$state_id);
       $stmt->bindValue('initials','XX');
       $stmt->bindParam('editor_role',$editor_role);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
    } catch (PDOException $pe){
       error_log("Error processing : show_auth48_list", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
    }

    $doc_array = array();
    $count_array = array();

    # build associative array editor => array of docs
    while ( $line = $stmt->fetch(PDO::FETCH_NUM)) {
        $doc_array[$line[0]][] = $line[1];
    }

    # display table
    print "<p>\n";
    $state_name = get_state_name($state_id);

    /*For PUB editor role heading*/
    
   /*To display AUTH48-DONE entries with PUB Editor*/
    if ($editor_role == '4'){
       $state_name = '4PUB';
    }
    print <<<END
      <div>
          <table border="0">
            <tr>
              <td>{$state_name}:</td>
            <tr>
          </table>
      </div> 

END;

    if ($debug_a48_lib === true) {
         print("<h4>doc_array</h4><pre>");
         print_r($doc_array); print("\n");
         print("</pre>");
    }
  
    /*When state is AUTH48-DONE and Primary Editor */
    /*Then the AUTH48-DONE table should show entries which are only with Primary Editor*/
    /*So below script checks if the AUTH48-DONE has same enries with PUB Editor and removed*/
    /* those entries from the display table*/
    /* Special testing comments are not v*/

    if ($state_id == '23' and $editor_role == '1'){
  
        /*1) Get the PUB editor doc array ie doc in auth48-done and editor is PUB editor*/
         $PUBeditor_rfcs = check_PUBeditor_display('23');
         if ($PUBeditor_rfcs){
             if ($debug_a48_lib === true) {
                 print "This is the PUB EDITOR \n";
                 print_r($PUBeditor_rfcs);
                 print "<br>";
             }
         }

        /* 2)Get the legth of the original AUTH48-DONE array with Primary Editor*/

        foreach (array_keys($doc_array) as $key) {
        # get count of docs and sort the docs
          $count_array[] = count($doc_array[$key]);
        }

       /* 3) Iterate through each editor in the loop */
       for ($i = 0; $i < max($count_array); $i++) {
          foreach (array_keys($doc_array) as $key) {
             if ($debug_a48_lib === true) {
               print $key."<br>"; 
             }
       /* 3) Check if there is an entry for the same state with PUB editor ie 4 and  state id is 23 */
             if ($PUBeditor_rfcs){
                    if ($debug_a48_lib === true) {
                        print "Now my value is ".$doc_array[$key][$i]."<br>"  ; 
                    }             
       /* 4) If the entry has PUB Editor entry for AUTH48-DONE state then remove the entry from display of AUTH48-DONE so remove it from array*/
                if (in_array($doc_array[$key][$i],$PUBeditor_rfcs)){ 
                    if ($debug_a48_lib === true) {
                        print " that is present in the PUB Editor so Get out from the array\n";
                    }
                    unset($doc_array[$key][$i]);
                }else{
                       if ($debug_a48_lib === true) {
                           print " that is not preset in the PUB Editor so Stay in the array\n";
                       }
                } 
             } /*End if ($auth48_done_rfcs*/
         } /*End foreach (array_keys*/
      } /*End for ($i=0*/

      if ($debug_a48_lib === true) {
         print("<h4>doc_array after </h4><pre>");
         print_r($doc_array); print("\n");
         print("</pre>");
      }


      /*5) To remove the key entry from the AUTH48-DONE array if the doc are not present*/
       foreach ($doc_array as $key=>$value) {
          if ($value) {
              if ($debug_a48_lib === true) {
                  print "Present with AUTH48-DONE values"."<br>";
                  print "My key is ".$key ." and ";
                  print_r($value);
                  print "<br>";
              }
          }else {
              if ($debug_a48_lib === true) {
                 print "Not Present with AUTH48-DONE values"."<br>";
                 print "My key is ".$key ." and ";
                 print_r($value);
                 print "<br>";
              }
              unset($doc_array[$key]);
          }
       }
       
       if ($debug_a48_lib === true) {
         print("<h4>doc_array after unsetting empty entry</h4><pre>");
         print_r($doc_array); print("\n");
         print("</pre>");
       }
 
    }


    print '<table border="1" cellpadding="3">';
    foreach (array_keys($doc_array) as $key) {
        print "<th>" . $key . "</th>\n";
        # get count of docs and sort the docs
        $count_array[] = count($doc_array[$key]);
        sort($doc_array[$key]);
    }

 for ($i = 0; $i < max($count_array); $i++) {
        $class = ($i % 2) ? "odd" : "even";
        print "<tr class=\"$class\">\n";
        foreach (array_keys($doc_array) as $key) {
            $number = preg_replace('/^RFC/',"", $doc_array[$key][$i]);
            
            if (($number != "") OR ($number != NULL)){
               $draft_base = get_draft_name($number);
               $cid = get_cluster_id($pdo,$draft_base);
            }else {
               $cid = 0;
            }

            if ($cid == "0"){
                $cid = "";
            }else {
                $cid = '<a href="/cluster_info.php?cid='. htmlspecialchars($cid).'">'.htmlspecialchars($cid).'</a>';
                $cid = "(".$cid.")";
            }
            print '<td width="50" align="left"><a href="' . htmlspecialchars($_SERVER["REQUEST_URI"]) . '?docnum=' . htmlspecialchars($number) . '">' . htmlspecialchars($number) . '</a>&nbsp;'. $cid .'</td>';
        }
        print "</tr>\n";
    }


        
    print "</table>\n";
   

    return $row;
}

# Lookup the approvals for a document
function approvals_lookup($docnum) {
   global $debug_a48_lib;
   global $pdo;

   $doc_id = sprintf('RFC%04d',$docnum);
   $query = "
       SELECT p.app_id, u.a48_id, name, approved, approved_date, create_date
       FROM auth48s u, approvals p
       WHERE u.a48_id = p.a48_id
       AND u.`doc-id` = :doc_id";

   if ($debug_a48_lib === TRUE) {
       print("\n<!-- approvals_lookup: query=$query\n-->\n");
   }

   try {
       $stmt = $pdo->prepare($query);
       $stmt->bindParam('doc_id',$doc_id);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   }catch (PDOException $pe){
       error_log("Error processing : approvals_lookup", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   $count = $num_of_rows;
   switch($count) {
       case 0: 
           # This should not happen because we don't call this function
           # if the auth48s SELECT returns an empty set
           print("<p class=\"warning\" style=\"margin-left: 25%;\">No AUTH48 Approval records for RFC $docnum.</p>");
           break;
       default:
           $name_count = 0;
           approvals_table_header();
           $row = $stmt->fetch(PDO::FETCH_ASSOC);
           do {
               display_approvals_row($row, $name_count);
               $name_count++;
           } while ($row = $stmt->fetch(PDO::FETCH_ASSOC));
           print("\n<tr><td colspan=\"4\"><input type=\"hidden\" name=\"name_count\" value=\"$name_count\" /></td></tr>\n");
   }
   return $count;
}



function approvals_form_open($a48_row) {
     print<<<END
<form name="auth48_edit" action="auth48_edit.php" method="post" onsubmit="return validateForm(this);">
<table style="width: 850px; margin: auto;">
  <tr>
    <td>
       <label class="a48">ID:</label> {$a48_row['a48_id']}
       <input name="a48_id" type="hidden" value="{$a48_row['a48_id']}" />
    </td>
    <td>
      <input name="doc-id" type="hidden" value="{$a48_row['doc-id']}" />
      <label class="a48">Status:</label> 
      <input name="status" type="hidden" value="{$a48_row['status']}" />

END;

    switch ($a48_row['status']) {
     case 'active':
          print("Active");
          break;
     case 'completed':
          print("Completed");
          break;
     }
     print<<<END
    </td>
    <td>
      <label class="a48">Started:</label> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{$a48_row['start_date']}<br/>
      <label class="a48">Completed:</label> {$a48_row['completion_date']}
    </td>
  </tr>
  <tr>
     <td colspan="3">
       <input name="old_state_id" type="hidden" value="${a48_row['state_id']}"/>
       <label class="a48">State:</label> 
       <select size="2" name="state_id" id="state_id">
END;
      switch($a48_row['state_id']){
      case '2':
               $state_name_selected = get_state_name($a48_row['state_id']);
               $state_name_other = get_state_name(23); 
	       print("<option value=\"${a48_row['state_id']}\" selected>$state_name_selected</option>\n");
               print("<option value=\"23\">$state_name_other</option>\n");
               break;
      case '23':
               $state_name_selected = get_state_name($a48_row['state_id']);
               $state_name_other = get_state_name(2); 
	       print("<option value=\"${a48_row['state_id']}\" selected>$state_name_selected</option>\n");
               print("<option value=\"2\">$state_name_other</option>\n");
               break;
      }
     print<<<END
       </select>
     </td>
  </tr>
END;

}

function approvals_form_notes($notes) {
     print<<<END
  <tr>
    <td colspan="3">
      <label class="a48">Notes</label>
    </td>
  </tr>
  <tr>
     <td colspan="3">
       <textarea rows="8" cols="80" id="notes" name="notes">$notes</textarea>
       <input type="hidden" name="old_notes" id="old_notes" value="$notes" />
     </td>
   </tr>

END;
}

function approvals_form_submit() {
     print<<<END
  <tr>
    <td colspan="2">&nbsp;</td>
    <td style="text-align: center;"><input type="submit" value="Update Records" name="update" /></td>
  </tr>

END;
}

function approvals_form_close() {
     print<<<END
 </table>
</form>

END;
}

# Returns TRUE if the generated filename exists on the file system.
function xml_file_check($docnum) {
     $filepath = "/a/ftp/in-notes/authors/rfc$docnum.xml";
     return file_exists($filepath);
}

function approvals_files_row($draft, $docnum) {
     print<<<END
  <tr>
    <td colspan="4">
      <p><b>Files</b><br />
        ORIGINAL: <a href="https://www.ietf.org/id/{$draft}.txt">{$draft}.txt</a>
        EDITED: <a href="https://www.rfc-editor.org/authors/rfc{$docnum}.txt">rfc{$docnum}.txt</a>
        DIFF: <a href="https://www.rfc-editor.org/authors/rfc{$docnum}-diff.html">rfc{$docnum}-diff.html</a>
END;

     if (xml_file_check($docnum)) {
          print<<<END
<br />
XML SOURCE: <a href="https://www.rfc-editor.org/authors/rfc{$docnum}.xml">rfc{$docnum}.xml</a>
END;
     }

     print<<<END
      </p>
    </td>
  </tr>

END;
}

function document_external_link($docnum){

     print<<<END
  <tr>
    <td colspan="4">
      <p><b>External link</b> : <a href="http://www.rfc-editor.org/auth48/rfc{$docnum}">rfc{$docnum}</a>
      </p>
    </td>
  </tr>

END;

}

function approvals_table_header() {
     print<<<END
    <tr>
      <th>Name</th>
      <th>Approved?</th>
      <th>Date of Approval</th>
    </tr>

END;
}

# Display one row from the approvals table.
function display_approvals_row($row, $num) {
     print<<<END
  <tr>
    <td>
      <input type="hidden" name="app_id_$num" id="app_id_$num" value="{$row['app_id']}" />
      <input type="hidden" name="old_name_$num" id="old_name_$num" value="{$row['name']}" />
      <input type="hidden" name="old_approved_{$num}" id="old_approved_{$num}" value="{$row['approved']}" />
      <input type="text" size="60" name="name_$num" id="name_$num" value="{$row['name']}" /></td>

END;
     switch ($row['approved']) {
     case 'yes':
          display_approved_yes($num);
          break;
     default:
          display_approved_no($num);
          break;
     }
     $app_date_id = "app_date_$num";
     print<<<END
          <td>
            <input type="hidden" name="old_$app_date_id" value="{$row['approved_date']}" />
            <input type="text" id="$app_date_id" name="app_date_$num" size="10" value="{$row['approved_date']}" />
            <input type="button" value="Set to Today" onclick="setAppDate('$app_date_id');" />
            <input type="button" value="Clear" onclick="clearAppDate('$app_date_id');"/>
          </td>
  </tr>

END;

}

function display_approved_yes($num) {
     print<<<END
    <td>
      <input type="radio" checked name="approved_$num" value="yes" /> Yes
      <input type="radio" name="approved_$num" value="no" /> No
    </td>

END;
}

function display_approved_no($num) {
     print<<<END
    <td>
      <input type="radio" name="approved_$num" value="yes" /> Yes
      <input type="radio" checked name="approved_$num" value="no" /> No
    </td>

END;
}

# Add the input line for a new approver.
function new_approver_row() {
     print<<<END
  <tr>
    <td><input type="text" size="60" id="new_approver" name="new_approver" /></td>
    <td>
      <input type="radio" name="new_approved" value="yes" /> Yes 
      <input type="radio" name="new_approved" value="no" /> No &nbsp;&nbsp;
    </td>
    <td>&nbsp;</td>
  </tr>

END;
}

function display_cluster_message($draft) {
    global $debug_a48_lib;
    global $pdo;

    $cid = get_cluster_id($pdo,$draft);
    if ($debug_a48_lib === true) {
         print("<h4>display_cluster_message</h4><pre>");
         print_r($draft); print("\n");
         print_r($cid); print("\n");
         print("</pre>");
    }

    if (is_string($cid) && (preg_match('/^C\d+/',$cid) == 1)){
        print("<tr><td colspan=\"4\">");
        print("<h4>** This document is part of ");
        print("<a href=\"/cluster_info.php?cid=$cid\">Cluster $cid</a>");
        print(", so may have additional holds before publication.</h4>\n");
        print("</td</tr>\n");
    }
}

function get_state_name($state_id){
   global $debug_a48_lib;
   global $pdo;
 
   $query = "
       SELECT s.state_id, s.state_name 
       FROM states s
       WHERE s.state_id = :state_id";

   if ($debug_a48_lib === TRUE) {
       print("\n<!-- get_state_name: query=$query\n-->\n");
   }  

   try {  
       $stmt = $pdo->prepare($query);
       $stmt->bindParam('state_id',$state_id);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
         error_log("Error processing : get_state_name", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   $row = $stmt->fetch(PDO::FETCH_ASSOC);
   $state_name = $row['state_name'];

   return $state_name;

}

function get_ad_name($draft){
   global $debug_a48_lib;
   global $pdo;

   $query = "SELECT a.area_director_name 
       FROM  area a, working_group w, `index` i 
       WHERE i.draft=:draft 
       AND i.source=w.wg_name 
       AND w.area_name=a.area_name"; 

   if ($debug_a48_lib === TRUE) {
       print("\n<!-- get_ad_name: query=$query\n-->\n");
   }

   try {
       $stmt = $pdo->prepare($query);
       $stmt->bindParam('draft',$draft);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   }catch (PDOException $pe){
       error_log("Error processing : get_ad_name", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   if ($num_of_rows > 0){
       $row = $stmt->fetch(PDO::FETCH_ASSOC);
       $ad_name = $row['area_director_name'];
   }
   return $ad_name;

}

#Get the draft base name from the docif
function get_draft_name($doc_id){

   global $debug_a48_lib;
   global $pdo;
   $doc_id = sprintf('RFC%04d',$doc_id);
   
   $query= "
       SELECT `internal_key`,`DRAFT` 
       FROM `index` i
       WHERE i.`doc-id` = :doc_id";

   try {
       $stmt = $pdo->prepare($query);
       $stmt->bindParam('doc_id',$doc_id);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   }catch (PDOException $pe){
       error_log("Error processing : get_draft_name", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   if ($debug_a48_lib === TRUE) {
       print("\n<!-- get_draft_name: query=$query\n-->\n");
   }

   #$count = $num_of_rows;
   switch($num_of_rows) {
       case 0:
           print("<p style=\"margin-left: 25%;\">No record found for document ID $doc_id.</p>");
           break;
       default:               
           $row = $stmt->fetch(PDO::FETCH_ASSOC);
           $key = $row['internal_key'];
           $draft = $row['DRAFT'];
	   $draft_base = strip_number($draft);
   }

   return $draft_base;
}

#Get iesg_contact_name
function get_iesg_contact($doc_id){
   global $debug_a48_lib;
   global $pdo;
   
   $query= "
       SELECT `iesg_contact`
       FROM `index` i
       WHERE i.`doc-id` = :doc_id";

   try {
       $stmt = $pdo->prepare($query);
       $stmt->bindParam('doc_id',$doc_id);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   }catch (PDOException $pe){
       error_log("Error processing : get_iesg_contact", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   if ($debug_a48_lib === TRUE) {
       print("\n<!-- get_iesg_contact: query=$query\n-->\n");
   }

   #$count = $num_of_rows;
   if ($num_of_rows > 0) {
       $row = $stmt->fetch(PDO::FETCH_ASSOC);
       $iesg_contact = $row['iesg_contact'];
   }

   return $iesg_contact;
}

#Get source name
                
function get_source_name($doc_id){
   global $debug_a48_lib;
   global $pdo;
        
   $query= "
       SELECT `source`
       FROM `index` i
       WHERE i.`doc-id` = :doc_id";

   try { 
       $stmt = $pdo->prepare($query);
       $stmt->bindParam('doc_id',$doc_id);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   }catch (PDOException $pe){
       error_log("Error processing : get_source_name", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
     
   if ($debug_a48_lib === TRUE) {
       print("\n<!-- get_source_contact: query=$query\n-->\n");
   }

   #$count = $num_of_rows;
   if ($num_of_rows > 0) {
       $row = $stmt->fetch(PDO::FETCH_ASSOC);
       $source = $row['source'];
   }

   return $source;
}

?>
