<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
/*******************************************************************************/
/*Script name : db_Connect.php                                                 */
/*              PDO- PHP connection file                                       */
/* June 2020  : Created the script - PN                                        */
/*******************************************************************************/

   require_once 'pdo_config.php';

   $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
   $options = [
    PDO::ATTR_EMULATE_PREPARES   => false, // turn off emulation mode for "real" prepared statements
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, //turn on errors in the form of exceptions
   # PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, //make the default fetch be an associative array
   ];
   
   try {
    $pdo = new PDO($dsn, $username, $password, $options);
    } catch (PDOException $pe) {
    die("Could not connect to the database $dbname :" . $pe->getMessage());
    } 

?>
