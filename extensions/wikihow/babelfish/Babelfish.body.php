<?
class Babelfish extends UnlistedSpecialPage {
	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage('Babelfish');
	}

	function execute($par) {
		$controller = new WAPUIBabelfishUser(new WAPBabelfishConfig());
		$controller->execute($par);
	}
}
