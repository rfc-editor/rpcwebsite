<?php
  /* Copyright The IETF Trust 2020 All Rights Reserved */
  /* June 2017 : Increased the length of login_name to 35 from 30  - PN */
  /* $Id: verify_login.php,v 1.2 2017/06/06 17:59:11 priyanka Exp $ */
session_start();

// Include WordPress
define('WP_USE_THEMES', false);
//require('/a/wordpress/wp-blog-header.php');
require_once ('/a/wordpress/wp-load.php');

// Coming in on HTTP instead of HTTPS?
include_once("ams_util_lib.php");

/*
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on") {
     header("Location: https://" . $_SERVER['SERVER_NAME'] . "/verify_login.php");
     exit;
}
*/
// Force HTTPS
//force_https();

// Already been here?
if (isset($_SESSION['login_name'])) { 
     //header("Location: http://{$_SERVER['SERVER_NAME']}/verifier_name.php");
     ams_redirect("verifier_name.php");
     exit;
}
include("errata_headers.php");
include("errata_authen_lib.php");

$debug_vlin = false;

$user_data = null;

/*Stripping the Wordpress additional slashes from GLOBAL vaiables*/
$_POST      = array_map( 'stripslashes_deep', $_POST);
$_GET       = array_map( 'stripslashes_deep', $_GET);
$_COOKIE    = array_map( 'stripslashes_deep', $_COOKIE );
$_REQUEST   = array_map( 'stripslashes_deep', $_REQUEST );



if (!isset($_POST['login_name'])) {
// We haven't logged in yet so create a page with the initial login form.
// Include WordPress
/*      errata_edit_header("Errata Verification Login", 
                        "rfcerrata", 
                        "scripts/verify_login_form.js",
                        "verify_login.php");
*/

add_action('wp_head','add_verify_login_script');

//Add java script function
function add_verify_login_script(){

print "<script src=\"scripts/verify_login_form.js\" type=\"text/javascript\">\n</script>";

}

//Add title specifying the dynamic page 
add_filter( 'wp_title', 'wp_title_verify_login', 10, 3 );

function wp_title_verify_login( $title, $sep, $seplocation ) {
    return 'Errata Verification Login &raquo; RFC Editor';
}

//Add body class constant to avoid error404 body class for Wordpress outside page

function add_body_class_function($classes){

return array ('verify_login');
}

add_action('body_class','add_body_class_function');


get_header();
get_sidebar(left);



     if ($debug_vlin === true) {
          print("<!--\n");
          print("\$_POST<br /><pre>");
          print_r($_POST);
          print("</pre>");
          print("\$_GET<br /><pre>");
          print_r($_GET);
          print("</pre>");
          print("\$_SESSION<br /><pre>");
          print_r($_SESSION);
          print("</pre>");
          print("\n-->\n");
     }

   /*  if (isset($_SESSION['message'])) {
          print($_SESSION['message']);
          unset($_SESSION['message']);
     }
*/
     print<<<END
<div id="content" class="narrowcolumn">
     <h1 class="post_title">Errata Verification Login Page</h1>

     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">
END;

 if (isset($_SESSION['message'])) {
          print($_SESSION['message']);
          unset($_SESSION['message']);
     }

     print<<<END
<p>Please log in to process errata reports.</p>
<form name="verify_login" method="POST" action="verify_login.php" 
      onsubmit="return validateLoginForm(document.verify_login)">
<table>
  <tr>
    <td>Username:</td>
    <td><input type="text" size="20" maxlength="35" name="login_name" /></td>
  </tr>
  <tr>
    <td>Password:</td>
    <td><input type="password" size="20" maxlength="10" name="password" /></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td><input type="submit" value="Log In" /></td>
  </tr>
</table>

</form>
<p>
This page is for use by specified members of the IAB, IESG, 
IRSG, RFC Editorial Board, and the RFC Editor. 
Please contact <a href="mailto:rfc-editor@rfc-editor.org">rfc-editor@rfc-editor.org</a> with questions.
</p>

<script type="text/javascript">
<!--
          document.forms.verify_login.login_name.focus();
//-->
</script>

END;
page_footer();
} else {
// The form was filled out and submitted. Validate the user input and continue
// as appropriate.
     $user_data = authenticate_user(substr($_POST['login_name'],0,MAX_LOGIN_NAME),
                                    substr($_POST['password'],0,MAX_PASSWD_LEN));
     if (is_string($user_data)) {
          error_log($user_data);
// TODO This should go to a general error page and not the same form offering login
          $_SESSION['message'] = '<p class="warning">The application has encountered an error. Please try again later.</p>';
          //header("Location: http://{$_SERVER['SERVER_NAME']}/verify_login.php");
          ams_redirect("verify_login.php");
          exit;
     }
     if (is_bool($user_data)) {
          $_SESSION['message'] = "<p class=\"error\">Invalid login</p>\n";
          //header("Location: http://{$_SERVER['SERVER_NAME']}/verify_login.php");
          ams_redirect("verify_login.php");
          exit;
     }
     // $user_data is associative array of matched row
     $_SESSION['login_name'] = $user_data['login_name'];
     $_SESSION['verifier_id'] = $user_data['verifier_id'];
     $_SESSION['ssp_name'] = $user_data['ssp_name'];
     $_SESSION['ssp_id'] = $user_data['ssp_id'];
     $_SESSION["loginIP"] = $_SERVER["REMOTE_ADDR"];
     if ($debug_vlin === false) {
          //header("Location: http://{$_SERVER['SERVER_NAME']}/verifier_name.php");
          ams_redirect("verifier_name.php");
          exit;
     } else {
       print("\$_SESSION<br /><pre>");
       print_r($_SESSION);
       print("</pre>");
   }
}
?>
