#!/bin/sh
# $Id: xmlIndexDaily.sh,v 2.2 2016/04/04 19:20:38 priyanka Exp $
#  xmlIndexDaily.sh   -- Run daily by Boreas cron job.
#
#  Creates ~in-notes/rfc-index.xml from database by
#  running xmlIndex.pl after deleting old copy.
# 
###############################################################

myhost=`hostname -s`
srvhost='bosco'
# commented out for production move (rc)
#if [ $myhost != $srvhost ] ; then
#    echo "This script must run on the RFC Editor database server. The"
#    echo "script is configured to run on ${srvhost}. If this is incorrect, please"
#    echo "have the script updated."
#    exit 1
#fi

# Assign locations to match locations used in previous versions
bindir='/rfc-ed/bin'
#tmpdir=${bindir}
tmpdir='/rfc-ed/tmp/webdiv7'
xmldir="/in-notes"
xsldir="/rfc-ed/bin"
wwwdir="/htdocs"

# This function runs xsltproc on the xml file to generate HTML
function run_xsltproc {
    xsltproc -o ${wwwdir}/rfc-index.html ${xsldir}/rfc_index2html.xsl ${xmldir}/rfc-index.xml
    xsltproc -o ${wwwdir}/rfc-index2.html ${xsldir}/rfc_index2html_rev.xsl ${xmldir}/rfc-index.xml
}

#This function creates rfc-index-100a.html file (Contains first 100 RFC entries)
function create_partial_index {
${bindir}/rfc-index-split.php
}

#This function creates rfc-index-100d.html file (Contains last 100 RFC entries)
function create_partial_reverse_index {
${bindir}/rfc-index-reverse-split.php
}

#create new version from the database into temp file
${bindir}/xmlIndex.pl >  ${tmpdir}/temp.rfc-index.xml

#run a validation check on the file
xmllint='/usr/bin/xmllint'
if [ -x $xmllint ] ; then
    cd ${xmldir}
    $xmllint --noout --schema ${wwwdir}/rfc-index.xsd ${tmpdir}/temp.rfc-index.xml 2>/dev/null
    status=$?
    case $status in
        0 ) 
        # everything is A-OK! install in public location.
            #mv ${tmpdir}/temp.rfc-index.xml ${xmldir}/rfc-index.xml
            cp ${tmpdir}/temp.rfc-index.xml ${xmldir}/rfc-index.xml
            #chgrp webdiv7 ${xmldir}/rfc-index.xml
            chgrp rfc ${xmldir}/rfc-index.xml
            chmod 666 ${xmldir}/rfc-index.xml
            # create the new rfc-index html files
            run_xsltproc
            # create the new rfc-index-100a.html file
	    create_partial_index
            # create the new rfc-index-100d.html file
            create_partial_reverse_index
            ;;
        * )
            echo '***************'
            echo "Validation of new rfc-index.xml failed with error $status"
            echo "Re-running test to capture errors."
            echo 'Current rfc-index.xml was NOT replaced!'
            echo 'HTML files rfc-index.html and rfc-index2.html not changed!'
            echo '***************'
        # Do it again to get the output from the error
            $xmllint --noout --schema ${wwwdir}/rfc-index.xsd ${tmpdir}/temp.rfc-index.xml 2>&1
            ;;
    esac
else
    ## TODO: Is this really a good idea? Maybe better to require xmllint
    echo "Program $xmllint not availalbe. Validation test not performed."
    cp ${tmpdir}/temp.rfc-index.xml ${xmldir}/rfc-index.xml
    chgrp webdiv7 ${xmldir}/rfc-index.xml
    chmod g+w ${xmldir}/rfc-index.xml
    run_xsltproc
    create_partial_index
    create_partial_reverse_index
fi
