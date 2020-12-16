<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
# Global db variables
$db_connected = 0;
$db_host = "localhost";
#error_log("db: " . $_SERVER['HTTP_HOST'] . " -> " . $web_host . " using " . $db_host . " as database server");



$db_name = "rfcdev";
$db_user = "rfcdev";
$db_passwd = "rfcrfc";




# Open a connection to the db, leaving the connection in dbh, unless such a
# connection already exists.
function open_db() {
  global $db_connected, $db_host, $db_name, $db_user, $db_passwd, $db_handle;
    
  if ( !$db_connected ) {
       $db_handle = mysqli_connect($db_host, $db_user, $db_passwd);
       if ( !is_bool($db_handle) ) {
	    $db_connected++;
	    if ( !mysqli_select_db($db_handle,$db_name) ) {
                 return "Error: Cannot select $db_name: " . mysqli_error($db_handle);
	    } else {
                 return $db_handle;
            }
	} else {
            $remote_host = gethostbyaddr($_SERVER['REMOTE_ADDR']);
            error_log("db: " . $_SERVER['REMOTE_ADDR'] . " ($remote_host) using " .
                      $_SERVER['HTTP_HOST'] . " -> " . $web_host . 
                      " resulting in " . $db_host . " as database server");
	    return "Error: Cannot connect to $db_host as $db_user: " . 
		mysqli_error($db_handle);
        }
    } else {
         $db_connected++; // Count new user of connection.
    }
    return $db_handle;
}

# If as many closes as opens have been called, really close up the DB
function close_db() {
  global $db_connected, $db_handle;
  if ( ! --$db_connected ) mysqli_close($db_handle);
}

#mysqli_result is used to write less code when database result is returning only a singlw row

function mysqli_result($res,$row=0,$col=0){ 
    $numrows = mysqli_num_rows($res); 
    if ($numrows && $row <= ($numrows-1) && $row >=0){
        mysqli_data_seek($res,$row);
        $resrow = (is_numeric($col)) ? mysqli_fetch_row($res) : mysqli_fetch_assoc($res);
        if (isset($resrow[$col])){
            return $resrow[$col];
        }
    }
    return false;
}

?>




