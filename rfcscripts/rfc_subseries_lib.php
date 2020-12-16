<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
/*
 * Script to process the display logic for rfc and subseries number
 * April 2017 Updates : Added the redirect link for Errata Id and RFC number - PN 
 * November 2020 : Modified the script to use PDO prepared statements - PN            
*/


include_once("db_connect.php");
#include_once("rfc_subseries_lib.php");
include_once("config.php");
$debug_rsmeta = FALSE;



/***********************************************************************************/
/*Function Builds the link to info page with given number				           */
/***********************************************************************************/

function build_info_link($in_number){
         global $debug_rsmeta;
	 $out_link_txt = "";
	 global $document_root;
	 $http_in_info_base_url = $document_root.'/info/';


	 list($in_data_name,$in_data_number,$length) = split_rfc($in_number); 
	 $in_data_name_link = strtolower($in_data_name);
         $http_in_data_name = sprintf('%s&nbsp;%d',$in_data_name,$in_data_number);
	 $info_link = sprintf('%s%d',$in_data_name_link,$in_data_number);


	 $http_in_info_url = $http_in_info_base_url.$info_link;
	 $format_in_data  = '<a href="' .$http_in_info_url. '"target="_blank">';
	 $format_in_data .= "$http_in_data_name"."</a>";
	 $out_link_txt .= $format_in_data;	    
	 
	if ($debug_rsmeta){
		  print "The more info anchor is.". $out_link_txt ."\n";
	}
	 
	return $out_link_txt;		
	
	
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


/***********************************************************************************/
/*Function Build anchor builds the correct format type anchor based on index.format*/
/***********************************************************************************/
function build_files_anchor($format,$rfcnumber){
	
		global $debug_rsmeta;	
	        global $document_root;
 	        $format_link = "";
		$format_ascii = "";
		$format_pdf = "";			
		$pdf_with_image = "";
		$anchor = "";
		
		
	        
                list($in_data_name,$in_data_number,$length) = split_rfc($rfcnumber); 
	        $in_data_name_link = strtolower($in_data_name);
		$rfctxt = sprintf("%s%d",$in_data_name_link,$in_data_number); // URL is rfcX.txt

                
		$format = trim($format);
	        $http_txt_url = $document_root. '/rfc/' . $rfctxt . '.txt';
		$http_pdftxt_url = $document_root. '/pdfrfc/' . $rfctxt . '.txt.pdf';
		$http_pdf_url = $document_root. '/rfc/' . $rfctxt . '.pdf';
	        $http_ps_url = $document_root. '/rfc/' . $rfctxt . '.ps';

		
		$format_ascii  = '<a href="' . $http_txt_url . '"target="_blank">';
                $format_ascii .= "ASCII"."</a>";
 	        
                $format_pdf  = '<a href="' . $http_pdftxt_url . '"target="_blank">';
                $format_pdf .= "PDF"."</a>";
		
                $pdf_with_image  = '<a href="' . $http_pdf_url . '"target="_blank">';
                $pdf_with_image .= "PDF with Images"."</a>";
		
                $format_ps = '<a href="' . $http_ps_url . '"target="_blank">';
                $format_ps .= "PS"."</a>";
		
		
		if (strpos($format, 'PS') !== false){
			$anchor = $format_ascii.", ".$format_ps.",".$format_pdf;
			}else {
				if ($format == "ASCII"){
				$anchor = $format_ascii.", ".$format_pdf;
			}elseif ($format == "ASCII, PDF"){
				$anchor = $format_ascii.", ".$format_pdf.", ".$pdf_with_image;
			} elseif($format == "PDF"){
				$anchor = $pdf_with_image;
			}
		}
	
	 if ($debug_rsmeta){
		  print "The anchor is.". $anchor ."\n";
		  print "The rfc number is.". $rfcnumber ."\n";
		  print "The rfctxt number is.". $rfctxt ."\n";

	 }
	 
	return $anchor;
}			


/***********************************************************************************/
/*Function get_more_info builds the More Information based on OBSOLETE/ONSOLETEDBY  */
/*UPDATE/UPDATED BY*/
/***********************************************************************************/
function get_more_info($record){
     global $debug_rsmeta;	
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
			  $more_info_txt .= ", ".$errata_txt;
		   	}
		  }
	 
	 if ($debug_rsmeta){
		  print "The more info text is.". $more_info_txt ."\n";
	 }
	 
	  return $more_info_txt;
}

/***********************************************************************************/
/*Function Build anchor for more info data 
/***********************************************************************************/

function build_more_info_anchor($in_data){
     global $debug_rsmeta;
     $out_data_txt = "";
     global $document_root;
     $http_in_data_base_url = $document_root. '/info/';

     $in_data_array = explode(",",$in_data);
     $count = count($in_data_array);
     $i = 0;
	 foreach ($in_data_array as $key=>$value){
		  $value = trim($value);
		  $i++;

		  if(preg_match('/^(ien|IEN|nic|NIC)/',$value)){


			    list($in_data_name,$in_data_number,$length) = split_rfc($value); 
				$http_in_data_name = sprintf('%s&nbsp;%d',$in_data_name,$in_data_number);
				if ($i == $count){
					$out_data_txt .= $http_in_data_name;
				}else {
					$out_data_txt .= $http_in_data_name.", ";
				}
		  }else		  
		  {
			  list($in_data_name,$in_data_number,$length) = split_rfc($value);
			  $in_data_name_link = strtolower($in_data_name);
			  $http_in_data_name = sprintf('%s&nbsp;%d',$in_data_name,$in_data_number);
			  $in_data_info_name = sprintf('%s%d',$in_data_name_link,$in_data_number);
			  if ($i == $count){
				 	$http_in_data_url = $http_in_data_base_url.$in_data_info_name;					
					$format_in_data  = '<a href="' .$http_in_data_url. '"target="_blank">';
					$format_in_data .= "$http_in_data_name"."</a>";
					$out_data_txt .= $format_in_data;	    
			  }else {
				 	$http_in_data_url = $http_in_data_base_url.$in_data_info_name;										
					$format_in_data  = '<a href="' . $http_in_data_url . '"target="_blank">';
					$format_in_data .= "$http_in_data_name"."</a>";
					$out_data_txt .= $format_in_data.", ";	   
			  }
		  }
    }	
	if ($debug_rsmeta){
		  print "The more info anchor is.". $out_data_txt ."\n";
	}
	 
	return $out_data_txt;		
	 
	}
	



/************************************************************************************/
/*Please check if the given RFC has a errata entry or not*/
/************************************************************************************/	
function get_errata_entry($doc_id){
	 global $debug_rsmeta;
	 global $document_root;
         global $pdo;      

         $errata_txt = "";
	 
	 $errata_http_base_url = $document_root. '/errata';

	 list($out_name, $out_number, $length) = split_rfc($doc_id);
	 
         $out_name_link = strtolower($out_name);
	 
         try {

	      $errata_query = "SELECT * from `errata` where `DOC-ID` = :doc_id";
    
	      $stmt = $pdo->prepare($errata_query);  
              $stmt->bindParam('doc_id',$doc_id);
	      $stmt->execute();
              $num_of_rows = $stmt->rowCount();
         } catch (PDOException $pe){
              error_log("Error processing : get_errata_entry", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
         }


         if ($num_of_rows > 0){
                 $errata_http_url = $errata_http_base_url."/"."$out_name_link$out_number";  
		 $errata_txt = '<a href="'. $errata_http_url .'"target="_blank">';
                 $errata_txt.= "Errata</a>";		
         } else {
	 	$errata_txt = ""; 	 
         }
                
         #$pdo = null;	 
	 if ($debug_rsmeta){
		  print "The errata txt is.". $errata_txt ."\n";
	 }
	 
	 
     return $errata_txt;
}


/**************************************************************************************/
/*This function formatts the status in the proper format and attached pub status to it*/
/*If the status is historic*/
/**************************************************************************************/
	
function get_formmated_status($line,$from){
	 global $debug_rsmeta;
	 $out_status = "";
	 $status_lower = "";
	 
	 $status_array = explode(" ",$line['status']);
	 
	 foreach ($status_array as $key=>$value){
			  $value = strtolower($value);
			  $value = ucfirst($value);
			
			  $status_lower .= $value." ";

	 }
			  	 
	 $out_status = $status_lower;

	 #Print the was published part only for search page report 

#	 if ($from == 'report') {

		 $pub_status_array = explode(" ",$line['PUB-STATUS']);
	 
		 foreach ($pub_status_array as $key=>$value){
			  $value = strtolower($value);
			  $value = ucfirst($value);
			
			  $pub_status_lower .= $value." ";

	 	 }
		  	 
	 	$pub_status_ucfirst = $pub_status_lower;
	
		 if (($out_status) &&($out_status != $pub_status_ucfirst)){
			 $out_status = $out_status." "."(was published as ".$pub_status_ucfirst.")";
		 }
#	 }
	 if ($debug_rsmeta){
		 print "Out status is:".$out_status."\n";
		 print "Pub status is:".$pub_status_ucfirst."\n";
	 }
	 
	 return $out_status;
	}





/*
* This function defines the table cell data and displays cell in case empty
*/

function display_cell($in_cell_data){
      global $debug_rsmeta;

      if ($in_cell_data){
	  print "<td>".$in_cell_data."</td>";	
      }else {
	  print "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
      }

}

?>
