<?php

include 'include/functions.inc.php';
$db = get_module_db('helpdesk');
$staffIdMap = [
    'swalertrules',
    'swcalls',
    'swcannedcategories',
    'swcannedresponses',
    'swchatchilds',
    'swchathits',
    'swchatobjects',
    'swchatskilllinks',
    'swescalationpaths',
    'swescalationrules',
    'swimportlogs',
    'swkbcategories',
    'swmacrocategories',
    'swmacroreplies',
    'swmessagequeue',
    'swmessages',
    'swnewsitems',
    'swnotificationpool',
    'swnotificationrules',
    'swonsitesessions',
    'swparserbans',
    'swreportcategories',
    'swreporthistory',
    'swreports',
    'swreportschedules',
    'swreportusagelogs',
    'swsearchstores',
    'swsignatures',
    'swstaffactivitylog',
    'swstaffloginlog',
    'swstaffprofileimages',
    'swstaffproperties',
    'swtaglinks',
    'swtags',
    'swtemplatehistory',
    'swticketdrafts',
    'swticketfilters',
    'swticketfollowups',
    'swticketlabels',
    'swticketlocks',
    'swticketmergelog',
    'swticketnotes',
    'swticketpostlocks',
    'swticketposts',
    'swticketpostsurvey',
    'swticketrecurrences',
    'swtickets',
    'swtickettimetracks',
    'swticketviews',
    'swticketwatchers',
    'swtroubleshootercategories',
    'swtroubleshootersteps',
    'swusernotes',
    'swvisitorbans',
    'swvisitornotes',
    'swvisitorpulls',
    'swvisitorpulls2',
    'swwidgets'
];
$staffEmailMap = [
    'swchathits' => ['email'],
    'swchatobjects' => ['useremail'],
    'swcomments' => ['email'],
    'swkbarticles' => ['email'],
    'swmessages' => ['email'],
    'swnewsitems' => ['email'],
    'swnewssubscribers' => ['email'],
    'swparserbans' => ['email'],
    'swparserlogs' => ['fromemail', 'toemail'],
    'swparserloophits' => ['emailaddress'],
    'swstaff' => ['email'],
    'swticketposts' => ['email','emailto'],
    'swtickets' => ['email', 'oldeditemailaddress'],
    'swuseremails' => ['email'],
    'swuserloginlog' => ['useremail']
];
$staffMap = [
    117 => [24, 51],
    106 => [49],
    97 => [84],
    89 => [10],
    127 => [86],
    125 => [81],
    129 => [67],
    110 => [38],
    126 => [54],
    128 => [132],
    116 => [103],
    101 => [60],
    131 => [5, 105],
    102 => [59],
    109 => [78],
    120 => [77],
    122 => [44],
    93 => [20],
    115 => [70],
    124 => [85],
    95 => [4],
    104 => [58],
    119 => [88],
    107 => [82]
];

foreach ($staffMap as $newId => $oldArray) {
    $db->query("select * from swstaff where staffid={$newId}");
    if ($db->num_rows() == 0) {
        exit;
    }
    $db->next_record(MYSQL_ASSOC);
    $newStaff = $db->Record;
    foreach ($oldArray as $oldId) {
        $db->query("select * from swstaff where staffid={$oldId}");
        if ($db->num_rows() == 0) {
            continue;
        }
        $db->next_record(MYSQL_ASSOC);
        $oldStaff = $db->Record;
        echo "Mapping {$oldId} to {$newId}\n";
        // update all tables setting the old staffid to the new staffid
        foreach ($staffIdMap as $table) {
            if ($table == 'swescalationpaths') {
                $field = 'ownerstaffid';
            } elseif (in_array($table, ['swreporthistory', 'swreports', 'swtickettimetracks'])) {
                $field = 'creatorstaffid';
            } else {
                $field = 'staffid';
            }
            echo "table {$table} field {$field}, ";
            $db->query("update {$table} set {$field}={$newId} where {$field}={$oldId}");
        }
        // update all tables setting the old email to the new email
        foreach ($staffEmailMap as $table => $fields) {
            foreach ($fields as $field) {
                echo "Updating table {$table} field {$field}, ";
                $db->query("update {$table} set {$field}='{$newStaff['email']}' where {$field}='{$oldStaff['email']}'");
            }
        }
        // delete old staff id
        echo "Deleting Staff ID\n";
        $db->query("delete from swstaff where staffid={$oldId}");
    }
    echo "Finished {$newId}\n";
}
