<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
# $Id: cluster_support_lib.php,v 1.2 2017/01/11 20:53:08 priyanka Exp $
# Routines related to cluster management for use in the work-flow web
# application. (Modeled after the Perl library in bin.)

require_once("db.php"); // required but should already be loaded.

//$debug_csl = TRUE; set in edit_lib.php, where loaded.

############################################
# Functions to be used by external callers #
############################################

# Use the REF field to setup a cluster. Check to see if any of the drafts in
# the REF field already have a cluster_id. Use it for all if so, else create
# a new one and add the rest to it.
function assign_cluster($fields) {
     global $debug_csl;

     $status = TRUE;
     $refs = explode(',',scrub_refs_db($fields['ref']));
     $draft_base = strip_number($fields['draft']);

     if ($debug_csl === TRUE) {
          print("<p>assign_cluster: draft='{$fields['draft']}' ref='{$fields['ref']}'</p>");
          print("\n<pre>\n");
          var_dump($draft_base);
          print("\n---------------------\n");
          var_dump($refs);
          print("\n</pre>\n");
     }

     $existing = find_existing_cluster($draft_base, $refs);
     if (!is_bool($existing)) {
          // Expected return is an associative array with the found cid
          // and the draft string that hit.
          if ($existing['cid'] == "XX") {
               // Didn't find anything, create with primary draft
               $existing['cid'] = create_cluster($draft_base);
               $existing['draft'] = $draft_base;
          }
          // Now add any refs not in that cluster to the cluster.
          foreach ($refs as $ref) {
               $ref_cid = get_cluster_id($ref);
               if ($ref_cid === FALSE) {
                    $status = FALSE;
                    break;
               }
               if ((preg_match('/^0/',$ref_cid)) == 1) {
                    if ($debug_csl === TRUE) print("<pre>$ref not in a cluster</pre>");
                    $status=add_to_cluster($existing['cid'],$ref);
                    if (!check_draft_received($ref)) {
                         set_anchored_unknown($ref);
                    }
               } elseif ((preg_match('/^C\d+/',$ref_cid)) == 1) {
                    if ($debug_csl === TRUE) print("<pre>$ref in cluster $ref_cid</pre>");
                    if ((strcmp($ref_cid,$existing['cid'])) != 0) {
                         error_log("attempt to give $draft_base references in multiple clusters!");
                         error_log("$draft_base: reference $ref in $ref_cid; {$existing['draft']} in {$existing['cid']}");
                         print("<p class='error'>Attempt to give $draft_base references in multiple clusters!<br />");
                         print("$draft_base: reference $ref in $ref_cid; {$existing['draft']} in {$existing['cid']}</p>");
                         $status = FALSE;
                         break;
                    }
               }
          }
          // If the CID came from an existing reference record, add the draft
          // to the cluster because it isn't a new cluster.
          if ($status && strcmp($draft_base,$existing['draft']) != 0) {
               $status = add_to_cluster($existing['cid'],$draft_base);
          }
          set_anchored_yes($draft_base); // draft with reference always anchored
     } else {
          $status = $existing; // propagate error condition up
     }

     if ($debug_csl === TRUE) {
          print("<p>assign_cluster: returning $status</p>");
     }
     return $status;
}

# Modify a cluster. Use the new data to overwrite old, delete any old
# references that are no longer in the references list.
# Called with:
#     $ndata: associative array of new, changed data
#     $odata: associative array of the old data
# NOTE: Arrays contain full records, not all is used.
function modify_cluster($ndata, $odata) {
     global $debug_csl;

     $draft = $ndata['draft'];
     $draft_base = strip_number($draft);
     $old_refs = explode(',',scrub_refs_db($odata['ref']));
     $new_refs = explode(',',scrub_refs_db($ndata['ref']));
     $cid = $ndata['cid'];

     if ($debug_csl === TRUE) {
          print("<h2>modify_cluster</h2><pre>\n");
          print('$cid        =' . print_r($cid, TRUE) . "\n");
          print('$draft      =' . print_r($draft, TRUE) . "\n");
          print('$draft_base =' . print_r($draft_base, TRUE) . "\n");
          print("\n\$new_refs:\n" . print_r($new_refs, TRUE));
          print("from {$ndata['ref']}\n");
          print("\n\$old_refs:\n" .  print_r($old_refs, TRUE));
          print("from {$odata['ref']}\n");
          print("\ncount of \$new_refs: " . count($new_refs));
          print("\ncount of \$old_refs: " . count($old_refs));
          print("</pre>\n");
     }

     $status = TRUE;

     if ($ndata['ref'] == NULL || strlen(trim($ndata['ref'])) == 0) {
          // the REF list was completely removed. Reset anchored and
          // process clustered drafts.
          if ($debug_csl === TRUE) {
               print("<p>Looks like $draft is loosing all references, calling set_anchored_no()</p>");
          }
          $status = set_anchored_no($draft);
     } elseif ($cid == NULL || strlen(trim($cid)) == 0) {
          // This draft wasn't in a cluster to start with, treat like
          // an add_draft call.
          if ($debug_csl === TRUE) {
               print("<p>Looks like $draft is newly assigned a cluster. calling assign_cluser()</p>");
          }
          return assign_cluster($ndata);
     } else {
          set_anchored_yes($draft); // we have references now
          // Something is different about the REF string, look for new drafts
          // to add to the cluster.
          for ($i = 0; $i < count($new_refs); $i++) {
               if ($debug_csl === TRUE) {
                    print("<pre>checking new ref {$new_refs[$i]}\n</pre>\n");
               }

               $cur_cid = get_cluster_id($new_refs[$i]);
               if ($debug_csl === TRUE) {
                    print("<pre>new ref {$new_refs[$i]} in cluster $cur_cid\n</pre>\n");
                    var_dump($cur_cid);
               }
               if (strcmp($cur_cid, $cid) == 0) {
                    if ($debug_csl === TRUE) {
                         print("<pre>new ref {$new_refs[$i]} already in $cid\n</pre>\n");
                    }
                    continue;
               } elseif (preg_match('/^C\d+/',$cur_cid) == 1) {
                    // This is a case where the user is trying to add a reference to
                    // a document that is already a reference to another document in
                    // another cluster. This "merge" is not yet supported.
                    error_log("$draft reference {$new_refs[$i]} cannot be added to cluster $cid because already in $cur_cid");
                    print<<<END
<p class='error'>
$draft reference {$new_refs[$i]} already assigned to cluster $cur_cid.
Cannot add reference to cluster $cid throught this application.
</p>
<p class='error'>Error log entry written.</p>

END;
/* 
It isn't clear how to proceed from here. Attempt to rollback all? (Not
easy given where the SQL calls are.) Or complete reset of references? The
fix at this point is to manually reset ref for this draft and re-run 
format_queue2.pl.
*/
                    $status = FALSE;
                    break;
/*
                    continue;
*/
               }

               if (preg_match("/^0/",$cur_cid) == 1) {
                    $status = add_to_cluster($cid,$new_refs[$i]);
                    if (!check_draft_received($new_refs[$i])) {
                         set_anchored_unknown($new_refs[$i]);
                    }
               } else {
                    $status = update_cluster($cur_cid,$new_refs[$i]);
                    $ref_tmp = get_references($new_refs[$i]);
                    if (count($ref_tmp) == 0) {
                         set_anchored_no($new_refs[$i]);
                    }
               }
               if ($status === FALSE) break;
          }

          if ($debug_csl === TRUE) {
               print("<pre>Completed processing of new refs\n</pre>\n");
          }
     }

     // Here we process the "old refs" data to remove any drafts no longer
     // needed for this cluster.
     if ($status == TRUE) {
          // build map of who uses whom
          $cluster_members = get_cluster_members($cid);
          $cluster_map = array();
          foreach ($cluster_members as $member) {
               if ($debug_csl === TRUE) print("<pre>Processing cluster member $member</pre>");
               if (strcmp($member,$draft_base) == 0) {
                    $cluster_map[$member] = $new_refs;
                    if ($debug_csl === TRUE) {
                         print("<pre>added references " . print_r($new_refs, TRUE) .
                               " for $member</pre>");
                    }
                    continue;   // database not updated yet, use caller data
               }
               // build the references list for all members of the cluster
               $these_references = get_references($member);
               if ($these_references != NULL && count($these_references) > 0) {
                    $cluster_map[$member] = $these_references;
                    if ($debug_csl === TRUE) {
                         print("<pre>added references " . print_r($these_references, TRUE) .
                               " for $member</pre>");
                    }
               } else {
                    if ($debug_csl === TRUE) {
                         print("<pre>skipped references " . print_r($these_references, TRUE) .
                               " for $member</pre>");
                    }                    
               }
          }

          if ($debug_csl === TRUE) {
               print("<pre>\$cluster_map\n" . print_r($cluster_map,TRUE) . "</pre>\n");
          }

          for ($i = 0; $i < count($old_refs); $i++) {
               if (strlen(trim($old_refs[$i])) == 0) {
                    continue;
               }
               if ($debug_csl === TRUE) {
                    print("<pre>checking \$old_refs[$i]: {$old_refs[$i]}</pre>");
               }

               if (in_array($old_refs[$i],$new_refs)) {
                    if ($debug_csl === TRUE) {
                         print("<pre>found old ref {$old_refs[$i]} in \$new_refs</pre>");
                    }
                    continue;
               }

               $found_old_ref = FALSE;
               if (array_key_exists($old_refs[$i], $cluster_map)) {
                    if ($debug_csl === TRUE) print("<pre>\t{$old_refs[$i]} is a key in cluster</pre>");
#                    $ref_tmp = $cluster_map[$old_refs[$i]];
                    foreach ($cluster_map[$old_refs[$i]] as $aref) {
#                         print("<pre>reference for {$old_refs[$i]} is $aref\n");
#                         print("length of string=" . strlen($aref) . "\n");
#                         print("trimed length=" . strlen(trim($aref)) . "</pre>");
                         if (strlen(trim($aref)) > 0) {
                              if ($debug_csl === TRUE)
                                   print("<pre>\t{$old_refs[$i]} is not empty: " .
                                         print_r($cluster_map[$old_refs[$i]], TRUE) .
                                         "</pre>");
                              $found_old_ref = TRUE;
                              break;
                         }
                    }
               }

               if (!$found_old_ref) {
                    // search in other reference lists
                    foreach ($cluster_map as $key => $value) {
                         if ($value == '' || (strlen(trim($value)) == 0)) {
                              if ($debug_csl === TRUE) print("<pre>\tempty value for key $key</pre>");
                              continue;
                         }
                         if ($debug_csl === TRUE) 
                              print("<pre>\tchecking $key list\n" . print_r($value, TRUE) .
                                    " for {$old_refs[$i]}</pre>");
                         if (in_array($old_refs[$i],$value)) {
                              $found_old_ref = TRUE;
                              if ($debug_csl === TRUE) {
                                   print("<pre>\tfound old ref {$old_refs[$i]} in references for $key</pre>");
                              }
                              break;
                         }
                    }
               }

               if (!$found_old_ref) {
                    if ($debug_csl === TRUE) {
                         print("<pre>old ref {$old_refs[$i]} not found anywhere!</pre>");
                    }
                    $status = delete_cluster_member($old_refs[$i]);
               }
               if ($debug_csl === TRUE) print("<pre>bottom \$old_refs loop: \$i=$i\t\$status='$status'</pre>\n");
               if ($status === FALSE) break;
          }

          if ($debug_csl === TRUE) {
               print("<pre>Completed processing of old refs</pre>");
          }

          if (strlen(trim($ndata['ref'])) == 0 ) {
               if ($debug_csl === TRUE) print("<pre>empty ref column: check if $draft_base still cluster member</pre>");
               $found_draft_ref = FALSE;
               foreach ($cluster_map as $key => $value) {
                    if (in_array($draft_base,$value)) {
                         $found_draft_ref = TRUE;
                         if ($debug_csl === TRUE) {
                              print("<pre>found draft $draft_base in references for $key</pre>");
                         }
                         break;
                    }
               }
               if (!$found_draft_ref) {
                    if ($debug_csl === TRUE) {
                         print("<pre>draft $draft_base not found anywhere!</pre>");
                    }
                    $status = delete_cluster_member($draft_base);
               }
          } else {
               if ($debug_csl === TRUE) print("<pre>non-null ref column: $draft_base still clustter member</pre>");
          }
     }

     if ($debug_csl === TRUE) {
          print("<p>modify_cluster returning '" . ($status == TRUE?"TRUE":"FALSE") . "'</p>");
     }

     return $status;
}

# Function that checks if a draft is in any cluster and if it is, it changes
# the anchored flag to 'no'. This is used when a previously not received
# document is entered in the queue and did not have any references.
function new_no_references_update($fields) {
     global $debug_csl;

     if ($debug_csl === TRUE) {
          print("<pre>new_no_references_updates:\n");
          print_r($fields);
          print("</pre>");
     }

     $old_cid = get_cluster_id($fields['draft']);
     if ($debug_csl === TRUE) print("<pre>new_no_references_update: found cid $old_cid</pre>");
     if (is_string($old_cid) && (preg_match('/^C\d+/',$old_cid) == 1)) {
          return set_anchored_no($fields['draft']);
     } elseif (is_bool($old_cid)) {
          if ($debug_csl === TRUE) print("<pre>new_no_references_update: passing $old_cid condition up</pre>");
          return $old_cid; // propagate error condition up
     }

     if ($debug_csl === TRUE) print("<p>new_no_references_update: returning true</p>");
     return true;
}

#########################
# Supporting  Functions #
#########################

# Strip the trailing dash and digits or pound signs from a draft
# name.
function strip_number($draft) {
     return $draft = preg_replace('/-(\d\d|\#\#)$/',"", $draft);
}

# The refs field of the DB has some nonsense values in it that need
# to be ignored.  This takes a raw refs field from the DB and erases
# bad values.
function scrub_refs_db($refs) {
     $refs = preg_replace('/\s/', '', $refs);
     $refs = preg_replace('/^NULL(\+N)?$/', '', $refs);
     $refs = preg_replace('/\+[A-Z]+/','', $refs);
     return $refs;
}

# Lookup a draft in the clusters table and return the cluster_id if found.
# Returns cluster_id string or 0 if not in table.
# A FALSE return indicates a database error.
function get_cluster_id($draft) {
     global $debug_csl;

     $draft_base = strip_number($draft);

     $query = "SELECT cluster_id FROM clusters where draft_base='$draft_base'";

     if ($debug_csl === TRUE) {
          print("<p>get_cluster_id: query=$query</p>");
          print("\n<pre>\n");
          var_dump($draft);
          print("\n</pre>\n");
     }

     $db_link = open_db();
     if (($result = mysqli_query($db_link,$query)) !== FALSE) {
          switch ($count = mysqli_num_rows($result)) {
          case 0:
               $cid = '0';
               break;
          case 1:
               $row = mysqli_fetch_row($result);
               $cid = $row[0];
               break;
          default:
               $row = mysqli_fetch_row($result);
               $cid = $row[0];
               error_log ("WARNING: get_cluster_id returned multiple rows for draft '$draft_base'");
               break;
          }
          mysqli_free_result($result);
     } else {
          $cid = FALSE;
          error_log("get_cluster_id: database error: " . mysqli_error($db_link) . ": $query");
     }
     close_db();

     if ($debug_csl === TRUE) {
          print("<p>get_cluster_id: returning cluster id '$cid'</p>");
     }

     return $cid;
}

# Some of the drafts included in the REF field are for documents that have not
# been received by the RFC Editor. This function checks that a draft has a
# record in the index table.
# Returns TRUE if the index record exists; FALSE if it does not.
# Returns -1 if a database error occurs.
function check_draft_received($draft) {
     global $debug_csl;

     $draft_base = strip_number($draft);
     $query = 
        "SELECT internal_key FROM `index` WHERE draft LIKE '$draft_base-__'";

     if ($debug_csl === TRUE) {
          print("<p>check_draft_received: query=$query<br />");
     }

     $db_link = open_db();
     if (($result = mysqli_query($db_link,$query)) !== FALSE) {
          switch ($count = mysqli_num_rows($result)) {
          case 0:
               $received = FALSE;
               break;
          case 1:
               $received = TRUE;
               break;
          default:
               $received = TRUE;
               error_log ("WARNING: check_draft_received returned multiple rows for draft '$draft_base'");
               break;
          }
          mysqli_free_result($result);
     } else {
          $received = -1;
          error_log("check_draft_received: database error: " . mysqli_error($db_link) . ": $query");
     }

     close_db();
     if ($debug_csl === TRUE) {
          print("check_draft_received: returning '$received'</p>");
     }
     return $received;
}

# Update a record with a new cluster id.
function update_cluster($new_cid, $draft) {
     global $debug_csl;

     $draft_base = strip_number($draft);
     $update = 
          "UPDATE clusters SET cluster_id='$new_cid' WHERE draft_base='$draft_base'";

     if ($debug_csl === TRUE) {
          print("<p>update_cluster: update=$update <br />");
     }

     $status = FALSE;
     $db_link = open_db();
     if (mysqli_query($db_link,$update) !== FALSE) {
          $status = TRUE;
     } else {
          error_log("update_cluster: database error: " .
                    mysqli_error($db_link) . ": update='$update'");
     }

     close_db();
     if ($debug_csl === TRUE) {
          print("update_cluster: returning '$status'</p>");
     }
     return $status;
}

# Set the anchored column to 'yes' for a draft. A 'yes' indicates that the
# document can only move forward with the rest of the cluster.
# Returns TRUE on success, else FALSE.
function set_anchored_yes($draft) {
     global $debug_csl;

     $draft_base = strip_number($draft);
     $update =
          "UPDATE clusters SET anchored='yes' WHERE draft_base='$draft_base'";

     if ($debug_csl === TRUE) {
          print("<p>set_anchored_yes: update=$update<br />");
     }

     $status = FALSE;
     $db_link = open_db();
     if (mysqli_query($db_link,$update) !== FALSE) {
          $status = TRUE;
     } else {
          error_log("set_anchored_yes: database error: " .
                    mysqli_error($db_link) . ": update='$update'");
     }

     close_db();
     if ($debug_csl === TRUE) {
          print("set_anchored_yes: returning '$status'</p>");
     }
     return $status;
}

# Set the anchored column to 'no' for a draft. A 'no' indicates that the
# document can move forward without the rest of the cluster. This is the
# default, but not received documents could need an 'unknown' switched.
# Returns TRUE on success, else FALSE.
function set_anchored_no($draft) {
     global $debug_csl;

     $draft_base = strip_number($draft);
     $update =
          "UPDATE clusters SET anchored='no' WHERE draft_base='$draft_base'";

     if ($debug_csl === TRUE) {
          print("<p>set_anchored_no: update=$update<br />");
     }

     $status = FALSE;
     $db_link = open_db();
     if (mysqli_query($db_link,$update) !== FALSE) {
          $status = TRUE;
     } else {
          error_log("set_anchored_no: database error: " .
                    mysqli_error($db_link) . ": update='$update'");
     }

     close_db();
     if ($debug_csl === TRUE) {
          print("set_anchored_no: returning '$status'</p>");
     }
     return $status;
}

# Set the anchored column to 'unknown' for a draft. An 'unkown' marks a
# document as not received and therefore with an unkown anchor.
function set_anchored_unknown($draft) {
     global $debug_csl;

     $draft_base = strip_number($draft);
     $update =
          "UPDATE clusters SET anchored='unknown' WHERE draft_base='$draft_base'";

     if ($debug_csl === TRUE) {
          print("<p>set_anchored_unknown: update=$update<br />");
     }

     $status = FALSE;
     $db_link = open_db();
     if (mysqli_query($db_link,$update) !== FALSE) {
          $status = TRUE;
     } else {
          error_log("set_anchored_yes: database error: " .
                    mysqli_error($db_link) . ": update='$update'");
     }

     close_db();
     if ($debug_csl === TRUE) {
          print("set_anchored_unknown: returning '$status'</p>");
     }
     return $status;
}

# Add a document to an existing cluster.
# Returns TRUE on success, else FALSE.
function add_to_cluster($cid, $draft) {
     global $debug_csl;

     if ($debug_csl === TRUE)  print("<p>add_to_cluster: $cid, $draft<br />");

     $draft_base = strip_number($draft);
     $insert =
          "INSERT INTO clusters (cluster_id, draft_base) VALUES ('$cid', '$draft_base')";

     if ($debug_csl === TRUE) {
          print("add_to_cluster: insert=$insert<br />");
     }

     $status = FALSE;
     if (strlen(trim($draft_base)) == 0) {
          error_log("add_to_cluster: can't add empty draft to cluster $cid");
          return $status;
     }     
     $db_link = open_db();
     if (mysqli_query($db_link,$insert) !== FALSE) {
          $status = TRUE;
     } else {
          error_log("add_to_cluster: database error: " .
                    mysqli_error($db_link) . ": insert='$insert'");
     }

     close_db();
     if ($debug_csl === TRUE) {
          print("add_to_cluster: returning '$status'</p>");
     }
     return $status;
}

# Create a new cluster using $draft as the first document.
# Returns the cluster_id on success, else boolean FALSE.
function create_cluster($draft) {
     global $debug_csl;

     if ($debug_csl === TRUE ) print("<p>create_cluster: $draft<br />");

     $draft_base = strip_number($draft);

     //Get the next cluster_id from the function
 
     $next_cluster_id =  get_next_cluster_id();
 
     $next_cluster_id = "C".$next_cluster_id;

     $insert = 
          "INSERT INTO clusters (cluster_id, draft_base) 
           VALUES ('$next_cluster_id', '$draft_base')";

     if ($debug_csl === TRUE) {
          print("create_cluster: insert=$insert<br />");
     }

     $cid = FALSE;
     if (strlen(trim($draft)) == 0) {
          error_log("add_to_cluster: can't add empty draft to cluster $cid");
          return $cid;
     }     

     $db_link = open_db();
     if (mysqli_query($db_link,$insert)) {
          $cid = get_cluster_id($draft_base);
     } else {
          error_log("create_cluster: database error: " .
                    mysqli_error($db_link) . ": insert='$insert'");
     }

     close_db();
     if ($debug_csl === TRUE) {
          print("create_cluster: returning cluster id '$cid'</p>");
     }
     return $cid;
}

function get_next_cluster_id(){
    global $debug_csl;
    
    $next_cluster_id = 0;

    $counter_update = "UPDATE counters SET value = value + 1 WHERE name = 'clusters'";

     if ($debug_csl === TRUE) {
          print("<p>get_next_cluster_id:counter update=$counter_update <br />");
     }

     $counter_status = FALSE;
     $db_link = open_db();
     if (mysqli_query($db_link,$counter_update) !== FALSE) {
          $counter_status = TRUE;
     } else {
          error_log("get_next_cluster counter update : database error: " .
                    mysqli_error($db_link) . ":counter update='$counter_update'");
     }

      if ($debug_csl === TRUE) {
          print("ge_next_cluster_id: returning '$counter_status'</p>");
     }
     
     if ($counter_status === TRUE){
         $select_counter = 
            "SELECT `value` FROM `counters` WHERE name = 'clusters'";

         if ($debug_csl === TRUE) {
          print("<p>get_next_cluster_id: query=$select_counter<br />");
         }

         if (($result_counter = mysqli_query($db_link,$select_counter)) !== FALSE) {
              $row = mysqli_fetch_row($result_counter);
              if ($row != NULL) {
                       $next_cluster_id  = $row[0];
               }
               
            mysqli_free_result($result_counter);
          } else {
             error_log("get_next_cluster_id: database error: " . mysqli_error($db_link) . ": $select_counter\n");
          }

          close_db();

        
       
     }

 return $next_cluster_id;

}

# Query the database to check on the existence of a cluster ID
# Returns TRUE if it exists else FALSE.
function cluster_exists($cid) {
     global $debug_csl;

     $query = 
          "SELECT cluster_id FROM clusters WHERE cluster_id='$cid'";

     if ($debug_csl === TRUE) {
          print("<p>cluster_exists: query=$query<br />");
     }

     $is_cid = FALSE;
     $db_link = open_db();
     if (($result = mysqli_query($db_link,$query))) {
          switch ($count = mysqli_num_rows($result)) {
          case 0:
               $is_cid = FALSE;
               break;
          default:
               $is_cid = TRUE;
               break;
          }
          mysqli_free_result($result);
     } else {
          error_log("get_cluster_id: database error: " . mysqli_error($db_link) . ": $query");
     }

     close_db();
     if ($debug_csl === TRUE) {
          print("cluster_exists: returning '$is_cid'</p>");
     }
     return $is_cid;
}

# Search the database for an existing cluster using the indicated draft and
# its references.
function find_existing_cluster($draft, $refs) {
     global $debug_csl;
     $matching = array('cid' => 'XX', 'draft' => "NONE");

     if ($debug_csl === TRUE) {
          print("<h3>find_existing_cluster</h3><p>$draft refs=" . implode(",",$refs)."</p>");
          print("\n<pre>\n");
          var_dump($draft);
          print("\n---------------------\n");
          var_dump($refs);
          print("\n</pre>\n");
     }

     $cid = get_cluster_id($draft);
     if (is_string($cid) && (preg_match('/^C\d+/',$cid) == 1)) {
          if ($debug_csl === TRUE) print("<p>find_existing_cluster: draft $draft matched cid='$cid'</p>");
          $matching['cid'] = $cid;
          $matching['draft'] = $draft;
     } elseif (is_bool($cid)) {
          print("<p class='error'>find_existing_cluster: error cid='$cid'</p>");
          $match = FALSE; // propagate error condition up
     } else {
          if ($debug_csl === TRUE) print("<p>find_existing_cluster: search refs because draft cid='$cid'</p>");
          foreach ($refs as $ref) {
               $cid = get_cluster_id($ref);
               if (is_string($cid) && (preg_match('/^C\d+/',$cid) == 1)) {
                    if ($debug_csl === TRUE) print("<p>find_existing_cluster: draft $ref matched cid='$cid'</p>");
                    $matching['cid'] = $cid;
                    $matching['draft'] = $ref;
                    break;
               }
          }
     }

    if ($debug_csl === TRUE) {
         print("<pre>find_existing_cluster: returning match\n");
         print_r($matching);
         print("</pre>");
     }

    return $matching;
}

# Remove a record from the table, thereby removing a document from a cluster.
function delete_cluster_member($draft) {
     global $debug_csl;

     $draft_base = strip_number($draft);
     $delete =
          "DELETE FROM clusters WHERE draft_base = '$draft_base'";

     if ($debug_csl === TRUE) {
          print("<p>delete_cluster_member: $draft</p>");
          print("\n<pre>\n");
          var_dump($delete);
          print("\n</pre>\n");
     }

     $status = FALSE;
     $db_link = open_db();
     if (mysqli_query($db_link,$delete)) {
          $status = TRUE;
     } else {
          error_log("delete_cluster_member: database error: " .
                    mysqli_error($db_link) . ": delete=<$delete>");
     }

     if ($debug_csl === TRUE) { print("<p>delete_cluster_member($draft) returning $status</p>"); }
     return $status;
}

# Retrieve the references for a draft
function get_references($draft) {
     global $debug_csl;

     $draft_base = strip_number($draft);
     $query = 
        "SELECT ref FROM `index` WHERE draft LIKE '$draft_base-__'";

     if ($debug_csl === TRUE) {
          print("<h4>get_references</h4><pre>query=$query\n");
     }

     $db_link = open_db();
     if (($result = mysqli_query($db_link,$query)) !== FALSE) {
          switch ($count = mysqli_num_rows($result)) {
          case 0:
               // Not in queue
               break;
          case 1:
               $row = mysqli_fetch_row($result);
               if ($row != NULL) {
                    $ref = explode(',',scrub_refs_db($row[0]));
               }
               break;
          default:
               $row = mysqli_fetch_row($result);
               if ($row != NULL) {
                    $ref = explode(',',scrub_refs_db($row[0]));
               }
               error_log ("WARNING: get_references returned multiple rows for draft '$draft_base'");
               break;
          }
          mysqli_free_result($result);
     } else {
          error_log("get_references: database error: " . mysqli_error($db_link) . ": $query\n");
     }

     close_db();
     if ($debug_csl === TRUE) {
          $ref_count = count($ref);
          print("get_references: returning ref\n" . print_r($ref,TRUE) . "$ref_count drafts from $count rows</pre>");
     }
     return $ref;
}

# Retrieve the documents in a cluster
function get_cluster_members($cid) {
     global $debug_csl;

     $query =
          "SELECT draft_base FROM clusters WHERE cluster_id='$cid'";

     if ($debug_csl === TRUE) {
          print("<h5>get_cluster_members</h5><pre>query=$query\n");
     }

     $db_link = open_db();
     if (($result = mysqli_query($db_link,$query)) !== FALSE) {
          while ($row = mysqli_fetch_row($result)) {
               if ($debug_csl === TRUE) { print("\tadding $row[0]\n"); }
               $members[] = $row[0];
          }
          mysqli_free_result($result);
     } else {
          error_log("get_references: database error: " . mysqli_error($db_link) . ": $query\n");
     }

     close_db();
     if ($debug_csl === TRUE) {
          print("get_cluster_members: returning \$members\n" . print_r($members,TRUE) . "</pre>");
     }
     return $members;
}
?>
