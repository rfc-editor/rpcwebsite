<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
/***********************************************************************************************************/
/*Script Name : track_by_editor.php                                                                        */
/*Description : The script shows the documents track by each editor and the respective states of the       */
/*              document                                                                                   */
/*Dec 2018     : Added the tool adjustment state TI -PN                                                    */
/* November 2020 : Modified the script to use PDO prepared statements - PN                                 */
/***********************************************************************************************************/

session_start();
include('header.php');
include('db_connect.php');
include('edit_lib.php');
include('display_adjust.php');
$debug_trk = false;
#$debug_trk = true;

rfc_header("Tracking by Editor");


$editor_order_array = create_header();
//print_r($editor_order_array);

echo "<h3 class=\"simple_center\">Tracking by Editor</h3>";
echo "<table class='gridtable'>";
echo "<tr><th>State\Editor</th>";
foreach ($editor_order_array as $editor){
		echo "<th>$editor</th>";
	}
echo "</tr>";	

//State order has to be in specific order as below
/*Has two AUTH48-DONE teh first represents teh Primary Editor and Second represents the PUB Editor for state AUTH48-DONE*/

$state_order = array('EDIT','RFC-EDITOR','AUTH','IANA','AUTH48','AUTH48-DONE','REF','MISSREF','IESG','TI');
			
$base_trk_query = "SELECT e.`initials` ,CASE WHEN i.`state_id` = '2' OR i.`state_id` = '23' THEN i.`DOC-ID` ELSE i.`DRAFT`END AS 'calval' , e.`role_key` , i.`state_id` , i.`DRAFT` , i.`DOC-ID` FROM `index` i, `editor_assignments` e, `states` s WHERE i.`internal_key` = e.`doc_key` AND e.`initials` != :xx_initials AND i.`state_id` = s.`state_id`";
	
$state_array = array();

$state_ids = [4,17,1,8,2,23,15,22,10,20];
   $in = "";
   foreach ($state_ids as $i => $item)
   {
       $key = ":state_id".$i;
       $in .= "$key,";
       $in_params[$key] = $item; // collecting values into key-value array
   }
   $in = rtrim($in,","); 
   global $pdo;
   $state_query = "Select `state_id`, `state_name` from states where state_id in($in)";
   try {   
       $stmt = $pdo->prepare($state_query);
       $stmt->execute($in_params); 
       $state_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : track_by_editor", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
   if ($state_rows > 0) {
        while ( $state_line = $stmt->fetch(PDO::FETCH_NUM)) {
            /*Need to have two AUTH48-DONE lines to show Primary Editor data under first
              line with heading AUTH48-DONE and state AUTH48-DONE and second line with 
              heading PUB , state AUTH48-DONE and PUB editor so to show the state AUTH48-DONE 2 times 
              addig the second time entry with state name AUTH48-DONE but state id as 24 which is not a 
              standard state_id*/
           if ($state_line[0] == '23'){
               array_push($state_array, $state_line);
               $state_line[0] = '48';
               array_push($state_array, $state_line);
            } else {
               array_push($state_array, $state_line);
            }
        }
   }
if ($debug_trk === true) {
        print("track_by_editor.php :: States query :\n");
        print_r($state_array); print("\n");
        print("\n");
}

$init_editor = "";
$first_editor = 0;
$orig_auth48_id = '';

foreach ($state_order as $state) { //Need to maintain default report order
 	foreach ($state_array as $state_line) {
			$i_editor = 0;
             $pattern = "/^$state(\*A|\*R)*$/";
			  if (preg_match($pattern, $state_line[1])) {
				   $st = $state_line[1];
	               $st_id = $state_line[0];
                       if ($st_id == '48'){
                           $st_id = '23'; /*Bring back the correct state id*/
                           $orig_auth48_id = '48';
                           $state_line[1] = '4PUB';
                       }
/*If row wise coloring needs this function has to use*/				   
/*				   $color = get_row_color($st_id);
				   echo "<tr bgcolor = '$color'>";
*/
                   echo "<tr>";
				   echo "<th>$state_line[1]</th>";	   
				   foreach ($editor_order_array as $editor){
					        $i_editor++;
                                                   $add_trk_query = "AND i.`state_id` = :st_id AND e.`initials` = :editor_initials ORDER BY e.`initials` , i.`state_id`";
						   $trk_query = "$base_trk_query"." AND e.`role_key` = :role_key "."$add_trk_query";   
	                                                 try {
                                                             $stmt = $pdo->prepare($trk_query);
                                                             $stmt->bindValue('xx_initials','XX');
                                                             if ($st_id == '17'){
                                                                $stmt->bindValue('role_key','3');
                                                                $stmt->bindParam('st_id',$st_id);
                                                                $stmt->bindParam('editor_initials',$editor);
						             } elseif (($st_id == '23') && ($orig_auth48_id == '48')){
                                                                $stmt->bindValue('role_key','4');
                                                                $stmt->bindParam('st_id',$st_id);
                                                                $stmt->bindParam('editor_initials',$editor);
                                                             } else {
	                                                            $stmt->bindValue('role_key','1');
                                                                    $stmt->bindParam('st_id',$st_id);
                                                                    $stmt->bindParam('editor_initials',$editor);
                                                             }            
                                                             $stmt->execute();
                                                             $num_of_rows = $stmt->rowCount();
                                                        } catch (PDOException $pe){
                                                             error_log("Error processing : track_by_editor", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
                                                        }

                                                   $color = ($i_editor % 2) ? "#EBF5F5" : "#F5EBEB";
						   print "<td bgcolor= '$color'>";
						   print "<table class='innertable'>";//Table for each individual cell						   
						   if ($debug_trk === true) {
			                     		   print("track_by_editor.php :: Track query :\n");
	        			                   print "$trk_query";
							   print("\n");
		                   		   }

						   $editor_state_records =  $num_of_rows;
					           if ($debug_trk === true) {
							print "<p>Found " . $num_of_rows .  " records</p>\n";
						   }

						   $trk_array = array();
						   
						   while ($trk_line = $stmt->fetch(PDO::FETCH_NUM)){
							      array_push($trk_array,$trk_line);
						   }
/*If row wise coloring needs this function has to use*/				   
/*						   $color = get_row_color($st_id);
*/						   
				           echo "<tr bgcolor = '$color'>";
						   print "<td>";
						   
/*****Testing comments***************
						   print "State id is : $st_id "; 	   
						   print $trk_query;
						   print_r($trk_array);		
						   print $editor_state_records;  				
*************************************/							   
						   if ($editor_state_records  > '0'){
							   	  for ($i = 0; $i < $editor_state_records; $i++){
									   for ($j=0; $j < 4; $j++){
	  										   $cal_val = $trk_array[$i][1];
											   $draft_name = $trk_array[$i][4];
								   }
								   
								   
								   //Get the cluster id for the draft
								   $cid = get_cluster_id($pdo,$draft_name);

								   	
								   if ($cid == "0"){
									   $cid = "";
								   }else {
									   $cid = "(".$cid.")";
									   }
								   
								   
								   //If the state is AUTH48 or AUTH48-DONE then display DOC-ID else 
								   //display the draft name followed by cluster id
								   if (($st_id == '2') ||($st_id == '23')){
										   $number = preg_replace('/^RFC/',"", $cal_val);

                                                                                   if (($st_id == '23') && ($orig_auth48_id == '')){
                                                                                      $auth48_done_rfcs = check_PUBeditor_display($st_id);
                                                                                      $auth48_len = count($auth48_done_rfcs);
                                                                                      if (in_array($cal_val,$auth48_done_rfcs)){
                                                                                       # print "Found the niddle";
                                                                                      }
                                                                                      else{
                                                                                      #  print "Not found the niddle";
                                                                                        if ($number){ 	
											   if (($i != ($editor_state_records -1))){
												   print "<a href=\"auth48_edit.php?docnum=$number\">$number</a> $cid<br>";
											   }else {
												   print "<a href=\"auth48_edit.php?docnum=$number\">$number</a> $cid ";
												   }
										        }
                                                                                      }
                                                                               
                                                                                   }else {
										   if ($number){ 	
											   if (($i != ($editor_state_records -1))){
												   print "<a href=\"auth48_edit.php?docnum=$number\">$number</a> $cid<br>";
											   }else {
												   print "<a href=\"auth48_edit.php?docnum=$number\">$number</a> $cid ";
												   }
										   }
                                                                                  }
					                            }
								    elseif (($st_id == '23') && ($orig_auth48_id == '48')){
										   $number = preg_replace('/^RFC/',"", $cal_val);
										   if ($number){ 	
											   if (($i != ($editor_state_records -1))){
												   print "<a href=\"auth48_edit.php?docnum=$number\">$number</a> $cid<br>";
											   }else {
												   print "<a href=\"auth48_edit.php?docnum=$number\">$number</a> $cid ";
												   }
										   }
										  }
											  
								   else {	
										   if (($i != ($editor_state_records -1))){
											   print "<a href=\"edit_draft.php?draft=$cal_val\">$cal_val</a> $cid<br><br>";
										   }else {
											   print "<a href=\"edit_draft.php?draft=$cal_val\">$cal_val</a> $cid";
											   }
										  }
								   }
/*****Testing comments****************								   
//						   print "||$editor_state_records || $trk_query";
/*****Testing comments****************/
						   print "</td></tr>";	   
						   }
						   elseif ($editor_state_records == '0') {
						   		  print "</td></tr>";
						   }						   						   
						   print "</table>";
						   print "</td>";	
			      }//End of foreach ($editor_order_array as $editor){
			  }//End of if (preg_match($pattern, $state_line[1])) {

	
 	}//End of foreach ($state_array as $state_line) {
    echo "</tr>";		
}//End of foreach ($state_order as $state)
echo "</table>";

/****************************************************************************************************/
/*Function : create_header()*/
/*			 This function shows the editors which have some data in some state*/
/****************************************************************************************************/
function create_header(){
   global $debug_trk;
   global $pdo;

   $editor_header_array = array();
   $editor_array = array();
   $editor_select_query = "SELECT e.`initials`  FROM `editors` e WHERE e.`assignable` = :assignable AND e.`initials` != :initials ORDER BY e.`initials`";
   try {
       $stmt = $pdo->prepare($editor_select_query);
       $stmt->bindValue('assignable','yes');
       $stmt->bindValue('initials','XX');
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing : create_header", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }
 
   # Pull up the query and just implode it into a table.
   if ( $num_of_rows > 0 ) {
       while ( $editor_line = $stmt->fetch(PDO::FETCH_NUM)) {
           array_push($editor_array, $editor_line);
       }
   }

   if ($debug_trk === true) {
       print("track_by_editor.php :: Editor Selection query :\n");
       print_r($editor_array); print("\n");
       print("\n");
   }

   foreach ($editor_array as $editor_line){
       array_push($editor_header_array,$editor_line[0]);
   }
   return $editor_header_array;
}


/**************************************************************************************/
/*This function gets the row color based on the state value                           */
/*No longer use as color is done columnwise instead of row                            */
/**************************************************************************************/
function get_row_color($in_state_id){
	
	  switch($in_state_id){
		  case '4': $color = "#F5EBEB";
		  		    break;
		  case '17': 
				  $color = "#EBF5F5";
		  			break;
		  case '1': $color = "#F5EBEB";
		  		  break;
		  case '8': 
		  	      $color = "#EBF5F5";
		  		  break;
		  case '2': 
		  		  $color = "#F5EBEB";
		          break;
		  case '23' : 
		            $color = "#EBF5F5";   
		            break;
		  case '15': 
		            $color = "#F5EBEB";
		            break;
		  case '22': 
		            $color = "#EBF5F5";
		            break;
		  case '10': 
		            $color = "#F5EBEB";
		            break;
		  default : break;
		  

		  }				
	return $color;
	}
	
	
?>
