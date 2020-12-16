<?php
  /* Copyright The IETF Trust 2020 All Rights Reserved */
  /* $Id:  */
include_once("ams_util_lib.php");

$dev_mode = get_dev_mode();

$debug_header = false;

if ($debug_header === true) {
    print('<h2>dev_mode</h2><p><pre>');
    print('\$dev_mode=\n'); var_dump(get_dev_mode());
    print('</pre></p>');
}

/*
 * Write a standard header for the display page.
 */
function auth48_page_header($title) {
     global $dev_mode;

     print<<<END
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
  "http://www.w3.org/TR/html4/loose.dtd">
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859">
    <link rel="stylesheet" href="/style/rfc-editor.css" type="text/css" />
END;

    // if dev_mode load custom stylesheet
    if ($dev_mode) {
	//print("<link rel=\"styesheet\" href=\"style/ams.css\" type=\"text/css\"/>\n");
	print<<<END

    <link rel="stylesheet" href="style/ams.css" type="text/css"/>

END;
    }
    print<<<END
    <title>$title</title>
</head>
<body>
    <table border="0" cellpadding="0" cellspacing="0">
        <tbody>
          <tr>
            <td height="87" valign="top" width="677">
               <hr>
               <a href="/queue2.html">
                  <img src="/IMAGES2/header_queue.gif"
                       alt="queue logo graphic" class="islink">
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
}

/*
 * Close the page.
 */
function auth48_page_footer() {
     print<<<END

  <hr />
  <p>
   Back to the <abbr>RFC</abbr> Editor <a href="/queue2.html">queue page.</a>
  </p>
</body>
</html>

END;
}

?>
