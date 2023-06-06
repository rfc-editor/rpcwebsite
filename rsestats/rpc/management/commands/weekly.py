# command line stuff

from django.core.management.base import BaseCommand
from rpc.datestats import snapstate, makestatebydate
from datetime import date

class Command(BaseCommand):
    help = 'Update weekly page and doc counts by state, or retroactively compile them'

    def add_arguments(self, parser):
        parser.add_argument('--since', type=str, help="Date to start retroactive snapshots YYYYMMDD")
        
    def handle(self, *args, **options):
        """
        do weekly update to statebydate
        """

        v = options['verbosity']        # 0 - 3, default 1

        if options['since']:
            os = options['since']
            try:
                since = date(year=int(os[:4]), month=int(os[4:6]), day=int(os[6:]))
            except:
                print("not a valid YYYYMMDD date",os)
                exit(1)

            print("catch up since",since)
            makestatebydate(since, verbose=v)
            exit()

        if v > 0:
            print("Update state snapshots")
        states = snapstate(v)
        if v > 0:
            print("Added entries for today, added {0} entries".format(len(states)))


