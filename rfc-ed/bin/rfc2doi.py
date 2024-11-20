#!/usr/bin/env python3
# 
# Upload DOI info to Crossref, taken from the XML RFC index
#
# John Levine, Standcore LLC
# Copyright 2015, 2021 by the IETF Trust
#
# 10/21 update for python3, simplify code JRL
#

import xml.etree.ElementTree as ET
import datetime
import argparse
import sys
import requests
import pickle
import rfcindex

#parameterfile = "/home/rfced/doiparameters.txt"
parameterfile = "doiparameters.txt"

# read in params, one per line, pairs of name value
# skips comment and blank lines
with open(parameterfile) as f:
   params = dict( l.strip().split(maxsplit=1) for l in f if l[:1] not in ('#','\n','') )

class DOIsub:
    """
    Create submission XML text for DOIs, based on dicts
    made from rfc index
    Upload it to crossref
    """

    # all Crossref XML in this namespace
    crversion = "4.4.2"
    namespace = "http://www.crossref.org/schema/{0}".format(crversion)

    def __init__(self, registrant=None, depositor=None, email=None, batchid=None, test=False):
        """ make the doi_batch and the head and body sections
            populate head section
        """

        # use test server?
        self.test = test
        if self.test:
            self.crdom = "test.crossref.org"
        else:
            self.crdom = "doi.crossref.org"

        
        # use defaults from parameter file
        if not registrant:
            registrant = params['doiregistrant']
        if not depositor:
            depositor = params['doidepositor']
        if not email:
            email = params['doiemail']

        # default batchid is rfce<datestamp>
        self.now = datetime.datetime.now(datetime.timezone.utc).strftime('%Y%m%d%H%M%S')
        if batchid:
            self.batchid = batchid
        else:
            self.batchid = "rfce"+self.now

        # see Crossref schema doc at http://help.crossref.org/deposit_schema
        self.root = ET.Element('doi_batch', attrib={
            "version": DOIsub.crversion,
            "xmlns": DOIsub.namespace,
            "xmlns:xsi": "http://www.w3.org/2001/XMLSchema-instance",
            "xsi:schemaLocation": "http://www.crossref.org/schema/{0} http://www.crossref.org/schema/deposit/crossref{0}.xsd".format(DOIsub.crversion)
        })

        head = ET.SubElement(self.root, "head")
        ET.SubElement(head, "doi_batch_id").text = self.batchid
        ET.SubElement(head, "timestamp").text = self.now

        dp = ET.SubElement(head, "depositor")
        ET.SubElement(dp, "depositor_name").text = depositor
        ET.SubElement(dp, "email_address").text = email

        ET.SubElement(head, "registrant").text = registrant

        # documents to be registered go into the body
        self.body = ET.SubElement(self.root, "body")

    def addrfc(self, rfc):
        """
        Add an RFC described by a hash from the RFCIndex class
        """
        rp = ET.SubElement(self.body, "report-paper")
        rp.append(ET.Comment("Translation of {0} {1}".format(rfc['doc-id'], rfc['stream'])))

        rpm = ET.SubElement(rp, "report-paper_metadata", language="en")

        # authors go into contributors section
        def doiauthor(author, sequence):
            """
            make a Crossref author chunk from a (name, role) pair
            HACK: if the second character of the name is a "." we assume it's a person, otherwise
            an organization
            """

            (aname, arole) = author
            arole = arole.lower()
            if "chair" in arole:
                arole = "chair"         # might be WG chair
            if "editor" in arole:
                arole = "editor"         # might be editors
            if arole not in ('author', 'editor', 'chair'):
                print("??? {0} unknown role {1} for {2}".format(rfc, arole, aname))

            if aname[1] == '.': # it's a person
                ae = ET.Element("person_name", sequence=sequence, contributor_role=arole)
                if " " in aname:
                    while "  " in aname:
                        aname = aname.replace("  ", " ") # squeeze out multiple spaces

                    (inits, lname) = aname.split(" ", 1)
                    ET.SubElement(ae, "given_name").text = inits
                    suffix = ""
                    if " " in lname:    # name with suffix
                        (blname, suffix) = lname.split(" ", 1)
                        if len(suffix) < 4: # Eastlake 3rd
                            ET.SubElement(ae, "surname").text = blname
                            ET.SubElement(ae, "suffix").text = suffix
                        else:   # van Brandenburg
                            ET.SubElement(ae, "surname").text = lname
                    else:   # ordinary last name
                        ET.SubElement(ae, "surname").text = lname
                else:
                    ET.SubElement(ae, "surname").text = aname
                return ae
            else:               # it's an organization
                oe = ET.Element("organization", sequence=sequence, contributor_role=arole.lower())
                oe.text = aname
                return oe

        co = ET.SubElement(rpm, "contributors")
        authors = rfc['authors']
        co.append(doiauthor(authors[0], "first"))
        for a in authors[1:]:
            co.append(doiauthor(a, "additional"))

        # title goes into titles section
        tis = ET.SubElement(rpm, "titles")
        ET.SubElement(tis, "title").text = rfc['title']
        
        # publication date 
        pd = ET.SubElement(rpm, "publication_date", media_type="online")
        ET.SubElement(pd, "month").text = "{0:02d}".format(rfc['monthno'])
        ET.SubElement(pd, "year").text = rfc['year']

        pu = ET.SubElement(rpm, "publisher")
        ET.SubElement(pu, "publisher_name").text = "RFC Editor"
        
        pi = ET.SubElement(rpm, "publisher_item")
        ET.SubElement(pi, "item_number").text = rfc['doc-id']

        # assign the doi, and the redirect URL
        # no timestamp, defaults to the one in the head
        dd = ET.SubElement(rpm, "doi_data")
        ET.SubElement(dd, "doi").text = rfc['doi']
        ET.SubElement(dd, "resource").text = params['doiurl'].format(rfc['doc-id'].lower())


    def write(self, filename):
        t = ET.ElementTree(self.root)
        t.write(filename)

    def __str__(self):
        return ET.tostring(self.root).decode('utf8')

    def upload(self, userid=None, userpw=None):
        """
        upload the current batch
        return triple of (batchfilename, statuscode, returned text)
        for details see http://help.crossref.org/using_http_to_post
        """

        if not userid:
            userid = params['crossrefuser']
        if not userpw:
            userpw = params['crossrefpw']

        self.uploadfilename = self.batchid+".xml"

        postargs = {
            "operation": "doMDUpload",
            "login_id": userid,
            "login_passwd": userpw
            }
        postfile = {
            "fname": ( self.uploadfilename, ET.tostring(self.root), "application/xml")
            }

        r = requests.post("https://{0}/servlet/deposit".format(self.crdom), data=postargs, files=postfile)
        return (self.uploadfilename, r.status_code, r.text)


################################################################

class DOIstatus:
    """
    maintain status of uploads in a pickled file

    self.rfcs = status for each rfc
    name is rfc1234 with "rfc" in lower case
    each entry is [ flag, batchfile ]
    flag = U uploaded
           S uploaded successfully

    self.batches = status for each batch
    name is the batchfilename typically rfce<timestamp>.xml
    each entry is [ timestamp, status, rfc list]
        timestamp is datetime
        status is U or S
        rfclist is list of rfcs in this upload
    
    """
    def __init__(self, statusfile=None, test=False):
        """
        load from status file, default to empty data
        """
        self.batches = {}
        self.rfcs = {}
        # use test server?
        self.test = test
        if self.test:
            self.crdom = "test.crossref.org"
        else:
            self.crdom = "doi.crossref.org"

        if not statusfile:
            statusfile = params['teststatusfile' if self.test else 'statusfile']
        try:
            with open(statusfile, "rb") as pf:
                self.batches = pickle.load(pf)
                self.rfcs = pickle.load(pf)
        except UnicodeDecodeError:  # old pickle, try old encoding
            with open(statusfile, "rb") as pf:
                self.batches = pickle.load(pf,encoding='latin1')
                self.rfcs = pickle.load(pf, encoding='latin1')
            print("Upgraded",statusfile,"to v3")
        except FileNotFoundError:
            pass                        # no such file yet, oh well
        return

    def save(self, statusfile=None):
        """
        save current version of the status file
        """
        if not statusfile:
            statusfile = params['teststatusfile' if self.test else 'statusfile']
        with open(statusfile, "wb") as pf:
            pickle.dump(self.batches, pf)
            pickle.dump(self.rfcs, pf)
        
    def addbatch(self, batchfile, rfcs):
        """
        add uploaded batch, with batchfile name and list of rfcs
        """
        self.batches[batchfile] = (datetime.datetime.now(datetime.timezone.utc), "U", rfcs)
        for rfc in rfcs:
            self.rfcs[rfc.lower()] = ( "U", batchfile )
        
    def checkbatch(self, batchfile):
        """
        check the status of a previously uploaded batch
        update batch status and RFCs
        return list of RFCs
        
        report is in Crossref doi_batch_diagnostic XML schema
        see http://help.crossref.org/tracking_submissions
        """

        didfail = False                 # did any part of the update fail ?
        print("Batch check of",batchfile)
        url = "https://{0}/servlet/submissionDownload".format(self.crdom)
        cbargs = {
                "usr":       params['crossrefuser'],
                "pwd":       params['crossrefpw'],
                "file_name": batchfile,
                "type":      "result"
            }
        r = requests.get(url, params=cbargs)
        code = r.status_code
        if code != 200:
            url.close()
            print("Check of batch {0} failed with code {1}".format(batchfile, code))
            return []

        rroot = ET.fromstring(r.text)
        if rroot.tag != "doi_batch_diagnostic":
            print("??? wrong XML for status: ", rroot.tag)
            return None

        rstatus = rroot.attrib['status']
        print("Batch {0} status {1}".format(batchfile, rstatus))
        if rstatus != "completed":
            return []
            
        rfcs = []
        for el in rroot.iter("record_diagnostic"):
            if el.attrib['status'] != "Success":
                msg = el.find('msg').text

                print("Batch {0} failed:\n{1}".format(batchfile, msg))
                didfail = True
                break
                
            doi = el.find('doi').text.lower()
            msg = el.find('msg').text
            print("result",doi,msg)
            if doi and "rfc" in doi:
                rfc = doi[doi.index("rfc"):]
                self.rfcs[rfc] = ("S", batchfile) # mark as done
                rfcs.append(rfc)
            else:
                didfail = True          # empty DOI in a failed update
                continue

            if el.attrib['status'] == 'Success':
                self.rfcs[rfc.lower()] = ("S", batchfile)
            else:
                print("??? DOI update failed for {0}: {1}".format(doi, msg))

        if didfail and batchfile in self.batches:     # any RFCs still pending in this batch won't work
            for rfc in self.batches[batchfile][2]: # RFCs in this batch
                if self.rfcs[rfc.lower()] == ("U", batchfile):
                    print("No update for {0}".format(rfc))
                    del self.rfcs[rfc.lower()]

        self.batches[batchfile] = (datetime.datetime.now(datetime.timezone.utc), "S", rfcs)
        return rfcs

    def checkall(self, agelimit=None):
        """
        check all internal batches that aren't marked as done
        if agelimit is set, delete completed batches more than agelimit days old
        """

        rfcs = []
        if agelimit:
            altime = datetime.datetime.now(datetime.timezone.utc) - datetime.timedelta(agelimit)

        # don't use iteritems because batches can be deleted
        klist = tuple(self.batches.keys())
        for batch in klist:
            bstatus = self.batches[batch]

            if bstatus[1] == 'S':
                if agelimit:
                    b0 = bstatus[0]
                    if b0.tzinfo is None:   # force into UTC if no timezone
                        b0 = datetime.datetime.combine(b0.date(), b0.time(), tzinfo=datetime.timezone.utc)
                    if b0 < altime: # too old and done
                        del self.batches[batch]
            else:
                rfcs.extend(self.checkbatch(batch))
        return rfcs


### 10/21 does not work, Crossref returns empty answers
    def resync(self):
        """
        use Crossref's Deposit Harvester to retrieve all of the DOIs it has and recreate the list
        of RFCs
        See http://help.crossref.org/memberdata
        Note that this service can take hours or maybe days to catch up with what's actually
        been deposited
        """

        rfcs = {}                       # new list of RFCs
        oans = "{http://www.openarchives.org/OAI/2.0/}" # some XML in this namespace
        crns = "{http://www.crossref.org/xschema/1.1}"  # some in this one
        rtoken = None                   # resumption token for multi-part results

        urlstring = "https://oai.crossref.org/DepositHarvester?"
        urlparams = {
            "verb":            "ListRecords",
            "metadataPrefix":  "cr_unixml",
            "set" :            "B:" + params['doiprefix'],
            "usr":             params['crossrefuser'],
            "pwd":             params['crossrefpw']
        }

        while True:                     # loop over partial bits with resumption tokens
            
            if rtoken:
                urlparams['resumptionToken'] = rtoken

            r = requests.get(urlstring, params=urlparams)
            print("fetch",urlparams)
            code = r.status_code
            if code != 200:
                print("Check of batch {0} failed with code {1}".format(batchfile, code))
                return None

            rroot = ET.fromstring(r.text)

            if rroot.tag != oans+"OAI-PMH":
                print("??? wrong XML for status: ", rroot.tag)
                return None

            # find next resumption token
            req = rroot.find(oans + "request")
            if "resumptionToken" in req.attrib:
                print("resumption is", req.attrib["resumptionToken"])
                rtoken = req.attrib["resumptionToken"]
            else:
                rtoken = None

            for r in rroot.iter(oans +"record"):
                rdoi = r.find(oans+"metadata/"+crns+"report-paper/"+crns+"report-paper_metadata/"+
                    crns+"doi_data/"+crns+"doi")
                if rdoi is None:
                    print("??? no DOI in record")
                    return None
                rrfc = rdoi.text[rdoi.text.index('/')+1:].lower()
                print("add",rrfc, end=" ")
                rfcs[rrfc] = ("S","harvest")

            if not rtoken:
                break
        # looped through
        self.rfcs = rfcs
        return rfcs
            
    def findnew(self, rfcs, dois, maxrfc=100, redo=False):
        """
            find RFCs that haven't been uploaded yet and add it to the DOI structure
            maxrfc limits the number to do
            redo False means only upload if it's never been uploaded
            redo True means also if uploaded and unconfirmed
            returns list of RFCs added to the dois structure
        """
        rfclist = []
        for r in rfcs.keys():
            rl = r.lower()
            #print("try", rl, maxrfc)
            if rl not in self.rfcs or (redo and self.rfcs[rl][0] != 'S'):
                dois.addrfc(rfcs.get(r))
                rfclist.append(r)
                maxrfc -= 1
                if maxrfc <= 0:
                    break
        return rfclist

    def __str__(self):
        """
        produce string version of the batch status for debugging
        """

        return "\n".join([
            "{0} {1} {2}\n    {3}".format(batch, bstatus[1], str(bstatus[0]), " ".join(bstatus[2]))
                for (batch,bstatus) in self.batches.items()])

    def rfcstatus(self, clean=False):
        """
        produce string version of the RFC status for debugging
        clean means clean out all undone RFCS
        """

        k = sorted(self.rfcs.keys())

        if clean:
            for rfc in k:
                if self.rfcs[rfc][0] == 'U':
                    del self.rfcs[rfc]

            k = self.rfcs.keys()
            sorted(k)

        return "\n".join([
            "{0} {1} {2}".format(rfc, self.rfcs[rfc][0], self.rfcs[rfc][1])
                for rfc in k])


################################################################

if __name__=="__main__":
    parser = argparse.ArgumentParser(description='Create DOI XML for upload to Crossref')
    parser.add_argument("--batch", type=int, help="Max batch size (default 100)")
    parser.add_argument("--write", type=str, help="Output RFC XML file")
    parser.add_argument("--check", type=str, help="check batch status, batch filename or @file")
    parser.add_argument("--checkall", type=int, help="Check batches, if N>0 delete done ones more than N days old")
    parser.add_argument("--upload", action='store_true', help="Upload to crossref")
    parser.add_argument("--batches", action='store_true', help="Status of batches")
    parser.add_argument("--rfcstatus", action='store_true', help="Status of RFCs")
    parser.add_argument("--rfcclean", action='store_true', help="Clean incomplete uploads")
#    parser.add_argument("--resync", action='store_true', help="Resync with Crossref database")
    parser.add_argument("--test", action='store_true', help="use Crossref test server")
    parser.add_argument("xml_in", nargs='?', help="Input RFC XML file")
    args = parser.parse_args()

    if args.batch or args.write or args.upload:
        if args.xml_in:                 # read in RFCs from rfc-index format file
            rfcs = rfcindex.RFCIndex(args.xml_in)
            dois = DOIsub(test=args.test)
        else:
            parser.print_help()
            exit(1)
    # isinstance because 0 is a value for checkall
    elif not (isinstance(args.checkall, int) or args.check or args.batches or args.rfcstatus or args.rfcclean):
        parser.print_help()
        exit(1)

    # load in previous status
    dstatus = DOIstatus(test=args.test)

    # create a Crossref batch
    if args.batch or args.write or args.upload:
        if args.batch:
            rfclist = dstatus.findnew(rfcs, dois, maxrfc=args.batch)
        else:
            rfclist = dstatus.findnew(rfcs, dois)
        if rfclist:
            print("batch contains", " ".join(rfclist))
        else:
            print("Empty batch, nothing written or uploaded")

    # write the batch to a file
    if args.write and rfclist:
        dois.write(args.write)

    # upload the batch to Crossref and add it to the status database
    if args.upload and rfclist:
        (batchfile, status, text) = dois.upload()
        dstatus.addbatch(batchfile, rfclist)
        print("uploaded batch", batchfile)

    # check all batches in the status database
    if isinstance(args.checkall, int):
        cav = dstatus.checkall(agelimit=args.checkall)
        print("Check found", " ".join(cav) if cav else "no batches")

    # check batch identified by filename, or @file with a list of them
    if args.check:
        if args.check[0] == '@':
            with open(args.check[1:], "r") as bf:
                for bfn in bf:
                    print("Check found", dstatus.checkbatch(bfn.strip()))
        else:
            print("Check found", " ".join(dstatus.checkbatch(args.check)))

    # report current batch status
    if args.batches:
        print (dstatus)

    # report current RFC status
    if args.rfcstatus:
        print (dstatus.rfcstatus())

    # clean out failed uploads and report
    if args.rfcclean:
        print (dstatus.rfcstatus(clean=True))

#    # resync status to Crossref
#    if args.resync:
#        print (dstatus.resync())

    # write out updated status
    dstatus.save()
