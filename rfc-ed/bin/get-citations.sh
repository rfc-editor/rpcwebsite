#!/bin/sh
# $Id: get-citations.sh,v 2.7 2024/05/01 19:05:28 priyanka Exp $
###################################################################
#                                                                 #
# Script        : get-citations.sh                                #
# Desc          : This shell script is executed by cron           #
#                 to build local citation files for RFC Editor    #
#                 it first runs parse-rfcref.py to parse the      #
#                 rfc-ref.txt file, then runs rsync to            #
#                 retrieve files from xml.resource.org            #
#                                                                 #
#    9-17-2010 rc                                                 #
#                                                                 #
#May 2024      : Commented parse-bcpref.py and parse-stdref.py    #
###################################################################

cd ~rfc-ed/bin

# parse rfc-ref.txt
./parse-rfcref.py

#Commenting BCP and STD citation file creation
#parse bcp-ref.txt
#./parse-bcpref.py

#parse std-ref.txt
#./parse-stdref.py

## DISABLED AND TRANSFERRED TO NEW ROoT SCRIPT BY GLEN - DO NOT REENABLE THIS PER GLEN
# rsync bibxml directories from xml.resource.org
#rsync -av xml2rfc.ietf.org::xml2rfc.bibxml/bibxml2/ /a/inc-work/refs/bibxml2  >/a/home/rfc-ed/rsync-logs/bibxml2-log.txt
#rsync -av xml2rfc.ietf.org::xml2rfc.bibxml/bibxml3/ /a/inc-work/refs/bibxml3  >/a/home/rfc-ed/rsync-logs/bibxml3-log.txt
#rsync -av xml2rfc.ietf.org::xml2rfc.bibxml/bibxml4/ /a/inc-work/refs/bibxml4  >/a/home/rfc-ed/rsync-logs/bibxml4-log.txt
#rsync -av xml2rfc.ietf.org::xml2rfc.bibxml/bibxml5/ /a/inc-work/refs/bibxml5  >/a/home/rfc-ed/rsync-logs/bibxml5-log.txt
#rsync -av xml2rfc.ietf.org::xml2rfc.bibxml/bibxml6/ /a/inc-work/refs/bibxml6  >/a/home/rfc-ed/rsync-logs/bibxml6-log.txt
