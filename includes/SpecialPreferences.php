<?php
/**
 * Hold things related to displaying and saving user preferences.
 * @addtogroup SpecialPage
 */

/**
 * Entry point that create the "Preferences" object
 */
function wfSpecialPreferences() {
	global $wgRequest, $wgHooks;

	$wgHooks["pageTabs"][] = array("wfPreferencesTabs");

	$form = new PreferencesForm( $wgRequest );
	$form->execute();
}

function wfPreferencesTabs(&$tabsArray) {
	$tab1->href = "#prefsection-0";
	$tab1->title = "User profile";
	$tab1->id = "tab_prefsection-0";
	$tab1->text = "User profile";
	$tab1->onclick = "return uncoversection(this)";
	$tab1->class = "on";
	$tabsArray[] = $tab1;

	$tab2->href = "#prefsection-1";
	$tab2->title = "Files";
	$tab2->id = "tab_prefsection-1";
	$tab2->text = "Files";
	$tab2->onclick = "return uncoversection(this);";
	$tabsArray[] = $tab2;

	$tab3->href = "#prefsection-2";
	$tab3->title = "Date and time";
	$tab3->id = "tab_prefsection-2";
	$tab3->text = "Date and time";
	$tab3->onclick = "return uncoversection(this)";
	$tabsArray[] = $tab3;

	$tab4->href = "#prefsection-3";
	$tab4->title = "Editing";
	$tab4->id = "tab_prefsection-3";
	$tab4->text = "Editing";
	$tab4->onclick = "return uncoversection(this)";
	$tabsArray[] = $tab4;

	$tab5->href = "#prefsection-4";
	$tab5->title = "Recent changes";
	$tab5->id = "tab_prefsection-4";
	$tab5->text = "Recent changes";
	$tab5->onclick = "return uncoversection(this)";
	$tabsArray[] = $tab5;

	$tab6->href = "#prefsection-5";
	$tab6->title = "Watchlist";
	$tab6->id = "tab_prefsection-5";
	$tab6->text = "Watchlist";
	$tab6->onclick = "return uncoversection(this)";
	$tabsArray[] = $tab6;

	$tab7->href = "#prefsection-6";
	$tab7->title = "Misc";
	$tab7->id = "tab_prefsection-6";
	$tab7->text = "Misc";
	$tab7->onclick = "return uncoversection(this)";
	$tabsArray[] = $tab7;


	/*$request = $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, 'RequestTopic'), wfMsg('requesttopic'));
	$request = preg_replace('@^<a@', '<a class="notab" style="float:right; margin-right:10px; font-size:1em; width:auto; font-weight:normal;"', $request);
	$tabs .= $request;*/

	return true;
}

/**
 * Preferences form handling
 * This object will show the preferences form and can save it as well.
 * @addtogroup SpecialPage
 */
class PreferencesForm {
	var $mQuickbar, $mOldpass, $mNewpass, $mRetypePass, $mStubs;
	var $mRows, $mCols, $mSkin, $mMath, $mDate, $mUserEmail, $mEmailFlag, $mNick;
	var $mUserLanguage, $mUserVariant;
	var $mRecent, $mRecentDays, $mHourDiff, $mAction;
	var $mReset, $mPosted, $mToggles, $mRealName, $mImageSize;
	var $mUnderline, $mWatchlistEdits;

	//XXADDED
	var $mDefaultEditor, $mIgnoreFanMail, $mScrollTalk, $mHidePersistantSaveBar, $mMarketingEmailFlag, $mAuthorEmailNotifications, $mUserTalkNotifications;
	var $mThumbsNotifications, $mThumbsEmailNotifications, $mRCTest;

	/**
	 * Constructor
	 * Load some values
	 */
	function PreferencesForm( &$request ) {
		global $wgContLang, $wgUser, $wgAllowRealName;

		$this->mQuickbar = $request->getVal( 'wpQuickbar' );
		$this->mOldpass = $request->getVal( 'wpOldpass' );
		$this->mNewpass = $request->getVal( 'wpNewpass' );
		$this->mRetypePass =$request->getVal( 'wpRetypePass' );
		$this->mStubs = $request->getVal( 'wpStubs' );
		$this->mRows = $request->getVal( 'wpRows' );
		$this->mCols = $request->getVal( 'wpCols' );
		$this->mSkin = $request->getVal( 'wpSkin' );
		$this->mMath = $request->getVal( 'wpMath' );
		$this->mDate = $request->getVal( 'wpDate' );
		$this->mUserEmail = $request->getVal( 'wpUserEmail' );
		$this->mRealName = $wgAllowRealName ? strip_tags( $request->getVal( 'wpRealName' ) ) : '';
		$this->mEmailFlag = $request->getCheck( 'wpEmailFlag' ) ? 0 : 1;
		//XX ADDED for welcome/marketing emails
		$this->mMarketingEmailFlag = $request->getCheck( 'wpMarketingEmailFlag' ) ? 0 : 1;
		$this->mAuthorEmailNotifications = $request->getCheck( 'wpAuthorEmailNotifications' ) ? 0 : 1;
		$this->mUserTalkNotifications = $request->getCheck( 'wpUserTalkNotifications' ) ? 0 : 1;
		if (class_exists('ThumbsUp')) {
			$this->mThumbsNotifications = $request->getCheck( 'wpThumbsNotifications' ) ? 0 : 1;
			$this->mThumbsEmailNotifications = $request->getCheck( 'wpThumbsEmailNotifications' ) ? 0 : 1;
		}
		if (class_exists('RCTest')) {
			$userGroups = $wgUser->getGroups();
			if (in_array('staff', $userGroups) || in_array('sysop', $userGroups) || in_array('newarticlepatrol', $userGroups)) {
				$this->mRCTest = $request->getCheck( 'wpOprctest' ) ? 0 : 1;
			} else {
				// Preference is always on for people not in staff, admin or nap group
				$this->mRCTest = 0;
			}
		}

		$this->mNick = $request->getVal( 'wpNick' );
		$this->mUserLanguage = $request->getVal( 'wpUserLanguage' );
		$this->mUserVariant = $request->getVal( 'wpUserVariant' );
		$this->mContentFilter = $request->getVal( 'wpContentFilter' );
		$this->mRecent = $request->getVal( 'wpRecent' );
		$this->mRecentDays = $request->getVal( 'wpRecentDays' );
		$this->mHourDiff = $request->getVal( 'wpHourDiff' );
		$this->mImageSize = $request->getVal( 'wpImageSize' );
		$this->mThumbSize = $request->getInt( 'wpThumbSize' );
		$this->mUnderline = $request->getInt( 'wpOpunderline' );
		$this->mAction = $request->getVal( 'action' );
		$this->mReset = $request->getCheck( 'wpReset' );
		$this->mPosted = $request->wasPosted();
		$this->mSuccess = $request->getCheck( 'success' );
		$this->mWatchlistDays = $request->getVal( 'wpWatchlistDays' );
		$this->mWatchlistEdits = $request->getVal( 'wpWatchlistEdits' );

		$this->mSaveprefs = $request->getCheck( 'wpSaveprefs' ) &&
			$this->mPosted &&
			$wgUser->matchEditToken( $request->getVal( 'wpEditToken' ) );

        // XXADDED
		$this->mDefaultEditor = $request->getVal( 'wpDefaultEditor' );
        $this->mDisableWarning = $request->getVal('wpOpdisablewarning');
		$this->mHidePersistantSaveBar = $request->getVal("wpOphidepersistantsavebar");
		$this->mIgnoreFanMail = $request->getVal('wpOpignorefanmail');
		$this->mScrollTalk = $request->getVal('wpOpscrolltalk');

		# User toggles  (the big ugly unsorted list of checkboxes)
		$this->mToggles = array();
		if ( $this->mPosted ) {
			$togs = User::getToggles();
			foreach ( $togs as $tname ) {
				$this->mToggles[$tname] = $request->getCheck( "wpOp$tname" ) ? 1 : 0;
			}
		}
		$this->mUsedToggles = array();

		# Validate language
		if ( !preg_match( '/^[a-z\-]*$/', $this->mUserLanguage ) ) {
			$this->mUserLanguage = 'nolanguage';
		}

		wfRunHooks( 'InitPreferencesForm', array( $this, $request ) );
	}

	function execute() {
		global $wgUser, $wgOut;

		if ( $wgUser->isAnon() ) {
			$wgOut->showErrorPage( 'prefsnologin', 'prefsnologintext' );
			return;
		}
		if ( wfReadOnly() ) {
			$wgOut->readOnlyPage();
			return;
		}
		$wgOut->addScript("<script src='/skins/common/preferences.js'></script>");
		if ( $this->mReset ) {
			$this->resetPrefs();
			$this->mainPrefsForm( 'reset', wfMsg( 'prefsreset' ) );
		} else if ( $this->mSaveprefs ) {
			$this->savePreferences();
		} else {
			$this->resetPrefs();
			$this->mainPrefsForm( '' );
		}
	}
	/**
	 * @access private
	 */
	function validateInt( &$val, $min=0, $max=0x7fffffff ) {
		$val = intval($val);
		$val = min($val, $max);
		$val = max($val, $min);
		return $val;
	}

	/**
	 * @access private
	 */
	function validateFloat( &$val, $min, $max=0x7fffffff ) {
		$val = floatval( $val );
		$val = min( $val, $max );
		$val = max( $val, $min );
		return( $val );
	}

	/**
	 * @access private
	 */
	function validateIntOrNull( &$val, $min=0, $max=0x7fffffff ) {
		$val = trim($val);
		if($val === '') {
			return $val;
		} else {
			return $this->validateInt( $val, $min, $max );
		}
	}

	/**
	 * @access private
	 */
	function validateDate( $val ) {
		global $wgLang, $wgContLang;
		if ( $val !== false && (
			in_array( $val, (array)$wgLang->getDatePreferences() ) ||
			in_array( $val, (array)$wgContLang->getDatePreferences() ) ) ) 
		{
			return $val;
		} else {
			return $wgLang->getDefaultDateFormat();
		}
	}

	/**
	 * Used to validate the user inputed timezone before saving it as
	 * 'timecorrection', will return '00:00' if fed bogus data.
	 * Note: It's not a 100% correct implementation timezone-wise, it will
	 * accept stuff like '14:30',
	 * @access private
	 * @param string $s the user input
	 * @return string
	 */
	function validateTimeZone( $s ) {
		if ( $s !== '' ) {
			if ( strpos( $s, ':' ) ) {
				# HH:MM
				$array = explode( ':' , $s );
				$hour = intval( $array[0] );
				$minute = intval( $array[1] );
			} else {
				$minute = intval( $s * 60 );
				$hour = intval( $minute / 60 );
				$minute = abs( $minute ) % 60;
			}
			# Max is +14:00 and min is -12:00, see:
			# http://en.wikipedia.org/wiki/Timezone
			$hour = min( $hour, 14 );
			$hour = max( $hour, -12 );
			$minute = min( $minute, 59 );
			$minute = max( $minute, 0 );
			$s = sprintf( "%02d:%02d", $hour, $minute );
		}
		return $s;
	}

	/**
	 * @access private
	 */
	function savePreferences() {
		global $wgUser, $wgOut, $wgParser;
		global $wgEnableUserEmail, $wgEnableEmail;
		global $wgEmailAuthentication, $wgRCMaxAge;
		global $wgAuth, $wgEmailConfirmToEdit;


		if ( '' != $this->mNewpass && $wgAuth->allowPasswordChange() ) {
			if ( $this->mNewpass != $this->mRetypePass ) {
				wfRunHooks( 'PrefsPasswordAudit', array( $wgUser, $this->mNewpass, 'badretype' ) );
				$this->mainPrefsForm( 'error', wfMsg( 'badretype' ) );
				return;
			}

			if (!$wgUser->checkPassword( $this->mOldpass )) {
				wfRunHooks( 'PrefsPasswordAudit', array( $wgUser, $this->mNewpass, 'wrongpassword' ) );
				$this->mainPrefsForm( 'error', wfMsg( 'wrongpassword' ) );
				return;
			}
			
			try {
				$wgUser->setPassword( $this->mNewpass );
				wfRunHooks( 'PrefsPasswordAudit', array( $wgUser, $this->mNewpass, 'success' ) );
				$this->mNewpass = $this->mOldpass = $this->mRetypePass = '';
			} catch( PasswordError $e ) {
				wfRunHooks( 'PrefsPasswordAudit', array( $wgUser, $this->mNewpass, 'error' ) );
				$this->mainPrefsForm( 'error', $e->getMessage() );
				return;
			}
		}
		$wgUser->setRealName( $this->mRealName );

		if( $wgUser->getOption( 'language' ) !== $this->mUserLanguage ) {
			$needRedirect = true;
		} else {
			$needRedirect = false;
		}

		# Validate the signature and clean it up as needed
		global $wgMaxSigChars;
		if( mb_strlen( $this->mNick ) > $wgMaxSigChars ) {
			global $wgLang;
			$this->mainPrefsForm( 'error',
				wfMsg( 'badsiglength', $wgLang->formatNum( $wgMaxSigChars ) ) );
			return;
		} elseif( $this->mToggles['fancysig'] ) {
			if( $wgParser->validateSig( $this->mNick ) !== false ) {
				$this->mNick = $wgParser->cleanSig( $this->mNick );
			} else {
				$this->mainPrefsForm( 'error', wfMsg( 'badsig' ) );
				return;
			}
		} else {
			// When no fancy sig used, make sure ~{3,5} get removed.
			$this->mNick = $wgParser->cleanSigInSig( $this->mNick );
		}

		$wgUser->setOption( 'language', $this->mUserLanguage );
		$wgUser->setOption( 'variant', $this->mUserVariant );
		$wgUser->setOption( 'contentfilter', $this->mContentFilter);
		$wgUser->setOption( 'nickname', $this->mNick );
		$wgUser->setOption( 'quickbar', $this->mQuickbar );
		$wgUser->setOption( 'skin', $this->mSkin );
		global $wgUseTeX;
		if( $wgUseTeX ) {
			$wgUser->setOption( 'math', $this->mMath );
		}
		$wgUser->setOption( 'date', $this->validateDate( $this->mDate ) );
		$wgUser->setOption( 'rclimit', $this->validateIntOrNull( $this->mRecent ) );
		$wgUser->setOption( 'rcdays', $this->validateInt($this->mRecentDays, 1, ceil($wgRCMaxAge / (3600*24))));
		$wgUser->setOption( 'wllimit', $this->validateIntOrNull( $this->mWatchlistEdits, 0, 1000 ) );
		$wgUser->setOption( 'rows', $this->validateInt( $this->mRows, 4, 1000 ) );
		$wgUser->setOption( 'cols', $this->validateInt( $this->mCols, 4, 1000 ) );
		$wgUser->setOption( 'stubthreshold', $this->validateIntOrNull( $this->mStubs ) );
		$wgUser->setOption( 'timecorrection', $this->validateTimeZone( $this->mHourDiff, -12, 14 ) );
		$wgUser->setOption( 'imagesize', $this->mImageSize );
		$wgUser->setOption( 'thumbsize', $this->mThumbSize );
		$wgUser->setOption( 'underline', $this->validateInt($this->mUnderline, 0, 2) );
		$wgUser->setOption( 'watchlistdays', $this->validateFloat( $this->mWatchlistDays, 0, 7 ) );

		if (class_exists('ThumbsUp')) {
			$wgUser->setOption( "thumbsnotifications", $this->mThumbsNotifications);
			$wgUser->setOption( "thumbsemailnotifications", $this->mThumbsEmailNotifications);
		}
		if (class_exists('RCTest')) {
			$wgUser->setOption( "rctest", $this->mRCTest);
		}
        // XXADDED
        $wgUser->setOption( "defaulteditor", $this->mDefaultEditor);
        $wgUser->setOption( "disablewarning", $this->mDisableWarning);
		$wgUser->setOption( "ignorefanmail", $this->mIgnoreFanMail);
		$wgUser->setOption( "hidepersistantsavebar", $this->mHidePersistantSaveBar);
		$wgUser->setOption( "scrolltalk", $this->mScrollTalk);


		if( $wgEnableEmail && $wgEnableUserEmail ) {
			$wgUser->setOption( 'disablemail', $this->mEmailFlag );
			//XXADDED Marketing emails
			$wgUser->setOption( 'disablemarketingemail', $this->mMarketingEmailFlag );
			$wgUser->setOption( 'enableauthoremail', $this->mAuthorEmailNotifications );
			$wgUser->setOption( 'usertalknotifications', $this->mUserTalkNotifications );
		}

		# Set user toggles
		foreach ( $this->mToggles as $tname => $tvalue ) {
			$wgUser->setOption( $tname, $tvalue );
		}

		$error = false;
		if( $wgEnableEmail ) {
			$newadr = $this->mUserEmail;
			$oldadr = $wgUser->getEmail();
			if( ($newadr != '') && ($newadr != $oldadr) ) {
				# the user has supplied a new email address on the login page
				if( $wgUser->isValidEmailAddr( $newadr ) ) {
					$wgUser->mEmail = $newadr; # new behaviour: set this new emailaddr from login-page into user database record
					$wgUser->mEmailAuthenticated = null; # but flag as "dirty" = unauthenticated
					if ($wgEmailAuthentication) {
						# Mail a temporary password to the dirty address.
						# User can come back through the confirmation URL to re-enable email.
						$result = $wgUser->sendConfirmationMail();
						if( WikiError::isError( $result ) ) {
							$error = wfMsg( 'mailerror', htmlspecialchars( $result->getMessage() ) );
						} else {
							$error = wfMsg( 'eauthentsent', $wgUser->getName() );
						}
					}
				} else {
					$error = wfMsg( 'invalidemailaddress' );
				}
			} else {
				if( $wgEmailConfirmToEdit && empty( $newadr ) ) {
					$this->mainPrefsForm( 'error', wfMsg( 'noemailtitle' ) );
					return;
				}
				$wgUser->setEmail( $this->mUserEmail );
			}
			if( $oldadr != $newadr ) {
				wfRunHooks( 'PrefsEmailAudit', array( $wgUser, $oldadr, $newadr ) );
			}
		}

		if (!$wgAuth->updateExternalDB($wgUser)) {
			$this->mainPrefsForm( 'error', wfMsg( 'externaldberror' ) );
			return;
		}

		$msg = '';
		if ( !wfRunHooks( 'SavePreferences', array( $this, $wgUser, &$msg ) ) ) {
			print "(($msg))";
			$this->mainPrefsForm( 'error', $msg );
			return;
		}

		$wgUser->setCookies();
		$wgUser->saveSettings();

		if( $needRedirect && $error === false ) {
			$title = SpecialPage::getTitleFor( 'Preferences' );
			$wgOut->redirect($title->getFullURL('success'));
			return;
		}

		$wgOut->setParserOptions( ParserOptions::newFromUser( $wgUser ) );
		$this->mainPrefsForm( $error === false ? 'success' : 'error', $error);
	}

	/**
	 * @access private
	 */
	function resetPrefs() {
		global $wgUser, $wgLang, $wgContLang, $wgContLanguageCode, $wgAllowRealName;

		$this->mOldpass = $this->mNewpass = $this->mRetypePass = '';
		$this->mUserEmail = $wgUser->getEmail();
		$this->mUserEmailAuthenticationtimestamp = $wgUser->getEmailAuthenticationtimestamp();
		$this->mRealName = ($wgAllowRealName) ? $wgUser->getRealName() : '';

		# language value might be blank, default to content language
		$this->mUserLanguage = $wgUser->getOption( 'language', $wgContLanguageCode );

		$this->mUserVariant = $wgUser->getOption( 'variant');
		$this->mContentFilter= $wgUser->getOption( 'contentfilter', 0);
		$this->mEmailFlag = $wgUser->getOption( 'disablemail' ) == 1 ? 1 : 0;
		//XXADDED Marketing emails
		$this->mMarketingEmailFlag = $wgUser->getOption( 'disablemarketingemail' ) == 1 ? 1 : 0;
		$this->mAuthorEmailNotifications = $wgUser->getOption( 'enableauthoremail' ) == 1 ? 1 : 0;
		$this->mUserTalkNotifications = $wgUser->getOption( 'usertalknotifications' );
		if (class_exists('ThumbsUp')) {
			$this->mThumbsNotifications = $wgUser->getOption( 'thumbsnotifications' );
			$this->mThumbsEmailNotifications = $wgUser->getOption( 'thumbsemailnotifications' );
		}
		if (class_exists('RCTest')) {
			$this->mRCTest = $wgUser->getOption( 'rctest' );
		}
		$this->mNick = $wgUser->getOption( 'nickname' );

		$this->mQuickbar = $wgUser->getOption( 'quickbar' );
		$this->mSkin = Skin::normalizeKey( $wgUser->getOption( 'skin' ) );
		$this->mMath = $wgUser->getOption( 'math' );
		$this->mDate = $wgUser->getDatePreference();
		$this->mRows = $wgUser->getOption( 'rows' );
		$this->mCols = $wgUser->getOption( 'cols' );
		$this->mStubs = $wgUser->getOption( 'stubthreshold' );
		$this->mHourDiff = $wgUser->getOption( 'timecorrection' );
		$this->mImageSize = $wgUser->getOption( 'imagesize' );
		$this->mThumbSize = $wgUser->getOption( 'thumbsize' );
		$this->mRecent = $wgUser->getOption( 'rclimit' );
		$this->mRecentDays = $wgUser->getOption( 'rcdays' );
		$this->mWatchlistEdits = $wgUser->getOption( 'wllimit' );
		$this->mUnderline = $wgUser->getOption( 'underline' );
		$this->mWatchlistDays = $wgUser->getOption( 'watchlistdays' );

        // XXADDED
		$this->mDefaultEditor = $wgUser->getOption("defaulteditor");
		$this->mIgnoreFanMail = $wgUser->getOption('ignorefanmail');
		$this->mScrollTalk = $wgUser->getOption('scrolltalk');
		$this->mHidePersistantSaveBar= $wgUser->getOption('hidepersistantsavebar');

		$togs = User::getToggles();
		foreach ( $togs as $tname ) {
			$this->mToggles[$tname] = $wgUser->getOption( $tname );
		}

		wfRunHooks( 'ResetPreferences', array( $this, $wgUser ) );
	}

	function getToggle( $tname, $trailer = false, $disabled = false, $selected = null ) {
		global $wgUser, $wgLang;

		$this->mUsedToggles[$tname] = true;
		$ttext = $wgLang->getUserToggle( $tname );

		// Added by Jordan.  Allow check override with the $selected variable. Use this for options where 0 represents a checked/on value
		$check = !is_null($selected) ? $selected : $wgUser->getOption($tname) == 1;
		$checked = $check ? ' checked="checked"' : '';
		$disabled = $disabled ? ' disabled="disabled"' : '';
		$trailer = $trailer ? $trailer : '';
		return "<div class='toggle'><input type='checkbox' class='input_med' value='1' id=\"$tname\" name=\"wpOp$tname\"$checked$disabled />" .
			" <span class='toggletext'><label class='normal_font' for=\"$tname\">$ttext</label>$trailer</span></div>\n";
	}

	function getToggles( $items ) {
		$out = "";
		foreach( $items as $item ) {
			if( $item === false )
				continue;
			if( is_array( $item ) ) {
				list( $key, $trailer ) = $item;
			} else {
				$key = $item;
				$trailer = false;
			}
			$out .= $this->getToggle( $key, $trailer );
		}
		return $out;
	}

	function addRow($td1, $td2) {
		return "<tr><td class='label' align='right'>$td1</td><td align='left'>$td2</td></tr>";
	}

	/**
	 * Helper function for user information panel
	 * @param $td1 label for an item
	 * @param $td2 item or null
	 * @param $td3 optional help or null
	 * @return xhtml block
	 */
	function tableRow( $td1, $td2 = null, $td3 = null ) {
		global $wgContLang;

		$align['align'] = $wgContLang->isRtl() ? 'right' : 'left';

		if ( is_null( $td3 ) ) {
			$td3 = '';
		} else {
			$td3 = Xml::tags( 'tr', null,
				Xml::tags( 'td', array( 'colspan' => '2'), $td3 )
			);
		}

		if ( is_null( $td2 ) ) {
			$td1 = Xml::tags( 'td', $align + array( 'colspan' => '2', 'class' => 'label'), $td1 );
			$td2 = '';
		} else {
			$td1 = Xml::tags( 'td', $align + array('class' => 'label'), $td1 );
			$td2 = Xml::tags( 'td', $align, $td2 );
		}

		return Xml::tags( 'tr', null, $td1 . $td2 ). $td3 . "\n";
	
	}

	/**
	 * @access private
	 */
	function mainPrefsForm( $status , $message = '' ) {
		global $wgUser, $wgOut, $wgLang, $wgContLang;
		global $wgAllowRealName, $wgImageLimits, $wgThumbLimits;
		global $wgDisableLangConversion;
		global $wgEnotifWatchlist, $wgEnotifUserTalk,$wgEnotifMinorEdits;
		global $wgRCShowWatchingUsers, $wgEnotifRevealEditorAddress;
		global $wgEnableEmail, $wgEnableUserEmail, $wgEmailAuthentication;
		global $wgContLanguageCode, $wgDefaultSkin, $wgSkipSkins, $wgAuth;
		global $wgEmailConfirmToEdit;

		$wgOut->setPageTitle( wfMsg( 'preferences' ) );
		$wgOut->setArticleRelated( false );
		$wgOut->setRobotpolicy( 'noindex,nofollow' );

		$wgOut->disallowUserJs();  # Prevent hijacked user scripts from sniffing passwords etc.

		if ( $this->mSuccess || 'success' == $status ) {
			$wgOut->wrapWikiMsg( '<div class="successbox"><strong>$1</strong></div>', 'savedprefs' );
		} else	if ( 'error' == $status ) {
			$wgOut->addWikiText( '<div class="errorbox"><strong>' . $message  . '</strong></div>' );
		} else if ( '' != $status ) {
			$wgOut->addWikiText( $message . "\n----" );
		}

		$qbs = $wgLang->getQuickbarSettings();
		$skinNames = $wgLang->getSkinNames();
		$mathopts = $wgLang->getMathNames();
		$dateopts = $wgLang->getDatePreferences();
		$togs = User::getToggles();

		$titleObj = SpecialPage::getTitleFor( 'Preferences' );
		$action = $titleObj->escapeLocalURL();

		# Pre-expire some toggles so they won't show if disabled
		$this->mUsedToggles[ 'shownumberswatching' ] = true;
		$this->mUsedToggles[ 'showupdated' ] = true;
		$this->mUsedToggles[ 'enotifwatchlistpages' ] = true;
		$this->mUsedToggles[ 'enotifusertalkpages' ] = true;
		$this->mUsedToggles[ 'enotifminoredits' ] = true;
		$this->mUsedToggles[ 'enotifrevealaddr' ] = true;
		$this->mUsedToggles[ 'ccmeonemails' ] = true;
		$this->mUsedToggles[ 'uselivepreview' ] = true;


		if ( !$this->mEmailFlag ) { $emfc = 'checked="checked"'; }
		else { $emfc = ''; }

		//XXADDED Marketing emails
		if ( !$this->mMarketingEmailFlag ) { $memfc = 'checked="checked"'; }
		else { $memfc = ''; }
		if ( !$this->mAuthorEmailNotifications ) { $auth_emfc = 'checked="checked"'; }
		else { $auth_emfc = ''; }
		if ( $this->mUserTalkNotifications == '0' ) { $ut_emfc = 'checked="checked"'; }
		else { $ut_emfc = ''; }
		
		if (class_exists('ThumbsUp')) {
			if (intval($this->mThumbsNotifications) === 0 ) { $thumbs_checked = 'checked="checked"'; }
			else { $thumbs_checked = ''; }
			if ( intval($this->mThumbsEmailNotifications) === 0 ) { $thumbs_email_checked = 'checked="checked"'; }
			else { $thumbs_email_checked = ''; }
		}

		if ($wgEmailAuthentication && ($this->mUserEmail != '') ) {
			if( $wgUser->getEmailAuthenticationTimestamp() ) {
				$emailauthenticated = wfMsg('emailauthenticated',$wgLang->timeanddate($wgUser->getEmailAuthenticationTimestamp(), true ) ).'<br />';
				$disableEmailPrefs = false;
			} else {
				$disableEmailPrefs = true;
				$skin = $wgUser->getSkin();
				$emailauthenticated = wfMsg('emailnotauthenticated').'<br />' .
					$skin->makeKnownLinkObj( SpecialPage::getTitleFor( 'Confirmemail' ),
						wfMsg( 'emailconfirmlink' ) ) . '<br />';
			}
		} else {
			$emailauthenticated = '';
			$disableEmailPrefs = false;
		}

		if ($this->mUserEmail == '') {
			$emailauthenticated = wfMsg( 'noemailprefs' ) . '<br />';
		}


		$enotifwatchlistpages = ($wgEnotifWatchlist) ? $this->getToggle( 'enotifwatchlistpages', false, $disableEmailPrefs ) : '';
		$enotifusertalkpages = ($wgEnotifUserTalk) ? $this->getToggle( 'enotifusertalkpages', false, $disableEmailPrefs ) : '';
		$enotifminoredits = ($wgEnotifWatchlist && $wgEnotifMinorEdits) ? $this->getToggle( 'enotifminoredits', false, $disableEmailPrefs ) : '';
		$enotifrevealaddr = (($wgEnotifWatchlist || $wgEnotifUserTalk) && $wgEnotifRevealEditorAddress) ? $this->getToggle( 'enotifrevealaddr', false, $disableEmailPrefs ) : '';

		# </FIXME>

		$wgOut->addHTML( "<form action=\"$action\" method='post'>" );
		$wgOut->addHTML( "<div id='preferences'>" );

		# User data

		$wgOut->addHTML(
			Xml::openElement( 'fieldset ' , array('id' => 'prefsection-0', 'class' => 'prefsection')) .
			"<legend class='mainLegend'>&nbsp;</legend>" .
			Xml::openElement( 'table' ) .
			$this->tableRow( Xml::element( 'legend', null, wfMsg( 'prefs-personal' ) ) )
		);

		$userInformationHtml =
			$this->tableRow( wfMsgHtml( 'username' ), htmlspecialchars( $wgUser->getName() ) ) .
			$this->tableRow( wfMsgHtml( 'uid' ), htmlspecialchars( $wgUser->getID() ) ) .
			$this->tableRow(
				wfMsgHtml( 'prefs-edits' ),
				$wgLang->formatNum( User::edits( $wgUser->getId() ) )
			);

		if( wfRunHooks( 'PreferencesUserInformationPanel', array( $this, &$userInformationHtml ) ) ) {
			$wgOut->addHtml( $userInformationHtml );
		}

		if ( $wgAllowRealName ) {
			$wgOut->addHTML(
				$this->tableRow(
					Xml::label( wfMsg('yourrealname'), 'wpRealName' ),
					Xml::input( 'wpRealName', 25, $this->mRealName, array( 'class' => 'input_med', 'id' => 'wpRealName' ) ),
					Xml::tags('div', array( 'class' => 'prefsectiontip' ),
						wfMsgExt( 'prefs-help-realname', 'parseinline' )
					)
				)
			);
		}
		if ( $wgEnableEmail ) {
			$wgOut->addHTML(
				$this->tableRow(
					Xml::label( wfMsg('youremail'), 'wpUserEmail' ),
					Xml::input( 'wpUserEmail', 25, $this->mUserEmail, array( 'class' => 'input_med', 'id' => 'wpUserEmail' ) )
				)
			);
		}

		global $wgParser, $wgMaxSigChars;
		if( mb_strlen( $this->mNick ) > $wgMaxSigChars ) {
			$invalidSig = $this->tableRow(
				'&nbsp;',
				Xml::element( 'span', array( 'class' => 'error' ),
					wfMsg( 'badsiglength', $wgLang->formatNum( $wgMaxSigChars ) ) )
			);
		} elseif( !empty( $this->mToggles['fancysig'] ) &&
			false === $wgParser->validateSig( $this->mNick ) ) {
			$invalidSig = $this->tableRow(
				'&nbsp;',
				Xml::element( 'span', array( 'class' => 'error' ), wfMsg( 'badsig' ) )
			);
		} else {
			$invalidSig = '';
		}

		$wgOut->addHTML(
			$this->tableRow(
				Xml::label( wfMsg( 'yournick' ), 'wpNick' ),
				Xml::input( 'wpNick', 25, $this->mNick,
					array(
						'id' => 'wpNick',
						'class' => 'input_med',
						// Note: $wgMaxSigChars is enforced in Unicode characters,
						// both on the backend and now in the browser.
						// Badly-behaved requests may still try to submit
						// an overlong string, however.
						'maxlength' => $wgMaxSigChars ) )
			) .
			$invalidSig .
			$this->tableRow( '&nbsp;', $this->getToggle( 'fancysig' ) )
		);

		list( $lsLabel, $lsSelect) = Xml::languageSelector( $this->mUserLanguage );
		$wgOut->addHTML(
			$this->tableRow( $lsLabel, $lsSelect )
		);

		/* see if there are multiple language variants to choose from*/
		if(!$wgDisableLangConversion) {
			$variants = $wgContLang->getVariants();
			$variantArray = array();

			$languages = Language::getLanguageNames( true );
			foreach($variants as $v) {
				$v = str_replace( '_', '-', strtolower($v));
				if( array_key_exists( $v, $languages ) ) {
					// If it doesn't have a name, we'll pretend it doesn't exist
					$variantArray[$v] = $languages[$v];
				}
			}

			$options = "\n";
			foreach( $variantArray as $code => $name ) {
				$selected = ($code == $this->mUserVariant);
				$options .= Xml::option( "$code - $name", $code, $selected ) . "\n";
			}

			if(count($variantArray) > 1) {
				$wgOut->addHtml(
					$this->tableRow(
						Xml::label( wfMsg( 'yourvariant' ), 'wpUserVariant' ),
						Xml::tags( 'select',
							array( 'name' => 'wpUserVariant', 'id' => 'wpUserVariant' ),
							$options
						)
					)
				);
			}
		}


		// TEEN FILTER
		$options = Xml::radioLabel( wfMsg('pref_content_preferences_all'),'wpContentFilter', 0 , 'wpContentFilter_0', $this->mContentFilter == 0, array('class' => 'normal_font')) . "<br/><br/>"
			. Xml::radioLabel(wfMsg('pref_content_preferences_young'),'wpContentFilter', 1, 'wpContentFilter_1', $this->mContentFilter == 1, array('class' => 'normal_font')) . "<br/><br/>"
			. Xml::radioLabel(wfMsg('pref_content_preferences_adult'),'wpContentFilter', 2, 'wpContentFilter_2', $this->mContentFilter == 2, array('class' => 'normal_font')) . "<br/><br/>"; 
			
       $wgOut->addHtml( $this->tableRow(wfMsg('pref_content_preferences_info'), $options));
					
		# Password
		if( $wgAuth->allowPasswordChange() ) {	
			$wgOut->addHTML(
				$this->tableRow( Xml::element( 'legend', null, wfMsg( 'changepassword' ) ) ) .
				$this->tableRow(
					Xml::label( wfMsg( 'oldpassword' ), 'wpOldpass' ),
					Xml::password( 'wpOldpass', 25, $this->mOldpass, array( 'id' => 'wpOldpass', 'class' => 'input_med' ) )
				) .
				$this->tableRow(
					Xml::label( wfMsg( 'newpassword' ), 'wpNewpass' ),
					Xml::password( 'wpNewpass', 25, $this->mNewpass, array( 'id' => 'wpNewpass', 'class' => 'input_med'  ) )
				) .
				$this->tableRow(
					Xml::label( wfMsg( 'retypenew' ), 'wpRetypePass' ),
					Xml::password( 'wpRetypePass', 25, $this->mRetypePass, array( 'id' => 'wpRetypePass', 'class' => 'input_med'  ) )
				) .
				Xml::tags( 'tr', null,
					Xml::tags( 'td', array( 'colspan' => '2' ),
						$this->getToggle( "rememberpassword" )
					)
				) .
				Xml::tags( 'tr', null,
					Xml::tags( 'td', array( 'colspan' => '2' ),
						""
					)
				)
			);
		}

		# <FIXME>
		# Enotif
		if ( $wgEnableEmail ) {

			$moreEmail = '';
			$marketingEmail = '';
			$authorEmail = '';
			if ($wgEnableUserEmail) {
				$emf = wfMsg( 'allowemail' );
				$disabled = $disableEmailPrefs ? ' disabled="disabled"' : '';
				$moreEmail =
				"<input type='checkbox' class='input_med' $emfc $disabled value='1' name='wpEmailFlag' id='wpEmailFlag' /> <label class='normal_font' for='wpEmailFlag'>$emf</label>";
				
				//XXADDED Author emails
                                $auth_emf = wfMsg( 'allowauthornotificationdialog' );
                                $authorEmail =
                                "<div><input type='checkbox' class='input_med' $auth_emfc $disabled value='1' name='wpAuthorEmailNotifications' id='wpAuthorEmailNotifications' /> <label class='normal_font' for='wpAuthorEmailNotifications'>$auth_emf</label></div>";

				if (class_exists('ThumbsUp')) {
					// Thumbs up talk notifications
					$thumbs_label = wfMsg( 'allowthumbsupnotifications' );
					$thumbsNotifyField =
					"<div><input type='checkbox' class='input_med' $thumbs_checked value='1' name='wpThumbsNotifications' id='wpThumbsNotifications' /> <label class='normal_font' for='wpThumbsNotifications'>" .
					"$thumbs_label</label></div>";

					// Thumbs up email notifications
					$thumbs_label = wfMsg( 'allowthumbsupemailnotifications' );
					$thumbsEmailNotifyField =
					"<div><input type='checkbox' class='input_med' $thumbs_email_checked value='1' name='wpThumbsEmailNotifications' id='wpThumbsNotifications' /> " . 
					"<label class='normal_font' for='wpThumbsEmailNotifications'>$thumbs_label</label></div>";

					//$thumbsboxoptions = "<div style='border:2px solid #DDD;margin:3px 0 3px 0;padding:3px;'>$thumbsNotifyField</div>\n";
					$thumbsboxoptions = "<div style='margin:3px 0 3px 0;padding:3px;'>$thumbsNotifyField $thumbsEmailNotifyField</div>\n";
				} else {
					$thumbsboxoptions = "";
				}

                                //XXADDED Marketing emails
                                $memf = wfMsg( 'allowmarketingemail' );

                                //XXADDED UserTalk emails
                                $ut_emf = wfMsg( 'usertalknotifications' );
                                $marketingEmail =
                                "<div><input type='checkbox' class='input_med'  $memfc $disabled value='1' name='wpMarketingEmailFlag' id='wpMarketingEmailFlag' /> <label class='normal_font' for='wpMarketingEmailFlag'>$memf</label></div>";
                                $usertalkEmail =
                                "<div><input type='checkbox' class='input_med'  $ut_emfc $disabled value='1' name='wpUserTalkNotifications' id='wpUserTalkNotifications' /> <label class='normal_font' for='wpUserTalkNotifications'>$ut_emf</label></div>";
								$articleEmail =
								"<div><a href='/Special:AuthorEmailNotification'>" . wfMsg('author_emails') . "</a></div>";
                                $emailboxoptions = "<div style='margin:3px 0 3px 0;padding:3px;'>$marketingEmail $usertalkEmail $articleEmail</div>\n";
			}


			$wgOut->addHTML(
				$this->tableRow( Xml::element( 'legend', null, wfMsg( 'email' ) ) ) .
				$this->tableRow(
					$emailboxoptions.
					$thumbsboxoptions.
					$emailauthenticated.
					$enotifrevealaddr.
					$enotifwatchlistpages.
					$enotifusertalkpages.
					$enotifminoredits.
					$moreEmail.
					$authorEmail.
					$this->getToggle( 'ccmeonemails' )
				)
			);
		}
		# </FIXME>

		$wgOut->addHTML(
			Xml::closeElement( 'table' ) .
			Xml::closeElement( 'fieldset' )
		);


		# Quickbar
		#
		if ($this->mSkin == 'cologneblue' || $this->mSkin == 'standard') {
			$wgOut->addHtml( "<fieldset>\n<legend>" . wfMsg( 'qbsettings' ) . "</legend>\n" );
			for ( $i = 0; $i < count( $qbs ); ++$i ) {
				if ( $i == $this->mQuickbar ) { $checked = ' checked="checked"'; }
				else { $checked = ""; }
				$wgOut->addHTML( "<div><label><input type='radio' class='input_med' name='wpQuickbar' value=\"$i\"$checked />{$qbs[$i]}</label></div>\n" );
			}
			$wgOut->addHtml( "</fieldset>\n\n" );
		} else {
			# Need to output a hidden option even if the relevant skin is not in use,
			# otherwise the preference will get reset to 0 on submit
			$wgOut->addHtml( wfHidden( 'wpQuickbar', $this->mQuickbar ) );
		}
/*
		# Skin
		#
		$wgOut->addHTML( "<fieldset>\n<h2>\n" . wfMsg('skin') . "</h2>\n" );
		$mptitle = Title::newMainPage();
		$previewtext = wfMsg('skinpreview');
		# Only show members of Skin::getSkinNames() rather than
		# $skinNames (skins is all skin names from Language.php)
		$validSkinNames = Skin::getSkinNames();
		# Sort by UI skin name. First though need to update validSkinNames as sometimes
		# the skinkey & UI skinname differ (e.g. "standard" skinkey is "Classic" in the UI).
		foreach ($validSkinNames as $skinkey => & $skinname ) {
			if ( isset( $skinNames[$skinkey] ) )  {
				$skinname = $skinNames[$skinkey];
			}
		}
		asort($validSkinNames);
		foreach ($validSkinNames as $skinkey => $sn ) {
			if ( in_array( $skinkey, $wgSkipSkins ) ) {
				continue;
			}
			$checked = $skinkey == $this->mSkin ? ' checked="checked"' : '';

			$mplink = htmlspecialchars($mptitle->getLocalURL("useskin=$skinkey"));
			$previewlink = "<a target='_blank' href=\"$mplink\">$previewtext</a>";
			if( $skinkey == $wgDefaultSkin )
				$sn .= ' (' . wfMsg( 'default' ) . ')';
			$wgOut->addHTML( "<input type='radio' name='wpSkin' id=\"wpSkin$skinkey\" value=\"$skinkey\"$checked /> <label for=\"wpSkin$skinkey\">{$sn}</label> $previewlink<br />\n" );
		}
		$wgOut->addHTML( "</fieldset>\n\n" );

		# Math
		#
		global $wgUseTeX;
		if( $wgUseTeX ) {
			$wgOut->addHTML( "<fieldset>\n<h2>" . wfMsg('math') . '</h2>' );
			foreach ( $mathopts as $k => $v ) {
				$checked = ($k == $this->mMath);
				$wgOut->addHTML(
					Xml::openElement( 'div' ) .
					Xml::radioLabel( wfMsg( $v ), 'wpMath', $k, "mw-sp-math-$k", $checked ) .
					Xml::closeElement( 'div' ) . "\n"
				);
			}
			$wgOut->addHTML( "</fieldset>\n\n" );
		}
*/
		# Files
		#
		$wgOut->addHTML( "<fieldset id='prefsection-1' class='prefsection'>\n<legend>" . wfMsg( 'files' )  . "</legend>\n");

		$imageLimitOptions = null;
		foreach ( $wgImageLimits as $index => $limits ) {
			$selected = ($index == $this->mImageSize);
			$imageLimitOptions .= Xml::option( "{$limits[0]}×{$limits[1]}" .
				wfMsg('unit-pixel'), $index, $selected );
		}

		$imageSizeId = 'wpImageSize';
		$wgOut->addHTML(
			"<div>" . Xml::label( wfMsg('imagemaxsize'), $imageSizeId ) . " " .
			Xml::openElement( 'select', array( 'name' => $imageSizeId, 'id' => $imageSizeId ) ) .
				$imageLimitOptions .
			Xml::closeElement( 'select' ) . "</div>\n"
		);

		$imageThumbOptions = null;
		foreach ( $wgThumbLimits as $index => $size ) {
			$selected = ($index == $this->mThumbSize);
			$imageThumbOptions .= Xml::option($size . wfMsg('unit-pixel'), $index,
				$selected);
		}

		$thumbSizeId = 'wpThumbSize';
		$wgOut->addHTML(
			"<div>" . Xml::label( wfMsg('thumbsize'), $thumbSizeId ) . " " .
			Xml::openElement( 'select', array( 'name' => $thumbSizeId, 'id' => $thumbSizeId ) ) .
				$imageThumbOptions .
			Xml::closeElement( 'select' ) . "</div>\n"
		);

		$wgOut->addHTML( "</fieldset>\n\n" );

		# Date format
		#
		# Date/Time
		#

		$wgOut->addHTML( "<fieldset id='prefsection-2' class='prefsection'>\n<legend>" . wfMsg( 'datetime' ) . "</legend>\n" );

		if ($dateopts) {
			$wgOut->addHTML( "<fieldset>\n<h5>" . wfMsg( 'dateformat' ) . "</h5>\n" );
			$idCnt = 0;
			$epoch = '20010115161234'; # Wikipedia day
			foreach( $dateopts as $key ) {
				if( $key == 'default' ) {
					$formatted = wfMsgHtml( 'datedefault' );
				} else {
					$formatted = htmlspecialchars( $wgLang->timeanddate( $epoch, false, $key ) );
				}
				($key == $this->mDate) ? $checked = ' checked="checked"' : $checked = '';
				$wgOut->addHTML( "<div><input type='radio' class='input_med' name=\"wpDate\" id=\"wpDate$idCnt\" ".
					"value=\"$key\"$checked /> <label class='normal_font' for=\"wpDate$idCnt\">$formatted</label></div>\n" );
				$idCnt++;
			}
			$wgOut->addHTML( "</fieldset>\n" );
		}

		$nowlocal = $wgLang->time( $now = wfTimestampNow(), true );
		$nowserver = $wgLang->time( $now, false );

		$wgOut->addHTML( '<fieldset><h5>' . wfMsg( 'timezonelegend' ). '</h5><table>' .
		 	$this->addRow( wfMsg( 'servertime' ), $nowserver ) .
			$this->addRow( wfMsg( 'localtime' ), $nowlocal ) .
			$this->addRow(
				'<label for="wpHourDiff">' . wfMsg( 'timezoneoffset' ) . '</label>',
				"<input type='text' class='input_med' name='wpHourDiff' id='wpHourDiff' value=\"" . htmlspecialchars( $this->mHourDiff ) . "\" size='6' />"
			) . "<tr><td colspan='2'>
				<input type='button' class='button' value=\"" . wfMsg( 'guesstimezone' ) ."\"
				onclick='javascript:guessTimezone()' id='guesstimezonebutton' style='display:none;' />
				</td></tr></table><div class='prefsectiontip'>¹" .  wfMsg( 'timezonetext' ) . "</div></fieldset>
		</fieldset>\n\n" );

		# Editing
		#
		global $wgLivePreview, $wgUser;

		# Editor choice
		$current = $wgUser->getOption('defaulteditor', '');
		if (empty($current)) {
			# backwards compatibility with old advanced editor option
			$current = $wgUser->getOption('useadvanced', false) ? 'advanced' : 'visual';
		}
		$opts = '';
		//$opts .= Xml::option(wfMsg('pref_visual_editor'), 'visual', $current != 'advanced' && $current != 'guided');
		$opts .= Xml::option(wfMsg('pref_advanced_editor'), 'advanced', $current == 'advanced');
		$opts .= Xml::option(wfMsg('pref_guided_editor'), 'guided', $current == 'guided');
		$editorChoice = $this->tableRow(
			Xml::label( wfMsg('pref_default_editor') . ' ', 'wpDefaultEditor' ),
			Xml::tags( 'select',
				array( 'name' => 'wpDefaultEditor', 'id' => 'wpDefaultEditor' ),
				$opts
			)
		);

		$wgOut->addHTML( '<fieldset id="prefsection-3" class="prefsection"><legend>' . wfMsg( 'textboxsize' ) . '</legend>
			<div>' .
				wfInputLabel( wfMsg( 'rows' ), 'wpRows', 'wpRows', 3, $this->mRows, array('class' => 'input_med')) .
				'<br />' .
				wfInputLabel( wfMsg( 'columns' ), 'wpCols', 'wpCols', 3, $this->mCols, array('class' => 'input_med') ) .
			" " . wfMsg('Pref_adv_only') . "</div><br/>" .
			$this->getToggles( array(
				'editsection',
				//'editsectiononrightclick',
				'editondblclick',
				'editwidth',
				'disablewarning',
				'hidepersistantsavebar',
				'ignorefanmail',
				'scrolltalk',
				'showtoolbar',
				'previewonfirst',
				'previewontop',
				'minordefault',
				'externaleditor',
				'externaldiff',
				$wgLivePreview ? 'uselivepreview' : false,
				$wgUser->isSysop() || in_array('staff', $wgUser->getGroups()) ? 'autopatrol' : false,
				'forceeditsummary',
			) ) . '<br/>' . $editorChoice . '</fieldset>'
		);

		$this->mUsedToggles['autopatrol'] = true; # Don't show this up for users who can't; the handler below is dumb and doesn't know it

		
		
		# Recent changes
		$wgOut->addHtml( '<fieldset id="prefsection-4" class="prefsection"><legend>' . wfMsgHtml( 'prefs-rc' ) . '</legend>' );
		
		$rc  = '<table><tr>';
		$rc .= '<td>' . Xml::label( wfMsg( 'recentchangesdays' ), 'wpRecentDays' ) . '</td>';
		$rc .= '<td>' . Xml::input( 'wpRecentDays', 3, $this->mRecentDays, array( 'class' => 'input_med', 'id' => 'wpRecentDays' ) ) . '</td>';		
		$rc .= '</tr><tr>';
		$rc .= '<td>' . Xml::label( wfMsg( 'recentchangescount' ), 'wpRecent' ) . '</td>';
		$rc .= '<td>' . Xml::input( 'wpRecent', 3, $this->mRecent, array( 'class' => 'input_med', 'id' => 'wpRecent' ) ) . '</td>';
		$rc .= '</tr></table>';
		$wgOut->addHtml( $rc );
		
		$wgOut->addHtml( '<br />' );
		
		$toggles[] = 'hideminor';
		if( $wgRCShowWatchingUsers )
			$toggles[] = 'shownumberswatching';
		$toggles[] = 'usenewrc';
		$wgOut->addHtml( $this->getToggles( $toggles ) );
		if (class_exists('RCTest')) {
			$userGroups = $wgUser->getGroups();
			if (in_array('staff', $userGroups) || in_array('admin', $userGroups) || in_array('newarticlepatrol', $userGroups)) {
				$wgOut->addHtml( $this->getToggle('rctest', false, false, intval($this->mRCTest) === 0));
			}
		}

		$wgOut->addHtml( '</fieldset>' );

		# Watchlist
		$wgOut->addHtml( '<fieldset id="prefsection-5" class="prefsection"><legend>' . wfMsgHtml( 'prefs-watchlist' ) . '</legend>' );
		
		$wgOut->addHtml( wfInputLabel( wfMsg( 'prefs-watchlist-days' ), 'wpWatchlistDays', 'wpWatchlistDays', 3, $this->mWatchlistDays,  array('class' => 'input_med')) );
		$wgOut->addHtml( '<br /><br />' );

		$wgOut->addHtml( $this->getToggle( 'extendwatchlist' ) );
		$wgOut->addHtml( wfInputLabel( wfMsg( 'prefs-watchlist-edits' ), 'wpWatchlistEdits', 'wpWatchlistEdits', 3, $this->mWatchlistEdits,  array('class' => 'input_med')) );
		$wgOut->addHtml( '<br /><br />' );

		$wgOut->addHtml( $this->getToggles( array( 'watchlisthideown', 'watchlisthidebots', 'watchlisthideminor' ) ) );
		
		if( $wgUser->isAllowed( 'createpage' ) || $wgUser->isAllowed( 'createtalk' ) )
			$wgOut->addHtml( $this->getToggle( 'watchcreations' ) );
		foreach( array( 'edit' => 'watchdefault', 'move' => 'watchmoves', 'delete' => 'watchdeletion' ) as $action => $toggle ) {
			if( $wgUser->isAllowed( $action ) )
				$wgOut->addHtml( $this->getToggle( $toggle ) );
		}
		$this->mUsedToggles['watchcreations'] = true;
		$this->mUsedToggles['watchdefault'] = true;
		$this->mUsedToggles['watchmoves'] = true;
		$this->mUsedToggles['watchdeletion'] = true;
		
		$wgOut->addHtml( '</fieldset>' );

		# Misc
		#
		$wgOut->addHTML('<fieldset id="prefsection-6" class="prefsection"><legend>' . wfMsg('prefs-misc') . '</legend>');
		$wgOut->addHtml( '<label for="wpStubs">' . wfMsg( 'stub-threshold' ) . '</label>&nbsp;' );
		$wgOut->addHtml( Xml::input( 'wpStubs', 6, $this->mStubs, array( 'class' => 'input_med', 'id' => 'wpStubs' ) ) );
		$msgUnderline = htmlspecialchars( wfMsg ( 'tog-underline' ) );
		$msgUnderlinenever = htmlspecialchars( wfMsg ( 'underline-never' ) );
		$msgUnderlinealways = htmlspecialchars( wfMsg ( 'underline-always' ) );
		$msgUnderlinedefault = htmlspecialchars( wfMsg ( 'underline-default' ) );
		$uopt = $wgUser->getOption("underline");
		$s0 = $uopt == 0 ? ' selected="selected"' : '';
		$s1 = $uopt == 1 ? ' selected="selected"' : '';
		$s2 = $uopt == 2 ? ' selected="selected"' : '';
		$wgOut->addHTML("
<div class='toggle'><p><label for='wpOpunderline'>$msgUnderline</label>
<select name='wpOpunderline' id='wpOpunderline'>
<option value=\"0\"$s0>$msgUnderlinenever</option>
<option value=\"1\"$s1>$msgUnderlinealways</option>
<option value=\"2\"$s2>$msgUnderlinedefault</option>
</select></p></div>");

		$userGroups = $wgUser->getGroups();
		if (!in_array('sysop', $userGroups) && !in_array('newarticlepatrol', $userGroups)) {
			$this->mUsedToggles['welcomer'] = true;
		}

		foreach ( $togs as $tname ) {
			if( !array_key_exists( $tname, $this->mUsedToggles ) ) {
				$wgOut->addHTML( $this->getToggle( $tname ) );
			}
		}

		if ($wgUser->isGPlusUser()) {
			$wgOut->addHTML('<br /><div id="gplus_disco_link" style="display:inline;"><a href="#" id="pb-gp-disco">Disconnect from Google+</a></div><br />');
		}		
		$wgOut->addHTML( '</fieldset>' );

		
		wfRunHooks( 'RenderPreferencesForm', array( $this, $wgOut ) );

		$token = htmlspecialchars( $wgUser->editToken() );
		$skin = $wgUser->getSkin();
		$wgOut->addHTML( "
	<div id='prefsubmit'>
	<div>
		<input class='button primary' type='submit' name='wpSaveprefs' class='btnSavePrefs' value=\"" . wfMsgHtml( 'saveprefs' ) . '"'.$skin->tooltipAndAccesskey('save')." />
		<input class='button secondary' type='submit' name='wpReset' value=\"" . wfMsgHtml( 'resetprefs' ) . "\" />
		<div style='clear:both'></div>
	</div>

	</div>

	<input type='hidden' name='wpEditToken' value=\"{$token}\" />
	</div></form>\n" );

		/*$wgOut->addHtml( Xml::tags( 'div', array( 'class' => "prefcache" ),
			wfMsgExt( 'clearyourcache', 'parseinline' ) )
		);*/

	}
}

