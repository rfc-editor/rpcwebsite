#!/bin/bash
# Copyright The IETF Trust 2020 All Rights Reserved 
# Script used to run the PHP scripts that generates the monthly state change
# summary based on the statistical data in the RFC Editor database.
#
# Running from cron, any error or warning messages will cause email to be sent
# to the cron address configured for the account that runs this.


#. input_names.txt 

#cd $report_path

cd /a/www/rfcscripts/reports

# Script state_change_summary.php generates the monthly state change summary at 
# specified location and sends email to appropriate people.


./state_change_summary.php
year=$(date '+%Y')

echo "To: rfced-ietf@ietf.org, rfc-editor@rfc-editor.org" > state-change-sum.txt
echo "From: rfc-editor@rfc-editor.org" >>  state-change-sum.txt
echo "Subject: State Changes by Month" >> state-change-sum.txt
echo "This report is available here:" >>  state-change-sum.txt
echo "https://www.rfc-editor.org/reports/state_change_$year.html" >> state-change-sum.txt

/usr/sbin/sendmail rfced-ietf@ietf.org rfc-editor@rfc-editor.org < ./state-change-sum.txt


echo "  Sent Monthly email of State Change Summary"
echo "  to RFC-Ed. Detailed listing in state-change-sum.txt"

# end of script
