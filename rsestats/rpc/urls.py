# RPC url switcher

from django.conf.urls import url

from . import views

app_name = 'rpc'
urlpatterns = [
    url(r'^$', views.index, name='index'),
    url(r'^range$', views.daterange, name='range'),
    url(r'^pgte$', views.pgte, name='pgte'),
    url(r'^stats$', views.stats, name='stats'),
    url(r'^qtrcharts$', views.qtrcharts, name='qtrcharts'),
    url(r'^moncharts$', views.moncharts, name='moncharts'),
    url(r'^histch$', views.histch, name='histch'),
    url(r'^monthly$', views.monthly, name='monthly'),
    url(r'^yearly$', views.yearly, name='yearly'),
    url(r'^errata$', views.errata, name='errata'),
    url(r'^logout$', views.dologout, name='logout'),
    url(r'^(?P<rfcno>RFC[0-9][0-9][0-9][0-9])$', views.showrfc, name='showrfc'),
    url(r'^(?P<rfcno>rfc[0-9][0-9][0-9][0-9])$', views.showrfc, name='showrfc'),
    url(r'^(?P<rfcno>BCP[0-9][0-9][0-9][0-9])$', views.showrfc, name='showrfc'),
    url(r'^(?P<rfcno>bcp[0-9][0-9][0-9][0-9])$', views.showrfc, name='showrfc'),
    url(r'^(?P<rfcno>STD[0-9][0-9][0-9][0-9])$', views.showrfc, name='showrfc'),
    url(r'^(?P<rfcno>std[0-9][0-9][0-9][0-9])$', views.showrfc, name='showrfc'),
    url(r'^ID(?P<idno>[0-9][0-9][0-9][0-9])$', views.showrfc, name='showrfc'),
    url(r'^id(?P<idno>[0-9][0-9][0-9][0-9])$', views.showrfc, name='showrfc'),
    url(r'^dl(?P<chart>\w+)/(?P<hash>\w{6})(?P<args>\w+)$', views.dodeeplink, name='deeplink')
]