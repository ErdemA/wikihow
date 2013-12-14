<?php
/**
 *
 * @addtogroup SpecialPage
 */

/**
 *
 */
function wfSpecialSpecialpages() {
	global $wgOut, $wgUser, $wgMessageCache;

	$wgMessageCache->loadAllMessages();

	$wgOut->setRobotpolicy( 'noindex,nofollow' );  # Is this really needed?
	$sk = $wgUser->getSkin();

	/** Pages available to all */
	wfSpecialSpecialpages_gen( SpecialPage::getRegularPages(), 'spheading', $sk );

	/** Restricted special pages */
	wfSpecialSpecialpages_gen( SpecialPage::getRestrictedPages(), 'restrictedpheading', $sk );
}

/**
 * sub function generating the list of pages
 * @param $pages the list of pages
 * @param $heading header to be used
 * @param $sk skin object ???
 */
function wfSpecialSpecialpages_gen($pages,$heading,$sk) {
	global $wgOut, $wgSortSpecialPages;

	if( count( $pages ) == 0 ) {
		# Yeah, that was pointless. Thanks for coming.
		return;
	}

	/** Put them into a sortable array */
	$sortedPages = array();
	foreach ( $pages as $page ) {
		if ( $page->isListed() ) {
			$sortedPages[$page->getDescription()] = $page->getTitle();
		}
	}

	/** Sort */
	if ( $wgSortSpecialPages ) {
		ksort( $sortedPages );
	}

	global $wgTitle;
	/** Now output the HTML */
	$wgOut->addHTML( '<div class="special_pages"><h2>' . wfMsgHtml( $heading ) . "</h2><div class='section_text'>\n<ul>" );
	foreach ( $sortedPages as $desc => $title ) {
		$link = $sk->makeKnownLinkObj( $title , htmlspecialchars( $desc ) );
		$wgOut->addHTML( "<li>{$link}</li>\n" );
	}
	$wgOut->addHTML( "</ul></div></div>\n" );
}


