<?php

class ProfileBox extends UnlistedSpecialPage {

	var $featuredArticles;
	public static $stats = '';

	/***************************
	 **
	 **
	 ***************************/
	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'ProfileBox' );
	}


	/***************************
	 **
	 **
	 ***************************/
	function getPBTitle() {
		global $wgUser, $wgOut, $wgLang, $wgTitle, $wgScriptPath, $wgStylePath;

	    wfLoadExtensionMessages('ProfileBox');

		$name = "";

		$name .= wfMsg('profilebox-name');
		$name .= " for ". $wgUser->getName();
		$avatar = Avatar::getPicture($wgUser->getName());

		if ($wgUser->getID() > 0) {
			if ($wgUser->getRegistration() != '') {
				$pbDate = ProfileBox::getMemberLength(wfTimestamp(TS_UNIX,$wgUser->getRegistration()));
			} else {
				$pbDate = ProfileBox::getMemberLength(wfTimestamp(TS_UNIX,'20060725043938'));
			}
		}
		$heading = $avatar . "<div id='avatarNameWrap'><h1 class=\"firstHeading\">" . $name . "</h1><div id='regdate'>" . wfMsg('pb-joinedwikihow', $pbDate) . "</div></div><div style='clear: both;'> </div>";

		return $heading;

	}

	function getStatsDisplay($stats, $username, $created_count = 0){
		global $wgUser;

		wfLoadExtensionMessages('ProfileBox');

		$sk = $wgUser->getSkin();
		$isLoggedIn = $wgUser && !$wgUser->isAnon();

		$display = "";

		if($stats['nab'] == 1 || $stats['admin'] == 1 || $stats['fa'] == 1 || $stats['created'] != 0 || $stats['edited'] != 0 || $stats['patrolled'] != 0 || $stats['viewership'] != 0){

			$display = "<div id='profileBoxStats' class='pb-stats minor_section'>
						<h2>" . wfMsg('pb-mystats') . "</h2><table id='profileBoxStatsContent' class='section_text'>";

			$contribsPage = SpecialPage::getTitleFor( 'Contributions', $username );

			$count = 0;

			$display .= "<tr>";
			if($stats['created'] != 0){
				$text = wfMsg('pb-articlesstarted');
				if ($isLoggedIn) {
					$text = $sk->makeKnownLinkObj( $contribsPage , $text );
				}
				//$created_count only counts main namespaces and the like
				//use that number
				//$display .= "<td class='" . ($count%2==0?"left":"right") . "'>" . $stats['created'] . " " . $text . "</td>";
				$display .= "<td class='" . ($count%2==0?"left":"right") . "'>" . $created_count . " " . $text . "</td>";
				$count++;
			}
			if($stats['edited'] != 0){
				$display .= "<td class='" . ($count%2==0?"left":"right") . "'>" . wfMsg('pb-articleedits',$stats['edited']) . "</a></td>";
				$count++;
			}

			if($count == 2)
				$display .= "</tr><tr>";
			if($stats['patrolled'] != 0){
				$text = wfMsg('pb-editspatrolled');
				if ($isLoggedIn) {
					$text = "<a href='/Special:Log?type=patrol&user=" . $username . "'>$text</a>";
				}
				$display .= "<td class='" . ($count%2==0?"left":"right") . "'>" . $stats['patrolled'] . " " . $text . "</td>";
				$count++;
			}
			if($count == 2)
				$display .= "</tr><tr>";
			if($stats['viewership'] != 0){
				$display .= "<td class='" . ($count%2==0?"left":"right") . "'>" . wfMsg('pb-articleviews',$stats['viewership']) . "</td>";
				$count++;
			}

			$display .= "</tr></table></div>";

		}

		return $display;
	}

	function showProfileData($u) {
		global $wgUser, $wgOut, $wgLang, $wgTitle, $wgScriptPath, $wgStylePath;

		wfLoadExtensionMessages('ProfileBox');

		$display = "";

		$display .= self::getRecentHistory($u->getID());

		$t = Title::newFromText($u->getUserPage() . '/profilebox-aboutme');
		if ($t->getArticleId() > 0) {
			$r = Revision::newFromTitle($t);
			$aboutme = $r->getText();
			$aboutme = strip_tags($aboutme, '<p><br><b><i>');
			$aboutme = preg_replace('/\\\\r\\\\n/s',"\n",$aboutme);
			$aboutme = stripslashes($aboutme);
		}
		if ($u->getOption('profilebox_stats') == 1) { $checkStats = 'true'; }
		else { $checkStats = 'false'; }

		if ($u->getOption('profilebox_startedEdited') == 1) { $checkStartedEdited = 'true'; }
		else { $checkStartedEdited = 'false'; }

		if ($u->getOption('profilebox_favs') == 1) { $checkFavs = 'true'; }
		else { $checkFavs = 'false'; }

		$profilebox_name = wfMsg('profilebox-name');
		$profilebox_contributions = wfMsg('profilebox-contributions');
		$profilebox_edited_more = wfMsg('profilebox-edited-more');

		$display .= "
<script language='javascript' src='" . wfGetPad('/extensions/wikihow/profilebox/profilebox.js?') . WH_SITEREV . "'></script>
<link rel='stylesheet' media='all' href='" . wfGetPad('/extensions/wikihow/profilebox/profilebox.css?') . WH_SITEREV . "' type='text/css' />

<script language='javascript'>
	var profilebox_username = '".$u->getName()."';
	var profilebox_name = '$profilebox_name';
	var msg_contributions = '$profilebox_contributions';
	var msg_edited_more = '$profilebox_edited_more';
	var pbstats_check = $checkStats;
	var pbstartededited_check = $checkStartedEdited;
	var pbfavs_check = false;
</script>\n";
		$display .= "<div id='profileBoxID'>\n";
		$display .= "<div class='article_inner'>";

		if ($aboutme != '') {
			$display .= "<p id='pb-aboutme'><strong>About me. </strong>".$aboutme."</p>\n";
		}

		if($checkStats == 'true' || $checkStartedEdited == 'true')
			$stats = self::fetchStats("User:" . $u->getName());
		if ($checkStats == 'true'){

			$display .= ProfileBox::getStatsDisplay($stats, $u->getName());
		}

		$display .="</div><!--end article_inner-->";

		if ($checkStartedEdited == 'true') {
			$data = self::fetchCreatedData(mysql_real_escape_string("User:" . $wgTitle->getText()), 6);
			$hasMoreCreated = count($data) > 5 ? true : false;

			$tmpl = new EasyTemplate( dirname(__FILE__) );
			$tmpl->set_vars(array('data' => $data));

			$display .= $tmpl->execute('started.tmpl.php');

			//$createdHtml = self::fetchCreated($data, 5);

			$display .= "
				<table class='pb-articles' id='pb-created' cellspacing='0' cellpadding='0'>
					<thead><tr>
						<th class='first pb-title'><strong>" . wfMsg('pb-articlesstarted') ."</strong> (" . $stats['created'] . ")</th>
						<th class='middle pb-star'>Rising Stars</th>
						<th class='middle pb-feature'>Featured</th>
						<th class='last pb-view'>Views</th></tr></thead>";

			$display .= "<tbody>" . $createdHtml . "</tbody>";
			$display .= "<tfoot>";
			if($hasMoreCreated)
				$display .= "<tr><td class='pb-title'><a href='#' id='created_more' onclick='pbShow_articlesCreated(\"more\"); return false;'>View more &raquo;</a><a href='#' id='created_less' style='display:none;' onClick='pbShow_articlesCreated(); return false;'>&laquo; View Less</a></td><td colspan='3' class='pb-view'>&nbsp;</td></tr>";
			$display .= "</tfoot>";
			$display .="</table>";
			if (class_exists('ThumbsUp')) {
				$dataThumbs = self::fetchThumbsData(mysql_real_escape_string("User:" . $wgTitle->getText()), 6);
				$hasMoreThumbs = count($dataThumbs) > 5 ? true : false;
				$thumbsHtml = self::fetchThumbed($dataThumbs, 5);

				$display .= "
					<table class='pb-articles' id='pb-thumbed' cellspacing='0' cellpadding='0'>
						<thead><tr>
							<th class='first pb-title'><strong>Thumbed Up Edits</strong></th>
							<!--<th class='middle pb-feature'>My Edit</th>
							<th class='last pb-view'>Thumbs</th>-->
							<th class='last pb-view'>Date</th>
						</tr></thead>";
				$display .= "<tbody>" . $thumbsHtml . "</tbody>";
				$display .= "<tfoot>";
				if($hasMoreThumbs)
					$display .= "<tr><td class='pb-title'><a href='#' id='thumbed_more' onclick='pbShow_Thumbed(\"more\"); return false;'>View more &raquo;</a><a href='#' id='thumbed_less' style='display:none;' onClick='pbShow_Thumbed(); return false;'>&laquo; View Less</a></td><td colspan='1' class='pb-view'>&nbsp;</td></tr>";
				$display .= "</tfoot>";
				$display .="</table>";
			}
		}

		/*
				if ($u->getOption('profilebox_favs') == 1) {
					$display .= "
		<div id='pbFavs' class='profileBox'>
		<strong>Favorite Articles:</strong><br />
		<div id='pbFavsContent'></div>
		</div>";
				}
		*/

		$display .="
<div id='pbTalkpage' style='float:right; padding-top:10px; margin-right:27px; padding-bottom:10px;'><a href='/".$u->getTalkPage()."'>Go to My Talk Page &raquo;</a></div>
<div style='clear:both'></div>

<script language='javascript'>
if (typeof jQuery == 'undefined') {
	Event.observe(window, 'load', pbInit);
} else {
	jQuery(window).load(pbInit);
}
</script>

</div>

		";


		return $display;
	}

	/***************************
	 ** outputs html for the users displaybox
	 ** u - user to act on
	 ** displayAsArticle - if true it wraps the stats in articleInner div
	 ***************************/
	function displayBox($u, $displayAsArticle=true) {
		wfLoadExtensionMessages('ProfileBox');

		$display = "";

		$t = Title::newFromText($u->getUserPage() . '/profilebox-aboutme');
		if ($t->getArticleId() > 0) {
			$r = Revision::newFromTitle($t);
			$aboutme = $r->getText();
			$aboutme = strip_tags($aboutme, '<p><br><b><i>');
			$aboutme = preg_replace('/\\\\r\\\\n/s',"\n",$aboutme);
			$aboutme = stripslashes($aboutme);
		}
		if ($u->getOption('profilebox_stats') == 1) { $checkStats = 'true'; }
		else { $checkStats = 'false'; }

		if ($u->getOption('profilebox_startedEdited') == 1) { $checkStartedEdited = 'true'; }
		else { $checkStartedEdited = 'false'; }

		if ($u->getOption('profilebox_favs') == 1) { $checkFavs = 'true'; }
		else { $checkFavs = 'false'; }

		$profilebox_name = wfMsg('profilebox-name');
		$profilebox_contributions = wfMsg('profilebox-contributions');
		$profilebox_edited_more = wfMsg('profilebox-edited-more');

		$display .= "
<script language='javascript' src='" . wfGetPad('/extensions/wikihow/profilebox/profilebox.js?') . WH_SITEREV . "'></script>
<link rel='stylesheet' media='all' href='" . wfGetPad('/extensions/wikihow/profilebox/profilebox.css?') . WH_SITEREV . "' type='text/css' />

<script language='javascript'>
	var profilebox_username = '".$u->getName()."';
	var profilebox_name = '$profilebox_name';
	var msg_contributions = '$profilebox_contributions';
	var msg_edited_more = '$profilebox_edited_more';
	var pbstats_check = $checkStats;
	var pbstartededited_check = $checkStartedEdited;
	var pbfavs_check = false;
</script>\n";
		$display .= "<div id='profileBoxID'>\n";
		if ($displayAsArticle) {
			$display .= "<div class='article_inner'>";
		}
		if ($aboutme != '') {
			$display .= "<p id='pb-aboutme'><strong>About me. </strong>".$aboutme."</p>\n";
		}

		if($checkStats == 'true' || $checkStartedEdited == 'true')
			$stats = self::fetchStats("User:" . $u->getName());
		if ($checkStats == 'true'){

			$display .= ProfileBox::getStatsDisplay($stats, $u->getName());
		}

		if ($displayAsArticle) {
			$display .= "<div class='clearall'></div>";
			$display .="</div><!--end article_inner-->";
		}

		if ($checkStartedEdited == 'true') {
			$data = self::fetchCreatedData(mysql_real_escape_string("User:" . $u->getName()), 6);
			$hasMoreCreated = count($data) > 5 ? true : false;
			$createdHtml = self::fetchCreated($data, 5);

			$display .= "
				<table class='pb-articles' id='pb-created' cellspacing='0' cellpadding='0'>
					<thead><tr>
						<th class='first pb-title'><strong>" . wfMsg('pb-articlesstarted') . "</strong> (" . $stats['created'] . ")</th>
						<th class='middle pb-star'>Rising Stars</th>
						<th class='middle pb-feature'>Featured</th>
						<th class='last pb-view'>Views</th></tr></thead>";

			$display .= "<tbody>" . $createdHtml . "</tbody>";
			$display .= "<tfoot>";
			if($hasMoreCreated)
				$display .= "<tr><td class='pb-title'><a href='#' id='created_more' onclick='pbShow_articlesCreated(\"more\"); return false;'>View more &raquo;</a><a href='#' id='created_less' style='display:none;' onClick='pbShow_articlesCreated(); return false;'>&laquo; View Less</a></td><td colspan='3' class='pb-view'>&nbsp;</td></tr>";
			$display .= "</tfoot>";
			$display .="</table>";
			if (class_exists('ThumbsUp')) {
				$dataThumbs = self::fetchThumbsData(mysql_real_escape_string("User:" . $u->getName()), 6);
				$hasMoreThumbs = count($dataThumbs) > 5 ? true : false;
				$thumbsHtml = self::fetchThumbed($dataThumbs, 5);

				$display .= "
					<table class='pb-articles' id='pb-thumbed' cellspacing='0' cellpadding='0'>
						<thead><tr>
							<th class='first pb-title'><strong>Thumbed Up Edits</strong></th>
							<!--<th class='middle pb-feature'>My Edit</th>
							<th class='last pb-view'>Thumbs</th>-->
							<th class='last pb-view'>Date</th>
						</tr></thead>";
				$display .= "<tbody>" . $thumbsHtml . "</tbody>";
				$display .= "<tfoot>";
				if($hasMoreThumbs)
					$display .= "<tr><td class='pb-title'><a href='#' id='thumbed_more' onclick='pbShow_Thumbed(\"more\"); return false;'>View more &raquo;</a><a href='#' id='thumbed_less' style='display:none;' onClick='pbShow_Thumbed(); return false;'>&laquo; View Less</a></td><td colspan='1' class='pb-view'>&nbsp;</td></tr>";
				$display .= "</tfoot>";
				$display .="</table>";
			}
		}

/*
		if ($u->getOption('profilebox_favs') == 1) {
			$display .= "
<div id='pbFavs' class='profileBox'>
<strong>Favorite Articles:</strong><br />
<div id='pbFavsContent'></div>
</div>";
		}
*/

		$display .="
<div id='pbTalkpage' style='float:right; padding-top:10px; margin-right:27px; padding-bottom:10px;'><a href='/".$u->getTalkPage()."'>Go to My Talk Page &raquo;</a></div>
<div style='clear:both'></div>

<script language='javascript'>
if (typeof jQuery == 'undefined') {
	Event.observe(window, 'load', pbInit);
} else {
	jQuery(window).load(pbInit);
}
</script>

</div>

		";


		return $display;
	}


	/***************************
	 **
	 **
	 ***************************/
	function displayForm() {
		global $wgUser, $wgOut, $wgLang, $wgTitle, $wgScriptPath, $wgStylePath, $wgLanguageCode;

		$wgOut->addHTML('<div class="minor_section">');
		$wgOut->addHTML($this->getPBTitle());
		$wgOut->addHTML('</div>');

		$live = '';
		$occupation = '';
		$aboutme = '';
		if ($wgUser->getOption('profilebox_display') == 1) {
			$t = Title::newFromText($wgUser->getUserPage() . '/profilebox-live');
			if ($t->getArticleId() > 0) {
				$r = Revision::newFromTitle($t);
				$live = $r->getText();
			}
			$t = Title::newFromText($wgUser->getUserPage() . '/profilebox-occupation');
			if ($t->getArticleId() > 0) {
				$r = Revision::newFromTitle($t);
				$occupation = $r->getText();
			}
			$t = Title::newFromText($wgUser->getUserPage() . '/profilebox-aboutme');
			if ($t->getArticleId() > 0) {
				$r = Revision::newFromTitle($t);
				$aboutme = $r->getText();
				$aboutme = preg_replace('/\\\\r\\\\n/s',"\n",$aboutme);
				$aboutme = stripslashes($aboutme);
			}

			if ($wgUser->getOption('profilebox_stats') == 1) { $checkStats = 'CHECKED'; }
			if ($wgUser->getOption('profilebox_startedEdited') == 1) { $checkStartedEdited = 'CHECKED'; }
			if ($wgUser->getOption('profilebox_favs') == 1) { $checkFavs = 'CHECKED'; }

			if ($t = Title::newFromID($wgUser->getOption('profilebox_fav1'))) {
				if ($t->getArticleId() > 0) {
					$fav1 = $t->getText();
					$fav1id = $t->getArticleId();
				}
			}
			if ($t = Title::newFromID($wgUser->getOption('profilebox_fav2'))) {
				if ($t->getArticleId() > 0) {
					$fav2 = $t->getText();
					$fav2id = $t->getArticleId();
				}
			}
			if ($t = Title::newFromID($wgUser->getOption('profilebox_fav3'))) {
				if ($t->getArticleId() > 0) {
					$fav3 = $t->getText();
					$fav3id = $t->getArticleId();
				}
			}

		} else {
			$checkStats = 'CHECKED';
			$checkStartedEdited = 'CHECKED';
			$checkFavs = 'CHECKED';
		}


		$wgOut->addHTML("
<script language='javascript' src='" . wfGetPad('/extensions/wikihow/profilebox/profilebox.js?') . WH_SITEREV . "'></script>
<link rel='stylesheet' media='all' href='" . wfGetPad('/extensions/wikihow/profilebox/profilebox.css?') . WH_SITEREV . "' type='text/css' />

<form method='post' name='profileBoxForm'>
<div class='minor_section'>
<strong>" . wfMsg('pb-demographic') . "</strong><br /><br />
<table width='100%' >
<tr>
	<td width='120'>" . wfMsg('pb-location') . "</td>
	<td width='530'><input class='loginText input_med' type='text' name='live' value='".$live."'></td>
</tr>
<tr>
	<td>" . ($wgLanguageCode == "en" ? wfMsg('pb-website-entry') : wfMsg('pb-website')) . "</td>
	<td><input class='loginText input_med' type='text' name='occupation' value='".$occupation."'></td>
</tr>
<tr>
	<td valign='top'>" . wfMsg('pb-aboutme') . "</td>
	<td><textarea class='textarea_med' name='aboutme' cols='55' rows='5' style='overflow:auto;' >".$aboutme."</textarea></td>
</tr>
</table>
</div>

<div class='minor_section'>
<strong>" . wfMsg('pb-displayinfo') . "</strong> <br /><br />
<input type='checkbox' name='articleStats' id='articleStats' ".$checkStats."> <label for='articleStats'>".wfMsg('profilebox-checkbox-stats')."</label><br /><br />
<input type='checkbox' name='articleStartedEdited' id='articleStartedEdited' ".$checkStartedEdited."> <label for='articleStartedEdited'>".wfMsg('profilebox-checkbox-startededited')."</label><br />
");
/*
<input type='checkbox' name='articleFavs' ".$checkFavs." > ".wfMsg('profilebox-checkbox-favs')."<br />
				<input type='text' id='pbFav1' name='pbFav1' value='".$fav1."' class='selectFavs'> <a onclick='deleteFav(1);'>X</a><br />
				<div id='autocomplete_choices1' class='autocomplete'></div>
				<input type='text' id='pbFav2' name='pbFav2' value='".$fav2."' class='selectFavs'> <a onclick='deleteFav(2);'>X</a><br />
				<div id='autocomplete_choices2' class='autocomplete'></div>
				<input type='text' id='pbFav3' name='pbFav3' value='".$fav3."' class='selectFavs'> <a onclick='deleteFav(3);'>X</a><br />
				<div id='autocomplete_choices3' class='autocomplete'></div>
<input type='hidden' id='fav1' name='fav1' value='".$fav1id."' >
<input type='hidden' id='fav2' name='fav2' value='".$fav2id."' >
<input type='hidden' id='fav3' name='fav3' value='".$fav3id."' >
*/

$wgOut->addHTML("
<br />

<div class='profileboxform_btns'>
	<a href='/".$wgUser->getUserPage()."' class='button secondary'>" . wfMsg('cancel') . "</a> 
	<input class='button primary' type='submit' id='gatProfileSaveButton' name='save' value='" . wfMsg('pb-save') . "' />
</div>
<!-- <input type='checkbox' name='recentTalkpage'> Most recent talk page messages<br /> -->
</div>

</form>
");
	}

	function initProfileBox($user){

		$user->setOption('profilebox_fav1', "");
		$user->setOption('profilebox_fav2', "");
		$user->setOption('profilebox_fav3', "");


		$user->setOption('profilebox_stats', 1);

		$user->setOption('profilebox_startedEdited', 1);

		$user->setOption('profilebox_display', 1);

		$user->saveSettings();
	}


	/***************************
	 **
	 **
	 ***************************/
	function pbConfig() {
		global $wgUser, $wgRequest, $wgOut;

			$live = mysql_real_escape_string(strip_tags($wgRequest->getVal('live'), '<p><br><b><i>'));
			$occupation = mysql_real_escape_string(strip_tags($wgRequest->getVal('occupation'), '<p><br><b><i>'));
			$aboutme = mysql_real_escape_string(strip_tags($wgRequest->getVal('aboutme'), '<p><br><b><i>'));

			$t = Title::newFromText($wgUser->getUserPage() . '/profilebox-live');
			$article = new Article($t);
			if ($t->getArticleId() > 0) {
				$article->updateArticle($live, 'profilebox-live-update', true, $watch);
			} else if($live != ''){
				$article->insertNewArticle($live, 'profilebox-live-update', true, $watch, false, false, true);
			}

			$t = Title::newFromText($wgUser->getUserPage() . '/profilebox-occupation');
			$article = new Article($t);
			if ($t->getArticleId() > 0) {
				$article->updateArticle($occupation, 'profilebox-occupation-update', true, $watch);
			} else if($occupation != ''){
				$article->insertNewArticle($occupation, 'profilebox-occupation-update', true, $watch, false, false, true);
			}

			$t = Title::newFromText($wgUser->getUserPage() . '/profilebox-aboutme');
			$article = new Article($t);
			if ($t->getArticleId() > 0) {
				$article->updateArticle($aboutme, 'profilebox-aboutme-update', true, $watch);
			} else if($aboutme != ''){
				$article->insertNewArticle($aboutme, 'profilebox-aboutme-update', true, $watch, false, false, true);
			}

		$userpageurl = $wgUser->getUserPage() . '';
		$t = Title::newFromText( $userpageurl, NS_USER );
		$article = new Article($t);
		$userpage = " \n";
		if ($t->getArticleId() > 0) {
			/*
			$r = Revision::newFromTitle($t);
			$curtext .= $r->getText();

			if (!preg_match('/<!-- blank -->/',$curtext)) {
				$userpage .= $curtext;
				$article->updateArticle($userpage, 'profilebox-userpage-update', true, $watch);
			}
			*/
		} else {
			$article->insertNewArticle($userpage, 'profilebox-userpage-update', true, $watch, false, false, true);
		}

		$wgUser->setOption('profilebox_fav1', $wgRequest->getVal('fav1'));
		$wgUser->setOption('profilebox_fav2', $wgRequest->getVal('fav2'));
		$wgUser->setOption('profilebox_fav3', $wgRequest->getVal('fav3'));

		if ($wgRequest->getVal('articleStats') == 'on') {
			$wgUser->setOption('profilebox_stats', 1);
		} else {
			$wgUser->setOption('profilebox_stats', 0);
		}

		if ($wgRequest->getVal('articleStartedEdited') == 'on') {
			$wgUser->setOption('profilebox_startedEdited', 1);
		} else {
			$wgUser->setOption('profilebox_startedEdited', 0);
		}

/*
		if ( ($wgRequest->getVal('articleFavs') == 'on') &&
				($wgRequest->getVal('fav1') || $wgRequest->getVal('fav2') || $wgRequest->getVal('fav3')) )
		{
			$wgUser->setOption('profilebox_favs', 1);
		} else {
			$wgUser->setOption('profilebox_favs', 0);
		}
*/

		$wgUser->setOption('profilebox_display', 1);

		$wgUser->saveSettings();

	}

	/***************************
	 ** Used in a maintenance script
	 ** deleteBannedPages.php
	 ***************************/

	static function removeUserData($user) {
		$removed = false;

		$t = Title::newFromText($user->getUserPage() . '/profilebox-live');
		if ($t->getArticleId() > 0) {
			$r = Revision::newFromTitle($t);
			$txt = $r->getText();
			if ($txt != '') {
				$a = new Article($t);
				$a->doEdit('', 'profilebox-live-empty' );
				$removed = true;
			}
		}

		$t = Title::newFromText($user->getUserPage() . '/profilebox-occupation');
		if ($t->getArticleId() > 0) {
			$r = Revision::newFromTitle($t);
			$txt = $r->getText();
			if ($txt != '') {
				$a = new Article($t);
				$a->doEdit('', 'profilebox-occupation-empty');
				$removed = true;
			}
		}

		$t = Title::newFromText($user->getUserPage() . '/profilebox-aboutme');
		if ($t->getArticleId() > 0) {
			$r = Revision::newFromTitle($t);
			$txt = $r->getText();
			if ($txt != '') {
				$a = new Article($t);
				$a->doEdit('', 'profilebox-aboutme-empty');
				$removed = true;
			}
		}

		$user->setOption('profilebox_stats', 0);
		$user->setOption('profilebox_startedEdited', 0);
		$user->setOption('profilebox_favs', 0);

		$user->setOption('profilebox_fav1', 0);
		$user->setOption('profilebox_fav2', 0);
		$user->setOption('profilebox_fav3', 0);

		$user->setOption('profilebox_display', 0);
		$user->saveSettings();

		return($removed);
	}

	/***************************
	 **
	 **
	 ***************************/
	function removeData() {
		global $wgUser, $wgRequest;

		self::removeUserData($wgUser);

		return "SUCCESS";

	}

	/***************************
	 **
	 **
	 ***************************/
	function fetchStats($pagename) {
		global $wgUser;

		if (count($stats) > 0) return $stats;

     	$dbr = wfGetDB(DB_SLAVE);
     	$dbw = wfGetDB(DB_MASTER);
		$t = Title::newFromText($pagename);
		$u = User::newFromName($t->getText());
		if (!$u || $u->getID() == 0) {
			$ret = wfMsg('profilebox_ajax_error');
			return;
		}

		$cachetime = 86400;
		if ($wgUser->getID() == $u->getID()) {
			$cachetime = 60;
		}

		$updateflag = 0;
		$response = array();
		$sql = "select *  from profilebox where pb_user=".$u->getID();
		$res = $dbr->query($sql);
		$row=$dbr->fetchObject($res);
		if ($row) {
			$now = time();
			$last = strtotime($row->pb_lastUpdated . " UTC");
			$diff = $now - $last;

			if (isset($row->pb_lastUpdated) && $diff <= $cachetime) {
				$response['created'] = number_format($row->pb_started, 0, "", ",");
				$response['edited'] = number_format($row->pb_edits, 0, "", ",");
				$response['patrolled'] = number_format($row->pb_patrolled, 0, "", ",");
				$response['viewership'] = number_format($row->pb_viewership, 0, "", ",");
				$response['uid'] = $u->getID();
				$response['contribpage'] = "/Special:Contributions/" . $u->getName();
				if (class_exists('ThumbsUp')) {
					$response['thumbs_given'] = number_format($row->pb_thumbs_given, 0, "", ",");
					$response['thumbs_received'] = number_format($row->pb_thumbs_received, 0, "", ",");
				}

				$updateflag = 0;
			} else {
				$updateflag = 1;
			}
		} else {
			$updateflag = 1;
		}

		if ($updateflag) {
			$options = array("fe_user='" . $u->getID() . "'");
			$created = $dbr->selectField('firstedit', 'count(*)', $options, 'pbCreated');

			$options = array('log_user=' . $u->getID(), 'log_type' => 'patrol');
			$patrolled = $dbr->selectField('logging', 'count(*)', $options, "pbPatrolled");

			$edited = WikihowUser::getAuthorStats($u->getName());

			$viewership = 0;
			$vsql = "select sum(page_counter) as viewership from page,firstedit where page_namespace=0 and page_id=fe_page and fe_user=".$u->getID();
			//More accurate but will take longer
			//$vsql = "select sum(distinct(page_counter)) as viewership from page,revision where page_namespace=0 and page_id=rev_page and rev_user=".$u->getID()." GROUP BY rev_page;
			$vres = $dbr->query($vsql);
			while ($row1=$dbr->fetchObject($vres)) {
				$viewership += $row1->viewership;
			}

			$sql = "INSERT INTO profilebox (pb_user,pb_started,pb_edits,pb_patrolled,pb_viewership,pb_lastUpdated) ";
			$sql .= "VALUES (".$u->getID().",$created, $edited, $patrolled, $viewership, '".wfTimestampNow()."') ";
			$sql .= "ON DUPLICATE KEY UPDATE pb_started=$created,pb_edits=$edited,pb_patrolled=$patrolled,pb_viewership=$viewership,pb_lastUpdated='".wfTimestampNow()."'";
			$res = $dbw->query($sql);

			$response['created'] = number_format($created, 0, "", ",");
			$response['edited'] = number_format($edited, 0, "", ",");
			$response['patrolled'] = number_format($patrolled, 0, "", ",");
			$response['viewership'] = number_format($viewership, 0, "", ",");
			$response['uid'] = $u->getID();
			$response['contribpage'] = "/Special:Contributions/" . $u->getName();
			if (class_exists('ThumbsUp')) {
				$response['thumbs_given'] = number_format($row->pb_thumbs_given, 0, "", ",");
				$response['thumbs_received'] = number_format($row->pb_thumbs_received, 0, "", ",");
			}

		}

		//check badges
		$groups = $u->getGroups();
		$rights = $u->getRights();
		if ( in_array( 'sysop', $groups ) )
			$response['admin'] = 1;
		else
			$response['admin'] = 0;
		if( in_array('newarticlepatrol', $rights ) )
			$response['nab'] = 1;
		else
			$response['nab'] = 0;
		$resFA = $dbr->select(array('firstedit', 'templatelinks'), '*', array('fe_page=tl_from', 'fe_user' => $u->getID(), ('tl_title = "Fa" OR tl_title = "FA"') ), __FUNCTION__, array('GROUP BY' => 'fe_page') );
		$resRS = $dbr->select(array('firstedit', 'pagelist'), '*', array('fe_page=pl_page', 'fe_user' => $u->getID() ), __FUNCTION__, array('GROUP BY' => 'fe_page') );
		if($dbr->numRows($resFA) + $dbr->numRows($resRS) >= 5)
			$response['fa'] = 1;
		else
			$response['fa'] = 0;
		
		if( in_array( 'welcome_wagon', $groups )  )
			$response['welcome'] = 1;
		else
			$response['welcome'] = 0;

		return $response;
	}

	function fetchThumbed($data, $limit = 5) {
		global $wgUser;

		$dbr = wfGetDB(DB_SLAVE);
		$t = Title::newFromText($pagename);
		$result = self::getThumbsTableHtml($data, $dbr, $limit);

		return $result;
	}

	function fetchThumbsData($username, $limit) {
		global $wgMemc, $wgUser;

		$username = urldecode($username);
		$cacheKey = wfMemcKey('pb_thumbs', $username, $limit);
		$result = $wgMemc->get($cacheKey);

		$profileOwner = $wgUser->getId() != 0 && 'User:' . $wgUser->getName() == $pagename;
		if (!$profileOwner && $result) {
			return $result;
		}

     	$dbr = wfGetDB(DB_SLAVE);


		$u = User::newFromName(stripslashes($username));

		$order = array();
		$order['GROUP BY'] = 'thumb_rev_id';
		$order['ORDER BY'] = 'rev_id DESC';
		if ($limit) {
			$order['LIMIT'] = $limit;
		}
		$res = $dbr->select(
			array('thumbs','page', 'revision'),
			array ('page_namespace', 'page_id', 'page_title', 'count(thumb_rev_id) as cnt', 'thumb_rev_id', 'rev_timestamp'),
			array ('thumb_recipient_id' => $u->getID(), 'thumb_exclude=0', 'thumb_page_id=page_id', 'thumb_rev_id = rev_id'),
			"",
			$order
			);

		while($row = $dbr->fetchRow($res)){
			$results[] = $row;
		}

		$dbr->freeResult($res);

		$wgMemc->set($cacheKey, $results, 60*10);
		return $results;
	}

	function fetchTopEditData($username) {
     	$dbr = wfGetDB(DB_SLAVE);

		$u = User::newFromName($username);

		$order = array();
		$order['GROUP BY'] = 'thumb_rev_id';
		$order['ORDER BY'] = 'cnt DESC';
		if ($limit) {
			$order['LIMIT'] = 1;
		}
		$res = $dbr->select(
			array('thumbs','page'),
			array ('page_namespace', 'page_id', 'page_title', 'count(thumb_rev_id) as cnt', 'thumb_rev_id'),
			array ('thumb_recipient_id' => $u->getID(), 'thumb_exclude=0', 'thumb_page_id=page_id'),
			"",
			$order
			);

		return $res;
	}

	function getThumbsTableHtml(&$data, &$dbr, $limit = '') {
		global $wgUser, $wgTitle;
		wfLoadExtensionMessages('ProfileBox');

		$sk = $wgUser->getSkin();
		$isLoggedIn = $wgUser && !$wgUser->isAnon();

		$html = '';

		// Display the most-thumbed article at the top of the table
		/*$topRevid = -1;
		if ($row = $dbr->fetchObject($topRes)) {
			$topRevId = $row->thumb_rev_id;
			$t = Title::makeTitle($row->page_namespace, $row->page_title);
			if ($t->getArticleID() > 0)  {
				$html .= self::getThumbsRowHtml($t, $row, $sk, $isLoggedIn);
			}
		}*/

		// Show the most recent thumbs up
		$count = 0;
		if(count($data) > 0) {
			foreach($data as $row) {
				if($limit != '' && $count >= $limit)
					break;
				$t = Title::makeTitle($row['page_namespace'], $row['page_title']);

				if ($t->getArticleID() > 0) {
					$html .= self::getThumbsRowHtml($t, $row, $sk, $isLoggedIn);
				}
				$count++;
			}
		}

		if (strlen($html) == 0) {
			$profileOwner = $wgUser->getId() != 0 && $wgUser->getName() == $wgTitle->getText();
			if($profileOwner)
				$html .= "<tr><td class='pb-title'>3" . wfMsgWikiHtml('pb-noedits') . "</td><td class='pb-view'>&nbsp;</td></tr>";
			else
				$html .= "<tr><td class='pb-title'>4" . wfMsg('pb-noarticles-anon') . "</td><td class='pb-view'>&nbsp;</td></tr>";
		}
		return $html;
	}

	function getThumbsRowHtml(&$t, &$row, &$sk, $isLoggedIn) {
		$text = wfTimeAgo($row['rev_timestamp']);
		if ($isLoggedIn) {
			$text = $sk->makeKnownLinkObj($t, $text, 'diff=' . $row['thumb_rev_id'] . '&oldid=PREV');
		}
		$html = "";
		$html .= "  <tr>";
		$html .= "    <td class='pb-title'><a href='/".$t->getPartialURL()."'>" . $t->getFullText() . "</a></td>\n";
		$html .= "    <td class='pb-view'>$text</td>\n";
		$html .= "  </tr>\n";
		return $html;
	}

	function getCreated(&$data, $limit = '') {
		$dbr = wfGetDB(DB_SLAVE);

		if(empty($this->featuredArticles)){
			// GET FEATURED ARTICLES
			$fasql = "select page_id, page_title, page_namespace from templatelinks left join page on tl_from = page_id where tl_title='Fa'";
			$fares = $dbr->query($fasql);

			while ($row=$dbr->fetchObject($fares)) {
				$this->featuredArticles[ $row->page_title ] = 1;
			}
		}

		foreach($data as $item) {

		}
	}


	/***************************
	 **
	 **
	 ***************************/
	function fetchCreated(&$data, $limit = '') {
		$dbr = wfGetDB(DB_SLAVE);

		if(empty($this->featuredArticles)){
			// GET FEATURED ARTICLES
			$fasql = "select page_id, page_title, page_namespace from templatelinks left join page on tl_from = page_id where tl_title='Fa'";
			$fares = $dbr->query($fasql);

			while ($row=$dbr->fetchObject($fares)) {
				$this->featuredArticles[ $row->page_title ] = 1;
			}
		}

		// DB CALL
		//$res = self::fetchCreatedData(mysql_real_escape_string($pagename), $limit);

		return self::getTableHtml($data, $limit, $dbr);
	}

	function getTableHtml(&$data, $limit, &$dbr){
		global $wgUser, $wgTitle;
		wfLoadExtensionMessages('ProfileBox');

		$html = "";
		$count = 0;

		if(count($data) > 0){
			foreach ($data as $row) {
				if($limit != '' && $count >= $limit)
					break;

				$t = Title::makeTitle($row->page_namespace, $row->page_title);
				$rs = $dbr->selectField('pagelist', array('count(*)'), array('pl_page'=>$t->getArticleID(), 'pl_list'=>'risingstar')) > 0;
				$risingstar = "";
				if ($rs) {
					$risingstar = "<img src='/extensions/wikihow/profilebox/star-green.png' height='20px' width='20px'>";
				}
				else{
					$risingstar = "&nbsp;";
				}

				if ($this->featuredArticles[ $t->getDBKey() ]) {
					//$featured = "<font size='+1' color='#2B60DE'>&#9733;</font>";
					$featured = "<img src='/extensions/wikihow/profilebox/star-blue.png' height='17px' width='21px'>";
				} else {
					$featured = "&nbsp";
				}


				if ($t->getArticleID() > 0)  {
					$html .= "  <tr>";
					$html .= "    <td class='pb-title'><a href='/".$t->getPartialURL()."'>" . $t->getFullText() . "</a></td>\n";
					$html .= "    <td class='pb-star'>$risingstar</td>";
					$html .= "    <td class='pb-feature'>$featured</td>";
					$html .= "    <td class='pb-view'>".number_format($row->page_counter, 0, '',',') ."</td>\n";
					$html .= "  </tr>\n";
				}

				$count++;
			}
		}

		if($html == ""){
			$profileOwner = $wgUser->getId() != 0 && $wgUser->getName() == $wgTitle->getText();
			if($profileOwner)
				$html .= "<tr><td class='pb-title' colspan='3'>" . wfMsgWikiHtml('pb-noarticles') . "</td><td class='pb-view'>&nbsp;</td></tr>";
			else
				$html .= "<tr><td class='pb-title' colspan='3'>" . wfMsg('pb-noarticles-anon') . "</td><td class='pb-view'>&nbsp;</td></tr>";
		}
		return $html;
	}

	/**
	 * Gets the sql result for articles created by the given user
	 */
	function fetchCreatedData($username, $limit){
		global $wgMemc, $wgUser;

		$cachekey = wfMemcKey('pb_fetchCreatedData', $username, $limit);
		//$result = $wgMemc->get($cachekey);
		$profileOwner = $wgUser->getId() != 0 && 'User:' . $wgUser->getName() == $pagename;
		if (!profileOwner && $result) {
			return result;
		}

     	$dbr = wfGetDB(DB_SLAVE);

		$u = User::newFromName(stripslashes($username));

		$order = array();
		$order['ORDER BY'] = 'fe_timestamp DESC';
		if ($limit) {
			$order['LIMIT'] = $limit;
		}
		$res = $dbr->select(
			array('firstedit','page'),
			array ('page_id', 'page_title', 'page_namespace', 'fe_timestamp', 'page_counter'),
			array ('fe_page=page_id', 'fe_user' => $u->getID(), "page_title not like 'Youtube%'", 'page_is_redirect' => 0, 'page_namespace' => NS_MAIN),
			__FUNCTION__,
			$order
			);

		foreach($res as $row) {
			$results[] = $row;
		}

		$dbr->freeResult($res);

		foreach($results as $row) {
			if($this->featuredArticles[$row->page_title])
				$row->fa = true;
			else
				$row->fa = false;

			$title = Title::makeTitle($row->page_namespace, $row->page_title);
			$rs = $dbr->selectField('pagelist', array('count(*)'), array('pl_page'=>$title->getArticleID(), 'pl_list'=>'risingstar')) > 0;
			if ($rs)
				$row->rs = true;
			else
				$row->rs = false;

			$row->title = $title;
		}

		$wgMemc->set($cachekey, $results);

		return $results;
	}

	function initFeaturedArticles() {
		if(empty($this->featuredArticles)){
			// GET FEATURED ARTICLES

			$dbr = wfGetDB(DB_SLAVE);

			$fasql = "select page_id, page_title, page_namespace from templatelinks left join page on tl_from = page_id where tl_title='Fa'";
			$fares = $dbr->query($fasql);

			while ($row=$dbr->fetchObject($fares)) {
				$this->featuredArticles[ $row->page_title ] = 1;
			}
		}
	}



	/***************************
	 **
	 **
	 ***************************/
	function fetchEdited($pagename, $limit = '') {
     	$dbr = wfGetDB(DB_SLAVE);
		$t = Title::newFromText($pagename);

		if(empty($this->featuredArticles)){
			// GET FEATURED ARTICLES
			$fasql = "select page_id, page_title, page_namespace from templatelinks left join page on tl_from = page_id where tl_title='Fa'";
			$fares = $dbr->query($fasql);
			while ($row=$dbr->fetchObject($fares)) {
				$fa[ $row->page_title ] = 1;
			}
		}

		// DB CALL
		$res = self::fetchEditedData($dbr->strencode($pagename), $limit);

		return self::getTableHtml($res, $dbr);
	}

	/*
	 * This function is used above for determining the
	 * category interests of a user
	 */
	function fetchEditedData($username, $limit){
     	$dbr = wfGetDB(DB_SLAVE);

		$u = User::newFromName(stripslashes($username));

		$order = array();
		$order['ORDER BY'] = 'rev_timestamp DESC';
		$order['GROUP BY'] = 'page_title';
		if ($limit) {
			$order['LIMIT'] = $limit;
		}
		$res = $dbr->select(
			array('revision','page'),
			array ('page_id', 'page_title', 'page_namespace', 'rev_timestamp', 'page_counter'),
			array ('rev_page=page_id', 'rev_user' => $u->getID(), 'page_namespace' => NS_MAIN),
			"",
			$order
			);

		return $res;
	}

	/***************************
	 **
	 **
	 ***************************/
	function fetchFavs($pagename) {
		$t = Title::newFromText($pagename);
		$u = User::newFromName($t->getText());
		if (!$u || $u->getID() == 0) {
			$ret = wfMsg('profilebox_ajax_error');
			return;
		}

		$display = "";

		for ($i=1;$i<=3;$i++) {
			$fav = 'profilebox_fav'.$i;
			$page_id = '';
			$page_id = $u->getOption($fav);

			if ($page_id) {
				$t = Title::newFromID($page_id);
				if ($t->getArticleID() > 0)  {
					$display .= "<a href='/".$t->getPartialURL()."'>" . $t->getFullText() . "</a><br />\n";
				}
			}
		}

		echo $display;
		return;
	}

	/***************************
	 **
	 **
	 ***************************/
	function favsTitleSelector() {
		global $wgRequest;
     	$dbr = wfGetDB(DB_SLAVE);
		$name = preg_replace('/ /','-', strtoupper($wgRequest->getVal('pbTitle')));

		$order = array();
		//$order['ORDER BY'] = 'page_timestamp DESC';
		$order['LIMIT'] = '6';

		$res = $dbr->select(
			array('page'),
			array ('page_id','page_title'),
			array ("UPPER(page_title) like '%".$name."%'", 'page_namespace' => NS_MAIN),
			"",
			$order
			);
		$display = "<ul>\n";
		//$display .= "  <li>" . $name . "</li>\n";
		while ($row=$dbr->fetchObject($res)) {
			$t = Title::makeTitle($row->page_namespace, $row->page_title);
			if ($t->getArticleID() > 0)  {
				$display .= "  <li id=".$row->page_id.">" . $t->getFullText() . "</li>\n";
			}
		}
		$display .= "</ul>\n";
		$dbr->freeResult($res);

		echo $display;
		return;
	}


	/***************************
	 **
	 **
	 ***************************/
	function execute ($par ) {
		global $wgUser, $wgOut, $wgTitle, $wgServer, $wgRequest, $IP;

		$type = $wgRequest->getVal('type');
		$wgOut->setArticleBodyOnly(true);

		//Just Display Box - Can probably delete now that it's being loaded in the skin.
		if ($type == 'display') {
			$username = $wgRequest->getVal('u');
			$u = User::newFromName(stripslashes($username));
			$wgOut->addHTML($this->displayBox($u));
			return;
		} else if ($type == 'favsselector') {
			$wgOut->setArticleBodyOnly(true);
			$this->favsTitleSelector();
			return;
		} else if ($type == 'ajax') {
			$wgOut->setArticleBodyOnly(true);
			$element = $wgRequest->getVal('element');
			$dbr = wfGetDB(DB_SLAVE);
			$pagename = $dbr->strencode($wgRequest->getVal('pagename'));
			if (($element != '') && ($pagename != '')) {
				switch($element) {
					case 'thumbed':
						$data = self::fetchThumbsData($pagename, 5);
						echo $this->fetchThumbed($data, 5);
						break;
					case 'thumbedall':
						$data = self::fetchThumbsData($pagename, 100);
						echo $this->fetchThumbed($data, 100);
						break;
					case 'stats':
						echo $this->fetchStats($pagename);
						break;
					case 'created':
						$data = self::fetchCreatedData($pagename, 5);
						echo $this->fetchCreated($data, 5);
						break;
					case 'createdall':
						$data = self::fetchCreatedData($pagename, 100);
						echo $this->fetchCreated($data, 100);
						break;
					/*case 'edited':
						echo $this->fetchEdited($pagename, 5);
						break;
					case 'editedall':
						echo $this->fetchEdited($pagename, 100);
						break;*/
					case 'favs':
						echo $this->fetchFavs($pagename);
						break;
					default:
						wfDebug("ProfileBox ajax requesting  unknown element: $element \n");
				}
			}
			return;
		}

		if( $wgUser->isBlocked() ) {
			$wgOut->blockedPage();
			return;
		}

		if( $wgUser->getID() == 0) {
			$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}
		$wgOut->setArticleBodyOnly(true);

		if ($wgRequest->wasPosted()) {
			$wgOut->setArticleBodyOnly(true);
			$this->pbConfig();

			$t = $wgUser->getUserPage();
			$wgOut->redirect($t->getFullURL());
		} else if ($type == 'remove') {
			$wgOut->setArticleBodyOnly(true);
			$this->removeData();
			$wgOut->addHTML("SUCCESS");
		} else {
			$wgOut->setArticleBodyOnly(false);
			$this->displayForm() ;
		}

	}

	static function getPageTop($u){
		global $wgUser, $wgRequest, $wgLang;
		wfLoadExtensionMessages('ProfileBox');

		$realName = User::whoIsReal($u->getId());
		if ($u->getRegistration() != '') {
			$pb_regdate = ProfileBox::getMemberLength(wfTimestamp(TS_UNIX,$u->getRegistration()));
		} else {
			$pb_regdate = ProfileBox::getMemberLength(wfTimestamp(TS_UNIX,'20060725043938'));
		}

		$pb_showlive = false;
		$t = Title::newFromText($u->getUserPage() . '/profilebox-live');
		if ($t->getArticleId() > 0) {
			$r = Revision::newFromTitle($t);
			$pb_live = $r->getText();
			if($pb_live != "")
				$pb_showlive = true;
		}

		$pb_showwork = false;
		$t = Title::newFromText($u->getUserPage() . '/profilebox-occupation');
		if ($t->getArticleId() > 0) {
			$r = Revision::newFromTitle($t);
			$pb_work = $r->getText();
			if($pb_work != "")
				$pb_showwork = true;
		}

		$t = Title::newFromText($u->getUserPage() . '/profilebox-aboutme');
		if ($t->getArticleId() > 0) {
			$r = Revision::newFromTitle($t);
			$pb_aboutme = $r->getText();
			$pb_aboutme = strip_tags($pb_aboutme, '<p><br><b><i>');
			$pb_aboutme = preg_replace('/\\\\r\\\\n/s',"\n",$pb_aboutme);
			$pb_aboutme = stripslashes($pb_aboutme);
		}
		
		$social = self::getSocialLinks();
		
		$vars = array(
			'pb_user_name' => $u->getName(),
			'pb_display_name' => ($realName) ? $realName : $u->getName(),
			'pb_display_show' => $u->getOption('profilebox_display'),
			'pb_regdate' => $pb_regdate,
			'pb_showlive' => $pb_showlive,
			'pb_live' =>$pb_live,
			'pb_showwork' => $pb_showwork,
			'pb_work' => $pb_work,
			'pb_aboutme' => $pb_aboutme,
			'pb_social' => $social,
			'pb_email_url' => "/" . $wgLang->specialPage('Emailuser') ."?target=" . $u->getName(),
		);

		$tmpl = new EasyTemplate( dirname(__FILE__) );
		$tmpl->set_vars($vars);

		return $tmpl->execute('header.tmpl.php');
	}

	static function getMemberLength($joinDate){
		wfLoadExtensionMessages('Misc');
		
		if ($joinDate == '') return wfMsg('since-unknown');

		$now = time();
		$over = wfMsg('over','');
		$periods = array(wfMsg("day-plural"), wfMsg("week-plural"), wfMsg("month-plural"), wfMsg("year-plural"));
		$period = array(wfMsg("day"), wfMsg("week"), wfMsg("month-singular"), wfMsg("year-singular"));

		$dt1 = new DateTime("@$joinDate");
		$dt2 = new DateTime("@$now");
		$interval = $dt1->diff($dt2);

		if ($interval->y > 0) {
			return $over . $interval->y .' '. ($interval->y==1?$period[3]:$periods[3]);
		}
		else if ($interval->m > 0) {
			return $over . $interval->m .' '. ($interval->m==1?$period[2]:$periods[2]);
		}
		else if ($interval->w > 0) {
			return $over . $interval->w .' '. ($interval->w==1?$period[1]:$periods[1]);
		}
		else if ($interval->d > 0) {
			return $over . $interval->d .' '. ($interval->d==1?$period[0]:$periods[0]);
		}
		else {
			return wfMsg('sincetoday');
		}
	}

	public static function getMetaDesc() {
		global $wgTitle;
		$user = $wgTitle->getText();

		$stats = self::fetchStats('User:'.$user);

		if ($stats) {
			$desc = wfMsg('user_meta_description_extended',
					$user,
					$stats['created'],
					$stats['edited'],
					$stats['viewership'],
					$stats['patrolled']);
		}

		return $desc;
	}

	function getRecentHistory($userId) {
		$html = '<div class="sidebox" id="rcwidget_profile">';
		$html .= RCWidget::getProfileWidget();
		$html .= "</div>";
		$html .= "<script type='text/javascript'>rcUser = {$userId}</script>";

		return $html;
	}

	static function getDisplayCreatedData($data, $maxCount) {
		global $wgUser, $wgTitle;
		wfLoadExtensionMessages("ProfileBox");
		
		$profileOwner = $wgUser->getId() != 0 && $wgUser->getName() == $wgTitle->getText();
		
		$tmpl = new EasyTemplate( dirname(__FILE__) );
		$tmpl->set_vars(array('data' => $data, 'max' => $maxCount, 'isOwner' => $profileOwner));

		return $tmpl->execute('started.tmpl.php');
	}

	static function getDisplayThumbData($data, $maxCount) {
		global $wgUser, $wgTitle;
		wfLoadExtensionMessages("ProfileBox");
		$profileOwner = $wgUser->getId() != 0 && $wgUser->getName() == $wgTitle->getText();
		
		$tmpl = new EasyTemplate( dirname(__FILE__) );
		$tmpl->set_vars(array('data' => $data, 'max' => $maxCount, 'isOwner' => $profileOwner));

		return $tmpl->execute('thumb.tmpl.php');
	}

	static function getDisplayBadge($data) {
		global $wgUser;

		$isLoggedIn = $wgUser->getID() != 0;
		$display = "";

		$right = 15;
		if($data['welcome'] == 1) {
			$inner = "<div class='pb-welcome pb-badge' style='right:{$right}px'></div>";
			$display .= $isLoggedIn ? "<a href='/Special:ProfileBadges'>$inner</a>" : $inner;
			$right += 75;
		}
		if($data['nab'] == 1) {
			$inner = "<div class='pb-nab pb-badge' style='right:{$right}px'></div>";
			$display .= $isLoggedIn ? "<a href='/Special:ProfileBadges'>$inner</a>" : $inner;
			$right += 75;
		}
		if($data['admin'] == 1){
			$inner = "<div class='pb-admin pb-badge' style='right:{$right}px'></div>";
			$display .= $isLoggedIn ? "<a href='/Special:ProfileBadges'>$inner</a>" : $inner;
			$right += 75;
		}
		if($data['fa'] == 1){
			$inner = "<div class='pb-fa pb-badge' style='right:{$right}px'></div>";
			$display .= $isLoggedIn ? "<a href='/Special:ProfileBadges'>$inner</a>" : $inner;
			$right += 75;
		}

		return $display;
	}
	
	function getFinalStats($contributions, $views) {
		$html = '<div class="minor_section" id="pb_finalstats">'.
				$contributions.$views.
				'</div>';
		return $html;
	}
	
	function getPageViews() {
		global $wgLang, $wgTitle;
		$a = new Article($wgTitle);
		$count = $wgLang->formatNum( $a->getCount() );
		$s = wfMsg( 'viewcountuser', $count );
		return $s;
	}
	
	function getSocialLinks() {
		global $wgTitle, $wgUser;
		$socialLinked = "";
		if ($u = User::newFromName($wgTitle->getDBKey())) {
			if(UserPagePolicy::isGoodUserPage($wgTitle->getDBKey())) {
				if (class_exists('FBLogin') && class_exists('FBLink') && $u->getID() == $wgUser->getId() && !$wgUser->isGPlusUser()) {
					//show or offer FB link
					$socialLinked = !$wgUser->isFacebookUser() ? FBLink::showCTAHtml() : FBLink::showCTAHtml('FBLink_linked');
				}
				elseif ($u->isGPlusUser()) {
					//G+ -- are we showing authorship?
					if ($u->getOption('show_google_authorship')) {
						$gp_id = $u->getOption('gplus_uid');
						$name = $u->getRealName() ? $u->getRealName() : $u->getName();
						$gplus_link = '<a href="https://plus.google.com/'.$gp_id.'" rel="me">'. $name .' on Google+</a>';
						$socialLinked = '<div class="pb-gp-link">'.$unlink_link.$gplus_link.'</div>';
					}
				}
			}
		}
		return $socialLinked;
	}
}

class ProfileStats {
	var $featuredArticles;
	var $user;
	var $isOwnPage;
	const CACHE_PREFIX = "pb-";

	public function __construct($user) {
		global $wgUser;
		$this->user = $user;
		$this->isOwnPage = $wgUser->getID() == $this->user->getID();
		$this->initFeaturedArticles();
	}

	/****
	 *
	 ***/
	public function getArticlesCreated($limit) {
		$dbr = wfGetDB(DB_SLAVE);

		global $wgMemc;
		$MAX_INITIAL_DISPLAYED = 5;

		$cacheKey = wfMemcKey(ProfileStats::CACHE_PREFIX . 'created', $this->user->getID(), $limit);
		$result = $wgMemc->get($cacheKey);

		if (!$this->isOwnPage && $result) {
			return $result;
		}

		$order = array();
		$order['ORDER BY'] = 'fe_timestamp DESC';
		if ($limit) {
			$order['LIMIT'] = $limit;
		}
		$res = $dbr->select(
			array('firstedit','page'),
			array ('page_id', 'page_title', 'page_namespace', 'fe_timestamp', 'page_counter'),
			array ('fe_page=page_id', 'fe_user' => $this->user->getID(), "page_title not like 'Youtube%'", 'page_is_redirect' => 0, 'page_namespace' => NS_MAIN),
			__METHOD__,
			$order
		);

		if ($res) {
			foreach($res as $row) {
				$results[] = $row;
			}
		}

		if ($results) {
			foreach($results as $i => $row) {
				if($this->featuredArticles[$row->page_title])
					$row->fa = true;
				else
					$row->fa = false;

				$title = Title::makeTitle($row->page_namespace, $row->page_title);
				$rs = $dbr->selectField('pagelist', array('count(*)'), array('pl_page'=>$title->getArticleID(), 'pl_list'=>'risingstar')) > 0;
				if ($rs)
					$row->rs = true;
				else
					$row->rs = false;

				$row->title = $title;

				// People like Ttrimm can have 1200+ articles created, so we
				// want to make sure we don't generate that many db queries
				if ($i >= $MAX_INITIAL_DISPLAYED) break;
			}
		}

		$wgMemc->set($cacheKey, $results, 60*10);

		return $results;
	}



	private function initFeaturedArticles() {
		$dbr = wfGetDB(DB_SLAVE);

		$res = $dbr->select(array('templatelinks', 'page'), array('page_id', 'page_title', 'page_namespace'), array('tl_from = page_id', 'tl_title' => 'Fa'), __METHOD__);

		foreach($res as $row) {
			$this->featuredArticles[$row->page_title] = true;
		}
	}

	function fetchThumbsData($limit) {
		global $wgMemc, $wgUser;

		$isLoggedIn = $wgUser && !$wgUser->isAnon();
		$skin = $wgUser->getSkin();

		$cacheKey = wfMemcKey(ProfileStats::CACHE_PREFIX . 'thumbs', $this->user->getID(), $limit);
		$result = $wgMemc->get($cacheKey);

		if (!$this->isOwnPage && $result) {
			return $result;
		}

		$dbr = wfGetDB(DB_SLAVE);

		$order = array();
		$order['GROUP BY'] = 'thumb_rev_id';
		$order['ORDER BY'] = 'rev_id DESC';
		if ($limit) {
			$order['LIMIT'] = $limit + 1;
		}
		$res = $dbr->select(
			array('thumbs','page', 'revision'),
			array ('page_namespace', 'page_id', 'page_title', 'count(thumb_rev_id) as cnt', 'thumb_rev_id', 'rev_timestamp'),
			array ('thumb_recipient_id' => $this->user->getID(), 'thumb_exclude=0', 'thumb_page_id=page_id', 'thumb_rev_id = rev_id'),
			"",
			$order
		);

		if ($res) {
			foreach($res as $row) {
				$results[] = $row;
			}
		}
		
		$dbr->freeResult($res);

		if ($results) {
			foreach($results as $row) {
				$row->title = Title::newFromID($row->page_id);
				$row->text = wfTimeAgo($row->rev_timestamp);
				if ($isLoggedIn) {
					$row->text = $skin->makeKnownLinkObj($row->title, $row->text, 'diff=' . $row->thumb_rev_id . '&oldid=PREV');
				}
			}
		}

		$wgMemc->set($cacheKey, $results, 60*10);

		return $results;
	}

	function getBadges() {
		$groups = $this->user->getGroups();
		$rights = $this->user->getRights();
		if ( in_array( 'sysop', $groups ) )
			$response['admin'] = 1;
		else
			$response['admin'] = 0;

		if( in_array('newarticlepatrol', $rights ) )
			$response['nab'] = 1;
		else
			$response['nab'] = 0;

		$dbr = wfGetDB(DB_SLAVE);

		$resFA = $dbr->select(array('firstedit', 'templatelinks'), '*', array('fe_page=tl_from', 'fe_user' => $this->user->getID(), ('tl_title = "Fa" OR tl_title = "FA"') ), __FUNCTION__, array('GROUP BY' => 'fe_page') );
		$resRS = $dbr->select(array('firstedit', 'pagelist'), '*', array('fe_page=pl_page', 'fe_user' => $this->user->getID() ), __FUNCTION__, array('GROUP BY' => 'fe_page') );
		if($dbr->numRows($resFA) + $dbr->numRows($resRS) >= 5)
			$response['fa'] = 1;
		else
			$response['fa'] = 0;

		if( in_array( 'welcome_wagon', $groups )  )
			$response['welcome'] = 1;
		else
			$response['welcome'] = 0;

		return $response;
	}
	
	function getContributions() {
		global $wgTitle, $wgUser;
		
		$user = $this->user;
		$username = $user->getName();
		$real_name = User::whoIsReal($user->getId());
		$real_name = ($real_name) ? $real_name : $username;
		$contribsPage = SpecialPage::getTitleFor( 'Contributions', $username );
		
		$isLoggedIn = ($wgUser && $wgUser->getID() > 0);
		
		$userstats = "<div id='userstats'>";
		if ($user && $user->getID() > 0) {
			$editsMade = number_format(WikihowUser::getAuthorStats($username), 0, "", ",");
			if ($isLoggedIn) {
				$userstats .= wfMsg('contributions-made', $real_name, $editsMade, $contribsPage->getFullURL());
			} else {
				$userstats .= wfMsg('contributions-made-anon', $real_name, $editsMade);
			}
		} else { // showing an anon user page
			if ($isLoggedIn) {
				$link = '<a href="' . $contribsPage->getFullURL() . '">' . $wgTitle->getText() . '</a>';
				$userstats .= wfMsg('contributions-link', $link);
			}
		}
		$userstats .= "</div>";
		
		return $userstats;
	}
}

