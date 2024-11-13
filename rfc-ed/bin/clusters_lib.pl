#!/usr/bin/perl
# $Id: clusters_lib.pl,v 2.1 2012/03/13 18:14:47 priyanka Exp $
####################################################################
#                                                                  #
# Script	: clusters_lib.pl				   #
# Author	: Craig Ward <cward@isi.edu>			   #
# Date  	: February 19, 2009			           #
# Desc		: Routines for Perl scripts to access the database #
#                 for document cluster data.                       #
# Notes                                                            #
#  (1) The database handle is assumed to exist as a global (our).  #
#  (2) Assumes library with strip_number is already loaded.        #
####################################################################

# Look in cluster table for a document entry. Return the CID if draft
# is part of a cluster, else return 0.
sub get_cluster_id {
    my ($draft) = &strip_number($_[0]);
    print "\tChecking for $draft in clusters table: " if defined($cl_debug);

    my $query = qq{
        SELECT cluster_id, draft_base, anchored
        FROM `clusters` 
        WHERE draft_base = '$draft'};
    my $sth;
    eval {
        $sth = $dbh->prepare ( $query );
        $sth->execute;
    };
    die "$iam: Cannot prepare/execute $query\nError: $@" if ( $@ );

    print "query = $query\n" if defined ($cl_debug);

    my ($cid, $draft_base, $anchored);
    $sth->bind_columns(\($cid, $draft_base, $anchored));
    $sth->fetch;
    if ($sth->rows != 0) {
        print "entry exists: cid=$cid.\n" if defined($cl_debug);
        return $cid;
    }
    print "entry does not exist.\n" if defined($cl_debug);
    return 0;
}

# Format a URL to use the PHP script that displays cluster members.
sub make_cluster_info_url {
    my ($cid) = $_[0];
    $url = qq {<b><a href='cluster_info.php?cid=$cid'>[$cid]</a></b>};
    print "$url\n" if defined($cl_debug);
    return $url;
}

# Because not all drafts in the clusters table are in the index table, the
# SQL can't sort a draft list the way the application needs to present them.
# This routine sorts the returned result set to the needed ordering.
sub cmp_date_received() {
    my $a_date = &get_draft_date_received($a);
    my $b_date = &get_draft_date_received($b);
    if ($a_date gt $b_date) { return 1; }
    if ($a_date lt $b_date) { return -1; }
     return 0;
}

# This sort routine strips the initial "C" from cluster IDs so that the list
# can be sorted using just the number part.
sub cmp_cluster_ids() {
    my $cid_a = substr($a,1);
    my $cid_b = substr($b,1);
    if ($cid_a > $cid_b) { return 1; }
    if ($cid_a < $cid_b) { return -1; }
    return 0;
}

# Query the database to get the list of drafts participating in a cluster.
# The list is then resorted with published drafts first and not received yet
# drafts last.
sub get_cluster() {
    my $cid = $_[0];

    my $query = qq{SELECT draft_base FROM clusters 
                   WHERE cluster_id='$cid' ORDER BY draft_base};
    
    my $sth;
    eval {
        $sth = $dbh->prepare($query);
        $sth->execute;
    };
    die "$iam: Cannot prepare/execute $query\nError: $@" if ( $@ );

    my ($draft_base, $count, @drafts);
    $sth->bind_columns(\$draft_base);
    while ($sth->fetch) {
        $count = push @drafts, $draft_base;
    }
    # This sort really slows down the program, but it allows the ordering to
    # match the public web page ordering. The extra work is a work-around
    # needed because draft_base is not a foreign key on index. Drafts not
    # received will not be in index.
    return sort cmp_date_received @drafts;
}

# Query the database for the list of active clusters. The list is resorted
# so that the initial "C" of the cluster ID is ignored and only the remaining
# number used.
sub get_active_cluster_list() {
    #my $query = qq{
    #    SELECT cluster_id
    #    FROM `index` i, clusters c
    #    WHERE state NOT IN ('PUB', 'DNP', 'REPL', 'WITHDRAWN') 
    #      AND `doc-id` IS NOT NULL 
    #      AND ref IS NOT NULL 
    #      AND ref <> ' '
    #      AND draft LIKE CONCAT(draft_base,'-__')
    #    GROUP BY cluster_id};

   my $query = qq{
	SELECT cluster_id
        FROM `index` i, clusters c
        WHERE i.state_id NOT IN ('14', '3', '16', '19')
          AND `doc-id` IS NOT NULL
          AND ref IS NOT NULL
          AND ref <> ' '
          AND draft LIKE CONCAT(draft_base,'-__')
        GROUP BY cluster_id};

    my $sth;
    eval {
        $sth = $dbh->prepare ( $query );
        $sth->execute;
    };
    die "$iam: Cannot prepare/execute $query\nError: $@" if ( $@ );

    my ($cid, $count, @clusters);
    $sth->bind_columns(\$cid);
    while ($sth->fetch) {
        $count = push @clusters, $cid;
    }
    return sort cmp_cluster_ids @clusters;
}

# Retrieve from the database the relevent columns for a draft.
# NOTE: This routine only retrieves data, it does not munge it for display.
sub get_draft_data() {
    my ($draft_base) = &strip_number($_[0]);
#    my $query = qq{
#        SELECT draft, date_received, state, authors, title, `char-count`,
#               ref, source,`time-out-date`, `pub-status`, `doc-id`,
#               anchored,
#           CASE
#	      WHEN SOURCE='INDEPENDENT' THEN 1
#	      ELSE 0
#           END indep
#        FROM `index`, clusters
#        WHERE state NOT IN ('DNP', 'REPL', 'WITHDRAWN') 
#            AND draft LIKE CONCAT('$draft_base','-__')
#            AND draft_base = '$draft_base'};


    my $query = qq{
        SELECT i.draft, i.date_received, 
	       CONCAT( s.state_name,CASE WHEN i.iana_flag = "1"THEN "*A"ELSE ""END ,CASE WHEN i.ref_flag = "1"THEN "*R"ELSE ""END,
               CASE WHEN i.generation_number = "1" THEN "(1G)" WHEN i.generation_number = "2" THEN "(2G)" WHEN i.generation_number = "3" THEN "(3G)" WHEN i.generation_number = "0" THEN "" END) as STATE,
               i.authors, i.title, i.`char-count`,
               i.ref, i.source,i.`time-out-date`, i.`pub-status`, i.`doc-id`,
               c.anchored,
           CASE
              WHEN i.SOURCE='INDEPENDENT' THEN 1
              ELSE 0
           END indep
        FROM `index` i , `clusters` c, `states` s 
        WHERE i.state_id NOT IN ('3', '16', '19')
            AND i.state_id = s.state_id
            AND i.draft LIKE CONCAT('$draft_base','-__')
            AND c.draft_base = '$draft_base'};


    my $sth;
    eval {
        $sth = $dbh->prepare ( $query );
        $sth->execute;
    };
    die "$iam: Cannot prepare/execute $query\nError: $@" if ( $@ );
    
    my $draft_data = $sth->fetchrow_hashref;
    return $draft_data;
}

sub any_not_received() {
    my ($cid) = @_[0];
    my $retval = 0;
    my $query = qq{SELECT anchored FROM clusters WHERE cluster_id='$cid'};
    my $sth;
    eval {
        $sth = $dbh->prepare ( $query );
        $sth->execute;
    };
    die "$iam: Cannot prepare/execute $query\nError: $@" if ( $@ );

    my $anchor;
    $sth->bind_columns(\$anchor);
    while ($sth->fetch) {
        if ($anchor eq 'unknown') {
            $retval = 1;
            break;
        }
    }
    return $retval;
}
# Lookup the date received and state for a draft. Some drafts will not have
# been received, so fake these with a future date. Published documents need
# to be sorted before any others, so push these dates back a constant number
# of years. For all others, just return the value in the database.
sub get_draft_date_received() {
    my ($draft) = &strip_number($_[0]);
#    my $query = qq{
#          SELECT date_received, state
#          FROM `index` 
#          WHERE state NOT IN ('DNP', 'REPL', 'WITHDRAWN')
#             AND draft LIKE '$draft-__' LIMIT 1};

    my $query = qq{
           SELECT i.date_received, 
	       CONCAT( s.state_name,CASE WHEN i.iana_flag = "1"THEN "*A"ELSE ""END ,CASE WHEN i.ref_flag = "1"THEN "*R"ELSE ""END,
               CASE WHEN i.generation_number = "1" THEN "(1G)" WHEN i.generation_number = "2" THEN "(2G)" WHEN i.generation_number = "3" THEN "(3G)" WHEN i.generation_number = "0" THEN "" END) as STATE
           FROM `index` i, `states` s
           WHERE i.state_id NOT IN ('3', '16', '19')
           AND i.state_id = s.state_id 
           AND draft LIKE '$draft-__' LIMIT 1};

    my $sth;
    eval {
        $sth = $dbh->prepare ( $query );
        $sth->execute;
    };
    die "$iam: Cannot prepare/execute $query\nError: $@" if ( $@ );

    my ($date_received, $state);
    $sth->bind_columns(\($date_received, $state));
    $sth->fetch;
    if ($sth->rows == 0) {
        # Make not received documents "in the future"
        $date_received = '9999-01-01';
    } else {
        if ($state eq 'PUB') {
            # For sorting documents by date received, make published documents look older
            # than documents in any other state            
            $date_received = &substract_years($date_received,30);
        }
    }
    # if not changed above, this is just the value from the database
    return $date_received;
}

# Accept a RFC Editor date string, YYYY-MM-DD, and substract some number from
# the year part.
sub substract_years() {
    my ($date_str,$years) = @_;
    my @fields = split '-', $date_str;
    $fields[0] -= $years;
    return join '-', @fields;
}

# Indicate successful load
1;
