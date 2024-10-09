#!/bin/sh
# Script used to run the PHP scripts that generates the monthly RFC editor
# time report.The script will run on first day of every month. 
#
# Running from cron, any error or warning messages will cause email to be sent
# to the cron address configured for the account that runs this.

cd /a/www/staff/

# rfc_ed_time.php script creates the monthly rfced time using state history
# data.

./rfc_ed_time.php

#rfc_ed_time_index.php script creates the index file.

./rfc_ed_time_index.php

# end of script

