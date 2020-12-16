<?php
  /* Copyright The IETF Trust 2020 All Rights Reserved */
  /* $Id: errata_search_lib.php,v 1.6 2020/11/11 01:03:39 priyanka Exp $ */
  /* 
   * Routines to support the Advanced Search form for Errata 2
   * 
   * April 2017 Updates : Added the redirect link for Errata Id and RFC number - PN
   * November 2020 : Modified the script to use PDO prepared statements - PN           
   */
include_once("errata_lib.php");

$debug_esl = false;

function add_rfcid($criteria) {
     print<<<END
       <td class="search_label_bold" colspan="2">
         <label for="rfc">RFC Number:</label>
         <input type="text" id="rfc" name="rfc" size="4" maxlength="4"
END;
     if (array_key_exists('rfc', $criteria)) {
          print(" value=\"" . htmlspecialchars($criteria['rfc']) . "\"");
     } 
     print(" />\n        </td>\n");
}

function add_eid($criteria) {
     print<<<END
       <td  class="search_label_bold" colspan="2">
         <label for="eid">Errata ID:</label>
         <input type="text" id="eid" name="eid" size="4" maxlength="4"
END;

     if (array_key_exists('eid',$criteria)) {
          print(" value=\"" . htmlspecialchars($criteria['eid']) . "\"");
     }
     print(" />\n       </td>\n");
}


function add_status($criteria) {
     global $errata_status_ids;
     global $debug_esl;

     $allstatus = STATUS_ALL;

     print<<<END
       <td><label for="rec_status">Status:</label></td>
       <td>
           <select name="rec_status" id="rec_status">
END;

     if (!(array_key_exists('rec_status',$criteria))) {
          if ($debug_esl === true) print("<!-- no rec_status key in \$criteria -->\n");
          print("             <option value=\"" . htmlspecialchars($allstatus) . "\" selected=\"selected\">All/Any</option>\n");
          print("             <option value=\"0\">Verified+Reported</option>\n");
          foreach ($errata_status_ids as $key => $value) {
               print("             <option value=\"" . htmlspecialchars($value) . "\">" . htmlspecialchars($key) . "</option>\n");
          }
     } else {
          if ($debug_esl === true) print("<!-- rec_status key in \$criteria: {$criteria['rec_status']} -->\n");
          print("             <option value=\"" . htmlspecialchars($allstatus) . "\">All/Any</option>\n");
          if ($criteria['rec_status'] == 'default') {
               print("<option value=\"0\" selected=\"selected\">Verified+Reported</option>");
          } else {
               print("<option value=\"0\" >Verified+Reported</option>");
          }
          foreach ($errata_status_ids as $key => $value) {
               if ((strcasecmp($key, $criteria['rec_status']) == 0)) {
                    print("             <option value=\"" . htmlspecialchars($value) . "\" selected=\"selected\">" . htmlspecialchars($key) . "</option>\n");
               } else {
                    print("             <option value=\"" . htmlspecialchars($value) . "\">" . htmlspecialchars($key) . "</option>\n");
               }
          }
     }
     print("           </select>\n");
     print("       </td>\n");
}


function add_area_acronyms_select($criteria) {

     $area_acronyms = get_area_acronyms();

     print<<<END
       <td><label for="area_acronym">Area Acronym:</label></td>
       <td class="userdata">
           <select name="area_acronym" id="area_acronym">
END;

     if (!(array_key_exists('area_acronym',$criteria))) {
          print("<option value=\"\" selected=\"selected\">All/Any</option>\n");
          foreach ($area_acronyms as $acronym) {
               print("             <option value=\"" . htmlspecialchars($acronym) . "\">" . htmlspecialchars($acronym) . "</option>\n");
          }
     } else {
          print("<option value=\"\">All/Any</option>\n");
          foreach ($area_acronyms as $acronym) {
               if (strcasecmp($acronym, $criteria['area_acronym']) == 0) {
                    print("             <option value=\"" . htmlspecialchars($acronym) . "\" selected=\"selected\">" . htmlspecialchars($acronym) . "</option>\n");
               } else {
                    print("             <option value=\"" . htmlspecialchars($acronym) . "\">" . htmlspecialchars($acronym) . "</option>\n");
               }
          }

     }


     print("            </select>\n");
     print("       </td>\n");

}

function add_errata_type_codes($criteria) {
     global $errata_type_codes;
     print<<<END
       <td>
          <label for="errata_type">Type:</label>
       </td>
       <td class="userdata">
           <select name="errata_type" id="errata_type">

END;

     if (!(array_key_exists('errata_type',$criteria))) {
          print("             <option value=\"\" selected=\"selected\">All/Any</option>");
          foreach ($errata_type_codes as $key => $value) {
               print("             <option value=\"" . htmlspecialchars($key) . "\">" . htmlspecialchars($value) . "</option>\n");
          }
     } else {
          print("             <option value=\"\">All/Any</option>");
          foreach ($errata_type_codes as $key => $value) {
               if ($key == $criteria['errata_type']) {
                    print("             <option value=\"" . htmlspecialchars($key) . "\" selected=\"selected\">" . htmlspecialchars($value) . "</option>\n");
               } else {
                    print("             <option value=\"" . htmlspecialchars($key) . "\">" . htmlspecialchars($value) . "</option>\n");
               }
          }
     }

     print<<<END
           </select>
        </td>

END;

}

function add_wg_acronym($criteria) {
     print<<<END
       <td>
          <label for="wg_acronym">WG Acronym:</label>
       </td>
       <td class="userdata">
          <input id="wg_acronym" size="6" type="text" name="wg_acronym" maxlength="10"
END;
     if ((array_key_exists('wg_acronym',$criteria))) {
          print(" value=\"" . htmlspecialchars($criteria['wg_acronym']) . "\"");
     }

          print(" />\n         </td>\n");

}

function add_submitter_name($criteria) {
     print<<<END
       <td>
          <label for="submitter_name">Submitter Name:</label>
       </td>
       <td class="userdata">
          <input id="submitter_name" size="20" type="text" name="submitter_name" maxlength="60"
END;
     if ((array_key_exists('submitter_name', $criteria))) {
          print(" value=\"" . htmlspecialchars($criteria['submitter_name']) . "\"");
     }
     print<<<END
 />
       </td>

END;
}

function add_stream_names($criteria) {

     $stream_names = array('IAB','INDEPENDENT','IRTF','Legacy');

     print<<<END
       <td>
          <label for="stream_name">Other:</label>
       </td>
       <td class="userdata">
          <select name="stream_name" id="stream_name">
END;
     if (!(array_key_exists('stream_name', $criteria))) {
          print("<option value=\"\" selected=\"selected\">All/Any</option>\n");
          foreach ($stream_names as $name) {
               print("            <option value=\"" . htmlspecialchars($name) . "\">" . htmlspecialchars($name) . "</option>\n");
          }
     }  else {
          print("<option value=\"\">All/Any</option>\n");
          foreach ($stream_names as $name) {
               if (strcasecmp($name, $criteria['stream_name']) == 0) {
                    print("            <option value=\"" . htmlspecialchars($name) . "\" selected=\"selected\">" . htmlspecialchars($name) . "</option>\n");
               } else {
                    print("            <option value=\"" . htmlspecialchars($name) . "\">" . htmlspecialchars($name) . "</option>\n");
               }
          }
     }

     print<<<END
          </select>
       </td>

END;

}

function add_date_submitted($criteria) {
     print<<<END
       <td>
          <label for="submit_date">Date Submitted:</label>
       </td>
       <td class="userdata">
          <input id="submit_date" size="8" type="text" name="submit_date" maxlength="10"
END;
     if (array_key_exists('submit_date',$criteria)) {
          print(" value=\"" . htmlspecialchars($criteria['submit_date']) . "\"");
     }
     print<<<END
 />
       </td>

END;

}

function add_presentation_radios($criteria) {
     if (!(array_key_exists('presentation',$criteria))) {
          print<<<END
         <div>
        Summary Table<input type="radio" name="presentation" id="presentationT" checked="on" value="table">
        Full Records<input type="radio" name="presentation"  id="presentationR" value="records">
         </div>

END;
     } else {
          switch ($criteria['presentation']) {
          case 'records':
               print<<<END
         <div>
        Summary Table<input type="radio" name="presentation"  id="presentationT" value="table">
        Full Records<input type="radio" name="presentation"  id="presentationR" checked="on" value="records">
         </div>

END;
               break;
          default:
               print<<<END
         <div>
        Summary Table<input type="radio" name="presentation"  id="presentationT" checked="on" value="table">
        Full Records<input type="radio" name="presentation"  id="presentationR" value="records">
         </div>

END;
               break;
          }
     }
}

/*
 * Create the Advance Serach Form
 */
function create_adv_search_form($criteria = array(), $action='/errata_search.php', $for_edit=false) {
     global $debug_esl;

     if ($debug_esl === true) {
          print("<!--\ncreate_adv_search_form: \$criteria=\n");
          var_dump($criteria);
          print("\n\$action=$action\n\$for_edit=$for_edit");
          print("\n-->\n");
     }
    
     print "<br>"; 
     print '
<form method="get" id="query" action="' . htmlspecialchars($action) . '" onsubmit="return validateRFCSearchForm(this);">
  <table  align="center" class="erratasearch">
     <tr>
       <th colspan="4"><h3>Errata Search</h3></th>
     </tr>
';

     print("     <tr class=\"ibar\">\n");
     add_rfcid($criteria);
     add_eid($criteria);
     print("     </tr>\n");
// Put a blank row under the RFC ID and Errata ID input fields.
     print<<<END
    <tr><td colspan="4">&nbsp;</td></tr>

END;
// This td element just contains a title for the various area, working groups
// and stream selections.
     print<<<END
     <tr>
       <td style="text-decoration: underline;">Source of RFC</td>

       <td>&nbsp;</td>
END;
     
     add_status($criteria);

     print("     </tr>\n     <tr>\n");
     
     add_area_acronyms_select($criteria);
     add_errata_type_codes($criteria);

     print("     </tr>\n     <tr>\n");

     add_wg_acronym($criteria);
     add_submitter_name($criteria);

     print("     </tr>\n     <tr>\n");

     add_stream_names($criteria);
     add_date_submitted($criteria);

     print("     </tr>\n");

     print("     <tr>\n       <td colspan=\"4\" class=\"sbar\">\n");
     if (!$for_edit) {
          add_presentation_radios($criteria);
     } else {
          // For verifiers/editors, only use table presentations
          print("         <input type=\"hidden\"  id=\"presentation\" name=\"presentation\" value=\"table\">\n");
     }

     print<<<END
         <input type="submit" value="Search" />
         <input type="button" value="Reset Fields" onclick="return clearAdvSearchForm(document.forms.query);" />
       </td>
     </tr>
  </table>
</form>
<script type="text/javascript">
<!--
           document.forms.query.rfc.focus();
//-->
</script>

END;
}

/*
 * Function to write out a row in the table presentation display of an RFC
 * errata record.
 * $row: associative array of the record from the database.
 */
function display_table_row($row,$class=null,$edit_eid=false) {
     $source_acronym=get_rfc_source_acronyms($row['doc-id']);
     $section_prefix = substr($row['section'],0,2);
     if ((strlen(trim($row['section'])) == 0) || ($section_prefix == "99")) {
          $section = '-';
     } else {
          $section = $row['section'];
     }
     /*Modifiying the eid_link part only in the errata_search part for the redirect links*/
     if ($edit_eid) {
           $eid_link = '<a href="'. htmlspecialchars($eid_link_verify_base).'?eid='.htmlspecialchars($row['errata_id']).'">'.htmlspecialchars($row['errata_id']).'</a>';

     } else {
           $eid_link = '<a href="/errata/eid'.htmlspecialchars($row['errata_id']).'">'.htmlspecialchars($row['errata_id']).'</a>';

  }
     if ($class == null) {
//          print("          <tr style=\"border: 1px solid black;\">");
          print("<tr>");

     } else {
//          print("          <tr class=\"$class\">");
          print("<tr class=\"$class\">");

     }

     print '
              <td>' . htmlspecialchars($row['doc-id']) . ' (' . $eid_link . ')</td>
              <td style="text-align:center">' . htmlspecialchars($section) . '</td>
              <td>' . htmlspecialchars($row['errata_type_code']) . '</td>
              <td style="text-align:center">' . htmlspecialchars($source_acronym) . '</td>
              <td>' . htmlspecialchars($row['submitter_name']) . '</td>
              <td>' . htmlspecialchars($row['format']) . '</td>
              <td>' . htmlspecialchars($row['submit_date']) . '</td>
          </tr>
';

}

/*
 * Write a section header for a status code, with count.
 */
function section_header($status, $count) {
     print("<h2>Status: <a href=\"status_type_desc.html\">" . htmlspecialchars($status) . "</a> (" . htmlspecialchars($count) . ")</h2>\n");
}

/*
 * Write out the headers for the table display
 */
function table_header($status, $count) {
//<table border="0" cellspacing="0">
global $source_of_rfc;
print '
<table class="dynamictable">
   <tr>
       <th style="text-align:left">RFC Number<br>(Errata ID)</th>
       <th style="text-align:center">Section</th>
       <th>Type</th>
       <th><a href="' . htmlspecialchars($source_of_rfc) . '">Source of RFC</a></th>
       <th style="text-align:left">Submitted By</th>
       <th style="text-align:left">Publication Format</th>
       <th style="text-align:left">Date Submitted</th>
   </tr>
';
}



/*
 * Close the table used for record display by summary table.
 */
function table_footer() {
     print<<<END

</table>

END;
}
// This string contains the set of columns to be retrieved by a query. The
// criteria from the form is used to create the additional WHERE clauses.
$base_adv_sql_query =
     "SELECT e.`doc-id`, errata_id, title, `pub-date`,errata_type_code,
             errata_status_code, submit_date, posted_date, verifier_id,
             verifier_name, verifier_email, submitter_name, submitter_email,
             orig_text, correct_text, e.notes, section, rs_code, e.insert_date,
             e.update_date, conv_format_check, i.source,
             submit_date, submitter_name, section, w.`ssp_id`,e.`format`
      FROM errata e, errata_type_codes t , errata_status_codes s,
           working_group w, area a, 
           `index` i left join area_assignments n on i.internal_key = n.fk_index
      WHERE e.`doc-id` = i.`doc-id` AND 
            e.type_id=t.errata_type_id AND 
            e.status_id=s.errata_status_id AND
            i.source = w.wg_name AND
            (a.area_name = w.area_name OR n.fk_area = a.area_id)";

function concat_criteria($criteria) {
     global $debug_esl;
     global $base_adv_sql_query;
     global $pdo; 
     
     $query = $base_adv_sql_query;
     foreach ($criteria as $key => $value) {
          switch ($key) {
          case 'rec_status':
          case 'presentation':
          case 'edit_eid_link':
               break; // skip
          case 'rfc':
               $query .= " AND e.`doc-id` = :doc_id";
               break;
          case 'stream_name':
//To get the stream values based on ssp id
               if ($value != 'IRTF') {  
               	   $query .= " AND w.wg_name = :wg_name \n";

               }else {
                   $query .= " AND w.ssp_id = :ssp_id \n ";
	       }
               break;
          case 'errata_type':
                   $query .= " AND e.type_id = :type_id \n";
               break;
          case 'wg_acronym':
                   $query .= " AND w.wg_acronym = :wg_acronym \n";
               break;
//If area_acronym is 'art' include working groups art, app and rai
//so that art ADs get a complete list.
          case 'area_acronym':
                if ($value == 'art'){
                    $query .= " AND ( area_acronym = :art_area  OR area_acronym = :app_area OR area_acronym = :rai_area )";
                }
                else{
		    $query .= " AND area_acronym = :area_acronym \n";
                }                
                 break; 
          case 'submitter_name':
               $query .= " AND submitter_name LIKE :submitter_name";
               break;
          case 'submit_date':
               $query .= " AND submit_date LIKE :submit_date";
               break;
          default:
               $query .= " AND $key = :".$key;
               break;
          }
     }

     return $query;
}

function run_query($query, $criteria, $status_subset) {
     global $pdo;
     try {
         $stmt = $pdo->prepare($query);
         foreach ($criteria as $key => $value) {
             switch ($key) {
                 case 'presentation':
                 case 'edit_eid_link':
                     break; // skip
                 case 'rfc':
                     $doc_id = sprintf('RFC%04d',$value);
                     $stmt->bindParam('doc_id',$doc_id);
                     break;
                 case 'stream_name':
                 //To get the stream values based on ssp id
                     if ($value != 'IRTF') {  
                         $wg_name = $value;
                         $stmt->bindParam('wg_name',$wg_name);
                     }else {
                         $stmt->bindValue('ssp_id',4);
	             }
                     break;
                 case 'errata_type':
                     $type_id = $value;
                     $stmt->bindParam('type_id',$type_id);
                     break;
                 case 'wg_acronym':
                 // "none" is used as a synonym for IETF Non-Working Group by IETF search tools
                     if ($value == 'none') {
                         $stmt->bindValue('wg_acronym','IETF-NWG');
                     } else {
                         $wg_acronym = $value; 
                         $stmt->bindParam('wg_acronym',$wg_acronym);
                     }
                     break;
                     //If area_acronym is 'art' include working groups art, app and rai
                     //so that art ADs get a complete list.
                 case 'area_acronym':
                     if ($value == 'art'){
                         $stmt->bindValue('art_area',art);
                         $stmt->bindValue('app_area',app);
                         $stmt->bindValue('rai_area',rai);
                     } else {
                         $area_acronym = $value;
                         $stmt->bindParam('area_acronym',$area_acronym);
                     }                
                     break; 
                 case 'submitter_name':
                     $submitter_name = '%'.$value.'%';
                     $stmt->bindParam('submitter_name',$submitter_name);
                     break;
                 case 'submit_date':
                     $submit_date = $value.'%';
                     $stmt->bindParam('submit_date',$submit_date);
                     break;
                 case 'rec_status':
                     //run_query is used to calculate all the counts using 
                     //each status seperately. That time rec_status value is 'all'
                     //but status_subset value is respected status and both are not same
                     //So this is a workaround to handle that.
                     if ( $value != $status_subset){
                         if ($status_subset == 'Verified'){
                             $value = 'verified';
                         }elseif ($status_subset == 'Reported'){
                             $value = 'reported';
                         }elseif ($status_subset == 'Held for Document Update'){
                             $value = 'held for document update';
                         }elseif ($status_subset == 'Rejected'){
                             $value = 'rejected';
                         }
                     }
                     
                     if ($value == 'verified')
                         $stmt->bindValue('status_verified_id', STATUS_VERIFIED);
                     elseif ($value == 'reported')
                         $stmt->bindValue('status_reported_id', STATUS_REPORTED);
                     elseif ($value == 'rejected')
                         $stmt->bindValue('status_rejected_id', STATUS_REJECTED);
                     elseif ($value == 'held for document update')
                         $stmt->bindValue('status_held_id', STATUS_HELD);
                     else { 
                         $stmt->bindValue('status_verified_id', STATUS_VERIFIED);
                         $stmt->bindValue('status_reported_id', STATUS_REPORTED);
                     }
                     break;
                 default:
                     $stmt->bindValue($key,$value);
                     break;

                 }
         }
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
     } catch (PDOException $pe){
         error_log("Error processing : run_query", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }

     if (array_key_exists('edit_eid_link',$criteria)) {
          $edit_eid_links = $criteria['edit_eid_link'];
     } else {
          $edit_eid_links = false;
     }

     $count = $num_of_rows;
     switch ($count) {
     case 0:
          print("<!-- <p>No records found for status $status_subset.</p> -->\n");
          break;
     default:
          section_header($status_subset, $count); 
          switch ($criteria['presentation']) {
          case 'table':
               /*Add div to make table scrollable*/
               print '<div class="scrolltable">';
               table_header($status_subset,$count);
               $row_count = 1;
               while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                   display_table_row($row, ($row_count % 2 == 0 ? "even" : "odd"), $edit_eid_links);
                   $row_count++;
               }
               table_footer(); 
               print '</div>';
               break;
          case 'records':
               $row = $stmt->fetch(PDO::FETCH_ASSOC);
               $last_doc = 'first doc';
               do {
                    if ($last_doc != $row['doc-id']) {
                         report_header($row['doc-id'],$row['title'],$row['pub-date']); 
                    }
                    $last_doc = $row['doc-id'];
                    if ($row['section'] != null) {
                         display_record($row); 
                    } else {
                         display_legacy_record($row); 
                    }
               } while ($row = $stmt->fetch(PDO::FETCH_ASSOC));
               break;
          default:
               break;
          }
          break;
     }

     return $count;
}
function query_verified($criteria) {
     global $debug_esl;
     global $errata_status_from_id;
     $query = concat_criteria($criteria);
     $query .= " AND e.status_id = :status_verified_id";
     // with the addition of area_assignments the base query now includes
     // an or clause, because the area can be determined in two ways:
     // via associated working_group or area_assignments table.
     // in cases where the area can be determined both ways 
     // and area acronym is not part of the search query
     // duplicate rows result.  use GROUP BY to eliminate them
     $query .= " GROUP BY errata_id ORDER BY i.`doc-id`, e.type_id DESC";

     if ($debug_esl === true) {
          print("<pre>query_verified: \$query=\n$query\n</pre>\n");
     }

     return run_query($query,$criteria,$errata_status_from_id[STATUS_VERIFIED]);
}

function query_reported($criteria) {
     global $debug_esl;
     global $errata_status_from_id;

     $query = concat_criteria($criteria);
     $query .= " AND e.status_id = :status_reported_id";
     $query .= " GROUP BY errata_id ORDER BY i.`doc-id`, e.type_id DESC";

     if ($debug_esl === true) {
          print("<pre>query_reported: \$query=\n$query\n</pre>\n");
     }
     return run_query($query,$criteria,$errata_status_from_id[STATUS_REPORTED]);
}

function query_held($criteria) {
     global $debug_esl;
     global $errata_status_from_id;

     $query = concat_criteria($criteria);
     $query .= " AND e.status_id = :status_held_id";
     $query .= " GROUP BY errata_id ORDER BY i.`doc-id`, e.type_id DESC";

     if ($debug_esl === true) {
          print("<pre>query_held: \$query=\n$query\n</pre>\n");
     }
     return run_query($query,$criteria,$errata_status_from_id[STATUS_HELD]);
}

function query_rejected($criteria) {
     global $debug_esl;
     global $errata_status_from_id;

     $query = concat_criteria($criteria);
     $query .= " AND e.status_id = :status_rejected_id";
     $query .= " GROUP BY errata_id ORDER BY i.`doc-id`, e.type_id DESC";

     if ($debug_esl === true) {
          print("<pre>query_rejected: \$query=\n$query\n</pre>\n");
     }
     return run_query($query,$criteria,$errata_status_from_id[STATUS_REJECTED]);
}

function found_count($criteria) {
     global $debug_esl;
     global $pdo; 
 
     $base_query=concat_criteria($criteria);
     $count_query = "SELECT e.errata_id\n";
     $count_query .= strstr($base_query,"FROM");

     switch ($criteria['rec_status']) {
     case 'all':
          // add nothing
          break;
     case 'verified':
          $count_query .= " AND e.status_id = :status_verified_id";
          break;
     case 'reported':
          $count_query .= " AND e.status_id = :status_reported_id";
          break;
     case 'rejected':
          $count_query .= " AND e.status_id = :status_rejected_id";
          break;
     case 'held for document update':
          $count_query .= " AND e.status_id = :status_held_id";
          break;
     default:
          $count_query .= " AND (e.status_id = :status_verified_id OR e.status_id = :status_reported_id)";
          break;   
     }
     $count_query .= " GROUP BY e.errata_id";

     if ($debug_esl === true) {
          print("<!-- found_count: \$count_query=\n");
          var_dump($count_query);
          print("\n-->\n");
     }
     try
     {
         $stmt = $pdo->prepare($count_query);
         foreach ($criteria as $key => $value) {
             switch ($key) {
                 case 'presentation':
                 case 'edit_eid_link':
                     break; // skip
                 case 'rfc':
                     $doc_id = sprintf('RFC%04d',$value);
                     $stmt->bindParam('doc_id',$doc_id);
                     break;
                 case 'stream_name':
                 //To get the stream values based on ssp id
                     if ($value != 'IRTF') {  
                         $wg_name = $value;
                         $stmt->bindParam('wg_name',$wg_name);
                     }else {
                         $stmt->bindValue('ssp_id',4);
	             }
                     break;
                 case 'errata_type':
                     $type_id = $value;
                     $stmt->bindParam('type_id',$type_id);
                     break;
                 case 'wg_acronym':
                 // "none" is used as a synonym for IETF Non-Working Group by IETF search tools
                     if ($value == 'none') {
                         $stmt->bindValue('wg_acronym','IETF-NWG');
                     } else {
                         $wg_acronym = $value; 
                         $stmt->bindParam('wg_acronym',$wg_acronym);
                     }
                     break;
                     //If area_acronym is 'art' include working groups art, app and rai
                     //so that art ADs get a complete list.
                 case 'area_acronym':
                     if ($value == 'art'){
                         $stmt->bindValue('art_area',art);
                         $stmt->bindValue('app_area',app);
                         $stmt->bindValue('rai_area',rai);
                     } else {
                         $area_acronym = $value;
                         $stmt->bindParam('area_acronym',$area_acronym);
                     }                
                     break; 
                 case 'submitter_name':
                     $submitter_name = '%'.$value.'%';
                     $stmt->bindParam('submitter_name',$submitter_name);
                     break;
                 case 'submit_date':
                     $submit_date = $value.'%';
                     $stmt->bindParam('submit_date',$submit_date);
                     break;
                 case 'rec_status':
                     if ($value == 'verified')
                         $stmt->bindValue('status_verified_id', STATUS_VERIFIED);
                     elseif ($value == 'reported')
                         $stmt->bindValue('status_reported_id', STATUS_REPORTED);
                     elseif ($value == 'rejected')
                         $stmt->bindValue('status_rejected_id', STATUS_REJECTED);
                     elseif ($value == 'held for document update')
                         $stmt->bindValue('status_held_id', STATUS_HELD);
                     elseif ($value == 'all'){
                     } else { 
                         $stmt->bindValue('status_verified_id', STATUS_VERIFIED);
                         $stmt->bindValue('status_reported_id', STATUS_REPORTED);
                     }
                     break;
                 default:
                     $stmt->bindValue($key,$value);
                     break;
             }
         }
     $stmt->execute();
     $num_of_rows = $stmt->rowCount();
     } catch (PDOException $pe){
         error_log("Error processing : found_count", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }
     return $num_of_rows;
}



/*
 * Driver for advanced search.
 * $criteria: Associative array of the search criteria entered by user.
 *
 * NOTE: This function opens the database connection. Subroutines of this
 * module that access the database do not open the connection themselves.
 */
function errata_adv_lookup($criteria)
{
     global $debug_esl;
     if ($debug_esl === true) {
          print("\n<h4>errata_adv_lookup driver</h4>\n");
          print("<pre>\$criteria:\n");
          var_dump($criteria);
          print("\n</pre>\n");
     }

     $total_records = 0;
     $total_found = found_count($criteria);
     if ($total_found > 0) {
          print("<p>Found $total_found record");
          if ($total_found > 1) print("s");
          print(".</p>\n");

          switch ($criteria['rec_status']) {
          case 'all':
               $total_records = query_verified($criteria);
               $total_records += query_reported($criteria);
               $total_records += query_held($criteria);
               $total_records += query_rejected($criteria);
               break;
          case 'verified':
               $total_records = query_verified($criteria);
               break;
          case 'reported':
               $total_records = query_reported($criteria);
               break;
          case 'held for document update':
               $total_records = query_held($criteria);
               break;
          case 'rejected':
               $total_records = query_rejected($criteria);
               break;
          default:
               $total_records = query_verified($criteria);
               $total_records += query_reported($criteria);
               break;
          }
          if ($total_records != $total_found) {
               error_log("errata_adv_lookup: \$total_found did not match \$total_records: $total_found != $total_records");
          }
     } else {
          print("<p>Found 0 records.</p>\n");
     }

}

?>
