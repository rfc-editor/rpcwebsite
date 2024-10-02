#!/usr/bin/env python3.9

import os
import sys
import posix
import datetime
import string
import re
import xml.etree.ElementTree as ET
import urllib.request
import bs4

debug = False

class Survey:
    def __init__(self, config=None, since=None, until=None, debug=False):
        assert config
        self.config = config
        self.debug = debug

        """ base date or beginning of prev month
        """
        if not since and 'since' in config:
            since = config['since']
        if since:
            if type(since) is str:
                dx = datetime.date.fromisoformat(since)
                self.since = datetime.datetime(dx.year, dx.month, dx.day)
            else:
                self.since = since
        else:                           # default to previous month
            t = datetime.date.today()
            if t.month == 1:
                self.since = datetime.datetime(t.year-1, 1,1)
            else:
                self.since = datetime.datetime(t.year, t.month-1, 1)

        if not until and 'until' in config:
            until = config['until']

        if until:
            if type(until) is str:
                dx = datetime.date.fromisoformat(until)
                self.until = datetime.datetime(dx.year, dx.month, dx.day)
            else:
                self.until = until
        else:                           # default to previous month
            self.until = datetime.datetime.now()
        if self.debug:
            print("since", self.since, "until", self.until)

    def getrfcs(self):
        """ list of RFCs of appropriate date
        """

        # list of XML rfcs
        xmlrfcs = [ x for x in os.scandir(self.config['rfcdir']) if re.match(r'rfc\d+\.xml', x.name) ]
        
        # timestamp to compare
        ts = self.since.timestamp()
        tu = self.until.timestamp()

        self.newrfcs = [ x for x in xmlrfcs if x.stat().st_mtime >= ts and x.stat().st_mtime <= tu ]
        return self.newrfcs

    def getxml(self, rfc):
        """ rfc is file name or DirEntry
        returns (number. title, list of (name, address))
        """

        xml = ET.parse(rfc)
        assert xml
        r = xml.getroot()
        assert r.tag == 'rfc'            # better be an RFC
        number = r.attrib['number']
        frontnode = r.find('front')
        assert frontnode
        title = frontnode.find('title').text
        if self.debug:
            print("title", title)
        authors = []
        for a in frontnode.findall('author'):
            aname = a.attrib['fullname']
            addr = a.find('address')
            if addr is None:
                continue                # no address for author

            email = addr.find('email')
            if email is not None and email.text is not None:
                emailtxt = email.text.lower()
                if emailtxt is not None:
                    authors.append((aname,emailtxt))
                    if self.debug:
                        print("author",aname, "email", emailtxt)
        return { 'number': number, 'title': title, 'authors': authors}
        
    def getshepherd(self, rfc):
        """ rfc is file name or DirEntry
        returns list of shepherd addresses
        """
        if type(rfc) is posix.DirEntry: # extract RFC number

            r = re.match(r'rfc(\d+)', rfc.name)
            if not r:
                print("mystery name", rfc.name)
                exit(1)
            rfc = r.group(1)
        url = self.config['dturl'].format(rfc=rfc)
        # get the datatracker page
        with urllib.request.urlopen(url) as uf:
            soup = bs4.BeautifulSoup(uf, 'html.parser')

        # find TH tag for contact
        tt = None
        for t in soup.find_all('th'):
            if 'shepherd' in t.text:
                tt = t
                break
        if not tt:
            return None                   # no shepherd for this one

        # two TD's later
        sheptd = tt.find_next('td').find_next('td')
        shepname = None
        shepaddr = None
        for shepa in sheptd.find_all('a'):
            if shepa.string and not shepname:
                shepname = shepa.string
                continue
            href = shepa['href']
            if href.startswith('mailto:'):
                shepaddr = href[7:].replace('%40', '@')
        return (shepname, shepaddr)

    def addrsplit(self, addr):
        """
        split name and addr
        """
        if '>' not in addr:
            return ('', addr)
        r = re.match(r'\s*(.*\S)\s*<([^>]+)>', addr)
        if not r:
            print("mystery address", addr)
            return None
        return (r.group(1).strip('"'), r.group(2).lower()) # no quotes in the name, we'll quote it later

################################################################
if __name__ == '__main__':
    import argparse
    import email.header
    
    parser = argparse.ArgumentParser(description='Make up questions')
    parser.add_argument('--since', type=str, help="find RFCs since YYYY-MM-DD");
    parser.add_argument('--until', type=str, help="find RFCs until YYYY-MM-DD");
    parser.add_argument('--config', type=str, help="config file", default='surveyconfig.txt');
    parser.add_argument('-d', action='store_true', help="debug info");
    args = parser.parse_args();

    with open(args.config, "r") as f:   # simple key value config file
        config = dict( l.strip().split(maxsplit=1) for l in f if l[:1] not in ('#','\n','') )

    debug = args.d
    s = Survey(config=config, since=args.since, until=args.until, debug=debug)

    rfcs = s.getrfcs()
    people = {}                         # indexed by adddress, contents is dict(name, msg)

    for r in rfcs:
        if debug:
            print("rfc", r)
        gx = s.getxml(r)
        print("===",r.name)
        name = f"RFC {gx['number']} {gx['title']}"
        for an, aa in gx['authors']:
            if debug: print("author",an,aa,name)
            if aa not in people:
                people[aa] = { "name": an, "msg": []}
                if debug:
                    print("author add", aa, people[aa])
            people[aa]['msg'].append(f"author of {name}")
                
        shep = s.getshepherd(r)
        if shep:
            (sn, sa) = shep
            if not sa:
                continue
            if debug: print("shepherd",sn,sa,name)
            if sa not in people:
                people[sa] = { "name": sn, "msg": []}
                if debug:
                    print("shep add", sa, people[sa])
            people[sa]['msg'].append(f"shepherd of {name}")

    with open(config['template'], 'r') as f:
        tptxt = f.read(10000)

    template = string.Template(tptxt)
    month = datetime.date.today().strftime('%B %Y')

    print("create messages")
    for p in iter(people):
        if debug:
            print("p", p)
        print(".",end='',flush=True)
        px = people[p]

        # mime encode name
        h = email.header.Header()
        h.append(px['name'])
        encname = h.encode()

        tpfill = {
            "addr": p,
            "name": encname,
            "month": month, 
            "rfcs": "\n".join(px['msg']), "url": config['surveyurl']
        }
        fname = config['msgfile'].format(re.sub(r'\W', '_', p))
        with open(fname, "w") as fo:
            print(template.substitute(**tpfill), file=fo)
    print(";")
    print("number of RFCs", len(rfcs))
    print("number of people", len(people))
        
