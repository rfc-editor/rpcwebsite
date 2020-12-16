<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
# $Id: auth48_setup.php,v 2.2 2020/11/11 01:03:17 priyanka Exp $
# Onetime-use-only script to load authors for initial AUTH48 automatated
# processing This file is not included in the Makefile because it isn't 
# needed for the web application.
/* November 2020 : Modified the script to use PDO prepared statements - PN                                 */

include("auth48_lib.php");
global $pdo;

$select = "SELECT `doc-id`, trim(authors) as authors, trim(source) as source 
           FROM `index` WHERE `state_id` = :state_id";
$indep_note = "
This document is in the Independent stream, which is currently on
hold, awaiting an update of the Trust Legal Provisions (TLP).
draft-braden-independent-submission was written to address this issue,
and will be published as an RFC. The IETF Trust has posted proposed changes
to the TLP to resolve the issue; please see
http://trustee.ietf.org/announcements/ann1.html.

We apologize that your document has gotten stuck in the queue
during the transition.";

$irtf_note = "
This document is in the IRTF stream, which is currently on hold,
awaiting an update of the Trust Legal Provisions (TLP). draft-irtf-rfcs
addresses this issue, and will be published as an RFC. The IETF Trust has
posted proposed changes to the TLP to resolve the issue; please see
http://trustee.ietf.org/announcements/ann1.html.

We apologize that your document has gotten stuck in the queue
during the transition. ";

try {
   $stmt = $pdo->prepare($select);
   $stmt->bindValue('state_id',2);
   $stmt->execute();
   $num_of_rows = $stmt->rowCount();
} catch (PDOException $pe){
       error_log("Error processing : auth48_setup", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
}

if ($num_of_rows > 0) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
       $a48_id = initialize_auth48($row['doc-id'],$row['authors']);
           if ($a48_id === FALSE) {
               print("??? Error at {$row['doc-id']}\nBailing!\n");
               break;
           } else {
               switch ($row['source']) {
                   case 'INDEPENDENT':
                       update_notes($a48_id,$indep_note);
                       break;
                   case 'IRTF':
                       update_notes($a48_id,$irtf_note);
                       break;
                   default:
                    # do nothing
                       break;
               }
          }
     }
} 


?>
