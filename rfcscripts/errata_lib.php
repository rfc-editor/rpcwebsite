<?php
  /* Copyright The IETF Trust 2020 All Rights Reserved */
  /* $Id: errata_lib.php,v 1.19 2022/08/16 22:28:32 priyanka Exp $
   * 
   * v1.33 2010/02/08 rcross: added handling for special characters in title, edit_full_record_form()
   * April 2017 Updates : Added the redirect link for Errata Id and RFC number - PN
   * May 2017 Updates : Removed rfcid from function generate_rfc_errata_search_query - PN 
   * March 2018 Updates : Made errata num as full link in errata Full record view - PN
   * August 2018 Updates : Added Format field to forms for Publication Status Changes - PN
   * May 2020 Updates : Replaced function Unserialize/ serialize from the script with JSON_DECODE/JSON_ENCODE as per ZX security review - PN
   * June 2020 Updates : Added inline errata link in the Full records view of an Errata - PN
   * November 2020 : Modified the script to use PDO prepared statements - PN            
   * June 2021 : Modified the script for server upgrade - PN                            
   * October 2021 : Modified the script to increase maxlength for section - PN                            
   * April 2022 : Added function insert_report_captcha_form Removed for form submission part to handle bot submission - PN 
   * August 2022 : Modified the script for Editorial stream - PN 
   */

include_once("db_connect.php");
include_once("core_lib.php");
include("config.php");
include_once("ams_util_lib.php");
#get_dev_mode();
$dev_mode = get_dev_mode();

define("EDIT_FORMS",1);         /* show user EDIT forms */
define("VERIFY_FORMS",2);       /* show user VERIFY forms */
// these constants must match the values in the errata_status_codes table.
define("STATUS_REPORTED", 2);
define("STATUS_VERIFIED", 1);
define("STATUS_DEFAULT", 0);
define("STATUS_HELD", 3);
define("STATUS_REJECTED", 9);
define("STATUS_ALL",(STATUS_REPORTED+STATUS_VERIFIED+STATUS_REJECTED+STATUS_HELD));

define("RFC_ED_VERIFIER_ID",2);
// define some constants to limit length of strings in POST/GET values
define("MAX_TEXT",80);
define("MAX_DATE",10);
define("MAX_STATUS",8);
define("MAX_TYPE",9);
define("MAX_RFC",7);
define("MAX_RFC_DIGIT",4);
define("MAX_NAME",80);
define("MAX_EMAIL",120);
define("MAX_TITLE",256);
define("MAX_SECTION",512);
define("MAX_EID_DIGIT",4);
define("MAX_SUBSET_CODE",8);
define("MAX_ACRONYM",10);
define("MAX_PRESENTATION",10);

$debug_erlib = false;

// Include WordPress
define('WP_USE_THEMES', false);
//require('/a/wordpress/wp-blog-header.php');
require_once ('/a/wordpress/wp-load.php');

/*
 * Columns in the database tables available to the application. Note that the
 * columns are not all in the same table.
 */
$errata_columns =
     array('errata_id', 'doc-id', 'status_id','type_id','section','orig_text',
           'correct_text','submitter_name','submitter_email','notes','submit_date',
           'posted_date','verifier_id','verifier_name','verifier_email',
           'errata_type_code','errata_status_code',
           'rs_code', 'insert_date', 'update_date', 'conv_format_check','format');

/*
 * Columns of just the errata table the the application may set.
 */
$errata_table_columns = 
     array('doc-id', 'status_id', 'type_id', 'section', 'orig_text', 'correct_text', 
           'submitter_name', 'submitter_email', 'notes', 'submit_date', 'posted_date', 
           'verifier_id', 'verifier_name', 'verifier_email','format');

/*
 * Display labels for forms. Some database columns use different labels depending
 * on values in other columns, e.g. the verifier_name column could be labled
 * "Verifier Name" or "Edited By" or "Rejected By" depending on the status column.
 */
$errata_labels =
     array('errata_id' => 'Errata ID', 
           'doc-id' => 'Document ID',
           'status_id' => 'Status ID',
           'type_id' => 'Type ID',
           'section' => 'Section',
           'orig_text' => 'Original Text',
           'correct_text' => 'Correct Text',
           'submitter_name' => 'Reported By',
           'submitter_email' => 'Email',
           'notes' => 'Notes',
           'submit_date' => 'Date Reported',
           'posted_date' => 'Date Verified',
           'verifier_id' => 'Verifier ID',
           'verifier_name' => 'Verifier Name',
           'verifier_email' => 'Verifier Email',
           'errata_type_code' => 'Type',
           'errata_status_code' => 'Status',
           'rs_code' => 'Record Source',
           'insert_date' => 'DB Insert Date',
           'update_date' => 'DB Update Date',
           'conv_format_check' => 'Format Check Flag',
           'format' => 'Format');

$errata_types = array('Select One', 'Technical','Editorial');
// Not Set isn't in the table and will cause insert/update to fail if used.
$errata_type_ids = array('Select One' => 0, 'Technical' => 2,'Editorial' => 1);
$errata_type_codes = array('1' => 'Editorial', '2' => 'Technical');

$verify_status_codes = array('Reported', 'Verified');
$errata_status_codes = array('Verified', 'Reported', 'Held for Document Update', 'Rejected');
$errata_status_ids   = array('Verified' => STATUS_VERIFIED, 
                             'Reported' => STATUS_REPORTED, 
                             'Held for Document Update' => STATUS_HELD,
                             'Rejected' => STATUS_REJECTED);
$errata_status_from_id = array('2' => 'Reported', '1' => 'Verified',
                               '9' => 'Rejected', '3' => 'Held for Document Update');

$errata_status_codes_lc = array('reported' => STATUS_REPORTED, 
                             'verified' => STATUS_VERIFIED, 
                             'rejected' => STATUS_REJECTED,
                             'held for document update'     => STATUS_HELD,
                             'default'  => STATUS_DEFAULT,
                             'all'      => STATUS_ALL);
$errata_subsets_by_id = array(STATUS_REPORTED => 'reported' , 
                              STATUS_VERIFIED => 'verified', 
                              STATUS_REJECTED => 'rejected',
                              STATUS_HELD     => 'held for document update',
                              STATUS_DEFAULT  => 'default' ,
                              STATUS_ALL      => 'all');
/*Format possible values*/
$formats = array('TEXT','PDF','HTML');

/*
 * Use the errata_id to lookup the RFC doc-id and then return just the digits.
 * Returns the digits of the RFC or boolean FALSE on error.
 */
function get_rfc_num($errata_id) {

     global $debug_erlib;
     global $pdo;


     try {
     $query =
          "SELECT `doc-id` FROM errata WHERE errata_id = :errata_id";
         $stmt = $pdo->prepare($query);
         $stmt->bindParam('errata_id',$errata_id);
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
     } catch (PDOException $pe){
         error_log("Error processing :  get_rfc_num", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
         $_SESSION['errata_lib_error'] = "Bad query: get_rfc_num";
         return false;
     }


     if ($debug_erlib === true) {
          print("<b>get_rfc_num</b>\n<pre>\n");
          print("\$query:\n"); var_dump($query);
          print("\n</pre>\n");
     }

     $row = null;
     
     switch ($num_of_rows) {
        case 0:
            $rfc_num = 0;
            break;
        case 1:
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $rfc_num = substr($row['doc-id'],3,MAX_RFC_DIGIT);
     }
     
     return $rfc_num;
}

function get_most_recent_errata_id($rfcid) {
     global $debug_erlib;

     $row = retrieve_most_recent_insert($rfcid);
     if (is_string($row)) { return $row; }
     return $row['errata_id'];
}

/*
 * Use the doc-id to select the addition data need for generating an email
 * acknowledging the report of an erratum.
 *
 * A successful return is an associative array of the record in the database.
 * An error return is a string.
 */
function get_extra_email_data($doc_id) {
     global $debug_erlib;
     global $pdo;
     
     $rfc_num = substr($doc_id,3);

     $mod_doc_id = sprintf('%s%04d','RFC',$rfc_num);
    

     try 
     {
         $query =
             "SELECT i.`doc-id`, i.email, i.authors, i.source, i.draft,
                  i.`pub-status`, w.area_name, a.area_director_email,
                  w.ssp_id, s.stream_name, s.ssp_email, s.ssp_name,
                  w.wg_chair_email, w.wg_email, w.wg_status,
                  CONCAT(w.wg_acronym,'-ads@tools.ietf.org') AS ad_list,
                  CONCAT(w.wg_acronym,'-chairs@tools.ietf.org') AS chair_list
             FROM `index` i, working_group w, stream_specific_parties s, area a
             WHERE w.wg_name = i.source 
                 AND w.area_name = a.area_name
                 AND w.ssp_id = s.ssp_id 
                 AND i.`doc-id`=:mod_doc_id LIMIT :limit_no";

	     $stmt = $pdo->prepare($query);
	     $stmt->bindParam('mod_doc_id',$mod_doc_id);
	     $stmt->bindValue('limit_no',1);
	     $stmt->execute();
	     $num_of_rows = $stmt->rowCount();
    } catch (PDOException $pe){
         error_log("Error processing : get_extra_email_data", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
    }

     if ($debug_erlib === true) {
          print("<b>get_extra_email_data</b>\n<pre>\n");
          print("\$query:\n"); var_dump($query);
          print("\n</pre>\n");
     }

     $row = null;
  
     if ($num_of_rows > 0){
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
     } 

     if ($debug_erlib === true) {
          print("<b>get_extra_email_data</b>\n<pre>\n");
          print_r($row);
          print("\n</pre>\n");
     }
     
     return $row;
}

// This string contains the main SQL SELECT used to retireve records. Functions
// append extra WHERE clauses to complete the statement.
$base_sql_query = 
     "SELECT e.`doc-id`, errata_id, title, `pub-date`,errata_type_code,
             errata_status_code, submit_date, posted_date, verifier_id,
             verifier_name, verifier_email, submitter_name, submitter_email,
             orig_text, correct_text, e.notes, section, rs_code, e.insert_date,
                  e.update_date, conv_format_check, e.`format`
      FROM errata e, `index` i, errata_type_codes t , errata_status_codes s
      WHERE e.`doc-id` = i.`doc-id` AND e.type_id=t.errata_type_id AND 
            e.status_id=s.errata_status_id";

/*
 * Lookup the errata record(s) for one RFC.
 * $rfc: RFC Number (without "RFC")
 * $subset_included: which combinations of status codes to include.
 */
function errata_lookup($rfc, $subset_included='default') {
     global $debug_erlib;
     global $base_sql_query;
     global $errata_status_codes_lc;
     global $pdo;
 
     $doc_id = sprintf('%s%04d','RFC',$rfc);

     $subset = $errata_status_codes_lc[$subset_included];
     
     $status_id = $subset;

     try { 
         $query = $base_sql_query . " AND e.`doc-id` = :doc_id";
         if ($status_id == 'STATUS_ALL'){ 
              $query .= " ORDER BY  type_id DESC, status_id, submit_date";
         } elseif ($status_id = 'STATUS_DEFAULT'){
              $query .= " AND (s.errata_status_id=:status_verified_id OR s.errata_status_id=:status_reported_id)";
              $query .= " ORDER BY  type_id DESC, status_id, submit_date";
         } else {
              $query .= " AND s.errata_status_id=:status_id";
              $query .= " ORDER BY  type_id DESC, status_id, submit_date";
         }
         
         $stmt = $pdo->prepare($query);
         if ($status_id == 'STATUS_ALL'){ 
             $stmt->bindParam('doc_id',$doc_id);
         } elseif ($status_id = 'STATUS_DEFAULT'){
             $stmt->bindParam('doc_id',$doc_id);
             $stmt->bindValue('status_verified_id', STATUS_VERIFIED);
             $stmt->bindValue('status_reported_id', STATUS_REPORTED);
         } else {
             $stmt->bindParam('doc_id',$doc_id);
             $stmt->bindParam('status_id', $status_id);
         }     
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
    } catch (PDOException $pe){
         error_log("Error processing : errata_lookup", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
    }

     if ($debug_erlib === true) {
          print('<h4>errata_lookup</h4><p><pre>');
          print('$rfc= ');    var_dump($rfc);
          print('$subset_included= '); var_dump($subset_included);
          print('$subset= '); var_dump($subset);
          print("\$query=\n");  var_dump($query);
          print('</pre></p>');
     }

     $count = $num_of_rows;
     switch ($count) {
     case 0:
          $rfchead = get_title_pub_date($rfc);
          if ($rfchead === false && isset($_SESSION['errata_lib_error'])) {
               print("<p class=\"warning\">" . $_SESSION['errata_lib_error'] . "</p>\n");
               unset($_SESSION['errata_lib_error']);
          }else {
		print("<p>No errata records returned for " . build_rfc_anchor("RFC" . $rfc) . ".</p>\n");
          }
          break;
     default:
          $row = $stmt->fetch(PDO::FETCH_ASSOC);
          $rfchead = array("pub-date" => $row['pub-date'], "title" => trim($row['title']));
          report_header($row['doc-id'],$row['title'],$row['pub-date']);
          print("\n<p>Found $count record");
          if ($count > 1)  print("s");
          print(".</p>\n");
/*          $status = errata_prolog($row['doc-id'],$subset); */
          $status = print("<hr \>\n");
          if (is_string($status)) {
               $rfchead = $status; // assign as error indicator
          } else {
               do  {
                    if ($row['section'] != null) {
                         display_record($row);
                             
                    } else {
                         display_legacy_record($row);
                    }
               } while ($row = $stmt->fetch(PDO::FETCH_ASSOC));
          }
     }

     return $rfchead;
}

function errata_lookup_by_id($errata_id, $include_rejected=true)
{
     global $debug_erlib;
     global $pdo;

     $query = 
          "SELECT e.`doc-id`, errata_id, title, `pub-date`, errata_type_code,
                  errata_status_code, submit_date, posted_date, verifier_id,
                  verifier_name, verifier_email,submitter_name, submitter_email,
                  orig_text, correct_text, e.notes, section, e.`format`
           FROM errata e, `index` i, errata_type_codes t , errata_status_codes s
           WHERE e.`doc-id` = i.`doc-id` AND e.type_id=t.errata_type_id AND 
                 e.status_id=s.errata_status_id AND e.`errata_id` = :errata_id";

     if ($include_rejected == false) {
         $query .= " AND ( s.errata_status_id=:status_verified_id OR s.errata_status_id=:status_reported_id OR s.errata_status_id=:status_held_id)";
     }
  

     try { 
         $stmt = $pdo->prepare($query);
         if  ($include_rejected == false) { 
             $stmt->bindParam('errta_id',$errata_id);
             $stmt->bindValue('status_verified_id', STATUS_VERIFIED);
             $stmt->bindValue('status_reported_id', STATUS_REPORTED);
             $stmt->bindValue('status_held_id', STATUS_HELD);
         } else {
             $stmt->bindParam('errata_id',$errata_id);
         }     
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
    } catch (PDOException $pe){
         error_log("Error processing : errata_lookup_by_id", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
    }

     if ($debug_erlib === true) {
          print('<h4>errata_lookup_by_id</h4><p><pre>');
          print('$errata_id= '); var_dump($errata_id);
          print("\$query=\n");   var_dump($query);
          print('</pre></p>');
     }

     $count = $num_of_rows;
     switch ($count) {
     case 0:
          print("<p>No report available for id " . htmlspecialchars($errata_id) .  ".</p>\n");
          $rfcnum = get_rfc_num($errata_id);
          if ($rfcnum != 0) {
               print '
                    <form method="get" action="/errata_search.php">
                    <input type="hidden" name="rfc" value="' . htmlspecialchars($rfcnum) . ' />
                    <input type="submit" value="Search Errata for RFC' . htmlspecialchars($rfcnum) . '" />
                    </form>';
          }
          break;
     default:
          $row = $stmt->fetch(PDO::FETCH_ASSOC);
          report_header($row['doc-id'],$row['title'],$row['pub-date']);
          #inline-errata link
          $eid_status = $row['errata_status_code'];  
          if ($eid_status == 'Verified'){

              list($rfc_value_name,$rfc_value_number,$length) = split_rfc($row['doc-id']);
              $formatted_rfc_number = sprintf('%s&nbsp;%d',htmlspecialchars($rfc_value_name),htmlspecialchars($rfc_value_number));
              
              $prefix = strtolower(substr($row['doc-id'],0,3));
              $number = substr($row['doc-id'],3);
              $inline_url = sprintf("%s%d.html",$prefix, $number);

	      $inline_errata_link = strtolower($row['doc-id']);
              print "See Also:"."&nbsp;<a href=\"https://www.rfc-editor.org/rfc/inline-errata/". htmlspecialchars($inline_url) ."\">". $formatted_rfc_number ." w/ inline errata</a>";         
          }
          print("<hr />\n");
          if ($row['section'] != null) {
             display_record($row);
                   } else {
             display_legacy_record($row);
           }
          $rfcnum = substr($row['doc-id'],3, MAX_RFC_DIGIT);
          print '
        <form method="get" action="/errata_search.php">
            <input type="hidden" name="rfc" value="' . htmlspecialchars($rfcnum) . ' />
            <input type="submit" value="Get All ' . htmlspecialchars($row['doc-id']) . ' Errata" />
        </form>';
     }
     return true;
}
/*
 * Funtion displays the errata with a button to edit a particular record.
 */
function errata_edit_lookup($rfc, $unverified_only=false, $form_set=EDIT_FORMS,
                            $use_eid=false, $eid=0) {
     global $debug_erlib;
     global $pdo;

     $doc_id = sprintf('%s%04d','RFC',$rfc);
     $query = 
          "SELECT errata_id, e.`doc-id`, title, `pub-date`,errata_type_code,
                  errata_status_code, submit_date, posted_date, verifier_id, 
                  verifier_name, verifier_email, submitter_name, 
                  submitter_email, orig_text, correct_text, e.notes, section,
                  rs_code, conv_format_check, insert_date, update_date, e.`format`
           FROM errata e, `index` i, errata_type_codes t , errata_status_codes s
           WHERE e.`doc-id` = i.`doc-id` AND e.type_id=t.errata_type_id AND 
                 e.status_id=s.errata_status_id AND e.`doc-id` = :doc_id";

     try {
          if ($unverified_only) {
             $query .= " AND s.errata_status_id=:status_reported_id";
          }

          if ($use_eid) {
             $query .= " AND e.errata_id = :eid";
          } else {
             $query .= " ORDER BY  type_id DESC, status_id, submit_date";
          }
          $stmt = $pdo->prepare($query);
          $stmt->bindParam('doc_id',$doc_id);
          if ($unverified_only){ 
              $stmt->bindValue('status_reported_id', STATUS_REPORTED);
          }
          if ($use_eid) {
             $stmt->bindParam('eid',$eid);
          }
          $stmt->execute();
          $num_of_rows = $stmt->rowCount();
     } catch (PDOException $pe){
         error_log("Error processing : errata_edit_lookup", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }

     if ($debug_erlib === true) {
          print('<h4>errata_edit_lookup</h4><p><pre>');
          print('$rfc= ');    var_dump($rfc);
          print('$unverified_only= '); var_dump($unverified_only);
          print("\$query=\n");  var_dump($query);
          print("\n\$form_set= "); var_dump($form_set); 
          print('</pre></p>');
     }

     $count = $num_of_rows;
     switch ($count) {
     case 0:
          print("<p>Found 0 records.</p>\n");
          break;
     default:
          $row = $stmt->fetch(PDO::FETCH_ASSOC);
          if ($form_set == EDIT_FORMS){
              print("<p><a href=\"errata_edit.php\">Search again</a> for errata.</p>\n");
          }else{
              print("<p><a href=\"verify_errata.php\">Search again</a> for more reported errata.</p>\n");
          }
          print "<div class=\"special_hr\"></div>";
          report_header($row['doc-id'],$row['title'],$row['pub-date']);
          print("<p>The following  ");
          if ($unverified_only) {
               print(($count == 1 ? "is a unverified erratum  " : " $count errata are unverified "));
          } else {
               print(($count == 1 ? "is an erratum  " : " $count errata reports are "));
          }
          print("for {$row['doc-id']}. ");
          do  {
               print('<div align="left">');
               print("<table style=\"background: inherit; margin: inherit;\">\n  <tr>\n    <td>\n");
               switch($form_set) {
               case EDIT_FORMS:
                    create_edit_form($row);
                    print("\n    </td>\n    <td>\n");
                    create_duplicate_form($row['errata_id'],$row['doc-id'],
                                          htmlspecialchars($row['title'],ENT_QUOTES),$row['pub-date']);
                    break;
               case VERIFY_FORMS:
                    create_verify_form($row);
                    print("\n    </td>\n    <td>\n");
/*                    create_hold_form($row);
                    print("\n    </td>\n    <td>\n");
                    create_reject_form($row);
                    print("\n    </td>\n    <td>\n");
*/
                    create_duplicate_form($row['errata_id'],$row['doc-id'],
                                          htmlspecialchars($row['title'],ENT_QUOTES),$row['pub-date'],
                                          "verify_errata.php");
                    break;
               default:
                    error_log("errata_edit_lookup: unknown \$form_set code: $form_set");
                    break;
               }
               print("\n    </td>\n  </tr>\n</table></div>\n");
               if ($row['section'] != null) {
     
                   display_record($row);
               } else {
                   display_legacy_record($row);
               }
          } while ($row = $stmt->fetch(PDO::FETCH_ASSOC));
          if ($use_eid) {
               print '
        <form method="post" action="verify_errata_select.php">
            <input type="hidden" name="rfc" value="' . htmlspecialchars($rfc) . '/>
            <input type="submit" name="query" value="Get All Unverified RFC' . htmlspecialchars($rfc) . ' Errata" />
        </form>';
          }
     }
     return true;
}

/*
 * Create a form with for verifier use
 */
function create_reject_form($row) {
     print<<<END
<form action="verify_reject.php" method="post">
  <input type="submit" name="submit" value="Reject" />

END;
     print('  <input type="hidden" name="rfcid" value="' . htmlspecialchars($row['doc-id']) . '" />' . "\n");
     add_verify_fields($row);
     print<<<END
</form>

END;
}

/*
 * Create a form with for verifier use
 */
function create_verify_form($row) {
     print<<<END
<form action="verify_errata.php" method="post">
  <input type="submit" name="submit" value="Edit" />

END;
     print('  <input type="hidden" name="rfcid" value="' . htmlspecialchars($row['doc-id']) . '" />' . "\n");
     add_verify_fields($row);

     print<<<END
</form>

END;
}

function add_verify_fields($row) {
     foreach($row as $key => $value) {
          switch ($key) {
          case "verifier_name": // added from $_SESSION later
          case "verifier_id":   // added from $_SESSION later
          case "verifier_email":// added from $_SESSION later
          case "posted_date":   // added from current date
               break;           // skip these
          default:
               print('  <input type="hidden" name="' . htmlspecialchars($key) . '" value="' . 
                     htmlspecialchars($value,ENT_QUOTES)        .
                     '" />' . "\n");
               break;
          }
     }

}
/*
 * Create a form for a verifier to "table" or defer a record.
 */
function create_hold_form($row) {
     global $errata_columns;
     global $errata_labels;
     global $debug_erlib;
     global $errata_status_from_id;

     print<<<END

<form action="verify_hold_report.php" method="post">
  <input type="submit" name="submit" value="Hold for Document Update" />

END;
     print('  <input type="hidden" name="rfcid" value="' . htmlspecialchars($row['doc-id']) . '" />' . "\n");
     add_verify_fields($row);

     print '<input type="hidden" name="verifier_name" value="' . htmlspecialchars($_SESSION['verifier_name']) . '" />';
     print '<input type="hidden" name="verifier_email" value="' . htmlspecialchars($_SESSION['verifier_email']) . '" />';
     print '<input type="hidden" name="verifier_id" value="' . htmlspecialchars($_SESSION['verifier_id']) . '" />';
     print '<input type="hidden" name="errata_status_code" value="' . htmlspecialchars($errata_status_from_id[STATUS_HELD]) . '" />';
     print '</form>';
}

/*
 * Create a form with all editable data
 */
function create_edit_form($row) {
     print<<<END
<form action="errata_edit.php" method="post">
  <input type="submit" name="submit" value="Edit" />

END;
     print('  <input type="hidden" name="rfcid" value="' . htmlspecialchars($row['doc-id']) . '" />' . "\n");
     foreach($row as $key => $value) {
          print('  <input type="hidden" name="' . htmlspecialchars($key) . '" value="' . 
                htmlspecialchars($value,ENT_QUOTES)        .
                '" />' . "\n");
     }

     print<<<END
</form>

END;
}

/*
 * Create form to create and edit a duplicate errata record.
 */
function create_duplicate_form($errata_id, $doc_id, $title, $pub_date, $dest="errata_edit.php" ) {
     print '
<form action="' . htmlspecialchars($dest) . '" method="post">
  <input type="submit" name="submit" value="Duplicate" />
';
     print("  <input type=\"hidden\" name=\"errata_id\" value=\"" . htmlspecialchars($errata_id) . "\" />\n");
     print("  <input type=\"hidden\" name=\"doc-id\" value=\"" . htmlspecialchars($doc_id) . "\" />\n");
     print("  <input type=\"hidden\" name=\"title\" value=\"" . htmlspecialchars($title) . "\" />\n");
     print("  <input type=\"hidden\" name=\"pub-date\" value=\"" . htmlspecialchars($pub_date) . "\" />\n");
     print("</form>");
}

/*
 * This form allows a general editing for update of an errata record
 */
function edit_full_record_form($data) {
     global $errata_types;
     global $errata_type_ids;
     global $errata_type_codes;
     global $errata_status_codes;
     global $errata_status_ids;
     global $errata_status_from_id;
     global $debug_erlib;

     if ($debug_erlib === true) {
          print("<h4>\$data in edit_full_record_form</h4><pre>\n");
          print_r($data);
          print("</pre>\n");
     }

     print<<<END
<form action="errata_edit_confirm.php" method="post">
  <table>
END;
     text_input('Date Submitted','submit_date',$data['submit_date'],false,false,MAX_DATE,MAX_DATE);
     text_input('Name', 'submitter_name', $data['submitter_name']);
   #  text_input('Email','submitter_email',$data['submitter_email'],false,false,MAX_EMAIL,40);
     text_email_input('Email','submitter_email',$data['submitter_email'],false,false,MAX_EMAIL,40);

     if (array_key_exists('errata_type_code',$data)) {
          select_input('Type', 'errata_type_code', 
                       $errata_types, $data['errata_type_code'], false);
     } else {
          $type_id = $data['type_id'];
          $selected_type = $errata_type_codes[$type_id];
          select_input('Type', 'errata_type_code', 
                       $errata_types, $selected_type, false);
     }

     if (array_key_exists('errata_status_code', $data)) {
          select_input('Status', 'errata_status_code',
                       $errata_status_codes, $data['errata_status_code'], false);
     } else {
          $status_id = $data['status_id'];
          $selected_code = $errata_status_from_id[$status_id];
          select_input('Status', 'errata_status_code',
                       $errata_status_codes, $selected_code, false);
     }

     text_input('Verified by', 'verifier_name', $data['verifier_name']);
     #text_input('Verifier Email','verifier_email',$data['verifier_email'],false,false,120,40);
     text_email_input('Verifier Email','verifier_email',$data['verifier_email'],false,false,120,40);
     text_input('Date Verified','posted_date',$data['posted_date'],false,false,20,20);

     text_input('Section', 'section', preg_replace("[\r\n]","",$data['section']), false, false, MAX_SECTION, MAX_TEXT);
     textarea_input("Original Text", "orig_text", $data['orig_text'],false ,10 ,80 ,'No' ,'Yes');
     textarea_input("Corrected Text", "correct_text", $data['correct_text'],false,10,80,'No','Yes');
     textarea_input("Notes", "notes", $data['notes'], false, 5);


     select_input('conv_format_check','conv_format_check', array("no","yes"), 
                  $data['conv_format_check'],false);
// Not editable in the web application
     print('<tr><td colspan="3"><strong>Fields not editable with web application.</strong></td></tr>');
     text_input('insert_date','insert_date',$data['insert_date'],true,false,20,20);
     text_input('update_date','update_date',$data['update_date'],true,false,20,20);
     text_input('rs_code','rs_code',$data['rs_code'],true,false,4,4);

     $errata_id = $data['errata_id'];
     $rfcid     = $data['rfcid'];
     $doc_id    = $data['doc-id'];
     $pub_date  = $data['pub-date'];
     // special handling for titles that contain special characters
     print '
      <tr>
        <td>
           <input type="hidden" name="errata_id" value="' . htmlspecialchars($errata_id) . '" />
           <input type="hidden" name="verifier_id" value="' . htmlspecialchars($_SESSION['verifier_id']). '" />
<!-- These fields to allow the display function to work -->
           <input type="hidden" name="rfcid" value="' . htmlspecialchars($rfcid) . '" />
           <input type="hidden" name="doc-id" value="' . htmlspecialchars($doc_id) . '" />
           <input type="hidden" name="pub-date" value="' . htmlspecialchars($pub_date) . '" />
           <input type="hidden" name="title" value="' . htmlspecialchars($data['title']) . '" />
        </td>
        <td><input type="submit" name="submit" value="Preview" /></td>
      </tr>
    </table>
</form>';
}

/*
 * This form allows a verifier to edit and accept a reported erratum.
 */
function verify_record_form($data) {
     global $errata_types;
     global $errata_type_ids;
     global $errata_type_codes;
     global $verify_status_codes;
     global $errata_status_ids;
     global $errata_status_from_id;
     global $debug_erlib;
     global $errata_status_codes;	
     global $formats;

//print_r($data);
     if ($debug_erlib === true) {
          print("<h4>\$data in verify_record_form</h4><pre>\n");
          print_r($data);
          print("</pre>\n");
     }
     $submitter_name = htmlspecialchars($data['submitter_name'],ENT_QUOTES);
     $submitter_email= htmlspecialchars($data['submitter_email'],ENT_QUOTES);
     print '
<p>
 Date Submitted: ' . htmlspecialchars($data["submit_date"]) . '<br />
 Submitted by: ' . htmlspecialchars($data['submitter_name']) . ', ' . htmlspecialchars($data['submitter_email']) . '
</p>
<hr />
<form name="verify_errata_confirm" action="verify_errata_confirm.php" method="post" onSubmit="return validateVerifyFields(this);">
  <table>';

     if (array_key_exists('errata_type_code',$data)) {
          select_input('Type', 'errata_type_code', 
                       $errata_types, $data['errata_type_code'], false);
     } else {
          $type_id = $data['type_id'];
          $selected_type = $errata_type_codes[$type_id];
          select_input('Type', 'errata_type_code', 
                       $errata_types, $selected_type, false);
     }

     if (array_key_exists('errata_status_code', $data)) {
/*          select_input('Status', 'errata_status_code',
                       $verify_status_codes, $data['errata_status_code'], false);
*/
          select_input('Status', 'errata_status_code',
                       $errata_status_codes, $data['errata_status_code'], false);

     } else {
          $status_id = $data['status_id'];
          $selected_code = $errata_status_from_id[$status_id];
/*          select_input('Status', 'errata_status_code',
                       $verify_status_codes, $selected_code, false);
*/
          select_input('Status', 'errata_status_code',
                       $errata_status_codes, $selected_code, false);

     }

     text_input('Section', 'section', preg_replace("[\r\n]","",$data['section']), false, true, 512, 20);


    if (array_key_exists('format',$data)){
 

       $searchString = ',';

       if( strpos($data['format'], $searchString) !== false ) {
            $current_format = array_map('trim', explode(',',$data['format']));
       }else {

            $current_format = json_decode($data['format']);
            if (empty($current_format)){
                foreach ($data['format'] as $key => $value){
                   array_push($current_format,$value);
                }
            }
        }

       list($rfc_value_name,$rfc_value_number,$length) = split_rfc($data['rfcid']); 
       
       if ($rfc_value_number >= '8650') {
          print("<tr><td>Publication Format(s):</td>\n");
          select_checkbox($formats, $current_format,false);
           print "</tr>";    
      }
}
     textarea_input("Original Text", "orig_text", $data['orig_text'],false ,10 ,80 ,'No' ,'Yes');
     textarea_input("Corrected Text", "correct_text", $data['correct_text'],false,10,80,'No','Yes');
     textarea_input("Notes", "notes", $data['notes'], false, 5);


     $posted_date = date("Y-m-d");

     print '
      <tr>
        <td>
           <input type="hidden" name="verifier_id" value="' . htmlspecialchars($_SESSION['verifier_id']) . '" />
           <input type="hidden" name="errata_id" value="' . htmlspecialchars($data['errata_id']) . '" />
<!-- These fields to allow the display function to work -->
           <input type="hidden" name="submit_date" value="' . htmlspecialchars($data['submit_date']) . '" />
           <input type="hidden" name="submitter_name" value="' . htmlspecialchars($data['submitter_name']) . '" />
           <input type="hidden" name="submitter_email" value="' . htmlspecialchars($data['submitter_email']). '" />
           <input type="hidden" name="posted_date" value="' . htmlspecialchars($posted_date). '" />
           <input type="hidden" name="verifier_name" value="' . htmlspecialchars($_SESSION['verifier_name']) . '" />
           <input type="hidden" name="verifier_email" value="' . htmlspecialchars($_SESSION['verifier_email']) . '" />
           <input type="hidden" name="rfcid" value="' . htmlspecialchars($data['rfcid']) . '" />
           <input type="hidden" name="doc-id" value="' . htmlspecialchars($data['doc-id']) . '" />
           <input type="hidden" name="pub-date" value="' . htmlspecialchars($data['pub-date']) . '" />
           <input type="hidden" name="title" value="' . htmlspecialchars($data['title']) . '" />
        </td>
        <td>
            <input type="submit" name="submit" id="submit1" value="Preview" onclick="setChecked(this);" />
        </td>
    </table>
</form>';
}

/*
 * Display a form to get the reason for a rejection.
 */
function verify_reject_form($data,$dest="errata_reject.php") {
     $posted_date = date("Y-m-d");
     print '
<p>Please provide a reason why this report is being rejected. This will be included in the report as "Verifier Notes".</p>
<form  action="'.$dest.'" method="post" onsubmit="return verifyRejection(this);">
   <textarea cols="80" rows="5" name="reason">
   </textarea>
   <input type="submit" name="submit" value="Continue to Reject" />
   <input type="hidden" name="errata_status_code" value="Rejected" />
   <input type="hidden" name="posted_date" value="' . htmlspecialchars($posted_date) . '" />
   <input type="hidden" name="verifier_id" value="' . htmlspecialchars($_SESSION['verifier_id']) . '" />
   <input type="hidden" name="verifier_name" value="' . htmlspecialchars($_SESSION['verifier_name']) . '" />
   <input type="hidden" name="verifier_email" value="' . htmlspecialchars($_SESSION['verifier_email']) . '" />';

     foreach ($data as $key => $value) {
          if ($key != 'submit' && $key != 'errata_status_code' && $key != 'format' ) {
               print('  <input type="hidden" name="' . htmlspecialchars($key) . '" value="' .
                     htmlspecialchars($value,ENT_QUOTES) . '" />' . "\n");
          }elseif ($key == 'format'){
                       print('  <input type="hidden" name="'. htmlspecialchars($key) . '"  value="' . htmlspecialchars(json_encode($value)) . '" />' . "\n");
               }

     }

    //For super user entry need this variable
     if ($dest == 'errata_reject.php'){
     print'<input type="hidden" name="super_user" value="yes" />';
     }
    
     print<<<END
</form>

<script type="text/javascript">
<!--
          document.forms.rejection.reason.focus();
//-->
</script>

END;

}
/*
*A function to decide which path has been taken by the verifier for 
*the edited data (VERIFIEFY/HELD/REJECT)
*/
function path_selector($data){

     global $errata_types;
     global $errata_type_ids;
     global $errata_status_codes;
     global $errata_status_ids;
     global $debug_erlib;
     

     if ($debug_erlib === true) {
          print("<h4>path_selector: \$data</h4><pre>\n");
          print_r($data);
          print("</pre>\n");
     }
     switch ($errata_status_ids[$data['errata_status_code']]) {
          case STATUS_REJECTED:
		   //Add title specifying the dynamic page 
                  add_filter( 'wp_title', 'wp_rfc_errata_rejection', 10, 3 );

                  function wp_rfc_errata_rejection( $title, $sep, $seplocation ) {
                   return 'RFC Errata Rejection &raquo; RFC Editor';
                   }

                   /*Add Wordpress header and Sidebar*/
                   get_header();
                   get_sidebar('left');

                   /*Add DIV use with wordpress for content*/
?>
     
<div id="content" class="narrowcolumn">
     <h1 class="post_title">Reject Errata eport for <?php print htmlspecialchars($data['rfcid']); ?></h1>
     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">
<?php
	          errata_edit_header("RFC Errata Rejection","rfcerrata","scripts/validate_rejection.js","verify_errata.php");
#		  print("<p>Please provide a reason why this report is being rejected.</p>\n");
#		  print("<p>The reason will be used used in a notification email to the relevant parties.</p>\n");
		  report_header($data['rfcid'],$data['title'],$data['pub-date']);
		  display_record($data);
		  print("<table style=\"background: inherit;\">\n<tr>\n");
		  print("<td>\n");
		  verify_reject_form($data,'verify_db_update.php');
		  print("\n</td>\n</tr><tr>");
		  print("<td>\n");
		  print<<<END
		       <form action="verify_errata.php" method="post">
		       <input type="submit" name="cancel" value="Cancel" />
		       </form>
END;
		  print("\n</td>\n");
		  print("\n</tr></table>\n");
               break;
          default:              // This case is for VERIFIED/HELD/REPORTED
		  
                  //Add title specifying the dynamic page 
                  add_filter( 'wp_title', 'wp_preview_edit', 10, 3 );

                  function wp_preview_edit( $title, $sep, $seplocation ) {
                   return 'Preview Edited Record&raquo; RFC Editor';
                   }

                   /*Add Wordpress header and Sidebar*/
                   get_header();
                   get_sidebar('left');

                   /*Add DIV use with wordpress for content*/
?>
     
<div id="content" class="narrowcolumn">
     <h1 class="post_title">Preview</h1>
     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">
<?php
                  errata_edit_header("Preview Edited Record","rfcerrata","NONE","verify_errata.php");
                  print<<<END
			<p>
			Below is how the edited report will appear when posted. If it is correct,
			click "Post". If there is an error, click "Edit Again".  If you click "Cancel",
			your edits will not be saved. If you have changed the status, a
			notification email will be sent to the relevant parties.
			</p>
			<hr />
END;
		  $rfcid    = substr($data['rfcid'], 0, MAX_RFC);
                  $doc_id   = substr($data['doc-id'], 0, MAX_RFC);
                  $pub_date = substr($data['pub-date'], 0, MAX_DATE);
                  $title    = $data['title'];

	     	  report_header($rfcid,$title,$pub_date);
	          display_record($data);

		  print("<table>\n<tr>\n");
		  print("<td>\n");
		  update_record_form($data, 'verify_db_update.php');
		  print("\n</td>\n");
		  print("<td>\n");
		  edit_errata_record_again($data, 'verify_errata.php');
		  print("\n</td>\n");
		  print("<td>\n");
		  print<<<END
		  <form name="abort" action="verify_errata.php" method="post">
		      <input type="submit" name="cancel" value="Cancel">
		  </form>
END;
		  print("\n</td>\n");
		  print("\n</tr></table>\n");

               break;
    }
}


/*
 * Write out a form with the edited data ready to be sent back to the
 * edit screen.
 */
function edit_errata_record_again($data, $dest="errata_edit.php") {
     print '
<form action="' . htmlspecialchars($dest) . '" method="post">
  <input type="submit" name="submit" value="Edit Again" />
';
     foreach ($data as $key => $value) {
          if ($key != 'submit') {
         /*      print('  <input type="hidden" name="' . $key . '" value="' .
                     htmlspecialchars($value,ENT_QUOTES) . '" />' . "\n");
          }
*/
               print('<input type="hidden" name="' . htmlspecialchars($key) . '"');
               switch ($key) {
                  case 'format':
                       print('value="' . htmlspecialchars(json_encode($value)) . '" />' . "\n");
                       break; 
                   default:
                       print('value="' . htmlspecialchars(trim($value,ENT_QUOTES)) . '" />' . "\n");
                       break;
               }
          }

     }
     print("</form>\n");
}

function update_record_form($data, $dest='errata_update.php') {
     print '
<form action="' . htmlspecialchars($dest) . '" method="post">
  <input type="submit" name="submit" value="Post" />
';

/*     foreach ($data as $key => $value) {
          if ($key != 'submit') {
               print('  <input type="hidden" name="' . $key . '" value="' .
                     htmlspecialchars($value,ENT_QUOTES) . '" />' . "\n");
          }
     }
*/

    foreach ($data as $key => $value) {
          if ($key != 'submit' && $key != 'format' ) {
               print('  <input type="hidden" name="' . htmlspecialchars($key) . '" value="' .
                     htmlspecialchars($value,ENT_QUOTES) . '" />' . "\n");
          }elseif ($key == 'format'){
                       print('  <input type="hidden" name="'. htmlspecialchars($key) . '"  value="' . htmlspecialchars(json_encode($value)) . '" />' . "\n");
               }

     }


     print("</form>\n");

}

/*
 * Return the existing data for an errata record. Used for comparing
 * new/old for edit updates.
 */
function retrieve_editable_field_data($errata_id) {
     global $pdo;
     
    try { 
        $query =
               "SELECT errata_id, e.`doc-id`, e.`doc-id` AS rfcid, type_id, status_id,
                    submit_date, posted_date, verifier_id, verifier_name,
                    verifier_email, submitter_name, submitter_email, orig_text,
                    correct_text, e.notes, section, title, `pub-date`,e.`format`
               FROM errata e, `index` i
               WHERE errata_id = :errata_id AND i.`doc-id` = e.`doc-id`";
     
        $stmt = $pdo->prepare($query);
        $stmt->bindParam('errata_id',$errata_id);
        $stmt->execute();
        $num_of_rows = $stmt->rowCount();
     }catch (PDOException $pe){
         error_log("Error processing : retrieve_editable_field_data", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }

     $count = $num_of_rows;
     if ($count == 1) {
          $row = $stmt->fetch(PDO::FETCH_ASSOC);
     } else {
          return "Error in retrieve_editable_field_data! $count rows returned for errata_id=$errata_id";
     }
     return $row;
}
/*
 * Return the most recent errata entry for a document. Used by the "Duplicate and Edit" function of
 * errata_edit.php.
 */
function retrieve_most_recent_insert($doc_id) {
     global $pdo;
     try {
         $query =
             "SELECT errata_id, `doc-id`, type_id , status_id,
                  submit_date, posted_date, verifier_id, verifier_name,
                  verifier_email, submitter_name, submitter_email, orig_text,
                  correct_text, notes, section,  rs_code, insert_date,
                  update_date, conv_format_check, `format`
             FROM errata
             WHERE `doc-id` = :doc_id";
         $query .= " ORDER BY insert_date DESC LIMIT :limit_no";
         $stmt = $pdo->prepare($query);
         $stmt->bindParam('doc_id',$doc_id);    
         $stmt->bindValue('limit_no',1);    
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
     }catch (PDOException $pe){
         error_log("Error processing : retrieve_most_recent_insert", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }

     $count = $num_of_rows;
     switch ($count)
     {
     case 0:
          $row = "retrieve_most_recent_insert: No row returned for doc-id=$doc_id";
          break;
     case 1:
          $row = $stmt->fetch(PDO::FETCH_ASSOC);
          break;
     default:
          $row = "retrieve_most_recent_insert: Multiple rows returned for doc-id=$doc_id";          
          break;
     }
     return $row;
}


/*
 * Return all the existing data for an errata record.
 */
function retrieve_all_field_data($errata_id) {
     global $pdo;

     try {
         $query =
             "SELECT errata_id, e.`doc-id`, e.`doc-id` AS rfcid, type_id , status_id,
                  e.submit_date, e.posted_date, verifier_id, verifier_name,
                  verifier_email, submitter_name, submitter_email, orig_text,
                  correct_text, e.notes, section,  rs_code, e.insert_date,
                  e.update_date, conv_format_check, title, `pub-date`
             FROM errata e, `index` i
             WHERE errata_id = :errata_id AND e.`doc-id` = i.`doc-id`";

         $stmt=$pdo->prepare($query);
         $stmt->bindParam('errata_id',$errata_id);
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
     }catch (PDOException $pe){
         error_log("Error processing : retrieve_all_field_data", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }
    
     $count = $num_of_rows;
     switch ($count)
     {
     case 0:
          $row = "retrieve_all_field_data: No row returned for errata_id=$errata_id";
          break;
     case 1:
          $row = $stmt->fetch(PDO::FETCH_ASSOC);
          break;
     default:
          $row = "retrieve_all_field_data: Multiple rows returned for errata_id=$errata_id";
          break;
     }
     return $row;
}


/*
 * Duplicate a report record. Aids data cleanup for transition form 
 * CGI, text-based errata system.
 */
function duplicate_report($errata_id) {
     global $pdo;
     try {
         $statement =
             "INSERT INTO errata (`doc-id`, status_id, type_id,section,orig_text,
              correct_text,submitter_name,submitter_email,notes,submit_date,
              posted_date,verifier_id,verifier_name,verifier_email,
              rs_code, conv_format_check,`format`)
              SELECT `doc-id`, status_id, type_id, section,orig_text,
              correct_text,submitter_name,submitter_email,notes,submit_date,
              posted_date,verifier_id,verifier_name,verifier_email,
              rs_code, conv_format_check, `format` FROM errata WHERE errata_id = :errata_id";

         $stmt=$pdo->prepare($statement);
         $stmt->bindParam('errata_id',$errata_id);
         $stmt->execute();
         $num_of_rows = $stmt->rowCount() ;
     }catch (PDOException $pe){
         error_log("Error processing : duplicate_report", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }

     if ($num_of_rows > 0){
       $status = TRUE;
     }else {
       $status = FALSE;
     }

     return $status;

}

/*
 * Function takes the form data, builds and executes a SQL UPDATE for
 * each of the fields that are detected as changed from the stored record.
 */
function update_errata_record($data) {
     global $errata_types;
     global $errata_type_ids;
     global $errata_status_codes;
     global $errata_status_ids;
     global $debug_erlib;
     global $pdo; 

     if ($debug_erlib === true) {
          print("<h4>update_errata_record: \$data</h4><pre>\n");
          print_r($data);
          print("</pre>\n");
     }

     $errata_id = $data['errata_id'];
     $old_data = retrieve_editable_field_data($errata_id);
     if (is_string($old_data)) {
          return $old_data;     // Error!
     }
     if ($debug_erlib === true) {
          print("<h4>update_errata_record: \$old_data</h4><pre>\n");
          print_r($old_data);
          print("</pre>\n");
     }
     $old_type_id = $old_data['type_id'];
     $new_type_id = $errata_type_ids[$data['errata_type_code']];
     $old_status_id = $old_data['status_id'];
     $new_status_id = $errata_status_ids[$data['errata_status_code']];

     $template = null;
     if ($old_type_id != $new_type_id) {
          $template = "SET type_id = :new_type_id";
     }
     if ($old_status_id != $new_status_id) {
          if ($template === null) {
               $template = "SET status_id = :new_status_id";
          } else {
               $template .= ", status_id = :new_status_id";
          }
     }

     #Get the format values from the array to the format key
     if (array_key_exists('format',$data)){
         $format_array = json_decode($data['format']);
             if (empty($format_array)){
                foreach ($data['format'] as $key => $value){
                  array_push($format_array,$value);
                }
             }  
     }

     foreach ($format_array as $format){
              if ($format === end($format_array)){
                    $format_list .= $format;
               }else {
                    $format_list .= $format.', ' ;
               }
     }
     
     list($rfc_value_name,$rfc_value_number,$length) = split_rfc($data['rfcid']);
  
     if ($rfc_value_number >= '8650'){ 
         $data['format'] = $format_list;
      }else {
        $data['format'] = 'TEXT';
      }

    if ($debug_erlib === true) {
          print("<h4>update_errata_record:data with format updates \$data</h4><pre>\n");
          print_r($data);
          print("</pre>\n");
     }

     # this section is flawed.  data dictionary contains fields like "title"
     # which are not fields in the errata table and so should not be added 
     # to this sql statement
     foreach ($data as $key => $value) {
          if (array_key_exists($key, $old_data) && $value != $old_data[$key]) {
               if ($template === null) {
                       $template = "SET " . $key . "= :" . $key;
               } else {
                       $template .= ", " . $key . "= :" . $key; 
               }
          }
     }
     
     if ($debug_erlib === true) {
          print("<h4>\$template</h4>\n<pre>");
          print_r($template);
          print("</pre>\n");
     }

     if ($template) {
        try
        {
              $query = "UPDATE errata $template WHERE errata_id=:errata_id";
              $stmt = $pdo->prepare($query);
              if ($old_type_id != $new_type_id) {
                 $stmt->bindParam('new_type_id',$new_type_id) ;
              }
              if ($old_status_id != $new_status_id) {
                 $stmt->bindParam('new_status_id',$new_status_id) ;
              }
              foreach ($data as $key => $value) {
                 if (array_key_exists($key, $old_data) && $value != $old_data[$key]) {
                        $stmt->bindValue($key,$value) ;
                 }
              }
              $stmt->bindParam('errata_id',$errata_id);
              $stmt->execute();
              $num_of_rows = $stmt->rowCount();

         } catch (PDOException $pe){
              error_log("Error processing : update_errata_record", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
         }

         if ($debug_erlib === true) {
               print("<h4>update_errata_record: \$query</h4><pre>\n");
               print_r($query);
               print("</pre>\n");
         }
        
         if ($num_of_rows > 0){
             $status = true;
         }else {
             $status = false;
         }

     } else { 
          // Nothing to, log anomaly and return true.
          error_log("Function update_errata_record called with no changed data. Database update skipped.");
          $status = true;
     }

     return $status;
}



/*
 * For :Super user use only
 * Function takes the form data, builds and executes a SQL UPDATE for
 * each of the fields that are detected as changed from the stored record.
 *
 */
function update_errata_superuser_record($data) {
     global $errata_types;
     global $errata_type_ids;
     global $errata_status_codes;
     global $errata_status_ids;
     global $debug_erlib;
     global $pdo;
 
     if ($debug_erlib === true) {
          print("<h4>update_errata_superuser_record: \$data</h4><pre>\n");
          print_r($data);
          print("</pre>\n");
     }

     $errata_id = $data['errata_id'];
     $old_data = retrieve_editable_field_data($errata_id);
     if (is_string($old_data)) {
          return $old_data;     // Error!
     }

     if ($debug_erlib === true) {
          print("<h4>update_errata_superuse_record: \$old_data</h4><pre>\n");
          print_r($old_data);
          print("</pre>\n");
     }

     $old_type_id = $old_data['type_id'];
     $new_type_id = $errata_type_ids[$data['errata_type_code']];
     $old_status_id = $old_data['status_id'];
     $new_status_id = $errata_status_ids[$data['errata_status_code']];

     $template = null;
     if ($old_type_id != $new_type_id) {
          $template = "SET type_id = :new_type_id";
     }

     if ($old_status_id != $new_status_id) {
          if ($template === null) {
               $template = "SET status_id = :new_status_id";
          } else {
               $template .= ", status_id = :new_status_id";
          }
          $status_change = TRUE;
     }


   if ($debug_erlib === true) {
          print("<h4>update_errata_superuse_record: \$old_status_id</h4><pre>\n");
          print_r($old_status_id);
          print("</pre>\n");
          print("<h4>update_errata_superuse_record: \$new_status_id</h4><pre>\n");
          print_r($new_status_id);
          print("</pre>\n");
          print("<h4>Status Change</h4><pre>\n");
          print($status_change);
          print("</pre>\n");

     }

     # this section is flawed.  data dictionary contains fields like "title"
     # which are not fields in the errata table and so should not be added 
     # to this sql statement
     foreach ($data as $key => $value) {
          if (array_key_exists($key, $old_data) && $value != $old_data[$key]) {
               if ($template === null) {
                    $template = "SET " . $key . "= :" . $key;
               } else {
                    $template .= ", " . $key . "= :" . $key;
               }
          }
     }
     
     if ($debug_erlib === true) {
          print("<h4>\$template</h4>\n<pre>");
          print_r($template);
          print("</pre>\n");
     }
     

     if ($template) {
     try
        {
              $query = "UPDATE errata $template WHERE errata_id=:errata_id";
              $stmt = $pdo->prepare($query);
              if ($old_type_id != $new_type_id) {
                 $stmt->bindParam('new_type_id',$new_type_id) ;
              }
              if ($old_status_id != $new_status_id) {
                 $stmt->bindParam('new_status_id',$new_status_id) ;
              }
              foreach ($data as $key => $value) {
                 if (array_key_exists($key, $old_data) && $value != $old_data[$key]) {
                        $stmt->bindValue($key,$value) ;
                 }
              }
              $stmt->bindParam('errata_id',$errata_id);
              $stmt->execute();
              $num_of_rows = $stmt->rowCount();

         } catch (PDOException $pe){
              error_log("Error processing : update_errata_superuser_record", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
         }

         if ($debug_erlib === true) {
               print("<h4>update_errata_record: \$query</h4><pre>\n");
               print_r($query);
               print("</pre>\n");
         }
        
         if ($num_of_rows > 0){
             $status = true;
         }else {
             $status = false;
         }

     } else { 
          // Nothing to, log anomaly and return true.
          error_log("Function update_errata_superuser_record called with no changed data. Database update skipped.");
          $status = true;
     }


     return array($status,$status_change);
}



/*
 * This function called if no records for an RFC exist in the errata table.
 * The title and pub-date might still be needed if a new errata report is
 * going to be made.
 *
 * Returns an associative array of data if found, else false with an error
 * message stored in the SESSION object.
 */
function get_title_pub_date($rfc) {
     global $pdo;    

     $rfc_num = sprintf('%s%04d','RFC',$rfc);
     
     try {
     $query =
          "SELECT title, `pub-date` FROM `index` WHERE `doc-id` = :rfc_num";
         $stmt = $pdo->prepare($query);
         $stmt->bindParam('rfc_num',$rfc_num);
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
     } catch (PDOException $pe){
         error_log("Error processing :  get_title_pub_date", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
         $_SESSION['errata_lib_error'] = "Bad query: get_title_pub_date";
         return false;
     }

     $count = $num_of_rows;
     switch ($count) {
     case 0:
          $rfchead = false;
          $_SESSION['errata_lib_error'] = "RFC". $rfc . ": Not found. It is not possible to submit errata for an RFC that does not exist.";
          break;
     case 1:
          $row = $stmt->fetch(PDO::FETCH_ASSOC);
          $rfchead = array("pub-date" => $row['pub-date'], "title" => trim($row['title']));
          break;
     default:
          $_SESSION['errata_lib_error']  = "Multiple matches for RFC" . $rfc . ". ";
          $_SESSION['errata_lib_error'] .= "Please report this error to the RFC Editor.";
          $rfchead = false;
          break;
     }
     return $rfchead;
}
/*
 * Inserts a new report record into the errata table
 */
function insert_errata_report($form_data) {
     global $debug_erlib, $errata_type_ids, $errata_table_columns, $errata_status_ids,$pdo;
     $format_array = array();
     $format_list = '';

     if (array_key_exists('doc-id',$form_data)) {
          $num = substr($form_data['doc-id'],3);
          $form_data['doc-id'] = sprintf("RFC%04s",$num);
     }

     if (array_key_exists('format',$form_data)){
        $format_array = json_decode($_POST['format']);
             if (empty($format_array)){
                foreach ($_POST['format'] as $key => $value){
                  array_push($format_array,$value);
                }
             }  
     }
     
     foreach ($format_array as $format){
              if ($format === end($format_array)){
                    $format_list .= $format;
               }else {
                    $format_list .= $format.', ' ;
               }
     }
   
     list($rfc_value_name,$rfc_value_number,$length) = split_rfc($form_data['rfcid']);

     if ($rfc_value_number >= '8650'){ 
         $form_data['format'] = $format_list;
     }else {
         $form_data['format'] = 'TEXT';
     }

     $insert =  "INSERT INTO errata (";
     
     foreach ($errata_table_columns as $column) {
          if (array_key_exists($column,$form_data) && 
              strlen($form_data[$column]) > 0) {
               $insert .= "`" . $column . "`,"; // Quote all fields for the odd ones
          }
     }
     // The status id will default to 1 ("Reported") if not present 
     // in the form data
     $has_status = (array_key_exists('errata_status_code', $form_data) && 
                    isset($form_data['errata_status_code']));
     if ($has_status === true) {
          $insert .= "status_id, ";
     }
     // The type_id is derived from the errata_type_code in the form.
     // Tack it on at the end, as we will below
     $insert .="type_id) ";

     $insert .= "VALUES (";

     foreach ($errata_table_columns as $column) {
          if (array_key_exists($column,$form_data) && 
              strlen($form_data[$column]) > 0) {
               if ($column == 'doc-id')
               {
                   $column = 'doc_id';
               }
               $insert .= ":" . $column .", ";
          }
     }
     if ($has_status === true) {
          $status_id = $errata_status_ids[$form_data['errata_status_code']];
          $insert .= ":status_id , ";
     }
     // Convert the type code in the form to the integer used in the table
     $type_id = $errata_type_ids[$form_data['errata_type_code']];
     $insert .=  ":type_id )";

    
     if ($debug_erlib === true) {
          print("<h4>insert_errata_report</h4>\n");
          print("<pre>\$query=\n"); print_r($insert); print("\n</pre>\n");
          ob_flush(); flush();
     }
     

     try {
         $stmt = $pdo->prepare($insert);
         foreach ($errata_table_columns as $column) {
             if (array_key_exists($column,$form_data) && 
                 strlen($form_data[$column]) > 0) {
                 if ($column == 'doc-id')
                 {
                     $stmt->bindValue('doc_id',$form_data[$column]);
                 } else {
                     $stmt->bindValue($column,$form_data[$column]);
                 }
             }
         }
         if ($has_status === true) {
             $status_id = $errata_status_ids[$form_data['errata_status_code']];
             $stmt->bindParam('status_id',$status_id);
         }
     
         $stmt->bindParam('type_id',$type_id);
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
     } catch (PDOException $pe){
              error_log("Error processing : insert_errata_report", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }

     if ($num_of_rows > 0){
             $status = true;
     }else {
             $status = false;
     }


     if ($status === false) {
          $remote_host = gethostbyaddr($_SERVER['REMOTE_ADDR']);
          error_log("errata_lib: db error servicing request from {$_SERVER['REMOTE_ADDR']} ($remote_host) " .
                    "globals HTTP_HOST={$_SERVER['HTTP_HOST']} SERVER_NAME={$_SERVER['SERVER_NAME']}");
          return "Bad insert:\n" . htmlspecialchars($insert);
     }
     return $status;
}


/*
 * Creates a form with one visible button that will return the data back
 * to the originating form.
 */
function edit_report_again_form($form_data, $dest="errata_report.php") {
     global $errata_columns;
     
     print '
             <form action="' . htmlspecialchars($dest) . '" method="post">
                <input type="submit" name="submit" value="Edit this Errata Report" />';


     if (array_key_exists('rfcnum',$form_data)) {
          print('<input type="hidden" name="rfcnum"');
               print('value="' . htmlspecialchars($form_data['rfcnum']) . '" />' . "\n");
     }

     // This happens because some fields have ambiguous syntax
     // because of the dash.
     if (array_key_exists('rfcid',$form_data)) {
          print('<input type="hidden" name="rfcid"');
               print('value="' . htmlspecialchars($form_data['rfcid']) . '" />' . "\n");
     }

     if (array_key_exists('pub-date',$form_data)) {
          print('<input type="hidden" name="pub-date"');
               print('value="' . htmlspecialchars($form_data['pub-date']) . '" />' . "\n");
     }

     if (array_key_exists('title',$form_data)) {
          print('<input type="hidden" name="title"');
          print('value="' . htmlspecialchars($form_data['title'],ENT_QUOTES) . '" />' . "\n");
     }
     // iterate over the form and put out all of the values so far.
     foreach ($errata_columns as $key) {
          if (array_key_exists($key, $form_data)) {
               print('<input type="hidden" name="' . htmlspecialchars($key) . '"');
               switch ($key) {
               case 'format':
                    print('value="' . htmlspecialchars(json_encode($form_data[$key])) . '" />' . "\n");
                    break; 
               case 'orig_text':
               case 'correct_text':
                    print('value="' . htmlspecialchars($form_data[$key],ENT_QUOTES) . '" />' . "\n");
                    break;
               default:
                    print('value="' . htmlspecialchars(trim($form_data[$key]),ENT_QUOTES) . '" />' . "\n");
                    break;
               }
          }
     }
     print("</form>\n");
}


/*
 * This function write part of a form with filled values before
 * user decides to cancel/edit the form or submit it by sloving
 * captcha..
 */
function insert_report_captcha_form($form_data, $dest="errata_insert.php") {
     global $errata_columns;

     // This happens because some fields have ambiguous syntax
     // because of the dash.
     if (array_key_exists('rfcid',$form_data)) {
          print('<input type="hidden" name="rfcid"');
               print('value="' . htmlspecialchars($form_data['rfcid']) . '" />' . "\n");
     }

     if (array_key_exists('pub-date',$form_data)) {
          print('<input type="hidden" name="pub-date"');
               print('value="' . htmlspecialchars($form_data['pub-date']) . '" />' . "\n");
     }

     if (array_key_exists('title',$form_data)) {
          print('<input type="hidden" name="title"');
          print('value="' . htmlspecialchars(trim($form_data['title']),ENT_QUOTES) . '" />' . "\n");
     }

     // iterate over the form and put out all of the values so far.
     foreach ($errata_columns as $key) {
          if (array_key_exists($key, $form_data)) {
               print('<input type="hidden" name="' . htmlspecialchars($key) . '"');
               switch ($key) {
               case 'format':
                    print('value="' . htmlspecialchars(json_encode($form_data[$key])) . '" />' . "\n");
                    break;
               case 'orig_text':
               case 'correct_text':
                    print('value="' . htmlspecialchars($form_data[$key],ENT_QUOTES) . '" />' . "\n");
                    break;
               default:
                    print('value="' . htmlspecialchars(trim($form_data[$key]),ENT_QUOTES) . '" />' . "\n");
                    break;
               }
          }
     }

}

/*
 * Format one row for display on page.
 */
function display_record($rec) {
     global $errata_labels;
     
     print("<p>\n");
     if (array_key_exists('errata_id',$rec)) {
          display_errata_id('errata_id',$rec,"<br /><br />");
     }
     print("<b>");
     if (array_key_exists('errata_status_code',$rec)) {
          display_field('errata_status_code',$rec);
     }
     display_field('errata_type_code',$rec);

     if ($rec['rfcid']){
          list($rfc_value_name,$rfc_value_number,$length) = split_rfc($rec['rfcid']);
      }else {
          list($rfc_value_name,$rfc_value_number,$length) = split_rfc($rec['doc-id']);
      }
     if ($rfc_value_number >= '8650') {
         if (array_key_exists('format',$rec)){
            print "Publication Format(s) :";
         
             if (is_array($rec['format'])){
                foreach ($rec['format'] as $format){
                   if ($format === end($rec['format'])){
                       print " " . htmlspecialchars($format) . "<br />";
                   }else {
                       print " " . htmlspecialchars($format) . ",";
                   }
                }
             }else {
                 print " " . htmlspecialchars($rec['format']);
             }
         }
     }else {
          print "Publication Format(s) : TEXT";
     }

          print("</b><br />\n");
     display_field('submitter_name',$rec);
     display_field('submit_date',$rec);

     if (array_key_exists('verifier_name',$rec) &&
         $rec['verifier_name'] != null) {
          display_verifier_name($rec);
     }
     if (array_key_exists('posted_date',$rec) && 
         $rec['posted_date'] != null &&
         $rec['posted_date'] != '0000-00-00') {
          display_posted_date($rec);
     }
     print("</p>\n");

     print("<p>");
     if ($rec['section'] == 'GLOBAL') {
          print("Throughout the document, when it says:\n");
     } else if ((strpos($rec['section'], "99")) === false) {
          print('Section ' . htmlspecialchars($rec['section']) . " says:\n");
     } else {
          if (strlen($rec['section']) > 2) {
               print(htmlspecialchars(substr($rec['section'],2)));
          } else {
               print("&nbsp;");
          }
     }
     print("</p>\n");
     print("<pre class=\"rfctext\">\n" .
           htmlspecialchars($rec['orig_text']) . "\n</pre>\n");

     if ($rec['correct_text'] != null) {
          print("<p>It should say:</p>\n");
          print("<pre class=\"rfctext\">\n" .
                htmlspecialchars($rec['correct_text']) . "\n</pre>\n");
     }
     if ($rec['notes'] != null) {
          print("<p>Notes:</p>\n");
          print("<p class=\"simpleindent\">\n");  // NOT in the rfctext class
          print(nl2br(htmlspecialchars($rec['notes'])) . "\n</p>\n");
     }
          
     //print("<hr />\n");
     print "<div class=\"special_hr\"></div>";
}

/*
 * Format one legacy row for display on page.
 */
function display_legacy_record($rec) {
     global $errata_labels;
  
     print('<p>');
     if (array_key_exists('errata_id',$rec)) {
          display_field('errata_id',$rec,"<br /><br />");
     }
     print("<b>");
     if (array_key_exists('errata_status_code',$rec)) {
          display_field('errata_status_code',$rec);
     }
     display_field('errata_type_code',$rec);

     if ($rec['rfcid']){
          list($rfc_value_name,$rfc_value_number,$length) = split_rfc($rec['rfcid']);
      }else {
          list($rfc_value_name,$rfc_value_number,$length) = split_rfc($rec['doc-id']);
      }
     if ($rfc_value_number >= '8650') {
         if (array_key_exists('format',$rec)){
             print "Publication Format(s) :";
             if (is_array($rec['format'])){
                 foreach ($rec['format'] as $format){
                    if ($format === end($rec['format'])){
                        print " " . htmlspecialchars($format) . "<br />";
                    }else {
                        print " " . htmlspecialchars($format) . ",";
                    }
                 }
             }else {
                  print " " . htmlspecialchars($rec['format']);
             }
         }
     }else {
          print "Publication Format(s) : TEXT";
     }
     print("</b><br />\n");

     display_field('submitter_name',$rec);
     display_field('submit_date',$rec);
     if (array_key_exists('verifier_name',$rec) && 
         $rec['verifier_name'] != null) {
          display_verifier_name($rec);
     }
     if (array_key_exists('posted_date',$rec) && 
         $rec['posted_date'] != null &&
         $rec['posted_date'] != '0000-00-00') {
          display_posted_date($rec);
     }
     print("Report Text:</p>\n");
     print('<pre class="legacytext">' . "\n");
     print(htmlspecialchars($rec['notes']) . "\n</pre>\n");
     print("<hr />\n");
}

/*Display errata id link*/
function display_errata_id($label,$rec,$separator='<br />'){
     global $errata_labels;
     print($errata_labels[$label].': ');
     $errata_link = '<a href="/errata/eid'.(htmlspecialchars($rec[$label],ENT_QUOTES)).'">'.(htmlspecialchars($rec[$label],ENT_QUOTES)).'</a>';
     print ($errata_link. $seperator . "<br />\n" );

}


/*
 * Special display function to select an appropriate heading for the value in
 * the verifier_name column. The header will different depending on the status
 * of the report being displayed. Uses the extra keys i the errata_labels array.
 */
function display_verifier_name($rec) {
     global $errata_status_ids;
     switch ($errata_status_ids[$rec['errata_status_code']]) {
          case STATUS_REPORTED:
               print('Edited by: ');
               print(htmlspecialchars($rec['verifier_name'],ENT_QUOTES) . "<br />\n" );
               break;                    
          case STATUS_VERIFIED:
               display_field('verifier_name',$rec); // normal case
               break;
          case STATUS_REJECTED:
               print('Rejected by: ');
               print(htmlspecialchars($rec['verifier_name'],ENT_QUOTES) .  "<br />\n" );
               break;
          case STATUS_HELD:
               print('Held for Document Update by: ');
               print(htmlspecialchars($rec['verifier_name'],ENT_QUOTES) .  "<br />\n" );
               break;
          default:              // don't display anything; log error and go on
               error_log("display_verifier_name: Unexpected status code value: {$rec['errata_status_code']}");
               break;
          }
}

/*
 * Speical display function for the posted date column. As with verifier name, the
 * header depends on the status of the report.
 */
function display_posted_date($rec) {
     global $errata_status_ids;
     switch ($errata_status_ids[$rec['errata_status_code']]) {
          case STATUS_REPORTED:
               print('Date Edited: ');
               print(htmlspecialchars($rec['posted_date'],ENT_QUOTES) . "<br />\n" );
               break;                    
          case STATUS_VERIFIED:
               display_field('posted_date',$rec); // normal case
               break;
          case STATUS_REJECTED:
               print('Date Rejected: ');
               print(htmlspecialchars($rec['posted_date'],ENT_QUOTES) .  "<br />\n" );
               break;
          case STATUS_HELD:
               print('Date Held: ');
               print(htmlspecialchars($rec['posted_date'],ENT_QUOTES) .  "<br />\n" );
               break;
          default:              // don't display anything; log error and go on
               error_log("display_verifier_name: Unexpected status code value: {$rec['errata_status_code']}");
               break;
     }
}

/*
 * Write a header for a errata report entry with embedded anchor to RFC.
 */
function report_header($rfcid,$rfctitle,$rfcdate) {
     global $debug_erlib;
     global $source_of_rfc;
     print('<h3>' . build_rfc_anchor($rfcid) . ', ');
     print('"' . htmlspecialchars(trim($rfctitle)) . '", ');
     print(format_date($rfcdate));
     print("</h3>\n");
     # get_rfcmeta_data takes number portion of id only
     $rfcnum = substr($rfcid,3,4);
     $metadata = get_rfcmeta_data($rfcnum);
     $obs = $metadata['obsoleted-by'];

     if ($debug_erlib === true) {
          print("<b>metadata</b>\n<pre>\n");
          print_r($metadata);
          print("\n</pre>\n");
     }

     # display obsolete message
     if ($obs != NULL && strlen(trim($obs)) != 0) {
          $values = explode(',', $obs);
          $formatted_obs = "<p><b>Note: This RFC has been obsoleted by ";
          foreach($values as $value) {
              list($rfc_value_name,$rfc_value_number,$length) = split_rfc($value); 
 	      $formatted_obs .= sprintf(' <a href="/info/%s">%s&nbsp;%d</a>', htmlspecialchars(trim(strtolower($value))), htmlspecialchars($rfc_value_name), htmlspecialchars(trim($rfc_value_number))); 

          }
          $formatted_obs .= "</b></p>\n";
          print("$formatted_obs");
     }
     # if this is Legacy or NONWG strip area acronym and print on separate line
     $src_acronym = get_rfc_source_acronyms($rfcid);
     if ($src_acronym != null) {
         if (preg_match('/(?P<group>^(Legacy|IETF - NON WORKING GROUP)) \((?P<area>\w+)\)/', $src_acronym, $matches)) {
             $group = $matches['group'];
             $area = $matches['area'];
             print("<a href=\"" . htmlspecialchars($source_of_rfc) . "\">Source of RFC</a>: " . htmlspecialchars($group) . "<br />\n");
             print("Area Assignment: " . htmlspecialchars($area) . "<br />\n");
         } else { 
             print("<a href=\"" . htmlspecialchars($source_of_rfc) . "\">Source of RFC</a>: " . htmlspecialchars($src_acronym) . "<br />\n");
         }
     }
}

function display_field($label, $rec, $separator='<br />') {
     global $errata_labels;
     print(htmlspecialchars($errata_labels[$label]) . ': ');
     print(htmlspecialchars($rec[$label],ENT_QUOTES) . $separator . "\n" );
}

/*
 * Generate a URL to retrieve the text copy of the RFC.
 */
function build_rfc_anchor($rfcid) {
#     $ftp_url = generate_ftp_url($rfcid);
     $ftp_url = generate_http_url($rfcid);
     list($rfc_name,$rfc_number,$length) = split_rfc($rfcid); 
     $formatted_rfc_number = sprintf('%s&nbsp;%d', htmlspecialchars($rfc_name), htmlspecialchars($rfc_number));
     $anchor  = '<a href="' . htmlspecialchars($ftp_url) . '" target="_blank">';
     $anchor .= $formatted_rfc_number. "</a>";

     return $anchor;
}
/*
 * Build the HREF property string using FTP
 * DEPRECATED in rev 1.25, March 2008
 */
function generate_ftp_url($rfcid) {
    $rfc_number = substr($rfcid,3); // rfcid is RFCXXXX
    $rfctxt = sprintf("rfc%d",$rfc_number); // URL is rfcX.txt
    $ftp_url = 'ftp://ftp.rfc-editor.org/in-notes/' . $rfctxt . '.txt';
    return $ftp_url;
}
/*
 * Build the HREF property string using HTTP
 */
function generate_http_url($rfcid) {
    $rfc_number = substr($rfcid,3); // rfcid is RFCXXXX
    $rfctxt = sprintf("rfc%d",$rfc_number); // URL is rfcX.txt
    $http_url = 'http://www.rfc-editor.org/rfc/' . $rfctxt . '.txt';
    return $http_url;
}

/*
 * Return a string for the href of an anchor to execute a search for the
 * indicated RFC or errata record. If the errata id is supplied, it will be
 * appended. The RFC id is not then needed, but it is kept as a aid to anyone
 * reading the anchor, e.g. in an email message.
 * Modified the function removed the rfcid from the link
 */
function generate_rfc_errata_search_query($errata_id=null) {
     global $search_query_base;
     
     if ($errata_id != null) {
         $search_query .=  $search_query_base."eid".$errata_id;

     }
     return $search_query;
}

/*
 * Write out a text input field for a form. Assumes form is part of a 
 * table and the field takes one row of the table.
 */
function text_input($label, 
                    $name,
                    $value,
                    $readonly=false, 
                    $help=false, 
                    $len=80, 
                    $size=40,
                    $placeholder='No') {
     print ("\n<tr>\n");
     /*print("\n<tr>\n  <td>");    // start the row of the table and first cell.
     if ($help) {               // if this field has help put the graphic in
          $id = $name . "_text";
          add_help_img($id);
     } else {
          print("&nbsp;</td>\n"); // close the cell
     }
     */
  
     // new cell, same row.
     print '<td><label for="' . htmlspecialchars($name) . '">' . htmlspecialchars($label) . ":</label></td>\n";
     // actual HTML form input widget

     if ($placeholder == 'No'){
     print '<td>
              <input type="text" id="' . htmlspecialchars($name) . '" name="' . htmlspecialchars($name) . '"
                     value="' . htmlspecialchars($value) . '" maxlength="' . htmlspecialchars($len) . '" size="' . htmlspecialchars($size) . '" ';
     }else {
     print '
          <td>
              <input type="text" id="' . htmlspecialchars($name) . '" name="' . htmlspecialchars($name) . '"
                     value="' . htmlspecialchars($value) . '" maxlength="' . htmlspecialchars($len) . '" size="' . htmlspecialchars($size) . '" placeholder="' . htmlspecialchars($placeholder) . '" ';

}
     if ($readonly) { print(" readonly"); }
     print(" />\n"); // end of text input widget

   #print ("</td>\n");
   if ($help){
    #      print ("<td>\n");
          $id= $name . '_text';
          add_help_text($id);
          print ("</td>\n");
   }else {
     #print ("<td>&nbsp;</td>");
      print ("</td>\n");
   }
   print ("</tr>");

}

/*
 * Write out a select input field for a form. Assumes form is part of a 
 * table and the field takes one row of the table.
 */
function select_input($label, 
                      $name,
                      $values,
                      $selected,
                      $help=false) {
     print ("\n<tr>\n");

/*     print("\n<tr>\n  <td>");    // start the row of the table and first cell.
     if ($help) {               // if this field has help put the graphic in
          $id= $name . '_select';
          add_help_img($id);
     } else {
          print("&nbsp;</td>\n"); // close the cell
     }
*/

     // new cell, same row.
     #print '<td><label for="' . htmlspecialchars($name) . '>' . htmlspecialchars($label) . ":</label></td>\n";
     print '<td><label for=' . htmlspecialchars($name) . '>' . htmlspecialchars($label) . ':</label></td>'."\n";

     print '
         <td>
           <select name="' . htmlspecialchars($name) . '">';
     foreach($values as $value) {
          if ($value == $selected) {
               print("    <option selected>" . htmlspecialchars($value) . "</option>\n");
          } else {
               print("    <option>" . htmlspecialchars($value) . "</option>\n");
          }
     }
     print("      </select>");
     #print("    </td>\n");
     if ($help){
      #    print "<td>";
          $id= $name . '_select';
          add_help_text($id);
          print "</td>";
     }else {
     #print ("<td>\n &nbsp; </td>\n");
           print ("</td>\n");
     }
     print("  </tr>\n");
}

/*
 * Write out a textarea input box.
 */
function textarea_input($label,
                        $name,
                        $value,
                        $help=false,
                        $rows=10,
                        $cols=80,
                        $placeholder='No',
                        $class='No' ) {

     print("\n<tr>\n");
     /*print("\n<tr><td>\n");
     if ($help) {
          $id = $name . "_textarea";
          add_help_img($id);
     } else {
          print("&nbsp;</td>\n");
     }
     */

     print("  <td><label for=" . htmlspecialchars($name) . ">" . htmlspecialchars($label) . ":</label></td><td>\n");
     print('<textarea cols="' . htmlspecialchars($cols) . '" rows="' . htmlspecialchars($rows));
     if ($placeholder != 'No'){
     print ('" placeholder="'. htmlspecialchars($placeholder));
     }

     if ($class != 'No'){
          
      //print ('" class="rfctext');
        print ('" style="font-family: monospace;');
     }
     print('" name="' . htmlspecialchars($name) . '">');
     print(htmlspecialchars($value));
    print("</textarea>\n");
     #print("  </td>\n");
     if ($help){
      #    print "<td>";
          $id= $name . '_textarea';
          add_help_text($id);
          print "</td>";
     }else{
       #   print ("<td>&nbsp;</td></tr>\n");
           print ("</td>\n");
     }
     print ("</tr>");
}


/*
 * Write out a email input field for a form. Assumes form is part of a 
 * table and the field takes one row of the table.
 */
function text_email_input($label, 
                    $name,
                    $value,
                    $readonly=false, 
                    $help=false, 
                    $len=80, 
                    $size=40,
                    $placeholder='No') {
     print ("\n<tr>\n");
     /*print("\n<tr>\n  <td>");    // start the row of the table and first cell.
     if ($help) {               // if this field has help put the graphic in
          $id = $name . "_text";
          add_help_img($id);
     } else {
          print("&nbsp;</td>\n"); // close the cell
     }
     */
  
     // new cell, same row.
     print '<td><label for=' . htmlspecialchars($name) . '>' . htmlspecialchars($label) . ":</label></td>\n";
     // actual HTML form input widget

     if ($placeholder == 'No'){
     print '
          <td>
              <input type="email" id="' . htmlspecialchars($name) . '" name="' . htmlspecialchars($name) . '"
                     value="' . htmlspecialchars($value) . '" maxlength="' . htmlspecialchars($len) . '" size="' . htmlspecialchars($size) . '"
';
     }else {
     print '
          <td>
              <input type="email" id="' . htmlspecialchars($name) . '" name="' . htmlspecialchars($name) . '"
                     value="' . htmlspecialchars($value) . '" maxlength="' . htmlspecialchars($len) . '" size="' . htmlspecialchars($size) . '" placeholder="' . htmlspecialchars($placeholder) . '"
';


}
     if ($readonly) { print(" readonly"); }
     print(" />\n"); // end of email input widget

   #print ("</td>\n");
   if ($help){
    #      print ("<td>\n");
          $id= $name . '_text';
          add_help_text($id);
          print ("</td>\n");
   }else {
     #print ("<td>&nbsp;</td>");
      print ("</td>\n");
   }
   print ("</tr>");

}

/*
 * Write out a checkbox field for a form. Assumes form is part of a 
 * table and the field takes one row of the table.
 */
function select_checkbox($formats,$current_format,$help=false) {
   
    $diff_list = array_diff($formats,$current_format);

    # print ('<td><table><tr><td><input type="checkbox" id="all" onClick="pubFormatSelect();">All</td>');

     print ('<td><table><tr><td>');
     foreach ($formats as $key => $value){
                  if ($value == 'TEXT'){
                      if (in_array($value,$diff_list)){
                          print ('<td><input type="checkbox" name="format[]" id="text" value="' . htmlspecialchars($value) . '">TEXT</td>');
                      }else {
                          print ('<td><input type="checkbox" name="format[]" id="text"  checked value="' . htmlspecialchars($value) . '">TEXT</td>');
                      }
                  }elseif ($value == 'PDF'){
                       if(in_array($value,$diff_list)){
                            print ('<td><input type="checkbox" name="format[]" id="pdf" value="' . htmlspecialchars($value) . '">PDF</td>');
                       }else {
                            print ('<td><input type="checkbox" name="format[]" id="pdf" checked value="' . htmlspecialchars($value) . '">PDF</td>');
                       }
                  }elseif ($value == 'HTML'){
                       if(in_array($value,$diff_list)){
                           print ('<td><input type="checkbox" name="format[]" id="html" value="' . htmlspecialchars($value) . '">HTML</td>');
                       }else {
                           print ('<td><input type="checkbox" name="format[]" id="html" checked value="' . htmlspecialchars($value) . '">HTML</td>');
                       }
                  }
      }
     print("</tr></table></td></tr>\n");
}

/*
 *  Lookup the status of an erratum report and return true if the status is
 * Reported, else false.
 */
function is_reported_status($eid) {
     global $errata_status_from_id;
     global $pdo;

     try {
         $query = "SELECT status_id, `doc-id` FROM errata WHERE errata_id=:eid";
         $stmt = $pdo->prepare($query);
         $stmt->bindParam('eid',$eid);
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
     } catch (PDOException $pe){
              error_log("Error processing : is_reported_status", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }

     
     $row = $stmt->fetch(PDO::FETCH_ASSOC);
     switch ($row['status_id']) {
     case STATUS_REPORTED:
          return true;
          break;
     default:
// Record a message for the access_denied function to display.
          $_SESSION['message'] =
               "<p>Report " . htmlspecialchars($eid) . " for " . htmlspecialchars($_SESSION['doc-id']) . " has already been classified as <b>
" . htmlspecialchars($errata_status_from_id[$row['status_id']]) . "</b>. Please contact the RFC Editor if you want
to edit this report.</p>";
          break;
     }
     return false;
}

/*
 * Get the assigned area for a document.  Only used for Legacy and Non-WG documents
 */
function get_area_assignment($doc_id) {
     global $debug_erlib;
     global $pdo;
    
     try {
         $query = 
             "SELECT a.area_acronym
             FROM area a, `index` i, area_assignments b
             WHERE i.`doc-id` = :doc_id
             AND b.fk_index = i.internal_key
             AND b.fk_area = a.area_id"; 

         $stmt = $pdo->prepare($query);
         $stmt->bindParam('doc_id',$doc_id);
         $stmt->execute();
         $num_of_rows = $stmt->rowCount(); 
     } catch (PDOException $pe){
         error_log("Error processing : get_area_assignment", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }

     if ($num_of_rows > 0) {
         $row = $stmt->fetch(PDO::FETCH_ASSOC);
     }
     return $row['area_acronym'];
}

/*
 * Function looks up source data for a document and returns a string containing
 * the working group and area acronyms for IETF documents or the stream name
 * for non-IETF documents.
 */
function get_rfc_source_acronyms($doc_id) {
     global $debug_erlib;
     global $pdo;
// Include the extra columns for debugging/development times. The extra
// overhead is minimal in this application.
     if ($debug_erlib === true ) { print("<!-- \$doc_id = $doc_id -->\n"); }
     try {
        $query =
             "SELECT i.source, w.wg_acronym,s.ssp_id, 
                 a.area_acronym, s.stream_name
             FROM `index` i, working_group w, area a, 
                 stream_specific_parties s
             WHERE i.source = w.wg_name AND
                 w.area_name = a.area_name AND 
                 w.ssp_id = s.ssp_id AND 
                 i.`doc-id` = :doc_id";
         $stmt = $pdo->prepare($query);
         $stmt->bindParam('doc_id',$doc_id);
         $stmt->execute();
         $num_of_rows = $stmt->rowCount(); 
     } catch (PDOException $pe){
         error_log("Error processing : get_rfc_source_acronyms", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }

     
     $row = $stmt->fetch(PDO::FETCH_ASSOC);
     $source_acronym = "{$row['wg_acronym']} ({$row['area_acronym']})";

     /*Added to handle the new RG groups added to working_group table under IRTF stream*/
     $ssp_id = $row['ssp_id'];

     if ($ssp_id == 4){

          if ($row['source'] == 'IRTF'){
	          $source_acronym = $row['source'];
          }
	  else{

                  $source_acronym = 'IRTF';
	  }
     }else {
     //ssp_id not 4 ie not a IRTF group
	     switch ($row['source']) {
	     case 'IAB':
	     case 'INDEPENDENT':
	     case 'IRTF':
             case 'Editorial':
	          $source_acronym = $row['source'];
        	  break;
	     case 'Legacy':
        	  $source_acronym = $row['source'];
	          # tack on area_assignment
        	  $aa = get_area_assignment($doc_id);
	          if($aa) {
        	    $source_acronym = $source_acronym." (".$aa.")";
	          }
        	  break;
	     case 'IETF - NON WORKING GROUP':
        	  $source_acronym = 'IETF - NON WORKING GROUP';
	          # tack on area_assignment
        	  $aa = get_area_assignment($doc_id);
	          if($aa) {
        	    $source_acronym = $source_acronym." (".$aa.")";
	       	  }
         	  break;
     	    }
     }
     return $source_acronym;
}

/*
 * Write out a help image with the given ID.
 */
function add_help_img($id) {
     print '

       <img id="' . htmlspecialchars($id) . '" name="' . htmlspecialchars($id) . '" src="graphics/questn1.gif"
           onmouseover="showText(this)" onmouseout="hideText()"
            width="20" height="20" alt="click for help" />
     </td>
';
}
/*
 *Write out help text for given ID
 */
function add_help_text($id) {

    $help_text;
    switch($id){

     case 'errata_type_code_select':
                           $help_text = "";    
                           break;
     case 'section_text':
                           $help_text = "";
                           break;

     case 'notes_textarea':
                           $help_text = "";
                           break;
     default: 
             break;                             
    }
    print ("$help_text");
}


function map_month($month) {
     switch ($month) {
     case '12':
          return 'December';
     case '11':
          return 'November';
     case '10':
          return 'October';
     case '9':
          return 'September';
     case '8':
          return 'August';
     case '7':
          return 'July';
     case '6':
          return 'June';
     case '5':
          return 'May';
     case '4':
          return 'April';
     case '3':
          return 'March';
     case '2':
          return 'February';
     case '1':
          return 'January';
     default:
          return 'UNKNOWN';
     }
}
/*
 * Called with the YYYY-MM-DD time string from database entry, returns
 * a string with format Month, YYYY
 */
function format_date($date) {
     $parsed_date = date_parse($date);
     $month = map_month($parsed_date['month']);
     return $month . ' ' . $parsed_date['year'];
}

function remote_hostname() {
     $hostname = gethostbyaddr($_SERVER['REMOTE_ADDR']);
     print("<p>You are visiting from " . htmlspecialchars($hostname) . ".</p>\n");
}

function show_db_host() {
     global $db_host;

     print("<p>Using " . htmlspecialchars($db_host) . " as database server.</p>\n");
}

function block_non_isi() {
     $is_isi = strpos(gethostbyaddr($_SERVER['REMOTE_ADDR']),'amsl.com');
     if ($is_isi === false) {
          print<<<END
<p class="warning">This page is being tested by the RFC Editor and is not available to outside parties.</p>

END;
          remote_hostname();
          page_footer();
          exit(1); // terminate the script
     }
}

/*
 * Retrieve the list of area acronyms stored in the database. Used to build 
 * drop down menus for query forms.
 */
function get_area_acronyms() {
     global $pdo;
     
     try{
         $query =
          "SELECT area_acronym FROM area WHERE area_acronym IS NOT NULL
           AND area_status = :area_status
           ORDER BY area_acronym";
         $stmt = $pdo->prepare($query);
         $stmt->bindValue('area_status','open');
         $stmt->execute();
         $num_of_rows = $stmt->rowCount(); 
     } catch (PDOException $pe){
         error_log("Error processing : get_area_acronyms", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }

     $acronyms = array();
     while ($row = $stmt->fetch(PDO::FETCH_NUM)){
          $acronyms = array_merge($acronyms,$row);
     }
     return $acronyms;
}

/**************************************************************************************/
/*Split rfc functions splits the given RFC/Subseries number in two parts*/
/**************************************************************************************/
function split_rfc($in_value){
         global $debug_rsmeta;

	 $length = strlen($in_value);

         if ($length == 9){
          $out_name = substr($in_value,0,'-6');
          $out_number = substr($in_value,'-5');
	}elseif ($length == 8){
         $out_name = substr($in_value,0,'-4');
         $out_number = substr($in_value,'-4');
	}elseif ($length == 7){
         $out_name = substr($in_value,0,'-4');
         $out_number = substr($in_value,'-4');
	}elseif ($length == 6){
         $out_name = substr($in_value,0,'-3');
         $out_number = substr($in_value,'-3');
	}elseif ($length == 5){
         $out_name = substr($in_value,0,'-2');
         $out_number = substr($in_value,'-2');
	}elseif ($length == 4){
         $out_name = substr($in_value,0,'-1');
         $out_number = substr($in_value,'-1');
	}
		
	 if ($debug_rsmeta){
		 print "Out name is :".$out_name."\n";
	     print "Out number is :".$out_number."\n";
  	     print "Length is :".$length."\n";
	 }
	 
	 return array($out_name, $out_number, $length);
	}
?>
