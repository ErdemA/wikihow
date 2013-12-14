<?
class Editfish extends UnlistedSpecialPage {
	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage('Editfish');
	}

	function execute($par) {
		$controller = new WAPUIEditfishUser(new WAPEditfishConfig());
		$controller->execute($par);
	}
}
