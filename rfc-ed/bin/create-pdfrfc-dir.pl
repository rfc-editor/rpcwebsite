#!/usr/bin/perl
# $Id: create-pdfrfc-dir.pl,v 2.0 2010/03/15 22:50:45 rcross Exp $

########################################################################
#                                                                      #
#   create-pdfrfc-dir.pl                                               #
#                                                                      #
#   Script which converts very txt file in current directory           #
#   into pdf file                                                      #
#                                                                      #
#   1) convert txt files to ps file                                    #
#                                                                      #
#      - Font Size 10 (current)                                        #
#     (enscript --margin-75 -B -fCourier10 -p rfcxxxx.ps rfcxxxx.txt)  #
#                                                                      #
#      - Font Size 11 (previous)                                       #
#     (enscript --margin-54 -B -fCourier11 -p rfcxxxx.ps rfcxxxx.txt)  #
#                                                                      #
#   2) convert ps file to pdf file                                     #
#     (ps2pdf rfcxxxx.ps rfcxxxx.txt.pdf)                              #
#                                                                      #
#   3) create a new directory  and put newly created pdf files in it   #
#                                                                      # 
#   * System need to have enscript and ps2pdf application              #
#     (currently ps2pdf is not installed on boreas and need            # 
#     to execute on LINUX with ps2pdf)                                 #
#                                                                      #
########################################################################





if($#ARGV ne 0) {
 
    print "[USAGE] create-pdfrfc-dir.pl PDF_DIR_NAME(to be created)\n";
    exit;
}

# $ARGV[0] =~ s/\/$//;

if(-e $ARGV[0] and -d $ARGV[0]) {
		print "\n*** Directory with same name already exist!\n";
    print "==> Type 'YES' if you want to proceed: ";
    $user_answer = <STDIN>;
		chomp($user_answer);
  
    if($user_answer eq "YES") {
       print "\n*** Proceed to overwrite directory $ARGV[0]...\n\n";  
		}
		else{
				print "\n*** Command Aborted!\n";
        print "==> Try again with different directory name!\n\n";
				exit;
		}
}

elsif(-e $ARGV[0]) {
		print "\n*** File with same name already exist!\n";
    print "==> Try again with different directory name!\n\n";   
    exit;
}

else{
   `mkdir $ARGV[0]`;
		print "\n*** Creating PDF version of txt document in New directory: $ARGV[0]\n";
}


@allfile =  `ls *.txt`;  


###################################
#  LOG file in current Directory  #
###################################
$logfile = $ARGV[0] . '/' . 'txt2pdf.log';

if (-e $logfile) {
   unlink($logfile);
}



foreach $file (@allfile) {

   chomp($file);   
   $fn = $file;

   $fn =~ s/.txt//;

   $txt_fn = $fn. '.txt';
   $ps_fn  = $ARGV[0] . '/' . $fn. '.ps';
   $pdf_fn = $ARGV[0] . '/' . $fn. '.txt.pdf';

#	 print "FN($fn) TXT_FN($txt_fn) PS_FN($ps_fn)  PDF_FN ($pdf_fn) \n";


   #############################################
   #  Remove ^L in the last page  (last line)  #
   #############################################

   $new_fn = $txt_fn . '.new';

   open(FILE, "$txt_fn") or die " cannot open $txt_fn\n";;
   @all = <FILE>;
   close(FILE);

   open(NEWFILE, ">$new_fn") or die "cannot create new file\n";


   for($i=0; $i <= $#all ; $i++) {

       #########################################
       # $^L or $FORMAT_FORMFEED matches '^L'  #
       #########################################

#      if($all[$i] =~ /$FORMAT_FORMFEED/gi and $i eq $#all) {
       if($all[$i] =~ /$^L/ and $i eq $#all) {

           $len =length $all[$i];
           if($len > 2 ) {
#             $all[$i] =~ s/$FORMAT_FORMFEED//gi;
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
   #  Create PS and PDF in Order #   
   ###############################
   $enscript_out = `enscript --margin=75 -B  -fCourier10 -p $ps_fn $new_fn 2>>$logfile`; 
   $ps2pdf_out = `ps2pdf $ps_fn $pdf_fn 2>>$logfile`;

   unlink($ps_fn);
   unlink($new_fn);

   print "--> $pdf_fn is created !\n";

}
 
 






