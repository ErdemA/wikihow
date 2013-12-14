<?
/*
* 
*/
class WikitextDownloader extends UnlistedSpecialPage {

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage('WikitextDownloader');
	}

	function execute($par) {
		global $wgOut, $wgRequest;

		if (!self::isAuthorized()) {
			$wgOut->setRobotpolicy('noindex,nofollow');
			$wgOut->errorpage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$dbr = wfGetDB(DB_SLAVE);
		$r = Revision::loadFromPageId($dbr, $wgRequest->getVal('pageid'));
		if ($r) {
			$title = $r->getTitle()->getText();
			Misc::outputFile("$title.txt", $r->getText(), "application/force-download");
		}
		return;
	}

	public static function isAuthorized() {
		global $wgUser, $isDevServer;
		$user = $wgUser->getName();
		$userGroups = $wgUser->getGroups();
		if ($wgUser->isBlocked() || !in_array('translator', $userGroups)) {
			return false;
		} else {
			return true;
		}
	}

}
