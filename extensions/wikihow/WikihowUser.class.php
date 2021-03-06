<?php

if ( !defined('MEDIAWIKI') ) die();

class WikihowUser extends User {

	/**
	 * Factory method to fetch user obj via an email address.
	 *
	 * @param $addr (string) the email address
	 * @return array($user, $count) Returns an array where the 1st 
	 *   parameter is the new User object (or null if there is not 
	 *   precisely 1 user account with that email address), and
	 *   the 2nd parameter is the number of user account with that email
	 *   address attached
	 */
	static function newFromEmailAddress( $addr ) {
		$result = self::getEmailCount($addr);
		$u = null;
		if ($result && $result['count'] == 1) {
			$u = new User;
			$u->mName = $result['user_name'];
			$u->mId = $result['user_id'];
			$u->mFrom = 'name';
		}
		return array($u, $result['count']);
	}

	/**
	 * Return the count of the number of times this email address has been
	 * registered, and the username associated with the email address.
	 */
	private static function getEmailCount($addr) {
		$addr = trim($addr);
		if (!$addr) {
			return array(0, '');
		}

		$dbr = wfGetDB( DB_SLAVE );
		$row = $dbr->selectRow(
			'user',
			array('count(*) as count', 'user_name', 'user_id'),
			array('user_email' => $addr),
			__METHOD__
		);
		return (array)$row;
	}

	static function getUsernameFromTitle($title) {
		$real_name = '';
		$username = $title->getText();
		$username = preg_replace('@/.*$@', '', $username); // strip trailing '/...'
		$user = User::newFromName($username);
		if ($user) {
			$real_name = $user->getRealName();
			if (!$real_name) $real_name = $username;
		}
		return array($user, $username, $real_name);
	}

	static function createTemporaryUser($real_name, $email) {
		$user = new User();

		$maxid = User::getMaxID();
		$anonid = $maxid + 1;
		$username = "Anonymous$anonid";

		$user->setName($username);
		$real_name = strip_tags($real_name);

		// make sure this hasn't already been created
		while ($user->idForName() > 0) {
			$anonid = rand(0, 100000);
			$username = "Anonymous$anonid";
			$user->setName($username);
		}

		if ($real_name) {
			$user->setRealName($real_name);
		} else {
			$user->setRealName("Anonymous");
		}

		if ($email) {
			$user->setEmail($email);
		}

		$user->setPassword(WH_ANON_USER_PASSWORD);
		$user->setOption("disablemail", 1);
		$user->addToDatabase();
		return $user;
	}

	static function getAuthorStats($userName) {
		$u = User::newFromName($userName);
		if ($u)
			$u->load();
		else
			return 0;
		return $u->mEditCount;
	}

	static function getBotIDs() {
		global $wgMemc;
		static $botsCached = null;

		if ($botsCached) return $botsCached;

		$key = wfMemcKey('botids');
		$bots = $wgMemc->get($key);
		if (!is_array($bots)) {
			$bots = array();
			$dbr = wfGetDB(DB_SLAVE);
			$res = $dbr->select('user_groups', array('ug_user'), array('ug_group'=>'bot'));
			while ($row = $dbr->fetchObject($res)) {
				$bots[] = $row->ug_user;
			}
			$wgMemc->set($key, $bots);
		}
		$botsCached = $bots;
		return $bots;
	}
	
	static function isGPlusAuthor($userName) {
		$u = User::newFromName($userName);
		if ($u)
			$u->load();
		else
			return 0;
			
		if ($u->isGPlusUser() && $u->getOption('show_google_authorship')) {
			return true;
		}
		else {
			return false;
		}
	}

}

