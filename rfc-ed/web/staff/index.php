<?php
  /* Copyright The IETF Trust 2020 All Rights Reserved */
  /* $Id: index.php,v 2.1 2010/03/18 20:02:31 rcross Exp $ */
  // make the default page the most used page.
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
/*include('header.php');

rfc_header("Main");
rfc_footer();*/
?>
