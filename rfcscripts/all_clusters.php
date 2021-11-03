<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
#+
# This module runs a report listing all active clusters of documents. (Perhaps
# "all_active_clusters" would be a better name, but there's no requirement to
# report on all clusters, past and present. An ad-hoc SQL query could fulfill
# that need.
# November 2020 : Modified the script to use PDO prepared statements - PN 
# June 2020 : Modified script for server upgrade - PN
#-
include("config.php");
include("cluster_lib.php");

$debug_ac = true;
define("BETWEEN_STATE_DRAFT",14);

function all_clusters_menu() {
     global $state_def,$queue,$cluster_def;
     print "<p>For the definition of a cluster, see the <a href=\"$cluster_def\">cluster definition page</a>.</p>";
     print "<p>Go to <a href=$state_def>definitions of state names</a>.</p>";

}

function all_clusters_note() {
     global $cluster_def,$queue; 
     print "<h3>Notes</h3>";
     print "<p>The asterisk (*) indicates documents that are normative references, but do
not themselves have any normative references to Internet-Drafts. The asterisk 
indicates that it may be published before the other documents in the cluster.</p>";
}

#cluster_report_header("All Document Clusters in the RFC Editor Queue");

// Include WordPress
define('WP_USE_THEMES', false);
#require('/a/wordpress/wp-blog-header.php');
require_once ('/a/wordpress/wp-load.php');

//Add title specifying the dynamic page
add_filter( 'wp_title', 'wp_title_all_clusters', 10, 3 );

function wp_title_all_clusters( $title, $sep, $seplocation ) {
    return 'All Document Clusters in the RFC Editor Queue &raquo; RFC Editor';
}

//Add body class constant to avoid error404 body class for Wordpress outside page

function add_body_class_function($classes){

return array ('allclusters');
}

add_action('body_class','add_body_class_function');


get_header();
get_sidebar('left');
#get_sidebar();
?>

<div id="content" class="narrowcolumn">
     <h1 class="post_title">All Document Clusters in the RFC Editor Queue</h1>

     <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
     <div class="singlepost entry">
<?php
$clusters = get_active_cluster_list($pdo);
$cluster_id = 0;
$clusters_miss = get_active_missrif_list($pdo);
$cluster_miss_id = 0;


if (count($clusters) > 0) {
     for ($i =0; $i < count($clusters); $i++){
          $cluster_id = $cluster_id + 1; 
     }
     #Count to show the total numbers of active clusters
     print "<h4>Total Number of Active clusters:  ".$cluster_id ."</h4>";
     for ($j =0; $j < count($clusters_miss); $j++){
          $cluster_miss_id = $cluster_miss_id + 1; 
     }
     #Count to show the active clusters with atleast one document in MISSREF
     print "<h4>Number of active clusters that contain at least one document in MISSREF:  ".$cluster_miss_id ."</h4>";
        
     all_clusters_note();
     #print("<p> ####   Clusters   #### </p>");
     $print_clusters_all = "";
     $print_cluster_line = "";
     $prev_cid = "";
     # process the list of clusters
     print "<table border=\"0\" width=\"80%\">";
     foreach ($clusters as $cid) {

          $draft_list = get_cluster($pdo,$cid);
          $print_cluster_non_ref_line = "";
          $ref_line_final = "";

          foreach ($draft_list as $draft_base) {
               $draft_state = get_ref_status($pdo,$draft_base);
               $anchor_value = anchored_state($pdo,$draft_base);

               #Get the draft data for the ref values
               $draft_data = get_draft_data($pdo,$draft_base);
         #$wg_ssp_id = $row['ssp_id'];

               $refs = "";
               
               if (($draft_data['ref'] != null) || ($draft_data['ref'] != NULL )) {
                    $refs = preg_split("[, *]",$draft_data['ref']);
               }

               print "<tr><td><a href=\"$cluster_info?cid=$cid\">[$cid]</a></td><td align=\"left\">$draft_state</td>";
#Added link to datatracker page to check NON-RECEIVED draft easily
               if ($draft_state != 'NOT-RECEIVED'){
               	   if ($anchor_value == IS_FREE) {
                          print "<td align=\"left\">*$draft_base</td></tr>"; 
                   } else {
                          print "<td align=\"left\">$draft_base</td></tr>";
                     }
               } else {
               	   if ($anchor_value == IS_FREE) {
                       print "<td align=\"left\"> <a href=\"$datatracker/$draft_base\">*$draft_base</a></td></tr>";

                   } else {
                       print "<td align=\"left\"><a href=\"$datatracker/$draft_base\">*$draft_base</a></td></tr>";
                   }
               } 


               foreach ($refs as $ref) {
			# The refs field of the DB has some nonsense values in it that need
			# to be ignored.
			if (($garbage = strpos($ref,'+')) !== false) {
			   $ref = substr($ref,0,$garbage);
			}
			$state = get_ref_status($pdo,$ref);
                        if ($state == 'NOT-RECEIVED'){
              
                        # Workaround to to avoid listing the same NON-RECEIVED refs 
                        # from appearing more than once and printing all the NON-RECEIVED refs
                        # at the end of cluster.

                        if (strpos($ref_line_final,$ref) === false ){
                            //print "$ref_line_final does not contains $ref<br>";
                            $ref_line_final .= $ref . "+";
                        }else {
                            //print "$ref_line_final contain $ref<br>";
                        }
                        
                        /*$print_cluster_non_ref_line .= "<tr><td><a href=\"$cluster_info?cid=$cid\">[$cid]</a></td><td align=\"left\">NOT RECEIVED</td>";
                        $print_cluster_non_ref_line .= "<td align=\"left\"><a href=\"$datatracker/$ref\">$ref</a></td></tr>";
			*/
                       
                       }

                }

 
          }
          if ($cid != $prev_cid){
              $final_non_ref = explode('+', $ref_line_final);
              $final_ref_count = count($final_non_ref);

             
              foreach ( $final_non_ref as $non_ref ){
                  if ($non_ref != ""){
                     print "<tr><td><a href=\"$cluster_info?cid=$cid\">[$cid]</a></td><td align=\"left\">NOT RECEIVED</td>";
                     print "<td align=\"left\"><a href=\"$datatracker/$non_ref\">$non_ref</a></td></tr>";
                  }
              }
              
              //   print "$print_cluster_non_ref_line";
              print "<tr><td colspan=\"3\"><hr></td></tr>";
          }
          $prev_cid = $cid;
    }
    #print "$print_clusters_all";
    print "</table>"; 
    print("\n\n");
    
} else {
     print<<<END
<p>No document clusters are active in the queue.</p>
END;
}
?>
      </div>
    </div>
<?php
$pdo = null;
all_clusters_menu();
?>
</div>
<?php
//cluster_report_footer();
    print "<br>";
    print "<br>";
get_footer();
?>



