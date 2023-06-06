"""
    rsestats URL Configuration
    single rpc app plus login/logout and admin
"""

from django.conf.urls import url, include
from django.contrib import admin
from rpc.views import index

urlpatterns = [
    url(r'^admin/', admin.site.urls),
    url('^', include('django.contrib.auth.urls')), # various login and logout URLs
    url(r'^rpc/', include('rpc.urls')), # actual stuff in rpc app
    url(r'^rpc$', index),                # default to index page
    url(r'^$', index)                   # start in rpc app
]
