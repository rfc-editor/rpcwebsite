#!/usr/bin/perl
# $Id: tvf_rfc_lib.pl,v 2.1 2014/02/19 18:46:15 priyanka Exp $

####################################################################
#                                                                  #
# Script	: tvf_rfc_lib.pl				   #
# Author	: Ted Faber <faber@isi.edu>			   #
# Date  	: December 8, 2006			           #
# Desc		: routines used by a variety of RFC editor         #
#                 scripts.  Primarily data scrubbing and reading   #
#                 the database configuretion files.  See           # 
#                 individual routines for details.                 #
# Modifications :						   # 
#                                                                  #
####################################################################

# Strip the trailing dash and digits or pound signs from a draft
# name.  While we're here, skim out any whitespace that's snuck in.
sub strip_number {
    my($n) = @_[0];
    $n =~ s/\s//g;
    $n =~ s/-(\d\d|\#\#)$//;
    $n;
}

# For output formats that go to {ht,x}ml, strings need to be scrubbed to avoid
# confusing browsers.  <, >, and & need to be replaced with the appropriate
# escape sequences.
sub scrub_for_xml {
    my($val) = @_;
    $val =~ s/</\&lt;/g;
    $val =~ s/>/\&gt;/g;
    $val =~ s/\&/\&amp;/g;

    return $val;
}

# The refs field of the DB has some nonsense values in it that need
# to be ignored.  This takes a raw refs field from the DB and erases
# bad values.
sub scrub_refs_db {
    my($refs) = @_;
    $refs =~ s/\s//g;
    $refs =~ s/^NULL(\+N)?$//;
    $refs =~ s/^\+$//;
    return $refs;
}

# Individual references are of a form name+status.  The status is
# useless and should be ignored.  In some cases the -xx version
# number is also there and should be removed.
sub scrub_ref {
    my($ref) = @_;
    $ref =~ s/\+(.)?$//;
    $ref = &strip_number($ref);
    return $ref;
}

# Takes a file to read mysql parameters from and returns those parameters for
# the calling script to use.  Errors are died upon, so use eval to catch them.
# The file is of the format field: value one per line where valid fields are
# username, password, database.  For historical reasons, user is a synonym for
# database.  The routine reads the control file and returns a 3 tuple with
# (mysql_user, password, DSN (valid for a DB connect).
sub get_db_parameters {
    my($db_file) = @_;
    my($db_name) = "DBNAME";
    my($db_host) = "HOSTNAME";

    $db_file = "./db_info" unless $db_file;

    my($dbinfo) = new IO::File("$db_file") 
	|| die "$0: Can't open $db_file: $!\n";
    while (<$dbinfo>) {
	/username:(.*)/ && do {
	    ($db_user = $1) =~ s/\s//g;
	    next;
	};
	/password:(.*)/ && do {
	    ($db_passwd = $1) =~ s/\s//g;
	    next;
	};
	# The user field is for compatibility with the old rfc-editor scripts.
	/(database|user):(.*)/ && do {
	    ($db_name = $2) =~ s/\s//g;
	    next;
	};
    }
    $dbinfo->close();

    die "No user or passwd info in $db_file(!?)\n" 
	unless $db_user && $db_passwd;

    my($DSN) = "DBI:mysql:database=$db_name:host=$db_host";
    return ($db_user, $db_passwd, $DSN);
}

# Remove leading and trailing whitespace
sub trim {
    my $string = $_[0];
    $string =~ s/^\s+//;
    $string =~ s/\s+$//;
    return $string;
}

# Indicate successful load
1;
