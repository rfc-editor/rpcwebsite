#!/usr/bin/python3

"""
    Script		: get-FYIcomments.py
    Orig Author		: Ryan Cross <rcross@amsl.com>
    Date		: 2021-03-01
    Description		: extracts comments from the main xml input FILE
                          Comments which include the string "[rfced]" and 
"FYI" are then printed to standard out with a numeric heading, #).
                          
                          NOTE: this is a cruder implementation of 
                          get-comments.py, required because target
                          files frequently contain invalid XML, namely
                          naked ampersands which cause the Python
                          XML parsers problems.
"""

import sys

# check arguments
if len(sys.argv) != 2:
    print("Usage: %s [FILE]" % sys.argv[0])
    exit()

try: 
    fil = open(sys.argv[1])
except IOError:
    print("Can't open file (%s)" % sys.argv[1])
    exit(1)

s=''
results=[]
comments=[]
in_comment=False

# extract comments
for line in fil.readlines():
    if line.count('<!--') > 0:
        in_comment=True
    if in_comment:
        s = s + line
    if line.count('-->') > 0:
       results.append(s)
       s = ''
       in_comment=False
    
fil.close()
# extract [rfced] comments
for result in results:
    if result.count('[rfced]  FYI'):
        comments.append(result) 
    if result.count('[rfced] FYI'):
        comments.append(result) 

# produce output
if comments:
    print('Here are the [rfced] comments marked as "FYI" in the XML file.\n')

    for i,comment in enumerate(comments):
        print("%d) %s" % (i+1, comment))
        print()

    print('Thank you.\n')
    print('RFC Editor')
else:
    print('No [rfced] PQ comments found.')
