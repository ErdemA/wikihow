<?php

if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['RCTest'] = dirname(__FILE__) . '/RCTest.class.php';

// Hook to mark quizzes deleted that reference pages no longer in the database
$wgHooks['ArticleDelete'][] = array("wfMarkRCTestDeleted");

function wfMarkRCTestDeleted($article, $user, $reason) {
	try {
		$dbw = wfGetDB(DB_MASTER);
		$id = $article->getTitle()->getArticleID();
		$dbw->update('rctest_quizzes', array('rq_deleted' => 1), array('rq_page_id' => $id));
	} catch (Exception $e) {

	}
	return true;
}

