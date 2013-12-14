<?php

class MethodEditor extends SpecialPage {
	
	const METHOD_EXPIRED = 1800; // 30 minutes
	const METHOD_ACTION_DELETE = 1;
	const METHOD_ACTION_KEEP = 2;
	const TABLE_NAME = "altmethodadder";
	const EDIT_COMMENT = "added method from [[Special:MethodEditor|Method Editor]]";
	
	var $skipTool;
	
	function __construct() {
		global $wgHooks;
		SpecialPage::SpecialPage("MethodEditor");
		$wgHooks['getToolStatus'][] = array('Misc::defineAsTool');
	}
	
	function execute($par) {
		global $wgOut, $wgRequest, $wgUser, $wgParser;
		
		wfLoadExtensionMessages("MethodEditor");
		
		if ($wgUser->isBlocked()) {
			$wgOut->blockedPage();
			return;
		}

        if ( !($wgUser->isSysop() || in_array( 'newarticlepatrol', $wgUser->getRights()) ) ) {
            $wgOut->setRobotpolicy( 'noindex,nofollow' );
            $wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
            return;
        }
		
		$this->skipTool = new ToolSkip("methodeditor", MethodEditor::TABLE_NAME, "ama_checkout", "ama_checkout_user", "ama_id");
		
		if ( $wgRequest->getVal('getNext') ) {
			$wgOut->disable();
			
			$result = $this->getNextMethod();
			echo json_encode($result);
			return;
		} else if ( $wgRequest->getVal('skipMethod') ) {
			$wgOut->disable();
			$methodId = $wgRequest->getVal('methodId');
			$this->skipTool->skipItem($methodId);
			$this->skipTool->unUseItem($methodId);
			$result = $this->getNextMethod();
			echo json_encode($result);
			return;
		}
		elseif ($wgRequest->getVal('deleteMethod')) {
			$wgOut->disable();
			
			$methodId = $wgRequest->getVal('methodId');
			$articleId = $wgRequest->getVal('articleId');
			$method = $wgRequest->getVal('method');
			$this->deleteMethod($methodId, $articleId, $method);
			
			$result = $this->getNextMethod();
			echo json_encode($result);
			return;
		}
		elseif ($wgRequest->getVal('keepMethod')) {
			$wgOut->disable();
			
			$methodId = $wgRequest->getVal('methodId');
			$articleId = $wgRequest->getVal('articleId');
			$altMethod = $wgRequest->getVal('method');
			$altSteps = $wgRequest->getVal('steps');
			$this->keepMethod($methodId, $articleId, $altMethod, $altSteps);
			
			$result = $this->getNextMethod();
			echo json_encode($result);
			return;
		}
		elseif( $wgRequest->getVal('quickEdit') ) {
			$wgOut->disable();

			$methodId = $wgRequest->getVal('methodId');
			$articleId = $wgRequest->getVal('articleId');
			$this->quickEditRecord($methodId, $articleId);

			$result = $this->getNextMethod();
			echo json_encode($result);
			return;
		}
		elseif( $wgRequest->getVal('clearSkip') ) {
			$wgOut->disable();
			$this->skipTool->clearSkipCache();
			echo "Skip cache has been cleared";
			return;
		}
		
		$wgOut->setHTMLTitle(wfMsg('methodeditor'));
		$wgOut->setPageTitle(wfMsg('methodeditor'));
		
		$wgOut->addScript(HtmlSnips::makeUrlTags('js', array('clientscript.js'), 'skins/common', false));
		$wgOut->addScript(HtmlSnips::makeUrlTags('css', array('methodeditor.css'), 'extensions/wikihow/altmethodadder', false));
		$wgOut->addScript(HtmlSnips::makeUrlTags('js', array('methodeditor.js'), 'extensions/wikihow/altmethodadder', false));
        $wgOut->addScript(HtmlSnips::makeUrlTags('js', array('jquery.cookie.js'), 'extensions/wikihow/common', false));
		$wgOut->addHTML(PopBox::getPopBoxJSAdvanced());
		
		$tmpl = new EasyTemplate( dirname(__FILE__) );

		$wgOut->addHTML($tmpl->execute('MethodEditor.tmpl.php'));
		$this->displayLeaderboards();

		$wgOut->addHTML(QuickNoteEdit::displayQuickEdit());
	}
	
	private function getNextMethod() {
		global $wgUser, $wgOut;
		
		$dbw = wfGetDB(DB_MASTER);
		$expired = wfTimestamp(TS_MW, time() - MethodEditor::METHOD_EXPIRED);
		$i = 0;
		$content['error'] = true;
		$goodRevision = false;
		do {
			$skippedIds = $this->skipTool->getSkipped();
			$where = array();
			$where[] = "ama_checkout < '$expired'";
            $where[] = "ama_patrolled = 1";
			$where[] = "NOT EXISTS (SELECT rc_id from recentchanges where rc_cur_id = ama_page and rc_patrolled = 0 LIMIT 1)";
			if($skippedIds) {
				$where[] = "ama_id NOT IN ('" . implode("','", $skippedIds) . "')";
			}
			$row = $dbw->selectRow(MethodEditor::TABLE_NAME, array('*'), $where, __METHOD__, array("LIMIT" => 1));
			$content['sql' . $i] = $dbw->lastQuery();
			$content['row'] = $row;

			if($row !== false) {
				$title = Title::newFromID($row->ama_page);
				$isRedirect = false;
				if ($title) {
					$dbr = wfGetDB(DB_SLAVE);
					$isRedirect = intval($dbr->selectField('page', 'page_is_redirect', 
						array('page_id' => $row->ama_page), __METHOD__, array("LIMIT" => 1)));
				}
				if($title && !$isRedirect) {
						$this->skipTool->useItem($row->ama_id);
						$revision = Revision::newFromTitle($title);
						$popts = $wgOut->parserOptions();
						$popts->setTidy(true);
						$parserOutput = $wgOut->parse($revision->getText(), $title, $popts);
						$content['article'] = WikihowArticleHTML::processArticleHTML($parserOutput, array('no-ads', 'ns' => NS_MAIN));
						$content['method'] = $row->ama_method;
						$content['methodId'] = $row->ama_id;
						$content['articleId'] = $row->ama_page;
						$content['steps'] = $row->ama_steps;
						$content['articleTitle'] = "<a href='{$title->getLocalURL()}'>{$title->getText()}</a>";

						$editURL = Title::makeTitle(NS_SPECIAL, "QuickEdit")->getFullURL() . '?type=editform&target=' . urlencode($title->getFullText());
						$class = "class='button secondary buttonleft'";
						$link =  "<a id='qe_button' title='" . wfMsg("rcpatrol_quick_edit_title") . "' accesskey='e' href='' $class onclick=\"return loadQuickEdit('".$editURL."') ;\">" . htmlspecialchars( 'Quick edit' ) . "</a> ";

						$content['quickEditUrl'] = $link;
						$content['error'] = false;
				} else {
					//article must no longer exist or be a redirect, so delete the tips associated with that article
					$dbw = wfGetDB(DB_MASTER);
					$dbw->delete(MethodEditor::TABLE_NAME, array('ama_page' => $row->ama_page));
				}
			}
			$i++;
		// Check up to 5 titles.
		// If no good title then return an error message
		} while ($i <= 5 && !$title && $row !== false);

		$content['i'] = $i;
		$content['methodCount'] = self::getCount();
		return $content;
		
	}
	
	private function getCount() {
		$dbr = wfGetDB(DB_SLAVE);
		return $dbr->selectField(MethodEditor::TABLE_NAME, 'count(*) as count', array('ama_patrolled' => 1));
	}
	
	private function deleteMethod($methodId = null, $articleId, $method) {
        global $wgUser;
		if($methodId != null) {
			$dbw = wfGetDB(DB_MASTER);
			$dbw->delete(MethodEditor::TABLE_NAME, array('ama_id' => $methodId));
			
			$title = Title::newFromID($articleId);
			if($title) {
				$logPage = new LogPage('methedit', false);
				$logS = $logPage->addEntry("Deleted", $title, wfMsg('editor-rejected-logentry', $title->getFullText(), $method));
			}

            wfRunHooks("MethodEdited", array($wgUser, $title, '0'));
		}
	}
	
	private function keepMethod($methodId, $articleId, $altMethod, $altSteps) {
		global $wgUser, $wgParser;
		
		$title = Title::newFromID($articleId);
		
		if($title) {
			$revision = Revision::newFromTitle($title);
			$article = new Article($title);
			if($revision && $article) {
				$wikitext = $revision->getText();
				$section = Wikitext::getStepsSection($wikitext, true);
				$newSection = $section[0] . "\n\n=== {$altMethod} ===\n{$altSteps}";
				
				$newText = $wgParser->replaceSection($wikitext, $section[1], $newSection);
				
				$success = $article->doEdit($newText, MethodEditor::EDIT_COMMENT);
				
				if($success) {
					$logPage = new LogPage('methedit', false);

                    $altMethodTransform = str_replace(" ", "_", $altMethod);
					
					$logS = $logPage->addEntry("Added", $title,
					wfMsg('editor-approved-logentry', $title->getFullText(), $altMethod, $altMethodTransform));
					
					$dbw = wfGetDB(DB_MASTER);
					$dbw->delete(MethodEditor::TABLE_NAME, array('ama_id' => $methodId));
				}
                wfRunHooks("MethodEdited", array($wgUser, $title, '0'));
				return $success;
			}
		}
	}

	private function quickEditRecord($methodId, $articleId) {
		global $wgUser;

		$title = Title::newFromID($articleId);

		if($title) {
			$logPage = new LogPage('methedit', false);

			$logPage->addEntry("Added", $title, wfMsg('editor-quickedit-logentry', $title->getFullText()));

			$dbw = wfGetDB(DB_MASTER);
			$dbw->delete(MethodEditor::TABLE_NAME, array('ama_id' => $methodId));

			wfRunHooks("MethodEdited", array($wgUser, $title, '0'));
		}

	}

	function displayLeaderboards() {
		$stats = new MethodEditorStandingsIndividual();
		$stats->addStatsWidget();
		$standings = new MethodEditorStandingsGroup();
		$standings->addStandingsWidget();
	}
}
