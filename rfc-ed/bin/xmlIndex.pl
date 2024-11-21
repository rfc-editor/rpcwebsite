#!/usr/bin/env perl
# $Id: xmlIndex.pl,v 2.10 2024/08/07 21:22:59 priyanka Exp $
#
# xmlIndex.pl: Creates rfc-index.xml from the RFC Editor relational database.
#
# The script writes the XML output to STDOUT. Pipe this to a file name of your
# choice. Supply an argument (any value) to get debug data on STDERR.
#
# Usage examples (sh/bash syntax):
#         perl xmlIndex.pl > rfc-index.xml
#         perl xmlIndex.pl debug_me > rfc-index.xml 2> xmlIndex.log
#
# The <errata-url> elements are created for RFC documents that have records
# in the errata table of the database. The file  ~rfc-ed/bin/RFCs_for_errata
# is no longer used by this script.
#
# This script will read each set of records and convert them to 2-d hash
# where the first-level keys are the document names (value of the DOC-ID
# field) and the 2nd level keys are the attributes of the given document.
#
# Example:
#		$docs{'rfc1818'} -> {'TYPE'} -> "RFC"
#				    {'DOC-ID'} -> "RFC1818"
#				     ...
#				    {'ABSTRACT'} -> "This document ... "
#
# History Note:
# This script adapted from a script of the same name that processed the text
# file INDEX to create the same XML output. See versions 1.4 and older for
# details.
#
# Added code to include DOIs in the XML, John Levine, April 2015
# May 2019 : Modified the script to display new formats v3PDF, HTML, dropped the CHAR-COUNt
#          : from the o/p for HTML files. - PN 
# August 2019 : Modified the script to display file-formats under the element format. Removed
#               CHAR-COUNT from the o/p. And made PAGE-COUNT as a single element. -PN 
# June 2021 : Modified the script for server upgrade - PN                            
# August 2022 : Modified the script for Editorial stream - PN
# October 2022 : Modified the script special suffix case II, III and et al. - PN  
# August 2024 : Modified the script to change errata url from http to https - PN  
use DBI ;
use IO::File;
use File::Basename;

use warnings;
use strict;
binmode STDOUT, ":encoding(utf8)";
 
# Define common locations of things

my $debug_xmlIndex = shift || 0;

my $iam = basename $0;

my $progdir = dirname $0;
my $db_file = "$progdir/database_information.txt";

&tracer ("Running $iam\n"); # If you don't want this, pipe it to /dev/null

my %docs;			# the hash that holds all the INDEX records
my @record;			# holds one record while looping

my @month_names = ("", "January", "February", "March", "April",
                   "May", "June", "July", "August", "September",
                   "October", "November", "December");
my %statusMap = (
	'BCP'			=>	'BEST CURRENT PRACTICE',
	'BEST CURRENT PRACTICE'	=>	'BEST CURRENT PRACTICE',
	'DRAFT STANDARD'	=>	'DRAFT STANDARD',
	'EXPERIMENTAL'		=>	'EXPERIMENTAL',
	'FYI'			=>	'FOR YOUR INFORMATION',
	'HISTORIC'		=>	'HISTORIC',
	'INFORMATION'		=>	'INFORMATIONAL',
	'INFORMATIONAL'		=>	'INFORMATIONAL',
	'PROPOSED STANDARD'	=>	'PROPOSED STANDARD',
	'PROPOSED STANDARD PROTOCOL' => 'PROPOSED STANDARD',
	'PROPOSESD STANDARD'	=>	'PROPOSED STANDARD',
	'INTERNET STANDARD'	=>	'INTERNET STANDARD',
	'STANDARDS TRACK'	=>	'INTERNET STANDARD',
	'STD'			=>	'INTERNET STANDARD',
	'UNKNOWN'		=>	'UNKNOWN'
    );

my %Relations = ( "OBSOLETES"    => "obsoletes",
		  "OBSOLETED-BY" => "obsoleted-by",
		  "UPDATES"      => "updates",
		  "UPDATED-BY"   => "updated-by",
		  "CLARIFIES"    => "clarifies",
		  "CLARIFIED-BY" => "clarified-by");

# Array used to make the order of placing relationship elements
# explicit. (Using "keys %Relations" did not return keys in the order required
# by the XML Schema.) As of edit 1.5, the "CLARIFIES" pair is not in the XSD
# and is not supported.
my @relation_order = ("OBSOLETES", "OBSOLETED-BY", "UPDATES", "UPDATED-BY");

# SQL to extract each set of documents. Sub series, BCP, FYI, and STD, do not
# need all of the columns that the main RFC documents need.
my $rfc_select = qq{
           SELECT TYPE, `DOC-ID`, TITLE, AUTHORS, `PUB-DATE`, KEYWORDS, 
                  ORGANIZATION, FORMAT, `CHAR-COUNT`, `PAGE-COUNT`, NOTES, 
                  OBSOLETES, `OBSOLETED-BY`, UPDATES, `UPDATED-BY`, `SEE-ALSO`,
                 `PUB-STATUS`, STATUS, ABSTRACT, DRAFT, EMAIL, SOURCE,
                  WG_NAME as 'WG_NAME', WG_ACRONYM as 'WG_ACRONYM',
                  w.AREA_NAME as 'AREA_NAME', AREA_ACRONYM as 'AREA_ACRONYM',
                 STREAM_NAME as 'STREAM', w.ssp_id as 'SSP_ID', i.DOI as DOI
           FROM `index` i, working_group w, area a, stream_specific_parties s
           WHERE TYPE='RFC' AND state_id='14' AND `DOC-ID` <> '0'
                AND `DOC-ID` <> '' 
                AND i.source = w.wg_name AND w.area_name = a.area_name 
                AND w.ssp_id = s.ssp_id
           ORDER BY `DOC-ID` };

my $bcp_select = qq{
          SELECT TYPE, `DOC-ID`, `SEE-ALSO`, TITLE,
                 `PUB-STATUS`, STATUS
          FROM `index`
          WHERE (`SEE-ALSO` LIKE '%BCP%' OR TYPE = 'BCP')
          ORDER BY `SEE-ALSO` };

my $fyi_select = qq{
          SELECT TYPE, `DOC-ID`, `SEE-ALSO`, TITLE,
                 `PUB-STATUS`, STATUS
          FROM `index`
          WHERE (`SEE-ALSO` LIKE '%FYI%' OR TYPE = 'FYI')
          ORDER BY `SEE-ALSO` };

my $std_select = qq{
          SELECT TYPE, `DOC-ID`, `SEE-ALSO`, TITLE,
                 `PUB-STATUS`, STATUS
          FROM `index`
          WHERE (`SEE-ALSO` LIKE '%STD%' OR TYPE = 'STD')
          ORDER BY `SEE-ALSO` };

require ("$progdir/tvf_rfc_lib.pl");
my ($db_user, $db_passwd, $DSN) = &get_db_parameters($db_file);
my $dbh = DBI->connect($DSN,$db_user,$db_passwd,
    {mysql_enable_utf8 => 1, RaiseError => 0, AutoCommit =>0 } ) ||
        die "$0: Cannot connect to database: $DBI::errstr\n";

my $row;

&tracer ("$bcp_select\n");
my $statement = $dbh->prepare($bcp_select)
    or die "Couldn't prepare query '$bcp_select' : $DBI::errstr\n";
$statement->execute() 
    or die "Couldn't execute query '$bcp_select': $DBI::errstr\n";

while ($row = $statement->fetchrow_hashref()) {
    my $temp_id = &addrow($row, 'BCP');
}

&tracer ("$fyi_select\n");
$statement = $dbh->prepare($fyi_select)
    or die "Couldn't prepare query '$fyi_select' : $DBI::errstr\n";
$statement->execute() 
    or die "Couldn't execute query '$fyi_select': $DBI::errstr\n";
while ($row = $statement->fetchrow_hashref()) {
    my $temp_id = &addrow($row, 'FYI');
}

&tracer ("$std_select\n");
$statement = $dbh->prepare($std_select)
    or die "Couldn't prepare query '$std_select' : $DBI::errstr\n";
$statement->execute() 
    or die "Couldn't execute query '$std_select': $DBI::errstr\n";
while ($row = $statement->fetchrow_hashref()) {
    my $temp_id = &addrow($row, 'STD');
}

&tracer ("$rfc_select\n");
$statement = $dbh->prepare($rfc_select)
    or die "Couldn't prepare query '$rfc_select' : $DBI::errstr\n";
$statement->execute() 
    or die "Couldn't execute query '$rfc_select': $DBI::errstr\n";
while ($row = $statement->fetchrow_hashref()) {
    my $temp_id = &addrow($row, 'RFC');
}

#
# Print out the XML root element with appropriate namespaces
#
print <<"END HEADER";
<?xml version="1.0" encoding="UTF-8"?>
<rfc-index xmlns="https://www.rfc-editor.org/rfc-index" 
           xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
           xsi:schemaLocation="https://www.rfc-editor.org/rfc-index 
                               https://www.rfc-editor.org/rfc-index.xsd">
END HEADER

#
# Loop through each entry in the INDEX file.  Only pay attention to those
# with a type of 'RFC'.
#
foreach my $id (sort keys %docs) {
    my $doctype = $docs{$id}{'TYPE'};

    &tracer ("$iam: processing $id as type $doctype\n");

    if ($doctype eq 'BCP') {
	&bcpDoc($id);
    }
    elsif ($doctype eq 'FYI') {
	&fyiDoc($id);
    }
    elsif ($doctype eq 'RFC') {
	#
	# Check to see whether we should create an 'rfc-entry' element or an
	# 'rfc-not-issued-entry element.  This will based on a value of "Not
	# Issued" in the TITLE field.
	#
	if ($docs{$id}{'STATUS'} eq 'NOT ISSUED' || 
            $docs{$id}{'TITLE'} eq 'Not Issued') {
	    &rfcNotIssuedDoc($id);
	}
	else {
	    &rfcDoc($id);
	}
    }
    elsif ($doctype eq 'STD') {
	&stdDoc($id);
    }
}

#
# Print out the root element end tag
#
print "</rfc-index>\n";
$dbh->disconnect;


#----------------------------------------------------------------------------
# END OF PROGRAM -- subroutines defined below

#
# Function to output a 'bcp-entry' element given a doc-id.
#
sub bcpDoc($) {
    my $id = shift;
    my @ids;
    #
    # Each BCP entry will make reference to exactly one RFC (this may change
    # at a later time).  Since this is the case, no looping is necessary.
    #
    print "    <bcp-entry>\n";
    print "        <doc-id>$id</doc-id>\n";

    if ($docs{$id}{'SEE-ALSO'}) {
	@ids = sort split(/,\s*/,$docs{$id}{'SEE-ALSO'});
	print "        <is-also>\n";
	my $idss;
	foreach $idss (@ids)
	{
	    if($idss ne '')
	    {
		print "            <doc-id>$idss</doc-id>\n";
	    }
	}
	print "        </is-also>\n";
    }

    print "    </bcp-entry>\n";
}

#
# Function to output a 'fyi-entry' element given a doc-id.
#
sub fyiDoc($) {
    my $id = shift;

    #
    # Each FYI entry will make reference to exactly one RFC (this may change
    # at a later time).  Since this is the case, no looping is necessary.
    #
    print "    <fyi-entry>\n";
    print "        <doc-id>$id</doc-id>\n";

    if ($docs{$id}{'SEE-ALSO'}) {
	print "        <is-also>\n";
	print "            <doc-id>$docs{$id}{'SEE-ALSO'}</doc-id>\n";
	print "        </is-also>\n";
    }

    print "    </fyi-entry>\n";
}

#
# Function to output an 'std-entry' element given a doc-id.
#
sub stdDoc($) {
    my $id = shift;

    #
    # An STD is often a composition of more than one RFC.  For that reason,
    # it bears an title of its own.  We will output the <title> in XML.
    #
    print "    <std-entry>\n";
    print "        <doc-id>$id</doc-id>\n";
    print "        <title>$docs{$id}{'TITLE'}</title>\n";

    if ($docs{$id}{'SEE-ALSO'}) {
	print "        <is-also>\n";

    	#
    	# Loop through the elements in the SEE-ALSO field because there may
	# be more than one.  Also note the distinction being made between
	# is-also and see-also.  In this case, an STD /is/ made up of RFCs.
    	#
    	my @isalso = sort split(', ', $docs{$id}{'SEE-ALSO'});
    	foreach my $doc (@isalso) {
	       
 	    print "            <doc-id>$doc</doc-id>\n";
	}

    	print "        </is-also>\n";
    }

    print "    </std-entry>\n";
}

#
# Function to output an 'rfc-not-issued-entry' element given a doc-id.
#
sub rfcNotIssuedDoc($) {
    my $id = shift;

    print "    <rfc-not-issued-entry>\n";
    print "        <doc-id>$id</doc-id>\n";
    print "    </rfc-not-issued-entry>\n";
}

#
# Function to output an 'rfc-entry' element.  This element will include the
# fields specified in rfc-index.txt.  These include:
#
#	REQUIRED:	1. number (exactly one)
#			2. title (exactly one)
#			3. author (one or more)
#			4. date (exactly one)
#			5. format (one or more)
#			6. status (exactly one)
#
#	OPTIONAL:	1. obsoletes
#			2. obsoleted-by
#			3. updates
#			4. updated-by
#			5. see-also
#                       6. stream
#                       7. working group acronym
#                       8. area acronym
#
# If the RFC has errata, include a URL to the errata search page.
#
# See rfc-index.xsd for details of what is required and what order things must
# be in.
#
sub rfcDoc($) {
    my $id = shift;

    print "    <rfc-entry>\n";
    print "        <doc-id>$id</doc-id>\n";
    print "        <title>$docs{$id}{'TITLE'}</title>\n";

    #
    # Assume that authors are delimited by commas.  Print out each author in
    # its own <author> element.
    #
    my @authors = split (', ', $docs{$id}{'AUTHORS'});


    # Since the suffix following an author's name is delimited by a comma,
    # it will appear to be a separate author.  If it is one of the known
    # special suffix values (those which appear in %sp_suffix), then combine
    # it with the name appears before it
    #

     my %sp_suffix = (
        'II'            =>      'II',
        'III'           =>      'III',
        'et al.'        =>      'et al.'
     );


    for (my $j = 0; $j <= $#authors; $j++) {
        if ($j < $#authors && exists $sp_suffix{$authors[$j+1]}) {
            $authors[$j] = $authors[$j].', '.$authors[$j+1];    #add the special suffix to author
            $authors[$j+1] = 'SKIP';    #yy set next value to SKIP
        }
    }
    
    #
    # Since the suffix following an author's name is delimited by a comma,
    # it will appear to be a separate author.  If it is one of the known
    # suffix values (those which appear in %suffix), append it to the name
    # that precedes it.
    #
    my %suffix = (
	'Ed.'		=>	'Editor',
	'ed.'		=>	'Editor',
	'Eds.'		=>	'Editors',
	'eds.'		=>	'Editors',
	'WG Chair'	=>	'WG Chair',
	'Chair'		=>	'Chair'
    );

    #
    # Cycle through each of the authors in the list.  If the entry following
    # an author's name matches a title in th %suffix hash, output that entry
    # in the <title> subelement of the current <author>.
    #
    for (my $i = 0; $i <= $#authors; $i++) {
	next if $authors[$i] eq 'SKIP';	# ignore entry if = to SKIP

	print "        <author>\n";
	print "            <name>$authors[$i]</name>\n";

	#
	# Check if the following entry is a title.  If so, print it out and
	# flag it to be skipped during subsequent consideration.
	#
	if ($i < $#authors && exists $suffix{$authors[$i+1]}) {
	    print "            <title>$suffix{$authors[$i+1]}</title>\n";
	    $authors[$i+1] = 'SKIP';	# set next value to SKIP
	}

	print "        </author>\n";
    }

    #
    # Transform the date into a usable form and output as a date group. 
    # This will contain the elements month, day, year where day is
    # optional.
    #
    my @date = &standardizeDate($docs{$id}{'PUB-DATE'});
    print "        <date>\n";
    print "            <month>$date[0]</month>\n";
    print "            <day>$date[1]</day>\n" if $date[1] ne '';
    print "            <year>$date[2]</year>\n";
    print "        </date>\n";

   
    #
    # Assume that formats are comma-delimited.  Each file format should have
    # a corresponding char-count associated with it.  Print these out as
    # format groups.
    #
    my @fileformats = split(', ', $docs{$id}{'FORMAT'});
#    my @charcounts = split(', ', $docs{$id}{'CHAR-COUNT'});
    my $pagecount = $docs{$id}{'PAGE-COUNT'};

    # Make sure that each format has a corresponding char-count
#    if ($#fileformats != $#charcounts) {
#	print "ERROR ($id): mismatch in file-types and char-counts.\n";
#    }

    # Print out format groups.

    print "        <format>\n";
    for (my $i = 0; $i <= $#fileformats; $i++) {
#	print "        <format>\n";
	$fileformats[$i] = removeWhitespace($fileformats[$i]);
        #v3PDF is the new format introduced after the new format which is similar to PDF
        if ($fileformats[$i] eq 'v3PDF'){
            print "            <file-format>PDF</file-format>\n";
        }else {
            print "            <file-format>$fileformats[$i]</file-format>\n";
        }
	
        #print "        </format>\n";
    }

    print "        </format>\n";
    $pagecount = removeWhitespace($pagecount);
    print "        <page-count>$pagecount</page-count>\n";

    #
    # Print out a list of keywords associated with this document.
    #
    if ($docs{$id}{'KEYWORDS'}) {
	print "        <keywords>\n";
	my @keywords = split(', ', $docs{$id}{'KEYWORDS'});
	foreach my $entry (@keywords) {
	    if ($entry =~ /\[/i)
	    {
	    	my @entries = split(/\|/, $entry);
		$entry = $entries[0];
		$entry =~ s/\[//g;
	    }
	    print "            <kw>$entry</kw>\n" unless $entry =~ /--/i;
	}
	print "        </keywords>\n";
    }

    #
    # Output abstract into a single element as-is.  Also output any notes
    # associated with an entry in a similar fashion.  Note: the "notes"
    # field may not be useful to the public, so we may consider removing it
    # from the XML instance in the future.
    #
    if ($docs{$id}{'ABSTRACT'}) {
	my $strippedAbstract;
	$strippedAbstract = &abstractCleanup($docs{$id}{'ABSTRACT'});
	if ($strippedAbstract =~ /<br>/ || $strippedAbstract =~/&lt;br&gt;/)
	{
	    print "        <abstract><p>";
	    $strippedAbstract =~ s/\r\n/ /g;
	    $strippedAbstract =~ s/<br>/<\/p><p>/ig;
	    $strippedAbstract =~ s/&lt;br&gt;/<\/p><p>/ig;
	    $strippedAbstract =~ s/^\s*(.*)\s*$/$1/;
	    $strippedAbstract =~ s/ +/ /g;
	    print "$strippedAbstract</p></abstract>\n";
	}
	else
	{
	  print "        <abstract><p>$strippedAbstract</p></abstract>\n";
        }

    }

    if ($docs{$id}{'DRAFT'}) {
	print "        <draft>$docs{$id}{'DRAFT'}</draft>\n";
    }

    foreach my $rel (@relation_order) {
        &tracer ("rfcDoc: checking \%Relations $rel\n");
	if ($docs{$id}{$rel}) {
		print "        <$Relations{$rel}>\n";
		my @reldocs = split(', ', $docs{$id}{$rel});
		foreach my $entry (@reldocs) {
	   		$entry = &removeWhitespace($entry);
	   	 	print "            <doc-id>$entry</doc-id>\n";
		}
		print "        </$Relations{$rel}>\n";
	}
    }

    #
    # Print out a list of see-also and is-also document references.
    #
    if ($docs{$id}{'SEE-ALSO'}) {
	my @seealso_field = sort split(', ', $docs{$id}{'SEE-ALSO'});
	my (@isalso, @seealso);

        &tracer ("$docs{$id}{'SEE-ALSO'} became @seealso_field\n");

	#
	# If a SEE-ALSO reference points to a BCP, FYI, or STD, we can
	# assume that the RFC /is also/ one of the subseries documents. 
	# However, if the SEE-ALSO field points to another RFC (or an
	# outside document type), it is likely that this reference is
	# informative (the read should /see also/ the other document).
	#
	foreach my $entry (@seealso_field) {
            &tracer ("rfcDoc: SEE-ALSO list entry $entry\n");
	    $entry = &removeWhitespace($entry);
	    if ($entry =~ /^(BCP|FYI|STD)/) {
		push @isalso, $entry;
	    } else {
		push @seealso, $entry;
	    }
	}

	#
	# Output <is-also> references.
	#
	if (@isalso) {
	    print "        <is-also>\n";
	    foreach my $doc (@isalso) {
		print "            <doc-id>$doc</doc-id>\n";
	    }
	    print "        </is-also>\n";
	}

	#
	# Output <see-also> references.
	#
	if (@seealso) {
	    print "        <see-also>\n";
	    foreach my $doc (@seealso) {
		print "            <doc-id>$doc</doc-id>\n";
	    }
	    print "        </see-also>\n";
	}
    }

    #
    # Standardize the value of the status fields and then output them.
    #
    my $status = $statusMap{$docs{$id}{'STATUS'}};
    print "        <current-status>$status</current-status>\n";
    my $pubstatus = $statusMap{$docs{$id}{'PUB-STATUS'}};
    print "        <publication-status>$pubstatus</publication-status>\n";

#    &tracer("---> rfcDoc: $docs{$id}{'SOURCE'}\n");
#    if ($docs{$id}{'SOURCE'} eq 'Legacy') {
#        print "        <stream>Legacy</stream>\n";
#    } elsif ($docs{$id}{'SOURCE'} eq 'IAB') {
#        print "        <stream>IAB</stream>\n";
#    } elsif ($docs{$id}{'SOURCE'} eq 'IRTF') {
#        print "        <stream>IRTF</stream>\n";
#    } elsif ($docs{$id}{'SOURCE'} eq 'INDEPENDENT') {
#        print "        <stream>INDEPENDENT</stream>\n";
#    } else {
#        print "        <stream>IETF</stream>\n";
#        if ($docs{$id}{'AREA_ACRONYM'}) {
#            print "        <area>$docs{$id}{'AREA_ACRONYM'}</area>\n";
#            &tracer("---> rfcDoc: $docs{$id}{'AREA_ACRONYM'}\n");
#        }
#        if ($docs{$id}{'WG_ACRONYM'} eq 'IETF-NWG') {
#            print "        <wg_acronym>NON WORKING GROUP</wg_acronym>\n";
#            &tracer("---> rfcDoc: NON WORKING GROUP\n");
 #       } else {
 #           print "        <wg_acronym>$docs{$id}{'WG_ACRONYM'}</wg_acronym>\n";
 #           &tracer("---> rfcDoc: $docs{$id}{'WG_ACRONYM'}\n");
 #       }
 #   }


    &tracer("---> rfcDoc: $docs{$id}{'SSP_ID'}\n");
    if ($docs{$id}{'SSP_ID'} eq '3') {
        print "        <stream>IAB</stream>\n";
    } elsif ($docs{$id}{'SSP_ID'} eq '4') {
        print "        <stream>IRTF</stream>\n";
    } elsif ($docs{$id}{'SSP_ID'} eq '6') {
        print "        <stream>INDEPENDENT</stream>\n";
    } elsif ($docs{$id}{'SSP_ID'} eq '8') {
        print "        <stream>Editorial</stream>\n";
    } else {
	if ($docs{$id}{'SOURCE'} eq 'Legacy') {
	        print "        <stream>Legacy</stream>\n";
        } else { 
	        print "        <stream>IETF</stream>\n";
	        if ($docs{$id}{'AREA_ACRONYM'}) {
	            print "        <area>$docs{$id}{'AREA_ACRONYM'}</area>\n";
        	    &tracer("---> rfcDoc: $docs{$id}{'AREA_ACRONYM'}\n");
	        }
	        if ($docs{$id}{'WG_ACRONYM'} eq 'IETF-NWG') {
        	    print "        <wg_acronym>NON WORKING GROUP</wg_acronym>\n";
	            &tracer("---> rfcDoc: NON WORKING GROUP\n");
	        } else {
	            print "        <wg_acronym>$docs{$id}{'WG_ACRONYM'}</wg_acronym>\n";
	            &tracer("---> rfcDoc: $docs{$id}{'WG_ACRONYM'}\n");
        	}
	 }
    }

    my $errata_exists = &errataCheck($id);
    if($errata_exists != 0)
    {
        my $rfcnum = substr($id,3);
        &tracer("---> rfcDoc: $id -> $rfcnum\n");
	my $errata_url=sprintf("https://www.rfc-editor.org/errata/rfc%d",$rfcnum);
	print "        <errata-url>$errata_url</errata-url>\n";
    }

    if ($docs{$id}{'DOI'}) {
        print "        <doi>$docs{$id}{'DOI'}</doi>\n";
    }
    
    print "    </rfc-entry>\n";
}

#
# Function to convert input date into a standard form and then return an
# array containing month, day, year to the caller.
#
sub standardizeDate {
    my $input = $_[0];
    my @date;

    &tracer (" --> standardizeDate: $input\n");
    my ($year,$month,$day) = split(/\-/,$input);
    $date[0] = $month_names[$month];
    if ($month == 4 && $day == 1) {
        $date[1] = 1; # April Fools Day RFC, else no day used.
    } else {
        $date[1] = '';
    }
    $date[2] = $year;
    &tracer (" --> Return @date\n");
    return @date;
}

#
# Iterate over one row and copy into the %docs hash. If the row was selected
# for one of the sub series types, do bizarre and crazy things to make the
# output right, because the database schema is so far off the mark with its
# business model that you can't rely on it.
#
sub addrow {
    my $row_ref = $_[0];
    my $doc_type = $_[1];
    my $doc_id = $row_ref->{'DOC-ID'};
    my $hashref = {}; # new hash for this row

    while ( my ($key, $value) = each %$row_ref) {
        if ($value) {
            $value = &replaceEntities(&trim($value));
            &tracer ("$doc_id: $key => $value\n");
        } else {
            &tracer ("$doc_id: $key => NULL\n");
        }
        if ($key eq 'DOC-ID') { # cannot be NULL
            $doc_id = &docidxsd($value);
        }
        $hashref->{$key} = $value;
    }

    if ($doc_type eq 'RFC') {
        # Add when we're running through RFCs
        $docs{$doc_id} = $hashref; # Store globally
        &tracer ("-> addrow: Added RFC \$doc_id $doc_id to \$docs\n");
    } elsif ($doc_type eq 'BCP' || $doc_type eq 'FYI' || $doc_type eq 'STD') {
        # if this sub series is already in the list, see if we need to add any
        # of the "See Also" records of this record
        &tracer ("-> addrow: working on $doc_type series with $doc_id\n");
        if ($doc_type eq $hashref->{'TYPE'}) {
            # This is the main, and first, record for this sub series.
            &tracer ("--> Adding $doc_id to \$docs hash\n");
            $docs{$doc_id} = $hashref;
        } else {
            # This is an RFC record with a SEE-ALSO to this sub series.
            my $sub_id = $hashref->{'SEE-ALSO'};
            if (exists $docs{$sub_id}) {
                &tracer ("--> addrow: $sub_id key in \$docs\n\tchecking for additions to $docs{$sub_id}{'SEE-ALSO'}\n");
                my @seealsos = split ', ', $hashref->{'SEE-ALSO'};
                foreach (@seealsos) {
                    &tracer ("---> addrow: Checking for $_ in $docs{$sub_id}{'SEE-ALSO'}\n");
                    if ($_ ne $sub_id && (index $docs{$sub_id}{'SEE-ALSO'}, $_) == -1) {
                        &tracer ("---> addrow: Adding $_ to $docs{$sub_id}{'SEE-ALSO'}\n");
                        $docs{$sub_id}{'SEE-ALSO'} = join ', ',$docs{$sub_id}{'SEE-ALSO'}, $_;
                    }
                }
                if ((index ($docs{$sub_id}{'SEE-ALSO'}, $doc_id)) == -1) {
                    &tracer ("----> Adding $doc_id to $docs{$sub_id}{'SEE-ALSO'}\n");
                    $docs{$sub_id}{'SEE-ALSO'} = join ', ',$docs{$sub_id}{'SEE-ALSO'}, $doc_id;
                }
                &tracer ("--> addrow: new value SEE-ALSO: $docs{$sub_id}{'SEE-ALSO'}\n");
            } else {
                &tracer ("--> addrow: $sub_id key NOT in \$docs\n");     
                # We have an RFC record with a SEE ALSO for this type
                &tracer ("--> addrow: working on $doc_type series with $sub_id\n");
                &tracer ("--> addrow: Overriding type $hashref->{'TYPE'} with $doc_type\n");
                $hashref->{'TYPE'} = $doc_type;
                &tracer ("--> addrow: Swapping $doc_id into $sub_id SEE-ALSO field\n");
                $hashref->{'SEE-ALSO'} = $doc_id;
                &tracer ("--> addrow: Overriding \$docs key $doc_id with $sub_id\n");
                $doc_id = $sub_id;
                $docs{$doc_id} = $hashref; # Store globally
                &tracer ("--> addrow: Added \$doc_id $doc_id to \$docs\n");
            }
        }
    } else {
            print STDERR "?? addrow: Unexpected \$doc_type <$doc_type>\n";
    }
    &tracer ("-> addrow: finishd processing $doc_id\n");
    return $doc_id;
}

#
# Function to replace special characters with their appropriate entities.
#	Ex: 	& -> &amp;
#
sub replaceEntities($) {
    my $value = shift;
    $value =~ s/\&/\&amp\;/g;
    $value =~ s/>/\&gt\;/g;
    $value =~ s/</\&lt\;/g;
    return $value;
}

# Unfortunately, xsd specifies r digits in docid. This function
# enforces that.
#
sub docidxsd {
    my $docidA;
    my $docidN;
    $docidA = $docidN = $_[0];
    $docidA =~ s/\d+//;
    $docidN =~ s/[A-Za-z]+//;
    my $docid = sprintf "%3s%04d", $docidA, $docidN;
    return $docid;
}

#
#
# Function to remove whitespace (both tabs and spaces) that appear anywhere
# in the input.  Return result as output.
#
sub removeWhitespace($) {
    my $word = shift;

    $word =~ s/\s//g;	# remove all spaces
    $word =~ s/\t//g;	# remove all tabs

    return $word;
}

#
# Process the text of an abstract so it conforms to the stype of the INDEX text
# file database. Code lifted from generate_INDEX_from_db.pl version of July
# 2009.
#
sub abstractCleanup {
    my $abstract = $_[0];
    
    # Replace 2 or more CR/LF pairs with <br/>
    $abstract =~ s/(\s*\r\n){2,}/\<br\> /g;
    # Replace single CR/LF with space, so text flows.
    $abstract =~ s/\r\n/ /g;
    # Remove multiple spaces
    $abstract =~ s/(\s){2,}/ /g;
    # Insert two spaces after inter-sentence period
    $abstract =~ s/[.] (\w)/\.  $1/g;
    # Remove leading and trailing spaces again, in case.
    $abstract =~ s/^\s//;
    $abstract =~ s/\s$//; 
    # Remove gratuitous CRs
    $abstract =~ s/\r//;

    return $abstract;
}

#
# Query the database to see in a document has errata records.
# Note: Assumes that a database connection is open.
#
sub errataCheck {
    my $query = qq{
         SELECT count(*) FROM errata WHERE `doc-id`='$_[0]'};
    my $errata_check = $dbh->prepare($query);
    $errata_check->execute() 
        or die "Couldn't execute query '$query': $DBI::errstr\n";
    my $count;
    $errata_check->bind_columns(\$count);
    $errata_check->fetch;
    if ($count == 0) {
        &tracer("-----> $_[0] does not have errata\n");
    } else {
        &tracer("-----> $_[0] has errata\n");
    }
    return $count;
}

#
# Simple execution trace. Writes its argument to STDERR if the debug flag is
# set.
#
sub tracer {
    if ($debug_xmlIndex)  {
        my $msg = $_[0];
        print STDERR $msg;
    }
}
