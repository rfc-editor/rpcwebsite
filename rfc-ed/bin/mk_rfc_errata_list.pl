#!/usr/bin/perl
# $Id: mk_rfc_errata_list.pl,v 2.0 2010/03/15 22:50:45 rcross Exp $

use DBI;
use IO::File;
use File::Basename;
use File::Copy;
use strict;

####################################################################
#                                                                  #
# Script        : mk_rfc_errata_list.pl                            #
# Author        : Craig Ward <cward@isi.edu>                       #
# Date          : October 2007                                     #
# Desc          : Read the rfc editor errata table and output the  #
#                 list of RFCs with errata records. The output can #
#                 then be used as input to other programs that     #
#                 generate links to errata entries in RFC lists.   #
# Consumers     : This script was used by xmlIndex.pl until it was #
#                 re-written to use the database directly. As of   #
#                 July 2009, only the legacy CGI search database   #
#                 uses the output of this script. External users   #
#                 may still access the file from the FTP           #
#                 repository.                                      #
# Usage         : mk_rfc_errata_list.pl <db_connection_info>       #
#                 Argument db_conneciton_info is a file with data  #
#                 needed to setup connection to the database.      #
# Modifications : See CVS log.                                     # 
####################################################################
my $iam  = basename($0);
my $base = dirname($0);
require ("$base/tvf_rfc_lib.pl");

my $query ="SELECT DISTINCT `doc-id` FROM errata";

# first argument name of file with connection info
my $db_file = shift || "./db_info";
my $db_user, my $db_passwd, my $DSN;
($db_user, $db_passwd, $DSN) = &get_db_parameters($db_file);

# Initialize connection to database
my $dbh = DBI->connect($DSN,$db_user,$db_passwd,
    {RaiseError => 0, AutoCommit =>0 } ) || 
        die "$iam: Cannot connect to database: $DBI::errstr\n";

my $sth = $dbh->prepare($query);
$sth->execute 
    or die "Couldn't execute query '$query': $DBI::errstr\n";

my $outfile="$base/RFCs_for_errata";
open(LIST, ">$outfile") or die "$iam: could not open $outfile for writing";


while (my @row = $sth->fetchrow_array) {
#    print $row[0]  . "\n";
#    printf("%d\n",substr($row[0],3));
    printf(LIST "%d\n",substr($row[0],3));
}
close(LIST);
$dbh->disconnect;

# Now copy the file into the FTP area for additional uses/users
my $ftpfile="/a/ftp/in-notes/RFCs_for_errata.txt";
copy($outfile, $ftpfile) or die "$iam: failed to copy $outfile to $ftpfile: $!";
