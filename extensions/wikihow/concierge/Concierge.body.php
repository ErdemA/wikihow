<?
class Concierge extends UnlistedSpecialPage {
	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage('Concierge');
	}

	function execute($par) {
		$controller = new WAPUIConciergeUser(new WAPConciergeConfig());
		$controller->execute($par);
	}
}
