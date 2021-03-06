<?php

require_once( 'commandLine.inc' );
$db = wfGetDB(DB_MASTER);
$sql = 'SELECT wh_user FROM wiki_shared.facebook_connect, wiki_shared.user WHERE wh_user = user_id AND user_email_authenticated IS NULL;';
$res = $db->query($sql);
$now = wfTimestampNow();
$count = 0;
$affected = 0;
foreach ($res as $row) {
	$update = 'update wiki_shared.user set user_email_authenticated=\''.$now.'\' where user_id = '.$row->wh_user.';';
	$db->query($update);
	$count++;
	if ($db->affectedRows() > 0) $affected++;
}

print "Rows changed: $affected. Rows examined: $count.\n";
