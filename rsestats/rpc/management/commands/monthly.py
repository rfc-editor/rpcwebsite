# command line stuff

from django.core.management.base import BaseCommand
from rpc.rpcstats import rfchistoryupdate

class Command(BaseCommand):
    help = 'Monthly update of document history summary'

    def handle(self, *args, **options):
        """
        do monthly updates to history summary

        """
        v = options['verbosity']        # 0 - 3, default 1

        if v > 0:
            print("Update history summary")
        showp = True if v > 1 else False
        (lsdate, lmdate, result) = rfchistoryupdate(showprogress=showp)
        if v > 0:
            print("Updated through {0}, added {1} documents".format(lmdate, len(result)))

