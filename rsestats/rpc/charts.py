# draw Excel charts

#from django.shortcuts import render
from django.http import HttpResponse
from datetime import date

import xlsxwriter

# SLA thrsholds (pagecount, days)
SLANCOUNT = 1950
SLANWEEKS = 6                           # normal six weeks
SLANPCT = 67
SLAMCOUNT = 3072
SLAMWEEKS = 12                          # moderate 12 weeks
SLAMPCT = 50

def xlsquarter(mindate, maxdate, labels, dopgte, pgte, dosubpages, subpages,
            dopubpages, pubpages, doslabands, dosplines):
    """
    draw excel charts for quarters
    labels, pgte, subpages, pubpages are parallel lists
    dopgte, dosubpages, dopubpages flags about what to display
    doslabands is color bands for SLA ranges, dosplines curves the curves
    """

    response = HttpResponse(content_type='application/vnd.ms-excel')
    response['Content-Disposition'] = 'attachment; filename="quarter.xlsx"'

    workbook = xlsxwriter.Workbook(response, {'in_memory': True})

    # add the sheet with labels

    worksheet = workbook.add_worksheet()
    worksheet.write_row(0,1,labels)
    worksheet.write_string(1,0,'PGTE')
    worksheet.write_row(1,1,pgte)
    worksheet.write_string(2,0,'Submitted')
    worksheet.write_row(2,1,subpages)
    worksheet.write_string(3,0,'Published')
    worksheet.write_row(3,1,pubpages)
    lastcol = len(list(labels))               # data start at column 1, so one-entry list ends at col 1

    # now put it on a chart
    chartsheet = workbook.add_chartsheet()
    chart = workbook.add_chart({'type': 'line'})
    chartsheet.set_chart(chart)
    
    chart.set_title({'name': "Quarterly from {0} to {1}".format(labels[0], labels[-1])})
    chart.set_y_axis({'name': 'Pages'})
    
    chart.add_series({
        'categories': [ 'Sheet1', 0, 1, 0, lastcol ], # labels in the first row
        'values': [ 'Sheet1', 1, 1, 1, lastcol ],
        'name': 'PGTE',
        'smooth': dosplines
        })

    chart.add_series({
        'categories': [ 'Sheet1', 0, 1, 0, lastcol ], # labels in the first row
        'values': [ 'Sheet1', 2, 1, 2, lastcol ],
        'name': 'Submitted',
        'smooth': dosplines
        })

    chart.add_series({
        'categories': [ 'Sheet1', 0, 1, 0, lastcol ], # labels in the first row
        'values': [ 'Sheet1', 3, 1, 3, lastcol ],
        'name': 'Published',
        'smooth': dosplines
        })

    if doslabands:                      # make a very dumb bar chart and whomp it onto the main chart
        bands = workbook.add_chart({'type': 'column', 'subtype': 'stacked'})
        # put the tiers into the main worksheet
        worksheet.write_string(5, 0, 'Tier 1')
        worksheet.write_string(6, 0, 'Tier 2')
        for i in range(lastcol):
            worksheet.write_number(5, i+1, SLANCOUNT)
            worksheet.write_formula(6, i+1, '={0}-{1}'.format(SLAMCOUNT,SLANCOUNT))

        bands.add_series({
            'categories': [ 'Sheet1', 5,0, 5,0 ], # labels in the first col
            'values': [ 'Sheet1', 5,1, 5, lastcol ],
            'fill': { 'color': '#D0FFD0', 'transparency': 50},
            'gap': 0,
            'name': 'Tier 1',
            })
        bands.add_series({
            'categories': [ 'Sheet1', 6,0, 6,0 ], # labels in the first col
            'values': [ 'Sheet1', 6,1, 6,lastcol ],
            'fill': { 'color': '#FFFF20', 'transparency': 50 },
            'gap': 0,
            'name': 'Tier 2',
            })
        chart.combine(bands)

    # now write it out
    workbook.close()
    return response

def xlsmonth(mindate, maxdate, ml, dodocs, dopgte, pgte, dosubpages, subpages,
            dopubpages, pubpages, dosplines):
    """
    make monthly page charts
    labels, pgte, subpages, pubpages are parallel lists
    dopgte, dosubpages, dopubpages flags about what to display
    doslabands is color bands for SLA ranges, dosplines curves the curves
    dodocs is count docs rather than pages
    """

    response = HttpResponse(content_type='application/vnd.ms-excel')
    response['Content-Disposition'] = 'attachment; filename="monthly.xlsx"'

    workbook = xlsxwriter.Workbook(response, {'in_memory': True})

    # add the sheet with labels

    worksheet = workbook.add_worksheet()
    worksheet.write_string(0,0,'Month')
    month_format = workbook.add_format({'num_format': 'mmm yyyy'})
    worksheet.write_row(0,1, (x[1] for x in ml), month_format)
    worksheet.write_string(1,0,'PGTE')
    worksheet.write_row(1,1,pgte)
    worksheet.write_string(2,0,'Submitted')
    worksheet.write_row(2,1,subpages)
    worksheet.write_string(3,0,'Published')
    worksheet.write_row(3,1,pubpages)
    lastcol = len(list(ml))                   # data start at column 1, so one-entry list ends at col 1

    # now put it on a chart
    chartsheet = workbook.add_chartsheet()
    chart = workbook.add_chart({'type': 'line'})
    chartsheet.set_chart(chart)
    
    chart.set_title({'name': "Monthly from {0}/{1} to {2}/{3}".format(
        ml[0][0].month, ml[0][0].year, ml[-1][1].month, ml[-1][1].year)})
    chart.set_x_axis({'date_axis': True,
        'minor_unit':      1,
        'minor_unit_type': 'months',
        'major_unit':      1,
        'major_unit_type': 'months',
        'num_format': 'mm/yy'
    })
    chart.set_y_axis({'name': 'Pages'})
    
    chart.add_series({
        'categories': [ 'Sheet1', 0, 1, 0, lastcol ], # labels in the first row
        'values': [ 'Sheet1', 1, 1, 1, lastcol ],
        'name': 'PGTE',
        'smooth': dosplines
        })

    chart.add_series({
        'categories': [ 'Sheet1', 0, 1, 0, lastcol ], # labels in the first row
        'values': [ 'Sheet1', 2, 1, 2, lastcol ],
        'name': 'Submitted',
        'smooth': dosplines
        })

    chart.add_series({
        'categories': [ 'Sheet1', 0, 1, 0, lastcol ], # labels in the first row
        'values': [ 'Sheet1', 3, 1, 3, lastcol ],
        'name': 'Published',
        'smooth': dosplines
        })

    # now write it out
    workbook.close()
    return response

def xlserrata(terrata, tetotal, serrata, setotal, eba, areas,
                yterrata, yserrata, types, statuses, tyears, syears, doarea):
    """
    spreadsheet with pie charts of errata
    terrata. tetotal is type summary
    serrata, setotal is status summary
    eba is errata by area
    areas is alphabetized area names
    yterrata, yeserrata is count by year and type or status
    types, statuses are names, tyears, syears are sorted lists of years
    """

    response = HttpResponse(content_type='application/vnd.ms-excel')
    response['Content-Disposition'] = 'attachment; filename="errata.xlsx"'

    workbook = xlsxwriter.Workbook(response, {'in_memory': True})
    yearformat = workbook.add_format({'num_format': "yyyy" })

    # add the sheet with labels

    ws1 = "Type and Status"
    ws2 = "By Source"
    ws3 = "By Year"
    
    worksheet = workbook.add_worksheet(ws1)
    worksheet.write_string(0,0,'Type')
    # fragile: I think iteritems always returns items in the same
    # order
    worksheet.write_row(0,1, (t for t,c in terrata.items()))
    worksheet.write_row(1,1, (c for t,c in terrata.items()))
    worksheet.write_string(2,0,'Status')
    worksheet.write_row(2,1, (s for s,c in serrata.items()))
    worksheet.write_row(3,1, (c for s,c in serrata.items()))

    typechart = workbook.add_chart({'type': 'pie'})
    statuschart = workbook.add_chart({'type': 'pie'})
    
    typechart.set_title({'name': "Errata by Type"})
    typechart.add_series({
        'categories': [ ws1, 0, 1, 0, len(terrata) ], # labels in the first row
        'values': [ ws1, 1, 1, 1, len(terrata) ],
        'data_labels': {'percentage': True},
        'name': 'Types',
        })

    statuschart.set_title({'name': "Errata by Status"})
    statuschart.add_series({
        'categories': [ ws1, 2, 1, 2, len(serrata) ], # labels in the first row
        'values': [ ws1, 3, 1, 3, len(serrata) ],
        'data_labels': {'percentage': True},
        'name': 'Status',
        })

    worksheet.insert_chart(5, 0, typechart)
    worksheet.insert_chart(5, 8, statuschart)

    # areas
    aworksheet = workbook.add_worksheet(ws2)
    aworksheet.write_string(0,0, 'Area')
    aworksheet.write_string(1,0, 'Count')
    aworksheet.write_string(2,0, 'Label')
    aworksheet.write_row(0,1, areas)
    aworksheet.write_row(1,1, (eba[a] for a in areas))
    aworksheet.write_row(2,1, ("{0} ({1})".format(a, eba[a]) for a in areas))

    areachart = workbook.add_chart({'type': 'pie'})
    areachart.set_size({'x_scale': 1.5, 'y_scale': 1.5})
    
    areachart.set_title({'name': "Errata by Source"})
    areachart.add_series({
        'categories': [ ws2, 2, 1, 2, len(areas) ], # labels in the third row
        'values': [ ws2, 1, 1, 1, len(areas) ],
        'data_labels': {'category': 'True'},
        'name': 'Types',
        })

    aworksheet.insert_chart(5, 0, areachart)

    # yearly charts
    yworksheet = workbook.add_worksheet(ws3)
    # types
    yworksheet.write_string(0,0, 'Year')
    yworksheet.write_row(0,1, [ date(y,1,1) for y in tyears ], yearformat)
    for r, n in enumerate(types, start=1):
        yworksheet.write_string(r,0, n)
        yworksheet.write_row(r, 1, [ yterrata[y].get(n, None) for y in yterrata ])

    # statuses
    statbase = len(list(types))+2
    yworksheet.write_string(statbase,0, 'Year')
    yworksheet.write_row(statbase,1, [ date(y,1,1) for y in syears ], yearformat)
    for r, n in enumerate(statuses, start=1+statbase):
        yworksheet.write_string(r,0, n)
        yworksheet.write_row(r, 1, [ yserrata[y].get(n, None) for y in yserrata ])
    
    chbase = statbase+len(list(statuses))+2
    ytypechart = workbook.add_chart({'type': 'area' if doarea else 'column', 'subtype': 'stacked' })
    ytypechart.set_size({'x_scale': 1.5, 'y_scale': 1.5})
    ytypechart.set_title({'name': "Errata by type and year from {0} to {1}".format(tyears[0], tyears[-1])})
    ytypechart.set_x_axis({
        'date_axis': True,
        'num_format': 'yyyy'
        })

    for r, n in enumerate(types, start=1):
        ytypechart.add_series({
            'values': [ws3, r,1, r,len(tyears)],
            'categories': [ws3, 0,1, 0,len(tyears)], # dates in row 0
            'name': n
            })

    ystatuschart = workbook.add_chart({'type': 'area' if doarea else 'column', 'subtype': 'stacked' })
    ystatuschart.set_size({'x_scale': 1.5, 'y_scale': 1.5})
    ystatuschart.set_title({'name': "Errata by status and year from {0} to {1}".format(syears[0], syears[-1])})
    ystatuschart.set_x_axis({
        'date_axis': True,
        'num_format': 'yyyy'
        })

    for r, n in enumerate(statuses, start=1+statbase):
        ystatuschart.add_series({
            'values': [ws3, r,1, r,len(syears)],
            'categories': [ws3, statbase,1, statbase,len(syears)], # dates in row 0
            'name': n[:4] if len(n) > 10 else n # trim Held for document update
            })

    yworksheet.insert_chart(chbase, 1, ytypechart)
    yworksheet.insert_chart(chbase+25, 1, ystatuschart)

    # now write it out
    workbook.close()
    return response

def xlshist(mindate, maxdate, doarea, chartdata, statenames):
    """
    weekly history as stacked bar or area
    chart is dict of 'EDIT','RFC_EDITOR','AUTH48','AUTH48_DONE' : list of (date,  values)
    cheat: assume all lists are the same length

    """
    response = HttpResponse(content_type='application/vnd.ms-excel')
    response['Content-Disposition'] = 'attachment; filename="weeklyhistory.xlsx"'

    workbook = xlsxwriter.Workbook(response, {'in_memory': True})
    dateformat = workbook.add_format({'num_format': 14 }) # format 14 is m/d/yy

    # add the sheet with labels

    worksheet = workbook.add_worksheet()
    worksheet.write_string(0,0,'Date')
    worksheet.write_row(0,1, (x[0] for x in chartdata['EDIT']), dateformat)

    lastcol = len(chartdata['EDIT']) # data starts in col 1

    # row of data for each state
    for r, n in enumerate(statenames, start=1):
        worksheet.write_string(r,0,n)
        worksheet.write_row(r,1, ( x[1] for x in chartdata[n.replace('-','_')]))

    # now put it on a chart
    chartsheet = workbook.add_chartsheet()
    chart = workbook.add_chart({'type': 'area' if doarea else 'line', 'subtype': 'stacked' })
    chartsheet.set_chart(chart)
    
    chart.set_title({'name': "Pages by state from {0} to {1}".format(mindate, maxdate)})
    chart.set_x_axis({
        'date_axis': True,
        'num_format': 'm/d/yy'
        })
    chart.set_y_axis({'name': 'Pages'})

    # add the series
    for r, n in enumerate(statenames, start=1):
        chart.add_series({
            'values': ['Sheet1', r,1, r,lastcol],
            'categories': ['Sheet1', 0,1, 0,lastcol], # dates in row 0
            'name': n
            })

    
    # now write it out
    workbook.close()
    return response

def xlsyearly(minyear, maxyear, doarea, dopages, rby, sby):
    """
    yearly bar or area chart
    of RFC counts
    """
    
    response = HttpResponse(content_type='application/vnd.ms-excel')
    response['Content-Disposition'] = 'attachment; filename="yearlycount.xlsx"'

    workbook = xlsxwriter.Workbook(response, {'in_memory': True})
    dateformat = workbook.add_format({'num_format': "yyyy" })

    # render the raw sql rby
    dates = [ date(x.pubyear, 1,1) for x in rby ]
    counts = [ x.numrfcs for x in rby ]

    # add the sheet with labels

    worksheet = workbook.add_worksheet()
    worksheet.write_string(0,0,'Date')
    worksheet.write_row(0,1, dates, dateformat)
    if date.today().year == maxyear:    # make this year YTD
        worksheet.write_string(0,len(dates), "{0}YTD".format(maxyear))
    worksheet.write_string(1,0,'Count')
    worksheet.write_row(1,1, counts)
    if sby:
        submitted = [ s['numrfcs'] if type(s) == dict else s.numrfcs for s in sby ]
        worksheet.write_string(2,0,'Submitted')
        worksheet.write_row(2,1, submitted)
        
    # now put it on a chart

    chartsheet = workbook.add_chartsheet()
    chart = workbook.add_chart({'type': 'area' if doarea else 'column' })
    chartsheet.set_chart(chart)
    
    chart.set_title({'name': "{2} from {0} to {1}".format(minyear, maxyear, "Page counts" if dopages else "RFCs published")})
    chart.set_x_axis({
        'date_axis': True,
        'num_format': 'yyyy'
        })
    chart.set_y_axis({'name': 'Pages' if dopages else 'RFCs'})

    chart.add_series({
        'values': ['Sheet1', 1,1, 1,len(dates)],
            'categories': ['Sheet1', 0,1, 0,len(dates)], # dates in row 0
            'name': 'Pages' if dopages else 'RFCs'
        })
    if sby:
        chart.add_series({
            'values': ['Sheet1', 2,1, 2,len(dates)],
            'categories': ['Sheet1', 0,1, 0,len(dates)], # dates in row 0
            'name': 'Submitted'
        })
    
    # now write it out
    workbook.close()
    return response
