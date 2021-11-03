<?php
/**************************************************************************************/
/* Copyright The IETF Trust 2020 All Rights Reserved                                  */
/*Script Name : auth48_cluster.php                                                    */
/*              Script shows the AUTH48 cluster specific data for a given Cluster     */
/*              request. The request is serveed from queue2.html AUTH48 request.      */                                                                   
/* November 2017 : Modified the script to handle the cluster Auth48 page for a PUB    */
/*                  state  - PN                                                       */
/* March 2020 : Modified the script to change the order of cluster detail based on    */
/*              ascending RFC numbers - PN                                            */
/* Apr 2020 : Added a condition to supress display of PUB documents only for C238 -PN */
/* November 2020 : Modified the script to use PDO prepared statements - PN            */
/* June 2021 : Modified the script for server upgrade - PN                            */
/**************************************************************************************/
include("config.php");
include('cluster_lib.php');
include('auth48_cluster_lib.php');


// Include WordPress
define('WP_USE_THEMES', false);
//require('/a/wordpress/wp-blog-header.php');
require_once ('/a/wordpress/wp-load.php');

$debug_auth48_cl = false;
if ($debug_auth48_cl === true) {
     if (isset($_POST)) {
          print("\$_POST<br /><pre>");
          print_r($_POST);
          print("</pre>");
     }
     if (isset($_GET)) {
          print("\$_GET<br /><pre>");
          print_r($_GET);
          print("</pre>");
     }
}

function cluster_info_notes() {
    
     print<<<END
<p>For more information about clusters, see the <a href="cluster_def.html">cluster definition page</a>.</p>
END;
}
function cluster_return_menu() {
     global $all_clusters,$state_def,$queue;

     print '<hr>Show all active <a href="' . htmlspecialchars($all_clusters) . '>document clusters.</a>';
     print '<p>Go to <a href="' . htmlspecialchars($state_def) . '>definitions of state names</a>.</p>';
     print '<p>Back to the RFC Editor <a href="' . htmlspecialchars($queue) . '">queue page.</a></p>';
}

function auth48_cluster_instruction_display($in_id){

     print<<<END
<h3>Instructions to author(s):</h3>
<p>Please see the AUTH48 notification email from the RFC Editor that contains links to the edited files and other information. To send your approval of or changes for a specific document, please Reply All to the message for the given RFC-to-be in question.</p>
<p>If cluster-wide questions were received, please Reply All to that email so each of the relevant parties may partake in the decision process.</p>
<p>See information below for the status of documents within the cluster that are in AUTH48.<p>
END;
     print "<p>When all approvals for a given document have been received, publication will continue once its normative references have completed the AUTH48 process as well. Upon publication of the cluster, this AUTH48 status page will no longer be available.</p>";



}

function auth48_cluster_detail($in_id){

    print "<h3>Cluster Details</h3>";


}

$cid=null;
if (isset($_GET) && array_key_exists('cid', $_GET)) {
     $cid  = substr($_GET['cid'],0,MAX_CID);
} elseif (isset($_POST) && array_key_exists('cid',$_POST)) {
     $cid  = substr($_POSTT['cid'],0,MAX_CID);
}

if ($debug_auth48_cl === true) {
     print("\n<pre>\n\$cid=");
     print_r($cid);
     print("\n</pre>");
}

if ($cid == null) {
//Add title specifying the dynamic page
     add_filter( 'wp_title', 'wp_title_auth48_cluster_null', 10, 3 );
     
      function wp_title_auth48_cluster_null( $title, $sep, $seplocation ) {
      return 'No Cluster Identifier &raquo; RFC Editor';
     }

//Add body class constant to avoid error404 body class for Wordpress outside page

function add_body_class_function($classes){

return array ('auth48cluster');
}

add_action('body_class','add_body_class_function');


     get_header();
     get_sidebar('left');
?>
     <div id="content" class="narrowcolumn">
     <h1 class="post_title">No Cluster Identifier</h1>

     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">
<?php
     print("<p>No cluster to lookup for AUTH48 status page</p>");


} else {

     global $document_root;
     global $internet_draft;
     global $pdo;   # keep the connection open between subroutine calls
//Add title specifying the dynamic page
     add_filter( 'wp_title', 'wp_title_auth48_cluster', 10, 3 );
     
      function wp_title_auth48_cluster( $title, $sep, $seplocation ) {
          global $cid;
          return 'AUTH48 status page for '. htmlspecialchars($cid).' &raquo; RFC Editor ';
     }

//Add body class constant to avoid error404 body class for Wordpress outside page

function add_body_class_function($classes){

return array ('auth48cluster');
}

add_action('body_class','add_body_class_function');

     get_header();
     get_sidebar('left');
 
?>
     <div id="content" class="narrowcolumn">
     <h1 class="post_title">AUTH48 status page for <?php print htmlspecialchars($cid); ?></h1>

     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">
<?php


     auth48_cluster_detail($cid);
     # get the drafts in the queue
     $draft_list = get_cluster_a48status($pdo,$cid);
     #$draft_list = get_cluster($cid);
     print "<p><ul>";
     foreach ($draft_list as $draft_base) {
          $draft_data = get_draft_data($pdo,$draft_base);
          $draft_data['draft_base'] = $draft_base;
          $draft_data['cid'] = $cid;
          $rfc_to_be = substr($draft_data['doc-id'],3);
          if (($draft_data['state_id'] == '2') OR ($draft_data['state_id'] == '23')){
              print "<li><a href=\"" . htmlspecialchars($document_root) . '/' . htmlspecialchars($internet_draft) . '/' . htmlspecialchars($draft_data['draft']) . ".txt\">";
	      print htmlspecialchars($draft_data['draft']) . ".txt (RFC-to-be " . htmlspecialchars($rfc_to_be) . ")</a>&nbsp;&nbsp;" . htmlspecialchars($draft_data['state']);
	      print "</a></li>";
          }elseif($draft_data['state_id'] == '14'){
               if ($cid != 'C238') {
                  $rfc_url = generate_rfc_url($draft_data['doc-id']);
                  print "<li>" . htmlspecialchars($draft_data['draft']) . ".txt&nbsp;&nbsp;<a href=\"" . htmlspecialchars($rfc_url) . "\">" . htmlspecialchars($draft_data['doc-id']) . "</a>&nbsp;&nbsp;" . htmlspecialchars($draft_data['state']) . "</li>";
               }
          }else {
	      print "<li><a href=\"" . htmlspecialchars($document_root) . "/" . htmlspecialchars($internet_draft) . "/" . htmlspecialchars($draft_data['draft']) . ".txt\">";
	      print htmlspecialchars($draft_data['draft']) . ".txt</a>&nbsp;&nbsp;" . htmlspecialchars($draft_data['state']);
	      print "</a></li>";
              
          }
     }
     print "</ul></p>";
     print "<hr>";
     foreach ($draft_list as $draft_base) {
         $draft_data = get_draft_data($pdo,$draft_base);
         if (($draft_data['state_id'] == '2') || ($draft_data['state_id'] == '23')){          
	         $dbstatus = auth48_approvals_display($pdo,substr($draft_data['doc-id'],3));
        	 if ($dbstatus === FALSE) {
	             display_error();
        	 }
             print "<hr>"; 

         } 
      }

     auth48_cluster_instruction_display($cid);
     $pdo = null;     
}
?>
    </div>
  </div>
</div>
<?php
    print "<br>";
    print "<br>";
get_footer();


//}

?>

