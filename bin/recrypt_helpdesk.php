#!/usr/bin/env php
<?php
/**
* Fix various account problems
* @author Joe Huss <detain@interserver.net>
* @package MyAdmin
* @category map_everything_to_my
* @copyright 2020
*/
use \MyAdmin\Orm\Accounts_Ext;

require_once __DIR__.'/../../include/functions.inc.php';

$db = get_module_db('helpdesk');
$db2 = clone $db;
$db->query("select * from swcustomfieldvalues where fieldvalue like '/./%'");
while ($db->next_record(MYSQL_ASSOC)) {
	$fieldvalue = $GLOBALS['tf']->decrypt_old($db->Record['fieldvalue']);
	if ($fieldvalue != $db->Record['fieldvalue']) {
		$query = "update swcustomfieldvalues set fieldvalue='".$db2->real_escape($GLOBALS['tf']->encrypt($fieldvalue))."' where customfieldvalueid={$db->Record['customfieldvalueid']}";
		echo "$query\n";
		$db2->query($query);
	}
}
