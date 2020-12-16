<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
# $Id: editor_lib.php,v 2.4 2020/11/11 01:03:17 priyanka Exp $
# --------------------------------------------------------------------------- #
# These functions support the additional fields representing editor
# assignments.
/* November 2020 : Modified the script to use PDO prepared statements - PN   */
# --------------------------------------------------------------------------- #


#Constants used for the editor log additions.
define('PRIMARY_EDITOR',1); // MUST match role_key value in editor_roles tbl.
define('COPY_EDITOR',2);    // MUST match role_key value in editor_roles tbl.
define('RFC_EDITOR',3);    // MUST match role_key value in editor_roles tbl.
define('PUB_EDITOR',4);    // MUST match role_key value in editor_roles tbl.

$default_editor = 'XX';     // That is, no editor!
#$editor_field_names = array('primary_editor','copy_editor','rfc_editor');
$editor_field_names = array('copy_editor','primary_editor','rfc_editor','pub_editor');

/*$editor_map = array('primary_editor' => PRIMARY_EDITOR, 
                    'copy_editor' => COPY_EDITOR,
		    'rfc_editor' => RFC_EDITOR);
*/
$editor_map = array('copy_editor' => COPY_EDITOR,
		    'primary_editor' => PRIMARY_EDITOR, 
		    'rfc_editor' => RFC_EDITOR,
                    'pub_editor' => PUB_EDITOR);


# fields for editor views
$editor_search = array("prim_ed","copy_ed","rfc_ed","pub_ed");
# map the HTML search fields to columns in the VIEWs of editor_assignments table
$editor_fields = array("prim_ed" => "p.initials", "copy_ed" => "c.initials", "rfc_ed" => "r.initials","pub_ed" => "pub.initials");

# Use the data in the record to create or update an editor assignment
# record. Return true on success, else false.
function set_editor_assignments($record) {
     global $editor_field_names, $editor_map, $default_editor;

     # Use the editor_field_names array to drive the selection of
     # data from the record and editor_map for either insert or
     # update of database record.
     foreach ($editor_field_names as $role) {
          $initials = strtoupper($record[$role]);
          if ($initials == null) { $initials = $default_editor; }
          $role_key = $editor_map[$role];
          if (!($status = set_editor($record['internal_key'],
                                     $role_key,
                                     $initials))) {
               break;
          }
     }

     return $status;
}

# Set or update a record for an editor assignment
function set_editor($doc_key, $role_key, $initials) {

     $status = true;
     $assign_id = get_assignment_id($doc_key,$role_key);

     switch ($assign_id) {
     case -1:
          print("<p class=\"error\">Database error. See error log for details.</p>\n");
          $status = false;
          break;
     case 0:
          $status = insert_assignment($doc_key, $role_key, $initials);
          break;
     default:
          $status = update_assignment($assign_id, $initials);
          break;
     }

     return $status;
}

# Return assignment ID of the existing assignment record for a
# particular document and editor role. Or a 0 if nothing exists.
function get_assignment_id($doc_key, $role_key) {
   global $pdo;
   $assign_id = 0;
   $query =
       "SELECT assign_id FROM editor_assignments
        WHERE doc_key  = :doc_key AND
              role_key = :role_key
        ORDER BY update_date DESC, create_date DESC LIMIT 1";
   
   try {  
       $stmt = $pdo->prepare($query);
       $stmt->bindParam('doc_key',$doc_key);
       $stmt->bindParam('role_key',$role_key);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : get_assignment_id", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
       $assign_id = -1;
   }

   $count = $num_of_rows;
   if ($count == 1) {
       $row = $stmt->fetch(PDO::FETCH_ASSOC);
       $assign_id = $row['assign_id'];
   }
   return $assign_id;
}

# Insert a new assignment, returns true on success, else false.
function insert_assignment($doc_key, $role_key, $initials) {
   global $pdo;
   
   $insert = 
          "INSERT INTO editor_assignments (doc_key, role_key, initials)
           VALUES (:doc_key, :role_key, :initials)";

   try {  
       $stmt = $pdo->prepare($insert);
       $stmt->bindParam('doc_key',$doc_key);
       $stmt->bindParam('role_key',$role_key);
       $stmt->bindParam('initials',$initials);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : insert_assignment", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   $status = false;

   if ($num_of_rows > 0) {
       $status = true;
   } else {
       error_log("insert_assignment: " . 
                    ": \$insert=$insert");
       }
     return $status;
}

function update_assignment($assign_id, $initials) {
   global $pdo;

   $update =
       "UPDATE editor_assignments SET initials=:initials
        WHERE assign_id = :assign_id";

   try {  
       $stmt = $pdo->prepare($update);
       $stmt->bindParam('initials',$initials);
       $stmt->bindParam('assign_id',$assign_id);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : update_assignment", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   $status = false;

   if ($num_of_rows > 0) {
       $status = true;
   } else {
       error_log("update_assignment:" . 
                    ": \$update=$update");
       }
   return $status;

}

# Use the internal_key to retrieve the initials of the primary editor assignment.
function get_primary_editor($doc_key) {
   global $default_editor;
   global $pdo;  
   
   $query = 
          "SELECT initials AS 'PE' FROM editor_assignments
           WHERE doc_key=:doc_key AND role_key=:role_key 
          ORDER BY update_date DESC, create_date DESC LIMIT :limit_no";

   try {  
       $stmt = $pdo->prepare($query);
       $stmt->bindParam('doc_key',$doc_key);
       $role_key = PRIMARY_EDITOR;
       $stmt->bindValue('role_key',$role_key);
       $stmt->bindValue('limit_no',1);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : get_primary_editor", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   $pe = $default_editor;
   $count = $num_of_rows;
   if ($count == 1) {
       $row = $stmt->fetch(PDO::FETCH_ASSOC);
       $pe = $row['PE'];
   }
   return $pe;
}


# Use the internal_key to retrieve the initials of the copy editor assignment.
function get_copy_editor($doc_key) {
   global $default_editor;
   global $pdo;

   $query =
          "SELECT initials AS 'CE' FROM editor_assignments
           WHERE doc_key=:doc_key AND role_key=:role_key
           ORDER BY update_date DESC, create_date DESC LIMIT :limit_no";

   try {  
       $stmt = $pdo->prepare($query);
       $stmt->bindParam('doc_key',$doc_key);
       $role_key = COPY_EDITOR;
       $stmt->bindValue('role_key',$role_key);
       $stmt->bindValue('limit_no',1);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : get_copy_editor", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   $ce = $default_editor;
   $count = $num_of_rows;
   if ($count == 1) {
       $row = $stmt->fetch(PDO::FETCH_ASSOC);
       $ce = $row['CE'];
   }
   return $ce;
}



# Use the internal_key to retrieve the initials of the rfc editor assignment.
function get_rfc_editor($doc_key) {
   global $default_editor;
   global $pdo;
   
   $query = 
        "SELECT initials AS 'RE' FROM editor_assignments
         WHERE doc_key=:doc_key AND role_key=:role_key 
         ORDER BY update_date DESC, create_date DESC LIMIT :limit_no";

   try {  
       $stmt = $pdo->prepare($query);
       $stmt->bindParam('doc_key',$doc_key);
       $role_key = RFC_EDITOR;
       $stmt->bindValue('role_key',$role_key);
       $stmt->bindValue('limit_no',1);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : get_rfc_editor", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   $ce = $default_editor;

   $count = $num_of_rows;
   if ($count == 1) {
       $row = $stmt->fetch(PDO::FETCH_ASSOC);
       $ce = $row['RE'];
   }
   return $ce;
}

# Use the internal_key to retrieve the initials of the pub editor assignment.
function get_pub_editor($doc_key) {
   global $default_editor;
   global $pdo;

   $query = 
        "SELECT initials AS 'PUB' FROM editor_assignments
         WHERE doc_key=:doc_key AND role_key=:role_key 
         ORDER BY update_date DESC, create_date DESC LIMIT :limit_no";

   try {  
       $stmt = $pdo->prepare($query);
       $stmt->bindParam('doc_key',$doc_key);
       $role_key = PUB_EDITOR;
       $stmt->bindValue('role_key',$role_key);
       $stmt->bindValue('limit_no',1);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : get_pub_editor", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   $ce = $default_editor;
   $count = $num_of_rows;
   if ($count == 1) {
       $row = $stmt->fetch(PDO::FETCH_ASSOC);
       $ce = $row['PUB'];
   }
   return $ce;
}

# Return a associative array of the editor assignments for a document
function get_editor_assignments($doc_key) {
     $assignments['primary_editor'] = get_primary_editor($doc_key);
     $assignments['copy_editor']    = get_copy_editor($doc_key);
     $assignments['rfc_editor']    = get_rfc_editor($doc_key);
     $assignments['pub_editor']    = get_pub_editor($doc_key);
  
     return $assignments;
}

function get_editor_roles() {
   global $editor_roles;
   global $pdo;

   $query = "SELECT role_code FROM editor_roles ORDER BY role_key";
   try {  
       $stmt = $pdo->prepare($query);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : get_editor_roles", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
   
   if  ($num_of_rows > 0) {
       while ( $line = $stmt->fetch(PDO::FETCH_NUM)){
           $editor_roles[] = trim($line[0]);
       }
   }
}

# Populate the editors variable; use for validation, not for a menu
function get_editors($assignable_only=true) {
   global $editors;
   global $pdo;

   $query = "SELECT initials FROM editors";
   if ($assignable_only) {
       $query .= " WHERE assignable=:assignable";
   }

   try {  
       $stmt = $pdo->prepare($query);
       if ($assignable_only) {
           $stmt->bindValue('assignable','yes');
       }
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : get_editors", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
 
   if ($num_of_rows > 0){
       while ( $line = $stmt->fetch(PDO::FETCH_NUM) )
           $editors[] = trim($line[0]);
   }
}



?>
