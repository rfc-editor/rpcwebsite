<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
/**************************************************************************************************************/
/*Script Name : list_drafts.php                                                                               */
/*Description : The script is used to search the documents (RFCs, drafts) at the staff side. The search form  */
/*              has various options that can be used for selection criteria                                   */
/*June 2017    : Modified to add the file type as one of the search field -  PN                               */
/* November 2020 : Modified the script to use PDO prepared statements - PN                                 */
/**************************************************************************************************************/
# $Id: list_drafts.php,v 2.12 2020/11/11 01:03:17 priyanka Exp $
session_start();
include('header.php');
include('db_connect.php');
include('edit_lib.php');

$debug_list_drafts = FALSE;

#$where="";

# Fields on which the queries can be sorted
$sort_fields = array("none", "draft", "doc_id", "date_received", 
    "pub_date", "state");

#Search fields
$search_fields = array("draft", "doc_id", "authors", "title", "source");
$cluster_fields = array('cid');

# Search dates
$search_dates = array("date_received", "pub_date");

# An extra label to allow "None" to show up in the sort requests
$field_labels['none'] = "None";

$docid['csv'] = "
	case 
	    when 
		s.state_id = \"14\" and `DOC-ID` is not null and `DOC-ID` != \"\"
		and draft is not null and draft != \"\"
		    then CONCAT(`DOC-ID`, ' (', draft, ')')
	    when 
		s.state_id = \"14\" and `DOC-ID` is not null and `DOC-ID` != \"\"
		and (draft is null or draft = \"\") 
		    then `DOC-ID`
	    when 
		`DOC-ID` is not null and `DOC-ID` != \"\" 
			and `DOC-ID` != \"RFC\"
		    then CONCAT(draft, ' (', `DOC-ID`, ')')
	    else
		draft
	end \"draft/doc_id\"";

 
# ADD STATE ID TO SELECTION BOX


$docid['html'] = "
	case 
	    when 
		s.state_id = \"14\" and `DOC-ID` is not null and `DOC-ID` != \"\"
		and draft is not null and draft != \"\"
		    then CONCAT(`DOC-ID`, \" (\", draft, 
			') [<a href=\"$base/edit_draft.php?draft=', draft,
			'\">Edit</a>]')
	    when 
		s.state_id = \"14\" and `DOC-ID` is not null and `DOC-ID` != \"\"
		and (draft is null or draft = \"\") 
		    then CONCAT(`DOC-ID`, 
			' [<a href=\"$base/edit_draft.php?doc_id=',
			`DOC-ID`, '\">Edit</a>]')
	    when 
		`DOC-ID` is not null and `DOC-ID` != \"\"  
			and `DOC-ID` != \"RFC\"
		    then CONCAT(draft, ' (', `DOC-ID`, 
			') [<a href=\"$base/edit_draft.php?draft=',
			draft,'\">Edit</a>]')
	    else
		CONCAT(draft, ' [<a href=\"$base/edit_draft.php?draft=',
		    draft,'\">Edit</a>]')
	end \"draft/doc_id\"";

# Create the part of the form to allow search by editor
function editor_search() {
     global $fields;
     print '
<table>
  <tr>
    <th colspan="2">Search by Editor</th>
  </tr>
  <tr>
        <td><label for="copy_ed">CE:</label></td>
        <td><input type="text" size="2" name="copy_ed" value="' . htmlspecialchars($fields['copy_ed']) . '" id="copy_ed" /></td>
  </tr>
  <tr>
        <td><label for="prim_ed">PE:</label></td>
        <td><input type="text" size="2" name="prim_ed" value="' . htmlspecialchars($fields['prim_ed']) . '" id="prim_ed" /></td>
  </tr>
  <tr>
        <td><label for="rfc_ed">RE:</label></td>
        <td><input type="text" size="2" name="rfc_ed" value="' . htmlspecialchars($fields['rfc_ed']) . '" id="rfc_ed" /></td>
  </tr>
  <tr>
        <td><label for="pub_ed">PUB:</label></td>
        <td><input type="text" size="2" name="pub_ed" value="' . htmlspecialchars($fields['pub_ed']) . '" id="pub_ed" /></td>
  </tr>
</table>';
}

# Creates a selector with the states in it for selecting drafts by state. 
# The selector appears inside a table of class "search_states" for styling.  NB 
# the name[] form name so PHP can find the multiple selections.  The field
# value is pulled from the global $fields array.
function list_states_select($name,$inq="in_queue") {
  # global $fields, $all_states,$state_name;
   global $fields,$state_name_all;

    print "<table class=\"search_states\">\n";
    print "<tr><th>";
    if ( $fields[$inq] == "on" )
	print "<input type=\"radio\" name=\"in_queue\" value=\"on\" ". 
	    "checked />";
    else
	print "<input type=\"radio\" name=\"in_queue\" value=\"on\" />";
    print "<label for=\"in_queue_on\">In-queue</label>";
    print "</th></tr>\n";
    print "<tr><th>";
    if ( $fields[$inq] == "off" )
	print "<input type=\"radio\" name=\"in_queue\" value=\"off\" ". 
	    "checked />";
    else
	print "<input type=\"radio\" name=\"in_queue\" value=\"off\" />";
    print "<label for=\"$name\">Draft States</label></th></tr>\n";
    print "<tr><td><select size=\"13\" id=\"$name\" ". 
	"name=\"${name}[]\" multiple>\n";
/*Changed the constant array 'all_states' to dynamically array 'state_name' developed synamically
  from the database table 'states'*/
#    foreach ($all_states as $s)
    foreach ($state_name_all as $s)
	if ( $fields[$name] == $s ) 
	    print "<option selected value=\"$s\">$s</option>\n";
	else
	    print "<option value=\"$s\">$s</option>\n";
    print "</select></td></tr>\n";
#    print "</table>\n";
}


function list_flags_select($name1,$name2,$name3){
    global $fields, $yes_no_flag, $file_types;
    print "<tr><th>";
    print "<label for=\"$name1\">IANA Flag</label></th>\n";
    print "<td><select id=\"$name1\" name=\"$name1\"\n>";
    foreach ($yes_no_flag as $f){
	    if ($fields[$name1] == $f){
		print "<option selected value=\"$f\">$f</option>";
	    }	
	else{
	    print "<option value=\"$f\">$f</option>\n";
	    }	
    }	
    print "</select></td></tr>";
    print "<tr><th>";
    print "<label for=\"$name2\">REF Flag</label></th>\n";
    print "<td><select id=\"$name2\" name=\"$name2\"\n>";
    foreach ($yes_no_flag as $f){
	    if ($fields[$name2] == $f){
		print "<option selected value=\"$f\">$f</option>";
	    }	
	else{
	    print "<option value=\"$f\">$f</option>\n";
	    }	
    }	
    print "</select></td></tr>";
    print "<tr><th>";
    print "<label for=\"$name3\">File type</label></th>\n";
    print "<td><select id=\"$name3\" name=\"$name3\"\n>";
    foreach ($file_types as $k=>$v){
	    if ($fields[$name3] == $k){
		print "<option selected value=\"$k\">$v</option>";
	    }	
	else{
	    print "<option value=\"$k\">$v</option>\n";
	    }	
    }	
    print "</select></td></tr>";

    print "</table>\n";

}


# Create a 3 cell row in a table with a label, a field selector and a 
# sort-order selector.  The global $sort_fields is used for the fields to 
# populate the rows and $field_labels for the labels.  The field contents are 
# in the global $fields
function sort_field_select($n) {
    global $fields, $sort_fields, $field_labels;
    print "<td><label for=\"sort$n\">Sort $n:</label></td>\n";
    print "<td><select id=\"sort$n\" name=\"sort$n\">\n";

    foreach ($sort_fields as $s ) 
	if ($fields["sort$n"] == $s ) 
	    print "<option selected value=\"$s\">$field_labels[$s]</option>\n";
	else
	    print "<option value=\"$s\">$field_labels[$s]</option>\n";
    print "</select></td>\n";
    print "<td><select name=\"sortdir$n\">\n";
    print "<option " . 
	($fields["sortdir$n"] == "ASC" ? " selected " : "" ) . 
	"value=\"ASC\">Ascending</option>\n";
    print "<option " . 
	($fields["sortdir$n"] == "DESC" ? " selected " : "" ) . 
	"value=\"DESC\">Descending</option>\n";
    print "</select></td>\n";
}

# output the format selection table
function format_select() {
    global $fields;

    print "<table><tr><th colspan=\"2\">Output Format</th></tr>\n";
    print "<tr>\n";
    foreach (array("html", "csv" ) as $k) {
	print "<td>";
	if ( $k == $fields['format']) 
	    print "<input id=\"${k}_format\" type=\"radio\" name=\"format\" value=\"$k\" checked />";
	else 
	    print "<input id=\"${k}_format\" type=\"radio\" name=\"format\" value=\"$k\" />";
	print "<label for=\"${k}_format\">" . strtoupper($k) . "</label></td>";
    }
    print "</tr>\n</table>\n";
}

function table_date_input($field, $width=10) {
    global $fields;
    global $field_labels;
    global $field_errors;
    $start=$fields["${field}_start"];
    $end=$fields["${field}_end"];

    if ($field_errors["${field}_start"]) { $class="class=\"error\""; } else { $class = ""; }
    print '
    <tr>
	<td>
           <label ' . $class . ' for="' . htmlspecialchars($field) . '_start">' . htmlspecialchars($field_labels[$field]) . ':</label>
        </td>
	<td>
           <input type="text" size="' . htmlspecialchars($width) . '" name="' . htmlspecialchars($field) . '_start" value="' . htmlspecialchars($start) . '" id="' . htmlspecialchars($field) . '_start"/>
        </td>
	<td>
           <input type="text" size="' . htmlspecialchars($width) . '" name="' . htmlspecialchars($field) . '_end" value="' . htmlspecialchars($end) . '" id="' . htmlspecialchars($field) . '_end"/>
       </td>
    </tr>';
}

#verify that the given string is a valid MySQL date or empty
function check_date($date) {
    if ($date) return preg_match("/^\d\d\d\d-\d\d?-\d\d?/", $date);
    else return 1;
}
    

# Little routine to accumulate select conditions.  Takes the current selection 
# and either ands the new condition or prepends a where, depending on if the 
# current condition is empty.
function add_where($where, $cond) {
#     error_log("add_where: adding $cond to $where");
     if ($where ) return "$where AND $cond\n";
     else return "WHERE $cond";
}

# Another little routine to add sort criteria.  Similar to add_where above, but 
# for ORDER BY constraints.
function add_sort($sort, $field, $dir) {
    global $field_mysqli_cols;
    if ( $field != "none") {
	if ( $sort) return "$sort, ". $field_mysqli_cols[$field] . " $dir";
	else return "ORDER BY " . $field_mysqli_cols[$field] . " $dir";
    }
    else return $sort;
}

#This fundion binds values and parameters to build
#the final execution of the query.
function add_parameters($in_stmt,$in_fields){
   global $search_fields;
   global $cluster_fields;
   global $search_dates;
   global $editor_search;
   global $editor_fields;

   if ( $in_fields['in_queue'] == "off" )  {
       if ($in_fields['states']){
       #Get the state id from the state lookup 
           $count = count($in_fields['states']);
           #If more than one state is selected then need to get the state id string  
           if ($count > 1) {
               for ($i = 0; $i < $count; $i++) {
	           $sel_state_id = get_state_id($in_fields['states'][$i]);
                   $in_stmt->bindValue('state_id_'.$i,$sel_state_id);
               }                   
           } else {#If count is 1
               $state_id = get_state_id($in_fields['states']);
               $in_stmt->bindValue('state_id',$state_id);
           }
       }
   } else {
       $in_stmt->bindValue('state_id_14',14);
       $in_stmt->bindValue('state_id_3',3);
       $in_stmt->bindValue('state_id_19',19);
   } 

   #If IANA flag is set up
   if (($in_fields['iana_flag']) AND ($in_fields['iana_flag'] != " ")){
       if (($in_fields['iana_flag']) != "no"){  
           $in_stmt->bindValue('iana_flag_yes',1);
       } else {
           $in_stmt->bindValue('iana_flag_no',0);
       }
   }

   #If REF flag is set up
   if (($in_fields['ref_flag']) AND ($in_fields['ref_flag'] != " ")){
       if (($in_fields['ref_flag']) != "no"){
           $in_stmt->bindValue('ref_flag_yes',1);
       } else {
           $in_stmt->bindValue('ref_flag_no',0);
       }
   }

   #If file_type is set up
   if (($in_fields['xml_file']) AND ($in_fields['xml_file'] != " ")){
       $xml_file = $in_fields['xml_file'];
       $in_stmt->bindParam('xml_file_flag',$xml_file);
   }

   foreach ($search_fields as $f) {
       if ($in_fields[$f] ) {
       # use exact match for source field
           if ($f == "source") {
               $in_stmt->bindValue('fields_source',$in_fields[$f]);
           } else if ($f == "authors"){
               $author_value = "%".$in_fields[$f]."%";
               $in_stmt->bindValue('fields_authors',$author_value);
           } else if ($f == "title"){
               $title_value = "%".$in_fields[$f]."%";
               $in_stmt->bindValue('fields_title',$title_value);
           } else {
               $f_value = ".*(".$in_fields[$f].").*";
               $in_stmt->bindValue('fields_f',$f_value);
           }
       }
   }

   foreach ($cluster_fields as $f) {
       if ($in_fields[$f]) {
           switch ($f) {
               case 'cid':
                   $in_stmt->bindValue('fields_cid',$in_fields[$f]);
	           break;
               default:
                    $f_value = ".*(".$in_fields[$f].").*";
                    $in_stmt->bindValue('fields_f',$f_value);

	       break;
           }
       }
   }

   foreach ($search_dates as $f) {
       $start = $in_fields["${f}_start"];
       $end = $in_fields["${f}_end"];
       # If both fields are vaild or empty and at least one's not empty, add a 
       # condition.
       if ((check_date($start) && check_date($end)) && ($start || $end) ) {
           if ( $start && $end ) {
           # Both are valid - add a between selector
               $in_stmt->bindValue($f.'_start',$start);
               $in_stmt->bindValue($f.'_end',$end);
           } else {
       	   # One's valid, find it exactly
	       if ( $start )
                   $in_stmt->bindValue($f.'_start',$start);
               else
                   $in_stmt->bindValue($f.'_end',$end);
           }
       }
   }

   #  Check our parameters to editor values and add to select if present.
   foreach ($editor_search as $edtype) {
       if (array_key_exists($edtype, $in_fields)) {
           #$where = add_where($where,"{$editor_fields[$edtype]}= :".$edtype."_editor");
           $in_stmt->bindValue($edtype.'_editor',$in_fields[$edtype]);
       }
   }

   return $in_stmt;
}


if ($debug_list_drafts === TRUE) {
     print("<h2>\$_POST</h2>\n<pre>\n");
    var_dump($_POST);
     print("\n</pre>\n");
}

# Collect submitted fields.  Move them into the $fields array so we can reuse 
# functions from edit_lib, which expects that.
for ($i = 1;$i < 4; $i++) {
     if (array_key_exists("sort$i", $_POST)) $fields["sort$i"] = $_POST["sort$i"];
    else $fields["sort$i"] = "none";
     if ( array_key_exists("sortdir$i", $_POST)) $fields["sortdir$i"] = $_POST["sortdir$i"];
    else $fields["sortdir$i"] = "ASC";
}

foreach ($search_fields as $f)
    $fields[$f] = trim($_POST[$f]);

foreach ($cluster_fields as $f)
    $fields[$f] = trim($_POST[$f]);

foreach ($editor_search as $f) { 
     if (strlen($_POST[$f]) > 0) 
         $fields[$f] = trim($_POST[$f]); 
}

# States and the in_queue toggle are treated a little differently from the 
# text-based search fields, in form generation and in query generation so 
# 'states' is not in the $search_fields array.
$fields['states'] = $_POST['states'];
$fields['in_queue'] = $_POST['in_queue'] ? $_POST['in_queue'] : "on";
$fields['format'] = $_POST['format'] ? $_POST['format'] : "html";

$fields['iana_flag']= $_POST['iana_flag'];
$fields['ref_flag']= $_POST['ref_flag'];
$fields['xml_file']= $_POST['xml_file'];

foreach ($search_dates as $f) {
    $fields["${f}_start"] = 
	trim($_POST["${f}_start"] ? $_POST["${f}_start"] : "");
    $fields["${f}_end"] = 
	trim($_POST["${f}_end"] ? $_POST["${f}_end"] : "");

    if (!check_date($fields["${f}_start"]) || !check_date($fields["${f}_end"]) )
	$field_errors["${f}_start"] = 1;
}

if ($debug_list_drafts === TRUE) {
     print("<h2>\$fields</h2>\n<pre>\n");
     var_dump($fields);
     print("\n</pre>\n");
}

if ( $fields['format'] == 'html' ) {
   rfc_header("list drafts");
   list($state_id,$state_name_all,$state_name,$state_table) = get_state_only();

   if ($debug_list_drafts === TRUE) {
       print("<h2>\State Lookup table</h2>\n<pre>\n");
       print_r ($state_id);
       print_r ($state_name_all);
       print_r ($state_name);
       print_r ($state_table);
       print("\n</pre>\n");
   }

   print "<form action=\"" . htmlspecialchars($base) . "/list_drafts.php\" method=\"post\">\n";
   # The form for selecting/sorting
   print "<table class=\"search\" ><tr><td>";
   list_states_select("states");
   list_flags_select("iana_flag","ref_flag","xml_file");
   editor_search();
   print "</td><td><table><tr><td><table class=\"search_fields\">\n";
   print "<tr><th colspan=\"2\">Search by field</th></tr>\n";
   foreach ($search_fields as $f) 
       table_text_input($f, 20); 
   foreach ($cluster_fields as $f) 
       table_text_input($f, 20); 
   print "</table></td></tr>\n";
   print "<tr><td><table>\n";
   print "<tr><th colspan=\"3\">Search by Date</th></tr>\n";
   foreach ($search_dates as $f ) {
       table_date_input($f);
   }
    //    print "</td></tr></table>\n";
   print "</table>\n";
   print "<tr><td><table class=\"sort_fields\">\n";
   print "<tr><th colspan=\"3\">Sort order</th></tr>\n";
   for ($i = 1;$i < 4; $i++) {
       print "<tr>\n";
       sort_field_select($i);
       print "</tr>\n";
   }
   print "</table></td></tr>\n";
   print "<tr><td>";
   format_select();
   print "</td></tr></table></td></tr>";
   print "<tr><td colspan=\"3\"><input type=\"submit\" name=\"submit\"/></td></tr>\n";
   print "</table>\n";
   print "</form>\n";
}

# only run a query if a request has been made.
if ( array_key_exists('submit',$_POST) ) {
   $format = $fields['format'];    # Only 1 layer of dereference in the ""

   # Build up the query.
   if ( $fields['in_queue'] == "off" )  {
       if ($fields['states']){
           $where = 'WHERE ';
           #Get the state id from the state lookup 
           $count = count($fields['states']);
           #If more than one state is selected then need to get the state id string  
           if ($count > 1) {
               $where .= "(";
               for ($i = 0; $i < $count; $i++) {
                   if ($i != ($count -1)) {
                       $where .= " s.state_id = :state_id_".$i." OR";
                   } else {
                       $where .= " s.state_id = :state_id_".$i." )";
                   }
               }
           } else {#If count is 1
               $where .= "s.state_id = :state_id";
           }
       }
   } else {
       $where = "WHERE ";
       $where .= "s.state_id != :state_id_14 AND s.state_id != :state_id_3 AND s.state_id != :state_id_19";
   }


   #If IANA flag is set up
   if (($fields['iana_flag']) AND ($fields['iana_flag'] != " ")){
       if (($fields['iana_flag']) != "no"){
           $where = add_where($where,"i.iana_flag = :iana_flag_yes");
       } else {
           $where = add_where($where,"i.iana_flag = :iana_flag_no");
       }
   }

   #If REF flag is set up
   if (($fields['ref_flag']) AND ($fields['ref_flag'] != " ")){
       if (($fields['ref_flag']) != "no"){
           $where = add_where($where,"i.ref_flag = :ref_flag_yes");
       } else {
           $where = add_where($where,"i.ref_flag = :ref_flag_no");
       }
   }

   #If file_type is set up
   if (($fields['xml_file']) AND ($fields['xml_file'] != " ")){
       $xml_file = $fields['xml_file'];
       $where = add_where($where,"i.xml_file = :xml_file_flag");
   }

   foreach ($search_fields as $f) {
       if ($fields[$f] ) {
           # use exact match for source field
           if ($f == "source") {
               $where = add_where($where,"$field_mysqli_cols[$f] = :fields_source");
           } else if ($f == "authors"){
               $where = add_where($where,"$field_mysqli_cols[$f] like :fields_authors");
           } else if ($f == "title"){
               $where = add_where($where,"$field_mysqli_cols[$f] like :fields_title");
           } else {
               $where = add_where($where,"$field_mysqli_cols[$f] REGEXP :fields_f");
           }
       }
   }

   foreach ($cluster_fields as $f) {
       #         error_log("POST submit: cluster_fields $f");
       if ($fields[$f]) {
           switch ($f) {
               case 'cid':
                   $where = add_where($where,"$field_mysqli_cols[$f]= :fields_cid");
                   break;
               default:
                   $where = add_where($where,"$field_mysqli_cols[$f] REGEXP :fields_f");
                   break;
           }
       }
   }

   foreach ($search_dates as $f) {
       $start = $fields["${f}_start"];
       $end = $fields["${f}_end"];
       # If both fields are vaild or empty and at least one's not empty, add a 
       # condition.
       if ((check_date($start) && check_date($end)) && ($start || $end) ) {
           if ( $start && $end ) {
               # Both are valid - add a between selector
               $where = add_where($where,"$field_mysqli_cols[$f] between :".$f."_start and :".$f."_end");
           } else {
               # One's valid, find it exactly
               if ( $start )
                   $where = add_where($where, "$field_mysqli_cols[$f] = :".$f."_start");
               else
                   $where = add_where($where, "$field_mysqli_cols[$f] = :".$f."_end");
           }
       }
   }
 
   # Check our parameters to editor values and add to select if present.
   foreach ($editor_search as $edtype) {
       if (array_key_exists($edtype, $fields)) {
           $where = add_where($where,"{$editor_fields[$edtype]}= :".$edtype."_editor");
       }
   }        
  
   for ($i = 1; $i < 4 ; $i ++) 
       $sort = add_sort($sort, $fields["sort$i"], $fields["sortdir$i"]);
   # Base of the query.  This combines doc id and draft names for a 
   # comprehensible and compact output format.  We could skim off the bad ref 
   # fields, but I'm leaving them in so they all go away when I remove the bad 
   # fields from the DB.
   // To add editor entries, added join using primary_ed and copy_ed views of
   // editor_assignments table.

   $select_clause1 = "SELECT CONCAT(c.initials,'/',p.initials, '/',r.initials, '/',pub.initials) AS 'CE/PE/RE/PUB',$docid[$format],title,";
   $select_clause1 .= 'CONCAT( s.state_name,CASE WHEN i.iana_flag = "1"THEN "*A"ELSE ""END ,CASE WHEN i.ref_flag = "1"THEN "*R"ELSE ""END ) AS "STATE",
		cluster_id, date_received, `pub-date`, source, authors, email, ref,generation_number,`PAGE-COUNT`';
   $from_clause1 = "FROM `index` i LEFT JOIN clusters l ON (i.draft LIKE CONCAT(l.draft_base,'-__')), primary_ed p, copy_ed c , rfced r, pub_ed pub, `states` s";
   $query = "$select_clause1\n$from_clause1\n$where\nAND i.internal_key=p.doc_key AND i.internal_key=c.doc_key AND i.internal_key = r.doc_key AND i.internal_key = pub.doc_key AND i.state_id = s.state_id $sort";

   print "\n\n";

   if ($debug_list_drafts === TRUE) {
       error_log("list_drafts.php: SQL Query: $query");
       print(" <---SQL Query \n\n");
       print "$query";
       print("\n ----->\n");
   }

   global $pdo;
   try {
       $stmt = $pdo->prepare($query);
       $stmt = add_parameters($stmt,$fields);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : list_draft", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   # First get the total page count
   $total_page_count = 0;
   while ($page_line = $stmt->fetch(PDO::FETCH_NUM)) { 
                $total_page_count = $total_page_count + $page_line[12];
   }
   # Pull up the query and just implode it into a table.
    #if ( ($result = mysqli_query($db_link,$query))) {
   switch ($fields['format']) {
       case 'html':
	    
           print "<p>Found " . $num_of_rows.  " records ( ".$total_page_count. " pages total )</p>\n";
           print "<table>\n";
	   print "<tr>\n";
           print "<th>Ce/pe/re/pub</th>";
           print "<th>Draft/doc id</th>";
           print "<th>Page Count</th>";
           print "<th>Title</th>";
           print "<th>State</th>";
           print "<th>Cluster id</th>";
           print "<th>Date received</th>";
           print "<th>Pub date</th>";
           print "<th>Source</th>";
           print "<th>Authors</th>";
           print "<th>Email</th>";
           print "<th>Ref</th>";

	   # This walks the field names and creates table headings based 
	    # on them.
	   print "</tr>\n";
	   # Here's the actual drafts output, with classes for styling.
	   $i=0;
           try {
               $final_stmt = $pdo->prepare($query);
               $final_stmt = add_parameters($final_stmt,$fields);
               $final_stmt->execute();
               $final_num_of_rows = $final_stmt->rowCount();
           } catch (PDOException $pe){
               error_log("Error processing : final_list_draft", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
           }

           while ($line = $final_stmt->fetch(PDO::FETCH_NUM)) {
	   #Add the generation number to "CURRENT STATE" for display
	       if ($line[11] == "1"){
                   $line[3] = $line[3]."(1G)";
               } else if ($line[11] == "2"){
                   $line[3] = $line[3]."(2G)";
               } else if ($line[11] == "3"){
                   $line[3] = $line[3]."(3G)";
               }

	       $class = ($i++ % 2) ? "odd" : "even";
	       print "<tr class=\"$class\"><td>\n";
#		print implode("</td><td>", $line);
               print "$line[0]</td>";
	       print "<td>$line[1]</td>";
	       print "<td>$line[12]</td>";
               print "<td>$line[2]</td>";
	       print "<td>$line[3]</td>";
	       print "<td>$line[4]</td>";
	       print "<td>$line[5]</td>";
	       print "<td>$line[6]</td>";
	       print "<td>$line[7]</td>";
	       print "<td>$line[8]</td>";
	       print "<td>$line[9]</td>";
	       print "<td>$line[10]";
	       print "</td></tr>\n";
	   }
	   print "</table>\n";
	   break;
       case 'csv':
	   # This walks the field names and creates table headings based 
	   # on them.
	   header("Content-Type; text/csv");
	   header("Content-Disposition: attachment; filename=\"export.csv\"");
	   $first = 1;
	   #while ($field = mysqli_fetch_field($result)) {
	       print "\"PE\/CE\/RE\/PUB\",";
	       print "\"draft\/doc id\",";
	       print "\"Page count\",";
               print "\"title\",";
	       print "\"state\",";
	       print "\"cluster id\",";
	       print "\"date received\",";
	       print "\"pub date\",";
	       print "\"source\",";
	       print "\"authors\",";
	       print "\"email\",";
	       print "\"ref\"";
	   #}
	   print("\r\n");
           try {
               $final_stmt = $pdo->prepare($query);
               $final_stmt = add_parameters($final_stmt,$fields);
               $final_stmt->execute();
               $final_num_of_rows = $final_stmt->rowCount();
           } catch (PDOException $pe){
               error_log("Error processing : final_list_draft", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
           }
           while ($line = $final_stmt->fetch(PDO::FETCH_NUM)) { 
               $line = str_replace('"', '""', $line);
	       #Add the generation number to "CURRENT STATE" for display
	       if ($line[11] == "1"){
                   $line[3] = $line[3]."(1G)";
               } else if ($line[11] == "2"){
                   $line[3] = $line[3]."(2G)";
               } else if ($line[11] == "3"){
                   $line[3] = $line[3]."(3G)";
               }
               print '"'.$line[0].'"';
	       print '"'.$line[1].'"';
	       print '"'.$line[12].'"';
               print '"'.$line[2].'"';
	       print '"'.$line[3].'"';
	       print '"'.$line[4].'"';
	       print '"'.$line[5].'"';
	       print '"'.$line[6].'"';
	       print '"'.$line[7].'"';
	       print '"'.$line[8].'"';
	       print '"'.$line[9].'"';
	       print '"'.$line[10].'"';
	       print "\r\n";
                 
	   }
      }
   }


if ( $fields['format'] == 'html') {
    rfc_footer();
}
?>
