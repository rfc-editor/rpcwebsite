<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
/********************************************************************************************************/
/*March 2020 : Modified the position of acronym and group name sub elements of node group to match with */
/*             json record version. - PN                                                                */
/*             Further detiled changes will be followed in the next release to make this independant    */
/*             of position                                                                              */
/*Sept 2022  : Modified script to hanlde the value for IESG contact based on different streams and added*/
/*             the "Editorial" stream condition to the script - PN                                      */
/********************************************************************************************************/
include('/a/www/staff/insert_draft.php');
include('/a/www/staff/json_msg_lib.php');

$debug_parse = FALSE;
#$debug_parse = TRUE;

if (!isset($_SERVER['PHP_AUTH_USER'])) {
        header("WWW-Authenticate: Basic realm=\"Private Area\"");
        header("HTTP/1.0 401 Unauthorized");
        // only reached if authentication fails
        print "Sorry - you need valid credentials granted access to the private area!\n";
        exit;
}else { 
if (array_key_exists('draft',$_POST)){//If all the required field are entered
  $draft = $_POST['draft'];
  $draft = trim($draft);

  if ($debug_parse){
	  print "The post array is <br/>";
	  print_r($_POST);
  }


		  // Use the curl method to read the file contents into a string variable,
		  // and decode the string using json_decode then parse the string into a data structure
		   $fields = array();
		   $name = "";
		   $acronym = "";
		   $type = "";
		   $group_name = "";
		   $acronym_value = "";
		   $type_value = "";
		   $group_name_value = "";
		   $iesg_state = "";
		   $expires = "";
		   $rev = "";
		   $rfceditor_state="";
		   $state = "";
		   $iana_review_state = "";
		   $time = "";
		   $iana_action_state = "";
		   $aliases = "";
		   $stream = "";
		   $std_level = "";
		   $authors = "";
		   $email = "";
		   $organization = "";
		   $pages = "";

		   $ch = curl_init();
#		   curl_setopt($ch, CURLOPT_URL,"http://statesync.ietf.beta.iola.dk/doc/$draft/doc.json");
		   curl_setopt($ch, CURLOPT_URL,"https://datatracker.ietf.org/doc/$draft/doc.json");

		   curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		   $output = curl_exec ($ch);
		   $info = curl_getinfo($ch);
		   curl_close ($ch);


		   if ($info['http_code']== '404'){
			print "FAIL";
			print "<h3>JSON record not present for draft $draft</h3>";	
		   }else {

			 if ($debug_parse){
				print "<h3>JSON input is: </h3>";
				print $output;
				print "<br/>";
		   	 }

		         if ($debug_parse){
				print	"<h3>Input record details </h3>";
			        print   "----------------------<br/>"	;
		         }

			$data = json_decode($output,true);

			foreach ($data as $key => $value){
				$value = str_replace("<","&lt;",$value);
				$value = str_replace(">","&gt;",$value);
       
			if ($debug_parse){
				print "<h3>$key</h3>";
			}

			switch ($key){
				case 'shepherd': /*DOC-SHEPHERD CASE*/
						  if ($value){			
							$format_value = get_value($value,$key);
							$doc_email =  preg_split('/&lt;/',$format_value);	
							$format_value = $doc_email[1];
							$format_value = str_replace("&gt;","",$format_value);
							$format_value = str_replace("<br/>","",$format_value);
							$format_value = rtrim($format_value);
							$fields['DOC-SHEPHERD']= $format_value;
							$format_value = "Original value $value and formatted is $format_value";
						  }else {
							$fields['DOC-SHEPHERD']= $value;
							$format_value = $value;
						  }
						  break;
				case 'group':   /*GROUP CASE WHICH GIVES ACRONYM, TYPE AND AREA NAME*/
						if ($value){
							$format_value = get_value($value,$key);
							$group_line = preg_split('/<br\/>/',$format_value);
							$group_count = count($group_line);
							for($i = 0; $i <= $group_count; $i++){
								if ($i == 0){
									$group_line1 = $group_line[$i];
									$group_name_value = preg_split('/\|/',$group_line1);
									$group_name = $group_name_value[1];
								}elseif ($i == 1){
									$group_line2 = $group_line[$i];
									$type_value = preg_split('/\|/',$group_line2);
									$type = $type_value[1];
								}elseif ($i == 2){
									$group_line2 = $group_line[$i];
									$acronym_value = preg_split('/\|/',$group_line2);
									$acronym = $acronym_value[1];
								}
			
							}
						}else {
							
							$group_name = $value;
                                                        $type = $value;
							$acronym = $value;
						
                                                }   
						break;
				case 'name':  /*GIVES THE DRAFT NAME ONLY*/  	
						$format_value = get_value($value,$key);
						$name = $value;
						break;
				case 'stream': /*GIVES THE STREAM*/
						$format_value = get_value($value,$key);
						$stream = $value;
						break; ;
				case 'title': /*GIVES THE TITLE*/
						$format_value = get_value($value,$key);
						$fields['title']= $value;
						break;
				case 'iesg_state': /*GIVES THE IESG STATE*/
						  $format_value = get_value($value,$key);
						  $iesg_state = $value; 
						  break;
				case 'abstract': /*GIVES THE ABSTRACT*/
						$format_value = get_value($value,$key);
						$fields['abstract'] = $value;
						break;
				case 'expires': /*GIVES THE EXPIRES*/
						$format_value = get_value($value,$key);
						$expires = $value;
						break;
				case 'rev': /*GIVES THE REVISION NUMBER FOR THE DRAFT*/
					    $format_value = get_value($value,$key);
					    $rev = $value;	
					    break;	
				case 'consensus': /*GIVES THE CONSENSUS BIT*/
						  $format_value = get_value($value,$key);
						  $fields['consensus']= $value;	
						  break;
				case 'rfceditor_state': /*GIVES THE rfceditor_state*/
							$format_value = get_value($value,$key);
							$rfceditor_state = $value;	
							break;
				case 'state': /*GIVES THE state*/
					      $format_value = get_value($value,$key);
					      $state = $value;
					      break;
				case 'iana_review_state': /*GIVES THE iana_review_state*/
							  $format_value = get_value($value,$key);
							  $iana_review_state = $value;
							  break;
				case 'time': /*GIVES THE time*/
					     $format_value = get_value($value,$key);
					     $time = $value;	
					      break;
				case 'authors': /*GIVES THE authors*/
						if ($value){
							$format_value = get_value($value,$key);
							$author_line = preg_split('/<br\/>/',$format_value);
							$author_count = count($author_line);
							for($i = 0; $i < $author_count; $i++){
									$author_line1 = $author_line[$i];
									$author_value = preg_split('/\|/',$author_line1);
									if ($author_value[0] == 'name')	{
										$author_array = preg_split('/\s/',$author_value[1]);
										foreach ($author_array as $key => $value){
											if (($value == 'Dr.') || ($value == 'Ph.D.')){
												unset($author_array[$key]);	
											}
										}
										$author_array_count = count($author_array);				
										$author_f_name = substr($author_array[0],0,1);
										for ($author_i=1; $author_i< $author_array_count; $author_i++){
										    $author_l_name = $author_array[$author_i];
										}
										    $author_name = $author_f_name.". ".$author_l_name;
										    if($authors){
											$authors .= ", ".$author_name;
										    }else {
											$authors .= $author_name;

										    }
									}elseif ($author_value[0] == 'email'){
										if ($email){
											$email .= ", ".$author_value[1];
										}else {
											$email .= $author_value[1];
										}	
									}elseif ($author_value[0] == 'affiliation'){
										if ($organization){
											if ($author_value[1]) {
											$organization .= ", ".$author_value[1];
											}else{
											$organization .= ", None";
											}	
										}else {
											
											if ($author_value[1]) {
											$organization .= $author_value[1];
											}else{
											$organization .= "None";
											}	
											#$organization .= $author_value[1];
										}	
									}
							}
						}else {
							$authors = $value;
							$email = $value;
							$organization = $value;
						}
						$fields['authors']= $authors;
						$fields['email']= $email;	
						$fields['organization'] = $organization;
						break;
				case 'std_level': /*GIVES THE std_level*/
						  $format_value = get_value($value,$key);
						  $std_level = $value;
						  
						  break;   
				case 'intended_std_level': /*GIVES THE intended_std_level*/
							  $format_value = get_value($value,$key);
							  $fields['PUB_STATUS'] = $value;
							  $fields['STATUS'] = $value;
							  break;
				case 'ad':/*GIVES THE iesg_contact INFORMATION*/
                                                if ($value){ 
                                                        if (($stream == 'IAB') || ($stream == 'IRTF') || ($stream == 'ISE') || ($stream == 'RSAB')) { # Covering conditions for source 'IAB'/'IRTF'/'Independent'/'Editorial' 
                                                                $fields['iesg_contact']= ''; 
                                                                $format_value = "Original value $value and format not set as $type "; 
 
                                                        } else { 
                                                                $format_value = get_value($value,$key); 
                                                                $iesg_contact_arr =  preg_split('/&lt;/',$format_value);         
                                                                $format_value = $iesg_contact_arr[1]; 
                                                                $format_value = str_replace("&gt;","",$format_value); 
                                                                $format_value = str_replace("<br/>","",$format_value); 
                                                                $format_value = rtrim($format_value); 
                                                                $fields['iesg_contact']= $format_value; 
                                                                $format_value = "Original value $value and formatted is $format_value";  
                                                        } 
                                                } else { 
                                                        $fields['iesg_contact']= $value; 
                                                        $format_value = $value; 
                                                } 
                                                break;
#				case 'iana_action_state': /*GIVES THE iana_action_state*/
/*							  $format_value = get_value($value,$key);
							  $iana_flag = get_iana_flag($value);
							  $fields['iana_flag'] = $iana_flag;	
							  break;
*/
				case 'aliases':  /*GIVES THE aliases*/
						$format_value = get_value($value,$key);
						$aliases = $value;
						break;
				case 'pages':  /*GIVES THE submitted page counts*/
						$format_value = get_value($value,$key);
						$fields['pages'] = $value;
						break;

				default: 
					 break;
		

			}/*End switch statement*/	

			if ($debug_parse){
				print "$format_value<br/>";
			}

			}/*End for statement*/	


			 /*Get the source from stream and type*/
			 $fields['draft'] = "$name-$rev";
			 if (($stream == 'IETF') && ($type == 'WG')){
			     $fields['source'] = $group_name;	
			 }elseif (($stream == 'IETF') && ($type == 'Area')){
			     $fields['source'] = 'IETF - NON WORKING GROUP';	
			 }elseif (($stream == 'IETF') && ($type == 'Individual')){
			     $fields['source'] = 'IETF - NON WORKING GROUP';	
			 }elseif ($stream == 'IAB'){
			     $fields['source'] = 'IAB';	
			 }elseif($stream == 'IRTF'){
			     $fields['source'] = $group_name;	
			 }elseif ($stream == 'ISE'){
			     $fields['source'] = 'INDEPENDENT';
			     $fields['consensus'] = 'N/A';	
			 }

		         /*State us Edit for the draft*/

		         if ($iesg_state != 'RFC Published'){
			     $fields['state_id'] = '99';
		         }

                         $fields['date_received']= date('Y-m-d'); 

                        /*The final output is Fields array that can be used by insert script for insertion*/
                        /*Printing details no need */
			
			if ($debug_parse){
				   print "<h3>The final input record is</h3>";	
				   foreach ($fields as $key => $value){
					print "<b>$key</b>  : $value <br/>";
	   			   }
		        }

			/*Call the insert script and give response to datatracker script*/
			/*DO not call insertion script for RFC Published drafts send direct email to RFC_ED*/
   
			if ($iesg_state != 'RFC Published'){

  	   		/*Call the insert script and give response to datatracker script*/
	    			$return_status =  insert_draft_process($fields); 
	    			if ($return_status){
					print "OK"; /*Send insert OK to datatracker*/
				}else {
					print "FAIL"; /*Send insert FAIL to datatracker*/
	    			}
   			}else{
				$pub_draft = $fields['draft'];
        			send_published_mail($pub_draft);
			
				if ($debug_parse){
					print "<h3>Published draft status change</h3>";
				}
				print "OK";
			}/*RFC Published case*/
#SPECIAL 404 else
		   } /*404 else end*/
#SPECIAL 404 else
  //        }//Authorize user else
//  }//Config file else
}else {
        print "FAIL";
	print "<h3>Input not proper</h3>";

}
}//HTTP Authentication else
/************************************************************************************/
/*This function returns the exact value from each element of json record            */
/************************************************************************************/

function get_value($in_value,$in_key){

	$out_value = "";;	
	if (is_array($in_value)){
		foreach ($in_value as $k => $v){
			if (is_array($v)){
			    foreach($v as $k_a => $v_a){
				$out_value .= $k_a ."|".$v_a ."<br/>";;
				
			    }	

			}else {
				$out_value .= $k ."|".$v ."<br/>";;


		        }
		}
	}else {
			$out_value .= "$in_value <br/>";
	}

return $out_value;
}
/******************************************************************************************/
/*This function gets the correct iana_flag based on the iana_action_value*/
/******************************************************************************************/
function get_iana_flag($in_value){
$out_value = "";
switch($in_value){
	case 'New Document':
			     $out_value = "";	
		             break;
	case 'In Progress' : 
			     $out_value = 'yes';	
			     break;

	case 'Waiting on Authors':
				  $out_value = 'yes';
				  break;
        case 'Waiting on ADs':
				$out_value = 'yes';
				break;
	case 'Waiting on WGC':
				$out_value = 'yes';
				break;
        case 'Waiting on RFC Editor':
					$out_value = 'yes';
					break;
	case 'RFC-Ed-Ack':
			   $out_value = 'no';
			   break;
 	case 'On Hold':
			$out_value = 'yes';
			break;
	case 'No IC':
			$out_value = 'no';
			break;
        default: break;		

}

return $out_value;

}

?>
