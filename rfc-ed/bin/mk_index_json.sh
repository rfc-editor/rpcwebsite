#!/bin/sh
#----------------------------------------------------------------------------------#
#	mk_index_json.sh 
#	The script perform following two tasks
#       1. Calls php script get_index_doc.php and get the list of Published documents
#          from the database table.
#       2. Calls php script index_json_creator.php with the document list from step 1
#          and creates the .json file for every doc_id and stores the o/p at specified
#           location.
#       The script is run through cron.
#
#  10-07-2022  Priyanka Narkar  - Created the script 
#----------------------------------------------------------------------------------#


# Change to bin directory
cd /a/home/rfc-ed/bin

## Delete old files, if any.
##
rm -f tmp_index_list.txt


./get_index_doc.php > /a/home/rfc-ed/bin/tmp_index_list.txt

input="/a/home/rfc-ed/bin/tmp_index_list.txt"

while read line; do
newline=$( echo $line | sed 's/^rfc0*//')
newline="rfc"${newline}

#./index_json_creator.php ${line}  > /a/www2/tmp_json_files/${newline}.json	
./index_json_creator.php ${line}  > /a/home/rfc-ed/temp_json/${newline}.json	
done < "$input"
