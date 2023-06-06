# model for legacy RPC database
# also has two new tables for rsestats

#from __future__ import unicode_literals

from django.db import models

class Approvals(models.Model):
    app_id = models.AutoField(primary_key=True)
    a48_id = models.ForeignKey('Auth48S', db_column='a48_id', on_delete=models.CASCADE)
    name = models.CharField(max_length=120)
    approved = models.CharField(max_length=3)
    approved_date = models.DateField(blank=True, null=True)
    create_date = models.DateTimeField()

    def __str__(self):
        return "{0}-{1}-{2}".format(self.app_id, self.a48_id, self.name)
        
    class Meta:
        managed = False
        db_table = 'approvals'


class Area(models.Model):
    area_id = models.AutoField(primary_key=True)
    area_name = models.CharField(unique=True, max_length=50)
    area_acronym = models.CharField(max_length=10, blank=True, null=True)
    area_status = models.CharField(max_length=6)
    area_director_name = models.CharField(db_column='AREA_DIRECTOR_NAME', max_length=200)  # Field name made lowercase.
    area_director_email = models.CharField(db_column='AREA_DIRECTOR_EMAIL', max_length=200)  # Field name made lowercase.
    area_web_page = models.CharField(db_column='AREA_WEB_PAGE', max_length=200, blank=True, null=True)  # Field name made lowercase.

    def __str__(self):
        return self.area_name

    class Meta:
        managed = False
        db_table = 'area'


class AreaAssignments(models.Model):
    id = models.AutoField(primary_key=True)
    area = models.ForeignKey(Area,db_column='fk_area', null=True, on_delete=models.CASCADE)
    index = models.ForeignKey('Index',db_column='fk_index', null=True, on_delete=models.CASCADE)

    def __str__(self):
        return str(self.id)

    class Meta:
        managed = False
        db_table = 'area_assignments'


class Auth48S(models.Model):
    a48_id = models.AutoField(primary_key=True)
    doc_id = models.CharField(db_column='doc-id', unique=True, max_length=10)  # Field renamed to remove unsuitable characters.
    status = models.CharField(max_length=9)
    start_date = models.DateTimeField()
    completion_date = models.DateTimeField(blank=True, null=True)
    notes = models.TextField(blank=True, null=True)

    def __str__(self):
        return str(self.a48_id)

    class Meta:
        managed = False
        db_table = 'auth48s'


class Clusters(models.Model):
    entry_key = models.AutoField(primary_key=True)
    cluster_id = models.CharField(max_length=10)
    draft_base = models.CharField(unique=True, max_length=200)
    anchored = models.CharField(max_length=7, blank=True, null=True)

    def __str__(self):
        return self.cluster_id

    class Meta:
        managed = False
        db_table = 'clusters'


class Counters(models.Model):
    name = models.CharField(primary_key=True, max_length=8)
    value = models.IntegerField()

    def __str__(self):
        return "{0}-{0}".format(self.name, self.value)

    class Meta:
        managed = False
        db_table = 'counters'


class EditorAssignments(models.Model):
    assign_id = models.AutoField(primary_key=True)
    initials = models.CharField(max_length=2)
    doc_key = models.ForeignKey('Index', db_column='doc_key', on_delete=models.CASCADE)
    role_key = models.ForeignKey('EditorRoles', db_column='role_key', on_delete=models.CASCADE)
    create_date = models.DateTimeField()
    update_date = models.DateTimeField(blank=True, null=True)

    def __str__(self):
        return "{0}-{0}".format(self.assign_id, self.initials)

    class Meta:
        managed = False
        db_table = 'editor_assignments'


class EditorRoles(models.Model):
    role_key = models.AutoField(primary_key=True)
    role_code = models.CharField(max_length=2)
    role_name = models.CharField(max_length=80, blank=True, null=True)

    def __str__(self):
        return self.role_code

    class Meta:
        managed = False
        db_table = 'editor_roles'


class Editors(models.Model):
    ed_key = models.AutoField(primary_key=True)
    initials = models.CharField(unique=True, max_length=2)
    name = models.CharField(max_length=80, blank=True, null=True)
    assignable = models.CharField(max_length=3, blank=True, null=True)

    def __str__(self):
        return self.name

    class Meta:
        managed = False
        db_table = 'editors'


class Errata(models.Model):
    errata_id = models.AutoField(primary_key=True)
    rs_code = models.CharField(max_length=3)
    doc_id = models.ForeignKey('Index', db_column='doc-id',to_field='doc_id', max_length=10,
        db_constraint=False, db_index=False, on_delete=models.CASCADE)  # Field name fixed characters, soft foreign key
    status = models.ForeignKey('ErrataStatusCodes',db_column='status_id', on_delete=models.CASCADE) # rename and make foreign key
    type = models.ForeignKey('ErrataTypeCodes',db_column='type_id', on_delete=models.CASCADE) # rename and make foreign key
    conv_format_check = models.CharField(max_length=3, blank=True, null=True)
    section = models.TextField(blank=True, null=True)
    orig_text = models.TextField(blank=True, null=True)
    correct_text = models.TextField(blank=True, null=True)
    submitter_name = models.CharField(max_length=80)
    submitter_email = models.CharField(max_length=120, blank=True, null=True)
    notes = models.TextField(blank=True, null=True)
    submit_date = models.DateField()
    posted_date = models.DateField(blank=True, null=True)
    verifier_id = models.IntegerField(blank=True, null=True)
    verifier_name = models.CharField(max_length=80, blank=True, null=True)
    verifier_email = models.CharField(max_length=120, blank=True, null=True)
    insert_date = models.DateTimeField()
    update_date = models.DateTimeField(blank=True, null=True)

    def __str__(self):
        return "{0}-{1}".format(self.errata_id, self.doc_id)

    class Meta:
        managed = False
        db_table = 'errata'

class ErrataLog(models.Model):
    elog_id = models.AutoField(primary_key=True)
    errata_id = models.ForeignKey(Errata, db_column='errata_id',on_delete=models.CASCADE)
    verifier_id = models.ForeignKey('Verifiers', db_column='verifier_id', blank=True, null=True, on_delete=models.CASCADE)
    verifier_name = models.CharField(max_length=80, blank=True, null=True)
    status_id = models.ForeignKey('ErrataStatusCodes', db_column='status_id', on_delete=models.CASCADE)
    doc_id = models.CharField(db_column='doc-id', max_length=10)  # Field renamed to remove unsuitable characters.
    type_id = models.ForeignKey('ErrataTypeCodes', db_column='type_id', on_delete=models.CASCADE)
    section = models.TextField(blank=True, null=True)
    orig_text = models.TextField(blank=True, null=True)
    correct_text = models.TextField(blank=True, null=True)
    notes = models.TextField(blank=True, null=True)
    edit_date = models.DateTimeField()

    def __str__(self):
        return "{0}-{1}".format(self.elog_id, self.errata_id)

    class Meta:
        managed = False
        db_table = 'errata_log'

class ErrataStatusCodes(models.Model):
    errata_status_id = models.AutoField(primary_key=True)
    errata_status_code = models.CharField(max_length=40)
    errata_status_text = models.CharField(max_length=120, blank=True, null=True)

    def __str__(self):
        return self.errata_status_code

    class Meta:
        managed = False
        db_table = 'errata_status_codes'


class ErrataTypeCodes(models.Model):
    errata_type_id = models.AutoField(primary_key=True)
    errata_type_code = models.CharField(max_length=10)
    errata_type_helptext = models.TextField(blank=True, null=True)

    def __str__(self):
        return self.errata_type_code

    class Meta:
        managed = False
        db_table = 'errata_type_codes'


class Index(models.Model):
    internal_key = models.AutoField(primary_key=True)
    draft = models.CharField(db_column='DRAFT', max_length=200, blank=True, null=True)  # Field name made lowercase.
    date_received = models.DateField(db_column='DATE_RECEIVED', blank=True, null=True)  # Field name made lowercase.
    time_out_date = models.DateField(db_column='TIME-OUT-DATE', blank=True, null=True)  # Field name made lowercase. Field renamed to remove unsuitable characters.
    expedite_need_date = models.CharField(db_column='EXPEDITE_NEED_DATE', max_length=10, blank=True, null=True)  # Field name made lowercase.
    iesg_approved = models.CharField(db_column='IESG_APPROVED', max_length=50, blank=True, null=True)  # Field name made lowercase.
    type = models.CharField(db_column='TYPE', max_length=3, blank=True, null=True)  # Field name made lowercase.
    doc_id = models.CharField(db_column='DOC-ID', max_length=10, unique=True, blank=True, null=True)  # Field reamed, unique makes foreign key happy even though not true
    title = models.TextField(db_column='TITLE', blank=True, null=True)  # Field name made lowercase.
    authors = models.CharField(db_column='AUTHORS', max_length=300, blank=True, null=True)  # Field name made lowercase.
    format = models.CharField(db_column='FORMAT', max_length=100, blank=True, null=True)  # Field name made lowercase.
    char_count = models.CharField(db_column='CHAR-COUNT', max_length=50, blank=True, null=True)  # Field name made lowercase. Field renamed to remove unsuitable characters.
    page_count = models.SmallIntegerField(db_column='page-count', null=True)  # Field renamed to remove unsuitable characters.
    pub_status = models.CharField(db_column='PUB-STATUS', max_length=21, blank=True, null=True)  # Field name made lowercase. Field renamed to remove unsuitable characters.
    status = models.CharField(db_column='STATUS', max_length=21, blank=True, null=True)  # Field name made lowercase.
    email = models.TextField(db_column='EMAIL', blank=True, null=True)  # Field name made lowercase.
    source = models.ForeignKey('WorkingGroup', db_column='SOURCE', to_field='wg_name', max_length=100, db_constraint=False,
        db_index=False, on_delete=models.CASCADE)  # Field name made lowercase, soft foreign key
    doc_shepherd = models.CharField(db_column='DOC_SHEPHERD', max_length=100, blank=True, null=True)  # Field name made lowercase.
    iesg_contact = models.CharField(db_column='IESG_CONTACT', max_length=100, blank=True, null=True)  # Field name made lowercase.
    abstract = models.TextField(db_column='ABSTRACT', blank=True, null=True)  # Field name made lowercase.
    pub_date = models.DateField(db_column='PUB-DATE', blank=True, null=True)  # Field name made lowercase. Field renamed to remove unsuitable characters.
    nroffed = models.CharField(db_column='NROFFED', max_length=50, blank=True, null=True)  # Field name made lowercase.
    keywords = models.TextField(db_column='KEYWORDS', blank=True, null=True)  # Field name made lowercase.
    organization = models.TextField(db_column='ORGANIZATION', blank=True, null=True)  # Field name made lowercase.
    queries = models.CharField(db_column='QUERIES', max_length=50, blank=True, null=True)  # Field name made lowercase.
    last_query = models.CharField(db_column='LAST-QUERY', max_length=50, blank=True, null=True)  # Field name made lowercase. Field renamed to remove unsuitable characters.
    responses = models.CharField(db_column='RESPONSES', max_length=100, blank=True, null=True)  # Field name made lowercase.
    last_response = models.CharField(db_column='LAST-RESPONSE', max_length=100, blank=True, null=True)  # Field name made lowercase. Field renamed to remove unsuitable characters.
    notes = models.TextField(db_column='NOTES', blank=True, null=True)  # Field name made lowercase.
    obsoletes = models.CharField(db_column='OBSOLETES', max_length=250, blank=True, null=True)  # Field name made lowercase.
    obsoleted_by = models.CharField(db_column='OBSOLETED-BY', max_length=250, blank=True, null=True)  # Field name made lowercase. Field renamed to remove unsuitable characters.
    updates = models.CharField(db_column='UPDATES', max_length=250, blank=True, null=True)  # Field name made lowercase.
    updated_by = models.CharField(db_column='UPDATED-BY', max_length=250, blank=True, null=True)  # Field name made lowercase. Field renamed to remove unsuitable characters.
    see_also = models.CharField(db_column='SEE-ALSO', max_length=100, blank=True, null=True)  # Field name made lowercase. Field renamed to remove unsuitable characters.
    see_also_title = models.TextField(db_column='SEE-ALSO-TITLE', blank=True, null=True)  # Field name made lowercase. Field renamed to remove unsuitable characters.
    ref = models.CharField(db_column='REF', max_length=300, blank=True, null=True)  # Field name made lowercase.
    ref_flag = models.IntegerField()
    iana_flag = models.IntegerField()
    state_id = models.ForeignKey('States', db_column='state_id', db_constraint=False, on_delete=models.CASCADE)
    generation_number = models.IntegerField()
    consensus_bit = models.CharField(max_length=3, blank=True, null=True)
    xml_file = models.IntegerField()
    doi = models.CharField(db_column='DOI', max_length=50, blank=True, null=True)  # Field name made lowercase.
    sub_page_count = models.PositiveSmallIntegerField(null=True) # new for stats

    def __str__(self):
        return self.doc_id if (self.doc_id and len(self.doc_id) >= 7) else str(self.internal_key)

    class Meta:
        managed = False
        db_table = 'index'


class ReportSources(models.Model):
    rs_code = models.CharField(primary_key=True, max_length=3)
    rs_description = models.CharField(max_length=180, blank=True, null=True)

    def __str__(self):
        return self.rs_code

    class Meta:
        managed = False
        db_table = 'report_sources'


class SourceList(models.Model):
    rfc_number = models.CharField(max_length=7)
    wg_acronym = models.CharField(max_length=20, blank=True, null=True)
    draft_string = models.CharField(max_length=100, blank=True, null=True)
    wg_name = models.CharField(max_length=300, blank=True, null=True)

    def __str__(self):
        return self.rfc_number

    class Meta:
        managed = False
        db_table = 'source_list'


class StateHistory(models.Model):
    id = models.AutoField(primary_key=True) # new for django
    internal_dockey = models.ForeignKey(Index, db_column='internal_dockey', on_delete=models.CASCADE)
    state_id = models.ForeignKey('States', db_column='state_id', db_constraint=False, on_delete=models.CASCADE)
    in_date = models.DateField()
    version_number = models.IntegerField(blank=True, null=True)
    iana_flag = models.IntegerField()
    ref_flag = models.IntegerField()
    generation_number = models.IntegerField()

    def __str__(self):
        return "{0}-{1}-{2}".format(self.internal_dockey, self.state_id, self.in_date)

    class Meta:
        managed = False
        db_table = 'state_history'


class States(models.Model):
    state_id = models.AutoField(primary_key=True)
    state_name = models.CharField(max_length=100)

    def __str__(self):
        return self.state_name

    class Meta:
        managed = False
        db_table = 'states'


class Statistics(models.Model):
    internal_key = models.ForeignKey(Index, db_column='internal_key', on_delete=models.CASCADE)
    weeks_in_state = models.DecimalField(max_digits=10, decimal_places=1)
    report_date = models.DateField()
    week_of_year = models.IntegerField()

    class Meta:
        managed = False
        db_table = 'statistics'


class StatusChanges(models.Model):
    dockey = models.IntegerField(primary_key=True)
    date_of_change = models.DateField(blank=True, null=True)
    url_of_change = models.TextField(db_column='URL_of_change', blank=True, null=True)  # Field name made lowercase.

    def __str__(self):
        return str(self.dockey)

    class Meta:
        managed = False
        db_table = 'status_changes'


class StreamSpecificParties(models.Model):
    ssp_id = models.AutoField(primary_key=True)
    stream_name = models.CharField(max_length=126, blank=True, null=True)
    ssp_name = models.CharField(max_length=126, blank=True, null=True)
    ssp_email = models.CharField(max_length=126)
    ssp_webpage = models.CharField(max_length=200, blank=True, null=True)

    def __str__(self):
        return str(self.ssp_id)

    class Meta:
        managed = False
        db_table = 'stream_specific_parties'


class Verifiers(models.Model):
    verifier_id = models.AutoField(primary_key=True)
    ssp_id = models.ForeignKey(StreamSpecificParties, db_column='ssp_id', on_delete=models.CASCADE)
    login_name = models.CharField(max_length=80)
    password = models.CharField(max_length=32)

    def __str__(self):
        return self.login_name

    class Meta:
        managed = False
        db_table = 'verifiers'


class WorkingGroup(models.Model):
    wg_id = models.AutoField(primary_key=True)
    area_name = models.ForeignKey(Area, db_column='area_name', to_field='area_name',
        max_length=50, db_constraint=False, db_index=False, on_delete=models.CASCADE)
    wg_acronym = models.CharField(max_length=10, blank=True, null=True)
    wg_name = models.CharField(max_length=100, unique=True)
    ssp_id = models.IntegerField()
    wg_chair_name = models.CharField(max_length=200, blank=True, null=True)
    wg_chair_email = models.CharField(max_length=200, blank=True, null=True)
    wg_email = models.CharField(max_length=80, blank=True, null=True)
    wg_status = models.CharField(max_length=5)
    other_areas = models.CharField(max_length=100, blank=True, null=True)

    def __str__(self):
        return self.wg_name

    class Meta:
        managed = False
        db_table = 'working_group'
        unique_together = (('wg_name', 'area_name'),)

## models for database views
class CopyEd(models.Model):
    initials = models.CharField(max_length=2)
    doc_key = models.ForeignKey(Index, db_column='doc_key', on_delete=models.CASCADE)

    class Meta:
        managed = False
        db_table = 'copy_ed'

class PrimaryEd(models.Model):
    initials = models.CharField(max_length=2)
    doc_key = models.ForeignKey(Index, db_column='doc_key', on_delete=models.CASCADE)

    class Meta:
        managed = False
        db_table = 'primary_ed'

class Rfced(models.Model):
    initials = models.CharField(max_length=2)
    doc_key = models.ForeignKey(Index, db_column='doc_key', on_delete=models.CASCADE)

    class Meta:
        managed = False
        db_table = 'rfced'

# new table for rsestats
# tracks state changes with old and new states for each document
# updated by manage monthly
class RfcStateSummary(models.Model):
    rfc = models.ForeignKey(Index, on_delete=models.CASCADE)    # link to internal_key in index table
    oldstate = models.ForeignKey(States,
        related_name='oldstate',
        db_constraint=False,
        db_index=False,
        null=True, on_delete=models.CASCADE) # previous state or zero
    old_iana_flag = models.BooleanField(null=True)
    old_ref_flag = models.BooleanField(null=True)
    old_version_number = models.IntegerField(null=True)

    state = models.ForeignKey(States, on_delete=models.CASCADE) # this state
    iana_flag = models.BooleanField(null=True)
    ref_flag = models.BooleanField(null=True)
    version_number = models.IntegerField(null=True)
    days = models.PositiveSmallIntegerField() # how long in that state

    class Meta:
        unique_together = ('rfc', 'oldstate', 'state', 'old_version_number','version_number',
            'old_iana_flag','old_ref_flag','iana_flag','ref_flag')
        index_together = ('oldstate', 'state')

    def __str__(self):
        return self.rfc.doc_id

# new table for rsestats
# snapshots for number of docs and pages in each state on a
# particular date
# updated each Monday by migrate weekly
# can also be made retroactively from history table
class StateByDate(models.Model):
    state_id = models.ForeignKey(States, db_column='state_id', on_delete=models.CASCADE)
    state_date = models.DateField()
    page_count = models.PositiveIntegerField(default=0)
    doc_count = models.PositiveIntegerField(default=0)

    class Meta:
        unique_together = ('state_id', 'state_date')
    def __str__(self):
        return "{0} {1}".format(self.state_date, self.state_id.state_name)
