<?
/*
* 
*/
class AltMethodAdder extends UnlistedSpecialPage {
	
	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage('AltMethodAdder');
	}

	function execute($par) {
		global $wgRequest, $wgOut, $wgUser;
		$articleId = intval($wgRequest->getVal('aid'));
		$altMethod = $wgRequest->getVal('altMethod');
		$altSteps = $wgRequest->getVal('altSteps');
		if($articleId != 0 && $altMethod != "" && $altSteps != "") {
			$wgOut->setArticleBodyOnly(true);
			$result = $this->addMethod($articleId, $altMethod, $altSteps);
			print_r(json_encode($result));
			return;
		}
		
		$userGroups = $wgUser->getGroups();
		if ($wgUser->isBlocked() || !in_array('staff', $userGroups))
		{
			$wgOut->setRobotpolicy('noindex,nofollow');
			$wgOut->errorpage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}
		
		list( $limit, $offset ) = wfCheckLimits();
		$llr = new NewAltMethods();
    	$result =  $llr->doQuery( $offset, $limit );

		return $result;
		
	}
	
	private function addMethod($articleId, $altMethod, $altSteps) {
		global $wgParser, $wgUser;
		$title = Title::newFromID($articleId);
		$result = array();
		if($title) {
			$dbw = wfGetDB(DB_MASTER);
			$dbw->insert('altmethodadder', array('ama_page' => $articleId, 'ama_method' => $altMethod, 'ama_steps' => $altSteps, 'ama_user' => $wgUser->getID(), 'ama_timestamp' => wfTimestampNow()));
			
			$result['success'] = true;
			
			//Parse the wikiText that they gave us.
			//Need to add in a steps header so that mungeSteps
			//actually knows that it's a steps section
			$newMethod = $wgParser->parse("== Steps ==\n=== " . $altMethod . " ===\n" . $altSteps, $title, new ParserOptions())->getText();
			$result['html'] = WikihowArticleHTML::postProcess($newMethod, array('no-ads' => true));
		}
		else
			$result['success'] = false;
		
		return $result;
	}
	
	/***
	 * This will get display on article pages
	 */
	public static function getCTA(&$t) {
		if (self::isActivePage() && self::isValidTitle($t)) {
			
			$tmpl = new EasyTemplate( dirname(__FILE__) );
			$tmpl->set_vars(array('title' => $t->getText()));
		
			return $tmpl->execute('AltMethodAdder.tmpl.php');
		}
	}
	
	public static function isValidTitle(&$t) {
		return $t && $t->exists() && $t->getNamespace() == NS_MAIN && !$t->isProtected() && !self::isTeenTopic($t);
	}

	public static function isTeenTopic(&$t) {
			$dbr = wfGetDB(DB_SLAVE);
			return intVal($dbr->selectField('page', CAT_TEEN . ' & page_catinfo > 0', array('page_id' => $t->getArticleId()), __METHOD__));
	}
	
	public static function isActivePage() {
		//only show on 5% of the pages
		return mt_rand(1,20) <= 3;
	}
	
	function getSQL() {
		return "SELECT ama_timestamp as value, altmethodadder.* from altmethodadder";
	}
}

class NewAltMethods extends QueryPage {

	function NewAltMethodAdder(){
		global $wgHooks, $wgOut, $wgRequest, $wgUser, $wgMemc;
		list( $limit, $offset ) = wfCheckLimits();
		$wgOut->setPageTitle("New Alternate Methods");
	}

	function getName() {
		return "Alternate Methods";
	}

	function isExpensive() {
		# page_counter is not indexed
		return true;
	}
	function isSyndicated() { return false; }

	function getSQL() {
		return AltMethodAdder::getSql();
	}

	function formatResult( $skin, $result ) {
		global $wgLang, $wgContLang;
		
		$title = Title::newFromID( $result->ama_page );

        	if($title) {
                $html = "";
                if($result->ama_patrolled)
                    $html .= "<span style='color:#229917'>&#10004</span> &nbsp;&nbsp;";

                $html .= "<a href='" . $title->escapeFullURL() . "'>". $title->getText() . "</a><br />" . $result->ama_method . "<br />" . $result->ama_steps;

                return $html;

            }
	}

}
