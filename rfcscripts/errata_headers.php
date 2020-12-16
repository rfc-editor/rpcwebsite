<?php
  /* Copyright The IETF Trust 2020 All Rights Reserved */
  /* $Id: errata_headers.php,v 1.1.1.1 2016/02/08 21:03:50 priyanka Exp $ */

// Set dev_mode
include_once("ams_util_lib.php");
/*##TODO 
include_once("ams_util_lib.php");

$lsi  = strrpos($_SERVER['PHP_SELF'], "/");
$sub  = substr($_SERVER['PHP_SELF'], 0,$lsi);
$base  = "http://";
if (isset($_SERVER['HTTPS'])) {
     $base  = "http://";
}     
$base .= $_SERVER['HTTP_HOST'];
$base .= $sub;
*/##TODO

/*
 * Function to output the standard header for a RFC Editor page.
 * $title: Title for page
 * $header_graphic: Text to add to src attribute to select a graphic file,
 */
function page_header($title="RFC Errata",
                     $header_graphic="rfcerrata", 
                     $script_extra="NONE",
                     $base_extra=null,
                     $style_sheet="style/rfc-editor.css",
                     $onload=null) {
/*     global $base;
     switch($base_extra) {
     case null:
          $anchor = $base;
          break;
     default:
          $anchor = $base . '/' . $base_extra;
          break;
     }
     print <<<END
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <title>$title</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

END;
    // if this page has a special script, load it.
    if ( strcmp($script_extra,"NONE") != 0 ) {
      print "\n<script src=\"$script_extra\" type=\"text/javascript\">\n</script>\n";
    }
    print "\n<link rel=\"stylesheet\" href=\"$style_sheet\" type=\"text/css\" />";

    // if dev_mode load custom stylesheet
    if (get_dev_mode()) {
        print "\n<link rel=\"stylesheet\" href=\"style/ams.css\" type=\"text/css\" />\n";
    }

    print "\n</head>\n";

    if ($onload != null) {
         print("<body onload=$onload>");
    } else {
         print("<body>");
    }

print<<<END

    <table border="0" cellpadding="0" cellspacing="0">
        <tbody>
          <tr>
            <td height="87" valign="top" width="677">
               <hr>
               <a href="$anchor">
                  <img src="IMAGES2/header_$header_graphic.gif" 
                       alt="errata logo graphic" class="islink">
               </a>
            </td>
          </tr>
         <tr valign="top">
         <td valign="top" width="677">
          <hr>
         </td>
        </tbody>
     </table>

END;


noscript_warning();
*/
}

/*
 * Wrapper around page_header to supply an alternate CSS file for the
 * errata edit subsystem.
 */
function errata_edit_header($title="RFC Errata Verification",
                            $header_graphic="rfcerrata", 
                            $script_extra="NONE",
                            $base_extra=null,
                            $onload=null) {

     /*page_header($title,
                 $header_graphic,
                 $script_extra,
                 $base_extra,
                 "style/edit-errata.css",
                 $onload);
     */

     if (isset($_SESSION['verifier_name'])) {
          print("\n<p style=\"text-align: right;\">");
          print("<b>" . $_SESSION['verifier_name'] . "</b> logged in for <b>" .
                $_SESSION['ssp_name'] . "</b>.<br />");
          print("<a href=\"verify_logout.php\">Logout</a></p>\n");
     }

}

/*
 * Write out the common navigation buttons to a page.
 */
function errata_nav() {
     global $base;

     print <<<END

<table class="nav" id="RFC-table">
  <tr>
    <td height="24" width="90" class="nav"><div class="navcell"><a href="$base/" class="white">RFC-ED<br>HOME</a></div></td>
    <td height="24" width="76" class="nav"><div class="navcell"><a href="$base/news.html" class="white">NEWS</a><br>&nbsp;</div></td>
    <td height="24" width="108" class="nav"><div class="navcell"><a href="$base/rfc.html" class="white">RFC<br>DATABASE</a></div></td>
    <td height="24" width="86" class="nav"><div class="navcell"><a href="$base/rfcsearch.html" class="white">RFC<br>SEARCH</a></div></td>
    <td height="24" width="90" class="nav"><div class="navcell"><a href="$base/errata.php" class="white">RFC<br>ERRATA</a></div></td>
    <td height="24" width="90" class="nav"><div class="navcell"><a href="$base/idsearch.html" class="white">I-D<br>SEARCH</a></div></td>
    <td height="24" width="91" class="nav"><div class="navcell"><a href="http://www.ietf.org/" class="white">IETF<br>HOME</a></div></td>
  </tr>
</table>
<table width="677">
  <tr>
    <td><hr></td>
  </tr>
</table>

END;
}
/*
 * Write out who's logged in if that is in the SESSION object.
 */
function  errata_edit_footer() {
   /* if (isset($_SESSION['verifier_name'])) {
          print("<p><b>" . $_SESSION['verifier_name'] . "</b> logged in for <b>" .
                $_SESSION['ssp_name'] . "</b>.</p>\n");
     }
  */  
   page_footer();
}
/*
 * Write out the closing tags for an HTML page.
 */
function page_footer() {
?>
   </div>
  </div>
</div>
<?php
    print "<br>";
    print "<br>";
get_footer();


}

function noscript_warning() {
     print<<<END

        <noscript>
           <p class="warning">
These pages are designed to work with JavaScript enabled. Please
enable JavaScript for this site.
           </p>
        </noscript>

END;
}

/*
 * This function checks to see if we're running on HTTPS, if so, redirect
 * to the HTTP side.
 */
function http_redirect() {
     if (isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == "on") {
          $redirect="http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
          
          header("Location: $redirect");
          exit;
     }

}
?>
