"""
WSGI config for rsestats project.

It exposes the WSGI callable as a module-level variable named ``application``.

For more information on this file, see
https://docs.djangoproject.com/en/1.9/howto/deployment/wsgi/
"""
import os
import sys
path = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
if not path in sys.path:
    sys.path.insert(0, path)

from django.core.wsgi import get_wsgi_application

os.environ.setdefault("DJANGO_SETTINGS_MODULE", "rsestats.settings")

application = get_wsgi_application()
                                                
