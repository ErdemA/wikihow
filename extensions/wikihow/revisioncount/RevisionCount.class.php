<?

/*
 * 
 */
class RevisionCount {

	public static function getArticleRevisionCountCacheKey($articleID) {
		return wfMemcKey('revisioncount', $articleID);
	}

	public static function getArticleRevisionCount($aid) {
		global $wgMemc;

		$cachekey = self::getArticleRevisionCountCacheKey($aid);
		$revisionCount = $wgMemc->get($cachekey);
		if (is_numeric($revisionCount)) return $revisionCount;

		$dbr = wfGetDB(DB_SLAVE);
		$revisionCount = intval($dbr->selectField("revision",array('count(*)'),array('rev_page'=>$aid)));
		$wgMemc->set($cachekey, $revisionCount);
		return $revisionCount;
	}

	public function onRevisionInsertComplete( &$revision, $data, $flags) {
	  global $wgMemc;
	  
	  $cachekey = self::getArticleRevisionCountCacheKey($aid);
	  $wgMemc->incr($cachekey,1);
	}

	public static function onArticleSaveComplete( &$article, &$user, $text, $summary,
		$minoredit, $watchthis, $sectionanchor, &$flags, $revision)
	{
	  global $wgMemc;
	  
	  $cachekey = self::getArticleRevisionCountCacheKey($article->getId());
	  $wgMemc->incr($cachekey,1);
		return true;

	}
}
