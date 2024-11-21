#!/usr/bin/perl
# $Id: format_queue.pl,v 2.6 2023/09/22 20:04:28 priyanka Exp $

use DBI;
use IO::File;
use File::Basename;

####################################################################
#                                                                  #
# Script	: format_queue.pl				   #
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
#               See CVS log.                                       #
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

# A little hacky, but should load some utility routines from the same dir as
# the script.
#($base = $0) =~ s#/[^/]*##g;
#$base = "." unless $base;
$base = dirname($0);
require ("$base/tvf_rfc_lib.pl");

@categories = (
    'IAB DOCUMENTS',
    'IESG DOCUMENTS',
    'WORKING GROUP STANDARDS TRACK',
    'NON-WORKING GROUP STANDARDS TRACK',
    'WORKING GROUP INFORMATIONAL/EXPERIMENTAL/BCP',
    'NON-WORKING GROUP INFORMATIONAL/EXPERIMENTAL/BCP',
    'INDEPENDENT SUBMISSIONS UNDER RFC EDITOR REVIEW'
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
SELECT  DRAFT,
    CASE
        WHEN state_id = "14" THEN "PUB"
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
#    SOURCE,`TIME-OUT-DATE`,`PUB-STATUS`, 
#    CASE
#	WHEN SOURCE="IAB" THEN 0
#	WHEN SOURCE="IESG" THEN 1
#	WHEN SOURCE NOT IN 
#	    ("IETF - NON WORKING GROUP", "IAB", "IESG", "INDEPENDENT")
#	    AND `PUB-STATUS` LIKE "%STANDARD%" 
#		THEN 2
#	WHEN SOURCE IN ("IETF - NON WORKING GROUP", "INDEPENDENT")
#	    AND `PUB-STATUS` LIKE "%STANDARD%" 
#		THEN 3
#	WHEN SOURCE NOT IN 
#	    ("IETF - NON WORKING GROUP", "IAB", "IESG", "INDEPENDENT")
#	    AND `PUB-STATUS` NOT LIKE "%STANDARD%" 
#		THEN 4
#	WHEN SOURCE IN ("IETF - NON WORKING GROUP", "INDEPENDENT")
#	    AND `PUB-STATUS` NOT LIKE "%STANDARD%" AND 
#		    STATE NOT IN  ("ISR", "ISR-AUTH")
#		THEN 5
#	WHEN 
#	    (SOURCE = "INDEPENDENT") OR 
#	    (SOURCE = "IRTF" AND STATE IN ("ISR", "ISR-AUTH"))
#		THEN 6
#	ELSE 7
 #   END cat, 
 #   CASE
#	WHEN SOURCE="INDEPENDENT" THEN 1
#	ELSE 0
#    END indep
#FROM `index` 
#    WHERE STATE NOT IN ("PUB", "DNP", "WITHDRAWN")
#ORDER BY cat, `DATE_RECEIVED`
#END

$queue_query=<<'END';
SELECT
    i.DATE_RECEIVED, i.DRAFT, 
    CONCAT( s.state_name,CASE WHEN i.iana_flag = "1"THEN "*A"ELSE ""END ,CASE WHEN i.ref_flag = "1"THEN "*R"ELSE ""END,
    CASE WHEN i.generation_number = "1" THEN "(1G)" WHEN i.generation_number = "2" THEN "(2G)" WHEN i.generation_number = "3" THEN "(3G)" WHEN i.generation_number = "0" THEN "" END) as STATE,
    i.AUTHORS, i.TITLE,i.`CHAR-COUNT`,i.REF,
    i.SOURCE,i.`TIME-OUT-DATE`,i.`PUB-STATUS`,
    CASE
        WHEN SOURCE="IAB" THEN 0
        WHEN SOURCE="IESG" THEN 1
        WHEN SOURCE NOT IN
            ("IETF - NON WORKING GROUP", "IAB", "IESG", "INDEPENDENT")
            AND `PUB-STATUS` LIKE "%STANDARD%"
                THEN 2
        WHEN SOURCE IN ("IETF - NON WORKING GROUP", "INDEPENDENT")
            AND `PUB-STATUS` LIKE "%STANDARD%"
                THEN 3
        WHEN SOURCE NOT IN
            ("IETF - NON WORKING GROUP", "IAB", "IESG", "INDEPENDENT")
            AND `PUB-STATUS` NOT LIKE "%STANDARD%"
                THEN 4
        WHEN SOURCE IN ("IETF - NON WORKING GROUP", "INDEPENDENT")
            AND `PUB-STATUS` NOT LIKE "%STANDARD%" AND
                    i.state_id NOT IN  ("12", "13")
                THEN 5
        WHEN
            (SOURCE = "INDEPENDENT") OR
            (SOURCE = "IRTF" AND i.state_id IN ("12", "13"))
                THEN 6
        ELSE 7
    END cat,
    CASE
        WHEN SOURCE="INDEPENDENT" THEN 1
        ELSE 0
    END indep
FROM `index` i, `states` s
    WHERE i.state_id NOT IN ("14", "3", "19") and
    i.state_id = s.state_id
ORDER BY cat, `DATE_RECEIVED`
END

# HTML Formatting

# The top of the HTML queue
sub header_fmt_html {
    my($fh) = @_;
    my($mon, $day, $year) = (localtime(time()))[4,3,5];
    # Localtime returns a 0-based month and years since 1900;
    $mon++; $year += 1900;
    print $fh <<END;
<html>
<head>
<title>RFC-Editor queue</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859">
</head>
<body bgcolor="#FFFFFF" onLoad="">
<TABLE border=0 cellPadding=0 cellSpacing=0 height=580 width=677 mm:layoutgroup="true">
<TBODY>
<tr>
<TD height=87 valign=top width=677>
<hr>
<img src="header_queue.gif">
</td>
</tr>
<tr>
<TD height=54 vAlign=top width=677>
<hr>
<TABLE border=1 cellSpacing=2 width=677 name="RFC-table">
<TBODY>
<TR bgcolor=#0000ff>
<TD height=24 width=90>
<DIV align=center><A href="index.html">
<FONT color=#ffffff face="Arial, Helvetica, sans-serif" size=2>RFC-ED<br>HOME</FONT></A></DIV></TD>
<TD bgColor=#0000ff height=24 width=76>
<DIV align=center><A href="news.html">
<FONT color=#ffffff face="Arial, Helvetica, sans-serif" size=2>NEWS</FONT></A></DIV></TD>
<TD bgColor=#0000ff height=24 width=108>
<DIV align=center><A href="rfc.html">
<FONT color=#ffffff face="Arial, Helvetica, sans-serif" size=2>RFC<br>DATABASE </FONT></A></DIV></TD>
<TD bgColor=#0000ff height=24 width=86>
<DIV align=center><A href="rfcsearch.html">
<FONT color=#ffffff face="Arial, Helvetica, sans-serif" size=2>RFC<br>SEARCH</FONT></A></DIV></TD>
<TD bgColor=#0000ff height=24 width=90>
<DIV align=center><A href="errata.php">
<FONT color=#ffffff face="Arial, Helvetica, sans-serif" size=2>RFC<br>ERRATA</FONT></A></DIV></TD>
<TD bgColor=#0000ff height=24 width=90>
<DIV align=center><A href="idsearch.html">
<FONT color=#ffffff face="Arial, Helvetica, sans-serif" size=2>I-D<br>SEARCH</FONT></A></DIV></TD>
<TD bgColor=#0000ff height=24 width=91>
<DIV align=center><A href="http://www.ietf.org/">
<FONT color=#ffffff face="Arial, Helvetica, sans-serif" size=2>IETF<br>HOME</FONT></A></DIV></TD></TR>
</TBODY>
</TABLE>
<HR>
<p><b>Last updated: $year/$mon/$day</b></p>
<p>
<a href="http://www.rfc-editor.org/CurrQstats.txt"><b>Summary Statistics</b></a>
on queue states.</p>
<hr>
<h3 style="color:RED;">Note: This page will be replaced by the <a style="color:RED;" href="current_queue.php" class="warning">Current Queue (sortable view)</a></h3>
<p><h3>Format of Queue Entry:</h3></p>
<P>
<UL>
<LI>
Date Received by RFC Editor (yyyy/mm/dd) &nbsp&nbsp&nbsp Internet-Draft String
<LI>
State Name
<LI>
REF (list of normative references with status) [if any]
<LI>
Document Author(s)
<LI>
Document Title
<LI>
Document size in Bytes (approximate)
<LI>
Working Group [if any]
</UL></P>
<hr>
<p>STATE NAMES:</p>
<P><UL>
<LI>
AUTH = Awaiting Author Action
<LI>
AUTH48 = Awaiting final author approval
<LI>
EDIT = Approved by the stream manager (e.g., IESG, IAB, IRSG, ISE), awaiting processing and publishing
<LI>
I = Independent Submission
<LI>
IANA = RFC-Editor/IANA Registration Coordination
<LI>
IESG = Holding for IESG Action
<LI>
ISR = Independent Submission Review by the ISE
<LI>
ISR-AUTH = Independent Submission awaiting author update, 
or in discussion between author and ISE
<LI>
REF = Holding for normative reference (followed by I-D
 string of referenced document)
<LI>
RFC-EDITOR = Awaiting final rfc-editor review before AUTH48
<LI>
TO = Time-out period during which the IESG reviews document for conflict/concurrence with other IETF working group work 
(followed by date)
<LI>
MISSREF = Awaiting missing normative reference.
</UL></P>

<p>** For more detailed information on states, please see the
<a href="ftp://ftp.rfc-editor.org/in-notes/rfc-editor/rfc-editor-process.gif"> RFC Editor Process Flow Chart</a>. 
<hr>
<pre>
END
}

# End of the HTML queue
sub footer_fmt_html {
    my($fh) = @_;
    print $fh <<'END';
</pre>
Please send <A HREF="mailto:rfc-editor@rfc-editor.org">mail</A> about any problems with or comments on this page. <BR> </P>
<p>Funding for the RFC Editor function is currently provided by
the Internet Society.</P>
</P>
</TD></TR></TBODY></TABLE>
</body>
</html>
END
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
sub draft_fmt_html {
    my($date_received, $draft, $state, $authors, $title, $chars, $ref, 
	$source, $timeout, $pub_status, $cat, $indep, $refmap, $fh) =@_;
    my($ftp_root)="https://datatracker.ietf.org/doc";
    my($stuck_on_IANA) = ($state =~ s/\*A//);
    my($anchor) = &strip_number($draft);

    $ref = &scrub_refs_db($ref);
#    $state =~ s/\*R//;

    $title = &scrub_for_xml($title);
    $source = &scrub_for_xml($source);
    $authors = &scrub_for_xml($authors);

    # For historical reasons, independent submissions have -I appended to
    # the date.
    $date_received .= '-I' if  $indep;
    # Drafts waiting for IESG TO get the ttimeout printed, too
    $state = "$state (expires $timeout)" if $state eq "TO";
    print $fh <<END;
<a name="$anchor"></a>
$date_received	<a href="$ftp_root/$draft.txt">$draft.txt</a>
END
    # Only print REF once and the loop below will handle it if the
    # state is REF (and you cannot be REF*A)
    print $fh "$state\n" unless $state eq "REF";
    print $fh "IANA\n" if $stuck_on_IANA;
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
$title
Bytes: $chars
END
    # Categories attached to WGs (including the IAB and IESG) get that printed,
    # too
    print $fh "Working Group: $source\n" if ($cat =~/[0124]/);
    print $fh "\n\n";
}

# XML formatting code

sub header_fmt_xml {
    my($fh) = @_;
    print $fh "<rfc-editor-queue xmlns=\"http://www.rfc-editor.org/" . 
	"rfc-editor-queue\">\n";
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
sub draft_fmt_xml {
    my($date_received, $draft, $state, $authors, $title, $chars, $ref, 
	$source, $timeout, $pub_status, $cat, $indep, $refmap, $fh) =@_;
    my($id) = &strip_number($draft);

    my($stuck_on_IANA) = ($state =~ s/\*A//);

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
    print $fh "<state>IANA</state>\n" if  $stuck_on_IANA;
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
	    $outstr .= "<ref>\n<name>$refterm</name>\n" . 
		"<ref-state>$rstat</ref-state>\n</ref>\n" 
		    unless $rstat eq "PUB";
	}
	print $fh "<state>REF</state>$outstr" if $outstr;
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
    print $fh "<source>$source</source>\n" if ($cat =~/[0124]/);
    print $fh "</entry>\n";
}



# MAIN code

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
    {RaiseError => 0, AutoCommit =>0 } ) || 
	die "$0: Cannot connect to database: $DBI::errstr\n";

# Open the output file after we know we have DB access.
$fh = new IO::File(">$file") || die "$0: Can't open $file: $!\n";

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
	$chars, $ref, $source, $timeout, $pub_status, $cat, $indep));

$lastcat=-1;	# Last category printed.

&$header_fmt($fh);
while ($sth->fetch ) {
    # Print a new header (or headers if there are no drafts in a given
    # category)
    while ( $lastcat < $cat ) { &$section_fmt(++$lastcat, $fh); }

    # Put out the html formatted document
    &$draft_fmt($date_received, $draft, $state, $authors, $title,
	$chars, $ref, $source, $timeout, $pub_status, $cat, $indep, 
	\%refmap, $fh);
}
&$footer_fmt($fh);
$fh->close();
