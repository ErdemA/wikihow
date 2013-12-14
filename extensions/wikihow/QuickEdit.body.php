<?php

class QuickEdit extends UnlistedSpecialPage 
{
    function __construct() {
        UnlistedSpecialPage::UnlistedSpecialPage( 'QuickEdit' );
    }

    public function execute() {
		global $wgUser, $wgRequest, $wgOut;
		
		if ($wgUser->isBlocked()) {
			$wgOut->blockedPage();
			return;
		}
		$type = $wgRequest->getVal('type', null);
		$target = $wgRequest->getVal('target', null);
		if ($type == 'editform') {
			$wgOut->setArticleBodyOnly(true);
			$title = Title::newFromURL($target);
			if (!$title) {
				$wgOut->addHTML('error: bad target');
			} else {
				self::showEditForm($title);
			}
			return;
		}

	}
	/**
	 * Display the Edit page for an article for an AJAX request.  Outputs
	 * HTML.
	 *
	 * @param Title $title title object describing which article to edit
	 */
	public static function showEditForm($title) {
		global $wgRequest, $wgTitle, $wgOut;
		$wgTitle = $title;
		$article = new Article($title);
		$editor = new EditPage($article);
		$editor->edit();

		if ($wgOut->mRedirect != '' && $wgRequest->wasPosted()) {
			$wgOut->redirect('');
			$rev = Revision::newFromTitle($title);
			$wgOut->addHTML($wgOut->parse($rev->getText()));
		}
	}

}
