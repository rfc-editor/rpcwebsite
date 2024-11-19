#!/usr/bin/perl
##################################################################################
#                                                                                #
# File Name: createxmlZIPandTAR                                                  #
#                                                                                # 
# Description:                                                                   #
#   Perl script to create tar.Z and .gzip versions of .xml files  of all RFCs    #
#  06-15-2020   Priyanka Narkar  - Created the script                            #
################################################################################## 

#Get the input directory
chdir "/in-notes/";


$xmltarfile="/in-notes/tar/xmlsource-rfc8650-latest.tar";
$xmlzipfile="/in-notes/tar/xmlsource-rfc8650-latest";
$xmlalltarfile="/in-notes/tar/xmlsource-all.tar";
$xmlallzipfile="/in-notes/tar/xmlsource-all";

#Delete previous log file and open file handle for it (LOG)

$logfile = "/rfc-ed/logs/createxmlZIPandTAR.log";

if (-e $logfile) {
		unlink($logfile);
}

open(LOG,">$logfile");

#Get the all .xml files
my @files = <*.xml>;
#Remove rfc-index.xml from the list.
my $rfc_index = shift @files;
my $xml_files = join (' ', @files);



#Creating all xml files 
`/bin/rm -f $xmlalltarfile.gz`;
print LOG `/bin/tar cvf $xmlalltarfile $xml_files`;
print LOG `/usr/bin/gzip -v9 $xmlalltarfile  2>&1`;

`/bin/rm -f $xmlallzipfile.zip`;
`/usr/bin/zip -9 $xmlallzipfile $xml_files`;

#Creating latest xml files
`/bin/rm -f $xmltarfile.gz`;
print LOG `/bin/tar cvf $xmltarfile $xml_files`;
print LOG `/usr/bin/gzip -v9 $xmltarfile  2>&1`;

`/bin/rm -f $xmlzipfile.zip`;
`/usr/bin/zip -9 $xmlzipfile $xml_files`;


close(LOG);

#THE END
