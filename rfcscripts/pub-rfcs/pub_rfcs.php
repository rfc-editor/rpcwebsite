<?php

include_once("db_connect.php");

// Include WordPress
define('WP_USE_THEMES', false);
//require('/a/wordpress/wp-blog-header.php');
require_once ('/a/wordpress/wp-load.php');


//Add title specifying the dynamic page 
add_filter( 'wp_title', 'wp_title_published_rfcs', 10, 3 );

function wp_title_published_rfcs( $title, $sep, $seplocation ) {
    return 'Monthly Published RFCs &raquo; RFC Editor';
}

//Add body class constant to avoid error404 body class for Wordpress outside page

function add_body_class_function($classes){

return array ('publishedrfcs');
}

add_action('body_class','add_body_class_function');


get_header();
get_sidebar('left');


?>

<div id="content" class="narrowcolumn">
     <h1 class="post_title">Published RFCs</h1>

     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">


  <br>
  <form method="post" id="query" action="pub_rfcs.php">
  <table  border= "0" align="center" class="erratasearch">
     <tr><td colspan="8" style="text-decoration: underline;">Select Month and Year from dropdown: </td></tr>
    <?php
    get_date_value($_POST);
?>
   <tr><td colspan="8" class="">
  <input type="submit" value="Submit" />
  </td></tr>
  </table>
  </form>
  <br>

<?php


   if (isset($_POST) && (array_key_exists('pub_year',$_POST))) {
     $pub_year = $_POST['pub_year']; 
   }
  
   if (isset($_POST) && (array_key_exists('pub_month',$_POST))) {
     $pub_month = $_POST['pub_month']; 
   }

   if ((($pub_month != "") || ($pub_month != null )) && (($pub_year != "") || ($pub_year != null ))) {
       $pub_month_num = date('m', strtotime($pub_month));
       $pub_monthlast = cal_days_in_month(CAL_GREGORIAN, $pub_month_num, $pub_year);
       $month_last_date = $pub_year.'-'.$pub_month_num.'-'.$pub_monthlast;
       $month_first_date = $pub_year.'-'.$pub_month_num.'-01';
       list($pub_count,$rfcs_published_list,$rfcs_number_list) = get_published_rfcs($month_first_date,$month_last_date);
       if ($pub_count > 0) {

          print "<p>Found $pub_count RFCs for $pub_month, $pub_year</p>";
          print '<div class="scrolltable">';
          print "<table class=\"dynamictable\">\n"; 
          #print "<table>";        
          for ($i=0;$i<= $pub_count; $i++){
               $pub_item = $rfcs_published_list[$i];
               $pub_number = $rfcs_number_list[$i]; 
               print "<tr><td colspan=\"2\"><a href=$pub_item>$pub_item</a></td><tr>";

          }
          print "</table>";  
          print "</div>";      
       } else {

          print "<p>Found 0 records</p>";
       }            
   }
  ?>
   </div>
  </div>
</div>
<?php
    print "<br>";
    print "<br>";
get_footer();



function get_date_value($in_values){
   $months = array('January','February','March','April','May','June','July','August','September','October','November','December');
   $years = array('2023');

?>
   <tr><td colspan="2"><label for="month">Month :</label></td>
       <td colspan="2">
         <select name="pub_month" id="pub_month">
<?php

  if (!(array_key_exists('pub_month',$in_values))) {
             print("<option value=\"\">Month</option>\n");
             foreach ($months as $pub_month) {
                 print("             <option value=\"$pub_month\">$pub_month</option>\n");
             }
  } else {
             print("<option value=\"\">Month</option>\n");
             foreach ($months as $pub_month) {
               if (strcasecmp($pub_month, $in_values['pub_month']) == 0) {
                   print("             <option value=\"$pub_month\" selected=\"selected\">$pub_month</option>\n");
               } else {
                   print("             <option value=\"$pub_month\">$pub_month</option>\n");
               }
             }
         }
         print("            </select>\n");
?>
       </td>
       <td colspan="2"><label for="year">Year :</label></td>

       <td colspan="2">
         <select name="pub_year" id="pub_year">
    
<?php
         if (!(array_key_exists('pub_year',$in_values))) {
             print("<option value=\"\">Year</option>\n");
             foreach ($years as $pub_year) {
                 print("             <option value=\"$pub_year\">$pub_year</option>\n");
             }
         } else {
             print("<option value=\"\">Year</option>\n");
             foreach ($years as $pub_year) {
                 if (strcasecmp($pub_year, $in_values['pub_year']) == 0) {
                     print("             <option value=\"$pub_year\" selected=\"selected\">$pub_year</option>\n");
                 } else {
                     print("             <option value=\"$pub_year\">$pub_year</option>\n");
                 }
             }
         }



/************************************************************************************************/
/* get_published_rfcs :  This function get the list of published RFC's for the given month      */
/************************************************************************************************/

function get_published_rfcs($start_date,$end_date) {

global $pdo;

$rfc_list = array();
$rfc_number_list = array();

$rfc_const = 'https://www.rfc-editor.org/rfc/';

$select = "SELECT LOWER(`DOC-ID`) FROM `index` where `PUB-DATE` BETWEEN :start_date AND :end_date";

try{
    $stmt = $pdo->prepare($select);
    $stmt->bindParam('start_date',$start_date);
    $stmt->bindParam('end_date',$end_date);
    $stmt->execute();
    $rfcs_published = $stmt->rowCount();
} catch (PDOException $pe){
       error_log("Error processing :  get_published_rfcs", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
}

    while ($row = $stmt->fetch(PDO::FETCH_NUM)){
           $rfc_number_list = array_merge($rfc_number_list,$row);
           $row[0] = $rfc_const.$row[0].'.html';
          $rfc_list = array_merge($rfc_list,$row);
    }
     return array($rfcs_published,$rfc_list,$rfc_number_list);

}


?>







       </select>
       </td>
     </tr>

<?php
}
?>

