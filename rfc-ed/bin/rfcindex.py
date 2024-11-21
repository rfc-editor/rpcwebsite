#!/usr/bin/python3
# used in rfc2doi and rfc2txt
# RFCIndex class reads in an XML file in the format of rfc-index.xml
# and makes an internal table of the RFC entries.
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
#
# September 2021 : Modified for using Python3. Similar changes are done for rfc2doi
#                  and rfc2txt for server upgrade project - PN                    
#
# October 2021 : rewrote and simplicied code JRL
# August 2024 : Modified ns from http to https for rfc-index - PN

import xml.etree.ElementTree as ET

class RFCIndex:
    """
        Read rfc-index.xml and turn it into an internal dict
        also query functions for it
    """

    # months to month numbers
    months = ("???","January","February","March","April","May","June","July","August","September","October","November","December")

    def __init__(self, filename, debug=False):
        """
        create dict from input, argument is the input XML file name
        dict key is docid, value is a dict whose keys are the field
        names doc-id, title, stream, current-status, doi, authors, date,
        abstract, keywords, obsoletes, obsoleted-by, updates, updated-by,
        is-also, see-also, format, and not-issued

        fields don't exactly match input XML since they're intended to
        create the Crossref XML and text index.  Single value fields are
        strings, possibly multivalue fields are lists, and will be a one
        entry list if there's only one value

        abstract field is a string with the whole abstract glommed together
        author field is list of (author, title)
        format field is list of (format)

        if an RFC wasn't issued, the only field is not-issued which is True
        """

        self.xtree = ET.parse(filename)
        self.root = self.xtree.getroot()
        self.rfcs = dict()
        self.debug = debug
        if self.debug:
            print("start parsing XML file")
            print("ET version", ET.VERSION)
            print(self.root)
            for e in self.root[:10]:
                print(" ",e)
        # make this the default namespace
        ns = {'r': "https://www.rfc-editor.org/rfc-index"}

        # go through and find RFCs
        for el in self.root.findall('r:rfc-entry', ns):
            rfc = dict()
            docid = el.find('r:doc-id', ns).text
            rfc['doc-id'] = docid
            rfc['title'] = el.find('r:title', ns).text
            rfc['stream'] = el.find('r:stream', ns).text
            rfc['current-status'] = el.find('r:current-status', ns).text
            rfc['doi'] = el.find('r:doi', ns).text
            
            rfc['authors'] = []
            for al in el.findall('r:author', ns):
                author = al.find('r:name', ns).text
                tx = al.find('r:title', ns)
                title = tx.text if (tx is not None) else "Author"
                rfc['authors'].append((author,title))

            rd = el.find('r:date', ns)
            m = rd.find('r:month', ns).text
            rfc['month'] = m
            rfc['monthno'] = self.months.index(m)
            rdd = rd.find('r:day', ns)
            if rdd is not None:
                d = rdd.text
                rfc['day'] = d
            else:
                d = None
            y = rd.find('r:year', ns).text
            rfc['year'] = y
            if d:
                rfc['date'] = "{1} {0} {2}".format(m,d,y)
            else:
                rfc['date'] = "{0} {1}".format(m,y)

            ra = el.find('r:abstract', ns)
            if ra is not None:
                rfc['abstract'] = "\n".join(ap.text for ap in ra.findall('r:p', ns))

            rk = el.find('r:keywords', ns)
            if rk is not None:
                rfc['keywords'] = [ k.text for k in rk.findall('r:kw', ns) ]

            # link attributes to other RFCs
            for la in ('obsoletes', 'obsoleted-by', 'updates', 'updated-by', 'is-also', 'see-also'):
                ra = el.find('r:'+la, ns)
                if ra is not None:
                    rfc[la] = [ d.text for d in ra.findall('r:doc-id', ns) ]

            rf = el.find('r:format', ns)
            if rf is not None:
                rfc['format'] = [ d.text for d in rf.findall('r:file-format', ns) ]
                
            self.rfcs[docid] = rfc

        if self.debug:
            print("number of RFCs found", len(self.rfcs))

        # and not-issued RFCs
        for el in self.root.findall('r:rfc-not-issued-entry', ns):
            docid = el.find('r:doc-id', ns).text
            self.rfcs[docid] = { 'doc-id': docid, 'not-issued': True }

        if self.debug:
            print("number of RFCs and not issued found", len(self.rfcs))

    def get(self, rfcno):
        """ get one of the RFCs with no error if not there
        """
        if rfcno in self.rfcs:
            return self.rfcs[rfcno]
        else:
            return {}

    def __getitem__(self, rfcno):
        """ get one of the RFCs, usually called by subscripting the class object
        """
        if rfcno in self.rfcs:
            return self.rfcs[rfcno]
        else:
            raise AttributeError(rfcno)
        
    def keys(self, notissued=False):
        """
        sorted list of RFCs in this index
        optionally include notissued ones
        """

        if notissued:
            k = list(self.rfcs.keys())
        else:
            k = [ rfc for rfc in self.rfcs.keys() if 'not-issued' not in self.rfcs[rfc] ]
            
        k.sort()                        # sort in place
        return k

    def doi(self, rfc):
        """
        return the DOI for an RFC
        for testing can invent an appropriate one if it doesn't already have one
        """

        if 'doi' not in rfc:
            return None
            #rfc['doi'] = "{0}/{1}".format(params['doiprefix'], rfc['doc-id'].lower())
        return rfc['doi']
