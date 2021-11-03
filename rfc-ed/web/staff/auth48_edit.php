<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
# $Id: auth48_edit.php,v 2.9 2021/06/23 05:11:48 priyanka Exp $
# AUTH48 edit forms.
/* November 2020 : Modified the script to use PDO prepared statements - PN*/
/* February 2021 : Added the changes for awaiting_ad_approval flag - PN */
/* June 2021 : Modified the script for server upgrade - PN                            */
session_start();
include("header.php");
include("auth48_lib.php");

$debug_auth48 = FALSE;
#$debug_auth48 = TRUE;

##############################################################################
# Routines implementing logic for this page when called to store updates.    #
##############################################################################

# Examine input for a new approver. Add if so. If an approval value is
# included, use it if 'yes.'
function new_approver_check($data) {
     global $a48_id, $debug_auth48;
     $awaiting_ad_approval = 0;  
     
     if ($debug_auth48 === TRUE) {
          print("<!--\nnew_approver={$data['new_approver']}\n");
          if (array_key_exists('new_approved', $data)) {
               print("new_approved={$data['new_approved']}\n-->\n");
          } else {
               print("new_approved key not in input array\n-->\n");
          }
     }

     $status = TRUE;
     if (array_key_exists('new_approver', $data) &&
         strlen(trim($data['new_approver'])) > 0) {
          $name = substr($data['new_approver'],0,MAX_APPROVER_NAME);
          $status = add_approver($a48_id, $name);
# When new approver is added to Approval table check if the entry begins with 'AD -'
# if that is the case then as we know the default approval flag is 'no' so now set the
# awaiting AD approval flag to 'Yes' in auth48_s table.

          if (preg_match("/^AD - /",$name)) { 
             $awaiting_ad_approval = 1; 
          }
     }
     
     if (array_key_exists('new_approved', $data)) {
          $value = substr($data['new_approved'],0,MAX_APPROVED_ENUM);
# The default is 'no' so only make call if pre-approved
          if (strcmp($value, 'yes') == 0) {
              #CASE 1 : 'AD -' John Smith and approved = 'yes' so awaiting_ad_approval not set
              $status = set_approval($a48_id,$name,$value);
          } else { #CASE 2 : 'AD -' John Smith and approved = 'no' so awaiting_ad_approval = 'yes'
                 if ($awaiting_ad_approval == '1'){
                   $status   = update_awaiting_ad_approval($a48_id,$awaiting_ad_approval);
                 }
          }
     } else { # CASE 3 : 'AD -' John Smith and approved = unselected so default value 'no so awaiting_ad_approval = 'yes'
                if ($awaiting_ad_approval == '1'){
                   $status   = update_awaiting_ad_approval($a48_id,$awaiting_ad_approval);
                }
     }
     
     return $status;
}

# Examine input for new notes and call for update if necessary
function new_notes_check($data) {
     global $a48_id, $debug_auth48;

     $status = TRUE;
     if (strcmp($data['old_notes'], $data['notes']) != 0) {
          $status = update_notes($a48_id, $data['notes']);
     }
     return $status;
}

# Examine input for new notes and call for update if necessary
function new_status_check($data) {
     global $a48_id, $debug_auth48;

     $status = TRUE;
     if (strcmp($data['old_status'], $data['status']) != 0) {
          $status = update_status($a48_id, $data['status']);
     }
     return $status;
}

function update_approvals_check($data) {
     $status = TRUE;
     $name_count = $_POST['name_count'];
     for ($counter=0; $counter < $name_count; $counter++) {
          $old_approved = $data["old_approved_$counter"];
          $approved = $data["approved_$counter"];
          if (strcmp($approved, $old_approved) != 0) {
               $status = update_approved($data["app_id_$counter"], $approved);
          }
          if ($status === FALSE) {
               break;
          }
     }
     return $status;
}

function update_names_check($data) {
     global $a48_id;
     $status = TRUE;
     $name_count = $_POST['name_count'];
     for ($counter=0; $counter < $name_count; $counter++) {
          $old_name = $data["old_name_$counter"];
          $name = $data["name_$counter"];
          if (strcmp($name, $old_name) != 0) {
               $status = update_name($data["app_id_$counter"], $name);
#Check if the name is begin with AD : and approved flag is no then set the 
#awaiting ad approval flag to yes
#Once 'AD -' is added in front of name it is not removed so not checking this case for
#old name 
               if (preg_match("/^AD - /",$name)) {
                  $approved = $data["approved_$counter"];
                  if ($approved == "no") {
                     $awaiting_ad_approval = 'yes';
                     $status   = update_awaiting_ad_approval($a48_id,$awaiting_ad_approval);
                  } elseif ($approved == 'yes'){
                     $awaiting_ad_approval = 'no';
                     $status   = update_awaiting_ad_approval($a48_id,$awaiting_ad_approval);
                  }
              } 
          } else /*When old name and new name is same and begins with 'AD -' */{
              if (preg_match("/^AD - /",$name)) {
                  $approved = $data["approved_$counter"];
                  if ($approved == "no") {
                     $awaiting_ad_approval = 'yes';
                     $status   = update_awaiting_ad_approval($a48_id,$awaiting_ad_approval);
                  } elseif ($approved == 'yes'){
                     $awaiting_ad_approval = 'no';
                     $status   = update_awaiting_ad_approval($a48_id,$awaiting_ad_approval);
                  }
              }        

          }
          
          if ($status === FALSE) {
               break;
          }
     }
     return $status;    
}

function update_approved_dates_check($data) {
     $status = TRUE;
     $name_count = $_POST['name_count'];
     for ($counter=0; $counter < $name_count; $counter++) {
          $old_approved_date = substr($data["old_app_date_$counter"], 0, MAX_DATE_STRING);
          $approved_date = substr($data["app_date_$counter"], 0, MAX_DATE_STRING);
          if (strcmp($approved_date, $old_approved_date) != 0) {
               if ($approved_date == NULL || strlen(trim($approved_date)) == 0) {
                    $status = clear_approved_date($data["app_id_$counter"]);
               } else {
                    $status = update_approved_date_app_id($data["app_id_$counter"], $approved_date);
               }
          }
          if ($status === FALSE) {
               break;
          }
     }
     return $status;
}
function update_state_check($data){
     global $debug_auth48;
     $status = TRUE;

     if ($debug_auth48 === TRUE) {
          print("update_state_check new state is={$data['state_id']}\n");
          print("update_state_check old state is={$data['old_state_id']}\n");

     }

     if ($data['old_state_id'] != $data['state_id']){
         $status = update_state($data['doc-id'], $data['state_id']);
        /* if ($status === FALSE) {
               break;
          }
        */
     }
     return $status;
}

# examine and filter the POST input
if (isset($_POST) && array_key_exists('docnum', $_POST)) {
     # we have a query for a new document record
     $docnum = substr($_POST['docnum'], 0, MAX_RFC_DIGIT);
     if (!is_numeric($docnum)) { $docnum = NULL; }
} elseif (isset($_GET['docnum'])) {
     $docnum = substr($_GET['docnum'], 0, MAX_RFC_DIGIT);
     if (!is_numeric($docnum)) { $docnum = NULL; }
} else {
     $docnum = NULL;
}

# If the a48_id is set, we have a reloaded record. Check for what changed and
# update as appropriate before re-reading the record.
if (isset($_POST) && array_key_exists('a48_id', $_POST)) {
     $a48_id = $_POST['a48_id'];
     if (!is_numeric($a48_id)) { $a48_id = 0; }
     # set $docnum to get lookup by stripping number from doc-id
     $docnum = substr($_POST['doc-id'],3);
     if (!is_numeric($docnum)) { $docnum = NULL; }
     new_approver_check($_POST);
     new_notes_check($_POST);
#No Need to capture Status change 
#     new_status_check($_POST);
##
     update_approvals_check($_POST);
     update_names_check($_POST);
     update_approved_dates_check($_POST);
     update_state_check($_POST);
}

# pick a title for the generated page
switch ($docnum) {
case NULL:
     rfc_header("AUTH48 Tracking");
     break;
default:
     rfc_header("RFC " . $docnum . " AUTH48 Tracking", "auth48.js");
     break;
}

if ($debug_auth48 === TRUE) {
     if (isset($_POST)) {
          print("\$_POST<br /><pre>");
          print_r($_POST);
          print("</pre>");
     }
}

query_form(); #always show this form

if ($docnum != NULL) {
     approvals_display_form($docnum);
} else {
     show_auth48_list(2);
     show_auth48_list(23);
     show_auth48_list(23,4);
}

print<<<END
<script type="text/javascript">
<!--
          document.forms.auth48_query.docnum.focus();
//-->
</script>

END;
rfc_footer();

?>
