<?php
require_once('commandLine.inc');
# Export list of alternative methods for a list of articles to CSV file

$filename = $argv[0];
$f = fopen($filename, 'r');
$contents = fread($f, filesize($filename));
fclose($f);
$pages = preg_split('@[\r\n]+@', $contents);
foreach($pages as $page) {
	$t = Title::newFromText($page);
	$gr = GoodRevision::newFromTitle($t);
	if($gr) {
		$dbr = wfGetDB(DB_SLAVE);
		$lr = $gr->latestGood();
		$r = Revision::loadFromId($dbr, $lr);
		if($r) {
			$text = Wikitext::getStepsSection($r->getText(), true);
			if(preg_match_all("@===([^=]+)===@", $text[0], $matches)) { 
				print $page;
				foreach($matches[1] as $m) {
					if(!preg_match("@\r\n@",$m)) {
						print ',' . $m;	
					}
				}
				print "\n";
			}
		}
	}
}
