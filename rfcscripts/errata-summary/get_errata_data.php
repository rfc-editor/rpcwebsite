#!/usr/bin/php
<?php
/******************************************************************************/
/* Copyright The IETF Trust 2020 All Rights Reserved                          */
/*Script name : get_errata_data.php                                           *
/*              This script gets the area wise and stream wise  errata counts */
/*              and sends monthly email to stream managers                    */
/* September 2024  : Created the script - PN                                  */
/******************************************************************************/
include('db_connect.php');

$area_sql;

$type_id = 2;
$status_id = 2;

 $params = [":type_id" => $type_id,":status_id" => $status_id];
 $area_id = [7,8,10];
          
     $in = "";
     foreach ($area_id as $i => $item)
     {
         $key = ":area_id".$i;
         $in .= "$key,";
         $in_params[$key] = $item; // collecting values into key-value array
     }
     $in = rtrim($in,",");

/*Get the area wise errata count*/
try {
   $area_sql = "SELECT a.`area_name`,a.`area_acronym`, COUNT(DISTINCT(e.`errata_id`)) 
            FROM errata e, errata_type_codes t , errata_status_codes s, working_group w, area a, 
            `index` i left join area_assignments n on i.internal_key = n.fk_index 
            WHERE e.`doc-id` = i.`doc-id` AND 
            e.type_id=t.errata_type_id AND 
            e.status_id=s.errata_status_id AND 
            i.source = w.wg_name AND 
            e.type_id = :type_id AND 
            e.status_id = :status_id AND
            a.area_id NOT IN ($in) AND 
            (a.area_name = w.area_name OR n.fk_area = a.area_id) GROUP BY a.`area_name`;";


   $area_stmt = $pdo->prepare($area_sql);
   $area_stmt->execute(array_merge($params,$in_params)); 
   $num_of_area_rows = $area_stmt->rowCount();
   }catch (PDOException $pe){ 
       error_log("Error processing :  Reported Errata Summary area query", $pe->getMessage(), $pe->getCode(), array('exception' => $pe)); 
   }




$area_result_array = array();

if ($num_of_area_rows > 0) { 
        while($area_row = $area_stmt->fetch(PDO::FETCH_NUM)) {
           array_push($area_result_array, $area_row);
        }
}


/*Get the stream wise errata count*/
try {
   $stream_sql = 'SELECT ssp.`stream_name`, ssp.`ssp_id`, COUNT(e.`errata_id`) 
              FROM errata e, errata_type_codes t , errata_status_codes s, 
              working_group w, area a, stream_specific_parties ssp, 
              `index` i left join area_assignments n on i.internal_key = n.fk_index 
              WHERE e.`doc-id` = i.`doc-id` AND 
              e.type_id=t.errata_type_id AND 
              e.status_id=s.errata_status_id AND 
              i.source = w.wg_name AND 
              e.type_id = :errata_type_id AND 
              e.status_id = :errata_status_id AND
              ssp.stream_name != :stream_name AND 
              w.`ssp_id` = ssp.`ssp_id` AND 
              (a.area_name = w.area_name OR n.fk_area = a.area_id) GROUP BY ssp.`stream_name`;';

   $stream_stmt = $pdo->prepare($stream_sql);

   $errata_type_id = 2;
   $errata_status_id = 2;
   $stream_name = 'IETF';

   $stream_stmt->bindParam('errata_type_id',$errata_type_id);
   $stream_stmt->bindParam('errata_status_id',$errata_status_id);
   $stream_stmt->bindParam('stream_name',$stream_name);
   $stream_stmt->execute();         

   $num_of_stream_rows = $stream_stmt->rowCount();
   }catch (PDOException $pe){ 
       error_log("Error processing :  Reported Errata Summary stream query", $pe->getMessage(), $pe->getCode(), array('exception' => $pe)); 
   }


$stream_result_array = array();

if ($num_of_area_rows > 0) { 
        while($stream_row = $stream_stmt->fetch(PDO::FETCH_NUM)) {
           array_push($area_result_array, $stream_row);
        }
}


#print_r($area_result_array);

$data_count = count($area_result_array);
$art_count = 0;
$wit_count = 0;

/*Area art count = app + rai + art*/

if ($data_count > 0){
   for ($i=0; $i < $data_count; $i++){
      for ($j=0; $j < 3; $j++){
         if ($area_result_array [$i][$j] == 'app'){
            $art_count = $art_count + $area_result_array[$i][2]; 
         }elseif ($area_result_array [$i][$j] == 'art'){
            $art_count = $art_count + $area_result_array[$i][2]; 
         }elseif ($area_result_array [$i][$j] == 'rai'){ 
            $art_count = $art_count + $area_result_array[$i][2]; 
         }elseif ($area_result_array [$i][$j] == 'Legacy'){
            $area_result_array[$i][1] = "Legacy";
         }
      }
   }
}

if ($data_count > 0){
   for ($i=0; $i < $data_count; $i++){
      for ($j=0; $j < 3; $j++){
         if ($area_result_array [$i][$j] == 'art'){
            $area_result_array [$i][2] = $art_count; 
         }
      }
   } 
}

#print_r($area_result_array);


if ($data_count > 0){
   $message = '';
   /*print_header is for the HTML errata email*/
   $print_header = '
   <html>
   <head>
       <title>Reported Errata Summary</title>
   </head>';
   $print_body = '<body>
       <p>Below is a summary of technical errata awaiting review (as of today).</p>
       <p>Log in to https://www.rfc-editor.org/verify_login.php before using the verifier links below.</p>
       <table border=\'0\' width=\'\'>
           <tr>
               <th>Errata #</th><th>    Source of RFC</th>
           </tr>';

   $print_line = '';
   $last_print_line = '';

/******************************************************************************************
Adjustments are made to keep the printing order as : 'Applications and Real-Time','General','Internet','Operations and Management','Routing','Security','Web and Internet Transport','IAB','INDEPENDENT','IRTF', 'Editorial', 'Legacy'
*****************************************************************************************/
   foreach ($area_result_array as $line) {
       if (trim($line[0]) == 'Legacy'){
           $last_print_line .= '<tr><td allign=\'center\'>'.$line[2].'</td><td>'.$line[0].' (<a href=\'https://www.rfc-editor.org/verify_errata_select.php?rec_status='.$errata_status_id.'&stream_name='.$line[1].'&errata_type='.$errata_type_id.'&presentation=table \'>verifier page</a> 
|<a href=\'https://www.rfc-editor.org/errata_search.php?rec_status='.$errata_status_id.'&errata_type='.$errata_type_id.'&stream_name='.$line[0].'&presentation=table \'> public page</a>)</td></tr>';
 
       }elseif (trim($line[0]) == 'IAB'){
           $print_line .= '<tr><td allign=\'center\'>'.$line[2].'</td><td>'.$line[0].' (<a href=\'https://www.rfc-editor.org/verify_errata_select.php?rec_status='.$errata_status_id.'&stream_name='.$line[0].'&errata_type='.$errata_type_id.'&presentation=table \'>verifier page</a> 
|<a href=\'https://www.rfc-editor.org/errata_search.php?rec_status='.$errata_status_id.'&errata_type='.$errata_type_id.'&stream_name='.$line[0].'&presentation=table \'> public page</a>)</td></tr>';
 
       }elseif (trim($line[0]) == 'INDEPENDENT'){
           $print_line .= '<tr><td allign=\'center\'>'.$line[2].'</td><td>Independent (<a href=\'https://www.rfc-editor.org/verify_errata_select.php?rec_status='.$errata_status_id.'&stream_name='.$line[0].'&errata_type='.$errata_type_id.'&presentation=table \'>verifier page</a> 
|<a href=\'https://www.rfc-editor.org/errata_search.php?rec_status='.$errata_status_id.'&errata_type='.$errata_type_id.'&stream_name='.$line[0].'&presentation=table \'> public page</a>)</td></tr>';

        }elseif (trim($line[0]) == 'IRTF'){
            $print_line .= '<tr><td allign=\'center\'>'.$line[2].'</td><td>'.$line[0].' (<a href=\'https://www.rfc-editor.org/verify_errata_select.php?rec_status='.$errata_status_id.'&stream_name='.$line[0].'&errata_type='.$errata_type_id.'&presentation=table \'>verifier page</a> 
|<a href=\'https://www.rfc-editor.org/errata_search.php?rec_status='.$errata_status_id.'&errata_type='.$errata_type_id.'&stream_name='.$line[0].'&presentation=table \'> public page</a>)</td></tr>';

        }elseif (trim($line[0]) == 'Editorial'){
          $print_line .= '<tr><td allign=\'center\'>'.$line[2].'</td><td>'.$line[0].' (<a href=\'https://www.rfc-editor.org/verify_errata_select.php?rec_status='.$errata_status_id.'&stream_name='.$line[0].'&errata_type='.$errata_type_id.'&presentation=table \'>verifier page</a> 
|<a href=\'https://www.rfc-editor.org/errata_search.php?rec_status='.$errata_status_id.'&errata_type='.$errata_type_id.'&stream_name='.$line[0].'&presentation=table \'> public page</a>)</td></tr>';

      }elseif (trim($line[0]) == 'Applications and Real-Time') {
          $print_line .= '<tr><td allign=\'center\'>'.$line[2].'</td><td>'.strtoupper($line[1]).' (<a href=\'https://www.rfc-editor.org/verify_errata_select.php?rec_status='.$errata_status_id.'&area_acronym='.$line[1].'&errata_type='.$errata_type_id.'&presentation=table \'>verifier page</a> | <a href=\'https://www.rfc-editor.org/errata_search.php?rec_status='.$errata_status_id.'&errata_type='.$errata_type_id.'&area_acronym='.$line[1].'&presentation=table \'>public page</a>)</td></tr>';
      } elseif (trim($line[0]) == 'General') {
          $print_line .= '<tr><td allign=\'center\'>'.$line[2].'</td><td>'.strtoupper($line[1]).' (<a href=\'https://www.rfc-editor.org/verify_errata_select.php?rec_status='.$errata_status_id.'&area_acronym='.$line[1].'&errata_type='.$errata_type_id.'&presentation=table \'>verifier page</a> | <a href=\'https://www.rfc-editor.org/errata_search.php?rec_status='.$errata_status_id.'&errata_type='.$errata_type_id.'&area_acronym='.$line[1].'&presentation=table \'>public page</a>)</td></tr>';
      } elseif (trim($line[0]) == 'Internet') {
          $print_line .= '<tr><td allign=\'center\'>'.$line[2].'</td><td>'.strtoupper($line[1]).' (<a href=\'https://www.rfc-editor.org/verify_errata_select.php?rec_status='.$errata_status_id.'&area_acronym='.$line[1].'&errata_type='.$errata_type_id.'&presentation=table \'>verifier page</a> | <a href=\'https://www.rfc-editor.org/errata_search.php?rec_status='.$errata_status_id.'&errata_type='.$errata_type_id.'&area_acronym='.$line[1].'&presentation=table \'>public page</a>)</td></tr>';
      } elseif (trim($line[0]) == 'Operations and Management') {
          $print_line .= '<tr><td allign=\'center\'>'.$line[2].'</td><td>'.strtoupper($line[1]).' (<a href=\'https://www.rfc-editor.org/verify_errata_select.php?rec_status='.$errata_status_id.'&area_acronym='.$line[1].'&errata_type='.$errata_type_id.'&presentation=table \'>verifier page</a> | <a href=\'https://www.rfc-editor.org/errata_search.php?rec_status='.$errata_status_id.'&errata_type='.$errata_type_id.'&area_acronym='.$line[1].'&presentation=table \'>public page</a>)</td></tr>';
      } elseif (trim($line[0]) == 'Routing') {
          $print_line .= '<tr><td allign=\'center\'>'.$line[2].'</td><td>'.strtoupper($line[1]).' (<a href=\'https://www.rfc-editor.org/verify_errata_select.php?rec_status='.$errata_status_id.'&area_acronym='.$line[1].'&errata_type='.$errata_type_id.'&presentation=table \'>verifier page</a> | <a href=\'https://www.rfc-editor.org/errata_search.php?rec_status='.$errata_status_id.'&errata_type='.$errata_type_id.'&area_acronym='.$line[1].'&presentation=table \'>public page</a>)</td></tr>';
      } elseif (trim($line[0]) == 'Security') {
          $print_line .= '<tr><td allign=\'center\'>'.$line[2].'</td><td>'.strtoupper($line[1]).' (<a href=\'https://www.rfc-editor.org/verify_errata_select.php?rec_status='.$errata_status_id.'&area_acronym='.$line[1].'&errata_type='.$errata_type_id.'&presentation=table \'>verifier page</a> | <a href=\'https://www.rfc-editor.org/errata_search.php?rec_status='.$errata_status_id.'&errata_type='.$errata_type_id.'&area_acronym='.$line[1].'&presentation=table \'>public page</a>)</td></tr>';
      } elseif (trim($line[0]) == 'Web and Internet Transport') {
          $print_line .= '<tr><td allign=\'center\'>'.$line[2].'</td><td>'.strtoupper($line[1]).' (<a href=\'https://www.rfc-editor.org/verify_errata_select.php?rec_status='.$errata_status_id.'&area_acronym='.$line[1].'&errata_type='.$errata_type_id.'&presentation=table \'>verifier page</a> 
      | <a href=\'https://www.rfc-editor.org/errata_search.php?rec_status='.$errata_status_id.'&errata_type='.$errata_type_id.'&area_acronym='.$line[1].'&presentation=table \'>public page</a>)</td></tr>';
       }

}
$print_line .= $last_print_line;
$print_line .='</table>';
$print_line .='<p>The links above are to the relevant results on https://www.rfc-editor.org/errata.php; it includes RFCs that have a source of Legacy or Non-WG with an area assignment.</p>';
$print_line .='<p>For an overview of how to verify errata, please see https://www.rfc-editor.org/how-to-verify/.</p>';
$print_line .='<p>Please let us know if you have questions.</p>';
$print_line .='<p>Thank you.</p>';
$print_line .= '<p>RFC Editor</p>';
$print_line .='</body></html>';
$message = $print_header.$print_body.$print_line;


$to_address = get_to_address();

/***************************************************************************/
/*Generate the header part for the email                                   */
/***************************************************************************/
$to = $to_address;
$subject = 'Reported Errata Summary for '.date('F Y');
$headers['From'] = 'rfc-editor@rfc-editor.org';
$headers['Reply-To']= 'rfc-editor@rfc-editor.org';
$headers['MIME-Version'] = 'MIME-Version: 1.0';
$headers['Content-type'] = 'text/html; charset=iso-8859-1';

$result = mail($to, $subject, $message, $headers);

if ($result) {
    echo 'Success!' . PHP_EOL;
} else {
    echo 'Error.' . PHP_EOL;
}

} else {

   print "No data to generate email so terminate!!";
   
}


$pdo = null;


function get_to_address(){

global $pdo;


$ssp_id = [5,7,8];
   
$in = "";
foreach ($ssp_id as $i => $item)
   {
       $key = ":ssp_id".$i;
       $in .= "$key,";
       $in_params[$key] = $item; // collecting values into key-value array
   }
   $in = rtrim($in,",");

try {

    $address_sql= "SELECT `ssp_email` FROM `stream_specific_parties` 
    WHERE `ssp_id` not in ($in);"; 

    $address_stmt = $pdo->prepare($address_sql);
    $address_stmt->execute($in_params); 
    $num_of_address_rows = $address_stmt->rowCount();

   }catch (PDOException $pe){ 
       error_log("Error processing :  Reported Errata Summary get to address query", $pe->getMessage(), $pe->getCode(), array('exception' => $pe)); 
   }


   $to_address = '';
   $row_counter = 1;


   #print "No of row :".$num_of_address_rows;

   if ($num_of_address_rows > 0) { 
        while($address_row = $address_stmt->fetch(PDO::FETCH_NUM)) {
            
           if ($row_counter < $num_of_address_rows) {
              $to_address .= $address_row[0].", ";
           } else {
              $to_address .= $address_row[0];
           }
           $row_counter++;
         }
   }

return $to_address;
}
  

 
?>
