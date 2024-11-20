#!/usr/bin/env python3
#
# create rfc-index.txt from rfc-index.xml
# John Levine, May 2015
#  Modified Apr 2019: Change from Adobe (PDF) to Portable Document Format (PDF) - PN
#  August 2019 : Modified rfcformat changes done rfc2txt.py and rfcindex.py
#                Changes are done to match the rfc-index.xml format modification - PN
#                Old format :    <format>
#                                   <file-format>file-format-1<file-format>
#                                   <char-count>respected char-dount<char-count>
#                                   <page-count>respected page-count<page-count>
#                                 </format>
#                New format : <format>
#                                  <file-format>file-format-1</file-format>
#                                  <file-format>file-format-2</file-format>
#                             </format>
# February 2020 : Added UTF-8 encoding for Title and Author field before writing to 
#                 output file - PN
# October 2021 : Removed UTF-8 encoding for Title and Author field before writing to 
#                 output file - PN
# Oct 2021 : minor changes for python3 - JRL

import rfcindex
import datetime
import argparse
import sys

debug = False

linelen = 74                            # maz line length for wrapping
boilerplate = """

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

                             RFC INDEX
                           -------------

(CREATED ON: {0}.)

This file contains citations for all RFCs in numeric order.

RFC citations appear in this format:

  ####  Title of RFC.  Author 1, Author 2, Author 3.  Issue date.
        (Format: ASCII) (Obsoletes xxx) (Obsoleted by xxx) (Updates xxx)
        (Updated by xxx) (Also FYI ####) (Status: ssssss) (DOI: ddd)

or

  ####  Not Issued.

For example:

  1129 Internet Time Synchronization: The Network Time Protocol. D.L.
       Mills. October 1989. (Format: TXT, PS, PDF, HTML) (Also RFC1119) 
       (Status: INFORMATIONAL) (DOI: 10.17487/RFC1129) 

Key to citations:

#### is the RFC number.

Following the RFC number are the title, the author(s), and the
publication date of the RFC.  Each of these is terminated by a period.

Following the number are the title (terminated with a period), the
author, or list of authors (terminated with a period), and the date
(terminated with a period).

The format follows in parentheses. One or more of the following formats 
are listed:  text (TXT), PostScript (PS), Portable Document Format 
(PDF), HTML, XML.

Obsoletes xxxx refers to other RFCs that this one replaces;
Obsoleted by xxxx refers to RFCs that have replaced this one.
Updates xxxx refers to other RFCs that this one merely updates (but
does not replace); Updated by xxxx refers to RFCs that have updated
(but not replaced) this one.  Generally, only immediately succeeding
and/or preceding RFCs are indicated, not the entire history of each
related earlier or later RFC in a related series.

The (Also FYI ##) or (Also STD ##) or (Also BCP ##) phrase gives the
equivalent FYI, STD, or BCP number if the RFC is also in those
document sub-series.  The Status field gives the document's
current status (see RFC 2026).  The (DOI ddd) field gives the
Digital Object Identifier.

RFCs may be obtained in a number of ways, using HTTP, FTP, or email.
See the RFC Editor Web page http://www.rfc-editor.org

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

                                RFC INDEX
                                ---------

"""

def writetext(rfcs, fo, nodois=False):
    """ write text version of RFCs to file fo
        nodois says leave out DOIS to make it look like it did before
    """
    if debug:
        print("In writetxt, rfcs",len(rfcs.rfcs))

    today = datetime.datetime.now(datetime.timezone.utc).strftime('%m/%d/%Y')
    print(boilerplate.format(today), file=fo)
    for rfcno in rfcs.keys(notissued=True):
        rfc = rfcs[rfcno]
        if 'not-issued' in rfc:
            rout = "{0} Not Issued.".format(rfcno[3:])
        else:
            authors = [a[0]+", Ed." if a[1] == "Editor" else a[0] for a in rfc['authors']]
            rfcdate = rfc['date']       # now includes day when available
            rfcformat = ", ".join([("TXT" if (f == "ASCII" or f == 'TEXT')else f)
                for f in rfc['format']])
            obsups = ""
            if 'obsoletes' in rfc:
                obsups += " (Obsoletes {0})".format(", ".join(rfc['obsoletes']))
            if 'obsoleted-by' in rfc:
                obsups += " (Obsoleted by {0})".format(", ".join(rfc['obsoleted-by']))
            if 'updates' in rfc:
                obsups += " (Updates {0})".format(", ".join(rfc['updates']))
            if 'updated-by' in rfc:
                obsups += " (Updated by {0})".format(", ".join(rfc['updated-by']))
            also = ""
            alsolist = []
            if 'is-also' in rfc:
                alsolist = rfc['is-also']
            if 'see-also' in rfc:
                alsolist += rfc['see-also']
            if alsolist:
                also = " (Also {0})".format(", ".join(alsolist))
            doi = ""
            if not nodois and 'doi' in rfc:
                doi = " (DOI: {0})".format(rfc['doi'])

            rout = "{0} {1}. {2}. {3}. (Format: {4}){5}{6} (Status: {7}){8}".format(
                rfcno[3:], rfc['title'],
                ", ".join(authors), rfcdate, rfcformat, obsups, also, rfc['current-status'], doi)

        print("", file=fo)
        # wrap at linelen
        while(len(rout) >= linelen):
            fold = rout.rfind(" ",0 ,linelen)
            if fold < 0:
                break                   # can't fold, the heck with it
            print(rout[:fold], file=fo)
            rout = "     " + rout[fold+1:] # spaces to make things line up
        print(rout, '', file=fo)


if __name__=="__main__":
    parser = argparse.ArgumentParser(description='Create RFC text index')
    parser.add_argument("--nodoi", action='store_true', help="Omit DOI info")
    parser.add_argument("--debug", action='store_true', help="Produce debug output")
    parser.add_argument("xml_in", type=str, help="Input RFC XML file")
    parser.add_argument("txt_out", type=str, nargs='?', help="Output RFC text file")
    args = parser.parse_args()

    debug = args.debug
    rfcs = rfcindex.RFCIndex(args.xml_in, debug=debug)  # read in RFCs from rfc-index format file
    
    if args.txt_out:
        try:
            with open(args.txt_out, "w") as fo:
                writetext(rfcs, fo, nodois=args.nodoi)
        except (OSError, IOError) as e:
            print("Cannot create", args.txt_out, e)
            exit(1)
    else:
        writetext(rfcs, sys.stdout, nodois=args.nodoi)
    exit(0)
