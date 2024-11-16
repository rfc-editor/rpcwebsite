#!/usr/bin/perl
# $Id: xxx-index-html.pl,v 2.4 2019/04/05 20:30:11 priyanka Exp $
##############################################################################
#                                                                            #
#  o Name: xxx-index-html.pl [up/down]                                       #
#                                                                            #
#  o Description:                                                            #
#                                                                            #
#   => create rfc-index, std-index, bcp-index, fyi-index in HTML format      #
#   (*Executed by ~rfc-ed/bin/create-indexes.sh script                       #
#                                                                            #
#  o Created by:  Dong-jin Son  (Apr 23, 2001)                               #
#  o Updated by:  Nathalie Collins (01.17.2003)				     #
#	- changed "For Your Interest" -> "For Your Information".	     #
#  o Updated by:  Nathalie Collins (10.18.2002)                              #
#	- changed $sendto to ftp.rfc-editor.org instead of ftp.isi.edu       #
#  o Updated by: Bob Braden (Oct 2007): Make it readable by fixing indents.  #
#  o Updated by: Bob Braden (Sep 2008): Use HTTP URL, not FTP. Also combine  #
#                     with reverse program -- parm [d[own]]                  #
#                                                                            #
#  o Updated by: Priyanka N (Apr 2019): Modified text from Adobe (PDF) to    #
#                Portable Document Format (PDF)                              #
##############################################################################

# Get command line argument and set $Decrease iff arg is "d..."
#
$parm = $ARGV[0];    
chomp $parm;
$Decrease  =  ($parm =~ /^d/);


##  Where doc is really stored
##
$where = "/a/ftp/in-notes/";

## URL we give user
##
$sendto = "http://www.rfc-editor.org/";

# RFC Data file (INDEX)
#
$data="/home/rfc-ed/INDEX";

# Date
$cur_date=`date \+\%b\" - \"\%e\" - \"\%Y`;

# File names for output .html files
#
$HTDOCS = "/a/www/rfc/htdocs";
#$HTDOCS = "/Library/WebServer/Documents";

if ($Decrease) {  ## print "Decreasing\n";
	$std_index = $HTDOCS . "/std-index2.html";
	$bcp_index = $HTDOCS . "/bcp-index2.html";
	$fyi_index = $HTDOCS . "/fyi-index2.html";
} else {
	$std_index = $HTDOCS . "/std-index.html";
	$bcp_index = $HTDOCS . "/bcp-index.html";
	$fyi_index = $HTDOCS . "/fyi-index.html";
}

# Log file
$logfile = "/home/rfc-ed/logs/xxx-index-html.log";
#$logfile = "xxx-index-html.log";


@file_type = ($std_index, $bcp_index, $fyi_index);

@FP = (STD, BCP, FYI);

%AttrNames = (
	"OBSOLETES" => "Obsoletes",
	"OBSOLETED-BY" => "Obsoleted by",
	"UPDATES" => "Updates",
	"UPDATED-BY" => "Updated by",
	"SEE-ALSO" => "Also",
	"CLARIFIES" => "Clarifies",
	"CLARIFIED-BY" => "Clarified by",
	);

# Delete previous HTML files (if exists)

for($i=0; $i<= $#file_type; $i++) {
	if(-e $file_type[$i]) {
###		print "o removing $file_type[$i] file ....\n";
        	print `rm -f $file_type[$i]`;
	}
}

# OPEN .html files for writing

open (STD, ">$std_index");
open (BCP, ">$bcp_index");
open (FYI, ">$fyi_index");

# Copy headers into .html files
#
&std_html_header($FP[0]);
&bcp_html_header($FP[1]);
&fyi_html_header($FP[2]);


#
# Subroutines to output HTML headers
#

  #######################
  # INDEX HEADERS       #
  #######################

sub std_html_header {
    my($fh) = @_;
      print $fh <<'END';

<html>
<body bgcolor="white">
<a name="top"><br>
<center><font size=6 color=brown><b><u>STD Index</u></b></font></center><br>
END
      print $fh "<font size =3><u>CREATED ON: $cur_date</u><br><br>";

      print $fh <<'END';

 o This file contains citations for all STDs in numeric order.<br><br>
 o Each STD represents a single STD sub-series citation,
 composed of one or more RFCs with Internet Standard status.<br><br>

 <table border=5><tr><td> Num </td><td> Information </td></tr>
 <tr> <td> #### </td><td> Title of STD. &nbsp; Author 1, Author 2, Author 3.
 &nbsp; Issue date. (ASCII) (Obsoletes xxx) (Obsoleted by xxx) (Updates xxx)
(Updated by xxx) (Also RFC ####, ...)<br>
</td></tr></table><br>

 &nbsp;- For example:<br>
  
 <table border=5><tr><td> Num </td><td> Information </td></tr>
     <tr><td> 0006 </td><td> User Datagram Protocol. J. Postel. 28 August 1980. (TXT=5896
        bytes) (Also RFC0768)</td></tr></table><br>

<br>o Key to citations:<br>
 - #### is the STD number.<br>
 - Following the STD number are the title, the author(s), and the
 publication date of the RFC.  Each of these is terminated by a period.<br><br>

 - Following the number are the title (terminated with a period), the
author, or list of authors (terminated with a period), and the date
(terminated with a period).<br><br>

 - The format and length follow in parenthesis.  One or more of the
following alternative formats are listed:  ASCII text (TXT), PostScript
(PS), and/or Portable Document Format (PDF).  Each format is followed by 
an equals sign and the number of bytes for that version.  For example
 (TXT=aaaaa, PS=bbbbbb bytes) shows that the ASCII text version is aaaaa
bytes, and the PostScript version of the RFC is bbbbbb bytes.<br><br>

 - Finally, the (Also RFC ##) phrase lists the RFC(s) that form this STD.<br><br>

 - RFCs may be obtained using HTTP, FTP, or email.  See the RFC Editor Web
page http://www.rfc-editor.org<br><br>
<br><br><center><font size=6> <b><u>STD &nbsp; Index</u></b></font></center><br>
<table>
    <tr bgcolor="#ffddbb"><td> Num </td><td>Information</td></tr>
END
}

sub bcp_html_header {
    my($fh) = @_;
      print $fh <<'END';

<html>
<body bgcolor="white">
<a name="top"><br>
<center><font size =6 color=brown><b><u>BCP Index</u></b></font></center><br>
END
      print $fh "<font size =3><u>CREATED ON: $cur_date</u><br><br>";

      print $fh <<'END';

 o This file contains citations for all BCPs in numeric order.<br>
 o The BCPs form a sub-series of the RFC document series, specifically those RFCs with 
the status BEST CURRENT PRACTICE.<br><br>

 <table border=5><tr><td> Num </td><td> Information </td></tr>
 <tr> <td> #### </td><td> Title of BCP. &nbsp;Author 1, Author 2, Author 3.
 &nbsp; Issue date. (ASCII) (Obsoletes xxx) (Obsoleted by xxx) (Updates xxx)
(Updated by xxx) (Also RFC ####)<br>
</td></tr></table><br>
 &nbsp;- For example:<br>
  
 <table border=5><tr><td> Num </td><td> Information </td></tr>
  
<tr><td> 0009 </td> <td> The Internet Standards Process -- Revision 3. S. Bradner. October
     1996. (TXT=86731 bytes) (Obsoletes RFC1602) (Also RFC2026) </td></tr></table><br>

 o Key to fields:<br><br>

 - #### is the BCP number.  The remainder of the fields are the same as 
 - those of the corresponding RFC, whose number is given in the final 
(Also RFC####) field.<br><br>

 - Following the BCP number are the title, the author(s), and the
publication date of the RFC.  Each of these is terminated by a period.<br><br>

 - The format and length follow in parenthesis.  One or more of the
following alternative formats are listed:  ASCII text (TXT), PostScript
(PS), and/or Portable Document Format (PDF).  Each format is followed by 
an equals sign and the number of bytes for that version.  For example
(TXT=aaaaa, PS=bbbbbb bytes) shows that the ASCII text version is aaaaa
bytes, and the PostScript version of the RFC is bbbbbb bytes.<br><br>

 - "Obsoletes xxxx" refers to other RFCs that this one replaces;
 - "Obsoleted by xxxx" refers to RFCs that have replaced this one.
 - "Updates xxxx" refers to other RFCs that this one merely updates but
does not replace); "Updated by xxxx" refers to RFCs that have updated
(but not replaced) this one.  Generally, only immediately succeeding
and/or preceding RFCs are indicated, not the entire history of each
related earlier or later RFC in a related series.<br><br>

 - BCPs may be obtained using HTTP, FTP, or email.  See the RFC Editor Web
page http://www.rfc-editor.org<br><br>

<br><br><center><font size=6> <b><u>BCP &nbsp; Index</u></b></font></center><br>

<table>
    <tr bgcolor ="ffddbb"><td> Num </td><td>Information</td></tr>
END
}

sub fyi_html_header {
    my($fh) = @_;
      print $fh <<'END';

<html>
<body bgcolor="white">
<a name="top"><br>
<center><font size =6 color=brown><b><u>FYI Index</u></b></font></center><br>
END
      print $fh "<font size =3><u>CREATED ON: $cur_date</u><br><br>";

      print $fh <<'END';

 o This file contains citations for all FYIs in numeric order.<br><br>

 o The FYIs ("For Your Information") documents form a sub-series of
 the RFC series,  specifically those documents that may be of particular 
 interest to Internet users.  The corresponding RFCs have status  
 INFORMATIONAL.<br><br>

 o FYI citations appear in this format:<br>

 <table border=5><tr><td> Num </td><td> Information </td></tr>
 <tr> <td> #### </td><td> Title of FYI. &nbsp;Author 1, Author 2, Author 3.
 &nbsp; Issue date. (ASCII) (Obsoletes xxx) (Obsoleted by xxx) (Updates xxx)
(Updated by xxx) (Also RFC ####)<br>
</td></tr></table><br>

 - For example:<br>

 <table border=5><tr><td> Num </td><td> Information </td></tr>

 <tr> <td> 0008 </td> <td>  Site Security Handbook. B. Fraser. September 1997. (
     TXT=191772 bytes) (Obsoletes RFC1244) (Also RFC2196) </td></tr></table><br><br>

 o Key to fields:<br><br>

 - #### is the FYI number.<br>
 -  The remainder of the fields are the same as 
those of the corresponding RFC, whose number is given in the final 
(Also RFC####) field.<br><br>

 - Following the FYI number are the title, the author(s), and the
publication date of the RFC.  Each of these is terminated by a period.<br><br>

 - The format and length follow in parenthesis.  One or more of the
following alternative formats are listed:  ASCII text (TXT), PostScript
(PS), and/or Portable Document Format (PDF).  Each format is followed by 
an equals sign and the number of bytes for that version.  For example (
TXT=aaaaa, PS=bbbbbb bytes) shows that the ASCII text version is aaaaa
bytes, and the PostScript version of the RFC is bbbbbb bytes.<br><br>

 - "Obsoletes xxxx" refers to other RFCs that this one replaces;
 - "Obsoleted by xxxx" refers to RFCs that have replaced this one. <
 - "Updates xxxx" refers to other RFCs that this one merely updates but
does not replace); "Updated by xxxx" refers to RFCs that have updated
(but not replaced) this one.  Generally, only immediately succeeding
and/or preceding RFCs are indicated, not the entire history of each
related earlier or later RFC in a related series.<br><br>

 - FYIs may be obtained using HTTP, FTP, or email.  See the RFC Editor Web
page http://www.rfc-editor.org<br><br>

<br><br><center><font size=6> <b><u>FYI &nbsp; Index</u></b></font></center><br>

<table>
<tr bgcolor ="#ffddbb"><td> Num </td><td>Information</td></tr>	 
END
}

 		 		 


#####################################################################
##  MAIN ROUTINE
#####################################################################

## Open INDEX file and read all entries, building arrays of info to print
##   If decreasing, use stack to reverse order.
##

open (DATA, "$data") || die "No INDEX file: $!\n";

if ($Decrease) {
	push @Stack, "--*EOR*--\n";   ## Initial EOR when reversed
	while (<DATA>) {
		push @Stack, $_;
	}
	pop @Stack;
	while (@Stack) {
		$line = pop @Stack;
		## First out of stack is last EOR; ignore it,
		&process_line;
	}
} else {

	while (<DATA>) {
		$line=$_;
		&process_line;
	}
}


# Output .html footers 

    print STD "</table></html>";
    print BCP "</table></html>";
    print FYI "</table></html>";

##   Make log entry
##
$date = `date`;
chomp($date);
open(LOG,">>$logfile") ;
print LOG "xxx-index-html.pl: $date\n";
close(LOG);

##########################################################
##
##                END MAIN PROGRAM
##
###########################################################

sub process_line {
    chomp($line);
    ($cat, $value, $value2) = split(/:\s/, $line);
    if(defined $value2) {
	$value = $value.": ".$value2;
    }

    if ($cat eq "TYPE") {
	$type = $value;
	$typeL = lc $type;
	$typeU = uc $type;
    }
    elsif ($cat eq  "DOC-ID") {
	$docidabc = $value;
	$docidnum = $value;
	$docidabc =~ s/[0-9]//g;
	$docidnum =~ s/[a-zA-Z]//g;
	$docidnum2 = int $docidnum; 
    }

    elsif ($cat eq "--*EOR*--") {

	if (length $type == 0 || $type =~ /ien/i) {
		return;
	}

	# Use doc type to choose corresponding .html file
	#

	if ($typeL =~ /rfc/){
	    return;	
	}	

	$TYPE = STD if ($typeL =~ /std/);
	$TYPE = BCP if ($typeL =~ /bcp/); 
	$TYPE = FYI if ($typeL =~ /fyi/);

	my $title = $Vhash{"TITLE"};
	if ($title =~ /Not Iss/) {
		print $TYPE "<tr valign=top><td valign=top>$docidnum</td><td>$title.</td>\n";
		return;
	}

        ## Sub-series : one directory lower
        $URL =  "$sendto" . "$typeL/$typeL$docidnum2.txt";
        $path = "$where"  . "$typeL/$typeL$docidnum2.txt";
        print $TYPE "<tr valign=top><td valign=top>";
        if (-e $path) {
            print $TYPE "<a href=\"$URL\" target=\"_blank\">$typeU$docidnum2</a></td>\n";
        }
        else {
            print $TYPE "$typeU$docidnum2</td>\n";
        } 

	my $author = $Vhash{"AUTHORS"};
	my $date = $Vhash{"DATE"};
	print $TYPE "<td><b>$title</b> $author [ $date ] \n";

	my $format = $Vhash{"FORMAT"};
	$format =~ s/ASCII/TXT/g;
	if (length $Vhash{"CHAR-COUNT"} >1) {
		my $char_cnt = $Vhash{"CHAR-COUNT"};
		print $TYPE " \($format = $char_cnt bytes\)";
        }

	foreach $keyw ("OBSOLETES", "OBSOLETED-BY", "UPDATES",
		"UPDATED-BY", "SEE-ALSO", "CLARIFIES", "CLARIFIED-BY") {
		&link_print($keyw);
	}
	
	$status = $Vhash{"STATUS"};
	print $TYPE " \(Status: $status\)</td></tr>\n";

	$line="";
	$cat="";
	$value="";
	$docidabc = "";
	$type="";
	foreach $Key (keys %Vhash) {
		delete $Vhash{$Key};
	} 
	$wasparen = 0;
    }
    else {
	$Vhash{$cat} = $value;
    }

}



##################################################
#                                                #
# sub link_print(<keyword>)                      #
#                                                #
#       <keyvals>  passed globally in %vhash{}   #                                      #                                                #
#                                                #
##################################################

sub link_print() {

    my $keyword = $_[0];
    my $keytitle = $AttrNames{$keyword};
    my $keyvals = $Vhash{$keyword};
	$keyvals =~ s/\s//g;

    if (length $keyvals < 2) {
	return;
    }
    if ($wasparen > 0) {
	print $TYPE "\n";
    }

    print $TYPE "\($keytitle ";
    my @doc_ids = split (/,/, $keyvals);

    for($i=0; $i <= $#doc_ids; $i++) {

	my $docid = $doc_ids[$i];
	my $doctype = $docid;
	my $docidnum = $docid;
	$doctype =~ s/[0-9]//g;
	my $doctypeL = lc $doctype;
	my $doctypeU = uc $doctype;
	$docidnum =~ s/[a-zA-Z]//g;
	my $docidnum2 = int $docidnum; 

	if ($doctypeL =~ /rfc/) {
           	################
          	# Type is RFC  #
          	################

		my $URL = "$sendto" . "rfc/rfc$docidnum2.txt";
		my $path = "$where" . "rfc$docidnum2.txt";

		if (-e $path) {
		    print $TYPE "<a href=\"$URL\" target=\"_blank\">RFC$docidnum</a>";
		}
		else {
		    print $TYPE "RFC$docidnum\n";
		}
	    }

	    elsif ($doctypeL =~ /ien/) {

           #######################################
           # Type is IEN : two dirs down         #
           #######################################

	        $URL =   "$sendto" . "ien/ien/ien$docidnum2.txt";
	        $path =  "$where" . "ien/ien$docidnum2.txt";
	        if (-e $path) {
    		    print $TYPE "<a href=\"$URL\" target=\"_blank\">$doctypeU$docidnum</a>";
		}

		else {
		    print $TYPE "$doctypeU$docidnum";
		}
	    }
	    else {

           #######################################
           # Type is sub-series                  #
           #######################################
	
	        $URL =   "$sendto" . "$doctypeL/$doctypeL$docidnum2.txt";
	        $path =  "$where" . "$doctypeL/$doctypeL$docidnum2.txt";
	        if (-e $path) {
    		    print $TYPE "<a href=\"$URL\" target=\"_blank\">$doctypeU$docidnum</a>";
		}

		else {
		    print $TYPE "$doctypeU$docidnum";
		}
	    }
	    if($i < $#doc_ids) {
		print $TYPE ", \n";
	    }
	    else {
		print $TYPE "\)";
		$wasparen = 1;
	    }
	}

}






