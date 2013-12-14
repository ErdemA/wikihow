<?
class BabelfishAdmin extends UnlistedSpecialPage {
	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage('BabelfishAdmin');
	}

	function execute($par) {
		$controller = new WAPUIBabelfishAdmin(new WAPBabelfishConfig());
		$controller->execute($par);
	}

}
