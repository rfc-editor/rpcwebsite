#!/bin/sh
# $Id: in-notes_notify.sh,v 2.2 2022/11/03 18:13:11 priyanka Exp $

#
# This script runs 'check-in-notes.py' on the ~in-notes directory to check for any
# garbage files.  It compiles a list and emails it to
# rfc-editor@rfc-editor.org.
#
# 03-25-10 rpc changed to run check-in-notes.py
# 11-03-22 Modified script to add From and To address

cd /home/rfc-ed/bin

echo "Running check-in-note.py..."
rm in-notes_notify.mail
echo "To: webmaster@rfc-editor.org" > in-notes_notify.mail
echo "From:  rfc-editor@rfc-editor.org" >>  in-notes_notify.mail
echo "Subject: Garbage in ~in-notes" >> in-notes_notify.mail
./check-in-notes.py /a/ftp/in-notes >> in-notes_notify.mail

echo "Emailing results..."
/usr/sbin/sendmail -frfc-editor@rfc-editor.org -t < ./in-notes_notify.mail

echo "Results sent successfully."
