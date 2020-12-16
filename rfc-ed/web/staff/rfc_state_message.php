<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
/* $Id: rfc_state_message.php,v 2.2 2020/11/11 01:03:17 priyanka Exp $ */
/* November 2020 : Modified the script to use PDO prepared statements - PN            */
include('db_connect.php');
include('rfc_state_lib.php');

$submit = $_POST['submit'];

switch ($submit) {
case "GO AHEAD, SEND IT":
     if (send_rfc_state_msg()) {
          proceed_to_index();
     }
     break;
case "NO MESSAGE":
default:
     proceed_to_index();       // i.e. Do Nothing!
     break;
}

function proceed_to_index() {
     print <<<END
     <html>
       <head>
          <script type="text/javascript">
             window.location.replace("list_drafts.php");
          </script>
       </head>
       <body>
       </body>
     </html>
END;
}
?>
