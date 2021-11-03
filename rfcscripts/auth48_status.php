<?php
/* Copyright The IETF Trust 2020 All Rights Reserved */
# $Id: auth48_status.php,v 1.5 2021/06/17 00:47:33 priyanka Exp $
# Script to query the status of the AUTH48 process for a document.
# November 2020 : Modified the script to use PDO prepared statements - PN            
# June 2021 : Modified the script for server upgrade - PN                           
include("config.php");
include("db_connect.php");
include_once("cluster_lib.php");

// Include WordPress
define('WP_USE_THEMES', false);
//require('/a/wordpress/wp-blog-header.php');
require_once ('/a/wordpress/wp-load.php');


$debug_a48_stat = false;

define("MAX_RFC_DIGIT", 4);

/*
 * Display an error notice to the user. (The actual error should be in the
 * log.)
 */
function display_error() {
     print<<<END

<p class="error">
An error occurred while communicating with the database. Please try again later.
</p>

END;
}

/*
 * Alternate text for when a record is not returned from the query.
 */
function display_norecords($docnum) {
     print<<<END
    <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
    <div class="singlepost entry">
    <p>
No active AUTH48 process found for RFC-to-be $docnum. 
This is because the document has already been published or the document does not exist.
    </p>
    <p><a href="/rfcsearch.html">Search</a> the RFC Index.</p>

END;
}

function author_instructions() {
     print<<<END

  <h4>Instructions to author(s):</h4>
    <p>
Please see the AUTH48 notification email from the RFC Editor that contains a link to the 
edited document and other information. To send your approval or changes, please <strong>Reply All</strong> to that message.
   </p>
    <p>
When all approvals have been received, publication is imminent. Upon publication as an RFC, 
this AUTH48 status page will no longer be available.
   </p>

END;
}

# Returns TRUE if the generated filename exists on the file system.
function xml_file_check($docnum) {
     global $filepath_constant;
     $filepath = $filepath_constant.$docnum.'.xml';
     return file_exists($filepath);
}

/*
 * Use the draft and document number to generate the links to files that
 * should exist for this document.
 */
function show_files($draft, $docnum) {
     global $document_root;
     global $authors;	
     global $rfc;
     print<<<END

END;
     if (xml_file_check($docnum)) {
          print<<<END
<br />
XML SOURCE: <a href="$document_root/$authors/$rfc{$docnum}.xml">rfc{$docnum}.xml</a><br />
Please update this edited XML file with your changes. (To save the file, we suggest using "View Page Source" in your browser.) 
END;
     }

     print<<<END
      <p>
      </p>

END;
}

/*
 * Function that issues the query for the process record. The record is
 * returned as an associative array with the column names as keys. If no record
 * is found, an empty array is created and returned.
 * On error, returns a BOOLEAN false.
 */
function get_auth48s_data($docnum) {
     global $debug_a48_stat,$pdo;
     
     $docid = 'RFC'.$docnum;
     $status = 'active';

     try {
     $sql=
      "SELECT a48_id, a.status, a.notes, draft,
              date_format(start_date,'%Y-%m-%d') as start_date
       FROM auth48s a, `index` i
       WHERE a.status=:status
         AND i.`doc-id` = a.`doc-id`
         AND a.`doc-id`= :docid";
      
       $stmt = $pdo->prepare($sql);
       $stmt->bindParam('status',$status);
       $stmt->bindParam('docid',$docid);
       $stmt->execute();
       $num_of_rows = $stmt->rowCount();

      } catch (PDOException $pe){
         error_log("Error processing : get_auth48s_data", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
      }

     if ($num_of_rows > 0){
       $row = $stmt->fetch(PDO::FETCH_ASSOC);
       if ($debug_a48_stat === TRUE) {
               print("\n<!--\nget_auth48s_data: \$row=\n");
               var_dump($row);
               print("\nEnd \$row\n-->\n");
       }

       return $row;
     } else {
         return array();
     }

     return FALSE;
}




/*
 * This function accepts an associative array representing one row from the
 * approvals table and uses it to generate one row in the HTML table displaying
 * the data.
 */
function display_approver($row) {
     $approved = substr(ucfirst($row['approved']), 0 , 1);
     $date = substr($row['approved_date'], 0, 10);
     if ((strlen($date)) != 10) { $date = '&nbsp;'; }
     print<<<END
       <tr style="border: thin solid black;">
          <td style="border: inherit; text-align:left;">{$row['name']}</td>
          <td style="border: inherit; text-align:center;">$approved</td>
          <td style="border: inherit; text-align:center;">$date</td>
       </tr>

END;
}

/*
 * Function uses the key from the auth48s table to retrieve the approver
 * records from the approvals table. It then passes each row to a display
 * function.
 */
function show_approval_data($a48_id) {
     global $pdo;
     
     try {
           $sql = 
          "SELECT name, approved, approved_date
           FROM approvals 
           WHERE a48_id=:a48_id";

      $stmt = $pdo->prepare($sql);
      $stmt->bindParam('a48_id',$a48_id);
      $stmt->execute();
      $num_of_rows = $stmt->rowCount();
     }catch (PDOException $pe){
         error_log("Error processing : show_approval_data", $pe->getMessage(), $pe->getCode(), array('exception' => $pe));
     }
     
     if ($num_of_rows > 0){
         while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
               display_approver($row);
          }
          return TRUE;
     }
     return FALSE;
}

/*
 * Start the page on which AUTH48 status data will be displayed.
 */
function display_header($docnum, $a48_data) {
     $date = substr($a48_data['start_date'], 0, 10);
     $a48_draft = $a48_data['draft']; 

     print "<h1 class=\"post_title\">AUTH48 status of $a48_draft (RFC-to-be $docnum)</h1>";

     print<<<END
    <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
    <div class="singlepost entry">
    <p>
This document is in <a href="/pubprocess.html#auth48">AUTH48
state</a> as of $date. It has not yet been published as an RFC.
The RFC Editor is awaiting approvals from the author(s) as 
shown below (and anyone else listed) before continuing the publication process.
   </p>

   <table style="border: thin solid black;" align="center" width="500">
    <tr>
    <th style="border: thin solid black; text-align:left;">Name</th>
    <th style="border: thin solid black; text-align:center;">Approved?</th>
    <th style="border: thin solid black; text-align:center;">Date of Approval</th>
   </tr>

END;
}

function end_table() {
    print("\n   </table>\n");
}

/*
 * Writes the NOTES field to the output page.
 */
function display_notes($notes) {
     print<<<END

   <h4>Notes:</h4>
END;
     if ($notes != null && (strlen(trim($notes))) > 0) {
          # Use CSS "pre" to keep newlines, but don't use a fixed-width font.
          print("\n<p style=\"white-space: pre-wrap;\">$notes</p>\n");
     }

}

function display_cluster_message($draft) {
    global $pdo,$debug_auth48_stat;

    $cid = get_cluster_id($pdo,$draft);
    if ($debug_auth48_stat === true) {
         print("<h4>display_cluster_message</h4><pre>");
         print_r($draft); print("\n");
         print_r($cid); print("\n");
         print("</pre>");
    }

    if (is_string($cid) && (preg_match('/^C\d+/',$cid) == 1)){
	print("<h4>** This document is part of ");
	print("<a href=\"/cluster_info.php?cid=$cid\">Cluster $cid</a>");
	print(", so may have additional holds before publication.</h4>\n");
    }
}

/*
 * This is the "driver" function. It calls for the initial query to fetch the
 * AUTH48 data. If there is a return, it displays it. If not, it calls the "no
 * records" function.
 * Returns a BOOLEAN false on a database error after logging the error.
 */
function approvals_display($docnum) {
     global $debug_auth48_stat;

     if (($a48_data = get_auth48s_data($docnum)) !== FALSE) {
          $rec_count = count($a48_data);
          if ($debug_auth48_stat === TRUE) {
               print("\n<!--\napprovals_display: \$rec_count=$rec_count\n--n");
          }
          if ($rec_count > 0) {
               display_header($docnum, $a48_data);
               show_approval_data($a48_data['a48_id']);
               end_table();
               display_cluster_message($a48_data['draft']);
               display_notes($a48_data['notes']);
               author_instructions();
               show_files($a48_data['draft'], $docnum);
          } else {
               display_norecords($docnum);
          }
          return TRUE;
     } else {
          error_log("??auth48_status.php: Error from database: ");
     }
     return FALSE;
}

##############################################################################
# Begin script execution
##############################################################################

# examine and filter the GET input
if (isset($_GET) && array_key_exists('docnum', $_GET)) {
     # we have a query for a new document record
     $docnum = substr($_GET['docnum'], 0, MAX_RFC_DIGIT);
     if (!is_numeric($docnum)) { $docnum = NULL; }
} else {
     $docnum = NULL;
}

# pick a title for the generated page
switch ($docnum) {
case NULL:
//Add title specifying the dynamic page
     add_filter( 'wp_title', 'wp_title_auth48_status_null', 10, 3 );
     
      function wp_title_auth48_status_null( $title, $sep, $seplocation ) {
      return 'AUTH48 Status Lookup &raquo; RFC Editor ';
     }
     break;
default:
//Add title specifying the dynamic page
     add_filter( 'wp_title', 'wp_title_auth48_status', 10, 3 );
     
      function wp_title_auth48_status( $title, $sep, $seplocation ) {
      global $docnum;
      return 'AUTH48 Status: RFC-to-be '.$docnum.' &raquo; RFC Editor ';
     }
     break;
}

if ($debug_a48_stat === TRUE) {
     if (isset($_GET)) {
          print("\n<!--\$_GET\n");
          print_r($_GET);
          print("\n-->\n");
     }
}


//Add body class constant to avoid error404 body class for Wordpress outside page

function add_body_class_function($classes){

return array ('auth48status');
}

add_action('body_class','add_body_class_function');

if ($docnum != NULL) {
     
     global $pdo;

     get_header();
     get_sidebar('left');
?>
    <div id="content" class="narrowcolumn">
<?php
    $dbstatus = approvals_display($docnum);
    $pdo = null;
     if ($dbstatus === FALSE) {
          display_error();
     }

}else {
     get_header();
     get_sidebar('left');
?>

    <div id="content" class="narrowcolumn">
       <div class="post-127 page type-page status-publish hentry post clear" id="post-127">
<?php
}
?>
  </div>
  </div>
</div>

<?php
    print "<br>";
    print "<br>";
get_footer();
?>
