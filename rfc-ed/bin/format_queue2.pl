#!/usr/bin/env perl
# $Id: format_queue2.pl,v 2.13 2023/09/22 20:04:28 priyanka Exp $

use DBI;
use IO::File;
use File::Basename;

####################################################################
#                                                                  #
# Script	: format_queue2.pl				   #
# Author	: Ted Faber <faber@isi.edu>			   #
# Date  	: December 8, 2006			           #
# Desc		: Read the rfc editor database and output the      #
#                 queue formatted in one fo several formats.       #
#                 Currently HTML and XML are supported.  The       #
#                 script takes as many as 3 parameters, the format #
#                 to use (xml,html), a filename to output, and a   #
#                 database info file.  The format defaults to xml, #
#                 the file to ./queue.$fmt where $fmt is the       #
#                 format parameter, and the db info file to        #
#                 ./db_info.                                       #
# Modifications :						   # 
#         See CVS log.                                             #
#         Updated from Ted's format_queue.pl: Braden, 2008 08 29   #
#         Added awaiting_ad_approval flag to xml output:           #
#                               PN, 2021 03 05                     #
#         Added the UTF8 handling for database reading and o/p     #
#         printing : PN, 2021 10 18                                #
#         Added the Editorial Stream handling part for queue query #
#         and the related places in the script : PN, 2022 08 16    #
####################################################################


# This script is reads the DB and outputs the current queue in a variety of
# formats.  For each format the basic control flow is the same and is
# specialized by subroutines that produce the formatting in question.
# Formatting functions are a header and footer function, a section function and
# a draft formatting function.  The header and footer functions are called with
# only the open output file handle, the section is called with the current
# caregory index (i.e., an index into @categories), and the draft formatter is
# called with all the relevant information for the current draft as well as a
# reference to the map of known referencable drafts.
#
# This file is broken up into global variables (categories, SQL queries), then
# the formatting functions and finally the main loop of the code.  You can find
# the main loop by searching on MAIN.

# Load some utility routines from the same dir as the script.
$base = dirname($0);
require ("$base/tvf_rfc_lib.pl"); # i.e. library is in same directory as script
require ("$base/clusters_lib.pl");# i.e. library is in same directory as script

@categories = (
    'IETF STREAM: WORKING GROUP STANDARDS TRACK',
    'IETF STREAM: NON-WORKING GROUP STANDARDS TRACK',
    'IETF STREAM: WORKING GROUP INFORMATIONAL/EXPERIMENTAL/BCP',
    'IETF STREAM: NON-WORKING GROUP INFORMATIONAL/EXPERIMENTAL/BCP',
    'IAB STREAM',
    'IRTF STREAM',
    'INDEPENDENT SUBMISSIONS',
    'Editorial Stream'
);

# This query retrieves only the state information for all queue entries in
# terms of their viability as references.  Basically anything published is
# listed as such, anything we know of is IN-QUEUE, anything WITHDRAWN or DNP is
# treated as NOT-RECEIVED as we're hopeful it will return.  Anything not in
# this map is NOT-RECEIVED

#$map_query=<<'END';
#SELECT DRAFT, 
#    CASE
#	WHEN STATE = "PUB" THEN "PUB"
#	WHEN STATE IN ("DNP", "WITHDRAWN") THEN "NOT-RECEIVED"
#	WHEN STATE IS NOT NULL THEN "IN-QUEUE"
#	ELSE "NULL"
#    END
#FROM `index`
#END


$map_query=<<'END';
SELECT DRAFT,
    CASE
        WHEN state_id = "14" THEN "PUB"
	WHEN state_id = "99" THEN "NULL"
        WHEN state_id IN ("3", "19") THEN "NOT-RECEIVED"
        WHEN state_id IS NOT NULL THEN "IN-QUEUE"
        ELSE "NULL"
    END
FROM `index`
END


# This query pulls drafts from the DB that are awating publication.  The case
# statement categorized the drafts into one of the categories in @categories
# above.  The case returns the index into categories so that we can sort the
# results from the DB.

#$queue_query=<<'END';
#SELECT 
#    DATE_RECEIVED, DRAFT, STATE, AUTHORS, TITLE,`CHAR-COUNT`,REF, 
#    SOURCE,`TIME-OUT-DATE`,`PUB-STATUS`, `DOC-ID`,
#    CASE
#	WHEN SOURCE NOT IN 
#	    ("IETF - NON WORKING GROUP", "IAB", "IESG", "IRTF", "INDEPENDENT")
#	    AND   `PUB-STATUS` LIKE "%STANDARD%"  
#		THEN 0
#	WHEN SOURCE IN ("IETF - NON WORKING GROUP")
#	    AND    `PUB-STATUS` LIKE "%STANDARD%" 
#		THEN 1
#	WHEN SOURCE NOT IN 
#	    ("IETF - NON WORKING GROUP", "IAB", "IESG", "IRTF", "INDEPENDENT")
#	    AND `PUB-STATUS` NOT LIKE "%STANDARD%"
#		THEN 2
#	WHEN SOURCE IN ("IETF - NON WORKING GROUP")
#	    AND `PUB-STATUS` NOT LIKE "%STANDARD%"
#		THEN 3
#	WHEN SOURCE = "IESG"
#		THEN 3     # Reclassify IESG as Non-WG INFO/EXPTL
#	WHEN SOURCE="IAB"
#		THEN 4
#	WHEN SOURCE="IRTF"
#		THEN 5
#	WHEN 
#	    (SOURCE = "INDEPENDENT")
#		THEN 6
#	ELSE 7
#    END cat, 
#    CASE
#	WHEN SOURCE="INDEPENDENT" THEN 1
#	ELSE 0
#   END indep
#FROM `index` 
#    WHERE STATE NOT IN ("PUB", "DNP", "WITHDRAWN")
#ORDER BY cat, `DATE_RECEIVED`
#END


$queue_query=<<'END';
SELECT
    i.DATE_RECEIVED, 
    i.DRAFT, 
    CONCAT( s.state_name,CASE WHEN i.iana_flag = "1"THEN "*A"ELSE ""END ,CASE WHEN i.ref_flag = "1"THEN "*R"ELSE ""END,
    CASE WHEN i.generation_number = "1" THEN "(1G)" WHEN i.generation_number = "2" THEN "(2G)" WHEN i.generation_number = "3" THEN "(3G)" WHEN i.generation_number = "0" THEN "" END) as STATE,
    i.AUTHORS, i.TITLE,i.`CHAR-COUNT`,i.REF,
    i.SOURCE,i.`TIME-OUT-DATE`,i.`PUB-STATUS`, i.`DOC-ID`,w.`ssp_id`,i.`consensus_bit`, 
    CASE
        WHEN SOURCE NOT IN
            ("IETF - NON WORKING GROUP", "IAB", "IESG", "IRTF", "INDEPENDENT", "Editorial")
            AND   `PUB-STATUS` LIKE "%STANDARD%" AND w.`ssp_id` != "4"
                THEN 0
        WHEN SOURCE IN ("IETF - NON WORKING GROUP")
            AND    `PUB-STATUS` LIKE "%STANDARD%"
                THEN 1
        WHEN SOURCE NOT IN
            ("IETF - NON WORKING GROUP", "IAB", "IESG", "IRTF", "INDEPENDENT", "Editorial")
            AND `PUB-STATUS` NOT LIKE "%STANDARD%" AND w.`ssp_id` != "4"
                THEN 2
        WHEN SOURCE IN ("IETF - NON WORKING GROUP")
            AND `PUB-STATUS` NOT LIKE "%STANDARD%"
                THEN 3
        WHEN SOURCE = "IESG"
                THEN 3     # Reclassify IESG as Non-WG INFO/EXPTL
        WHEN SOURCE="IAB"
                THEN 4
        WHEN SOURCE="IRTF" OR w.`ssp_id` = "4"
                THEN 5
        WHEN
            (SOURCE = "INDEPENDENT")
                THEN 6
        WHEN SOURCE="Editorial" OR w.`ssp_id` = "8"
                THEN 7
        ELSE 8
    END cat,
    CASE
        WHEN SOURCE="INDEPENDENT" THEN 1
        ELSE 0
    END indep,
    CASE 
        WHEN (i.`state_id` = "2" OR i.`state_id` = "23") THEN a.`awaiting_ad_approval`
        ELSE ""
    END awaiting_ad_approval  
FROM `index` i, `states` s, `working_group` w, `auth48s` a
    WHERE i.state_id NOT IN ("14", "3", "19","99") and
    i.state_id = s.state_id and
    i.source = w.wg_name and 
    i.`doc-id` = a.`doc-id`
ORDER BY cat, `DATE_RECEIVED`
END


# HTML Formatting

# The top of the HTML queue
sub header_fmt_html {
    my($fh) = @_;
    print $fh <<END;
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <title>RFC-Editor queue</title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859" />
    <link rel="stylesheet" href="style/rfc-editor.css" type="text/css" />
</head>
<body>
<TABLE border=0 cellPadding=0 cellSpacing=0 width=677>
<TBODY>
<tr>
<td height=87 valign=top width=677>
<hr>
<img src="header_queue.gif">
</td>
</tr>
<tr>
<td height=54 vAlign=top width=677>
<hr>
  <table class="nav" id="RFC-table">
  <tr>
    <td height="24" width="90" class="nav"><div class="navcell"><a href="/" class="white">RFC-ED<br>HOME</a></div></td>
    <td height="24" width="76" class="nav"><div class="navcell"><a href="news.html" class="white">NEWS</a><br>&nbsp;</div></td>
    <td height="24" width="108" class="nav"><div class="navcell"><a href="rfc.html" class="white">RFC<br>DATABASE</a></div></td>
    <td height="24" width="86" class="nav"><div class="navcell"><a href="rfcsearch.html" class="white">RFC<br>SEARCH</a></div></td>
    <td height="24" width="90" class="nav"><div class="navcell"><a href="errata.php" class="white">RFC<br>ERRATA</a></div></td>
    <td height="24" width="90" class="nav"><div class="navcell"><a href="idsearch.html" class="white">I-D<br>SEARCH</a></div></td>
    <td height="24" width="91" class="nav"><div class="navcell"><a href="http://www.ietf.org/" class="white">IETF<br>HOME</a></div></td>
  </tr>
</table>
<hr>
<h3 class ="warning">Note: This page will be replaced by the <a href="current_queue.php" class="warning">Current Queue (sortable view)</a></h3>
<h3>Format of Queue Entries</h3>
<div style="font-family: monospace; margin-left:30px;">
<pre>
Date Received by RFC Editor (yyyy-mm-dd)&nbsp;&nbsp;&nbsp;&nbsp;Internet-Draft String [<a href="cluster_def.html">Cluster ID</a>, if any]
<a href="state_def.html">State Name</a>
REF (list of normative references with status) [if any]
Document Author(s)
Document Title
Document size in Bytes (approximate)
Working Group [if any]
</pre>
</div>
<hr>
<h3><a href="current_queue.php">Current Queue (sortable view)</a></h3>
<h3><a href="all_clusters.php">List of Active Clusters</a></h3>
<h3><a href="Qother.html">Additional Queue-Related Information</a></h3>
<hr>
<h2>Publication Queue</h2>
<pre>
END
}

# End of the HTML queue
sub footer_fmt_html {
    my($fh) = @_;

   my($mon, $day, $year) = (localtime(time()))[4,3,5];
    # Localtime returns a 0-based month and years since 1900;
    $mon++; $year += 1900;

    print $fh <<'END';
</pre>
<hr>
END
print $fh "<p><b>Queue Last Updated (year/month/day): $year/$mon/$day</b></p>";

print $fh <<'END';
</TD></TR></TBODY></TABLE>
</body>
</html>
END
}

# AUTH48 link for HTML
sub auth48_link {
    my($docnum, $state, $cid, $fh) = @_;
#    print $fh "<a href=\"/auth48/rfc$docnum\">AUTH48</a>\n";
    if ($cid=~/^0/) {
	   print $fh "<a href=\"/auth48/rfc$docnum\">$state</a>\n";
    }else{
           $cid_number = substr($cid,1);
 	   print $fh "<a href=\"/auth48/C$cid_number\">$state</a>\n";
    }

}

# Section header for HTML
sub section_fmt_html {
    my($cat, $fh) = @_;
    print $fh <<END;

-------------------------------------------------------------------
$categories[$cat] (by date received)
-------------------------------------------------------------------

END
}

# format the draft for HTML output.  We scrub the title, author list and source
# for bad XML characters, but assume the rest are OK.
#
sub draft_fmt_html {
    my($date_received, $draft, $state, $authors, $title, $chars, $ref, 
	$source, $timeout, $pub_status, $cat, $indep, $doc_id, $consensus_bit, $awaiting_ad_approval, $refmap, $fh) = @_;
## CHANGE FROM FTP TO HTTP AS STANDARD TRANSMITTAL MECHANISM
## BB 20080828
##
##  my($ftp_root)="ftp://ftp.rfc-editor.org/in-notes/internet-drafts";
    my($ftp_root)="https://datatracker.ietf.org/doc";

#    my($stuck_on_IANA) = ($state =~ s/\*A//);
    my($anchor) = &strip_number($draft);
    my($cid) = &get_cluster_id($draft); # return '0' if none
#    print "$draft is in cluster $cid\n" unless $cid =~/0/;
    $ref = &scrub_refs_db($ref);
#    $state =~ s/\*R//;

    $title = &scrub_for_xml($title);
    $source = &scrub_for_xml($source);
    $authors = &scrub_for_xml($authors);

###    ## For historical reasons, independent submissions have -I appended to
###    # the date.
###    $date_received .= '-I' if  $indep;

    # Drafts waiting for IESG TO get the timeout printed, too
    $state = "$state (expires $timeout)" if $state eq "TO";

    my $cluster_url;
    unless ($cid=~/^0/) {
#        print "Adding cluster $cid for $draft\n";
        $cluster_url = &make_cluster_info_url($cid);
    }
    # if $cluster_url isn't defined, it will not show in the output.
    print $fh <<END;
<a name="$anchor"></a>
$date_received	<a href="$ftp_root/$draft.txt">$draft.txt</a> $cluster_url
END
#    if ($state eq 'AUTH48') {
    if ($state =~ m/AUTH48/) {
#        &auth48_link(substr($doc_id,3), $fh);
         &auth48_link(substr($doc_id,3),$state, $cid,$fh);
    } else {
    # Only print REF once and the loop below will handle it if the
    # state is REF (and you cannot be REF*A)
        print $fh "$state\n" unless $state eq "REF";
    #    print $fh "IANA\n" if $stuck_on_IANA;
    }
    if ( $ref ) {
	# Even if we get here, it's possible that we don't need to
	# print anything, specifically, if all the refs are
	# published, there's nothing to print.  Compose the output
	# and if there's something to print, prefix the
	# REF and print it.
	$outstr = "";
	foreach $rt (split(",", $ref)) {
	    $refterm = &scrub_ref($rt);
	    $rstat = $refmap->{$refterm} || "NOT-RECEIVED";
	    $outstr .= "\t$refterm\t$rstat\n" unless $rstat eq "PUB";
	}
	print $fh "REF$outstr" if $outstr;
    }
    print $fh <<END;
$authors
\"$title\"
Bytes: $chars
END
    # Categories attached to WGs: print WG names, too
    # for the categories in the regexp set (see SQL CASE above)
    print $fh "Working Group: $source\n" if ($cat =~/[02]/);
    print $fh "\n";
}

# XML formatting code

sub header_fmt_xml {
    my($fh) = @_;
print $fh <<"END";
<?xml version="1.0" encoding="UTF-8"?>
<rfc-editor-queue xmlns=\"http://www.rfc-editor.org/rfc-editor-queue">
END
}

sub footer_fmt_xml {
    my($fh) = @_;
    print $fh "</section>\n";
    print $fh "</rfc-editor-queue>\n";
}

sub section_fmt_xml {
    my($cat, $fh) = @_;

    # If another section is open close it.  Another section is open unless this
    # is the first call to this function - category 0.
    print $fh "</section>\n" unless $cat == 0;
    print $fh "<section name=\"$categories[$cat]\">\n";
}

#format the draft for xml output
#The "doc_id" argument isn't used here. The HTML function does and this is
#needed here to keep the function signatures the same.
sub draft_fmt_xml {
    my($date_received, $draft, $state, $authors, $title, $chars, $ref, 
	$source, $timeout, $pub_status, $cat, $indep, $doc_id, $consensus_bit, $awaiting_ad_approval, $refmap, $fh) =@_;
    my($id) = &strip_number($draft);

#    my($stuck_on_IANA) = ($state =~ s/\*A//);

    $ref = &scrub_refs_db($ref);

    $title = &scrub_for_xml($title);
    $source = &scrub_for_xml($source);
    $authors = &scrub_for_xml($authors);
#    $state =~ s/\*R//;

    print $fh <<END;
<entry xml:id="$id">
<draft>$draft.txt</draft>
<date-received>$date_received</date-received>
END
    # Only print one <state>REF</state> and the loop below will
    # handle it if the state is REF (and you cannot be REF*A)
    print $fh "<state>$state</state>\n" unless $state eq "REF";
#    print $fh "<state>IANA</state>\n" if  $stuck_on_IANA;
#    if ( $state eq "AUTH48" ) {
#    if ($state eq "AUTH48" or $state eq "AUTH48*R") {
    if ($state =~ m/AUTH48/) {
        my $lower = lc($doc_id);
        print $fh "<auth48-url>https://www.rfc-editor.org/auth48/$lower</auth48-url>\n";
    }
    if ( $ref ) {
	# Even if we get here, it's possible that we don't need to
	# print anything, specifically, if all the refs are
	# published, there's nothing to print.  Compose the output
	# and if there's something to print, prefix the
	# <state>REF</state> and print it.
	$outstr= "";

	# Print the refs and states properly formatted
	foreach $rt (split(",", $ref)) {
	    $refterm = &scrub_ref($rt);
	    $rstat = $refmap->{$refterm} || "NOT-RECEIVED";
	    $outstr .= "<normRef>\n<ref-name>$refterm</ref-name>\n" . 
		"<ref-state>$rstat</ref-state>\n</normRef>\n" 
		    unless $rstat eq "PUB";
	}
	print $fh "$outstr" if $outstr;
    }
    print $fh <<END;
<authors>$authors</authors>
<title>
$title
</title>
END
    print $fh "<bytes>$chars</bytes>\n" if $chars;
    # Categories attached to WGs (including the IAB and IESG) get that printed,
    # too
    print $fh "<source>$source</source>\n" if ($cat =~/[0-8]/);
    # Consensus bit place holder
    print $fh "<consensus>$consensus_bit</consensus>\n" if $consensus_bit;
    print $fh "<awaiting_ad_approval>$awaiting_ad_approval</awaiting_ad_approval>\n" if $awaiting_ad_approval;
    print $fh "</entry>\n";
}


############################################################
#
#      MAIN code
#
############################################################A

$fmt = shift || "xml";
$file = shift || "./queue.$fmt";
$db_file = shift || "./db_info";

if ( $fmt eq "xml" ) {
    $header_fmt = \&header_fmt_xml;
    $section_fmt = \&section_fmt_xml;
    $draft_fmt = \&draft_fmt_xml;
    $footer_fmt = \&footer_fmt_xml;
}
elsif ($fmt eq "html" ) {
    $header_fmt = \&header_fmt_html;
    $section_fmt = \&section_fmt_html;
    $draft_fmt = \&draft_fmt_html;
    $footer_fmt = \&footer_fmt_html;
}
else {
    die "unknown format: $fmt\n";
}


($db_user, $db_passwd, $DSN) = &get_db_parameters($db_file);

# Initialize connection to database
$dbh = DBI->connect($DSN,$db_user,$db_passwd,
    {mysql_enable_utf8 => 1, RaiseError => 0, AutoCommit =>0 } ) || 
	die "$0: Cannot connect to database: $DBI::errstr\n";

# Open the output file after we know we have DB access.
$fh = new IO::File(">$file") || die "$0: Can't open $file: $!\n";

#Force UTF8 mode for o/p file
$fh->binmode(':utf8');

# Build a quick (bit)map of the drafts we know about
$sth = $dbh->prepare( $map_query );
$sth->execute;
# pull the map query into these variables.
$sth->bind_columns(\($draft, $state));

while ($sth->fetch) {
    $key = &strip_number($draft);
    $refmap{$key} = $state;
}

#  The map's built, now walk the list of unpublished documents and format them.
$sth = $dbh->prepare($queue_query);
$sth->execute;
$sth->bind_columns(\($date_received, $draft, $state, $authors, $title,
	$chars, $ref, $source, $timeout, $pub_status, $doc_id, $ssp_id, $consensus_bit, $cat, $indep, $awaiting_ad_approval));

$lastcat=-1;	# Last category printed.

&$header_fmt($fh);
while ($sth->fetch ) {
    # Print a new header (or headers if there are no drafts in a given
    # category)
    while ( $lastcat < $cat ) { &$section_fmt(++$lastcat, $fh); }

    # Put out the html formatted document
    &$draft_fmt($date_received, $draft, $state, $authors, $title,
	$chars, $ref, $source, $timeout, $pub_status, $cat, $indep, $doc_id, $consensus_bit, $awaiting_ad_approval,
	\%refmap, $fh);
}
&$footer_fmt($fh);
$fh->close();
