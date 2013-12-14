<?
class UnpatrolTips extends UnlistedSpecialPage {

    function __construct() {
        UnlistedSpecialPage::UnlistedSpecialPage( 'UnpatrolTips' );
    }

	function padVar ($name) {
		global $wgRequest;
		$val = $wgRequest->getVal($name);
		if ($val && strlen($val) < 2)
			$val = "0" . $val;
		return $val;
	}

    function execute ($par) {
		global $wgOut, $wgRequest, $wgUser, $wgLang;
		if ( !in_array( 'staff', $wgUser->getGroups() ) ) {
			$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}
		$target = $wgRequest->getVal("target");
		if ($target && is_numeric($target)) {
			$target = User::whoIs(intval($target));
		}
		$wgOut->addHTML("
			<form action='/Special:UnpatrolTips' method='POST'>
				Username: <input type='text' name='username' value='$target'/> <br/><br/>
				Start date: Year: " . date("Y") . " Month: <input type='text' name='month_1' size='2' value='" . date("m") . "'/>
						Day: <input type='text' name='day_1' size='2' value='" . date("d") . "'>
						Hour (GMT): <input type='text' name='hour_1' size='2' value='00'> <br/><br/>
				End date (optional): Year: " . date("Y") . " <input type='text' name='month_2' size='2'>
						Day: <input type='text' name='day_2' size='2'>
						Hour (GMT): <input type='text' name='hour_2' size='2' value='00'> <br/><br/>
				<input type='submit'/>
			</form>	");
		if ($wgRequest->wasPosted()) {
			$user   = $wgRequest->getVal('username');

			$start  = date("Y") . $this->padVar('month_1') . $this->padVar('day_1') . $this->padVar('hour_1') . "0000";
			$end =  $wgRequest->getVal('month_2') ?
						date("Y") . $this->padVar('month_2') . $this->padVar('day_2') . $this->padVar('hour_2') . "0000" : null;

		    $cutoff = wfTimestamp(TS_MW, $start);
		    $cutoff2 = null;
		    if (!$end) {
		        $wgOut->addHTML("reverting changes by $user since {$cutoff}<br/>");
		    } else {
		        $cutoff2 = wfTimestamp(TS_MW, $end);
		        $wgOut->addHTML("reverting changes by $user between {$cutoff} and {$cutoff2} <br/>");
		    }

		    $user = User::newFromName($user);

			if ($user->getID() == 0) {
				$wgOut->addHTML("<b>There is no user with this name {$wgRequest->getVal('username', '')}, bailing.</b>");
				return;
			}

			$unpatrolled = $this->revertTips($user,$cutoff,$cutoff2,false);

			if ($unpatrolled > 0) {
				$wgOut->addHTML("Reverted " . $unpatrolled . " tips patrolled by {$user->getName()}\n");
			}
			else {
				$wgOut->addHTML("There were no tips patrolled to undo for this time frame.<br/>");
			}
		}

		return;
	}

	public static function revertTips($user,$cutoff,$cutoff2,$unpatrol_limit) {
		global $wgLang, $wgOut;


		//max number of possible unpatrols
		$limit = array();
		if (!empty($unpatrol_limit)) {
			$limit = array('LIMIT' => $unpatrol_limit);
		}

		$dbw = wfGetDB(DB_MASTER);
		$options = array('tw_user'=>$user->getID(), "tw_timestamp > '{$cutoff}'");
		if ($cutoff2) {
			$options[] = "tw_timestamp < '{$cutoff2}'";
		}

		$res = $dbw->select('tipsandwarnings_log', array('tw_id', 'tw_rev_this'), $options, __METHOD__, $limit);

		$tipIds = array();
		while ($row = $dbw->fetchObject($res)) {
			$tipIds[$row->tw_id] = $row->tw_rev_this;
		}

		wfRunHooks('UnpatrolTips', array(&$tipIds));

		$count = sizeof($tipIds);
		foreach ($tipIds as $tipId=>$revId) {
			// undo the tip
			$success = TipsPatrol::undoTip($tipId);
			if($revId) {
				$revision = Revision::newFromId($revId);
				$title = $revision->getTitle();
				if (!$success) {
					$wgOut->addHTML("failed to undo tip: $tipId with revId: $revId with Title: $title<br>");
					continue;
				}
				$wgOut->addHTML("undid approved tip: $tipId with revId: $revId with Title: $title<br>");
			} else {
				$wgOut->addHTML("undid rejected tip: $tipId <br>");
			}
		}

		//log the change
		$logTitle = Title::newFromText('Special:UnpatrolTips');
		$logPage = new LogPage('undotips', false);
		$logData = array();
		$logMessage = "[[User:" . $user->getName() . "]]: $count tip patrols undone";
		$logPage->addEntry('unpatrol', $logTitle, $logMessage, $logData);

		// set the original logs to be deleted
		unset($options);
		$options = array('log_user'=>$user->getID(), 'log_type'=>'newtips', "log_timestamp > '{$cutoff}'", 'log_deleted' => 0);
		if ($cutoff2) {
			$options[] = "tw_timestamp < '{$cutoff2}'";
		}
		$res = $dbw->update('logging', array('log_deleted' => 1), $options, __METHOD__,$limit);

		return $count;
	}
}
class Unpatrol extends UnlistedSpecialPage {

    function __construct() {
        UnlistedSpecialPage::UnlistedSpecialPage( 'Unpatrol' );
    }

		function padVar ($name) {
			global $wgRequest;
			$val = $wgRequest->getVal($name);
			if ($val && strlen($val) < 2)
				$val = "0" . $val;
			return $val;
		}

    function execute ($par) {
		global $wgOut, $wgRequest, $wgUser, $wgLang;

		if ( !in_array( 'staff', $wgUser->getGroups() ) ) {
         	$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
         	return;
		}

		$wgOut->addHTML("
				<form action='/Special:Unpatrol' method='POST'>
					Username: <input type='text' name='username' value='{$wgRequest->getVal('username', '')}'/> <br/><br/>
					Start date: Year: " . date("Y") . " Month: <input type='text' name='month_1' size='2' value='" . date("m") . "'/>
							Day: <input type='text' name='day_1' size='2' value='" . date("d") . "'>
							Hour (GMT): <input type='text' name='hour_1' size='2' value='00'> <br/><br/>
					End date (optional): Year: " . date("Y") . " <input type='text' name='month_2' size='2'>
                            Day: <input type='text' name='day_2' size='2'>
                            Hour (GMT): <input type='text' name='hour_2' size='2' value='00'> <br/><br/>
					<input type='submit'/>
				</form>	");
		if ($wgRequest->wasPosted()) {
    		$user   = $wgRequest->getVal('username');
    	
			$start  = date("Y") . $this->padVar('month_1') . $this->padVar('day_1') . $this->padVar('hour_1') . "0000";
			$end =  $wgRequest->getVal('month_2') ? 
						date("Y") . $this->padVar('month_2') . $this->padVar('day_2') . $this->padVar('hour_2') . "0000" : null;
		
		    $cutoff = wfTimestamp(TS_MW, $start);
		    $cutoff2 = null;
		    if (!$end) {
		        $wgOut->addHTML("reverting changes by $user since {$cutoff}<br/>");
		    } else {
		        $cutoff2 = wfTimestamp(TS_MW, $end);
		        $wgOut->addHTML("reverting changes by $user between {$cutoff} and {$cutoff2} <br/>");
		    }
		
		    $user = User::newFromName($user);

			if ($user->getID() == 0) {
				$wgOut->addHTML("<b>WHoa! There is no user with this name {$wgRequest->getVal('username', '')}, bailing.</b>");
				return;
			}
			
			$unpatrolled = $this->doTheUnpatrol($user,$cutoff,$cutoff2,false);
			
			if ($unpatrolled > 0) {
				$wgOut->addHTML("Unpatrolled " . $unpatrolled . " patrols by {$user->getName()}\n");
			}
			else {
				$wgOut->addHTML("There were no patrolled edits to undo for this time frame.<br/>");
			}
		}		
		return;	
	}
	
	//does the unpatrolling
	// - returns the count of unpatrolled articles
	// *** MAKE SURE TO ADD AN UNPATROL LIMIT ***
	public static function doTheUnpatrol($user,$cutoff,$cutoff2,$unpatrol_limit) {
		global $wgLang;
		
		//max number of possible unpatrols
		if (!empty($unpatrol_limit)) {
			$limit = array('LIMIT' => $unpatrol_limit);
		}
		else {
			$limit = array();
		}

		$dbw = wfGetDB(DB_MASTER);
		$options = array('log_user'=>$user->getID(), 'log_type'=>'patrol', "log_timestamp > '{$cutoff}'", 'log_deleted' => 0);
		if ($cutoff2)
			$options[] = "log_timestamp < '{$cutoff2}'";
	
		$res = $dbw->select('logging', 
				array('log_title', 'log_params'), 
				$options,
				__METHOD__,  
				$limit);
	
		$oldids = array();
		while ($row = $dbw->fetchObject($res)) {
			#echo "{$row->log_title}\t". str_replace("\n", " ", $row->log_params) . "\n";
			$oldids[]= preg_replace("@\n.*@", "", $row->log_params);
		}		
		
		$count = sizeof($oldids);
		if ($count > 0) {
			//set the patrols in recentchanges as not patrolled
			$sql = "UPDATE recentchanges set rc_patrolled=0 where rc_this_oldid IN (" . implode(", ", $oldids) . ")";
			if (!empty($unpatrol_limit)) $sql .= " LIMIT ".$unpatrol_limit;
			$res = $dbw->query($sql,__METHOD__);
			
			wfRunHooks('Unpatrol', array(&$oldids));

			if ($res) {
				//set logs to deleted
				$res = $dbw->update('logging', array('log_deleted' => 1), $options, __METHOD__,$limit);
				
				//remove from QG
				$del_res = $dbw->delete("qc", array("qc_rev_id IN (" . implode(", ", $oldids) . ")","qc_user" => $dbw->addQuotes($user->getID())));
				
				//log the change
				$title = Title::newFromText('Special:Unpatrol');
				$log = new LogPage( 'unpatrol', false );
				$msg = wfMsgHtml("unpatrol_log", $count, "[[User:" . $user->getName() . "]]", $wgLang->date($cutoff), $cutoff2==null?$wgLang->date(wfTimestampNow()):$wgLang->date($cutoff2));
				$log->addEntry('unpatrol', $title, $msg);
			}
		}
		
		return $count;
	}
}
