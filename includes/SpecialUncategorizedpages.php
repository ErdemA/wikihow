<?php
/**
 *
 * @addtogroup SpecialPage
 */

/**
 * A special page looking for page without any category.
 * @addtogroup SpecialPage
 */
class UncategorizedPagesPage extends PageQueryPage {
	var $requestedNamespace = NS_MAIN;

	function getName() {
		return "Uncategorizedpages";
	}

	function sortDescending() {
		return false;
	}

	function isExpensive() {
		return true;
	}
	function isSyndicated() { return false; }

	function getSQL() {
		$dbr = wfGetDB( DB_SLAVE );
		list( $page, $categorylinks ) = $dbr->tableNamesN( 'page', 'categorylinks' );
		$name = $dbr->addQuotes( $this->getName() );

		//XXADDED
		$not_in  = "";
		global $wgLanguageCode;
		if ($wgLanguageCode == 'en') {
        	$templates = wfMsgForContent('templates_further_editing');
        	$t_arr = split("\n", $templates);
        	$not_in  = " AND cl_to NOT IN ('" . implode("','", $t_arr) . "')";
		}
			
      $templates = wfMsgForContent('templates_further_editing');
        $t_arr = split("\n", $templates);
        $templates = "'" . implode("','", $t_arr) . "'";
		return
			"
			SELECT
				$name as type,
				page_namespace AS namespace,
				page_title AS title,
				page_title AS value
			FROM $page
			LEFT JOIN $categorylinks ON page_id=cl_from $not_in
			WHERE cl_from IS NULL AND page_namespace={$this->requestedNamespace} AND page_is_redirect=0
			";
	}


	/**
	 * Format and output report results using the given information plus
	 * OutputPage
	 *
	 * @param OutputPage $out OutputPage to print to
	 * @param Skin $skin User skin to use
	 * @param Database $dbr Database (read) connection to use
	 * @param int $res Result pointer
	 * @param int $num Number of available result rows
	 * @param int $offset Paging offset
	 */
	protected function outputResults( $out, $skin, $dbr, $res, $num, $offset ) {
		global $wgContLang, $wgUser, $wgLanguageCode;
	
		if( $num > 0 ) {
			$html = array();
			if( !$this->listoutput )
				$html[] = $this->openList( $offset );
			
			# $res might contain the whole 1,000 rows, so we read up to
			# $num [should update this to use a Pager]
			for( $i = 0; $i < $num && $row = $dbr->fetchObject( $res ); $i++ ) {
				$line = $this->formatResult( $skin, $row );
				$title = preg_replace('/-/',' ',$row->title);
				if( $line ) {
					$attr = ( isset( $row->usepatrol ) && $row->usepatrol && $row->patrolled == 0 )
						? ' class="not-patrolled"'
						: '';

					if ($wgUser->getID() > 0 && ( $wgLanguageCode != 'zh' && $wgLanguageCode != 'ru' && $wgLanguageCode != 'hi' )) {

						$html[] = $this->listoutput
							? $line
							: "<li{$attr}><div id=\"".htmlentities($title,ENT_QUOTES)."\">{$line}   <input type=\"button\" value=\"" . wfMsg('up_add_category') . "\" onclick=\"frames['dlogBody'].supAC('". urlencode($title) ."');\"></div> </li>\n";

					} else {
						$html[] = $this->listoutput
							? $line
							: "<li{$attr}>{$line}</li>\n";
					}
				}
			}
			
			# Flush the final result
			if( $this->tryLastResult() ) {
				$row = null;
				$line = $this->formatResult( $skin, $row );
				if( $line ) {
					$attr = ( isset( $row->usepatrol ) && $row->usepatrol && $row->patrolled == 0 )
						? ' class="not-patrolled"'
						: '';
					$html[] = $this->listoutput
						? $line
						: "<li{$attr}>{$line}</li>\n";
				}
			}
			
			if( !$this->listoutput )
				$html[] = $this->closeList();
			
			$html = $this->listoutput
				? $wgContLang->listToText( $html )
				: implode( '', $html );
			
			$out->addHtml( $html );

			if ($wgUser->getID() > 0 && ( $wgLanguageCode !='zh' && $wgLanguageCode != 'ru' && $wgLanguageCode != 'hi' )) {
				$out->addHtml( $this->getCategoryPopup() );
			}
		}
	}


	function getCategoryPopup() {
		$display = "";

		$css = HtmlSnips::makeUrlTags('css', array('categoriespopup.css'), 'extensions/wikihow', false);

		$display .=  "
$css
<div id=\"modalPage\">
  <div class=\"modalBackground\" id=\"modalBackground\"></div>
   <div class=\"modalContainer\" id=\"modalContainer\">
    <div class=\"modalTitle\"><a onclick=\"document.getElementById('modalPage').style.display = 'none';\">X</a></div>
    <div class=\"modalBody\">
		<script type=\"text/javascript\">
		if (screen.height < 701) {
			document.getElementById(\"modalContainer\").style.top = \"1%\";
		}
		</script>

		<strong><ABBR id=\"ctitle\" title=\"\">" .  wfMsg("selectCategory") . "</ABBR> </strong><br /><br />

		<iframe id=\"dlogBody\" name=\"dlogBody\" src=\"/Special:Categoryhelper?type=categorypopup\"  frameborder=\"0\" vspace=\"0\" hspace=\"0\" marginwidth=\"0\" marginheight=\"0\" width=\"470\" height=\"400\" scrolling=\"no\" stype=\"overflow:visible\"></iframe>
		</div>
	</div>
 </div>
		";

		return $display;
	}
}


/**
 * constructor
 */
function wfSpecialUncategorizedpages() {
	list( $limit, $offset ) = wfCheckLimits();

	$lpp = new UncategorizedPagesPage();

	//XXADDED
	global $wgOut;
	$wgOut->addWikiText(wfMsg("Uncategorizedpages_info", wfMsg('templates_further_editing')));
	return $lpp->doQuery( $offset, $limit );
}


