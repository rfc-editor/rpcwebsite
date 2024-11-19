#! /bin/sh
## $Id: create-indexes.sh,v 2.4 2021/02/01 19:32:58 priyanka Exp $
##  Create 5 index.txt files: rfc-index.txt, std-index.txt, bcp-index.txt,
##  fyi-index.txt, and ien-index.txt, from INDEX.
##
##  Also invokes xxx-index-html.pl twice, to create 8 rfc/sss-index.html files:
##  rfc-index.html, std-index.html, bcp-index.html, fyi-index.html, 
##  rfc-index2.html, std-index2.html, bcp-index2.html, fyi-index2.html,
##  from INDEX.  Second 4 are in reverse direction.
##
##	Creates one-line log entry in ~rfc-ed/logs/create-indexes.log
#
##  Written: October 2007  Bob Braden
##
##      Replaces 4 scripts xxx-list written 14Sep2001 and modified 7 times
##	since then by Braden, Emad, gjthomas, and falk.  Also replaces
## 	the common driver new.xxx-index.
##
##  Modified Sept 2008: Add Clarifies, Clarified By keywords.
##  Modified May 2015: don't make rfc-index.txt, now made separately
##  Modified Apr 2019: Change from Adobe (PD) to Portable Document Format (PDF)

cd /in-notes

## Delete old files, if any.
##

#rm -f rfc-index.txt
rm -f bcp-index.txt
rm -f std-index.txt
rm -f fyi-index.txt
rm -f ien-index.txt
rm -f /rfc-ed/temp.index.txt

##
##  Prefix each xxx-index.txt file with its explanatory text
##

echo "

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

                             STD INDEX
                           -------------

`date '+CREATED ON: %m/%d/%Y.'`

This file contains citations for all STDs in numeric order.  Each
STD represents a single Internet Standard technical specification,
composed of one or more RFCs with Internet Standard status.

STD citations in this file appear in the following format:

  ####  Title of STD.  Author 1, Author 2, Author 3.  Issue date.
        (Format: ASCII) (Obsoletes xxx) (Obsoleted by xxx) (Updates xxx)
        (Updated by xxx) (Also RFC####, ...)

For example:

  0006 User Datagram Protocol. J. Postel. 28 August 1980. (Format: TXT=5896
       bytes) (Also RFC0768)

Key to citations:

#### is the STD number.

Following the number are the title (terminated with a period), the
author, or list of authors (terminated with a period), and the date
(terminated with a period).

The format and length information follows in parentheses.  It lists
some combination of ASCII text (TXT), PostScript (PS), and/or Portable 
Document Format (PDF), each followed by an equals sign and the number 
of bytes for that version.  For example (Format: TXT=aaaaa, PS=bbbbbb bytes) 
shows that the ASCII text version is aaaaa bytes, and the PostScript 
version is bbbbbb bytes.

Finally, the (Also RFC ##) phrase lists the RFC(s) that form this STD.

RFCs may be obtained using HTTP, FTP, or email.  See the RFC Editor Web
page http://www.rfc-editor.org


~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

                                STD INDEX
                                ---------
   " >> std-index.txt



echo "

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

                             BCP INDEX
                           -------------

`date '+CREATED ON: %m/%d/%Y.'`

This file contains citations for all BCPs in numeric order.  The BCPs
form a sub-series of the RFC document series, specifically those RFCs
with the status BEST CURRENT PRACTICE.

BCP citations appear in this format:

  #### Title of BCP.  Author 1, Author 2, Author 3.  Issue date.
       (Format: ASCII) (Obsoletes xxx) (Obsoleted by xxx) (Updates xxx)
       (Updated by xxx) (Also RFC####)

For example:

  0009 The Internet Standards Process -- Revision 3. S. Bradner. October
       1996. (Format: TXT=86731 bytes) (Obsoletes RFC1602) (Also RFC2026)

Key to fields:

#### is the BCP number.  The remainder of the fields are the same as
those of the corresponding RFC, whose number is given in the final
(Also RFC####) field.

Following the BCP number are the title, the author(s), and the
publication date of the RFC.  Each of these is terminated by a period.

The format and length of the RFC follow in parenthesis.  One or more of
the following alternative formats are listed:  ASCII text (TXT),
PostScript (PS), and/or Portable Document Format (PDF). Each format is 
followed by an equals sign and the number of bytes for that version.  
For example (Format:  TXT=aaaaa, PS=bbbbbb bytes) shows that the ASCII 
text version is aaaaa bytes, and the PostScript version of the RFC is 
bbbbbb bytes.

"Obsoletes xxxx" refers to other RFCs that this one replaces;
"Obsoleted by xxxx" refers to RFCs that have replaced this one.
"Updates xxxx" refers to other RFCs that this one merely updates (but
does not replace); "Updated by xxxx" refers to RFCs that have updated
(but not replaced) this one.  Generally, only immediately succeeding
and/or preceding RFCs are indicated, not the entire history of each
related earlier or later RFC in a related series.

BCPs and other RFCs may be obtained using HTTP, FTP, or email.  See the
RFC Editor Web page http://www.rfc-editor.org.

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

                                BCP INDEX
                                ---------
   " >> bcp-index.txt

echo "

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

                             FYI INDEX
                           -------------

`date '+CREATED ON: %m/%d/%Y.'`

This file contains citations for all FYIs in numeric order.  The FYIs
("For Your Information") documents form a sub-series of the RFC series,
specifically those documents that may be of particular interest
to Internet users.  The corresponding RFCs have status INFORMATIONAL.

FYI citations appear in this format:

  ####  Title of FYI.  Author 1, Author 2, Author 3.  Issue date.
        (Format: ASCII) (Obsoletes xxx) (Obsoleted by xxx) (Updates xxx)
        (Updated by xxx) (Also RFC####)

For example:

  0008 Site Security Handbook. B. Fraser. September 1997. (Format:
       TXT=191772 bytes) (Obsoletes RFC1244) (Also RFC2196)

Key to citations:

#### is the FYI number. The remainder of the fields are the same as
those of the corresponding RFC, whose number is given in the final
(Also RFC####) field.

Following the BCP number are the title, the author(s), and the
publication date of the RFC.  Each of these is terminated by a period.

The format and length follow in parenthesis.  One or more of the
following alternative formats are listed:  ASCII text (TXT), PostScript
(PS), and/or Portable Document Format (PDF).  Each format is followed 
by an equals sign and the number of bytes for that version.  For example 
(Format:TXT=aaaaa, PS=bbbbbb bytes) shows that the ASCII text version is 
aaaaa bytes, and the PostScript version of the RFC is bbbbbb bytes.

"Obsoletes xxxx" refers to other RFCs that this one replaces;
"Obsoleted by xxxx" refers to RFCs that have replaced this one.
"Updates xxxx" refers to other RFCs that this one merely updates (but
does not replace); "Updated by xxxx" refers to RFCs that have updated
(but not replaced) this one.  Generally, only immediately succeeding
and/or preceding RFCs are indicated, not the entire history of each
related earlier or later RFC in a related series.

FYIs and other RFCs may be obtained using HTTP, FTP, or email.  See the
RFC Editor Web page http://www.rfc-editor.org

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

                                FYI INDEX
                                ---------
   " >> fyi-index.txt

nawk '

function print_field(field_tag, I) {
	if ($2 == "") return;
	printf " (%s", field_tag;
	print_words();
	printf "%s",  ")" ;
}

function print_words(I) {
	I = 2;
	while (I <= NF && $I != "") {
		printf " %s", $(I++);
	}
}

/TYPE:/ { type = toupper($2); next };

/DOC-ID:/ { printf "\n\n%s ", $2; next; }
## Output docid now, strip off three leading letters in next pass

##  Not Issued => the FORMAT, CHAR-COUNT, PAGE-COUNT, STATUS, and PUB-STATUS
##  fields of INDEX entry are set.  Ignore them.
##
/TITLE: Not Issued/ {
        printf "Not Issued.";
        Ignore = 1;
        next;
}

/TITLE:/ {
	Ignore = 0;
 	print_words();
	if ($(NF) !~ /\.\]/) printf ".";
	next;
}

/AUTHORS:/ {
 	if ($2 ~ /[^ ]/ ){
		print_words();
		printf ".";
	}
	next;
}

/DATE:/ {
 	if ($2 != "") {
		print_words();
		printf ".";
	}
	next;
    }
	
/FORMAT:/ {
 	## Remove keyword and leading blanks, then split on comma
        $1 = "";
	gsub("^ ", "");
	gsub("ASCII", "TXT");
        fmtmax = split($0, savefmt, ",[ ]*");
	next;
}

/CHAR-COUNT:/ {
	if (Ignore == 1) { next; }
###
### An issue here: should we give char-count for sub-series entries??
### The following code does...
###
	if (NF <= 1) {
		if (type == "RFC") { 
			printf  " %s", "(Not online)";
			## But a sub-series doc cannot be offline.
		}
	}
	else {
		printf  " (Format:";
		for (fmtcnt = 1; fmtcnt <= fmtmax; fmtcnt++) {
			printf  " %s=%s", savefmt[fmtcnt], $(fmtcnt+1);
		}
		printf " bytes)";
	}
	next;
}

/OBSOLETES:/ {
	print_field("Obsoletes");
}

/OBSOLETED-BY:/ {
	print_field("Obsoleted by");
}

/UPDATES:/ {
	print_field("Updates");
};

/UPDATED-BY:/ {
	print_field("Updated by");
}

/SEE-ALSO:/ {
	print_field("Also");
}

/CLARIFIES:/ {
	print_field("Clarifies");
}

/CLARIFIED-BY:/ {
	print_field("Clarified by");
}


/PUB-STATUS:/ { next; }   # rfc-index.txt does not display this

/STATUS:/ { 
	if (Ignore == 1) { next; }
	if (type == "RFC")
		printf " (Status: %s)", substr($0, 9);
	next;
}

/--*EOR*--/ {
	next;
}' /home/rfc-ed/INDEX |sed -e 's/ \./\./g' | sed -e 's/ )/)/g' >/home/rfc-ed/temp.index.txt

##
##  Second pass to split entries into TYPE-specific files
##

nawk '

BEGIN {
	OUTDIR = "/a/ftp/in-notes/"
#	filemap["rfc"] = OUTDIR "rfc-index.txt";
	filemap["std"] = OUTDIR "std-index.txt";
	filemap["bcp"] = OUTDIR "bcp-index.txt";
	filemap["fyi"] = OUTDIR "fyi-index.txt";
	filemap["ien"] = OUTDIR "ien-index.txt";
}

/^[A-Z]/ {
	type = tolower(substr($0,1,3));
	if(type == "rfc")	# now made separately
		next

	outfile = filemap[type];

	$0 = substr($0,4);

	# Now fold lines nicely on word boundaries
	#
	printf "\n\n" >> outfile;
	printf "%s", $1 >> outfile;
	linesz = 4;
	for (i=2; i<=NF; i++) {
		thislen = length($i) + 1;
	    
		if (linesz + thislen >70) {
			printf "\n    " >> outfile;
			linesz = 0;
		}
		linesz += thislen;
		printf " %s", $i >> outfile;
	}
	next;
}
END {
	for (Type in filemap) {
		printf "\n" >> filemap[Type];
	}
        system("date > /home/rfc-ed/bin/mydate.txt");
        getline < "/home/rfc-ed/bin/mydate.txt";
	print "create-indexes.sh: ", 
                  $0 >> "/home/rfc-ed/logs/create-indexes.log";
}' /home/rfc-ed/temp.index.txt

# set file permissions
chmod 666 ???-index.txt

##
## Invoke scripts to create HTML version in both directions
##
/rfc-ed/bin/xxx-index-html.pl up
/rfc-ed/bin/xxx-index-html.pl down
