<?php
require_once('commandLine.inc');
require_once("$IP/extensions/wikihow/titus/Titus.class.php");
$titus = new TitusDB(true);
$allStats = TitusConfig::getNightlyStats();
$allStats['Social'] = 1;
$allStats['Stu'] = 0;
$allStats['Photos'] = 0;
$titus->calcStatsForAllPages($allStats);
