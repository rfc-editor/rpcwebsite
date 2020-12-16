<?php
  /* Copyright The IETF Trust 2020 All Rights Reserved */
  /* $Id: ams_util_lib.php,v 2.5 2017/10/27 17:08:44 priyanka Exp $ */
  /* 
   * AMS Utility routines
   */

/*
 * Determine dev mode status by checking URI for "/dev/"
 */
function get_dev_mode() {
    return ((bool)preg_match("/^\/s*dev\//", $_SERVER['REQUEST_URI']));
}

/*
 * Redirect to a different page in the current directory that was requested
 */
function ams_redirect($url) {
    $host = $_SERVER['HTTP_HOST'];
    $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    header("Location: http://$host$uri/$url");
}

/*
 * A wrapper for PHP mail() which handles devmode
 */
function ams_mail($to, $subject, $body, $header) {
#    $devaddrs = "priyanka@amsl.com,ahagens@amsl.com";
    $devaddrs = "priyanka@amsl.com";

    // Add UTF-8 content type for Subject
    $subject = '=?UTF-8?B?'.base64_encode($subject).'?=';


    if (get_dev_mode()) {
        // add original headers to message body
        $body .= "\n\nDEVELOPMENT MODE -------------";
        $body .= "\nThis was the original header:";
        $body .= "\n\nTo: " . $to;
        $body .= "\n".$header;

        // strip Cc from header
        $new_header = '';
	$lines = preg_split('/\n/', $header);
	foreach ($lines as $line) {
	    if (!preg_match('/^Cc/',$line)) {
               $new_header .= $line;
            }
	}

        //Add UTF-8 content type 
        $new_header .= "\n";
        $new_header .= "Content-Type: text/plain; charset=UTF-8";
        $new_header .="\n";

        // overwite headers
        $to = $devaddrs;  
        $header =  $new_header;

        return (mail($to, $subject, $body, $header));
    } else {
       // Add UTF-8 content type
        $header .= 'Content-type: text/plain; charset=UTF-8' . "\r\n";
        return (mail($to, $subject, $body, $header));
    }
}

function get_var($var) {
    $config_file = "rfc.conf";
    $comment = "#";

    $fp = fopen($config_file, "r");
 
    while (!feof($fp)) {
        $line = trim(fgets($fp));
        if ($line && !ereg("^$comment", $line)) {
            $pieces = explode("=", $line);
            $option = trim($pieces[0]);
            $value = trim($pieces[1]);
            $config_values[$option] = $value;
        }
    }
    fclose($fp);

    return ($config_values[$var]);
}

?>
