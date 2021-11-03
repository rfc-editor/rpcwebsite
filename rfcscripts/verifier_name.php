<?php
  /* Copyright The IETF Trust 2020 All Rights Reserved */
  /* $Id: verifier_name.php,v 1.3 2021/06/17 00:47:33 priyanka Exp $ */
  /* June 2021 : Modified the script for server upgrade - PN                            */
session_start();
include("errata_headers.php");
include("errata_authen_lib.php");

//$_SESSION['loginIP'] = "CANT_MATCH"; // Test session hijack code
session_authenticate();

include("errata_lib.php");

// Include WordPress
define('WP_USE_THEMES', false);
//require('/a/wordpress/wp-blog-header.php');
require_once ('/a/wordpress/wp-load.php');

/*Stripping the Wordpress additional slashes from GLOBAL vaiables*/
$_POST = array_map( 'stripslashes_deep', $_POST);
$_GET = array_map( 'stripslashes_deep', $_GET);
$_COOKIE    = array_map( 'stripslashes_deep', $_COOKIE );
$_REQUEST   = array_map( 'stripslashes_deep', $_REQUEST );

$privileged_user = is_privileged_user();

function dispatch_to_edit($privileged_user)
{
     if ($privileged_user) {
          //header("Location: http://{$_SERVER['SERVER_NAME']}/errata_edit.php");     
          ams_redirect("errata_edit.php");
     } else {
          //header("Location: http://{$_SERVER['SERVER_NAME']}/verify_errata.php");
          ams_redirect("verify_errata.php");
     }
}


if (isset($_SESSION['verifier_name'])) { dispatch_to_edit($privileged_user); exit; }

if (isset($_POST['verifier_name']) && isset($_POST['verifier_email'])) {
     $_SESSION['verifier_name'] = substr($_POST['verifier_name'],0, MAX_NAME);
     $_SESSION['verifier_email'] = substr($_POST['verifier_email'],0, MAX_EMAIL);
     dispatch_to_edit($privileged_user);
     exit;
}
// Some login names are also email addresses. Do this simple check and if
// there's an "@" in the string, set it up default for the form below.
$default_verifier_email = (strpos($_SESSION['login_name'],"@")) === FALSE ?
                           null : 
                           $_SESSION['login_name'];

/*errata_edit_header("Provide Name and Email",
                   "rfcerrata",
                   "scripts/verify_name_email.js",
                   "errata_edit.php");
*/


add_action('wp_head','add_verifier_name_script');

//Add java script function
function add_verifier_name_script(){

print "<script src=\"scripts/verify_name_email.js\" type=\"text/javascript\">\n</script>";

}

//Add title specifying the dynamic page 
add_filter( 'wp_title', 'wp_title_verify_name', 10, 3 );

function wp_title_verify_name( $title, $sep, $seplocation ) {
    return 'Provide Name and Email &raquo; RFC Editor';
}

//Add body class constant to avoid error404 body class for Wordpress outside page

function add_body_class_function($classes){

return array ('verifier_name');
}

add_action('body_class','add_body_class_function');

get_header();
get_sidebar('left');

     print<<<END
<div id="content" class="narrowcolumn">
     <h1 class="post_title">Errata Verification Login Page</h1>

     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">
END;




print("<p>You are logged in as <b>" . session_ssp_name() . "</b>.</p>\n");
print<<<END
<p>
Please provide your name and email address.  
This information will be used for any errata reports that you verify 
or reject during this session.  For example, your name will be used on the posted report
("Verifier Name:") and in the notification regarding rejected reports.
</p>

<form name="name_email" id="name_verifier" method="POST" action="verifier_name.php" onsubmit="return validateNameEmailForm(this)">
  <table>
    <tr>
     <td>Name:</td>
     <td><input type="text" size="40" maxlength="80" name="verifier_name" /></td>
    </tr>
    <tr>
     <td>Email:</td>
     <td><input type="email" size="40" maxlength="80" name="verifier_email" value="{$default_verifier_email}" /></td>
    </tr>
    <tr>
     <td>&nbsp;</td>
     <td><input type="submit" value="Continue" /></td>
    </tr>
  </table>
</form>


<script type="text/javascript">
<!--
          document.forms.name_verifier.verifier_name.focus();
//-->
</script>

END;

errata_edit_footer()
?>
