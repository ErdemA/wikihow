<?php
/**
 * wikiHow html for article page and more
 *
 * @todo document
 * @package MediaWiki
 * @subpackage Skins
 */

if( !defined( 'MEDIAWIKI' ) )
	die();

/** */
global $IP;
require_once("$IP/includes/SkinTemplate.php");

/**
 * Inherit main code from SkinTemplate, set the CSS and template filter.
 * @todo document
 * @package MediaWiki
 * @subpackage Skins
 */
class SkinWikihowskin extends SkinTemplate {

	public $mSidebarWidgets = array();
	public $mSidebarTopWidgets = array();

	function initPage( &$out ) {
		SkinTemplate::initPage( $out );
		$this->skinname  = 'WikiHow';
		$this->stylename = 'WikiHow';
		$this->template  = 'WikiHowTemplate';
		
		//load the main msg file
		global $wgExtensionMessagesFiles, $IP;
		$wgExtensionMessagesFiles['wikiHow'] = "$IP/extensions/wikihow/wikiHow.i18n.php";
		wfLoadExtensionMessages('wikiHow');		
	}

	function addWidget($html, $class = '') {
		$display = "
	<div class='sidebox $class'>
		$html
	</div>\n";

		array_push($this->mSidebarWidgets, $display);
		return;
	}

	function addTopWidget($html, $class = '') {
		$display = "
	<div class='sidebox $class'>
		$html
	</div>\n";

		array_push($this->mSidebarTopWidgets, $display);
		return;
	}

	/*
	 * A mild hack to allow for the language appropriate 'How to' to be added to
	 * interwiki link titles. Note: German (de) is a straight pass-through
	 * since the 'How to' is already stored in the de database
	 */
	function getInterWikiLinkText($linkText, $langCode) {
		static $formatting = array(
			"ar" => "$1 كيفية",
			"de" => "$1",
			"es" => "Cómo $1",
			"en" => "How to $1",
			"fa" => "$1 چگونه",
			"fr" => "Comment $1",
			"he" => "$1 איך",
			"it" => "Come $1",
			"ja" => "$1（する）方法",
			"nl" => "$1",
			"pt" => "Como $1",
		);

		$result = $linkText;
		$format = $formatting[$langCode];
		if(!empty($format)) {
			$result = preg_replace("@(\\$1)@", $linkText, $format);
		}
		return $result;
	}

	function getInterWikiCTA($langCode, $linkText, $linkHref) {
		static $cta = array(
			'es' => '<a href="<link>">¿Te gustaría saber <title>? ¡Lee acerca de eso en español!</a>',
			'de' => '<a href="<link>">Lies auch unseren deutschen Artikel: <title>.</a>',
			'pt' => '<a href="<link>">Gostaria de aprender <title>? Leia sobre o assunto em português!</a>',
			'it' => '<a href="<link>">Ti piacerebbe sapere <title>? Leggi come farlo, in italiano!</a>',
			'fr' => '<a href="<link>">Voudriez-vous apprendre <title>? Découvrez comment le faire en le lisant en français!</a>',
			'nl' => '<a href="<link>">Wil je graag leren <title>? Lees erover in het Nederlands</a>',
		);
		$title = $this->getInterWikiLinkText($linkText, $langCode);
		$result = '';
		$linkHref .= '?utm_source=enwikihow&utm_medium=translatedcta&utm_campaign=translated';
		if ($title && isset($cta[$langCode])) {
			$title = '<i>' . $title . '</i>';
			$result = $cta[$langCode];
			$result = str_replace('<title>', $title, $result);
			$result = str_replace('<link>', $linkHref, $result);
		}
		return $result;
	}

	static function getFooterCategoryList() {
		global $wgOut, $wgMemc;

		$cachekey = wfMemcKey('footer_top_categories');
		$list = $wgMemc->get($cachekey);
		if (!$list) {
			$t = Title::makeTitle(NS_PROJECT, "Top Categories List");
			if (!$t) return '';
			$r = Revision::newFromTitle($t);
			if (!$r) return '';
			$text = $r->getText();
			if (!$text) return '';
			$list = $wgOut->parse($text);
			$wgMemc->set($cachekey, $list); // cache the output html
		}
		return $list;
	}

	function pageStats() {
		global $wgOut, $wgLang, $wgArticle, $wgRequest, $wgTitle;
		global $wgDisableCounters, $wgMaxCredits, $wgShowCreditsIfMax;

		extract( $wgRequest->getValues( 'oldid', 'diff' ) );
		if ( ! $wgOut->isArticle() ) { return ''; }
		if ( isset( $oldid ) || isset( $diff ) ) { return ''; }
		if ( $wgArticle == null || 0 == $wgArticle->getID() ) { return ''; }

		$s = '';
		if ( !$wgDisableCounters ) {
			$count = $wgLang->formatNum( $wgArticle->getCount() );
			if ( $count ) {
				if ($wgTitle->getNamespace() == NS_USER)
					$s = wfMsg( 'viewcountuser', $count );
				else
					$s = wfMsg( 'viewcount', $count );
			}
		}

		return $s;
	}

	function userTalkLink( $userId, $userText ) {
		global $wgLang;
		$talkname = wfMsg('talk'); //$wgLang->getNsText( NS_TALK ); # use the shorter name

		$userTalkPage = Title::makeTitle( NS_USER_TALK, $userText );
		$userTalkLink = $this->makeLinkObj( $userTalkPage, $talkname );
		return $userTalkLink;
	}

	/**
	 * @param $userId Integer: user id in database.
	 * @param $userText String: user name in database.
	 * @return string HTML fragment with talk and/or block links
	 * @private
	 */
	function userToolLinks( $userId, $userText ) {
		global $wgUser, $wgDisableAnonTalk, $wgSysopUserBans, $wgTitle, $wgLanguageCode, $wgRequest, $wgServer;
		$talkable = !( $wgDisableAnonTalk && 0 == $userId );
		$blockable = ( $wgSysopUserBans || 0 == $userId );

		$items = array();
		if ($talkable) {
			$items[] = $this->userTalkLink( $userId, $userText );
		}

		//XXMOD Added for quick note feature
		if ($wgTitle->getNamespace() != NS_SPECIAL &&
			$wgLanguageCode =='en' &&
			$wgRequest->getVal("diff", ""))
		{
			$items[] = QuickNoteEdit::getQuickNoteLink($wgTitle, $userId, $userText);
		}

		$contribsPage = SpecialPage::getTitleFor( 'Contributions', $userText );
		$items[] = $this->makeKnownLinkObj( $contribsPage,
			wfMsgHtml('contribslink') );

		if ($wgTitle->getNamespace() == NS_SPECIAL && $wgTitle->getText() == "Recentchanges" && $wgUser->isAllowed('patrol') ) {
			$contribsPage = SpecialPage::getTitleFor( 'Bunchpatrol', $userText );
			$items[] = $this->makeKnownLinkObj( $contribsPage , 'bunch' );
		}
		if ($blockable && $wgUser->isAllowed( 'block' )) {
			$items[] = $this->blockLink( $userId, $userText );
		}

		if ($items) {
			return ' (' . implode( ' | ', $items ) . ')';
		} else {
			return '';
		}
	}

	// Overloaded method from Skin
	function generateRollback( $rev ) {
		global $wgUser, $wgRequest, $wgTitle, $wgServer;
		$title = $rev->getTitle();

		$extraRollback = $wgRequest->getBool( 'bot' ) ? '&bot=1' : '';
		$extraRollback .= '&token=' . urlencode(
				$wgUser->editToken( array( $title->getPrefixedText(), $rev->getUserText() ) ) );

		if ($wgTitle->getNamespace() == NS_SPECIAL)
			return Skin::generateRollback($rev);

		$titleVal = $title->getLocalUrl();
		$titleVal = substr($titleVal, 1, strlen($titleVal));
		// Put urls in /index.php?title= form so we can bypass the varnish redirect rules for mobiel and tables
		$url = $wgServer . "/index.php?title={$titleVal}&action=rollback&from=" . urlencode( $rev->getUserText() ).  $extraRollback . "&useajax=true";
		$s = "<script type='text/javascript'>
				var gRollbackurl = \"{$url}\";

			</script>
			<script type='text/javascript' src='".wfGetPad('/extensions/min/f/extensions/wikihow/rollback.js?') . WH_SITEREV ."'></script>
			<span class='mw-rollback-link' id='rollback-link'>
			<script type='text/javascript'>
				var msg_rollback_complete = \"" . htmlspecialchars(wfMsg('rollback_complete')) . "\";
				var msg_rollback_fail = \"" . htmlspecialchars(wfMsg('rollback_fail')) . "\";
				var msg_rollback_inprogress = \"" . htmlspecialchars(wfMsg('rollback_inprogress')) . "\";
				var msg_rollback_confirm= \"" . htmlspecialchars(wfMsg('rollback_confirm')) . "\";
			</script>
				[<a href='' id='rollback-link' onclick='return rollback();'>" . wfMsg('rollbacklink') . "</a>]
			</span>";

		return $s;
	}

	function makeHeadline( $level, $attribs, $anchor, $text, $link ) {
		if ($level == '2') {
			return "<a name=\"$anchor\" class='anchor'></a><h$level$attribs $link<span>$text</span></h$level>";
		}
		return "<a name=\"$anchor\" class='anchor'></a><h$level$attribs <span>$text</span></h$level>";
	}

	function editSectionLink( $nt, $section, $hint='' ) {
		global $wgContLang, $wgLanguageCode;

		$editurl = '&section='.$section;
		$hint = ( $hint=='' ) ? '' : ' title="' . wfMsgHtml( 'editsectionhint', htmlspecialchars( $hint ) ) . '"';

		//INTL: Edit section buttons need to be bigger for intl sites
		$editSectionButtonClass = "edit";

		$url = $this->makeKnownLinkObj( $nt, wfMsg('editsection'), 'action=edit'.$editurl, '', '', 'id="gatEditSection" class="' . $editSectionButtonClass . '" onclick="gatTrack(gatUser,\'Edit\',\'Edit_section\');" ',  $hint );
		return $url;
	}

	function makeExternalLink( $url, $text, $escape = true, $linktype = '', $ns = null ) {
		$style = $this->getExternalLinkAttributes( $url, $text, 'external ' . $linktype );
		global $wgNoFollowLinks, $wgNoFollowNsExceptions;
		if( $wgNoFollowLinks && !(isset($ns) && in_array($ns, $wgNoFollowNsExceptions)) ) {
			$style .= ' rel="nofollow"';
		}
		$url = htmlspecialchars( $url );
		if( $escape ) {
			$text = htmlspecialchars( $text );
		}
		return '<a href="'.$url.'"'.$style.'>'.$text.'</a>';
	}

	function makeBrokenLinkObj( $nt, $text = '', $query = '', $trail = '', $prefix = '' ) {
		# Fail gracefully
		if ( ! isset($nt) ) {
			# wfDebugDieBacktrace();
			return "<!-- ERROR -->{$prefix}{$text}{$trail}";
		}

		wfProfileIn(__METHOD__);

		$u = $nt->getLocalURL();

		if ( '' == $text ) {
			$text = htmlspecialchars( $nt->getPrefixedText() );
		}
		if ($nt->getNamespace() >= 0)
			$style = $this->getInternalLinkAttributesObj( $nt, $text, "new" );
		else
			$style = $this->getInternalLinkAttributesObj( $nt, $text, "" );

		$inside = '';
		if ( '' != $trail ) {
			if ( preg_match( '/^([a-z]+)(.*)$$/sD', $trail, $m ) ) {
				$inside = $m[1];
				$trail = $m[2];
			}
		}
		$s = "<a href=\"{$u}\"{$style}>{$prefix}{$text}{$inside}</a>{$trail}";
		wfProfileOut(__METHOD__);
		return $s;
	}

	/**
	 * User links feature: users can get a list of their own links by specifying
	 * a list in User:username/Mylinks
	 */
	function getUserLinks() {
		global $wgUser, $wgParser, $wgTitle;
		$ret = "";
		if ($wgUser->getID() > 0) {
			$t = Title::makeTitle(NS_USER, $wgUser->getName() . "/Mylinks");
			if ($t->getArticleID() > 0) {
				$r = Revision::newFromTitle($t);
				$text = $r->getText();
				if ($text != "") {
					$ret = "<h3>" . wfMsg('mylinks') . "</h3>";
					$ret .= "<div id='my_links_list'>";
					$options = new ParserOptions();
					$output = $wgParser->parse($text, $wgTitle, $options);
					$ret .= $output->getText();
					$ret .= "</div>";
				}
			}
		}
		return $ret;
	}

	private function needsFurtherEditing(&$title) {
		$cats = $title->getParentCategories();
		if (is_array($cats) && sizeof($cats) > 0) {
			$keys = array_keys($cats);
			$templates = wfMsgForContent('templates_further_editing');
			$templates = split("\n", $templates);
			$templates = array_flip($templates); // switch all key/value pairs
			for ($i = 0; $i < sizeof($keys) && !$found; $i++) {
				$t = Title::newFromText($keys[$i]);
				if (isset($templates[$t->getText()]) ) {
					return true;
				}
			}
		}
		return false;
	}

	function getRelatedArticlesBox($e, $isBoxShape = false) {
		global $wgTitle, $wgContLang, $wgUser, $wgRequest, $wgMemc;

		if (!$wgTitle
			|| $wgTitle->getNamespace() != NS_MAIN
			|| $wgTitle->getFullText() == wfMsg('mainpage')
			|| $wgRequest->getVal('action') != '')
		{
			return '';
		}

		$cachekey = wfMemcKey('relarticles_box', intval($isBoxShape), $wgTitle->getArticleID());
		$val = $wgMemc->get($cachekey);
		if ($val) return $val;

		$cats = Categoryhelper::getCurrentParentCategories();
		$cat = '';
		if (is_array($cats) && sizeof($cats) > 0) {
			$keys = array_keys($cats);
			$templates = wfMsgForContent('categories_to_ignore');
			$templates = split("\n", $templates);
			$templates = str_replace("http://www.wikihow.com/Category:", "", $templates);
			$templates = array_flip($templates); // make the array associative.
			for ($i = 0; $i < sizeof($keys); $i++) {
				$t = Title::newFromText($keys[$i]);
				if (isset($templates[urldecode($t->getPartialURL())]) ) {
					continue;
				} else {
					$cat = $t->getDBKey();
					break;
				}
			}
		}
		// Populate related articles box with other articles in the category,
		// displaying the featured articles first
		$result = "";
		if (!empty($cat)) {
			$dbr = wfGetDB(DB_SLAVE);
			$num = intval(wfMsgForContent('num_related_articles_to_display'));
			$res = $dbr->select(array('categorylinks', 'page'),
				array('cl_from', 'page_is_featured, page_title'),
				array(
					'cl_from = page_id',
					'cl_to' => $cat,
					'page_namespace' => 0,
					'page_is_redirect' => 0,
					'(page_is_featured = 1 OR page_random > ' . wfRandom() . ')'
				),
				__METHOD__,
				array('ORDER BY' => 'page_is_featured DESC'));

			if ($isBoxShape) $result .= '<div class="related_square_row">';

			$count = 0;
			foreach ($res as $row) {
				if ($count >= $num) break;
				if ($row->cl_from == $wgTitle->getArticleID()) continue;

				$t = Title::newFromDBkey($row->page_title);
				if (!$t || $this->needsFurtherEditing($t)) continue;

				if ($isBoxShape) {
					//exit if there's a word that will be too long
					$word_array = explode(' ',$t->getText());
					foreach ($word_array as $word) {
						if (strlen($word) > 7) continue;
					}

					$data = $this->featuredArticlesAttrs($t, $t->getFullText(), 200, 162);
					$result .= $this->relatedArticlesBox($data,$num_cols);
					if ($count == 1) $result .= '</div><div class="related_square_row">';
				}
				else {
					//$data = $this->featuredArticlesAttrs($t, $t->getFullText());
					$result .= $this->getArticleThumb($t, 127, 140);
				}

				$count++;
			}

			if ($isBoxShape) $result .= '</div>';

			if (!empty($result)) {
				if ($isBoxShape) {
					$result = "<div id='related_squares'>$result\n</div>";
				}
				else {
					$result = "<h3>" . wfMsg('relatedarticles') . "</h3>$result<div class='clearall'></div>\n"; }
			}
		}
		$wgMemc->set($cachekey, $result, 3600);
		return $result;
	}

	function getGalleryImage($title, $width, $height, $skip_parser = false) {
		global $wgMemc, $wgLanguageCode, $wgContLang;

		$cachekey = wfMemcKey('gallery1', $title->getArticleID(), $width, $height);
		$val = $wgMemc->get($cachekey);
		if ($val) {
			return $val;
		}

		if (($title->getNamespace() == NS_MAIN) || ($title->getNamespace() == NS_CATEGORY) ) {
			if ($title->getNamespace() == NS_MAIN) {
				$file = Wikitext::getTitleImage($title, $skip_parser);

				if ($file && isset($file)) {
					//need to figure out what size it will actually be able to create
					//and put in that info. ImageMagick gives prefence to width, so
					//we need to see if it's a landscape image and adjust the sizes
					//accordingly
					$sourceWidth = $file->getWidth();
					$sourceHeight = $file->getHeight();
					$heightPreference = false;
					if($width/$height < $sourceWidth/$sourceHeight) {
						//desired image is portrait
						$heightPreference = true;
					}
					$thumb = $file->getThumbnail($width, $height, true, true, $heightPreference);
					if ($thumb instanceof MediaTransformError) {
						// we got problems!
						$thumbDump = print_r($thumb, true);
						wfDebug("problem getting thumb for article '{$title->getText()}' of size {$width}x{$height}, image file: {$file->getTitle()->getText()}, path: {$file->getPath()}, thumb: {$thumbDump}\n");
					} else {
						$wgMemc->set($cachekey, wfGetPad($thumb->url), 2* 3600); // 2 hours
						return wfGetPad($thumb->url);
					}
				}
			}

			$catmap = Categoryhelper::getIconMap();

			// if page is a top category itself otherwise get top
			if (isset($catmap[urldecode($title->getPartialURL())])) {
				$cat = urldecode($title->getPartialURL());
			} else {
				$cat = Categoryhelper::getTopCategory($title);

				//INTL: Get the partial URL for the top category if it exists
				// For some reason only the english site returns the partial
				// URL for getTopCategory
				if (isset($cat) && $wgLanguageCode != 'en') {
					$title = Title::newFromText($cat);
					if ($title) {
						$cat = $title->getPartialURL();
					}
				}
			}

			if (isset($catmap[$cat])) {
				$image = Title::newFromText($catmap[$cat]);
				$file = wfFindFile($image, false);
				$sourceWidth = $file->getWidth();
				$sourceHeight = $file->getHeight();
				$heightPreference = false;
				if($width/$height < $sourceWidth/$sourceHeight) {
					//desired image is portrait
					$heightPreference = true;
				}
				$thumb = $file->getThumbnail($width, $height, true, true, $heightPreference);
				if ($thumb) {
					$wgMemc->set($cachekey, wfGetPad($thumb->url),  2 * 3600); // 2 hours
					return wfGetPad($thumb->url);
				}
			} else {
				$image = Title::makeTitle(NS_IMAGE, "Book_266.png");
				$file = wfFindFile($image, false);
				if(!$file) {
					$file = wfFindFile("Book_266.png");
				}
				$sourceWidth = $file->getWidth();
				$sourceHeight = $file->getHeight();
				$heightPreference = false;
				if($width/$height < $sourceWidth/$sourceHeight) {
					//desired image is portrait
					$heightPreference = true;
				}
				$thumb = $file->getThumbnail($width, $height, true, true, $heightPreference);
				if ($thumb) {
					$wgMemc->set($cachekey, wfGetPad($thumb->url), 2 * 3600); // 2 hours
					return wfGetPad($thumb->url);
				}
			}
		}
	}

	function featuredArticlesLineWide($t) {
		$data = $this->featuredArticlesAttrs($t, $t->getText(), 103, 80);
		$html = "<td>
				<div>
				  <a href='{$data['url']}' class='rounders2 rounders2_tl rounders2_white'>
					<img src='{$data['img']}' alt='' width='103' height='80' class='rounders2_img' />
		  </a>
				  {$data['link']}
				</div>
			  </td>";

		return $html;
	}

	function getArticleThumb(&$t, $width, $height) {
		$html = "";
		$data = $this->featuredArticlesAttrs($t, $t->getText(), $width, $height);
		$html .= "<div class='thumbnail' style='width:{$width}px; height:{$height}px;'><a href='{$data['url']}'><img src='{$data['img']}' alt='' /><div class='text'><p>" . wfMsg('Howto','') . "<br /><span>{$t->getText()}</span></p></div></a></div>";

		return $html;
	}

	function getArticleThumbWithPath($t, $width, $height, $file) {
		$sourceWidth = $file->getWidth();
		$sourceHeight = $file->getHeight();
		$xScale = $width/$sourceWidth;
		if($height > $xScale*$sourceHeight)
			$heightPreference = true;
		else
			$heightPreference = false;
		$thumb = WatermarkSupport::getUnwatermarkedThumbnail($file, $width, $height, true, true, $heightPreference);
		//removed the fixed width for now
		$html = "<div class='thumbnail' ><a href='{$t->getFullUrl()}'><img src='" . wfGetPad($thumb->url) . "' alt='' /><div class='text'><p>" . wfMsg('Howto','') . "<br /><span>{$t->getText()}</span></p></div></a></div>";

		return $html;
	}

	private function featuredArticlesAttrs($title, $msg, $dimx = 44, $dimy = 33) {
		global $wgUser;
		$sk = $wgUser->getSkin();
		$link = $sk->makeKnownLinkObj($title, $msg);
		$img = $this->getGalleryImage($title, $dimx, $dimy);
		return array(
			'url' => $title->getLocalURL(),
			'img' => $img,
			'link' => $link,
			'text' => $msg,
		);
	}

	function featuredArticlesRow($data) {
		if (!is_array($data)) { // $data is actually a Title obj
			$data = $this->featuredArticlesAttrs($data, $data->getText());
		}
		$html = "<tr>
					<td class='thumb'>
						<a href='{$data['url']}'><img alt='' src='{$data['img']}' /></a>
					</td>
					<td>{$data['link']}</td>
				</tr>\n";
		return $html;
	}

	function relatedArticlesBox($data) {
		if (!is_array($data)) { // $data is actually a Title obj
			$data = $this->featuredArticlesAttrs($data, $data->getText());
		}

		if (strlen($data['text']) > 35) {
			//too damn long
			$the_title = substr($data['text'],0,32) . '...';
		}
		else {
			//we're good
			$the_title = $data['text'];
		}

		$html = '<a class="related_square" href="'.$data['url'].'" style="background-image:url('.$data['img'].')">
				<p><span>'.wfMsg('howto','').'</span>'.$the_title.'</p></a>';

		return $html;
	}

	function getNewArticlesBox() {
		global $wgMemc;
		$cachekey = wfMemcKey('newarticlesbox');
		$cached = $wgMemc->get($cachekey);
		if ($cached)  {
			return $cached;
		}
		$dbr = wfGetDB(DB_SLAVE);
		$ids = array();
		$res = $dbr->select('pagelist',
			'pl_page',
			array('pl_list'=>'risingstar'),
			__METHOD__,
			array('ORDER BY' => 'pl_page desc', 'LIMIT' => 5));
		while($row = $dbr->fetchObject($res)) {
			$ids[] = $row->pl_page;
		}
		$html = "<div id='side_new_articles'><h3>" . wfMsg('newarticles') . "</h3>\n<table>";
		if ($ids) {
			$res = $dbr->select(array('page'),
				array('page_namespace', 'page_title'),
				array('page_id IN (' . implode(",", $ids) . ")"),
				__METHOD__,
				array('ORDER BY' => 'page_id desc', 'LIMIT' => 5));
			foreach ($res as $row) {
				$t = Title::makeTitle(NS_MAIN, $row->page_title);
				if (!$t) continue;
				$html .= $this->featuredArticlesRow($t);
			}
		}
		$html .=  "</table></div>";
		$one_hour = 60 * 60;
		$wgMemc->set($cachekey, $html, $one_hour);
		return $html;
	}

	function getFeaturedArticlesBox($daysLimit = 11, $linksLimit = 4) {
		global $wgUser, $wgServer, $wgTitle, $IP, $wgMemc, $wgProdHost;

		$sk = $wgUser->getSkin();

		$cachekey = wfMemcKey('featuredbox', $daysLimit, $linksLimit);
		$result = $wgMemc->get($cachekey);
		if ($result) return $result;

		$feeds = FeaturedArticles::getFeaturedArticles($daysLimit);

		$html = "<h3><span onclick=\"location='" . wfMsg('featuredarticles_url') . "';\" style=\"cursor:pointer;\">" . wfMsg('featuredarticles') . "</span></h3>\n";

		$now = time();
		$popular = Title::makeTitle(NS_SPECIAL, "Popularpages");
		$randomizer = Title::makeTitle(NS_SPECIAL, "Randomizer");
		$count = 0;
		foreach ($feeds as $item) {
			$url = $item[0];
			$date = $item[1];
			if ($date > $now) continue;
			$url = str_replace("$wgServer/", "", $url);
			if ($wgServer != 'http://www.wikihow.com') {
				$url = str_replace("http://www.wikihow.com/", "", $url);
			}
			$url = str_replace("http://$wgProdHost/", "", $url);

			$title = Title::newFromURL(urldecode($url));
			if ($title) {
				//$html .= $this->featuredArticlesRow($title);
				$html .= $this->getArticleThumb($title, 126, 120);
			}
			$count++;
			if ($count >= $linksLimit) break;
		}

		// main page stuff
		if ($daysLimit > 8) {
			$data = $this->featuredArticlesAttrs($popular, wfMsg('populararticles'));
			$html .= $this->featuredArticlesRow($data);
			$data = $this->featuredArticlesAttrs($randomizer, wfMsg('or_a_random_article'));
			$html .= $this->featuredArticlesRow($data);
		}
		$html .= "<div class='clearall'></div>";

		// expires every 5 minutes
		$wgMemc->set($cachekey, $html, 5 * 60);

		return $html;
	}

	function getFeaturedArticlesBoxWide($daysLimit = 11, $linksLimit = 4, $isMainPage = true) {
		global $wgUser, $wgServer, $wgTitle, $IP, $wgProdHost;

		$sk = $wgUser->getSkin();
		$feeds = FeaturedArticles::getFeaturedArticles($daysLimit);
		$html = '';

		$html .= "<div class='featured_articles_inner' id='featuredArticles'>
		  <table class='featuredArticle_Table'><tr>";

		$hidden = "<div id='hiddenFA' style='display:none; zoom:1;'><div>
	<table class='featuredArticle_Table'><tr>";

		$now = time();
		$popular = Title::makeTitle(NS_SPECIAL, "Popularpages");
		$count = 0;
		foreach ($feeds as $item) {
			$url = $item[0];
			$date = $item[1];
			if ($date > $now) continue;
			$url = str_replace("$wgServer/", "", $url);
			if ($wgServer != 'http://www.wikihow.com') {
				$url = str_replace("http://www.wikihow.com/", "", $url);
			}
			$url = str_replace("http://$wgProdHost/", "", $url);

			$title = Title::newFromURL(urldecode($url));
			if ($title) {
				if ($count < $linksLimit)
					$html .= $this->featuredArticlesLineWide($title);
				else
					$hidden .= $this->featuredArticlesLineWide($title);

				$count++;
			}
			if ($count >= 2 * $linksLimit) {
				break;
			}
			if ($count % 5  == 0){
				if ($count < $linksLimit)
					$html .= "</tr><tr>";
				else
					$hidden .= "</tr><tr>";
			}
		}
		$html .= "</tr></table>";
		$hidden .= "</tr></table></div></div>";

		$html .= '</div></div>';

		return $html;
	}

	// overloaded from Skin class
	function drawCategoryBrowser($tree, &$skin, $count = 0) {
		$return = '';
		$queryString =  WikihowCategoryViewer::getViewModeParam();
		foreach ($tree as $element => $parent) {
			/*
			if ($element == "Category:WikiHow" ||
				$element == "Category:Featured-Articles" ||
				$element == "Category:Honors") {
					continue;
			}
			*/
				
			$count++;
			$start = ' ' . self::BREADCRUMB_SEPARATOR;
			
			/*
			//not too many...
			if ($count > self::BREADCRUMB_LIMIT && !self::$bShortened) {
				$return .= '<li class="bread_ellipsis"><span>'.$start.'</span> ... </li>';
				self::$bShortened = true;
				break;
			}
			*/
			
			$eltitle = Title::NewFromText($element);
			if (empty($parent)) {
				# element start a new list
				$return .= "\n";
			} else {
				# grab the others elements
				$return .= $this->drawCategoryBrowser($parent, $skin, $count) ;
			}
			# add our current element to the list
			$return .=  "<li>$start " . $skin->makeLinkObj( $eltitle, $eltitle->getText(), $queryString)  . "</li>" ;
		}
		return $return;
	}

	const BREADCRUMB_SEPARATOR = '&raquo;';
	const BREADCRUMB_LIMIT = 2;
	static $bShortened = false;

	function getCategoryLinks($usebrowser) {
		global $wgOut, $wgUser, $wgContLang;

		if( !$usebrowser && count( $wgOut->mCategoryLinks ) == 0 ) return '';

		// Use Unicode bidi embedding override characters,
		// to make sure links don't smash each other up in ugly ways.
		$dir = $wgContLang->isRTL() ? 'rtl' : 'ltr';
		$embed = "<span dir='$dir'>";
		$pop = '</span>';
		$t = $embed . implode ( "{$pop} | {$embed}" , $wgOut->mCategoryLinks ) . $pop;
		if (!$usebrowser)
			return $t;

		$mainPageObj = Title::newMainPage();
		$sk = $wgUser->getSkin();

		$sep = self::BREADCRUMB_SEPARATOR;

		$queryString =  WikihowCategoryViewer::getViewModeParam();
		$categories = $sk->makeLinkObj(Title::newFromText('Special:Categorylisting'), wfMsg('categories'), $queryString);
		$s = "<li class='home'>" . $sk->makeLinkObj($mainPageObj, wfMsg('home')) . "</li> <li>$sep $categories</li>";

		# optional 'dmoz-like' category browser. Will be shown under the list
		# of categories an article belong to
		if($usebrowser) {
			$s .= ' ';

			# get a big array of the parents tree
			$parenttree = Categoryhelper::getCurrentParentCategoryTree();
			if (is_array($parenttree)) {
				$parenttree = array_reverse($parenttree);
			} else {
				return $s;
			}
			# Skin object passed by reference cause it can not be
			# accessed under the method subfunction drawCategoryBrowser
			$tempout = explode("\n", $this->drawCategoryBrowser($parenttree, $this) );
			$newarray = array();
			foreach ($tempout as $t) {
				if (trim($t) != "") { $newarray[] = $t; }
			}
			$tempout = $newarray;

			asort($tempout);
			$olds = $s;
			$s .= $tempout[0]; // this usually works

			if (strpos($s, "/Category:WikiHow") !== false
                || strpos($s, "/Category:Featured") !== false
                || strpos($s, "/Category:Nomination") !== false
            ) {
                for ($i = 1; $i <= sizeof($tempout); $i++) {
                    if (strpos($tempout[$i], "/Category:WikiHow") === false
                    && strpos($tempout[$i], "/Category:Featured") == false
                    && strpos($tempout[$i], "/Category:Nomination") == false
                    ) {
                        $s = $olds;
                        $s .= $tempout[$i];
                        break;
                    }
                }
            }

		}
		return $s;
	}

	function suppressH1Tag() {
		global $wgTitle, $wgLang;
		$titleText = $wgTitle->getFullText();

		if ($titleText == wfMsg('mainpage'))
			return true;
		if ($titleText == $wgLang->specialPage("Userlogin"))
			return true;
			
		return false;
	}

	function getSiteNotice() {
		global $wgUser, $wgRequest;

		if (!$wgUser->hasCookies() && $wgRequest->getVal('c') == 't') {
			$siteNotice = wfMsgExt('sitenotice_cachedpage', 'parse');
		// } elseif (!$wgUser->isAnon() && wfMsg('sitenotice_loggedin') != '-') {
			// $siteNotice = wfMsgExt('sitenotice_loggedin', 'parse');
		} elseif (!$wgUser->isAnon()) {
			if (wfMsg('sitenotice_loggedin') == '-' || wfMsg('sitenotice_loggedin') == '') return '';
			$siteNotice = wfMsgExt('sitenotice_loggedin', 'parse');
		} else {
			// $siteNotice = wfGetSiteNotice();
			if (wfMsg('sitenotice') == '-' || wfMsg('sitenotice') == '') return '';
			$siteNotice = wfMsgExt('sitenotice', 'parse');
		}
		
		$x = '<a href="#" id="site_notice_x"></a>';
		
		// format here so there's no logic later
		$count = 0;
		$siteNotice = preg_replace('@^\s*(<p>\s*)?important\s+@i', '', $siteNotice, 1, $count);
		$colorClassName = $count == 0 ? 'notice_bgcolor' : 'notice_bgcolor_important';

		$siteNotice = "<div id='site_notice' class='sidebox $colorClassName'>$x$siteNotice</div>";
		
		return $siteNotice;
	}

	/**
	 * Calls the MobileWikihow class to determine whether or
	 * not a browser's User-Agent string is that of a mobile browser.
	 */
	static function isUserAgentMobile() {
		if (class_exists('MobileWikihow')) {
			return MobileWikihow::isUserAgentMobile();
		} else {
			return false;
		}
	}

	/**
	 * Calls the WikihowCSSDisplay class to determine whether or
	 * not to display a "special" background.
	 */
	static function isSpecialBackground() {
		if (class_exists('WikihowCSSDisplay')) {
			return WikihowCSSDisplay::isSpecialBackground();
		} else {
			return false;
		}
	}

	/**
	 * Calls any hooks in place to see if a module has requested that the
	 * right rail on the site shouldn't be displayed.
	 */
	static function showSideBar() {
		global $wgTitle;
		$result = true;
		wfRunHooks('ShowSideBar', array(&$result));
		return $result;
	}

	/**
	 * Calls any hooks in place to see if a module has requested that the
	 * bread crumb (category) links at the top of the article shouldn't
	 * be displayed.
	 */
	static function showBreadCrumbs() {
		global $wgTitle, $wgRequest;
		$result = true;
		wfRunHooks('ShowBreadCrumbs', array(&$result));
		if ($result) {
			$namespace = $wgTitle ? $wgTitle->getNamespace() : NS_MAIN;
			$action = $wgRequest ? $wgRequest->getVal('action') : '';
			$goodAction = empty($action) || $action == 'view';
			if (!in_array($namespace, array(NS_CATEGORY, NS_MAIN, NS_SPECIAL)) || !$goodAction) {
				$result = false;
			}
		}
		return $result;
	}
	
	/**
	 * Calls any hooks in place to see if a module has requested that the
	 * right rail on the site shouldn't be displayed.
	 */
	static function showGrayContainer() {
		global $wgTitle, $wgRequest;
		$result = true;
		wfRunHooks('ShowGrayContainer', array(&$result));
		
		$action = $wgRequest ? $wgRequest->getVal('action') : '';
		
		if ($wgTitle->exists() || $wgTitle->getNamespace() == NS_USER) {
			if ($wgTitle->getNamespace() == NS_USER ||
				$wgTitle->getNameSpace() == NS_IMAGE || 
				$wgTitle->getNameSpace() == NS_CATEGORY || 
				($wgTitle->getNamespace == NS_MAIN) && ($action == 'edit' || $action == 'submit2')) {
					$result = false;
			}
		}
		return $result;
	}


	static function getTabsArray($showArticleTabs) {
		global $wgTitle, $wgUser, $wgRequest;

		$action = $wgRequest->getVal('action', 'view');
		if ($wgRequest->getVal('diff')) $action = 'diff';
		$skin = $wgUser->getSkin();

		$tabs = array();

		wfRunHooks('pageTabs', array(&$tabs));

		if(count($tabs) > 0) {
			return $tabs;
		}

		if(!$showArticleTabs)
			return;

		//article
		if ($wgTitle->getNamespace() != NS_CATEGORY) {
			$articleTab->href = $wgTitle->isTalkPage()?$wgTitle->getSubjectPage()->getFullURL():$wgTitle->getFullURL();
			$articleTab->text = $wgTitle->getSubjectPage()->getNamespace() == NS_USER?wfMsg("user"):wfMsg("article");
			$articleTab->class = (!MWNamespace::isTalk($wgTitle->getNamespace()) && $action != "edit" && $action != "history")?"on":"";
			$articleTab->id = "tab_article";
			$tabs[] = $articleTab;
		}

		//edit
		if ($wgTitle->getNamespace() != NS_CATEGORY && (!in_array($wgTitle->getNamespace(), array(NS_USER, NS_USER_TALK, NS_IMAGE)) || $action == 'edit' || $wgUser->getID() > 0)) {
			$editTab->href = $wgTitle->escapeLocalURL($skin->editUrlOptions());
			$editTab->text = wfMsg('edit');
			$editTab->class = ($action == "edit")?"on":"";
			$editTab->id = "tab_edit";
			$tabs[] = $editTab;
		}

		//talk
		if ($wgTitle->getNamespace() != NS_CATEGORY) {
			if ($action =='view' && MWNamespace::isTalk($wgTitle->getNamespace())) {
				$talklink = '#postcomment';
			} else {
				$talklink = $wgTitle->getTalkPage()->getLocalURL();
			}
			if (in_array($wgTitle->getNamespace(), array(NS_USER, NS_USER_TALK))) {
				$msg = wfMsg('talk');
			} else {
				$msg = wfMsg('discuss');
			}
			$talkTab->href = $talklink;
			$talkTab->text = $msg;
			$talkTab->class = ($wgTitle->isTalkPage() && $action != "edit" && $action != "history")?"on":"";
			$talkTab->id = "tab_discuss";
			$tabs[] = $talkTab;
		}

		//history
		if (!$wgUser->isAnon() && $wgTitle->getNamespace() != NS_CATEGORY) {
			$historyTab->href = $wgTitle->getLocalURL( 'action=history' );
			$historyTab->text = wfMsg('history');
			$historyTab->class = ($action == "history")?"on":"";
			$historyTab->id = "tab_history";
			$tabs[] = $historyTab;
		}

		//for category page: link for image view
		if (!$wgUser->isAnon() && $wgTitle->getNamespace() == NS_CATEGORY) {
			$imageViewTab->href = $wgTitle->getLocalURL();
			$imageViewTab->text = wfMsg('image_view');
			$imageViewTab->class = $wgRequest->getVal('viewMode', 0) ? "" : "on";
			$imageViewTab->id = "tab_image_view";
			$tabs[] = $imageViewTab;
		}

		// For category page: link for text view
		if (!$wgUser->isAnon() && $wgTitle->getNamespace() == NS_CATEGORY) {
			$textViewTab->href = $wgTitle->getLocalURL('viewMode=text');
			$textViewTab->text = wfMsg('text_view');
			$textViewTab->class = $wgRequest->getVal('viewMode', 0) ? "on" : "";
			$textViewTab->id = "tab_text_view";
			$tabs[] = $textViewTab;
		}

		//admin
		if ($wgUser->isSysop() && $wgTitle->userCan('delete')) {
			$adminTab->href = "#";
			$adminTab->text = wfMsg('admin_admin');
			$adminTab->class = "";
			$adminTab->id = "tab_admin";
			$adminTab->hasSubMenu = true;
			$adminTab->subMenuName = "AdminOptions";

			$adminTab->subMenu = array();
			$admin1->href = $wgTitle->getLocalURL( 'action=protect' );
			$admin1->text = !$wgTitle->isProtected() ? wfMsg('protect') : wfMsg('unprotect');
			$adminTab->subMenu[] = $admin1;
			$admin2->href = SpecialPage::getTitleFor("Movepage", $wgTitle)->getLocalURL() .
				($wgTitle->getNamespace() == NS_IMAGE ? '&=' . time() : '');
			$admin2->text = wfMsg('admin_move');
			$adminTab->subMenu[] = $admin2;
			$admin3->href = $wgTitle->getLocalURL( 'action=delete' );
			$admin3->text = wfMsg('admin_delete');
			$adminTab->subMenu[] = $admin3;

			$tabs[] = $adminTab;
		}

		return $tabs;


	}

	function getTabsHtml($tabs) {
		$html = "";

		if(count($tabs) > 0) {
			$html .= "<div id='article_tabs'>";
			$html .= "<ul id='tabs'>";
			foreach($tabs as $tab) {
				$attributes = "";
				foreach($tab as $attribute => $value) {
					if($attribute != "text") {
						$attributes .= " {$attribute}='{$value}'";
					}
				}

				$html .= "<li><a {$attributes}>{$tab->text}</a>";
				//$activeClass = $tab->active?'on':'';
				//$html .= "<li><a href='{$tab->link}' id='{$tab->id}' class='{$activeClass}'>{$tab->text}";
				if($tab->hasSubMenu) {
					$html .= "<span class='admin_arrow'></span>";
					$html .= "<div id='{$tab->subMenuName}' class='menu'>";
					foreach($tab->subMenu as $subTab) {
						$html .= "<a href='{$subTab->href}'/>{$subTab->text}</a>";
					}
					$html .= "</div>";
				}
				$html .= "</li>";
			}
			$html .= "</ul></div>";
		}

		return $html;
	}

	/**
	 * Calls any hooks in place to see if a module has requested that the
	 * bread crumb (category) links at the top of the article shouldn't
	 * be displayed.
	 */
	static function showHeadSection() {
		global $wgTitle;
		$result = true;
		wfRunHooks('ShowHeadSection', array(&$result));

		// Don't show head section in wikiHow:Tour pages
		if ($wgTitle->getNamespace() == NS_PROJECT
			&& stripos($wgTitle->getPrefixedText(),'wikiHow:Tour') !== false)
		{
			$result = false;
		}
		return $result;
	}

	/*
	static function genNavigationMenu() {
		global $wgTitle, $wgUser, $wgRequest;
		global $wgLang, $wgLanguageCode, $wgForumLink;

		$logoutPage = $wgLang->specialPage("Userlogout");
		$returnTarget = $wgTitle->getPrefixedURL();
		$returnto = strcasecmp( urlencode($logoutPage), $returnTarget ) ? "returnto={$returntarget}" : "";

		$isLoggedIn = $wgUser->getID() > 0;
		$action = $wgRequest->getVal('action', 'view');
		$sk = $wgUser->getSkin();

		$isMainPage = $wgTitle
			&& $wgTitle->getNamespace() == NS_MAIN
			&& $wgTitle->getText() == wfMsg('mainpage')
			&& $action == 'view';

		$navigation = "
		<div class='sidebox' id='side_nav'>" . $sk->getUserLinks() . "</div>";

		return $navigation;
	}
	*/

	static function genNavigationTabs() {
		global $wgUser, $wgTitle, $wgLanguageCode;
		
		$sk = $wgUser->getSkin();

		$isLoggedIn = $wgUser->getID() > 0;
		$mainPage = Title::newMainPage();
		$loginPage = Title::makeTitle(NS_SPECIAL, "Userlogin");
		$dashboardPage = $wgLanguageCode == 'en' ? Title::makeTitle(NS_SPECIAL, "CommunityDashboard") : Title::makeTitle(NS_PROJECT, wfMsg("community"));
		$communityPage = Title::makeTitle(NS_PROJECT, "Community");

		$navTabs = array(
			'nav_messages'	=> array('menu' => $sk->getHeaderMenu('messages'), 'link' => '#', 'text' => wfMsg('navbar_messages')),
			'nav_profile'   => array('menu' => $sk->getHeaderMenu('profile'), 'link' => $isLoggedIn ? $wgUser->getUserPage()->getLocalURL() : '#', 'text' => $isLoggedIn ? strtoupper(wfMsg('navbar_profile')) : strtoupper(wfMsg('login'))),
			'nav_explore'	=> array('menu' => $sk->getHeaderMenu('explore'), 'link' => '#', 'text' => wfMsg('navbar_explore')),
			'nav_help'		=> array('menu' => $sk->getHeaderMenu('help'), 'link' => '#', 'text' => wfMsg('navbar_help'))
		);

		if ($wgTitle
			&& $wgTitle->getNamespace() == NS_MAIN
			&& $wgTitle->getText() != wfMsgForContent('mainpage')
			&& $wgTitle->userCanEdit()
			&& $wgLanguageCode == 'en')
		{
			$editPage = $wgTitle->escapeLocalURL($sk->editUrlOptions());
			$navTabs['nav_edit'] = array('menu' => $sk->getHeaderMenu('edit'), 'link'=> $editPage, 'text'=> strtoupper(wfMsg('edit')));
		}

		return $navTabs;
	}
	
	function getHeaderMenu($menu) {
		global $wgLanguageCode, $wgTitle, $wgUser, $wgForumLink;
		
		$html = '';
		$menu_css = 'menu';
		$sk = $wgUser->getSkin();
		$isLoggedIn = $wgUser->getID() > 0;
		
		switch ($menu) {
			case 'edit':
				$html = "<a href='" . $wgTitle->escapeLocalURL($sk->editUrlOptions()) . "'>" . wfMsg('edit-this-article') . "</a>";
				if (!$isLoggedIn) break;
				$html .= $sk->makeLinkObj( SpecialPage::getTitleFor( 'Importvideo', $wgTitle->getText() ), wfMsg('importvideo'));
				if ($wgLanguageCode == 'en') {
					$html .= $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "RelatedArticle"), wfMsg('manage_related_articles'), "target=" . $wgTitle->getPrefixedURL()) .
							$sk->makeLinkObj(SpecialPage::getTitleFor("Articlestats", $wgTitle->getText()), wfMsg('articlestats'));
				}
				$html .= "<a href='" . Title::makeTitle(NS_SPECIAL, "Whatlinkshere")->getLocalUrl() . "/" . $wgTitle->getPrefixedURL() . "'>" . wfMsg('whatlinkshere') . "</a>";
				break;
			case 'profile':
				if ($isLoggedIn) {
					$html = $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, 'Mytalk'), wfMsg('mytalkpage'), '#post' ) .
							$sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, 'Mypage'), wfMsg('myauthorpage') ) .
							$sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, 'Watchlist'), wfMsg('watchlist') ) .
							$sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, 'Drafts'), wfMsg('mydrafts') ) .
							$sk->makeLinkObj(SpecialPage::getTitleFor('Mypages', 'Contributions'),  wfMsg ('mycontris')) .
							$sk->makeLinkObj(SpecialPage::getTitleFor('Mypages', 'Fanmail'),  wfMsg ('myfanmail')) .
							$sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, 'Preferences'), wfMsg('mypreferences') ) .
							$sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, 'Userlogout'), wfMsg('logout') );
				}
				else {
					$html = UserLoginBox::getLogin(true);
					$menu_css = 'menu_login';
				}
				break;
			case 'explore':				
				$dashboardPage = $wgLanguageCode == 'en' ? Title::makeTitle(NS_SPECIAL, "CommunityDashboard") : Title::makeTitle(NS_PROJECT, wfMsg("community"));
				$html = $sk->makeLinkObj($dashboardPage,wfMsg('community_dashboard'));
				if ($isLoggedIn) {
					$html .= "<a href='$wgForumLink'>" . wfMsg('forums') . "</a>";
				}
				$html .= "<a href='/Special:Randomizer'>".wfMsg('randompage')."</a>";
				if (!$isLoggedIn) {
					$html .= $sk->makeLinkObj(Title::makeTitle(NS_PROJECT, "About-wikiHow"), wfMsg('navmenu_aboutus'));
				}
				$html .= $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "Categorylisting"), wfMsg('navmenu_categories')) .
						$sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "Recentchanges"), wfMsg('recentchanges'));
				if ($isLoggedIn) { 
					$html .= $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "Specialpages"), wfMsg('specialpages'));
					$html .= $sk->makeLinkObj(Title::makeTitle(NS_PROJECT_TALK, 'Help-Team'), wfMsg('help'));
				}
				break;
			case 'help':
				$html = $sk->makeLinkObj( Title::makeTitle(NS_SPECIAL, "CreatePage"), wfMsg('Write-an-article') );
				if ($wgLanguageCode == 'en') {
					$html .= $sk->makeLinkObj( Title::makeTitle(NS_SPECIAL, "RequestTopic"), wfMsg('requesttopic') ) .
							$sk->makeLinkObj( Title::makeTitle(NS_SPECIAL, "ListRequestedTopics"), wfMsg('listrequtestedtopics') );
				}

				if ($isLoggedIn) { 
					if ($wgLanguageCode == 'en') {	
						$html .= $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "TipsPatrol"), wfMsg('navmenu_tipspatrol')); 
					}
					$html .= $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "RCPatrol"), wfMsg('PatrolRC')); 
					if ($wgLanguageCode == 'en') {
						$html .= $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "Categorizer"), wfMsg('categorize_articles'));
					}
				}

				if ($wgLanguageCode == 'en') {
					$html .= "<a href='/Special:CommunityDashboard'>" . wfMsg('more-ideas') . "</a>";
				} else {
					$html .= $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "Uncategorizedpages"), wfMsg('categorize_articles')) .
							"<a href='/Contribute-to-wikiHow'>" . wfMsg('more-ideas') . "</a>";
				}
				break;
			case 'messages':
				list($html,$this->notifications_count) = Notifications::loadNotifications();
				$menu_css = 'menu_messages';
				break;
		}
		
		if ($html) $html = '<div class="'.$menu_css.'">'.$html.'</div>';
		return $html;
	}

	function outputPage(&$out) {
		global $wgTitle, $wgArticle, $wgUser, $wgLang, $wgContLang, $wgOut;
		global $wgScript, $wgStylePath, $wgLanguageCode, $wgContLanguageCode;
		global $wgMimeType, $wgOutputEncoding, $wgUseDatabaseMessages;
		global $wgRequest, $wgUseNewInterlanguage;
		global $wgDisableCounters, $wgLogo, $action, $wgFeedClasses;
		global $wgMaxCredits, $wgShowCreditsIfMax, $wgSquidMaxage, $IP;
		global $wgServer;

		$fname = __METHOD__;
		wfProfileIn( $fname );

		wfRunHooks( 'BeforePageDisplay', array(&$wgOut) );
		$this->mTitle = $wgTitle;

		extract( $wgRequest->getValues( 'oldid', 'diff' ) );

		wfProfileIn( "$fname-init" );
		$this->initPage( $out );
		$tpl =& $this->setupTemplate( $this->template, 'skins' );

		$tpl->setTranslator(new MediaWiki_I18N());
		wfProfileOut( "$fname-init" );

		wfProfileIn( "$fname-stuff" );
		$this->thispage = $wgTitle->getPrefixedDbKey();
		$this->thisurl = $wgTitle->getPrefixedURL();
		$this->loggedin = $wgUser->getID() != 0;
		$this->iscontent = ($wgTitle->getNamespace() != NS_SPECIAL );
		$this->iseditable = ($this->iscontent and !($action == 'edit' or $action == 'submit'));
		$this->username = $wgUser->getName();
		$this->userpage = $wgContLang->getNsText(NS_USER) . ":" . $wgUser->getName();
		$this->userpageUrlDetails = $this->makeUrlDetails($this->userpage);

		$this->usercss =  $this->userjs = $this->userjsprev = false;
		$this->setupUserCss();
		$this->setupUserJs(false);
		$this->titletxt = $wgTitle->getPrefixedText();
		wfProfileOut( "$fname-stuff" );

		// add utm

		wfProfileIn( "$fname-stuff2" );
		$tpl->set( 'title', $wgOut->getPageTitle() );
		$tpl->set( 'pagetitle', $wgOut->getHTMLTitle() );

		$tpl->setRef( "thispage", $this->thispage );
		$subpagestr = $this->subPageSubtitle();
		$tpl->set(
			'subtitle',  !empty($subpagestr)?
				'<span class="subpages">'.$subpagestr.'</span>'.$out->getSubtitle():
				$out->getSubtitle()
		);
		$undelete = $this->getUndeleteLink();
		$tpl->set(
			"undelete", !empty($undelete)?
				'<span class="subpages">'.$undelete.'</span>':
				''
		);

		$description = ArticleMetaInfo::getCurrentTitleMetaDescription();
		if ($description) {
			$wgOut->addMeta('description', htmlspecialchars($description));
		}
		$keywords = ArticleMetaInfo::getCurrentTitleMetaKeywords();
		if ($keywords) {
			$wgOut->mKeywords = array();
			$wgOut->addMeta('keywords', $keywords);
		}

		ArticleMetaInfo::addFacebookMetaProperties($tpl->data['title']);
		$title = wfMsg('howto', $tpl->data['title']);

		if( $wgOut->isSyndicated() ) {
			$feeds = array();
			foreach( $wgFeedClasses as $format => $class ) {
				$feeds[$format] = array(
					'text' => $format,
					'href' => $wgRequest->appendQuery( "feed=$format" ),
					'ttip' => wfMsg('tooltip-'.$format)
				);
			}
			$tpl->setRef( 'feeds', $feeds );
		} else {
			$tpl->set( 'feeds', false );
		}
		$tpl->setRef( 'mimetype', $wgMimeType );
		$tpl->setRef( 'charset', $wgOutputEncoding );
		$tpl->set( 'headlinks', $out->getHeadLinks() );
		$tpl->setRef( 'wgScript', $wgScript );
		$tpl->setRef( 'skinname', $this->skinname );
		$tpl->setRef( 'stylename', $this->stylename );
		$tpl->setRef( 'loggedin', $this->loggedin );
		$tpl->set('nsclass', 'ns-'.$wgTitle->getNamespace());
		$tpl->set('notspecialpage', $wgTitle->getNamespace() != NS_SPECIAL);
		/* XXX currently unused, might get useful later
		$tpl->set( "editable", ($wgTitle->getNamespace() != NS_SPECIAL ) );
		$tpl->set( "exists", $wgTitle->getArticleID() != 0 );
		$tpl->set( "watch", $wgTitle->userIsWatching() ? "unwatch" : "watch" );
		$tpl->set( "protect", count($wgTitle->isProtected()) ? "unprotect" : "protect" );
		$tpl->set( "helppage", wfMsg('helppage'));
		*/
		$tpl->set( 'searchaction', $this->escapeSearchLink() );
		$tpl->set( 'search', trim( $wgRequest->getVal( 'search' ) ) );
		$tpl->setRef( 'stylepath', $wgStylePath );
		$tpl->setRef( 'logopath', $wgLogo );
		$tpl->setRef( "lang", $wgContLanguageCode );
		$tpl->set( 'dir', $wgContLang->isRTL() ? "rtl" : "ltr" );
		$tpl->set( 'rtl', $wgContLang->isRTL() );
		$tpl->set( 'langname', $wgContLang->getLanguageName( $wgContLanguageCode ) );
		$tpl->setRef( 'username', $this->username );
		$tpl->setRef( 'userpage', $this->userpage);
		$tpl->setRef( 'userpageurl', $this->userpageUrlDetails['href']);
		$tpl->setRef( 'usercss', $this->usercss);
		$tpl->setRef( 'userjs', $this->userjs);
		$tpl->setRef( 'userjsprev', $this->userjsprev);

		if( $this->iseditable && $wgUser->getOption( 'editsectiononrightclick' ) ) {
			$tpl->set( 'body_onload', 'setupRightClickEdit()' );
		} else {
			$tpl->set( 'body_onload', false );
		}
		global $wgUseSiteJs;
		if ($wgUseSiteJs) {
			if($this->loggedin) {
				$tpl->set( 'jsvarurl', $this->makeUrl($this->userpage.'/-','action=raw&gen=js&maxage=' . $wgSquidMaxage) );
			} else {
				$tpl->set( 'jsvarurl', $this->makeUrl('-','action=raw&gen=js') );
			}
		} else {
			$tpl->set('jsvarurl', false);
		}

		wfProfileOut( "$fname-stuff2" );

		wfProfileIn( "$fname-stuff3" );
		$tpl->setRef( 'newtalk', $ntl );
		$tpl->setRef( 'skin', $this);
		$tpl->set( 'logo', $this->logoText() );
		if ( $wgOut->isArticle() and (!isset( $oldid ) or isset( $diff )) and ($wgArticle != null && 0 != $wgArticle->getID() )) {
			if ( !$wgDisableCounters ) {
				$viewcount =  $wgArticle->getCount() ;
				if ( $viewcount ) {
					$tpl->set('viewcount', wfMsg( "viewcount", $viewcount ));
				} else {
					$tpl->set('viewcount', false);
				}
			} else {
				$tpl->set('viewcount', false);
			}
			$tpl->set('lastmod', $this->lastModified());
			$tpl->set('copyright',$this->getCopyright());

			$this->credits = false;

			if (isset($wgMaxCredits) && $wgMaxCredits != 0) {
				require_once("$IP/includes/Credits.php");
				$this->credits = getCredits($wgArticle, $wgMaxCredits, $wgShowCreditsIfMax);
			}

			$tpl->setRef( 'credits', $this->credits );

		} elseif ( isset( $oldid ) && !isset( $diff ) ) {
			$tpl->set('copyright', $this->getCopyright());
			$tpl->set('viewcount', false);
			$tpl->set('lastmod', false);
			$tpl->set('credits', false);
		} else {
			$tpl->set('copyright', false);
			$tpl->set('viewcount', false);
			$tpl->set('lastmod', false);
			$tpl->set('credits', false);
		}
		wfProfileOut( "$fname-stuff3" );

		wfProfileIn( "$fname-stuff4" );
		$tpl->set( 'copyrightico', $this->getCopyrightIcon() );
		$tpl->set( 'poweredbyico', $this->getPoweredBy() );
		$tpl->set( 'disclaimer', $this->disclaimerLink() );
		$tpl->set( 'about', $this->aboutLink() );

		$tpl->setRef( 'debug', $out->mDebugtext );

		//$out->addHTML($printfooter);
		if($wgTitle->getNamespace() == NS_USER && $wgUser->getId() == 0 && !UserPagePolicy::isGoodUserPage($wgTitle->getDBKey())) {
			$txt = $out->parse(wfMsg('noarticletext_user'));
			$tpl->setRef('bodytext', $txt);
			header('HTTP/1.1 404 Not Found');
		}
		else {
			$tpl->setRef( 'bodytext', $out->getHTML() );
		}

		# Language links
		$language_urls = array();
		if ( !$wgHideInterlanguageLinks ) {
			foreach( $wgOut->getLanguageLinks() as $l ) {
				$tmp = explode( ':', $l, 2 );
				$class = 'interwiki-' . $tmp[0];
				$code = $tmp[0];
				$lTitle = $tmp[1];
				unset($tmp);
				$nt = Title::newFromText( $l );
				$language_urls[] = array(
					'code' => $code,
					'href' => $nt->getFullURL(),
					'text' =>  $lTitle,
					'class' => $class,
					'language' => ($wgContLang->getLanguageName( $nt->getInterwiki()) != ''?$wgContLang->getLanguageName( $nt->getInterwiki()) : $l) . ": "
				);
			}
		}
		if(count($language_urls)) {
			$tpl->setRef( 'language_urls', $language_urls);
		} else {
			$tpl->set('language_urls', false);
		}
		wfProfileOut( "$fname-stuff4" );

		# Personal toolbar
		$tpl->set('personal_urls', $this->buildPersonalUrls());
		$content_actions = $this->buildContentActionUrls();
		$tpl->setRef('content_actions', $content_actions);

		// XXX: attach this from javascript, same with section editing
		if($this->iseditable && $wgUser->getOption("editondblclick") ) {
			$tpl->set('body_ondblclick', 'document.location = "' .$content_actions['edit']['href'] .'";');
		} else {
			$tpl->set('body_ondblclick', false);
		}
		//$tpl->set( 'navigation_urls', $this->buildNavigationUrls() );
		//$tpl->set( 'nav_urls', $this->buildNavUrls() );

		// execute template
		wfProfileIn( "$fname-execute" );
		$res = $tpl->execute();
		wfProfileOut( "$fname-execute" );

		// result may be an error
		$this->printOrError( $res );
		wfProfileOut( $fname );
	}

	static function getHolidayLogo() {
		// Note 1: you should take into account 24h varnish page caching when 
		//   considering these dates!
		// Note 2: we use full dates for safety rather than figuring out what year 
		//   we're in! We just need to change these once a year.
		$holidayLogos = array(
			array('logo' => '/skins/owl/images/wikihow_logo_halloween.png',
				'start' => strtotime('October 25, 2013 PST'),
				'end' => strtotime('November 1, 2013 PST'),
			),
		);
		$now = time();
		foreach ($holidayLogos as $hl) {
			if ($hl['start'] <= $now && $now <= $hl['end']) {
				return $hl['logo'];
			}
		}
		return '';
	}
}

class WikiHowTemplate extends QuickTemplate {

	/**
	 * Template filter callback for wikiHow skin.
	 * Takes an associative array of data set from a SkinTemplate-based
	 * class, and a wrapper for MediaWiki's localization database, and
	 * outputs a formatted page.
	 *
	 * @access private
	 */
	function execute() {
		global $wgArticle, $wgUser, $wgLang, $wgTitle, $wgRequest, $wgParser, $wgGoogleSiteVerification;
		global $wgOut, $wgScript, $wgStylePath, $wgLanguageCode, $wgForumLink;
		global $wgContLang, $wgXhtmlDefaultNamespace, $wgContLanguageCode;
		global $wgWikiHowSections, $IP, $wgServer, $wgServerName, $wgIsDomainTest;
		global $wgSSLsite, $wgSpecialPages;

		$prefix = "";

		if (class_exists('MobileWikihow')) {
			$mobileWikihow = new MobileWikihow();
			$result = $mobileWikihow->controller();
			// false means we stop processing template
			if (!$result) return;
		}

		$action = $wgRequest->getVal('action', 'view');
		if (count($wgRequest->getVal('diff')) > 0) $action = 'diff';

		$isMainPage = $wgTitle
			&& $wgTitle->getNamespace() == NS_MAIN
			&& $wgTitle->getText() == wfMsgForContent('mainpage')
			&& $action == 'view';

		$isArticlePage = $wgTitle
			&& !$isMainPage
			&& $wgTitle->getNamespace() == NS_MAIN
			&& $action == 'view';

		$isDocViewer = $wgTitle->getText() == "DocViewer";

		$isBehindHttpAuth = !empty($_SERVER['HTTP_AUTHORIZATION']);

		// determine whether or not the user is logged in
		$isLoggedIn = $wgUser->getID() > 0;

		$isTool = false;
		wfRunHooks('getToolStatus', array(&$isTool));

		$sk = $wgUser->getSkin();


		wikihowAds::setCategories();
		if(!$isLoggedIn && $action == "view")
			wikihowAds::getGlobalChannels();

		$isWikiHow = false;
		if ($wgArticle != null && $wgTitle->getNamespace() == NS_MAIN)  {
			$whow = WikihowArticleEditor::newFromCurrent();
			$isWikiHow = $whow->isWikihow();
		}

		$showAds = wikihowAds::isEligibleForAds();

		$isIndexed = RobotPolicy::isIndexable($wgTitle);

		// set the title and what not
		$avatar = '';
		if ($wgTitle->getNamespace() == NS_USER || $wgTitle->getNamespace() == NS_USER_TALK) {
			$username = $wgTitle->getText();
			$usernameKey = $wgTitle->getDBKey();
			$avatar = ($wgLanguageCode == 'en') ? Avatar::getPicture($usernameKey) : "";

			$pagetitle = $username;
			$this->set("pagetitle", $wgLang->getNsText(NS_USER) . ": $pagetitle - wikiHow");

			if ($wgTitle->getNamespace() == NS_USER_TALK) {
				$pagetitle = $wgLang->getNsText(NS_USER_TALK) . ": $pagetitle";
				$this->set("pagetitle", "$pagetitle - wikiHow");
			}
			elseif ($username == $wgUser->getName()) {
				//user's own page
				$profileboxname = wfMsg('profilebox-name');
				$profile_links = "<div id='gatEditRemoveButtons'>
								<a href='/Special:Profilebox' id='gatProfileEditButton' >Edit</a>
								 | <a href='#' onclick='removeUserPage(\"$profileboxname\");'>Remove $profileboxname</a>
								 </div>";
			}
			$h1 = $pagetitle . $profile_links;
			$this->set("title", $h1);
		}
		$title = $this->data['pagetitle'];

		if ($isWikiHow && $action == "view")  {
			if ($wgLanguageCode == 'en') {
				if (!$this->titleTest) {
					$this->titleTest = TitleTests::newFromTitle($wgTitle);
				}
				if ($this->titleTest) {
					$title = $this->titleTest->getTitle();
				}
			} else {
				$howto = wfMsg('howto', $this->data['title']);
				$title = wfMsg('pagetitle', $howto);
			}
		}

        if ($isMainPage)
            $title = 'wikiHow - '.wfMsg('main_title');

        if ($wgTitle->getNamespace() == NS_CATEGORY) {
			$title = wfMsg('category_title_tag', $wgTitle->getText());
		}

		$logoutPage = $wgLang->specialPage("Userlogout");
		$returnTarget = $wgTitle->getPrefixedURL();
		$returnto = strcasecmp( urlencode($logoutPage), $returnTarget ) ? "returnto={$returnTarget}" : "";

		$login = "";
		if ( !$wgUser->isAnon() ) {
			$uname = $wgUser->getName();
			if (strlen($uname) > 16) { $uname = substr($uname,0,16) . "..."; }
			$login = wfMsg('welcome_back', $wgUser->getUserPage()->getFullURL(), $uname );

			if ($wgLanguageCode == 'en' && $wgUser->isFacebookUser()) {
				$login =  wfMsg('welcome_back_fb', $wgUser->getUserPage()->getFullURL() ,$wgUser->getName() );
			}
		   elseif ($wgLanguageCode == 'en' && $wgUser->isGPlusUser()) {
				$gname = $wgUser->getName();
				if (substr($gname,0,3) == 'GP_') $gname = substr($gname,0,12).'...';
				$login =  wfMsg('welcome_back_gp', $wgUser->getUserPage()->getFullURL(), $gname);
			}
		} else {
			if($wgLanguageCode == "en") {
				$login =  wfMsg('signup_or_login', $returnto) . " " . wfMsg('social_connect_header');
			}
			else {
				$login =  wfMsg('signup_or_login', $returnto);
			}
		}

		//XX PROFILE EDIT/CREAT/DEL BOX DATE - need to check for pb flag in order to display this.
		$pbDate = "";
		$pbDateFlag = 0;
		$profilebox_name = wfMsg('profilebox-name');
		if ( $wgTitle->getNamespace() == NS_USER ) {
			if ($u = User::newFromName($wgTitle->getDBKey())) {
				if(UserPagePolicy::isGoodUserPage($wgTitle->getDBKey())) {
					$pbDate = ProfileBox::getPageTop($u);
					$pbDateFlag = true;
				}
			}
		}

		if (! $sk->suppressH1Tag()) {
			if ($isWikiHow && $action == "view") {
				if (Microdata::showRecipeTags() && Microdata::showhRecipeTags()) {
					$itemprop_name1 = " fn'";
					$itemprop_name2 = "";
				}
				else {
					$itemprop_name1 = "' itemprop='name'";
					$itemprop_name2 = " itemprop='url'";
				}

				$heading = "<h1 class='firstHeading".$itemprop_name1."><a href=\"" . $wgTitle->getFullURL() . "\"".$itemprop_name2.">" . wfMsg('howto', $this->data['title']) . "</a></h1>";

			} else {

				if ((($wgTitle->getNamespace() == NS_USER && UserPagePolicy::isGoodUserPage($wgTitle->getDBKey())) || $wgTitle->getNamespace() == NS_USER_TALK) ) {
					$heading = "<h1 class=\"firstHeading\" >" . $this->data['title'] . "</h1>  ".$pbDate;
					if ($avatar != "") $heading = $avatar . "<div id='avatarNameWrap'>".$heading."</div><div style='clear: both;'> </div>";
				} else {
					if ($this->data['title']) {
						$heading = "<h1 class='firstHeading'>" . $this->data['title'] . "</h1>";
					}
				}
			}
		}
		
		// get the breadcrumbs / category links at the top of the page
		$catLinksTop = $sk->getCategoryLinks(true);
		wfRunHooks('getBreadCrumbs', array(&$catLinksTop));
		$mainPageObj = Title::newMainPage();

		if (MWNamespace::isTalk($wgTitle->getNamespace()) && $action == "view")

			$isPrintable = $wgRequest->getVal("printable") == "yes";

		// QWER links for everyone on all pages

		//$helplink = $sk->makeLinkObj(Title::makeTitle(NS_PROJECT_TALK, 'Help-Team'), wfMsg('help'));
		$logoutlink = $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, 'Userlogout'), wfMsg('logout'));

		$rsslink = "<a href='" . $wgServer . "/feed.rss'>" . wfMsg('rss') . "</a>";
		$rplink = $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "Randompage"), wfMsg('randompage') ) ;


		if ($wgTitle->getNamespace() == NS_MAIN && !$isMainPage && $wgTitle->userCanEdit())
			$links[] = array (Title::makeTitle(NS_SPECIAL, "Recentchangeslinked")->getFullURL() . "/" . $wgTitle->getPrefixedURL(), wfMsg('recentchangeslinked') );

		//Editing Tools
		$uploadlink = "";
		$freephotoslink = "";
		$uploadlink = $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "Upload"), wfMsg('upload'));
		$freephotoslink = $sk->makeLinkObj(Title::makeTitle(NS_SPECIAL, "ImportFreeImages"), wfMsg('imageimport'));
		$relatedchangeslink = "";
		if ($wgArticle != null)
			$relatedchangeslink = "<li> <a href='" .
				Title::makeTitle(NS_SPECIAL, "Recentchangeslinked")->getFullURL() . "/" . $wgTitle->getPrefixedURL() . "'>"
				. wfMsg('recentchangeslinked') . "</a></li>";

		//search
		$searchTitle = Title::makeTitle(NS_SPECIAL, "LSearch");

		$otherLanguageLinks = array();
		$translationData = array();
		if ($this->data['language_urls']) {
			foreach ($this->data['language_urls'] as $lang) {
				if ($lang['code'] == $wgLanguageCode) continue;
				$otherLanguageLinks[ $lang['code'] ] = $lang['href'];
				$langMsg = $sk->getInterWikiCTA($lang['code'], $lang['text'], $lang['href']);
				if (!$langMsg) continue;
				$encLangMsg = json_encode( $langMsg );
				$translationData[] = "'{$lang['code']}': {'msg':$encLangMsg}";
			}
		}
		
		if (!$isMainPage && !$isDocViewer && !$_COOKIE["sitenoticebox"]) $siteNotice = $sk->getSiteNotice();
		
		// Right-to-left languages
		$rtl = $wgContLang->isRTL() ? " dir='RTL'" : '';
		$head_element = "<html xmlns:fb=\"https://www.facebook.com/2008/fbml\" xmlns=\"{$wgXhtmlDefaultNamespace}\" xml:lang=\"$wgContLanguageCode\" lang=\"$wgContLanguageCode\" $rtl>\n";

		$rtl_css = "";
		if ($wgContLang->isRTL()) {
			$rtl_css = "<style type=\"text/css\" media=\"all\">/*<![CDATA[*/ @import \a".wfGetPad("/extensions/min/f/skins/WikiHow/rtl.css")."\"; /*]]>*/</style>";
			$rtl_css .= "
   <!--[if IE]>
   <style type=\"text/css\">
   BODY { margin: 25px; }
   </style>
   <![endif]-->";

		}
		$printable_media = "print";
		if ($wgRequest->getVal('printable') == 'yes')
			$printable_media = "all";

		$featured = false;
		if (false && $wgTitle->getNamespace() == NS_MAIN) {
			$dbr = wfGetDB(DB_SLAVE);
			$page_isfeatured = $dbr->selectField('page', 'page_is_featured', array("page_id={$wgTitle->getArticleID()}"), __METHOD__);
			$featured = ($page_isfeatured == 1);
		}

		$top_search = "";
	   	$footer_search = "";
	   	if ($wgLanguageCode == 'en') {
		   //INTL: Search options for the english site are a bit more complex
		   if (!$isLoggedIn) {
				   $top_search = GoogSearch::getSearchBox("cse-search-box");
		   } else {
				  $top_search  = '
				   <form id="bubble_search" name="search_site" action="' . $searchTitle->getFullURL() . '" method="get">
				   <input type="text" class="search_box" name="search" x-webkit-speech />
				   <input type="submit" value="Search" id="search_site_bubble" class="search_button" />
				   </form>';
		   }
		} else {
			//INTL: International search just uses Google custom search
			$top_search = GoogSearch::getSearchBox("cse-search-box");
	   	}


		$text = $this->data['bodytext'];
		// Remove stray table under video section. Probably should eventually do it at
		// the source, but then have to go through all articles.
		if (strpos($text, '<a name="Video">') !== false) {
			$vidpattern="<p><br /></p>\n<center>\n<table width=\"375px\">\n<tr>\n<td><br /></td>\n<td align=\"left\"></td>\n</tr>\n</table>\n</center>\n<p><br /></p>";
			$text = str_replace($vidpattern, "", $text);
		}
		$this->data['bodytext'] = $text;

		// hack to get the FA template working, remove after we go live
		$fa = '';
		if ($wgLanguageCode != "nl" && strpos($this->data['bodytext'], 'featurestar') !== false) {
			$fa = '<p id="feature_star">' . wfMsg('featured_article') . '</p>';
			//$this->data['bodytext'] = preg_replace("@<div id=\"featurestar\">(.|\n)*<div style=\"clear:both\"></div>@mU", '', $this->data['bodytext']);
		}

		$body = "";

		if ($wgTitle->userCanEdit() && 
			$action != 'edit' && 
			$action != 'diff' && 
			$action != 'history' &&
			(($isLoggedIn && !in_array($wgTitle->getNamespace(), array(NS_USER, NS_USER_TALK, NS_IMAGE, NS_CATEGORY))) || 
				!in_array($wgTitle->getNamespace(), array(NS_USER, NS_USER_TALK, NS_IMAGE, NS_CATEGORY)))) {
				//INTL: Need bigger buttons for non-english sites
				$editlink_text = ($wgTitle->getNamespace() == NS_MAIN) ? wfMsg('editarticle') : wfMsg('edit');
				$heading = '<a href="' . $wgTitle->escapeLocalURL($sk->editUrlOptions()) . '" class="edit">'.$editlink_text.'</a>' . $heading;
		}

		if ($isArticlePage || ($wgTitle->getNamespace() == NS_PROJECT && $action == 'view') || ($wgTitle->getNamespace() == NS_CATEGORY && !$wgTitle->exists())) {
			if ($wgTitle->getNamespace() == NS_PROJECT && ($wgTitle->getDbKey() == 'RSS-feed' || $wgTitle->getDbKey() == 'Rising-star-feed')) {
				$list_page = true;
				$sticky = false;
			}
			else {
				$list_page = false;
				$sticky = true;
			}
			$body .= $heading . ArticleAuthors::getAuthorHeader() . $this->data['bodytext'];
			$body = '<div id="bodycontents">'.$body.'</div>';
			$this->data['bodytext'] = WikihowArticleHTML::processArticleHTML($body, array('sticky-headers' => $sticky, 'ns' => $wgTitle->getNamespace(), 'list-page' => $list_page));
		}
		else {
			if ($action == 'edit') $heading .= WikihowArticleEditor::grabArticleEditLinks($wgRequest->getVal("guidededitor"));
			$this->data['bodyheading'] = $heading;
			$body = '<div id="bodycontents">'.$this->data['bodytext'].'</div>';
			if(!$isTool) {
				$this->data['bodytext'] = WikihowArticleHTML::processHTML($body,$action,array('show-gray-container' => $sk->showGrayContainer()));
			} else {
				// a little hack to style the no such special page messages for special pages that actually
				// exist
				if (false !== strpos($body, 'You have arrived at a "special page"')) {
					$body = "<div class='minor_section'>$body</div>";
				}
				$this->data['bodytext'] = $body;
			}
		}

		// post-process the Steps section HTML to get the numbers working
		if ($wgTitle->getNamespace() == NS_MAIN
			&& !$isMainPage
			&& ($action=='view' || $action == 'purge')
		) {
			// for preview article after edit, you have to munge the
			// steps of the previewHTML manually
			$body = $this->data['bodytext'];
			$opts = array();
			if(!$showAds)
				$opts['no-ads'] = true;
			//$this->data['bodytext'] = WikihowArticleHTML::postProcess($body, $opts);
		}

		// insert avatars into discussion, talk, and kudos pages
		if (MWNamespace::isTalk($wgTitle->getNamespace()) || $wgTitle->getNamespace() == NS_USER_KUDOS){
			$this->data['bodytext'] = Avatar::insertAvatarIntoDiscussion($this->data['bodytext']);
		}

		//$navMenu = $sk->genNavigationMenu();

		$navTabs = $sk->genNavigationTabs();

		//XX TALK/DISCUSSION SUBMENU
		// add article_inner if it's not already there, CSS needs it
		if (strpos( $this->data['bodytext'], "article_inner" ) === false
			&& wfRunHooks('WrapBodyWithArticleInner', array()))
		{
			//echo "ADDING";
			//$this->data['bodytext'] = "<div class='article_inner'>{$this->data['bodytext']}</div>";
		}

		// set up the main page
		$mpActions = "";
		$mpWorldwide = '

		';

		$profileBoxIsUser = false;
		if ($isLoggedIn && $wgTitle && $wgTitle->getNamespace() == NS_USER) {
			$name = $wgTitle->getDBKey();
			$profileBoxUser = User::newFromName($name);
			if ($profileBoxUser && $wgUser->getID() == $profileBoxUser->getID()) {
				$profileBoxIsUser = true;
			}
		}

		// Reuben (11/2013): Micro-customization as a test for BR
		$slowSpeedUsers = array('BR');
		$isSlowSpeedUser = $wgUser && in_array($wgUser->getName(), $slowSpeedUsers);

		$optimizelyJS = false;
		if(class_exists('OptimizelyPageSelector') && $wgTitle && $wgTitle->exists()) {	
			if(OptimizelyPageSelector::isArticleEnabled($wgTitle) && OptimizelyPageSelector::isUserEnabled($wgUser)) {
				$optimizelyJS = OptimizelyPageSelector::getOptimizelyTag();	
			}
		}

		$showSpotlightRotate =
			$isMainPage &&
			$wgLanguageCode == 'en';

		$showBreadCrumbs = $sk->showBreadCrumbs();
		$showSideBar = $sk->showSideBar();
		$showHeadSection = $sk->showHeadSection();
		$showArticleTabs = $wgTitle->getNamespace() != NS_SPECIAL && !$isMainPage;
		if (in_array($wgTitle->getNamespace(), array(NS_IMAGE))
			&& (empty($action) || $action == 'view')
			&& !$isLoggedIn)
		{
			$showArticleTabs = false;
		}

		$showWikiTextWidget = false;
		if (class_exists('WikitextDownloader')) {
			$showWikiTextWidget = WikitextDownloader::isAuthorized() && !$isDocViewer;
		}

		$showRCWidget =
			class_exists('RCWidget') &&
			$wgTitle->getNamespace() != NS_USER &&
			(!$isLoggedIn || $wgUser->getOption('recent_changes_widget_show') != '0' ) &&
			($isLoggedIn || $isMainPage) &&
			!in_array($wgTitle->getPrefixedText(),
				array('Special:Avatar', 'Special:ProfileBox', 'Special:IntroImageAdder')) &&
			strpos($wgTitle->getPrefixedText(), 'Special:Userlog') === false &&
			!$isDocViewer &&
			$action != 'edit';

		$showFollowWidget = 
			class_exists('FollowWidget') && 
			!$isDocViewer &&
			in_array($wgLanguageCode, array('en', 'de', 'es', 'pt'));

		$showSocialSharing = 
			$wgTitle &&
			$wgTitle->exists() && 
			$wgTitle->getNamespace() == NS_MAIN && 
			!$isSlowSpeedUser &&
			class_exists('WikihowShare');

		$showSliderWidget =
			class_exists('Slider') &&
			$wgTitle->exists() &&
			$wgTitle->getNamespace() == NS_MAIN &&
			!$isPrintable &&
			!$isMainPage &&
			$isIndexed &&
			$showSocialSharing &&
			$wgRequest->getVal('oldid') == '' &&
			($wgRequest->getVal('action') == '' || $wgRequest->getVal('action') == 'view');

		$showTopTenTips =
			$wgTitle->exists() &&
			$wgTitle->getNamespace() == NS_MAIN &&
			$wgLanguageCode == 'en' &&
			!$isPrintable &&
			!$isMainPage &&
			$wgRequest->getVal('oldid') == '' &&
			($wgRequest->getVal('action') == '' || $wgRequest->getVal('action') == 'view');

		$showAltMethod = false;
		if(class_exists('AltMethodAdder')) {
			$showAltMethod = true;
		}

		$showExitTimer = $wgLanguageCode == 'en' && class_exists('BounceTimeLogger') && !$isSlowSpeedUser;

		$showRUM = ($isArticlePage || $isMainPage) && !$isBehindHttpAuth && !$isSlowSpeedUser;
		$showGoSquared = ($isArticlePage || $isMainPage) && !$isLoggedIn && !$isBehindHttpAuth && mt_rand(1, 100) <= 30; // 30% chance
		$showClickIgniter = !$isLoggedIn && !$isBehindHttpAuth && !$wgSSLsite;

		$showGA = !$isSlowSpeedUser;
		$showGAevents = $wgLanguageCode == 'en' && $isMainPage && !$isSlowSpeedUser;

		$isLiquid = false;//!$isMainPage && ( $wgTitle->getNameSpace() == NS_CATEGORY );

		$showFeaturedArticlesSidebar = $action == 'view'
			&& !$isMainPage
			&& !$isDocViewer
			&& !$wgSSLsite
			&& $wgTitle->getNamespace() != NS_USER;

		$isSpecialPage = $wgTitle->getNamespace() == NS_SPECIAL
			|| ($wgTitle->getNamespace() == NS_MAIN && $wgRequest->getVal('action') == 'protect')
			|| ($wgTitle->getNamespace() == NS_MAIN && $wgRequest->getVal('action') == 'delete');

		$showTextScroller =
			class_exists('TextScroller') &&
			$wgTitle->exists() &&
			$wgTitle->getNamespace() == NS_MAIN &&
			!$isPrintable &&
			!$isMainPage &&
			strpos($this->data['bodytext'], 'textscroller_outer') !== false;

		$showImageFeedback =
			class_exists('ImageFeedback') &&
			ImageFeedback::isValidPage();

		$showWikivideo =
			class_exists('WHVid') &&
			(($wgTitle->exists() && $wgTitle->getNamespace() == NS_MAIN) || $wgTitle->getNamespace() == NS_SPECIAL) &&
			!$isPrintable &&
			!$isMainPage &&
			strpos($this->data['bodytext'], 'whvid_cont') !== false;

		$showStaffStats = !$isMainPage
			&& $isLoggedIn
			&& (in_array('staff', $wgUser->getGroups()) || in_array('staff_widget', $wgUser->getGroups()))
			&& $wgTitle->getNamespace() == NS_MAIN
			&& class_exists('Pagestats');

		$showThumbsUp = class_exists('ThumbsNotifications');
		
		$postLoadJS = $isArticlePage;

		// add present JS files to extensions/min/groupsConfig.php
		$fullJSuri = '/extensions/min/g/whjs' .
			(!$isArticlePage ? ',jqui' : '') .
			($showExitTimer ? ',stu' : '') . 
			($showRCWidget ? ',rcw' : '') . 
			($showSpotlightRotate ? ',sp' : '') . 
			($showFollowWidget ? ',fl' : '') . 
			($showSliderWidget ? ',slj' : '') . 
			($showThumbsUp ? ',thm' : '') . 
			($showWikiTextWidget ? ',wkt' : '') . 
			($showAltMethod ? ',altj' : '') . 
			($showTopTenTips ? ',tpt' : '') . 
			($isMainPage ? ',hp' : '') .
			($showWikivideo ? ',whv' : '') . 
			($showImageFeedback ? ',ii' : '') . 
			($showTextScroller ? ',ts' : '');

		if ($wgOut->mJSminCodes) {
			$fullJSuri .= ',' . join(',', $wgOut->mJSminCodes);
		}
		$cachedParam = $wgRequest && $wgRequest->getVal('c') == 't' ? '&c=t' : '';
		$fullJSuri .= '&r=' . WH_SITEREV . $cachedParam . '&e=.js';

		$fullCSSuri = '/extensions/min/g/whcss' . 
			(!$isArticlePage ? ',jquic,nona' : '') .
			($isLoggedIn ? ',li' : '') . 
			($showSliderWidget ? ',slc' : '') . 
			($showAltMethod ? ',altc' : '') . 
			($showTopTenTips ? ',tptc' : '') . 
			($showWikivideo ? ',whvc' : '') . 
			($showTextScroller ? ',tsc' : '') .
			($isMainPage ? ',hpc' : '') .
			($showImageFeedback ? ',iic' : '') .
			($isSpecialPage ? ',spc' : '');

		if ($wgOut->mCSSminCodes) {
			$fullCSSuri .= ',' . join(',', $wgOut->mCSSminCodes);
		}
		$fullCSSuri .= '&r=' . WH_SITEREV . $cachedParam . '&e=.css';

		$tabsArray = $sk->getTabsArray($showArticleTabs);

		wfRunHooks( 'JustBeforeOutputHTML', array( &$this ) );

?>
<!DOCTYPE html>
<?= $head_element ?><head prefix="og: http://ogp.me/ns# fb: http://ogp.me/ns/fb# article: http://ogp.me/ns/article#">
	<title><?= $title ?></title>
	<? if ($showRUM): ?>
<script>
<!--
window.UVPERF = {};
UVPERF.authtoken = 'b473c3f9-a845-4dc3-9432-7ad0441e00c3';
UVPERF.start = new Date().getTime();
//-->
</script>
	<? endif; ?>
	<? if ($wgIsDomainTest): ?>
	<base href="http://www.wikihow.com/" />
	<? endif; ?>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<meta name="verify-v1" content="/Ur0RE4/QGQIq9F46KZyKIyL0ZnS96N5x1DwQJa7bR8=" />
	<meta name="google-site-verification" content="Jb3uMWyKPQ3B9lzp5hZvJjITDKG8xI8mnEpWifGXUb0" />
	<meta name="msvalidate.01" content="CFD80128CAD3E726220D4C2420D539BE" />
	<meta name="y_key" content="1b3ab4fc6fba3ab3" />
	<meta name="p:domain_verify" content="bb366527fa38aa5bc27356b728a2ec6f" />
	<? if ($isArticlePage || $isMainPage): ?>
	<link rel="alternate" media="only screen and (max-width: 640px)" href="http://<?php if($wgLanguageCode != 'en') { echo ($wgLanguageCode . "."); } ?>m.wikihow.com/<?= $wgTitle->getPartialUrl() ?>">
	<? endif; ?>
<?= Skin::makeGlobalVariablesScript( $this->data ) ?>
	<? // add CSS files to extensions/min/groupsConfig.php ?>
	<style type="text/css" media="all">/*<![CDATA[*/ @import "<?= $fullCSSuri ?>"; /*]]>*/</style>
	<? // below is the minified http://www.wikihow.com/extensions/min/f/skins/owl/printable.css ?>
	<style type="text/css" media="<?=$printable_media?>">/*<![CDATA[*/ body{background-color:#FFF;font-size:1.2em;}#header_outer{background:none;position:relative;}#header{text-align:center;}#logo_link{float:none!important;}#article_shell{float:none;padding-bottom:2em;margin:0 auto;}.sticking{position:absolute!important;top:0!important;}#actions,#notification_count,#bubble_search,#cse-search-box,#header_space,#sidebar,#breadcrumb,#originators,#article_tabs,#sliderbox,#article_rating,#end_options,#footer_outer,.edit{display:none!important;} /*]]>*/</style>
		<?
		// Bootstapping certain javascript functions:
		// A function to merge one object with another; stub funcs
		// for button swapping (this should be done in CSS anyway);
		// initialize the timer for bounce stats tracking.
		?>
		<script>
			<!--
			var WH = WH || {};
			WH.lang = WH.lang || {};
			button_swap = button_unswap = function(){};
			WH.exitTimerStartTime = (new Date()).getTime();
			WH.mergeLang = function(A){for(i in A){v=A[i];if(typeof v==='string'){WH.lang[i]=v;}}};
			//-->
		</script>
		<? if (!$postLoadJS): ?>
			<script type="text/javascript" src="<?= $fullJSuri ?>"></script>
		<? endif ?>

		<? $this->html('headlinks') ?>
		
	<? if (!$wgIsDomainTest) { ?>
			<link rel='canonical' href='<?=$wgTitle->getFullURL()?>'/>
			<link href="https://plus.google.com/102818024478962731382" rel="publisher" />
		<? } ?>
	<? if ($sk->isUserAgentMobile()): ?>
			<link media="only screen and (max-device-width: 480px)" href="<?= wfGetPad('/extensions/min/f/skins/WikiHow/iphone.css') ?>" type="text/css" rel="stylesheet" />
		<? else: ?>
			<!-- not mobile -->
		<? endif; ?>
	<!--<![endif]-->
	<?= $rtl_css ?>
	<link rel="alternate" type="application/rss+xml" title="wikiHow: How-to of the Day" href="http://www.wikihow.com/feed.rss"/>
	<link rel="apple-touch-icon" href="<?= wfGetPad('/skins/WikiHow/safari-large-icon.png') ?>" />
	<?//= wfMsg('Test_setup') ?>
	<?
	if (class_exists('CTALinks') && trim(wfMsgForContent('cta_feature')) == "on") {
		echo CTALinks::getGoogleControlScript();
	}
	?>
	<?= $wgOut->getHeadItems() ?>
			
	<? if($wgTitle && $wgTitle->getText() == "Get Caramel off Pots and Pans") {
		echo wfMsg('Adunit_test_top');
	} ?>

	<? foreach ($otherLanguageLinks as $lang => $url): ?>
			<link rel="alternate" hreflang="<?= $lang ?>" href="<?= htmlspecialchars($url) ?>" />
		<? endforeach; ?>
		</head>
		<body <?php if($this->data['body_ondblclick']) { ?>ondblclick="<?php $this->text('body_ondblclick') ?>"<?php } ?>
			  <?php if($this->data['body_onload']) { ?>onload="<?php $this->text('body_onload') ?>"<?php } ?>
			>
		<?php wfRunHooks( 'PageHeaderDisplay', array( $sk->isUserAgentMobile() ) ); ?>

		<?php
		if(!$isLoggedIn)
			echo wikihowAds::getSetup();
		?>
		<div id="header_outer"><div id="header">
			<ul id="actions">
				<? foreach ($navTabs as $tabid => $tab): ?>
					<li id="<?= $tabid ?>_li">
						<div class="nav_icon"></div>
						<a id='<?= $tabid ?>' class='nav <?= $tab['status'] ?>' href='<?= $tab['link'] ?>' <?= $tab['mouseevents'] ?>><?= $tab['text'] ?></a>
						<?= $tab['menu'] ?>
					</li>
				<? endforeach; ?>
			</ul><!--end actions-->
			<? if ($sk->notifications_count > 0): ?>
				<div id="notification_count" class="notice"><?= $sk->notifications_count ?></div>
			<? endif; ?>
			<? $holidayLogo = SkinWikihowskin::getHolidayLogo(); 
				$logoPath = $holidayLogo ? $holidayLogo : '/skins/owl/images/wikihow_logo.png'; 
				if($wgLanguageCode != "en") {
					$logoPath = "/skins/owl/images/wikihow_logo_intl.png";
				}
				?>
			<a href='<?=$mainPageObj->getLocalURL();?>' id='logo_link'><img src="<?= wfGetPad($logoPath) ?>" class="logo" /></a>
			<?= $top_search ?>
			<? if($wgLanguageCode=='zh') { ?>
				<style>
					#header #wpUserVariant { background-color: #C9DCB9; border: medium none; left: 590px; position: relative; top: -48px; }
					#header.shrunk #wpUserVariant { top: -35px; }
					.search_box { width: 119px }
				</style>
				<form action="" method="post">
				<select id="wpUserVariant">
				<?php
					$variant = $wgContLang->getPreferredVariant();
					$zhVarArr = array("zh" => "选择语言", "zh-hans"=>"‪中文(简体)‬", "zh-hant"=>"‪中文(繁體)‬",  "zh-tw"=>"‪中文(台灣)‬", "zh-sg" => "‪中文(新加坡)‬", "zh-hk" => "‪中文(香港)‬");
					foreach($zhVarArr as $k => $v) { ?>
						<option <? if($variant == $k) { ?>selected <?php } ?> value="<?= $k ?>"><?= $v ?></option>
					<?php } ?>
				</select>
				</form>
			<? } ?>

		</div></div><!--end header-->
		<?php wfRunHooks( 'AfterHeader', array( &$wgOut ) ); ?>
		<div id="main_container" class="<?= ($isMainPage?'mainpage':'') ?>">
			<div id="header_space"></div>
		
		<div id="main">
		<?php wfRunHooks( 'BeforeActionbar', array( &$wgOut ) ); ?>
		<div id="actionbar" class="<?= ($isTool?'isTool':'') ?>">
			<? if ($showBreadCrumbs): ?>
				<div id="gatBreadCrumb">
					<ul id="breadcrumb" class="Breadcrumbs">
						<?= $catLinksTop ?>
					</ul>
				</div>
			<? endif; ?>
			<? if(count($tabsArray) > 0) {
				echo $sk->getTabsHtml($tabsArray);
			} ?>

		</div><!--end actionbar-->
		<script>
		<!--
		WH.translationData = {<?= join(',', $translationData) ?>};
		//-->
		</script>
		<?= $announcement ?>
		<?= $mpActions ?>
		<?php
		$sidebar = !$showSideBar ? 'no_sidebar' : '';

		// INTL: load mediawiki messages for sidebar expand and collapse for later use in sidebar boxes
		$langKeys = array('navlist_collapse', 'navlist_expand', 'usernameoremail','password');
		print Wikihow_i18n::genJSMsgs($langKeys);
		?>
		<div id="container" class="<?= $sidebar ?>">
		<div id="article_shell">
		<div id="article"<?= Microdata::genSchemaHeader() ?>>

			<?php wfRunHooks( 'BeforeTabsLine', array( &$wgOut ) ); ?>
			<?= $profileBox ?>
			<? 
			if (!$isArticlePage && !$isMainPage && $this->data['bodyheading']) {
				echo '<div class="wh_block">'.$this->data['bodyheading'].'</div>';
			}
			echo $this->html('bodytext');	
			
			echo $bottom_site_notice;
			
			$showingArticleInfo = 0;
			if (in_array($wgTitle->getNamespace(), array(NS_MAIN, NS_PROJECT)) && $action == 'view' && !$isMainPage) {
				$catLinks = $sk->getCategoryLinks(false);
				$authors = ArticleAuthors::getAuthorFooter();
				if ($authors || is_array($this->data['language_urls']) || $catLinks) {
					$showingArticleInfo = 1;
				}
				?>

				<div class="section">
					<? if($showingArticleInfo): ?>
						<h2 class="section_head" id="article_info_header"><span><?= wfMsg('article_info') ?></span></h2>
						<div id="article_info" class="section_text">
					<? else: ?>
						<h2 class="section_head" id="article_tools_header"><span><?= wfMsg('article_tools') ?></span></h2>
						<div id="article_tools" class="section_text">
					<? endif ?>
						<?= $fa ?>
						<?php if ($catLinks): ?>
							<p class="info"> <?= wfMsg('categories') ?>: <?= $catLinks ?></p>
						<?php endif; ?>
						<p><?=$authors?></p>
						<?php if (is_array($this->data['language_urls'])) { ?>
							<p class="info"><?php $this->msg('otherlanguages') ?>:</p>
							<p class="info"><?php
								$links = array();
								$sk = $wgUser->getSkin();
								foreach($this->data['language_urls'] as $langlink) {
									$linkText = $langlink['text'];
									preg_match("@interwiki-(..)@", $langlink['class'], $langCode);
									if (!empty($langCode[1])) {
										$linkText = $sk->getInterWikiLinkText($linkText, $langCode[1]);
									}
									$links[] = htmlspecialchars(trim($langlink['language'])) . '&nbsp;<span><a href="' .  htmlspecialchars($langlink['href']) . '">' .  $linkText . "</a><span>";
								}
								echo implode("&#44;&nbsp;", $links);
								?>
							</p>
						<? } 
						//talk link
						if ($action =='view' && MWNamespace::isTalk($wgTitle->getNamespace())) {
							$talk_link = '#postcomment';
						} else {
							$talk_link = $wgTitle->getTalkPage()->getLocalURL();
						}
						?>
						<ul id="end_options">
							<li class="endop_discuss"><span></span><a href="<?= $talk_link ?>" id="gatDiscussionFooter"><?=wfMsg('at_discuss')?></a></li>
							<li class="endop_print"><span></span><a href="<?= $wgTitle->getLocalUrl('printable=yes') ?>" id="gatPrintView"><?= wfMsg('print') ?></a></li>
							<li class="endop_email"><span></span><a href="#" onclick="return emailLink();" id="gatSharingEmail"><?=wfMsg('at_email') ?></a></li>
							<? if($isLoggedIn): ?>
								<? if ($wgTitle->userIsWatching()) { ?>
									<li class="endop_watch"><span></span><a href="<?echo $wgTitle->getLocalURL('action=unwatch');?>"><?=wfMsg('at_remove_watch')?></a></li>
								<? } else { ?>
									<li class="endop_watch"><span></span><a href="<?echo $wgTitle->getLocalURL('action=watch');?>"><?=wfMsg('at_watch')?></a></li>
								<? } ?>
							<? endif; ?>
							<li class="endop_edit"><span></span><a href="<?echo $wgTitle->getEditUrl();?>" id="gatEditFooter"><?echo wfMsg('edit');?></a></li>
							<? if ($wgTitle->getNamespace() == NS_MAIN) { ?>
								<li class="endop_fanmail"><span></span><a href="/Special:ThankAuthors?target=<?echo $wgTitle->getPrefixedURL();?>" id="gatThankAuthors"><?=wfMsg('at_fanmail')?></a></li>
							<? } ?>
						</ul> <!--end end_options -->
							<? if (!in_array($wgTitle->getNamespace(), array(NS_USER, NS_CATEGORY))): ?>

							<? endif; ?>
							<?php if( $showAds && $wgTitle->getNamespace() == NS_MAIN ) {
								//only show this ad on article pages
								echo wikihowAds::getAdUnitPlaceholder(7);
							} ?>
                        <div class="clearall"></div>
					</div><!--end article_info section_text-->
						<p class='page_stats'><?= $sk->pageStats() ?></p>

						<div id='article_rating'>
							<?echo RateItem::showForm('article');?>
						</div>
				</div><!--end section-->

			<? }
			if (in_array($wgTitle->getNamespace(), array(NS_USER, NS_MAIN, NS_PROJECT)) && $action == 'view' && !$isMainPage) {
			?>

		</div> <!-- article -->
		<div id="">

			<? } ?>
		</div>  <!--end last_question-->
		<div class="clearall"></div>

		</div>  <!--end article_shell-->


		<? if ($showSideBar): 
			$loggedOutClass = "";
			if ($showAds && $wgTitle->getText() != 'Userlogin' && $wgTitle->getNamespace() == NS_MAIN) {
				$loggedOutClass = ' logged_out';
			}
		?>
			<div id="sidebar">		
				<?= $siteNotice ?>

				<!-- Sidebar Top Widgets -->
				<? foreach ($sk->mSidebarTopWidgets as $sbWidget): ?>
					<?= $sbWidget ?>
				<? endforeach; ?>
				<!-- END Sidebar Top Widgets -->

				<? if (!$isDocViewer) { 
				?>
				<div id="top_links" class="sidebox<?=$loggedOutClass?>" <?= is_numeric(wfMsg('top_links_padding')) ? ' style="padding-left:' . wfMsg('top_links_padding') . 'px;padding-right:' . wfMsg('top_links_padding') . 'px;"' : '' ?>>
					<a href="/Special:Randomizer" id="gatRandom" accesskey='x' class="button secondary"><?=wfMsg('randompage'); ?></a>
					<a href="/Special:Createpage" id="gatWriteAnArticle" class="button secondary"><?=wfMsg('writearticle');?></a>
					<? if (class_exists('Randomizer') && Randomizer::DEBUG && $wgTitle && $wgTitle->getNamespace() == NS_MAIN && $wgTitle->getArticleId()): ?>
						<?= Randomizer::getReason($wgTitle) ?>
					<? endif; ?>
				</div><!--end top_links-->
				<? } ?>
				<?php if ($showStaffStats): ?>
					<div class="sidebox" style="padding-top:10px" id="staff_stats_box"></div>
				<?php endif; ?>

				<?php if ($showWikiTextWidget) { ?>
					<div class="sidebox" id="side_rc_widget">
						<a id='wikitext_downloader' href='#'>Download WikiText</a>
					</div><!--end sidebox-->
				<?php } ?>


				<?php
				if ($showAds && $wgTitle->getText() != 'Userlogin' && $wgTitle->getNamespace() == NS_MAIN) {
// temporary ad code for amazon ad loading, added by Reuben 3/13, disabled 4/23, and re-enabled 5/28
						if($wgLanguageCode == 'en'):
					?>
					<script>
						<!--
						var aax_src='3003';
						var amzn_targs = '';
						var url = encodeURIComponent(document.location);
						try { url = encodeURIComponent("" + window.top.location); } catch(e) {}
						document.write("<scr"+"ipt src='//aax-us-east.amazon-Adsystem.com/e/dtb/bid?src=" + aax_src + "&u="+url+"&cb=" + Math.round(Math.random()*10000000) + "'></scr"+"ipt>");
						document.close();
						//-->
					</script>
						<?php endif; ?>
					<?
					//only show this ad on article pages
					//comment out next line to turn off HHM ad
					if (wikihowAds::isHHM() && $wgLanguageCode =='en')
						echo wikihowAds::getHhmAd();
					else
						echo wikihowAds::getCategoryAd();

					//Temporairily taking down Jane
					/*if (class_exists('StarterTool')) {
						//spellchecker test "ad"
						echo "<a href='/Special:StarterTool?ref=1' style='display:none' id='starter_ad'><img src='" . wfGetPad('/skins/WikiHow/images/sidebar_spelling3.png') . "' nopin='nopin' /></a>";
					}*/
				}
				//<!-- <a href="#"><img src="/skins/WikiHow/images/imgad.jpg" /></a> -->
				?>

				<? if ($sk->getUserLinks()) { ?>
				<div class='sidebox'>
					<?= $sk->getUserLinks() ?>
				</div>
				<? } ?>

				<?
				$related_articles = $sk->getRelatedArticlesBox($this);
				//disable custom link units
				//  if (!$isLoggedIn && $wgTitle->getNamespace() == NS_MAIN && !$isMainPage)
				//if ($related_articles != "")
				//$related_articles .= WikiHowTemplate::getAdUnitPlaceholder(2, true);
				if ($action == 'view' && $related_articles != "") {
					$related_articles = '<div id="side_related_articles" class="sidebox">' 
						. $related_articles .  '</div><!--end side_related_articles-->';

					echo $related_articles;
				}

				?>

				<? if ($showSocialSharing): ?>
					<div class="sidebox<?=$loggedOutClass?>" id="sidebar_share">
						<h3><?= wfMsg('social_share') ?></h3>
						<?
						if ($isMainPage) {
							echo WikihowShare::getMainPageShareButtons();
						} else {
							echo WikihowShare::getTopShareButtons($isIndexed);
						}
						?>
						<div style="clear:both; float:none;"></div>
					</div>
				<? endif; ?>

				<?if ($mpWorldwide !== "") { ?>
					<?= $mpWorldwide ?>
				<? }  ?>

				<? /*
				<!--
				<div class="sidebox_shell">
					<div class='sidebar_top'></div>
					<div id="side_fb_timeline" class="sidebox">
					</div>
					<div class='sidebar_bottom_fold'></div>
				</div>
				-->
				<!--end sidebox_shell-->
				*/ ?>

				<!-- Sidebar Widgets -->
				<? foreach ($sk->mSidebarWidgets as $sbWidget): ?>
					<?= $sbWidget ?>
				<? endforeach; ?>
				<!-- END Sidebar Widgets -->

				<? //if ($isLoggedIn) echo $navMenu; ?>


				<? if ($showFeaturedArticlesSidebar): ?>
					<div id="side_featured_articles" class="sidebox">
						<?= $sk->getFeaturedArticlesBox(4, 4) ?>
					</div>
				<? endif; ?>

				<? if ($showRCWidget): ?>
					<div class="sidebox" id="side_rc_widget">
						<? RCWidget::showWidget(); ?>
						<p class="bottom_link">
							<? if ($isLoggedIn) { ?>
								<?= wfMsg('welcome', $wgUser->getName(), $wgUser->getUserPage()->getLocalURL()); ?>
							<? } else { ?>
								<a href="/Special:Userlogin" id="gatWidgetBottom"><?=wfMsg('rcwidget_join_in')?></a>
							<? } ?>
							<a href="" id="play_pause_button" onclick="rcTransport(this); return false;" ></a>
						</p>
					</div><!--end side_recent_changes-->
				<? endif; ?>

				<? if (class_exists('FeaturedContributor') && ($wgTitle->getNamespace() == NS_MAIN || $wgTitle->getNamespace() == NS_USER ) && !$isMainPage && !$isDocViewer): ?>
					<div id="side_featured_contributor" class="sidebox">
						<?  FeaturedContributor::showWidget();  ?>
						<? if (! $isLoggedIn): ?>
							<p class="bottom_button">
								<a href="/Special:Userlogin" class="button secondary" id="gatFCWidgetBottom" onclick='gatTrack("Browsing","Feat_contrib_cta","Feat_contrib_wgt");'><? echo wfMsg('fc_action') ?></a>
							</p>
						<? endif; ?>
					</div><!--end side_featured_contributor-->
				<? endif; ?>

				<? //if (!$isLoggedIn) echo $navMenu; ?>

				<?= $user_links ?>
				<? if ($showFollowWidget): ?>
					<div class="sidebox">
						<? FollowWidget::showWidget(); ?>
					</div>
				<? endif; ?>
			</div><!--end sidebar-->
		<? endif; // end if $showSideBar ?>
		<div class="clearall" ></div>
		</div> <!--end container -->
		</div><!--end main-->
			<div id="clear_footer"></div>
		</div><!--end main_container-->
		<div id="footer_outer">
			<div id="footer">
				<div id="footer_side">
					<? if ($isLoggedIn): ?>
						<?=wfMsgExt('site_footer_owl', 'parse'); ?>
					<? else: ?>
						<?=wfMsgExt('site_footer_owl_anon', 'parse'); ?>
					<? endif; ?>
				</div><!--end footer_side-->

				<div id="footer_main">

					<div id="sub_footer">
						<?php if ($isLoggedIn || $isMainPage): ?>
							<?= wfMsg('sub_footer_new', wfGetPad(), wfGetPad()) ?>
						<?php else: ?>
							<?= wfMsg('sub_footer_new_anon', wfGetPad(), wfGetPad()) ?>
						<? endif; ?>
					</div>
				</div><!--end footer_main-->
			</div>
				<br class="clearall" />
		</div><!--end footer-->
		<div id="dialog-box" title=""></div>

		<?
		// Quick note/edit popup
		if ($action == 'diff' && $wgLanguageCode =='en') {
			echo QuickNoteEdit::displayQuicknote();
			echo QuickNoteEdit::displayQuickedit();
		}

		// Slider box -- for non-logged in users on articles only
		if ($showSliderWidget) {
			echo Slider::getBox();
			echo '<div id="slideshowdetect"></div>';
		}
		?>

		<div id="fb-root" ></div>

		<? if ($postLoadJS): ?>
			<script type="text/javascript" src="<?= $fullJSuri ?>"></script>
		<? endif; ?>
		<?php if ($optimizelyJS) { print $optimizelyJS; } ?>

		<? if ($showExitTimer): ?>
			<script>
				<!--
				if (WH.ExitTimer) {
					WH.ExitTimer.start();
				}
				//-->
			</script>
		<? endif; ?>

		<? if ($showRCWidget): ?>
			<? RCWidget::showWidgetJS() ?>
		<? endif; ?>

		<script type="text/javascript">
			<!--
			var _gaq = _gaq || [];
	<? if ($showGA): ?>
			_gaq.push(['_setAccount', 'UA-2375655-1']);
			_gaq.push(['_setDomainName', '.wikihow.com']);
			_gaq.push(['_trackPageview']);
			(function() {
				var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
				ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
				var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
			})();
	<? endif; ?>
			//-->
		</script>

	<? if ($showGA): ?>
		<? // Google Analytics Event Track ?>
		<script type="text/javascript">
			<!--
			if (typeof Event =='undefined' || typeof Event.observe == 'undefined') {
				jQuery(window).load(gatStartObservers);
			} else {
				Event.observe(window, 'load', gatStartObservers);
			}
			//-->
		</script>
		<? // END Google Analytics Event Track ?>
		<?
		if (class_exists('CTALinks') && trim(wfMsgForContent('cta_feature')) == "on") {
			echo CTALinks::getGoogleControlTrackingScript();
			echo CTALinks::getGoogleConversionScript();
		}
		?>
		<? // Load event listeners ?>
		<? if ($showGAevents): ?>
			<script type="text/javascript">
				<!--
				if (typeof Event =='undefined' || typeof Event.observe == 'undefined') {
					jQuery(window).load(initSA);
				} else {
					Event.observe(window, 'load', initSA);
				}
				//-->
			</script>
		<? endif; ?>
	<? endif; // $showGA ?>

		<? // Load event listeners all pages ?>
		<?
		if (class_exists('CTALinks') && trim(wfMsgForContent('cta_feature')) == "on") {
			echo CTALinks::getBlankCTA();
		}
		?>

		<? if ($showClickIgniter): ?>
			<script type="text/javascript">
			(function() {
				var ci = document.createElement('script'); ci.type = 'text/javascript'; ci.async = true;
				ci.src = 'http://cdn.clickigniter.io/ci.js';
				var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ci, s);
			})();
			</script>
		<? endif; ?>
		<? if ($showGoSquared): ?>
			<script type="text/javascript">
				var GoSquared = {};
				GoSquared.acct = "GSN-491441-Y";
				(function(w){
					function gs(){
						w._gstc_lt = +new Date;
						var d = document, g = d.createElement("script");
						g.type = "text/javascript";
						g.src = "//d1l6p2sc9645hc.cloudfront.net/tracker.js";
						g.async = true;
						var s = d.getElementsByTagName("script")[0];
						s.parentNode.insertBefore(g, s);
					}
					w.addEventListener ?
						w.addEventListener("load", gs, false) :
						w.attachEvent("onload", gs);
				})(window);
			</script>
		<? endif; ?>
		<? if ($showRUM): ?>
		<script>
			(function(){
				var a=document.createElement('script'); a.type='text/javascript'; a.async=true;
				a.src='//yxjj4c.rumanalytics.com/sampler/basic2';
				var b=document.getElementsByTagName('script')[0]; b.parentNode.insertBefore(a,b);
			})();
		</script>
		<? endif; ?>
		<?  wfRunHooks('ArticleJustBeforeBodyClose', array()); ?>
		<? if (($wgRequest->getVal("action") == "edit"
				|| $wgRequest->getVal("action") == "submit2")
			&& $wgRequest->getVal('advanced', null) != 'true'): ?>
			<script type="text/javascript">
				if (document.getElementById('steps') && document.getElementById('wpTextbox1') == null) {
					InstallAC(document.editform,document.editform.q,document.editform.btnG,"./<?= $wgLang->getNsText(NS_SPECIAL).":TitleSearch" ?>","en");
				}
			</script>
		<? endif; ?>

		<? if ($wgLanguageCode == 'en' && !$isLoggedIn && class_exists('GoogSearch')): ?>
			<?= GoogSearch::getSearchBoxJS() ?>
		<? endif; ?>

<script type="text/javascript">
	(function ($) {
		$(document).ready(function() {
			WH.addScrollEffectToTOC();
		});

		$(window).load(function() {
			if ($('.twitter-share-button').length && (!$.browser.msie || $.browser.version > 7)) {

				$.getScript("https://platform.twitter.com/widgets.js", function() {
					twttr.events.bind('tweet', function(event) {
						if (event) {
							var targetUrl;
							if (event.target && event.target.nodeName == 'IFRAME') {
								targetUrl = extractParamFromUri(event.target.src, 'url');
							}
							_gaq.push(['_trackSocial', 'twitter', 'tweet', targetUrl]);
						}
					});

				});
			}

			if (isiPhone < 0 && isiPad < 0 && $('.gplus1_button').length) {
				WH.setGooglePlusOneLangCode();
				var node2 = document.createElement('script');
				node2.type = 'text/javascript';
				node2.async = true;
				node2.src = 'http://apis.google.com/js/plusone.js';
				$('body').append(node2);
			}
			if (typeof WH.FB != 'undefined') WH.FB.init('new');
			if (typeof WH.GP != 'undefined') WH.GP.init();

			if ($('#pinterest').length) {
				var node3 = document.createElement('script');
				node3.type = 'text/javascript';
				node3.async = true;
				node3.src = 'http://assets.pinterest.com/js/pinit.js';
				$('body').append(node3);
			}

			if (typeof WH.imageFeedback != 'undefined') {
				WH.imageFeedback();
			}
		});
	})(jQuery);
</script>
<?
			//Temporarily taking down Jane
			/*
			var r = Math.random();
			if(r <= .05) {
				$('#starter_ad').show();
			}*/
?>
<? if ($showStaffStats): ?>
	<?= Pagestats::getJSsnippet("article") ?>
<? endif; ?>
<?= $wgOut->getScript() ?>

<? if (class_exists('GoodRevision')): ?>
	<? $grevid = $wgTitle ? GoodRevision::getUsedRev( $wgTitle->getArticleID() ) : ''; ?>
	<!-- shown patrolled revid=<?= $grevid ?>, latest=<?= $wgArticle ? $wgArticle->getLatest() : '' ?> -->
<? endif; ?>
<?= wfReportTime() ?>
</body>
</html>
<?php
	}

}

