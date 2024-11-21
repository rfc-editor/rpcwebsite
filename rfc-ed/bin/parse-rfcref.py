#!/usr/bin/python3
# $Id: parse-rfcref.py,v 2.4 2021/09/29 18:56:04 priyanka Exp $

"""
    Script              : parse-rfcref.py
    Author              : Ryan Cross <rcross@amsl.com>
    Date                : 09-17-2010
    Description         : This script parses rfc-ref.txt.  For each entry
                          a file is created refXXXX.txt which contains the
			  authors.  The script will be run nightly by cron.
			  This was requested by RFC Editor so they can
			  import citation files using emacs file import command.
    Modifications       : see CVS
"""

import os

SOURCE='/a/in-notes/rfc-ref.txt'
TARGET='/a/inc-work/refs/'

f = open(SOURCE,'r',encoding='latin-1')
lines = f.readlines()
f.close()
for line in lines:
    if line.startswith('RFC'):
        num,obs,ref = line.split('|')
        filename = os.path.join(TARGET,num.replace('RFC','ref').rstrip()+'.txt')
        f = open(filename,'wb')
        f.write(ref.encode('latin-1').lstrip())
        f.close()



