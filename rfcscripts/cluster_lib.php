<?php
  /* $Id: cluster_lib.php,v 1.13 2023/09/29 17:30:05 priyanka Exp $ */
/**************************************************************************************/
/* Copyright The IETF Trust 2020 All Rights Reserved                                  */
/* March 2020 : Modified the script to change the order of cluster detail based on    */
/*              ascending RFC numbers - PN                                            */
/* May 2020 : Added the state_id != 19 (withdrawn) condition to get_clsuter - PN      */
/* July 2020 : Added PDO related changes  - PN and XSS changes are done by - ZX       */
/* November 2020 : Modified the script to use PDO prepared statements - PN            */
/* September 2021 : Added get_draft_exact_data to script - PN                         */
/* October 2022 : Added horizontal line between drafts for special case - PN          */
/* July 2023 : Modified function to eliminate duplicate draft - PN                    */
/* Sept 2023 : Modified link for internet-drafts - PN                                  */
/**************************************************************************************/
#
#+
# This module contains support files for the cluster reporting pages made
# available on the public RFC Editor web site.
#-

# We piggy-back on the Errata application, which is separate but runs from
# the same location, for database access. This file is NOT part of the CVS
# repository for the public cluster report pages.
include("config.php");
include_once("db_connect.php");
include_once("ams_util_lib.php");



define("MAX_CID",5); # characters allowed in a CID, i.e. C9999.
define("IS_ANCHORED", 0);
define("IS_FREE", 1);
define("UNKNOWN_ANCHOR",2);


# First part of URL locating draft documents
//$document_root="http://www.rfc-editor.org/internet-drafts";

# Callback function to compare two draft_received dates. This is needed
# because the draft in index won't always match a draft_base in clusters.
# A join using date_received would miss documents that have not yet been
# received and are not in index but are known to be part of a cluster.
function cmp_date_received($a,$b) {
     global $pdo;
     $a_date = get_draft_date_received($pdo,$a);
     $b_date = get_draft_date_received($pdo,$b);
     # date_received has form YYYY-MM-DD so we can compare strings
     # directly
     if ($a_date > $b_date) return 1;
     if ($a_date < $b_date) return -1;
     return 0;
}
# Callback function to sort a list of cluster ids as if they didn't
# have a "C" tacked to the front.
function cmp_cluster_ids($a, $b) {
     $cid_a = substr($a,1);
     $cid_b = substr($b,1);
     if ($cid_a > $cid_b) return 1;
     if ($cid_a < $cid_b) return -1;
     return 0;
}

# print a header for a cluster report page
function cluster_report_header($page_title) {
     global $ams_css;
     $dev_mode = get_dev_mode();
     print<<<END
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" 
  "http://www.w3.org/TR/html4/loose.dtd">
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859">
END;
     if ($dev_mode) { print("\n    <link rel=\"stylesheet\" href=\"$ams_css\" type=\"text/css\"/>\n"); }
     print '<title>' . htmlspecialchars($page_title) . '</title>
</head>
<body style="width: 677px;">';
}

# print a footer for a cluster report page.
function cluster_report_footer() {
     print<<<END
</body>
</html>
END;
}


/*This function gets the list of drafts in a cluster in  the doc-id order */
/*The function is used for only auth48_status page*/
function get_cluster_a48status($pdo,$cid, $exclude_pub=false) {
     $state_id = '99';
     try {
         $sql = "SELECT DISTINCT(c.draft_base)
	         FROM `index` i, clusters c
	         WHERE cluster_id = :cid
	         AND state_id != :state_id
	         AND draft LIKE CONCAT( draft_base, '-__' ) order by `doc-id`";


         $stmt = $pdo->prepare($sql);
         $stmt->bindParam('cid',$cid);
         $stmt->bindParam('state_id',$state_id);
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
     } catch (PDOException $pe){
         error_log("Error processing : get_cluster_a48status", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
   if ($num_of_rows > 0){
         while($row = $stmt->fetch(PDO::FETCH_NUM)) {
         # If we need to exclude published documents, we have to query the
         # database again for each draft because we can't do a join because
         # some reference drafts in the cluster might not be in the queue at
         # all so they are not in the index file.
             if ($exclude_pub) {
                 $draft = $row[0];
                 $state = get_ref_status($pdo,$draft);
                 if ($state != 'PUB') {
                     $drafts[] = $draft; // keep if not PUB
                 }
             } else {
                   $drafts[] = $row[0]; // Always
             }
        }
   }
   
   $pdo = null;
   return $drafts;
}

# Return an array containing the drafts for one cluster.
# 
# A question for the business rules was whether to include published documents
# in the cluster reports. One view was to ignore them completely, another to
# include in REF fields, and another to always include but display differently.
# The $exclude_pub defaults to false because the latter was selected (at least
# for initial deployment).
function get_cluster($pdo,$cid, $exclude_pub=false) {
   $state_id_99 = '99';
   $state_id_19 = '19';

   try {
        $sql = "SELECT draft_base
          	FROM `index` i, clusters c
            	WHERE cluster_id = :cid
	        AND state_id != :state_id_99
	        AND state_id != :state_id_19
	        AND draft LIKE CONCAT( draft_base, '-__' )";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam('cid',$cid);
        $stmt->bindParam('state_id_99',$state_id_99);
        $stmt->bindParam('state_id_19',$state_id_19);
        $stmt->execute();
        $num_of_rows = $stmt->rowCount();

     } catch (PDOException $pe){
         error_log("Error processing : get cluster", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   if ($num_of_rows > 0){
      while($row = $stmt->fetch(PDO::FETCH_NUM)) {
         # If we need to exclude published documents, we have to query the
         # database again for each draft because we can't do a join because
         # some reference drafts in the cluster might not be in the queue at
         # all so they are not in the index file.
             if ($exclude_pub) {
                 $draft = $row[0];
                 $state = get_ref_status($pdo,$draft);
                 if ($state != 'PUB') {
                     $drafts[] = $draft; // keep if not PUB
                 }
             } else {
                   $drafts[] = $row[0]; // Always
             }
        }
   }

     usort($drafts,'cmp_date_received');
     $pdo = null;
     return $drafts;
}

# Return an array of associative arrays containing all of the clusters
#
# NOTE: This function does not distinguish between active and published
# clusters.
#
function get_all_clusters($pdo) {
     
    try {  
       $sql = "
            SELECT cluster_id 
            FROM clusters
            GROUP BY cluster_id";
    
     $stmt = $pdo->prepare($sql);
     $stmt->execute();
     $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
         error_log("Error processing : all clusters", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
    }

     if ($num_of_rows > 0) {
         while($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $clusters[] = $row[0];
         }
     }
     # Sort so that C2 comes before C10, etc.
     usort($clusters,"cmp_cluster_ids");

     $pdo = null; 
     return $clusters;
}


# Return an array containing the cluster_id of active clusters.
function get_active_cluster_list($pdo) {
     # Join of clusters and index to use the data in index to filter
     # the results for GROUP BY to collapse.

   $ref = ' ';
   $params = [":ref" => $ref];
   $state_ids = [14,3,16,19,99];
   $in = "";
   foreach ($state_ids as $i => $item)
   {
       $key = ":state_id".$i;
       $in .= "$key,";
       $in_params[$key] = $item; // collecting values into key-value array
   }
   $in = rtrim($in,","); 


    try {
     $sql = "
        SELECT cluster_id
        FROM `index` i, clusters c
        WHERE state_id NOT IN ($in)
          AND `doc-id` IS NOT NULL
          AND ref IS NOT NULL
          AND ref <> :ref
          AND draft LIKE CONCAT(draft_base,'-__')
        GROUP BY cluster_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($params,$in_params)); // just merge two arrays
        $num_of_rows = $stmt->rowCount();
   }catch (PDOException $pe){
         error_log("Error processing : get_active_cluster_list", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));

   }


   if ($num_of_rows > 0) {
       while($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $clusters[] = $row[0];
       }
    }
     # Sort so that C2 comes before C10, etc.
     usort($clusters,"cmp_cluster_ids");

     $pdo = null; 
     return $clusters;

}
#Return an array containing the cluster_id of active clusters having at least one document in MISSREF
function get_active_missrif_list($pdo){
     # Join of clusters and index to use the data in index to filter
     # the results for GROUP BY to collapse.
   
   $state_id_22 = 22;
   $ref = ' ';
   $params = [":ref" => $ref,":state_id_22" => $state_id_22];
   $state_ids = [14,3,16,19,99];
   $in = "";
   foreach ($state_ids as $i => $item)
   {
       $key = ":state_id".$i;
       $in .= "$key,";
       $in_params[$key] = $item; // collecting values into key-value array
   }
   $in = rtrim($in,","); 


    try {
     $sql = "
        SELECT cluster_id
        FROM `index` i, clusters c
        WHERE state_id NOT IN ($in)
          AND state_id = :state_id_22
          AND `doc-id` IS NOT NULL
          AND ref IS NOT NULL
          AND ref <> :ref
          AND draft LIKE CONCAT(draft_base,'-__')
        GROUP BY cluster_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($params,$in_params)); // just merge two arrays
        $num_of_rows = $stmt->rowCount();
   }catch (PDOException $pe){
         error_log("Error processing : get_active_missref_list", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));

   }

   if ($num_of_rows > 0) {
       while($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $clusters_miss[] = $row[0];
       }
    }
   
     # Sort so that C2 comes before C10, etc.
   usort($clusters_miss,"cmp_cluster_ids");

   $pdo = null;
   return $clusters_miss;

}
# Queries the database to retrive displayable data for one draft. The "draft
# string" is the draft_base from the clusters table, not the draft string from
# the index table.
function get_draft_data($pdo,$draft_base) {
   $params = [":draft_base" => $draft_base];
   $state_ids = [3,16,19,99];
   $in = "";
   foreach ($state_ids as $i => $item)
   {
       $key = ":state_id".$i;
       $in .= "$key,";
       $in_params[$key] = $item; // collecting values into key-value array
   }
   $in = rtrim($in,","); 


    try {
        $sql= "
        SELECT i.draft, i.date_received,i.state_id, 
		CONCAT( s.state_name,CASE WHEN i.iana_flag = \"1\"THEN \"*A\"ELSE \"\"END ,CASE WHEN i.ref_flag = \"1\"THEN \"*R\"ELSE \"\"END,
	        CASE WHEN i.generation_number = \"1\" THEN \"(1G)\" WHEN i.generation_number = \"2\" THEN \"(2G)\" WHEN i.generation_number = \"3\" THEN \"(3G)\" WHEN i.generation_number = \"0\" THEN \"\" END) as state,
		i.authors, i.title, i.`char-count`,
               i.ref, i.source,i.`time-out-date`, i.`pub-status`, i.`doc-id`,
           CASE
              WHEN i.SOURCE='INDEPENDENT' THEN 1
              ELSE 0
           END indep
        FROM `index` i, `states` s 
        WHERE i.state_id NOT IN ($in)
	    AND  i.state_id = s.state_id	
            AND draft LIKE CONCAT(:draft_base,'%')";
        

        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($params,$in_params)); // just merge two arrays
        $num_of_rows = $stmt->rowCount();
   
   }catch (PDOException $pe){
         error_log("Error processing : get_draft_data", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }


    if ($num_of_rows > 0){
       $row = $stmt->fetch(PDO::FETCH_ASSOC);
    }

     $pdo = null;
     return $row;


}

# Queries the database to retrive displayable data for one draft. The "draft
# name" is the exact draft namedraft_base from the index table.
function get_draft_exact_data($pdo,$draft_base) {
   $params = [":draft_base" => $draft_base];
   $state_ids = [3,16,19,99];
   $in = "";
   foreach ($state_ids as $i => $item)
   {
       $key = ":state_id".$i;
       $in .= "$key,";
       $in_params[$key] = $item; // collecting values into key-value array
   }
   $in = rtrim($in,","); 


    try {
        $sql= "
        SELECT  i.draft, i.date_received,i.state_id, 
		CONCAT( s.state_name,CASE WHEN i.iana_flag = \"1\"THEN \"*A\"ELSE \"\"END ,CASE WHEN i.ref_flag = \"1\"THEN \"*R\"ELSE \"\"END,
	        CASE WHEN i.generation_number = \"1\" THEN \"(1G)\" WHEN i.generation_number = \"2\" THEN \"(2G)\" WHEN i.generation_number = \"3\" THEN \"(3G)\" WHEN i.generation_number = \"0\" THEN \"\" END) as state,
		i.authors, i.title, i.`char-count`,
               i.ref, i.source,i.`time-out-date`, i.`pub-status`, i.`doc-id`, i.`internal_key`,
           CASE
              WHEN i.SOURCE='INDEPENDENT' THEN 1
              ELSE 0
           END indep
        FROM `index` i, `states` s 
        WHERE i.state_id NOT IN ($in)
	    AND  i.state_id = s.state_id	
            AND draft LIKE CONCAT(:draft_base,'%')";
        

        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($params,$in_params)); // just merge two arrays
        $num_of_rows = $stmt->rowCount();
   
   }catch (PDOException $pe){
         error_log("Error processing : get_draft_data", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   $result_row = array();
    if ($num_of_rows > 0){
       switch ($num_of_rows) {
          case '2':
               while ($index_row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $index_draft = $index_row['draft'];
                    $index_draft_base = strip_number($index_draft);
                    if ($index_draft_base == $draft_base) {
                       $result_row['draft'] = $index_row['draft'];
                       $result_row['date_received'] = $index_row['date_received'];
                       $result_row['state_id'] = $index_row['state_id'];
                       $result_row['state'] = $index_row['state'];
                       $result_row['authors'] = $index_row['authors'];
                       $result_row['title'] = $index_row['title'];
                       $result_row['char-count'] = $index_row['char-count'];
                       $result_row['ref'] = $index_row['ref'];
                       $result_row['source'] = $index_row['source'];
                       $result_row['pub-status'] = $index_row['pub-status'];
                       $result_row['doc-id'] = $index_row['doc-id'];
                       $result_row['internal_key'] = $index_row['internal_key'];
                       $result_row['indexp'] = $index_row['indep'];
                     }
               }
               break; 
          case '1':
          default:
          // Return matched data
               $row = $stmt->fetch(PDO::FETCH_ASSOC);
               break;
        }
     }

     $pdo = null;
     if ($result_row) {
         return $result_row;
     }else {
     return $row;
     }


}
# Add the STATE to the display.
# This routine replaces the line:
#   if ($draft_data['state'] != 'REF') print("{$draft_data['state']}\n");
# The new functionality is to detect AUTH48 and add a link to the AUTH48
# info script.
function display_draft_state($state,$docnum,$state_id,$cid) {
     $length = strlen($cid);

     $cid_name = substr($cid,0,'-'.($length -1));
     $cid_number = substr($cid,'-'.($length -1));

     switch ($state_id) {
     case '15':
          break; // Do Nothing
     case '2':
          // Link to AUTH48 info page
          print '<a href="/auth48/C' . htmlspecialchars($cid_number) . '">' . htmlspecialchars($state). "</a>\n";
          break;
     case '23':
          // Link to AUTH48 info page
          print '<a href="/auth48/C' . htmlspecialchars($cid_number) . '">' . htmlspecialchars($state) . "</a>\n";
          break;
     default:
          // Display unchanged
          print htmlspecialchars($state) . "\n";
          break;
     }
}

# Driver function for the display of cluster data
function display_draft_data($draft_data) {
     global $document_root;
     global $internet_draft;
     global $datatracker;
     global $ietf_root;
     global $pdo;     
     print "<p>";
     print '<a id="' . htmlspecialchars($draft_data['draft_base']) . '"></a>'; // always empty name anchor
     if (array_key_exists('draft',$draft_data)) {
# Draft is received
          switch ($draft_data['state_id']) {
          case '14':
               $rfc_url = generate_rfc_url($draft_data['doc-id']);
               print "<div class=\"cluster_heading\">";
               print("<b>PUB</b>&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"" . htmlspecialchars($rfc_url) . "\">" . htmlspecialchars($draft_data['doc-id']) . "</a>&nbsp;&nbsp;&nbsp;&nbsp;|");
               print("&nbsp;&nbsp;&nbsp;&nbsp; " . htmlspecialchars($draft_data['draft']) . ".txt");
               print "</div>";         
               print "<hr>";
               break;
          default:
               print "<p><h5>";
               print htmlspecialchars($draft_data['date_received']) . " &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;";

               print("<a href=\"" . htmlspecialchars($ietf_root . '/id/' . $draft_data['draft']) . ".txt\">");
               print htmlspecialchars($draft_data['draft']) . ".txt</a>&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;";

               display_draft_state($draft_data['state'], substr($draft_data['doc-id'],3),$draft_data['state_id'],$draft_data['cid']);
               print "</h5></p><p>"; 
               process_ref_string($draft_data['ref']);
               print "</p>";
               print("<p><b>Authors:</b>&nbsp;&nbsp;" . htmlspecialchars($draft_data['authors']));
               print "</p><p>";
               print("<b>Title:</b>&nbsp;&nbsp;\"" . htmlspecialchars($draft_data['title']) . "\"");
               print "</p><p>"; 
               printf("<b>Bytes:</b> %d\n", $draft_data['char-count']);
               print "</p>";
               #Get the ssp_id for the draft
               $ssp_id = get_wg_ssp_id($pdo,$draft_data['draft']); 
	       if ($ssp_id == 4){
               //If ssp_id is 4
	   	  if($source != "IRTF")
   		  {
	           print("<b>Research Group:</b> " . htmlspecialchars($draft_data['source']) . "\n</p>");
     	          }
	  
	       }else{
               //If ssp_id is not 4
               switch ($draft_data['source']) {
               case "IETF - NON WORKING GROUP":
               case "IAB":
               case "IESG":
               case "IRTF": 
               case "INDEPENDENT":
                    /* Do Nothing */
                         print("<div class=\"special_hr\"></div>");
                    break;
               default:
                   # if (strpos($draft_data['pub-status'],"STANDARD") !== false) {
                         print("<p><b>Working Group:</b> " . htmlspecialchars($draft_data['source']) . "\n</p> <div class=\"special_hr\"></div>");
                   # }
                    break;
               }
               break;
               }
         }
     } else {
# Draft is not received 
          if ($debug_cl === true) {print("<!-- processing not-received draft -->"); }
          print("NOT-RECEIVED   <a href=\"" . htmlspecialchars($datatracker . '/' . $draft_data['draft_base']) . "\">" . htmlspecialchars($draft_data['draft_base']) . "</a> <b>[" . htmlspecialchars($draft_data['cid']) . "]</b>\n</p>");

     }
     //print "</p>";
    // print "<hr>";


}

function process_ref_string($refstr) {
     global $datatracker;
     global $pdo;

     if ($refstr == null || strpos($refstr, "NULL") !== false) {
          return;
     }
     
     $refs = preg_split("[, *]",$refstr);

     print("<b>REF&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</b>");
     print "<ul>";
     $start_ref = true;
     foreach ($refs as $ref) {
          # The refs field of the DB has some nonsense values in it that need
          # to be ignored.
          if (($garbage = strpos($ref,'+')) !== false) {
               $ref = substr($ref,0,$garbage);
          }
          $state = get_ref_status($pdo,$ref);
          if ($state == 'NOT-RECEIVED'){
              print '<li><a href="' . htmlspecialchars($datatracker . '/' . $ref) . '">' . htmlspecialchars($ref) . '</a> &nbsp;&nbsp;' . htmlspecialchars($state) . "</li>";
          }else {
	       print"<li>" . htmlspecialchars($ref) . " &nbsp;&nbsp;" . htmlspecialchars($state) . "</li>";
          }
     }
  print "</ul>";
}

# Lookup the state of a draft for the references subsection
function get_ref_status($pdo,$draft) {
   $limit_no = 1;
   $params = [":draft" => $draft, ":limit_no" => $limit_no];
   $state_ids = [3,16,19,99];
   $in = "";
   foreach ($state_ids as $i => $item)
   {
       $key = ":state_id".$i;
       $in .= "$key,";
       $in_params[$key] = $item; // collecting values into key-value array
   }
   $in = rtrim($in,","); 

   try {
        $sql = "
      	 SELECT CONCAT( s.state_name,CASE WHEN i.iana_flag = \"1\"THEN \"*A\"ELSE \"\"END ,CASE WHEN i.ref_flag = \"1\"THEN \"*R\"ELSE \"\"END,
	        CASE WHEN i.generation_number = \"1\" THEN \"(1G)\" WHEN i.generation_number = \"2\" THEN \"(2G)\" WHEN i.generation_number = \"3\" THEN \"(3G)\" WHEN i.generation_number = \"0\" THEN \"\" END) as state
         FROM `index` i, `states` s
         WHERE i.state_id NOT IN ($in)
            AND i.state_id = s.state_id 
            AND draft LIKE CONCAT (:draft, '%') LIMIT :limit_no ";
       
       $stmt = $pdo->prepare($sql);
       $stmt->execute(array_merge($params,$in_params));
       $num_of_rows = $stmt->rowCount();
    } catch (PDOException $pe){
         error_log("Error processing : get_ref_status", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
    }

    switch($num_of_rows) {
       case 1:
           $row = $stmt->fetch(PDO::FETCH_ASSOC);
           $state = $row['state'];
           break;
       default:
           $state = 'NOT-RECEIVED';
           break;
    }
    $pdo = null;      
    return $state;


}

# Generate a URL for an A tag for a RFC document
function generate_rfc_url($rfcid) {
    global $document_root;
    global $rfc;
    $rfc_number = substr($rfcid,3); // rfcid is RFCXXXX
    $rfctxt = sprintf("rfc%d",$rfc_number); // URL is rfcX.txt
    $http_url = $document_root.'/'.$rfc.'/'. $rfctxt . '.txt';
    return $http_url;
}

# Query the database to get the date received. If the draft is not received
# (not in the database), return '9999-01-01' as a default
#
# NOTE: Assumes the database is already opened.
function get_draft_date_received($pdo,$draft) {

   $limit_no = 1;
   $params = [":draft" => $draft,":limit_no" => $limit_no];
   $state_ids = [3,16,19,99];
   $in = "";
   foreach ($state_ids as $i => $item)
   {
       $key = ":state_id".$i;
       $in .= "$key,";
       $in_params[$key] = $item; // collecting values into key-value array
   }
   $in = rtrim($in,","); 

   try {
       $sql = "
          SELECT i.date_received, 
	     CONCAT( s.state_name,CASE WHEN i.iana_flag = \"1\"THEN \"*A\"ELSE \"\"END ,CASE WHEN i.ref_flag = \"1\"THEN \"*R\"ELSE \"\"END,
             CASE WHEN i.generation_number = \"1\" THEN \"(1G)\" WHEN i.generation_number = \"2\" THEN \"(2G)\" WHEN i.generation_number = \"3\" THEN \"(3G)\" WHEN i.generation_number = \"0\" THEN \"\" END) as state          
          FROM `index` i, `states` s
          WHERE i.state_id NOT IN ($in)
          AND i.state_id = s.state_id
          AND draft LIKE CONCAT (:draft, '%') LIMIT :limit_no ";
    
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($params,$in_params)); // just merge two arrays
        $num_of_rows = $stmt->rowCount();
   }catch (PDOException $pe){
         error_log("Error processing : get_draft_date_received", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));

   }

     switch($num_of_rows) {
     case 1:
          $row = $stmt->fetch(PDO::FETCH_ASSOC);
          $date_received = $row['date_received'];
          $state = $row['state'];
          if ($state == 'PUB') {
               # make the PUB dates look earlier than others
               $date_received = substract_years($date_received,30);
          }
          break;
     default:
          $date_received = '9999-01-01';
          break;
     }
     
    $pdo = null;     
     return $date_received;
}
# Query the database to get the "anchored" value. Anchored documents are those
# that can not finish the publication process separately from the documents in
# the cluster.
#
# Returns constant associated with the selected anchored value.
#
# NOTE: Assumes the database is already opened.
function anchored_state($pdo,$draft_base) {

   try {     
       $sql = "SELECT anchored FROM clusters WHERE draft_base=:draft_base";

       $stmt = $pdo->prepare($sql);
       $stmt->bindParam('draft_base',$draft_base);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){

         error_log("Error processing : anchored_state", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   $anchored = UNKNOWN_ANCHOR;
   switch($num_of_rows) {
       case 1:
           $row = $stmt->fetch(PDO::FETCH_NUM);
           switch ($row[0]) {
                 case 'yes':
                       $anchored = IS_ANCHORED;
                       break;
                 case 'no':
                       $anchored = IS_FREE;
                       break;
                 default:
                       $anchored = UNKNOWN_ANCHOR;
                       break;
          }
          break;
       default:
          error_log("multiple or 0 rows returned from clusters with draft_base=$draft_base");
          break;
     }
    
     $pdo = null; 
     return $anchored;
}


# Subtract a number of years from a date string of the form YYYY-MM-DD. Return
# the date with the same format with the year part changed.
function substract_years($date_value, $years) {
     $date_parts = explode('-',$date_value);
     $date_parts[0] = $date_parts[0] - $years;
     return implode('-',$date_parts);
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
         error_log("Error processing : get_cluster_id", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

     
   if ($num_of_rows > 0) {
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
     } else {
          $cid = FALSE;
          error_log("get_cluster_id: database error");
     }

     $pdo = null;
     return $cid;
}
# Strip the trailing dash and digits or pound signs from a draft
# name.
function strip_number($draft) {
     return $draft = preg_replace('/-(\d\d|\#\#)$/',"", $draft);
}

/*
 * Routine to query database to retrieve the ssp_id for the working group
 * that created the document being announced.
 *
 * NOTE: Function assumes that the database is opened and closed by caller.
 */

function get_wg_ssp_id($pdo,$draftname) {

   try {
       $sql = "SELECT w.ssp_id
           FROM `index` i, working_group w
           WHERE w.wg_name = i.source AND i.draft=:draftname";
 
       $stmt = $pdo->prepare($sql);
       $stmt->bindParam('draftname',$draftname); 
       $stmt->execute();  
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pd){
         error_log("Error processing : get_wg_ssp_id", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
     
   if ($num_of_rows > 0){
         $wg_ssp_id = $stmt->fetch(PDO::FETCH_ASSOC);
   }

   $pdo = null;
   return $wg_ssp_id['ssp_id'];
}



?>
