<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
/*
 * Core Library Routines
 * $Id: core_lib.php,v 1.3 2020/11/11 01:03:39 priyanka Exp $
 * November 2020 : Modified the script to use PDO prepared statements - PN            
 */
include_once("db_connect.php");

/*
 * Retrieves the meta data from the database.
 */
function get_rfcmeta_data($rfcnum) {
     global $debug_rmeta,$pdo;

     $padded_num = sprintf("%04d", $rfcnum);
     $doc_id = 'RFC'.$padded_num;
     $state_id = 14;
     try {

     // This query retreives more than will be used, at least initially.
         $sql = "
           SELECT i.source, w.wg_acronym, `doc-id`, 
                CONCAT( st.state_name,CASE WHEN i.iana_flag = \"1\"THEN \"*A\"ELSE \"\"END ,CASE WHEN i.ref_flag = \"1\"THEN \"*R\"ELSE \"\"END,
	        CASE WHEN i.generation_number = \"1\" THEN \"(1G)\" WHEN i.generation_number = \"2\" THEN \"(2G)\" WHEN i.generation_number = \"3\" THEN \"(3G)\" WHEN i.generation_number = \"0\" THEN \"\" END) as state,
                status, `pub-status`,
                type, `pub-date`, title, `char-count`, `page-count`,
                obsoletes, `obsoleted-by`, updates, `updated-by`, `see-also`,`internal_key`,
                `see-also-title`, authors, abstract, format, a.area_acronym,
                s.stream_name, s.ssp_webpage, w.`ssp_id`, i.DOI, w.`wg_email`
           FROM `index` i, working_group w, area a, `states` st,
                stream_specific_parties s
           WHERE i.source = w.wg_name AND
                 w.area_name = a.area_name AND
                 w.ssp_id = s.ssp_id AND
                 i.state_id = st.state_id AND
                 i.state_id =:state_id AND `doc-id`=:doc_id";

         $stmt = $pdo->prepare($sql);
         $stmt->bindParam('state_id',$state_id);
         $stmt->bindParam('doc_id',$doc_id);
         $stmt->execute();
         $num_of_rows = $stmt->rowCount();
     } catch (PDOException $pe){
         error_log("Error processing : get_rfcmeta_data", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }
    
     #$pdo = null;
     if ($num_of_rows > 0){
         $row = $stmt->fetch(PDO::FETCH_ASSOC);
       return $row;
     } else {
         return array();
     }
}

?>
