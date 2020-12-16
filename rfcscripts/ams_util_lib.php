<?php
  /* Copyright The IETF Trust 2020 All Rights Reserved */
  /* $Id: ams_util_lib.php,v 1.1.1.1 2016/02/08 21:03:50 priyanka Exp $ */
  /* 
   * AMS Utility routines
   */

/*
 * Determine dev mode status by checking URI for "/dev/"
 */
function get_dev_mode() {
    return ((bool)preg_match("/^\/dev\//", $_SERVER['REQUEST_URI']));
    #return ((bool)preg_match("/^www2\//", $_SERVER['REQUEST_URI']));
    #return ($_SERVER['REQUEST_URI']); 
    # print ($_SERVER['HTTP_HOST']); 
    # print ($_SERVER['REQUEST_URI']); 
    #return ((bool)preg_match("/^\/rfcscripts\//", $_SERVER['REQUEST_URI']));
    /*Checking the development mode with www2 now can modify later*/
    //return ((bool)preg_match("/^www2/", $_SERVER['HTTP_HOST']));

}

function ams_redirect($url) {
    /* Redirect to a different page in the current directory that was requested */
    $host = $_SERVER['HTTP_HOST'];
    #$uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

     #$uri = '/rfcscripts';
     $uri = '';
   
    /*if($_SERVER['SERVER_PORT'] == 443){
       header("Location: https://$host$uri/$url");
    } else {
       header("Location: http://$host$uri/$url");
    }
      */
 
       header("Location: https://$host$uri/$url");

}

function force_https() {
    /* redirect to https if not already */
    if (intval($_SERVER['SERVER_PORT']) != 443) {
        $redirect = "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        header("Location: $redirect");
    }
}

?>
