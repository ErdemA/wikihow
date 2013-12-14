<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

 class NewHowtoArticles extends UnlistedSpecialPage{

	var $risingStars;

	static $cachelen_long = 3600;
	static $cachelen_short = 1800;

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'NewHowtoArticles' );
	}

	function getNewArticlesBox(){
		global $wgUser;
		$sk = $wgUser->getSkin();
		$titles = $this->getRisingStars();

		$html = "<div id='category_shell'><img src='" . wfGetPad('/skins/WikiHow/images/article_top.png') . "' width='679' height='10' alt='' class='module_cap' /><div id='newarticles'>";
		$html .= "<div id='newarticles_header' class='newarticles_header'><h1>" . wfMsg('newarticles') . "</h1></div>";
		$html .= "<div class='newarticles_inner'><table class='featuredArticle_Table'>";
		foreach($titles as $title){
			$html .= $sk->featuredArticlesLineWide($title);
		}
		$html .=  "</table>";


		//for now, no longer showing bottom half.
		//if($wgUser->getID() > 0){
			$html .= "</div></div><img src='" . wfGetPad('/skins/WikiHow/images/article_bottom_wh.png') . "' width='679' height='12' alt='' class='module_cap' /></div>";
		//}
		/*else{
			$html .= "<div id='newarticles_divider'>" . wfMsg('morenewarticles') . "</div>";

			$html .= "<div id='newarticles_list'>" . $this->getArticleList() . "<div class='clearall'></div></div>";

			$html .= "</div></div><img src='" . wfGetPad('/skins/WikiHow/images/article_bottom.png') . "' width='679' height='12' alt='' class='module_cap' /></div>";
		}*/

		return $html;
	}

	function getArticleList(){
		global $wgMemc, $wgUser;
		$key = wfMemcKey("newarticleslist");
		$cached = $wgMemc->get($key);
		if ($cached)  {
			return $cached;
		}

		$skin = $wgUser->getSkin();

		$dbr = wfGetDB(DB_SLAVE);
		$sql = self::getSql() . " ORDER BY value DESC LIMIT 50";
		$res = $dbr->query( $sql );

		$html = "";
		$total_num = wfMsg('newarticles_listnum');
		$column_num = ceil($total_num/3);

		for( $i = 0; $i < $total_num && $row = $dbr->fetchObject( $res ); ) {
			$title = Title::makeTitle($row->namespace, $row->title);
			$link = $skin->makeKnownLinkObj($title);
			if($i % $column_num == 0)
				$html .= "<ul>";
			$html .= "<li>" . $link . "</li>";
			if($i % $column_num == $column_num - 1)
				$html .= "</ul>";

			$i++;
		}

		if($i % $column_num != $column_num)
			$html .= "</ul>";

		$title = Title::makeTitle( NS_SPECIAL, 'NewHowtoArticles' );
		$link = $skin->makeKnownLinkObj( $title, wfMsg('more_newarticles') );

		$html .= "<p id='more_newarticles'>" . $link . "<img alt='' src='" . wfGetPad('/skins/WikiHow/images/actionArrow.png') . "' ></p>";

		$wgMemc->set($key, $html, NewHowtoArticles::$cachelen_long);
		return $html;
	}

	function getSql(){
		return "SELECT na_page, page_counter as counter, page_title AS title, page_id as cur_id, na_timestamp as value FROM newarticles LEFT JOIN page ON na_page = page_id WHERE na_valid = 1";
	}

	function getRisingStarsBox() {
		global $wgUser;
		$sk = $wgUser->getSkin();
		$titles = $this->getRisingStars();

		$html = "<h3>" . wfMsg('newarticles') . "</h3>\n<table>";
		foreach($titles as $title){
			$html .= $sk->featuredArticlesRow($title);
		}
		$html .=  "</table>";

		return $html;
	}

	function getRisingStars(){
		global $wgMemc;
		$key = wfMemcKey("newarticlesrisingstars");
		$cached = $wgMemc->get($key);
		if ($cached)  {
			return $cached;
		}
		$dbr = wfGetDB(DB_SLAVE);
		$ids = array();
		$res = $dbr->select('pagelist', 'pl_page', array('pl_list'=>'risingstar'),
			"WikiHowSkin::getNewArticlesBox",
			array('ORDER BY' => 'pl_page desc', 'LIMIT'=>8) //put in a few extra just in case
			);
		while($row = $dbr->fetchObject($res)) {
			$ids[] = $row->pl_page;
		}
		$res = $dbr->select(array('page'),
			array('page_namespace', 'page_title'),
			array('page_id IN (' . implode(",", $ids) . ")"),
			"WikiHowSkin::getNewArticlesBox",
			array('ORDER BY' => 'page_id desc', 'LIMIT'=>5)
			);
		$titles = array();
		$this->risingStars = array();
		while($row = $dbr->fetchObject($res)) {
			$t = Title::makeTitle(NS_MAIN, $row->page_title);
			if (!$t)
				continue;
			$titles[] = $t;
			$this->risingStars[] = $t;
		}

		$wgMemc->set($key, $titles, NewHowtoArticles::$cachelen_long);
		return $titles;
	}

	function execute($par){
		global $wgOut, $wgMemc;

		$wgOut->setRobotPolicy("index,follow");

		list( $limit, $offset ) = wfCheckLimits();
		$llr = new NewHowtoArticlesPage();
    	$result =  $llr->doQuery( $offset, $limit );

		return $result;
	}

 }


 class NewHowtoArticlesPage extends QueryPage {

	function NewHowtoArticlesPage(){
		global $wgHooks, $wgOut, $wgRequest, $wgUser, $wgMemc;
		list( $limit, $offset ) = wfCheckLimits();
		$wgOut->setPageTitle(wfMsg('newarticles_range', $offset+1, $offset+$limit));

		if($wgRequest->wasPosted()){
			$dbw = &wfGetDB(DB_MASTER);

			$pageId = $dbw->addQuotes( $wgRequest->getVal('pageId') );
			$sql = "UPDATE newarticles SET na_timestamp = " . wfTimestampNow() . ", na_valid = 0, na_user_text = '" . $wgUser->getName() . "' WHERE na_page = " . $pageId;

			$dbw->query($sql);

			$wgMemc->delete('newarticleslist');
		}
	}

	function getName() {
		return "NewHowtoArticles";
	}

	function isExpensive() {
		# page_counter is not indexed
		return true;
	}
	function isSyndicated() { return false; }

	function getSQL() {
		return NewHowtoArticles::getSql();
	}

	function formatResult( $skin, $result ) {
		global $wgLang, $wgContLang;
		$title = Title::makeTitle( $result->namespace, $result->title );
		$link = $skin->makeKnownLinkObj( $title, htmlspecialchars( $wgContLang->convert( $title->getPrefixedText() ) ) );
		$nv = wfMsgExt( 'nviews', array( 'parsemag', 'escape'),
			$wgLang->formatNum( $result->counter ) );
		return wfSpecialList($link, $nv) . $this->getRemoveButton($title->getArticleID());
	}

	function getRemoveButton($pageId){
		global $wgUser;
		$userGroups = $wgUser->getGroups();
		if (!in_array('staff', $userGroups)) {
			return "";
		}
		
		$html = "<form method='POST' action='/Special:" . $this->getName() . "' style='display:inline; margin-left:10px;'>";
		$html .= "<input type='submit' value='remove article' />";
		$html .= "<input type='hidden' name='pageId' value='" . $pageId . "' />";
		$html .= "</form>";

		return $html;
	}
}

?>
