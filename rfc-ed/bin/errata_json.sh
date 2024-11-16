#!/bin/sh
#----------------------------------------------------------------------------------#
#	errata_json 
#	The script calls php script errata_json_creator.php and is run through    
#       cron everyday.
#  01-22-2019   Priyanka Narkar  - Created the script during V3 implementation
#----------------------------------------------------------------------------------#


#Goto bin directory
cd /a/home/rfc-ed/bin



./errata_json_creator.php > /a/www/rfc/htdocs/errata.json

