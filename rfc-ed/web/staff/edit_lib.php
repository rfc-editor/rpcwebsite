<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
/***********************************************************************************************************/
/*Script Name : edit_lib.php                                                                               */
/*Description : The script handles the mail functionality for Add draft, edit draft and List drafts        */
/*              Works as lib script for add_draft.php, edit_draft.php, list_draft.php and many other       */
/*May 2017    : Modified to add the file type changes  - PN                                                */
/*June 2017   : Modified file_types array  value 1 -> XMLv2 file  - PN                                     */
/*June 2017   : Added one more file type value 6 -> No File in the Add/Edit drop down  - PN                */
/*April 2017   : Modified file type drop down  for Add/Edit draft- PN                                      */
/*              XMLv2 file (X) ,XMLv3 file (v3), NROFF file (N),No file (NO), Looks like XML (X?)          */
/*              Asked for a file (AA),id2xml in progress (in_prog),id2xml complete (id2xml)                */
/*                                                                                                         */
/*September 2020 : Modified the return type from error to warning for editor_key - PN                      */
/*November 2020 : Modified the script to use PDO prepared statements - PN                                  */
/*February  2023 : Modified the script to lock DOC-ID after the publication - PN                           */
/***********************************************************************************************************/
#
# $Id: edit_lib.php,v 2.34 2023/02/22 22:50:32 priyanka Exp $
# $Id: edit_lib.php,v 2.34 2023/02/22 22:50:32 priyanka Exp $
# $Id: edit_lib.php,v 2.34 2023/02/22 22:50:32 priyanka Exp $
# $Id: edit_lib.php,v 2.34 2023/02/22 22:50:32 priyanka Exp $
# $Id: edit_lib.php,v 2.34 2023/02/22 22:50:32 priyanka Exp $
# $Id: edit_lib.php,v 2.34 2023/02/22 22:50:32 priyanka Exp $
# 
# 03-23-10 rpc added get_stream_name

include('export_lib.php');
include('rfc_state_lib.php');
include('editor_lib.php');
include_once('cluster_support_lib.php');

$debug_csl = false; // trigger debug prints in cluster_support_lib.php
$debug_edit_lib = false;

# The enum in the status fields
$statuses = array(' ', 'PROPOSED STANDARD', 'INFORMATIONAL', 'EXPERIMENTAL',
    'UNKNOWN', 'HISTORIC', 'INTERNET STANDARD', 'DRAFT STANDARD',
    'BEST CURRENT PRACTICE', 'STD');
# The valid type values
$types = array('BCP','FYI','RFC','STD','IEN');

# A yes/no array for yes/no radio buttons
$yes_no = array("yes", "no");


#Yes/No flag for IANA flag and Ref flag
$yes_no_flag = array (" ","yes","no");


$consensus_bit = array(" ","yes","no","N/A");

#The file type field from database the xml_file

$file_types = array('0' =>'','1'=>'XMLv2 file (X)','2'=>'NROFF file (N)','3'=>'Looks like XML (X?)','4'=>'Asked for a file (AA)','5'=>'XMLv3 file (v3)','6'=>'No file (NO)','7'=>'id2xml in progress (in_prog)','8'=>'id2xml complete (id2xml)');


# The field names we use as form values.  The PHP scripts all manipulate fields 
# using these names.
$field_names = 
#     array('internal_key', 'draft', 'state', 'iana', 'date_received', 'expiration', 
     array('internal_key', 'draft','iana', 'date_received', 'expiration', 

	'expdite', 'iesg_approved', 'type', 'doc_id', 'title', 'authors',
	'format', 'chars', 'pages', 'pub_status', 'status', 'source',
	'abstract', 'nroffed', 'keywords', 'organization',
	'e_mail', 'notes', 'obsoletes', 'obsoleted_by', 'updates',
	'updated_by', 'see_also', 'see_also_title', 'ref', 'pub_date',
        'queries', 'last_query', 'responses', 'last_response',
        'primary_editor','copy_editor', 'cid', 'doc_shepherd', 'iesg_contact',
	'state_id','generation_number','consensus_bit','rfc_editor','xml_file','sub_page_count','pub_editor');


# Labels for each field
$field_labels = 
     array('internal_key' => 'Internal Key', 'draft' => 'Draft Name',
       	'iana' => 'IANA Requirements',
       	'date_received' => 'Date received', 'expiration' => 'TO expiration date', 
	'expdite' => 'Expedite by date', 'iesg_approved' => 'IESG approved date', 
	'type' => 'Type', 'doc_id' => 'Doc. ID', 'title' => 'Title',
	'authors' => 'Authors', 'format' => 'Format', 'chars' => 'Bytes',
	'pages' => 'Pages', 'pub_status' => 'Publication Status', 
	'status' => 'Current Status', 'source' => 'Source', 'abstract' => 'Abstract',
	'pub_date' => 'Publication Date', 'nroffed' => 'Nroffed',
       	'keywords' => 'Keywords', 'organization' => "Authors' organizations",
	'e_mail' => "Authors' e-mail", 'notes' => 'Notes',
	'obsoletes' => 'Obsoletes', 'obsoleted_by' => 'Obsoleted by', 
	'updates' => 'Updates', 'updated_by' => 'Updated by', 
	'see_also' => 'See Also', 'see_also_title' => 'See Also Title', 
	'ref' => 'Normative references', 
	'queries' => 'Queries', 'last_query' => 'Last query',
        'responses' => 'Responses', 'last_response' => 'Last response',
        'primary_editor' => 'PE', 'copy_editor' => 'CE', 'cid' => 'Cluster ID',
        'doc_shepherd' => 'Document Shepherd', 'iesg_contact' => 'IESG Contact',
        'state_id' => 'State','consensus_bit' => 'Consensus','rfc_editor' => 'RE',
	'xml_file' => 'File type?','sub_page_count' => 'Sub Pages','pub_editor' => 'PUB');


# The mysql columns that a given field corresponds to 
$field_mysqli_cols = 
     array('internal_key' => 'internal_key', 'draft' => 'DRAFT',  
       	'date_received' => 'DATE_RECEIVED', 'expiration' => '`TIME-OUT-DATE`', 
	'expdite' => 'EXPEDITE_NEED_DATE', 'iesg_approved' => 'IESG_APPROVED', 
	'type' => 'TYPE', 'doc_id' => '`DOC-ID`', 'title' => 'TITLE',
	'authors' => 'AUTHORS', 'format' => 'FORMAT', 'chars' => '`CHAR-COUNT`',
	'pages' => '`PAGE-COUNT`', 'pub_status' => '`PUB-STATUS`', 
	'status' => 'STATUS', 'source' => 'SOURCE', 'abstract' => 'ABSTRACT',
	'pub_date' => '`PUB-DATE`', 'nroffed' => 'NROFFED',
       	'keywords' => 'KEYWORDS', 'organization' => "ORGANIZATION",
	'e_mail' => "EMAIL", 'notes' => 'NOTES',
	'obsoletes' => 'OBSOLETES', 'obsoleted_by' => '`OBSOLETED-BY`', 
	'updates' => 'UPDATES', 'updated_by' => '`UPDATED-BY`', 
	'see_also' => '`SEE-ALSO`', 'see_also_title' => '`SEE-ALSO-TITLE`', 
	'ref' => 'REF', 
	'queries' => 'QUERIES', 'last_query' => '`LAST-QUERY`', 
        'responses' => 'RESPONSES', 'last_response' => '`LAST-RESPONSE`',
        'cid' => 'cluster_id', 'doc_shepherd' => 'DOC_SHEPHERD',
        'iesg_contact' => 'IESG_CONTACT','state_id' => '`STATE_ID`',
	'iana' => '`iana_flag`','generation_number'=>'`generation_number`',
	'ref_flag'=>'`ref_flag`','consensus_bit' => '`consensus_bit`',
	'xml_file' => '`xml_file`','sub_page_count' => '`sub_page_count`');
       /*,'primary_editor' => 'PE', 'copy_editor' => 'CE'); */

# Defaults for fields that have defaults
$field_defaults = 
    array( 'state' => 'EDIT', 'iana' => '0', 'date_received' => date('Y-m-d'),
           'type' => 'RFC', 'doc_id' => 'RFC', 'format' => 'ASCII',
           'pub_status' => ' ', 'status' => ' ', 'consensus_bit' => 'N/A','xml_file' => '0');

# Regexps for parsing out multi-entry fields from their DB format.
$list_split = 
    array('authors' =>"/\s*,\s*/", 'e_mail' => "/[\s,]+/", 
    'organization' => "/\s*,\s*/", 'ref' => "/[\s,]+/", 
    'obsoletes' => "/[\s,]+/", 'obsoleted_by' => "/[\s,]+/",
    'updates' => "/[\s,]+/", 'updated_by' => "/[\s,]+/",
    'see_also' => "/[\s,]+/");

# The list of required field names
$required =
     array("draft", "state_id", "iana", "chars", "date_received", "type", "title",
           "authors", "format", "pub_status", "source", "e_mail");

# return an array containing the DB entry for the given draft.  If there's a 
# DB error or if more than one record matches, return an array with a single 
# value $fields['error'] that contains an error message.  Otherwise return 
# the keyed array.  If you plan to do more than just pulling, be sure to use
# db_open and close outside here.  If $raw is set, the state is not adjusted
# for use  in an edit form.  If draft is empty and docid is given, do the query 
# by docid.
function pull_from_db($key, $raw=0, $keyfield="draft") {
   global $field_names, $field_mysqli_cols, $states;
   global $editor_field_names;
   global $debug_edit_lib;
   global $pdo; 

   if ($debug_edit_lib === true) {
       print("<h3>pull_from_db</h3>");
       print("&nbsp;key=");     var_dump($key);      print("<br />");
       print("&nbsp;raw=");     var_dump($raw);      print("<br />");
       print("&nbsp;keyfield"); var_dump($keyfield); print("<br />");
   }

   $query= "select ";
   $first = 1;

   foreach ($field_mysqli_cols as $field => $name) {
       $query .= (($first) ? " " : ", " ) . $name;
       $first = 0;
   }

   $query .= " from `index` i LEFT JOIN clusters l ON (i.draft LIKE CONCAT(l.draft_base,'-__')) WHERE $field_mysqli_cols[$keyfield] ";
    if ($keyfield == 'internal_key') {
       $query .= " = :internal_key";   // we're matching by id number
   } else {
         $query .= "regexp :key_v"; // we're comparing strings
   }
   
   if ($debug_edit_lib === true) {
       print("query=<br />");
       print_r($query);
       print("<br />");
   }

   try {
       $stmt = $pdo->prepare($query);
       if ($keyfield == 'internal_key') {
           $stmt->bindValue('internal_key',$key);
       } else {
           $key_v  = ".*($key).*";
           $stmt->bindValue('key_v',$key_v);
       }
       $stmt->execute();
       $num_of_rows = $stmt->rowCount(); 
   } catch (PDOException $pe){
       error_log("Error processing : pull_from_db", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
  
   if ( $num_of_rows < 1 ) return array('error' => "No such draft: $key");
   else if ( $num_of_rows > 1) 
       return array('error' =>
		"Draft is not unique ($rows matches): $key");
   
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

   foreach ($field_mysqli_cols as $name => $col ) 
       $fields[$name] = $row[trim($col, "`")];
    
   $fields['primary_editor'] = get_primary_editor($fields['internal_key']);
   $fields['copy_editor'] = get_copy_editor($fields['internal_key']);
   $fields['rfc_editor'] = get_rfc_editor($fields['internal_key']);   
   $fields['pub_editor'] = get_pub_editor($fields['internal_key']);   

   return $fields;
}

# For a given keyfield, look it up as a regexp and return a set of links to 
# edit pages that will retrieve them.  Called if a pull_from_db returns 
# multiple links.  The key and keyfield are passed and an array of valid links 
# are returned.  $page  is the page to link to from the global $base.  Like 
# edit_draft, $page needs to accept query strings of the form draft= or 
# doc_id=.
function matching_links($key, $keyfield="draft", $page="edit_draft.php") {
   global $base, $field_mysqli_cols, $pdo;
   
   # Pull the matching fields from the DB.  Use the query itself to construct 
   # the HTML to link to them.  When constructing the link, prefer a doc_id 
   # query string to a draft one for published documents.
   # The ord field is synthesized to allow us to present the results with 
   # ubpublished drafts first in alphabetical order then RFCs by number.  
   # Similarly the did field cleans up a grubby state of affairs in DOC-ID.
   $query = "
       SELECT
	   CASE
	       WHEN (draft is not null and draft != \"\") 
		   and (`DOC-ID` is not null and `DOC-ID` != \"\"
	           and `DOC-ID` != 'RFC') 
		   and (state_id != '14') 
	       THEN CONCAT('<a href=\"$base/$page?draft=', draft,
			'\">',`DOC-ID`, ' (', draft, ')</a>')
	       WHEN (draft is not null and draft != \"\") 
		   and (`DOC-ID` is not null and `DOC-ID` != \"\") 
		   and (state_id = '14') 
	       THEN CONCAT('<a href=\"$base/$page?doc_id=', `DOC-ID`,
			'\">',`DOC-ID`, ' (', draft, ')</a>')
	       WHEN (draft is not null and draft != \"\") 
		   and (`DOC-ID` is null or `DOC-ID` != \"\") 
	       THEN CONCAT('<a href=\"$base/$page?draft=', draft,
			'\">', draft, '</a>')
	       WHEN (draft is null or draft != \"\") 
		   and (`DOC-ID` is not null and `DOC-ID` != \"\") 
		   THEN CONCAT('<a href=\"$base/$page?doc_id=', 
		    `DOC-ID`, '\">',`DOC-ID`, '</a>')
		   ELSE \"Something weird\"
	       END,
	   CASE
	       WHEN state_id != '14' then 0
	       ELSE 1
	       END ord,
	   CASE 
	       WHEN `DOC-ID` = 'RFC' or `DOC-ID` is NULL THEN ''
	       ELSE `DOC-ID`
	       END did
       FROM `index`
	   WHERE $field_mysqli_cols[$keyfield] REGEXP :key
	   ORDER BY ord, did DESC, draft";

   try {
       $stmt = $pdo->prepare($query);
       $key = ".*(".$key.").*";
       $stmt->bindValue('key',$key);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : matching_links", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   # Nothing could be simpler.  Copy the results out.

   while ($row = $stmt->fetch(PDO::FETCH_NUM)) 
       $rv[] = $row[0];
    
   return $rv;
}

# Output a pair of table cells with a text input field for the given field
# name .  The label is retrieved from the field_labels array and the value from 
# fields.
function table_text_input($name, $width=70) {
    global $fields;
    global $field_labels;
    global $read_only;
    global $field_errors;

    $label_class = ""; // so PHP won't complain about varible not set
    $attr        = ""; // ditto
    if ( $read_only[$name] ) {
	$attr = " readonly";
	$label_class="class=\"readonly\"";
    }
    elseif ( $field_errors[$name] ) {
	$label_class = " class=\"error\"";
    }

    /*DOC_ID is read only for PUB documents*/
    if ($name == 'doc_id') {
       if ($fields['state_id'] == '14'){
           $attr = " readonly";
           $label_class="class=\"readonly\"";
       }
    
    }
    

    print '
    <tr>
	<td>
           <label ' . $label_class . ' for="' . htmlspecialchars($name) . '">' . htmlspecialchars($field_labels[$name]) . ':</label>
        </td>
	<td>
           <input type="text" ' . $attr . ' size="' . htmlspecialchars($width) . '" name="' . htmlspecialchars($name) . '"
	    value="' . htmlspecialchars($fields[$name]) . '" id="' . htmlspecialchars($name) . '"/>
        </td>
    </tr>';
}

# Some fields should be presented together on one line of the screen. This puts the fields
# in the array $names into one row of the form table.
function table_text_input_row($names, $width=2) {
    global $fields;
    global $field_labels;
    global $field_errors;
    $label_class = ""; // so PHP won't complain about varible not set
    print("<!-- \$field_errors array\n");
    print_r($field_errors);
    print("\n-->");
    print("\n<tr>\n");
    foreach ($names as $name) {
         print("<!--\$field_errors $name is {$field_errors[$name]} -->\n");
         if ($field_errors[$name]) {
              $label_class=" class=\"error\"";
         }
         print("      <td>\n<label $label_class for=\"$name\">$field_labels[$name]:</label></td>\n");
         print("      <td>\n<input type=\"text\" size=$width name=\"$name\" ");
         print("value=\"$fields[$name]\" id=\"$name\" /></td>\n");
    }
    print("\n</tr>\n");
}

# Output a pair of table cells with a select box in it for the specified
# field name.  Choices is an array of valid choices for that field.  The label 
# is chosen from the global field_labels array.  Choices are scrubbed through 
# htmlspecialchars as they may come from the DB or other untrustworthy places.
function table_select($name, $choices) {
    global $fields;
    global $field_labels;
    global $read_only;
    global $field_errors;

    $label_class = ""; // so PHP won't complain about varible not set
    if ( $read_only[$name] ) {
	$attr = " readonly";
	$label_class="class=\"readonly\"";
    }
    elseif ( $field_errors[$name] ) {
	$label_class = " class=\"error\"";
    }

    print <<<END
    <tr>
    <td><label $label_class for="$name">$field_labels[$name]:</label></td>
	<td><select $attr name="$name" id="$name">
END;
    foreach ($choices as $choice) {
	if ($fields[$name] == $choice ) 
	    print "<option selected>" . htmlspecialchars($choice) . 
		"</option>\n";
	else
	    print "<option>" . htmlspecialchars($choice) . "</option>\n";
    }
    print <<<END
	</select></td>
    </tr>
END;
}

# Output a text area as a pair of table cells - label and text area.  The
# textarea is for teh given field and teh label picked from the global 
# $field_labels.  Row and column widths are given and default to a single
# row of 80 columns - which looks nice on Firefox with the default text_input.
function table_textarea($name, $rows=1, $cols=80) {
    global $fields;
    global $field_labels;
    global $read_only;
    global $field_errors;

    $label_class = ""; // so PHP won't complain about varible not set
    if ( $read_only[$name] ) {
	$attr = " readonly";
	$label_class="class=\"readonly\"";
    }
    elseif ( $field_errors[$name] ) {
	$label_class = " class=\"error\"";
    }

    if ( $rows ) $dim = " rows=\"" . htmlspecialchars($rows) . "\"";
    if ( $cols ) $dim .= " cols=\"" . htmlspecialchars($cols) . "\"";
    print '
	<tr>
	    <td>
		<label ' . $label_class . ' for="' . htmlspecialchars($name) . '">' . htmlspecialchars($field_labels[$name]) . ':</label>
	    </td>
	    <td>
		<textarea ' . $dim . ' ' . $attr . ' name="' . htmlspecialchars($name) . '" id="' . htmlspecialchars($name) . '">' . htmlspecialchars($fields[$name]) . '</textarea>
	    </td>
	</tr>';
}

# Put out a set of radio buttons for an exclusive choice in a air of tables 
# cells.  The choice cell contains another table, arranging the buttons in a 
# column.  The column tabls is of class choices for formatting.  $name is the 
# field to set and $choices are the radio button selections.  The label is
# taken from the global $field_labels and the field value from the global
# $fields. 
function table_radio_input($name, $choices) {
    global $fields;
    global $field_labels;
    global $read_only;
    global $field_errors;
    global $debug_edit_lib;


#    print_r($fields);
#    print_r($field_labels);

    if ($debug_edit_lib === true) {
        print("<h3>table_radio_input</h3>");
        print("&nbsp;Field value="); var_dump($fields[$name]);      print("<br />");
        print("&nbsp;Field name=");  var_dump($name);      print("<br />");
        print("&nbsp;Field choices=");  var_dump($choices);      print("<br />");
        print("&nbsp;Field choices=");  var_dump($field_labels[$name]);      print("<br />");

    }

    $label_class = ""; // so PHP won't complain about varible not set
    if ( $read_only[$name] ) {
	$attr = " readonly";
	$label_class="class=\"readonly\"";
    }
    elseif ( $field_errors[$name] ) {
	$label_class = " class=\"error\"";
    }


    print "<tr><td>$field_labels[$name]:</td><td>\n";
    print "<table class=\"choices\">\n";

/*    foreach ($choices as $choice) {
	if ($choice == $fields[$name] ) 
	    print "<tr><td><input $attr type=\"radio\" checked name=\"$name\" ". 
		"value=\"$choice\" id=\"${name}_$choice\" /></td><td>" . 
		"<label $label_class for=\"${name}_$choice\">$choice</label>" .
		"</td></tr>\n";
	else
	    print "<tr><td><input $attr type=\"radio\" name=\"$name\" ". 
		"value=\"$choice\" id=\"${name}_$choice\" /></td><td>" . 
		"<label $label_class for=\"${name}_$choice\">$choice</label>" .
	       	"</td></tr>\n";
    }
*/
    foreach ($choices as $choice) {
	if (($choice == "yes") AND ($fields[$name] == '1')){
	     print "<tr><td><input $attr type=\"radio\" checked name=\"$name\" ". 
		"value=\"$choice\" id=\"${name}_$choice\" /></td><td>" . 
		"<label $label_class for=\"${name}_$choice\">$choice</label>" .
		"</td></tr>\n";
        }
	elseif (($choice == "no") AND ($fields[$name] == '0')){
	     print "<tr><td><input $attr type=\"radio\" checked name=\"$name\" ". 
		"value=\"$choice\" id=\"${name}_$choice\" /></td><td>" . 
		"<label $label_class for=\"${name}_$choice\">$choice</label>" .
		"</td></tr>\n";
        }	    	
	else
	    print "<tr><td><input $attr type=\"radio\" name=\"$name\" ". 
		"value=\"$choice\" id=\"${name}_$choice\" /></td><td>" . 
		"<label $label_class for=\"${name}_$choice\">$choice</label>" .
	       	"</td></tr>\n";
    }

     print "</table>\n</td></tr>\n";
}

# Put out a checkbox for an exclusive choice in a air of tables # cells.  
# $name is the  field to set and the javascript function getXMLvalue gets the value for
# the checkbox.  The label is taken from the global $field_labels and the field value from 
# the global $fields. 
function table_checkbox_input($name) {
    global $fields;
    global $field_labels;
    global $read_only;
    global $field_errors;
    global $debug_edit_lib;


    if ($debug_edit_lib === true) {
        print("<h3>table_checkbox_input</h3>");
        print("&nbsp;Field value="); var_dump($fields[$name]);      print("<br />");
        print("&nbsp;Field name=");  var_dump($name);      print("<br />");
        print("&nbsp;Field choices=");  var_dump($field_labels[$name]);      print("<br />");

    }

    $label_class = ""; // so PHP won't complain about varible not set
    if ( $read_only[$name] ) {
	$attr = " readonly";
	$label_class="class=\"readonly\"";
    }
    elseif ( $field_errors[$name] ) {
	$label_class = " class=\"error\"";
    }


    print "<tr><td>$field_labels[$name]</td><td>\n";
    print "<table class=\"choices\">\n";

   
      
     if ($fields[$name] == '1'){
     	 print "<tr><td><input $attr type=\"checkbox\" checked name=\"$name\" ".
               "onChange=\"getXMLvalue()\" id=\"${name}\" /></td></tr>";
     }elseif ($fields[$name] == '0'){
     	 print "<tr><td><input $attr type=\"checkbox\" name=\"$name\" ".
               "onChange=\"getXMLvalue()\" id=\"${name}\" /></td></tr>";
     }else {
	     	 print "<tr><td><input $attr type=\"checkbox\" name=\"$name\" ".
        	       "onChange=\"getXMLvalue()\" id=\"${name}\" /></td></tr>";

     }


     print "</table>\n</td></tr>\n";
}

# Emit a hidden form with all the global values in $fields emitted in hidden
# values and a set of submit buttons labelled as in $buttons.  The form is to
# be posted to $dest.  This allows us to keep track of information when 
# displaying error messages, etc.  If there are fields flagged as having 
# errors (because $field_errors[field] is set, export that list as well.
function hidden_form($dest, $buttons) {
    global $fields;
    global $field_errors;

    print "<form action=\"" . htmlspecialchars($dest) . "\" method=\"post\">\n";

    foreach ($fields as $name => $value )
	if ( $value ) 
	    print "<input name=\"" . htmlspecialchars($name) . "\" value=\"" . htmlspecialchars($value) . 
		"\" type=\"hidden\"/>\n";

    if ($field_errors ) {
	print "<input name=\"field_errors\" value=\"" .
	    htmlspecialchars(implode(",", array_keys($field_errors))) . 
	    "\" type=\"hidden\"/>\n";
    }
    foreach ($buttons as $button ) 
	print "<input name=\"submit\" " .  "value=\"" . htmlspecialchars($button) . "\" " . 
	    "type=\"submit\"/>\n";

    print "</form>\n";
}

# Turn a DB formatted list into a lsit of values separated by newlines.
# This makes the multivalue variables look like multi-values on screen and 
# newline is a more safe delimiter than the commas and plus signs that seem to 
# be running around.
function canonicalize_list($orig, $split="/[\s,]+/", $join="\n") {
    $new =  preg_replace($split, $join, trim($orig));
    return preg_replace("/${join}$/", "", $new);
}

# Count the fields in a newline-separated list.  This needs to be a little careful to count 1-element lists correctly.
function count_canonical_list($list) { 
    return ($list) ? substr_count($list, "\n") + 1 : 0;
}

# Spit out the field to edit a document entry.  This is a linear combination of 
# the individual input generators.
function edit_document_form($dest) {
    global $fields;
    global $wgs,$statuses, $yes_no, $states, $types, $consensus_bit, $file_types;
    global $editor_field_names;
    global $debug_edit_lib;

    print '
<form action="' . htmlspecialchars($dest) . '" method="post">
<table>';

    table_text_input_row($editor_field_names);

    print<<<END
</table>
<table>
END;
    $stage = strpos($_SERVER['PHP_SELF'],"edit_draft");
    if ($debug_edit_lib === true) {print("<p>\$stage="); var_dump($stage); print("</p>"); }
    if (!$stage === false) {
        table_text_input('internal_key');
    }
    table_text_input("draft");

    /*Modify the xml_file from check box to drop down*/
    get_edited_filetype("xml_file");

    get_edited_state("state_id");

    if ($fields['state_id'] == "18" )  
	table_text_input("expiration");

    
    if (!$stage === false) {
        table_text_input('pub_date');
    }

  /*IANA ADJUSTMENTS */
    if (is_null($fields['iana'])){
	$fields['iana'] = '0';
	}

    if ($fields['iana'] == "no"){
        $fields['iana'] = '0';
    }

    if ($fields['iana'] == "yes"){
        $fields['iana'] = '1';
    }

    table_radio_input("iana", $yes_no);

    table_textarea("title", 2);
    table_textarea("authors", 3);
    table_textarea("e_mail", 3);
    table_textarea("organization", 3);
    table_text_input("date_received");
    table_text_input("expiration");
    table_text_input("expdite");
    table_text_input("iesg_approved");
    table_select("consensus_bit", $consensus_bit);
    if (!$stage === false) {
        table_select("status", $statuses);
    }
    table_select("pub_status", $statuses);
    table_select("source", $wgs);
    table_text_input("doc_shepherd");
    table_text_input("iesg_contact");
    table_text_input("keywords");
    table_textarea("abstract", 5);
    table_text_input("doc_id");
    table_select("type", $types);
    table_text_input("format");
    table_text_input("chars");
    table_text_input("sub_page_count");
    table_text_input("pages");
    if (strpos($dest,'add_draft.php') === false) {
         // only include when editing existing records
         table_text_input("cid", 10);
    }
    table_textarea("ref", 3);
    table_textarea("obsoletes", 3);
    table_textarea("obsoleted_by", 3);
    table_textarea("updates", 3);
    table_textarea("updated_by", 3);
    table_textarea("see_also", 3);
    table_textarea("notes", 15);

    print "<tr><td colspan=\"2\"><input name=\"submit\" " . 
	"value=\"submit\" type=\"submit\"/></td></tr>\n";
    print "</table>\n";

// Setup the internal key as lookup key for the commit so that the draft
// string can be edited.
    print "<input type=\"hidden\" name=\"keyfield\" " .
        "value=\"internal_key\"/>\n";

    print "</form>\n";

}

# Error check the user data if a commit has bee requested.  Returns a string 
# indicating what, if anything is wrong.
# The following are errors and must be corrected:
#   All fileds in (global) $required have a value
#   The state field and iana fields are valid
#   The number of authors and e-mail addresses match
#
# A non-fatal warning is generated if
#   The number of organizations doesn't match the number of authors
# Warning or Error is indicated by prefixing the string with that string.
# Fields with errors are added to the global $field_errors array, which will be 
# exported by warn_page or error_page via hidden_form.
function check_fields() {
    global $fields;
    global $states, $required, $yes_no, $field_labels;
    global $field_errors;
    global $editors, $editor_field_names;
    global $debug_edit_lib;
    global $pdo;    

    if ($debug_edit_lib === true) {
         print("<h4>check_fields</h4><pre>");
         print_r($fields); print("\n");
         print_r($editors); print("\n");
         print("</pre>");
    }


    #MISSREF drafts does not allow to select states so adjustment
    if (!($fields['state_id'])){
	$fields['state_id'] = '22';
	if ($debug_edit_lib === true) {
         print("<h4>State change</h4><pre>");
         print $fields['state_id'];
         print("</pre>");
    	}
    }	    

   
    #Generation number adjustment if it is not selected through MISSREF then make it 0
    if (!($fields['generation_number'])){
	$fields['generation_number'] = '0';
	if ($debug_edit_lib === true) {
         print("<h4>Generation_number change</h4><pre>");
         print $fields['generation_number'];
         print("</pre>");
    	}
    }	    

    #xml_file flag adjustment if it is not selected due to on value
/*    if ($fields['xml_file']){
	$fields['xml_file'] = '1';
	if ($debug_edit_lib === true) {
         print("<h4>XML file change</h4><pre>");
         print $fields['xml_file'];
         print("</pre>");
        }
    }
*/

    # check for multi-cluster problem
    $refs = explode("\n",$fields['ref']);
    $draft_base = strip_number($fields['draft']);
    $cid_array = array();
    $cid_error_array = array();

    $cid = get_cluster_id($pdo,$draft_base);
    if (is_string($cid) && (preg_match('/^C\d+/',$cid) == 1)){
        array_push($cid_array, $cid);
        array_push($cid_error_array, $draft_base.' in '.$cid);
    }

    foreach ($refs as $ref){
        $ref_cid = get_cluster_id($pdo,trim($ref));
        if (is_string($ref_cid) && (preg_match('/^C\d+/',$ref_cid) == 1)){
            if (!in_array($ref_cid, $cid_array)){
                array_push($cid_array, $ref_cid);
                array_push($cid_error_array, $ref.' in '.$ref_cid);
            }
        }
    }

    if ($debug_edit_lib === true) {
         print("<h4>cid array</h4><pre>");
         print_r($cid_array); print("\n");
         print("<h4>refs</h4><pre>");
         print_r($refs); print("\n");
         print("</pre>");
    }

    if (count($cid_array) > 1 ){
         return "Error: Attempt to give ".$draft_base." constraints references in multiple clusters! " . implode(", ",$cid_error_array);
    }
    # --------- end cluster check

    # check for missing fields
    foreach ($required as $req ) {
	if (!$fields[$req]) {
	    $missing[] = $field_labels[$req];
	    $field_errors[$req] = 1;
	}
    }

    if ( count($missing) ) 
	return "Error: missing required fields: " . implode(", ",$missing);

    foreach ($editor_field_names as $editor_key) {
         if (($fields[$editor_key] != null) && (array_search($fields[$editor_key],$editors)) === false) {
              $field_errors[$editor_key] = 1;
              return "Warning: unknown editor in $editor_key";
         }
    }

    #Get all the valid states from state lookup table and check the validity of entered state
    list($state_id,$state_name_all,$state_name,$state_table) = get_state_only();

/*    if ( !in_array($fields['state'], $states)) {
	$field_errors['state'] = 1;
	return "Error: Bad state(!?): " . $fields['state'];
    }
*/
    if ( !in_array($fields['state_id'], $state_id)) {
	$field_errors['state_id'] = 1;
	return "Error: Bad state(!?): " . $fields['state_id'];
    }

    if ( !in_array($fields['iana'], $yes_no)) {
	$field_errors['iana'] = 1;
	return "Error: bad iana conflict: " . $fields['iana'];
    }

    if ($fields['pub_status'] == ' ') {
         $field_errors['pub_status'] = 1;
         return "Error: Publication Status set to blank";
    }


    $au = count_canonical_list($fields['authors']);
    $org = count_canonical_list($fields['organization']);
    $email = count_canonical_list($fields['e_mail']);

    if ( $au != $org ) {
	$field_errors['authors'] = $field_errors['organization'] = 1;
	return "Warning: number of authors different from number of " . 
	    "organizations";
    }
    if ( $au != $email ) {
	$field_errors['authors'] = $field_errors['e_mail'] = 1;
	return "Error: Number of authors different from number of ". 
	    "e-mail addresses";
    }
    if ( !empty($fields['doc_shepherd']) && (preg_match('/^[^@\s]+@([-a-z0-9]+\.)+[a-z]{2,}$/i', $fields['doc_shepherd']) != 1)) {
        $field_errors['doc_shepherd'] = 1;
        return "Error: Document Shepherd must be a valid email address";
    }
    if ( !empty($fields['iesg_contact']) && (preg_match('/^[^@\s]+@([-a-z0-9]+\.)+[a-z]{2,}$/i', $fields['iesg_contact']) != 1)) {
        // $field_errors['iesg_contact'] = 1;
        return "Warning: IESG Contact must be a valid email address";
    }
    if ( !empty($fields['title']) && (preg_match('/\n/',$fields['title']))) {
       // $field_errors['iesg_contact'] = 1;
        return "Warning: Title contains a line break";
    }



//If the Normatic reference ends up with a version number, show it as error.

    foreach ($refs as $ref){
        if (preg_match('/-[0-9][0-9]$/',$ref) == 1){
            return "Warning: It appears that a normative reference has been entered with a version number. It should be without a version number. ";
        }
    }


// If the internal_key field exists, it is a document edit, else a document add
    if (array_key_exists('internal_key',$fields)) {
         if (is_duplicate_draftname($fields['internal_key'],$fields['draft'])) {
              $field_errors['draft'] = 1;
              return "Error: This update will create more than one entry with the same draft name";
         }
    } else {
         $count = count_drafts($fields['draft']);
         if ($debug_edit_lib === true) {
              print("\$count = "); var_dump($count); print("<br />");
         }
         if ($count > 0) {
              $field_errors['draft'] = 1;
              return "Error: This update will create more than one entry with the same draft name";
         }
    }
    return "";
}

# Return the count of records with this draft name
function count_drafts($draftname) {
   global $debug_edit_lib;
   global $pdo;

   if ($debug_edit_lib === true) {
       print("<h5>count_drafts</h5><p>");
       var_dump($draftname);
       print("<br />");
   }
   $sql_statement = "SELECT count(draft) FROM `index` WHERE draft = :draftname";
   try {
      $stmt = $pdo->prepare($sql_statement);
       $stmt->bindParam('draftname',$draftname);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : count_drafts", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   if ($debug_edit_lib === true) {
       print_r($sql_statement);
       print("<br />");
   }
   
   $row = $stmt->fetch(PDO::FETCH_NUM);
   $count = $row['0']; 
   
 //  if ($debug_edit_lib === true) {
       print("Result of count query for  $draftname is $count");
       var_dump($count);
       print("</p>");
  // }
   return $count;
}
# Check that the draft string either points to the same record in the database
# or that no other record in the database has this same draft name
function is_duplicate_draftname($editing_key, $draftname) {
   global $debug_edit_lib;
   global $pdo;
     
   $sql_statement = "SELECT internal_key FROM `index` WHERE draft = :draftname";
   
   try {
       $stmt = $pdo->prepare($sql_statement);
       $stmt->bindParam('draftname',$draftname);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   }catch (PDOException $pe){
       error_log("Error processing : is_duplicate_draftname", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   if ($debug_edit_lib === true) {
       print("<h5>is_duplicate_draftname</h5><p>");
       var_dump($editing_key);   print("<br />");
       var_dump($draftname);     print("<br />");
       var_dump($sql_statement); print("<br />");
   }

   $row_count = $num_of_rows;
   if ($debug_edit_lib === true) { var_dump($row_count); print("<br />"); }
   if ($row_count ==  0) { // no drafts with this name
       return false;
   }
   $row = $stmt->fetch(PDO::FETCH_ASSOC);
   $existing_key = $row['internal_key'];
   if ($debug_edit_lib === true) { var_dump($existing_key); print("</p>"); }
       if ($existing_key == $editing_key) {
           return false;         // new name is the same as old name
       }
   return true;               // the internal keys do not match! Error if committed!
}


# Request had an error.  Print the error string ($err) and allow the user to 
# re-edit the data.  (And only to re-edit)
function error_page($err, $dest) {
    print '
<p>
    Your request had the following errors.  Hit the &ldquo;re-edit&rdquo;
    button and fix the error(s).
</p>
<p class="error">
    ' . htmlspecialchars($err) . '
</p>';
    hidden_form($dest, array("re-edit", "commit"));
}

# Request had a warning.  Tell the requestor, but allow a commit.
function warn_page($err, $dest) {
    print '
<p>
    Your request had the following warnings.  Either hit the 
    &ldquo;re-edit&rdquo; button and fix the error(s) or the 
    &ldquo;commit&rdquo; button to ignore the warnings and commit the record 
    to the database.
</p>
<p class="warning">
    ' . htmlspecialchars($err) . '
</p>';
    hidden_form($dest, array("re-edit", "commit"));
}

# All is well.  Let the requestro commit.  This is put up because web forms 
# sometimes go on through to commit when enter is pressed at the wrong time.  
# Putting up this page makes accidental commits less likely.
function commit_page($dest) {
    print <<<END
<p>
    Ready to commit your record to the database.  Hit &ldquo;commit&rdquo; to 
    complete the operation or &ldquo;re-edit&rdquo; to return to editing this 
    draft.
</p>
END;
    hidden_form($dest, array("commit", "re-edit"));
}

# Pump out a little DB error message wrapped in HTML.
function db_err($str) {

    print "<p>Database error: $str</p>\n";
    print"<pre>" . mysqli_error($db_link) . "</pre>\n";
}

# Populate the $wgs variable from the DB.  If the database wasn't open when 
# this is called, it will be and closed afterward.  This may call rfc_fail.
function get_wgs($open_only=false) {
   global $wgs;
   global $debug_edit_lib;
   global $pdo;

   # Get the working group names for use as a selection in the edit form
   $query="SELECT WG_NAME FROM working_group ";
   if ($open_only === true) {
       $query .= "WHERE WG_STATUS=:wg_status ";
   }
   $query .= "ORDER BY WG_NAME";

   try {
       $stmt = $pdo->prepare($query);
       if ($open_only === true) {
           $stmt->bindValue('wg_status','open');
       }
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   }catch (PDOException $pe){
       error_log("Error processing : get_wgs", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   if ($debug_edit_lib === true) {
       print("<h5>get_wgs</h5><p>");
       print "$query"."<br>";
   }
  
   while ( $line = $stmt->fetch(PDO::FETCH_NUM))
       $wgs[] = trim($line[0]);
}

/*****************************************************************************************/
/*state_adjust_logic : Function is used to adjust the state of MISSREF drafts and run final 
                       ref check run on the ADD/EDIT draft                               */
/*****************************************************************************************/

function state_adjust_logic(){
    global $fields;
    global $debug_edit_lib;
    
    print("<h2>Running MISSREF logic</h2>");
    /*1. Run ref fields check logic on MISSREF1G drafts*/  
    run_check_on_missref(1);

    /*2. Run ref fields check logic on MISSREF2G drafts*/  
    run_check_on_missref(2);

    /*3. Run ref fields check logic on MISSREF3G drafts*/  
    run_check_on_missref(3);

    /*4 Check the ref of the remaining drafts in the database*/
    run_check_on_other();

   /*5. Run ref fields check logic on ADD/EDIT draft again fields array hold it values*/
   /*   The last values passed to functions are flags which indicates that this draft */
   /*   has come from the database with 'N' and need to show reference chain with 'Y' */

    list($out_state_id,$out_generation_number, $out_iana_flag, $out_ref_flag) = check_ref_field($fields['draft'],$fields['state_id'],$fields['ref'],$fields['ref_flag'],$fields['iana'],$fields['generation_number'],'N','Y');

    $old_state_id = $fields['state_id'];
    $old_iana_flag = $fields['iana'];
    $old_ref_flag = $fields['ref_flag'];
    $old_generation_number = $fields['generation_number'];         

    $old_state_name = get_state_name($old_state_id);      
    if ($old_iana_flag == '1'){
        $old_iana = '*A';
    }else {
        $old_iana = "";
    } 
            
    if ($old_ref_flag == '1'){
          $old_ref = '*R';
    }else {
          $old_ref = "";
    }
 
    if ($old_generation_number > '0'){
          $old_generation = "(".$old_generation_number."G)";
    }else {
          $old_generation = ""; 
    }

    $old_state = "$old_state_name$old_iana$old_ref$old_generation";

    $out_state_name = get_state_name($out_state_id);      
    if ($out_iana_flag == '1'){
        $out_iana = '*A';
    }else{
        $out_iana = "";
    } 
            
    if ($out_ref_flag == '1'){
          $out_ref = '*R';
    }else{
          $out_ref = "";
    }
    if ($out_generation_number > '0'){
          $out_generation = "(".$out_generation_number."G)";
    }else {
          $out_generation = ""; 
    }

    $old_state = "$old_state_name$old_iana$old_ref$old_generation";
  
    $new_state = "$out_state_name$out_iana$out_ref$out_generation";
    
    print "For draft : ". htmlspecialchars($fields['draft']) . "&nbsp;&nbsp;&nbsp;&nbsp;Temporary state : " . htmlspecialchars($old_state) . " &nbsp;&nbsp;&nbsp;&nbsp;Final state : ". htmlspecialchars($new_state);
      
    if ($new_state != $old_state){   
#	   print "For draft :". $fields['draft'] ." Old state : " .$old_state ." New state : ". $new_state;
           $index_status = update_index($fields['internal_key'],$out_state_id,$out_iana_flag,$out_ref_flag,$out_generation_number);
      	   if ($index_status){ //insert state data to state history table 
           } else {
           rfc_fail("Update failed on Index: state_adjust_logic for $draft");
       	   }
    } 

    $fields['state_id'] = $out_state_id;
    $fields['iana'] = $out_iana_flag;
    $fields['ref_flag'] = $out_ref_flag;
    $fields['generation_number'] = $out_generation_number;
   
}
/*****************************************************************************************/
/*run_check_on_missrref : Function is used to get the 1/2/3 generation drafts and        */
/*                        run the check ref fields logic on each of the missref draft to */
/*                        find 'Add/Edit draft' has impacted the missref drafts.         */
/*****************************************************************************************/

function run_check_on_missref($generation_number){
    global $fields;
    global $debug_edit_lib;
   
   /*1. Get all the draft in MISSREF 1G*/

   $generation_draft = get_generation_draft($generation_number);	

    

   if ($debug_edit_lib === true) {
    	print("<h2>run_check_on_missref</h2>");
    	print("<h4>Generation draft for $generation_number generation</h4>");
        print_r ($generation_draft);

   }

   print "<table><tr><th>The following drafts in $generation_number"."G changed state</th></tr>";
   print "<tr><td></td></tr><tr><td></td></tr></table>";

   /*2 Run the check_ref_field on every MISSREF1G draft and use output to set its state */
   for ($row = 0; $row < count($generation_draft) ; $row++){
         for ($col = 0; $col < 6; $col++)
         {
           $internal_key = $generation_draft[$row][0];
           $draft= $generation_draft[$row][1];
           $state_id = $generation_draft[$row][5];
           $ref = $generation_draft[$row][2];
           $ref_flag = $generation_draft[$row][3];
           $iana_flag = $generation_draft[$row][4];
           $generation_number = $generation_draft[$row][6];

         }

           $old_state_name = get_state_name($state_id);      
           if ($iana_flag == '1'){
               $old_iana = '*A';
           }else{
               $old_iana = "";
	   } 
            
           if ($ref_flag == '1'){
               $old_ref = '*R';
           }else {
               $old_ref = ""; 
           }
	   if ($generation_number > '0'){
               $old_generation = "(".$generation_number."G)";
           }else {
               $old_generation = ""; 
	   }

           $old_state = "$old_state_name$old_iana$old_ref$old_generation";

           list($out_state_id,$out_generation_number, $out_iana_flag, $out_ref_flag) = check_ref_field($draft,$state_id,$ref,$ref_flag,$iana_flag,$generation_number);
           $out_state_name = get_state_name($out_state_id);      
           if ($out_iana_flag == '1'){
               $out_iana = '*A';
           }else{
               $out_iana = "";
           } 
            
           if ($out_ref_flag == '1'){
               $out_ref = '*R';
           }else {
               $out_ref = "";
           }
	   if ($out_generation_number > '0'){
               $out_generation = "(".$out_generation_number."G)";
           }else {
               $out_generation = ""; 
	   }

           $new_state = "$out_state_name$out_iana$out_ref$out_generation";
          
           print "<table>";
           if ($new_state != $old_state){   
   	           print "<tr><td>$draft modified from state : $old_state -> $new_state</td></tr>";                                                            	                $version_number = substr($draft,-2); //Get the version number
          	   $status = insert_to_state_history($internal_key,$out_state_id,$out_iana_flag,$out_ref_flag,$out_generation_number,$version_number);
          	   if ($status){ //insert state data to state history table
   	           } else {
               	    rfc_fail("Insert failed on State History: run_check_on_missref for $draft");
       	           }
                   $index_status = update_index($internal_key,$out_state_id,$out_iana_flag,$out_ref_flag,$out_generation_number);
          	   if ($index_status){ //insert state data to state history table 
   	           } else {
               	    rfc_fail("Update failed on Index: run_check_on_missref for $draft");
       	           }

           }//End if ($new_state != $old_state){   
/*           else{
	   print "<tr><td>$draft : No state change</td>";
           } 
*/
	   print "</table>"; 

    }
  
}

/*****************************************************************************************/
/*run_check_on_other : Function is used to get the drafts  not in MISSREF, PUB, DNP and  */
/*                     WITHDRAWN from the index table.                                   */
/*                     It runs the check ref fields logic on each of the missref draft   */
/*                     to find 'Add/Edit draft' has impacted the missref drafts.         */
/*****************************************************************************************/

function run_check_on_other(){

    global $fields;
    global $debug_edit_lib;
   
   /*1. Get all the draft in MISSREF 1G*/

   $other_draft = get_other_draft();	


   if ($debug_edit_lib === true) {
    	print("<h2>run_check_on_other</h2>");
    	print("<h4>Other draft </h4>");
        print_r ($other_draft);

   }

   print "<table><tr><th>The following remaining drafts changed state</th></tr>";
   print "<tr><td></td></tr><tr><td></td></tr></table>";

   /*2 Run the check_ref_field on every other draft and use output to set its state */
   for ($row = 0; $row < count($other_draft) ; $row++){
         for ($col = 0; $col < 6; $col++)
         {
           $internal_key = $other_draft[$row][0];
           $draft= $other_draft[$row][1];
           $state_id = $other_draft[$row][5];
           $ref = $other_draft[$row][2];
           $ref_flag = $other_draft[$row][3];
           $iana_flag = $other_draft[$row][4];
           $generation_number = $other_draft[$row][6];

         }

           $old_state_name = get_state_name($state_id);      
           if ($iana_flag == '1'){
               $old_iana = '*A';
           }else{
               $old_iana = "";
	   } 
            
           if ($ref_flag == '1'){
               $old_ref = '*R';
           }else {
               $old_ref = ""; 
           }
	   if ($generation_number > '0'){
               $old_generation = "(".$generation_number."G)";
           }else {
               $old_generation = ""; 
	   }

           $old_state = "$old_state_name$old_iana$old_ref$old_generation";

           list($out_state_id,$out_generation_number, $out_iana_flag, $out_ref_flag) = check_ref_field($draft,$state_id,$ref,$ref_flag,$iana_flag,$generation_number);
           $out_state_name = get_state_name($out_state_id);      
           if ($out_iana_flag == '1'){
               $out_iana = '*A';
           }else{
               $out_iana = "";
           } 
            
           if ($out_ref_flag == '1'){
               $out_ref = '*R';
           }else {
               $out_ref = "";
           }
	   if ($out_generation_number > '0'){
               $out_generation = "(".$out_generation_number."G)";
           }else {
               $out_generation = ""; 
	   }

           $new_state = "$out_state_name$out_iana$out_ref$out_generation";
          
           print "<table>";
           if ($new_state != $old_state){   
    	           print "<tr><td>$draft modified from state : $old_state -> $new_state</td></tr>";                                                            
  	           $version_number = substr($draft,-2); //Get the version number
          	   $status = insert_to_state_history($internal_key,$out_state_id,$out_iana_flag,$out_ref_flag,$out_generation_number,$version_number);
          	   if ($status){ //insert state data to state history table
   	           } else {
               	    rfc_fail("Insert failed on State History: run_check_on_other for $draft");
       	           }
                   $index_status = update_index($internal_key,$out_state_id,$out_iana_flag,$out_ref_flag,$out_generation_number);
          	   if ($index_status){ //insert state data to state history table 
   	           } else {
               	    rfc_fail("Update failed on Index: run_check_on_other for $draft");
       	           }

           }//End if ($new_state != $old_state){   
/*           else{
	   print "<tr><td>$draft : No state change</td>";
           } 
*/
	   print "</table>"; 

    }



}

/******************************************************************************************************/
/*This functions gets the information of the draft in the missref state based on the generation number*/
/* passed                                                                                             */
/******************************************************************************************************/
function get_generation_draft($generation_number){
   global $pdo;
   $generation_draft = array();      

   $query = "SELECT `internal_key` , `DRAFT` , `REF` , `ref_flag` , `iana_flag` , `state_id` , `generation_number`
       FROM `index`
       WHERE `generation_number` = :generation_number
       AND `state_id` = :state_id";
   try {
       $stmt = $pdo->prepare($query);
       $stmt->bindParam('generation_number',$generation_number);
       $stmt->bindValue('state_id',22);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : get_generation_draft", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

  while ( $line = $stmt->fetch(PDO::FETCH_ASSOC)){ 
       $internal_key = $line['internal_key'];
       $draft = $line['DRAFT'];
       $ref = $line['REF'];
       $ref_flag = $line['ref_flag'];
       $iana_flag = $line['iana_flag'];
       $state_id = $line['state_id'];
       $generation_number = $line['generation_number'];
       $tmp_gen_array = array($internal_key,$draft,$ref,$ref_flag,$iana_flag,$state_id,$generation_number);
       array_push($generation_draft,$tmp_gen_array); 
   }//End of while for $query
   
  return $generation_draft;
}
/******************************************************************************************************/
/*This functions gets the information of the draft in the missref state based on the generation number*/
/* passed                                                                                             */
/******************************************************************************************************/
function get_other_draft(){
   global $pdo;
   $other_draft = array();      

   $state_ids = [3,14,16,19,22];
   $in_state_id = "";
   foreach ($state_ids as $i => $item)
   {
       $key = ":state_id".$i;
       $in_state_id .= "$key,";
       $in_params[$key] = $item; // collecting values into key-value array
   }
   $in_state_id = rtrim($in_state_id,","); 

   $refs = ['','NULL','+'];
   $in_refs = "";
   foreach ($refs as $ref_i => $ref_item)
   {
       $ref_key = ":ref".$ref_i;
       $in_refs .= "$ref_key,";
       $in_ref_params[$ref_key] = $ref_item; // collecting values into key-value array
   }
   $in_refs = rtrim($in_refs,","); 

   $query = "SELECT `internal_key`,`DRAFT`, `REF`, `ref_flag`, `iana_flag`, `state_id`, `generation_number` from `index` where `state_id` NOT IN ($in_state_id) AND `ref` NOT IN ($in_refs)"; 
 
   try {
       $stmt = $pdo->prepare($query);
       $stmt->execute(array_merge($in_params,$in_ref_params)); // just merge two arrays
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : get_other_draft", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   while ( $line = $stmt->fetch(PDO::FETCH_ASSOC)){ 
       $internal_key = $line['internal_key'];
       $draft = $line['DRAFT'];
       $ref = $line['REF'];
       $ref_flag = $line['ref_flag'];
       $iana_flag = $line['iana_flag'];
       $state_id = $line['state_id'];
       $generation_number = $line['generation_number'];
       $tmp_gen_array = array($internal_key,$draft,$ref,$ref_flag,$iana_flag,$state_id,$generation_number);
       array_push($other_draft,$tmp_gen_array); 
   }//End of while for $query
   
  return $other_draft;
}


/******************************************************************************************************/
/* This functions inserts a row to state history table for the state change in the indes table.       */ 
/******************************************************************************************************/
function insert_to_state_history($internal_key,$state_id,$iana_flag,$ref_flag,$generation_number,$version_number) {

   global $debug_edit_lib;
   global $pdo;
   $in_date = date('Y-m-d');

   $insert_query = "INSERT INTO `state_history` (internal_dockey,state_id,in_date,iana_flag,ref_flag,version_number,generation_number) VALUES (:internal_key,:state_id,:in_date,:iana_flag,:ref_flag,:version_number,:generation_number) ";

   if ($debug_edit_lib === true) {
       print("<h2>insert_to_state_history</h2>");
       print("<h3>Query</h3>");
       print_r($insert_query);
   }

   try {
       $stmt = $pdo->prepare($insert_query);
       $stmt->bindParam('internal_key',$internal_key);
       $stmt->bindParam('state_id',$state_id);
       $stmt->bindParam('in_date',$in_date);
       $stmt->bindParam('iana_flag',$iana_flag);
       $stmt->bindParam('ref_flag',$ref_flag);
       $stmt->bindParam('version_number',$version_number);
       $stmt->bindParam('generation_number',$generation_number);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : insert_to_state_history", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   if ($num_of_rows > 0){
       $status = 1; 
   } else {
       $status = 0;    
   }		
 
   return $status;
}



/************************************************************************************************* 
# Add to the draft to the DB.  This is where references are checked and 
# reference and IANA tags added to the DB state.
*************************************************************************************************/
function add_draft() {
   global $debug_edit_lib;
   global $fields;
   global $field_mysqli_cols, $all_states, $list_split;
   global $pdo;

   print <<<END
<h2>Database Update</h2>
<p>
    Adding <span class="draftname">$fields[draft]</span> to the 
    RFC Editor database.
</p>
END;
    
  /*IANA flag adjustment*/
   if ($fields['iana'] == "yes"){
       $fields['iana'] = '1';		
   }
       else {
       $fields['iana'] = '0';
   }	

/*NOTE*/
/*Check referecnes of the newly added draft to get the it exact state              */
/*Insert this state to index table but do not store it in state history            */
/*This state might change during missref draft state adjustment logic              */
/*called through function state_adjust_logic. So final state is store              */
/*in state history table after the function call                                   */
/*   The last values passed tgo functions are flags which indicates that this draft*/
/*   has come from the database with 'N' and need to show reference chain with 'Y' */

/*NOTE*/
   list($out_state_id,$out_generation_number, $out_iana_flag, $out_ref_flag) = check_ref_field($fields['draft'],$fields['state_id'],$fields['ref'],$fields['ref_flag'],$fields['iana'],$fields['generation_number'],'Y','N'); /*Y N*/
  
   $fields['state_id'] = $out_state_id;
   $fields['iana'] = $out_iana_flag;
   $fields['ref_flag'] = $out_ref_flag;
   $fields['generation_number'] = $out_generation_number;

   foreach ($list_split as $key => $val) 
       $fields[$key] = preg_replace("/\s*\n\s*/", ', ',$fields[$key]);

   # Build the insertion SQL
   $template = "(";
   $values="(";
    
   foreach ($fields as $key => $value) {
       if ($key == 'cid') continue; // special handling case
           if ( $field_mysqli_cols[$key] && $value) {
	       if ($template == "(" ) $template .= $field_mysqli_cols[$key];
	       else $template .= "," . $field_mysqli_cols[$key];

	       if ($values == "(" ) 
		   $values .= ':' .$key;
	       else 
		$values .= ', :'.$key;
	}
   }
   $template .= ")";
   $values .=")";

   $insert = "insert into `index` $template values $values\n";
   if ($debug_edit_lib === true) {
         print("<!-- add_draft: insert=$insert -->\n");
   }

   # process clusters first so on error, can abort insert.
   require_once('cluster_support_lib.php');
   if ((array_key_exists('ref',$fields)) && (strlen(trim($fields['ref'])) > 0)) {
       if (! assign_cluster($pdo,$fields) ) {
           rfc_fail("Insert failed on cluster assignment processing: See error log.");
       }
   } else {
       if ( ! new_no_references_update($pdo,$fields) ) {
           rfc_fail("Insert failed on cluster update processing: See error log.");
       }
   }
   print("<h2>Cluster Assignment Processing</h2><p>Successful!</p>");

   try {
       $stmt = $pdo->prepare($insert);
       foreach ($fields as $key => $value) {
           if ($key == 'cid') continue; // special handling case
           if ( $field_mysqli_cols[$key] && $value) {
	       $stmt->bindValue($key,$value);
	   }
       }
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
              error_log("Error processing : add_draft", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
 
   if ($num_of_rows > 0) {
       $fields['internal_key'] = get_new_internal_key($fields['draft']);
       state_adjust_logic();
       $version_number = substr($fields['draft'],-2); //Get the version number	
       $status = insert_to_state_history($fields['internal_key'],$fields['state_id'],$fields['iana'],$fields['ref_flag'],$fields['generation_number'],$version_number);
       if ($status){ //insert state data to state history table 
       } else {
           rfc_fail("Insert failed on State History: Add draft");
       }
 
       if (set_editor_assignments($fields)) {

	   update_queue_html();    // format for HTML page
           update_queue_xml();     // format for XML page
	   $post_status = post_datatracker();
	   if ($post_status == 'OK'){
	       print "<h2>Posting queue2.xml to the Datatracker... Done.</h2>";
	   } else {
	       print "<h2>Posting queue2.xml to the Datatracker... Not Done.</h2>";
               print "$post_status";
	   }

           print '
<p>
    Draft <span class="draftname">' . htmlspecialchars($fields['draft']) . '</span> has been added to the 
    database.  <span class="warning">Do not reload this page.</span>
</p>';

           unset($fields['internal_key']); // remove to preserve RFC State message logic
           send_new_add_msg_form($fields);
       } else {
            rfc_fail("Insert failed on editor assignment");
       } 
   } else { 
      rfc_fail("Insert failed"); 
   }
}
# Retrieve the internal_key for a newly added document so the editor
# assignment code will work.
function get_new_internal_key($draft) {
   global $pdo;

   $query = "SELECT internal_key FROM `index` WHERE draft= :draft";
     
   try { 
       $stmt = $pdo->prepare($query);
       $stmt->bindParam('draft',$draft);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount(); 
   } catch (PDOException $pe){
              error_log("Error processing : get_new_internal_key", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   $count = $num_of_rows;
   if ($count == 1) {
       $row = $stmt->fetch(PDO::FETCH_ASSOC);
       $internal_key = $row['internal_key'];
   } else {
       $internal_key = -1;
       error_log("get_new_internal_key: database error");
   }
   return $internal_key;
}


    
# Add to the draft to the DB.  This is where references are checked and 
# reference and IANA tags added to the DB state.
function commit_edit_draft() {
   global $fields;
   global $field_mysqli_cols, $all_states, $list_split;
   global $default_editor;
   global $debug_edit_lib;
   global $pdo;

   #Generation number adjustment     
   if (!$fields['generation_number']){
       $fields['generation_number'] = 0;
   }

   if ($debug_edit_lib === true) {
       print("<h2>commit_edit_draft</h2>");
       print("<h3>\$fields</h3><pre>");
       print_r($fields);
       print("</pre>");
   }

   print <<<END
<p>
    Committing changes to <span class="draftname">$fields[draft]</span> to the 
    RFC Editor database.
</p>
END;
  
   /*IANA flag adjustment*/
   if ($fields['iana'] == "yes"){
       $fields['iana'] = '1';		
   } else {
       $fields['iana'] = '0';
   }	

   if (($fields['state_id'] != '14') && ($fields['state_id'] != '3') && ($fields['state_id'] != '19')){

/*NOTE*/
/*Check referecnes of the newly added draft to get the it exact state  */
/*Insert this state to index table but do not store it in state history*/
/*This state might change during missref draft state adjustment logic  */
/*called through function state_adjust_logic. So final state is store  */
/*in state history table after the function call                       */
/*NOTE*/

       list($out_state_id,$out_generation_number, $out_iana_flag, $out_ref_flag) = check_ref_field($fields['draft'],$fields['state_id'],$fields['ref'],$fields['ref_flag'],$fields['iana'],$fields['generation_number'],'Y','N'); /*Y N*/

       $fields['state_id'] = $out_state_id;
       $fields['iana'] = $out_iana_flag;
       $fields['ref_flag'] = $out_ref_flag;
       $fields['generation_number'] = $out_generation_number;

   } else{ /*MISSREF calculation supressed for PUB state*/
       $fields['generation_number'] = '0';  
       $fields['ref_flag'] = '0';
   }   
   
   foreach ($list_split as $key => $val) 
       $fields[$key] = preg_replace("/\s*\n\s*/", ', ',$fields[$key]);

   # check if editor assignments changed

   $old_assignments = get_editor_assignments($fields['internal_key']);
   $chg_pe = ($fields['primary_editor'] != $old_assignments['primary_editor']);
   $chg_ce = ($fields['copy_editor'] != $old_assignments['copy_editor']);
   $chg_re = ($fields['rfc_editor'] != $old_assignments['rfc_editor']);
   $chg_pube = ($fields['pub_editor'] != $old_assignments['pub_editor']); 
   
   if ($chg_pe || $chg_ce || $chg_re || $chg_pube) {

         set_editor_assignments($fields);
   }


    # Build the insertion SQL

   $old_fields = pull_from_db($fields[$fields['keyfield']], "raw", 
                               $fields['keyfield']);

   if ($debug_edit_lib === true) {
       print("<h3>Old Fields</h3><pre>");
       print_r($old_fields);
       print("</pre>");
   }

   $template = "";
# Interate over the old data checking to see if the the input record includes
# the same key. If a key is found in the input array, compare values and update
# as appropriate. If the key is not in the input, check the old value for a
# NULL. If not null, assume user is trying to remove the data and add the
# setting of the field to NULL. (POST drops elements that have no text in
# them so removing a value will cause the field to be missing from the input
# array.)
   foreach ($old_fields as $key => $o_value) {
       if ($key == 'cid') continue; // special case
           if ($debug_edit_lib === true) { print("checking \$key $key => \$o_value is $o_value<br>"); }
           if (array_key_exists($key, $fields)) { // data in input
               if ($debug_edit_lib === true) { print("&nbsp;&nbsp;found $key: new value is $fields[$key]<br>"); }
               if ($o_value != $fields[$key]) {
                   if ($key == 'ref') $chg_cid = true;
                   if ($template) { // data is different so add
                       $template .= ", " . $field_mysqli_cols[$key] . " = :".$key;
                   } else {
                       $template .= "set " . $field_mysqli_cols[$key] . " = :".$key; 
                   }
              }
           } else {    // data not in input array; check if it should be removed.
               if ($debug_edit_lib === true) {print("&nbsp;&nbsp;$key not in fields: checking for NULL value<br>"); }
               if ($o_value != null && strlen(trim($o_value)) > 0) {
                   if ($template) {
                       $template .= ", " . $field_mysqli_cols[$key] . " = :".$key;
                   } else {
                       $template .= "set " .  $field_mysqli_cols[$key] . " = :".$key;
                   }
               }

           }
   }

   #Get the old state from old fiels

   $old_state = "";
   $old_state = get_state_from_array($old_fields);


   if ($debug_edit_lib === true) {
         print <<<END
<p>template=$template</p>
<p>chg_cid=$chg_cid</p>
<p>Old State=$old_state</p>
END;
   }

   if ( $template ) {
       $update = "update `index` $template where " . 
           $field_mysqli_cols[$fields['keyfield']] . " = :".$field_mysqli_cols[$fields['keyfield']];
           if ($debug_edit_lib === true) {
              print("<p>SQL update string:<br />$update</p>");
           }

       if ($chg_cid == true) {
           require_once('cluster_support_lib.php');
           if (modify_cluster($pdo,$fields,$old_fields)) {
                   print<<<END
<h2>Cluster Update Data</h2>
<p>Successful!</p>
END;
           } else {
               rfc_fail("Cluster processing failed. Update aborted!");
           }
       }

       try {
           $stmt = $pdo->prepare($update);
           foreach ($old_fields as $key => $o_value) {
               if ($key == 'cid') continue; // special case
               if (array_key_exists($key, $fields)) { // data in input
                   if ($o_value != $fields[$key]) {
                       ($fields[$key] == null ? " NULL " : $fields[$key]);
	               $stmt->bindValue($key,$fields[$key]);
                  }
               } else {    // data not in input array; check if it should be removed.
                   if ($o_value != null && strlen(trim($o_value)) > 0) {
                       $null_value = NULL;
	               $stmt->bindValue($key,$null_value);
                   }
              }
           }
           if ($fields['keyfield'] == 'internal_key') {
	      $stmt->bindValue($field_mysqli_cols[$fields['keyfield']], $fields[$fields['keyfield']]);
           } else {
              $keyfield_value .= "\"" . $fields[$fields['keyfield']] . "\"";
	      $stmt->bindValue($field_mysqli_cols[$fields['keyfield']], $keyfield_value);
           }
           $stmt->execute();
           $num_of_rows = $stmt->rowCount();
       } catch (PDOException $pe){
           error_log("Error processing : commit_edit_draft", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
       }


       if ($num_of_rows > 0) {
	   if (($old_fields['state_id'] != '14') && ($old_fields['state_id'] != '3') && ($old_fields['state_id'] != '19')){
	       state_adjust_logic(); 
           }
           $new_state = "";
           $new_state = get_state_from_array($fields);
           if ($old_state != $new_state){//if state change
               $version_number = substr($fields['draft'],-2); //Get the version number	
	       $status = insert_to_state_history($fields['internal_key'],$fields['state_id'],$fields['iana'],$fields['ref_flag'],$fields['generation_number'],$version_number);
               if ($status){ //insert state data to state history table 
	       } else {
                   rfc_fail("Insert failed on State History: ");
               }
           }

           update_queue_html(); // format for HTML page
           update_queue_xml();  // format for XML page


	   $post_status = post_datatracker();

	   if ($post_status == 'OK'){
	       print "<h2>Posting queue2.xml to the Datatracker... Done.</h2>";
	   } else {
	       print "<h2>Posting queue2.xml to the Datatracker... Not Done.</h2>";
	       print "$post_status";
	   }

           print <<<END
<p>
    Draft <span class="draftname">$fields[draft]</span> has been committed 
    to the database.  <span class="warning">Do not reload this page.</span>
</p>
END;

           if (edit_needs_announcement($old_fields,$fields)) {

               send_state_msg_form($old_fields,$fields);

           }


       } else { 
           rfc_fail("Commit failed: ");
       }
   } else {
	print <<<END
<p>
    No changes to draft.  Nothing written to INDEX.
</p>
END;
   } 

   if ($chg_pe || $chg_ce || $chg_re || $chg_pube) {

         print<<<END
<p>
    Updated editor assignments.
</p>
END;
    }

}

function hack_overloaded_fields()
{
     global $fields;
# XXX: This is *super aggrivating.  Authors can have ", Ed." after their name, 
# so ","is not really a good delimeter.  This is a super hack:  Change the 
# commas that are part of the ", Ed." "token" into semi-colons, do the split 
# and restore the "token."
     $fields['authors'] = preg_replace('/, Ed\./', '; Ed.', $fields['authors']);
     $fields['organization'] = preg_replace('/, Inc\./', '; Inc.', $fields['organization']);
     $fields['organization'] = preg_replace('/, Ltd\./', '; Ltd.', $fields['organization']);
     $fields['organization'] = preg_replace('/, LLC/', '; LLC', $fields['organization']);

}

function unhack_overloaded_fields()
{
     global $fields;

     $fields['authors'] = preg_replace('/; Ed\./', ', Ed.', $fields['authors']);
     $fields['organization'] = preg_replace('/; Inc\./', ', Inc.', $fields['organization']);
     $fields['organization'] = preg_replace('/; Ltd\./', ', Ltd.', $fields['organization']);
     $fields['organization'] = preg_replace('/; LLC/', ', LLC', $fields['organization']);
}

/*
 * Return just the name of the stream for a document.
 */
function get_stream_name($internal_key) {
   global $debug_edit_lib;
   global $pdo;

   $query =
       "SELECT s.stream_name
        FROM `index` i, working_group w, stream_specific_parties s 
        WHERE w.wg_name = i.source 
        AND w.ssp_id = s.ssp_id 
        AND i.`internal_key`= :internal_key";


   try {  
       $stmt = $pdo->prepare($query);
       $stmt->bindParam('internal_key',$internal_key);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
         error_log("Error processing : get_stream_name", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   $row = $stmt->fetch(PDO::FETCH_ASSOC);

   if ($debug_edit_lib === true) {
       print("<h3>get_stream_name</h3>");
       print("&nbsp;query=");     var_dump($query);      print("<br />");
       print("&nbsp;stream name=");     var_dump($row['stream_name']);      print("<br />");
   }
   return $row['stream_name'];
}


/******************************************************************************************************/
/*Gets the state only without any flag value*/
/*This one is used for the list draft page*/
/******************************************************************************************************/

function get_state_only(){
   global $pdo;
   $state_id = array();
   $state_name = array();
   $state_table = array();
   $state_name_all = array();	
   
   try {  
       $query = "SELECT state_id , state_name FROM `states`";
       $stmt = $pdo->prepare($query);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
         error_log("Error processing : get_state_only", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   while ($row_state_id = $stmt->fetch(PDO::FETCH_ASSOC)){
       array_push($state_id,$row_state_id['state_id']);
       if ($state_id != '22'){
	   array_push($state_name_all,$row_state_id['state_name'] );    
	   array_push($state_name,$row_state_id['state_name'] );    
       } else {
	   array_push($state_name,$row_state_id['state_name'] );    
       }
       $tmp_array_row = array($row_state_id['state_id'],$row_state_id['state_name']);
       array_push($state_table,$tmp_array_row);
   }
   return array($state_id,$state_name_all,$state_name,$state_table);
}

/******************************************************************************************************/
/* Gets Dropdown for edit stae with edited state selected
/* It has the MISSREF state in the display but user can not select it for selection*/
/******************************************************************************************************/
function get_edited_state($name){
   global $fields;
   global $field_labels;
   global $debug_edit_lib;
   global $pdo;

   if ($debug_edit_lib === true) {
       print("<h3>get_edited_state</h3>");
       print("&nbsp;Field value=");     var_dump($fields[$name]);      print("<br />");
       print("&nbsp;Field name=");     var_dump($name);      print("<br />");
   }
   
   print <<<END
   <tr>
   <td><label for="$name">$field_labels[$name]:</label></td>
END;

   $dropdown = "<td><select name=\"$name\" id=\"$name\">";

   $query = "SELECT state_id , state_name FROM `states`";
   try {
       $stmt = $pdo->prepare($query);
       $stmt->bindParam('draft',$draft);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount(); 
   } catch (PDOException $pe){
       error_log("Error processing : get_edited_state", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
   
   while ($row_state = $stmt->fetch(PDO::FETCH_ASSOC)){
       if ($name == "state_id" && !(is_null($fields['state_id']))){
	   if ($fields[$name] == $row_state['state_id']){
	       if ($row_state['state_id'] == '22'){
                   $dropdown .= "\r\n<option value='{$row_state['state_id']}' disabled='true' selected>{$row_state['state_name']}</option>";
	       } else {  
	           $dropdown .= "\r\n<option value='{$row_state['state_id']}' selected>{$row_state['state_name']}</option>";
	         }	
	   } else {
	       $dropdown .= "\r\n<option value='{$row_state['state_id']}'>{$row_state['state_name']}</option>";
	   }	
       } else if  (is_null($fields['state_id'])){
           if ($row_state['state_id'] == '4'){
	       $dropdown .= "\r\n<option value='{$row_state['state_id']}' selected>{$row_state['state_name']}</option>";
	   } else {  
               if ($row_state['state_id'] != '22'){
                   $dropdown .= "\r\n<option value='{$row_state['state_id']}'>{$row_state['state_name']}</option>";
	       } else {
		   $dropdown .= "\r\n<option value='{$row_state['state_id']}' disabled='true'>{$row_state['state_name']}</option>";
               } 	
           }	
       }
   }
   $dropdown .= "\r\n</select></td>";
   echo "$dropdown";
   print "</tr>";
}


/******************************************************************************************************/
/* Gets Dropdown for file type with edited file type selected
/*                                                                                                    */
/******************************************************************************************************/
function get_edited_filetype($name){
	global $fields;
        global $field_labels;
        global $debug_edit_lib;
        global $file_types;
 
        if ($debug_edit_lib === true) {
	  print("<h3>get_edited_filetype</h3>");
    	  print("&nbsp;Field value=");     var_dump($fields[$name]);      print("<br />");
          print("&nbsp;Field name=");     var_dump($name);      print("<br />");

        }

        print <<<END
        <tr>
        <td><label for="$name">$field_labels[$name]</label></td>
END;

        $dropdown = "<td><select name=\"$name\" id=\"$name\">";
        foreach ($file_types as $key=>$value){
              
             if ($name == "xml_file" && !(is_null($fields['xml_file']))){
             if ($fields[$name] == $key){/*To show the selected value*/

	            $dropdown .= "\r\n<option value='$key' selected>$value</option>";

                }else {
                    $dropdown .= "\r\n<option value='$key'>$value</option>";
                }

            } else if (is_null($fields['xml_file'])){
                        if ($key == 0){
			    $dropdown .= "\r\n<option value='$key' selected>$value</option>";
                         } else{
                            $dropdown .= "\r\n<option value='$key'>$value</option>";
                         }
           }
        }


        $dropdown .= "\r\n</select></td>";
	        echo "$dropdown"; 
	        print "</tr>";

     
}



/******************************************************************************************************/
/*Gets the state with all the fields that makes whole as a                                            */ 
/*STATE(state_id-name,iana_flag,ref_flag,generation)						      */
/*This function is no more in use								      */	
/******************************************************************************************************/
function get_state_from_array($record){
      global $debug_edit_lib;

      $state_id = $record['state_id'];
      $state_name = get_state_name($state_id);
      if ($record['iana']){
	  $iana = '*A';	
      }
      else {
	  $iana = '';
      }	
      if ($record['ref_flag']){
	  $ref_flag = '*R';	
      }
      else {
	  $ref_flag = '';
      }	
     
      if (!($record['generation_number'])){
  	  $generation_number = '';
      }else{
	      $generation_number = $record['generation_number'];
	      $generation_number = "(".$generation_number."G)";
       }

      $combine_state = "$state_name$iana$ref_flag$generation_number";
      
      if ($debug_edit_lib === true) {
	print("<h3>get_state_from_array</h3>");
	print("&nbsp;query=");     var_dump($query);      print("<br />");
	print("&nbsp;combine_state=");     var_dump($combine_state);      print("<br />");
      }
     
      return $combine_state;
}

/******************************************************************************************************/
/*Gets the state from the passed state id                                                             */ 
/******************************************************************************************************/
function get_state_name($state_id){
   global $pdo;

   $query = "SELECT state_name FROM `states` where state_id = :state_id";
   
   try {  
       $stmt = $pdo->prepare($query);
       $stmt->bindParam('state_id',$state_id);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
         error_log("Error processing : get_state_name", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   while ( $row_state_id = $stmt->fetch(PDO::FETCH_ASSOC)){
       $state_name = $row_state_id['state_name']; 
   }

   return $state_name;
}
/*****************************************************************************************************/
/*This function is to get the state_id for the passed statenames based on the states table           */
/*****************************************************************************************************/
function get_state_id($getfields){
   global $pdo;
   $array_count = count($getfields);
   if (is_array($getfields)){
       $array_count = count($getfields);
       if ($array_count== '1') {
         $str_getfields = $getfields[0];
       } 
   } else {
       $str_getfields = $getfields;
   }
   try {
       $query_selectlookup = "SELECT state_id FROM `states` where `STATE_NAME` = :state_name";
       $stmt = $pdo->prepare($query_selectlookup);
       $stmt->bindParam('state_name',$str_getfields);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : get_state_id", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
     while ($row_state_id = $stmt->fetch(PDO::FETCH_ASSOC)){
           $selected_state_id = $row_state_id['state_id'];
     }
   
     return $selected_state_id;
}

/************************************************************************************************/
/* This function gets the status of the input draft weather its PUB or IN-QUEUE or NOT-RECEIVED */
/************************************************************************************************/
function check_draft_status($in_draft) {

   global $debug_edit_lib;
   global $pdo;

   $draftstatus = array();
   $in_draft_00 = $in_draft."-00";
   $in_draft_99 = $in_draft."-99";

   $query = "select internal_key, DRAFT,state_id, ref, ref_flag, iana_flag ,generation_number from `index` 
       where draft >=:in_draft_00 and draft <= :in_draft_99 and
       state_id != :state_id_3 and state_id != :state_id_19 and
       state_id is not null";

   try {  
       $stmt = $pdo->prepare($query);
       $stmt->bindParam('in_draft_00',$in_draft_00);
       $stmt->bindParam('in_draft_99',$in_draft_99);
       $stmt->bindValue('state_id_3',3);
       $stmt->bindValue('state_id_19',19);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   
   } catch (PDOException $pe){
       error_log("Error processing : check_draft_status", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   while ( $line = $stmt->fetch(PDO::FETCH_NUM)){ 
       $internal_key = $line[0];
       $draft = $line[1];
       $state_id = $line[2];
       $ref = $line[3];
       $ref_flag = $line[4];
       $iana_flag = $line[5];
       $generation_number = $line[6];     
   }
 
   if ($debug_edit_lib === true) {
       print("<h2>check_draft_status</h2>");
       print("<h3>\$query</h3>");
       print_r($query);
       print("<h3>Output values</h3>");
       print("<h3>Internal Key : $internal_key</h3>");
       print("<h3>DRAFT : $draft</h3>");
       print("<h3>State ID : $state_id</h3>");
       print("<h3>Reference : $ref</h3>");
       print("<h3>Ref flag : $ref_flag</h3>");
       print("<h3>Iana flag : $iana_flag</h3>");
       print("<h3>Generation Number : $generation_number</h3>");
   }
     
   return array($internal_key, $draft, $state_id, $ref,$ref_flag,$iana_flag,$generation_number); 
}

/**************************************************************************************************/
/*Function : check_ref_field                                                                      */
/*           This function gets the ref as input for a draft and gets the status of each referecne*/
/*           Following cases are covered                                                          */
/*           1. Ref is empty o/p existing state                                                   */
/*           2. If all PUB, o/p state is same as i/p and clear ref flag                           */
/*           3. If any not in PUB set ref flag                                                    */
/*              3.1 If any NOT-RECEIVED o/p state= MISSREF1G exit                                 */
/*              3.2 If any MISSREF1G o/p state= MISSREF2G exit                                    */
/*              3.3 If any MISSREF2G o/p state= MISSREF3G exit                                    */
/*              Otherwise o/p = EDIT exit                                                         */
/*           The function assumes to keep existing IANA flag                                      */               
/*           $add_edit = Y/N decides if the function call is made from the ADD/EDIT draft or      */
/*                       MISSREF draft. Its initial value is 'N'                                  */ 
/*           $display_ref_chain = Y/N decides if the ref follwing chain needs to display or not   */
/*                                Its initial value is 'N'                                        */ 
/**************************************************************************************************/

function check_ref_field($in_draft,$in_state_id,$in_ref,$in_ref_flag,$in_iana_flag,$in_generation_number,$add_edit='N',$display_ref_chain='N')
 {
 
 global $debug_edit_lib;
 $draftstatus = array();
 $display_ref_chain;


 #1. 
 if ($in_ref){/*Ref following logic if ref is present*/

     #2.1 Get the individual reference from ref field
#     if (strpos($in_ref, '\n') !== false) {
     if	($add_edit == 'Y'){ /*If the call to check_Ref_field is made from add/edit draft*/
 	   $refs = explode("\n",$in_ref);
     }else {
  	   $refs = explode(",",$in_ref);
     }
     #2.2 Get the status of each reference in the array
     $PUB_count = 0;
     $NR_count = 0;
     $INQ_count = 0;
     $MISSREF1G_count = 0;
     $MISSREF2G_count = 0;
  
     foreach ($refs as $r) {
	    # Remove +type
            $r = trim($r," ");
	    $r = preg_replace("/\+.$/", "", $r);
	    # Remove trailing number
	    $r = preg_replace("/-\d\d$/", "", $r);
          #  list($r_internal_key, $r_state_id, $r_ref,$r_ref_flag,$r_iana_flag,$r_generation_number)= check_draft_status($r); 
            list($r_internal_key, $r_draft, $r_state_id, $r_ref,$r_ref_flag,$r_iana_flag,$r_generation_number)= check_draft_status($r); 

            if ($r_state_id == '14'){
                $draft_status = "PUBLISHED";
                $PUB_count++;
            }elseif ($r_state_id == ""){
                $draft_status = "NOT-RECEIVED";  
                $NR_count++;
	    }else {
                $draft_status = "IN-QUEUE";
		$INQ_count++;
                if (($r_state_id == '22')&&($r_generation_number == '1'))  {
                    $MISSREF1G_count++;    
		} 
                if (($r_state_id == '22')&&($r_generation_number == '2'))  {
                    $MISSREF2G_count++;    
		} 
             } 
            array_push($draftstatus,array($r,$draft_status,$r_internal_key, $r_state_id, $r_ref,$r_ref_flag,$r_iana_flag,$r_generation_number));  
     }//End of foreach ($refs as $r) {

     #2.3 Iterate through the array of reference status

     $count = count($draftstatus); 
     
     if ($debug_edit_lib === true) {
     print("<h2>check_ref_field</h2>");
     print "Draft ref array\:n";
     print_r($draftstatus);
     print "<h3>Array count $count</h3>";
     print "<h3>PUBcount $PUB_count</h3>";
     print "<h3>NRCount $NR_count</h3>";
     print "<h3>INQcount $INQ_count</h3>";
     print "<h3>MISSREF1Gcount $MISSREF1G_count</h3>";
     print "<h3>MISSREF2Gcount $MISSREF2G_count</h3>";
     }
     
     if ($count != $PUB_count){/*If few references are not published*/

         $in_ref_flag = '1'; #Set the ref flag
   
         if ($NR_count > 0){ /*If any Not received docs*/
             $in_state_id = '22';
             $in_generation_number = '1';
     
         }elseif ($NR_count == '0') {
             
             if ($MISSREF1G_count > 0){
                $in_state_id = '22';
                $in_generation_number = '2';

             }  elseif ($MISSREF1G_count == 0){

                      if ($MISSREF2G_count > 0){
                          $in_state_id = '22';
                          $in_generation_number = '3';
		      } elseif ($MISSREG2G_count == 0){
                          /*SPECIAL CASE : When function check_ref_field is called from drafts in MISSREF */ 
                          /*And on calculating the ref it turned out that the o/p has to be other than    */
                          /*MISSREF then only that draft goes to EDIT state. For all other cases draft    */
                          /*will have the same o/p as i/p*/  

	                  if (($in_state_id == '') OR ($in_state_id == '22')) {   
	                          $in_state_id = '4';
                          }

                          $in_generation_number = '0';
                      }
 
             } //elseif ($MISSREF1G == 0){
 
/*	     foreach ($draftstatus as $draftarr){
		 foreach ($draftarr as $k => $v){
	           print "KEy is $k value is $v \n";
		 } 	
     	     }
*/

         }// elseif ($NR_count == '0') {

     } elseif ($count == $PUB_count){/*If all the references are published*/

	$in_ref_flag = 0;
     }    
       
 
 }else/*Ref not present*/ {

    if ($in_state_id == '22'){
        $in_state_id = '4';
    }  
    $in_generation_number = '0';
    $in_ref_flag = '0';
    
    if ($display_ref_chain =='Y'){
    	print "<table><tr><th>Following Reference Chain...</th></tr>";   
    	print "<tr><td></td></tr><tr><td></td></tr></table>";
    	print "<table><tr><th colspan=\"2\">$in_draft has no references </th></tr>";
    	print "</table>";
    }
 }

 if ($in_ref) {
      if ($display_ref_chain =='Y'){ /*If the call to check_Ref_field is made from add/edit draft*/
	   print "<table><tr><th>Following Reference Chain...</th></tr>";   
	   print "<tr><td></td></tr><tr><td></td></tr></table>";
	   print "<table><tr><th colspan=\"2\">$in_draft Normative References: </th></tr>";
	   foreach ($draftstatus as $draftarr){
	 	foreach ($draftarr as $k => $v){
                  if ($k == '0') {
                      print "<tr><td>$v</td>";
                  }
                  if ($k == '1') {
                      print "<td>$v</td></tr>";
                  }

		} 	
     	   }
     }
 }/* END if ($in_ref) */

   print "</table>";

   if ($debug_edit_lib === true) {
	   print "<table><tr><th>The final output for : $in_draft</th></tr>";
	   print "<tr><td></td></tr><tr><td></td></tr></table>";
	   print "<table>";
	   print "<tr><td>State :$in_state_id</td><td>Generation Number : $in_generation_number</td><td>Ref flag : $in_ref_flag</td><td>IANA flag: $in_iana_flag</td>";
	   print "</table>"; 
   }   
  
   return array($in_state_id,$in_generation_number, $in_iana_flag, $in_ref_flag); 

}

/*****************************************************************************************************/
/*Function : update_index updates the index table with the modified state flags                      */
/*           This function is used during state adjustment logic. In the ADD/EDIT draft functionality*/
/*           if the add/edit draft changes it states as a effect of MISSREF state adjustment then    */
/*           this update is incorporated                                                             */ 
/*****************************************************************************************************/
function update_index($internal_key,$out_state_id,$out_iana_flag,$out_ref_flag,$out_generation_number){

   global $debug_edit_lib;
   global $pdo;

   if ($debug_edit_lib === true) {
    	print("<h2>update_index</h2>");
        print "Key for update: $internal_key";
        print "State id : $out_state_id";
        print "IANA : $out_iana_flag";
        print "Ref flag :$out_ref_flag";
        print "Generation: $out_generation_number";
   }
   
   #Flip the draft state from MISSREF to EDIT
   $query = "UPDATE `index` SET `state_id` = :out_state_id,`ref_flag` = :out_ref_flag,`iana_flag` = :out_iana_flag,`generation_number` = :out_generation_number WHERE `internal_key` = :internal_key";
       
   try {
       $stmt = $pdo->prepare($query);
       $stmt->bindValue('out_state_id',$out_state_id);
       $stmt->bindValue('out_ref_flag',$out_ref_flag);
       $stmt->bindValue('out_iana_flag',$out_iana_flag);
       $stmt->bindValue('out_generation_number',$out_generation_number);
       $stmt->bindValue('internal_key',$internal_key);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : update_index", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   if ($num_of_rows > 0) {
       $flip_status = 1;
   }else {
       $flip_status = 0;
       rfc_fail("Update failed for flipping state update_index: ");
   }	
   return $flip_status;

}


/************************************************************************/
/*Post Updates to Datatracker and gets the response of the call         */
/************************************************************************/


function post_datatracker(){

 $ch = curl_init();
 curl_setopt($ch, CURLOPT_URL, "https://datatracker.ietf.org/sync/rfceditor/notify/queue/");

 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 curl_setopt ($ch, CURLOPT_POST, true);

/* $data = array( 
	'username' => 'rfceditorsync',
	'password' => '');
 curl_setopt ($ch, CURLOPT_POSTFIELDS, $data);
*/
 curl_setopt ($ch, CURLOPT_POSTFIELDS, "username=rfcedsync&password=/wpMNi3QLUO9");

 $output = curl_exec ($ch);
 $info = curl_getinfo($ch);
 curl_close ($ch);

// print_r($info);
//print $output;

/*print "Output is $output";
if ($output == 'OK'){
	print "<h2>Datatracker Notification Established</h2>";
}else {
	print "<h2>Unsucessful Datatracker Notification</h2>";

}
*/
return $output;

}
?>
