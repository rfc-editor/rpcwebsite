<?php 
 /* August 2022 : Added 'Editorial' stream to the script - PN */

error_reporting(E_ALL ^ E_NOTICE);
if (!function_exists("GetSQLValueString")) {
function GetSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "") 
{
  if (PHP_VERSION < 6) { $theValue = get_magic_quotes_gpc() ? stripslashes($theValue) : $theValue; }
  $theValue = function_exists("mysql_real_escape_string") ? mysql_real_escape_string($theValue) : mysql_escape_string($theValue);
  switch ($theType) {
    case "text":
      $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
      break;    
    case "long":
    case "int":
      $theValue = ($theValue != "") ? intval($theValue) : "NULL";
      break;
    case "double":
      $theValue = ($theValue != "") ? doubleval($theValue) : "NULL";
      break;
    case "date":
      $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
      break;
    case "defined":
      $theValue = ($theValue != "") ? $theDefinedValue : $theNotDefinedValue;
      break;
  }
  return $theValue; } }
if (!function_exists("Emit")) {
function Emit() {
global $curyear, $curmonth;
$sf=0;
$ef=0;


$dataname = "rfcdev";
$username = "rfcdev";
$password = "dev";

$idata = array();
global $curyear, $curmonth, $sf, $ef;
$cy = $curyear; if ($cy<2013) die();
$cm = $curmonth; if ($cm<1) die() ;
$ny = $cy; $nm = ( $cm + 1 ) % 13 + 1; if($nm==1) $ny++; else $nm--;
if($cm<10) $cm = "0".$cm;
if($nm<10) $nm = "0".$nm;
$sf = $cy."-".$cm."-01";
$ef = $ny."-".$nm."-01";
$mysqli = new mysqli("localhost",$username,$password,$dataname);
if ($mysqli->connect_errno) { echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error; die(); }
# Subcounts IAB
$q1 = "SELECT `pub-status`, COUNT(DISTINCT `draft`) as cc from `index` where `doc-id` like 'RFC%' AND source='IAB' AND date_received >='".$sf."' AND date_received <'".$ef."' GROUP BY `pub-status`";
$r1 = mysqli_query($mysqli, $q1) or die(mysqli_error($mysqli));
$r1->data_seek(0);
while ($row = $r1->fetch_assoc()) { $ir=$row['pub-status']; $id=$row['cc']; $idata['SIAB'][$ir]=$id; $idata['SIAB']['TOTAL']+=$id; }
mysqli_free_result($r1);
# Subcounts WG from July 2022 consider Editorial Stream
if ($sf >= '2022-07-01') {
    $q1 = "SELECT `pub-status`, COUNT(DISTINCT `draft`) as cc from `index` where `doc-id` like 'RFC%' AND (source!='IETF - NON WORKING GROUP' AND source!='IAB' AND source!='INDEPENDENT' AND source NOT LIKE '%Research Group' AND source!='Editorial') AND date_received >='".$sf."' AND date_received <'".$ef."' GROUP BY `pub-status`";
    $r1 = mysqli_query($mysqli, $q1) or die(mysqli_error($mysqli));
    $r1->data_seek(0);
    while ($row = $r1->fetch_assoc()) { $ir=$row['pub-status']; $id=$row['cc']; $idata['SWG'][$ir]=$id; $idata['SWG']['TOTAL']+=$id; }
    mysqli_free_result($r1);
} else {
  # Subcounts WG without Editorial Stream
    $q1 = "SELECT `pub-status`, COUNT(DISTINCT `draft`) as cc from `index` where `doc-id` like 'RFC%' AND (source!='IETF - NON WORKING GROUP' AND source!='IAB' AND source!='INDEPENDENT' AND source NOT LIKE '%Research Group') AND date_received >='".$sf."' AND date_received <'".$ef."' GROUP BY `pub-status`";
    $r1 = mysqli_query($mysqli, $q1) or die(mysqli_error($mysqli));
    $r1->data_seek(0);
    while ($row = $r1->fetch_assoc()) { $ir=$row['pub-status']; $id=$row['cc']; $idata['SWG'][$ir]=$id; $idata['SWG']['TOTAL']+=$id; }
    mysqli_free_result($r1);
}

# Subcounts NWG
$q1 = "SELECT `pub-status`, COUNT(DISTINCT `draft`) as cc from `index` where `doc-id` like 'RFC%' AND source='IETF - NON WORKING GROUP' AND date_received >='".$sf."' AND date_received <'".$ef."' GROUP BY `pub-status`";
$r1 = mysqli_query($mysqli, $q1) or die(mysqli_error($mysqli));
$r1->data_seek(0);
while ($row = $r1->fetch_assoc()) { $ir=$row['pub-status']; $id=$row['cc']; $idata['SNWG'][$ir]=$id; $idata['SNWG']['TOTAL']+=$id; }
mysqli_free_result($r1);
# Subcounts IN
$q1 = "SELECT `pub-status`, COUNT(DISTINCT `draft`) as cc from `index` where `doc-id` like 'RFC%' AND source='INDEPENDENT' AND date_received >='".$sf."' AND date_received <'".$ef."' GROUP BY `pub-status`";
$r1 = mysqli_query($mysqli, $q1) or die(mysqli_error($mysqli));
$r1->data_seek(0);
while ($row = $r1->fetch_assoc()) { $ir=$row['pub-status']; $id=$row['cc']; $idata['SIN'][$ir]=$id; $idata['SIN']['TOTAL']+=$id; }
mysqli_free_result($r1);
# Subcounts IR
$q1 = "SELECT `pub-status`, COUNT(DISTINCT `draft`) as cc from `index` where `doc-id` like 'RFC%' AND source LIKE '%Research Group' AND date_received >='".$sf."' AND date_received <'".$ef."' GROUP BY `pub-status`";
$r1 = mysqli_query($mysqli, $q1) or die(mysqli_error($mysqli));
$r1->data_seek(0);
while ($row = $r1->fetch_assoc()) { $ir=$row['pub-status']; $id=$row['cc']; $idata['SIR'][$ir]=$id; $idata['SIR']['TOTAL']+=$id; }
mysqli_free_result($r1);

/***************/
# Subcounts Editorial Stream into account from July 2022
if ($sf >= '2022-07-01') {
   $q1 = "SELECT `pub-status`, COUNT(DISTINCT `draft`) as cc from `index` where `doc-id` like 'RFC%' AND source = 'Editorial' AND date_received >='".$sf."' AND date_received <'".$ef."' 
          GROUP BY `pub-status`";
    $r1 = mysqli_query($mysqli, $q1) or die(mysqli_error($mysqli));
    $r1->data_seek(0);
    while ($row = $r1->fetch_assoc()) { $ir=$row['pub-status']; $id=$row['cc']; $idata['SEDIT'][$ir]=$id; $idata['SEDIT']['TOTAL']+=$id; }
    mysqli_free_result($r1);
}
/***************/
# Subcounts TOT
$q1 = "SELECT `pub-status`, COUNT(DISTINCT `draft`) as cc from `index` where `doc-id` like 'RFC%' AND date_received >='".$sf."' AND date_received <'".$ef."' GROUP BY `pub-status`";
$r1 = mysqli_query($mysqli, $q1) or die(mysqli_error($mysqli));
$r1->data_seek(0);
while ($row = $r1->fetch_assoc()) { $ir=$row['pub-status']; $id=$row['cc']; $idata['STOT'][$ir]=$id; $idata['STOT']['TOTAL']+=$id; }
mysqli_free_result($r1);
# Pubcounts IAB
$q1 = "SELECT `pub-status`, COUNT(DISTINCT `doc-id`) as cc from `index` where `doc-id` like 'RFC%' AND state_id='14' AND source='IAB' AND `pub-date` >='".$sf."' AND `pub-date` <'".$ef."' GROUP BY `pub-status`";
$r1 = mysqli_query($mysqli, $q1) or die(mysqli_error($mysqli));
$r1->data_seek(0);
while ($row = $r1->fetch_assoc()) { $ir=$row['pub-status']; $id=$row['cc']; $idata['PIAB'][$ir]=$id; $idata['PIAB']['TOTAL']+=$id; }
mysqli_free_result($r1);
# Pubcounts WG with Editorial Stream
if ($sf >= '2022-07-01') {
    $q1 = "SELECT `pub-status`, COUNT(DISTINCT `doc-id`) as cc from `index` where `doc-id` like 'RFC%' AND state_id='14' AND (source!='IETF - NON WORKING GROUP' AND source!='IAB' AND source!='INDEPENDENT' AND source NOT LIKE '%Research Group' AND source != 'Editorial') AND `pub-date` >='".$sf."' AND `pub-date` <'".$ef."' GROUP BY `pub-status`";
    $r1 = mysqli_query($mysqli, $q1) or die(mysqli_error($mysqli));
    $r1->data_seek(0);
    while ($row = $r1->fetch_assoc()) { $ir=$row['pub-status']; $id=$row['cc']; $idata['PWG'][$ir]=$id; $idata['PWG']['TOTAL']+=$id; }
    mysqli_free_result($r1);
} else {
# Pubcounts WG without Editorial Stream
    $q1 = "SELECT `pub-status`, COUNT(DISTINCT `doc-id`) as cc from `index` where `doc-id` like 'RFC%' AND state_id='14' AND (source!='IETF - NON WORKING GROUP' AND source!='IAB' AND source!='INDEPENDENT' AND source NOT LIKE '%Research Group') AND `pub-date` >='".$sf."' AND `pub-date` <'".$ef."' GROUP BY `pub-status`";
    $r1 = mysqli_query($mysqli, $q1) or die(mysqli_error($mysqli));
    $r1->data_seek(0);
    while ($row = $r1->fetch_assoc()) { $ir=$row['pub-status']; $id=$row['cc']; $idata['PWG'][$ir]=$id; $idata['PWG']['TOTAL']+=$id; }
    mysqli_free_result($r1);
}

# Pubcounts NWG
$q1 = "SELECT `pub-status`, COUNT(DISTINCT `doc-id`) as cc from `index` where `doc-id` like 'RFC%' AND state_id='14' AND source='IETF - NON WORKING GROUP' AND `pub-date` >='".$sf."' AND `pub-date` <'".$ef."' GROUP BY `pub-status`";
$r1 = mysqli_query($mysqli, $q1) or die(mysqli_error($mysqli));
$r1->data_seek(0);
while ($row = $r1->fetch_assoc()) { $ir=$row['pub-status']; $id=$row['cc']; $idata['PNWG'][$ir]=$id; $idata['PNWG']['TOTAL']+=$id; }
mysqli_free_result($r1);
# Pubcounts IN
$q1 = "SELECT `pub-status`, COUNT(DISTINCT `doc-id`) as cc from `index` where `doc-id` like 'RFC%' AND state_id='14' AND source='INDEPENDENT' AND `pub-date` >='".$sf."' AND `pub-date` <'".$ef."' GROUP BY `pub-status`";
$r1 = mysqli_query($mysqli, $q1) or die(mysqli_error($mysqli));
$r1->data_seek(0);
while ($row = $r1->fetch_assoc()) { $ir=$row['pub-status']; $id=$row['cc']; $idata['PIN'][$ir]=$id; $idata['PIN']['TOTAL']+=$id; }
mysqli_free_result($r1);
# Pubcounts IR
$q1 = "SELECT `pub-status`, COUNT(DISTINCT `doc-id`) as cc from `index` where `doc-id` like 'RFC%' AND state_id='14' AND source LIKE '%Research Group' AND `pub-date` >='".$sf."' AND `pub-date` <'".$ef."' GROUP BY `pub-status`";
$r1 = mysqli_query($mysqli, $q1) or die(mysqli_error($mysqli));
$r1->data_seek(0);
while ($row = $r1->fetch_assoc()) { $ir=$row['pub-status']; $id=$row['cc']; $idata['PIR'][$ir]=$id; $idata['PIR']['TOTAL']+=$id; }
mysqli_free_result($r1);
/**************/
# Pubcounts Editorial into account only from July 2022

if ($sf >= '2022-07-01') {
   $q1 = "SELECT `pub-status`, COUNT(DISTINCT `doc-id`) as cc from `index` where `doc-id` like 'RFC%' AND state_id='14' AND source = 'Editorial' AND `pub-date` >='".$sf."' AND `pub-date` <'".$ef."' GROUP BY `pub-status`";
   $r1 = mysqli_query($mysqli, $q1) or die(mysqli_error($mysqli));
   $r1->data_seek(0);
   while ($row = $r1->fetch_assoc()) { $ir=$row['pub-status']; $id=$row['cc']; $idata['PEDIT'][$ir]=$id; $idata['PEDIT']['TOTAL']+=$id; }
   mysqli_free_result($r1);
}
/**************/
# Pubcounts TOT
$q1 = "SELECT `pub-status`, COUNT(DISTINCT `doc-id`) as cc from `index` where `doc-id` like 'RFC%' AND state_id='14' AND `pub-date` >='".$sf."' AND `pub-date` <'".$ef."' GROUP BY `pub-status`";
$r1 = mysqli_query($mysqli, $q1) or die(mysqli_error($mysqli));
$r1->data_seek(0);
while ($row = $r1->fetch_assoc()) { $ir=$row['pub-status']; $id=$row['cc']; $idata['PTOT'][$ir]=$id; $idata['PTOT']['TOTAL']+=$id; }
mysqli_free_result($r1);
mysqli_close($mysqli);
echo "<h4 align=\"left\">".date("F", mktime(0, 0, 0, $curmonth, 10))."</h4>\n";
echo "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
echo "<tr bgcolor=\"#ffffff\"><td class=\"e1\" width=\"50%\">";
echo "<table class=\"statictable\">";
echo "<tr>";
echo "<th colspan=\"2\">&nbsp;</th>";
echo "<th colspan=\"6\" align=\"left\" class=\"submheader\">Submissions</th>";
echo "</tr>";
if ($sf >= '2022-07-01') {
    echo "<tr class=\"hdr\">";
    echo "<td width=\"12%\">&nbsp;</th>";
    echo "<td width=\"15%\">IAB</th>";
    echo "<td width=\"15%\">IETF WG</th>";
    echo "<td width=\"15%\">IETF NWG</th>";
    echo "<td width=\"15%\">Indep</th>";
    echo "<td width=\"15%\">IRTF</th>";
    echo "<td width=\"15%\">Editorial</th>";
    echo "<td width=\"15%\"><b>Total</b></th>";
    echo "</tr>";
    echo "<tr class=\"even\">";
    echo "<td>IS</td>";
    echo "<td>".intval($idata['SIAB']['INTERNET STANDARD'])."</td><td>".intval($idata['SWG']['INTERNET STANDARD'])."</td><td>".intval($idata['SNWG']['INTERNET STANDARD'])."</td><td>".intval($idata['SIN']['INTERNET STANDARD'])."</td><td>".intval($idata['SIR']['INTERNET STANDARD'])."</td><td>".intval($idata['SEDIT']['INTERNET STANDARD'])."</td><td class=\"submtotal\">".intval($idata['STOT']['INTERNET STANDARD'])."</td>";
    echo "</tr>";
    echo "<tr class=\"odd\">";
    echo "<td>PS</td>";
    echo "<td>".intval($idata['SIAB']['PROPOSED STANDARD'])."</td><td>".intval($idata['SWG']['PROPOSED STANDARD'])."</td><td>".intval($idata['SNWG']['PROPOSED STANDARD'])."</td><td>".intval($idata['SIN']['PROPOSED STANDARD'])."</td><td>".intval($idata['SIR']['PROPOSED STANDARD'])."</td><td>".intval($idata['SEDIT']['PROPOSED STANDARD'])."</td><td class=\"submtotal\">".intval($idata['STOT']['PROPOSED STANDARD'])."</td>";
    echo "</tr>";
    echo "<tr class=\"even\">";
    echo "<td>BCP</td>";
    echo "<td>".intval($idata['SIAB']['BEST CURRENT PRACTICE'])."</td><td>".intval($idata['SWG']['BEST CURRENT PRACTICE'])."</td><td>".intval($idata['SNWG']['BEST CURRENT PRACTICE'])."</td><td>".intval($idata['SIN']['BEST CURRENT PRACTICE'])."</td><td>".intval($idata['SIR']['BEST CURRENT PRACTICE'])."</td><td>".intval($idata['SEDIT']['BEST CURRENT PRACTICE'])."</td><td class=\"submtotal\">".intval($idata['STOT']['BEST CURRENT PRACTICE'])."</td>";
    echo "</tr>";
    echo "<tr class=\"odd\">";
    echo "<td>Exp</td>";
    echo "<td>".intval($idata['SIAB']['EXPERIMENTAL'])."</td><td>".intval($idata['SWG']['EXPERIMENTAL'])."</td><td>".intval($idata['SNWG']['EXPERIMENTAL'])."</td><td>".intval($idata['SIN']['EXPERIMENTAL'])."</td><td>".intval($idata['SIR']['EXPERIMENTAL'])."</td><td>".intval($idata['SEDIT']['EXPERIMENTAL'])."</td><td class=\"submtotal\">".intval($idata['STOT']['EXPERIMENTAL'])."</td>";
    echo "</tr>";
    echo "<tr class=\"even\">";
    echo "<td>Info</td>";
    echo "<td>".intval($idata['SIAB']['INFORMATIONAL'])."</td><td>".intval($idata['SWG']['INFORMATIONAL'])."</td><td>".intval($idata['SNWG']['INFORMATIONAL'])."</td><td>".intval($idata['SIN']['INFORMATIONAL'])."</td><td>".intval($idata['SIR']['INFORMATIONAL'])."</td><td>".intval($idata['SEDIT']['INFORMATIONAL'])."</td><td class=\"submtotal\">".intval($idata['STOT']['INFORMATIONAL'])."</td>";
    echo "</tr>";
    echo "<tr class=\"odd\">";
    echo "<td>Hist</td>";
    echo "<td>".intval($idata['SIAB']['HISTORIC'])."</td><td>".intval($idata['SWG']['HISTORIC'])."</td><td>".intval($idata['SNWG']['HISTORIC'])."</td><td>".intval($idata['SIN']['HISTORIC'])."</td><td>".intval($idata['SIR']['HISTORIC'])."</td><td>".intval($idata['SEDIT']['HISTORIC'])."</td><td class=\"submtotal\">".intval($idata['STOT']['HISTORIC'])."</td>";
    echo "</tr>";
    echo "<tr class=\"total\">";
    echo "<td><b>Total</b></td>";
    echo "<td>".intval($idata['SIAB']['TOTAL'])."</td><td>".intval($idata['SWG']['TOTAL'])."</td><td>".intval($idata['SNWG']['TOTAL'])."</td><td>".intval($idata['SIN']['TOTAL'])."</td><td>".intval($idata['SIR']['TOTAL'])."</td><td>".intval($idata['SEDIT']['TOTAL'])."</td><td class=\"subs\"><a href=\"subpub_sub.php?y=".$cy."&m=".$cm."\">".intval($idata['STOT']['TOTAL'])."</a></td>";
    echo "</tr>";
    echo "</table>";
} else {
    echo "<tr class=\"hdr\">";
    echo "<td width=\"12%\">&nbsp;</th>";
    echo "<td width=\"15%\">IAB</th>";
    echo "<td width=\"15%\">IETF WG</th>";
    echo "<td width=\"15%\">IETF NWG</th>";
    echo "<td width=\"15%\">Indep</th>";
    echo "<td width=\"15%\">IRTF</th>";
    echo "<td width=\"15%\"><b>Total</b></th>";
    echo "</tr>";
    echo "<tr class=\"even\">";
    echo "<td>IS</td>";
    echo "<td>".intval($idata['SIAB']['INTERNET STANDARD'])."</td><td>".intval($idata['SWG']['INTERNET STANDARD'])."</td><td>".intval($idata['SNWG']['INTERNET STANDARD'])."</td><td>".intval($idata['SIN']['INTERNET STANDARD'])."</td><td>".intval($idata['SIR']['INTERNET STANDARD'])."</td><td class=\"submtotal\">".intval($idata['STOT']['INTERNET STANDARD'])."</td>";
    echo "</tr>";
    echo "<tr class=\"odd\">";
    echo "<td>PS</td>";
    echo "<td>".intval($idata['SIAB']['PROPOSED STANDARD'])."</td><td>".intval($idata['SWG']['PROPOSED STANDARD'])."</td><td>".intval($idata['SNWG']['PROPOSED STANDARD'])."</td><td>".intval($idata['SIN']['PROPOSED STANDARD'])."</td><td>".intval($idata['SIR']['PROPOSED STANDARD'])."</td><td class=\"submtotal\">".intval($idata['STOT']['PROPOSED STANDARD'])."</td>";
    echo "</tr>";
    echo "<tr class=\"even\">";
    echo "<td>BCP</td>";
    echo "<td>".intval($idata['SIAB']['BEST CURRENT PRACTICE'])."</td><td>".intval($idata['SWG']['BEST CURRENT PRACTICE'])."</td><td>".intval($idata['SNWG']['BEST CURRENT PRACTICE'])."</td><td>".intval($idata['SIN']['BEST CURRENT PRACTICE'])."</td><td>".intval($idata['SIR']['BEST CURRENT PRACTICE'])."</td><td class=\"submtotal\">".intval($idata['STOT']['BEST CURRENT PRACTICE'])."</td>";
    echo "</tr>";
    echo "<tr class=\"odd\">";
    echo "<td>Exp</td>";
    echo "<td>".intval($idata['SIAB']['EXPERIMENTAL'])."</td><td>".intval($idata['SWG']['EXPERIMENTAL'])."</td><td>".intval($idata['SNWG']['EXPERIMENTAL'])."</td><td>".intval($idata['SIN']['EXPERIMENTAL'])."</td><td>".intval($idata['SIR']['EXPERIMENTAL'])."</td><td class=\"submtotal\">".intval($idata['STOT']['EXPERIMENTAL'])."</td>";
    echo "</tr>";
    echo "<tr class=\"even\">";
    echo "<td>Info</td>";
    echo "<td>".intval($idata['SIAB']['INFORMATIONAL'])."</td><td>".intval($idata['SWG']['INFORMATIONAL'])."</td><td>".intval($idata['SNWG']['INFORMATIONAL'])."</td><td>".intval($idata['SIN']['INFORMATIONAL'])."</td><td>".intval($idata['SIR']['INFORMATIONAL'])."</td><td class=\"submtotal\">".intval($idata['STOT']['INFORMATIONAL'])."</td>";
    echo "</tr>";
    echo "<tr class=\"odd\">";
    echo "<td>Hist</td>";
    echo "<td>".intval($idata['SIAB']['HISTORIC'])."</td><td>".intval($idata['SWG']['HISTORIC'])."</td><td>".intval($idata['SNWG']['HISTORIC'])."</td><td>".intval($idata['SIN']['HISTORIC'])."</td><td>".intval($idata['SIR']['HISTORIC'])."</td><td class=\"submtotal\">".intval($idata['STOT']['HISTORIC'])."</td>";
    echo "</tr>";
    echo "<tr class=\"total\">";
    echo "<td><b>Total</b></td>";
    echo "<td>".intval($idata['SIAB']['TOTAL'])."</td><td>".intval($idata['SWG']['TOTAL'])."</td><td>".intval($idata['SNWG']['TOTAL'])."</td><td>".intval($idata['SIN']['TOTAL'])."</td><td>".intval($idata['SIR']['TOTAL'])."</td><td class=\"subs\"><a href=\"subpub_sub.php?y=".$cy."&m=".$cm."\">".intval($idata['STOT']['TOTAL'])."</a></td>";
    echo "</tr>";
    echo "</table>";
}

echo "</td><td class=\"e2\" width=\"5\">&nbsp;</td><td class=\"e1\" width=\"50%\">";
echo "<table class=\"statictable\">";
echo "<tr>";
echo "<th colspan=\"2\">&nbsp;</th>";
echo "<th colspan=\"6\" align=\"left\" class=\"submheader\">Publications</th>";
echo "</tr>";
if ($sf >= '2022-07-01') /*Considering Editorial Stream*/{
    echo "<tr class=\"hdr\">";
    echo "<td width=\"12%\">&nbsp;</th>";
    echo "<td width=\"15%\">IAB</th>";
    echo "<td width=\"15%\">IETF WG</th>";
    echo "<td width=\"15%\">IETF NWG</th>";
    echo "<td width=\"15%\">Indep</th>";
    echo "<td width=\"15%\">IRTF</th>";
    echo "<td width=\"15%\">Editorial</th>";
    echo "<td width=\"15%\"><b>Total</b></th>";
    echo "</tr>";
    echo "<tr class=\"even\">";
    echo "<td>IS</td>";
    echo "<td>".intval($idata['PIAB']['INTERNET STANDARD'])."</td><td>".intval($idata['PWG']['INTERNET STANDARD'])."</td><td>".intval($idata['PNWG']['INTERNET STANDARD'])."</td><td>".intval($idata['PIN']['INTERNET STANDARD'])."</td><td>".intval($idata['PIR']['INTERNET STANDARD'])."</td><td>".intval($idata['PEDIT']['INTERNET STANDARD'])." </td><td class=\"total\">".intval($idata['PTOT']['INTERNET STANDARD'])."</td>";
    echo "</tr>";
    echo "<tr class=\"odd\">";
    echo "<td>PS</td>";
    echo "<td>".intval($idata['PIAB']['PROPOSED STANDARD'])."</td><td>".intval($idata['PWG']['PROPOSED STANDARD'])."</td><td>".intval($idata['PNWG']['PROPOSED STANDARD'])."</td><td>".intval($idata['PIN']['PROPOSED STANDARD'])."</td><td>".intval($idata['PIR']['PROPOSED STANDARD'])."</td><td>".intval($idata['PEDIT']['PROPOSED STANDARD'])."</td><td class=\"total\">".intval($idata['PTOT']['PROPOSED STANDARD'])."</td>";
    echo "</tr>";
    echo "<tr class=\"even\">";
    echo "<td>BCP</td>";
    echo "<td>".intval($idata['PIAB']['BEST CURRENT PRACTICE'])."</td><td>".intval($idata['PWG']['BEST CURRENT PRACTICE'])."</td><td>".intval($idata['PNWG']['BEST CURRENT PRACTICE'])."</td><td>".intval($idata['PIN']['BEST CURRENT PRACTICE'])."</td><td>".intval($idata['PIR']['BEST CURRENT PRACTICE'])."</td><td>".intval($idata['PEDIT']['BEST CURRENT PRACTICE'])."</td><td class=\"total\">".intval($idata['PTOT']['BEST CURRENT PRACTICE'])."</td>";
    echo "</tr>";
    echo "<tr class=\"odd\">";
    echo "<td>Exp</td>";
    echo "<td>".intval($idata['PIAB']['EXPERIMENTAL'])."</td><td>".intval($idata['PWG']['EXPERIMENTAL'])."</td><td>".intval($idata['PNWG']['EXPERIMENTAL'])."</td><td>".intval($idata['PIN']['EXPERIMENTAL'])."</td><td>".intval($idata['PIR']['EXPERIMENTAL'])."</td><td>".intval($idata['PEDIT']['EXPERIMENTAL']) ."</td><td class=\"total\">".intval($idata['PTOT']['EXPERIMENTAL'])."</td>";
    echo "</tr>";
    echo "<tr class=\"even\">";
    echo "<td>Info</td>";
    echo "<td>".intval($idata['PIAB']['INFORMATIONAL'])."</td><td>".intval($idata['PWG']['INFORMATIONAL'])."</td><td>".intval($idata['PNWG']['INFORMATIONAL'])."</td><td>".intval($idata['PIN']['INFORMATIONAL'])."</td><td>".intval($idata['PIR']['INFORMATIONAL'])."</td><td>".intval($idata['PEDIT']['INFORMATIONAL'])."</td><td class=\"total\">".intval($idata['PTOT']['INFORMATIONAL'])."</td>";
    echo "</tr>";
    echo "<tr class=\"odd\">";
    echo "<td>Hist</td>";
    echo "<td>".intval($idata['PIAB']['HISTORIC'])."</td><td>".intval($idata['PWG']['HISTORIC'])."</td><td>".intval($idata['PNWG']['HISTORIC'])."</td><td>".intval($idata['PIN']['HISTORIC'])."</td><td>".intval($idata['PIR']['HISTORIC'])."</td><td>".intval($idata['PEDIT']['HISTORIC'])."</td><td class=\"total\">".intval($idata['PTOT']['HISTORIC'])."</td>";
    echo "</tr>";
    echo "<tr class=\"total\">";
    echo "<td><b>Total</b></td>";
    echo "<td>".intval($idata['PIAB']['TOTAL'])."</td><td>".intval($idata['PWG']['TOTAL'])."</td><td>".intval($idata['PNWG']['TOTAL'])."</td><td>".intval($idata['PIN']['TOTAL'])."</td><td>".intval($idata['PIR']['TOTAL'])."</td><td>".intval($idata['PEDIT']['TOTAL'])."</td><td class=\"pubs\"><a href=\"subpub_pub.php?y=".$cy."&m=".$cm."\">".intval($idata['PTOT']['TOTAL'])."</a></td>";
    echo "</tr>";
} else {
    echo "<tr class=\"hdr\">";
    echo "<td width=\"12%\">&nbsp;</th>";
    echo "<td width=\"15%\">IAB</th>";
    echo "<td width=\"15%\">IETF WG</th>";
    echo "<td width=\"15%\">IETF NWG</th>";
    echo "<td width=\"15%\">Indep</th>";
    echo "<td width=\"15%\">IRTF</th>";
    echo "<td width=\"15%\"><b>Total</b></th>";
    echo "</tr>";
    echo "<tr class=\"even\">";
    echo "<td>IS</td>";
    echo "<td>".intval($idata['PIAB']['INTERNET STANDARD'])."</td><td>".intval($idata['PWG']['INTERNET STANDARD'])."</td><td>".intval($idata['PNWG']['INTERNET STANDARD'])."</td><td>".intval($idata['PIN']['INTERNET STANDARD'])."</td><td>".intval($idata['PIR']['INTERNET STANDARD'])."</td><td class=\"total\">".intval($idata['PTOT']['INTERNET STANDARD'])."</td>";
    echo "</tr>";
    echo "<tr class=\"odd\">";
    echo "<td>PS</td>";
    echo "<td>".intval($idata['PIAB']['PROPOSED STANDARD'])."</td><td>".intval($idata['PWG']['PROPOSED STANDARD'])."</td><td>".intval($idata['PNWG']['PROPOSED STANDARD'])."</td><td>".intval($idata['PIN']['PROPOSED STANDARD'])."</td><td>".intval($idata['PIR']['PROPOSED STANDARD'])."</td><td class=\"total\">".intval($idata['PTOT']['PROPOSED STANDARD'])."</td>";
    echo "</tr>";
    echo "<tr class=\"even\">";
    echo "<td>BCP</td>";
    echo "<td>".intval($idata['PIAB']['BEST CURRENT PRACTICE'])."</td><td>".intval($idata['PWG']['BEST CURRENT PRACTICE'])."</td><td>".intval($idata['PNWG']['BEST CURRENT PRACTICE'])."</td><td>".intval($idata['PIN']['BEST CURRENT PRACTICE'])."</td><td>".intval($idata['PIR']['BEST CURRENT PRACTICE'])."</td><td class=\"total\">".intval($idata['PTOT']['BEST CURRENT PRACTICE'])."</td>";
    echo "</tr>";
    echo "<tr class=\"odd\">";
    echo "<td>Exp</td>";
    echo "<td>".intval($idata['PIAB']['EXPERIMENTAL'])."</td><td>".intval($idata['PWG']['EXPERIMENTAL'])."</td><td>".intval($idata['PNWG']['EXPERIMENTAL'])."</td><td>".intval($idata['PIN']['EXPERIMENTAL'])."</td><td>".intval($idata['PIR']['EXPERIMENTAL'])."</td><td class=\"total\">".intval($idata['PTOT']['EXPERIMENTAL'])."</td>";
    echo "</tr>";
    echo "<tr class=\"even\">";
    echo "<td>Info</td>";
    echo "<td>".intval($idata['PIAB']['INFORMATIONAL'])."</td><td>".intval($idata['PWG']['INFORMATIONAL'])."</td><td>".intval($idata['PNWG']['INFORMATIONAL'])."</td><td>".intval($idata['PIN']['INFORMATIONAL'])."</td><td>".intval($idata['PIR']['INFORMATIONAL'])."</td><td class=\"total\">".intval($idata['PTOT']['INFORMATIONAL'])."</td>";
    echo "</tr>";
    echo "<tr class=\"odd\">";
    echo "<td>Hist</td>";
    echo "<td>".intval($idata['PIAB']['HISTORIC'])."</td><td>".intval($idata['PWG']['HISTORIC'])."</td><td>".intval($idata['PNWG']['HISTORIC'])."</td><td>".intval($idata['PIN']['HISTORIC'])."</td><td>".intval($idata['PIR']['HISTORIC'])."</td><td class=\"total\">".intval($idata['PTOT']['HISTORIC'])."</td>";
    echo "</tr>";
    echo "<tr class=\"total\">";
    echo "<td><b>Total</b></td>";
    echo "<td>".intval($idata['PIAB']['TOTAL'])."</td><td>".intval($idata['PWG']['TOTAL'])."</td><td>".intval($idata['PNWG']['TOTAL'])."</td><td>".intval($idata['PIN']['TOTAL'])."</td><td>".intval($idata['PIR']['TOTAL'])."</td><td class=\"pubs\"><a href=\"subpub_pub.php?y=".$cy."&m=".$cm."\">".intval($idata['PTOT']['TOTAL'])."</a></td>";
    echo "</tr>";




}
echo "</table>"; 
echo "</td></tr></table>";
} }
?>
