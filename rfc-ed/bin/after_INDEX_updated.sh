#!/bin/sh
# $Id: after_INDEX_updated.sh,v 2.2 2021/10/05 23:58:25 priyanka Exp $
###################################################################
#                                                                 #
# Script        : scripts_to_run_after_INDEX_update.sh            #
# Desc          : This shell script is executed by RFC Editor     #
#                 to update all data files that are derived from  #
#                 INDEX.                                          #
#                                                                 #
#  Mods: 18 Sep 07: Braden: remove id-search call (not ~INDEX)    #
#                 and symlink call (replaced by dir links) and    #
#                 rfcindex.pl call (bogus)                        #
#    5 Oct 07: Braden: remove obs makeref (made ref file from     #
#                 INDEX; replaced by make_ref.pl invocation from  #
#                 bosco cron job, once a day.                     #
#    25 Oct 07: Braden: create-indexes.sh replaces new.xxx-index  #
#    16 Mar 08: Braden: add xmlIndexDaily.sh                      #
#    13 Aug 09: Ward: remove xmlIndexDaily.sh                     #
#    23 Apr 15: JRL: add DOI upload                               #
#    5  Oct 21:PRN :add LC_ALL=en_US.UTF-8 in front of script run #
#                                                                 #
###################################################################

# to find rfc-index.xml for DOIs
xmldir="/a/ftp/in-notes"

cd /home/rfc-ed/bin
echo ""
echo "RUN SCRIPTS TO UPDATE DERIVED FILES AFTER INDEX IS UPDATED"

echo ""
echo "Running new-search-db to generate search engine data."
./new-search-db

echo ""
echo "Running create-indexes.sh to create 5 ~in-notes/xxx-index.txt files"
echo "and 8 <htdocs>xxx-index.html files."
./create-indexes.sh

echo ""
echo "Running rfc2txt.py to create new rfc-index.txt file."
LC_ALL=en_US.UTF-8 ./rfc2txt.py /a/ftp/in-notes/rfc-index.xml /a/ftp/in-notes/rfc-index.txt

## Has to run on Bosco (where the mysql server runs)
##echo ""
##echo "Running xmlIndexDaily.sh to create rfc-index.xml and check it."
##xmlIndexDaily.sh

## Has to run on Bosco
##echo ""
##echo "Running twelveRecentRFCs.pl"
##twelveRecentRFCs.pl

echo ""
echo "Running create-categories.pl to create /categories/*html files."
./create-categories.pl

echo ""
echo "Running consistencyCheck.pl to check INDEX for consistency."
./consistencyCheck.pl

echo ""
echo "Running rfc2doi.py to upload new DOIs."
./rfc2doi.py --upload ${xmldir}/rfc-index.xml


echo ""
echo "DONE"
