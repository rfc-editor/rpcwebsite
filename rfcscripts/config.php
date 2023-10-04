<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
/* April 2017 Updates : Added the redirect link for Errata Id and RFC number - PN*/
/* June 2019 Updates : Modified the search_query_base from http to https - PN*/
/* July 2021 Updates : Modified the errata_processing_html link - PN*/
/* Sept 2023 Updates : Added ietf_root link - PN*/


/*********************************************************/
/*For all_clusters.php/rfcmeta.php/auth48_cluster.php*/
/*********************************************************/
 $datatracker = " http://datatracker.ietf.org/doc"; /*Datatracker link*/
 $state_def = "/about/queue/#state_def/"; /*Defination of states*/
 $queue = "queue2.html"; /*Queue*/
 $cluster_info = "cluster_info.php"; /*Cluster info*/
 $cluster_def = "/about/clusters/"; /*Cluster def*/

/*********************************************************/
/*For cluster_lib.php/rfcmeta.php/auth48_cluster.php*/
/*********************************************************/
$document_root= "https://www.rfc-editor.org";
$internet_draft="internet-drafts";/* First part of URL locating draft documents*/
$rfc = "rfc"; /*URL base for RFC editor*/
$ietf_root = "https://www.ietf.org";

$all_clusters = "all_clusters.php";/*All cluster*/
/*********************************************************/
/*CSS Paths*/
/*********************************************************/
$ams_css = "/style/ams.css";/*For test environment CSS*/

/*********************************************************/
/**For auth48_status.php**/
/*********************************************************/
$filepath_constant = "/nfs/ftp/in-notes/authors/rfc";
$authors = "authors";

/*********************************************************/
/*For export_lib.php*/
/*********************************************************/
$cmd_base = "/home/rfc-ed/bin"; // production
$www_path = "/a/www/rfc/htdocs";

/*********************************************************/
/*****For rfcmeta.php******/
/*****And errata.php*******/
/*********************************************************/
$datatracker_base = "https://datatracker.ietf.org";
$ietf_base = "https://www.ietf.org";
$tools_ietf = "http://tools.ietf.org";
$bib_link_base = "https://bib.ietf.org";

/*********************************************************/
/*For current queue external                             */
/*********************************************************/
$datatracker_baselink = "https://datatracker.ietf.org/doc/";

/*********************************************************/
/*For reports Weekly/Monthly                             */
/*********************************************************/
$report_base = "/reports/";

/*********************************************************/
/*Errata search lib url constant                         */
/*********************************************************/
$eid_link_verify_base = "/verify_errata_select.php";
$eid_link_search_base = "/errata_search.php";

/*********************************************************/
/*Errata search query base path                          */
/*********************************************************/
$search_query_base = "https://" . $_SERVER['SERVER_NAME'] . "/errata/";
/*********************************************************/
/*Constants from errata.php     */
/*********************************************************/
$status_type_errata = "/errata-definitions/";
$how_to_report = "/how-to-report/";
$how_to_verify = "/how-to-verify/";
$errata_processing_html = "/about/groups/iesg/statements/processing-errata-ietf-stream/";
$materials = "/materials";
$draft_errata_process= "/draft-rfc-editor-errata-process-02.txt";
/*********************************************************/
/*Constants from errata_lib.php     */
/*********************************************************/
$source_of_rfc = "/source/";
/*********************************************************/
/*Errata Template Directory from errata_mail_lib.php     */
/*********************************************************/
$ack_template = "/home/rfc-ed/Templates/Errata-Msgs/ack-message.txt";
//$ack_template = "/home/rfc-ed/Templates/Errata-Msgs/cause-error.txt";
$rej_template = "/home/rfc-ed/Templates/Errata-Msgs/rejected-message.txt";
$ver_template = "/home/rfc-ed/Templates/Errata-Msgs/verified-message.txt";
$defer_template = "/home/rfc-ed/Templates/Errata-Msgs/held-message.txt";

/*********************************************************/
/*Constants from errata_edit.php     */
/*********************************************************/
$draft_rfc_editor_errata_process = "/draft-rfc-editor-errata-process-02.txt";
/*********************************************************/
/*For rfcmeta.php*/
/*********************************************************/
$file_base_images = '/rfcscripts/images';
$inline_errata_base = '/rfc/inline-errata/';
?>
