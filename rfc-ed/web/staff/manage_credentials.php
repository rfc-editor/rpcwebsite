<?php
   include_once("db_connect.php");

   $username= $_POST['login_name'];
   $password = $_POST['password'];

   #print "User name is $username";
   #print "Password is $password";

   /* Create the new password hash. */
   $hash = password_hash($password, PASSWORD_DEFAULT);


  #print "The has is $hash";
   /* Update query template. */
   try {
       #$query = 'UPDATE test_verifiers SET password = :passwd WHERE login_name = :username';
       $query = 'UPDATE verifiers SET password = :passwd WHERE login_name = :username';

       /* Values array for PDO. */
       $stmt = $pdo->prepare($query);
       $stmt->bindParam('passwd',$hash);
       $stmt->bindParam('username',$username);

       /* Execute the query. */
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();
     } catch (PDOException $pe){
       error_log("Error processing : Password modification", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }
     
     if ($num_of_rows > 0) {
         // too many or too few rows!
         print "Status : Password is updated sucessfully for $username";
     }else {
         print "Status : Password not updated";
     }







?>
