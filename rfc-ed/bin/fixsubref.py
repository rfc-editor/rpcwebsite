#!/usr/bin/env python3
# find references in XML drafts to subsections of RFCs and IDs and
# make them link to the subscection
#
# John Levine, August 2021
#
# Copyright 2021 by the IETF Trust

import re

class Fixsubref:
    # in patterns, <s> is section number <r> is RFC number, <x> is
    # xref element.  Patterns can have <r> or <x> but not both
    
    pats = [
        r'(?P<x><xref[^>]+>)RFC(\s*|&nbsp;)\d+\s*Section\s*(?P<s>[0-9]([0-9.]*\d)?)\s*</xref>',

        r'section\s+(?P<s>[0-9]([0-9.]*\d)?)\s+of\s+RFC(\s*|&nbsp;)(?P<r>[0-9]{4})',
        r'section\s+(?P<s>[0-9]([0-9.]*\d)?)\s+of\s+(?P<x><xref[^>]+>)(</xref>)?',

        r'RFC(\s*|&nbsp;)(?P<r>[0-9]{4}),?\s+sections?\s+(?P<s>[0-9]([0-9.]*\d)?)',
        r'(?P<x><xref[^>]+>)(</xref>)?,?\s+sections?\s+(?P<s>[0-9]([0-9.]*\d)?)',
        ]

    def __init__(self, verbose=False, pats=None):
        if pats:
            self.pats = pats
        else:
            self.pats = Fixsubref.pats
        self.verbose = verbose

    def fixxmlfile(self, infile, outfile=None):
        """
        read and fix a file
        returns input,output file name
        """
        if not outfile:
            if '.' not in infile:
                outfile = infile + ".fix.xml"
            else:
                a, b = infile.rsplit('.', maxsplit=1)
                outfile = "{}.fix.{}".format(a, b)

        inxml = ""
        with open(infile, "r") as f:
            inxml = f.read()

        outxml = self.fixxmlstr(inxml)

        with open(outfile, "w") as fo:
            fo.write(outxml)
        return (infile, outfile)

    def fixxmlstr(self, inxml):
        """
        fix an XML RFC or draft as a string
        """

        # make a list of all of the pattern matches for each pattern
        for pat in self.pats:
            if self.verbose:
                print("pat", pat)
            cpat = re.compile(pat, flags=re.I)
            matches = []

            for cm in cpat.finditer(inxml):
                matches.append( ( cm.start(), cm.end(), cm.groupdict(), cm.group(0)) )

            if not matches:
                continue

            # section display format
            mcomma = 'comma' if ',' in pat else 'of'

            # do in reverse order to so inxml offsets are still correct
            for m in reversed(matches):
                mstart, mend, mdict, mmatch = m
                # get the reference target
                if 'r' in mdict:
                    target = 'RFC'+mdict['r']
                    tadd = ""
                elif 'x' in mdict:
                    m = re.match(r'<xref\s+target\s*=\s*"([^"]+)"\s*([^/]*)/?>', mdict['x'])
                    if not m:
                        print("mystery xref",mdict['x'])
                        exit(1)
                    target = m.group(1)
                    tadd = m.group(2)       # extra junk in xref
                else:
                    print("mystery match", m)
                    exit(1)

                repl = '<xref target="{0}" section="{1}" sectionFormat="{2}" {3}/>'.format(target, mdict['s'], mcomma, tadd)
                if self.verbose:
                    print("replace",mstart,repr(mmatch),"->",repl,mdict)
                    
                inxml = inxml[:mstart] + repl + inxml[mend:]

        return inxml
                    

if __name__ == "__main__":
    import argparse

    parser = argparse.ArgumentParser(description='Fix section references')
    parser.add_argument('-v', action='store_true', help="verbose")
    parser.add_argument('infile', type=str)
    parser.add_argument('outfile', type=str, nargs='?')
    args = parser.parse_args()

    f = Fixsubref(verbose=args.v)
    z = f.fixxmlfile(args.infile, args.outfile)
    print("fixed",z[0],"to",z[1])
