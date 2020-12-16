<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
/******************************************************************************************/
/*insert_draft.php : This script is used to add draft to RPC system from datatracker after*/
/*                   approval of a draft*/
/*March 20202 : Modified the formt to HTML, TEXT, v3PDF, XML from ASCII - PN              */
/* November 2020 : Modified the script to use PDO prepared statements - PN                */
/******************************************************************************************/
include('/a/www/staff/db_connect.php');
include('/a/www/staff/editor_lib.php');

#$debug_insert_draft = true;
$debug_insert_draft = false;


/***************************************************************************************/
/*Function : insert_draft_process                                                      */
/*           This function calls the various functions to check if the dattracker      */
/*           approved draft exists in the queue  Or not.                               */
/***************************************************************************************/

function insert_draft_process($fields) {
	global $debug_insert_draft;
	$insert_status = false;


        if ($fields){
		if (insert_draft($fields)){
		#	$insert_status = false; /*Duplicate draft so check for field change and any updates*/
			$insert_status = true;  /*Datatracker needs OK message so make insert_status true */
		}else {
		        $insert_db_status = insert_to_db($fields);
			if ($insert_db_status){
                                /*Editor assignment*/

                                if (insert_editor_assignments($fields)){
					send_new_draft_mail($fields['draft']);	
				}else {
				 	if ($debug_insert_draft === true) { 
						print "Insert failed on editor assignment row is not added to database</h3>".mysqli_error($db_link);		
			 	}

				}
		        }else {
				/**/
				 if ($debug_insert_draft === true) { 
					print "Row is not added to database<br/>";		
			         }
			} 
			$insert_status = true; /*Not duplicate so insert to database NO ADD*/
			/*If successful insert status is true else status is false*/
		}
	}

return $insert_status;
}

/***************************************************************************************/
/*Function : insert_draft                                                              */
/*           This function checks if the dattracker approved draft exists in the queue */
/*           Or not. It sends "Duplicate" mail if the draft exists and "New draft mail"*/
/*           not present in the queue                                                  */
/***************************************************************************************/

function insert_draft($record){

       	global $debug_insert_draft;
	$return = 0;

        if (is_draft_exist($record['draft'])){
		$return = 1;	/*Duplicate draft*/
		send_duplicate_mail($record['draft']);
	}else {
		if (version_change($record)){//Draft version is change
			$return = 1; //Version change

		}else {
			$return = 0;   //New draft

		}

	}
return $return;
}

/***************************************************************************************/
/*Function : if_draft_exist                                                            */
/*           This function checks if the dattracker approved draft exists in the queue */
/*           Or not. It returns "true" if the draft exists and "false" if draft is not */
/*           present in the queue                                                      */
/***************************************************************************************/
function is_draft_exist($draft_name){
   global $debug_insert_draft;
   global $pdo;   
   $draft_exist = false;

   $params = [":draft_name" => $draft_name];
   $state_ids = [14,19,16,3,12,13,18];
   $in = "";
   foreach ($state_ids as $i => $item)
   {
       $key = ":state_id".$i;
       $in .= "$key,";
       $in_params[$key] = $item; // collecting values into key-value array
   }
   $in = rtrim($in,","); 

   $sql_statement = "SELECT `internal_key` FROM `index` WHERE draft = :draft_name and `state_id` NOT IN ($in)";

   if ($debug_insert_draft === true) {
       print("<h5>is_draft_exist</h5>");
       var_dump($draft_name);     print("<br />");
       var_dump($sql_statement); print("<br />");
   }
   try {        
       $stmt = $pdo->prepare($sql_statement);
       $stmt->execute(array_merge($params,$in_params)); 
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
         error_log("Error processing : $sql_statement", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }  
   
   $row_count = $num_of_rows;
   if ($debug_insert_draft === true) { 
       print "Row count is <br/>";		
		var_dump($row_count); print("<br />"); 
   }
   $row = $stmt->fetch(PDO::FETCH_ASSOC);
   if ($row_count == '1') {
       $draft_exist = true;         // draft exist in the table
       if ($debug_insert_draft === true) { 
           print "<h3>Draft $draft_name exist in the queue</h3>";
       }
   } else {
       $draft_exist = false;               //draft does not exist in the table
       if ($debug_insert_draft === true) { 
      	   print "<h3>Draft $draft_name does not exist in the queue</h3>";
       }
   }	
return $draft_exist;
}


/***************************************************************************************/
/*Function : version_change                                                            */
/*           This function checks if the dattracker approved draft version is same as  */
/*            the draft in the queue                                                   */
/***************************************************************************************/
function version_change($record){
   global $debug_insert_draft;
   global $pdo;
 
   $draft_version_change = false; 
        
   list($json_version,$json_draft) = get_version($record['draft']);

   if ($debug_insert_draft === true) { 
		print("JSON draft name $json_draft and version $json_version<br />"); 
   }
   $json_draft_orig = $json_draft;
   $json_draft = $json_draft.'-%';
   $params = [":json_draft" => $json_draft];
   $state_ids = [14,19,16,3,12,13,18];
   $in = "";
   foreach ($state_ids as $i => $item)
   {
       $key = ":state_id".$i;
       $in .= "$key,";
       $in_params[$key] = $item; // collecting values into key-value array
   }
   $in = rtrim($in,","); 
   $version_sql = "SELECT `DOC_SHEPHERD`,`ABSTRACT`,`consensus_bit`,`AUTHORS`,`EMAIL`,`ORGANIZATION`,`PUB-STATUS`,`STATUS`,`IESG_CONTACT`,`DRAFT`,`SOURCE`,`state_id`, `DATE_RECEIVED`,`internal_key` FROM `index` WHERE draft like :json_draft AND `state_id` NOT IN ($in)";
   if ($debug_insert_draft === true) {
       print("<h5>version_change</h5><p>");
       var_dump($json_draft);     print("<br />");
       var_dump($version_sql); print("<br />");
   }
   try {        
       $stmt = $pdo->prepare($version_sql);
       $stmt->execute(array_merge($params,$in_params)); 
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
         error_log("Error processing : version_change", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }  
   $row_count = $num_of_rows;
   if ($debug_insert_draft === true) { 
       var_dump($row_count); 
       print("<br />"); 
   }
   if ($row_count == 0) {
       $draft_version_change = false;         // There is no version change
   } elseif ($row_count == 1){
       while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
	   $DOC_SHEPHERD = $row['DOC_SHEPHERD'];
	   $ABSTRACT = $row['ABSTRACT'];
	   $consensus_bit = $row['consensus_bit'];
	   $AUTHORS = $row['AUTHORS'];
	   $EMAIL = $row['EMAIL'];
	   $ORGANIZATION = $row['ORGANIZATION'];
	   $PUB_STATUS = $row['PUB-STATUS'];
	   $STATUS = $row['STATUS'];
	   $IESG_CONTACT = $row['IESG_CONTACT'];
	   $DRAFT = $row['DRAFT'];
	   $SOURCE = $row['SOURCE'];
	   $state_id = $row['state_id'];
	   $DATE_RECEIVED = $row['DATE_RECEIEVED'];
	   $internal_key = $row['internal_key'];
       }
       list($rfc_version,$rfc_draft) = get_version($DRAFT);
       if ($debug_insert_draft === true) { 
	   print("RFC db draft name $rfc_draft and version $rfc_version<br />"); 
       }
       if (($rfc_draft == $json_draft_orig) && ($rfc_version != $json_version)){ 
           if ($debug_insert_draft === true) { 
	       print("<h3>Version change in JSON draft and RPC DB draft</h3>"); 
	   }
	       send_version_change_mail($DRAFT,$json_version);/*Version change email*/
	       $draft_version_change = true;               //draft exist in the table
	   }
   }//End elseif ($row_count == 1)
return $draft_version_change;
}/*End version change*/


/***************************************************************************************/
/*Function : get_version                                                               */
/*           This function gets the version number and draft name from the draft       */
/***************************************************************************************/
function get_version($in_draft){
   global $debug_insert_draft;

   $version_number = substr($in_draft,-2); //Get the version number	
   $version_draft = substr($in_draft,0,-3);//Get the draft name

return array($version_number,$version_draft);
}

/***************************************************************************************/
/*Function : insert_to_db                                                              */
/*           This function inserts the draft to database                               */
/***************************************************************************************/
function insert_to_db($record){
   global $debug_insert_draft;
   global $pdo;
   
   $insert_string = "";

   $doc_shepherd = $record['DOC-SHEPHERD'];
   $title = $record['title'];
   $abstract = $record['abstract'];
   $consensus = $record['consensus'];
   $authors = $record['authors'];
   $email = $record['email'];
   $organization = $record['organization'];
   $pub_status = $record['PUB_STATUS'];
   $status = $record['STATUS'];
   $iesg_contact = $record['iesg_contact'];
   $draft = $record['draft'];
   $source = $record['source'];
   $state_id = $record['state_id'];
   $date_received = $record['date_received'];
   $page_count = $record['pages'];
   $sub_page_count = $record['pages'];

   /*Get the char count and [*page count* not used now] for draft*/
   list($char_count,$file_page_count)= get_counts($draft);
       
   /*Some constant values*/
   $doc_id = 'RFC';
   $type = 'RFC';
   $format = 'HTML, TEXT, v3PDF, XML'; 
   
   $insert_query = "INSERT INTO `index` (`DOC_SHEPHERD`,`TITLE`, `ABSTRACT`,`consensus_bit`,`AUTHORS`,`EMAIL`,`ORGANIZATION`,`PUB-STATUS`,`STATUS`,`IESG_CONTACT`,`DATE_RECEIVED`,`DRAFT`,`SOURCE`,`state_id`,`DOC-ID`,`TYPE`,`FORMAT`,`CHAR-COUNT`,`PAGE-COUNT`,`sub_page_count`) VALUES (:doc_shepherd,:title,:abstract,:consensus,:authors,:email,:organization,:pub_status,:status,:iesg_contact,:date_received,:draft,:source,:state_id,:doc_id,:type,:format,:char_count,:page_count,:sub_page_count)";

   if ( $debug_insert_draft === true) {
       print("<h2>insert_to_db</h2>");
       print("<h3>Query</h3>");
       print_r($insert_query);
   }
   try {
       $stmt = $pdo->prepare($insert_query);
       $stmt->bindParam('doc_shepherd',$doc_shepherd);
       $stmt->bindParam('title',$title);
       $stmt->bindParam('abstract',$abstract);
       $stmt->bindParam('consensus',$consensus);
       $stmt->bindParam('authors',$authors);
       $stmt->bindParam('email',$email);
       $stmt->bindParam('organization',$organization);
       $stmt->bindParam('pub_status',$pub_status);
       $stmt->bindParam('status',$status);
       $stmt->bindParam('iesg_contact',$iesg_contact);
       $stmt->bindParam('date_received',$date_received);
       $stmt->bindParam('draft',$draft);
       $stmt->bindParam('source',$source);
       $stmt->bindParam('state_id',$state_id);
       $stmt->bindParam('doc_id',$doc_id);
       $stmt->bindParam('type',$type);
       $stmt->bindParam('format',$format);
       $stmt->bindParam('char_count',$char_count);
       $stmt->bindParam('page_count',$page_count);
       $stmt->bindParam('sub_page_count',$sub_page_count);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : insert query insert draft", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   if ($num_of_rows > 0){
       $status = 1; 
       if ( $debug_insert_draft === true) {
	   print "<h3>$draft added to queue</h3>";
       }
   } else{
       $status = 0;   
       if ( $debug_insert_draft === true) {
           print "<h3>Insert failed on index : insert-to_db for $draft</h3>" . mysqli_error($db_link);
       }
   }		
return $status;
}
/*******************************************************************************************/
/*Function : insert_editor_assignments                                                     */
/*           This function inserts the editor entry for new draft(CE/PE/RE/PUB)            */
/*******************************************************************************************/
function insert_editor_assignments($record){
   global $debug_insert_draft;

   $new_internal_key = get_new_internal_key($record['draft']);

   $pe_editor_status = set_editor($new_internal_key,'1','XX');
   $ce_editor_status = set_editor($new_internal_key,'2','XX');
   $re_editor_status = set_editor($new_internal_key,'3','XX');
   $pub_editor_status = set_editor($new_internal_key,'4','XX');

   if ($pe_editor_status && $ce_editor_status && $re_editor_status && $pub_editor_status){
       $return_editor_status = 1;
   } else {
       $return_editor_status = 0;
   }

return $return_editor_status;	

}

/***************************************************************************************/
/*Function : get_new_internal_key                                                      */
/* Retrieve the internal_key for a newly added document so the editor                  */
/* assignment code will work.                                                          */
/***************************************************************************************/
function get_new_internal_key($draft) {
   global $pdo;
   $query =
          "SELECT internal_key FROM `index` WHERE draft=:draft";
   try {
       $stmt = $pdo->prepare($query);
       $stmt->bindParam('draft',$draft);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : get_new_internal_key", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
       $internal_key = -1;
   }
   
   $count = $num_of_rows;
   if ($count == 1) {
       $row = $stmt->fetch(PDO::FETCH_ASSOC);
       $internal_key = $row['internal_key'];
   }
   return $internal_key;
}

/***************************************************************************************/
/*Function : get_counts                                                                */
/* Gets the byte count and page count for the newly added draft                        */
/***************************************************************************************/

function get_counts($draft){

	global $debug_insert_draft;
	$bytes = "";
	$pages = "";
        
        $filename = '/a/ftp/in-notes/internet-drafts/'.$draft.'.txt';
        
	if (!(file_exists($filename))){
            if ( $debug_insert_draft === true) {
		print "<h3>Text draft file $filename not present</h3>";
            }
	}else {
		$bytes = filesize($filename);
		$tailcount = shell_exec("tail -2 $filename");
		$pagestring = strstr($tailcount,'Page');
		$pagestring = strstr($pagestring,' ');
		$pieces = explode("]",$pagestring);
		$pages = $pieces[0];
		$pages = trim($pages);
       }
return array($bytes,$pages);
}
?>
