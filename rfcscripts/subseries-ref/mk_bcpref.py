#!/usr/bin/python3
"""
    Script              : mk-bcpref.py
    Author              : Priyanka Narkar <priyanka@amsl.com>
    Date                : 05-01-2024
    Description         : This script parses bcp-combined.txt removes everything except the 
                          reference information.For each entry a file is created ref-bcpXXXX.txt 
                          which contains the authors, DOI information. The script will be run manually.
"""


import os
import re


SOURCE='bcp-combined.txt'
TMP_FILENAME = 'tmp-bcp-ref.txt'
FINAL_FILENAME = 'bcp-ref.txt'
TARGET='/refs/'


#String to search in input file
str_name='   [BCP'
author_str = 'Author\'s Address'

with open(SOURCE,'r',encoding='utf-8') as source_f,open(TMP_FILENAME, "w") as tmp_f:
   found = False
   for line in source_f :
       if str_name in line:
          found = True
       if found:
          tmp_f.write(line)

with open(TMP_FILENAME, 'r', encoding='utf-8') as tmp_f, open(FINAL_FILENAME, "w") as out_f:
   author_found = False
   for line in tmp_f :
       if  author_str in line:
           author_found = True
       if  not author_found:
           out_f.write(line)

#Delete the temp file
if os.path.exists(TMP_FILENAME):
   os.remove(TMP_FILENAME)
#   print("Temp File "+ TMP_FILENAME+" removed.")
else:
   print("Temp File "+ TMP_FILENAME+" not found.")

#Now parse the input BCP files which has only references

with open(FINAL_FILENAME,'r',encoding='utf-8') as f:
   text = f.read()
   formatted_text = text.replace("   [BCP"," =-[BCP")
   #print (formatted_text) 
     
   split_text = formatted_text.split(" =-")
   
   del split_text[0]
   #print (split_text)
  
   for i in split_text:
      name = i.split("\n\n")
      name_length = len(name)
      file_name = name[0].split("] ")
      
      #Get the file name
      final_file_name = "ref-"+ file_name[0].replace("[","").replace("]","").lower() +".txt"
     
      name[0] = name[0].replace("[BCP", "   [BCP")
      with open (TARGET + final_file_name,"w") as f:
           for i in range(name_length):
               if (i >= 1):
                  f.write("\n\n") 
                  f.write(name[i])
               else :
                  f.write(name[i])


