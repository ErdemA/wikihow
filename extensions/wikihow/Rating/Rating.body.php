<?php

include_once(dirname(__FILE__) . '/RatingArticle.php');
include_once(dirname(__FILE__) . '/RatingSample.php');

/**
 * page that handles the reason for a rating
 */
class RatingReason extends UnlistedSpecialPage {

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'RatingReason' );
	}

	function execute($par) {
		global $wgRequest, $wgOut, $wgUser;

		$ratrItem = $wgRequest->getVal("item_id");
		$ratrUser = $wgUser->getID();
		$ratrUserText = $wgUser->getName();
		$ratrReason = $wgRequest->getVal('reason');
		$ratrType = $wgRequest->getVal('type');
		$target = intval($target);
		$wgOut->disable();

		wfLoadExtensionMessages('RateItem'); 
		$ratingTool = new RatingSample();
        echo $ratingTool->addRatingReason($ratrItem, $ratrUser, $ratrUserText, $ratrReason, $ratrType);
	}
}
/**
 * The actual special page that displays the list of low accuracy / low
 * rating articles
 */
class AccuracyPatrol extends SpecialPage {

	function __construct() {
		SpecialPage::SpecialPage( 'AccuracyPatrol' );
	}

	function execute($par) {

		if($par == NULL)
			$ratingTool = RateItem::getRatingTool('article');
		else
			$ratingTool = RateItem::getRatingTool($par);


		$ratingTool->showAccuracyPatrol();
	}

	/**
	 *
	 * This function is used for de-indexing purposes. All articles that show up on the
	 * page Special:AccuracyPatrol are de-indexed. This is only used for
	 *
	 */
	static function isInaccurate($articleId, &$dbr) {
		$row = $dbr->selectField('rating_low', 'rl_page', array('rl_page' => $articleId), __METHOD__);

		return $row !== false;
	}

}

/**
 * AJAX call class to actually rate an item.
 * Currently we can rate: articles and samples
 */
class RateItem extends UnlistedSpecialPage {

	function __construct() {
		global $wgHooks;
		UnlistedSpecialPage::UnlistedSpecialPage( 'RateItem' );
		$wgHooks['ArticleDelete'][] = array("RateItem::clearRatingsOnDelete");
	}

	/**
	 *
	 * This function can only get called when an article gets deleted
	 *
	 **/
	function clearRatingsOnDelete ($article, $user, $reason) {
		$ratingTool = new RatingArticle();
		$ratingTool->clearRatings($article->getID(), $user, "Deleting page");
		return true;
	}

	function execute($par) {
		global $wgRequest, $wgOut, $wgUser;

		$ratType = $wgRequest->getVal("type", 'article');

		$ratingTool = RateItem::getRatingTool($ratType);

		$ratId = $wgRequest->getVal("page_id");
		$ratUser = $wgUser->getID();
		$ratUserext = $wgUser->getName();
		$ratRating = $wgRequest->getVal('rating');
		$wgOut->disable();

		// disable ratings more than 5, less than 1
		if ($ratRating > 5 || $ratRating < 0) return;

		wfLoadExtensionMessages('RateItem');

		echo $ratingTool->addRating($ratId, $ratUser, $ratUserext, $ratRating);

	}

	function showForm($type) {
	   $ratingTool = self::getRatingTool($type);

	   return $ratingTool->getRatingForm();
	}

	function showMobileForm($type) {
		$ratingTool = self::getRatingTool($type);

		return $ratingTool->getMobileRatingForm();
	}

	static function getRatingTool($type) {
		switch(strtolower($type)) {
			case 'article':
				return new RatingArticle();
			case 'sample':
				return new RatingSample();
		}
	}

}

/**
 * Special page to clear the ratings of an article. Accessed via the list
 * of low ratings pages.
 */
class Clearratings extends SpecialPage {

	function __construct() {
		SpecialPage::SpecialPage( 'Clearratings' );
	}

	function addClearForm($target, $type, $err) {
		global $wgOut;
		$blankme = Title::makeTitle(NS_SPECIAL, "Clearratings");

		$tmpl = new EasyTemplate( dirname(__FILE__) );
		$tmpl->set_vars(array('actionUrl' => $blankme->getFullURL(), 'target' => htmlspecialchars($target), 'type' => $type, 'err' => $err));

		$wgOut->addHTML($tmpl->execute('selectForm.tmpl.php'));
	}

	function execute($par) {
		global $wgOut, $wgUser, $wgRequest, $wgLang;
		$err = "";
		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );
		$restore = $wgRequest->getVal('restore', null);
		$sk = $wgUser->getSkin();

		$wgOut->setHTMLTitle('Clear Ratings - Accuracy Patrol');
		$type = $wgRequest->getVal('type');

		$ratingTool = RateItem::getRatingTool($type);

		$t = $ratingTool->makeTitle($target);
		if ($t == '') {
			$wgOut->addHTML(wfMsg('clearratings_notitle'));
			$this->addClearForm($target, $type, $err);
			return;
		}
		$me =  SpecialPage::getTitleFor( 'Clearratings', $target );
		if ($wgUser->getID() == 0) {
			return;
		}

		if ($wgRequest->wasPosted()) {
			// clearing ratings
			$clearId = $wgRequest->getVal('clearId', null);

			if ($clearId != null) {
				$ratingTool->clearRatings($clearId, $wgUser);
				$wgOut->addHTML(wfMsg('clearratings_clear_finished') . "<br/><br/>");
			}
		}


		if ($restore != null && $wgRequest->getVal('reason', null) == null) {
			//ask why the user wants to resotre
			$tmpl = new EasyTemplate( dirname(__FILE__) );
			$tmpl->set_vars(array('postUrl' => $me->getFullURL(), 'params' => $_GET,));
			$wgOut->addHTML($tmpl->execute('restore.tmpl.php'));
			return;
		} else if ($restore != null) {
			$user = $wgRequest->getVal('user');
			$page = $wgRequest->getVal('page');
			$reason = $wgRequest->getVal('reason');
			$u = User::newFromId($user);
			$up = $u->getUserPage();
			$hi = $wgRequest->getVal('hi');
			$low = $wgRequest->getVal('low');

			$count = $ratingTool->getUnrestoredCount($page);

			$ratingTool->restore($page, $user, $hi, $low);

			$wgOut->addHTML("<br/><br/>" . wfMsg('clearratings_clear_restored', $sk->makeLinkObj($up, $u->getName()), $when) . "<br/><br/>");

			// add the log entry
			$ratingTool->logRestore($page, $low, $hi, $reason, $count);
		}


		if ($target != null && $type != null) {
			$id = $ratingTool->getId($t);
			if ($id === 0) {
				$err = wfMsg('clearratings_no_such_title', $target);
			} else if ($type == "article" && $t->getNamespace() != NS_MAIN) {
				$err = wfMsg('clearratings_only_main', $target);
			} else {
				// clearing info
				$ratingTool->showClearingInfo($t, $id, $me, $target);
				$ap = Title::makeTitle(NS_SPECIAL, "AccuracyPatrol");
				$wgOut->addHTML($sk->makeLinkObj($ap, "Return to accuracy patrol"));
			}
		}

		$this->addClearForm($target, $type, $err);
	}

}

/**
 * List the ratings of some set of pages
 */
class ListRatings extends SpecialPage {

	function __construct() {
		SpecialPage::SpecialPage( 'ListRatings' );
	}

	function execute($par) {

		if($par == NULL)
			$ratingTool = RateItem::getRatingTool('article');
		else
			$ratingTool = RateItem::getRatingTool($par);

		$ratingTool->showListRatings();

	}

}
