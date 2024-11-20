#!/usr/bin/python3
# $Id: get-comments.py,v 2.2 2021/09/22 23:15:31 priyanka Exp $

"""
    Script		: get-comments.py
    Author		: Ryan Cross <rcross@amsl.com>
    Date		: 03-22-2010
    Description		: extracts comments from the main xml input FILE
                          Comments which include the string "[rfced]" are then
                          printed to standard out with a numeric heading, #).
    Modifications	: see CVS
"""

import sys
import xml.parsers.expat

def handle_comment(data):
    if data.find('[rfced]') >= 0:
        comments.append(data)

# check arguments
if len(sys.argv) != 2:
    print("Usage: %s [FILE]" % sys.argv[0])
    exit()

try: 
    fil = open(sys.argv[1],"rb")
except IOError:
    print("Can't open file (%s)" % fil)

comments = []

p = xml.parsers.expat.ParserCreate()
p.CommentHandler = handle_comment
p.ParseFile(fil)
fil.close()

for i,comment in enumerate(comments):
    print("%d) %s" % (i+1, comment))

