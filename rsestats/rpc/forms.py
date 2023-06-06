# forms for RPC statistics

from django.forms import Form, MultiValueField, MultiWidget, ValidationError, ChoiceField, Select, BooleanField, IntegerField, DateField
import datetime
from .slaparams import *

THISYEAR = datetime.date.today().year
MANYYEARRANGE = range(1968,THISYEAR+1)  # for the annual RFC counts
YEARRANGE = range(2013,THISYEAR+1)

# calendar month and quarter widget and field
QUARTERS = ((1, 'Q1'), (4, 'Q2'), (7, 'Q3'), (10, 'Q4'))
MONTHS = tuple(enumerate( ('Jan','Feb', 'Mar', 'Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'),
    start=1))
YEARS = tuple( (y,str(y)) for y in YEARRANGE)
MANYYEARS = tuple( (y,y) for y in MANYYEARRANGE)

class MonthWidget(MultiWidget):
    """
    choose dates by selecting month and year
    
    """
    def __init__(self, *args, **kwargs):
        widgets=(Select(choices=MONTHS), Select(choices=YEARS))
        super(MonthWidget, self).__init__(widgets=widgets, *args, **kwargs)

    def decompress(self, value):
        if value:
            return [value.month, value.year ]
        return [None, None]

class MonthField(MultiValueField):
    """
    handle months
    value is date of the first day of the month
    """

    def __init__(self, *args, **kwargs):
        fields = (
            ChoiceField(choices=MONTHS),
            ChoiceField(choices=YEARS)
        )
        widget = MonthWidget()
        super(MonthField, self).__init__(fields=fields, widget=widget, *args, **kwargs)

    def compress(self, datalist):
        if datalist:
            return datetime.date(day=1, month=int(datalist[0]), year=int(datalist[1]))
    
# form with one month
class MonthForm(Form):
    error_css_class = 'err'
    required_css_class = 'err'
    month = MonthField(label="Date", initial=datetime.date.today())

class MonthRangeForm(Form):
    error_css_class = 'err'
    required_css_class = 'err'
    mindate = MonthField(label="Min date", initial=datetime.date(2018,1,1))
    maxdate = MonthField(label="Max date", initial=datetime.date.today())

    def clean(self):
        """
        make sure data are internally consistent

        """
        mindate = self.cleaned_data.get("mindate")
        maxdate = self.cleaned_data.get("maxdate")
        if not mindate or not maxdate:
            raise ValidationError("Both dates are required")
        if mindate > maxdate:
            raise ValidationError("Min date must be less than max date")

# ranges for edit times
class EditMonthRangeForm(MonthRangeForm):
    retshort = IntegerField(label="Weeks for short edit", initial=RETSHORT)
    retmedium = IntegerField(label="Weeks for medium edit", initial=RETMEDIUM)

    def clean(self):
        super(EditMonthRangeForm, self).clean()
        retshort = self.cleaned_data.get("retshort")
        retmedium = self.cleaned_data.get("retmedium")
        if retshort <= 0 or retmedium <= 0:
            raise ValidationError("Times must be greater than zero")
        if retshort >= retmedium:
            raise ValidationError("Medium time must be greater than short time")
            
    
### same thing with quarters
class QuarterWidget(MultiWidget):
    """
    choose calendar quarters by selecting quarter and year
    
    """
    def __init__(self, *args, **kwargs):
        widgets=(Select(choices=QUARTERS), Select(choices=YEARS))
        super(QuarterWidget, self).__init__(widgets=widgets, *args, **kwargs)

    def decompress(self, value):
        if value:
            return [value.month, value.year ]
        return [None, None]

class QuarterField(MultiValueField):
    """
    handle calendar quarters
    value is date of the first day of the quarter
    """

    def __init__(self, *args, **kwargs):
        fields = (
            ChoiceField(choices=QUARTERS),
            ChoiceField(choices=YEARS)
        )
        widget = QuarterWidget()
        super(QuarterField, self).__init__(fields=fields, widget=widget, *args, **kwargs)

    def compress(self, datalist):
        if datalist:
            return datetime.date(day=1, month=int(datalist[0]), year=int(datalist[1]))
    
# form with one quarter
class QuarterForm(Form):
    error_css_class = 'err'
    required_css_class = 'err'
    quarter = QuarterField(label="Date", initial=datetime.date.today())

# form with a quarter range
class RangeForm(Form):
    error_css_class = 'err'
    required_css_class = 'err'
    mindate = QuarterField(label="Min date", initial=datetime.date(2018,1,1))
    maxdate = QuarterField(label="Max date", initial=datetime.date.today())

    def clean(self):
        """
        make sure data are internally consistent

        """
        mindate = self.cleaned_data.get("mindate")
        maxdate = self.cleaned_data.get("maxdate")
        if not mindate or not maxdate:
            raise ValidationError("Both dates are required")

        if mindate > maxdate:
            raise ValidationError("Min date must be less than max date")


STATCHOICES = (('T', 'Edit times'), ('P', 'Page count'), ('S', 'SLA stats'), ('F', "Formatted stats"))
class StatsRangeForm(RangeForm):
    which = ChoiceField(label="Which statistics:", widget=Select, choices=STATCHOICES)
# allow adjusted SLA parameters
    slancount = IntegerField(label="Normal page count", initial=SLANCOUNT)
    slanweeks = IntegerField(label="Normal weeks", initial=SLANWEEKS)
    slanpct = IntegerField(label="Normal percent", initial=SLANPCT, min_value=0, max_value=100)

    slamcount = IntegerField(label="Moderate page count", initial=SLAMCOUNT)
    slamweeks = IntegerField(label="Moderate weeks", initial=SLAMWEEKS)
    slampct = IntegerField(label="Moderate percent", initial=SLAMPCT, min_value=0, max_value=100)
# this next line can be commented out if not debugging
#    asof = DateField(label="As of date", initial=datetime.date.today())

    def clean(self):
        """
        make sure data are internally consistent

        """
        slancount  = self.cleaned_data.get("slancount")
        slamcount  = self.cleaned_data.get("slamcount")
        if slancount >= slamcount:
            raise ValidationError("Moderate page count must be more than normal page count")
    
        slanweeks  = self.cleaned_data.get("slanweeks")
        slamweeks  = self.cleaned_data.get("slamweeks")
        if slanweeks >= slamweeks:
            raise ValidationError("Moderate weeks must be more than normal weeks")
    
        slanpct  = self.cleaned_data.get("slanpct")
        slampct  = self.cleaned_data.get("slampct")
        if slanpct <= slampct:
            raise ValidationError("Moderate percent must be be less than normal percent")

# SLA charts
class ChartsRangeForm(RangeForm):
    dopgte = BooleanField(label="Include PGTE?", required=False)
    dosubpages = BooleanField(label="Include submtted?", required=False)
    dopubpages = BooleanField(label="Include published?", required=False)
    doslabands = BooleanField(label="Include SLA bands?", required=False)
    dosplines =  BooleanField(label="Smooth curves?", required=False)
    doxls =  BooleanField(label="Excel spreadsheet?", required=False)

    def clean(self):
        """
        make sure data are internally consistent

        """
        super(ChartsRangeForm, self).clean() # check dates

        dopgte = self.cleaned_data.get("dopgte")
        dosubpages = self.cleaned_data.get("dosubpages")
        dopubpages = self.cleaned_data.get("dopubpages")

        if not (dopgte or dosubpages or dopubpages):
            raise ValidationError("Must select at least one data series")

class MonthChartsRangeForm(MonthRangeForm):
    dopgte = BooleanField(label="Include PGTE?", required=False)
    dosubpages = BooleanField(label="Include submtted?", required=False)
    dopubpages = BooleanField(label="Include published?", required=False)
    dosplines =  BooleanField(label="Smooth curves?", required=False)
    dodocs = BooleanField(label="Docs rather than pages?", required=False)
    doxls =  BooleanField(label="Excel spreadsheet?", required=False)

    def clean(self):
        """
        make sure data are internally consistent

        """
        super(MonthChartsRangeForm, self).clean() # check dates

        dopgte = self.cleaned_data.get("dopgte")
        dosubpages = self.cleaned_data.get("dosubpages")
        dopubpages = self.cleaned_data.get("dopubpages")

        if not (dopgte or dosubpages or dopubpages):
            raise ValidationError("Must select at least one data series")

# history bar charts
class HistChartsRangeForm(RangeForm):
    doarea = BooleanField(label="Area chart?", required=False, initial=True)
    qlabel = BooleanField(label="Quarter labels?", required=False)
    doxls =  BooleanField(label="Excel spreadsheet?", required=False)

ERRATACHOICES = (('P', 'Type / Status pie'), ('Y', 'Yearly column'), ('A', 'WG source pie'),('X', 'Excel spreadsheet'))
class ErrataForm(Form):
    which = ChoiceField(label="Which report:", widget=Select, choices=ERRATACHOICES)
    doarea = BooleanField(label="Area rather than column?", required=False)

# RFCs by year
class YearChartForm(Form):
    minyear = ChoiceField(choices=MANYYEARS, label="Min year", initial=1968) # unlikely to change
    maxyear = ChoiceField(choices=MANYYEARS, label="Max year", initial=THISYEAR) # unlikely to change
    doarea = BooleanField(label="Area chart?", required=False)
    dopages = BooleanField(label="Page count?", required=False)
    dosub = BooleanField(label="Include submitted?", required=False)
    doxls =  BooleanField(label="Excel spreadsheet?", required=False)

    def clean(self):
        """
        make sure data are internally consistent

        """
        minyear = self.cleaned_data.get("minyear")
        maxyear = self.cleaned_data.get("maxyear")
        if not minyear or not maxyear:
            raise ValidationError("Must select both years")
        if minyear > maxyear:
            raise ValidationError("No time warps, please")
