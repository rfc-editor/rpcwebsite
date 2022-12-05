<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
/**************************************************************************************************************************************************************************/
/* script name : rfc_search_detail.php                                                                                                                                    */
/* June 2018  : Added security feature to the script - PN                                                                                                                 */
/* May 2019  : Added security feature to the script - PN                                                                                                                  */
/* August 2020  : Added checking for from_year, to_year, from_month, to_month to the script - PN                                                                          */
/* August 2020  : Added checking for pubstatus to the script - PN                                                                                                         */
/* November 2020 : Modified the script to use PDO prepared statements - PN                                                                                                */
/* August 2022 : Added 'Editorial' stream to the script - PN                                                                                                              */
/* December 2022 : Modified the script to handle search when only rfc or RFC is entered - PN                                                                              */
/**************************************************************************************************************************************************************************/

include("rfc_headers.php");
include("rfc_lib_new.php");
page_header("RFC Search Detail","js/jquery.js","js/validate_rfcsearch.js", "","css/rfcsearch_wp.css","selectPubDateType()");

#$debug_search_detail = true;
$debug_search_detail = false;
$criteria = array();
if ($debug_search_detail === true) {
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

//Now get the values from the combined box.

$build_input = array();

/*post_array is used to capture errors from POST data through combined box*/
/*If error is captured then build_input filled with error value */

$post_errors = array();
$post_errors = getErrors($_POST);

if (count($post_errors)){
    $build_input['Error'] =  getErrors($_POST);
}

if (empty($build_input)){
   foreach ($_POST as $name => $value){
       if ($name == 'combo_box'){
           if ((preg_match('/^\"/',$value)) && (preg_match('/\"$/',$value))){// Strings within quotes
               $build_input['title'] = $value;
           } else {
               if (preg_match('/^\d{1,4}$/',$value)){
	           $build_input['rfc'] = $value;
               } else {
	           if (preg_match('/^rfc|RFC|std|STD|bcp|BCP|fyi|FYI/',$value)){
	               $build_input['rfc'] = $value;
	           } else {/*Decide if it is a Title/keyword or Authorlast name*/ 
	               if (preg_match('/^yang|YANG|Yang/',$value)) {/*Exception yang case*/
		           $build_input['title'] = $value;
	               } else {
		           if (preg_match('/\"/',$value)){/*With quotes should not come in this case*/
			       $build_input['Error'] = 'Unexpected input value';
		           } else {		
		               global $pdo;
			       $a_value = strtolower($value); 
			       $a_value = '%'.$a_value.'%';
			       try { 
		                   $author_query = "select `doc-id` from `index` where `authors` LIKE :a_value and state_id=:state_id";
			           $stmt = $pdo->prepare($author_query);
			           $stmt->bindParam('a_value',$a_value);
			           $stmt->bindValue('state_id',14);
			           $stmt->execute();
			           $num_of_rows = $stmt->rowCount();
			       } catch (PDOException $pe){
			           error_log("Error processing : rfc_search_detail.php", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
			       }
			       $return_author_count = $num_of_rows;
			       if ($return_author_count > 0){
			           $build_input['author'] = $value;
			       } else { 
			           $build_input['title'] = $value;
		               }
		           }
	               }
	           }
               }
               if ($debug_search_detail === true) {
	           print ("Now I have my combo box input as\n");
	           print_r($build_input);
               }
           }
       } 
   }//Foreach
}// Empty build input

if (!(empty($build_input))){
   $errors = getErrors($build_input);
}else {
//Validate the form and get the erros
$errors = getErrors($_GET);
}
if(!count($errors)){
#  echo 'Your form has no errors.';


// These next three variables control how the script responds to a query.
// $rfc holds the digits of the RFC doc-id field (data is truncated to
// limit number of digits to the allowed maximum).

$rfc=null;
$title=null;
$author=null;
$area_acronym=null;
$stream_name=null;
$wg_acronym=null;
$pub_status=null;
$pub_date_type=null;
$this_month=null;
$this_year=null;
$from_month=null;
$from_year=null;
$from_date=null;
$to_month=null;
$to_year=null;
$abstract=null;
$keywords=null;
$pubstatus=null;
$std_trk=null;
$abstract_box= null;

###BUILD Criteria from combo box

        if (!(empty($build_input))){
        	if (array_key_exists('rfc',$build_input)) {
                    $rfc = $build_input['rfc'];
	            if ($rfc != null) $criteria['rfc'] = $rfc;
                }else if ($build_input['author']){
                    $author = $build_input['author']; 
                    if ($author != null) $criteria['author'] = $author;
                    }else
                    {
                    $title = $build_input['title'];
        	    if ($title != null) $criteria['title'] = $title;        
                    }
               $pubstatus_array = array();
               $pubstatus_array['0'] = 'Any';
               $criteria['pubstatus'] = $pubstatus_array;
               $criteria['pub_date_type'] = "any";
               if ($debug_search_detail === true) {
                   print ("Now I have my criteria as\n");
                   print_r($criteria);
               }
        }
	###RFC
	if (array_key_exists('rfc',$_POST)) {
		 $rfc = $_POST['rfc'];
	}elseif (array_key_exists('rfc',$_GET)){
		 $rfc = $_GET['rfc'];
		}
	#if ($rfc != null && is_numeric($rfc)) $criteria['rfc'] = $rfc;
	if ($rfc != null) $criteria['rfc'] = $rfc;
		
	###TITLE
	if (array_key_exists('title',$_POST)) {
		 $title = $_POST['title'];
	}elseif (array_key_exists('title',$_GET)) {
		 $title = $_GET['title'];	}
	if ($title != null) $criteria['title'] = $title;
	
	###author
	if (array_key_exists('author',$_POST)) {
		 $author = $_POST['author'];
	}elseif (array_key_exists('author',$_GET)) {
		 $author = $_GET['author'];
	}
	if ($author != null) $criteria['author'] = $author;
	
	#area acronym
	if (array_key_exists('area_acronym',$_POST)) {
		 $area_acronym = $_POST['area_acronym'];
	}elseif (array_key_exists('area_acronym',$_GET)) {
		 $area_acronym = $_GET['area_acronym'];
	}
	if ($area_acronym != null) $criteria['area_acronym'] = $area_acronym;
	
	#stream name
	if (array_key_exists('stream_name',$_POST)) {
		 $stream_name = $_POST['stream_name'];
	}elseif (array_key_exists('stream_name',$_GET)) {
		 $stream_name = $_GET['stream_name'];
	}
	if ($stream_name != null) $criteria['stream_name'] = $stream_name;
	
	#wg_acronym
	if (array_key_exists('wg_acronym',$_POST)) {
		 $wg_acronym = $_POST['wg_acronym'];
	}elseif (array_key_exists('wg_acronym',$_GET)) {
		 $wg_acronym = $_GET['wg_acronym'];
	}
#	if ($wg_acronym != null) $criteria['wg_acronym'] = $wg_acronym;
	
	
	if ($wg_acronym != null) {
		$criteria['wg_acronym'] = $wg_acronym;
		if (($wg_acronym == 'none') || ($wg_acronym == 'NONE')|| ($wg_acronym == 'None')){
			 	$criteria['source'] = 'IETF - NON WORKING GROUP';
		}
	}
		
	#status
	if (array_key_exists('pub_status',$_POST)) {
		 $pub_status = $_POST['pub_status'];
	}elseif (array_key_exists('pub_status',$_GET)) {
		 $pub_status = $_GET['pub_status'];
	}
	if ($pub_status != null )$criteria['pub_status'] = $pub_status;
	
	
	#status
	if (array_key_exists('pubstatus',$_POST)) {
		 $pubstatus = $_POST['pubstatus'];
	}elseif (array_key_exists('pubstatus',$_GET)) {
		 $pubstatus = $_GET['pubstatus'];
	}
	if ($pubstatus != null )$criteria['pubstatus'] = $pubstatus;
	
	#status standard track :: special case
	
	foreach ($pubstatus as $v){
	if ($v == 'Standards Track'){
		if (array_key_exists('std_trk',$_POST)) {
			 $std_trk = $_POST['std_trk'];
		}elseif (array_key_exists('std_trk',$_GET)) {
			 $std_trk = $_GET['std_trk'];
		}
		if ($std_trk != null )$criteria['std_trk'] = $std_trk;
	}
	}
	#from_month
	if (array_key_exists('from_month',$_POST)) {
		 $from_month = $_POST['from_month'];
	}elseif (array_key_exists('from_month',$_GET)) {
		 $from_month = $_GET['from_month'];
	}
	if ($from_month != null) $criteria['from_month'] = $from_month;
	
	#from_year
	if (array_key_exists('from_year',$_POST)) {
		 $from_year = $_POST['from_year'];
	}elseif (array_key_exists('from_year',$_GET)) {
		 $from_year = $_GET['from_year'];
	}
	if ($from_year != null) $criteria['from_year'] = $from_year;
	
	#Get the month number from month name and build the from date.
	if (($from_year != null) && ($from_month != null)){
		$from_date = build_date($from_month,1,$from_year,0);
		$criteria['from_date'] = $from_date;
	}elseif (($from_month == null) && $from_year != null){
		$from_month = "January";
		$from_date = build_date($from_month,1,$from_year,0);
		$criteria['from_date'] = $from_date;
	}
	if ($from_month != null) $criteria['from_month'] = $from_month;

    #pub_date_type
	if (array_key_exists('pub_date_type',$_POST)) {
		 $pub_date_type = $_POST['pub_date_type'];
	}elseif (array_key_exists('pub_date_type',$_GET)) {
		 $pub_date_type = $_GET['pub_date_type'];
	}
	if ($pub_date_type != null) $criteria['pub_date_type'] = $pub_date_type;
	
	
	#this_month

	#to_month
	if (array_key_exists('to_month',$_POST)) {
		 $to_month = $_POST['to_month'];
	}elseif (array_key_exists('to_month',$_GET)) {
		 $to_month = $_GET['to_month'];
	}
	if ($to_month != null) $criteria['to_month'] = $to_month;
	
	#to_year
	if (array_key_exists('to_year',$_POST)) {
		 $to_year = $_POST['to_year'];
	}elseif (array_key_exists('to_year',$_GET)) {
		 $to_year = $_GET['to_year'];
	}
	
	if ($to_year != null) $criteria['to_year'] = $to_year;
	
	
	#Get the month number from month name and build the to date.
	if (($to_year != null) && ($to_month != null)){
		$to_date = build_date($to_month,0,$to_year,1);
		$criteria['to_date'] = $to_date;
	}elseif (($to_month == null) && $to_year != null){
		$to_month = "December";
		$to_date = build_date($to_month,0,$to_year,1);
		$criteria['to_date'] = $to_date;
	}


	#abstract
	if (array_key_exists('abstract',$_POST)) {
		 $abstract = $_POST['abstract'];
	}elseif (array_key_exists('abstract',$_GET)) {
		 $abstract = $_GET['abstract'];
	}
	if ($abstract != null )$criteria['abstract'] = $abstract;


	#keywords
	if (array_key_exists('keywords',$_POST)) {
		 $keywords = $_POST['keywords'];
	}elseif (array_key_exists('keywords',$_GET)) {
		 $keywords = $_GET['keywords'];
	}
	if ($keywords != null )$criteria['keywords'] = $keywords;


	#abstract box
	if (array_key_exists('abstract_box',$_POST)) {
		 $abstract_box = $_POST['abstract_box'];
	}elseif (array_key_exists('abstract_box',$_GET)) {
		 $abstract_box = $_GET['abstract_box'];
	}
	if ($abstract_box != null) $criteria['abstract_box'] = $abstract_box;


	if ($debug_search_detail === true) {
		 print("\$rfc="); var_dump($rfc);
		 print('<br />');
		 print("\$title="); var_dump($title);
		 print('<br />');
		 print("\$author="); var_dump($author);
		 print('<br />');
		 print("\$stream_name="); var_dump($stream_name);
		 print('<br />');
		 print("\$area_acronym="); var_dump($area_acronym);
		 print('<br />');
		 print("\$wg_acronym="); var_dump($wg_acronym);
		 print('<br />');
		 print("\$pub_status="); var_dump($pub_status);
		 print('<br />');
 		 print("\$pubstatus="); var_dump($pubstatus);
		 print('<br />');
  		 print("\$std_trk="); var_dump($std_trk);
		 print('<br />');
		 print("\$pub_date_type="); var_dump($pub_date_type);
		 print('<br />');
		 print("\$from_month="); var_dump($from_month);
		 print('<br />');
		 print("\$from_year="); var_dump($from_year);
		 print('<br />');
		 print("\$to_month="); var_dump($to_month);
		 print('<br />');
		 print("\$to_year="); var_dump($to_year);
		 print('<br />');
		 print("\$abstract="); var_dump($abstract);
		 print('<br />');
		 print("\$keywords="); var_dump($keywords);
		 print('<br />');
 		 print("\$abstract_box="); var_dump($abstract_box);
		 print('<br />');
	}
	create_adv_search_form($criteria);
	//rfc_adv_lookup($criteria);
	page_footer();

}
else 
{
  create_adv_search_form();	
  print '<div class="errors">';
  echo '<strong>Errors found in form:</strong><ul><li>';
  echo join('</li><li>',$errors);
  echo '</li></ul><p>Please correct the input and try again.</p>';
  print '</div>'; //Errors ended
  page_footer();	
	
}

/********************************************************************************/
/*This function builds the date. It first gets the month number from input month*/
/*Next it formatts the date in the desired form*/
/********************************************************************************/
function build_date($month_name,$from,$year_name,$to){
	$month_number = "";
	for($i=1;$i<=12;$i++){
	    if(strtolower(date("F", mktime(0, 0, 0, $i, 1, 0))) == strtolower($month_name)){
	        $month_number = $i;
	        break;
	    }
	}
	$month_length = strlen($month_number);
	if ($month_length == '1'){
		$month_number = "0".$month_number;
		}
	if ($from){
#		$formatted_date =date("Y-m-d", mktime(0, 0, 0, $month_number, 1, $year_name))."\n";
		$formatted_date = "$year_name-$month_number-00";
	}
	if ($to){
		$formatted_date =date("Y-m-d", mktime(0, 0, 0, $month_number, 31, $year_name))."\n";
	}

	return $formatted_date;
}

/********************************************************************************/
/*This function checks the form data to find any erros*/
/********************************************************************************/

function getErrors($getData){
	
	$errors = array();
        $month_names = array('January','February','March','April','May','June','July','August','September','October','November','December');
        $standard_error = 'Error: Invalid input :&nbsp;';
        $advn_statuses = array('Any','Standards Track','Best Current Practice','Informational','Experimental','Historic', 'Unknown');
	$standard_message = '<br/>'.'Please enter an RFC or subseries number, optionally preceded by RFC, BCP, FYI, or STD '; 	 	
	foreach ($getData as $name => $value){
		switch ($name){
		        case 'Error': 
                                      $error_message = 'Error : Unwanted characters/letters in the selection'; 
                                      $errors[] = $error_message;
                                break;
			case 'rfc':
			            /*Max length is 8 */

						$length = strlen($value);
						if ($length > 8) {
						  $errors[] = 'RFC/subseries number should not be more than 8';
						}else {
								switch($length){
									case '8':
											 /*Check if the allowed values are entered here*/	 											
											 if (!(preg_match('/^(rfc|RFC|bcp|BCP|fyi|FYI|std|STD)\s([0-9][0-9][0-9][0-9])/',$value))){
#												 $errors[] = 'Error: Invalid input :'. $value .'  Please enter an RFC or subseries number, optionally preceded by RFC, BCP, FYI, or STD '; 	 
												 $error_message = $standard_error. $standard_message; 	 	
												 $errors[] = $error_message; 
												 
											 }
											 break;
									case '7':
											 /*Check if the allowed values are entered here*/	 											
											if (!(preg_match('/^(rfc|RFC|bcp|BCP|fyi|FYI|std|STD)([0-9][0-9][0-9][0-9])$|^(rfc|RFC|bcp|BCP|fyi|FYI|std|STD)\s([0-9][0-9][0-9])$/',$value))){												
#												 $errors[] = 'Error: Invalid input :'. $value .'  Please enter an RFC or subseries number, optionally preceded by RFC, BCP, FYI, or STD '; 	 
												 $error_message = $standard_error. $standard_message; 	 	
												 $errors[] = $error_message; 

											 }								
											 break;
									case '6':
											 if (!(preg_match('/^(rfc|RFC|bcp|BCP|fyi|FYI|std|STD)([0-9][0-9][0-9])$|^(rfc|RFC|bcp|BCP|fyi|FYI|std|STD)\s([0-9][0-9])$/',$value))){
#												 $errors[] = 'Error: Invalid input :'. $value .'  Please enter an RFC or subseries number, optionally preceded by RFC, BCP, FYI, or STD '; 	 
												 $error_message = $standard_error. $standard_message; 	 	
												 $errors[] = $error_message; 
												 
											 }								
											 break;
									case '5':
											if (!(preg_match('/^(rfc|RFC|bcp|BCP|fyi|FYI|std|STD)([0-9][0-9])$|^(rfc|RFC|bcp|BCP|fyi|FYI|std|STD)\s([0-9])$/',$value))){
#												 $errors[] = 'Error: Invalid input :'. $value .'  Please enter an RFC or subseries number, optionally preceded by RFC, BCP, FYI, or STD '; 	 
 												 $error_message = $standard_error. $standard_message; 	 	
												 $errors[] = $error_message; 

											 }
											 break;
									case '4':
											if (!(preg_match('/(^(rfc|RFC|bcp|BCP|fyi|FYI|std|STD)([0-9])$)|^([0-9][0-9][0-9][0-9])$|^(rfc|RFC|bcp|BCP|fyi|FYI|std|STD)\s$/',$value))){
#												 $errors[] = 'Error: Invalid input :&nbsp;'. $value .'<br/>'.'Please enter an RFC or subseries number, optionally preceded by RFC, BCP, FYI, or STD '; 	
												 $error_message = $standard_error. $standard_message; 	 	
												 $errors[] = $error_message; 
 											 }								
											 break;
									case '3':
											if (!(preg_match('/^([0-9][0-9][0-9])$|^(rfc|RFC|bcp|BCP|fyi|FYI|std|STD)$/',$value))){
#												 $errors[] = 'Error: Invalid input :'. $value .'  Please enter an RFC or subseries number, optionally preceded by RFC, BCP, FYI, or STD '; 	 
												 												 $error_message = $standard_error. $standard_message; 	 	
												 $errors[] = $error_message; 

											}
											 break;
									case '2':
											 if (!(preg_match('/^([0-9][0-9])$/',$value))){
												 $errors[] = 'Error: Invalid input : Please enter an RFC or subseries number, optionally preceded by RFC, BCP, FYI, or STD '; 	 
											 }
											 break;
									case '1':
											 if (!(preg_match('/^([0-9])$/',$value))){
												 $errors[] = 'Error: Invalid input : Please enter an RFC or subseries number, optionally preceded by RFC, BCP, FYI, or STD '; 	 
											 }
											 break;
											 
								}/*ENd switch*/								
							
							
						}/*End else*/

/*						if (($value != null) or ($value != "")){
							  if (!(is_numeric($value))){
								  $errors[] =  $name.' number must be numeric';
							  }elseif (strlen($value) > 4){
								  $errors[] = $name.' number should not be more than 4 digit';
							  }
						}
*/						
						break;
			case 'title':
						if (($value != null) or ($value != "")){
							  if (strlen($value) > '40'){
								  $errors[] = 'Title should not be more than 40';
							  }
                                                    if ((preg_match('/^\"/',$value)) && (preg_match('/\"$/',$value))){// Strings within quotes
                                                        if (preg_match('/^(SELECT|select|INSERT|insert|Delete|delete|UPDATE|update|PASSWORD|password|Password|passwd|LOGIN|login|Login|USER|user|User)$/ ',$value)){
                                                            $errors[] = 'Error: Invalid input : Restricted character or word used in the Title/Keyword\n';
                                                           
                                                        }else {
                                                            $str = preg_replace('/\"/',"",$value);
                                                            if (!preg_match('/^[0-9a-zA-Z\-\/ ]+$|^\s*(\+|-)?((\d+(\.\d+)?)|(\.\d+))\s*$/',$str)){
                                                            $errors[] =  'Error: Invalid input : The Title/Keyword may contain only alphanumeric characters (and double quotes to indicate a search phrase). A query can include "and", "or", or double quotes, but not in combination.\n';
                                                            }
                                                        }
                                                    }elseif ((!preg_match('/^\"/',$value)) && (!preg_match('/\"$/',$value))){// Strings not with quotes {
            
 							    if (preg_match('/^(SELECT|select|INSERT|insert|Delete|delete|UPDATE|update|PASSWORD|password|Password|passwd|LOGIN|login|Login|USER|user|User)$/ ',$value)){
                                                              $errors[] = 'Error: Invalid input : Restricted character or word used in the Title/Keyword\n';
                                                           
                                                            } 
                                                            if (!preg_match('/^[0-9a-zA-Z\-\/ ]+$|^\s*(\+|-)?((\d+(\.\d+)?)|(\.\d+))\s*$/',$value )){
                                                            $errors[] =  'Error: Invalid input : The Title/Keyword may contain only alphanumeric characters (and double quotes to indicate a search phrase). A query can include "and", "or", or double quotes, but not in combination.\n';
                                                            }
                                                            if (preg_match('/\band/gi',$value) &&  (preg_match('/\bor/gi',$value))){
                                                           $errors[] = 'Error: Invalid input : The Title/Keyword may contain only alphanumeric characters (and double quotes to indicate a search phrase). A query can include \"and\", \"or\", or double quotes, but not in combination.\n';
                                                            }
                                                        }
                                                }
                                                
						break;

                        case 'stream_name':

                                                $stream_names = array('IETF','IRTF','IAB','Independent','Editorial','Legacy');
                                          	if (($value != null) or ($value != "")){
							  if (strlen($value) > '11'){
								  $errors[] = 'Stream name should not be more than 10';
							  }else{
                                                              if (!(in_array($value,$stream_names))){
						                 $errors[] = 'Error: Invalid input : Restricted character or word used in the Stream names \n';
                                                              }else{
                                                                # print "Matched area acronym"; 
                                                              }
                                                          }
                                                 }
                                                break;
			case 'area_acronym':
						if (($value != null) or ($value != "")){
							  if (strlen($value) > '10'){
								  $errors[] = 'Area Acronym should not be more than 10';
							  }else{
                                                              $area_acronyms = get_area_acronyms();
                                                              if (!(in_array($value,$area_acronyms))){
						                 $errors[] = 'Error: Invalid input : Restricted character or word used in the Area Acronym \n';
                                                              }else{
                                                                # print "Matched area acronym"; 
                                                              }
                                                          }
                                                 }
					         break;
		
                	 case 'wg_acronym':
						if (($value != null) or ($value != "")){
							  if (strlen($value) > '10'){
								  $errors[] = 'WG Acronym should not be more than 10';
							  }else{
                                                                 if (!ctype_alnum($value)){
                                                                    $errors[] =  'Error: Invalid input : The WG Acronym  may contain only alphanumeric characters \n';
                                                                  } else {
                                                                      if (preg_match('/^(SELECT|select|Select|INSERT|insert|Insert|DELETE|Delete|delete|UPDATE|update|Update|PASSWORD|password|Password|passwd|LOGIN|login|Login|USER|user|User|<Script>|<script>|<SCRIPT>|AND|AnD|and|aND|aNd|Sleep|SLEEP|sLeep|SLeep|sLEEP) $/ ',$value)){
						                          $errors[] = 'Error: Invalid input : Restricted character or word used in the WG Acronym \n';
                                                                       }else{
                                                                          
                                                                       }  
                                                                  }
						          }
                                                }
							break;
			case 'from_year':
                                               if (($value != null) or ($value != "")){
                                                     if (strlen($value) > '4'){
								  $errors[] = 'From year should not be more than 4 digits';
							  }else{
                                                                 if (!is_numeric($value)){
                                                                    $errors[] =  'Error: Invalid input : The From Year may contain only numric characters \n';
                                                                 } else {
                                                                          if (preg_match('/^(SELECT|select|Select|INSERT|insert|Insert|DELETE|Delete|delete|UPDATE|update|Update|PASSWORD|password|Password|passwd|LOGIN|login|Login|USER|user|User|<Script>|<script>|<SCRIPT>|AND|AnD|and|aND|aNd|Sleep|SLEEP|sLeep|SLeep|sLEEP) $/ ',$value)){
						                          $errors[] = 'Error: Invalid input : Restricted character or word used in the From year \n';
                                                                       }  
                                                                  }
                                                           }

                                                $get_from_year = $value;
                                                }
                                                break;

			case 'to_year':
                         		     if (($value != null) or ($value != "")){

                                                    if (($get_from_year == null) or ($get_from_year == "")){
								  $errors[] = 'Please select From year value';
							  }elseif ($value < $get_from_year){
								  $errors[] = 'From year should not be greater than to year value';								                                                       }
                                                    
                                                     if (strlen($value) > '4'){
								  $errors[] = 'To Year should not be more than 4 digits';
							  }else{
                                                                 if (!is_numeric($value)){
                                                                    $errors[] =  'Error: Invalid input : The To Year may contain only numric characters \n';
                                                                 } else {
                                                                          if (preg_match('/^(SELECT|select|Select|INSERT|insert|Insert|DELETE|Delete|delete|UPDATE|update|Update|PASSWORD|password|Password|passwd|LOGIN|login|Login|USER|user|User|<Script>|<script>|<SCRIPT>|AND|AnD|and|aND|aNd|Sleep|SLEEP|sLeep|SLeep|sLEEP) $/ ',$value)){
						                          $errors[] = 'Error: Invalid input : Restricted character or word used in the To year \n';
                                                                       }  
                                                                  }
                                                           }
                                                  }
                                                                          
						  break;
                       case 'from_month':
                                          	if (($value != null) or ($value != "")){
                                                     if (!(in_array($value,$month_names))){
						          $errors[] = 'Error: Invalid input : Please use the month name from the dropdown \n';
                                                     }else{
                                                                # print "Matched month"; 
                                                     }
                                                          
                                                 }
                                                 break;
                        case 'to_month':        
                                                if (($value != null) or ($value != "")){
                                                     if (!(in_array($value,$month_names))){
						          $errors[] = 'Error: Invalid input : Please use the month name from the dropdown \n';
                                                     }else{
                                                                # print "Matched month"; 
                                                     }
                                                          
                                                 }
                                                 break;

			case 'pubstatus':
				                if (($value != null) or ($value != "")){
                                                     if (!(is_array($value))){
						          $errors[] = 'Error: Invalid input : Please use standard value from the dropdown \n';
                                                     }else{
                                                               $status_count = count($value);
                                                               for ($i =0; $i < $status_count; $i++) {
                                                                   $value[$i];
                                                                   if (!(in_array($value[$i],$advn_statuses))){
						                      $errors[] = 'Error: Invalid input : Please use Status from the dropdown \n';
                                                                   }
                                                               }
                                                     }
                                                          
                                                 }
						 break;				
			case 'abstract_box':
							if (($value != null) or ($value != "")){
							  if (strlen($value) > '40'){
								  $errors[] = 'Abstract should not be more than 40';
							  }
							}
							break;				
			case 'combo_box':	
                        default:
					           $value = preg_replace('/\"/',"",$value);
                                                   if ((!preg_match('/[0-9a-zA-Z\-\/ ]+$|^\s*(\+|-)?((\d+(\.\d+)?)|(\.\d+))\s*$/',$value)) || preg_match('/\b(SELECT|select|Select|INSERT|insert|Insert|DELETE|Delete|delete|UPDATE|update|Update|PASSWORD|password|Password|passwd|LOGIN|login|Login|USER|user|User|<Script>|<script>|<SCRIPT>|AND|AnD|and|aND|aNd|Sleep|SLEEP|sLeep|SLeep|sLEEP)\b/',$value)|| preg_match('/^(SELECT|select|Select|INSERT|insert|Insert|DELETE|Delete|delete|UPDATE|update|Update|PASSWORD|password|Password|passwd|LOGIN|login|Login|USER|user|User|<Script>|<script>|<SCRIPT>|AND|AnD|and|aND|aNd|Sleep|SLEEP|sLeep|SLeep|sLEEP) $/',$value)){


 					               $errors[] = 'Unwanted characters/letters in the selection';
  
						       if ($debug_search_detail === true) {
      							 print "Unwanted characters/letters in the selection";
   						       }
                                                    }
					            break;												
			
		}/*End switch*/
	}/*End foreach*/

	return $errors;
}
?>
