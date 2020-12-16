<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
/*
 * Script to process the stored data into the "meta" data page for a RFC
 * document. While many of the routines are similar or the same as those found
 * in the Errata System, because this application is logically different, it is
 * treated as separate even though both applications "live" in the same
 * directory on the web server.
 *
 * The exception to the above is the included file "db.php." This file is
 * shared because the database is shared.
 *
 * Note: The initial deployment of the script uses an Apache rewrite rule:
 *
 *       RewriteRule ^/info/rfc([0-9]+)$     /rfcmeta.php?rfc=$1
 *
 * The rule has the web server call the script for the given URL pattern; the
 * script is not called directly.
 *
 * Updated to change STANDARD to INTERNET STANDARD (Jan. 2013) - AR
 * April 2017 Updates : Added the redirect link for Errata Id and RFC number - PN
 * July 2017 : Added case to show page for non existing RFC or subseries number -PN
 * August 2017 : Modified the Other action links to show View Errata for existing Errata and Submit Errata for No errata - PN 
 * Suptember 2017 : Added special format handling case for RFC8187, RFC8264, RFC8265, RFC8266 as per Sandy G. request - PN 
 * Suptember 2017 : Removed special format handling case for RFC8187, RFC8264, RFC8265, RFC8266 as per Sandy G. request - PN
 * October 2017 : Added special format handling case for RFC8187, RFC8264, RFC8265, RFC8266 -PN 
 * June 2018 : Corrected multiparagraph abstract display -PN 
 * May 2019 : Modified file formats part to handle the new Publication formats like XML, HTML, v3PDF, Text with ASCII - PN
 * June 2019 : Updated the script for the new images for PDF, PDF with images and added alt for all formats. - PN
 * August 2019 : Updated the script to handle the special display for RFC 32. - PN 
 * September 2019 : Commented the cononical URL - PN
 * October 2019 : Added the missing dot in the url string concatenation for type TEXT - PN
 * February 2020 : Added the inline errata format(if available) to the format list - PN
 * March 20202 : Added the icon for inline errata format - PN
 * November 2020 : Modified the script to use PDO prepared statements - PN            
 * December 2020 : Modified the the link for the definition of Stream from RFC 4844 to RFC 8729 - PN            
*/
include_once("db_connect.php");
include_once("core_lib.php");
include_once("rfc_subseries_lib.php");
include_once("config.php");
$debug_rmeta = FALSE;

define("MAX_CID",5); // Trim the input string to just five digits.

  define('WP_USE_THEMES', false);
  //require('/a/wordpress/wp-blog-header.php');
  require_once ('/a/wordpress/wp-load.php');

/*
 * Write a header for non existing RFC/Subseries number
 */
function no_rfc_header(){
    global $rfcnum;
    global $display;
    global $subseries_num;

//Add title specifying the dynamic page
add_filter( 'wp_title', 'wp_title_no_rfc_header', 10, 3 );

function wp_title_no_rfc_header( $title, $sep, $seplocation ) {
    global $rfcnum;
    global $display;
    global $subseries_num;
 
    if ($display == 'RFC'){
       return 'RFC not found &raquo; RFC Editor';
    }
    else {
       return $display.' not found &raquo; RFC Editor';

    }
}
//Add body class constant to avoid error404 body class for Wordpress outside page

function add_body_class_function($classes){

return array ('rfcmeta');
}

add_action('body_class','add_body_class_function');


get_header();
?>

 <div id="content" class="nosidebar">
     <h1 class="post_title"></h1>
     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">
<?php
}

/*
 * Write a standard header for the display page.
 */
function rfcmeta_header($rfcnum,$display) {
    // Include WordPress

    add_filter( 'wp_title', 'wp_title_rfcmeta', 10, 3 );

    function wp_title_rfcmeta( $title, $sep, $seplocation ) {
    global $rfcnum;
    global $display;
    global $subseries_num;
 
    if ($display == 'RFC'){
    return 'Information on '.$display.' '.$rfcnum. ' &raquo; RFC Editor';
    }
    else {
    return 'Information on '.$display.' '.$subseries_num. ' &raquo; RFC Editor';

    }
    }
    global $meta_in_data;
    if ($display == 'RFC'){/*Build the meta tag*/
	$meta_in_data = get_rfcmeta_data($rfcnum);
#	$meta = generate_meta_data($in_data);
        add_action('wp_head','generate_meta_data');
    }
    /*else {
	Handle the other cases not conidered now
    }*/
 
 //Add body class constant to avoid error404 body class for Wordpress outside page

function add_body_class_function($classes){

return array ('rfcmeta');
}

add_action('body_class','add_body_class_function');


    get_header();
 #   get_sidebar(left);
?>
 <div id="content" class="nosidebar">
     <h1 class="post_title"></h1>

     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">
<?php    
}

/*
*Generate the Meta tags for the document
*/
function generate_meta_data(){

global $debug_rmeta;
global $meta_in_data;

       	$meta_title = $meta_in_data['title'];
        $meta_title = htmlspecialchars($meta_title,ENT_QUOTES);
        $meta_in_data['authors'] = preg_replace('/, Ed\./', '; Ed.', $meta_in_data['authors']);
	$authors_list = $meta_in_data['authors'];
	$pub_date = date("Y" ,strtotime($meta_in_data['pub-date']));
        $trim_format = trim($meta_in_data['format']);  
        $curl = generate_url_by_format($meta_in_data['doc-id'], $trim_format);
        $url = sprintf('<a href="%s">%s</a>',$curl,$curl);
        $prefix = substr($meta_in_data['doc-id'],0,3);
        $number = substr($meta_in_data['doc-id'],3);
        $report_number = sprintf('%s %d',$prefix,$number); // URL is rfcX.txt


        if ($debug_rmeta === TRUE) {
 		print "Title is $meta_title";
		print "Authors are $authors_list";
		print "Pub date is $pub_date";
		print "URL is $url";
                print "Report number is $report_number";
	}		

	$authors = explode(',', $authors_list);
	$num_auths = count($authors);
        foreach ($authors as $author) {
	    $author = trim($author);
	    $author = preg_replace('/; Ed\./', ', Ed.', $author);
	    $author_tag .= "<meta name=\"citation_author\" content=\"$author\">";	
            }


        $meta_tag = "<meta name=\"citation_title\" content=\"$meta_title\">
		     $author_tag
                     <meta name=\"citation_publication_date\" content=\"$pub_date\">
                     <meta name=\"citation_issn\" content=\"2070-1721\">
                     <meta name=\"citation_pdf_url\" content=\"$curl\">
		     <meta name=\"citation_technical_report_number\" content=\"$report_number\">";



	#return $meta_tag;
        print $meta_tag;
}

/*
 * Insert into the output stream a footer for the meta data record display.
 */
function display_footer_section($in_data) {

     if ($in_data['doc-id'] != 'RFC0032') {
     print<<<END
  <hr>
<p>
For the definition of <b>Status</b>,
see <a href="/info/rfc2026">RFC 2026</a>.
</p><p>
For the definition of <b>Stream</b>, see <a href="/info/rfc8729">RFC 8729</a>.
</p>
<hr>
<p><a href="//get.adobe.com/reader/">Download PDF Reader</a></p>
END;
}
else {
     print<<<END
<hr>
<p>For individuals searching for "Connecting M.I.T. Computers to the ARPA Computer-to-Computer Communication Network" by Dietrich Vedder via the same RFC identifier, please see <a href="/rfc/museum/rfc32-alt.html">RFC 32 alt</a>.
</p>  
<hr>
<p>
For the definition of <b>Status</b>,
see <a href="/info/rfc2026">RFC 2026</a>.
</p><p>
For the definition of <b>Stream</b>, see <a href="/info/rfc8729">RFC 8729</a>.
</p>
<hr>
<p><a href="//get.adobe.com/reader/">Download PDF Reader</a></p>
END;
}


}
/*
 * Insert the line for the citation links
 */
function display_citation_links($num,$prefix,$doi) {
     
     $numwithzeros = NULL;
     $numwithzeros = substr($num, -4);
     $prefix_low = strtolower($prefix);
     $str_num = ltrim($numwithzeros, '0');

     $doistring = "";
     if($doi > "") $doistring = "<b>DOI</b>: &nbsp;$doi"; 
     
     if ($prefix == 'RFC'){ 
     print<<<END
<p>
<b>Cite this $prefix</b>: 
<a href="/refs/ref$numwithzeros.txt">TXT</a> &nbsp;|</b>&nbsp;
<a href="/refs/bibxml/reference.RFC.$numwithzeros.xml">XML</a>
</p><p>
$doistring
</p>
END;
}
    elseif ($prefix == 'BCP' or $prefix == 'STD'){
     print<<<END
<p>
<b>Cite this $prefix</b>: 
<a href="/refs/ref-$prefix_low$str_num.txt">TXT</a></p>

END;



    }
}


/*
 * Display mailing list
 */
function display_mailing_list($in_data) {
     global $rfcnum;
     $mailing_list;
    # print_r($in_data);

            switch ($in_data['source']) {
             case "INDEPENDENT":
                  $mailing_list = "rfc-ise@rfc-editor.org";
                  break;
             case "IAB":
                  $mailing_list = "iab@iab.org";
                  break;
	     case "IRTF":
                  $mailing_list = "irsg@irtf.org";
                  break;
             case "Legacy":
                  if ($in_data['area_acronym']){
		     $mailing_list = $in_data['area_acronym']."-ads@ietf.org";
                  }else{
                     $mailing_list = "iesg@ietf.org";
                  }
                  break;
             case "IESG":
                  $mailing_list = "iesg@ietf.org";
                  break;
	     case "IETF - NON WORKING GROUP":
                  if ($in_data['area_acronym']){
		     $mailing_list = $in_data['area_acronym']."-ads@ietf.org";
                  }else{
                     $mailing_list = "iesg@ietf.org";
                  }
                  break;  
             default:
          // for working groups, lookup in the database
                  if ($in_data['wg_acronym']){
		      #$mailing_list = $in_data['wg_acronym']."-ads@tools.ietf.org, " . $in_data['wg_acronym']."-chairs@tools.ietf.org";
             	      $mailing_list = $in_data['wg_email'];
  
	          } 
                  if ($mailing_list == null){
                      $mailing_list = "iesg@ietf.org";
                  } 
                  break;
             }

             $prefix = substr($in_data['doc-id'],0,3);


             print "<p><b>Discuss this RFC</b>: Send questions or comments to <a href=\"mailto:$mailing_list?subject=Question regarding $prefix $rfcnum \">$mailing_list</a></p>";

}



/*
 * Insert into the output stream the errata section.
 * Gets the errata count for the RFC and shows the RFC if present
 * user can submit errata is errata does not exist 
*/

function display_errata_and_ipr_section($data_in) {
     global $rfcnum;
     global $datatracker_base;
     global $debug_rmeta;

     $errata_count = check_errata($data_in['doc-id']);

     if ($debug_rmeta === TRUE) {
         print "Errata count is".$errata_count;
     }

     /*If given i/p is rfcnum */
     if ($rfcnum){
     if ($errata_count > 0){
     print<<<END
<p>
<b>Other actions</b>:
<a href="/errata/rfc$rfcnum">View Errata</a> &nbsp;|</b>&nbsp; 
<a href="/errata.php#reportnew">Submit Errata</a> &nbsp;|</b>&nbsp;
<a href="$datatracker_base/ipr/search/?draft=&rfc=$rfcnum&submit=rfc">
Find IPR Disclosures from the IETF</a>
</p>

END;

     } else {

     print<<<END
<p>
<b>Other actions</b>: 
<a href="/errata.php#reportnew">Submit Errata</a> &nbsp;|</b>&nbsp;
<a href="$datatracker_base/ipr/search/?draft=&rfc=$rfcnum&submit=rfc">
Find IPR Disclosures from the IETF</a>
</p>

END;
     }

    } else {/*If given i/p is subseries number*/

     $doc_id_in = $data_in['doc-id'];
     $doc_id_in = strtolower($doc_id_in);
     if ($errata_count > 0){
     print<<<END
<p>
<b>Other actions</b>:
<a href="/errata/$doc_id_in">View Errata</a> &nbsp;|</b>&nbsp; 
<a href="/errata.php#reportnew">Submit Errata</a> &nbsp;|</b>&nbsp;
<a href="$datatracker_base/ipr/search/?draft=&rfc=$doc_id_in&submit=rfc">
Find IPR Disclosures from the IETF</a>
</p>

END;

     } else {

     print<<<END
<p>
<b>Other actions</b>: 
<a href="/errata.php#reportnew">Submit Errata</a> &nbsp;|</b>&nbsp;
<a href="$datatracker_base/ipr/search/?draft=&rfc=$doc_id_in&submit=rfc">
Find IPR Disclosures from the IETF</a>
</p>

END;
     }


}
}

/*
* Check if the given RFC number has any errata present or not
*/
function check_errata($errata_doc_id){
     global $rfcnum;
     global $datatracker_base;
     global $debug_rmeta;
     global $pdo;    

     $params = [":errata_doc_id" => $errata_doc_id];
     $status_id = [1,2,3,6,9];

     $in = "";
     foreach ($status_id as $i => $item)
     {
         $key = ":status_id".$i;
         $in .= "$key,";
         $in_params[$key] = $item; // collecting values into key-value array
     }
     $in = rtrim($in,","); 

 
 // This query retreives status change information.
     try {
          $errata_query = "SELECT e.`doc-id` , errata_id, title, `pub-date` , errata_type_code, errata_status_code, submit_date, posted_date, verifier_id, verifier_name, verifier_email, submitter_name, 
		       submitter_email, orig_text, correct_text, e.notes, section, rs_code, e.insert_date, e.update_date, conv_format_check, i.source, submit_date, submitter_name, section, w.`ssp_id`
                       FROM errata e, errata_type_codes t, errata_status_codes s, working_group w, area a, `index` i
                       LEFT JOIN area_assignments n ON i.internal_key = n.fk_index
                       WHERE e.`doc-id` = i.`doc-id`
                       AND e.type_id = t.errata_type_id
                       AND e.status_id = s.errata_status_id
                       AND i.source = w.wg_name
                       AND (
                       a.area_name = w.area_name
                       OR n.fk_area = a.area_id
                       )
		       AND e.`doc-id` = :errata_doc_id
		       AND e.status_id
                       IN ($in)
		       GROUP BY errata_id
                       ORDER BY i.`doc-id` , e.type_id DESC";
             
             $stmt = $pdo->prepare($errata_query);
             $stmt->execute(array_merge($params,$in_params)); // just merge two arrays
             $num_of_rows = $stmt->rowCount();
         }catch (PDOException $pe){
         error_log("Error processing :  check_errata", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));

     }

     if ($debug_rmeta === TRUE) {
          print("<pre>\n");
          var_dump($errata_query);
          print("</pre>\n");
     }

     #$pdo = null; 
     $return_errata_count = $num_of_rows;
     return $return_errata_count;


}



/*
 * Create one entry in a Definition List HTML element.  
 */
function display_dt($term, $def) {
     print("  <dt><b>$term</b><dd>$def</dd>\n");
}

/*
 * Display meta data formatted for an issued document.
 */
function display_issued_rfcmeta($data) {
     // Put this section up only if the RFC represents a standard, best
     // practice or FYI.
     printf('<div class="entryheader">');
     switch ($data['status']) {
     case 'BEST CURRENT PRACTICE':
     case 'INTERNET STANDARD':
          if ($data['see-also'] != NULL) {
              display_see_also_subseries($data);
          }
          break;
     case 'INFORMATIONAL':
          // Not all INFORMATIONAL RFCs are FYIs. Marker is FYIxxxx in SEE-ALSO
          if (!(strpos($data['see-also'],'FYI') === FALSE)) {
               display_see_also_subseries($data);
          }
          break;
     default:
          // Skip for all others.
          break;
     }

     printf('<h2><a href="%s">%s</a></h2>',
            generate_canonical_url($data['doc-id'], $data['format']),
            pretty_doc_id($data['doc-id']));

     printf("\n<h3>%s, <span>%s</span></h3>\n", trim($data['title']), 
            format_meta_date($data['pub-date']));

     printf('</div>');
     
     print("<dl>\n");           // Start the Definition List structure

     //$curl = generate_canonical_url($data['doc-id'], $data['format']);
     //$url = sprintf('<a href="%s">%s</a>',$curl,$curl);
     //display_dt("Canonical URL:", $url);

     //Now get the file formats
     display_dt("File formats:", generate_canonical_url_dt($data));
    
  
     display_dt("Status:",format_status($data['status'], $data['pub-status'],$data['internal_key']));

     if ($data['obsoletes'] != NULL && strlen(trim($data['obsoletes'])) != 0) {
          display_dt("Obsoletes:",format_doc_id_list($data['obsoletes']));
     }
     if ($data['obsoleted-by'] != NULL && 
         strlen(trim($data['obsoleted-by'])) != 0) {
          display_dt("Obsoleted by:",format_doc_id_list($data['obsoleted-by']));
     }
     if ($data['updates'] != NULL && strlen(trim($data['updates'])) != 0) {
          display_dt("Updates:",format_doc_id_list($data['updates']));
     }
     if ($data['updated-by'] != NULL && strlen(trim($data['updated-by'])) != 0) {
          display_dt("Updated by:",format_doc_id_list($data['updated-by']));
     }

     $data['authors'] = preg_replace('/, Ed\./', '; Ed.', $data['authors']);
     $csv = strpos($data['authors'],',');
     if ($csv === FALSE) {
          display_dt("Author:",format_author_list($data['authors']));
     } else {
          display_dt("Authors:",format_author_list($data['authors']));
     }

     if (strcmp($data['stream_name'], 'IETF') == 0) {
          display_dt("Stream:",format_stream_data($data));
          if (strcmp($data['source'], "Legacy") != 0) {
               display_dt("Source:",format_source_data($data));
          }
     } else {
          display_dt("Stream:",format_stream_data($data));
     }
   
     print("</dl>\n");           // End the Definition List structure


     $prefix = substr($data['doc-id'],0,3);
     $number = substr($data['doc-id'],3);

     display_citation_links($data['doc-id'],$prefix,$data['DOI']);
     display_mailing_list($data);
     display_errata_and_ipr_section($data);


     if ($data['abstract'] !=  NULL && strlen(trim($data['abstract'])) != 0) {

          $data['abstract'] = htmlspecialchars($data['abstract']);
          $data['abstract'] = preg_replace("[\r\n\r\n]","</p><p>", $data['abstract']);
          
	  print<<<END
<hr/>
<h2>Abstract</h2>
<p>{$data['abstract']}</p>
END;
     }
}

/*
 * Display meta data for a document not issued. (This is a very terse
 * display.)
 */
function display_not_issued_rfcmeta($data) {
     print ('<div class="entryheader">');
     printf('<h2><a href="%s">%s</a></h2>',
            generate_canonical_url($data['doc-id'], $data['format']),
            pretty_doc_id($data['doc-id']));

     printf("\n<h2>%s</h2>\n", trim($data['title']));
     print ('</div>');
}

/*
 * Called for some documents that are also part of subseries, e.g. BCP or
 * INTERNET STANDARD.
 */
function display_see_also_subseries($data) {
     printf('<h2><a href="%s">%s</a></h2>', 
            generate_canonical_url($data['see-also'],$data['format']),
            pretty_doc_id($data['see-also']));
     print("\n");
}

/*
 * Selects which format to display meta data. One is for documents not issued
 * and the other is for issued documents.
 */
function display_rfcmeta_data($data) {
     global $debug_rmeta;

     if ($debug_rmeta === TRUE) {
          print("<pre>\n");
          var_dump($data);
          print("</pre>\n");
     }

     switch ($data['status']) {
     case 'NOT ISSUED':
          display_not_issued_rfcmeta($data);
          break;
     default:
          display_issued_rfcmeta($data);
          break;
     }
     
     display_footer_section($data);
}

/*
 * Format the stream as a URL or plain text depending on the source.
 */
function format_stream_data($data) {
     global $ietf_base;
     $stream;
        /*Added check for ssp id 4 for RG groups*/
/*     	     	switch ($data['source']) {
		     case 'IAB':
		     case 'IRTF':
		     case 'INDEPENDENT':
        		  $stream = sprintf('<a href="%s">%s</a>', $data['ssp_webpage'],$data['source']);
		          break;
		     case 'Legacy':
	        	  $stream = "[Legacy]";
		          break;
		     default: 
        	  # Source is an IETF working group. Use stream_name and hardcode IETF URL.
        		  $stream = "<a href=\"http://www.ietf.org/\">" . $data['stream_name'] . "</a>";
	          break;
       		}
       	}
*/
	     	switch ($data['ssp_id']) {
		     case '3':
	       		  $stream = sprintf('<a href="%s">%s</a>', $data['ssp_webpage'],$data['source']);
                          break;
		     case '4':
                          $stream = sprintf('<a href="%s">%s</a>', $data['ssp_webpage'],'IRTF');
                          break;
		     case '6':
        		  $stream = sprintf('<a href="%s">%s</a>', $data['ssp_webpage'],$data['source']);
		          break;
		     default: 
                          if ($data['source'] == 'Legacy'){
                          $stream = "[Legacy]";    
			  }else{
                          
        	  # Source is an IETF working group. Use stream_name and hardcode IETF URL.
        		  $stream = "<a href=\"$ietf_base\">" . $data['stream_name'] . "</a>";
                          }  
	          break;
       		}

     return $stream;
}

/*
 * Formats the document Source
 */
function format_source_data($data) {

        global $ietf_base;
        /*Added check for ssp id 4 for RG groups*/
/*	     switch($data['source']) {
	     case 'IESG':
	     case 'IAB':
	     case 'INDEPENDENT':
	     case 'IRTF':
	          $formatted_source = $data['source'];
        	  break;
	     case 'Legacy':
	          $formatted_source = "[{$data['source']}]";
        	  break;
	     case 'IETF - NON WORKING GROUP':
        	  $formatted_source = '<a href="http://www.ietf.org/iesg/statement/ad-sponsoring-docs.html">NON WORKING GROUP</a>';
	          break;
	     default:
	          $formatted_source = sprintf('<a href="http://tools.ietf.org/wg/%s/">%s</a> 
                                      (<a href="http://tools.ietf.org/area/%s/">%s</a>)', 
                                      $data['wg_acronym'], $data['wg_acronym'],
                                      $data['area_acronym'],$data['area_acronym'] );
        	  break;
     	     }
     
*/

	     switch($data['ssp_id']) {
	     case '1':
                    if ($data['source'] == 'IETF - NON WORKING GROUP') {
	        	  $formatted_source = '<a href= '.$ietf_base.'/blog/guidance-area-director-sponsoring-documents>NON WORKING GROUP</a>';
                        }
                    else if ($data['source'] == 'IESG') {
                          $formatted_source = $data['source'];
                        }
                    else {
		          $formatted_source = sprintf('<a href="//datatracker.ietf.org/wg/%s/charter/">%s</a> 
                                      (<a href="//datatracker.ietf.org/wg/#%s/">%s</a>)', 
                                      $data['wg_acronym'], $data['wg_acronym'],
                                      $data['area_acronym'],$data['area_acronym'] );
			} 
                    break;
	     case '3':
	     case '6':
	     case '4':
	          $formatted_source = $data['source'];
        	  break;
	     case '7':
        	  $formatted_source = '<a href="$ietf_base/blog/guidance-area-director-sponsoring-documents">NON WORKING GROUP</a>';
                  break;
	     default:
                  if ($data['source'] == 'Legacy'){
		          $formatted_source = "[{$data['source']}]";
                  }else {
		          $formatted_source = sprintf('<a href="//datatracker.ietf.org/wg/%s/charter/">%s</a> 
                                      (<a href="//datatracker.ietf.org/wg/#%s/">%s</a>)', 
                                      $data['wg_acronym'], $data['wg_acronym'],
                                      $data['area_acronym'],$data['area_acronym'] );
                  }  
        	  break;
     	     }


     return $formatted_source;
}

/*
 * Format the status columns for display.
 */
function format_status($status, $pub_status,$internal_key) {
     global $debug_rmeta;
     global $pdo;

     try {
         // This query retreives status change information.
         $date_query = "SELECT  DATE_FORMAT(`date_of_change`,'%M %Y') as 'date_of_change',`URL_of_change` FROM `status_changes` WHERE `dockey` = :internal_key";
         $stmt = $pdo->prepare($date_query);
         $stmt->bindParam('internal_key',$internal_key);
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
     } catch (PDOException $pe){
         error_log("Error processing :  format_status", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));

     }

     if ($debug_rmeta === TRUE) {
          print("<pre>\n");
          var_dump($date_query);
          print("</pre>\n");
     }

     $return_date_count = $num_of_rows;
     if ($return_date_count > 0){	
	      while ( $line = $stmt->fetch(PDO::FETCH_ASSOC)){
	              $date_of_change = $line[0];
        	      $url_of_change = $line[1];
       		}
     }

     #$pdo = null;
     //Attached the status change information along with the status if it exists.

     if (strcmp($status, $pub_status) == 0) {
          return $status;
     }

     //Check if Status change information is present for the draft

     if ($date_of_change){
		if ($url_of_change){
			$format_date_url  = ' '.'<a href="'.$url_of_change.'"target="_blank">';
			$format_date_url .= "$date_of_change"."</a>";
		}else{
			$format_date_url = ' '.$date_of_change;
		     }
     }

     return "$status (changed from $pub_status$format_date_url)";
}


$months = array(1 => "January", 2 => "February", 3 => "March", 4 => "April",
                5 => "May", 6 => "June", 7 => "July", 8 => "August",
                9 => "September",10 => "October",11 => "November", 
                12 => "December");
                
/*
 * Called with the YYYY-MM-DD time string from database entry, returns
 * a string with format Month, YYYY
 */
function format_meta_date($date) {
     global $months;

     $parsed_date = date_parse($date);
     $month = $months[$parsed_date['month']];
     // Special for April Fools' Day RFCs
     if ($parsed_date['month'] == 4 && $parsed_date['day'] == 1) {
          return $month . ' 1 ' . $parsed_date['year'];
     }
     return $month . ' ' . $parsed_date['year'];
}

/*
 * Take the CSV string of authors and format it so that each name is on a
 * separate line.
 */
function format_author_list($author_list) {
     global $debug_rmeta;

     $authors = explode(',', $author_list);
     $num_auths = count($authors);
     $count = 0;

     if ($debug_rmeta === TRUE) {
          print("<pre>\n");
          var_dump($author_list);
          var_dump($authors);
          var_dump($num_auths);
          var_dump($count);
          print("</pre>\n");
     }

     $formatted_authors = NULL;
     foreach ($authors as $author) {
          $formatted_authors .= trim($author);
          $count++;
          if ($count < $num_auths) { $formatted_authors .= "<br />"; }
     }
 
     $formatted_authors = preg_replace('/; Ed\./', ', Ed.', $formatted_authors);
 
    if ($debug_rmeta === TRUE) {
          print("<pre>\n");
          var_dump($formatted_authors);
          print("</pre>\n");
     }

     return $formatted_authors;
}

/*
 * Take a string CSV list of RFC documents and make meta data URLs out of them.
 */
function format_doc_id_list($doc_id_list) {
     $docs = explode(',', $doc_id_list);
     $num_docs = count($docs);
     $count = 0;
     $formatted_doc_list = '';
     foreach ($docs as $doc) {
          $trimed_doc = trim($doc);
          $count++;
          $type = substr($trimed_doc,0,3);
          $num =  substr($trimed_doc,3);
          switch ($type) {
          case 'RFC':
               $formatted_doc_list .= sprintf('<a href="/info/rfc%d">%s %d</a>', $num, $type, $num );
               break;
          default:
               $formatted_doc_list .= sprintf('%s %d', $type, $num );
               break;
          }
          if ($count < $num_docs) { $formatted_doc_list .= ", "; }
     }
     return $formatted_doc_list;
}

function generate_url_by_format($docid, $format="PLAINTEXT") {
     global $debug_rmeta;
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
     case 'TEXT':
          // New file format TXT with UTF-8 characters
          $url .= sprintf("%s/%s%d.txt", $prefix, $prefix, $number);
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

     if ($debug_rmeta === TRUE) {
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
     global $debug_rmeta;
     $curl = NULL;
     if (strcmp(trim($format_csv),"PDF") == 0) {
          $curl = generate_url_by_format($docid,"PDF");
     } else {
          $curl = generate_url_by_format($docid); // get the PLAINTEXT version
     }
     return $curl;
}

#$format_labels = array("ASCII" => "TXT.PDF", "PDF" => "PDF", "PS" => "PostScript");
$format_labels = array("ASCII" => "PDF", "PDF" => "PDF with images", "PS" => "PostScript");


function generate_canonical_url_dt($data) {
     global $debug_rmeta;
     global $format_labels;
     global $file_base_images;
     $file_image = "";
     /*Commenting this as this is already taken care outside
     $curl = generate_canonical_url($data['doc-id'], $data['format']);
     $url = sprintf('<a href="%s">%s</a>',$curl,$curl);
    */

     $formats = explode(',',$data['format']);
     $num_formats = count($formats);

     $prefix = strtolower(substr($data['doc-id'],0,3));
     $number = substr($data['doc-id'],3);
 

    /*Hack for arranging the formats in the form ASCII, PDF, PS*/
     if (($num_formats == '3') && (trim($formats[1]) == 'PS')){
       $formats= Array('ASCII','PDF','PS');
     }
     switch ($formats[0]) {
     case '':
          $url .= "<br />This file is not available online. Please contact the RFC Editor if you have a copy of this RFC.";
          break;
#To show only PDF case
#   case 'PDF':
#         break;
     default:
        //  $url .= "<br />This document is also available in ";/*Not required due to outside caption*/
      //    $url .= $num_formats == 1 ? "this non-normative format: " : "these non-normative formats: ";
  
          $url .= "<br />";
          $count = 0;
          foreach ($formats as $format) {
               $trimed_format = trim($format);
               $furl = generate_url_by_format($data['doc-id'], $trimed_format);
              if ($trimed_format == 'ASCII'){
		   $file_image = '<img src='.$file_base_images.'/RFC_ICONS_Text_40x50.png alt="icon for text file" title="text">';
                   $curl = generate_canonical_url($data['doc-id'], $data['format']);
		   $url = '<a href='.$curl.'>'. $file_image.'</a>';
                   
                   $file_image = '<img src='.$file_base_images.'/RFC_ICONS_PDF_2019_50x40.png alt="icon for PDF" title="PDF">';
                   $url .= ' <a href='.$furl.'>'. $file_image.'</a>';
               }elseif ($trimed_format == 'PDF'){
                    $file_image = '<img src='.$file_base_images.'/RFC_ICONS_PDFimage_2019_50x40.png alt="icon for Supplementary PDF" title="Supplementary PDF">';
                    $url .= '<a href='.$furl.'>'. $file_image.'</a>';
               }elseif ($trimed_format == 'PS'){
                    $file_image = '<img src='.$file_base_images.'/RFC_ICONS_PS_40x50.png alt="icon for PS file" title="PS">';
		    $url .= '<a href='.$furl.'>'. $file_image.'</a>';
                }elseif ($trimed_format == 'TEXT'){
                     $file_image = '<img src='.$file_base_images.'/RFC_ICONS_Text_40x50.png alt="icon for text file" title="text">';
		     $curl = generate_canonical_url($data['doc-id'], $data['format']);
                     $url .= '<a href='.$curl.'>'. $file_image.'</a>';
               }elseif ($trimed_format == 'XML'){ 
                     $file_image = '<img src='.$file_base_images.'/RFC_ICONS_XML_40x50.png alt="icon for XML" title="XML">';
		     $url .= '<a href='.$furl.'>'. $file_image.'</a>';
               }elseif ($trimed_format == 'HTML'){
                     $file_image = '<img src='.$file_base_images.'/RFC_ICONS_HTML_40x50.png alt="icon for HTML" title="HTML">';
		      $url .= '<a href='.$furl.'>'. $file_image.'</a>';
               }elseif ($trimed_format == 'v3PDF'){
                     $file_image = '<img src='.$file_base_images.'/RFC_ICONS_PDF_2019_50x40.png alt="icon for v3pdf" title="v3pdf">';
		     $url .= '<a href='.$furl.'>'. $file_image.'</a>';
               }
               $count++;
               if ($count < $num_formats) {
                    $url .= '     ';
               } else {
                    $url .= '';
               }
          }
          break;
     }
     if ($debug_rmeta === TRUE) {
          print("<pre>generate_canonical_url_dt\n");
          var_dump($formats);
          var_dump($num_formats);
          var_dump($url);
          print("</pre>\n");
     }


#Get the in-line errata information and add it to thr format list

     $inline_verified_errata =  get_inline_errata($data['doc-id']); 
     if ($inline_verified_errata!= ""){
           $file_image = '<img src='.$file_base_images.'/HTML_correction_40x50_2020.png alt="icon for inline errata" title="HTML with inline errata">';
           $url .= '   <a href='.$inline_verified_errata.'>'. $file_image.'</a>';
     }
     return $url;
}

/*
 * Take a canonical document id, e.g. RFC0123, and reformat to the prettier
 * format of "RFC 123." Stripping the first three characters as a prefix allows
 * the function to work for BCP, STD, and other similar document ids.
 */
function pretty_doc_id($docid) {
     $prefix = substr($docid, 0, 3);
     $num = substr($docid,3);
     return sprintf("%s %d", $prefix, $num);
}


/* Input RFC0123; returns 0123
function get_with_zeros($docid) {
  $withzeros = substr($docid, -4);
  return $withzeros;  
}
*/


/***********************************************************************************************/
/*Function : get_inline_errata()                                                               */
/***********************************************************************************************/
function get_inline_errata($in_doc_id){
     global $debug_rmeta;
     global $pdo;
     global $document_root;
     global $inline_errata_base;
     $inline_errata_anchor = "";
     $http_inline_errata_url = "";

     try {
         $inline_errata_query = "select distinct(`doc-id`) from `errata` e where e.`status_id` = :status_id and e.`doc-id` =:in_doc_id";
         $stmt= $pdo->prepare($inline_errata_query);
         $stmt->bindValue('status_id',1);
         $stmt->bindParam('in_doc_id',$in_doc_id);
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
     } catch (PDOException $pe){
         error_log("Error processing :  get_inline_errata", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));

     }
    
     if ($num_of_rows > 0){
     #Record verified errata count
     $return_errata_count = $num_of_rows;
          #$rfc_number = sprintf('%s%d','rfc',$in_rfc_number);
          $rfc_number = strtolower($in_doc_id);
          $prefix = strtolower(substr($in_doc_id,0,3));
          $number = substr($in_doc_id,3);
          $inline_url = sprintf("%s%d.html",$prefix, $number);
          $http_inline_errata_url = $document_root. $inline_errata_base . $inline_url;
     }

     if ($debug_rmeta){
          print "The inline errata anchor is.". $http_inline_errata_url ."\n";
     }

   #  $pdo = null;
     return $http_inline_errata_url;

}






/*
This function decides weather the subseries case is one-to-one or one-to-many
Retrieves the bcp meta data from the database
*/
function get_otherrfc_data($subseries_num,$display,$case) {
     global $debug_rmeta,$pdo;

     // This query retreives more than will be used, at least initially.
/*   $query = "
        SELECT i.source,`doc-id`, status, `pub-status`, type, `pub-date`, title, `char-count`, `page-count`, obsoletes, `obsoleted-by`, updates, `updated-by`, `see-also`,`see-also-title`, authors, abstract, format FROM `index` i";
*/
     $padded_num = sprintf("%04d",($subseries_num));
     $display = $display.$padded_num;
     
     try {
         $query = "
             SELECT i.source, w.wg_acronym, `doc-id`, 
                 CONCAT( st.state_name,CASE WHEN i.iana_flag = \"1\"THEN \"*A\"ELSE \"\"END ,CASE WHEN i.ref_flag = \"1\"THEN \"*R\"ELSE \"\"END,
	         CASE WHEN i.generation_number = \"1\" THEN \"(1G)\" WHEN i.generation_number = \"2\" THEN \"(2G)\" WHEN i.generation_number = \"3\" THEN \"(3G)\" WHEN i.generation_number = \"0\" THEN \"\" END) as state,
                 status, `pub-status`,
                 type, `pub-date`, title, `char-count`, `page-count`,
                 obsoletes, `obsoleted-by`, updates, `updated-by`, `see-also`,`internal_key`,
                 `see-also-title`, authors, abstract, format, a.area_acronym,
                 s.stream_name, s.ssp_webpage, w.`ssp_id` 
            FROM `index` i, working_group w, area a, `states` st,
                 stream_specific_parties s
            WHERE i.source = w.wg_name AND
                 w.area_name = a.area_name AND
                 w.ssp_id = s.ssp_id AND
                 i.state_id = st.state_id AND
                 i.state_id =:state_id";
     

      if ($case){
	     $query .= " AND i.`doc-id`=:display";
         }else {
	     $query .= " AND i.`SEE-ALSO`=:display";
         }
	
         $stmt = $pdo->prepare($query);
         $stmt->bindValue('state_id',14);
         $stmt->bindParam('display',$display);
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
     } catch (PDOException $pe){
         error_log("Error processing :  get_otherrfc_data", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }

     if ($debug_rmeta === TRUE) {
          print("<pre>\n");
          var_dump($query);
          var_dump($display);
          var_dump($case);
          print("</pre>\n");
     }

   
     if ($num_of_rows > 0){
             $row = $stmt->fetch(PDO::FETCH_ASSOC);
     }
     return $row;
}
/*
*Displays the metadata for subseries number who has one to one mapping
*with RFC's
*/
function display_one_to_one($data){
     global $debug_rmeta;

     if ($debug_rmeta === TRUE) {
          print("<pre>\n");
          var_dump($data);
          print("</pre>\n");
     }

     switch ($data['status']) {
     case 'NOT ISSUED':
          display_not_issued_rfcmeta($data);
          break;
     default:
          display_issued_one_to_one($data);
          break;
     }

     display_footer_section($data);

}



function display_issued_one_to_one($data){

     global $debug_rmeta;

     print('<div class="entryheader">');
     printf('<h2><a href="%s">%s</a></h2>',
            generate_canonical_url($data['see-also'], $data['format']),
            pretty_doc_id($data['see-also']));

     printf('<h2><a href="%s">%s</a></h2>',
            generate_canonical_url($data['doc-id'], $data['format']),
            pretty_doc_id($data['doc-id']));


     printf("\n<h3>%s, <span>%s</span></h3>\n", trim($data['title']), 
            format_meta_date($data['pub-date']));
     
     print('</div>');
     print("<dl>\n");           // Start the Definition List structure



     //$curl = generate_canonical_url($data['see-also'], $data['format']);
     //$url = sprintf('<a href="%s">%s</a>',$curl,$curl);

     //display_dt("Canonical URL:",$url);

    //Now get the file formats
     display_dt("File formats:", generate_canonical_url_dt($data));


     #display_dt("Canonical URL:", generate_canical_url($data['see-also'],$data['format']));
     display_dt("Status:",format_status($data['status'], $data['pub-status'],$data['internal_key']));

     if ($data['obsoletes'] != NULL && strlen(trim($data['obsoletes'])) != 0) {
          display_dt("Obsoletes:",format_doc_id_list($data['obsoletes']));
     }
     if ($data['obsoleted-by'] != NULL && 
         strlen(trim($data['obsoleted-by'])) != 0) {
          display_dt("Obsoleted by:",format_doc_id_list($data['obsoleted-by']));
     }
     if ($data['updates'] != NULL && strlen(trim($data['updates'])) != 0) {
          display_dt("Updates:",format_doc_id_list($data['updates']));
     }
     if ($data['updated-by'] != NULL && strlen(trim($data['updated-by'])) != 0) {
          display_dt("Updated by:",format_doc_id_list($data['updated-by']));
     }

     $data['authors'] = preg_replace('/, Ed\./', '; Ed.', $data['authors']);
     $csv = strpos($data['authors'],',');
     if ($csv === FALSE) {
          display_dt("Author:",format_author_list($data['authors']));
     } else {
          display_dt("Authors:",format_author_list($data['authors']));
     }

     if (strcmp($data['stream_name'], 'IETF') == 0) {
          display_dt("Stream:",format_stream_data($data));
          if (strcmp($data['source'], "Legacy") != 0) {
               display_dt("Source:",format_source_data($data));
          }
     } else {
          display_dt("Stream:",format_stream_data($data));
     }
   
     print("</dl>\n");           // End the Definition List structure

     $prefix = substr($data['see-also'],0,3);
     $number = substr($data['see-also'],3);

     display_citation_links($data['see-also'],$prefix,$data['DOI']);
     display_mailing_list($data);
     display_errata_and_ipr_section($data);

/*
 *     display_errata_and_ipr_section();
 */

     if ($data['abstract'] !=  NULL && strlen(trim($data['abstract'])) != 0) {
          $data['abstract'] = htmlspecialchars($data['abstract']);
          $data['abstract'] = preg_replace("[\r\n\r\n]","</p><p>", $data['abstract']);
          print<<<END
<hr/>
<h2>Abstract</h2>
<p>{$data['abstract']}</p>
END;
     }




}


/*
 * Selects which format to display meta data. One is for documents not issued
 * and the other is for issued documents.
 */
function display_otherrfc_data($data) {
     global $debug_rmeta;

     if ($debug_rmeta === TRUE) {
          print("<pre>\n");
          var_dump($data);
          print("</pre>\n");
     }

     switch ($data['status']) {
     case 'NOT ISSUED':
          display_not_issued_rfcmeta($data);
          break;
     default:
          display_issued_otherrfc($data);
          break;
     }

     display_footer_section($data);
}


/*
 * Dispalys the subseries data for issued numbers.
 */

function display_issued_otherrfc($data) {
     global $debug_rmeta;

     if ($debug_rmeta === TRUE) {
          print("<pre>\n");
          var_dump($data);
          print("</pre>\n");
     }

     print ('<div class="entryheader">');
     printf('<h2><a href="%s">%s</a></h2>',
     generate_canonical_url($data['doc-id'], $data['format']),
     pretty_doc_id($data['doc-id']));
     print ('</div>');


     //$curl = generate_canonical_url($data['doc-id'], $data['format']);
     //$url = sprintf('<a href="%s">%s</a>',$curl,$curl);

     //display_dt("Canonical URL:",$url);
     
     $prefix = substr($data['doc-id'],0,3);
     $number = substr($data['doc-id'],3);

     display_citation_links($data['doc-id'],$prefix,$data['DOI']);
     display_subseries_table($data);


#     display_footer_section();
}

/*
* This function displays the subseries info for a passed bcp in a tabular format
*/
function display_subseries_table($data){
     global $debug_rmeta;

     if ($debug_rmeta === TRUE) {
          print("<pre>\n");
          var_dump($data);
          print("</pre>\n");
     }


     if ($data['see-also']){
	 $sub_series = explode(',', $data['see-also']);
     	 $num_subseries = count($sub_series);
     }

     if ($debug_rmeta === TRUE) {
          print("<pre>\n");
          var_dump($data['see-also']);
          var_dump($sub_series);
          var_dump($num_series);
          var_dump($count);
          print("</pre>\n");
     }

     $formatted_subseries = NULL;
     
     if ($data['see-also']){
        $format_subseries_id = pretty_doc_id($data['doc-id']);
	print "<b>".$format_subseries_id." contains the following RFCs:</b>";
        print '<div class="scrolltable">';

       	print '<table class="statictable">';
 	print "<tr><th>Number</th><th>Files</th><th>Title</th><th>Authors</th><th>Date</th><th>More Info</th><th>Status</th></tr>";

     	foreach ($sub_series as $subseries) {
		  $subseries = trim($subseries);	
		  $subseries_row =  get_subseries_data($subseries);
		  #Format number column
		  $formatted_rfc_number = build_info_link($data['doc-id']);
		  $formatted_subseries_link = build_info_link($subseries_row['DOC-ID']); 
		  $number_column = sprintf('%s, %s',$formatted_subseries_link,$formatted_rfc_number);
                  
                  #Format file format 
     		  $format_files_anchor = build_files_anchor($data['format'],$subseries_row['DOC-ID']);

		  #Get the more info data in proper format
		  $more_info = get_more_info($subseries_row);

		  #Get the formatted status 
		  $formatted_status = get_formmated_status($subseries_row,'rfcmeta');

		  print "<tr>";

                  display_cell($number_column);
                  display_cell($format_files_anchor);
                 // display_cell($subseries_row['TITLE']);
                  print "<td><b>";
                  print $subseries_row['TITLE'];

                  print "</b></td>";
                 
                  display_cell($subseries_row['AUTHORS']);
		  display_cell($subseries_row['PUB-DATE']);
                  display_cell($more_info);
		  display_cell($formatted_status);

		  print "</tr>";

     	}
	print "</table>";
     }
         print "</div>";
         print "<hr/>";
   	
     foreach ($sub_series as $subseries) {
		  $subseries = trim($subseries);	
		  $subseries_row =  get_subseries_data($subseries);
		  $format_doc_id = pretty_doc_id($subseries_row['DOC-ID']);
		  print "<h2>Abstract of ".$format_doc_id."</h2>";
                  if ($subseries_row['abstract'] !=  NULL && strlen(trim($subseries_row['abstract'])) != 0) {
                      $subseries_row['abstract'] = htmlspecialchars($subseries_row['abstract']);
                      $subseries_row['abstract'] = preg_replace("[\r\n\r\n]","</p><p>", $subseries_row['abstract']);
                  }
		  print " <p>".$subseries_row['abstract']."</p>";

     }

}


/*This function gets the individual data for the subseries number*/
function get_subseries_data($subseries){
     global $debug_rmeta,$pdo;
     
     try{
         // This query retreives more than will be used, at least initially.
         $query = "SELECT `DOC-ID`, `SEE-ALSO`,`format`,`TITLE`,`AUTHORS`,DATE_FORMAT(`PUB-DATE`,'%M %Y') as \"PUB-DATE\", 
		        `OBSOLETES`,`OBSOLETED-BY`,`UPDATES`,`UPDATED-BY`,`status`, `abstract`, `PUB-STATUS` from
		           `index` i  WHERE `doc-id` = :subseries";

         $stmt = $pdo->prepare($query);
         $stmt->bindParam('subseries',$subseries);
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
     } catch (PDOException $pe){
         error_log("Error processing :  get_subseries_data", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }

     if ($debug_rmeta === TRUE) {
          print("<pre>\n");
          var_dump($query);
          print("</pre>\n");
     }

     if ($num_of_rows > 0){
             $row = $stmt->fetch(PDO::FETCH_ASSOC);
     }
     return $row;
}



/* ** ******************* ** */
/* ** Start of processing ** */
/* ** ******************* ** */

$display= "";
$rfcnum = NULL;
#$bcpnum = NULL;

$subseries_num = NULL;

$case = true;

if (isset($_GET) && array_key_exists('rfc',$_GET)) {
     $rfcnum = substr($_GET['rfc'], 0, MAX_CID);
     $display = 'RFC';
}
elseif (isset($_GET) && (!(array_key_exists('rfc',$_GET)))){
	if (array_key_exists('bcp',$_GET)) {
	     $subseries_num = substr($_GET['bcp'], 0, MAX_CID);
	     $display = 'BCP';	
        }
 	if (array_key_exists('std',$_GET)) {
	     $subseries_num = substr($_GET['std'], 0, MAX_CID);
	     $display = 'STD';	
        }
 	if (array_key_exists('fyi',$_GET)) {
	     $subseries_num = substr($_GET['fyi'], 0, MAX_CID);
	     $display = 'FYI';	
        }

}

/*if (isset($_GET) && array_key_exists('bcp',$_GET)) {
     $bcpnum = substr($_GET['bcp'], 0, MAX_CID);
     $display = 'BCP';	
}
*/

if ($debug_rmeta === TRUE) {
     if (isset($_POST)) {
          print("\$_POST<br /><pre>");
          print_r($_POST);
          print("</pre>");
     }
     if (isset($_GET)) {
          print("\$_GET<br /><pre>");
          print_r($_GET);
          print("</pre>");
     }
}


if ($display != ""){
	if ($rfcnum != NULL) {
	     $meta_data = get_rfcmeta_data($rfcnum);
             if ($meta_data != NULL){
                rfcmeta_header($rfcnum,$display);
                if (!is_bool($meta_data)){
        	    display_rfcmeta_data($meta_data);
	         } else {
        	  print '<p>No data returned for RFC' . htmlspecialchars($rfcnum) . '</p>';
     	           }
	     }
                 else {/*Case for non exist RFC number*/
                       no_rfc_header();
		       printf('<div class="entryheader">');
                       print "<h3> RFC " . htmlspecialchars($rfcnum) . " does not exist</h3>";
                       print ('</div>');
                }
        }

	if ($subseries_num != NULL) {
	     /*This does one to many case handling as we get result treating subseries number as doc-id*/
	     $meta_data = get_otherrfc_data($subseries_num,$display,$case);


	     if (isset($meta_data)) {
		 if ($debug_rmeta === TRUE) {
                     print "My many case data is ";
		     print_r($meta_data);	
                 }
		 rfcmeta_header($subseries_num,$display);
        	 display_otherrfc_data($meta_data);
	     } else {
	     /*This does one to one case handling as we get result treating subseries number as see-also*/
                  $case = false; 
		  $print_num = sprintf('%s&nbsp;%d',$display,$subseries_num); 
		  $meta_data = get_otherrfc_data($subseries_num,$display,$case);
		  if ($meta_data != NULL) {
		     rfcmeta_header($subseries_num,$display);
                     if (!is_bool($meta_data)) {
			 if ($debug_rmeta === TRUE) {
                             print "My one to one data is";
                  	     print_r($meta_data);
                         }
			 display_one_to_one($meta_data, $rfcnum);
                     }
                  }else {  /*Case for non exist subseries number*/
		       no_rfc_header();
                       printf('<div class="entryheader">');
                       print "<h3> " . htmlspecialchars($display) . ' ' . htmlspecialchars($subseries_num) . " does not exist</h3>";
                       print ('</div>');
                  }


              }
	  if (is_bool($meta_data)) {

        	  print '<p>No data returned for ' . htmlspecialchars($print_num) . '</p>';
     	     }
	}
} else {
print<<<END
<p>No RFC to lookup!</p>
END;
}
?>
    </div>
  </div>
</div>
<?php
$pdo = null;
    print "<br>";
    print "<br>";
get_footer();

?>
