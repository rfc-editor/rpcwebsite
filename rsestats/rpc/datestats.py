# logic for weekly bar charts

from django.db.models import Count, Sum
from .models import Index, States, StateHistory, StateByDate
from datetime import timedelta, date

# make statebydate from edit history
# or do a current snapshot

# terminal states
terminal = ('PUB','DNP','WITHDRAWN')

def makestatebydate(mindate, maxdate=None, verbose=0):
    """
    go through and make statebydate from statehistory
    """

    # start on a Monday
    if mindate.weekday():
        mindate += timedelta(7-mindate.weekday())

    if not maxdate:
        maxdate = date.today()

    # throw away old entries
    StateByDate.objects.filter(state_date__gte=mindate, state_date__lte=maxdate).delete()

    # get termimal states
    tsl = States.objects.filter(state_name__in=terminal)
    
    # keep track of each doc's state
    docs = dict()
    statehist = StateHistory.objects.filter(in_date__gte=mindate, in_date__lt=maxdate).order_by('in_date')
    curdate = mindate
    nextdate = curdate+timedelta(7)     # dump data once a week

    def dumpdocs():
        """
        put snapshot of pagecounts in statebydate
        """
        
        pages = dict()                  # will be [pages, docs]

        # loop over docs and get total page count for each state
        for doc, st in docs.items():
            #print doc, st, doc.sub_page_count
            if doc.sub_page_count:
                pc = doc.sub_page_count
            elif doc.page_count:
                pc = doc.page_count
            else:
                pc = None
            if pc:
                if st in pages :
                    pages[st][0] += pc
                    pages[st][1] += 1
                else:
                    pages[st] = [ pc, 1 ]

        # now store them into the table
        for st, count in pages.items():
            sbd = StateByDate(state_id=st, state_date=nextdate, page_count=count[0], doc_count=count[1])
            sbd.save()

        if verbose > 0:
            print("saved state for", nextdate)

    # now go through the state history and make the snapshots
    for st in statehist:
        if st.in_date > nextdate:
            dumpdocs()
            # move to next week
            curdate = nextdate
            nextdate += timedelta(7)

        try:                        # HACK -- there are history entries for nonexistent docs and states
            if st.state_id in tsl:
                if st.internal_dockey in docs:
                    del docs[st.internal_dockey]
            else:
                docs[st.internal_dockey] = st.state_id
        except (Index.DoesNotExist, States.DoesNotExist):
            pass

    # don't dump whatever's left at the end, it's past the end date

def snapstate(verbose=0):
    """
    add a snapshot of today's state
    to StateByDate for all non-terminal states
    return list of state, count
    counts page_count, not sub_page_count
    """
    
    today = date.today()

    # get active states, and state numbers
    asl = States.objects.exclude(state_name__in=terminal)

    # need a table that maps state IDs to state objects because
    # the state_ids in Index aren't linked to the state table
    stateobjs = { s.state_id: s for s in asl }

    # list of state ids
    live = stateobjs.keys()

    # summary page and doc counts by state
    pcounts = Index.objects.filter(state_id__in=live).values('state_id').annotate(pages=Sum('page_count'), docs=Count('state_id'))

    # delete any current entries for today
    StateByDate.objects.filter(state_date=today).delete()

    # add them to StateByDate
    for p in pcounts:
        if verbose > 1:
            print("add ",p)
        sbd = StateByDate(state_id=stateobjs[p['state_id']], page_count=p['pages'], doc_count=p['docs'], state_date=today)
        sbd.save()

    return pcounts

def stateseries(mindate, maxdate, state_name, docs=False):
    """
    get a formatted state series for graphing
    format is specific to CanvasJS or for xlsxwriter, [( date, value ), ... ]
    """
    sbd = StateByDate.objects.filter(state_date__gte=mindate,state_date__lte=maxdate,
        state_id__state_name=state_name).order_by('state_date')

    return ([ (x.state_date,  x.doc_count if docs else x.page_count) for x in sbd ])
