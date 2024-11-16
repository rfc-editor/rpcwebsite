#!/usr/bin/php
<?php
/************************************************************************************/
/*Script name : xmlRFCref.php                                                       */
/*Description : The script generates individual XML files using the database        */
/*              information at a specified location                                 */
/* Added code for DOI in the XML, but turned off for now                            */
/*August 2017 : Modified the info page base URL to use HTTPS - PN                   */
/************************************************************************************/
include('dbi.php');
$debug_xmlRef = false;
# where to put the bibxml files
$bibxml_dir = "/a/inc-work/refs/bibxml";
# set to true to include DOIs
$include_doi = true;



 get_data();


/**************************************************************************************/
/*Function :  get_data                                                                */
/*            Get the data arranged in the XML format from the database               */
/**************************************************************************************/
function get_data(){
global $debug_xmlRef, $include_doi, $bibxml_dir;

$rfc_select = "SELECT TYPE, `DOC-ID`, TITLE, AUTHORS, DATE_FORMAT(`PUB-DATE`,'%M %Y'), KEYWORDS, 

		    ORGANIZATION, FORMAT, `CHAR-COUNT`,`SEE-ALSO`, DOI, ABSTRACT
           FROM `index` i, working_group w, area a, stream_specific_parties s
           WHERE TYPE='RFC' AND state_id='14' AND `DOC-ID` <> '0'
                AND `DOC-ID` <> '' 
                AND i.source = w.wg_name AND w.area_name = a.area_name 
                AND w.ssp_id = s.ssp_id
           ORDER BY `DOC-ID`";

$db_link = open_db();

if ($result = mysqli_query($db_link,$rfc_select)){
    $result_array = array();

    if ( $debug_xmlRef === true) {
	print("XMLized reference data :\n");
	print_r($result_array); print("\n");
	print("\n");
    }

    # build result array
    while ( $line = mysqli_fetch_row($result)) {
       $print_data = "";
       list($rfc_name,$rfc_number,$length) = split_rfc(trim($line[1]));
       $xmlRFCref_file =fopen("$bibxml_dir/reference.RFC.$rfc_number.xml",'w');

       $lower_rfc   = strtolower($line[1]);

       /*Get the anchor and target value*/
       $http_in_info_base_url = 'https://www.rfc-editor.org/info/';
       $in_data_name_link = strtolower($rfc_name);
       $http_in_data_name = sprintf('%s&nbsp;%d',$rfc_name,$rfc_number);
       $info_link = sprintf('%s%d',$in_data_name_link,$rfc_number);
       $http_in_info_url = $http_in_info_base_url.$info_link;
       $print_data .= "<?xml version='1.0' encoding='UTF-8'?>\n\n"; 
       $print_data .= "<reference  anchor='$line[1]' target='$http_in_info_url'>\n"; 
       $print_data .= "<front>\n";


       $title = htmlspecialchars($line[2]);
       $title = trim($title); 
       $print_data .= "<title>$title</title>\n";
       $print_data .= get_authors($line[3]);
       if(isset($line[4])) {
               list($month,$year) = preg_split("/[\s]+/",$line[4]);
               $print_data .= "<date year='$year' month='$month' />\n";
       }
       $abstract = htmlspecialchars($line[11]);
       $abstract = trim($abstract); 
       $abstract = str_replace("\r\n\r\n",'</t><t>',$abstract);
       $abstract = str_replace("\n\n",'</t><t>',$abstract);
       $abstract = str_replace("\r",'',$abstract);
       $abstract = str_replace("\n",' ',$abstract);
       if(strlen($abstract)>0) { $print_data .= "<abstract><t>$abstract</t></abstract>\n"; }
       $print_data .= "</front>\n";
       if(preg_match('/^(bcp|BCP|std|STD|fyi|FYI)/',$line[9])){
          $print_data .= get_seriesinfo($rfc_name,$rfc_number,$line[9],$line[10]);
       }else{
         $rfc_number = print_number($rfc_number);
         $print_data .= "<seriesInfo name='$rfc_name' value='$rfc_number'/>\n";
         if($include_doi && isset($line[10])){
                 $print_data .= "<seriesInfo name='DOI' value='$line[10]'/>\n";
         }
       }
       $format = trim($line[7]);
       $octets = trim($line[8]);
       # $print_data .= "<format type='$format' octets='$octets'/>\n"; 
       $print_data .= "</reference>\n";

       $xml_mainContent = $print_data;
       $xml_footer =  "";

       if (! $xmlRFCref_file) die("Error opening file");
           fwrite($xmlRFCref_file,$xml_mainContent);
           fwrite($xmlRFCref_file,$xml_footer);
           fclose($xmlRFCref_file);

    }
}else {

  print "Query \"$rfc_select\" failed: " . mysqli_error();

}
close_db();

}

/**************************************************************************************/
/*Function :  get_authors                                                             */
/*            Get the author names seperated and as per the desired XMLized format    */
/**************************************************************************************/
function get_authors($authors_list){
    global $debug_xmlRef;
    $author_tag = "";
 
    $authors_list = preg_replace('/, Ed\./', '; Ed.', $authors_list);
    $authors_list = preg_replace('/, Eds\./', '; Eds.', $authors_list);
    $authors_list = preg_replace('/, ed\./', '; ed.', $authors_list);
    $authors_list = preg_replace('/, eds\./', '; eds.', $authors_list);

    $authors = explode(',', $authors_list);
 
    foreach ($authors as $author) {
       $found_ed = FALSE;
       $found_eds = FALSE;

       $found_WGchair = FALSE;
       $found_chair = FALSE;

       $author = trim($author);
       $author = preg_replace('/; Ed\./', ', Ed.', $author);
       $author = preg_replace('/; ed\./', ', ed.', $author);
       $author = preg_replace('/; Eds\./', ', Eds.', $author);
       $author = preg_replace('/; eds\./', ', eds.', $author);
       $author = preg_replace("/'/","&apos;",$author);

       if (preg_match("/, Ed./",$author)) {
           $found_ed = TRUE;
           $author = preg_replace('/, Ed./', '', $author);
       }

       if (preg_match("/, ed./",$author)) {
           $found_ed = TRUE;
           $author = preg_replace('/, ed./', '', $author);
       }

       if (preg_match("/, Eds./",$author)) {
           $found_eds = TRUE;
           $author = preg_replace('/, Eds./', '', $author);
       }

       if (preg_match("/, eds./",$author)) {
           $found_eds = TRUE;
           $author = preg_replace('/, eds./', '', $author);
       }

       if (preg_match("/WG Chair/",$author)){
           $found_WGchair = TRUE;
       }

       if (preg_match("/Chair/",$author)){
           $found_chair = TRUE;
       }


       $author_string = preg_split("/[\s]+/",$author);

       $authorname_count = count($author_string);

       if ($debug_xmlRef === true){
          print "Authorname_count is".$authorname_count."\n";
          print_r($author_string)."\n";
       }

       if ( $debug_xmlRef === true) {
       	   print_r($author_string);
       }
       if (preg_match("/IAB|IANA|IESG|National|Internationa|Working|Internet|Defense|Forum|Force|Comittee|Network|Board|Authority|Council|Trustees|Society|Microsystems|Laboratories|University/",$author)){
           $author_tag .= "<author><organization>$author</organization></author>\n";
       }else {
           if ($authorname_count <= '2') {/*If authorname is in the form of initials and last name without spaces*/           
         	if ($found_ed){
              	   $author_tag .= "<author initials='$author_string[0]' surname='$author_string[1]' fullname='$author' role='editor'><organization /></author>\n";
                } elseif ($found_ed){
                     $author_tag .= "<author initials='$author_string[0]' surname='$author_string[1]' fullname='$author' role='editors'><organization /></author>\n";
                } elseif ($found_WGchair){
                     $author_tag .= "<author initials='$author_string[0]' surname='$author_string[1]' fullname='$author' role='WG Chair'><organization /></author>\n";
                } elseif ($found_chair && isset($author_string[1])) {
                     $author_tag .= "<author initials='$author_string[0]' surname='$author_string[1]' fullname='$author' role='Chair'><organization /></author>\n";
                } elseif(isset($author_string[0])) {
                        if(!isset($author_string[1])) $author_string[1] = ""; /* hack for RFC 3141 with too many authors */
                        $author_tag .= "<author initials='$author_string[0]' surname='$author_string[1]' fullname='$author'><organization /></author>\n";
                }
           } else {/*If authorname is in the form of initials and last name with spaces*/
                $author_string_rest = "";
                for ($i=1; $i <= $authorname_count; $i++){
                     if(isset($author_string[$i])) $author_string_rest .= " ".$author_string[$i]; 
                }
                $author_string_rest = trim($author_string_rest);

		$author_string_with_dot = ""; 
                $author_string_surname = "";
                $author_string_withdot_array_count = "";
                $max_count_minus_one = "";
                $length_withdot_element= "";

                if (preg_match("/\./",$author_string_rest)) { /*Check if the Author name has more than one initials*/
                   $author_string_withdot = preg_split("/[.]/",$author_string_rest);       
                   $length_withdot_element = strlen($author_string_withdot[0]); 
                   
                   if  ($length_withdot_element < '2'){ 
    
			   $author_string_withdot_array_count = count($author_string_withdot);        

			   for ($j=0;$j<= $author_string_withdot_array_count;$j++){
			      if ($j <  ($author_string_withdot_array_count - 1)){
			       $author_string_with_dot .=  $author_string_withdot[$j]."." ;
			      }else {
				  $author_string_surname .= $author_string_withdot[$j];
				 }
			   }

			   
			   if ( $debug_xmlRef === true) {
			    print "Special Author names with two initials";
			    print_r($author_string_withdot);
			    print "\nFirst String length to check initial or surname ";
			    print "$length_withdot_element\n"; 
			    print "$author_string_rest";
			    print "\nAuthor initials part with dot is\n";
			    print "Part one :$author_string[0]";
			    print "\nPart later:$author_string_with_dot";
			    print "\nPart surname:$author_string_surname ";
			    print "Part fullname: $author";

                           }

                    $author_string[0].= " ".$author_string_with_dot;
                    $author_string_rest = trim($author_string_surname);
                  }
                }
   
	  	if ($found_ed){
              	   $author_tag .= "<author initials='$author_string[0]' surname='$author_string_rest' fullname='$author' role='editor'><organization /></author>\n";
                } elseif ($found_ed){
                     $author_tag .= "<author initials='$author_string[0]' surname='$author_string_rest' fullname='$author' role='editors'><organization /></author>\n";
                } elseif ($found_WGchair){
                     $author_tag .= "<author initials='$author_string[0]' surname='$author_string_rest' fullname='$author' role='WG Chair'><organization /></author>\n";
                } elseif ($found_chair){
                     $author_tag .= "<author initials='$author_string[0]' surname='$author_string_rest' fullname='$author' role='Chair'><organization /></author>\n";
                } else { 
                     $author_tag .= "<author initials='$author_string[0]' surname='$author_string_rest' fullname='$author'><organization /></author>\n";
                }
           } 
           
       }
    }

      return $author_tag;


}

/**************************************************************************************/
/*Function : get_seriesinfo                                                           */
/*           get_seriesinfo function checks see-also field and creates seriesinfo tag */
/**************************************************************************************/
function get_seriesinfo($in_rfc_name,$in_rfc_number,$see_also,$doi_number){
    global $debug_xmlRef,$include_doi;
    $see_also_data = "";
    $see_also_array = explode(',', $see_also);
    foreach ($see_also_array as $see_also_entry)
    {
        $see_also_entry = trim($see_also_entry);
        if (preg_match("/BCP|bcp/",$see_also_entry)){
             list($see_name,$see_number,$length) = split_rfc($see_also_entry);
             $see_number = print_number($see_number);
             $see_also_data .= "<seriesInfo name='BCP' value='$see_number'/>\n";
        }
        if (preg_match("/STD|std/",$see_also_entry)){
             list($see_name,$see_number,$length) = split_rfc($see_also_entry);
             $see_number = print_number($see_number);
             $see_also_data .= "<seriesInfo name='STD' value='$see_number'/>\n";
        }
	if (preg_match("/FYI|fyi/",$see_also_entry)){
             list($see_name,$see_number,$length) = split_rfc($see_also_entry);
             $see_number = print_number($see_number);
             $see_also_data .= "<seriesInfo name='FYI' value='$see_number'/>\n";
        }
	if (preg_match("/RFC|rfc/",$see_also_entry)){
             list($see_name,$see_number,$length) = split_rfc($see_also_entry);
             $see_number = print_number($see_number);
             $see_also_data .= "<seriesInfo name='RFC' value='$see_number'/>\n";
        }
    }
    $in_rfc_number = print_number($in_rfc_number);
    $see_also_data .= "<seriesInfo name='$in_rfc_name' value='$in_rfc_number'/>\n";
    if($include_doi && isset($doi_number))
        $see_also_data .= "<seriesInfo name='DOI' value='$doi_number'/>\n";


    return $see_also_data;
}

/**************************************************************************************/
/*Function : print_number                                                             */
/*           print_number prints the RFC number in desired format                     */
/**************************************************************************************/
function print_number($in_number){
   $out_number = sprintf('%d',$in_number);
   return $out_number;
}  
/**************************************************************************************/
/*Function : split_rfc                                                                */
/*           Split rfc functions splits the given RFC/Subseries number in two parts   */
/**************************************************************************************/
function split_rfc($in_value){

	 global $debug_xmlRef;
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

        if ( $debug_xmlRef === true) {
             print "Out name is :".$out_name."\n";
	     print "Out number is :".$out_number."\n";
  	     print "Length is :".$length."\n";
	}
	 
	return array($out_name, $out_number, $length);
	}
	
?>
