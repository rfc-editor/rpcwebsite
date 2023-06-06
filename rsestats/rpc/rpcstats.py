# logic for RPC forms

from django.db.models import Count, Max, Min, Sum, IntegerField, Case, When
from .models import Index, StateHistory, RfcStateSummary, StateByDate, Errata, ErrataStatusCodes, ErrataTypeCodes
from datetime import timedelta, date
from math import sqrt
from collections import Counter
from functools import reduce

# some quarter date arithmetic
def rquarter(d):
    """
    tuple with a range of this quarter
    """
    if d.month <= 3:
        return (date(day=1, month=1, year=d.year), date(day=31, month=3, year=d.year))
    elif d.month <= 6:
        return (date(day=1, month=4, year=d.year), date(day=30, month=6, year=d.year))
    elif d.month <= 9:
        return (date(day=1, month=7, year=d.year), date(day=30, month=9, year=d.year))
    else:
        return (date(day=1, month=10, year=d.year), date(day=31, month=12, year=d.year))

def quarterlist(mindate, maxdate):
    """
    return a list of quarter ranges
    """
    mindate = mindate.replace(day=1)
    maxdate = maxdate.replace(day=1)
    r = []
    while mindate <= maxdate:
        r.append(rquarter(mindate))
        if mindate.month < 10:
            mindate = mindate.replace(month=mindate.month+3)
        else:
            mindate = mindate.replace(month=1, year=mindate.year+1)
    return r

def quarterfmt(d):
    """
    make a date look like a quarter
    """
    return "Q{0} {1}".format((d.month+2)//3, d.year)

# month ranges
def rmonth(d):
    """
    tuple with a range of this month
    """
    if d.month < 12:
        return (date(day=1, month=d.month, year=d.year), date(day=1, month=d.month+1, year=d.year)-timedelta(1))
    else:
        return (date(day=1, month=12, year=d.year), date(day=31, month=12, year=d.year))

def monthlist(mindate, maxdate):
    """
    return a list of month ranges
    """
    mindate = mindate.replace(day=1)
    maxdate = maxdate.replace(day=1)
    r = []
    while mindate <= maxdate:
        r.append(rmonth(mindate))
        if mindate.month < 12:
            mindate = mindate.replace(month=mindate.month+1)
        else:
            mindate = mindate.replace(month=1, year=mindate.year+1)
    return r

def monthfmt(d):
    """
    make a date look like a month
    """
    return d.strftime("%b %Y")

def lastmonth(d):
    """
    end of the month before that date
    """
    return d.replace(day=1)-timedelta(1)

def getdateranges():
    """
    return date ranges of monthly and weekly data
    """
    ranges = StateByDate.objects.aggregate(wfirst=Min('state_date'),wlast=Max('state_date'))
    mranges = RfcStateSummary.objects.aggregate(mfirst=Min('rfc__pub_date'),mlast=Max('rfc__pub_date'))
    ranges.update(mranges)
    return ranges

def rfcstatehistory(rfc):
    """
    take an Index object representing an RFC, give a list of state pairs and times
    each list entry is a dict date, old and new states, weeks
    states are annotated with gen number, iana flag, ref flag
    """
    # ignore bogus zero states
    sh = StateHistory.objects.filter(internal_dockey=rfc, state_id__gt=0).order_by('in_date', 'id')

    shl = [ { "date": sh[i].in_date,
              "state": sh[i].state_id,
              "version": sh[i].version_number,
              "iana": bool(sh[i].iana_flag),
              "ref": bool(sh[i].ref_flag),
              "oldstate": sh[i-1].state_id if i>0 else None,
              "oldversion": sh[i-1].version_number if i>0 else None,
              "oldiana": bool(sh[i-1].iana_flag) if i>0 else None,
              "oldref": bool(sh[i-1].ref_flag) if i>0 else None,
              "days": (sh[i+1].in_date-sh[i].in_date).days if (i+1)<len(sh) else None
            } for i in range(len(sh)) ]
    return shl

def rfcstorehistory(rfc):
    """
    take an Index object representing an RFC, store RfcStateSummary
    records for the time in each state
    """
    shl = rfcstatehistory(rfc)
    # summarize number of days in each state pair
    days = {}
    for sh in shl:
        stkey = tuple(sh[x] for x in ('oldstate','state','oldversion','version','oldiana','iana','oldref','ref'))
        if sh['days'] != None:
            if stkey not in days:
                days[stkey] = sh['days']
            else:
                days[stkey] += sh['days']

    # store in database
    for st, nd in days.items():
        rs = RfcStateSummary(rfc=rfc, oldstate=st[0], state=st[1], old_version_number=st[2], version_number=st[3],
            old_iana_flag=st[4], iana_flag=st[5], old_ref_flag=st[6], ref_flag=st[7],
            days=nd)
        rs.save()

def rfchistoryupdate(datelimit=date.today(), showprogress=False):
    """
    update state summarys to add RFCs published to the end of the previous month
    """
    # last summary date
    ls = RfcStateSummary.objects.all().aggregate(Max('rfc__pub_date'))
    lsdate = ls['rfc__pub_date__max']
    if lsdate == None:                  # first time, only back to 2010
        lsdate = date(2010,1,1)

    # last month date
    lmdate = lastmonth(datelimit)

    # find RFCs published since lsdate up to lmdate
    rfcs = Index.objects.filter(pub_date__gt=lsdate, pub_date__lte=lmdate)

    # and store state summary for all of them
    result = []
    for rfc in rfcs:
        if showprogress:
            print (rfc.doc_id,rfc.pub_date)
        rfcstorehistory(rfc)
        result.append(rfc)

    return (lsdate, lmdate, result)

def pubcount(mindate, maxdate):
    """
    all of the RFCs published in a date range
    may not match editstats count due to April 1 RFCs
    that never go into EDIT state
    """
    rfcs = Index.objects.filter(pub_date__gte=mindate, pub_date__lte=maxdate)
    pagecount = int(rfcs.aggregate(Sum('page_count'))['page_count__sum'])
    return { 'count': len(rfcs), 'pagecount': pagecount, 'rfcs': rfcs }

def subcount(mindate, maxdate):
    """
    submitted pages for all of the RFCs submitted in a date range
    return number and page count
    """
    rfcs = Index.objects.filter(date_received__gte=mindate, date_received__lte=maxdate)
    if not rfcs:
        return None                     # no data for this range
    # count sub_page_count if set otherwise page_count
    rfcspc = rfcs.aggregate(pages=Sum(Case(When(sub_page_count__gte=1, then='sub_page_count'),
        default='page_count', output_field=IntegerField())))
    pagecount = int(rfcspc['pages'])

    return { 'count': len(rfcs), 'pagecount': pagecount, 'rfcs': rfcs, 'partial': maxdate > date.today() }

def getpgte(mindate, maxdate, oldway=False, perversion=False, dodocs=False):
    """
    get PGTE, total submitted pages during a date rage
    where submitted means documents that first went to EDIT state
    during that range
    perversion counts multiple versions of the same document
    UGLY: normal and oldway docs are list of Querysets
    but perversion is list of dicts
    dodocs says return document count, not pages
    """

    # find documents with a state history entry
    # during the date range to EDIT
    # and add up sub_page_count of the docs
    
    # old way, with distinct, double counts docs that go to EDIT
    # twice in different quarters
    if oldway:
        pc = Index.objects.filter(statehistory__in_date__gte=mindate, statehistory__in_date__lte=maxdate,
            statehistory__state_id__state_name='EDIT').distinct()
    else:
        # new way, aggregate EDIT states per document use the earliest one

        # look back min date for aggregation, a year before now
        lbmin = mindate.replace(year=mindate.year-1)

        # django sure lets you say some arcane stuff
        if perversion:
            # count each version of a document to EDIT separately
            pc = Index.objects.filter(statehistory__in_date__gte=lbmin, statehistory__in_date__lte=maxdate,
                statehistory__state_id__state_name='EDIT').values('internal_key','statehistory__version_number'). \
                annotate(firstedit=Min('statehistory__in_date')).filter(firstedit__gte=mindate)
        else:
            # count each document to EDIT once
            pc = Index.objects.filter(statehistory__in_date__gte=lbmin, statehistory__in_date__lte=maxdate,
                statehistory__state_id__state_name='EDIT'). \
                annotate(firstedit=Min('statehistory__in_date')).filter(firstedit__gte=mindate)

    if not pc:
        return None                     # no data, probably the future

    if dodocs:
        return { 'count': int(pc.aggregate(Count('sub_page_count'))['sub_page_count__count']), 'docs': pc }
    else:
        return { 'count': int(pc.aggregate(Sum('sub_page_count'))['sub_page_count__sum']), 'docs': pc }


def rd1(x):
    """
    round to one place
    to make it look nice
    """
    return round(x, 1)

def editstats(mindate, maxdate, p, maxweeks=None, today=date.today()):
    """
    get EDIT + RFC_EDITOR times for RFCs published
    during an interval
    and get the statistics of those times
    and p'th percentile time
    """

    # django still lets you say some arcane stuff
    # find docs published in the date range
    dl = Index.objects.filter(pub_date__gte=mindate, pub_date__lte=maxdate)

    # take statesummary entries for EDIT and RFC-EDITOR
    # add them up for each document as totdays, return days per
    # document
    # this is different because Apr 1 RFCs never go to EDIT
    pcdl = dl.filter(rfcstatesummary__state__state_name__in=('EDIT', 'RFC-EDITOR')). \
        annotate(totdays=Sum('rfcstatesummary__days')).order_by('totdays')

    if not pcdl:
        return None                     # no stats for this quarter

    partial = maxdate > today

    # total page count, hack around bogus null page counts
    pagecount = sum(map(lambda x: int(x.page_count or 0), dl))

    # get sorted list of number of days for each document
    weeks = list(map(lambda x: x.totdays/7.0, pcdl))

    editcount = len(weeks)

    # get the median
    # middle item if count is odd, average of middle items if count
    # is even
    if len(weeks)%2:
        median = weeks[len(weeks)//2]
    else:
        median = (weeks[len(weeks)//2] + weeks[1+(len(weeks)//2)])/2.0
    
    # get the mean
    mean = float(sum(weeks))/editcount

    # variance and standard deviation
    # average of sum of squares of difference from the mean
    if editcount > 1:
        variance = reduce(lambda x,y: x+(y-mean)*(y-mean), weeks, 0.0)/editcount
    else:
        variance = 0
    stddev = sqrt(variance)

    # Nth percentile
    pp = p/100.0*editcount              # percentile, between two entries
    pn = int(pp)                        # lower entry
    pd = pp-pn                          # amount to attribute to lower entry

    if pn < len(weeks)-1:                # check for running off the end
        percentile = pd*weeks[pn]+(1.0-pd)*weeks[pn+1]
    else:
        percentile = weeks[pn]           # use that one

    if maxweeks:
        metweeks = len(list(filter(lambda x: x <= maxweeks, weeks)))
    else:
        metweeks = None        

    return { 'count': len(dl), 'editcount': editcount, 'pagecount': pagecount,
        'mean': rd1(mean), 'median': rd1(median), 'variance': rd1(variance), 'stddev': rd1(stddev),
        'okpercentile': rd1(percentile), 'partial': partial,
        'weeks': weeks, 'metweeks': metweeks }

def getpercentile(dl, n):
    """
    find out what percentile n is of
    dl list of values
    """
    return round(float(len(list(filter(lambda x: x <= n, dl))))/len(dl)*100, 1)

def edittime(mindate, maxdate):
    """
    get edit time for each RFC
    """
    # django kludgery, get the RFCs published in this date range
    # with the total EDIT and RFC-EDITOR time
    # and also the doc_id and draft
    r = RfcStateSummary.objects.filter(rfc__pub_date__gte=mindate,rfc__pub_date__lte=maxdate,
        state__state_name__in=('EDIT','RFC-EDITOR')).values('state__state_name',
        'rfc__doc_id','rfc__draft').annotate(edittime=Sum('days')).order_by('rfc__doc_id')

    # now combine the entries for each RFC into a line per document
    docs = []
    lastrfc = None
    lastdraft = None
    edittime = 0.0
    rfcedtime = 0.0
    for rd in r:
        if rd['rfc__doc_id'] != lastrfc:
            if lastrfc:
                docs.append({ 'rfc': lastrfc, 'draft': lastdraft, 'edit': rd1(edittime),
                    'rfced': rd1(rfcedtime), 'both': rd1(edittime+rfcedtime) })
            lastrfc = rd['rfc__doc_id']
            lastdraft = rd['rfc__draft']
            edittime = 0.0
            rfcedtime = 0.0
        if rd['state__state_name'] == 'EDIT':
            edittime = rd['edittime'] / 7.0 # make into weeks
        elif rd['state__state_name'] == 'RFC-EDITOR':
            rfcedtime = rd['edittime'] / 7.0 # make into weeks
        else:
            print ("odd time ",repr(rd))

    if lastrfc:
        docs.append({ 'rfc': lastrfc, 'draft': lastdraft, 'edit': rd1(edittime),
            'rfced': rd1(rfcedtime), 'both': rd1(edittime+rfcedtime) })

    return docs

def erratameta(by='Type'):
    """
    return list of type or status names
    """
    if by == 'Type':
        return [ x.errata_type_code for x in  ErrataTypeCodes.objects.all() ]
    elif by == 'Status':
        return [ x.errata_status_code for x in ErrataStatusCodes.objects.all() ]
    else:
        return None

def geterrata(by='Type'):
    """
    get errata summaries
    dict summarized by type or by status
    list of doc-id's by RFC
    """
    
    if by == 'Type':
        e = ErrataTypeCodes.objects.annotate(Count('errata'))
        errata = { ee.errata_type_code: ee.errata__count for ee in e }
    elif by == 'Status':
        e = ErrataStatusCodes.objects.annotate(Count('errata'))
        errata = { ee.errata_status_code: ee.errata__count for ee in e if ee.errata__count } # skip zero counts
    elif by == 'RFC':                      # the RFCs with errors
        e = Errata.objects.values('doc_id').distinct()
        errata = [ x['doc_id'] for x in e ]
    else:
        errata = None

    return errata

def getyearerrata(by='Type'):
    """
    get errata summaries by year
    summarized by type or by status
    return dict per year
    can't do this in djangoese, as far as I can tell
    """

    errata = dict()
# HACK Django demands the primary key, MySQL doesn't allow a
# nonaggregated column.  So we provide a fake version of the primary
# key to keep django happy
    if by == 'Type':
        e = Errata.objects.raw("""SELECT 1 as errata_id,type_id,YEAR(submit_date) AS submityear,
            count(*) AS nerrata FROM errata GROUP BY submityear,type_id HAVING submityear < 2100""")

        for et in e:
            if et.submityear in errata:
                errata[et.submityear][et.type.errata_type_code] = et.nerrata
            else:
                errata[et.submityear] = { et.type.errata_type_code: et.nerrata }
    elif by == 'Status':
# HACK see above
        e = Errata.objects.raw("""SELECT 1 as errata_id,status_id,YEAR(submit_date) AS submityear,
            count(*) AS nerrata FROM errata GROUP BY submityear,status_id HAVING submityear < 2100""")
        
        for es in e:
            if es.submityear in errata:
                errata[es.submityear][es.status.errata_status_code] = es.nerrata
            else:
                errata[es.submityear] = { es.status.errata_status_code: es.nerrata }

    return errata

def erratabyarea(type=None, status=None):
    """
    return counts of errata tagged by area or area like thing
    optionally filter by type and status
    for things where area is N/A, it's IRTF if name includes "Research Group"
    it's IETF if it's NON WORKING GROUP
    hack: combine RAI and APP into ART
    """
    eo = Errata.objects
    if type:
        eo = eo.filter(type__errata_type_code=type)
    if status:
        eo = eo.filter(status__errata_status_code=status)

    # first get real WGs not in Legacy or 'N/A' area and annotate with area
    # name and acronym
    ea = eo.exclude(doc_id__source__area_name__in=('Legacy','N/A')). \
        values('doc_id__source', 'doc_id__source__area_name', 'doc_id__source__area_name__area_acronym')

    # collect all of the acronyms, or name for areas with no acronym (Legacy)
    areas = [ e['doc_id__source__area_name__area_acronym'] for e in ea ]

    # crunch into a dict of counts
    areacount = Counter(areas)

    # handle Legacy and non-WG via areaassignments
    legacy = eo.filter(doc_id__source__in=('Legacy','IETF - NON WORKING GROUP')).values('doc_id__areaassignments__area__area_acronym')
    areacount += Counter(e['doc_id__areaassignments__area__area_acronym'] for e in legacy)

    # now add in the non-Legacy non-IETF pseudo-WGs with a bunch of special cases
    eao = eo.filter(doc_id__source__area_name='N/A').exclude(doc_id__source__in=('Legacy','IETF - NON WORKING GROUP')).values('doc_id__source')
    oddsources = [ e['doc_id__source'] for e in eao ]

    # now merge the counts of the odd sources into the areas
    for os, oc in Counter(oddsources).items():
        if os == 'IRTF' or 'Research Group' in os: # add all RGs into IRTF
            areacount['IRTF'] += oc     # note Counters default to zero
        elif os == 'INDEPENDENT':       # too long, hack
            areacount['IND'] = oc
        else:
            areacount[os] = oc

    # adhoc hack: add APP and RAI into ART
    hacka = ('art','app','rai')
    ct = sum((areacount.get(x,0) for x in hacka))
    for k in hacka:
        if k in areacount:
            del areacount[k]
    if ct:
        areacount['art'] = ct

    return areacount

def rfcsbyyear(minyear, maxyear, dopages=False ,dosub=False):
    """
    return count of RFCs by year list of (pubyear: year, numrfcs: count)
        or count of pages
        or return by submitted date
    have to do this with raw SQL due to django's weak date arithmetic
    HACK: fake internal key to avoid nonaggregated column complaints
    """

    if dosub:                           # submitted date
        if dopages:
            rby = Index.objects.raw("""SELECT 1 as internal_key,sum(`page-count`) AS numrfcs,YEAR(`DATE_RECEIVED`) AS subyear FROM `index`
                WHERE YEAR(`DATE_RECEIVED`) >= %s AND YEAR(`DATE_RECEIVED`) <= %s AND type='RFC' GROUP BY subyear""", (minyear, maxyear))
        else:
            rby = Index.objects.raw("""SELECT 1 as internal_key,count(*) AS numrfcs,YEAR(`DATE_RECEIVED`) AS subyear FROM `index`
                WHERE YEAR(`DATE_RECEIVED`) >= %s AND YEAR(`DATE_RECEIVED`) <= %s AND type='RFC' GROUP BY subyear""", (minyear, maxyear))
    else:
        if dopages:
            rby = Index.objects.raw("""SELECT 1 as internal_key,sum(`page-count`) AS numrfcs,YEAR(`pub-date`) AS pubyear FROM `index`
                WHERE YEAR(`pub-date`) >= %s AND YEAR(`pub-date`) <= %s AND type='RFC' GROUP BY pubyear""", (minyear, maxyear))
        else:
            rby = Index.objects.raw("""SELECT 1 as internal_key,count(*) AS numrfcs,YEAR(`pub-date`) AS pubyear FROM `index`
                WHERE YEAR(`pub-date`) >= %s AND YEAR(`pub-date`) <= %s AND type='RFC' GROUP BY pubyear""", (minyear, maxyear))
    return rby


