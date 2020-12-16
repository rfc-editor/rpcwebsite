<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
# $Id: format_lib.php,v 2.0 2010/02/16 22:53:45 rcross Exp $
# --------------------------------------------------------------------------- #
# Routines for some common string formating.
# --------------------------------------------------------------------------- #

# Take a DOC-ID string and reformat it such that the type identifier
# and number are separated by a space and any zero padding is removed.
# e.g. BCP0123 becomes BCP 123.
function format_id($id) {
     $type = substr($id,0,3);
     $num  = substr($id,3);
     if (ctype_digit($num)) {
          return sprintf("$type %d",$num);
     }
     return "[-=>$id<=- not in expected format]";
}


# Called with the YYYY-MM-DD time string from database entry, returns
# a string with format Month, YYYY
function format_date($date) {
     $parsed_date = date_parse($date);
     $month = map_month($parsed_date['month']);
     return $month . ' ' . $parsed_date['year'];
}


# Return name for a month.
function map_month($month) {
     switch ($month) {
     case '12':
          return 'December';
     case '11':
          return 'November';
     case '10':
          return 'October';
     case '9':
          return 'September';
     case '8':
          return 'August';
     case '7':
          return 'July';
     case '6':
          return 'June';
     case '5':
          return 'May';
     case '4':
          return 'April';
     case '3':
          return 'March';
     case '2':
          return 'February';
     case '1':
          return 'January';
     default:
          return 'UNKNOWN';
     }
}

?>
