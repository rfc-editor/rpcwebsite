<?php
 print<<<END
<html><head>
<title></title>
</head>
<body>
<h2>Verifier Management</h2>
<h3>Please enter name and desired password</h3>
<form name="test_login" method="POST" action="manage_credentials.php">
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
    <td><input type="submit" value="Modify" /></td>
  </tr>
</table>

</form>
<p>
</body>
</html>
END;
?>
