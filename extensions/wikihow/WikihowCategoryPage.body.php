<?

if (!defined('MEDIAWIKI')) die();

class WikihowCategoryPage extends CategoryPage {
	
	const STARTING_CHUNKS = 20;
	const PULL_CHUNKS = 5;
	const SINGLE_WIDTH = 163; // (article_shell width - 2*article_inner padding - 3*SINGLE_SPACING)/4
	const SINGLE_HEIGHT = 119; //should be .73*SINGLE_WIDTH
	const SINGLE_SPACING = 16;

	var $catStream;

	function view() {
		global $wgOut, $wgRequest, $wgUser, $wgTitle, $wgHooks;
		 
		if (!$wgTitle->exists()) {
			parent::view();
			return;
		}
		
		if (count($wgRequest->getVal('diff')) > 0) {
			return Article::view();
		}
		
		$restAction = $wgRequest->getVal('restaction');
		if ($restAction == 'pull-chunk') {
			$wgOut->setArticleBodyOnly(true);
			$start = $wgRequest->getInt('start');
			if (!$start) return;
			$categoryViewer = new WikihowCategoryViewer($wgTitle);
			$this->catStream = new WikihowArticleStream($categoryViewer, $start);
			$html = $this->catStream->getChunks(4, WikihowCategoryPage::SINGLE_WIDTH, WikihowCategoryPage::SINGLE_SPACING, WikihowCategoryPage::SINGLE_HEIGHT);
			$ret = json_encode( array('html' => $html) );
			$wgOut->addHTML($ret);
		} else {
			$wgOut->setRobotPolicy('index,follow', 'Category Page');
			$wgOut->setPageTitle($wgTitle->getText());
			$from = $wgRequest->getVal( 'from' );
			$until = $wgRequest->getVal( 'until' );
			$viewer = new WikihowCategoryViewer( $this->mTitle, $from, $until );
			$viewer->clearState();
			$viewer->doQuery();
			$viewer->finaliseCategoryState();
			if ($wgRequest->getVal('viewMode',0)) {
				$wgOut->addHtml('<div class="section minor_section">');
				$wgOut->addHtml('<ul><li>');
				$wgOut->addHtml(implode("</li>\n<li>", $viewer->articles));
				$wgOut->addHtml('</li></ul>');
				$wgOut->addHtml('</div>');
			}
			else {
				$wgHooks['BeforePageDisplay'][] = array('WikihowCategoryPage::addCSSAndJs');
				$categoryViewer = new WikihowCategoryViewer($wgTitle);
				$this->catStream = new WikihowArticleStream($categoryViewer, 0);
				$html = $this->catStream->getChunks(self::STARTING_CHUNKS, WikihowCategoryPage::SINGLE_WIDTH, WikihowCategoryPage::SINGLE_SPACING, WikihowCategoryPage::SINGLE_HEIGHT);
				$wgOut->addHTML($html);
			}


			$sk = $wgUser->getSkin();
			$subCats = $viewer->shortListRD( $viewer->children, $viewer->children_start_char );
			if($subCats != "") {
				$subCats  = "<h3>{$this->mTitle->getText()}</h3>{$subCats}";
				$sk->addWidget($subCats);
			}

			$furtherEditing = $viewer->getArticlesFurtherEditing($viewer->articles, $viewer->article_info);
			if($furtherEditing != "");
				$sk->addWidget($furtherEditing);
		}
	}

	function addCSSAndJs() {
		global $wgOut;

		$wgOut->addJSCode('catj');
		$wgOut->addCSScode('catc');

		return true;
	}

	function isFileCacheable() {
		return true;
	}

}

