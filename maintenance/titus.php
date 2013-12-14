<?
require_once('commandLine.inc');
require_once("$IP/extensions/wikihow/titus/Titus.class.php");

$statsToCalc = TitusConfig::getBasicStats();
$statsToCalc['Title'] = 0;
$statsToCalc['Accuracy'] = 1;
$statsToCalc['Templates'] = 1;
$titus = new TitusDB(true);
/*
$titus->calcStatsForAllPages($statsToCalc);
$ids = array(1132186,1126217,722887,510006);
$titus->calcStatsForPageIds($statsToCalc, $ids);
*/
$dailyEditStats = TitusConfig::getDailyEditStats();
$titus->calcLatestEdits($dailyEditStats);
