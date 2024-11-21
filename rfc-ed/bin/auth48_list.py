#!/usr/bin/env python3
# $Id: auth48_list.py,v 2.8 2023/05/01 23:02:31 priyanka Exp $

"""
    Script              : auth48_list.py
    Author              : Ryan Cross <rcross@amsl.com>
    Date                : 09-23-2010
    Description         : This script runs a db query to retrieve a list
                          of docs in AUTH48 state and sends in an email.
			  Like other bin scripts the first argument is
			  the db info file.
    Modifications       : see CVS
                        05-01-2023 Added add a blank line between each row
                        and https:// to each URL - PN
"""
import MySQLdb
import os
import sys

# check arguments
if len(sys.argv) != 2:
    print("Usage: %s [db info file]" % sys.argv[0])
    exit()

# read db parameters
try: 
    f = open(sys.argv[1], 'r')
except IOError:
    print("Can't open file (%s)" % sys.argv[1])
    exit(1)

params = {}
for line in f.readlines():
    k,v = line.split(':')
    params[k] = v.strip()

f.close()

# run query
#query = """select i.`doc-id`, i.draft, ROUND((TO_DAYS(CURDATE())- (TO_DAYS(a.start_date)))/7, 1) as "WEEKS_IN_AUTH48", c.cluster_id as "Cluster", concat("www.rfc-editor.org/auth48/",LOWER(i.`doc-id`)) as "URL"
#from auth48s a,`index` i left join clusters c on i.draft like concat(c.draft_base, '-__')
#where i.state = "AUTH48" 
#and i.`doc-id` = a.`doc-id`
#order by WEEKS_IN_AUTH48 desc"""

query = """select i.`doc-id`, i.draft, ROUND((TO_DAYS(CURDATE())- (TO_DAYS(a.start_date)))/7, 1) as "WEEKS_IN_AUTH48", c.cluster_id as "Cluster", concat("https://www.rfc-editor.org/auth48/",LOWER(i.`doc-id`)) as "URL"
from auth48s a,`index` i left join clusters c on i.draft like concat(c.draft_base, '-__')
where i.`state_id` = "2"
and i.`doc-id` = a.`doc-id`
order by WEEKS_IN_AUTH48 desc"""

db = MySQLdb.connect(user=params['username'],db=params['user'],passwd=params['password'])
db.query(query)
r = db.store_result()
rows = r.fetch_row(0)

# format report
name_lengths = [len(x[1]) for x in rows]
max_name = max(name_lengths)
report = 'RFC     Draft               Weeks in AUTH48    Cluster URL\n'
for row in rows:
   report = report + '%s %s %s %s %s\n\n' % (row[0], row[1].ljust(max_name), row[2], row[3], row[4])
print(report)

report = report + '\n\n'

#query_done = """select i.`doc-id`, i.draft, ROUND((TO_DAYS(CURDATE())- (TO_DAYS(a.start_date)))/7, 1) as "WEEKS_IN_AUTH48_DONE", c.cluster_id as "Cluster", concat("www.rfc-editor.org/auth48/",LOWER(i.`doc-id`)) as "URL"
#from auth48s a,`index` i left join clusters c on i.draft like concat(c.draft_base, '-__')
#where i.`state_id` = "23"
#and i.`doc-id` = a.`doc-id`
#order by WEEKS_IN_AUTH48_DONE desc"""

query_done = """select i.`doc-id`, i.draft, ROUND((TO_DAYS(CURDATE())- (TO_DAYS(h.in_date)))/7, 1) as "WEEKS_IN_AUTH48_DONE", c.cluster_id as "Cluster", concat("https://www.rfc-editor.org/auth48/",LOWER(i.`doc-id`)) as "URL"
from auth48s a,`index` i left join clusters c on i.draft like concat(c.draft_base, '-__'), `state_history` h
where i.`state_id` = "23"
and i.`doc-id` = a.`doc-id`
and i.`internal_key` = h.`internal_dockey`
and i.`state_id` = h.`state_id`
order by WEEKS_IN_AUTH48_DONE desc"""

db.query(query_done)
r_done = db.store_result()
done_rows = r_done.fetch_row(0)

# format report
#name_lengths = [len(x_done[1]) for x_done in done_rows]
#max_name = max(name_lengths) + 7
report = report + 'RFC     Draft          Weeks in AUTH48-DONE    Cluster URL\n'

for done_row in done_rows:
   report = report + '%s %s %s %s %s\n\n' % (done_row[0], done_row[1].ljust(max_name) , done_row[2], done_row[3], done_row[4])
print(report)


# send email
SENDMAIL = '/usr/sbin/sendmail'
FROM='rfc-editor@rfc-editor.org'
TO=['iesg@ietf.org']
CC=['rfc-editor@rfc-editor.org']
SUBJECT='RFC AUTH48 Queue'
TEXT=report


message = """\
From: %s
To: %s
Cc: %s
Subject: %s

%s
""" % (FROM, ", ".join(TO), ", ".join(CC), SUBJECT, TEXT)

p = os.popen("%s -t -i" % SENDMAIL, "w")
p.write(message)
status = p.close()
if status:
    print("Sendmail exit status", status)

