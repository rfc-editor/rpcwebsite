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
# Script summary_stats.php creates the Weekly current queue summary and adds the
# corresponding statistics data to database table statistics.


./state_change_summary.php

# end of script
