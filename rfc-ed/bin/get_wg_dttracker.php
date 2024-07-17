#!/usr/bin/php
<?php
/************************************************************************************************************/
/*Script Name : get_wg_dttracker.php                                                                        */
/*Description : The script calls the datatracker API to get the information about active Working group      */
/*              Seperate API calls required to get the details of Area, and various Working group chair     */
/*              detail                                                                                      */
/* February 2022: Working Group Table Data - PN                                                             */
/************************************************************************************************************/

#Datatracker API call to get active Working Group List
$active_group_url = 'https://datatracker.ietf.org/api/v1/group/group/?format=json&state=active&type__in=wg&limit=2000';

//read json file from url in php
$read_group_file = file_get_contents($active_group_url);
 
//convert json to array in php
$group_array = json_decode($read_group_file, TRUE);

$group_array_meta = $group_array['meta'];
$group_array_objects = $group_array['objects'];
$wg_step1_array = array();
$wg_step2_array = array();
$wg_step3_array = array();
#Base For URL call
$dttracker_base = 'https://datatracker.ietf.org';
$parent_url = "";

foreach ($group_array_objects as $group_array){

     $group_parent = get_parent_id($group_array['parent']);
     $group_parent_url = '';            
     $group_parent_url = $dttracker_base."/api/v1/group/group/".$group_parent.'?format=json';
     $group_parent_name = get_group_parent_name($group_parent_url); #Area Name
     array_push($wg_step1_array,array($group_array['id'],$group_array['acronym'],$group_array['list_email'],$group_array['name'],$group_parent_name));
}


foreach ($wg_step1_array as $group){
         foreach ($group as $key=>$value) {
            if ($key == '0'){
               #Datatracker API call to get Chair information for active Working Groups          
               $url_string = 'https://datatracker.ietf.org/api/v1/group/role/?format=json&group__state=active&group__type__in=wg&name__slug=chair&group__id='.$value.'&limit=10';
            }
         }
         array_push($wg_step2_array,array($group[0],$group[1],$group[2],$group[3],$group[4],$url_string));
}


$wg_length = count($wg_step2_array);


for($i=0;$i<$wg_length;$i++){
    for ($j=0;$j<=5;$j++){
         if ($j == 5) {
            $group_role_url = $wg_step2_array[$i][$j];
            //read json file from url in php
            $group_role_json = file_get_contents($group_role_url);
            $decoded_group_role_json = json_decode($group_role_json, TRUE);
            $decoded_group_role_meta = $decoded_group_role_json['meta'];
            $decoded_group_role_objects = $decoded_group_role_json['objects'];
            
            $chair_email_list = "";
            $chair_list = "";
            $chair_email_string = "";
            $dttracker_base = 'https://datatracker.ietf.org';
            $dttracker_url = '';            
            $chair_person = "";
            foreach ($decoded_group_role_objects as $group_role_array) {
                  $chair_email_string = get_exact_chair_email($group_role_array['email']);#Get the chair email from the email array     
                  if ($chair_email_list == ""){
                      $chair_email_list .= $chair_email_string;
                  } else {
                      $chair_email_list .= ", ".$chair_email_string;
                  }

                  $dttracker_url = $dttracker_base.$group_role_array['person'].'?format=json';
                  $chair_person = get_person_name($dttracker_url);
                  if ($chair_list == ""){
                      $chair_list .= $chair_person;
                  } else {
                      $chair_list .= ", ".$chair_person;
                  }
            } 
            
                  array_push($wg_step3_array,array($wg_step2_array[$i][0],$wg_step2_array[$i][1],$wg_step2_array[$i][2],$wg_step2_array[$i][3],$wg_step2_array[$i][4],$chair_email_list,$chair_list));
         }
    }
}


$final_array_length = count($wg_step3_array);
for($i=0;$i<$final_array_length;$i++){
    for ($j=0;$j<=6;$j++){
       $element = $wg_step3_array[$i][$j];
       if ($j != 6) {
           print "$element"."|";
       } else {
           print "$element";
       }
    }
    print "\n";
}

/*******************************************************************************************************/
/*get_parent_id : Function seperates the Parent id from the input string                               */
/*******************************************************************************************************/
function get_parent_id($in_string) {

    $parent_array = "";
    $parent_array = preg_split("/[ \/,]+/",$in_string);
    $parent_id = $parent_array[5];

     return $parent_id;
}
/*******************************************************************************************************/
/*get_group_parent_name : Function get the parent name through API url call.                           */
/*******************************************************************************************************/
function get_group_parent_name($parent_url){

    $parent_json = file_get_contents($parent_url);
    $decoded_parent_json = json_decode($parent_json, TRUE);
    $parent_name = $decoded_parent_json['name'];
    return($parent_name); 
}
/*******************************************************************************************************/
/*get_extract_chair_email : Function extracts email from the i/p string.                                 */
/*******************************************************************************************************/
function get_exact_chair_email($in_string) {

    $chair_email_array = "";
    $chair_email_array = preg_split("/[ \/,]+/",$in_string);
    $exact_chair_email = $chair_email_array[5];

     return $exact_chair_email;
}

/*******************************************************************************************************/
/*get_person_name : Function get the person name through API url call.                                 */
/*******************************************************************************************************/
function get_person_name($person_url){
    $person_json = file_get_contents($person_url);
    $decoded_person_json = json_decode($person_json, TRUE);
    $person_name = $decoded_person_json['name'];
    return($person_name); 
}

?>
