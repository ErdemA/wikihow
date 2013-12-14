<?php

class RCBuddy extends UnlistedSpecialPage {

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'RCBuddy' );
	}

	// Gets the number of unpatrolled edits to featured articles, excluding 
	// edits by the current user
	function getFeaturedUnpatrolCount($delay, $skip) {
		global $wgMemc, $wgUser;
		$fname = __METHOD__;
		wfProfileIn($fname);
		$key = wfMemcKey("rcbuddy_unp_count_{$delay}_{$wgUser->getID()}");
		if ($wgMemc->get($key)) {
			wfProfileOut($fname);
			return $wgMemc->get($key);
		}
		$dbr = wfGetDB(DB_SLAVE);
		$row = $dbr->selectRow(array('recentchanges', 'page'),
			array('count(*) as c'),
			array('rc_cur_id=page_id',
				'rc_patrolled=0',
				'rc_user !=' . $wgUser->getId(),
				'page_is_featured=1',
				$delay == 0 ? "1=1" : "rc_timestamp < '" . wfTimestamp( TS_MW, time() - $delay * 60 ) . "'",
				$skip == "" ? "1=1" : "rc_id not in ($skip)"),
			$fname);
		$count = $row->c;
		$wgMemc->set($key, $count, 60);
		wfProfileOut($fname);
		return $count;
	}

	// Gets page wide stats about # of unpatrolled edits, # of users online,
	// with cacheable results, since they are page wide stats and not user 
	// specific
	function getPageWideStats($results) {
		global $wgMemc;
		$fname = __METHOD__;
		wfProfileIn($fname);

		$key = wfMemcKey("rcbuddy_pagewidestats");
		$result = $wgMemc->get($key);
		if ($result) {
			wfProfileOut($fname);
			return array_merge($results, $result);
		}

		$dbr = wfGetDB(DB_SLAVE);
		$newstuff = array();
		$count = $dbr->selectField(array ('recentchanges'),
			array('count(*) as c'),
			array('rc_patrolled=0'),
			$fname);
		$newstuff['unpatrolled_total']= $count;

		$t =  gmdate("YmdHis", time() - 60 * 30); // thirty minutes ago
		$row = $dbr->selectRow(array('recentchanges'),
			array('count(distinct(rc_user)) as c'),
			array("rc_timestamp > $t ",
				'rc_user > 0'),
			$fname
		);
		$count = $row->c;
		$newstuff['users_editing'] = $count;

		$nab_unpatrolled = Newarticleboost::getNABCount($dbr);
		$newstuff['nab_unpatrolled'] = $nab_unpatrolled;

		$wgMemc->set($key, $newstuff, 60);
		wfProfileOut($fname);
		return array_merge($newstuff, $results);
	}

	// The following is just used by the wikihow toolbar
	function execute($par) {
		global $wgOut, $wgUser, $wgRequest, $wgServer, $wgCookiePrefix;

		$fname = __METHOD__;
		wfProfileIn($fname);
		$wgOut->disable(true);
		header("Content-type: text/plain");

		// users may have skipped a bunch of edits, don't include them here
		$skip = "";
		foreach ($_COOKIE as $key=>$value) {
			if (strpos($key, $wgCookiePrefix . "WsSkip_") === 0) {
				if ($skip != "") $skip .= ",";
				$skip .= $value;
			}
		}
		$delay = $wgRequest->getVal('delay');
		$count = $this->getFeaturedUnpatrolCount($delay, $skip);
		$results = array('unpatrolled_fa' => $count);

		$results = self::getPageWideStats($results);

		if( $wgUser->getNewtalk())
			$results['new_talk'] = 1;
		else
			$results['new_talk'] = 0;

		$window = Patrolcount::getPatrolcountWindow();
		$dbr = wfGetDB( DB_SLAVE );
		$count = $dbr->selectField('logging',
			array('count(*)'),
			array("log_user={$wgUser->getId()}",
				"log_type='patrol'",
				"log_timestamp > '{$window[0]}'",
				"log_timestamp < '{$window[1]}'"),
			$fname);
		$results['patrolledtoday'] = $count;

		foreach ($results as $k=>$v) {
			echo "$k=$v\n";
		}
		wfProfileOut($fname);
		return;
	}

}

