#!/usr/bin/perl
# $Id: recreateZIPandTAR.pl,v 2.0 2010/03/15 22:50:45 rcross Exp $

#
# This script creates all of the previous RFC archives (.txt, .ps, .pdf,
# and /pdrrfc/rfc*.txt.pdf) in .zip and .tar.gz formats.
# It will break the RFCs into sets of 500 up to RFCss00, where and write
# them into their respective archives.
#
# 	2002.02.24	Created by Nathalie Collins <collins@isi.edu>
#	2006.10.12      Highest number updated to 4500 <braden@isi.edu>
#	2007.08.21	Highest number updated to 5000 <braden@isi.edu>
#       2007.08.22      Compute highest number <braden@isi.edu>


#
# Define source (where the RFCs live) and destination (where the tar/zip
# files should go) at the beginning.
#
my $src_path = '/a/ftp/in-notes/';
my $dst_path = '/a/ftp/in-notes/tar/';
## my $dst_path = '/nfs/gren/braden/rfced/work/tar/';

my $log_file = '/home/rfc-ed/logs';

# known RFC file extensions (anything that succeeds rfc<num>)
my @known_ext = ('.txt', '.pdf', '.ps', '.tar', 'a.txt', '.hastabs.txt');  

chdir($src_path);

#
# Cycle through each group of 500 RFCs up to and including rfctt00.txt,
#   where tt = 5*ceil(biggestRFC#/500)
# 
# Each group will make up the contents of a tar/zip archive.
#

my    ( $lastfile, $biggest, $num_of_file );

@files = `ls`;

($lastfile, $biggest) = findLastFile();

$max_num = 500 * int($biggest / 500);

open(LOG,">>$log_file/recreateZIPandTAR.log") || die ("cant open log file $!");
 
for (my $m = 0; $m < $max_num; $m += 500) {
    my @tar_list;		# name of all RFCs to go into tarball
    my @pdfrfc_list;		# name of .txt.pdf RFCs in pdfrfc/ directory
    my @ziptxt_list;		# name of all ascii text RFCs
    my @zip_list;		# name of all RFCs not in @ziptxt_list

    #
    # Loop through each RFC number within the group of 500.
    #
    for (my $k = 1; $k <= 500; $k++) {
	my $basename = sprintf("rfc%d", $m + $k);    # ex: 'rfc23'
	
	#
	# Using the base filename created above and the array of file
	# extentions, check for the existance of all possible files with
	# this name.
	#
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
    my $start = sprintf("%4d", $m + 1);
    $start =~ s/ /0/g;
    my $end = sprintf("%4d", $m + 500);
    $end =~ s/ /0/g;

    my $rfcfile = 'RFCs' . $start . '-' . $end;
    my $pdfrfcfile = 'pdfrfc' . $start . '-' . $end;

    #
    # Create the appropriate tarball using the complete file list stored in
    # @tar_list (then translated into $tar_line).
    #
    print LOG `/bin/rm -f $dst_path$rfcfile.tar.gz`;
    print LOG `/bin/tar cvf $dst_path$rfcfile.tar $tar_line`;
    print LOG `/usr/bin/gzip -v9 $dst_path$rfcfile.tar 2>&1`;

    #
    # Create a zip file of the same files.  However, create an archive
    # containing only text files (translate EOL chars using -l), then add
    # all binary files without using the option.
    #
    print LOG `/bin/rm -f $dst_path$rfcfile.zip`;
    print LOG `/usr/bin/zip -l9 $dst_path$rfcfile $ziptxt_line`;
    print LOG `/usr/bin/zip -9 $dst_path$rfcfile $zip_line`;

    #
    # Create a tarball of all of the "pdfrfc" RFCs.
    #
    print LOG `/bin/rm -f $dst_path$pdfrfcfile.tar.gz`;
    print LOG `/bin/tar cvf $dst_path$pdfrfcfile.tar $pdfrfc_line`;
    print LOG `/usr/bin/gzip -v9 $dst_path$pdfrfcfile.tar 2>&1`;

    #
    # Create the companion zip file.
    #
    print LOG `/bin/rm -f $dst_path$pdfrfcfile.zip`;
    print LOG`/usr/bin/zip -9 $dst_path$pdfrfcfile $pdfrfc_line`;
}

close(LOG);

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
print LOG "BIGGEST Doc Number is $biggest (file $lastfile)\n";

   return ($lastfile, $biggest);
}
