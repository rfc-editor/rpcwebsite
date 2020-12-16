#!/bin/bash
# Copyright The IETF Trust 2020 All Rights Reserved 
# Script used to run the PHP scripts that generates the weekly/monthly email for current queue
# summary and stores the statistical data in the RFC Editor database.
# Email type is decided based on the parameter passed.w or m
#
# Running from cron, any error or warning messages will cause email to be sent
# to the cron address configured for the account that runs this.

#. input_names.txt

#cd $report_path

cd /a/www/rfcscripts/reports

report_type=$1

# Script summary_stats.php creates the Weekly/Monthly current queue summary and adds the
# corresponding statistics data to database table statistics.

./summary_stats.php $report_type


# end of script
