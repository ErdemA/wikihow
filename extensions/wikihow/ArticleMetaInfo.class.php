<?

if ( !defined('MEDIAWIKI') ) die();

/**
 * Controls the html meta descriptions that relate to Google and Facebook
 * in the head of all article pages.
 *
 * Follows something like the active record pattern.
 */
class ArticleMetaInfo {
	static $dbr = null,
		$dbw = null;

	static $wgTitleAMIcache = null;

	var $title = null,
		$articleID = 0,
		$namespace = NS_MAIN,
		$titleText = '',
		$wikitext = '',
		$cachekey = '',
		$isMaintenace = false,
		$row = null;

	const MAX_DESC_LENGTH = 240;

	const DESC_STYLE_NOT_SPECIFIED = -1;
	const DESC_STYLE_ORIGINAL = 0;
	const DESC_STYLE_INTRO = 1;
	const DESC_STYLE_DEFAULT = 1; // SAME AS ABOVE
	const DESC_STYLE_STEP1 = 2;
	const DESC_STYLE_EDITED = 3;
	const DESC_STYLE_INTRO_NO_TITLE = 4;
	const DESC_STYLE_FACEBOOK_DEFAULT = 4; // SAME AS ABOVE

	public function __construct($title, $isMaintenace = false) {
		$this->title = $title;
		$this->articleID = $title->getArticleID();
		$this->namespace = $title->getNamespace();
		$this->titleText = $title->getText();
		$this->isMaintenace = $isMaintenace;
		$this->cachekey = wfMemcKey('metadata2', $this->namespace, $this->articleID);
	}

	/**
	 * After each edit of an article or when an article is started
	 */
	public static function refreshMetaDataCallback($article, $user, $wikitext) {
		$title = $article->getTitle();
		if ($title
			&& $title->exists()
			&& $title->getNamespace() == NS_MAIN
			&& $wikitext)
		{
			$meta = new ArticleMetaInfo($title, true);
			$meta->refreshMetaData();
		}
		return true;
	}

	/**
	 * Refresh all computed data about the meta description stuff
	 */
	public function refreshMetaData($style = self::DESC_STYLE_NOT_SPECIFIED) {
		$this->loadInfo();
		$this->updateImage();
		$this->populateDescription($style);
		$this->populateFacebookDescription();
		$this->saveInfo();
	}

	/**
	 * Return the image meta info for the article record
	 */
	public function getImage() {
		$this->loadInfo();
		// if ami_img == NULL, this field needs to be populated
		if ($this->row && $this->row['ami_img'] === null) {
			if ($this->updateImage()) {
				$this->saveInfo();
			}
		}
		return @$this->row['ami_img'];
	}

	/**
	 * Update the image meta info for the article record
	 */
	private function updateImage() {
		$url = WikihowShare::getShareImage($this->title);
		$this->row['ami_img'] = $url;
		return true;
	}

	/**
	 * Grab the wikitext for the article record
	 */
	private function getArticleWikiText() {
		// cache this if it was already pulled
		if ($this->wikitext) {
			return $this->wikitext;
		}

		if (!$this->title || !$this->title->exists()) {
			//throw new Exception('ArticleMetaInfo: title not found');
			return '';
		}

		$good = GoodRevision::newFromTitle($this->title, $this->articleID);                                           
		$revid = $good ? $good->latestGood() : 0;

		$dbr = $this->getDB();
		$rev = Revision::loadFromTitle($dbr, $this->title, $revid);
		if (!$rev) {
			//throw new Exception('ArticleMetaInfo: could not load revision');
			return '';
		}

		$this->wikitext = $rev->getText();
		return $this->wikitext;
	}

	/**
	 * Populate Facebook meta description.
	 */
	private function populateFacebookDescription() {
		$fbstyle = self::DESC_STYLE_FACEBOOK_DEFAULT;
		return $this->populateDescription($fbstyle, true);
	}

	/**
	 * Add a meta description (in one of the styles specified by the row) if
	 * a description is needed.
	 */
	private function populateDescription($forceDesc = self::DESC_STYLE_NOT_SPECIFIED, $facebook = false) {
		$this->loadInfo();

		if (!$facebook && 
			(self::DESC_STYLE_NOT_SPECIFIED == $forceDesc
			 || self::DESC_STYLE_EDITED == $this->row['ami_desc_style']))
		{
			$style = $this->row['ami_desc_style'];
		} else {
			$style = $forceDesc;
		}

		if (!$facebook) {
			$this->row['ami_desc_style'] = $style;
			list($success, $desc) = $this->buildDescription($style);
			$this->row['ami_desc'] = $desc;
		} else {
			list($success, $desc) = $this->buildDescription($style);
			$this->row['ami_facebook_desc'] = $desc;
		}

		return $success;
	}

	/**
	 * Sets the meta description in the database to be part of the intro, part
	 * of the first step, or 'original' which is something like "wikiHow
	 * article on How to <title>".
	 */
	private function buildDescription($style) {
		if (self::DESC_STYLE_ORIGINAL == $style) {
			return array(true, '');
		}
		if (self::DESC_STYLE_EDITED == $style) {
			return array(true, $this->row['ami_desc']);
		}

		$wikitext = $this->getArticleWikiText();
		if (!$wikitext) return array(false, '');

		if (self::DESC_STYLE_INTRO == $style
			|| self::DESC_STYLE_INTRO_NO_TITLE == $style)
		{
			// grab intro
			$desc = Wikitext::getIntro($wikitext);

			// append first step to intro if intro maybe isn't long enough
			if (strlen($desc) < 2 * self::MAX_DESC_LENGTH) {
				list($steps, ) = Wikitext::getStepsSection($wikitext);
				if ($steps) {
					$desc .= ' ' . Wikitext::cutFirstStep($steps);
				}
			}
		} elseif (self::DESC_STYLE_STEP1 == $style) {
			// grab steps section
			list($desc, ) = Wikitext::getStepsSection($wikitext);

			// pull out just the first step
			if ($desc) {
				$desc = Wikitext::cutFirstStep($desc);
			} else {
				$desc = Wikitext::getIntro($wikitext);
			}
		} else {
			//throw new Exception('ArticleMetaInfo: unknown style');

			return array(false, '');
		}

		$desc = Wikitext::flatten($desc);
		$howto = wfMsg('howto', $this->titleText);
		if ($desc) {
			if (self::DESC_STYLE_INTRO_NO_TITLE != $style) {
				$desc = $howto . '. ' . $desc;
			}
		} else {
			$desc = $howto;
		}

		$desc = self::trimDescription($desc);
		return array(true, $desc);
	}
	
	private static function trimDescription($desc) {
		// Chop desc length at MAX_DESC_LENGTH, and then last space in
		// description so that '...' is added at the end of a word.
		$desc = mb_substr($desc, 0, self::MAX_DESC_LENGTH);
		$len = mb_strlen($desc);
		// TODO: mb_strrpos method isn't available for some reason
		$pos = strrpos($desc, ' ');

		if ($len >= self::MAX_DESC_LENGTH && $pos !== false) {
			$toAppend = '...';
			if ($len - $pos > 20)  {
				$pos = $len - strlen($toAppend);
			}
			$desc = mb_substr($desc, 0, $pos) . $toAppend;
		}

		return $desc;
	}

	/**
	 * Load and return the <meta name="description" ... descriptive text.
	 */
	public function getDescription() {
		// return copy of description already found
		if ($this->row && $this->row['ami_desc']) {
			return $this->row['ami_desc'];
		}

		$this->loadInfo();

		// needs description
		if ($this->row
			&& $this->row['ami_desc_style'] != self::DESC_STYLE_ORIGINAL
			&& !$this->row['ami_desc'])
		{
			if ($this->populateDescription()) {
				$this->saveInfo();
			}
		}

		return @$this->row['ami_desc'];
	}

	/**
	 * Return the description style used.  Can be compared against the
	 * self::DESC_STYLE_* constants.
	 */
	public function getStyle() {
		$this->loadInfo();
		return $this->row['ami_desc_style'];
	}

	/**
	 * Returns the description in the "intro" style.  Note that this function
	 * is not optimized for caching and should only be called within the
	 * admin console.
	 */
	public function getDescriptionDefaultStyle() {
		$this->loadInfo();
		list($success, $desc) = $this->buildDescription(self::DESC_STYLE_DEFAULT);
		return $desc;
	}

	/**
	 * Set the meta description to a hand-edited one.
	 */
	public function setEditedDescription($desc) {
		$this->loadInfo();
		$this->row['ami_desc_style'] = self::DESC_STYLE_EDITED;
		$this->row['ami_desc'] = self::trimDescription($desc);
		$this->refreshMetaData();
	}

	/**
	 * Set the meta description to a hand-edited one.
	 */
	public function resetMetaData() {
		$this->loadInfo();
		$this->row['ami_desc_style'] = self::DESC_STYLE_DEFAULT;
		$this->row['ami_desc'] = '';
		$this->refreshMetaData();
	}

	/**
	 * Load and return the <meta name="description" ... descriptive text.
	 */
	public function getFacebookDescription() {
		// return copy of description already found
		if ($this->row && $this->row['ami_facebook_desc']) {
			return $this->row['ami_facebook_desc'];
		}

		$this->loadInfo();

		// needs FB description
		if ($this->row && !$this->row['ami_facebook_desc']) {
			if ($this->populateFacebookDescription()) {
				$this->saveInfo();
			}
		}

		return @$this->row['ami_facebook_desc'];
	}

	/**
	 * Retrieve the meta info stored in the database.
	 */
	/*public function getInfo() {
		$this->loadInfo();
		return $this->row;
	}*/

	/* DB schema
	 *
	 CREATE TABLE article_meta_info (
	   ami_id int unsigned not null,
	   ami_namespace int unsigned not null default 0,
	   ami_title varchar(255) not null default '',
	   ami_updated varchar(14) not null default '',
	   ami_desc_style tinyint(1) not null default 1,
	   ami_desc varchar(255) not null default '',
	   ami_facebook_desc varchar(255) not null default '',
	   ami_img varchar(255) default null,
	   primary key (ami_id)
	 ) DEFAULT CHARSET=utf8;
	 *
	 alter table article_meta_info add column ami_facebook_desc varchar(255) not null default '' after ami_desc;
	 *
	 */

	/**
	 * Create a database handle.  $type can be 'read' or 'write'
	 */
	private function getDB($type = 'read') {
		if ($type == 'write') {
			if (self::$dbw == null) self::$dbw = wfGetDB(DB_MASTER);
			return self::$dbw;
		} elseif ($type == 'read') {
			if (self::$dbr == null) self::$dbr = wfGetDB(DB_SLAVE);
			return self::$dbr;
		} else {
			throw new Exception('unknown DB handle type');
		}
	}

	/**
	 * Load the meta info record from either DB or memcache
	 */
	private function loadInfo() {
		global $wgMemc;

		if ($this->row) return;

		$res = null;
		// Don't pull from cache if maintenance is being performed
		if (!$this->isMaintenace) {
			$res = $wgMemc->get($this->cachekey);
		}

		if ($res === null) {
			$articleID = $this->articleID;
			$namespace = MW_MAIN;
			$dbr = $this->getDB();
			$sql = 'SELECT * FROM article_meta_info WHERE ami_id=' . $dbr->addQuotes($articleID) . ' AND ami_namespace=' . intval($namespace);
			$res = $dbr->query($sql, __METHOD__);
			$this->row = $dbr->fetchRow($res);

			if (!$this->row) {
				$this->row = array(
					'ami_id' => $articleID,
					'ami_namespace' => intval($namespace),
					'ami_desc_style' => self::DESC_STYLE_DEFAULT,
					'ami_desc' => '',
					'ami_facebook_desc' => '',
				);
			} else {
				foreach ($this->row as $k => $v) {
					if (is_int($k)) {
						unset($this->row[$k]);
					}
				}
			}
			$wgMemc->set($this->cachekey, $this->row);
		} else {
			$this->row = $res;
		}
	}

	/**
	 * Save article meta info to both DB and memcache
	 */
	private function saveInfo() {
		global $wgMemc;

		if (empty($this->row)) {
			throw new Exception(__METHOD__ . ': nothing loaded');
		}

		$this->row['ami_updated'] = wfTimestampNow(TS_MW);

		if (!isset($this->row['ami_title'])) {
			$this->row['ami_title'] = $this->titleText;
		}
		if (!isset($this->row['ami_id'])) {
			$articleID = $this->articleID;
			$this->row['ami_id'] = $articleID;
		}
		if (!isset($this->row['ami_namespace'])) {
			$namespace = $this->namespace;
			$this->row['ami_namespace'] = $namespace;
		}
		if (!isset($this->row['ami_desc_style']) || is_null($this->row['ami_desc_style'])) {
			$this->row['ami_desc_style'] = self::DESC_STYLE_DEFAULT;
		}

		$dbw = $this->getDB('write');
		$sql = 'REPLACE INTO article_meta_info SET ' . $dbw->makeList($this->row, LIST_SET);
		$res = $dbw->query($sql, __METHOD__);
		$wgMemc->set($this->cachekey, $this->row);
	}

	private static function getMetaSubcategories($title, $limit = 3) {
		$results = array();
		$dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->select(
			array('categorylinks', 'page'),
			array('page_namespace', 'page_title'),
			array('page_id=cl_from', 'page_namespace' => NS_CATEGORY, 'cl_to' => $title->getDBKey()),
			__METHOD__,
			array('ORDER BY' => 'page_counter desc', 'LIMIT' => ($limit + 1) )
		);
		$requests = wfMsg('requests');
		$count = 0;
		foreach ($res as $row) {
			if ($count++ == $limit) break;
			$t = Title::makeTitle($row->page_namespace, $row->page_title);
			if (strpos($t->getText(), $requests) === false) {
				$results[] = $t->getText();
			}
		}
		return $results;
	}

	// Add these meta properties that the Facebook graph protocol wants
	// https://developers.facebook.com/docs/opengraph/
	static function addFacebookMetaProperties($titleText) {
		global $wgOut, $wgTitle, $wgRequest, $wgServer;

		$action = $wgRequest->getVal('action', '');
		if ($wgTitle->getNamespace() != NS_MAIN
			|| $wgTitle->getText() == wfMsg('mainpage')
			|| (!empty($action) && $action != 'view'))
		{
			return;
		}

		$url = $wgTitle->getFullURL();

		if (!self::$wgTitleAMIcache) {
			self::$wgTitleAMIcache = new ArticleMetaInfo($wgTitle);
		}
		$ami = self::$wgTitleAMIcache;
		$fbDesc = $ami->getFacebookDescription();

		$img = $ami->getImage();

		// if this was shared via thumbs up, we want a different description.
		// url will look like this, for example:
		// http://www.wikihow.com/Kiss?fb=t
		if ($wgRequest->getVal('fb', '') == 't') {
			$fbDesc = wfMsg('article_meta_description_facebook', $wgTitle->getText());
			$url .= "?fb=t";
		}


		// If this url isn't a facebook action, make sure the url is formatted appropriately 
		if ($wgRequest->getVal('fba','') == 't') {
			$url .= "?fba=t";
		} else {
			// If this url isn't a facebook action, add 'How to ' to the title
			$titleText = wfMsg('howto', $titleText);
		}	

		$props = array(
			array( 'property' => 'og:title', 'content' => $titleText ),
			array( 'property' => 'og:type', 'content' => 'article' ),
			array( 'property' => 'og:url', 'content' => $url ),
			array( 'property' => 'og:site_name', 'content' => 'wikiHow' ),
			array( 'property' => 'og:description', 'content' => $fbDesc ),
		);
		if ($img) {
			// Note: we can add multiple copies of this meta tag at some point
			// Note 2: we don't want to use pad*.whstatic.com because we want
			//   these imgs to refresh reasonably often as the page refreshes
			$img = $wgServer . $img;
			$props[] = array( 'property' => 'og:image', 'content' => $img );
		}

		foreach ($props as $prop) {
			$wgOut->addHeadItem($prop['property'], '<meta property="' . $prop['property'] . '" content="' . htmlentities($prop['content']) . '"/>' . "\n");
		}
	}

	static function getCurrentTitleMetaDescription() {
		global $wgTitle;
		static $titleTest = null;

		$return = '';
		if ($wgTitle->getNamespace() == NS_MAIN && $wgTitle->getFullText() == wfMsg('mainpage')) {
			$return = wfMsg('mainpage_meta_description');
		} elseif ($wgTitle->getNamespace() == NS_MAIN) {
			$desc = '';
			if (!$titleTest) {
				$titleTest = TitleTests::newFromTitle($wgTitle);
				if ($titleTest) {
					$desc = $titleTest->getMetaDescription();
				}
			}
			if (!$desc) {
				if (!self::$wgTitleAMIcache) {
					self::$wgTitleAMIcache = new ArticleMetaInfo($wgTitle);
				}
				$ami = self::$wgTitleAMIcache;
				$desc = $ami->getDescription();
			}
			if (!$desc) {
				$return = wfMsg('article_meta_description', $wgTitle->getText() );
			} else {
				$return = $desc;
			}
		} elseif ($wgTitle->getNamespace() == NS_CATEGORY) {
			// get keywords
			$subcats = self::getMetaSubcategories($wgTitle, 3);
			$keywords = implode(", ", $subcats);
			if ($keywords) {
				$return = wfMsg('category_meta_description', $wgTitle->getText(), $keywords);
			} else {
				$return = wfMsg('subcategory_meta_description', $wgTitle->getText(), $keywords);
			}
		} elseif ($wgTitle->getNamespace() == NS_USER) {
			$desc = ProfileBox::getMetaDesc();
			$return = $desc;
		} elseif ($wgTitle->getNamespace() == NS_IMAGE) {
			$articles = ImageHelper::getLinkedArticles($wgTitle);
			if (count($articles) && $articles[0]) {
				$articleTitle = wfMsg('howto', $articles[0]);
				if (preg_match('@Step (\d+)@', $wgTitle->getText(), $m)) {
					$imageNum = '#' . $m[1];
				} else {
					$imageNum = '';
				}
				$return = wfMsg('image_meta_description', $articleTitle, $imageNum);
			} else {
				$return = wfMsg('image_meta_description_no_article', $wgTitle->getText());
			}
		} elseif ($wgTitle->getNamespace() == NS_SPECIAL && $wgTitle->getText() == "Popularpages") {
			$return = wfMsg('popularpages_meta_description');
		}
		return $return;

	}

	static function getCurrentTitleMetaKeywords() {
		global $wgTitle;

		$return = '';
		if ($wgTitle->getNamespace() == NS_MAIN && $wgTitle->getFullText() == wfMsg('mainpage')) {
			$return = wfMsg('mainpage_meta_keywords');
		} elseif ($wgTitle->getNamespace() == NS_MAIN ) {
			$return = wfMsg('article_meta_keywords', htmlentities($wgTitle->getText()) );
		} elseif ($wgTitle->getNamespace() == NS_CATEGORY) {
			$subcats = self::getMetaSubcategories($wgTitle, 10);
			$return = implode(", ", $subcats);
			if (!trim($return)) {
				$return = wfMsg( 'category_meta_keywords_default', htmlentities($wgTitle->getText()) );
			}
		} elseif ($wgTitle->getNamespace() == NS_SPECIAL && $wgTitle->getText() == "Popularpages"){
			$return = wfMsg('popularpages_meta_keywords');
		}
		return $return;
	}


}

