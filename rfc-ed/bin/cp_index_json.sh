#!/bin/sh
#----------------------------------------------------------------------------------#
#	cp_index_json.sh 
#	The script perform following two tasks
#       1. Compares the JSON file at two locations.(temp and in-notes)
#       2. For mismatched files updates information in the log and copies the file
#          from temp to in-notes.
#       The script is run through cron.
#
#  10-07-2022  Priyanka Narkar  - Created the script 
#----------------------------------------------------------------------------------#



rm -f /a/home/rfc-ed/logs/index_json.log

#Change to temp file dir

cd /a/home/rfc-ed/temp_json/


count=0;
for j in *.json  
do


if ! cmp -s $j /a/in-notes/$j  
then  
#cp $j /a/www2/diffjson/$j 
cp $j /a/in-notes/$j 
printf 'Updated file "%s" \n' "$j" >> /a/home/rfc-ed/logs/index_json.log
count=$((count + 1)) 
fi  
done

printf 'Total Updated JSON files"%s" \n' "$count" >> /a/home/rfc-ed/logs/index_json.log

