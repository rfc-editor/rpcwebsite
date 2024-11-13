#!/usr/bin/perl
# $Id: createLatestZIPandTAR.pl,v 2.0 2010/03/15 22:50:45 rcross Exp $

#	createLatestZIPandTAR.pl
#
#	Creates .zip and .tar.gz files for published RFCs with
#	.txt, .ps, .pdf, and /pdfrfc/rfc*txt.pdf formats.  The
#	companion script recreateZIPandTAR.pl breaks RFCs into
#	sets of 500: 1-500, 501-1000, 1001-1500, ... , pp01-qq00
#	where is pp is multiple of 5 and qq = pp+5;  This
#	script handles the rest, qq01-latest, where "latest"
#	actually means highest RFC number published, which is not
#	generally the latest publication date.  The general idea
#	here is that the recreate script can run only once a week,
#	since the RFC #s 1 - qq00 should seldom change, while this
#	script can run daily to capture the recent publications.
# 
# 2002.02.24	recreate script created by Nathalie Collins
# 2007.10.06	This script adapted from Nathalie's recreate script by
#		Bob Braden, to replace a couple of smaller scripts.
# 2007.10.29	Bob Braden: output only to log file in ~rfc-ed/logs
##


#
# Define source (where the RFCs live) and destination (where the tar/zip
# files should go) at the beginning.
#
my $src_path = '/a/ftp/in-notes/';
$dst_path = '/a/ftp/in-notes/tar/';

## Define log file name
#
$logfile = "/home/rfc-ed/logs/createLatestZipandTar.log";
if (-e $logfile) {
	unlink($logfile);
}

open( LOG,">$logfile");
$date = `date`;
print LOG "createLatestZIPandTAR: $date\n";

# known RFC file extensions (anything that succeeds rfc<num>)
my @known_ext = ('.txt', '.pdf', '.ps', '.tar', 'a.txt', '.hastabs.txt');  

chdir($src_path);

@files = `ls`;     ## complete set of file names in ~in-notes

my    ( $lastfile, $biggest, $num_of_file );

($lastfile, $biggest) = findLastFile();

$m = 1 + 500 * int(($biggest-1) / 500);  ## qq01: begin partial range


my @tar_list;		# name of all RFCs to go into tarball
my @pdfrfc_list;	# name of .txt.pdf RFCs in pdfrfc/ directory
my @ziptxt_list;	# name of all ascii text RFCs
my @zip_list;		# name of all RFCs not in @ziptxt_list

    #
    # Loop through each RFC number within partial range.
    #
    for (my $k = $m; $k <= $biggest; $k++) {

	
	#
	# Using the array of file extentions, check for the existence
	# of all possible files with  RFC # k.
	#
	my $basename = sprintf("rfc%d", $k);    # No leading zeros

	foreach my $ext (@known_ext) {
	    my $filename = $basename . $ext;	# add extension

	    if (-e $filename) {
		push @tar_list, $filename;	# add to tarball file list
						# if file exists
		if ($ext =~ /\.txt$/) {
		    push @ziptxt_list, $filename;

		    #
		    # For each .txt file, see if there is an equivalent
		    # .txt.pdf file in 'pdfrfc/'.  If so, add its name to
		    # the appropriate list.
		    #
		    my $pdf = 'pdfrfc/' . $filename . '.pdf';
		    if (-e $pdf) {
			push @pdfrfc_list, $pdf;
		    }
		}
		else {
		    push @zip_list, $filename;
		}
	    }
	}
    }

    #
    # Turn the arrays of filenames created above into strings representing
    # command line arguments.
    #
    my $tar_line = join(' ', @tar_list);
    my $pdfrfc_line = join(' ', @pdfrfc_list);
    my $ziptxt_line = join(' ', @ziptxt_list);
    my $zip_line = join(' ', @zip_list);

    #
    # Use the beginning and ending RFC numbers to create correct tar and zip
    # archive filenames.
    #
    my $start = sprintf("%4d", $m);
    $start =~ s/ /0/g;

    my $rfcfile = 'RFCs' . $start . '-latest';
    my $pdfrfcfile = 'pdfrfc' . $start . '-latest';

    #
    # Create the appropriate tarball using the complete file list stored in
    # @tar_list (then translated into $tar_line).
    #
    `/bin/rm -f $dst_path$rfcfile.tar.gz`;
    print LOG `/bin/tar cvf $dst_path$rfcfile.tar $tar_line`;
    print LOG `/usr/bin/gzip -v9 $dst_path$rfcfile.tar  2>&1`;

    #
    # Create a zip file of the same files.  However, create an archive
    # containing only text files (translate EOL chars using -l), then add
    # all binary files without using the option.
    #
    `/bin/rm -f $dst_path$rfcfile.zip`;
    `/usr/bin/zip -l9 $dst_path$rfcfile $ziptxt_line`;
    if ($zip_line) {
   	`/usr/bin/zip -9 $dst_path$rfcfile $zip_line`;
    }

    #
    # Create a tarball of all of the "pdfrfc" RFCs.
    #
    `/bin/rm -f $dst_path$pdfrfcfile.tar.gz`;
    print LOG `/bin/tar cvf $dst_path$pdfrfcfile.tar $pdfrfc_line`;
    print LOG `/usr/bin/gzip -v9 $dst_path$pdfrfcfile.tar  2>&1`;

    #
    # Create the companion zip file.
    #
    `/bin/rm -f $dst_path$pdfrfcfile.zip`;
    `/usr/bin/zip -9 $dst_path$pdfrfcfile $pdfrfc_line`;

    close(LOG);

## THE END

####################################################
#                                                  #
#   findLastFile( )                                #
#                                                  #
#   Find last file which has biggest RFC number    #
#   and Return ($lastfile, $biggest)               #
#                                                  #
####################################################

sub findLastFile {

   my ($biggest, $curNum, $lastfile);
 
   $biggest =0;
   foreach $file (@files) {
     if($file =~ /rfc(\d+)/) {
         if ($biggest < $1) {
             $biggest = $1;
             chomp($file);
             $lastfile = $file;
         }           
     }
   }
   return ($lastfile, $biggest);
}
