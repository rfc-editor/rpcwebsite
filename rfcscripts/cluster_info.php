<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
#+
# This module runs a report on a particular cluster of documents.
# Apr 2020 : Added a condition to supress display of PUB documents only for C238 - PN 
# July 2020 : Modified for PDO related changes - PN
# November 2020 : Modified the script to use PDO prepared statements - PN            
# September 2021 : Modified script to use get_draft_exact_data instead of get_draft_data - PN
#-
include('cluster_lib.php');
include('config.php');

$debug_ci = false;
if ($debug_ci === true) {
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
     global $cluster_def;
     print '<p>For the definition of a cluster, see the <a href="' . htmlspecialchars($cluster_def) . '">cluster definition page</a>.</p>';
}
function cluster_return_menu() {
     global $all_clusters,$state_def,$queue,$cluster_def;
     print '<p>For the definition of a cluster, see the <a href="' . htmlspecialchars($cluster_def) . '">cluster definition page</a>.</p>';
     print '<p>';
     print 'Show all active <a href="' . htmlspecialchars($all_clusters) . '">document clusters.</a>';
     print '</p><p>';
     print 'Go to <a href="' . htmlspecialchars($state_def) . '">definitions of state names</a>.';
     print '</p>';
}

$cid=null;
if (isset($_GET) && array_key_exists('cid', $_GET)) {
     $cid  = substr($_GET['cid'],0,MAX_CID);
} elseif (isset($_POST) && array_key_exists('cid',$_POST)) {
     $cid  = substr($_POST['cid'],0,MAX_CID);
}

if ($debug_ci === true) {
     print("\n<pre>\n\$cid=");
     print_r($cid);
     print("\n</pre>");
}

// Include WordPress
define('WP_USE_THEMES', false);
//require('/a/wordpress/wp-blog-header.php');
require_once ('/a/wordpress/wp-load.php');

//Add title specifying the dynamic page
add_filter( 'wp_title', 'wp_title_cluster_info', 10, 3 );

function wp_title_cluster_info( $title, $sep, $seplocation ) {
    global $cid;
    return '['.$cid.']' .' &raquo; RFC Editor';
}

//Add body class constant to avoid error404 body class for Wordpress outside page

function add_body_class_function($classes){

return array ('clusterinfo');
}

add_action('body_class','add_body_class_function');

get_header();
get_sidebar(left);
#get_sidebar();

if ($cid == null) {
#     cluster_report_header("No Cluster Identifier");
     print("<p>No cluster to lookup</p>\n");
} else {

   
#     cluster_report_header("[$cid]");
     # get the drafts in the queue
?>
<div id="content" class="narrowcolumn">
     <h1 class="post_title"><?php print "[" . htmlspecialchars($cid) . "]"; ?></h1>
     
     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">
<?php
    
     //cluster_info_notes();
/*Supress PUB documents for cluster C238 only */
     if ($cid == 'C238'){
        $draft_list = get_cluster($pdo,$cid,true);
     }else {
        $draft_list = get_cluster($pdo,$cid);
     }
     foreach ($draft_list as $draft_base) {
          $draft_data = get_draft_exact_data($pdo,$draft_base);
          $draft_data['draft_base'] = $draft_base;
          $draft_data['cid'] = $cid;
          display_draft_data($draft_data);
     }

?>   
      </div>
    </div>
<?php
     cluster_return_menu();
?>
</div>
<?php
//     get_sidebar();
//     cluster_return_menu();
}

//cluster_report_footer();
    print "<br>";
    print "<br>";
get_footer();


?>
