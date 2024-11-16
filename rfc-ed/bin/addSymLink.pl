#!/usr/bin/perl
# $Id: addSymLink.pl,v 2.0 2010/03/15 22:50:45 rcross Exp $

############################################################# 
#                                                           #
# Name: addSymLink.pl                                       #
#                                                           # 
# Description:                                              # 
#                                                           # 
#   [USAGE] addSymLink.pl [-f] rfcxxxx [sssyyyy]            #
#                                                           #
#     Exec'd by hand as part of publication procedure       #
#                                                           # 
#     If sssyyyy parm given, do 'ln -s' from ~in-notes/sss  #
#     If it obsoletes previous symbolic link, remove it.    #
#                                                           # 
#      -f : overwrite existing symbolic link if exists      #  
#                                                           #             
#     Also, make symbolic link(s) for rfc-info email server #
#     Also, create .txt.pdf version of .txt file            #
#                                                           #
# Created by: Dong-jin Son (May 30, 2001)                   #
# Mods:Braden 13 Sep 2007: allow sssyyyy parm to be optional#
#      Braden 18 Oct 2007: remove spurious INDEX path       #
#                                                           # 
#############################################################

## (test?
$production = 1;

##############
# Variables  #
##############

$filedir_rfc = "/a/ftp/in-notes/";

## Sub-Series directory paths
##
%ssfiledir = (
	"std" => "/a/ftp/in-notes/std/",
	"bcp" => "/a/ftp/in-notes/bcp/",
	"fyi" => "/a/ftp/in-notes/fyi/"
);

##  Email server path
##
$rfcinfoDir = "/a/info-sys/perl/rfc-info/Data";


#######################
#  Argument Handling  # 
#######################

if( $#ARGV < 0 || $#ARGV > 2) {
	print_usage();  ## must be 1,2, or 3 parms
	exit;
}
$f_flag = 0;
if($ARGV[0] =~ /\-f/) {
	$f_flag = 1;
	shift @ARGV;
}

unless ($ARGV[0] =~ /^rfc[0-9]+$/i) {
	print_usage();
	exit;
}

$rfcNum = int substr (shift @ARGV, 3);
#$rfcDoc = sprintf "rfc%4d.txt", $rfcNum;
$rfcDoc = sprintf "rfc%d.txt", $rfcNum;
$rfcDoc_fp = ${filedir_rfc} . $rfcDoc;

$ssType = "";
if ($ssparm = $ARGV[0]) {
	unless ($ssparm =~ /^(bcp|std|fyi)[0-9]+$/i) {
		print_usage();
		exit;
	}
	$ssType = lc( substr $ssparm, 0, 3);
	$ssNum = int substr $ssparm, 3;
	$ssDoc = sprintf "$ssType%d.txt", $ssNum;
	$ssDoc_fp = $ssfiledir{$ssType} . $ssDoc;
}

### print "$rfcNum -- $rfcDoc_fp $ssType $ssNum $ssDoc $ssDoc_fp  $f_flag\n";

#######################################################################
#  Add a Symbolic link in ~info-sys/perl/rfc-info/Data/RFC directory  #
#######################################################################

#$rfcinfoDirRFC = sprintf "$rfcinfoDir/RFC/RFC%4d.ASCII", $rfcNum;
$rfcinfoDirRFC = sprintf "$rfcinfoDir/RFC/RFC%04d.ASCII", $rfcNum;

print "\n##  Add RFC-Info symbolic link to $rfcDoc_fp \n";
print "    from $rfcinfoDirRFC\n";
print "    (ln -s $rfcDoc_fp $rfcinfoDirRFC) \n";

if (-e $rfcinfoDirRFC) {
	$infolink = `ls -l $rfcinfoDirRFC`;
	chomp $infolink;
	print "\nXX  RFC-Info link exists for RFC:\n";
	print "    ($infolink).\n";
	print "XX  To override, remove link and re-execute this command.\n";
}
elsif ($production) {
	print `ln -s $rfcDoc_fp $rfcinfoDirRFC`;
}


if ($ssType ne "") {

##########################################################################
#  Sub-series parameter in command.                                      #
#                                                                        #
#  Add a Symbolic link in ~info-sys/perl/rfc-info/Data/(BCP|STD|FYI) dir #
##########################################################################

    $linked_Doc = sprintf "$rfcinfoDir/%s/%s%04d.ASCII",
				 uc($ssType), uc($ssType), $ssNum;

    print "\n## Add RFC Info sub-series link for $ssDoc_fp \n";
    print "   from $linked_Doc\n";

    print "   (ln -s $ssDoc_fp $linked_Doc) \n";

    if (-e $linked_doc) {
	print "\nXX RFC-Info link already exists for $ssType. To override, remove the\n";
	print "   old link and re-execute this command.\n";
    }
    elsif ($production) {
	print `ln -s $ssDoc_fp $linked_Doc`;
    }


    ###########################################################
    #   Make ~in-notes/<subseries>/sssnnn.txt entry, unless   #
    #    it already exists.                                   #
    ###########################################################

    ## go to sub-series directory

    chdir $ssfiledir{$ssType};

    @checklist = `ls -l `; ## get directory

    $write_flag = 1;
    $slink_flag = 0;

    foreach $ssfile (@checklist) {

	chomp $ssfile;
	if ($ssfile =~ /$ssDoc/) {
	    ## matching sub-series document already there
	    print "\n** Sub-series file exists as: \'$ssfile\'\n";

            if ($ssfile =~ m/l.*[-][>]/) {
		##  Is symbolic link.  Check if matches.
		$slink_flag = 1;

###		$_ = "$ssfile";
###		/(.*)\.\.\/(.*)/;
###		$docid = "$2";  ## substring after ../ is rfc docid

		$docid = $ssfile;
		$docid =~ s/.*(rfc\d+[.]txt)$/$1/;
	    }

	    if ((! $slink_flag ) || ($rfcDoc ne $docid)) {
		## Not symbolic link or link does not match...
		## Check for override (-f) parameters.
		if ($f_flag) {
			print "-f: Removing old file $ssDoc\n";
			if ($production) { print `rm -f $ssDoc`; }
		}
	    	else {
			$write_flag = 0;
			print "XX Is a data file or wrong symbolic link.  To override, use -f flag\n";
		}
	    }
	    else {
		print "## Correct symbolic link already in place\n";
		$write_flag = 0;
	    }
	    last;
	}
    }
		
    if ($write_flag) {
	    print "\n##  Creating new symbolic link (ln -s ../$rfcDoc $ssDoc)\n";
	    print "    in directory: $ssfiledir{$ssType}\n";
	    if ($production) { print `ln -s ../$rfcDoc $ssDoc`; }
    }
}


###########################################################
#  Make rfc[#].txt.pdf file in ~in-notes/pdfrfc/          #
###########################################################

print "\n**  Create /pdfrfc/rfc[#}.txt.pdf file\n";

system "create-pdfrfc.pl $rfcDoc";

exit;



sub print_usage() {
	print "\nUsage: addSymLink.pl [-f]  rfcxxxx [ sssyyyy ]\n";
	print "   where sss is subseries: std|bcp|fyi.\n";
	print "   (Leading zeros in xxxx or yyyy don't matter).\n";
	print "   -f replaces sub-series link if it already exists.\n\n";
        return;
}
