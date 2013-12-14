<?php

if ( !defined('MEDIAWIKI') ) exit;

function getSearchKeyStopWords() {
	global $wgMemc;

	$cacheKey = wfMemcKey('stop_words');
	$cacheResult = $wgMemc->get($cacheKey);
	if ($cacheResult) {
		return $cacheResult;
	}

	$sql = "SELECT stop_words FROM stop_words limit 1";
	$stop_words = null;
	$db = wfGetDB(DB_SLAVE);
	$res = $db->query($sql, __METHOD__);
	if ( $db->numRows($res) ) {
		while ( $row = $db->fetchObject($res) ) {
			$stop_words = split(", ", $row->stop_words);
		}
	}
	$db->freeResult( $res );

	$s_index = array();
	if (is_array($stop_words)) {
		foreach ($stop_words as $s) {
			$s_index[$s] = "1";
		}
	}

	$wgMemc->set($cacheKey, $s_index);

	return $s_index;
}

function generateSearchKey($text) {
	$stopWords = getSearchKeyStopWords();

	$text = strtolower($text);
	$tokens = split(' ', $text);
	$ok_words = array();
	foreach ($tokens as $t) {
		if ($t == '' || isset($stopWords[$t]) ) continue;
		$ok_words[] = $t;
	}
	sort($ok_words);
	$key = join(' ', $ok_words);
	$key = trim($key);

	return $key;
}

function updateSearchIndex($new, $old) {
	$dbw = wfGetDB(DB_MASTER);
	if ($new != null
		&& ($new->getNamespace() == 0
			|| $new->getNamespace() == 16) )
	{
		$dbw->delete( 'title_search_key',
			array('tsk_title' => $new->getDBKey(),
				  'tsk_namespace' => $new->getNamespace()),
			__METHOD__ );

		$dbw->insert( 'title_search_key',
			array('tsk_title' => $new->getDBKey(),
				  'tsk_namespace' => $new->getNamespace(),
				  'tsk_key' => generateSearchKey($new->getText()) ),
			__METHOD__ );
	}

	if ($old != null) {
		$dbw->delete( 'title_search_key',
			array('tsk_title' => $old->getDBKey(),
				  'tsk_namespace' => $old->getNamespace()),
			__METHOD__ );
	}
}

function wfMarkUndoneEditAsPatrolled() {
	global $wgRequest;
	if ($wgRequest->getVal('wpUndoEdit', null) != null) {
		$oldid = $wgRequest->getVal('wpUndoEdit');
		// using db master to avoid db replication lag
		$dbr = wfGetDB(DB_MASTER);
		$rcid = $dbr->selectField('recentchanges', 'rc_id', array("rc_this_oldid=$oldid") );
		RecentChange::markPatrolled($rcid);
		PatrolLog::record($rcid, false);
	}
	return true;
}

function wfTitleMoveComplete($title, &$newtitle, &$user, $oldid, $newid) {
	updateSearchIndex($title, $newtitle);
	return true;
}
$wgHooks['TitleMoveComplete'][] = array('wfTitleMoveComplete');

function wfArticleSaveComplete($article, $user, $p2, $p3, $p5, $p6, $p7) {
	global $wgMemc;

	if ($article) {
		updateSearchIndex($article->getTitle(), null);
	}
	wfMarkUndoneEditAsPatrolled();

	// In WikiHowSkin.php we cache the info for the author line. we want to
	// remove this if that article was edited so that old info isn't cached.
	if ($article && class_exists('SkinWikihowskin')) {
		$cachekey = ArticleAuthors::getLoadAuthorsCachekey($article->getID());
		$wgMemc->delete($cachekey);
	}

	return true;
}
$wgHooks['ArticleSaveComplete'][] = array('wfArticleSaveComplete');

function wfImageConvertNoScale($dstPath, $params) {
	// edge case...if the image will not actually get watermarked because it's too small, just return true
	if (WatermarkSupport::validImageSize($params['physicalWidth'], $params['physicalHeight']) == false) {
		return true;
	}

	// return false here..we want to create the watemarked file!
	if ($params[WatermarkSupport::ADD_WATERMARK] || file_exists($dstPath)) {
		return false;
	}

	return true;
}
$wgHooks['ImageConvertNoScale'][] = array('wfImageConvertNoScale');

function wfImageConvertComplete($dstPath, $params) {
	if ($params[WatermarkSupport::ADD_WATERMARK]) {
		WatermarkSupport::addWatermark($dstPath, $dstPath, $params['physicalWidth'], $params['physicalHeight']);
	}
	return true;
}
$wgHooks['ImageConvertComplete'][] = array('wfImageConvertComplete');

function wfFileTransform($image, &$params) {
	if ( $image->user_text && $image->user_text == WatermarkSupport::WIKIPHOTO_CREATOR && $params[WatermarkSupport::NO_WATERMARK] != true) {
		$params[WatermarkSupport::ADD_WATERMARK] = true;
	}

	if ($params[WatermarkSupport::FORCE_TRANSFORM] != true) {
		return true;
	}

	return false;
}
$wgHooks['FileTransform'][] = array('wfFileTransform');

function wfFileThumbName($image, $params, &$thumbName) {
		$wm = '';
		if ( $image->user_text && $image->user_text == WatermarkSupport::WIKIPHOTO_CREATOR && $params[WatermarkSupport::NO_WATERMARK] == true) {
			$wm = 'nowatermark-';
			$thumbName = $image->handler->makeParamString( $params ) . '-' . $wm . $image->getName();
		}

	return true;
}
$wgHooks['FileThumbName'][] = array('wfFileThumbName');

function wfImageConvert($cmd, $image, $srcPath, $dstPath, $params) {
	if ($params[WatermarkSupport::FORCE_TRANSFORM] == false) {
		global $wgMemc;
		$key = wfMemcKey('imgconvert', md5($cmd));
		if ($wgMemc->get($key)) {
			return false;
		}
		$wgMemc->set($key, 1, 3600);
	}
	$physicalWidth = $params['physicalWidth'];
	$physicalHeight = $params['physicalHeight'];
	$addWatermark = $params[WatermarkSupport::ADD_WATERMARK];
	$srcWidth = $image->getWidth();
	$srcHeight = $image->getHeight();

	if ( $physicalWidth == $srcWidth && $physicalHeight == $srcHeight && $addWatermark ) {
		WatermarkSupport::addWatermark($srcPath, $dstPath, $physicalWidth, $physicalHeight);
		return false;
	}

	return true;
}
$wgHooks['ImageConvert'][] = array('wfImageConvert');

function wfUpdateCatInfoMask(&$article, &$user) {
	if ($article) {
		$title = $article->getTitle();
		if ($title && $title->getNamespace() == NS_MAIN) {
			$mask = $title->getCategoryMask();
			$dbw = wfGetDB(DB_MASTER);
			$dbw->update('page',
				array('page_catinfo' => $mask),
				array('page_id' => $article->getID()),
				__METHOD__);
		}
	}
	return true;
}
$wgHooks['ArticleSaveComplete'][] = array('wfUpdateCatInfoMask');

function wfUpdatePageFeaturedFurtherEditing($article, $user, $text, $summary, $flags) {
	if ($article) {
		$t = $article->getTitle();
		if (!$t || $t->getNamespace() != NS_MAIN) {
			return true;
		}
	}

	$templates = split("\n", wfMsgForContent('templates_further_editing'));
	$regexps = array();
	foreach ($templates as $template) {
		$template = trim($template);
		if ($template == "") continue;
		$regexps[] ='\{\{' . $template;
	}
	$re = "@" . implode("|", $regexps) . "@i";

	$updates = array();
	if (preg_match_all($re, $text, $matches)) {
		$updates['page_further_editing'] = 1;
	}
	else{
		$updates['page_further_editing'] = 0; //added this to remove the further_editing tag if its no longer needed
	}
	if (preg_match("@\{\{fa\}\}@i", $text)) {
		$updates['page_is_featured'] = 1;
	}
	if (sizeof($updates) > 0) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->update('page', $updates, array('page_id'=>$t->getArticleID()), __METHOD__);
	}
	return true;
}

$wgHooks['ArticleSaveComplete'][] = array('wfDeleteParentCategoryKey');

/*
* Delete the memcache key that stores the parent category breadcrumbs so that they will update
* on wikitext category changes
*/
function wfDeleteParentCategoryKey($article, $user, $text, $summary, $flags) {
	global $wgMemc;

	if ($article) {
		$t = $article->getTitle();
		if (!$t || $t->getNamespace() != NS_MAIN) {
			return true;
		}
		$key = wfMemcKey('parentcattree', $t->getArticleId());
		$wgMemc->delete($key);
	}
	return true;
}

$wgHooks['ArticleSaveComplete'][] = array('wfUpdatePageFeaturedFurtherEditing');

function wfSetPage404IfNotExists() {
	global $wgTitle, $wgOut, $wgLanguageCode;

	// Note: if namespace < 0, it's a virtual namespace like NS_SPECIAL
	// Check if image exists for foreign language images, because Title may not exist since image may only be on English
	if ($wgTitle && $wgTitle->getNamespace() >= 0 && !$wgTitle->exists() && 
	($wgLanguageCode =="en" || $wgTitle->getNamespace() != NS_IMAGE || !wfFindFile($wgTitle))
	) {
		$redirect = Misc::check404Redirect($wgTitle);
		if (!$redirect) {
			$wgOut->setStatusCode(404);
		} else {
			$wgOut->redirect('/' . $redirect, 301);
		}
	}
	return true;
}
$wgHooks['OutputPageBeforeHTML'][] = array('wfSetPage404IfNotExists');

// implemented in Misc.body.php
$wgHooks['JustBeforeOutputHTML'][] = array('Misc::setMobileLayoutHeader');
$wgHooks['JustBeforeOutputHTML'][] = array('Misc::addVarnishHeaders');

// implemented in ArticleMetaInfo.class.php
if(!IS_LANG_CRON) {
	// NOTE: this hook would optimally be run only on patrol. We don't do this because
	// there are a few different hooks for patrolling -- I believe autopatrol, RC patrol,
	// etc, have their own hooks.
	$wgHooks['ArticleSaveComplete'][] = array('ArticleMetaInfo::refreshMetaDataCallback');
}

function wfAddCacheControlHeaders() {
	global $wgTitle, $wgRequest;

	if ($wgRequest && $wgTitle && $wgTitle->getText() == wfMsg('mainpage')) {
		$wgRequest->response()->header('X-T: MP');
	}

	return true;
}
$wgHooks['AddCacheControlHeaders'][] = array('wfAddCacheControlHeaders');

// Add to the list of available JS vars on every page
function wfAddJSglobals(&$vars) {
	$vars['wgCDNbase'] = wfGetPad('');
	return true;
}
$wgHooks['MakeGlobalVariablesScript'][] = array('wfAddJSglobals');

//
// Hooks for managing 404 redirect system
//
function wfFix404AfterMove($oldTitle, $newTitle) {
	if ($oldTitle && $newTitle) {
		Misc::modify404Redirect($oldTitle->getArticleID(), null);
		Misc::modify404Redirect($newTitle->getArticleID(), $newTitle);
	}
	return true;
}
function wfFix404AfterDelete($article) {
	if ($article) {
		$pageid = $article->getID();
		Misc::modify404Redirect($pageid, null);
	}
	return true;
}
function wfFix404AfterInsert($article) {
	if ($article) {
		$title = $article->getTitle();
		if ($title) {
			Misc::modify404Redirect($article->getID(), $title);
		}
	}
	return true;
}
function wfFix404AfterUndelete($title) {
	if ($title) {
		$pageid = $title->getArticleID();
		Misc::modify404Redirect($pageid, $title);
	}
	return true;
}
$wgHooks['TitleMoveComplete'][] = array('wfFix404AfterMove');
$wgHooks['ArticleDelete'][] = array('wfFix404AfterDelete');
$wgHooks['ArticleInsertComplete'][] = array('wfFix404AfterInsert');
$wgHooks['ArticleUndelete'][] = array('wfFix404AfterUndelete');

