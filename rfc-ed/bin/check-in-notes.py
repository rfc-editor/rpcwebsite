#!/usr/bin/python3
# $Id: check-in-notes.py,v 2.4 2021/09/22 23:05:13 priyanka Exp $

"""
    Script		: check-in-notes.py
    Author		: Ryan Cross <rcross@amsl.com>
    Date		: 03-25-2010
    Description		: script to report garbage files in in-notes directory.
                          in-notes-notify.sh will use this script in place of
                          cleanDir.pl (a generic garbage checker) because
                          Bob has requested only specific file names be allowed. 
    Modifications	: see CVS
    Date                : 06-17-2019 By Priyanka <priyanka@amsl.com>
                        : Added html files as a valid file type for in-notes directory
    Date                : 10-15-2019 By Priyanka <priyanka@amsl.com>
                        : Added json files as a valid file type for in-notes directory
"""

import re
import sys
import os

# check arguments
if len(sys.argv) != 2:
    print("Usage: %s [DIRECTORY]" % sys.argv[0])
    exit()

target_dir = sys.argv[1]
author_exceptions = ['lastpage.ietfsub.nroff','lastpage.indsub.nroff']

# check top directory
print('------------------- %s' % target_dir)
files = os.listdir(target_dir)
pattern_a = re.compile('rfc\d+.[txt|pdf|ps|html|json]')
pattern_b = re.compile('\S*index\S*')
for file in files:
    if os.path.isfile(os.path.join(target_dir,file)):
    	if not pattern_a.match(file) and not pattern_b.match(file):
                print(file)

# check ./authors directory
target_dir = os.path.join(target_dir,'authors')
print('------------------- %s' % target_dir)
files = os.listdir(target_dir)
pattern = re.compile('rfc\S*[.txt|.xml|diff.html]')
for file in files:
    if os.path.isfile(os.path.join(target_dir,file)):
        if file not in author_exceptions:
            if not pattern.match(file):
                print(file)

