#!/usr/bin/env python3
#
# make sitemaps and a siteindex that includes them
# RFC map from rfc-index.xml has all RFCs
# BCP/STD map from rfc-index.xml has all BCPs and STDs
# misc sitemap by scraping the html sitemap
# siteindex refers to those three maps
#
# John Levine, Standcore LLC 2022
# Copyright 2022 by the IETF Trust

import argparse
import xml.etree.ElementTree as ET
import datetime
import os

# these are not in the standard library
import requests
from bs4 import BeautifulSoup

# URLs for various document formats

urlbase = "https://www.rfc-editor.org"

infourl = urlbase + "/info/rfc{0}"
txturl = urlbase + "/rfc/rfc{0}.txt"
txtpdfurl = urlbase + "/rfc/pdfrfc/rfc{0}.txt.pdf"
htmlurl = urlbase + "/rfc/rfc{0}.html"
psurl = urlbase + "/rfc/rfc{0}.ps"
pdfurl = urlbase + "/rfc/rfc{0}.pdf"
xmlurl = urlbase + "/rfc/rfc{0}.xml"

bcpurl = urlbase + "/info/bcp{0}"
stdurl = urlbase + "/info/std{0}"

# HTML sitemap
mapurl = urlbase + "/sitemap/"

# input XML namespaces
ns = { 'r': 'http://www.rfc-editor.org/rfc-index',
       's': "http://www.sitemaps.org/schemas/sitemap/0.9"
    }

months = {'January': 1, 'February': 2, 'March': 3, 'April': 4, 'May': 5, 'June': 6, 'July': 7, 'August': 8,
    'September': 9, 'October': 10, 'November': 11, 'December': 12
}
    
# empty sitemap
mapbase = """<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
</urlset>
"""

# empty site index
indexbase = """<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
</sitemapindex>
"""

# HACK HACK HACK old ElementTree doesn't have indent function
# so don't call it if it's not there
doindent = 'indent' in ET.__dict__

# some parameters
debug = False
# site map times
rfctime = bcptime = misctime = None

parser = argparse.ArgumentParser(description='Create site maps')
parser.add_argument('-d', action='store_true', help="debug")
parser.add_argument('-r', action='store_true', help="make RFC map")
parser.add_argument('--rmap', help="RFC map", default="rfcsitemap.xml")
parser.add_argument('-b', action='store_true', help="make BCP/STD map")
parser.add_argument('--bmap', help="BCP map", default="bcpsitemap.xml")
parser.add_argument('-m', action='store_true', help="make site index for misc map")
parser.add_argument('--mmap', help="Misc map from siteindex", default="miscsitemap.xml")
parser.add_argument('--index', help="site index", default="siteindex.xml")
parser.add_argument('-c', action='store_true', help="check that RFC links exist")
parser.add_argument('infile', help="RFC XML index")
args = parser.parse_args();

debug = args.d

# slurp in RFC index
xmlindex = ET.parse(args.infile)

# no prefixes on our sitemapes
ET.register_namespace('', 'http://www.sitemaps.org/schemas/sitemap/0.9')

checkurl = args.c

# these globals change when we change maps
curmap = None
freq = "yearly" # url update frequency in map

# utility fn to add an entry to a map
def addref(urlpat, rfcno, refdate):
    """
    add a reference to the current sitemap

    urlpat: pattern for url to add
    rfcno: number of rfc or whatever to plug in
    refdate: apprximate change date, need not be very accurate
    
    map is in curmap, update rate in freq
    """

    global curmap, freq, numrefs

    if rfcno is not None:
        rurl = urlpat.format(rfcno)
    else:
        rurl = urlpat

    if checkurl:    # see if this URL exists
        try:
            r = requests.head(rurl)
            if debug:
                print(".", sep="", end="", flush=True)
            if r.status_code != 200:
                print("missing",rurl)
        except requests.exceptions.ConnectionError as err:
            print("barf",rurl,err)
        
    url = ET.Element("url")
                
    l = ET.Element("loc")
    l.text = rurl
    url.append(l)

    l = ET.Element("lastmod")
    l.text = refdate
    url.append(l)

    l = ET.Element("changefreq")
    l.text = freq
    url.append(l)
    curmap.append(url)
    numrefs += 1
    
# collect RFCs and also dates for BCP and STD

numrefs = 0
rfcmap = ET.fromstring(mapbase)
curmap = rfcmap

rfcdates = dict()   # for BCP and STD dates

for rfc in xmlindex.findall('r:rfc-entry', ns):
    docid = rfc.find('r:doc-id', ns).text
    rfcno = int(docid[3:])

    rfcdate = rfc.find('r:date', ns)
    rfcmon = rfcdate.find('r:month', ns).text
    rfcyr = rfcdate.find('r:year', ns).text
    rdate = "{0:04d}-{1:02d}-01".format(int(rfcyr), months[rfcmon])
    rfcdates[rfcno] = rdate
    if debug:
        print("===", docid, rfcno, rdate)

    format = rfc.find('r:format', ns)
    formats = set( ff.text.lower() for ff in format.findall('r:file-format', ns))

    # all have info
    addref(infourl, rfcno, rdate)

    if 'text' in formats or 'ascii' in formats:
        addref(txturl, rfcno, rdate)
        if 'xml' not in formats:
            addref(txtpdfurl, rfcno, rdate)

    if 'html' in formats:
        addref(htmlurl, rfcno, rdate)

    # if XML, has xml and PDF
    if 'xml' in formats:    # XML RFCs all have text, html, xml, pdf
        addref(xmlurl, rfcno, rdate)
        addref(pdfurl, rfcno, rdate)
    elif 'ps' in formats:   # ps should also be converted to PDF
        addref(psurl, rfcno, rdate)
        addref(pdfurl, rfcno, rdate)
    elif 'pdf' in formats:    # original PDF
        addref(pdfurl, rfcno, rdate)
            
# write it out
print("number of refs", numrefs)

if args.r:
    if doindent:
        ET.indent(rfcmap)
    rfcet = ET.ElementTree(rfcmap)

    with open(args.rmap, "wb") as fo:
        rfcet.write(fo, encoding="UTF-8", xml_declaration=True)
        fo.write(b'\n')
    print("Wrote rfc map", args.rmap)
    rfctime = datetime.datetime.now(tz=datetime.timezone.utc)

# BCPs and STDs
# create entry for each BCP or STD that corresponds to RFCs

if args.b:
    bcpmap = ET.fromstring(mapbase)
    curmap = bcpmap
    numrefs = 0
    for rfc in xmlindex.findall('r:bcp-entry', ns):
        docid = rfc.find('r:doc-id', ns).text
        bcpno = int(docid[3:])
        if debug:
            print("===",docid,bcpno)

        # find most recent RFC for the date
        isalso = rfc.find('r:is-also', ns)
        if not isalso:
            if debug:
                print(" no RFCs, skipped")
            continue
        # get most recent rfc date
        docids = [ int(did.text[3:]) for did in isalso.findall('r:doc-id', ns) ]
        maxdocid = max(docids)
        docdate = rfcdates[maxdocid]
        if debug:
            print(" docids", docids, maxdocid, docdate)
        addref(bcpurl, bcpno, docdate)

    numbcps = numrefs
    numrefs = 0
    for rfc in xmlindex.findall('r:std-entry', ns):
        docid = rfc.find('r:doc-id', ns).text
        stdno = int(docid[3:])
        if debug:
            print("===",docid,stdno)
        isalso = rfc.find('r:is-also', ns)
        if not isalso:
            if debug:
                print(" no RFCs, skipped")
            continue

        # get most recent rfc date
        docids = [ int(did.text[3:]) for did in isalso.findall('r:doc-id', ns) ]
        maxdocid = max(docids)
        docdate = rfcdates[maxdocid]
        if debug:
            print(" docids", docids, maxdocid, docdate)
        addref(stdurl, stdno, docdate)

    numstds = numrefs

    print("number of bcps", numbcps, "stds", numstds)

    # write it out

    if doindent:
        ET.indent(bcpmap)
    bcpet = ET.ElementTree(bcpmap)

    with open(args.bmap, "wb") as fo:
        bcpet.write(fo, encoding="UTF-8", xml_declaration=True)
        fo.write(b'\n')
    print("Wrote bcp,std map", args.bmap)
    bcptime = datetime.datetime.now(tz=datetime.timezone.utc)

# scrape site map web page
if args.m:
    # guess that URLs were modified this month
    mdate = datetime.date.today().replace(day=1)
    mdstr = str(mdate)

    # slurp in the HTML site index
    r = requests.get(mapurl)
    r.raise_for_status()
    sm = BeautifulSoup(r.content, 'html.parser') 

    miscmap = ET.fromstring(mapbase)
    curmap = miscmap
    freq = "monthly"

    numrefs = 0

    # find all the links in <div id="content">

    for d in sm.find_all("div", id="content"):
        for a in d.find_all("a"):
            h = a['href']
            if h.startswith('/'):
                href = urlbase + h
            else:
                href = mapurl + h
            if debug:
                print("  ", href)
            addref(href, None, mdstr)

    # write it out
    nummisc = numrefs
    print("num misc entries", nummisc)
    if doindent:
        ET.indent(miscmap)
    miscet = ET.ElementTree(miscmap)

    with open(args.mmap, "wb") as fo:
        miscet.write(fo, encoding="UTF-8", xml_declaration=True)
        fo.write(b'\n')
    print("Wrote misc map", args.mmap)
    misctime = datetime.datetime.now(tz=datetime.timezone.utc)

# rebuild the site index
siteindex = ET.fromstring(indexbase)

# add 
for ufile,utime in ((args.mmap, misctime), (args.rmap, rfctime), (args.bmap, bcptime)):
    smap = ET.Element("sitemap")
                
    l = ET.Element("loc")
    l.text = urlbase + '/' + ufile
    smap.append(l)

    if not utime:   # use time for existing file
        try:
            ft = os.stat(ufile)
            utime = datetime.datetime.fromtimestamp(int(ft.st_mtime), tz=datetime.timezone.utc)
            if debug:
                print(f"utime of {ufile} is {utime}")
        except FileNotFoundError:
            utime = datetime.datetime.now(tz=datetime.timezone.utc) - datetime.timedelta(days=1)
            print(f"Cannot find {ufile}, guessing it was updated yesterday {utime}")
    utime = utime.replace(microsecond=0)   # lose the microseconds in the output

    l = ET.Element("lastmod")
    l.text = utime.isoformat()
    smap.append(l)

    siteindex.append(smap)

if doindent:
    ET.indent(siteindex)
smet = ET.ElementTree(siteindex)

with open(args.index, "wb") as fo:
    smet.write(fo, encoding="UTF-8", xml_declaration=True)
    fo.write(b'\n')
print("Wrote site index", args.index)

