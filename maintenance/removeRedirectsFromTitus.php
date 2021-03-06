<?php
require_once('commandLine.inc');
require_once("$IP/extensions/wikihow/titus/Titus.class.php");

$dbw = new Database(TITUS_DB_HOST, WH_DATABASE_MAINTENANCE_USER, WH_DATABASE_MAINTENANCE_PASSWORD, TitusDB::getDBName());

$sql = "delete t.* from " . TitusDB::getDBName() . ".titus_intl t left join " . Misc::getLangDB($wgLanguageCode) . ".page p on ti_page_id = p.page_id WHERE p.page_is_redirect=1 AND ti_language_code='$wgLanguageCode'";
$dbw->query($sql);
print $dbw->affectedRows() . " rows deleted in $wgLanguageCode "; 
