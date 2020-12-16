<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
/***************************************************************************************************************************************************************************/
/* script name : rfc_lib_new.php                                                                                                                                           */
/* April 2017 : Added the redirect link for Errata Id and RFC number - PN                                                                                                  */
/* June  2017 : Corrected the case where i/p RFC number has see-also number as a RFC Number - PN                                                                           */
/*              Original Query : $all_digits_query = "AND (i.`DOC-ID` like '%%%04d' OR i.`SEE-ALSO` like '%%%04d')";                                                       */
/*              Modified Query : $all_digits_query = "AND (i.`DOC-ID` like '%%%04d' OR i.`SEE-ALSO` = 'BCP%04d' OR i.`SEE-ALSO` = 'STD%04d' OR i.`SEE-ALSO` = 'FYI%04d')"; */
/* Suptember 2017 : Added special format handling case for RFC8187, RFC8264, RFC8265, RFC8266 as per Sandy G. request - PN                                                 */
/* Suptember 2017 : Removed special format handling case for RFC8187, RFC8264, RFC8265, RFC8266 as per Sandy G. request - PN                                               */
/* October 2017 : Added special format handling case for RFC8187, RFC8264, RFC8265, RFC8266 - PN                                                                           */
/* June 2018  : Added security feature to the script - PN                                                                                                                  */
/* May 2019 : Modified file formats part to handle the new Publication formats like XML, HTML, v3PDF, Text with ASCII - PN                                                 */
/* October 2019 : Modified link for v3PDF file format - PN                                                                                                                 */
/* February 2020 : Added the inline errata format(if available) to the format list - PN                                                                                    */
/* November 2020 : Modified the script to use PDO prepared statements - PN                                                                                                */
/***************************************************************************************************************************************************************************/
include_once('db_connect.php');
include('search_config.php');
#$debug_rsl = true;
$debug_rsl = false;

#SEARCH query

$base_query = "SELECT DISTINCT(`DOC-ID`), `SEE-ALSO`, 
	          `format`,concat( `DOC-ID`,\" \",`SEE-ALSO`,\" \",`format`) as  \"Number\", 
	          `TITLE`,`AUTHORS`,DATE_FORMAT(`PUB-DATE`,'%M %Y') as \"PUB-DATE\",`PUB-DATE` as \"ORDER-DATE\", 
	          CONCAT(`OBSOLETES`,`OBSOLETED-BY`,`UPDATES`,`UPDATED-BY`) as \"More_Info\",
		  `OBSOLETES`,`OBSOLETED-BY`,`UPDATES`,`UPDATED-BY`,`SOURCE`,
		  `status`,w.`ssp_id` , w.`wg_name`, a.`area_name`, i.`PUB-STATUS`,i.`abstract`, i.keywords, i.internal_key
             FROM `working_group` w, area a, `stream_specific_parties` s,
             `index` i left join area_assignments n on i.internal_key = n.fk_index
             WHERE i.`SOURCE` = w.`wg_name` 
	        AND w.`ssp_id` = s.`ssp_id`
	        AND   a.area_name = w.area_name
		AND  (a.area_name = w.area_name OR n.fk_area = a.area_id)
		AND (i.state_id = '14')
		AND   i.`DOC-ID` NOT LIKE 'IEN%'
		AND   i.`DOC-ID` LIKE 'RFC%'";


if (!function_exists("GetSQLValueString")) {
function GetSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "") 
{
   if (PHP_VERSION < 6) {
       $theValue = get_magic_quotes_gpc() ? stripslashes($theValue) : $theValue;
   }

   $theValue = function_exists("mysql_real_escape_string") ? mysql_real_escape_string($theValue) : mysql_escape_string($theValue);

   switch ($theType) {
    case "text":
      $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
      break;    
    case "long":
    case "int":
      $theValue = ($theValue != "") ? intval($theValue) : "NULL";
      break;
    case "double":
      $theValue = ($theValue != "") ? doubleval($theValue) : "NULL";
      break;
    case "date":
      $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
      break;
    case "defined":
      $theValue = ($theValue != "") ? $theDefinedValue : $theNotDefinedValue;
      break;
   }
   return $theValue;
}
}

/***********************************************************************************************/
/*Function : create_adv_search_form*/
/*			 Creates the advanced search form */
/***********************************************************************************************/

function create_adv_search_form($criteria = array()){
     global $debug_rsl;
     global $base;
     if ($debug_rsl === true) {
         print "create_adv_search_form\n";	
         var_dump($criteria);
     }
/*    <!-- Begin .container -->  */

     print '<div class="container">';

/*    <!-- Begin .header -->  */
     print '<div class="header">';
/*    <!-- Begin .headerimgwrap headerimg blog_title -->  */
     print '<div class="headerimgwrap"><div class="headerimg"><span class="blog_title"><a href="/">RFC Editor</a></span></div></div>';
/*    <!-- end .headerimgwrap headerimg blog_title -->  */

/*    <!-- Begin .help_box  -->  */
     print '<div class="help_box"> <div class="help_link"><a href="/about/search/">About this page</a></div></div>';
/*    <!-- end.help_box  -->  */

     print '</div>';

/*    <!-- end .header -->  */
/*    <!-- Begin .content -->  */       
     print '<div class="content">';
/*    <!-- Begin .searchform -->  */
     print '<div class="searchform">';
     print'<form id="query" name="query" action="rfc_search_detail.php" method="GET" onsubmit="return validateRFCSearchForm(this);" >';
/*    <!-- Begin .formtable -->  */
     print '<table border ="0" class="formtable" width="52%">';
     print '<tr>';
     print '<td class="boldtext"><abbr>RFC</abbr> Number (or Subseries Number):</td>';									
     print '<td><input type="text" id="rfc" name="rfc" size="8" maxlength="8"';
     if (array_key_exists('rfc', $criteria)) {
	 print(" value=\"{$criteria['rfc']}\"");
     }
     print '/></td>';									
     print '</tr>';	
     print '<tr>';
     print '<td class="boldtext">Title/Keyword:&nbsp;&nbsp;&nbsp;&nbsp;</td>';									
     print '<td>';
     print '<input name="title" type="text" id="title" size="40" maxlength="40" class="mytext"';
     if (array_key_exists('title', $criteria)) {
	 $title_exist = $criteria['title'];	
         print ' value= "' .htmlspecialchars($title_exist).'" ';
     }
     print '></td>';
     print '</tr>';
     print '<tr><td colspan="2">';
     add_abstract($criteria);
     add_keywords($criteria);
     print '</td></tr>';
     print '</table>';
/*<!--  Begin collapse div-->*/
     print '<label class="collapse" for="_1">Additional Criteria <img src="image/down.png" width="15" height="10"></label>';
     print '<input id="_1" type="checkbox">';
     print '<div>';
     print '<table width="75%" class="formtable" border="0">';
     add_status_checkboxes($criteria);
     add_publication_date($criteria);
     add_stream_names($criteria);
     add_area_acronyms_select($criteria);
     add_wg_acronym($criteria);		  		  
     add_authors($criteria);		  
     add_abstarctBox($criteria);			  
     print '</table>';
     print '</div>';
/*<!--  end collapse div-->*/
     print '<p align="center"><input value="Search" type="submit" />';
     print '<input name="Reset" type="button" value="Clear all" onclick="return clearAdvSearchForm(document.forms.query);"/>';
     print '</p>';
     print '</form>';
     print '</div>';
/*<!-- End searchform  -->	*/				
     if ($criteria){
	 rfc_adv_lookup($criteria);
     }
}
	//print '</div>';
/*<!-- End .content  -->	*/			

/*********************************************************************************/
/*Function : add_rfcis                                                           */
/*           This function gets the rfcid 						  	             */
/*********************************************************************************/
function add_rfcid($criteria) {
     print<<<END
	 <p>
         <label for="rfc"><abbr>RFC</abbr> Number:</label>
         <input type="text" id="rfc" name="rfc" size="4" maxlength="4"
		 </p>
END;
     if (array_key_exists('rfc', $criteria)) {
         print(" value=\"{$criteria['rfc']}\"");
     }
 }
/*********************************************************************************/
/*Function : add_authors                                                         */
/*           This function gets the author name 						  	     */
/*********************************************************************************/
function add_authors($criteria){
     global $debug_rsl;
     print<<<END
	 <tr><td class="boldtext">Author (surname):</td>
         <td><input name="author" type="text" id="author" size="10" maxlength="20" 
END;
     if ((array_key_exists('author', $criteria))) {
         print(" value=\"{$criteria['author']}\"");
     }
     print"/></td></tr>";	
}

/*********************************************************************************/
/*Function : add_stream_names                                                    */
/*           This function gets the stream name and shows them in selected       */
/*           dropdown                       							  	     */
/*********************************************************************************/

function add_stream_names($criteria){
     $stream_names = array('IETF','IRTF','IAB','Independent','Legacy');
/*	 <p>
	 <div class="menuheader">
       <label for = "stream_name" class="content"> Stream:</label>
	 </div><select name="stream_name" id="stream_name">

*/
     print<<<END
	 <tr><td class="boldtext"><label for = "stream_name" class="content"> Stream:</label></td>
         <td><select name="stream_name" id="stream_name">
END;
     if (!(array_key_exists('stream_name', $criteria))) {
         print("<option value=\"\" selected=\"selected\">Any</option>\n");
         foreach ($stream_names as $name) {
             print("            <option value=\"$name\">$name</option>\n");
         }
     }  else {
         print("<option value=\"\">Any</option>\n");
         foreach ($stream_names as $name) {
             if (strcasecmp($name, $criteria['stream_name']) == 0) {
                 print("            <option value=\"$name\" selected=\"selected\">$name</option>\n");
             } else {
                 print("            <option value=\"$name\">$name</option>\n");
             }
         }
     }

     print<<<END
         </select>
	 </td></tr>
END;
   }

/*********************************************************************************/
/*Function : add_area_acronyms_select*/
/*           This function gets the area ascrnym from Area table and shows the 	  */
/*			 area dropdown and selected area    							  	  */
/*********************************************************************************/
function add_area_acronyms_select($criteria){
     $area_acronyms = get_area_acronyms();
/*	   <p>
	   <div class="menuheader">
       <label for="area_acronym">Area:</label>
	   </div>
	   </p>
	   <p>
*/
     print<<<END
         <tr><td class="boldtext"><label for = "area_acronym" class="content"> Area:</label></td>
	 <td><select name="area_acronym" id="area_acronym">
END;

     if (!(array_key_exists('area_acronym',$criteria))) {
         print("<option value=\"\" selected=\"selected\">Any</option>\n");
         foreach ($area_acronyms as $acronym) {
             print("             <option value=\"$acronym\">$acronym</option>\n");
         }
     } else {
         print("<option value=\"\">Any</option>\n");
         foreach ($area_acronyms as $acronym) {
             if (strcasecmp($acronym, $criteria['area_acronym']) == 0) {
                 print("             <option value=\"$acronym\" selected=\"selected\">$acronym</option>\n");
             } else {
                 print("             <option value=\"$acronym\">$acronym</option>\n");
             }
         }
     }

     print("            </select>\n");
     print("</td></tr>");
}

/*********************************************************************************/
/*Function : get_area_acronyms*/
/*           This function gets the area acronym from Area table 				 */
/*********************************************************************************/
	
 function get_area_acronyms(){
   global $pdo;
     try { 
         $query =
             "SELECT area_acronym FROM area WHERE area_acronym IS NOT NULL
             AND area_status = :area_status
             ORDER BY area_acronym";

         $stmt = $pdo->prepare($query);
         $stmt->bindValue('area_status',open);
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
     } catch (PDOException $pe){
         error_log("Error processing : get_area_acronyms", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }

     $acronyms = array();
     while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
          $acronyms = array_merge($acronyms,$row);

     }
     return $acronyms;

}
	
/*********************************************************************************/
/*Function : add_wg_acronym*/
/*           This function adds the WG acronym to search form				 	  */
/*********************************************************************************/
function add_wg_acronym($criteria){
     print<<<END
	 <tr><td class="boldtext">WG Acronym:</td>
         <td><input id="wg_acronym" size="6" type="text" name="wg_acronym" maxlength="10"
END;
     if ((array_key_exists('wg_acronym',$criteria))) {
         print(" value=\"{$criteria['wg_acronym']}\"");
     }
     print("/></td></tr>");
}

/*********************************************************************************/
/*Function : add_status*/
/*           This function adds the status dropdown to search form 				 */
/*********************************************************************************/

function add_status($criteria){
     $statuses = array(' ', 'PROPOSED ', 'INFORMATIONAL', 'EXPERIMENTAL',
     'UNKNOWN', 'HISTORIC', 'STANDARD', 'DRAFT STANDARD',
     'BEST CURRENT PRACTICE');
	
     print<<<END
	 <p>
         <label for="pub_status">Publication Status:</label>
         <select name="pub_status" id="pub_status">
END;

     if (!(array_key_exists('pub_status',$criteria))) {
         print("<option value=\"\" selected=\"selected\">All/Any</option>\n");
         foreach ($statuses as $status) {
             print("             <option value=\"$status\">$status</option>\n");
         }
     } else {
         print("<option value=\"\">All/Any</option>\n");
         foreach ($statuses as $status) {
             if (strcasecmp($status, $criteria['pub_status']) == 0) {
                 print("             <option value=\"$astatus\" selected=\"selected\">$status</option>\n");
             } else {
                 print("             <option value=\"$status\">$status</option>\n");
             }
         }

     }
     print("            </select>\n");
     print("</p>");
}	
/*********************************************************************************/
/*Function : add_status_checkboxes												 */
/*           This function adds the Publication Status checkboxes based on value */
/*			 to search form                                                      */
/*********************************************************************************/
function add_status_checkboxes($criteria){
     $advn_statuses = array('Any','Standards Track','Best Current Practice','Informational','Experimental','Historic', 'Unknown');
     $std_trk_statuses = array('Any','Proposed Standard','Draft Standard','Internet Standard');	
	//Test
			//<tr><td><input name="pubstatus[]" type="checkbox" value="Any" id = "Any" checked onchange="disableElement();"/>  Any
			//</td></tr>
  /*  <label for="pubstatus">Status:</label><br><br><br><br><br><br>*/

     if (!(array_key_exists('pubstatus',$criteria))) {
         print<<<END
             <tr>
             <td class="boldtext">
             <label for="pubstatus">Status:</label><br><br><br><br><br><br>    
             </td> 
	     <td>      
	     <input name="pubstatus[]" type="checkbox" value="Any" id = "Any" checked  onchange="enableAny();"/>  Any
	    <br>
END;
   	 print'<input name="pubstatus[]" type="checkbox" value="Standards Track" id ="st" onchange="disableAny();"/> Standards Track&nbsp::&nbsp';
	 print'<select name="std_trk" id="std_trk">';
	 foreach ($std_trk_statuses as $std_trk_status) {
    	     print "<option value=\"$std_trk_status\">$std_trk_status</option>\n";
         }
         print"</select>\n";
	 print "<br>";
	
	 print<<<END
	     <input name="pubstatus[]" type="checkbox" value="Best Current Practice" id ="bct" onchange="disableAny();"/>  Best Current Practice
	     <br>       
	     <input name="pubstatus[]" type="checkbox" value="Informational" id ="il" onchange="disableAny();"/>  Informational
             <br>       
             <input name="pubstatus[]" type="checkbox" value="Experimental" id ="ex" onchange="disableAny();"/>  Experimental
             <br>       
	     <input name="pubstatus[]" type="checkbox" value="Historic" id ="hc" onchange="disableAny();"/>  Historic
	     <br>      
	     <input name="pubstatus[]" type="checkbox" value="Unknown" id ="un" onchange="disableAny();"/>  Unknown
END;
     } else {
	 $pubstatus = $criteria['pubstatus'];
	 $pub_count = count($pubstatus);
	 print<<<END
   	     <tr><td class="boldtext">
             <label for="pubstatus">Status:</label><br><br><br><br><br><br>
	     </td><td>
END;
	 $status_id = "";
	 foreach ($advn_statuses as $pbstatus){
	     $status_id = get_id($pbstatus);
	     if (in_array($pbstatus,$pubstatus)){
		 if ($pbstatus != 'Standards Track'){
		     if ($pbstatus == 'Any'){
			 $java_function = '"enableAny();"';	
		     } else {
			 $java_function = '"disableAny();"';	
		     }
                     print '<input name="pubstatus[]" type="checkbox" value="'.$pbstatus.'" checked id="'.$status_id.'" onchange='.$java_function.'/>'.$pbstatus.'<br>';
		 } elseif ($pbstatus == 'Standards Track') {
		     print '<input name="pubstatus[]" type="checkbox" value="'.$pbstatus.'" checked id="st" onchange="disableAny();"/>'.$pbstatus.'&nbsp:: &nbsp';
		     print'<select name="std_trk" id="std_trk">';
		     foreach ($std_trk_statuses as $std_trk_status) {
	                 if (strcasecmp($std_trk_status, $criteria['std_trk']) == 0) {
    		             print("             <option value=\"$std_trk_status\" selected=\"selected\">$std_trk_status</option>\n");
            	         } else {
			     print("             <option value=\"$std_trk_status\">$std_trk_status</option>\n");
               	         }
                     }
		     print"</select>\n";
		     print "<br>";
		 }
	     } else {
	         if ($pbstatus != 'Standards Track'){
		     if ($pbstatus == 'Any'){
		         $java_function = '"enableAny();"';	
		     } else {
			 $java_function = '"disableAny();"';	
		     }
		     print '<input name="pubstatus[]" type="checkbox" value="'.$pbstatus.'" id="'.$status_id.'" onchange='.$java_function.'/>'.$pbstatus.'<br>';					       } elseif ($pbstatus == 'Standards Track'){
		     print'<input name="pubstatus[]" type="checkbox" value="Standards Track" id= "st" onchange="disableAny();"/> Standards Track&nbsp::&nbsp';
		     print'<select name="std_trk" id="std_trk">';
		     foreach ($std_trk_statuses as $std_trk_status) {
		         print "<option value=\"$std_trk_status\">$std_trk_status</option>\n";
		     }
		     print"</select>\n";
		     print "<br>";							
  		 }
	     }
	 }/*End foreach $advn_statuses*/

     }	
        print '</td></tr>';
}
/*********************************************************************************/
/*Function : get_id()															 */
/*	         This function gets the id name for the fgiven status				 */
/*********************************************************************************/
function get_id($status){
     switch($status){
	 case "Any" :
	     $id = "Any";
	     break;
	 case "Best Current Practice":
	     $id = "bct";
	     break;
         case "Informational":
	     $id = "il";
	     break;
         case "Experimental":
	     $id = "ex";
	     break;								
         case "Historic" :
	     $id = "hc";
	     break;
         case "Unknown":
	     $id = "un";
	     break;
     }
     return $id;																
}	


/*********************************************************************************/
/*Function : add_authors                                                         */
/*           This function gets the author name 						  	     */
/*********************************************************************************/
function add_abstarctBox($criteria){
     global $debug_rsl;
     print<<<END
	 <tr><td class="boldtext">Abstract contains:</td>
	 <td>
         <input name="abstract_box" type="text" id="abstract_box" size="25" maxlength="40" 
END;
     if ((array_key_exists('abstract_box', $criteria))) {
         $abstract_box_exist = $criteria['abstract_box'];
#        print(" value=\"{$criteria['abstract_box']}\"");
         print ' value= "' .htmlspecialchars($abstract_box_exist).'" ';		  
     }
     print"/></td></tr>";	
}	
/*********************************************************************************/
/*Function : add_publication_date												 */
/*           This function adds the Publication From date and To date dropdowns  */
/*			 to search form                                                      */
/*********************************************************************************/
	
function add_publication_date($criteria){
     $months = array('January','February','March','April','May','June','July','August','September','October','November','December');
     $years = get_years();
     $pub_date_type = $criteria['pub_date_type'];
     //print<<<END
     print '<tr><td class="boldtext">';
     print '<label for="pub_date">Publication Date:</label>';
     print '</td><td>';
     print  '<select name="pub_date_type" id="pub_date_type" onchange="selectPubDateType();">';

//END; 
     if (!(array_key_exists('pub_date_type',$criteria))){
         print'<option value="any" selected >Any</option>';
   	 print'<option value="range">Range (inclusive)</option>';
	 print'<option value="this_month">This Month</option>';
	 print'<option value="this_year">This Year</option>';
     } else {
	 switch($pub_date_type){
	     case 'any':
		 print'<option value="any" selected >Any</option>';
		 print'<option value="range">Range (inclusive)</option>';
		 print'<option value="this_month">This Month</option>';
		 print'<option value="this_year">This Year</option>';
		 break;
	     case 'this_month':
		 print'<option value="any">Any</option>';
		 print'<option value="range">Range (inclusive)</option>';
		 print'<option value="this_month" selected>This Month</option>';
		 print'<option value="this_year">This Year</option>';
		 break;
	     case  'this_year':
		 print'<option value="any">Any</option>';
		 print'<option value="range">Range (inclusive)</option>';
		 print'<option value="this_month">This Month</option>';
		 print'<option value="this_year" selected>This Year</option>';
		 break;
	     case 'range':
		 print'<option value="any">Any</option>';
		 print'<option value="range" selected>Range (inclusive)</option>';
		 print'<option value="this_month">This Month</option>';
		 print'<option value="this_year">This Year</option>';
		 break;
	     default:
		 break;
	 }
     }
     print<<<END
	 </select></td></tr>			
         <tr><td class="boldtext"><label id="label_from">From</label></td>
	 <td>
         <select name="from_month" id="from_month">
END;
         if (!(array_key_exists('from_month',$criteria))) {
	     print("<option value=\"\">Month</option>\n");
             foreach ($months as $from_month) {
	         print("             <option value=\"$from_month\">$from_month</option>\n");
	     }
         } else {
	     print("<option value=\"\">Month</option>\n");
	     foreach ($months as $from_month) {
	       if (strcasecmp($from_month, $criteria['from_month']) == 0) {
		   print("             <option value=\"$from_month\" selected=\"selected\">$from_month</option>\n");
	       } else {
		   print("             <option value=\"$from_month\">$from_month</option>\n");
	       }
	     }
         }
	 print("            </select>\n");
	 print<<<END
	     <select name="from_year" id="from_year">
END;

         if (!(array_key_exists('from_year',$criteria))) {
             print("<option value=\"\">Year</option>\n");
             foreach ($years as $from_year) {
                 print("             <option value=\"$from_year\">$from_year</option>\n");
             }
         } else {
             print("<option value=\"\">Year</option>\n");
             foreach ($years as $from_year) {
                 if (strcasecmp($from_year, $criteria['from_year']) == 0) {
                     print("             <option value=\"$from_year\" selected=\"selected\">$from_year</option>\n");
                 } else {
                     print("             <option value=\"$from_year\">$from_year</option>\n");
                 }
             }
         }
	 
         print("            </select>\n");
 	 print("\n\n");
	 print<<<END
	 </td></tr>
	 <tr><td class="boldtext"><label id="label_to">To</label> 
         </td>
	 <td>
         <select name="to_month" id="to_month">

END;
         if (!(array_key_exists('to_month',$criteria))) {
             print("<option value=\"\">Month</option>\n");		 
             foreach ($months as $to_month) {
                 print("             <option value=\"$to_month\">$to_month</option>\n");
             }
         } else {
             print("<option value=\"\">Month</option>\n");
             foreach ($months as $to_month) {
                 if (strcasecmp($to_month, $criteria['to_month']) == 0) {
                     print("             <option value=\"$to_month\" selected=\"selected\">$to_month</option>\n");
                 } else {
                    print("             <option value=\"$to_month\">$to_month</option>\n");
                 }
             }
         }
	 
         print("            </select>\n");
	 print<<<END
	   <select name="to_year" id="to_year">
END;

         if (!(array_key_exists('to_year',$criteria))) {
   	     print("<option value=\"\">Year</option>\n");
             foreach ($years as $to_year) {
                 print("             <option value=\"$to_year\">$to_year</option>\n");
             }
         } else {
             print("<option value=\"\">Year</option>\n");
             foreach ($years as $to_year) {
                 if (strcasecmp($to_year, $criteria['to_year']) == 0) {
                     print("             <option value=\"$to_year\" selected=\"selected\">$to_year</option>\n");
                 } else {
                    print("             <option value=\"$to_year\">$to_year</option>\n");
                 }
             }
         }
	 
         print("            </select>\n");
	 //print ("<label for=\"pub_date_inc\">(Inclusive)</label>");
	 print("</td></tr>");
   }	


/*********************************************************************************/
/*Function : get_years*/
/*           This function gets the publication year values from index table	 */
/*********************************************************************************/
function get_years(){
     global $pdo;	
     
     try {
	 $query = "select DISTINCT(YEAR(`pub-date`)) from `index` where `pub-date` is not NULL and `pub-date` != :pub_date order by `pub-date`  asc";
         $stmt = $pdo->prepare($query);
         $stmt->bindValue('pub_date','0000-00-00');
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
     } catch (PDOException $pe){
         error_log("Error processing : get_yearss", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }

     $years = array();
     while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $years = array_merge($years,$row);
     }
     return $years;
}
/**********************************************************************************/
/*Function : add_abstract														  */
/*           This function adds the abstract to the form                          */
/**********************************************************************************/
/*function add_abstract($criteria){
	    
	print "<p><label for=\"abstract\">Show Abstract:</label>";

	if ((array_key_exists('abstract',$criteria))) {
	     foreach ($criteria as $key => $value) {
    	      switch ($key) {
				  case 'abstract': if ($value == "abson"){
									print '<input type=radio id="abson" name=abstract value="abson" checked>On';
							   	    print '<input type=radio id="absoff" name=abstract value="absoff">Off';	
				  				   }
									elseif ($value == "absoff")	{
										print '<input type=radio id="abson" name=abstract value="abson">On';
									    print '<input type=radio id="absoff" name=abstract value="absoff" checked>Off';	
						 		   }
			                   		break;
				  default:
				  			break;
			  
			  }
	 	}
	} else {
			print '<input type=radio id="abson" name=abstract value="abson">On';
		    print '<input type=radio id="absoff" name=abstract value="absoff" checked>Off';	
		}
    print("</p>");
    
}
*/
function add_abstract($criteria){
     if ((array_key_exists('abstract',$criteria))) {
	 foreach ($criteria as $key => $value) {
    	     switch ($key) {
	         case 'abstract': 
                     if ($value == "abson"){
		         print '<input name="abstract" type="checkbox" value="abson" checked="checked" id="abstract"/>Show Abstract';
		     }
	             break;
	         default:
		     break;
	     }
	 }
     } else {
	 print '<input name="abstract" type="checkbox" value="abson" id="abstract"/>Show Abstract&nbsp;&nbsp;';
     }
}

/**********************************************************************************/
/*Function : add_keyword														  */
/*           This function adds the abstract to the form                          */
/**********************************************************************************/
function add_keywords($criteria){
	    
/*	print "<p><label for=\"keywords\">Show keywords:</label>";

	if ((array_key_exists('keywords',$criteria))) {
	     foreach ($criteria as $key => $value) {
    	      switch ($key) {
				  case 'keywords': if ($value == "keyson"){
									print '<input type=radio id="keyson" name=keywords value="keyson" checked>On';
							   	    print '<input type=radio id="keysoff" name=keywords value="keysoff">Off';	
				  				   }
									elseif ($value == "keysoff")	{
										print '<input type=radio id="keyson" name=keywords value="keyson">On';
									    print '<input type=radio id="keysoff" name=keywords value="keysoff" checked>Off';	
						 		   }
			                   		break;
				  default:
				  			break;
			  
			  }
	 	}
	} else {
			print '<input type=radio id="keyson" name=keywords value="keyson">On';
		    print '<input type=radio id="keysoff" name=keywords value="keysoff" checked>Off';	
		}
    print("</p>");
    
}
*/	 
     if ((array_key_exists('keywords',$criteria))) {
	 foreach ($criteria as $key => $value) {
    	     switch ($key) {
		 case 'keywords': 
                     if ($value == "keyson"){
			 print '<input name="keywords" type="checkbox" value="keyson" checked="checked" id="keywords"/>Show Keywords';
		     }
	             break;
		 default:
		     break;
	     }
	 }
     } else {
	 print '<input name="keywords" type="checkbox" value="keyson" id="keywords"/>Show Keywords';
     }
    
}

/**********************************************************************************/
/*Function : rfc_adv_lookup														  */
/*           This function gets the query result and formats the output for search*/
/**********************************************************************************/
function rfc_adv_lookup($criteria){
     global $debug_rsl;
     global $pdo;

     list($final_query,$order_by) = concat_criteria($criteria);
     if ($debug_rsl){
         print "The final query is.". $final_query ."\n";
     }
	
     if (isset($_GET['sortkey'])){
         $sortkey = $_GET['sortkey'];
	 if ($sortkey == 'Number'){
	     $orderby = 'ORDER BY `DOC-ID`';
	 } elseif ($sortkey == 'Date'){
	     $orderby = 'ORDER BY `ORDER-DATE`';
	 }
     } else {
         $orderby = 'ORDER BY `DOC-ID`';
         $sortkey = 'Number';
     }
     if (isset($_GET['sorting'])){
	 $sorting = $_GET['sorting'];
     } else {
	 $sorting = 'ASC';
     }    

     switch($sorting){
         case "ASC":
             $sort = 'DESC';
	     $original_sort = 'ASC';
             break;
         case "DESC":
             $sort = 'ASC';
	     $original_sort = 'DESC';
             break;
         default:
             break;
     }

     $final_query .= "$orderby $original_sort";

     try {
         $stmt = $pdo->prepare($final_query);
         $stmt = add_parameters($stmt,$criteria);
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
     }catch (PDOException $pe){
       error_log("Error processing : rfc_adv_lookup", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }
     
     $return_search_count_total = $num_of_rows;
	
     if (isset($_GET["page"])) { $page  = $_GET["page"]; } else { $page=25;};
     if ($page != 'All'){
         if ($return_search_count_total > 25){
             $end_to = $page;
         }
     }else{
         $end_to = 'All';
     }
     if ($end_to != 'All'){
         if ($return_search_count_total > 25){
             $final_query .= " LIMIT  0, ". $end_to;
         }
         $show_bold = $end_to;
     }else {
         if ($page != 'All'){
             $show_bold = 25;
         } else {
             $show_bold = 'All';
         }
         $final_query = $final_query;
     }

     if ($debug_rsl){
         print "The final query modified is.". $final_query ."\n";
     }

     /*Now get the desired count for display onlt 25 counts per page*/
     try {
         $final_stmt = $pdo->prepare($final_query);
         $final_stmt = add_parameters($final_stmt,$criteria);
         $final_stmt->execute();
         $num_of_rows = $final_stmt->rowCount();
     }catch (PDOException $pe){
       error_log("Error processing : rfc_adv_lookup_2", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }
     $return_search_count = $num_of_rows;	 

     #Criteria array contains the user selected criteria this function call builds 
     #the Get string for the paging functionality using criteria array values
     $criteria_link = build_criteria_getlink($criteria);	
     $sorting_link = "sortkey=$sortkey&sorting=$original_sort";

     #The following logic builds the paging links showing first 25, 50, 100 and All options
     #The criteria link provides the user selected Get string from the search form
     print "<p>$return_search_count_total&nbsp;result"; 
     if ($return_search_count_total > 1)  print("s");
     if ($return_search_count_total > 25){
	 print "&nbsp;( Show";	
	 $i=25;
	 if (!($criteria_link)){
	     print "&nbsp;&nbsp;";
	     if ($show_bold == $i){
		 echo "<b>$i</b>";
	     } else {
		 echo "<a href='rfc_search_detail.php?page=".$i."&".$sorting_link."'>".$i."</a> ";
	     }
	 } else {
	     print "&nbsp;&nbsp;";
	     if ($show_bold == $i){
	         echo "<b>$i</b>";						
	     } else {
		 echo "<a href='rfc_search_detail.php?page=".$i."&".$criteria_link."&".$sorting_link."'>".$i."</a> ";
	     }
	 }
	 print "&nbsp;";
	 print "|";
	 print "&nbsp;";
	 if (!($criteria_link)){
	     if ($show_bold == 'All'){
		 echo "<b>All</b>";
	     } else {
		 echo "<a href='rfc_search_detail.php?page=".All."&".$sorting_link."'>".All."</a> ";
	     }
	 } else {
	     if ($show_bold == 'All'){
                 echo "<b>All</b>";
	     } else {
		 echo "<a href='rfc_search_detail.php?page=".All."&".$criteria_link."&".$sorting_link."'>".All."</a>";					
	     }
	 }
	 print "&nbsp;";
	 print ")</p>";					
	 } 

     #The following logic builds the reporting result in a tabular format
	 if ($return_search_count > 0){
/*<!---Begin .scrolltable-->*/
 	     print '<div class="scrolltable">';
/*<!---Begin .gridtable-->*/
 	     print "<table class='gridtable'>";	 
  	     print "<tr><th><a href='rfc_search_detail.php?sortkey=Number&sorting=".$sort."&page=".$page."&".$criteria_link."'>Number</a></th>";
	     print "<th>Files</th><th>Title</th><th>Authors</th>";
	     print "<th><a href='rfc_search_detail.php?sortkey=Date&sorting=".$sort."&page=".$page."&".$criteria_link."'>Date</a></th>";	 
	     print"<th>More Info</th><th>Status</th></tr>";

	     while ($line = $final_stmt->fetch(PDO::FETCH_ASSOC)) {
		 $format_anchor = "";
	         list($rfc_name,$rfc_number,$length) = split_rfc($line['DOC-ID']);
		 $formatted_rfc_number = sprintf('%s&nbsp;%d',$rfc_name,$rfc_number);
		 #Get the RFC number in proper format with link
		 $formatted_rfc_number = build_info_link($line['DOC-ID']);
		 #Get the File format link using format
		 $format_files_anchor = build_files_anchor($line['format'],$rfc_number);
		 print "<tr>";
		 if (((is_null($line['SEE-ALSO'])) || ($line['SEE-ALSO'] == ""))){
		     print "<td>".$formatted_rfc_number."</td>";
		 } else {
		     $formatted_see_also = build_see_also($line['SEE-ALSO']);
		     #Check if the see-also is a subseries number if yes then go for subseries handling logic
		     #Else print as it is
		     if(preg_match('/^(bcp|BCP|std|STD|fyi|FYI)/',$line['SEE-ALSO'])){
		         $subseries_status = subseries_logic_handling($line);
#			 print "<td>".$formatted_rfc_number."&nbsp;".$subseries_status."&nbsp".$formatted_see_also."</td>";					
			 print "<td>".$formatted_rfc_number." ".$subseries_status."&nbsp;".$formatted_see_also."</p></td>";					
		     } else {
                         /*Case where see-also is not a suseries number but its an rfc number         */
	                 if (preg_match('/^(rfc|RFC)/',$line['SEE-ALSO'])) { 
                             print "<td>".$formatted_rfc_number."</td>";		
                         }
                     }
		 }
		 #Get the inline errata inline errata 
		 $inline_verified_errata = get_inline_errata($rfc_number);
		 if ($inline_verified_errata){  
		     if ($format_files_anchor){
			 print "<td>".$format_files_anchor.", ".$inline_verified_errata."</td>";
		     } else {
			#print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
			 print "<td>$inline_verified_errata</td>";
		     }
		 } else {
		     if ($format_files_anchor){
			 print "<td>".$format_files_anchor."</td>";
		     } else {
			 print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
		     }
		 }
		 #If DOC is obsoleted then it has to be shown as grey
		 if ($line['TITLE']){
		     if ($line['OBSOLETED-BY']){
			 print "<td class=\"special\">".$line['TITLE']."</td>";
		     } else {
			 print "<td class=\"title\">".$line['TITLE']."</td>";
		     }
		 } else {
		     print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
		 }
		 if ($line['AUTHORS']){
		     print "<td>".$line['AUTHORS']."</td>";
		 } else {
		     print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
		 }
		 if ($line['PUB-DATE']){
		     $display_pub_date = handle_april_fool_date($line);
#						print"<td>".$line['PUB-DATE']."</td>";
		     print"<td>".$display_pub_date."</td>";
		 } else {
		     print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
		 }
					
		 #Get the more info data in proper format
		 $more_info = get_more_info($line);
		 if ($more_info){
		     print "<td>".$more_info."</td>";
		 } else {
		     print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
		 }
		 $formatted_status = get_formatted_status($line);
		 if ($formatted_status){
		     print "<td>".$formatted_status."</td>";
		 } else {
		     print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
		 }
		 if ($criteria['abstract'] == 'abson'){
		     print "</tr>";									
		     print '<tr><td class="title">ABSTRACT</td>';
#						print '<tr><td><img src="image/abstract-sm.jpg"></td>';
		     print '<td colspan="6">'.htmlspecialchars($line['abstract']);
		     print "</td>";
		 }
		 if ($criteria['keywords'] == 'keyson'){
		     print "</tr>";									
		     print '<tr><td class="title">KEYWORDS</td>';
#						print '<tr><td><img src="image/keywords-sm.jpg"></td>';
		     print '<td colspan="6">'.$line['keywords'];
		     print "</td>";
		 }
		 print "</tr>";									
	     }
	     print "</table></div>";
 /*<!--  </div end .scrolltable> */
 
	 }
	 
     }
/***********************************************************************************/
/*Function is used to bind the parameters/values to variables placeholders for the 
/*PDO prepare statement to perform final execution of the query depending upon the
/* input serach criteria of serach.
/***********************************************************************************/
function add_parameters($in_stmt,$in_criteria){
     global $debug_rsl;
	 
     if ($debug_rsl){
	 print_r($in_criteria);
     }

     foreach ($in_criteria as $key => $value) {
         switch ($key) {
	     case 'rfc':
                 $in_stmt = build_rfc_criteria($value,$in_stmt);
                 break;
             case 'title':
		 $in_stmt = build_title_criteria($value,$in_stmt); 
		 break;	    
             case 'author':
		 $value = strtolower($value);		  			 
 	         if (($value == "st. johns") OR ($value == "st.johns") OR ($value == "stjohns") OR($value == "st johns") or ($value == "stjohn") OR ($value == "st john") OR ($value == "st.john")){
/* 		     $adv_query .= "AND (i.`AUTHORS` like \"%St. Johns%\"  OR i. `AUTHORS` like \"%St.Johns%\" OR i. `AUTHORS` like \"%StJohns%\" OR i. `AUTHORS` like \"%St Johns%\" OR i. `AUTHORS` like \"%St. Johns%\" OR i. `AUTHORS` like \"%St.Johns%\" OR i. `AUTHORS` like \"%st johns%\" OR i. `AUTHORS` like \"%stjohns%\" OR i. `AUTHORS` like \"%st john%\" OR i. `AUTHORS` like \"%stjohn%\"  OR i. `AUTHORS` like \"%STJOHNS%\" OR i. `AUTHORS` like \"%ST JOHNS%\" OR i. `AUTHORS` like \"%ST. JOHNS%\" OR i. `AUTHORS` like \"%ST.JOHNS%\" OR i. `AUTHORS` like \"%ST.JOHN%\" OR i. `AUTHORS` like \"%ST. JOHN%\") \n";
*/		
		     $stjohns1 = '%St. Johns%';
		     $in_stmt->bindParam('stjohns1',$stjohns1);
		     $stjohns2 = '%St.Johns%';
		     $in_stmt->bindParam('stjohns2',$stjohns2);
		     $stjohns3 = '%StJohns%';
		     $in_stmt->bindParam('stjohns3',$stjohns3);
		     $stjohns4 = '%St Johns%';
		     $in_stmt->bindParam('stjohns4',$stjohns4);
		     $stjohns5 = '%St. John%';
		     $in_stmt->bindParam('stjohns5',$stjohns5);
		     $stjohns6 = '%St.John%';
		     $in_stmt->bindParam('stjohns6',$stjohns6);
		     $stjohns7 = '%st johns%';
		     $in_stmt->bindParam('stjohns7',$stjohns7);
		     $stjohns8 = '%stjohns%';
		     $in_stmt->bindParam('stjohns8',$stjohns8);
		     $stjohns9 = '%st john%';
		     $in_stmt->bindParam('stjohns9',$stjohns9);
		     $stjohns10 = '%stjohn%';
		     $in_stmt->bindParam('stjohns10',$stjohns10);
		     $stjohns11 = '%STJOHNS%';
		     $in_stmt->bindParam('stjohns11',$stjohns11);
		     $stjohns12 = '%ST JOHNS%';
		     $in_stmt->bindParam('stjohns12',$stjohns12);
		     $stjohns13 = '%ST. JOHNS%';
		     $in_stmt->bindParam('stjohns13',$stjohns13);
		     $stjohns14 = '%ST.JOHNS%';
		     $in_stmt->bindParam('stjohns14',$stjohns14);
		     $stjohns15 = '%ST.JOHN%';
		     $in_stmt->bindParam('stjohns15',$stjohns15);
		     $stjohns16 = '%ST. JOHN%';
		     $in_stmt->bindParam('stjohns16',$stjohns16);
                 } else {
	             $original_value = '%'.$value.'%';
		     $in_stmt->bindParam('original_value',$original_value);
		     $trim_value = str_replace(" ","",$value);
		     $trim_value = '%'.$trim_value.'$';
		     $in_stmt->bindParam('trim_value',$trim_value);
		 }
		 break;
             case 'stream_name':
		 if ($value == 'IETF'){
		     $in_stmt->bindValue('stream_name','IETF');
		     $in_stmt->bindValue('source','Legacy');
		 }else if ($value == 'IRTF'){  
		     $in_stmt->bindValue('ssp_id',4);
		 } elseif ($value == 'Legacy'|| $value == 'IAB' || $value == 'Independent') {
                     $source = $value;
		     $in_stmt->bindParam('source',$source);
		 }
                 break;	
             case 'area_acronym':			
	             $area_acronym = $value;
		     $in_stmt->bindParam('area_acronym',$area_acronym);
		 break; 
             case 'wg_acronym':
		 if (($value == 'none') || ($value == 'None') || ($value == 'NONE')){
		     $adv_query = $adv_query;
		 }else {
		     #$adv_query .= "AND w.`wg_acronym` = '$value'\n";	
	             $wg_acronym = $value;
		     $in_stmt->bindParam('wg_acronym',$wg_acronym);
		 }
	         break; 
             case 'pub_status':			
		 #$adv_query .= "AND i.`status` = '$value'\n";	
	         $status = $value;
		 $in_stmt->bindParam('status',$status);
		 break;
             case 'pubstatus':
		 $pub_val_count = count($value); 
		 $i = 0;
		 $pub_val_string = "";
		 $std_trk_string = "";
		 $any_status = false;
		 foreach ($value as $v){
		     if (($v != 'Standards Track') && ($v != 'Any')){
			 $i++;
                         $in_stmt->bindValue('pubstatus_'.$i,$v);         
		     }elseif ($v == 'Any'){/*If Publication status is selected as any then no need to select particular*/
			 $any_status = true;	
		     }elseif ($v == 'Standards Track'){
			 if ($in_criteria['std_trk'] == 'Any'){/*If standards track is selected as 'Any' */
		             $in_stmt->bindValue('proposed_standard','Proposed Standard');
		             $in_stmt->bindValue('draft_standard','Draft Standard');
		             $in_stmt->bindValue('internet_standard','Internet Standard');
			 }/* NO NEED elseif ($criteria['std_trk'] == 'Draft Standard (deprecated)'){
				 $std_trk_string = "'Draft Standard'";
				 }*/
			 else {
                             $std_trk  = $in_criteria['std_trk'];
		             $in_stmt->bindValue('std_trk',$std_trk);
			 }
		     } /*ELSE PART FOR STANDARD TRACK*/
		 }
                 break;	
             case 'pub_date_type':
		 $this_month_start = "";
		 $this_month_end = "";
		 $this_year_start = "";
		 $this_year_end = "";
		 $cur_year = date('Y');
		 $cur_month = date('m');
		 $num_of_days = cal_days_in_month(CAL_GREGORIAN, $cur_month, $cur_year); 
		 if ($value == 'this_month'){
		     $this_month_start = $cur_year."-".$cur_month."-00";
		     $this_month_end = $cur_year."-".$cur_month."-".$num_of_days;
		     #$adv_query .= "AND i.`PUB-DATE` >= '$this_month_start' AND i.`PUB-DATE` <= '$this_month_end'\n";
		     $in_stmt->bindParam('pub_date_type_start',$this_month_start);
		     $in_stmt->bindParam('pub_date_type_end',$this_month_end);
		 }elseif($value == 'this_year'){
		     $this_year_start = $cur_year."-01-00";
		     $this_year_end = $cur_year."-12-31";
		     #$adv_query .= "AND i.`PUB-DATE` >= '$this_year_start' AND i.`PUB-DATE` <= '$this_year_end'\n";								
                     $in_stmt->bindParam('pub_date_type_start',$this_year_start);
		     $in_stmt->bindParam('pub_date_type_end',$this_year_end);
                 }
                 break;		
	     case  'from_date':
                 $from_date = $value;
		 $in_stmt->bindParam('from_date',$from_date);
	         break; 		
	     case  'to_date':
                 $to_date = $value;
		 $in_stmt->bindParam('to_date',$to_date);
	         break;
 	     case  'abstract_box':
		 $in_stmt= build_abstract_box_criteria($value,$in_stmt); 
		 break;			
	     case 'source':
                 $source = $value;
		 $in_stmt->bindParam('source',$source);
		 break;
	     default:
                 break;
         }
     }
     

 return $in_stmt;
      
}

/***********************************************************************************/
/*Function is used to form the GET link from criteria in order to retrieve search  */
/*			criteria during paging												   */
/***********************************************************************************/
	
function build_criteria_getlink($in_criteria){
     global $debug_rsl;
     $out_get_link = "";	 

     foreach ($in_criteria as $key => $value) {
         switch ($key) {
	     case 'rfc':
	     case 'title':
	     case 'author':
  	     case 'stream_name':
             case 'area_acronym':
   	     case 'wg_acronym':
	     case 'abstract_box':
	     case 'abstract':
	     case 'keywords':
	     case 'std_trk':
	     case 'status':
		 if ($out_get_link == ""){
		     $out_get_link = "$key=$value";	
		 } else {
		     $out_get_link .= "&"."$key=$value";	
		 }
		 break;
             case 'pubstatus':
		 $pub_val_string = "";
		     foreach ($value as $v){
			 if ($pub_val_string == ""){
			     $pub_val_string = "pubstatus[]=$v";
			 } else {
			     $pub_val_string .= "&"."pubstatus[]=$v";
			 }
		     }
		     if ($out_get_link == ""){
			 $out_get_link = $pub_val_string;	
		     } else {
			  $out_get_link .= "&".$pub_val_string;	
		     }
		     break;
	     case 'pub_date_type':

		   /*if ($value != 'range'){
			if ($out_get_link == ""){
			$out_get_link = "$key=$value";	
			}
			else {
			$out_get_link .= "&"."$key=$value";	
			}
				
		}
		*/
		     if ($out_get_link == ""){
			 $out_get_link = "$key=$value";	
		     } else {
			 $out_get_link .= "&"."$key=$value";	
		     }
		     break; 						 
  	     case 'from_month':
		 if ($out_get_link == ""){
		     $out_get_link = "$key=$value";	
		 } else {
		     $out_get_link .= "&"."$key=$value";	
		 }
	         break;
             case 'from_year':
		 if ($out_get_link == ""){
		     $out_get_link = "$key=$value";	
		 } else {
		     $out_get_link .= "&"."$key=$value";	
		 }
	         break;
             case 'to_month':
		 if ($out_get_link == ""){
		     $out_get_link = "$key=$value";	
		 } else {
		     $out_get_link .= "&"."$key=$value";	
		 }
	         break;
             case 'to_year':
		 if ($out_get_link == ""){
		     $out_get_link = "$key=$value";	
		 } else {
		     $out_get_link .= "&"."$key=$value";	
		 }
	         break;

/*  			  case 'from_month':
			  					 $from_month_link = "$key=$value";
								  break;
  			  case 'from_year':
			  					 $from_year_link = "$key=$value";
								  break;
  			  case 'to_month':
  			  					 $to_month_link = "$key=$value";
								  break;
  			  case 'to_year':
   					  			 if ($out_get_link = ""){
						  			 $out_get_link = $from_month_link."&".$from_year_link."&".$to_month_link."&"."$key=$value";	
								 }
								  else {
								  $out_get_link .= "&".$from_month_link."&".$from_year_link."&".$to_month_link."&"."$key=$value";	
								  }
								  break;
	
	
	*/				  
	     default:
                 break;
	     }
	}

 	if ($debug_rsl){
             print "The crieria get link is". $out_get_link ."\n";
	}
	  
	return $out_get_link;
}
	
/***********************************************************************************/
/*Function Builds the link to info page with given number				           */
/***********************************************************************************/
function build_info_link($in_number){
	 global $debug_rsl;
	 global $document_root;

         $out_link_txt = "";
     $http_in_info_base_url = $document_root.'/info/';
     list($in_data_name,$in_data_number,$length) = split_rfc($in_number); 
	 $in_data_name_link = strtolower($in_data_name);
     $http_in_data_name = sprintf('%s&nbsp;%d',$in_data_name,$in_data_number);
	 $info_link = sprintf('%s%d',$in_data_name_link,$in_data_number);
	 $http_in_info_url = $http_in_info_base_url.$info_link;
	 $format_in_data  = '<a href="' .$http_in_info_url. '"target="_blank">';
	 $format_in_data .= "$http_in_data_name"."</a>";
	 $out_link_txt .= $format_in_data;	    
	 
	if ($debug_rsl){
		  print "The more info anchor is.". $out_link_txt ."\n";
	}
	 
	return $out_link_txt;		
	
	
	}
/***********************************************************************************/
/*Function Build see also data 												       */
/***********************************************************************************/
	
function build_see_also($see_also){
		global $debug_rsl;	
		$out_see_also = "";
		$in_see_array = array();
		if ($debug_rsl){
			print $see_also;
			}
	     $in_see_array = explode(",",$see_also);
		 foreach ($in_see_array as $key=>$value){
		  	$value = trim($value);
			$see_also_link = build_info_link($value);
			if ($out_see_also == ""){
				$out_see_also .= $see_also_link;
			}else {
				$out_see_also .= ", ".$see_also_link;
				}
		 }
		return $out_see_also; 		 
	}	
/***********************************************************************************/
/*Function Build anchor builds the correct format type anchor based on index.format*/
/***********************************************************************************/
function build_files_anchor($format,$rfcnumber){
	
		global $debug_rsl;	
	        global $document_root;


                $format_link = "";
		$format_ascii = "";
		$format_pdf = "";			
		$pdf_with_image = "";
                $format_pdf_with = "";
                $format_ps = "";
                $format_newtxt = "";
                $format_xml = "";
                $format_html = "";
                $format_v3pdf = "";
             	$anchor = "";
		
/*New changes*/	
		$rfctxt = sprintf("rfc%d",$rfcnumber); // URL is rfcX.txt

                $formats = explode(',',$format);
                $num_formats = count($formats);

		/*Hack for arranging the formats in the form ASCII, PDF, PS*/
                if (($num_formats == '3') && (trim($formats[1]) == 'PS')){
                    $formats= Array('ASCII','PDF','PS');
                }


                switch ($formats[0]) {
                   case '':
                           $url .= "<br />This file is not available online. Please contact the RFC Editor if you have a copy of this RFC.";
                           break;
                   default:
                         $url .= "<br />";

                   $count = 0;
                   foreach ($formats as $format) {
                      $trimed_format = trim($format);
                      if ($trimed_format == 'ASCII'){
                          $http_txt_url = $document_root. '/rfc/' . $rfctxt . '.txt';
		          $http_pdftxt_url = $document_root. '/pdfrfc/' . $rfctxt . '.txt.pdf';
			  $format_ascii  = '<a href="' . $http_txt_url . '"target="_blank">';
                          $format_ascii .= "ASCII"."</a>";
 	                  $format_pdf  = '<a href="' . $http_pdftxt_url . '"target="_blank">';
                          $format_pdf .= "PDF"."</a>";
                          if (($rfcnumber == '8187') OR ($rfcnumber == '8264') OR ($rfcnumber == '8265') OR ($rfcnumber == '8266')){
                	       $format_txt  = '<a href="' . $http_txt_url . '"target="_blank">';
                               $format_txt .= "TEXT"."</a>";
                               $anchor = $format_txt.", ".$format_pdf;
                          }else{
		              $anchor = $format_ascii.", ".$format_pdf;
                          }
                     }elseif ($trimed_format == 'PDF'){
               		  $http_pdf_url = $document_root. '/rfc/' . $rfctxt . '.pdf';
		          $format_pdf_with  = '<a href="' . $http_pdf_url . '"target="_blank">';
		          $format_pdf_with .= "PDF&nbsp;with&nbsp;Images"."</a>";
                          $anchor .= $format_pdf_with;	
                    }elseif ($trimed_format == 'PS'){
	                 $http_ps_url = $document_root. '/rfc/' . $rfctxt . '.ps';
		         $format_ps = '<a href="' . $http_ps_url . '"target="_blank">';
                         $format_ps .= "PS"."</a>";
                         $anchor .= $format_ps;
                    }elseif ($trimed_format == 'TEXT'){
                 	 $http_newtxt_url = $document_root.'/rfc/'.$rfctxt.'.txt';
                         $format_newtxt =  '<a href="' . $http_newtxt_url . '"target="_blank">';
                         $format_newtxt .= "TEXT"."</a>";
                         $anchor .= $format_newtxt;
                    }elseif ($trimed_format == 'XML'){ 
                         $http_xml_url = $document_root.'/rfc/'.$rfctxt.'.xml';
   		         $format_xml  = '<a href="' . $http_xml_url . '"target="_blank">';
		         $format_xml .= "XML"."</a>";
                         $anchor .= $format_xml;
                    }elseif ($trimed_format == 'HTML'){
		         $http_html_url = $document_root.'/rfc/'.$rfctxt.'.html';
	   	         $format_html  = '<a href="' . $http_html_url . '"target="_blank">';
		         $format_html .= "HTML"."</a>";
                         $anchor .= $format_html;
                    }elseif ($trimed_format == 'v3PDF'){
		 	 $http_v3pdf_url = $document_root.'/rfc/'.$rfctxt.'.pdf';
		         $format_v3pdf  = '<a href="' . $http_v3pdf_url . '"target="_blank">';
		         $format_v3pdf .= "PDF"."</a>";
                         $anchor .= $format_v3pdf; 
                    }

               //$url .= sprintf('<a href="%s">%s</a>',$furl,$format_labels[$trimed_format]);
               $count++;
               if ($count < $num_formats) {
                    $anchor .= ',     ';
               } else {
                    $anchor .= '';
               }
          }
          break;
     }
	
	 if ($debug_rsl){
		  print "The anchor is.". $anchor ."\n";
	 }
	 
	return $anchor;
}	

/***********************************************************************************************/
/*Function : get_inline_errata()                                                               */
/***********************************************************************************************/
function get_inline_errata($in_rfc_number){
     global $debug_rsl;
     global $pdo;
     global $document_root;
     $inline_errata_anchor = "";
     $http_inline_errata_url = "";

     $doc_id = sprintf('%s%04d','RFC',$in_rfc_number);
     
     try {
         $inline_errata_query = "select distinct(`doc-id`) from `errata` e where e.`status_id` = :state_id and e.`doc-id` = :doc_id";
         $stmt = $pdo->prepare($inline_errata_query);
         $stmt->bindValue('state_id',1);
         $stmt->bindParam('doc_id',$doc_id);
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
    } catch (PDOException $pe){
         error_log("Error processing : get_inline_errata", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
    }
		  
     #Record verified errata count
     $return_errata_count = $num_of_rows;	 
     if ( $return_errata_count > 0) {
          $rfc_number = sprintf('%s%d','rfc',$in_rfc_number);
          $http_inline_errata_url = $document_root. '/rfc/inline-errata/' . $rfc_number . '.html';
          $inline_errata_anchor  = '<a href="' . $http_inline_errata_url . '"target="_blank">';
          $inline_errata_anchor .= "HTML with inline errata"."</a>";
     }
    
     if ($debug_rsl){
          print "The inline errata anchor is.". $inline_errata_anchor ."\n";
     }
     return $inline_errata_anchor; 

}
function subseries_logic_handling($line){
     global $debug_rsl;
     global $pdo;	
     $subseries_status="";
     $in_see_array = array();
     if(preg_match('/^(bcp|BCP|std|STD|fyi|FYI)/',$line['SEE-ALSO'])){
	 #Check if this see also exists as an doc-id and get the SEE-ALSO fields for that doc-ID
	 #check_see_also_status()
	 $in_see_also = $line['SEE-ALSO'];
         try {
	     $see_also_query = "select `doc-id`, `see-also` from `index` i where i.`doc-id` = :in_see_also";
	     $stmt = $pdo->prepare($see_also_query);
	     $stmt->bindParam('in_see_also',$in_see_also);
	     $stmt->execute();
	     $num_of_rows = $stmt->rowCount();
         } catch (PDOException $pe){
	     error_log("Error processing : get_inline_errata", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
         }
         #Record count with limit value 
         $return_see_also_count = $num_of_rows;	 
         if ($return_see_also_count > 0){
	     #Now check the see-also field and see if the Original line[doc-id] present as one of the entry
	     while ($record = $stmt->fetch(PDO::FETCH_ASSOC)){
	         $see_also = $record['see-also'];			  
	         $doc_id = $record['doc-id'];
	     }
	     $in_see_array = explode(",",$see_also);
	     foreach ($in_see_array as $key=>$value){
	         $value = trim($value);
	         if ($value == $line['DOC-ID']){
		     $subseries_status = "part&nbsp;of";					 
	         }
	     }
         } else {
	     $subseries_status = "a.k.a.";					 
         }
     }
     if ($debug_rsl){
         print "The subseries status is.". $subseries_status ."\n";
     }
     return $subseries_status;	
}

/***********************************************************************************/
/*Function handle_april_fool_date handles the Display for April Fool Dat		   */
/***********************************************************************************/

function handle_april_fool_date($record){
     global $debug_rsl;	
	 $display_dt_txt = "";	
	
	
	 
	 if (preg_match('/^April/',$record['PUB-DATE'])){
		if (preg_match('/01$/',$record['ORDER-DATE'])){
		 	$display_dt_txt = "1&nbsp;".$record['PUB-DATE'];
		}else {
			 $display_dt_txt = $record['PUB-DATE'];
			}
	 } else {
		 $display_dt_txt = $record['PUB-DATE'];
		 }

	 return $display_dt_txt;
	}
	
/***********************************************************************************/
/*Function get_more_info builds the More Information based on OBSOLETE/ONSOLETEDBY  */
/*UPDATE/UPDATED BY*/
/***********************************************************************************/
function get_more_info($record){
     global $debug_rsl;	
	 $more_info_txt = "";

     $record['OBSOLETES'] = trim($record['OBSOLETES']);
	 if (($record['OBSOLETES'] != null) || ($record['OBSOLETES'] != "")){
		  
		  $more_info_txt .= "Obsoletes ";
		  $more_info_txt .= build_more_info_anchor($record['OBSOLETES']);
	 }
     $record['OBSOLETED-BY'] = trim($record['OBSOLETED-BY']);	 
     if (($record['OBSOLETED-BY'] != null) || ($record['OBSOLETED-BY'] != "")){
		 if ($more_info_txt != ""){
			 $more_info_txt .= ", Obsoleted by ";
 		     $more_info_txt .= build_more_info_anchor($record['OBSOLETED-BY']); 
		 }else {
			 $more_info_txt .= "Obsoleted by ";
 		     $more_info_txt .= build_more_info_anchor($record['OBSOLETED-BY']); 
			 
		 }
	  }
	  
     if (($record['UPDATES'] != null) || ($record['UPDATES'] != "")){
		 if ($more_info_txt != ""){
			 $more_info_txt .= ", Updates ";
			 $more_info_txt .= build_more_info_anchor($record['UPDATES']); 
		 }else {
		 	 $more_info_txt .= "Updates "; 
 			 $more_info_txt .= build_more_info_anchor($record['UPDATES']); 
		 }
	  }
	   
     if (($record['UPDATED-BY'] != null) || ($record['UPDATED-BY'] != "")){
		 if ($more_info_txt != ""){
			 $more_info_txt .= ", Updated by "; 
			 $more_info_txt .= build_more_info_anchor($record['UPDATED-BY']); 
		 }else {
		 	 $more_info_txt .= "Updated by "; 
 			 $more_info_txt .= build_more_info_anchor($record['UPDATED-BY']); 
		 }
	  }

      $errata_txt = get_errata_entry($record['DOC-ID']);      	  
	  if ($more_info_txt == ""){
		  $more_info_txt = $errata_txt;
		}else {
		  if ($errata_txt){	
			  $more_info_txt = $errata_txt.", ".$more_info_txt;
		   	}
		  }
	 
	 if ($debug_rsl){
		  print "The more info text is.". $more_info_txt ."\n";
	 }
	 
	  return $more_info_txt;
}

/***********************************************************************************/
/*Function Build anchor for more info data 
/***********************************************************************************/

function build_more_info_anchor($in_data){
	 global $debug_rsl;
         global $document_root;
	 $out_data_txt = "";
     $http_in_data_base_url = $document_root.'/info/';

     $in_data_array = explode(",",$in_data);
     $count = count($in_data_array);
     $i = 0;
	 foreach ($in_data_array as $key=>$value){
		  $value = trim($value);
		  $i++;
			  list($in_data_name,$in_data_number,$length) = split_rfc($value);
			  $in_data_name_link = strtolower($in_data_name);
			  $http_in_data_name = sprintf('%s&nbsp;%d',$in_data_name,$in_data_number);
			  $in_data_info_name = sprintf('%s%d',$in_data_name_link,$in_data_number);
			  if ($i == $count){
				 	$http_in_data_url = $http_in_data_base_url.$in_data_info_name;					
					$format_in_data  = '<a href="' .$http_in_data_url. '"target="_blank">';
					$format_in_data .= "$http_in_data_name"."</a>";
#					$out_data_txt .= $format_in_data;	    
				    if (preg_match('/^(rfc|RFC|bcp|BCP|fyi|FYI|std|STD)/',$value)){		
						$out_data_txt .= $format_in_data;	    
					}
					else {
						$out_data_txt .= $http_in_data_name;	    
					}
			  }else {
				 	$http_in_data_url = $http_in_data_base_url.$in_data_info_name;										
					$format_in_data  = '<a href="' . $http_in_data_url . '"target="_blank">';
					$format_in_data .= "$http_in_data_name"."</a>";
#					$out_data_txt .= $format_in_data.", ";	   
				    if (preg_match('/^(rfc|RFC|bcp|BCP|fyi|FYI|std|STD)/',$value)){		
						$out_data_txt .= $format_in_data.", ";	    
					}
					else {
						$out_data_txt .= $http_in_data_name.", ";	    
					}

			  }

    }	
	if ($debug_rsl){
		  print "The more info anchor is.". $out_data_txt ."\n";
	}
	 
	return $out_data_txt;		
	 
	}
	
/************************************************************************************/
/*Please check if the given RFC has a errata entry or not*/
/************************************************************************************/	
function get_errata_entry($doc_id){
     global $debug_rsl;
     global $document_root;
     global $pdo;
     $errata_txt = "";
     $out_name = "";
     $out_number = "";
	 
     $errata_http_base_url = $document_root.'/errata';
     list($out_name, $out_number, $length) = split_rfc($doc_id);
	 
     $out_name_link = strtolower($out_name);
     $out_number = ltrim($out_number,"0");	

     try { 
         $errata_query = "SELECT * from `errata` where `DOC-ID` = :doc_id";
         $stmt = $pdo->prepare($errata_query);
         $stmt->bindParam('doc_id',$doc_id);
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
     } catch (PDOException $pe){
         error_log("Error processing : get_errata_entry", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }
 
     $return_errata_count = $num_of_rows;
     #If the RFC has and errata entry
     if ($return_errata_count > 0){	
	 $errata_http_url = $errata_http_base_url."/"."$out_name_link$out_number";  
         $errata_txt = '<a class="boldtext" href="'. $errata_http_url .'"target="_blank">';
         $errata_txt.= "Errata</a>";		 
     }else{ 
	 $errata_txt = ""; 	 
     }
	 
     if ($debug_rsl){
         print "The errata txt is.". $errata_txt ."\n";
     }
     return $errata_txt;
}

/***********************************************************************************/
/*concat_criteria : Function builds the final query based on the criteria selection*/
/***********************************************************************************/	
function concat_criteria($criteria) {
     global $debug_rsl;
     global $base_query;
     $adv_query = "";
     $order_by_query = "";
     $default_order_by = "ORDER BY `DOC-ID` ASC";						 						 
	 
     if ($debug_rsl){
	 print_r($criteria);
     }
     foreach ($criteria as $key => $value) {
         switch ($key) {
	     case 'rfc':
		 list($rfc_criteria,$order_by_query) = build_rfc_criteria($value);
		 $adv_query = $rfc_criteria;
	         break;
	     case 'title':
		 list($title_criteria,$title_oder_by)=build_title_criteria($value); 
		 $adv_query = $title_criteria;
		 break;			 
	     case 'author':
		 $value = strtolower($value);		  			 
 	         if (($value == "st. johns") OR ($value == "st.johns") OR ($value == "stjohns") OR($value == "st johns") or ($value == "stjohn") OR ($value == "st john") OR ($value == "st.john")){
 
                  $adv_query .= "AND (i.`AUTHORS` like :stjohns1  OR i. `AUTHORS` like :stjohns2 OR i. `AUTHORS` like :stjohns3 OR i. `AUTHORS` like :stjohns4 OR i. `AUTHORS` like :stjohns5 OR i. `AUTHORS` like :stjohns6 OR i. `AUTHORS` like :stjohns7 OR i. `AUTHORS` like :stjohns8 OR i. `AUTHORS` like :stjohns9 OR i. `AUTHORS` like :stjohns10  OR i. `AUTHORS` like :stjohns11 OR i. `AUTHORS` like :stjohns12 OR i. `AUTHORS` like :stjohns13 OR i. `AUTHORS` like :stjohns14 OR i. `AUTHORS` like :stjohns15 OR i. `AUTHORS` like :stjohns16) \n";
                 } else {
		     $adv_query .= "AND (i.`AUTHORS` like :original_value  OR i. `AUTHORS` like :trim_value)\n";
		 }
		 break;			
	     case 'stream_name':
		 if ($value == 'IETF'){
		     $adv_query .= " AND s.`stream_name` = :stream_name AND i.`SOURCE` NOT IN (:source)\n"; 
		 }elseif ($value == 'IRTF'){  
		     $adv_query .= " AND w.ssp_id = :ssp_id \n ";
		 } elseif ($value == 'Legacy'|| $value == 'IAB' || $value == 'Independent') {
		     $adv_query .= " AND i.`SOURCE` = :source \n";
		 }
                 break;			
             case 'area_acronym':			
	         $adv_query .= "AND a.`area_acronym` = :area_acronym \n";	
		 break; 
             case 'wg_acronym':
		 if (($value == 'none') || ($value == 'None') || ($value == 'NONE')){
		     $adv_query = $adv_query;
		 }else {
		     $adv_query .= "AND w.`wg_acronym` = :wg_acronym\n";	
		 }
	         break; 
             case 'pub_status':			
		 $adv_query .= "AND i.`status` = :status \n";	
		 break; 
	     case 'pubstatus':
		 $pub_val_count = count($value); 
		 $i = 0;
		 $pub_val_string = "";
		 $std_trk_string = "";
		 $any_status = false;
		 foreach ($value as $v){
		     if (($v != 'Standards Track') && ($v != 'Any')){
			 $i++;
			 if ($i != $pub_val_count){
			     $pub_val_string .= " = :pubstatus_".$i." OR i.`status` ";
			 } else {
			     $pub_val_string .= "= :pubstatus_".$i;
			 }
		     }elseif ($v == 'Any'){/*If Publication status is selected as any then no need to select particular*/
			 $any_status = true;	
		     }elseif ($v == 'Standards Track'){
			 if ($criteria['std_trk'] == 'Any'){/*If standards track is selected as 'Any' */
			     $std_trk_string = "= :proposed_standard OR  i.`status` = :draft_standard OR i.`status` = :internet_standard";
			 }/* NO NEED elseif ($criteria['std_trk'] == 'Draft Standard (deprecated)'){
				 $std_trk_string = "'Draft Standard'";
				 }*/
			 else {
                             $std_trk_string = "= :std_trk";
			 }
		     } /*ELSE PART FOR STANDARD TRACK*/
		 }
		 if ($std_trk_string){
		     $pub_val_string = $pub_val_string.$std_trk_string;
		 }
		 if (!($any_status)){
		     $adv_query .= "AND ( i.`status`".$pub_val_string .")";	
		 }
                 break;		
             case 'pub_date_type':
                 if (($value == 'this_month') OR ($value == 'this_year')) {     
                    $adv_query .= "AND i.`PUB-DATE` >= :pub_date_type_start AND i.`PUB-DATE` <= :pub_date_type_end \n";
                 } 
                 break;		
	     case  'from_date':
  		 $adv_query .= "AND i.`PUB-DATE` >= :from_date \n";	
	         break; 		
	     case  'to_date':
	         $adv_query .= "AND i.`PUB-DATE` <= :to_date \n";	
	         break; 		
	     case  'abstract_box':
		 list($abstract_box_criteria, $abstract_box_order_by)= build_abstract_box_criteria($value); 
		 $adv_query .= $abstract_box_criteria;
		 break;			
	     case 'source':
		 $adv_query .= "AND i.`SOURCE` = :source \n";	
		 break;
	     default:
                 break;

         }
		  
     }

     $adv_query = $base_query." ". $adv_query;
     if ($order_by_query){
	 $order_by_query = $order_by_query;
     }else{
	 $order_by_query = $default_order_by;
     }
     return array($adv_query,$order_by_query);
}



/****************************************************************************************/
/*Function : build_rfc_criteria                                                         */
/*           This function builds the complex RFC criteria for DOC-ID field and SEE_ALSO*/
/*           based on the input entry in RFC/Subseries number                           */
/****************************************************************************************/
function build_rfc_criteria($value,$build_stmt=NULL){
     global $debug_rsl;
     $rfc_criteria = "";
     $order_by_criteria = "";
	 
     $rfc_flag = 0;
     $bcp_flag = 0;
     $std_flag = 0;
     $fyi_flag = 0;
 
	 #Define queries
     $rfc_query = "AND i.`DOC-ID` = :rfc_doc_id";
     $bcp_query = "AND ( i.`SEE-ALSO` = :bcp_see_also OR i.`DOC-ID` = :bcp_doc_id)";
     $std_query = "AND ( i.`SEE-ALSO` = :std_see_also OR i.`DOC-ID` = :std_doc_id)";
     $fyi_query = "AND ( i.`SEE-ALSO` = :fyi_see_also OR i.`DOC-ID` = :fyi_doc_id)";
     $all_digits_query = "AND (i.`DOC-ID` like :rfc_doc_id OR i.`SEE-ALSO` = :bcp_see_also OR i.`SEE-ALSO` = :std_see_also OR i.`SEE-ALSO` = :fyi_see_also)";
     $bcp_like_query = "AND ( i.`SEE-ALSO` like :bcp_see_also OR i.`DOC-ID` like :bcp_doc_id)";
     $std_like_query = "AND ( i.`SEE-ALSO` like :std_see_also OR i.`DOC-ID` like :std_doc_id)";
     $fyi_like_query = "AND ( i.`SEE-ALSO` like :fyi_see_also OR i.`DOC-ID` like :fyi_doc_id)";

     $default_order_by = "ORDER BY FIELD(`DOC-ID`,'RFC%') ASC";						 						 

     $length = strlen($value);
     if ($debug_rsl){
	  print "Value is $value and length $length\n";
     }
	 
     if (preg_match('/^(rfc|RFC)/',$value)){
	 $rfc_flag = 1;
     }elseif(preg_match('/^(bcp|BCP)/',$value)){
	 $bcp_flag = 1;
     }elseif(preg_match('/^(std|STD)/',$value)){
	 $std_flag = 1;
     }elseif (preg_match('/^(fyi|FYI)/',$value)){
	 $fyi_flag = 1;
     }
 
     if  (preg_match('/\s/',$value)){
	 $value = str_replace(" ","",$value) ;
     }
     $length = strlen($value);	
 

     if ($debug_rsl){
	 print "Value is $value and length $length\n";
	 print "Flag values RFC $rfc_flag BCP $bcp_flag FYI $fyi_flag STD $std_flag\n";
     } 
 
	 
     switch($length){
         case '7':
	 case '6':
	 case '5':
	     /*Use split rfc*/
	     list($out_name, $out_number, $length) = split_rfc($value);
	     if ($rfc_flag){
                 if ($build_stmt) {
                     $rfc_doc_id = sprintf('RFC%04d',$out_number);
                     $build_stmt->bindParam('rfc_doc_id',$rfc_doc_id);
                 }else {
	             $rfc_criteria = $rfc_query;
		     $order_by_criteria = "ORDER BY i.`SEE-ALSO` ASC, i.`DOC-ID` ASC";
                 }
	     } else if ($bcp_flag){
	         if ($build_stmt) {
                     $bcp_doc_id = sprintf('BCP%04d',$out_number);
                     $build_stmt->bindParam('bcp_see_also',$bcp_doc_id);
                     $build_stmt->bindParam('bcp_doc_id',$bcp_doc_id);
                 } else {
                     $rfc_criteria =  $bcp_query;
		     $order_by_criteria = "ORDER BY i.`SEE-ALSO` ASC, i.`DOC-ID` ASC";	
                 }
	     } else if ($std_flag){
	         if ($build_stmt) {
                     $std_doc_id = sprintf('STD%04d',$out_number);
                     $build_stmt->bindParam('std_see_also',$std_doc_id);
                     $build_stmt->bindParam('std_doc_id',$std_doc_id);
                 } else {
                     $rfc_criteria = $std_query;
		     $order_by_criteria = "ORDER BY i.`SEE-ALSO` ASC, i.`DOC-ID` ASC";
                 }				 
	     } else if($fyi_flag){
	         if ($build_stmt) {
                     $fyi_doc_id = sprintf('FYI%04d',$out_number);
                     $build_stmt->bindParam('fyi_see_also',$fyi_doc_id);
                     $build_stmt->bindParam('fyi_doc_id',$fyi_doc_id);
                 } else {
                     $rfc_criteria = $fyi_query;
		     $order_by_criteria = "ORDER BY i.`SEE-ALSO` ASC, i.`DOC-ID` ASC";
                 }				 
	     }
	     break;
         case '4':
	     if (preg_match('/^(rfc|RFC|bcp|BCP|fyi|FYI|std|STD)([0-9])$/',$value)){
		 list($out_name, $out_number, $length) = split_rfc($value);
		 if ($rfc_flag)	{
	             if ($build_stmt) {
                         $rfc_doc_id = sprintf('RFC%04d',$out_number);
                         $build_stmt->bindParam('rfc_doc_id',$rfc_doc_id);
                     } else {
                         $rfc_criteria = $rfc_query;
		         $order_by_criteria = "ORDER BY i.`SEE-ALSO` ASC, i.`DOC-ID` ASC";
                     }						 
		 }
		 else if ($bcp_flag){
		     if ($build_stmt) {
                         $bcp_doc_id = sprintf('BCP%04d',$out_number);
                         $build_stmt->bindParam('bcp_see_also',$bcp_doc_id);
                         $build_stmt->bindParam('bcp_doc_id',$bcp_doc_id);
                     } else {
		         $rfc_criteria = $bcp_query;
	                 $order_by_criteria = "ORDER BY i.`SEE-ALSO` ASC, i.`DOC-ID` ASC";							 
                     }
                 }else if ($std_flag){
	             if ($build_stmt) {
                         $std_doc_id = sprintf('STD%04d',$out_number);
                         $build_stmt->bindParam('std_see_also',$std_doc_id);
                         $build_stmt->bindParam('std_doc_id',$std_doc_id);
                     } else {
                         $rfc_criteria = $std_query;
	                 $order_by_criteria = "ORDER BY i.`SEE-ALSO` ASC, i.`DOC-ID` ASC";	
                     }
         	 }else if($fyi_flag){
	             if ($build_stmt) {
                         $fyi_doc_id = sprintf('FYI%04d',$out_number);
                         $build_stmt->bindParam('fyi_see_also',$fyi_doc_id);
                         $build_stmt->bindParam('fyi_doc_id',$fyi_doc_id);
                     } else {
                         $rfc_criteria = $fyi_query;
	                 $order_by_criteria = "ORDER BY i.`SEE-ALSO` ASC, i.`DOC-ID` ASC";
                     }						 
		 }											 							                                       } elseif (preg_match('/^[0-9][0-9][0-9][0-9]/',$value)){
                     if ($build_stmt) {
	                 $out_number = $value;
                         $rfc_doc_id = sprintf('%04d',$out_number);
                         $rfc_doc_id = '%'.$rfc_doc_id;                         
                         $build_stmt->bindParam('rfc_doc_id',$rfc_doc_id);
                         $bcp_see_also  = sprintf('BCP%04d',$out_number);
                         $build_stmt->bindParam('bcp_see_also',$bcp_see_also);
                         $std_see_also  = sprintf('STD%04d',$out_number);
                         $build_stmt->bindParam('std_see_also',$std_see_also);
                         $fyi_see_also  = sprintf('FYI%04d',$out_number);
                         $build_stmt->bindParam('fyi_see_also',$fyi_see_also);
                     } else {
                         $rfc_criteria = $all_digits_query;
		         $order_by_criteria = "ORDER BY i.`SEE-ALSO` ASC, i.`DOC-ID` ASC";	
                     }
						 
	     }								
	     break;
	 case '3':
	     if (preg_match('/^([0-9][0-9][0-9])$/',$value)){
	         $out_number = $value;
                 if ($build_stmt) {
                     $out_number = $value;
                     $rfc_doc_id = sprintf('%04d',$out_number);
                     $rfc_doc_id = '%'.$rfc_doc_id;                         
                     $build_stmt->bindParam('rfc_doc_id',$rfc_doc_id);
                     $bcp_see_also  = sprintf('BCP%04d',$out_number);
                     $build_stmt->bindParam('bcp_see_also',$bcp_see_also);
                     $std_see_also  = sprintf('STD%04d',$out_number);
                     $build_stmt->bindParam('std_see_also',$std_see_also);
                     $fyi_see_also  = sprintf('FYI%04d',$out_number);
                     $build_stmt->bindParam('fyi_see_also',$fyi_see_also);
                 } else {
                     $rfc_criteria = $all_digits_query;
                     /*Order by modify to accomplish special order for reslut RFC has to come first*/						 
	             $order_by_criteria = "ORDER BY FIELD(`DOC-ID`,'RFC%') ASC";
                 }
	     } elseif ((preg_match('/^(bcp|BCP|fyi|FYI|std|STD)$/',$value))){
		 #$out_number = $value;
		 if ($bcp_flag){
                     if ($build_stmt) {
                         $bcp_see_also = 'BCP%';
                         $build_stmt->bindParam('bcp_see_also',$bcp_see_also);
                         $bcp_doc_id = 'BCP%';
                         $build_stmt->bindParam('bcp_doc_id',$bcp_doc_id);
                     } else {
                         $rfc_criteria = $bcp_like_query;
		         $order_by_criteria = "ORDER BY i.`SEE-ALSO` ASC, i.`DOC-ID` ASC";		
                     }
		 } else if ($std_flag){
	             if ($build_stmt) {
                         $std_see_also = 'STD%';
                         $build_stmt->bindParam('std_see_also',$std_see_also);
                         $std_doc_id = 'STD%';
                         $build_stmt->bindParam('std_doc_id',$std_doc_id);
                     } else {
                         $rfc_criteria = $std_like_query;
		         $order_by_criteria = "ORDER BY i.`SEE-ALSO` ASC, i.`DOC-ID` ASC";	
                     }
      	         }else if($fyi_flag){
	             if ($build_stmt) {
                         $fyi_see_also = 'FYI%';
                         $build_stmt->bindParam('fyi_see_also',$fyi_see_also);
                         $fyi_doc_id = 'FYI%';
                         $build_stmt->bindParam('fyi_doc_id',$fyi_doc_id);
                     } else {
                         $rfc_criteria = $fyi_like_query;
		         $order_by_criteria = "ORDER BY i.`SEE-ALSO` ASC, i.`DOC-ID` ASC";	
                     }
     	         }
             }
             break;
	 case '2':
	     if (preg_match('/^([0-9][0-9])$/',$value)){
	         if ($build_stmt) {
                     $out_number = $value;
                     $rfc_doc_id = sprintf('%04d',$out_number);
                     $rfc_doc_id = '%'.$rfc_doc_id;                         
                     $build_stmt->bindParam('rfc_doc_id',$rfc_doc_id);
                     $bcp_see_also  = sprintf('BCP%04d',$out_number);
                     $build_stmt->bindParam('bcp_see_also',$bcp_see_also);
                     $std_see_also  = sprintf('STD%04d',$out_number);
                     $build_stmt->bindParam('std_see_also',$std_see_also);
                     $fyi_see_also  = sprintf('FYI%04d',$out_number);
                     $build_stmt->bindParam('fyi_see_also',$fyi_see_also);
                 } else {
                     $rfc_criteria = $all_digits_query;
                     /*Order by modify to accomplish special order for reslut RFC has to come first*/						 					
                     $order_by_criteria = $default_order_by;	
                 }				 
	     }			
	     break;
	 case '1':
	     if (preg_match('/^([0-9])$/',$value)){
		 $out_number = $value;
                 if ($build_stmt) {
                     $out_number = $value;
                     $rfc_doc_id = sprintf('%04d',$out_number);
                     $rfc_doc_id = '%'.$rfc_doc_id;                         
                     $build_stmt->bindParam('rfc_doc_id',$rfc_doc_id);
                     $bcp_see_also  = sprintf('BCP%04d',$out_number);
                     $build_stmt->bindParam('bcp_see_also',$bcp_see_also);
                     $std_see_also  = sprintf('STD%04d',$out_number);
                     $build_stmt->bindParam('std_see_also',$std_see_also);
                     $fyi_see_also  = sprintf('FYI%04d',$out_number);
                     $build_stmt->bindParam('fyi_see_also',$fyi_see_also);
                 } else {
                     $rfc_criteria = $all_digits_query;
                     /*Order by modify to accomplish special order for reslut RFC has to come first*/						 						                 $order_by_criteria = $default_order_by;

                 }
	     }			
	     break;
	 default:
	     break;		 
     }
     if ($debug_rsl){
	 print "RFc criteria : ". $rfc_criteria;
	 print "Order By : ". $order_by_criteria;
     }
     if ($build_stmt){
          return $build_stmt;
     } else {	  
        return array($rfc_criteria,$order_by_criteria);
     }
}


/****************************************************************************************/
/*Function : build_title_criteria                                                       */
/*           This function builds the complex RFC criteria for Title and keyword fields */
/*           based on the input entry in Title/Keyword field                            */
/****************************************************************************************/
function build_title_criteria($title_value,$title_stmt=NULL){
     global $debug_rsl;
     $title_criteria = "";
     $title_order_by = "";

     $quote_flag = 0;

     if (preg_match('/^\"/',$title_value)){
	 $quote_flag = 1;
         #print "We are in quotes\n";
     }

     if ($quote_flag){
         if ($title_stmt){
	     $title_value = str_replace('"','',$title_value);
             $title_value = '%'.$title_value.'%';
             $title_stmt->bindParam('title_value',$title_value);
             $title_stmt->bindParam('keyword_value',$title_value);
         } else {
	     $title_criteria = '(i.`TITLE` like :title_value OR i.`keywords` like :keyword_value';
         }
     }else {
	 if (preg_match('/\b(AND|OR)/i',$title_value)){/*Title search string contains ANR|OR*/				
	     if ((preg_match('/\b(AND)/i',$title_value)) && (!preg_match('/\b(OR)/i',$title_value))){/*Case : AND*/				
	         $title_value = strtolower($title_value); /*Make search lower string string */
		 $title_value_array = explode(" and ",$title_value);
		 foreach ($title_value_array as $key=>$value){
		     if ($title_criteria == ""){
                         if ($title_stmt){
                             $value  = '%'.$value.'%';
                             $title_stmt->bindValue('title_value_'.$key,$value);         
                             $title_stmt->bindValue('keyword_value_'.$key,$value);       
                         } else {
			     $title_criteria .= '((i.`TITLE` like :title_value_'.$key. ' OR i.`keywords` like :keyword_value_'. $key. ')';
                         }
		     }else {
                         if ($title_stmt){
                             $value  = '%'.$value.'%';
                             $title_stmt->bindValue('title_value_'.$key,$value);         
                             $title_stmt->bindValue('keyword_value_'.$key,$value); 
                         } else {
			     $title_criteria .= ' AND (i.`TITLE` like :title_value_'.$key. ' OR i.`keywords` like :keyword_value_'. $key. ')';
                         }
		     }
		 }
             }else if ((!preg_match('/\b(AND)/i',$title_value)) && (preg_match('/\b(OR)/i',$title_value))){/*Case : OR*/				
		 $title_value = strtolower($title_value); /*Make search lower string string */
		 $title_value_array = explode(" or ",$title_value);
		 foreach ($title_value_array as $key=>$value){
		     if ($title_criteria == ""){
                         if ($title_stmt){
                             $value  = '%'.$value.'%';
                             $title_stmt->bindValue('title_value_'.$key,$value);         
                             $title_stmt->bindValue('keyword_value_'.$key,$value); 
                         } else {
			     $title_criteria .= '((i.`TITLE` like :title_value_'.$key. ' OR i.`keywords` like :keyword_value_'. $key. ')';
                         }
		     }else {
                         if ($title_stmt){
                             $value  = '%'.$value.'%';
                             $title_stmt->bindValue('title_value_'.$key,$value);         
                             $title_stmt->bindValue('keyword_value_'.$key,$value); 
                         } else {
			     $title_criteria .= ' OR (i.`TITLE` like :title_value_'.$key. '%" OR i.`keywords` like :keyword_value_'. $key. ')';
                         }
		     }
		 }
	     }
	 } else {/*Title search string does not contains ANR|OR*/
	     $title_value_array = explode(" ",$title_value);
	     foreach ($title_value_array as $key=>$value){
		 if ($title_criteria == ""){
                     if ($title_stmt){
                         $value  = '%'.$value.'%';
                         $title_stmt->bindValue('title_value_'.$key,$value);         
                         $title_stmt->bindValue('keyword_value_'.$key,$value);         
                     } else {
		         $title_criteria .= '((i.`TITLE` like :title_value_'.$key. ' OR i.`keywords` like :keyword_value_'. $key. ')';
                     }
		 }else {
                     if ($title_stmt){
                         $value  = '%'.$value.'%';
                         $title_stmt->bindValue('title_value_'.$key,$value);         
                         $title_stmt->bindValue('keyword_value_'.$key,$value);         
                     } else {
		         $title_criteria .= ' AND (i.`TITLE` like :title_value_'.$key. ' OR i.`keywords` like :keyword_value_'. $key. ')';
                     }
		 }
	     }
	 }		 
     }
	 
     $title_criteria = "AND ".$title_criteria.")";	

     if ($title_stmt){
         return $title_stmt;
     } else {
         return array($title_criteria,$title_order_by);
     }
}


	
/****************************************************************************************/
/*Function : contains																	*/
/*			 Title supporting function													*/
/****************************************************************************************/
function contains($needle, $haystack){
	 global $debug_rsl;
	 	 return strpos($haystack, $needle);
	
	}
	
/****************************************************************************************/
/*Function : build_abstract_box_criteria                                                 */
/*           This function builds the complex RFC criteria for Abstract Contains box    */
/*           based on the input entry in Abstract Contains Box                          */
/****************************************************************************************/
function build_abstract_box_criteria($abstract_box_value,$abstract_stmt=NULL){
     global $debug_rsl;
     $abstract_box_criteria = "";
     $abstract_box_order_by = "";
     $quote_flag = 0;
     if (preg_match('/^\"/',$abstract_box_value)){
	 $quote_flag = 1;
     }
     if ($quote_flag){
         if ($abstract_stmt) {
	     $abstract_box_value = str_replace('"','',$abstract_box_value) ;
	     $abstract_box_value = '%'.$abstract_box_value.'%';
             $abstract_stmt->bindParam('abstract_box_value',$abstract_box_value);
         } else {
	 #$abstract_box_criteria = '(i.`abstract` like "%'.$abstract_box_value.'%"';
	     $abstract_box_criteria = '(i.`abstract` like :abstract_box_value';
         }
     } else {
	 if (preg_match('/\b(AND|OR)/i',$abstract_box_value)){/*Abstract contain search string contains ANR|OR*/			
	     if ((preg_match('/\b(AND)/i',$abstract_box_value)) && (!preg_match('/\b(OR)/i',$abstract_box_value))){/*Case : AND*/				
         	 $abstract_box_value = strtolower($abstract_box_value); /*Make search lower string string */
		 $abstract_box_value_array = explode(" and ",$abstract_box_value);
		     foreach ($abstract_box_value_array as $key=>$value){
			 if ($abstract_box_criteria == ""){
			     #$abstract_box_criteria .= '((i.`abstract` like "%'.$value. '%")';
                             if ($abstract_stmt) {
                                 $value = '%'.$value.'%';
                                 $abstract_stmt->bindValue('abstract_box_value_'.$key,$value);
                             } else {
			         $abstract_box_criteria .= '((i.`abstract` like :abstract_box_value_'.$key.')';
                             }
			 }else {
			     #$abstract_box_criteria .= ' AND (i.`abstract` like "%'.$value. '%")';
                              if ($abstract_stmt) {
                                 $value = '%'.$value.'%';
                                 $abstract_stmt->bindValue('abstract_box_value_'.$key,$value);
                             } else {
			         $abstract_box_criteria .= ' AND (i.`abstract` like :abstract_box_value_'.$key.')';
                             }
			 }
		     }
	     }else if ((!preg_match('/\b(AND)/i',$abstract_box_value)) && (preg_match('/\b(OR)/i',$abstract_box_value))){/*Case : OR*/				
		 $abstract_box_value = strtolower($abstract_box_value); /*Make search lower string string */
		 $abstract_box_value_array = explode(" or ",$abstract_box_value);
		 foreach ($abstract_box_value_array as $key=>$value){
	             if ($abstract_box_criteria == ""){
			 #$abstract_box_criteria .= '((i.`abstract` like "%'.$value. '%")';
                         if ($abstract_stmt) {
                             $value = '%'.$value.'%';
                             $abstract_stmt->bindValue('abstract_box_value_'.$key,$value);
                         } else {
			     $abstract_box_criteria .= '((i.`abstract` like :abstract_box_value_'.$key.')';
                         }
		     } else {
			 #$abstract_box_criteria .= ' OR (i.`abstract` like "%'.$value. '%")';
		         if ($abstract_stmt) {
			     $value = '%'.$value.'%';
			     $abstract_stmt->bindValue('abstract_box_value_'.$key,$value);
		         } else {
			     $abstract_box_criteria .= ' OR (i.`abstract` like :abstract_box_value_'.$key.')';
		         }
	             }
	         }
	    } 
           } else {/*Title search string does not contains ANR|OR*/
              $abstract_box_value_array = explode(" ",$abstract_box_value);
	      foreach ($abstract_box_value_array as $key=>$value){
	         if ($abstract_box_criteria == ""){
		     #$abstract_box_criteria .= '((i.`abstract` like "%'.$value. '%")';
                     if ($abstract_stmt) {
                         $value = '%'.$value.'%';
                         $abstract_stmt->bindValue('abstract_box_value_'.$key,$value);
                     } else {
		         $abstract_box_criteria .= '((i.`abstract` like :abstract_box_value_'.$key.')';
                     }
	         } else {
                     if ($abstract_stmt) {
                         $value = '%'.$value.'%';
                         $abstract_stmt->bindValue('abstract_box_value_'.$key,$value);
                     } else {
		         $abstract_box_criteria .= ' AND (i.`abstract` like :abstract_box_value_'.$key.')';
                     }
	         }
	     }
         }
     }

     if ($abstract_stmt) {
        return $abstract_stmt;  
     } else {
        $abstract_box_criteria = "AND ".$abstract_box_criteria.")";	 
         return array($abstract_box_criteria,$abstract_box_order_by);
     }
}
	
/**************************************************************************************/
/*This function formatts the status in the proper format and attached pub status to it*/
/*If the status is historic*/
/**************************************************************************************/
	
function get_formatted_status($line){
	 global $debug_rsl;
	 $out_status = "";
	 $status_lower = "";
	 $pub_status_lower = "";
	 $format_date_url = "";
	 
 	 $status_array_count = 0;
	 $j = 0;

	 $status_array = explode(" ",$line['status']);
	 $status_array_count = count($status_array);
	 foreach ($status_array as $key=>$value){
		 	  $j++;	
			  $value = strtolower($value);
			  $value = ucfirst($value);
			   if ($j == $status_array_count){
			     $status_lower .= $value;
			   }else {
#				  $status_lower .= $value."&nbsp;";
  				  $status_lower .= $value." ";
			   }

	 }
			  	 
	 $out_status = $status_lower;

	 $pub_status_array_count = 0;
	 $i = 0;
	 
	 $pub_status_array = explode(" ",$line['PUB-STATUS']);
	 $pub_status_array_count = count($pub_status_array);
	
	 foreach ($pub_status_array as $key=>$value){
		 	$i++;
			  $value = strtolower($value);
			  $value = ucfirst($value);
			  if ($i == $pub_status_array_count){
				  $pub_status_lower .= $value;
			  }else {
			
#				  $pub_status_lower .= $value."&nbsp;";
				  $pub_status_lower .= $value." ";				  
			  }
	 }
		 
		
	 
	 $pub_status_ucfirst = $pub_status_lower;

 
	 if (($out_status) &&($out_status != $pub_status_ucfirst)){
		 //Get the date of status change
#		 $date_of_change = get_date_of_change($line);
		list($date_of_change,$url_of_change) = get_date_of_change($line);		 
		if ($date_of_change){
			if ($url_of_change){
				$format_date_url  = ' '.'<a href="'.$url_of_change.'"target="_blank">';
				$format_date_url .= "$date_of_change"."</a>";
			}else{
				$format_date_url = ' '.$date_of_change;
				}
		}
#		 $out_status = $out_status."&nbsp;"."(was published as ".$pub_status_ucfirst.")";
# 		 $out_status = $out_status." "."(was published as ".$pub_status_ucfirst.")";
  		 $out_status = $out_status." "."(changed from ".$pub_status_ucfirst."".$format_date_url.")";
	 }
	 if ($debug_rsl){
		 print "Out status is:".$out_status."\n";
		 $docid = $line['DOC-ID'];
		 print "Key : $docid";
		 print "Pub Status: $pub_status_ucfirst";
		 print "Status : $out_status";
		 print "Date: $format_date_url";
	 }
	 
	 return $out_status;
	}

/**************************************************************************************/
/*Get the date of status change from the status_changes table
/**************************************************************************************/
function get_date_of_change($line){
     global $debug_rsl;
     global $pdo;	
     $dockey = $line['internal_key'];

     try {
	 $date_query = "SELECT  DATE_FORMAT(`date_of_change`,'%M %Y') as 'date_of_change',`URL_of_change` FROM `status_changes` WHERE `dockey` = :dockey";
         $stmt = $pdo->prepare($date_query);
         $stmt->bindParam('dockey',$dockey);
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
    } catch (PDOException $pe){
         error_log("Error processing : get_date_of_change", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
    }
    
	 
     $return_date_count = $num_of_rows;
     #If the RFC has and errata entry
     if ($return_date_count > 0){	
         while ( $line = $stmt->fetch(PDO::FETCH_NUM)){
             $date_of_change = $line[0];
             $url_of_change = $line[1];
       	 }
     }
     return array($date_of_change,$url_of_change) ;
}
/**************************************************************************************/
/*Split rfc functions splits the given RFC/Subseries number in two parts*/
/**************************************************************************************/
function split_rfc($in_value){

	 global $debug_rsl;
	 $length = strlen($in_value);

	 $out_name = "";
	 $out_number = "";

	if ($length == 9){
         $out_name = substr($in_value,0,'-6');
         $out_number = substr($in_value,'-5');
	}
	elseif ($length == 8){
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
	 if ($debug_rsl){
		 print "Out name is :".$out_name."\n";
	     print "Out number is :".$out_number."\n";
  	     print "Length is :".$length."\n";
	 }
	 
	 return array($out_name, $out_number, $length);
	}
	
	
?>
