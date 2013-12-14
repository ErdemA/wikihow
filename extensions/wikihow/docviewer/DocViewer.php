<?
if (!defined('MEDIAWIKI')) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'DocViewer',
	'author' => 'Scott Cushman',
	'description' => 'The page that displays embedded documents.',
);

$wgSpecialPages['DocViewer'] = 'DocViewer';
$wgAutoloadClasses['DocViewer'] = dirname( __FILE__ ) . '/DocViewer.body.php';
$wgExtensionMessagesFiles['DocViewer'] = dirname(__FILE__) . '/DocViewer.i18n.php';

$wgSpecialPages['DocViewerList'] = 'DocViewerList';
$wgAutoloadClasses['DocViewerList'] = dirname( __FILE__ ) . '/DocViewerList.body.php';
$wgGroupPermissions['*']['DocViewerList'] = false;
$wgGroupPermissions['staff']['DocViewerList'] = true;

$wgSpecialPages['GetSamples'] = 'GetSamples';
$wgAutoloadClasses['GetSamples'] = dirname( __FILE__ ) . '/GetSamples.body.php';


$wgHooks["ParserGetDocThumb"][] = array("wfGrabDocThumb"); 
$wgHooks["ArticleSaveComplete"][] = array("wfConnectDoc");

function wfGrabDocThumb(&$parser, &$nt, &$ret) {
	if (!$nt) return true;
	//remove that annoying colon if it's there
	$nt = preg_replace('@:@','',$nt);
	//do it
	$ret = DocViewer::GrabDocThumb($nt);
	return true;
}

/*
 * If someone added a [[Doc:foo]] then add it to the link table
 */
function wfConnectDoc(&$article, &$user, $text, $summary, $minoredit, $watchthis, $sectionanchor, &$flags, $revision) {
	if (!$article || !$text) return true;
	if ($article->getID() == 0) return true;

	//first check to see if there's a [[Doc:foo]] in the article
	$count = preg_match_all('@\[\[Doc:([^\]]*)\]\]@i', $text, $matches, PREG_SET_ORDER);
	
	if ($count) {
		$doc_array = array();
	
		//cycle through and clean up the samples, check for multiples, etc.
		foreach ($matches as $match) {
			$doc = preg_replace('@ @','-',$match[1]);
			
			//check for multiple
			$sample_array = explode(',',$doc);
			foreach ($sample_array as $doc) {
				$doc_array[] = $doc;
			}
		}
		
		//update that link table
		foreach ($doc_array as $doc) {
			DocViewer::updateLinkTable($article,$doc);
		}
		
		//make sure we didn't lose any
		$dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->select('dv_links', 'dvl_doc', array('dvl_page' => $article->getID()), __METHOD__);
		
		foreach ($res as $row) {
			if (!in_array($row->dvl_doc, $doc_array)) {
				//no longer on the page; remove it
				DocViewer::updateLinkTable($article, $row->dvl_doc, false);
			}
		}
	}
	else {
		//nothing in the article?
		//remove anything in the link table if there are mentions
		DocViewer::updateLinkTable($article,'',false);
	}
	
	return true;
}