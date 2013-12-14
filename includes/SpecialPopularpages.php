<?php
/**
 *
 * @addtogroup SpecialPage
 */

/**
 * implements Special:Popularpages
 * @addtogroup SpecialPage
 */
class PopularPagesPage extends QueryPage {

	function getName() {
		return "Popularpages";
	}

	function isExpensive() {
		# page_counter is not indexed
		return true;
	}
	function isSyndicated() { return false; }

	function getSQL() {
		$dbr = wfGetDB( DB_SLAVE );
		$page = $dbr->tableName( 'page' );

		$query = 
			"SELECT 'Popularpages' as type,
			        page_namespace as namespace,
			        page_title as title,
			        page_counter as value
			FROM $page ";
		$where =
			"WHERE page_is_redirect=0 AND page_namespace";

		global $wgContentNamespaces;
		if( empty( $wgContentNamespaces ) ) {
			$where .= '='.NS_MAIN;
		} else if( count( $wgContentNamespaces ) > 1 ) {
			$where .= ' in (' . implode( ', ', $wgContentNamespaces ) . ')';
		} else {
			$where .= '='.$wgContentNamespaces[0];
		}

		return $query . $where;
	}

	function formatResult( $skin, $result ) {
		global $wgLang, $wgContLang;
		$title = Title::makeTitle( $result->namespace, $result->title );
		$link = $skin->makeKnownLinkObj( $title, htmlspecialchars( $wgContLang->convert( $title->getPrefixedText() ) ) );
		$nv = wfMsgExt( 'nviews', array( 'parsemag', 'escape'),
			$wgLang->formatNum( $result->value ) );
		return wfSpecialList($link, $nv);
	}
}

/**
 * Constructor
 */
function wfSpecialPopularpages() {
	global $wgOut;
	list( $limit, $offset ) = wfCheckLimits();
	$wgOut->setRobotPolicy("index,follow");
	$ppp = new PopularPagesPage();
	if ($limit != 50 || $offset != 0) {
   		$wgOut->setPageTitle(wfMsg('popularpages_range', $offset+1, $offset+$limit));
    }
	return $ppp->doQuery( $offset, $limit );
}


