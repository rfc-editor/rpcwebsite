#!/usr/bin/perl
# $Id: generate_INDEX_from_db.pl,v 2.1 2012/03/13 18:14:47 priyanka Exp $
####################################################################
#                                                                  #
# Script	: generate_INDEX_from_db.pl      	           #
# Author	: Deepa Prakash <dprakash@isi.edu>		   #
# Date  	: October 26, 2005			           #
# Desc		: This script generates INDEX file.                #  
# Changes     Oct 2007: Completely rewritten by Bob Braden, to     #
#               fix several bugs, add consitency checks and        #
#               error reports, and make it much more maintainable  #
#             Jan 2010: rcross: fixed hardcoded hostname           #
#                                                                  #
####################################################################

# The path in the should be fixed to be absolute but script bombs when 
# the path is fixed; looks like mismatch between DBI versions - AF
# DBI installed for system on bosco -- CW
#use lib "~/MyPerlScripts";
#use DBI qw(:sql_types);
##use CGI::Carp qw(fatalsToBrowser);
use Sys::Hostname;

$host = hostname();
$basedir = "/home/rfc-ed/";
$base =    "/home/rfc-ed/bin";
$logdir = "$basedir/logs/generate_INDEX_from_db.log";

#  Where to put INDEX file:
#
$index = "/home/rfc-ed/INDEX";

$db_file = "$basedir/bin/database_information.txt";
use DBI;

use IO::File;
use File::Basename;

$! = 0;                 #  Clear $! from previous error code
$errors = "";           #  No error message string


# Open INDEX file for writing
#
open (INDEX1, ">$index") or die "Couldn't open $index for writing";
print INDEX1 "--*EOR*--\n";

#print "- Connecting to the MySQL Database: rfc-ed\n";

require ("$base/tvf_rfc_lib.pl");
($db_user, $db_passwd, $DSN) = &get_db_parameters($db_file);

$dbh = DBI->connect($DSN,$db_user,$db_passwd,
    {RaiseError => 0, AutoCommit =>0 } ) ||
        die "$0: Cannot connect to database: $DBI::errstr\n";

#####################################################################
##                                                                 ##
##			BCPs                                       ##
##                                                                 ##
#####################################################################

    #  Define return codes from common routine process_SSS()
    ($UseSSS, $UseSSSandRepeat, $UseRFC, $Ignore) = (2, 1, 0, -1);

    $SSS = "BCP";

    $sss_name = "\"%$SSS%\"";
    &init_SSS($SSS);  # Build hash table of SEE-ALSO fields per SSS docid

    $sql = "SELECT * FROM `index` WHERE `SEE-ALSO` LIKE $sss_name ORDER BY `SEE-ALSO` ASC";
    $statement = $dbh->prepare($sql)
		or die "Couldn't prepare query '$sql' : $DBI::errstr\n";
    $statement->execute()
		or die "Couldn't execute query '$sql': $DBI::errstr\n";

    while ($rfc_ref = $statement->fetchrow_hashref()) {

 	$result = &process_SSS($rfc_ref);
   	while ($result == int $UseSSSandRepeat) {
		set_up_fields($sss_ref);		
		$abstract = '';
		$draft= '';	    
		$email= '';
		make_record();		
		$result = int &process_SSS($rfc_ref);
	}
	if ($result == $Ignore) {
		next;    ## Ignore: would be duplicate sub-series record
	}
	elsif ($result == $UseSSS) {
		set_up_fields($sss_ref);  # Adapt from SSS entry
	}
	else {
	    # No explicit entry.  Copy RFC entry and then adjust some fields
	    #
		set_up_fields($rfc_ref);

		$type = $SSS;
		$doc_id = $sss_doc;
		$see_also = $rfc_ref->{'DOC-ID'};
	}
	$draft= '';	    
	$email= '';

	##  Finally, output BCP record into INDEX
	##
	make_record();
    }

#   print " - Created $rec_no $SSS records in INDEX ...\n";

#####################################################################
##                                                                 ##
##			FYIs                                       ##
##                                                                 ##
#####################################################################



#  We assume at present that there are no explicit FYI entries in
#  the dBase, since an FYI can only be one RFC.

#  For each (RFC) entry that has a FYInnnn in the SEE-ALSO field,
#  construct a corresponding FYI record in INDEX.


    $sql = "SELECT * FROM `index` WHERE `SEE-ALSO` LIKE \"%FYI%\" ORDER BY `SEE-ALSO` ASC";
    $statement = $dbh->prepare($sql)
		or die "Couldn't prepare query '$sql' : $DBI::errstr\n";
    $statement->execute()
		or die "Couldn't execute query '$sql': $DBI::errstr\n";
    $rec_no=0;

    while ($rfc_ref = $statement->fetchrow_hashref()) {
      
	$fyidoc = $rfc_ref->{'SEE-ALSO'};

	set_up_fields($rfc_ref);
	$type = "FYI";
	$doc_id = $fyidoc;
	$see_also = $rfc_ref->{'DOC-ID'};
	$pub_status = 'FYI';    # Not sure why this anomaly
	$status = 'FYI';    
	$draft= '';	    
	$email= '';
	make_record();
    }

#   print " - Created $rec_no FYI records in INDEX ...\n";

#####################################################################
##                                                                 ##
##			RFCs                                       ##
##                                                                 ##
#####################################################################

    $sql = "SELECT * FROM `index` WHERE `TYPE`= \"RFC\" AND `state_id`= \"14\" AND \`DOC-ID\` <> \"0\" AND \`DOC-ID\` <> '' ORDER BY `DOC-ID` ASC";
    $statement = $dbh->prepare($sql)
		or die "Couldn't prepare query '$sql' : $DBI::errstr\n";
    $statement->execute()
		or die "Couldn't execute query '$sql': $DBI::errstr\n";
    $rec_no=0;

    while ($rfc_ref = $statement->fetchrow_hashref()) {
	set_up_fields($rfc_ref);
	make_record();
    }

#   print " - Created $rec_no RFC records in INDEX ...\n";

#####################################################################
##                                                                 ##
##			STDs                                       ##
##                                                                 ##
#####################################################################


    $SSS = "STD";
    $sss_name = "\"%$SSS%\"";
    &init_SSS($SSS);  # Build hash table of SEE-ALSO fields per SSS docid

    ## Scan all (RFC) entries in dBase whose SEE-ALSO contains an STD
    ##
    $sql = "SELECT * FROM `index` WHERE `SEE-ALSO` LIKE $sss_name ORDER BY `SEE-ALSO` ASC";
    $statement = $dbh->prepare($sql)
		or die "Couldn't prepare query '$sql' : $DBI::errstr\n";
    $statement->execute()
		or die "Couldn't execute query '$sql': $DBI::errstr\n";

    while ($rfc_ref = $statement->fetchrow_hashref()) {

 	$result = &process_SSS($rfc_ref);
   	while ($result == int $UseSSSandRepeat) {
		set_up_fields($sss_ref);		
		$draft= '';	    
		$email= '';
		make_record();		
		$result = int &process_SSS($rfc_ref);
	}
	if ($result == $Ignore) {
		next;    ## Ignore: would be duplicate sub-series record
	}
	elsif ($result == $UseSSS) {
		set_up_fields($sss_ref);  # Adapt from SSS entry
	}
	else {
	    # No explicit entry.  Copy RFC entry and then adjust some fields
	    #
		set_up_fields($rfc_ref);

		$type = $SSS;
		$doc_id = $sss_doc;
		$see_also = $rfc_ref->{'DOC-ID'};
	}
	$draft= '';	    
	$email= '';

	##  Finally, output BCP record into INDEX
	##
	make_record();
    }

#   print " - Created $rec_no $SSS records in INDEX ...\n";


#####################################################################
##                                                                 ##
##			IENs                                       ##
##                                                                 ##
#####################################################################


    $sql = "SELECT * FROM `index` WHERE `DOC-ID` LIKE \"%IEN%\" ORDER BY `DOC-ID` ASC";

    $statement = $dbh->prepare($sql)
		or die "Couldn't prepare query '$sql' : $DBI::errstr\n";
    $statement->execute()
		or die "Couldn't execute query '$sql': $DBI::errstr\n";
    $rec_no=0;

    while ($row_ref = $statement->fetchrow_hashref()) {

	## Set up standard set of dBase fields, then override a few
	##
	set_up_fields($row_ref);

	#print all the fields to a new record of the INDEX file.
	make_record();

     }

#   print " - Created $rec_no IEN records in INDEX ...\n";



#####################################################################
##                                                                 ##
##	Error message and logging                                  ##
##                                                                 ##
#####################################################################


#print "- Disconnecting from the MySQL Database: rfc-ed\n";

$statement->finish;
$statement1->finish;
$dbh->disconnect;

#If there is an error then an email is sent to rfc-editor@rfc-editor.org
#stating the error. If the script executes successfully, then the script
#with date and the time at which it ran is logged in the file 
# ~rfc-ed/logs/generate_INDEX_from_db.log.
#While accessing MySQl data base by means of perl the value 
#of variable $! sometimes equals " Resource temporarily unavailable". 
#At the mean time everything works correct.

if( (($!) && ($! ne "Resource temporarily unavailable")) || $errors) {
##  $subject = "Messages from script: generate_INDEX_from_db.pl";
    $body .= "$host: generate_INDEX_from_db.pl:\n";
    $body .= "$errors\n $!";

#   print "$body\n";

##   unless(open (MAIL, "|/usr/sbin/sendmail -t")) {
##	print "error.\n";
##	warn "Error starting sendmail: $!";
##    }
##    else{
##	print MAIL "From: rfc-editor\@rfc-editor.org\r\n";
####	print MAIL "To: rfc-editor\@rfc-editor.org\r\n";
##	print MAIL "Subject: $subject\n\n";
##	print MAIL "$body";
##	close(MAIL) || warn "Error closing mail: $!";
##    }
}

##  Now get time/date and log it.
    ($log_sec,$log_min,$log_hour,$log_mday,$log_mon,$log_year,
                      $log_wday,$log_yday,$log_isdst) = localtime(time);
	
    $year = $log_year + 1900;
    $mon=$log_mon+1;
    $log_str="generate_INDEX_from_db.pl: $year/$mon/$log_mday: $log_hour:$log_min:$log_sec";

    open(LOG, ">>$logdir") || die ("can't open log file $!");
    print LOG "$log_str\n";
    if ($errors) { 
	print LOG "$errors\n";
    }
    close(LOG);

#############################################################
##		Extract fields from specific dBase entry
##

sub set_up_fields {
	my @month_name = ("", "January", "February", "March", "April",
			"May", "June", "July", "August", "September",
			"October", "November", "December");
	my $row_ref = $_[0];

	$type =  $row_ref->{'TYPE'};
        $doc_id = $row_ref->{'DOC-ID'};
	$doc_id_pfx = $doc_id;
	$doc_id_pfx =~ s/\d*//;
	if ($doc_id_pfx != $type) {
		$errors .= "Bad DOC-ID: $doc_id\n";
		$doc_id =~ s/[A-Z]*//;
		$doc_id = $type . $doc_id;
	}
        $title = $row_ref->{'TITLE'};
	$title =~ s/^\s//; 		# Remove leading, trailing blanks
	$title =~ s/\s$//; 
   	$title =~ s/\r//;		# Remove gratuitous CRs

        $authors = $row_ref->{'AUTHORS'};
	$authors =~ s/^\s//; 		# Remove leading, trailing blanks
	$authors =~ s/\s$//; 

        $date = $row_ref->{'PUB-DATE'};
	($year,$month,$day) = split(/\-/,$date);
	#  Print the date as MONTH YEAR and not as YYYY-MM-DD as in the 
	#  database.
	$monthno = int $month;
	$amonth = $month_name[$month];
	if($year eq "0000") {
	    $adate = "";
	}
	elsif ($amonth eq "April" && $day == 1) {
	    	$adate = $amonth." 1 ".$year;
	}
	else {
	    $adate = $amonth." ".$year;
	}
        $keywords = $row_ref->{'KEYWORDS'};
        $organization = $row_ref->{'ORGANIZATION'};
        $format = $row_ref->{'FORMAT'};
        $char_count = $row_ref->{'CHAR-COUNT'};
        $page_count = $row_ref->{'PAGE-COUNT'};
        $queries = $row_ref->{'QUERIES'};
        $last_query =  $row_ref->{'LAST-QUERY'};
        $responses = $row_ref->{'RESPONSES'};
        $last_response = $row_ref->{'LAST-RESPONSE'};
        $notes = $row_ref->{'NOTES'};
        $notes =~ s/\r\n\r*\n*/\, /g;
	$notes =~ s/\s\s\s\s\s*//g;

        $obsoletes = $row_ref->{'OBSOLETES'};
        $obsoleted_by = $row_ref->{'OBSOLETED-BY'};
        $updates = $row_ref->{'UPDATES'};
        $updated_by = $row_ref->{'UPDATED-BY'};
        $see_also = $row_ref->{'SEE-ALSO'};
	$pub_status =  $row_ref->{'PUB-STATUS'};
        $status = $row_ref->{'STATUS'};

        $abstract = $row_ref->{'ABSTRACT'};
        	# Remove leading and trailing spaces
	$abstract =~ s/^\s//;
	$abstract =~ s/\s$//; 
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

	$draft= $row_ref->{'DRAFT'};
	$email= $row_ref->{'EMAIL'};
	$source = $row_ref->{'SOURCE'};
}

sub make_record {
		#print all the fields to the INDEX file.
	print INDEX1 "TYPE: $type\n";
        print INDEX1 "DOC-ID: $doc_id\n";
        print INDEX1 "TITLE: $title\n";
        print INDEX1 "AUTHORS: $authors\n";
        print INDEX1 "DATE: $adate\n"; 
	print INDEX1 "KEYWORDS: $keywords\n";
        print INDEX1 "ORGANIZATION: $organization\n";
        print INDEX1 "FORMAT: $format\n";
        print INDEX1 "CHAR-COUNT: $char_count\n";
        print INDEX1 "PAGE-COUNT: $page_count\n";
        print INDEX1 "QUERIES: $queries\n";
        print INDEX1 "LAST-QUERY: $last_query\n";
        print INDEX1 "RESPONSES: $responses\n";
        print INDEX1 "LAST-RESPONSE: $last_response\n";
        print INDEX1 "NOTES: $notes\n";
        print INDEX1 "OBSOLETES: $obsoletes\n";
        print INDEX1 "OBSOLETED-BY: $obsoleted_by\n";
        print INDEX1 "UPDATES: $updates\n";
        print INDEX1 "UPDATED-BY: $updated_by\n";
        print INDEX1 "SEE-ALSO: $see_also\n";
        print INDEX1 "PUB-STATUS: $pub_status\n";
        print INDEX1 "STATUS: $status\n";
	print INDEX1 "ABSTRACT: $abstract\n";
        print INDEX1 "DRAFT: $draft\n";
        print INDEX1 "EMAIL: $email\n";
	print INDEX1 "SOURCE: $source\n";
        print INDEX1 "--*EOR*--\n";
	$rec_no++;
}

#
# Split list of document IDs into array
#
sub split_doclist {
	my $doclist = $_[0];
	$doclist =~ s/^\s*//;  # Remove leading, trailing blanks
	$doclist =~ s/\s*$//;  

	# Replace commas with blanks, delete multiple blanks, and split.
	$doclist =~ s/,/ /g;
	$doclist =~ s/\s+/ /g;
	return split " ", $doclist;
}

#####################################################################
##
##  Common Subroutines for Sub-Series (STD and BCP) that allow
##  multiple RFCs
##

#####################################################################
#
#  Initialize for processing sub-series SSS 
#
#  Do mySQL query to extract all SSS entries from dBase
#  Then save SEE-ALSO field of each entry in hash %sss_seealsos.
#

sub init_SSS {  # Input: global sss_name string
		# Output: global $sss_seealsos{ } hash

   $sql1 = "SELECT * FROM `index` WHERE `DOC-ID` LIKE  $sss_name ";
    $statement1 = $dbh->prepare($sql1) or die "Couldn't prepare query '$sql1' : $DBI::errstr\n";
    $statement1->execute() or die "Couldn't execute query '$sql1': $DBI::errstr\n";

    while($row_ref = $statement1->fetchrow_hashref()) {
	$sss_doc = $row_ref->{'DOC-ID'};
	$sss_seealsos{$sss_doc} = $row_ref->{'SEE-ALSO'};
    }
    $rec_no = 0;
	$sss_next = 1;
}

##################################################################
#
#  Process one RFC that has sub-series entry in SEE-ALSO field.
#  Called in order of sub-series docid's
#
#  Inputs: (parameter): reference to RFC dBase entry
#          (global): sss_doc: SSS DOC-ID from RFC entry
#  Outputs: (global): sss_ref when UseSSS or UseSSSandRepeat is returned.
#           Return codes:
#


sub process_SSS {

	## This one-pass algorithm cannot merge corresponding fields of
	## the multiple RFCs forming one SSS.

	$rfc_ref = $_[0];
	$rfc_docid = $rfc_ref->{'DOC-ID'};

	@rfc_seealsos = split_doclist($rfc_ref->{'SEE-ALSO'});
	if ($#rfc_seealso > 0) {
		$errors .= "WARNING: $rfc_docid has multiple SEE-ALSOs\n";
	}
	$sss_doc = $rfc_seealsos[0];   ## assume only one See-Also for now
	($sss_num = $sss_doc) =~ s/[A-Z]*//i;

	while ($sss_num > ($sss_prev+1)) {
		# There are (probably) one or more "idle" SSS entries
		# intervening. If so, create record(s) for them and
		# ask for a repeat of the same $rfc_ref, thus merging
		# the "idle" SSS entries into the sequence of records.

		$sss_prev++;
		$sss_doc1 = sprintf "$SSS%4.4d", $sss_prev;

		unless (exists $sss_seealsos{$sss_doc1}) {
			# There really is a gap in SSS series
			next;
		}

###		print "Idle $sss_doc1\n";
		# Fetch "idle" SSS entry and insert it here in order
		#
		$sql1 = "SELECT * FROM `index` WHERE `DOC-ID` = \"$sss_doc1\"";
    		$statement1 = $dbh->prepare($sql1) 
			or die "Couldn't prepare query '$sql1' : $DBI::errstr\n";
   		$statement1->execute()
			or die "Couldn't execute query '$sql1': $DBI::errstr\n";

		$sss_ref = $statement1->fetchrow_hashref();
   		if ($sss_seealsos{$sss_doc1} != "") {
			$errors .= "ERROR: SEE-ALSO reciprocity:$sss_seealsos{$sss_doc1} in $sss_doc1 \n";
		}
###		print "Repeat: $sss_prev\n";
		return $UseSSSandRepeat;
	}

	## Don't make a duplicate SSS record: ignore this later call.
	##
	if ($sss_num == $sss_prev) {
###		print "Ignore\n";
		return $Ignore;
	}
	$sss_prev = $sss_num;

	## If no actual dBase entry for this SS, construct one using
	## the RFC entry that pointed to it.

	unless (exists $sss_seealsos{$sss_doc}) {
###	    print "UseRFC $sss_doc\n";
	    return $UseRFC;
 	}

	# Check for matching docid in SSS seealso field
	#
	@sss2rfcV = split_doclist( $sss_seealsos{$sss_doc});
	$Match = 0;
	foreach $rfcdoc (@sss2rfcV) {
	    if ($rfcdoc !~ /RFC[0-9]{4,4}/i  ) {
		$errors .= "ERROR: bad See-Also $rfc_ref in $sss_doc\n";
	    }
	    if ($rfcdoc eq $rfc_docid) {
		$Match++;
	    }
	}

	if ($Match == 0) {
	    $errors .= "ERROR: SEE-ALSO reciprocity: $rfc_docid <> $sss_doc\n";
	}

	## It is not obvious what to do if the explicit BCP entry refers
	## to only one RFC; should the record be based on the RFC or on
	## the SSS entry?  We take the latter choice, with trepidation,
	## and with a warning message.

	if ($#sss2rfcV < 1) {
		$errors .= "NOTE: only one RFC for entry $sss_doc\n";
	}
	    ## There is an explict SSS entry in dBase, one of whose
	    ## SEE-ALSO fields points back to this RFC.  Re-fetch
	    ## SSS entry for caller to use to build SSS record

	$sql1 = "SELECT * FROM `index` WHERE `DOC-ID` = \"$sss_doc\"";

    	$statement1 = $dbh->prepare($sql1) 
		or die "Couldn't prepare query '$sql1' : $DBI::errstr\n";
   	$statement1->execute()
		or die "Couldn't execute query '$sql1': $DBI::errstr\n";

	$sss_ref = $statement1->fetchrow_hashref();
####	print "UseSSS $sss_doc\n";
   	return $UseSSS;
    }

