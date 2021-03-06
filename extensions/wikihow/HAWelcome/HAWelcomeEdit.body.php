<?php

class HAWelcomeEdit extends UnlistedSpecialPage {

	private	$mTitle;

	public function __construct() {
		wfLoadExtensionMessages('HAWelcome');
		parent::__construct( 'HAWelcomeEdit', 'HAWelcomeEdit', null, false );
	}

	public function execute( $subpage ) {
		global $wgOut, $wgUser, $wgRequest;

		wfProfileIn( __METHOD__ );

		$this->setHeaders();
		$this->mTitle = SpecialPage::getTitleFor( 'HAWelcomeEdit' );

		if( $this->isRestricted() && !$this->userCanExecute( $wgUser ) ) {
			$this->displayRestrictionError();
			return;
		}
		
		if( $wgRequest->wasPosted() ) {
			$this->doPost();
		}
		
		$this->showCurrent();
		$this->showChange();
		
		wfProfileOut( __METHOD__ );
	}
	
	private function showCurrent(){
		global $wgOut, $wgMemc;

		$wgOut->addHTML("<fieldset>\n");
		$wgOut->addHTML("<legend>CurrentValue</legend>\n");
		$sysopId = $wgMemc->get( wfMemcKey( "last-sysop-id" ) );
			if( $sysopId ) {
				$this->mSysop = User::newFromId( $sysopId );
				$sysopName = wfEscapeWikiText( $this->mSysop->getName() );
				$groups = $this->mSysop->getEffectiveGroups();
				$wgOut->addHTML("ID: <code>".$sysopId."</code><br/>");
				$wgOut->addHTML("Name: <code>".$sysopName."</code><br/>");
				$wgOut->addHTML("Groups: <code>". implode(", ", $groups) ."</code><br/>");

				$action_url = $this->mTitle->getFullURL();
				$wgOut->addHTML("<form action='{$action_url}' method='post'>\n");
				$wgOut->addHTML("<input type='hidden' name='method' value='clear' />\n");
				$wgOut->addHTML("<input type='submit' value='clear' />\n");
				$wgOut->addHTML("</form>\n");
			}
			else {
				$wgOut->addHTML("<i>n/a</i>");
			}
		$wgOut->addHTML("</fieldset>\n");
	}
	
	private function showChange(){
		global $wgOut;

		$wgOut->addHTML("<fieldset>\n");
		$wgOut->addHTML("<legend>ChangeValue</legend>\n");

		$action_url = $this->mTitle->getFullURL();
		$wgOut->addHTML("<form action='{$action_url}' method='post'>\n");
		$wgOut->addHTML("<input type='hidden' name='method' value='by_id' />\n");
		$wgOut->addHTML("<label for='new_sysop_id'>Change by ID</label><br/>\n");
		$wgOut->addHTML("<input type='text' name='new_sysop_id' />\n");
		$wgOut->addHTML("<input type='submit' value='change' />\n");
		$wgOut->addHTML("</form>\n");

		$wgOut->addHTML("<hr />\n");

		$wgOut->addHTML("<form action='{$action_url}' method='post'>\n");
		$wgOut->addHTML("<input type='hidden' name='method' value='by_name'>\n");
		$wgOut->addHTML("<label for='new_sysop_text'>Change by Name</label><br/>\n");
		$wgOut->addHTML("<input type='text' name='new_sysop_text' />\n");
		$wgOut->addHTML("<input type='submit' value='change' />\n");
		$wgOut->addHTML("</form>\n");

		$wgOut->addHTML("</fieldset>\n");
	}

	private function doPost(){
		global $wgOut, $wgRequest, $wgMemc;

		
		$method = $wgRequest->getVal('method');
		
		if( $method == 'by_id' ) {
			$new_id = $wgRequest->getInt('new_sysop_id');
			if( empty($new_id) || $new_id < 0 ) {
				$wgOut->addHTML("bad input");
				return false;
			}
			if( !User::whois($new_id) ) {
				$wgOut->addHTML("no user with that id");
				return false;
			}
			
			$wgMemc->set( wfMemcKey( "last-sysop-id" ), $new_id, 86400 );
			$wgOut->addHTML("new value saved");
		}
		elseif( $method == 'by_name' ) {
			$new_text = $wgRequest->getText('new_sysop_text');
			if( empty($new_text) ) {
				$wgOut->addHTML("bad input");
				return false;
			}
			$new_id = User::idFromName($new_text);
			if( empty($new_id) ) {
				$wgOut->addHTML("name not found as user");
				return false;
			}

			$wgMemc->set( wfMemcKey( "last-sysop-id" ), $new_id, 86400 );
			$wgOut->addHTML("new value saved");
		}
		elseif( $method == 'clear' ) {
			$wgMemc->delete( wfMemcKey( "last-sysop-id" ) );
			$wgOut->addHTML("cleared");
		}
		else {
			$wgOut->addHTML( "unknown method [{$method}] used to POST<br/>\n");
		}
	}
}
