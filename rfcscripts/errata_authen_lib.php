<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
/*
 * Authentication routines for the Errata Verification & Editing pages.
 * $Id: errata_authen_lib.php,v 1.4 2020/11/11 01:03:39 priyanka Exp $
 */
/* June 2017 : Increased the length of MAX_LOGIN_NAME to 35 from 30  - PN */
/* November 2020 : Modified the script to use PDO prepared statements - PN            */
include_once("db_connect.php");
include_once("ams_util_lib.php");

define("MAX_LOGIN_NAME",35);    /* Cutoffs for login form fields */
define("MAX_PASSWD_LEN",10);

/** These constants must match ssp_id in stream_specific_parties table **/
define("RFCED_SSP_ID", 2);
define("DEVTEST_SSP_ID", 5);
/** **/

/*
 * Looks up the document stream for an RFC and compares to the stream of the
 * current logged-in user.
 * Returns true if access should be granted, else false. In both cases, sets
 * session variables for what the document values are.
 */
function access_is_allowed($rfc_num, $ssp_id) {
     global $pdo;
     $is_allowed = false;
     $doc_id = sprintf('%s%04d','RFC',$rfc_num);

     try {
         $query = 
             "SELECT w.ssp_id, i.`doc-id`, i.source, s.stream_name, s.ssp_name
             FROM `index` i, working_group w, stream_specific_parties s
             WHERE i.source=w.wg_name AND w.ssp_id=s.ssp_id AND `doc-id`= :doc_id";

	 $stmt = $pdo->prepare($query);
	 $stmt->bindParam('doc_id',$doc_id);
	 $stmt->execute();
	 $num_of_rows = $stmt->rowCount();

     } catch (PDOException $pe){
         error_log("Error processing : access_is_allowed", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }
    
     switch ($num_of_rows) {
          case 0:
               $_SESSION['message'] = "<p>No records returned for RFC$rfc_num. No RFC$rfc_num found in the database.</p>";
               break;
          case 1:
               $row = $stmt->fetch(PDO::FETCH_ASSOC);
               $_SESSION['doc-id'] = $row['doc-id'];
               $_SESSION['document_stream'] = $row['stream_name'];
               $_SESSION['document_ssp'] = $row['ssp_name'];
               $is_allowed = ($row['ssp_id'] == $ssp_id);
               break;
          default:
               // too many or too few rows! (Did rfc exist?)
               error_log("Query '" . $query . "' did not return one row only!");
               $_SESSION['message'] = "<p class=\"error\">Error accessing database. Please try again later.</p>\n";
               break;
     }
     return $is_allowed;
}

/*
 * Function to create paragraph explaining why verification can not continue. Should only
 * be called after access_is_allowed, which sets the SESSION variables.
 */
function access_denied() {
     print("<h1 class=\"maintitle\">Access Denied</h1>\n");
     if (array_key_exists('message',$_SESSION) && isset($_SESSION['message'])) {
          print($_SESSION['message']);
          unset($_SESSION['message']);
     } else {
          print<<<END
<p>
{$_SESSION['doc-id']} is not a product of your stream. You are not the verifier of its errata.
If you have information about this errata, please contact the <b>{$_SESSION['document_ssp']}</b>.
</p>

END;
     }
}

/*
 * Lookup the password and associated information for the user.
 * Returns:
 *         A string if SQL error
 *         Boolean false if no match or too many matches
 *         Associative array of result on success
 */
function authenticate_user($username, $password)
{
     global $pdo;
     // Test the username and password parameters
     if (!isset($username) || !isset($password))
       return false;               // bail now!

     // Guard against SQL injection
     $login_name = $username;
     // Create a digest of the password collected from
     // the challenge
     $password_digest = md5(trim($password) . trim($username));

     // Formulate the SQL find the user

     try {
         $query =
             "SELECT v.verifier_id, v.login_name, s.ssp_name,
                 s.ssp_id
             FROM verifiers v, stream_specific_parties s 
             WHERE v.ssp_id = s.ssp_id 
                 AND v.login_name=:login_name
                 AND v.password=:password_digest";
	 $stmt = $pdo->prepare($query);
	 $stmt->bindParam('login_name',$login_name);
	 $stmt->bindParam('password_digest',$password_digest);
	 $stmt->execute();
	 $num_of_rows = $stmt->rowCount();
     } catch (PDOException $pe){
         error_log("Error processing : authenticate_user", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }

     $ret_value = null;


     if ($num_of_rows != 1) {
         // too many or too few rows!
         $ret_value = false;
     }else {
         $ret_value = $stmt->fetch(PDO::FETCH_ASSOC);
     }

     return $ret_value;
}




/*
 * Connects to a session and checks that the user has authenticated.
 */
function session_authenticate()
{
     /*if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on") {
          $redirect="https://" . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];
          header("Location: $redirect");
          exit;
     }*/
  // Check if the user hasn't logged in
     if (!isset($_SESSION["login_name"])) {
          // The request does not identify a session
          $_SESSION["message"] = 
               "<p class=\"warning\">You must login before using this application.</p>";

          //header("Location: http://{$_SERVER['SERVER_NAME']}/verify_login.php");
          ams_redirect("verify_login.php");
          exit;
     }
  // Check if the request is from a different IP address to previously
  //   if (!isset($_SESSION["loginIP"]) || 
  //       ($_SESSION["loginIP"] != $_SERVER["REMOTE_ADDR"])) {
          // The request did not originate from the machine
          // that was used to create the session.
          // THIS IS POSSIBLY A SESSION HIJACK ATTEMPT

  //        $_SESSION["message"] = "<p class=\"error\">You are not authorized to access the URL 
  //                          {$_SERVER["REQUEST_URI"]} from the address 
  //                          {$_SERVER["REMOTE_ADDR"]}</p>";

          //header("Location: http://{$_SERVER['SERVER_NAME']}/verify_logout.php");
   //       ams_redirect("verify_logout.php");
   //       exit;
   //  }
  return true;
}

function session_ssp_name() {
     if (isset($_SESSION['ssp_name'])) return $_SESSION['ssp_name'];
     return "UNKNOWN";
}

function session_ssp_id() {
     if (isset($_SESSION['ssp_id'])) return $_SESSION['ssp_id'];
     return 0;
}

/*
 * Lookup the ssp_id for the document source. Return true if the same as the
 * ssp_id for the session, else false.
 */
function is_same_ssp_id($rfc_num, $ssp_id) {
     error_log("is_same_ssp_id: " . $rfc_num . ", " . $ssp_id . ".");
     global $pdo;

     $doc_id = sprintf('%s%04d','RFC',$rfc_num);
     $is_same = false;
     
     try {
         $query =
             "SELECT ssp_id 
             FROM `index`, working_group 
             WHERE source=wg_name AND `doc-id`=:doc_id";
	     $stmt = $pdo->prepare($query);
	     $stmt->bindParam('doc_id',$doc_id);
	     $stmt->execute();
	     $num_of_rows = $stmt->rowCount();
     } catch (PDOException $pe){
         error_log("Error processing : is_same_ssp_id", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }


     if ($num_of_rows != 1){
           // too many or too few rows! (Did rfc exist?)
         error_log("Query '" . $query . "' did not return one row only!");
     } else {
         $row = $stmt->fetch(PDO::FETCH_NUM);
         $rfc_ssp_id = $row[0];
         $is_same = ($rfc_ssp_id == $ssp_id);
     } 
     return $is_same;
}
/*
 * Compare the ssp_id of the session with the ssp_ids of privileged users.
 * Return true if match, else false.
 */
function is_privileged_user() {
     $is_privileged = false;
     if (isset($_SESSION['ssp_id'])) {
          switch($_SESSION['ssp_id']) {
          case RFCED_SSP_ID:
          case DEVTEST_SSP_ID:
               $is_privileged = true;
               break;
          default:
               $is_privileged = false;
               break;
          }
     }
     return $is_privileged;
}
?>
