<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
/*###################################################################
# This functions gets the exact display padding length for passed variable
####################################################################*/

function get_print_data_pad($data,$data_max)
{

    $data_display = ""; //Specifies the available display length for passed data
    $print_data_pad = ""; //Specifies the padding needed in front of the data
    $data_length = strlen($data); //Gives the length of the data
    $data_display = ($data_max - $data_length);	
    if ($data_display >= '9'){
    for ($i=0; $i < $data_display; $i++){
         $print_data_pad .= "&nbsp;&nbsp;"; 
    }	
    #$print_data_pad .= "PPPPPPPP";
    }else {
    for ($i=0; $i < $data_display; $i++){
         $print_data_pad .= "&nbsp;"; 
    }
    }
    return $print_data_pad;
}

?>
