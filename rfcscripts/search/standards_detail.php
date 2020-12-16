<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
/***************************************************************************************************************************************************************************/
/* script name : standards_detail.php                                                                                                                                      */
/* June  2017 : Corrected the case where i/p RFC number has see-also number as a RFC Number - PN                                                                           */
/*              Original Script :  <td>".$formatted_rfc_number.", ".$formatted_see_also."</td>"                                                                            */
/*              Modified Script : "<td>".$formatted_rfc_number."</td>";
/* November 2020 : Modified the script to use PDO prepared statements - PN                                                                                                */
/***************************************************************************************************************************************************************************/

include('rfc_lib_new.php');
include('search_constant.php');
$debug_vsh = false;
#$debug_vsh = true;

$view_query_base ="SELECT DISTINCT(`DOC-ID`), `SEE-ALSO`, `format`,
				  concat( `DOC-ID`,\" \",`SEE-ALSO`,\" \",`format`) as \"Number\", 
				  `TITLE`,`AUTHORS`,DATE_FORMAT(`PUB-DATE`,'%M %Y') as \"PUB-DATE\",`PUB-DATE` as \"ORDER-DATE\", 	                     
				  CONCAT(`OBSOLETES`,`OBSOLETED-BY`,`UPDATES`,`UPDATED-BY`) as \"More_Info\",    
				  `OBSOLETES`,`OBSOLETED-BY`,`UPDATES`,`UPDATED-BY`, 
				  `SOURCE`, `status`,w.`ssp_id` , w.`wg_name`, a.`area_name`, 
				  i.`PUB-STATUS`,i.`abstract`, i.keywords , i.`internal_key` FROM `working_group` w, area a, 
				  `stream_specific_parties` s, 
				  `index` i left join area_assignments n on i.internal_key = n.fk_index 
				  WHERE i.`SOURCE` = w.`wg_name` AND w.`ssp_id` = s.`ssp_id` AND 
				  a.area_name = w.area_name AND (a.area_name = w.area_name OR n.fk_area = a.area_id) AND 
				  (i.state_id = '14') AND i.`DOC-ID` NOT LIKE 'IEN%' AND i.`DOC-ID` LIKE 'RFC%' AND 
				  i.`status` = ";


$view_retired_base= "SELECT CASE WHEN i.`SEE-ALSO` IS NULL OR i.`SEE-ALSO` like 'RFC%' THEN i.`DOC-ID` ELSE i.`SEE-ALSO` END AS 'RETIRED-PLUS',
					`DOC-ID`, `SEE-ALSO`,
					`format`, concat( `DOC-ID`,\" \",`SEE-ALSO`,\" \",`format`) as \"Number\",
					 `TITLE`,`AUTHORS`,DATE_FORMAT(`PUB-DATE`,'%M %Y') as \"PUB-DATE\",`PUB-DATE` as \"ORDER-DATE\", 
                    CONCAT(`OBSOLETES`,`OBSOLETED-BY`,`UPDATES`,`UPDATED-BY`) as \"More_Info\", 
					`OBSOLETES`,`OBSOLETED-BY`,`UPDATES`,`UPDATED-BY`, 
                    `SOURCE`, `status`,w.`ssp_id` , w.`wg_name`, a.`area_name`,
					 i.`PUB-STATUS`,i.`abstract`, i.keywords , i.`internal_key` 
                     FROM `working_group` w, area a, `stream_specific_parties` s,
                    `index` i left join area_assignments n on i.internal_key = n.fk_index 
                     WHERE i.`SOURCE` = w.`wg_name` AND w.`ssp_id` = s.`ssp_id`
                     AND a.area_name = w.area_name AND (a.area_name = w.area_name OR n.fk_area = a.area_id)
                     AND (i.`DOC-ID` LIKE 'STD%' OR i.`SEE-ALSO` LIKE '%STD%' OR i.TYPE = 'STD') ";


/***********************************************************************************************/
/*Function : create_search_view*/
/*			 Creates the advanced search view */
/***********************************************************************************************/
function create_search_view(){
	global $debug_vsh;
	global $view_query_base;
	global $search_page;
	global $view_retired_base;

        $orderby;
	$sort;
	$sort_number;
	$std_set;
	$show_retired;

        $ret_sortkey_const;
        $obs_sortkey_const;
        

	if ($debug_vsh === true) {
		  print "create_search_view\n";	
     }

?>
 <div id="content" class="nosidebar">
     <h1 class="post_title">Official Internet Protocol Standards</h1>

     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">

 
<?php
	print '<p>This page contains the current lists of</p>'; 
	print '<ul><li><p><a href="#IS">Internet Standards</a></p></li><li><p><a href="#DS">Draft Standards</a><p>[Note: This maturity level was retired by RFC 6410: "Any protocol or service that is currently at the abandoned Draft Standard maturity level will retain that classification, absent explicit actions."] </p></li><li><p><a href="#PS">Proposed Standards</a></p></li>';
  print '</ul>';
    
	/*Show/Hide retired STD numbers*/


/*Set the show retired and show OBS flags with initial settings check N */
   if (isset($_GET['showRET'])){
      $showRET = $_GET['showRET']; 
   }else {
      $showRET = 'N';
   }

   if (isset($_GET['showOBS'])){
      $showOBS = $_GET['showOBS'];  
   }else {
      $showOBS = 'N';
   }


/*Set the sort order */
	
	if (isset($_GET['sorting'])){
    	$sorting = $_GET['sorting'];
	}
	elseif (!isset($_GET['sorting']) OR $_GET['sorting'] == ''){
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



   if ($debug_vsh === true) {
     print ' The showret value is '. $_GET['showRET'].'<br>';
     print ' The showobs value is '. $_GET['showOBS'].'<br>';
     print 'The sort key is '. $_GET['sortkey'].'<br>'; 
     print 'The sorting is '. $_GET['sorting'].'<br>'; 
     print 'The sort number is '. $_GET['sort_number'].'<br>'; 
        
}


   if (($showRET == 'N') && ($showOBS == 'N') && ( (!(isset($_GET['sort_number']))) OR ($_GET['sort_number'] == '') )){ /*This is Initial condition*/
       print '<p>[<b><a href="standards.php?sortkey=STDRET&showRET=Y&showOBS=N">Show</a></b> / Hide retired or reserved STD numbers on this page]</p>';
       print '<p>[<b><a href="standards.php?sortkey=OBSRFC&showRET=N&showOBS=Y">Show</a></b> / Hide obsoleted RFCs on this page]</p>';
   }/*Initial Condition End*/
   elseif (($showRET == 'N') && ($showOBS == 'N') && isset($_GET['sort_number'])){ /* showRET=N showOBS=N */

             if ($_GET['sort_number'] == '1'){
                 if (isset($_GET['sortkey'])){
                 switch($_GET['sortkey']){
                 case "STDNO":
                              $ret_sortkey_const = 'STDRET';
                              break;
                 case "Number":
                              $ret_sortkey_const = 'NumberRET';
                              break;
                 case "Date":
                              $ret_sortkey_const = 'DateRET';
                              break;
                 default:
                 #        $ret_sortkey_const= 'STDNO'
                         break;
                 }
                 }
                 else {
                       $ret_sortkey_const= 'STDNO';
                 }
             print '<p>[<b><a href="standards.php?sortkey=' . htmlspecialchars($ret_sortkey_const) . '&showRET=Y&showOBS=' . htmlspecialchars($showOBS) . '&sorting=' . htmlspecialchars($sorting) . '&sort_number=' . htmlspecialchars($_GET['sort_number']) . '">Show</a></b> / Hide retired or reserved STD numbers on this page]</p>';
             print '<p>[<b><a href="standards.php?sortkey=OBSRFC&showRET=' . htmlspecialchars($showRET) . '&showOBS=Y&sorting=' . htmlspecialchars($sorting) . '&sort_number=' . htmlspecialchars($_GET['sort_number']) . '">Show</a></b> / Hide obsoleted RFCs on this page]</p>';
             }elseif(($_GET['sort_number']) == '2' OR ($_GET['sort_number'] == '3')){
                if (isset($_GET['sortkey'])){
 
		 switch($_GET['sortkey']){
                 case "Number":
                              $obs_sortkey_const = 'OBSRFC';
                              break;
                 case "Date":
                              $obs_sortkey_const = 'DateOBS';
                              break;
                 default:
                  #       $obs_sortkey_const = 'Number';
                         break;
                 }
                }else {
                     $obs_sortkey_const = 'Number';
                }
             print '<p>[<b><a href="standards.php?sortkey=STDRET&showRET=Y&showOBS=' . htmlspecialchars($showOBS) . '&sorting=' . htmlspecialchars($sorting) . '&sort_number=' . htmlspecialchars($_GET['sort_number']) . '">Show</a></b> / Hide retired or reserved STD numbers on this page]</p>';
             print '<p>[<b><a href="standards.php?sortkey=' . htmlspecialchars($obs_sortkey_const) . '&showRET=' . htmlspecialchars($showRET) . '&showOBS=Y&sorting=' . htmlspecialchars($sorting) . '&sort_number=' . htmlspecialchars($_GET['sort_number']) . '">Show</a></b> / Hide obsoleted RFCs on this page]</p>';



             }

   }/* End showRET=N showOBS=N */
   elseif(($showRET == 'Y') && ($showOBS == 'N') && ( (!(isset($_GET['sort_number']))) OR ($_GET['sort_number'] == '') )) { /* showRET=Y showOBS=N sort_number not set*/
              
           print '<p>[Show / <b><a href="standards.php?sortkey=STDNO&showRET=N&showOBS=' . htmlspecialchars($showOBS) . '&sorting=' . htmlspecialchars($sorting) . '&sort_number=' . htmlspecialchars($_GET['sort_number']) . '">Hide</a></b> retired or reserved STD numbers on this page]</p>';
           print '<p>[<b><a href="standards.php?sortkey=OBSRFC&showRET=' . htmlspecialchars($showRET) . '&showOBS=Y&sorting=' . htmlspecialchars($sorting) . '&sort_number=' . htmlspecialchars($_GET['sort_number']) . '">Show</a></b> / Hide obsoleted RFCs on this page]</p>';
 
   } /* End showRET=Y showOBS=N sort_number not set*/
   elseif(($showRET == 'Y') && ($showOBS == 'N') && isset($_GET['sort_number']) ){ /* showRET=Y showOBS=N sort_number isset*/
 
             if ($_GET['sort_number'] == '1'){

		if (isset($_GET['sortkey'])){

                 switch($_GET['sortkey']){
                 case "STDRET":
                              $ret_sortkey_const = 'STDNO';
                              break;
                 case "NumberRET":
                              $ret_sortkey_const = 'Number';
                              break;
                 case "DateRET":
                              $ret_sortkey_const = 'Date';
                              break;
                 default:
               #          $ret_sortkey_const='STDRET';
                         break;
                 }
               }else {
                 $ret_sortkey_const='STDRET';
              }
            	 print '<p>[Show / <b><a href="standards.php?sortkey=' . htmlspecialchars($ret_sortkey_const) . '&showRET=N&showOBS=' . htmlspecialchars($showOBS) . '&sorting=' . htmlspecialchars($sorting) . '&sort_number=' . htmlspecialchars($_GET['sort_number']) . '">Hide</a></b> retired or reserved STD numbers on this page]</p>';
     

                  print '<p>[ <b><a href="standards.php?sortkey=OBSRFC&showRET=' . htmlspecialchars($showRET) . '&showOBS=Y&sorting=' . htmlspecialchars($sorting) . '&sort_number=' . htmlspecialchars($_GET['sort_number']) . '">Show</a></b> / Hide obsoleted RFCs on this page]</p>';
             
             }elseif(($_GET['sort_number']) == '2' OR ($_GET['sort_number'] == '3')){
		if (isset($_GET['sortkey'])){

		 switch($_GET['sortkey']){
                 case "Number":
                              $obs_sortkey_const = 'OBSRFC';
                              break;
                 case "Date":
                              $obs_sortkey_const = 'DateOBS';
                              break;
                 default:
                #         $obs_sortkey_const='Number';
                         break;
                 }
               }else{
                  $obs_sortkey_const='Number';
               }
	     print '<p>[Show / <b><a href="standards.php?sortkey=STDNO&showRET=N&showOBS=' . htmlspecialchars($showOBS) . '&sorting=' . htmlspecialchars($sorting) . '&sort_number=' . htmlspecialchars($_GET['sort_number']) . '">Hide</a></b> retired or reserved STD numbers on this page]</p>';
             print '<p>[ <b><a href="standards.php?sortkey=' . htmlspecialchars($obs_sortkey_const) . '&showRET=' . htmlspecialchars($showRET) . '&showOBS=Y&sorting=' . htmlspecialchars($sorting) . '&sort_number=' . htmlspecialchars($_GET['sort_number']) . '">Show</a></b> / Hide obsoleted RFCs on this page]</p>';

             }


   }/* End showRET=Y showOBS=N sort_number isset*/
    elseif(($showRET == 'Y') && ($showOBS == 'Y') && ( (!(isset($_GET['sort_number']))) OR ($_GET['sort_number'] == '') )){ /* showRET=Y showOBS=Y sort_number not set*/
              
              print '<p>[Show / <b><a href="standards.php?sortkey=STDNO&showRET=N&showOBS='. htmlspecialchars($showOBS) . '&sorting=' . htmlspecialchars($sorting) . '&sort_number=' . htmlspecialchars($_GET['sort_number']) . '">Hide</a></b> retired or reserved STD numbers on this page]</p>';
              print '<p>[Show / <b><a href="standards.php?sortkey=Number&showRET=' . htmlspecialchars($showRET) . '&showOBS=N&sorting=' . htmlspecialchars($sorting) . '&sort_number=' . htmlspecialchars($_GET['sort_number']) . '">Hide</a></b> obsoleted RFCs on this page]</p>';
 
 
   } /* End showRET=Y showOBS=Y sort_number not set*/
   elseif(($showRET == 'Y') && ($showOBS == 'Y') && isset($_GET['sort_number']) ){ /* showRET=Y showOBS=Y sort_number is set*/
            
/*TODO this*/ 
             if ($_GET['sort_number'] == '1'){/*See if isset sotkey works or not*/
	       if (isset($_GET['sortkey'])){

                 switch($_GET['sortkey']){
                 case "STDRET":
                              $ret_sortkey_const = 'STDNO';
                              break;
                 case "NumberRET":
                              $ret_sortkey_const = 'Number';
                              break; 
                 case "DateRET":
                              $ret_sortkey_const = 'Date';
                              break;
                 default:
		#	 $ret_sortkey_const = 'STDRET';
                         break;
                 }
              }else {
                 $ret_sortkey_const = 'STDRET';
              }
              print '<p>[Show / <b><a href="standards.php?sortkey=' . htmlspecialchars($ret_sortkey_const) . '&showRET=N&showOBS=' . htmlspecialchars($showOBS) . '&sorting=' . htmlspecialchars($sorting) . '&sort_number=' . htmlspecialchars($_GET['sort_number']) . '">Hide</a></b> retired or reserved STD numbers on this page]</p>';
	      print '<p>[Show / <b><a href="standards.php?sortkey=Number&showRET=' . htmlspecialchars($showRET) . '&showOBS=N&sorting=' . htmlspecialchars($sorting) . '&sort_number=' . htmlspecialchars($_GET['sort_number']) . '">Hide</a></b> obsoleted RFCs on this page]</p>';
 

             }elseif(($_GET['sort_number']) == '2' OR ($_GET['sort_number'] == '3')){
		if (isset($_GET['sortkey'])){

		 switch($_GET['sortkey']){
                 case "OBSRFC":
                              $obs_sortkey_const = 'Number';
                              break;
                 case "DateOBS":
                              $obs_sortkey_const = 'Date';
                              break;
                 default:
                 #        $obs_sortkey_const = 'OBSRFC';
                         break;
                 }
               }else {
                  $obs_sortkey_const = 'OBSRFC';
               }
              print '<p>[Show / <b><a href="standards.php?sortkey=STDNO&showRET=N&showOBS=' . htmlspecialchars($showOBS) . '&sorting=' . htmlspecialchars($sorting) . '&sort_number=' . htmlspecialchars($_GET['sort_number']) . '">Hide</a></b> retired or reserved STD numbers on this page]</p>';
              print '<p>[Show / <b><a href="standards.php?sortkey=' . htmlspecialchars($obs_sortkey_const) . '&showRET=' . htmlspecialchars($showRET) . '&showOBS=N&sorting=' . htmlspecialchars($sorting) . '&sort_number=' . htmlspecialchars($_GET['sort_number']) .'">Hide</a></b> obsoleted RFCs on this page]</p>';
 
             }


   } /* End showRET=Y showOBS=Y sort_number is set*/
   elseif(($showRET == 'N') && ($showOBS == 'Y')  && ( (!(isset($_GET['sort_number']))) OR ($_GET['sort_number'] == '') )){ /* showRET=N showOBS=Y sort_number not set*/
          
          print '<p>[<b><a href="standards.php?sortkey=STDRET&showRET=Y&showOBS=' . htmlspecialchars($showOBS) . '&sorting=' . htmlspecialchars($sorting) . '&sort_number=' . htmlspecialchars($_GET['sort_number']) . '">Show</a></b> / Hide retired or reserved STD numbers on this page]</p>';
          print '<p>[Show / <b><a href="standards.php?sortkey=Number&showRET=' . htmlspecialchars($showRET) . '&showOBS=N&sorting=' . htmlspecialchars($sorting) . '&sort_number=' . htmlspecialchars($_GET['sort_number']) . '">Hide</a></b> obsoleted RFCs on this page]</p>';

   } /* End showRET=N showOBS=Y sort_number not set*/
   elseif(($showRET == 'N') && ($showOBS == 'Y') && isset($_GET['sort_number']) ){ /* showRET=N showOBS=Y sort_number is set*/
               
              if ($_GET['sort_number'] == '1'){
		if (isset($_GET['sortkey'])){

                 switch($_GET['sortkey']){
                 case "STDNO":
                              $ret_sortkey_const = 'STDRET';
                              break;
                 case "Number":
                              $ret_sortkey_const = 'NumberRET';
                              break;
                 case "Date":
                              $ret_sortkey_const = 'DateRET';
                              break;
                 default:
                  #       $ret_sortkey_const = 'STDNO';
                         break;
                 }
              }else {
                 $ret_sortkey_const = 'STDNO';
              }
             print '<p>[<b><a href="standards.php?sortkey=' . htmlspecialchars($ret_sortkey_const) . '&showRET=Y&showOBS=' . htmlspecialchars($showOBS) . '&sorting=' . htmlspecialchars($sorting) . '&sort_number=' . htmlspecialchars($_GET['sort_number']) . '">Show</a></b> / Hide retired or reserved STD numbers on this page]</p>';
               print '<p>[Show / <b><a href="standards.php?sortkey=Number&showRET=' . htmlspecialchars($showRET) . '&showOBS=N&sorting=' . htmlspecialchars($sorting) . '&sort_number=' . htmlspecialchars($_GET['sort_number']) . '">Hide</a></b> obsoleted RFCs on this page]</p>';
 

             }elseif(($_GET['sort_number']) == '2' OR ($_GET['sort_number'] == '3')){
	       if (isset($_GET['sortkey'])){

		 switch($_GET['sortkey']){
                 case "OBSRFC":
                              $obs_sortkey_const = 'Number';
                              break;
                 case "DateOBS":
                              $obs_sortkey_const = 'Date';
                              break;
                 default:
                   #      $obs_sortkey_const = 'OBSRFC';
                         break;
                 }
               }else {
                  $obs_sortkey_const = 'OBSRFC';
               }
             print '<p>[<b><a href="standards.php?sortkey=STDRET&showRET=Y&showOBS=' . htmlspecialchars($showOBS) . '&sorting=' . htmlspecialchars($sorting) . '&sort_number=' . htmlspecialchars($_GET['sort_number']) . '">Show</a></b> / Hide retired or reserved STD numbers on this page]</p>';
             print '<p>[Show / <b><a href="standards.php?sortkey=' . htmlspecialchars($obs_sortkey_const) . '&showRET=' . htmlspecialchars($showRET) . '&showOBS=N&sorting=' . htmlspecialchars($sorting) . '&sort_number=' . htmlspecialchars($_GET['sort_number']) . '">Hide</a></b> obsoleted RFCs on this page]</p>';
 
             }


   } /* End showRET=N showOBS=Y sort_number is set*/



/****************Checking ***************************/

//TODO    if ((isset ($_GET['section_name'])) OR ($_GET['sortkey'] == 'STDRET') OR ($_GET['sortkey'] == 'NumberRET') OR ($_GET['sortkey'] == 'DateRET')) {
    if (($_GET['sortkey'] == 'STDRET') OR ($_GET['sortkey'] == 'NumberRET') OR ($_GET['sortkey'] == 'DateRET') or ($showRET == 'Y')) {		
		$show_retired = true;
	} else {
		$show_retired = false;
		}
	
    if ($debug_vsh === true) {
		  print "create_search_view\n";	
		  print "Show retired";
		  print "$show_retired\n";
     }

    if (($_GET['sortkey'] == 'OBSRFC') OR ($_GET['sortkey'] == 'DateOBS') OR ($showOBS == 'Y')) {		
		$show_obsolete = true;
	} else {
		$show_obsolete = false;
	}
		
	if ($debug_vsh === true) {
		  print "create_search_view\n";	
		  print "Show obsolete";
		  print "$show_obsolete\n";
     }
		
//    $section_name =   $_GET['section_name'];
	//print "My section is $section_name";
	
   /*********Sorting related setting**********************/
	if (isset($_GET['sort_number'])) {
		$sort_number = $_GET['sort_number'];
	}else {
		$sort_number = "1";
		$std_set = true;
	}
					

   /*************Sort key*******************/
	if (isset($_GET['sortkey'])){
        $sortkey = $_GET['sortkey'];
		if (($sortkey == 'Number') OR ($sortkey == 'NumberRET') OR ($sortkey == 'OBSRFC')){
	        $orderby = 'ORDER BY `DOC-ID`';
		}
		elseif (($sortkey == 'Date') OR ($sortkey == 'DateRET') OR ($sortkey == 'DateOBS')){
	        $orderby = 'ORDER BY `ORDER-DATE`';
		}
		elseif ($sortkey == 'STDNO'){//Sorting for STD No
			$orderby = 'ORDER BY `SEE-ALSO`';			
			}
		elseif ($sortkey == 'STDRET'){//Sorting for STD No
			$orderby = "ORDER BY CASE WHEN i.`SEE-ALSO` IS NULL OR i.`SEE-ALSO` like 'RFC%' THEN i.`DOC-ID` ELSE i.`SEE-ALSO` END";
			}
	
	}
	else {
       $orderby = 'ORDER BY `DOC-ID`';
	   $sortkey = 'Number';
	}


	if ($debug_vsh === true) {
		  print "create_search_view\n";	
  		  print "Sort_number is $sort_number\n";	
  		  print "Sort key is $sortkey\n";	
  		  print "Sort order is $sort\n";	
		  print "Order by is $orderby\n";	
        }


	/************Main report printing*******/
	  //TODO priyanka WP print '<div class="box" align="center">';

	/***********Internet Standard*********/ 
//	  print '<a name="IS">';
  //    print "<h2>Internet Standards</h2>"; 	



	  $standard_number = '1';
	
	
	  if (!($show_retired)){
		print '<a name="IS"></a>';
        print "<h2>Internet Standards</h2>"; 	
        print_table_header_IS(1,$sort,"STDNO",$showRET,$showOBS);

		  if ($sort_number == 1){	
				if ($std_set){
				   $view_query = "$view_query_base". " :standard_value ORDER BY `SEE-ALSO`";				
				   $std_set = false;
				  } else {
						$view_query = "$view_query_base"." :standard_value "."$orderby $original_sort";
				}
		  }else {
				   $view_query = "$view_query_base"." :standard_value ORDER BY `SEE-ALSO`";				
		   }
	
		  report_print_new($view_query,$standard_number); 
	  }else{

		  $view_retired_base;
	       print '<a name="IS"></a>';
           print "<h2>Internet Standards</h2>"; 	

         print_table_header_IS(1,$sort,"STDRET",$showRET,$showOBS);		
		  if ($sort_number == 1){	
				if ($std_set){
				   $view_query = "$view_retired_base"."ORDER BY CASE WHEN i.`SEE-ALSO` IS NULL OR i.`SEE-ALSO` like 'RFC%' THEN i.`DOC-ID` ELSE i.`SEE-ALSO` END ";

				   $std_set = false;
				  } else {
						$view_query = "$view_retired_base"."$orderby $original_sort";
				}
		  }else {
	#			  $view_query = "$view_query_base"." "."('".$standard_value."')"."ORDER BY `DOC-ID`";
				  $view_query = "$view_retired_base". "ORDER BY CASE WHEN i.`SEE-ALSO` IS NULL OR i.`SEE-ALSO` like 'RFC%' THEN i.`DOC-ID` ELSE i.`SEE-ALSO` END ";
				}
				
		    report_print_retired($view_query); 
			print "</table>";	  	  

   }
	  

	/***********Draft Standard*********/ 
	  print '<a name="DS"></a>';	
          print "<h2>Draft Standards</h2>"; 	
          print "<p>[Note: This maturity level was retired by RFC 6410: \"Any protocol or service that is currently at the abandoned Draft Standard maturity level will retain that classification, absent explicit actions.\"]</p>";

      print_table_header(3,$sort,$show_obsolete,$showRET,$showOBS);
  
	  $standard_number = '3';

  	  if ($sort_number == 3){	
			  $view_query = "$view_query_base"." :standard_value $orderby $original_sort";
	  }else {
			  $view_query = "$view_query_base"." :standard_value ORDER BY `DOC-ID`";
	   }

	  report_print($view_query,$show_obsolete,$standard_number); 
	  
	  
  	/***********Proposed Standard*********/ 
	
	
	  print '<a name="PS"></a>';	
      print "<h2>Proposed Standards</h2>"; 	            
      print_table_header(2,$sort,$show_obsolete,$showRET,$showOBS);

	  $standard_number = "2";

	  if ($sort_number == 2){	
			  $view_query = "$view_query_base".": standard_value $orderby $original_sort";
	  }else {
			  $view_query = "$view_query_base"." :standard_value ORDER BY `DOC-ID`";
	   }
	  
	  report_print($view_query,$show_obsolete,$standard_number); 


}

/***********************************************************************************************/
/*function : get_query_part*/
/*           decides the query based on the standard number passed                             */
/***********************************************************************************************/

function get_query_part($in_number){
	
		global $debug_vsh;

		$out_value = "";
		
		
		switch ($in_number) {
				  case '1': $out_value = "Internet Standard";
			                   		break;
				  case '2': $out_value = "Proposed Standard";
			                   		break;
				  case '3': $out_value = "Draft Standard";
			                   		break;

				  default:
				  			break;
			  }
			  
		if ($debug_vsh === true) {
		  print "get_query_part\n";	
          var_dump($in_number);
          var_dump($out_value);
     	}
			  
		return $out_value;
}

/***********************************************************************************************/
/*function : report_print_retired*/
/*           Executes the mysql query and displayes the result in tabular format               */
/*           For Retire internet standards document                                            */
/***********************************************************************************************/

function  report_print_retired($in_query){
	
     global $debug_vsh;
     global $pdo;

     try {
         $stmt = $pdo->prepare($in_query);
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
     }catch (PDOException $pe){
       error_log("Error processing : report_print_retired", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }
     $return_view_count = $num_of_rows;

	
    #The following logic builds the reporting result in a tabular format
	 if ($return_view_count > 0){
	 	 while ($line = $stmt->fetch(PDO::FETCH_ASSOC)){
			 
			 if (!(preg_match('/^\[/',$line['TITLE'])) && ($line['status'] == 'STD') OR ($line['status'] == 'STANDARD'))/*A hack to avoid the rows with Status as STD or STANDARD in the retired result*/
				 continue;
			 
			 $format_anchor = "";
			 list($rfc_name,$rfc_number,$length) = split_rfc($line['DOC-ID']);
			 $formatted_rfc_number = sprintf('%s&nbsp;%d',$rfc_name,$rfc_number);
			 #Get the RFC number in proper format with link
			 $formatted_rfc_number = build_info_link($line['DOC-ID']);
			 #Get the File format link using format
			 $format_files_anchor = build_files_anchor($line['format'],$rfc_number);
                         #For "Internet Standards" we need records which are not obsolete
			 if (!($line['OBSOLETED-BY'])) { 		 
				 print "<tr>";
				 $formatted_see_also = build_see_also($line['SEE-ALSO']);
				 
				 $formatted_retired_plus = build_see_also($line['RETIRED-PLUS']); 
				 
				 if ( preg_match('/^\[/',$line['TITLE'])){ /*Retired*/
					 list($retired_name,$retired_number,$length) = split_rfc($line['RETIRED-PLUS']);
					 $formatted_retired_number = sprintf('%s&nbsp;%d',$retired_name,$retired_number);
									 print "<td>".$formatted_retired_number."</td>";					 					 
				 }else {
					 print "<td>".$formatted_retired_plus."</td>";					 
				 }
				 
				 if ( preg_match('/^\[/',$line['TITLE'])){/*Retired*/
					 
					 print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
				 }else{
	 				 print "<td>".$formatted_rfc_number."</td>";
				 
                                 }
				 
 				 if ( preg_match('/^\[/',$line['TITLE'])){/*Retired*/ 
					 print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
				 }else {
						 if ($format_files_anchor){
									print "<td>".$format_files_anchor."</td>";
						 } else {
									print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
						 }
				 }


 				 if ( preg_match('/^\[/',$line['TITLE'])){/*Retired*/ 
					 //print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
					 print "<td>".$line['TITLE']."</td>";
				 }else {
				 		#If DOC is obsoleted then it has to be shown as grey
						if ($line['TITLE']){
									if ($line['OBSOLETED-BY']){
										print "<td class=\"special\">".$line['TITLE']."</td>";
									}else {
										print "<td class=\"title\">".$line['TITLE']."</td>";
										}
						}else {
									print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
						}
				 }

				 if ( preg_match('/^\[/',$line['TITLE'])){/*Retired*/ 
					 print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
				 }else { 
					 if 	($line['AUTHORS']){
								print "<td>".$line['AUTHORS']."</td>";
							}else {
								print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
					 } 
				 }
					 
			     if ( preg_match('/^\[/',$line['TITLE'])){/*Retired*/ 
					 print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
				 }else { 
					if ($line['PUB-DATE']){
								$display_pub_date = handle_april_fool_date($line);
								print"<td>".$display_pub_date."</td>";
					}else {
								print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
					}
				 }
				 
				 
			    if ( preg_match('/^\[/',$line['TITLE'])){/*Retired*/ 
					 print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
				 }else {
				 
					#Get the more info data in proper format
					$more_info = get_more_info($line);
					if ($more_info){
								print "<td>".$more_info."</td>";
					}else {
								print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
					}
				 }
				 
				 
				 if ( preg_match('/^\[/',$line['TITLE'])){/*Retired*/ 
					 print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
				 }else {
	                                $formatted_status = get_formatted_status($line);
					if ($formatted_status){
								print "<td>".$formatted_status."</td>";
					}else {
								print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
					}
				 }
				print "</tr>";									
			 }//End of obesolete by clause for Internet Standard
		 }//End of while
	 	 print "</table>";
		 
	 }

	} 

/***********************************************************************************************/
/*function : report_print_new*/
/*           Executes the mysql query and displayes the result in tabular format               */
/***********************************************************************************************/
function  report_print_new($standard_query,$in_standard_number){
	
     global $debug_vsh;
     global $pdo;

    $standard_value = get_query_part($in_standard_number);
    try {
         $stmt = $pdo->prepare($standard_query);
         $stmt->bindParam('standard_value',$standard_value);
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
     }catch (PDOException $pe){
       #error_log("Error processing : report_print_new", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }
     $return_view_count = $num_of_rows;
	
    #The following logic builds the reporting result in a tabular format
	 if ($return_view_count > 0){
		 
	 	 while ($line = $stmt->fetch(PDO::FETCH_ASSOC)){

			 $format_anchor = "";
			 list($rfc_name,$rfc_number,$length) = split_rfc($line['DOC-ID']);
			 $formatted_rfc_number = sprintf('%s&nbsp;%d',$rfc_name,$rfc_number);
			 #Get the RFC number in proper format with link
			 $formatted_rfc_number = build_info_link($line['DOC-ID']);
			 #Get the File format link using format
			 $format_files_anchor = build_files_anchor($line['format'],$rfc_number);
			 #For "Internet Standards" we need records which are not obsolete
			 if (!($line['OBSOLETED-BY'])){ 		 
				 print "<tr>";
				 $formatted_see_also = build_see_also($line['SEE-ALSO']);
				 print "<td>".$formatted_see_also."</td>";					 
 				 print "<td>".$formatted_rfc_number."</td>";
				 if ($format_files_anchor){
							print "<td>".$format_files_anchor."</td>";
				 } else {
							print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
				 }
				#If DOC is obsoleted then it has to be shown as grey
				if ($line['TITLE']){
							if ($line['OBSOLETED-BY']){
								print "<td class=\"special\">".$line['TITLE']."</td>";
							}else {
								print "<td class=\"title\">".$line['TITLE']."</td>";
								}
				}else {
							print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
				}
				
				if 	($line['AUTHORS']){
							print "<td>".$line['AUTHORS']."</td>";
						}else {
							print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
				}
				
				if ($line['PUB-DATE']){
							$display_pub_date = handle_april_fool_date($line);
							print"<td>".$display_pub_date."</td>";
				}else {
							print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
				}
						
				#Get the more info data in proper format
				$more_info = get_more_info($line);
				if ($more_info){
							print "<td>".$more_info."</td>";
				}else {
							print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
				}
				
				$formatted_status = get_formatted_status($line);
				if ($formatted_status){
							print "<td>".$formatted_status."</td>";
				}else {
							print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
				}
				print "</tr>";									
			 }//End of obesolete by clause for Internet Standard
		 }//End of while
	 	 print "</table>";
		 
	 }

}  


/***********************************************************************************************/
/*function : report_print*/
/*           Executes the mysql query and displayes the result in tabular format               */
/***********************************************************************************************/

function report_print($standard_query,$obs_show,$in_standard_number){
     global $debug_vsh;
     global $pdo;
    
    $standard_value = get_query_part($in_standard_number);
    try {
         $stmt = $pdo->prepare($standard_query);
         $stmt->bindParam('standard_value',$standard_value);
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
     }catch (PDOException $pe){
       error_log("Error processing : report_print", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }
     $return_view_count = $num_of_rows;
	
    #The following logic builds the reporting result in a tabular format
	 if ($return_view_count > 0){
		 
	 	 while ($line = $stmt->fetch(PDO::FETCH_ASSOC)){
			 $format_anchor = "";
			 list($rfc_name,$rfc_number,$length) = split_rfc($line['DOC-ID']);
			 $formatted_rfc_number = sprintf('%s&nbsp;%d',$rfc_name,$rfc_number);
			 #Get the RFC number in proper format with link
			 $formatted_rfc_number = build_info_link($line['DOC-ID']);
			 #Get the File format link using format
			 $format_files_anchor = build_files_anchor($line['format'],$rfc_number);
			 if ($obs_show){
				 print "<tr>";
				 if (((is_null($line['SEE-ALSO'])) || ($line['SEE-ALSO'] == ""))){
								print "<td>".$formatted_rfc_number."</td>";
				 } else {
								$formatted_see_also = build_see_also($line['SEE-ALSO']);
								#Check if the see-also is a subseries number if yes then go for subseries handling logic
								#Else print as it is
								if(preg_match('/^(bcp|BCP|std|STD|fyi|FYI)/',$line['SEE-ALSO'])){
									$subseries_status = subseries_logic_handling($line);
									print "<td>".$formatted_rfc_number." ".$subseries_status."&nbsp;".$formatted_see_also."</p></td>";					
								} else {/*Case when see-also is RFC number*/
								#Get the see-also links using see-also field
								        print "<td>".$formatted_rfc_number."</td>";					
								}
				 }
				 if ($format_files_anchor){
							print "<td>".$format_files_anchor."</td>";
				} else {
							print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
						}
				#If DOC is obsoleted then it has to be shown as grey
				if ($line['TITLE']){
							if ($line['OBSOLETED-BY']){
								print "<td class=\"special\">".$line['TITLE']."</td>";
							}else {
								print "<td class=\"title\">".$line['TITLE']."</td>";
								}
				}else {
							print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
				}
				
				if 	($line['AUTHORS']){
							print "<td>".$line['AUTHORS']."</td>";
						}else {
							print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
				}
				
				if ($line['PUB-DATE']){
							$display_pub_date = handle_april_fool_date($line);
							print"<td>".$display_pub_date."</td>";
				}else {
							print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
				}
						
				#Get the more info data in proper format
				$more_info = get_more_info($line);
				if ($more_info){
							print "<td>".$more_info."</td>";
				}else {
							print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
				}
				
				if ($view_status){
							$formatted_status = get_formmated_status($line);
							print "<td>".$formatted_status."</td>";
				}else {
							print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
				}
						
						
						print "</tr>";									
			 }
			 else {/*No obsolete values in the result*/

					 if (!($line['OBSOLETED-BY'])){ 		 				 
								 print "<tr>";
								 if (((is_null($line['SEE-ALSO'])) || ($line['SEE-ALSO'] == ""))){
												print "<td>".$formatted_rfc_number."</td>";
								 } else {
												$formatted_see_also = build_see_also($line['SEE-ALSO']);
												#Check if the see-also is a subseries number if yes then go for subseries handling logic
												#Else print as it is
												if(preg_match('/^(bcp|BCP|std|STD|fyi|FYI)/',$line['SEE-ALSO'])){
													$subseries_status = subseries_logic_handling($line);
													print "<td>".$formatted_rfc_number." ".$subseries_status."&nbsp;".$formatted_see_also."</p></td>";					
												} else {
												#Get the see-also links using see-also field
													print "<td>".$formatted_rfc_number."</td>";					
												}
								 }
								 if ($format_files_anchor){
											print "<td>".$format_files_anchor."</td>";
								} else {
											print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
										}
								#If DOC is obsoleted then it has to be shown as grey
								if ($line['TITLE']){
											if ($line['OBSOLETED-BY']){
												print "<td class=\"special\">".$line['TITLE']."</td>";
											}else {
												print "<td class=\"title\">".$line['TITLE']."</td>";
												}
								}else {
											print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
								}
								
								if 	($line['AUTHORS']){
											print "<td>".$line['AUTHORS']."</td>";
										}else {
											print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
								}
								
								if ($line['PUB-DATE']){
											$display_pub_date = handle_april_fool_date($line);
											print"<td>".$display_pub_date."</td>";
								}else {
											print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
								}
										
								#Get the more info data in proper format
								$more_info = get_more_info($line);
								if ($more_info){
											print "<td>".$more_info."</td>";
								}else {
											print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
								}
								
								$formatted_status = get_formatted_status($line);
								if ($formatted_status){
											print "<td>".$formatted_status."</td>";
								}else {
											print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
								}
										print "</tr>";									
									 
							    }
				 }
			 
			 }//End of while
	 	 print "</table>";
	 }

}  
/***********************************************************************************************/
/*function : print_table_header*/
/*           Prints the table header depends on the Standard type				               */
/***********************************************************************************************/
function print_table_header($in_number,$in_sort,$obs_show,$in_ret_value,$in_obs_value){

		global $debug_vsh;
		
		if ($debug_vsh === true) {
		  print "print_table_header <br>";
                  print "In nuber is $in_number <br>";	
                  print "In sort value $in_sort <br>";
                  print "In obs show value $obs_Show <br>";
     	        }
	
		if (!($obs_show)){
			 //print "<table>";
			 print "<table class='statictable'>";	 
			 print "<tr><th><a href='standards.php?sortkey=Number&sorting=" . htmlspecialchars($in_sort) . "&sort_number=" . htmlspecialchars($in_number) . "&showRET=" . htmlspecialchars($in_ret_value) . "&showOBS=" . htmlspecialchars($in_obs_value) . "'>Number</a></th>";
			 print "<th>Files</th><th>Title</th><th>Authors</th>";
			 print "<th><a href='standards.php?sortkey=Date&sorting=" . htmlspecialchars($in_sort) . "&sort_number=" . htmlspecialchars($in_number) . "&showRET=" . htmlspecialchars($in_ret_value) . "&showOBS=" . htmlspecialchars($in_obs_value) . "'>Date</a></th>";	 
			 print"<th>More Info</th><th>Status</th></tr>";
		} else{
			 //print "<table>";
			 print "<table class='statictable'>";	 
			 print "<tr><th><a href='standards.php?sortkey=OBSRFC&sorting=" . htmlspecialchars($in_sort) . "&sort_number=" . htmlspecialchars($in_number) . "&showRET=" . htmlspecialchars($in_ret_value) . "&showOBS=" . htmlspecialchars($in_obs_value) . "'>Number</a></th>";
			 print "<th>Files</th><th>Title</th><th>Authors</th>";
			 print "<th><a href='standards.php?sortkey=DateOBS&sorting=" . htmlspecialchars($in_sort) . "&sort_number=" . htmlspecialchars($in_number) . "&showRET=" . htmlspecialchars($in_ret_value) . "&showOBS=" . htmlspecialchars($in_obs_value) . "'>Date</a></th>";	 
			 print"<th>More Info</th><th>Status</th></tr>";
			}
}
	
/***********************************************************************************************/
/*function : print_table_header_IS*/
/*           Prints the table header for Internet Standards				       */
/***********************************************************************************************/
function print_table_header_IS($in_number,$in_sort,$std_value,$in_ret_value,$in_obs_value){

		global $debug_vsh;
		global $section_name;
		
		if ($debug_vsh === true) {
		  print "print_table_header_IS\n";	
          var_dump($in_number);
          var_dump($in_sort);
     	}
	
		 //print "<table>";
		 print "<table class='statictable'>";	 
		 if ($std_value == "STDNO"){
			 print "<tr><th><a href='standards.php?sortkey=STDNO&sorting=" . htmlspecialchars($in_sort) . "&sort_number=" . htmlspecialchars($in_number) . "&showRET=" . htmlspecialchars($in_ret_value) . "&showOBS=" . htmlspecialchars($in_obs_value) . "'>STD #</a></th>";
	 		 print "<th><a href='standards.php?sortkey=Number&sorting=" . htmlspecialchars($in_sort) . "&sort_number=" . htmlspecialchars($in_number) . "&showRET=" . htmlspecialchars($in_ret_value) . "&showOBS=" . htmlspecialchars($in_obs_value) . "'>Number</a></th>";
			 print "<th>Files</th><th>Title</th><th>Authors</th>";
			 print "<th><a href='standards.php?sortkey=Date&sorting=" . htmlspecialchars($in_sort) . "&sort_number=" . htmlspecialchars($in_number) . "&showRET=" . htmlspecialchars($in_ret_value) . "&showOBS=" . htmlspecialchars($in_obs_value) . "'>Date</a></th>";	 
			 print"<th>More Info</th><th>Status</th></tr>";

		 }else {
			 print "<tr><th><a href='standards.php?sortkey=STDRET&sorting=" . htmlspecialchars($in_sort) . "&sort_number=" . htmlspecialchars($in_number) . "&showRET=" . htmlspecialchars($in_ret_value) . "&showOBS=" . htmlspecialchars($in_obs_value) . "'>STD #</a></th>";
			 print "<th><a href='standards.php?sortkey=NumberRET&sorting=" . htmlspecialchars($in_sort) . "&sort_number=" . htmlspecialchars($in_number) . "&showRET=" . htmlspecialchars($in_ret_value) . "&showOBS=" . htmlspecialchars($in_obs_value) . "'>Number</a></th>";
			 print "<th>Files</th><th>Title</th><th>Authors</th>";
			 print "<th><a href='standards.php?&sortkey=DateRET&sorting=" . htmlspecialchars($in_sort) . "&sort_number=" . htmlspecialchars($in_number) . "&showRET=" . htmlspecialchars($in_ret_value) . "&showOBS=" . htmlspecialchars($in_obs_value) . "'>Date</a></th>";	
			 print"<th>More Info</th><th>Status</th></tr>";
			 }
}

?>
