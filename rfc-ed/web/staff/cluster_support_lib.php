<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
# $Id: cluster_support_lib.php,v 2.3 2020/11/11 01:03:17 priyanka Exp $
# Routines related to cluster management for use in the work-flow web
# application. (Modeled after the Perl library in bin.)
/* November 2020 : Modified the script to use PDO prepared statements - PN                                 */
############################################
# Functions to be used by external callers #
############################################

# Use the REF field to setup a cluster. Check to see if any of the drafts in
# the REF field already have a cluster_id. Use it for all if so, else create
# a new one and add the rest to it.
function assign_cluster($pdo,$fields) {
     $status = TRUE;
     $refs = explode(',',scrub_refs_db($fields['ref']));
     $draft_base = strip_number($fields['draft']);

     $existing = find_existing_cluster($pdo,$draft_base, $refs);
     if (!is_bool($existing)) {
          // Expected return is an associative array with the found cid
          // and the draft string that hit.
          if ($existing['cid'] == "XX") {
               // Didn't find anything, create with primary draft
               $existing['cid'] = create_cluster($pdo,$draft_base);
               $existing['draft'] = $draft_base;
          }
          // Now add any refs not in that cluster to the cluster.
          foreach ($refs as $ref) {
               $ref_cid = get_cluster_id($pdo,$ref);
               if ($ref_cid === FALSE) {
                    $status = FALSE;
                    break;
               }
               if ((preg_match('/^0/',$ref_cid)) == 1) {
                    $status=add_to_cluster($pdo,$existing['cid'],$ref);
                    if (!check_draft_received($pdo,$ref)) {
                         set_anchored_unknown($pdo,$ref);
                    }
               } elseif ((preg_match('/^C\d+/',$ref_cid)) == 1) {
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
               $status = add_to_cluster($pdo,$existing['cid'],$draft_base);
          }
          set_anchored_yes($pdo,$draft_base); // draft with reference always anchored
     } else {
          $status = $existing; // propagate error condition up
     }
     return $status;
}

# Modify a cluster. Use the new data to overwrite old, delete any old
# references that are no longer in the references list.
# Called with:
#     $ndata: associative array of new, changed data
#     $odata: associative array of the old data
# NOTE: Arrays contain full records, not all is used.
function modify_cluster($pdo,$ndata, $odata) {
     $draft = $ndata['draft'];
     $draft_base = strip_number($draft);
     $old_refs = explode(',',scrub_refs_db($odata['ref']));
     $new_refs = explode(',',scrub_refs_db($ndata['ref']));
     $cid = $ndata['cid'];

     $status = TRUE;

     if ($ndata['ref'] == NULL || strlen(trim($ndata['ref'])) == 0) {
          // the REF list was completely removed. Reset anchored and
          // process clustered drafts.
          $status = set_anchored_no($pdo,$draft);
     } elseif ($cid == NULL || strlen(trim($cid)) == 0) {
          // This draft wasn't in a cluster to start with, treat like
          // an add_draft call.
          return assign_cluster($pdo,$ndata);
     } else {
          set_anchored_yes($pdo,$draft); // we have references now
          // Something is different about the REF string, look for new drafts
          // to add to the cluster.
          for ($i = 0; $i < count($new_refs); $i++) {

               $cur_cid = get_cluster_id($pdo,$new_refs[$i]);
          
               if (strcmp($cur_cid, $cid) == 0) {
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
                    $status = add_to_cluster($pdo,$cid,$new_refs[$i]);
                    if (!check_draft_received($pdo,$new_refs[$i])) {
                         set_anchored_unknown($pdo,$new_refs[$i]);
                    }
               } else {
                    $status = update_cluster($pdo,$cur_cid,$new_refs[$i]);
                    $ref_tmp = get_references($pdo,$new_refs[$i]);
                    if (count($ref_tmp) == 0) {
                         set_anchored_no($pdo,$new_refs[$i]);
                    }
               }
               if ($status === FALSE) break;
          }
         
     }

     // Here we process the "old refs" data to remove any drafts no longer
     // needed for this cluster.
     if ($status == TRUE) {
          // build map of who uses whom
          $cluster_members = get_cluster_members($pdo,$cid);
          $cluster_map = array();
          foreach ($cluster_members as $member) {
               if (strcmp($member,$draft_base) == 0) {
                    $cluster_map[$member] = $new_refs;
                            continue;   // database not updated yet, use caller data
               }
               // build the references list for all members of the cluster
               $these_references = get_references($pdo,$member);
               if ($these_references != NULL && count($these_references) > 0) {
                    $cluster_map[$member] = $these_references;
               } else {
                                   
               }
          }

          for ($i = 0; $i < count($old_refs); $i++) {
               if (strlen(trim($old_refs[$i])) == 0) {
                    continue;
               }

               if (in_array($old_refs[$i],$new_refs)) {
                    continue;
               }

               $found_old_ref = FALSE;
               if (array_key_exists($old_refs[$i], $cluster_map)) {
                    foreach ($cluster_map[$old_refs[$i]] as $aref) {
                         if (strlen(trim($aref)) > 0) {
                              $found_old_ref = TRUE;
                              break;
                         }
                    }
               }

               if (!$found_old_ref) {
                    // search in other reference lists
                    foreach ($cluster_map as $key => $value) {
                         if ($value == '' || (strlen(trim($value)) == 0)) {
                              continue;
                         }
                         if (in_array($old_refs[$i],$value)) {
                              $found_old_ref = TRUE;
                              break;
                         }
                    }
               }

               if (!$found_old_ref) {
                    $status = delete_cluster_member($pdo,$old_refs[$i]);
               }
               if ($status === FALSE) break;
          }

          if (strlen(trim($ndata['ref'])) == 0 ) {
               $found_draft_ref = FALSE;
               foreach ($cluster_map as $key => $value) {
                    if (in_array($draft_base,$value)) {
                         $found_draft_ref = TRUE;
                         break;
                    }
               }
               if (!$found_draft_ref) {
                    $status = delete_cluster_member($pdo,$draft_base);
               }
          } else {
          }
     }
     return $status;
}

# Function that checks if a draft is in any cluster and if it is, it changes
# the anchored flag to 'no'. This is used when a previously not received
# document is entered in the queue and did not have any references.
function new_no_references_update($pdo,$fields) {
     $old_cid = get_cluster_id($pdo,$fields['draft']);
     if (is_string($old_cid) && (preg_match('/^C\d+/',$old_cid) == 1)) {
          return set_anchored_no($pdo,$fields['draft']);
     } elseif (is_bool($old_cid)) {
          return $old_cid; // propagate error condition up
     }
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
function get_cluster_id($pdo,$draft) {
  $draft_base = strip_number($draft);
   try {
        $sql = "SELECT cluster_id FROM clusters where draft_base=:draft_base";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam('draft_base',$draft_base);
        $stmt->execute();
        $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
         error_log("Error processing : returning cluster id", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   switch ($num_of_rows) {
   case 0:
       $cid = '0';
       break;
   case 1:
       $row = $stmt->fetch(PDO::FETCH_NUM);
       $cid = $row[0];
       break;
   default:
       $row = $stmt->fetch(PDO::FETCH_NUM);
       $cid = $row[0];
       error_log ("WARNING: get_cluster_id returned multiple rows for draft '$draft_base'");
       break;
   }

   $pdo = null;
   return $cid;
}



# Some of the drafts included in the REF field are for documents that have not
# been received by the RFC Editor. This function checks that a draft has a
# record in the index table.
# Returns TRUE if the index record exists; FALSE if it does not.
# Shows if a database error occurs.

function check_draft_received($pdo,$draft) {
     $draft_base = strip_number($draft);
     try {
         $sql = "SELECT internal_key FROM `index` where draft LIKE CONCAT(:draft_base, '%') ";
         $stmt = $pdo->prepare($sql);
         $stmt->bindParam('draft_base',$draft_base);
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
     } catch (PDOException $pe){
         error_log("Error processing : check draft received", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }
     
     switch ($num_of_rows) {
         case 0:
                $received = FALSE;
                break;
         case 1:
                $received = TRUE;
                break;
         default:
                $received = TRUE;
                error_log ("WARNING: check_draft_receive returned multiple rows for draft '$draft_base'");
                break;
     }

     $pdo = null;

     return $received;
}


# Update a record with a new cluster id.
function update_cluster($pdo,$new_cid, $draft) {

     $draft_base = strip_number($draft);

     $status = FALSE;

     try {
         $sql = "UPDATE clusters SET cluster_id = :new_cid where draft_base = :draft_base";
         $stmt = $pdo->prepare($sql);
         $stmt->bindParam('new_cid',$new_cid);
         $stmt->bindParam('draft_base',$draft_base);
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
    } catch (PDOException $pe){
         error_log("Error processing : update cluster", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }

    if ($num_of_rows > 0){
       $status = TRUE;
     }else {
       $status = FALSE;
     }
     
     $pdo = null;  

     return $status;
}


# Set the anchored column to 'yes' for a draft. A 'yes' indicates that the
# document can only move forward with the rest of the cluster.
# Returns TRUE on success, else FALSE.
function set_anchored_yes($pdo,$draft) {

     $draft_base = strip_number($draft);

     $status = FALSE;
     $anchored = 'yes';

     try {
         $sql = "UPDATE clusters SET anchored = :anchored where draft_base = :draft_base";
         $stmt = $pdo->prepare($sql);
         $stmt->bindParam('anchored',$anchored);
         $stmt->bindParam('draft_base',$draft_base);
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
    } catch (PDOException $pe){
         error_log("Error processing : set anchored yes", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }

    if ($num_of_rows > 0){
       $status = TRUE;
     }else {
       $status = FALSE;
     }
     
     $pdo = null;  

     return $status;
}


# Set the anchored column to 'no' for a draft. A 'no' indicates that the
# document can move forward without the rest of the cluster. This is the
# default, but not received documents could need an 'unknown' switched.
# Returns TRUE on success, else FALSE.
function set_anchored_no($pdo,$draft) {

     $draft_base = strip_number($draft);

     $status = FALSE;
     $anchored = 'no';

     try {
         $sql = "UPDATE clusters SET anchored = :anchored where draft_base = :draft_base";
         $stmt = $pdo->prepare($sql);
         $stmt->bindParam('anchored',$anchored);
         $stmt->bindParam('draft_base',$draft_base);
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
     } catch (PDOException $pe){
         error_log("Error processing : set anchored no", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }

     if ($num_of_rows > 0){
         $status = TRUE;
     }else {
       $status = FALSE;
     }
     
     $pdo = null;  

     return $status;
}





# Set the anchored column to 'unknown' for a draft. An 'unkown' marks a
# document as not received and therefore with an unkown anchor.
function set_anchored_unknown($pdo,$draft) {
     $draft_base = strip_number($draft);
     $status = FALSE;
     $anchored = 'unknown';

     try {
         $sql = "UPDATE clusters SET anchored = :anchored where draft_base = :draft_base";
         $stmt = $pdo->prepare($sql);
         $stmt->bindParam('anchored',$anchored);
         $stmt->bindParam('draft_base',$draft_base);
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
     } catch (PDOException $pe){
         error_log("Error processing : set anchored unknown", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }

     if ($num_of_rows > 0){
       $status = TRUE;
     }else {
       $status = FALSE;
     }
     
     $pdo = null;  

     return $status;

}

# Add a document to an existing cluster.
# Returns TRUE on success, else FALSE.
function add_to_cluster($pdo,$cid, $draft) {
     $draft_base = strip_number($draft);
   
     $status = FALSE;
     if (strlen(trim($draft_base)) == 0) {
          error_log("add_to_cluster: can't add empty draft to cluster $cid");
          return $status;
     }     
    
     try {
         $sql = "INSERT INTO clusters (cluster_id, draft_base) VALUES (:cid, :draft_base)";
         $stmt = $pdo->prepare($sql);
         $stmt->bindParam('cid',$cid);
         $stmt->bindParam('draft_base',$draft_base);
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
     } catch (PDOException $pe){
         error_log("Error processing : add to cluster", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }

    if ($num_of_rows > 0){
         $status = TRUE;
     }else {
         $status = FALSE;
     }
     
     $pdo = null;  

     return $status;

}


# Create a new cluster using $draft as the first document.
# Returns the cluster_id on success, else boolean FALSE.

function create_cluster($pdo,$draft) {

     $draft_base = strip_number($draft);

     //Get the next cluster_id from the function
 
     $next_cluster_id =  get_next_cluster_id($pdo);
 
     $next_cluster_id = "C".$next_cluster_id;

     $cid = FALSE;
     if (strlen(trim($draft)) == 0) {
          error_log("add_to_cluster: can't add empty draft to cluster $cid");
          return $cid;
     }

     try {
         $sql = "INSERT INTO clusters (cluster_id, draft_base) VALUE (:next_cluster_id,:draft_base)";
         $stmt = $pdo->prepare($sql);
         $stmt->bindParam('next_cluster_id',$next_cluster_id);
         $stmt->bindParam('draft_base',$draft_base);
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
     } catch (PDOException $pe){
         error_log("Error processing : create cluster", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }

     if ($num_of_rows > 0){
         $cid = get_cluster_id($pdo,$draft_base);
     }else {
           error_log("create_cluster: database error ");
     }
   
     $pdo = null;
     return $cid;
}

function get_next_cluster_id($pdo){
    
     $next_cluster_id = 0;
     $name = 'clusters';
     $counter_status = FALSE;
    
     try {
         $sql = "UPDATE counters SET value = value + 1 where name = :name";
         $stmt = $pdo->prepare($sql);
         $stmt->bindParam('name',$name);
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
    } catch (PDOException $pe){
         error_log("Error processing : get next cluster id", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }

     if ($num_of_rows > 0){
         $counter_status = TRUE;
     }else {
         $counter_status = FALSE;
     }
     

    if ($counter_status === TRUE){
         $select_counter = "SELECT `value` from  counters where name = :name";
         $stmt = $pdo->prepare($select_counter);
         $stmt->bindParam('name',$name);
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
   
         if ($num_of_rows > 0){
             $row = $stmt->fetch(PDO::FETCH_NUM);
             $next_cluster_id = $row[0];
         }else {
             error_log("get_next_cluster_id: database error");
         }
     }

     $pdo = null;
     return $next_cluster_id;

}

# Query the database to check on the existence of a cluster ID
# Returns TRUE if it exists else FALSE.
function cluster_exists($pdo,$cid) {
     $is_cid = FALSE;
     try {
         $sql = "SELECT cluster_id FROM clusters where cluster_id=:cid";
         $stmt = $pdo->prepare($sql);
         $stmt->bindParam('cid',$cid);
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
     } catch (PDOException $pe){
         error_log("Error processing : returning cluster exists", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }


     switch ($num_of_rows) {
     case 0:
            $is_cid = FALSE;
            break;
     default:
            $is_cid = TRUE;
            break;
   }

   $pdo = null;     
   return $is_cid;
}



# Search the database for an existing cluster using the indicated draft and
# its references.
function find_existing_cluster($pdo,$draft, $refs) {
     $matching = array('cid' => 'XX', 'draft' => "NONE");

     $cid = get_cluster_id($pdo,$draft);
     if (is_string($cid) && (preg_match('/^C\d+/',$cid) == 1)) {
          $matching['cid'] = $cid;
          $matching['draft'] = $draft;
     } elseif (is_bool($cid)) {
          print("<p class='error'>find_existing_cluster: error cid='$cid'</p>");
          $match = FALSE; // propagate error condition up
     } else {
          foreach ($refs as $ref) {
               $cid = get_cluster_id($pdo,$ref);
               if (is_string($cid) && (preg_match('/^C\d+/',$cid) == 1)) {
                    $matching['cid'] = $cid;
                    $matching['draft'] = $ref;
                    break;
               }
          }
     }

    return $matching;
}

# Remove a record from the table, thereby removing a document from a cluster.
function delete_cluster_member($pdo,$draft) {
     $draft_base = strip_number($draft);

     try {
          $delete = "DELETE FROM clusters WHERE draft_base = :draft_base";
          $stmt = $pdo->prepare($delete);
          $stmt->bindParam('draft_base',$draft_base);
          $stmt->execute();
          $num_of_rows = $stmt->rowCount();
     } catch (PDOException $pe){
          error_log("Error processing : delete cluster member", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }


     if ($num_of_rows > 0){
         $status = TRUE;
     }else {
         $status = FALSE;
     }
     
     $pdo = null;  

     return $status;
}

# Retrieve the references for a draft
function get_references($pdo,$draft) {
     $draft_base = strip_number($draft);
     try {
         $sql = "SELECT ref FROM `index` where draft LIKE CONCAT(:draft_base, '%') ";
         $stmt = $pdo->prepare($sql);
         $stmt->bindParam('draft_base',$draft_base);
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
     } catch (PDOException $pe){
         error_log("Error processing : get references", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }

     switch ($num_of_rows) {
      case 0:
           //Not in queue
         break;
      case 1:
         $row = $stmt->fetch(PDO::FETCH_NUM);
         if ($row != NULL) {
                    $ref = explode(',',scrub_refs_db($row[0]));
         }
         break;
      default:
         $row = $stmt->fetch(PDO::FETCH_NUM);
         if ($row != NULL) {
                $ref = explode(',',scrub_refs_db($row[0]));
         }
            error_log ("WARNING: get_references returned multiple rows for draft '$draft_base'");
        break;
     }

     $pdo = null;  

     return $ref;

}


# Retrieve the documents in a cluster
function get_cluster_members($pdo,$cid) {
     try {
         $sql = "SELECT draft_base FROM clusters WHERE cluster_id=:cid";
         $stmt = $pdo->prepare($sql);
         $stmt->bindParam('cid',$cid);
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
     } catch (PDOException $pe){
         error_log("Error processing : get references", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }
     if ($num_of_rows > 0){
          while($row = $stmt->fetch(PDO::FETCH_NUM)) {
              $members[] = $row[0];
          }
     }

     $pdo = null;
     return $members;
}

?>
