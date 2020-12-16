<?php
  /* Copyright The IETF Trust 2020 All Rights Reserved */
  /* $Id: ams_util_lib.php,v 1.1.1.1 2016/02/08 21:03:51 priyanka Exp $ */
  /* 
   * AMS Utility routines
   */

/*
 * Determine dev mode status by checking URI for "/dev/"
 */
function get_dev_mode() {
    return ((bool)preg_match("/^\/dev\//", $_SERVER['REQUEST_URI']));
}

function ams_redirect($url) {
    /* Redirect to a different page in the current directory that was requested */
    $host = $_SERVER['HTTP_HOST'];
    $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    if($_SERVER['SERVER_PORT'] == 443){
       header("Location: https://$host$uri/$url");
    } else {
       header("Location: http://$host$uri/$url");
    }
}

function force_https() {
    /* redirect to https if not already */
    if (intval($_SERVER['SERVER_PORT']) != 443) {
        $redirect = "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        header("Location: $redirect");
    }
}

?>
