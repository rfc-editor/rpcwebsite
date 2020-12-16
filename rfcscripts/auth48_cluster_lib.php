<?php
/**************************************************************************************/
/* Copyright The IETF Trust 2020 All Rights Reserved                                  */
/*Script Name : auth48_cluster_lib.php                                                */
/*              Script is include script for aith48_cluster.php                       */
/* November 2020 : Modified the script to use PDO prepared statements - PN            */
/**************************************************************************************/

$debug_auth48_cl = false;

/*
This function handles the request for individual data display for every entry that belongs
to a particular cluster.
*/
function auth48_approvals_display($pdo,$docnum){
  global $debug_auth48_cl, $pdo;

   if (($a48_data = get_auth48s_data($docnum)) !== FALSE) {
          $rec_count = count($a48_data);
          if ($debug_auth48_cl === TRUE) {
               print("\n<!--\napprovals_display: \$rec_count=$rec_count\n--n");
          }
          if ($rec_count > 0) {
               display_header($docnum, $a48_data);
               show_approval_data($a48_data['a48_id']);
               end_table();
               display_cluster_message($a48_data['draft']);
               display_notes($a48_data['notes']);

          } else {
               display_norecords($docnum);
          }
          return TRUE;
     } else {
          error_log("??auth48_cluster_lib.php: Error from database: " . mysqli_error($db_link));
     }
     return FALSE;
}

/*
 * Function that issues the query for the process record. The record is
 * returned as an associative array with the column names as keys. If no record
 * is found, an empty array is created and returned.
 * On error, returns a BOOLEAN false.
 */
function get_auth48s_data($docnum) {
     global $debug_auth48_cl, $pdo;

     $docid = 'RFC'.$docnum;
     $status = 'active';

     try {
     $sql=
      "SELECT a48_id, a.status, a.notes, draft,state_id,
              date_format(start_date,'%Y-%m-%d') as start_date
       FROM auth48s a, `index` i
       WHERE a.status=:status
         AND i.`doc-id` = a.`doc-id`
         AND a.`doc-id`= :docid";

       $stmt = $pdo->prepare($sql);
       $stmt->bindParam('status',$status);
       $stmt->bindParam('docid',$docid);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();

      } catch (PDOException $pe){
         error_log("Error processing : get_auth48s_data", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
      }

     if ($num_of_rows > 0){
       $row = $stmt->fetch(PDO::FETCH_ASSOC);
       if ($debug_a48_stat === TRUE) {
               print("\n<!--\nget_auth48s_data: \$row=\n");
               var_dump($row);
               print("\nEnd \$row\n-->\n");
       }

       return $row;
     } else {
         return array();
     }

     return FALSE;


}

/*
This function displays the tabular format representation for the author information
*/
function display_header($docnum, $a48_data) {
     $date = substr($a48_data['start_date'], 0, 10);

     if ($a48_data['state_id'] == '2') {
         print "<h3>AUTH48 status of {$a48_data['draft']} (RFC-to-be $docnum)</h3>";
     }elseif($a48_data['state_id'] == '23'){
         print "<h3>AUTH48-DONE status of {$a48_data['draft']} (RFC-to-be $docnum)</h3>";
     }
     print<<<END
    <p>
This document is in <a href="/pubprocess.html#auth48">AUTH48
state</a> as of $date. It has not yet been published as an RFC.
The RFC Editor is awaiting approvals from the author(s) as 
shown below (and anyone else listed) before continuing the publication process.
   </p>

   <table style="border: thin solid black;" align="center" width="500">
    <tr>
    <th style="border: thin solid black; text-align:left;">Name</th>
    <th style="border: thin solid black; text-align:center;">Approved?</th>
    <th style="border: thin solid black; text-align:center;">Date of Approval</th>
   </tr>

END;
}

/*
 * Function uses the key from the auth48s table to retrieve the approver
 * records from the approvals table. It then passes each row to a display
 * function.
 */
function show_approval_data($a48_id) {
     global $pdo;

     try {
          $sql = 
          "SELECT name, approved, approved_date
           FROM approvals 
           WHERE a48_id=:a48_id";
    
          $stmt = $pdo->prepare($sql);
          $stmt->bindParam('a48_id',$a48_id);
          $stmt->execute();
          $num_of_rows = $stmt->rowCount();
     }catch (PDOException $pe){
         error_log("Error processing : show_approval_data", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }
     if ($num_of_rows > 0){
       while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
               display_approver($row);
       }
       return TRUE;
     } else {
       return FALSE;
     }
}

/*
 * This function accepts an associative array representing one row from the
 * approvals table and uses it to generate one row in the HTML table displaying
 * the data.
 */
function display_approver($row) {
     $approved = substr(ucfirst($row['approved']), 0 , 1);
     $date = substr($row['approved_date'], 0, 10);
     if ((strlen($date)) != 10) { $date = '&nbsp;'; }
     print<<<END
       <tr style="border: thin solid black;">
          <td style="border: inherit; text-align:left;">{$row['name']}</td>
          <td style="border: inherit; text-align:center;">$approved</td>
          <td style="border: inherit; text-align:center;">$date</td>
       </tr>

END;
}

function end_table() {
     print("\n   </table>\n");
}

function display_cluster_message($draft) {
    global $debug_auth48_cl,$pdo;

    $cid = get_cluster_id($pdo,$draft);
    if ($debug_auth48_cl === true) {
         print("<h4>display_cluster_message</h4><pre>");
         print_r($draft); print("\n");
         print_r($cid); print("\n");
         print("</pre>");
    }

    if (is_string($cid) && (preg_match('/^C\d+/',$cid) == 1)){
	print "<br>";
        print("<p><b>** This document is part of ");
	print("<a href=\"/cluster_info.php?cid=$cid\">Cluster $cid</a>");
	print(", so may have additional holds before publication.</b></p>\n");
    }
}
/*
 * Writes the NOTES field to the output page.
 */
function display_notes($notes) {
     print<<<END

   <h4>Notes:</h4>
END;
     if ($notes != null && (strlen(trim($notes))) > 0) {
          # Use CSS "pre" to keep newlines, but don't use a fixed-width font.
          print("\n<p style=\"white-space: pre-wrap;\">$notes</p>\n");
         # print("\n<p>$notes</p>\n");
     }

}

?>



