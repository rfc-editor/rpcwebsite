#!/usr/bin/python3
# $Id: get-comments2.py,v 2.4 2021/09/22 23:15:31 priyanka Exp $

"""
    Script		: get-comments2.py
    Author		: Ryan Cross <rcross@amsl.com>
    Date		: 04-05-2010
    Description		: extracts comments from the main xml input FILE
                          Comments which include the string "[rfced]" are then
                          printed to standard out with a numeric heading, #).
                          
                          NOTE: this is a cruder implementation of 
                          get-comments.py, required becuase target
                          files frequently contain invalid XML, namely
                          naked ampersands which cause the Python
                          XML parsers problems.
    Modifications	: see CVS
"""

import sys

# check arguments
if len(sys.argv) != 2:
    print ("Usage: %s [FILE]" % sys.argv[0])
    exit()

try: 
    fil = open(sys.argv[1],"r")
except IOError:
    print ("Can't open file (%s)" % sys.argv[1])
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
    if result.count('[rfced]'):
        comments.append(result) 

# produce output
if comments:
    print ('Authors,\n')
    print ('While reviewing this document during AUTH48, please resolve (as necessary) the following questions, which are also in the XML file.\n')

    for i,comment in enumerate(comments):
        print ("%d) %s" % (i+1, comment))
        print ()

    print ('Thank you.\n')
    print ('RFC Editor')
else:
    print ('No [rfced] comments found.')
