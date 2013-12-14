<?php

class TipsPatrol extends SpecialPage {

	const TIP_EXPIRED = 1800; // 30 minutes
	const TIP_ACTION_DELETE = 1;
	const TIP_ACTION_KEEP = 2;
	const TIP_ACTION_SKIP = 3;
	const TIP_ACTION_QG = 4;

	const TPC_DIFFICULTY_EASY = 0;
	const TPCOACH_ENABLED = "tpcoach_enabled";

	var $skipTool;

	function __construct() {
		global $wgHooks;
		SpecialPage::SpecialPage("TipsPatrol");
		$wgHooks['getToolStatus'][] = array('Misc::defineAsTool');
	}

	function execute($par) {
		global $wgOut, $wgRequest, $wgUser, $wgParser;

		wfLoadExtensionMessages("TipsPatrol");

		if ($wgUser->isBlocked()) {
			$wgOut->blockedPage();
			return;
		}

		if ($wgUser->isAnon() || self::isBlockedFromTipsPatrol($wgUser)) {
			$wgOut->setRobotpolicy( 'noindex,nofollow' );
			$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		$this->skipTool = new ToolSkip("tiptool", "tipsandwarnings", "tw_checkout", "tw_checkout_user", "tw_id");

		if ($wgRequest->wasPosted()) {
			$wgOut->disable();
			$result = array();

			$tipId = $wgRequest->getVal('tipId');

			if ($wgRequest->getVal('coachTip')) {
				$this->coachResult($tipId, &$result);
			} elseif ($tipId != null && $wgRequest->getVal('skipTip') ) {
				$this->logTip($tipId, self::TIP_ACTION_SKIP);
				$this->skipTool->skipItem($tipId);
				$this->skipTool->unUseItem($tipId);
			} elseif ($wgRequest->getVal('deleteTip')) {
				$articleId = $wgRequest->getVal('articleId');
				$tip = $wgRequest->getVal('tip');
				$this->deleteTip($tipId, $articleId, $tip);
			} elseif ($wgRequest->getVal('keepTip')) {
				//used to send to keepTip, but now we have an extra step: QG
				$articleId = $wgRequest->getVal('articleId');
				$tip = $wgRequest->getVal('tip');
				$this->logTip($tipId, self::TIP_ACTION_QG, '', $tip);
				$this->addToQG($tipId, $articleId, $tip, $result);
			}

			$this->getNextTip(&$result);
			echo json_encode($result);
			return;
		}

		$wgOut->setHTMLTitle(wfMsg('tipspatrol'));
		$wgOut->setPageTitle(wfMsg('tipspatrol'));
		$wgOut->addHTML(HtmlSnips::makeUrlTags('css', array('tipspatrol.css'), 'extensions/wikihow/tipsandwarnings', false));
		$wgOut->addScript(HtmlSnips::makeUrlTags('js', array('tipspatrol.js'), 'extensions/wikihow/tipsandwarnings', false));
		$wgOut->addScript(HtmlSnips::makeUrlTags('js', array('mousetrap.min.js,jquery.cookie.js'), 'extensions/wikihow/common', false));


		EasyTemplate::set_path(dirname(__FILE__));
		$vars = array();
		$vars['tip_skip_title'] = wfMsg('tip_skip_title');
		$vars['tip_keep_title'] = wfMsg('tip_keep_title');
		$vars['tip_delete_title'] = wfMsg('tip_delete_title');
		$wgOut->addHTML(EasyTemplate::html('TipsPatrol.tmpl.php', $vars));

		$bubbleText = "Only publish this tip if you can make it helpful, clear, and grammatically correct. Most tips should get deleted.";

		InterfaceElements::addBubbleTipToElement('tip_tip', 'tptrl', $bubbleText);
		$this->displayLeaderboards();
	}

/*
* tables for tips patrol coaching data
*
* tipspatrol_test
* CREATE TABLE `tipspatrol_test` (
* `tpt_id` int(8) unsigned NOT NULL AUTO_INCREMENT,
* `tpt_tip` text collate utf8_unicode_ci,
* `tpt_fail_message` text collate utf8_unicode_ci,
* `tpt_success_message` text collate utf8_unicode_ci,
* `tpt_difficulty` int(1) unsigned NOT NULL DEFAULT 0,
* `tpt_page_id` int(8) unsigned NOT NULL,
* `tpt_user_id` int(8) unsigned NOT NULL,
* `tpt_answer` int(1) unsigned NOT NULL,
* PRIMARY KEY  (`tpt_id`)
* ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
*
* tipspatrol_completed_test
* CREATE TABLE `tipspatrol_completed_test` (
* `tpc_user_id` int(8) unsigned NOT NULL,
* `tpc_test_id` int(8) unsigned NOT NULL,
* `tpc_score` int(1) NOT NULL,
* `tpc_timestamp` varchar(14) collate utf8_unicode_ci NOT NULL default '',
* KEY (`tpc_user_id`),
* KEY `tpc_timestamp` (`tpc_timestamp`)
* ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
*
* tipspatrol_views
* CREATE TABLE `tipspatrol_views` (
* `tpv_user_id` int(8) unsigned NOT NULL,
* `tpv_count` int(8) unsigned NOT NULL,
* `tpv_user_blocked` BOOL NOT NULL DEFAULT 0,
* PRIMARY KEY (`tpv_user_id`)
* ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
*
*/
	private function isBlockedFromTipsPatrol($user) {
		$dbr = wfGetDB(DB_SLAVE);
		$blocked = $dbr->selectField('tipspatrol_views', 'tpv_user_blocked', 'tpv_user_id = ' . intval($user->getID())) ?: false;
		return $blocked;
	}

	private function showCoachTip($content) {
		global $wgUser;
		if (!self::isTPCoachEnabled()) {
			return false;
		}

		$userGroups = $wgUser->getGroups();
		if (in_array('staff', $userGroups) || in_array('admin', $userGroups) || in_array('newarticlepatrol', $userGroups)) {
			return false;
		}

		$userId = $wgUser->getID();
		$dbr = wfGetDB(DB_SLAVE);
		$patrolledCount = $dbr->selectField('tipspatrol_views', 'tpv_count', 'tpv_user_id = ' . intval($userId)) ?: 0;

		// do not show a coach tip for your first one
		if ($patrolledCount < 1) {
			return false;
		}

		$coachedCount = $dbr->selectField('tipspatrol_completed_test', 'count(*)', 'tpc_user_id = ' . intval($userId));

		$firstSectionSize = 10;
		$secondSectionSize = 100;
		// default, show a tip 5% of the time
		$p = 95;
		if ($patrolledCount <= $firstSectionSize) {
			// 3 in first 10
			$target = 3;
			if ($coachedCount < $target) {
				$target = $target - $coachedCount;
				$p = 100 - 100 * $target / ($firstSectionSize - $patrolledCount);
			}
		} else if ($patrolledCount <= $secondSectionSize) {
			// show a tip every 15 tips or so
			$p = 85;
		}
		$r = rand(1, 100);
		if($r > $p) {
			return true;
		}
		return false;
	}

	function disableTPCoach() {
		if (!self::isTPCoachEnabled()) {
			return;
		}
		ConfigStorage::dbStoreConfig(self::TPCOACH_ENABLED, 0);
	}

	function enableTPCoach() {
		if (self::isTPCoachEnabled()) {
			return;
		}
		ConfigStorage::dbStoreConfig(self::TPCOACH_ENABLED, 1);
	}

	function isTPCoachEnabled() {
		return ConfigStorage::dbGetConfig(self::TPCOACH_ENABLED) > 0;
	}

	private function getCoachTip($content) {
		global $wgUser, $wgOut;

		$content['error'] = true;

		// get a coach tip that the users hasn't seen before
		$userId = $wgUser->getID();
		$where = array("tpt_id != 1 AND tpt_id NOT IN (SELECT tpc_test_id from tipspatrol_completed_test where tpc_user_id = $userId)");

		$dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->select('tipspatrol_test', array('*'), $where, __METHOD__);

		$numRows = $res->numRows();
		if ($numRows < 1) {
			return false;
		}

		$index = rand(0, $numRows - 1);
		$res->seek($index);
		$row = $res->current();

		$title = Title::newFromID($row->tpt_page_id);
		$revision = Revision::newFromTitle($title);
		$parserOutput = $wgOut->parse($revision->getText());

		$content['article'] = WikihowArticleHTML::postProcess($parserOutput, array('no-ads'));
		$content['tip'] = $row->tpt_tip;
		$content['tipId'] = $row->tpt_id;

		$content['articleId'] = $row->tpt_page_id;
		$content['articleTitle'] = $title->getText();
		$content['articleUrl'] = $title->getPartialUrl();
		$content['tipCount'] = self::getCount();
		$content['coaching'] = true;
		$content['error'] = false;
		return true;
	}

	function setUserBlocked($userId, $block, $result) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->update('tipspatrol_views',
					array("tpv_user_blocked" => $block),
					array("tpv_user_id" => $userId),
					__METHOD__,
					array('IGNORE'));
		$result['success'] = True;
	}

	function resetUserViews($userId, $result) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->update('tipspatrol_views',
					array("tpv_count" => 0),
					array("tpv_user_id"=>$userId),
					__METHOD__,
					array('IGNORE'));
		$dbw->delete('tipspatrol_completed_test', array("tpc_user_id"=>$userId), __METHOD__);
		$result['success'] = True;
	}

	private function saveTestResult($testId, $userId, $score) {
		$dbw = wfGetDB(DB_MASTER);
		$data = array("tpc_test_id"=> $testId,
					"tpc_user_id"=>$userId,
					"tpc_score"=>$score,
					"tpc_timestamp"=>wfTimestampNow());

		$dbw->insert('tipspatrol_completed_test', $data, __METHOD__, array('IGNORE'));
	}

	private function coachResult($tipId, $content) {
		global $wgUser, $wgRequest;

		$dbr = wfGetDB(DB_SLAVE);
		$where = array("tpt_id" => $tipId);

		$dbr = wfGetDB(DB_SLAVE);
		$row = $dbr->selectRow('tipspatrol_test', '*', $where, __METHOD__);
		$answer = $row->tpt_answer;
		$score = -1;
		if ($wgRequest->getVal('skipTip') ) {
			// nothing for now
		} elseif ($wgRequest->getVal('deleteTip')) {
			if ($answer == self::TIP_ACTION_KEEP) {
				$score = 0;
			}
			if ($answer == self::TIP_ACTION_DELETE) {
				$score = 1;
			}
		} elseif ($wgRequest->getVal('keepTip')) {
			if ($answer == self::TIP_ACTION_KEEP) {
				$score = 1;
			}
			if ($answer == self::TIP_ACTION_DELETE) {
				$score = 0;
			}
		}
		$content['coachResult'] = array("answer" => $answer,
										"score"=>$score,
										"fail_message"=>$row->tpt_fail_message,
										"success_message"=>$row->tpt_success_message,
										"difficulty"=>$row->tpt_difficulty,
										"userName"=>$wgUser->getName());
		$userId = $wgUser->getID();
		self::saveTestResult($tipId, $userId, $score);

		if ($score == 0 && $answer == self::TIP_ACTION_DELETE) {
			// send a talk message to the user telling them they failed
			$from_user = User::newFromName('Patrol-Coach');
			$comment = "Oops! It looks like you accidentally added a less helpful tip while working on Tips Patrol just now. 
						That's okay though; you're still learning, and it can be tricky at first!\r\n
						If you haven't already, read our article on [[Use the Tips Patrol Tool on wikiHow|How to Use the Tips Patrol Tool on wikiHow]] and 
						give it another try! If you have any questions about adding tips, don't hesitate to reach out to the [[wikiHow_talk:Help-Team|Help Team]]. 
						And remember, if you're not sure what to do, just press the \"skip\" 
						button and you'll do fine :)\r\n
						The Patrol Coach";
			Misc::adminPostTalkMessage($wgUser,$from_user,$comment);
		}
	}

	function deleteTest($testId) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->delete('tipspatrol_test', array('tpt_id' => $testId));
	}

	function addTest($tip, $page, $failMessage, $successMessage, $answer, $difficulty, $result=array()) {
		global $wgUser;

		$t = null;
		if (!is_numeric($page)) {
			$t = Title::newFromText($page);
		} else {
			$t = Title::newFromID(intval($page));
		}

		if (!$t || !$t->isKnown()) {
			return;
		}

		$pageId = $t->getArticleID();

		if (!$tip) {
			return;
		}

		$answerId = null;
		switch (strtolower($answer)) {
			case "delete":
				$answerId = self::TIP_ACTION_DELETE;
				break;
			case "publish":
			case "keep":
				$answerId = self::TIP_ACTION_KEEP;
				break;
			default:
				break;
		}

		if (!$answerId) {
			return;
		}

		$data = array("tpt_tip"=> $tip,
					"tpt_page_id"=>$pageId,
					"tpt_user_id"=>$wgUser->getID(),
					"tpt_answer"=>$answerId);

		if ($failMessage) {
			$data["tpt_fail_message"] = $failMessage;
		}
		if ($successMessage) {
			$data["tpt_success_message"] = $successMessage;
		}
		if ($difficulty) {
			$data["tpt_difficulty"] = $difficulty;
		}

		$dbw = wfGetDB(DB_MASTER);
		$dbw->insert('tipspatrol_test', $data, __METHOD__, array('IGNORE'));
		$result['success'] = True;
	}

	private function getNextTip($content) {
		$showCoachTip = $this->showCoachTip(&$content);

		$coachTip = false;
		if ($showCoachTip) {
			$coachTip = $this->getCoachTip(&$content);
		}

		if (!$coachTip) {
			$this->getDBTip(&$content);
		}
	}

	private function getDBTip($content) {
		global $wgUser, $wgOut;

		$dbw = wfGetDB(DB_MASTER);
		$expired = wfTimestamp(TS_MW, time() - TipsPatrol::TIP_EXPIRED);
		$i = 0;
		$content['error'] = true;
		$goodRevision = false;
		do {
			$skippedIds = $this->skipTool->getSkipped();
			$where = array();
			$where[] = "tw_checkout < '$expired'";
			$where[] = "NOT EXISTS (SELECT rc_id from recentchanges where rc_cur_id = tw_page and rc_patrolled = 0 LIMIT 1)";
			if($skippedIds) {
				$where[] = "tw_id NOT IN ('" . implode("','", $skippedIds) . "')";
			}
			$row = $dbw->selectRow('tipsandwarnings', array('*'), $where, __METHOD__, array("LIMIT" => 1));
			//$content['sql' . $i] = $dbw->lastQuery();
			//$content['row'] = $row;

			if($row !== false) {
				$title = Title::newFromID($row->tw_page);
				$isRedirect = false;
				if ($title) {
					$dbr = wfGetDB(DB_SLAVE);
					$isRedirect = intval($dbr->selectField('page', 'page_is_redirect',
						array('page_id' => $row->tw_page), __METHOD__, array("LIMIT" => 1)));
				}
				if($title && !$isRedirect) {
						$this->skipTool->useItem($row->tw_id);
						$revision = Revision::newFromTitle($title);
						$popts = $wgOut->parserOptions();
						$popts->setTidy(true);
						$parserOutput = $wgOut->parse($revision->getText(), $title, $popts);
						$content['article'] = WikihowArticleHTML::processArticleHTML($parserOutput, array('no-ads', 'ns' => NS_MAIN));
						$content['tip'] = $row->tw_tip;
						$content['tipId'] = $row->tw_id;
						$content['articleId'] = $row->tw_page;
						$content['articleTitle'] = $title->getText();
						$content['articleUrl'] = $title->getPartialUrl();
						$content['error'] = false;
				} else {
					//article must no longer exist or be a redirect, so delete the tips associated with that article
					$dbw = wfGetDB(DB_MASTER);
					$dbw->delete('tipsandwarnings', array('tw_page' => $row->tw_page));
				}
			}
			$i++;
		// Check up to 5 titles.
		// If no good title then return an error message
		} while ($i <= 5 && !$title && $row !== false);

		$content['i'] = $i;
		$content['tipCount'] = self::getCount();
		return $content;

	}

	private function getCount() {
		$dbr = wfGetDB(DB_SLAVE);
		return $dbr->selectField('tipsandwarnings', 'count(*) as count');
	}

	public function deleteTip($tipId = null, $articleId, $tip, $bAccepted = false, $fromQG = false) {
		global $wgUser;
		if($tipId != null) {
			$action = ($bAccepted) ? self::TIP_ACTION_KEEP : self::TIP_ACTION_DELETE;
			self::logTip($tipId, $action);

			if ($fromQG) {
				//tip actually deleted when sent to QG
				//all that's left is to remove the "to QG" log entry
				$dbw = wfGetDB(DB_MASTER);
				$dbw->delete('tipsandwarnings_log', array('tw_id' => $tipId, 'tw_action' => self::TIP_ACTION_QG), __METHOD__);
			}
			else {
				//remove it from Tips Patrol queue (QG already does this)
				$dbw = wfGetDB(DB_MASTER);
				$dbw->delete('tipsandwarnings', array('tw_id' => $tipId), __METHOD__);
			
				$title = Title::newFromID($articleId);
				if($title) {
					$logPage = new LogPage('newtips', false);
					$logData = array();
					$logMsg = wfMsg('newtips-rejected-logentry', $title->getFullText(), $tip);
					$logS = $logPage->addEntry("Rejected", $title, $logMsg);
				}
			}
		}
	}

	/*
	* see if this tip is already in the tips section
	*
	*
	* $tipId - text of tip being submitted
	* $articleId - article tip is being added to
	* $newTip - the text of the new tip
	* $tipsSection -  the current tips of this article
	*
	* returns - true if this tip has been added to this article already
	*/
	private function tipAlreadyAdded($tipId, $articleId, $newTip, $tipsSection) {
		$dbr = wfGetDB(DB_SLAVE);
		$pageId = $dbr->selectField('tipsandwarnings_log', 'tw_page', array('tw_id' => $tipId, 'tw_action' => self::TIP_ACTION_KEEP), __METHOD__); 
		if (intval($articleId) == intval($pageId)) {
			return true;
		}
		$tips = Wikitext::splitTips($tipsSection);
		$newTip = self::cleanTip($newTip);
		foreach ($tips as $tip) {
			$tip = self::cleanTip($tip);
			if ($tip == $newTip) {
				return true;
			}
		}
		return false;
	}

	public function keepTip($tipId, $articleId, $tip, &$result = array()) {
		global $wgUser, $wgParser;

		$result['debug'][] = "keepTip";
		$title = Title::newFromID($articleId);

		if($title) {
			$revision = Revision::newFromTitle($title);
			$article = new Article($title);
			if($revision && $article) {
				$wikitext = $revision->getText();
				$section = Wikitext::getSection($wikitext, "Tips", true);

				// do not add the tip if the tips section does not exist.
				if ($section[1] == 0 || !$section[0] || $section[0] == "") {
					return false;
				}

				// see if this tip already exists...if it does do not add it
				if (self::tipAlreadyAdded($tipId, $articleId, $tip, $section[0])) {
					return false;
				}

				$newSection = $section[0] . "\n* $tip";

				$newText = $wgParser->replaceSection($wikitext, $section[1], $newSection);

				// the save hook will log this tip being approved
				$success = $article->doEdit($newText, wfMsg('newtips-article-edit-entry'));

				return $success;
			}
		}
	}
	
	private function addToQG($tipId, $articleId, $tip, &$result) {
		$title = Title::newFromID($articleId);
		if ($title) { 
			$article = new Article($title);
			if ($article) {
				//Add it to the QG queue
				$l = new QCRuleTip($article, $tipId);
				$l->process();	
				
				//remove it from Tips Patrol queue
				$dbw = wfGetDB(DB_MASTER);
				$dbw->delete('tipsandwarnings', array('tw_id' => $tipId), __METHOD__);
				
				//log it
				$logPage = new LogPage('newtips', false);
				$logData = array($tipId);
				$logMsg = wfMsg('newtips-sentToQG-logentry', $title->getFullText(), $tip);
				$logS = $logPage->addEntry("Approved", $title, $logMsg, $logData);
				
				$result['debug'][] = "processed";
			}
		}
		return;
	}

	// **************************************************************
	// ** deprecated because we use QG as a Step 2 for Tips Patrol **
	// **************************************************************
	/*function articleSaved($article, $user, $text, $summary, $minor, $p6, $p7, $flags, $revision) {
		global $wgRequest;

		if ($summary == TipsAndWarnings::EDIT_COMMENT) {
			$title = $article->getTitle();
			$tipId = $wgRequest->getVal('tipId');
			$tip = $wgRequest->getVal('tip');
			if ($tipId && $tip) {
					$logPage = new LogPage('newtips', false);

					$logMsg = wfMsg('newtips-approved-logentry', $title->getFullText(), $tip);
					$logData = array($tipId);
					$logS = $logPage->addEntry("Approved", $title, $logMsg, $logData);

					$dbw = wfGetDB(DB_MASTER);
					TipsPatrol::logTip($tipId, self::TIP_ACTION_KEEP, $revision);
					$dbw->delete('tipsandwarnings', array('tw_id' => $tipId));
			}
		}
		return true;
	}
	*/

	function displayLeaderboards() {
		$stats = new TipsPatrolStandingsIndividual();
		$stats->addStatsWidget();
		$standings = new TipsPatrolStandingsGroup();
		$standings->addStandingsWidget();
	}

	function logTip($tipId, $tipAction, $revision=null, $newtip=null) {
		global $wgUser;
		$userId = $wgUser->getID();

		$row = TipsPatrol::getTipRow($tipId);
		if (is_array($row)) {
			$row['tw_action'] = $tipAction;
			if ($revision) {
				$row['tw_rev_this'] = $revision->getId();
			}

			// if it's a skip don't bother recording the tip itself
			if ($tipAction == TipsPatrol::TIP_ACTION_SKIP) {
				unset($row['tw_tip']);
			}

			if ($tipAction == TipsPatrol::TIP_ACTION_QG) {
				if ($newtip != null) $row['tw_tip'] = $newtip;
			}
			else {
				$row['tw_user'] = $userId;
			}
			
			$row['tw_timestamp'] = wfTimestampNow();
			$dbw = wfGetDB(DB_MASTER);
			$dbw->insert('tipsandwarnings_log', $row, __METHOD__, array('IGNORE'));

			// only count views while tipspatrol is active
			if (TipsPatrol::isTPCoachEnabled()) {
				$dbw->query("INSERT INTO `tipspatrol_views` (`tpv_user_id`, `tpv_count`) VALUES ($userId, 1) ON DUPLICATE KEY UPDATE tpv_count = tpv_count + 1");
			}
		}
	}

	private function cleanTip($tip) {
		if ($tip[0] == "*") {
			$tip = substr($tip, 1);
		}
		return trim($tip);
	}

	/*
	* revert a revision in which a tip was added
	*
	* If the revision is undone, it will mark the recentchange as patrolled
	*
	* $pageId - page to act on
	* $revId - revision to undo
	*
	*/
	function revertTipOnArticle($pageId, $revId) {
		global $wgParser;

		// do not revert if no revId
		if ($revId <= 0 || $revId == null || $revId == "") {
			return false;
		}

		$undoRevision = Revision::newFromId($revId);
		$previousRevision = $undoRevision ? $undoRevision->getPrevious() : null;

		// do not revert if the page is wrong or changed..
		if ( is_null($undoRevision) || is_null($previousRevision) || $undoRevision->getPage()!=$previousRevision->getPage() || $undoRevision->getPage()!=$pageId ) {
			return false;
		}

		$title = Title::newFromID($pageId);
		$article = new Article($title);

		$undoRevisionText = $undoRevision->getText();
		$currentText = $article->getContent();

		$undoTips = Wikitext::splitTips(reset(Wikitext::getSection($undoRevisionText, "Tips", true)));
		$prevTips = Wikitext::splitTips(reset(Wikitext::getSection($previousRevision->getText(), "Tips", true)));
		$currentTipsSection = Wikitext::getSection($currentText, "Tips", true);
		$currentTips = Wikitext::splitTips($currentTipsSection[0]);
		$section = $currentTipsSection[1];

		$undoTipsFormatted = array();
		foreach ($undoTips as $tip) {
			$undoTipsFormatted[] = self::cleanTip($tip);
		}

		$prevTipsFormatted = array();
		foreach ($prevTips as $tip) {
			$prevTipsFormatted[] = self::cleanTip($tip);
		}

		$badTips = array_diff($undoTipsFormatted, $prevTipsFormatted);
		$resultTips = "== Tips ==";
		foreach($currentTips as $currentTip) {
			$tip = self::cleanTip($currentTip);
			if (in_array($tip, $badTips)) {
				continue;
			}
			$resultTips .= "\n".$currentTip;
		}
		$newText = $wgParser->replaceSection($currentText, $section, $resultTips);
		$success = $article->doEdit($newText, 'reverting tip from revision '.$revId, EDIT_UPDATE | EDIT_MINOR );

		// mark the recent change as patrolled
		if ($success) {
			// should be ok to read from slave here because the change has been done earlier.
			$dbr = wfGetDB(DB_SLAVE);
			$rcid = $dbr->selectField('recentchanges', 'rc_id', array("rc_this_oldid=$revId") );
			RecentChange::markPatrolled($rcid);
			PatrolLog::record($rcid, false);
		}

		return $success;
	}

	/*
	* Undo a tip that was added/removed via tips patrol
	*
	* It will put the tip back into tips patrol
	* if the tip was added, it will try to undo the revision
	* if the tip was skipped it just removes it
	* if the revision is undone, it will mark the recentchange as patrolled
	*
	* $tipId - id of the tip in tipsandwarnings_log to undo (same id as the original tip)
	*
	* returns - the new tipId of the tip that was added back to tipsandwarnings
	*/
	function undoTip($tipId) {
		$tipData = TipsPatrol::getTipLogRow($tipId);
		$action = $tipData['tw_action'];

		if ($action == TipsPatrol::TIP_ACTION_KEEP) {
			// undo the revision
			$success = TipsPatrol::revertTipOnArticle($tipData['tw_page'], $tipData['tw_rev_this']);
			if (!$success) {
				return false;
			}
		}

		// tip data to add back to tipsandwarnings table
		$data = array("tw_page"=> $tipData['tw_page'],
					"tw_tip"=>$tipData['tw_tip'],
					"tw_user"=>0,
					"tw_timestamp"=>wfTimestampNow());

		$dbw = wfGetDB(DB_MASTER);
		if ($action != TipsPatrol::TIP_ACTION_SKIP) {
			$dbw->insert('tipsandwarnings', $data, __METHOD__, array('IGNORE'));
		}
		$dbw->delete('tipsandwarnings_log', array('tw_id' => $tipId));

		return true;
	}

	function getTipLogRow($tipId) {
		$dbr = wfGetDB(DB_SLAVE);
		if ($row = $dbr->selectRow('tipsandwarnings_log', '*', array('tw_id' => $tipId), __METHOD__)) {
			$row = get_object_vars($row);
		} else {
			$row = null;
		}
		return $row;
	}

	function getTipRow($tipId) {
		$dbr = wfGetDB(DB_SLAVE);
		if ($row = $dbr->selectRow('tipsandwarnings', '*', array('tw_id' => $tipId), __METHOD__)) {
			$row = get_object_vars($row);
		} else {
			$row = null;
		}
		return $row;
	}

}
