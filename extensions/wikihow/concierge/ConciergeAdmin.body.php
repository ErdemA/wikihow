<?
class ConciergeAdmin extends UnlistedSpecialPage {
	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage('ConciergeAdmin');
	}

	function execute($par) {
		$controller = new WAPUIConciergeAdmin(new WAPConciergeConfig());
		$controller->execute($par);
	}
}
