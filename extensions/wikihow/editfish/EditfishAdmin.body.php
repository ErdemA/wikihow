<?
class EditfishAdmin extends UnlistedSpecialPage {
	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage('EditfishAdmin');
	}

	function execute($par) {
		$controller = new WAPUIEditfishAdmin(new WAPEditfishConfig());
		$controller->execute($par);
	}
}
