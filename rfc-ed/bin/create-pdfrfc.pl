#!/usr/bin/perl
# $Id: create-pdfrfc.pl,v 2.0 2010/03/15 22:50:45 rcross Exp $

########################################################################
#                                                                      #
#   create-pdfrfc.pl                                                   #
#                                                                      #
#   Script that converts specified (as an argument) .txt RFC to        #
#   .txt.pdf file in ~in-notes/pdfrfc/                                 #
#                                                                      #
#   1) convert specific rfc[#].txt file in ~in-notes to ps file        #
#                                                                      #
#      - Font Size 10 (current)                                        #
#     (enscript --margin-75 -B -fCourier10 -p rfcxxxx.ps rfcxxxx.txt)  #
#                                                                      #
#      - Font Size 11 (previous)                                       #
#     (enscript --margin-54 -B -fCourier11 -p rfcxxxx.ps rfcxxxx.txt)  #
#                                                                      #
#   2) convert ps file to pdf file                                     #
#     (ps2pdf rfcxxxx.ps pdfrfc/rfcxxxx.pdf)                           #
#                                                                      # 
#   Mods: 18Oct07remove gratuitous code for missing pdfrfc directory   #
#                                                                      #
########################################################################



$DEFAULT_PDFDIR = 'pdfrfc';
$BASEDIR = "/a/ftp/in-notes/";

my $def_dir_flag;

if($#ARGV ne 0) {
 
    print "[USAGE] create-pdfrfc.pl rfcxxxx(.txt)\n";
    exit;
}

chdir $BASEDIR;

($file_name, $file_ext) = split /\./,$ARGV[0];

$file_name_pdf = $file_name . '.txt.pdf';
$file_name_txt = $file_name . '.txt';


if(!(-e $file_name_txt )) {
     print " *** Error: $file_name_txt doesn't exist !\n";
     exit;
}

##if(!(-e $DEFAULT_PDFDIR and -d $DEFAULT_PDFDIR)) {
##    print " *** WARNING: No PDF Directory ($DEFAULT_PDFDIR) !\n";
##    print "==> Type 'yes' if you want to create .txt.pdf file: ";
##    $user_answer = <STDIN>;	
##    chomp($user_answer);  
##    if($user_answer eq "yes") {
##       print "\n*** Create PDF file($file_name_pdf) \n\n";  
##    }
##    else{
##	  print "\n*** Command Aborted!\n";
##        exit;
##    }
##
##    $def_dir_flag ='n';
##}
##else{
##    $file_name_pdf = $DEFAULT_PDFDIR . '/' . $file_name_pdf;
##    $def_dir_flag ='y';
##}


$file_name_pdf = $DEFAULT_PDFDIR . '/' . $file_name_pdf;

if(-e $file_name_pdf) {

    print "*** [$file_name_pdf] already exist !\n";

    print "==> Type 'yes' if you want to overwrite previous file:\n";
 
    $user_answer = <STDIN>;
    chomp($user_answer);
  
    if($user_answer eq "yes") {
	print "\n*** Overwriting $file_name_pdf\n\n";  
	unlink($file_name_pdf);
    }
    else{
	print "\n*** Command Aborted!\n";
	exit;
    }
}

##############################################
#  Remove ^L from the last page  (last line) #
##############################################

$new_fn = $file_name_txt . '.new';

open(FILE, "$file_name_txt");
@all = <FILE>;
close(FILE);

open(NEWFILE, ">$new_fn") or die "cannot create new file\n";


for($i=0; $i <= $#all ; $i++) {

    #########################################
    # $^L or $FORMAT_FORMFEED matches '^L'  #
    #########################################


    if($all[$i] =~ /$^L/ and $i eq $#all) {

        $len =length $all[$i];
        if($len > 2 ) {

#          $all[$i] =~ s/$FORMAT_FORMFEED//gi;
           print NEWFILE "$all[$i]";
           print "* $file_name may have extra page (last page)!!!\n";
	}
    }
    else {
       print NEWFILE "$all[$i]";
    }
}

close(NEWFILE);

###############################
#  Create PS and PDF files    #   
###############################

## if($def_dir_flag eq 'y') {
##    $file_name_ps  = $DEFAULT_PDFDIR . '/' . $file_name . '.ps';
##}
##else {
##    $file_name_ps  = $file_name . '.ps';
##}

## Temp .ps file in /pdfrfc/
##
$file_name_ps  = $DEFAULT_PDFDIR . '/' . $file_name . '.ps';

$enscript_out = `enscript --margin=75 -B  -fCourier10 -p $file_name_ps $new_fn`; 
$ps2pdf_out = `ps2pdf $file_name_ps $file_name_pdf`;

## Delete temporary files
##
unlink($new_fn);
unlink($file_name_ps);

print "\n ==> $file_name_pdf successfully created !\n\n";
 
 












