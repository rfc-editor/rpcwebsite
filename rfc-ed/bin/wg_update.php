#!/usr/bin/php
<?php
/************************************************************************************************************/
/*Script Name : wp_update.php                                                                               */
/*Description : The script uses i/p file wg_data.txt to update the database table working_group             */
/*February 2022: Working Group Table Data - PN                                                              */
/*May 2024: Added the changes to remove word 'Area' from the area name before adding to Working Group       */
/*          Table - PN                                                                                      */
/************************************************************************************************************/
    include_once("db_connect.php");
    $input_file_path = "wg_data.txt";#File generated through reading Data from API call

    $handle_in = fopen($input_file_path, "r");

    if (! $handle_in) die("Error opening Read file");
    
    if ($handle_in) {
       $run_year = date('Y');
       $run_month = date('m');
       $run_day = date('d');
       $run_date = date('Y-m-d');
       print "                            Working Group Update Run on $run_date           \n";
        while (($line = fgets($handle_in)) !== false) {
               $dataArray = explode("|",$line);
               list($id,$acronym,$email,$name,$area,$chair_email,$chair_name) = $dataArray;
               #Check Working group present in working_group table
               $wg_db_status = check_wg_status($acronym,$email,$name,$area,$chair_email,$chair_name);             
               
        }
    }

/*******************************************************************************************************/
/*check_wg_status : Function gets the existing data from working_group table for the specified acronym */
/*                  and checks if it is a complete new entry or update to Working group table and      */
/*                  follows the desired path.                                                          */
/*******************************************************************************************************/
function check_wg_status($in_acronym,$in_email,$in_name,$in_area,$in_chair_email,$in_chair_name){
    global $pdo;

    try {
           $wg_query = "SELECT * from `working_group` where `wg_acronym` = :in_acronym";
           $stmt = $pdo->prepare($wg_query);
           $stmt->bindParam('in_acronym',$in_acronym);
           $stmt->execute();
           $num_of_rows = $stmt->rowCount();
         } catch (PDOException $pe){
           error_log("Error processing : check_wg_status", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
    }
    $status = 0;
    switch ($num_of_rows) {
         case 0 :
                 $status = add_wg($in_acronym,$in_email,$in_name,$in_area,$in_chair_email,$in_chair_name);
                 #$status = 1;
                 break;
         case 1 :
                 #Make status active
                 $row = $stmt->fetch(PDO::FETCH_ASSOC);
                 $wg_id = $row['wg_id'];
                 $wg_acronym = $row['wg_acronym'];
                 $wg_name = trim($row['wg_name']);
                 $area_name = trim($row['area_name']);
                 $wg_chair_name = trim($row['wg_chair_name']);
                 $wg_chair_email = trim($row['wg_chair_email']);
                 $wg_email = trim($row['wg_email']);
                 $wg_status = trim($row['wg_status']);
                 
                 $wg_name_flag = 0;
                 $area_name_flag = 0;
                 $chair_name_flag = 0;
                 $chair_email_flag = 0;
                 $wg_email_flag = 0;
                 $wg_status_flag = 0;

                 print "--------------------------------------------------------------------------------------------------------------------------\n";
                 print "                                        ".$wg_acronym." WG\n";
                 if ($wg_name != trim($in_name)) {
                     print "**Working group name** \n";
                     print "OLD: ".$wg_name ."\n";
                     print "NEW: ". $in_name ."\n" ;
                     $wg_name_flag = 1;
                 }
                 
                 $in_area = trim($in_area);
                 $rtrim_in_area = rtrim($in_area,"Area");
                 $rtrim_in_area = trim($rtrim_in_area);

                 if ($area_name != $rtrim_in_area) {
                     print "Area name \n";
                     print "OLD: ". $area_name ."\n";
                     print "NEW: ". $in_area . "\n";
                     print "After removing the word Area: ". $rtrim_in_area ."\n";
                     $area_name_flag = 1;
                 }
                
                 if ($wg_chair_name != trim($in_chair_name)) {
                     print "Chair name \n";
                     print "OLD: ". $wg_chair_name ."\n";
                     print "NEW: ". $in_chair_name ."\n";
                     $chair_name_flag = 1;
                 }

                 if ($wg_chair_email != trim($in_chair_email)) {
                     print "Chair email \n";
                     print "OLD: ". $wg_chair_email ."\n";
                     print "NEW: ". $in_chair_email ."\n";
                     $chair_email_flag = 1;
                 }

                 if ($wg_email != trim($in_email)) {
                     print "WG email \n";
                     print "OLD : ". $wg_email ."\n";
                     print "NEW : ". $in_email ."\n";
                     $wg_email_flag = 1;
                 }
                 
                 if ($wg_status != 'open') { 
                     print "WG status \n";
                     print "OLD : ". $wg_status ."\n";
                     print "NEW : open \n";
                     $wg_status_flag = 1;
                 }
          

                 if ($wg_name_flag || $area_name_flag || $chair_name_flag || $chair_email_flag || $wg_email_flag || $wg_status_flag) {
                     $status =  update_wg($in_acronym,$in_email,$in_name,$rtrim_in_area,$in_chair_email,$in_chair_name,$wg_name_flag,$area_name_flag,$chair_name_flag,$chair_email_flag,$wg_email_flag,$wg_status_flag);
                 }
                 print "--------------------------------------------------------------------------------------------------------------------------\n";
                 break;
         default:
                 break;

    }

    return $status;

}
/*******************************************************************************************************/
/*add_wg : Function adds the new working group to the working_group table                              */
/*******************************************************************************************************/
function add_wg($wg_acronym,$wg_email,$wg_name,$wg_area,$wg_chair_email,$wg_chair_name){

   global $pdo;
  
   if (preg_match("/Area/",$wg_area)){
      $wg_area = trim($wg_area);
      $wg_area = rtrim($wg_area,"Area");
      $wg_area = trim($wg_area);
   }


   //Insert into working_group table
   $insert = "insert into `working_group` (wg_acronym,wg_name,area_name,wg_chair_name,wg_chair_email,wg_email,wg_status) values (:wg_acronym,:wg_name,:wg_area,:wg_chair_name,:wg_chair_email,:wg_email,:wg_status)";
  
   try {
       $stmt = $pdo->prepare($insert);
       $stmt->bindParam('wg_acronym',$wg_acronym);
       $stmt->bindParam('wg_name',$wg_name);
       $stmt->bindParam('wg_area',$wg_area);
       $stmt->bindParam('wg_chair_name',$wg_chair_name);
       $stmt->bindParam('wg_chair_email',$wg_chair_email);
       $stmt->bindParam('wg_email',$wg_email);
       $stmt->bindValue('wg_status','open');
       $stmt->execute();
       $insert_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing :  add_wg insert to working group ", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   if ($insert_rows > 0){
       $wg_insert_status = 1; 
       print "Added working group  $wg_acronym with area $wg_area to working_group table \n";
   } else {
       $wg_insert_status = 0;
   }             
   
   return $wg_insert_status;

}


/*******************************************************************************************************/
/*update_wg : Function updates the modified data to the working_group table                            */
/*******************************************************************************************************/
function update_wg($wg_acronym,$wg_email,$wg_name,$wg_area,$wg_chair_email,$wg_chair_name,$wg_name_flag,$area_name_flag,$chair_name_flag,$chair_email_flag,$wg_email_flag,$wg_status_flag){

   global $pdo;

   $base_update = "UPDATE `working_group` SET"; #Base query
   $template = "";
   $where_clause = "  WHERE `wg_acronym` = :wg_acronym";

   if ($wg_name_flag) {
       $template = " `wg_name` = :wg_name";
   }
   if ($area_name_flag){
       if ($template) {
           $template .= ", `area_name` = :wg_area";
       } else {
           $template .= " `area_name` = :wg_area";
       }
   }   
   if ($chair_name_flag){
       if ($template) {
           $template .= ", `wg_chair_name` = :wg_chair_name";
       } else {
           $template .= " `wg_chair_name` = :wg_chair_name";
       }
   }
   if ($chair_email_flag){
       if ($template) {
           $template .= ", `wg_chair_email` = :wg_chair_email";
       } else {
           $template .= " `wg_chair_email` = :wg_chair_email";
       }
   }
   if ($wg_email_flag){
       if ($template) {
           $template .= ", `wg_email` = :wg_email";
       } else {
           $template .= " `wg_email` = :wg_email";
       }
   }
   if ($wg_status_flag){
       if ($template) {
           $template .= ", `wg_status` = :wg_status";
       } else {
           $template .= " `wg_status` = :wg_status";
       }
   }

   $update = $base_update.$template.$where_clause;
   try {
       $stmt = $pdo->prepare($update);
      
       if ($wg_name_flag) {
           $stmt->bindParam('wg_name',$wg_name);
       }
       if ($area_name_flag) {
           $stmt->bindParam('wg_area',$wg_area);
       }
       if ($chair_name_flag) {    
           $stmt->bindParam('wg_chair_name',$wg_chair_name);
       }
       if ($chair_email_flag) {
           $stmt->bindParam('wg_chair_email',$wg_chair_email);
       }
       if ($wg_email_flag) {
           $stmt->bindParam('wg_email',$wg_email);
       }
       if ($wg_status_flag) {
           $stmt->bindValue('wg_status','open');
       }
       $stmt->bindParam('wg_acronym',$wg_acronym);
       $stmt->execute();
       $update_rows = $stmt->rowCount();
   } catch (PDOException $pe){
       error_log("Error processing :  update_wg updated to working group ", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
   }

   if ($update_rows > 0){
       $wg_update_status = 1; 
       print "Updated working group  $wg_acronym data to working_group table \n";
   } else {
       $wg_update_status = 0;
   }             
   
   return $wg_update_status;

}
?>
