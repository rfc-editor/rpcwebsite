<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
# $Id: editor_lib.php,v 1.2 2017/01/11 20:53:08 priyanka Exp $
# --------------------------------------------------------------------------- #
# These functions support the additional fields representing editor
# assignments.
# --------------------------------------------------------------------------- #


#Constants used for the editor log additions.
define('PRIMARY_EDITOR',1); // MUST match role_key value in editor_roles tbl.
define('COPY_EDITOR',2);    // MUST match role_key value in editor_roles tbl.
define('RFC_EDITOR',3);    // MUST match role_key value in editor_roles tbl.

$default_editor = 'XX';     // That is, no editor!
#$editor_field_names = array('primary_editor','copy_editor','rfc_editor');
$editor_field_names = array('copy_editor','primary_editor','rfc_editor');

/*$editor_map = array('primary_editor' => PRIMARY_EDITOR, 
                    'copy_editor' => COPY_EDITOR,
		    'rfc_editor' => RFC_EDITOR);
*/
$editor_map = array('copy_editor' => COPY_EDITOR,
		    'primary_editor' => PRIMARY_EDITOR, 
		    'rfc_editor' => RFC_EDITOR);

# fields for editor views
$editor_search = array("prim_ed","copy_ed","rfc_ed");
# map the HTML search fields to columns in the VIEWs of editor_assignments table
$editor_fields = array("prim_ed" => "p.initials", "copy_ed" => "c.initials", "rfc_ed" => "r.initials");

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
     $query =
          "SELECT assign_id FROM editor_assignments
           WHERE doc_key  = $doc_key AND
                 role_key = $role_key
           ORDER BY update_date DESC, create_date DESC LIMIT 1";

     $assign_id = 0;

     $db_link = open_db();
     if (($result = mysqli_query($db_link,$query))) {
          $count = mysqli_num_rows($result);
          if ($count == 1) {
               $row = mysqli_fetch_assoc($result);
               $assign_id = $row['assign_id'];
          }
          mysqli_free_result($result);
     } else {
          $assign_id = -1;
          error_log("get_assignment_id: database error: " . mysqli_error($db_link) . ": $query");
     }
     close_db();
     return $assign_id;
}

# Insert a new assignment, returns true on success, else false.
function insert_assignment($doc_key, $role_key, $initials) {
     $insert = 
          "INSERT INTO editor_assignments (doc_key, role_key, initials)
           VALUES ($doc_key, $role_key, '$initials')";

     $status = false;
     $db_link = open_db();

     if (mysqli_query($db_link,$insert)) {
          $status = true;
     } else {
          error_log("insert_assignment: database error: " . 
                    mysqli_error($db_link) . 
                    ": \$insert=$insert");
     }
     close_db();
     return $status;
}

# Update an existing, returns true on success, else false.
function update_assignment($assign_id, $initials) {
     $update =
          "UPDATE editor_assignments SET initials='$initials'
           WHERE assign_id = $assign_id";

     $status = false;
     $db_link = open_db();

     if (mysqli_query($db_link,$update)) {
          $status = true;
     } else {
          error_log("insert_assignment: database error: " . 
                    mysqli_error($db_link) . 
                    ": \$update=$update");
     }
     close_db();
     return $status;

}

# Use the internal_key to retrieve the initials of the primary editor assigment
function get_primary_editor($doc_key) {
     global $default_editor;
     $query = 
          "SELECT initials AS 'PE' FROM editor_assignments
           WHERE doc_key=$doc_key AND role_key=" . PRIMARY_EDITOR . 
          " ORDER BY update_date DESC, create_date DESC LIMIT 1";

     $pe = $default_editor;
     $db_link = open_db();
     if (($result = mysqli_query($db_link,$query))) {
          $count = mysqli_num_rows($result);
          if ($count == 1) {
               $row = mysqli_fetch_assoc($result);
               $pe = $row['PE'];
          }
          mysqli_free_result($result);
     } else {
          error_log("get_primary_editor: database error: " . mysqli_error($db_link) . ": $query");
     }
     return $pe;
}

# Use the internal_key to retrieve the initials of the copy editor assignment.
function get_copy_editor($doc_key) {
     global $default_editor;

     $query = 
          "SELECT initials AS 'CE' FROM editor_assignments
           WHERE doc_key=$doc_key AND role_key=" . COPY_EDITOR . 
          " ORDER BY update_date DESC, create_date DESC LIMIT 1";

     $ce = $default_editor;
     $db_link = open_db();
     if (($result = mysqli_query($db_link,$query))) {
          $count = mysqli_num_rows($result);
          if ($count == 1) {
               $row = mysqli_fetch_assoc($result);
               $ce = $row['CE'];
          }
          mysqli_free_result($result);
     } else {
          error_log("get_copy_editor: database error: " . mysqli_error($db_link) . ": $query");
     }
     return $ce;
}

# Use the internal_key to retrieve the initials of the rfc editor assignment.
function get_rfc_editor($doc_key) {
     global $default_editor;

     $query = 
          "SELECT initials AS 'RE' FROM editor_assignments
           WHERE doc_key=$doc_key AND role_key=" . RFC_EDITOR . 
          " ORDER BY update_date DESC, create_date DESC LIMIT 1";

     $ce = $default_editor;
     $db_link = open_db();
     if (($result = mysqli_query($db_link,$query))) {
          $count = mysqli_num_rows($result);
          if ($count == 1) {
               $row = mysqli_fetch_assoc($result);
               $ce = $row['RE'];
          }
          mysqli_free_result($result);
     } else {
          error_log("get_rfc_editor: database error: " . mysqli_error($db_link) . ": $query");
     }
     return $ce;
}



# Return a associative array of the editor assignments for a document
function get_editor_assignments($doc_key) {
     $assignments['primary_editor'] = get_primary_editor($doc_key);
     $assignments['copy_editor']    = get_copy_editor($doc_key);
     $assignments['rfc_editor']    = get_rfc_editor($doc_key);

     return $assignments;
}

# Populate the editor_roles variables.
function get_editor_roles() {
     global $editor_roles;

     $query = "SELECT role_code FROM editor_roles ORDER BY role_key";
     if (($str = open_db())) rfc_fail($str);
     if ( ($result = mysqli_query($str,$query))) {
          while ( $line = mysqli_fetch_row($result))
               $editor_roles[] = trim($line[0]);
     }
     close_db();
}

# Populate the editors variable; use for validation, not for a menu
function get_editors($assignable_only=true) {
     global $editors;

     $query = "SELECT initials FROM editors";
     if ($assignable_only) {
          $query .= " WHERE assignable='yes'";
     }

     if (($str = open_db())) rfc_fail($str);
     if ( ($result = mysqli_query($str,$query))) {
          while ( $line = mysqli_fetch_row($result))
               $editors[] = trim($line[0]);
     }
     close_db();
}


?>
