#!/bin/sh
# $Id: format_queues.sh,v 2.0 2010/03/15 22:50:45 rcross Exp $

## Script to make all versions of qeueue file
## This script must run on Bosco, where RFC Editor dBase lives.
/home/rfc-ed/bin/format_queue.pl html /a/www/rfc/htdocs/queue.html /home/rfc-ed/bin/database_information.txt 

/home/rfc-ed/bin/format_queue.pl xml /a/www/rfc/htdocs/queue.xml /home/rfc-ed/bin/database_information.txt

/home/rfc-ed/bin/format_queue2.pl html /a/www/rfc/htdocs/queue2.html /home/rfc-ed/bin/database_information.txt

/home/rfc-ed/bin/format_queue2.pl xml /a/www/rfc/htdocs/queue2.xml /home/rfc-ed/bin/database_information.txt

#echo "Generated queue.html, .xml, queue2.html, .xml"
