#!/usr/bin/perl 
# $Id: create-categories.pl,v 2.3 2013/01/18 21:21:30 ahagens Exp $
#####################################################################
#                                                                   #
#  o Name: create-categories.pl                                     #
#                                                                   #
#                                                                   #
#  [Description]                                                    #
#                                                                   #
#  This script produces the web directories for RFCs by category    #
#  (/nfs/jade/web/rfc-editor.org/htdocs/categories/rfc-ccccccc)     #
#  from the master INDEX file.                                      #
#  It must be run whenever INDEX is edited.                         #
#                                                                   #
#  o Updated 15 Jan 01  Bob Braden                                  #
#  o Updated 21 Mar 01  Dong-Jin Son                                #
#  o Updated 22 Mar 10  Ryan Cross - changed to use httpurl         # 
#                                                                   #
#####################################################################

$ftpurl		= "ftp://ftp.rfc-editor.org/in-notes";
$httpurl	= "http://www.rfc-editor.org/rfc";
$index		= "/home/rfc-ed/INDEX";
$catbase	= "/a/www/rfc/htdocs/categories";
##$catbase	= "/nfs/jade/rfc-ed/bin.braden/categories";

@categories			= ("INTERNET STANDARD", "DRAFT STANDARD",
                                   "PROPOSED STANDARD",
				   "BEST CURRENT PRACTICE",
                                   "FYI", "EXPERIMENTAL",
                                   "INFORMATIONAL",
				   "HISTORIC", "UNKNOWN");
$titles{'INTERNET STANDARD'}	= "Internet Standards";
$titles{'DRAFT STANDARD'}	= "Draft Standards";
$titles{'PROPOSED STANDARD'}	= "Proposed Standards";
$titles{'BEST CURRENT PRACTICE'}= "Best Current Practice (BCP) Sub-Series";
$titles{'FYI'}                  = "For Your Information (FYI) Sub-Series";
$titles{'EXPERIMENTAL'}		= "Experimental";
$titles{'INFORMATIONAL'}	= "Informational";
$titles{'HISTORIC'}		= "Historic";
$titles{'UNKNOWN'}		= "Unclassified";

%filehandles			= ();



# open the index file, then open all of the rfc-*.html files for writing
# and put header in each.
#
open (INDEX, $index)		|| die "Could not open $index, $!\n";

foreach (@categories) {
    ($filename, $filehandle) = "";

    /(\w+)/ && do { $filehandle = $1 };

    if ($filehandle eq "INTERNET") {
	$filename = "STANDARD";
    }
    else {
    $filename = $filehandle;
    }
    $filename =~ tr/A-Z/a-z/;
    $filename = "$catbase/rfc-$filename" . ".html";

    $filehandles{$_} = $filehandle;
    open($filehandles{$_}, ">$filename") 
			|| die "Could not open $filename, $!\n";

    &print_header ($filehandles{$_}, $titles{$_});
}


# read INDEX file and put each RFC into category file.
#
#
$line	= 0;
while (<INDEX>) {
    $line++;

    /^\-\-\*EOR\*\-\-/	&& do { &print_entry(); next; };
    /^TYPE: (.*)/	&& do { $type = $1; next; };
    /^DOC-ID: (.*)/	&& do { $docid = $1; next; };
    /^TITLE: (.*)/	&& do { $title = $1; next; };

    /^OBSOLETED-BY: (.*)/ && do {$obsoleted = $1; next; };
    /^SEE-ALSO: (.*)/	&& do { $seealso = $1; next; };
    /^STATUS: (.*)/	&& do { $cat = $1; next; };


}

# Print footer and close each category file
#
#
foreach (@categories) {
    &print_footer ($filehandles{$_});
    close ($filehandles{$_}) || warn "Could not close file, $!\n";
}

##
## DONE
##


sub print_entry {

## This assumes SEE-ALSO: field contains only a single entry when
## it is a pointer to a BCP or STD.
##
    return if ($cat eq "NOT ISSUED" || $title eq "");

    $seealsoabc=$seealso;
    $seealsonum=$seealso;
    $seealsoabc =~ s/[0-9]//g;
    $seealsoabc2=lc($seealsoabc);
    $seealsonum =~ s/[a-zA-Z]//gi;

    if ($seealsoabc2 eq "std") {
	$std = int $seealsonum;
    }

    if ($type eq "RFC") {
   	$rfc = $docid;
    }
    elsif ($type eq "FYI") {

	## Should check here that SeeAlso is really an RFCnnnn
	$rfc = $seealso;
	$fyinum = $docid;
#	$fyinum  =~ s/[a-zA-Z]//gi;
	$fyinum =~ s/fyi[0]+/fyi/;
	$cat = "FYI";
    }
    else {
        return;
    }

    $rfc_name = $rfc;

    $rfc =~ tr/A-Z/a-z/;
    $rfc =~ s/rfc[0]+/rfc/;
    $filehandle = $filehandles{$cat};

    if (length $obsoleted > 3) {
	$start_bold = "<font color=red>";
	$end_bold = "</font>";
        $comment_st = "(Obsoleted by: ";
        $comment_end = ")";

    }
    else {
	$start_bold = "<b>";
	$end_bold = "</b>";
        $comment_st ='';
        $comment_end ='';
    }

    $printthis = "<a href=\"$httpurl/$rfc.txt\"><b>$rfc_name</b>";

    ##
    ##   DISPLAY SUB-SERIES ENTRIES
    ##

    $space =0;

    if ($seealsoabc2 eq "std" && $cat eq "INTERNET STANDARD" ) {
	$printthis = "$printthis &nbsp; ($seealso)" ;
	$space = 1;
    }
    elsif ($seealsoabc2 eq "bcp" && $cat eq "BEST CURRENT PRACTICE") {
	$printthis = "$printthis &nbsp; ($seealso)" ;
	$space = 1;
    }
    elsif ($type eq "FYI") {
	$printthis = "$printthis ($fyinum)" ;
	$space = 1;
    } 
			
    if ($space eq 1) {
	$addtab = "&nbsp;&nbsp;&nbsp;";
    }
    elsif ($filehandle eq 'INTERNET STANDARD'){ 
        $addtab = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"
    }
    else {
	 $addtab = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
    }

    $printthis = $printthis . "</a>   $addtab$start_bold $title $end_bold &nbsp;$comment_st$obsoleted$comment_end<br>\n";
    
    print $filehandle "$printthis";

    &reset_vars;
}


sub reset_vars {
    $printthis  = "";
    $type	= "UNKNOWN";
    $cat	= "unknown";
    $docid	= "";
    $seealso	= "";
    $title	= "";
    $std        = "";
    $obsoleted ="";
}


sub print_header {
    ($file, $htmltitle) = @_;

    $date = `date`;

    print $file "
<html>
<head>
   <meta http-equiv=\"Content-Type\" CONTENT=\"text/html; charset=iso-8859-1\">
   <title> $htmltitle </TITLE>
</head>
<body bgcolor=\"#FFFFFF\" text=\"#000000\" link=\"#0000FF\" vlink=\"#FF0000\">

<h1><c>$htmltitle</c></h1>

<font size =4>-In each of these lists, RFC titles are shown in <b>bold-face</b> if they are current; obsoleted RFCs are shown in <font color =red>light red</font></font>

<HR>
<br>
";
}


sub print_footer {
    local ($file) = @_;
    print $file	"
<HR>
<br><p>

<address>

<A HREF=\"http://www.rfc-editor.org/category.html\">Go back to Category page</A><br>
<A HREF=\"http://www.rfc-editor.org/index.html\">Go back to RFC Editor home page.</a><br>
<p>
This page is updated by the RFC Editor whenever necessary.
Please send <a href=\"mailto:rfc-editor\@rfc-editor.org\">mail</a> about any
problems with or comments on this page. <br>

Last modified: $date

</address>

</body>
</html>";
}
