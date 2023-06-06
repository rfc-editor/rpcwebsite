# KLUDGE KLUDGE KLUDGE
# django's test harness doesn't create tables for unmanaged models
# when testing, so we do it here

# this is brute force, manually create each table using fixed SQL
# commands
# these tables are loaded via fixtuures
# see the mkfixtures script to see where the fixture files come from

# if some future version adds primary key fields to all the tables
# and makes the models managed, you can delete this whole module and
# use the default test runner

from django.test.runner import DiscoverRunner
from django.conf import settings
from django.db import connection

class UnmanagedDiscoverRunner(DiscoverRunner):

    def setup_databases(self, *args, **kwargs):
        """
        create the unmanaged tables with raw SQL
        """

        super(UnmanagedDiscoverRunner, self).setup_databases(*args, **kwargs)

        with connection.cursor() as c:
         # create Index table
            c.execute("""
CREATE TABLE `index` (
  `internal_key` int(11) NOT NULL AUTO_INCREMENT,
  `DRAFT` varchar(200) DEFAULT NULL,
  `DATE_RECEIVED` date DEFAULT NULL,
  `TIME-OUT-DATE` date DEFAULT NULL,
  `EXPEDITE_NEED_DATE` varchar(10) DEFAULT NULL,
  `IESG_APPROVED` varchar(50) DEFAULT NULL,
  `TYPE` enum('BCP','FYI','RFC','STD','IEN') DEFAULT NULL,
  `DOC-ID` varchar(10) DEFAULT NULL,
  `TITLE` text,
  `AUTHORS` varchar(300) DEFAULT NULL,
  `FORMAT` varchar(100) DEFAULT NULL,
  `CHAR-COUNT` varchar(50) DEFAULT NULL,
  `PAGE-COUNT` varchar(50) DEFAULT NULL,
  `PUB-STATUS` enum('PROPOSED STANDARD','INFORMATIONAL','EXPERIMENTAL','UNKNOWN','HISTORIC','STANDARD','DRAFT STANDARD','BEST CURRENT PRACTICE','STD','NOT ISSUED','INTERNET STANDARD') DEFAULT NULL,
  `STATUS` enum('PROPOSED STANDARD','INFORMATIONAL','EXPERIMENTAL','UNKNOWN','HISTORIC','STANDARD','DRAFT STANDARD','BEST CURRENT PRACTICE','STD','NOT ISSUED','INTERNET STANDARD') DEFAULT NULL,
  `EMAIL` text,
  `SOURCE` varchar(100) DEFAULT NULL,
  `DOC_SHEPHERD` varchar(100) DEFAULT NULL,
  `IESG_CONTACT` varchar(100) DEFAULT NULL,
  `ABSTRACT` text,
  `PUB-DATE` date DEFAULT NULL,
  `NROFFED` varchar(50) DEFAULT NULL,
  `KEYWORDS` text,
  `ORGANIZATION` text,
  `QUERIES` varchar(50) DEFAULT NULL,
  `LAST-QUERY` varchar(50) DEFAULT NULL,
  `RESPONSES` varchar(100) DEFAULT NULL,
  `LAST-RESPONSE` varchar(100) DEFAULT NULL,
  `NOTES` text,
  `OBSOLETES` varchar(250) DEFAULT NULL,
  `OBSOLETED-BY` varchar(250) DEFAULT NULL,
  `UPDATES` varchar(250) DEFAULT NULL,
  `UPDATED-BY` varchar(250) DEFAULT NULL,
  `SEE-ALSO` varchar(100) DEFAULT NULL,
  `SEE-ALSO-TITLE` text,
  `REF` varchar(600) DEFAULT NULL,
  `ref_flag` tinyint(1) NOT NULL,
  `iana_flag` tinyint(1) NOT NULL,
  `state_id` int(11) NOT NULL,
  `generation_number` int(11) NOT NULL,
  `consensus_bit` enum('yes','no','N/A') DEFAULT NULL,
  `xml_file` tinyint(1) NOT NULL,
  `DOI` varchar(50) DEFAULT NULL,
  `sub_page_count` smallint(5) unsigned DEFAULT NULL,
  PRIMARY KEY (`internal_key`),
  KEY `doc-id` (`DOC-ID`),
  KEY `source` (`SOURCE`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1
             """)

            c.execute("""
CREATE TABLE `errata` (
  `errata_id` int(11) NOT NULL AUTO_INCREMENT,
  `rs_code` char(3) NOT NULL DEFAULT 'PHP',
  `doc-id` varchar(10) NOT NULL,
  `status_id` int(11) NOT NULL DEFAULT '2',
  `type_id` int(11) NOT NULL DEFAULT '2',
  `conv_format_check` enum('yes','no') DEFAULT 'no',
  `section` text,
  `orig_text` text,
  `correct_text` text,
  `submitter_name` varchar(80) NOT NULL,
  `submitter_email` varchar(120) DEFAULT NULL,
  `notes` text,
  `submit_date` date NOT NULL,
  `posted_date` date DEFAULT NULL,
  `verifier_id` int(11) DEFAULT '99',
  `verifier_name` varchar(80) DEFAULT NULL,
  `verifier_email` varchar(120) DEFAULT NULL,
  `insert_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_date` datetime DEFAULT NULL,
  PRIMARY KEY (`errata_id`),
  KEY `doc-id` (`doc-id`),
  KEY `status_id` (`status_id`),
  KEY `type_id` (`type_id`),
  KEY `rs_code` (`rs_code`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='rs_code is tag to aid correcting converted CGI records'
            """)

            c.execute("""
CREATE TABLE `errata_status_codes` (
  `errata_status_id` int(11) NOT NULL AUTO_INCREMENT,
  `errata_status_code` varchar(40) NOT NULL,
  `errata_status_text` varchar(120) DEFAULT NULL,
  PRIMARY KEY (`errata_status_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1
             """)

            c.execute("""
CREATE TABLE `errata_type_codes` (
  `errata_type_id` int(11) NOT NULL AUTO_INCREMENT,
  `errata_type_code` varchar(10) NOT NULL,
  `errata_type_helptext` text,
  PRIMARY KEY (`errata_type_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=latin1
             """)

            c.execute("""
CREATE TABLE `states` (
  `state_id` int(11) NOT NULL AUTO_INCREMENT,
  `state_name` varchar(100) NOT NULL,
  PRIMARY KEY (`state_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1
            """)

            c.execute("""
CREATE TABLE `state_history` (
  `internal_dockey` int(11) NOT NULL,
  `state_id` int(11) NOT NULL,
  `in_date` date NOT NULL,
  `version_number` int(2) DEFAULT NULL,
  `iana_flag` tinyint(1) NOT NULL,
  `ref_flag` tinyint(1) NOT NULL,
  `generation_number` int(11) NOT NULL,
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1
             """)

            c.execute("""
CREATE TABLE `area` (
  `area_id` int(11) NOT NULL AUTO_INCREMENT,
  `area_name` varchar(50) NOT NULL,
  `area_acronym` varchar(10) DEFAULT NULL,
  `area_status` enum('open','closed') NOT NULL DEFAULT 'open',
  `AREA_DIRECTOR_NAME` varchar(200) NOT NULL DEFAULT '',
  `AREA_DIRECTOR_EMAIL` varchar(200) NOT NULL DEFAULT '',
  `AREA_WEB_PAGE` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`area_id`),
  UNIQUE KEY `area_name` (`area_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1
            """)

            c.execute("""
CREATE TABLE `area_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fk_area` int(11) NOT NULL,
  `fk_index` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1
             """)

            c.execute("""
CREATE TABLE `working_group` (
  `wg_id` int(11) NOT NULL AUTO_INCREMENT,
  `area_name` varchar(50) NOT NULL,
  `wg_acronym` varchar(10) DEFAULT NULL,
  `wg_name` varchar(100) NOT NULL,
  `ssp_id` int(11) NOT NULL DEFAULT '1',
  `wg_chair_name` varchar(200) DEFAULT NULL,
  `wg_chair_email` varchar(200) DEFAULT NULL,
  `wg_email` varchar(80) DEFAULT NULL,
  `wg_status` enum('open','close') NOT NULL DEFAULT 'open',
  `other_areas` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`wg_id`),
  UNIQUE KEY `wg_area_name_k` (`wg_name`,`area_name`),
  KEY `ssp_id_k` (`ssp_id`),
  KEY `area_name_k` (`area_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1
            """)

        return ()

    def teardown_databases(self, old_config, **kwargs):
        """
        undo what setup did, delete all the tables it created
        """
        with connection.cursor() as c:
            # whack the tablea
            c.execute("DROP TABLE `index`")

            # whack the database, dunno why I have to do this
            dbname = settings.DATABASES['default']['NAME']
            print "deleting test database",dbname
            c.execute("DROP DATABASE `{0}`".format(dbname))

        return super(UnmanagedDiscoverRunner, self).teardown_databases(old_config, **kwargs)


