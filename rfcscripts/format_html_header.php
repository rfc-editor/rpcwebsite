<?php
/***************************************************************************************/
/* Copyright The IETF Trust 2020 All Rights Reserved                                   */
/*Script Name : format_html_header.php                                                  */
/*Description : The script gets input values as display(rfc|bcp|std|fyi), then number  */
/*              XXXX and type as HTML                                                  */
/*              Gets the desired values from the database to display the header for the*/
/*              rfcXXXX.html file and displays header with proper formatting           */
/*January 2019 : Created the script - PN                                               */
/*May 2019     : Added script to production under Publication format changes Phase I   */
/*               - PN                                                                  */
/*May 2019     : Modified the script to handle the case where document is not publised */
/*               .html file does not exist - PN                                        */
/*May 2019     : Modified script to correct the Dublin Core values for Authors and Date*/
/*               Issued (Published date) field - PN                                    */
/*May 2019     : Modified script to correct the Dublin Core identifier for Date as     */
/*               DC.Date.Issued Issued  - PN                                           */
/*Feb 2020     : Modified script to correct the Status field from the header to based  */
/*               database field STATUS instead of PUB-STATUS - PN                      */
/*May 2020     : Modified the script to accomadate very long Updated By value in the   */
/*               header. This required modification of the processing of header        */
/*               with a new approach - PN                                              */
/*November 2021 : Modified the script to add info link in the first line of HTML       */
/*               header - PN                                                           */
/*January 2022  : Change tags to make Google Scholar happy, fix broken HTML - JRL      */
/*August 2022  : Modified CSS to comment the line-height  - PN                         */
/*January 2023  : Added closing tag for citation_technical_report_number - PN          */
/***************************************************************************************/


   include('db_connect.php');
   $debug_html_header=FALSE;
   $debug_add_header=FALSE;

/******************************************************************/
/*get_error_header : Gets the Header information for Errored i/p  */
/******************************************************************/
   function get_error_header($in_num,$display,$type){
      global $debug_html_header;
      $html_error_header = "";

      $padded_num = sprintf("%04d",$in_num);

      $doc_id = $display.$padded_num;

      $html_error_title = '<title>' . htmlspecialchars(strtoupper($display)) . ' ' . htmlspecialchars($in_num) . ': Not found</title>';

      $html_error_css = get_css();

      $html_error_javascript = get_javascript();

      $html_error_remaining = get_error_remaining($rfc_data,$display,$in_num,$case);
      
      $html_error_header = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="robots" content="index,follow" />
    <meta name="creator" content="rfchandler version 0.2" />';

      $html_error_header .= $html_error_title;
  
      $html_error_header .= $html_error_css;
      
      $html_error_header .= $html_error_javascript;
 
      $html_error_header .= $html_error_remaining;
      
      return $html_error_header;
 
   }

/**********************************************************************/
/*get_html_header : Gets the Header information for published i/p RFC */
/**********************************************************************/
   function get_html_header($in_num,$display,$type){
   
      global $debug_html_header;
      $html_header = "";

      $padded_num = sprintf("%04d",$in_num);

      $doc_id = $display.$padded_num;


      list($rfc_data,$case) = check_for_subseries($display,$doc_id,$in_num);
      
      if ($display == 'rfc'){
        $html_meta_header = get_meta_header($rfc_data,$display,$in_num);
      }

      $html_title = '<title>'. htmlspecialchars(strtoupper($display)) . ' ' . htmlspecialchars($in_num) . ': ' . htmlspecialchars($rfc_data['TITLE']) . '</title>';

      $html_css = get_css();

      $html_javascript = get_javascript();

      $html_remaining = get_html_remaining($rfc_data,$display,$in_num,$case);
           

      $html_header = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="robots" content="index,follow" />
    <meta name="creator" content="rfchandler version 0.2" />' . "\n";

      $html_header .= $html_meta_header;

      $html_header .= $html_title;

      $html_header .= $html_css;
      
      $html_header .= $html_javascript;

      $html_header .= "</head>\n<body>\n";	// everything after this is in the body
 
      $html_header .= $html_remaining;
      
      return $html_header;
   }

/***********************************************************/
/*get_meta_header : Gets the data for the input values      */
/***********************************************************/

function get_meta_header($rfc_data,$display,$in_num){

   global $debug_html_header;
  
    
   $author_tag = "";
   if ($debug_html_header === TRUE) {
       print("<pre>\n");
       print_r($rfc_data);
       print("</pre>\n");
   }

   $rfc_data['AUTHORS'] = preg_replace('/, Ed\./', '; Ed.', $rfc_data['AUTHORS']);
	$authors_list = $rfc_data['AUTHORS'];
   $authors = explode(',', $authors_list);
   $num_auths = count($authors);
   foreach ($authors as $author) {
       $author = trim($author);
       $author = preg_replace('/; Ed\./', '', $author); // no Ed. per Google Scholar
       $author_tag .= "      <meta name=\"citation_author\" content=\"" . htmlspecialchars($author) . "\"/>\n";
   }
  
  $pub_day = substr($rfc_data['PUB-DATE'],-2);

  if ($pub_day == '00') { /*Cases which has Publication date day as 00*/
         $date_pieces = explode("-",$rfc_data['PUB-DATE']);
         $date_pieces[2] = '01';
         $pub_date = $date_pieces[0]."-".$date_pieces[1]."-".$date_pieces[2];
         $date_published = date("F, Y" ,strtotime($pub_date));
  }else {
         $date_published = date("F, Y" ,strtotime($rfc_data['PUB-DATE']));
 }
  
  // server name for PDF URL below
  if (isset($_SERVER['SERVER_NAME'])) {
	  $server = "https://" . $_SERVER['SERVER_NAME'];
  } else {
	  $server = "http://localhost";
  }
  $reportnumber = sprintf("%s%d", strtolower(substr($rfc_data['DOC-ID'],0,3)),substr($rfc_data['DOC-ID'], 3)); // rfc123

  $meta_header = $author_tag .
      '      <meta name="citation_publication_date" content="' . htmlspecialchars($date_published) . '"/>
      <meta name="citation_title" content="'.htmlspecialchars($rfc_data['TITLE']).'"/>
      <meta name="citation_doi" content="'.htmlspecialchars($rfc_data['DOI']).'"/>
      <meta name="citation_issn" content="2070-1721"/>
      <meta name="citation_technical_report_number" content="' . $reportnumber . '"/>
      <meta name="citation_pdf_url" content="' .  htmlspecialchars($server . generate_url_by_format($rfc_data['DOC-ID'], 'ASCII, PDF')) . "\"/>\n";

   if ($debug_html_header === TRUE) {
      print "Inside get_meta_header";
      print $meta_header;
   }

   return $meta_header;
}

/***********************************************************/
/*get_css : Gets the css for the header                    */
/***********************************************************/

   function get_css(){
   
   $txt_css = '    
        

        <style type="text/css">
	@media only screen 
	  and (min-width: 992px)
	  and (max-width: 1199px) {
	    body { font-size: 14pt; }
            div.content { width: 96ex; margin: 0 auto; }
        }
	@media only screen 
	  and (min-width: 768px)
	  and (max-width: 991px) {
            body { font-size: 14pt; }
            div.content { width: 96ex; margin: 0 auto; }
        }
	@media only screen 
	  and (min-width: 480px)
	  and (max-width: 767px) {
            body { font-size: 11pt; }
            div.content { width: 96ex; margin: 0 auto; }
        }
	@media only screen 
	  and (max-width: 479px) {
            body { font-size: 8pt; }
            div.content { width: 96ex; margin: 0 auto; }
        }
	@media only screen 
	  and (min-device-width : 375px) 
	  and (max-device-width : 667px) {
            body { font-size: 9.5pt; }
            div.content { width: 96ex; margin: 0; }
        }
	@media only screen 
	  and (min-device-width: 1200px) {
            body { font-size: 10pt; margin: 0 4em; }
            div.content { width: 96ex; margin: 0; }
        }
        h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6 {
	    font-weight: bold;
           /* line-height: 0pt; */
            display: inline;
            white-space: pre;
            font-family: monospace;
            font-size: 1em;
	    font-weight: bold;
        }
        pre {
            font-size: 1em;
            margin-top: 0px;
            margin-bottom: 0px;
        }
	.pre {
	    white-space: pre;
	    font-family: monospace;
	}
	.header{
	    font-weight: bold;
	}
        .newpage {
            page-break-before: always;
        }
        .invisible {
            text-decoration: none;
            color: white;
        }
        a.selflink {
          color: black;
          text-decoration: none;
        }
        @media print {
            body {
                font-family: monospace;
                font-size: 10.5pt;
            }
            h1, h2, h3, h4, h5, h6 {
                font-size: 1em;
            }
        
            a:link, a:visited {
                color: inherit;
                text-decoration: none;
            }
            .noprint {
                display: none;
            }
        }
	@media screen {
	    .grey, .grey a:link, .grey a:visited {
		color: #777;
	    }
            .docinfo {
                background-color: #EEE;
            }
            .top {
                border-top: 7px solid #EEE;
            }
            .bgwhite  { background-color: white; }
            .bgred    { background-color: #F44; }
            .bggrey   { background-color: #666; }
            .bgbrown  { background-color: #840; }            
            .bgorange { background-color: #FA0; }
            .bgyellow { background-color: #EE0; }
            .bgmagenta{ background-color: #F4F; }
            .bgblue   { background-color: #66F; }
            .bgcyan   { background-color: #4DD; }
            .bggreen  { background-color: #4F4; }

            .legend   { font-size: 90%; }
            .cplate   { font-size: 70%; border: solid grey 1px; }
	}
    </style>
    <!--[if IE]>
    <style>
    body {
       font-size: 13px;
       margin: 10px 10px;
    }
    </style>
    <![endif]-->';

    return $txt_css;  
   }

/***********************************************************/
/*get_javascript : Gets the javascript for the header      */
/***********************************************************/
     function get_javascript(){
    $txt_javascript = '    <script type="text/javascript"><!--
    function addHeaderTags() {
        var spans = document.getElementsByTagName("span");
        for (var i=0; i < spans.length; i++) {
            var elem = spans[i];
            if (elem) {
                var level = elem.getAttribute("class");
                if (level == "h1" || level == "h2" || level == "h3" || level == "h4" || level == "h5" || level == "h6") {
                    elem.innerHTML = "<"+level+">"+elem.innerHTML+"</"+level+">";               
                }
            }
        }
    }
    var legend_html = "Colour legend:<br />      <table>         <tr><td>Unknown:</td>                   <td><span class=\'cplate bgwhite\'>&nbsp;&nbsp;&nbsp;&nbsp;</span></td></tr>         <tr><td>Draft:</td>                     <td><span class=\'cplate bgred\'>&nbsp;&nbsp;&nbsp;&nbsp;</span></td></tr>         <tr><td>Informational:</td>             <td><span class=\'cplate bgorange\'>&nbsp;&nbsp;&nbsp;&nbsp;</span></td></tr>         <tr><td>Experimental:</td>              <td><span class=\'cplate bgyellow\'>&nbsp;&nbsp;&nbsp;&nbsp;</span></td></tr>         <tr><td>Best Common Practice:</td>      <td><span class=\'cplate bgmagenta\'>&nbsp;&nbsp;&nbsp;&nbsp;</span></td></tr>         <tr><td>Proposed Standard:</td>         <td><span class=\'cplate bgblue\'>&nbsp;&nbsp;&nbsp;&nbsp;</span></td></tr>         <tr><td>Draft Standard (old designation):</td> <td><span class=\'cplate bgcyan\'>&nbsp;&nbsp;&nbsp;&nbsp;</span></td></tr>         <tr><td>Internet Standard:</td>         <td><span class=\'cplate bggreen\'>&nbsp;&nbsp;&nbsp;&nbsp;</span></td></tr>         <tr><td>Historic:</td>                  <td><span class=\'cplate bggrey\'>&nbsp;&nbsp;&nbsp;&nbsp;</span></td></tr>         <tr><td>Obsolete:</td>                  <td><span class=\'cplate bgbrown\'>&nbsp;&nbsp;&nbsp;&nbsp;</span></td></tr>     </table>";
    function showElem(id) {
        var elem = document.getElementById(id);
        elem.innerHTML = eval(id+"_html");
        elem.style.visibility=\'visible\';
    }
    function hideElem(id) {
        var elem = document.getElementById(id);
        elem.style.visibility=\'hidden\';        
        elem.innerHTML = "";
    }
    // -->
    </script>';


     return $txt_javascript;
     }

/*********************************************************************************/
/*get_error_remaning : Gets the remaining HTML for non existing data             */
/*********************************************************************************/
   function get_error_remaining($rfc_data,$display,$in_num,$case){
    global $debug_html_header;
    $error_var = '';

    $error_var .= '<span class="pre noprint docinfo">';

   //Get the length of the variable first line entries
   
    $error_first_chars = 0;

    $error_first_line = '[<a href="https://www.rfc-editor.org" title="RFC Editor">RFC Home</a>]';

    $error_first_chars = 0 + 11; // For [RFC Home] and a space

    $first_error_pad_line = get_padded_data($error_first_chars);
    $error_var .= $error_first_line.$first_error_pad_line.'<br/>';
 

    $error_second_chars = 1;
    $second_error_pad_line = get_padded_data($error_second_chars);
   
    $error_var .= $error_second_line.$second_error_pad_line.'<br/>';
 
    $error_third_chars = 1;
    $third_error_pad_line = get_padded_data($error_third_chars);
   
    $error_var .= $error_third_line.$third_error_pad_line.'<br/>';
 

    $error_fourth_chars = 1;
    $fourth_error_pad_line = get_padded_data($error_fourth_chars);
   
    $error_var .= $error_fourth_line.$second_error_pad_line.'</span><br/>';
 

     return $error_var;
   }
/***********************************************************/
/*get_html_remaning : Gets the remaining HTML              */
/***********************************************************/

    function get_html_remaining($in_data,$display,$in_num,$in_case){
    global $debug_html_header;
   
    $html_var = '';
    $url = '';
 
    $formats = explode(',',$in_data['FORMAT']);
    $num_formats = count($formats);
    $prefix = strtolower(substr($in_data['DOC-ID'],0,3));
    $number = substr($in_data['DOC-ID'],3);

    /*Hack for arranging the formats in the form ASCII, PDF, PS*/
    if (($num_formats == '3') && (trim($formats[1]) == 'PS')){
        $formats= Array('ASCII','PDF','PS');
    }
    $format_length = 0;
    switch ($formats[0]) {
       case '':
            $url .= "<br />This file is not available online. Please contact the RFC Editor if you have a copy of this RFC.";
            break;
       default:
        #    $url .= "<br />";
           
            $count = 0;
           
            foreach ($formats as $format) {
               $trimed_format = trim($format);
               
               $furl = generate_url_by_format($in_data['DOC-ID'], $trimed_format);
               if ($trimed_format == 'ASCII'){
                   if ($in_case == '1'){ // This is sebseries one-to-many case
                      $curl = generate_canonical_url($in_data['DOC-ID'], $in_data['FORMAT']);
                      $url = '<a href="' . htmlspecialchars($curl) . '">TEXT</a>';
                      $format_length = 4; //Length of TEXT 
                   }else{
                      $curl = generate_canonical_url($in_data['DOC-ID'], $in_data['FORMAT']);
                      $url = '<a href="' . htmlspecialchars($curl) . '">TEXT</a>';
                      $url .= '|<a href="' . htmlspecialchars($furl) . '">PDF</a>';
                      $format_length = $format_length + 7 + 1; //Length of TEXT AND PDF AND in between |
                   }
               }elseif ($trimed_format == 'PDF'){
                   $url .= '<a href="' . htmlspecialchars($furl) . '">PDF</a>';
                   $format_length = $format_length + 3 ; //Length of PDF
               }elseif ($trimed_format == 'PS'){
                   $url .= '<a href="' . htmlspecialchars($furl) . '">PS</a>';
                   $format_length = $format_length + 2 ; //Length of PS
               }elseif ($trimed_format == 'TXT'){
                   $curl = generate_canonical_url($in_data['DOC-ID'], $in_data['FORMAT']);
                   $url = '<a href="' . htmlspecialchars($curl) . '">TEXT</a>';
                   $format_length = $format_length + 4 ; //Length of TEXT
               }elseif ($trimed_format == 'XML'){ 
                   $url .= '<a href="' . htmlspecialchars($furl) .'">XML</a>';
                   $format_length = $format_length + 3 ; //Length of XML
               }elseif ($trimed_format == 'HTML'){
                   $url .= '<a href="' . htmlspecialchars($furl) . '">HTML</a>';
                   $format_length = $format_length + 4 ; //Length of HTML
               }elseif ($trimed_format == 'v3PDF'){
                   $url .= '<a href="' . htmlspecialchars($furl) . '">v3PDF</a>';
                   $format_length = $format_length + 5 ; //Length of v3PDF
               }
              
               $count++;
               if ($count < $num_formats) {
                  $url .= '|';
                  $format_length = $format_length + 1;
                  $herecount++;
               } else {
                 $url .= '';
               }
           }
           break;
     }

    $format_length = $format_length + 2; // For [ and ] so format will be [ASCII|PDF]
  
   $html_var = '';
   $html_var .= '<span class="pre noprint docinfo">';

   //Get the length of the variable first line entries
   
   $html_first_chars = 0;

   $html_first_line = '[<a href="https://www.rfc-editor.org" title="RFC Editor">RFC Home</a>]';
   $html_first_line .= ' ['.$url.'] ';

   if ($display == 'rfc'){
       $html_first_line .= '[<a href=\'https://datatracker.ietf.org/doc/' . htmlspecialchars($display.$in_num) . '\' title=\'IETF Datatracker information for this document\'>Tracker</a>] [<a href="https://datatracker.ietf.org/ipr/search/?rfc=' . htmlspecialchars($in_num) . '&amp;submit=' . htmlspecialchars($display) . '" title="IPR disclosures related to this document">IPR</a>]';
   }

   $html_first_chars = 0 + 11; // For [RFC Home] and a space
   $html_first_chars = $html_first_chars + $format_length; // For Formats such as [ASCII|PDF]

   if ($display == 'rfc'){
      $html_first_chars = $html_first_chars + 10; // For [Tracker] and a space
      $html_first_chars = $html_first_chars + 6; //For [IPR] and a space
   } else {
      $html_first_chars = $html_first_chars + 1;
      }

   if ($display == 'rfc'){
   $errata_txt = "";
   $errata_txt = get_errata_entry($in_data['DOC-ID']);
   if ($errata_txt != ""){
      $html_first_line .= ' ['.$errata_txt.']';
      $html_first_chars = $html_first_chars + 9; // For [Errata] and a space
   }
   }

   if ($display == 'rfc'){
      $info_page_text = '[<a href=\'https://www.rfc-editor.org/info/' . htmlspecialchars($display.$in_num) . '\' title=\'Info page\'>Info page</a>]';
      $html_first_line .= ' '.$info_page_text;
      $html_first_chars = $html_first_chars + 12; // For [Info page] and a space
   }

   $first_pad_line = get_padded_data($html_first_chars);
   $html_var .= $html_first_line.$first_pad_line.'</span><br/>';

   
   //Get the Second line of the HTML
   $html_second_var = '';
   $html_second_var = '<span class="pre noprint docinfo">';
   $html_second_chars = 0;
   $second_pad_line = get_padded_data($html_second_chars);
   $html_var .= $html_second_var.$second_pad_line.'</span>'.'<br />';

   $in_data['OBSOLETED-BY'] = trim($in_data['OBSOLETED-BY']); 
   $in_data['UPDATED-BY'] = trim($in_data['UPDATED-BY']);

   $status_len = strlen($in_data['STATUS']);

   $obsoleted_by_txt = "";
   $updated_by_txt = "";
   $multiline_obsoleted_txt = "";
   $multiline_updated_txt = "";   
   $obsoleted_pad_count = 0;
   $updated_pad_count = 0;

/*Assumptions the multiline obsolete and updated txt will have value only when its more than 61 chars*/
  /*Get the obsoleted by value*/
  if (($in_data['OBSOLETED-BY'] != null) || ($in_data['OBSOLETED-BY'] != "")){
       $obsoleted_txt_len = strlen($in_data['OBSOLETED-BY']);
       if ($obsoleted_txt_len <= 61){#This case where obsoleted by is within range so updated by value goes for normal
            list($obsoleted_by_txt,$obsoleted_length)= get_more_info_entry($in_data['OBSOLETED-BY']);
            $obsoleted_by_txt = 'Obsoleted by: '.$obsoleted_by_txt;
            $obsoleted_pad_count = $obsoleted_length + 14; //For length of Obsoleted by: and a space
       }elseif ($obsoleted_txt_len > 61){#Case where OBSOLETED BY has more values
             $multiline_obsoleted_txt = "";
             $obsoleted_txt_array = str_split($in_data['OBSOLETED-BY'],62);
             $obsoleted_txt_array_count = count($obsoleted_txt_array);
            for ($i = 0 ; $i <= $obsoleted_txt_array_count; $i++){
                  if (substr($obsoleted_txt_array[$i],-1) == ','){
                        $obsoleted_txt_array[$i] = rtrim($obsoleted_txt_array[$i],","); 
                  }
            }
            for ($i = 0 ; $i <= $obsoleted_txt_array_count; $i++){
                  if (substr($obsoleted_txt_array[$i],0,1) == ','){
                      $obsoleted_txt_array[$i] = ltrim($obsoleted_txt_array[$i],","); 
                  }
            }
            for ($i = 0 ; $i < $obsoleted_txt_array_count; $i++){
            list($obsoleted_by_txt,$obsoleted_length) = get_more_info_entry($obsoleted_txt_array[$i]);
                if ($multiline_obsoleted_txt == ""){
                    $multiline_obsoleted_txt = 'Obsoleted by: '.$obsoleted_by_txt.$in_data['STATUS'] ;
                }else{
                     $multiline_obsoleted_txt .= "<br>"."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$obsoleted_by_txt;
                }  
            }
       }
   }
       
   /*Now get the updated by value*/  
   if (($in_data['UPDATED-BY'] != "") OR ($in_data['UPDATED-BY'] != null)){
       $updated_txt_len = strlen($in_data['UPDATED-BY']);
       if ($updated_txt_len <= 61){
           list($updated_by_txt,$update_length) = get_more_info_entry($in_data[ 'UPDATED-BY']);
           $update_by_txt = 'Updated by: '.$updated_by_txt;
           $updated_pad_count = $update_length + 12; //For length of Updated by: and a space
       }elseif ($updated_txt_len > 61) {#CASE where updated by is mroe than 61 chars
            $multiline_updated_txt = "";
            $in_data['UPDATED-BY']=trim($in_data['UPDATED-BY']);
            $updated_data = explode(',',$in_data['UPDATED-BY']);
            $update_array_count =  count($updated_data);
            $update_multiline_dev =  round($update_array_count / 7);
            $update_multiline_mod = ($update_array_count %7 );
            $update_flat_string = "";

            for ($u=0;$u<=$update_multiline_dev;$u++){
                if ($u == 0){
                  $k = 0;
                }else {
                   $k = $k + 7;
                }
                $update_string_array[$u] = array_slice($updated_data,$k,7);
            }

           for ($x=0; $x< $update_multiline_dev;$x++){
               for ($y=0;$y< 7;$y++){
                 if (($update_string_array[$x][$y] != '') OR ($update_string_array[$x][$y] != null)){
                     if ($y != 6) {
                               $update_flat_string .= $update_string_array[$x][$y].',';
                     }else {
                        if ($x != $update_multiline_dev -1){
                           $update_flat_string .= $update_string_array[$x][$y].'NEW';
                       }else {
                        $update_flat_string .= $update_string_array[$x][$y];
                        }
                     }
                 }
               }
           }

            if (($update_multiline_dev) == 1 and ($update_multiline_mod ==1)){/*Special arrangement for only one element in the second row*/
                           $update_flat_string .= 'NEW'.$update_string_array[1][0];
            }
            if ($update_multiline_mod != 0){
               $update_flat_string = rtrim($update_flat_string,",");
            }

            $updated_txt_array = explode('NEW',$update_flat_string);
            if ($update_multiline_mod != 0){
            }
            $updated_txt_array_count = count($updated_txt_array);
            
            for ($i = 0 ; $i < $updated_txt_array_count; $i++){
            list($updated_by_txt,$update_length) = get_more_info_entry($updated_txt_array[$i]);
               if ($multiline_updated_txt == ""){
                      $updated_pad_count = $update_length + 12; //For length of Updated by: and a space
                       #Check if obsoleted by is empty then add status to first line of multiline Updated by
                       if ($obsoleted_by_txt == ""){
                            $remaining_max_length = 72 - $status_len; 
                            $html_update_filler_num = $remaining_max_length - $updated_pad_count;
                            $html_update_filler_num = $html_update_filler_num -1; /*This is for the "," added for multiline*/ 
                            for ($j=0;$j< $html_update_filler_num;$j++){
                                $html_update_filler_pad .= " "; 
                            } 
                            $multiline_updated_txt = 'Updated by: '.$updated_by_txt.','.$html_update_filler_pad.$in_data['STATUS'];
                       }else {
                            $html_update_filler_num = 72 - $updated_pad_count;
                            $html_update_filler_num = $html_update_filler_num -1; /*This is for the "," added for multiline*/ 
                            for ($n=0;$n< $html_update_filler_num;$n++){
                                $html_update_filler_pad .= " "; 
                            } 
                           $multiline_updated_txt = 'Updated by: '.$updated_by_txt.','.$html_update_filler_pad;
                       }
                 }else{
                     $update_start_filler_pad = '';
                     $updated_pad_count = 12; //For length of Updated by: and a space
                     for ($k=0;$k< $updated_pad_count;$k++){
                                $update_start_filler_pad .= " "; 
                     } 
 
                     if ($i == ($updated_txt_array_count - 1)){
                         if ($errata_txt != ''){
                            $errata_length = 12; //The length of string Errata Exist
                         }else {
                            $errata_length = 0;
                         }

                         $update_used_length =$updated_pad_count + $update_length +$errata_length;
                         $update_end_remaining = 72 - $update_used_length;/*This gives us the remaning space from 72*/
                         $update_end_pad_count = $update_end_remaining; /*From which remove the Errata exist length*/
                         $update_end_filler_pad = '';  
                         for ($l=0;$l< $update_end_pad_count;$l++){
                                $update_end_filler_pad .= " "; 
                         }

                         if ($errata_txt != ''){
                             $multiline_updated_txt .= "<br>".$update_start_filler_pad.$updated_by_txt.$update_end_filler_pad.'<span style=\'color: #C00;\'>Errata Exist</span>';
                          }else {
                             $multiline_updated_txt .= "<br>".$update_start_filler_pad.$updated_by_txt.$update_end_filler_pad;
                          }

                    }else {
                         $update_used_length =$updated_pad_count + $update_length;
                         $update_end_remaining = 72 - $update_used_length;/*This gives us the remaning space from 72*/
                         $update_end_pad_count = $update_end_remaining - 1;/*This -1 is for the "," added to multiline text at the end*/
                         
                          $update_end_filler_pad = '';  
                          for ($m=0;$m< $update_end_pad_count;$m++){
                                $update_end_filler_pad .= " "; 
                          }

                         $multiline_updated_txt .= "<br>".$update_start_filler_pad.$updated_by_txt.','.$update_end_filler_pad;
                      }
                  }
            }
       }
   }

/*Final adjustment considering 4 conditions*/
/*1. Obsoleted by and Updated by more than 61 chars*/
/*2. Obsoleted by more than 61 chars and Updated by less than 61 chars*/
/*1. Obsoleted by less than 61 chars and Updated by more than 61 chars*/
/*1. Obsoleted by less than 61 chars and Updated by less than 61 chars*/

   if ($multiline_obsoleted_txt != ""){ #Obsoleted by is more than 61 chars
      if ( $multiline_updated_txt != ""){ #Updated By is more than 61 chars
       #1.Print multiline obsoleted by text + status add to $html_var
       $html_var .= '<span class="pre noprint docinfo">'.$multiline_obsoleted_txt.'</span><br />';
       #2.Print multiline updated by text + Errata status add to html_var
       $html_var .= '<span class="pre noprint docinfo">'.$multiline_updated_txt.'</span><br />';
      }elseif ($multiline_updated_txt == ""){#Updated By is less than 61 chars
       #1.Print multiline obsoleted by text + status add to $html_var
       $html_var .= '<span class="pre noprint docinfo">'.$multiline_obsoleted_txt.'</span><br />';
       #2.Print single line updated by text + Errata status add to html_var
       $errata_length = 12; //The length of string Errata Exist
       if ($errata_txt != ""){
        $remaining_max_length = 72 - $errata_length;//Get the max length by excluding errata length
        $html_fourth_filler_num = $remaining_max_length - $updated_pad_count;
        for ($i=0;$i< $html_fourth_filler_num;$i++){
            $html_fourth_filler_pad .= " "; 
        }
        $html_var .= '<span class="pre noprint docinfo">'.$update_by_txt.$html_fourth_filler_pad.'<span style=\'color: #C00;\'>Errata Exist</span></span>';
        }else {
            $html_fourth_filler_num = 72 - $updated_pad_count; //Now no errata so take max value
            for ($i=0;$i< $html_fourth_filler_num;$i++){
               $html_fourth_filler_pad .= " "; 
            }
            $html_var .= '<span class="pre noprint docinfo">'.$update_by_txt.$html_fourth_filler_pad.'</span>';
        }  
      }
   }elseif ($multiline_obsoleted_txt == ""){#Obsoleted by is less than 61 chars
     if ( $multiline_updated_txt != ""){ #Updated By is more than 61 chars
       #1.Print single line obsoleted by text + status add to $html_var
          $status_len = strlen($in_data['STATUS']);
          $remaining_max_length = 72 - $status_len;   
          $html_third_filler_num = $remaining_max_length - $obsoleted_pad_count;
	  $html_third_filler_pad = str_repeat(" ", $html_third_filler_num);

          if ($obsoleted_by_txt != ""){
          $html_var .= '<span class="pre noprint docinfo">'.$obsoleted_by_txt.$html_third_filler_pad.$in_data['STATUS'].'</span><br />';
          }
       #2.Print multiline line updated by text + Errata status add to html_var
          $html_var .= '<span class="pre noprint docinfo">'.$multiline_updated_txt.'</span><br />';
      }elseif ($multiline_updated_txt == ""){#Updated By is less than 61 chars
        #1.Print single line obsoleted by text + status add to $html_var
          $status_len = strlen($in_data['STATUS']);
          $remaining_max_length = 72 - $status_len;   
          $html_third_filler_num = $remaining_max_length - $obsoleted_pad_count;
	  $html_third_filler_pad = str_repeat(" ", $html_third_filler_num);

	  $html_var .= '<span class="pre noprint docinfo">'.$obsoleted_by_txt.$html_third_filler_pad.$in_data['STATUS'].'</span><br />';
        #2.Print single line updated by text + Errata status add to html_var
          $errata_length = 12; //The length of string Errata Exist
          if ($errata_txt != ""){
             $remaining_max_length = 72 - $errata_length;//Get the max length by excluding errata length
             $html_fourth_filler_num = $remaining_max_length - $updated_pad_count;
	     $html_fourth_filler_pad = str_repeat(" ", $html_fourth_filler_num);

             $html_var .= '<span class="pre noprint docinfo">'.$update_by_txt.$html_fourth_filler_pad.'<span style=\'color: #C00;\'>Errata Exist</span></span>';
          }else {
               $html_fourth_filler_num = 72 - $updated_pad_count; //Now no errata so take max value
               for ($i=0;$i< $html_fourth_filler_num;$i++){
                  $html_fourth_filler_pad .= " "; 
               }
               $html_var .= '<span class="pre noprint docinfo">'.$update_by_txt.$html_fourth_filler_pad.'</span>';
           } 
      }
   }


 return $html_var;




  }

/****************************************************************************************/
/*get_padded_data : Gets the padded data for given input character for the specific line*/
/****************************************************************************************/

function get_padded_data($in_chars){
global $debug_html_header;

   $html_pad_value = 0;
   $html_pad_line = '';
   if ($debug_html_header === TRUE) {
          print("<h4>get_padded_data</h4><pre>\n");
          var_dump($in_chars);
          print("</pre>\n");
   }
   $html_pad_value = 72 - $in_chars;

   for ($i=0; $i < $html_pad_value; $i++){
        $html_pad_line .= " ";
   }
 /* 
   print "The in chars value is".$in_chars;
   print "The padded max value is ".$html_pad_value;
  */
   return $html_pad_line;
}

/****************************************************************************************/
/*generate_url_by_format : Formats the URL                                              */
/****************************************************************************************/
function generate_url_by_format($docid, $format="PLAINTEXT") {
     global $debug_html_header;
     global $document_root;

     $url = $document_root."/";
     $prefix = strtolower(substr($docid,0,3));
     $number = substr($docid,3);

     $v3 = 'v3';

     switch ($format) {
     case 'ASCII':
          // Treat this as a PDF of the plain text RFC file
          $url .= sprintf("%s/pdfrfc/%s%d.txt.pdf", $prefix, $prefix, $number);
          break;
     case 'ASCII, PDF':
          // Treat this as a PDF of the plain text RFC file
          $url .= sprintf("%s/pdfrfc/%s%d.txt.pdf", $prefix, $prefix, $number);
          break;

     case 'PDF':
     case 'v3PDF':
          // For older RFCs, this is the only copy online; for others, it is
          // the "Enhanced," illustrated version.
          $url .= sprintf("%s/%s%d.pdf", $prefix, $prefix, $number);
          break;
     case 'PS':
          // This is a version in the PostScript printer language
          $url .= sprintf("%s/%s%d.ps", $prefix, $prefix, $number);
          break;
     case 'TXT':
          // New file format TXT with UTF-8 characters
          #$number = $number.'v3';
          $url .= sprintf("%s/pdfrfc/%s%d.txt", $prefix, $prefix, $number);
          break; 
     case 'XML':
          // This is XML version of RFC
          #$url .= sprintf("%s/%s%d%s.xml", $prefix, $prefix, $number, $v3);
           $url .= sprintf("%s/%s%d.xml", $prefix, $prefix, $number);
           break; 
    case 'HTML':
          // This is HTML version of RFC
          #$url .= sprintf("%s/%s%d%s.html", $prefix, $prefix, $number, $v3);
          $url .= sprintf("%s/%s%d.html", $prefix, $prefix, $number);
          break;
/*TODO   case 'v3PDF':
          // This is v3 PDF version of RFC
          $url .= sprintf("%s/%s%d%s.pdf", $prefix, $prefix, $number, $v3);
          break;
*/
     case 'PLAINTEXT':
          // fallthrough
     default:
          // This is the default and actual canonical version for most RFCs.
          $url .= sprintf("%s/%s%d.txt", $prefix, $prefix, $number);
          break;
     }

     if ($debug_html_header === TRUE) {
          print("<h4>generate_url_by_format</h4><pre>\n");
          var_dump($docid);
          var_dump($format);
          var_dump($prefix);
          var_dump($number);
          var_dump($url);
          print("</pre>\n");
     }
     return $url;
}

function generate_canonical_url($docid, $format_csv) {
     global $debug_html_header;
     

     $curl = NULL;
     if (strcmp(trim($format_csv),"PDF") == 0) {
          $curl = generate_url_by_format($docid,"PDF");
     } else {
          $curl = generate_url_by_format($docid); // get the PLAINTEXT version
     }
     return $curl;
}

/*******************************************************************************************/
/*get_errata_entry : Checks if the errata exist for the given RFC                          */
/*******************************************************************************************/
function get_errata_entry($in_doc_id){
    global $debug_html_header;
    global $document_root;
    global $pdo;

    $errata_txt = "";
    
    
    list($rfc_name,$rfc_number,$length) = split_rfc($in_doc_id);
    $rfc_name = strtolower($rfc_name);
    $errata_http_base_url = $document_root.'/errata';

    
    try { 
        $errata_query = "SELECT * from `errata` where `DOC-ID` = :in_doc_id";
        $stmt = $pdo->prepare($errata_query);
        $stmt->bindParam('in_doc_id',$in_doc_id);
        $stmt->execute();
        $num_of_rows = $stmt->rowCount();
     } catch (PDOException $pe){
         error_log("Error processing :  get_errata_entry", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }

     if ($num_of_rows > 0){  
         $return_errata_count = $num_of_rows;
     }
     #If the RFC has and errata entry
     if ($return_errata_count > 0){
         $errata_http_url = $errata_http_base_url."/"."$rfc_name$rfc_number";
         $errata_txt = '<a class="boldtext" href="'. $errata_http_url .'" target="_blank">';
         $errata_txt.= "Errata</a>";
     }
     else{
 	 $errata_txt = "";
     }

     if ($debug_html_header){
         print "The errata txt is.". $errata_txt ."\n";
     }

     return $errata_txt;

 }
/********************************************************************************************/
/*get_more_info_entry : Get the more information for given value*/
/********************************************************************************************/

function get_more_info_entry($in_data,$format=""){
    global $debug_html_header;
    global $document_root;

    $out_data_txt = "";
    $http_in_data_base_url = $document_root.'/rfc/';

    $in_data_array = explode(",",$in_data);
    $count = count($in_data_array);
    $i = 0;
    $out_number_len = 0;
    foreach ($in_data_array as $key=>$value){
        $value = trim($value);
        $i++;
        list($in_data_name,$in_data_number,$length) = split_rfc($value);
        $in_data_name_link = strtolower($in_data_name);
        $http_in_data_name = sprintf('%d',$in_data_number);
        $http_number_length = strlen($http_in_data_name);
        $in_data_info_name = sprintf('%s%d',$in_data_name_link,$in_data_number);
        if ($i == $count){
             if ($format != "") { #TODO removed the format code from here if we decide to stick with rfcxxx format for rfc html files instead of rfcxxxx.html
                 $http_in_data_url = $http_in_data_base_url.$in_data_info_name.'.'.$format;   
             }else{
                 $http_in_data_url = $http_in_data_base_url.$in_data_info_name;                                  
             }
             $format_in_data  = '<a href="' .$http_in_data_url. '" target="_blank">';
             $format_in_data .= "$http_in_data_name"."</a>";
             if (preg_match('/^(rfc|RFC|bcp|BCP|fyi|FYI|std|STD)/',$value)){             
                 $out_data_txt .= $format_in_data;           
             }
             else {
                 $out_data_txt .= $http_in_data_name;        
             }
             $out_number_len =  $out_number_len + $http_number_length;
        }else {
                 if ($format != "") {
                     $http_in_data_url = $http_in_data_base_url.$in_data_info_name.'.'.$format;   
                 }else{
                     $http_in_data_url = $http_in_data_base_url.$in_data_info_name;                                  
                 }
                 $format_in_data  = '<a href="' . $http_in_data_url . '" target="_blank">';
                 $format_in_data .= "$http_in_data_name"."</a>";
                 if (preg_match('/^(rfc|RFC|bcp|BCP|fyi|FYI|std|STD)/',$value)){             
                      $out_data_txt .= $format_in_data.", ";      
                 }
                 else {
                       $out_data_txt .= $http_in_data_name.", ";           
                      }

		 $out_number_len = $out_number_len + $http_number_length + 2 ; //For number and a comma value
	}

        #print 'My out number is'.$out_number_len;
    }   
        
    if ($debug_html_header){
        print "The more info anchor is.". $out_data_txt ."\n";
    }

    return array($out_data_txt,$out_number_len);

}


function check_for_subseries($in_display,$in_doc_id,$in_number){
    global $debug_html_header;

    $case_flag = 0; 
    if ($in_display != 'rfc'){
        list ($return_data,$case_flag) = get_subseries_data($in_doc_id);

    }else {
        list ($return_data,$case_flag) = get_data($in_doc_id);
    }
    return array($return_data,$case_flag);
}

/***********************************************************/
/*get_data : Gets the data for the input values            */
/***********************************************************/
   function get_data($doc_id){
      global $debug_html_header,$pdo;
      $case = 0;

      try { 
          $query = "SELECT `DOC-ID`,`AUTHORS`,`PUB-DATE`,`FORMAT`,`STATUS`,`TITLE`,`OBSOLETES`,`OBSOLETED-BY`,`UPDATED-BY`,`DOI` from `index` where `DOC-ID` =:doc_id";
          $stmt = $pdo->prepare($query);
          $stmt->bindParam('doc_id',$doc_id);
          $stmt->execute();
          $num_of_rows = $stmt->rowCount();
     } catch (PDOException $pe){
         error_log("Error processing :  get_data", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }

     if ($debug_html_header === TRUE) {
         print("<pre>\n");
         var_dump($query);
         print("</pre>\n");
     }

    
     if ($num_of_rows > 0){
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
     }
     return array($row,$case);
}
/***********************************************************/
/*get_data : Gets the data for the input values            */
/***********************************************************/
function get_subseries_data($doc_id){
      global $debug_html_header,$pdo;
      
      $case = 1;
      
      try {
          $query = "SELECT `DOC-ID`,`AUTHORS`,`PUB-DATE`,`FORMAT`,`STATUS`,`TITLE`,`OBSOLETES`,`OBSOLETED-BY`,`UPDATED-BY`,`DOI` from `index` where `DOC-ID` =:doc_id";
          $stmt = $pdo->prepare($query);
          $stmt->bindParam('doc_id',$doc_id);
          $stmt->execute();
          $num_of_rows = $stmt->rowCount();
     } catch (PDOException $pe){
         error_log("Error processing :  get_subseries_data", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }

     
     if ($debug_html_header === TRUE) {
         print("<pre>\n");
         var_dump($query);
         print("</pre>\n");
      }

      $row_count = $num_of_rows;


      if ($row_count > 0) {
          $row = $stmt->fetch(PDO::FETCH_ASSOC); //This is a one-to-many subseries case so we want this to show formats in the header display
      } else {

             try {
                 $see_also_query = "SELECT `DOC-ID`,`AUTHORS`,`DATE_RECEIVED`,`PUB-DATE`,`FORMAT`,`STATUS`,`TITLE`,`OBSOLETES`,`OBSOLETED-BY`,`UPDATED-BY`,`DOI` from `index` where `SEE-ALSO` =:doc_id";
                 $stmt = $pdo->prepare($see_also_query);
                 $stmt->bindParam('doc_id',$doc_id);
                 $stmt->execute();
                 $num_of_rows = $stmt->rowCount();
            } catch (PDOException $pe){
                 error_log("Error processing :  get_subseries_data : see-also-query", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
            }
  
            if ($num_of_rows){
               $row = $stmt->fetch(PDO::FETCH_ASSOC); 
               $case = 2; //This is a one-to-one subseries case so we want this to show formats in the header display
            }

    }

    return array($row,$case);
}

/***********************************************************/
/*split_rfc : Splits the rfc number into value and number  */
/***********************************************************/
 function split_rfc($in_value){

         global $debug_html_header, $debug_add_header;
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

         if ($debug_add_header){
                 print "Out name is :".$out_name."\n";
             print "Out number is :".$out_number."\n";
             print "Length is :".$length."\n";
         }

         return array($out_name, $out_number, $length);
}    
?>

