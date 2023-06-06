# views for RPC statistics pages

from datetime import date
from hashlib import md5                 # I know this is weak, who cares
from django.shortcuts import render, redirect
from django.http import HttpResponse, HttpResponseNotFound
from django.contrib.auth import logout
from django.contrib.auth.decorators import login_required
from .forms import RangeForm, StatsRangeForm, ChartsRangeForm, HistChartsRangeForm, QuarterForm, \
    MonthRangeForm, EditMonthRangeForm, MonthChartsRangeForm, ErrataForm, YearChartForm
from .models import Index
from .rpcstats import rfcstatehistory, editstats, getpgte, monthlist, monthfmt, rmonth, quarterlist, quarterfmt, getpercentile, \
    rquarter, subcount, edittime, erratameta, geterrata, getyearerrata, erratabyarea, getdateranges, rfcsbyyear
from .datestats import stateseries
from .charts import xlsquarter, xlsmonth, xlserrata, xlshist, xlsyearly
from .slaparams import *

@login_required
def index(request):
    """
    initial page
    """

    dateranges = getdateranges()

    return render(request, 'rpc/index.html', {
        'dateranges': dateranges, 'bpnav': bpnav(request, 'index') })

@login_required
def pgte(request):
    """
    look at various PGTE counts
    """
    nav = bpnav(request, 'pgte')

    if request.method == 'GET':
        form = QuarterForm()
        return render(request, 'rpc/pgte.html', { 'form': form, 'bpnav': nav})

    form = QuarterForm(request.POST)
    if not form.is_valid():
        return render(request, 'rpc/pgte.html', { 'form': form, 'bpnav': nav })

    # get PGTE all three ways
    qdate = form.cleaned_data['quarter']
    quarter = rquarter(qdate)
    oldpgte = getpgte(quarter[0], quarter[1], oldway=True)

    if not oldpgte:                     # check for out of range
        return HttpResponse("No data for {0}".format(quarterfmt(qdate)))

    newpgte = getpgte(quarter[0], quarter[1], oldway=False)
    verpgte = getpgte(quarter[0], quarter[1], oldway=False, perversion=True)

    oldpgtes = map(lambda x: x.internal_key, oldpgte['docs'])
    newpgtes = map(lambda x: x.internal_key, newpgte['docs'])
    verpgtes = map(lambda x: x['internal_key'], verpgte['docs']) # list of dicts, not of query sets
    pgtes = set(oldpgtes).union(newpgtes, verpgtes)

    rfcs = Index.objects.filter(pk__in=pgtes).order_by('date_received')
    
    oldok = map(lambda x: x.internal_key in oldpgtes, rfcs)
    newok = map(lambda x: x.internal_key in newpgtes, rfcs)
    verok = map(lambda x: x.internal_key in verpgtes, rfcs)

    # glom them all together into a list we can iterate over in the
    # template
    rfcglom = zip(oldok, newok, verok, rfcs)

    #get the pagecounts and dcocount
    pgte = verpgte['count']
    dgte = len(verpgte['docs'])


    return render(request, 'rpc/pgte1.html', { 'quarter': quarterfmt(qdate),
        'verpgtes': verpgtes, 'pgtes': pgtes, 'rfcs': rfcglom, 'pgte': pgte, 'dgte': dgte, 'bpnav': nav })

@login_required
def daterange(request):
    """
    find RFCs published between two dates
    """
    nav = bpnav(request, 'range')

    if request.method == 'GET':
        form = RangeForm()
        return render(request, "rpc/range0.html", { 'form': form, 'bpnav': nav })

    form = RangeForm(request.POST)
    if not form.is_valid():
        return render(request, 'rpc/range0.html', { 'form': form, 'bpnav': nav })

    mindate = form.cleaned_data['mindate']
    maxdate = rquarter(form.cleaned_data['maxdate'])[1] # end of the quarter
    rfcs = Index.objects.filter(pub_date__gte=mindate, pub_date__lte=maxdate, doc_id__istartswith='RFC').order_by('doc_id')
    return render(request, 'rpc/range.html', { 'count': len(rfcs), 'rfcs': rfcs,
        'mindate': mindate, 'maxdate': maxdate, 'bpnav': nav })

@login_required
def showrfc(request, rfcno=None, idno=None):
    """
    show info for a document,  by RFC# or internal key
    """

    try:
        if rfcno:
            rfc = Index.objects.get(doc_id=rfcno)
        elif idno:
            rfc = Index.objects.get(internal_key=idno)
            
    except:
        return HttpResponse("Cannot find {0}".format(rfcno if rfcno else idno))

    shl = rfcstatehistory(rfc)
    return render(request, "rpc/rfc.html", { 'rfc': rfc, 'states': shl, 'bpnav': bpnav(request, 'rfc') })

@login_required
def stats(request):
    """
    show some SLA statistics
    """
    nav = bpnav(request, 'stats')
    
    if request.method == 'GET':
        form = StatsRangeForm()
        return render(request, "rpc/stats.html", { 'title': "SLA stats", 'form': form, 'bpnav': nav })

    form = StatsRangeForm(request.POST)
    if not form.is_valid():
        return render(request, 'rpc/stats.html', { 'form': form, 'bpnav': nav })

    mindate = form.cleaned_data['mindate']
    maxdate = form.cleaned_data['maxdate']
    which = form.cleaned_data['which']

    slancount = form.cleaned_data['slancount']
    slanweeks = form.cleaned_data['slanweeks']
    slanpct = form.cleaned_data['slanpct']
    
    slamcount = form.cleaned_data['slamcount']
    slamweeks = form.cleaned_data['slamweeks']
    slampct = form.cleaned_data['slampct']

    if 'asof' in form.cleaned_data:     # optional as-of date for debugging
        asof = form.cleaned_data['asof']
    else:
        asof = None
    
    return statscomm(request, mindate, maxdate, which, slancount, slanweeks, slanpct, slamcount, slamweeks, slampct, nav, today=asof)

def statscomm(request, mindate, maxdate, which, slancount, slanweeks, slanpct, slamcount, slamweeks, slampct, nav=None, deeplink=False,
    today=None):
    """
        regular or deep version
        parameters for all the SLA values
        as-of date for today, mostly for debugging

        currently only 'F' can be deep linked
    """

    if which == 'P':                    # page counts
        pagecounts = [ (quarterfmt(mm[0]), subcount(mm[0], mm[1])) for mm in quarterlist(mindate, maxdate) ]
        return render(request, 'rpc/statspages.html', { 'min': quarterfmt(mindate), 'max': quarterfmt(maxdate),
            'pagecounts': pagecounts, 'bpnav': nav })
            
    if which == 'T':                    # edit times
        edstats = [ (quarterfmt(mm[0]), editstats(mm[0], mm[1] , SLAMPCT)) for mm in quarterlist(mindate, maxdate) ]
        return render(request, 'rpc/statsout.html', { 'min': quarterfmt(mindate),
            'max': quarterfmt(maxdate), 'edstats': edstats,
            'bpnav': nav })

    if which in 'CFS':   # Chart, Formatted, or Stats
        slastats = []

        # check each quarter to see if SLA met
        # catchup means tier 2 in previous quarter,
        # BUG need to look back from first quarter

        # look back a quarter to do catch up
        catchup = False
        if mindate.month < 4:
            prevq = date(mindate.year-1, 10, 1)
        else:
            prevq = date(mindate.year, mindate.month-3, 1)
        pc = getpgte(*rquarter(prevq))
        if pc:                      # skip if there's nothing going on this quarter
            if pc['count'] > slancount:
                catchup = True
        
        ql = quarterlist(mindate, maxdate)
        for mm in ql:
            pc = getpgte(*mm)
            if pc:                      # skip if there's nothing going on this quarter
                pgte = pc['count']
                dgte = len(pc['docs'])

                if pgte <= slancount:
                    percent = slampct if catchup else slanpct
                    goal = slamweeks if catchup else slanweeks
                    volume = 'Tier 2*' if catchup else 'Tier 1' # also report what t1 would have said if in 2*
                    catchup = False
                elif pgte <= slamcount:
                    percent = slampct
                    goal = slamweeks
                    catchup = True
                    volume = 'Tier 2'
                else:
                    percent = 0
                    goal = 999
                    catchup = True
                    volume = 'Tier 3'

                stats = editstats(mm[0], mm[1], percent, maxweeks=goal)
                if not stats:
                    continue
                metsla = stats['okpercentile'] <= goal
                percentile = getpercentile(stats['weeks'], goal)
                stats['pgte'] = pgte
                stats['dgte'] = dgte
                stats['oldpgte'] = getpgte(mm[0], mm[1], oldway=True)['count']
                stats['startdate'] = quarterfmt(mm[0])
                stats['goal'] = "{0}w / {1}%".format(goal, percent)
                stats['metsla'] = metsla
                stats['volume'] = volume
                if '2*' in volume:
#                    stats['metweeks'] = '{0} / {1}'.format(stats['metweeks'], len(filter(lambda x: x <= SLANWEEKS, stats['weeks'])))
                    percentile = '{0} / {1}'.format(percentile, getpercentile(stats['weeks'], SLANWEEKS))
                stats['percentile'] = percentile

                # get the submission counts
                c = subcount(*mm)
                stats['subdocs'] = c['count']
                stats['subpages'] = c['pagecount']
                stats['partial'] = c['partial']
                slastats.append(stats)

        if which == 'S':
            return render(request, 'rpc/statssla.html', { 'min': quarterfmt(mindate), 'max': quarterfmt(maxdate),
                'slastats': slastats, 'bpnav': nav })

        if which == 'F':
            # turn the chart sideways to make it look like the old one
            # make headers
            hdr1 = ""
            hdr2 = ""
            if not today:
                today = date.today()
            oyear = None                # year of previous header
            qspan = None                # quarters to span
            for mm in ql:
#                if mm[0] > today:
#                    break               # nothing past now
                if mm[0].year != oyear:
                    if oyear:
                        hdr1 += '<th class="center" colspan="{0}">{1}</th>'.format(qspan, oyear)
                    oyear = mm[0].year
                    qspan = 0
                hdr2 += '<th class="center">Q{0}{1}</th>'.format(((mm[0].month+2)//3), "<br/>(so far)" if mm[1] > today else "")
                qspan += 1
            hdr1 += '<th class="center" colspan="{0}">{1}</th>'.format(qspan, oyear)

            # now slice them all sideways
            subpages = (s['subpages'] for s in slastats)
            pgte = (s['pgte'] for s in slastats)
            dgte = (s['dgte'] for s in slastats)
            pubpcount = (s['count'] for s in slastats)
            pubpages = (s['pagecount'] for s in slastats)
            subdocs = (s['subdocs'] for s in slastats)
            metweeks = (s['metweeks'] for s in slastats)
            tier = ( "" if s['partial'] else s['volume'] for s in slastats)
#            tier = ( s['volume'] for s in slastats)
            metsla = ( 'x' if s['partial'] else "y" if s['metsla'] else "n" for s in slastats)
#            metsla = ( "y" if s['metsla'] else "n" for s in slastats)

            return render(request, 'rpc/statsdeep.html' if deeplink else 'rpc/statsfmt.html',
                { 'min': quarterfmt(mindate), 'max': quarterfmt(maxdate),
                'hdr1': hdr1, 'hdr2': hdr2, 'subpages': subpages, 'pgte': pgte, 'dtge': dgte,
                'subdocs': subdocs, 'ncols': len(ql), 'pubcount': pubpcount, 'pubpages': pubpages,
                'metweeks': metweeks, 'tier': tier, 'metsla': metsla,
                'dl': deeplinkurl('sla', mindate=mindate, maxdate=maxdate, which=which, slancount=slancount, slanweeks=slanweeks, slanpct=slanpct,
                    slamcount=slamcount, slamweeks=slamweeks, slampct=slampct),
                'bpnav': nav })

        return HttpResponse("Mystery request "+which)

@login_required
def moncharts(request):
    """
    draw submission charts
    """
    nav = bpnav(request, 'monchart')
    
    if request.method == 'GET':
        form = MonthChartsRangeForm()
        return render(request, "rpc/stats.html", { 'title': "Pages by month", 'form': form, 'bpnav': nav })

    form = MonthChartsRangeForm(request.POST)
    if not form.is_valid():
        return render(request, 'rpc/stats.html', { 'title': "Pages by month", 'form': form, 'bpnav': nav })

    mindate = form.cleaned_data['mindate']
    maxdate = form.cleaned_data['maxdate']
    dodocs = form.cleaned_data['dodocs']
    dopgte = form.cleaned_data['dopgte']
    dosubpages = form.cleaned_data['dosubpages']
    dopubpages = form.cleaned_data['dopubpages']
    dosplines = form.cleaned_data['dosplines']
    doxls = form.cleaned_data['doxls']

    return moncomm(request, mindate, maxdate, dodocs, dopgte, dosubpages, dopubpages, dosplines, doxls=doxls, nav=nav)

def moncomm(request, mindate, maxdate, dodocs, dopgte, dosubpages, dopubpages, dosplines, doxls=False, nav=None, deeplink=False):
    """ do regular or deep linked
    monthly charts
    """
    
    dlurl = deeplinkurl('mon', mindate=mindate, maxdate=maxdate, dodocs=dodocs, dopgte=dopgte, dosubpages=dosubpages, dopubpages=dopubpages, dosplines=dosplines)

    pgte = []
    oldpgte = []
    subpages = []
    pubpages = []
    
    ml = monthlist(mindate, maxdate)
    for mm in ml:
        p = getpgte(*mm, dodocs=dodocs)
        if p:                           # off the end
            pgte.append(p['count'])
        else:
            pgte.append(None)

        p = getpgte(*mm, oldway=True, dodocs=dodocs)
        if p:
            oldpgte.append(p['count'])
        else:
            oldpgte.append(None)

        p = subcount(*mm)
        if p:
            subpages.append(p['count' if dodocs else 'pagecount'])
        else:
            subpages.append(None)

        e = editstats(mm[0], mm[1], 0)
        if e:                           # off the end
            pubpages.append(e['count' if dodocs else 'pagecount'])
        else:
            pubpages.append(None)

    labels = list(map(lambda x: monthfmt(x[0]), ml))
    today = date.today()

    if ml[-1][1] > today:               # hack for partial quarter
        labels[-1] = labels[-1][:3]+" so far"

    if doxls:
        return xlsmonth(mindate, maxdate, ml, dodocs, dopgte, pgte,
            dosubpages, subpages, dopubpages, pubpages, dosplines)

    def plv(series):
        """
        canvasJS wants paired label and value
        skip items where value is None (or zero which may be bad)
        """
        return [{ 'label': x[0], 'y': x[1] } for x in zip(labels, series) if x[1] ]

    # charts
    chart = { 
            "pgte": plv(pgte),
            "oldpgte": plv(oldpgte),
            'subpages': plv(subpages),
            'pubpages': plv(pubpages),
        }

    return render(request, 'rpc/mondeep.html' if deeplink else 'rpc/monchart.html', { 'min': monthfmt(mindate), 'max': monthfmt(maxdate),
            'dodocs': dodocs,
            'dopgte': dopgte,
            'dosubpages': dosubpages,
            'dopubpages': dopubpages,
            'doslabands': False,
            'charttype': "spline" if dosplines else "line",
            'chart': chart, 'bpnav': nav, 'dl': dlurl })

@login_required
def qtrcharts(request):
    """
    draw SLA charts
    """
    nav = bpnav(request, 'qtrchart')
    
    if request.method == 'GET':
        form = ChartsRangeForm()
        return render(request, "rpc/stats.html", { 'title': "Pages by Qtr", 'form': form, 'bpnav': nav })

    form = ChartsRangeForm(request.POST)
    if not form.is_valid():
        return render(request, 'rpc/stats.html', { 'title': "Pages by Qtr", 'form': form, 'bpnav': nav })

    mindate = form.cleaned_data['mindate']
    maxdate = form.cleaned_data['maxdate']
    dopgte = form.cleaned_data['dopgte']
    dosubpages = form.cleaned_data['dosubpages']
    dopubpages = form.cleaned_data['dopubpages']
    doslabands = form.cleaned_data['doslabands']
    dosplines = form.cleaned_data['dosplines']
    doxls = form.cleaned_data['doxls']

    return qtrcomm(request, mindate, maxdate, dopgte, dosubpages, dopubpages, doslabands, dosplines, doxls=doxls, nav=nav)

def qtrcomm(request, mindate, maxdate, dopgte, dosubpages, dopubpages, doslabands, dosplines, doxls=False, nav=None, deeplink=False):
    """ regular or deep link quarterly charts
    """

    dlurl = deeplinkurl('qtr', mindate=mindate, maxdate=maxdate, dopgte=dopgte, dosubpages=dosubpages, dopubpages=dopubpages,
        doslabands=doslabands, dosplines=dosplines)

    pgte = []
    subpages = []
    pubpages = []

    ql = quarterlist(mindate, maxdate)
    for mm in ql:
        p = getpgte(*mm)
        if p:
            pgte.append(p['count'])
        else:
            pgte.append(None)

        p = subcount(*mm)
        if p:
            subpages.append(p['pagecount'])
        else:
            subpages.append(None)

        e = editstats(mm[0], mm[1], 0)
        if e:                           # off the end
            pubpages.append(e['pagecount'])
        else:
            pubpages.append(None)
    today = date.today()
    labels = [ quarterfmt(x[0]) for x in ql]
    if ql[-1][1] > today:               # hack for partial quarter
        labels[-1] = labels[-1][:3]+"so far"

    def plv(series):
        """
        canvasJS wants paired label and value
        skip if value is None
        """
        return [{ 'label': x[0], 'y': x[1] } for x in zip(labels, series) if x[1] ]

    # charts
    chart = { 
            "pgte": plv(pgte),
#            "oldpgte": plv(oldpgte),
            'subpages': plv(subpages),
            'pubpages': plv(pubpages),
        }

    if doxls:
        return xlsquarter(mindate, maxdate, labels, dopgte, pgte, dosubpages, subpages,
            dopubpages, pubpages, doslabands, dosplines)

    return render(request, 'rpc/qtrdeep.html' if deeplink else 'rpc/qtrchart.html', { 'min': quarterfmt(mindate), 'max': quarterfmt(maxdate),
            'dopgte': dopgte,
            'dosubpages': dosubpages,
            'dopubpages': dopubpages,
            'doslabands': doslabands,
            'charttype': "spline" if dosplines else "line",
            'chart': chart, 'bpnav': nav, 'dl': dlurl })

@login_required
def histch(request):
    """
    draw history bar charts
    """
    nav = bpnav(request, 'histch')

    if request.method == 'GET':
        form = HistChartsRangeForm()
        return render(request, "rpc/stats.html", { 'title': "Primary States", 'form': form, 'bpnav': nav })

    form = HistChartsRangeForm(request.POST)
    if not form.is_valid():
        return render(request, 'rpc/stats.html', { 'title': "Primary States", 'form': form, 'bpnav': nav })

    mindate = form.cleaned_data['mindate']
    maxdate = rquarter(form.cleaned_data['maxdate'])[1] # end of quarter
    doarea = form.cleaned_data['doarea']
    qlabel = form.cleaned_data['qlabel']
    doxls = form.cleaned_data['doxls']
    
    return histcomm(request, mindate, maxdate, doarea, qlabel, doxls=doxls, nav=nav)

def histcomm(request, mindate, maxdate, doarea, qlabel, doxls=False, nav=None, deeplink=False):
    """state history normal or deep
    """

    dlurl = deeplinkurl('hist', mindate=mindate, maxdate=maxdate, doarea=doarea, qlabel=qlabel)

    statenames = ('EDIT','RFC-EDITOR','AUTH48','AUTH48-DONE')
    # get each series with dates
    chart = {
        stn.replace('-','_'): stateseries(mindate, maxdate, stn) # django templates don't like hyphens in names
            for stn in statenames
            }
        
    if doxls:
        return xlshist(mindate, maxdate, doarea, chart, statenames)

    return render(request, 'rpc/histdeep.html' if deeplink else 'rpc/histchart.html', { 'min': mindate, 'max': maxdate,
            'charttype': "line" if doarea else "bar", 'statenames': statenames, 'qlabel': qlabel,
            'chart': chart, 'bpnav': nav, 'dl': dlurl })

@login_required
def monthly(request):
    """
    return monthly stats
    """
    nav = bpnav(request, 'monthly')
    if request.method == 'GET':
        form = EditMonthRangeForm()
        return render(request, 'rpc/month.html', { 'form': form, 'bpnav': nav})

    form = EditMonthRangeForm(request.POST)
    if not form.is_valid():
        return render(request, 'rpc/month.html', { 'form': form, 'bpnav': nav })

    # get edit times for those RFCs
    mindate = form.cleaned_data['mindate']
    maxdate = rmonth(form.cleaned_data['maxdate'])[1] # end of month
    retshort = form.cleaned_data.get("retshort")
    retmedium = form.cleaned_data.get("retmedium")

    times = edittime(mindate, maxdate)

    # sort times by total time
    times.sort(key=lambda x: x['both'])
    retshortcount = 0
    retmediumcount = 0
    retlongcount = 0
    for t in times:
        tx = t['both']
        if tx <= retshort:
            retshortcount += 1
        elif tx <= retmedium:
            retmediumcount += 1
        else:
            retlongcount += 1
    
    # monthly doc count
    count = len(times)

    return render(request, 'rpc/month1.html', { 'min': monthfmt(mindate), 'max': monthfmt(maxdate),
        'times': times, 'retshort': retshort, 'retshortcount': retshortcount, 'retmedium': retmedium,
        'retmediumcount': retmediumcount, 'retlongcount': retlongcount, 'bpnav': nav, 'count': count })
    
@login_required
def errata(request):
    """
    charts for errata
    P: pie by type and status
    Y: stacked column by year and type/status
    S: type/status crosstab
    X: excel of all of the above
    """

    nav = bpnav(request, 'errata')
    if request.method == 'GET':
        form = ErrataForm()
        return render(request, 'rpc/errata0.html', { 'form': form, 'bpnav': nav})

    form = ErrataForm(request.POST)
    if not form.is_valid():
        return render(request, 'rpc/errata.html', { 'form': form, 'bpnav': nav })

    which = form.cleaned_data['which']
    doarea = form.cleaned_data['doarea']

    return commerrata(request, which, doarea, nav=nav)

def commerrata(request, which, doarea, nav=None, deeplink=False):
    """ regular or deep errata chart
    """

    dlurl = deeplinkurl('err', which=which, doarea=doarea)

    # get count of RFCs
    errfcs = geterrata(by='RFC')
    nerrata = len(list(errfcs))

    # get summarized data
    # get everything for XLS

    if which in 'PX':
        # get type sommary
        terrata = geterrata(by='Type')
        tetotal = sum(terrata.values())  # total errata count

        # get status sommary
        serrata = geterrata(by='Status')
        setotal = sum(serrata.values())  # total errata count

    if which == 'P':                    # web page version
        typedata = ",\n".join(["{{y: {0}, indexLabel: \"{1}\"}}".format(c, t) for t,c in terrata.items()])
        statusdata = ",\n".join(["{{y: {0}, indexLabel: \"{1}\"}}".format(c, s.split(' ')[0]) for s,c in serrata.items()])
        return render(request, 'rpc/erratadeep.html' if deeplink else 'rpc/errata.html', { 'terrata': terrata, 'tetotal': tetotal, 'typedata': typedata,
         'serrata': serrata, 'setotal': setotal, 'statusdata': statusdata, 'nerrata': nerrata,
        'bpnav': nav, 'dl': dlurl })

    if which in 'YX':
        yterrata = getyearerrata(by='Type')
        yserrata = getyearerrata(by='Status')
        types = erratameta(by='Type')
        statuses = erratameta(by='Status')
        tyears = list(yterrata.keys())
        tyears.sort()                    # years look nicer in order
        syears = list(yserrata.keys())
        syears.sort()                    # years look nicer in order
    if which in 'Y':
        # make year series by type and status
        tydata = {
                ty: ",\n".join([ "{{ x: new Date({0}, 1, 1), y: {1} }}".format(y, yterrata[y].get(ty, 0)) for y in sorted(tyears)])
            for ty in types
            }

        # split status name and use only first word
        # skip entries for years where it's zero (that renders OK in
        # the chart)
        sydata = {
                st.split(' ')[0]: ",\n".join([ "{{ x: new Date({0}, 1, 1), y: {1} }}".format(y,
                    yserrata[y][st]) for y in sorted(syears) if st in yserrata[y] ])
            for st in statuses
            }
        return render(request, 'rpc/erratayeardeep.html' if deeplink else 'rpc/erratayear.html', { 'tydata': tydata, 'types': types, 'tyears': tyears,
            'sydata': sydata, 'statuses': statuses, 'syears': syears,
            'charttype': "stackedArea" if doarea else "stackedColumn",
            'bpnav': nav, 'dl': dlurl })

    if which in 'AX':
        eba = erratabyarea(type='Technical', status='Reported')
        # put them in nice alpha order
        areas = [ t for t,c in eba.items() if t]
        areas.sort()

    if which == 'A':                    # by area
        etotal = sum((c for t,c in eba.items() if t))

        areadata = ",\n".join(["{{y: {0}, indexLabel: \"{1} ({0})\"}}".format(eba[a], a) for a in areas])

        return render(request, 'rpc/errataareadeep.html' if deeplink else 'rpc/errataarea.html', { 'areadata': areadata,
            'etotal': etotal, 'bpnav': nav, 'dl': dlurl })

    # excel version
    if which == 'X':
        return xlserrata(terrata, tetotal, serrata, setotal, eba, areas,
            yterrata, yserrata, types, statuses, tyears, syears, doarea)

    return HttpResponse("Mystery chart "+which)

@login_required
def yearly(request):
    """
    simple chart of RFCs per year
    """
    nav = bpnav(request, 'yearly')

    if request.method == 'GET':
        form = YearChartForm()
        return render(request, "rpc/stats.html", { 'title': "Annual Pub", 'form': form, 'bpnav': nav })

    form = YearChartForm(request.POST)
    if not form.is_valid():
        return render(request, 'rpc/stats.html', { 'title': "Annual Pub", 'form': form, 'bpnav': nav })

    minyear = int(form.cleaned_data['minyear'])
    maxyear = int(form.cleaned_data['maxyear'])
    doarea = form.cleaned_data['doarea']
    dopages = form.cleaned_data['dopages']
    dosub = form.cleaned_data['dosub']
    doxls = form.cleaned_data['doxls']
    
    return yearcomm(request, minyear, maxyear, doarea, dopages, dosub, doxls=doxls, nav=nav)

def yearcomm(request, minyear, maxyear, doarea, dopages, dosub, doxls=False, nav=None, deeplink=False):
    """ regular or deep yearly charts
    """

    dlurl = deeplinkurl('year', minyear=minyear, maxyear=maxyear, doarea=doarea, dopages=dopages, dosub=dosub)

    rby = rfcsbyyear(minyear, maxyear, dopages=dopages)
    if dosub:
        sby = rfcsbyyear(minyear, maxyear, dopages=dopages, dosub=True)
        # pad this out since it only starts in 1996
        if minyear < sby[0].subyear:
            y = sby[0].subyear
            sby0 = [ {'subyear':i, 'numrfcs':0 } for i in range(minyear, y) ] # not quite the same as a raw query, but close enough
            sby = sby0 + list(sby)
    else:
        sby = None

    if doxls:
        return xlsyearly(minyear, maxyear, doarea, dopages, rby, sby)

    return render(request, 'rpc/yeardeep.html' if deeplink else 'rpc/yearchart.html', { 'minyear': minyear, 'maxyear': maxyear,
        'charttype': 'area' if doarea else 'column', 'dopages': dopages, 'thisyear': date.today().year,
        'rby': rby, 'sby': sby, 'bpnav': nav, 'dl': dlurl})

def dologout(request):
    """
    log out, return to main screen
    """
    logout(request)
    return redirect("/login/?next=/rpc/")

# boilerplate navigation toolbar at the top of each page
def bpnav(request, here):
    """
    return HTML for boilerplate navigation toolbar
    here is current page
    """
    def bp(page, desc):
        if here in page:
            return "<li><a href=\"{}\" class=active>{}</a></li>\n".format(page,desc)
        else:
            return "<li><a href=\"{}\">{}</a></li>\n".format(page,desc)

    bo = "<ul id=tabnav>\n"
    bo += bp("/rpc","Home")
    bo += bp("/rpc/range","RFCs by date")
    bo += bp("/rpc/pgte","PGTE docs")
    bo += bp("/rpc/monthly","RET")
    bo += bp("/rpc/stats","*SLA stats")
    bo += bp("/rpc/moncharts","*Pages by month")
    bo += bp("/rpc/qtrcharts","*Pages by Qtr")
    bo += bp("/rpc/yearly","Annual Pub")
    bo += bp("/rpc/histch","*Primary States")
    bo += bp("/rpc/errata","Errata")
    bo += bp("/admin","Admin")
    bo += bp("/rpc/logout", "Logout")
    bo += "</ul>\n<p align=right>Logged in as " + request.user.username
    bo += "</p>"
    return bo

###
### deep links
###

# how to make and parse deeplink URLs

# chart arg description
# name of function to call, and list of argtypename
# arg types are
# D date
# Y year
# L single letter
# T true or false (Y/F)
# I integer
# the names aare the names of arguments to pass to the function

dlcharts = {
    'sla': (statscomm, 'Dmindate', 'Dmaxdate', 'Lwhich', 'Islancount', 'Islanweeks', 'Islanpct', 'Islamcount', 'Islamweeks', 'Islampct'),
    'mon': (moncomm, 'Dmindate', 'Dmaxdate', 'Tdodocs', 'Tdopgte', 'Tdosubpages', 'Tdopubpages', 'Tdosplines'),
    'qtr': (qtrcomm, 'Dmindate', 'Dmaxdate', 'Tdopgte', 'Tdosubpages', 'Tdopubpages', 'Tdoslabands', 'Tdosplines'),
    'year': (yearcomm, 'Yminyear', 'Ymaxyear', 'Tdoarea', 'Tdopages', 'Tdosub'),
    'hist': (histcomm, 'Dmindate', 'Dmaxdate', 'Tdoarea', 'Tqlabel'),
    'err': (commerrata, 'Lwhich', 'Tdoarea')
}

dlsalt = "seekrit"                      # salt for DL hash

def dodeeplink(request, chart, hash, args):
    """deep link versions of charts

    decodes the args into the various types
    and then call the appropriate function
    """
    
    if chart not in dlcharts or not hash or not args:           # no such chart, die
        return HttpResponseNotFound('<p>No such deep link {0}.</p>'.format(chart))
    
    # check hash is OK
    #h = md5(dlsalt+chart+args).hexdigest()[:6]

    h_text = dlsalt+chart+args
    h = md5(h_text.encode('utf-8')).hexdigest()[:6]

    if h != hash:
        return HttpResponseNotFound('<p>Invalid deep link.</p>')

    # now decode the argument string into a dict of arguments
    av = { 'deeplink': True }           # always tell it this was a deep link

    dcfunc = dlcharts[chart][0]         # the function to call
    dcargs = dlcharts[chart][1:]        # the args to pass

    # there's no defensive code here, because we (optimistically) assume
    # that any link with a valid hash was produced here so will have
    # valid data
    for dca in dcargs:
        dtype = dca[0]
        dname = dca[1:]

        if dtype == 'D':                # date as YYYYMMDD
            av[dname] = date(int(args[:4]), int(args[4:6]), int(args[6:8]))
            args = args[8:]
        elif dtype == 'Y':                # year as YYYY
            av[dname] = int(args[:4])
            args = args[4:]
        elif dtype == 'L':               # single letter
            av[dname] = args[0]
            args = args[1:]
        elif dtype == 'T':               # true/false T/F
            av[dname] = args[0] == 'T'
            args = args[1:]
        elif dtype == 'I':
            av[dname] = int(args[:5])
            args = args[5:]
        else:
            print("invalid dtype ", dtype)
            return HttpResponseNotFound('<p>Internal deep link error.</p>')
            
    return dcfunc(request, **av)                 # call function with appropriate arguments



def deeplinkurl(chart, **args):
    """ create the deeplink URL for a chart

    takes a list of arguments to encode,
    returns dict with chart, hash, args to put into template {% url dl chart=... }
    """

    dcargs = dlcharts[chart][1:]        # the args to pass
    argstr = ""

    for dca in dcargs:
        dtype = dca[0]
        dname = dca[1:]
        dval = args[dname]

        if dtype == 'D':                # date as YYYYMMDD
            argstr += "{0.year:04d}{0.month:02d}{0.day:02d}".format(dval)
        elif dtype == 'Y':                # year as YYYY
            argstr += "{0:04d}".format(dval)
        elif dtype == 'L':               # single letter
            argstr += dval[0]
        elif dtype == 'T':               # true/false T/F
            argstr += 'T' if dval else 'F'
        elif dtype == 'I':
            argstr += "{:05d}".format(dval)
        else:
            print("invalid dtype ", dtype)
 
    #hash = md5(dlsalt+chart+argstr.encode("utf-8")).hexdigest()[:6]
    text = dlsalt+chart+argstr
    hash = md5(text.encode('utf-8')).hexdigest()[:6]
    return { 'chart': chart, 'hash': hash, 'args': argstr }
