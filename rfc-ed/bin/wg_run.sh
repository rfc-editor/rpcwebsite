#!/bin/sh
#-----------------------------------------------------------------------------------------#
#Script Nanme : wg_run.sh (Runs weekly through cron)
#Description  : The script calls php get_wg_dttracker.php to gather the working group data 
#               using Datatracker API to a file wg_data.txt.    
#               The script wg_update.php updates the database table working_group using 
#               the file wg_data.txt
#  01-22-2019   Priyanka Narkar  - Created the script during V3 implementation
#  11-02-2023   Priyanka Narkar  - Modified the log file name without the year
#----------------------------------------------------------------------------------#


#Goto bin directory
cd /a/home/rfc-ed/bin

#Get the Datatracker API data to a file
./get_wg_dttracker.php > /a/home/rfc-ed/bin/wg_data.txt
#Load the Database table using above file.
./wg_update.php >> /a/home/rfc-ed/logs/wg_data_log.txt


