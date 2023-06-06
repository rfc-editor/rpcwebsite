# test all of the RPC views
# there is intended to be a test for all of the URLs in urls.py
# for the ones that accept POST, there's a test for each page (mostly
# charts) they can return
# for pages with deep links, it scrapes the deep link, logs out, and
# then checks that the deep link returns more or less the same page
# as the original

from datetime import date
import re

from django.test import TestCase
from django.contrib.auth.models import User
from django.core.urlresolvers import reverse
from .datestats import makestatebydate
from .rpcstats import rfchistoryupdate

# tests are for 1Q2015, mkfixtures smashes the tables in the database down to just
# that range
MINDATE = date(2015,1,1)
MINQUARTER = 1                          # for forms that take quarter numbers
MINMONTH = MINDATE.month                # for forms that take months

MAXDATE = date(2015,3,31)
MAXQUARTER = 1                          # for forms that take quarter numbers
MAXMONTH = MAXDATE.month                # for forms that take months, last month of quarter
MAXQMONTH = MAXDATE.month-2             # for forms that take months, first month of quarter

def unicontent(r):
    """
    Return a HttpResponse object's content as unicode
    Keep in mind one RFCs can have Unicode names or authors
    """
    content_type = r._headers.get("content-type", "text/html; charset=utf-8")
    if 'charset=' in content_type:
        mediatype, charset = content_type.split(';')
        encoding = charset.split('=')[1].strip()
    else:
        encoding = 'utf-8'
    return r.content.decode(encoding)

def getdeeplink(s):
    """
    look through response text for a deep link URL
    and return it
    Note the absolute URL which will have to change if this module is mounted somewhere other than /rpc
    """
    r = re.search(r'/rpc/dl[a-z]{3,4}/\w+', s)
    if r:
        return r.group(0)
    else:
        return None

class RpcTestCase(TestCase):
    fixtures = [ 'index', 'errata','erratastatuscodes','erratatypecodes','states','statehistory','workinggroup','area','areaassignments' ]
        
    def setUpTestData(self):
        """
        not using this because django doesn't call it
        if you only run specific tests
        """
        pass

    def setUp(self):
        """
        populate the statebydate and rfcstatesummary tables
        for the first quarter of 2015
        create and log in the test user
        """
        makestatebydate(mindate=MINDATE, maxdate=MAXDATE, verbose=False)
        rfchistoryupdate(datelimit=MAXDATE, showprogress=False)

        # create a user and log it in
        u = User.objects.create_user('testuser', 'test@example.com', 'x')
        self.assertTrue(u)                  # user created
        r = self.client.login(username='testuser', password='x')
        self.assertTrue(r)                  # login worked


    def test_base(self):
        """
        home page, no buttons to push
        also check that logging out forces you to the login page
        """
        r = self.client.get(reverse('rpc:index'))
        self.assertEqual(r.status_code, 200)
        self.assertTrue('RSE reports and charts' in r.content)

        # log out and try again
        self.client.logout()
        r = self.client.get(reverse('rpc:index'))
        self.assertEqual(r.status_code, 302) # shoud redirect to login page

    def test_range(self):
        """
        RFCs by date tab
        get should produce begin/end menu
        post should produce a list of RFCs
        """
        # get plain page
        r = self.client.get(reverse('rpc:range'))
        self.assertEqual(r.status_code, 200)
        self.assertTrue('Date range of RFCs' in r.content)

        # invalid date range
        r = self.client.post(reverse('rpc:range'), { 'mindate_0': MINMONTH, 'mindate_1': MINDATE.year,
             'maxdate_0': MAXQMONTH, 'maxdate_1': MINDATE.year-1 })
        self.assertEqual(r.status_code, 200)
        self.assertTrue('Min date must be less than max date' in r.content)

        # valid range for the quarter
        r = self.client.post(reverse('rpc:range'), { 'mindate_0': MINMONTH, 'mindate_1': MINDATE.year,
            'maxdate_0': MAXQMONTH, 'maxdate_1': MAXDATE.year })
        self.assertEqual(r.status_code, 200)
        self.assertTrue('RFCs from Jan. 1, 2015 to March 31, 2015' in r.content)
        self.assertTrue('Administration Protocol for Federated File Systems' in r.content)

    def test_rfc(self):
        """
        test the internal link that returns info for an individual RFC
        """
        r = self.client.get(reverse('rpc:showrfc', kwargs={'rfcno': 'RFC7414'}))
        self.assertEqual(r.status_code, 200)
        self.assertTrue('RFC7414 A Roadmap for Transmission Control Protocol' in r.content) # it's the right RFC
        self.assertTrue('EDIT:RFC-EDITOR' in r.content) # it's got state history

        r = self.client.get(reverse('rpc:showrfc', kwargs={'idno': '7905'}))
        self.assertEqual(r.status_code, 200)
        self.assertTrue('RFC7493 The I-JSON Message Format' in r.content) # it's the right RFC
        self.assertTrue('EDIT:RFC-EDITOR' in r.content) # it's got state history

    def test_pgte(self):
        """
        PGTE explorer section
        get returns form
        post returns page of drafts with ticks and crosses
        """
        # get plain page
        r = self.client.get(reverse('rpc:pgte'))
        self.assertEqual(r.status_code, 200)
        self.assertTrue('Quarter to explore' in r.content)

        # pick a quarter
        r = self.client.post(reverse('rpc:pgte'), { 'quarter_0': MINMONTH, 'quarter_1': MINDATE.year })
        self.assertEqual(r.status_code, 200)
        self.assertTrue('PGTE explorer for Q1 2015' in r.content)
        self.assertTrue('<a href="/rpc/id7905">draft-ietf-json-i-json-06</a>' in r.content)

    def test_monthly(self):
        """
        montly stats by document
        get returns form
        post returns page of drafts with times
        """
        # get plain page
        r = self.client.get(reverse('rpc:monthly'))
        self.assertEqual(r.status_code, 200)
        self.assertTrue('Months to report' in r.content)

        # invalid date range
        r = self.client.post(reverse('rpc:monthly'), { 'mindate_0': MINMONTH, 'mindate_1': MINDATE.year,
             'maxdate_0': MAXMONTH, 'maxdate_1': MINDATE.year-1 })
        self.assertEqual(r.status_code, 200)
        self.assertTrue('Min date must be less than max date' in r.content)

        # pick a quarter
        r = self.client.post(reverse('rpc:monthly'), { 'mindate_0': MINMONTH, 'mindate_1': MINDATE.year,
            'maxdate_0': MAXMONTH, 'maxdate_1': MAXDATE.year })
        self.assertEqual(r.status_code, 200)
        self.assertTrue('Edit times for Jan 2015 through Mar 2015' in r.content)
        self.assertTrue('<a href="/rpc/RFC7449">RFC7449</a>' in r.content) # has the right links

    def test_stats(self):
        """
        SLA stats
        base is a form
        with dates returns any of four result pages
        """
        # get plain page
        r = self.client.get(reverse('rpc:stats'))
        self.assertEqual(r.status_code, 200)
        self.assertTrue('Which statistics:' in r.content)

        # invalid date range
        r = self.client.post(reverse('rpc:stats'), { 'mindate_0': MINMONTH, 'mindate_1': MINDATE.year,
             'maxdate_0': MAXQMONTH, 'maxdate_1': MINDATE.year-1 })
        self.assertEqual(r.status_code, 200)
        self.assertTrue('Min date must be less than max date' in r.content)

        # Edit times page
        r = self.client.post(reverse('rpc:stats'), { 'mindate_0': MINMONTH, 'mindate_1': MINDATE.year,
             'maxdate_0': MAXQMONTH, 'maxdate_1': MINDATE.year, 'which': 'T' })
        self.assertEqual(r.status_code, 200)
        self.assertTrue('Edit stats from Q1 2015' in r.content)
        self.assertTrue('2268' in r.content or '2231' in r.content) # published page count, yes, this is fragile

        # Page count page
        r = self.client.post(reverse('rpc:stats'), { 'mindate_0': MINMONTH, 'mindate_1': MINDATE.year,
             'maxdate_0': MAXQMONTH, 'maxdate_1': MINDATE.year, 'which': 'P' })
        self.assertEqual(r.status_code, 200)
        self.assertTrue('Submitted page counts from Q1 2015' in r.content)
        self.assertTrue('666' in r.content) # submitted page count, yes, this is fragile

        # SLA stats
        r = self.client.post(reverse('rpc:stats'), { 'mindate_0': MINMONTH, 'mindate_1': MINDATE.year,
             'maxdate_0': MAXQMONTH, 'maxdate_1': MINDATE.year, 'which': 'S' })
        self.assertEqual(r.status_code, 200)
        self.assertTrue('SLA stats from Q1 2015' in r.content)
        self.assertTrue('2268' in r.content or '2231' in r.content) # published page count, yes, this is fragile

        # formatted SLA stats
        # SLA stats
        def fst(self, r):
            self.assertEqual(r.status_code, 200)
            self.assertTrue('static/no-icon.png' in r.content) # failed SLA test
            self.assertTrue('2268' in r.content or '2231' in r.content) # published page count, yes, this is fragile
            
        r = self.client.post(reverse('rpc:stats'), { 'mindate_0': MINMONTH, 'mindate_1': MINDATE.year,
             'maxdate_0': MAXQMONTH, 'maxdate_1': MINDATE.year, 'which': 'F' })
        self.assertTrue('SLA statistics from Q1 2015' in r.content)
        fst(self, r)

        # log out and try deep links
        self.client.logout()

        # get the deep link for that page and try logged out
        deeplink = getdeeplink(r.content)
        self.assertTrue(deeplink)
        r = self.client.get(deeplink)
        fst(self, r)

        # check that a bogus deep link doesn't work
        r = self.client.get(deeplink+'x')
        self.assertTrue('Invalid deep link' in r.content)

    def test_moncharts(self):
        """
        Monthly charts
        base is a form
        with dates and series returns HTML or excel charts
        """
        # get plain page
        r = self.client.get(reverse('rpc:moncharts'))
        self.assertEqual(r.status_code, 200)
        self.assertTrue('Charts' in r.content)

        # no series
        r = self.client.post(reverse('rpc:moncharts'), { 'mindate_0': MINMONTH, 'mindate_1': MINDATE.year,
             'maxdate_0': MAXMONTH, 'maxdate_1': MAXDATE.year })
        self.assertEqual(r.status_code, 200)
        self.assertTrue('Must select at least one data series' in r.content)

        # invalid date range
        r = self.client.post(reverse('rpc:moncharts'), { 'mindate_0': MINMONTH, 'mindate_1': MINDATE.year,
             'maxdate_0': MAXMONTH, 'maxdate_1': MINDATE.year-1, 'dopgte': True })
        self.assertEqual(r.status_code, 200)
        self.assertTrue('Min date must be less than max date' in r.content)

        # draw a monthly document count chart
        def mct(self, r):
            self.assertEqual(r.status_code, 200)
            self.assertTrue('Document counts from Jan 2015 to Mar 2015' in r.content)
            self.assertTrue('PGTE' in r.content)
            self.assertTrue('chart.render' in r.content)
            
        r = self.client.post(reverse('rpc:moncharts'), { 'mindate_0': MINMONTH, 'mindate_1': MINDATE.year,
             'maxdate_0': MAXMONTH, 'maxdate_1': MAXDATE.year, 'dopgte': True,
             'dosubpages': True, 'dopubpages': True, 'dosplines': True, 'dodocs': True })
        mct(self, r)
        deeplink = getdeeplink(r.content)
        
        # draw an Excel chart
        r = self.client.post(reverse('rpc:moncharts'), { 'mindate_0': MINMONTH, 'mindate_1': MINDATE.year,
             'maxdate_0': MAXMONTH, 'maxdate_1': MAXDATE.year, 'dopgte': True,
             'dosubpages': True, 'dopubpages': True, 'dosplines': True, 'doxls': True })
        self.assertEqual(r.status_code, 200)
        content_type = r._headers.get("content-type", [])
        self.assertEqual(content_type[1], 'application/vnd.ms-excel')

        # log out and try deep links
        self.client.logout()

        # get the deep link for that page and try logged out
        self.assertTrue(deeplink)
        r = self.client.get(deeplink)
        mct(self, r)

    def test_qtrcharts(self):
        """
        Quarterly charts
        base is a form
        with dates and series returns HTML or excel charts
        """
        # get plain page
        r = self.client.get(reverse('rpc:qtrcharts'))
        self.assertEqual(r.status_code, 200)
        self.assertTrue('Charts' in r.content)

        # no series
        r = self.client.post(reverse('rpc:qtrcharts'), { 'mindate_0': MINMONTH, 'mindate_1': MINDATE.year,
             'maxdate_0': MAXQMONTH, 'maxdate_1': MAXDATE.year })
        self.assertEqual(r.status_code, 200)
        self.assertTrue('Must select at least one data series' in r.content)

        # invalid date range
        r = self.client.post(reverse('rpc:qtrcharts'), { 'mindate_0': MINMONTH, 'mindate_1': MINDATE.year,
             'maxdate_0': MAXQMONTH, 'maxdate_1': MINDATE.year-1, 'dopgte': True })
        self.assertEqual(r.status_code, 200)
        self.assertTrue('Min date must be less than max date' in r.content)

        # draw a chart
        def qct(self, r):
            self.assertEqual(r.status_code, 200)
            self.assertTrue('Page counts from Q1 2015 to Q1 2015' in r.content)
            self.assertTrue('Tier1' in r.content) # side caption
            self.assertTrue('Published' in r.content) # series
            self.assertTrue('chart.render' in r.content)

        r = self.client.post(reverse('rpc:qtrcharts'), { 'mindate_0': MINMONTH, 'mindate_1': MINDATE.year,
             'maxdate_0': MAXQMONTH, 'maxdate_1': MAXDATE.year, 'doslabands': True, 'dopgte': True,
             'dosubpages': True, 'dopubpages': True, 'dosplines': True, 'dodocs': True })
        qct(self, r)
        deeplink = getdeeplink(r.content)
        
        # draw an Excel chart
        r = self.client.post(reverse('rpc:qtrcharts'), { 'mindate_0': MINMONTH, 'mindate_1': MINDATE.year,
             'maxdate_0': MAXQMONTH, 'maxdate_1': MAXDATE.year, 'doslabands': True, 'doxls': True, 'dopgte': True,
             'dosubpages': True, 'dopubpages': True, 'dosplines': True, 'dodocs': True })
        self.assertEqual(r.status_code, 200)
        content_type = r._headers.get("content-type", [])
        self.assertEqual(content_type[1], 'application/vnd.ms-excel')

        # log out and try deep links
        self.client.logout()

        # get the deep link for that page logged out
        self.assertTrue(deeplink)
        r = self.client.get(deeplink)
        qct(self, r)

    def test_yearly(self):
        """
        Yearly charts
        base is a form
        with dates and series returns HTML or excel charts
        """
        # get plain page
        r = self.client.get(reverse('rpc:yearly'))
        self.assertEqual(r.status_code, 200)
        self.assertTrue('RFCs by year' in r.content)

        # invalid date range
        r = self.client.post(reverse('rpc:yearly'), { 'minyear': MINDATE.year, 'maxyear': MINDATE.year-1, 'doarea': True })
        self.assertEqual(r.status_code, 200)
        self.assertTrue('time warp' in r.content)

        # draw a chart
        def yct(self, r):
            self.assertEqual(r.status_code, 200)
            self.assertTrue('RFC page counts from 2015 to 2015' in r.content)
            self.assertTrue('Number of pages' in r.content) # side caption
            self.assertTrue('Published' in r.content) # series
            self.assertTrue('2268' in r.content or '2231' in r.content) # published page count, yes, this is fragile
            self.assertTrue('chart.render' in r.content)

        r = self.client.post(reverse('rpc:yearly'), { 'minyear': MINDATE.year, 'maxyear': MAXDATE.year,
            'doarea': False, 'dopages': True })
        yct(self, r)
        deeplink = getdeeplink(r.content)

        # Excel version
        r = self.client.post(reverse('rpc:yearly'), { 'minyear': MINDATE.year, 'maxyear': MAXDATE.year,
            'doarea': False, 'dopages': True, 'doxls': True })
        self.assertEqual(r.status_code, 200)
        content_type = r._headers.get("content-type", [])
        self.assertEqual(content_type[1], 'application/vnd.ms-excel')

        # log out and try deep links
        self.client.logout()

        # deep link for that chart
        self.assertTrue(deeplink)
        r = self.client.get(deeplink)
        yct(self, r)

    def test_histch(self):
        """
        History charts
        base is a form
        with dates and series returns HTML or excel charts
        """
        # get plain page
        r = self.client.get(reverse('rpc:histch'))
        self.assertEqual(r.status_code, 200)
        self.assertTrue('Charts' in r.content)

        # invalid date range
        r = self.client.post(reverse('rpc:histch'), { 'mindate_0': MINMONTH, 'mindate_1': MINDATE.year,
             'maxdate_0': MAXQMONTH, 'maxdate_1': MINDATE.year-1, 'doarea': True })
        self.assertEqual(r.status_code, 200)
        self.assertTrue('Min date must be less than max date' in r.content)

        # draw a chart
        def hct(self, r):
            self.assertEqual(r.status_code, 200)
            self.assertTrue('Pages by state from Jan. 1, 2015 to March 31, 2015' in r.content)
            self.assertTrue('Pages' in r.content) # side caption
            self.assertTrue('RFC-EDITOR' in r.content) # series
            self.assertTrue('new Chart(ctx,' in r.content) # use the other chart package
            
        r = self.client.post(reverse('rpc:histch'), { 'mindate_0': MINMONTH, 'mindate_1': MINDATE.year,
             'maxdate_0': MAXQMONTH, 'maxdate_1': MAXDATE.year, 'doarea': True, 'qlabel': True })
        hct(self, r)
        deeplink = getdeeplink(r.content)
        
        # Excel version
        r = self.client.post(reverse('rpc:histch'), { 'mindate_0': MINMONTH, 'mindate_1': MINDATE.year,
             'maxdate_0': MAXQMONTH, 'maxdate_1': MAXDATE.year, 'doarea': True, 'qlabel': True, 'doxls': True })
        self.assertEqual(r.status_code, 200)
        content_type = r._headers.get("content-type", [])
        self.assertEqual(content_type[1], 'application/vnd.ms-excel')

        # log out and try deep links
        self.client.logout()

        # deep link for that chart
        self.assertTrue(deeplink)
        r = self.client.get(deeplink)
        hct(self, r)

    def test_errata(self):
        """
        Errata charts
        base is a form
        Three reports and a spreadsheet
        """
        # get plain page
        r = self.client.get(reverse('rpc:errata'))
        self.assertEqual(r.status_code, 200)
        self.assertTrue('Errata' in r.content)

        # Type / Status pie
        def tsp(self, r):
            self.assertEqual(r.status_code, 200)
            self.assertTrue('Errata by Type' in r.content)
            self.assertTrue('Errata by Status' in r.content)
            self.assertTrue('typechart.render()' in r.content)
            self.assertTrue('statuschart.render()' in r.content)

        r = self.client.post(reverse('rpc:errata'), { 'which': 'P', 'doarea': False })
        tsp(self, r)
        deeplinkp = getdeeplink(r.content)
        
        # Yearly column / area
        def yca(self, r):
            self.assertEqual(r.status_code, 200)
            self.assertTrue('Errata by type and year' in r.content)
            self.assertTrue('Errata by status and year' in r.content)
            self.assertTrue('typechart.render()' in r.content)
            self.assertTrue('statuschart.render()' in r.content)

        r = self.client.post(reverse('rpc:errata'), { 'which': 'Y', 'doarea': False })
        yca(self, r)
        deeplinky = getdeeplink(r.content)

        # WG source pie
        def aca(self, r):
            self.assertEqual(r.status_code, 200)
            self.assertTrue('Reported Technical Errata by Source' in r.content)
            self.assertTrue('art (1)' in r.content) # an erratum
            self.assertTrue('areachart.render' in r.content)
            
        r = self.client.post(reverse('rpc:errata'), { 'which': 'A', 'doarea': False })
        aca(self, r)
        deeplinka = getdeeplink(r.content)

        # excel
        r = self.client.post(reverse('rpc:errata'), { 'which': 'X', 'doarea': False })
        self.assertEqual(r.status_code, 200)
        content_type = r._headers.get("content-type", [])
        self.assertEqual(content_type[1], 'application/vnd.ms-excel')

        # log out and try deep links
        self.client.logout()

        # deep link Type / Status pie
        self.assertTrue(deeplinkp)
        r = self.client.get(deeplinkp)
        tsp(self, r)

        # deep link Yearly column / area
        self.assertTrue(deeplinky)
        r = self.client.get(deeplinky)
        yca(self, r)

        # deep link pie by WG
        self.assertTrue(deeplinka)
        r = self.client.get(deeplinka)
        aca(self, r)

    def test_logout(self):
        """
        logs you out
        """
        # get plain page
        r = self.client.get(reverse('rpc:logout'))
        self.assertEqual(r.status_code, 302)
