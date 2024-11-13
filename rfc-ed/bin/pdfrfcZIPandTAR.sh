#!/bin/sh
# $Id: pdfrfcZIPandTAR.sh,v 2.0 2010/03/15 22:50:45 rcross Exp $

#-----------------------------------------------------------------------------
# Command: pdfrfcZIPandTAR.sh
#
# Description:
#	This script will create a tar and zip file in ~in-notes containing
#	all PDF-formatted RFCs (those living in ~in-notes/pdfrfc).  It is
#	run weekly via rfc-ed's crontab.
#
# Revision History:
#	12-06-2002	Created by Nathalie Collins <collins@isi.edu>
#       01-04-2003      Updated to include this header/description section.
#-----------------------------------------------------------------------------

# make sure scratch directory is there - create otherwise
if test ! -d /tmp/tarAndZip
then
    /bin/mkdir /tmp/tarAndZip
fi

# change current working directory
cd /a/ftp/in-notes

# create zip file
/usr/bin/zip -r9 /tmp/tarAndZip/pdfrfc-all pdfrfc
/bin/mv /tmp/tarAndZip/pdfrfc-all.zip /a/ftp/in-notes/tar/

# create tarball
/bin/tar -vcf /tmp/tarAndZip/pdfrfc-all.tar pdfrfc
/usr/bin/gzip -v9 /tmp/tarAndZip/pdfrfc-all.tar
/bin/mv /tmp/tarAndZip/pdfrfc-all.tar.gz /a/ftp/in-notes/tar/

exit 0

