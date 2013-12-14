<?php
/**
 */

/**
 *
 */
if( !defined( 'MEDIAWIKI' ) )
	die( 1 );

/**
 * Special handling for image description pages
 *
 * @addtogroup Media
 */
class ImagePage extends Article {

	/* private */ var $img;  // Image object this page is shown for
	/* private */ var $repo;
	var $mExtraDescription = false;

	function __construct( $title, $time = false ) {
		parent::__construct( $title );
		$this->img = wfFindFile( $this->mTitle, $time );
		if ( !$this->img ) {
			$this->img = wfLocalFile( $this->mTitle );
			$this->current = $this->img;
		} else {
			$this->current = $time ? wfLocalFile( $this->mTitle ) : $this->img;
		}
		$this->repo = $this->img->repo;
	}

	/**
	 * Handler for action=render
	 * Include body text only; none of the image extras
	 */
	function render() {
		global $wgOut;
		$wgOut->setArticleBodyOnly( true );
		$wgOut->addSecondaryWikitext( $this->getContent() );
	}

	function view() {
		global $wgOut, $wgShowEXIF, $wgRequest, $wgUser, $wgTitle;

$startTime = strtotime('November 5, 2013');
$oneWeek = 7 * 24 * 60 * 60;
$rolloutArticle = Misc::percentileRollout($startTime, $oneWeek);
$ua = @$_SERVER['HTTP_USER_AGENT'];
if (!$rolloutArticle && preg_match('@msnbot@', $ua)) {
header('HTTP/1.1 503 Service Temporarily Unavailable');
echo "Sorry, not now MSNBOT!";
exit;
}
		$sk = $wgUser->getSkin();
		$diff = $wgRequest->getVal( 'diff' );
		$diffOnly = $wgRequest->getBool( 'diffonly', $wgUser->getOption( 'diffonly' ) );

		if ( $this->mTitle->getNamespace() != NS_IMAGE || ( isset( $diff ) && $diffOnly ) )
			return Article::view();

		if ($wgShowEXIF && $this->img->exists()) {
			// FIXME: bad interface, see note on MediaHandler::formatMetadata().
			$formattedMetadata = $this->img->formatMetadata();
			$showmeta = $formattedMetadata !== false;
		} else {
			$showmeta = false;
		}

		//NEW
		//if ($this->img->exists())
			//$wgOut->addHTML($this->showTOC($showmeta));

		$this->openShowImage();
		ImageHelper::showDescription($this->mTitle);

		$lastUser = $this->current->getUser();
		$userLink = $sk->makeLinkObj(Title::makeTitle(NS_USER, $lastUser), $lastUser);

		$wgOut->addHTML("<div style='margin-bottom:20px'></div>");
		//ImageHelper::getSummaryInfo($this->img);

		# Show shared description, if needed
		if ( $this->mExtraDescription ) {
			$fol = wfMsgNoTrans( 'shareddescriptionfollows' );
			if( $fol != '-' && !wfEmptyMsg( 'shareddescriptionfollows', $fol ) ) {
				$wgOut->addWikiText( $fol );
			}
			$wgOut->addHTML( '<div id="shared-image-desc">' . $this->mExtraDescription . '</div>' );
		}
		$this->closeShowImage();
		$currentHTML = $wgOut->getHTML();
		$wgOut->clearHTML();
		Article::view();
		$articleContent = $wgOut->getHTML();
		$wgOut->clearHTML();
		$wgOut->addHTML($currentHTML);

		$diffSeparator = "<h2>" . wfMsg('currentrev') . "</h2>";
		$articleParts = explode($diffSeparator, $articleContent);
		if(count($articleParts) > 1){
			$wgOut->addHTML($articleParts[0]);
		}

		$articles = ImageHelper::getLinkedArticles($this->mTitle);
		
		if (ImageHelper::IMAGES_ON) {
			ImageHelper::getConnectedImages($articles, $this->mTitle);
			ImageHelper::getRelatedWikiHows($this->mTitle);
		}
		ImageHelper::addSideWidgets($this->mTitle);

		# No need to display noarticletext, we use our own message, output in openShowImage()
		if ( $this->getID() ) {
			

		} else {
			# Just need to set the right headers
			$wgOut->setArticleFlag( true );
			$wgOut->setRobotpolicy( 'noindex,nofollow' );
			$wgOut->setPageTitle( $this->mTitle->getPrefixedText() );
			$this->viewUpdates();
		}

		if ($wgUser && !$wgUser->isAnon()) {
			$this->imageHistory();
		}

		ImageHelper::displayBottomAds();

		if ( $showmeta ) {
			global $wgStylePath, $wgStyleVersion;
			$expand = htmlspecialchars( wfEscapeJsString( wfMsg( 'metadata-expand' ) ) );
			$collapse = htmlspecialchars( wfEscapeJsString( wfMsg( 'metadata-collapse' ) ) );
			$wgOut->addHTML( Xml::element( 'h2', array( 'id' => 'metadata' ), wfMsg( 'metadata' ) ). "\n" );
			$wgOut->addWikiText( $this->makeMetadataTable( $formattedMetadata ) );
			$wgOut->addHTML(
				"<script type=\"text/javascript\" src=\"$wgStylePath/common/metadata.js?$wgStyleVersion\"></script>\n" .
				"<script type=\"text/javascript\">attachMetadataToggle('mw_metadata', '$expand', '$collapse');</script>\n" );
		}
	}

	/**
	 * Create the TOC
	 *
	 * @access private
	 *
	 * @param bool $metadata Whether or not to show the metadata link
	 * @return string
	 */
	function showTOC( $metadata ) {
		global $wgLang;
		$r = '<ul id="filetoc">
			<li><a href="#file">' . $wgLang->getNsText( NS_IMAGE ) . '</a></li>
			<li><a href="#filehistory">' . wfMsgHtml( 'filehist' ) . '</a></li>
			<li><a href="#filelinks">' . wfMsgHtml( 'imagelinks' ) . '</a></li>' .
			($metadata ? ' <li><a href="#metadata">' . wfMsgHtml( 'metadata' ) . '</a></li>' : '') . '
		</ul>';
		return $r;
	}

	/**
	 * Make a table with metadata to be shown in the output page.
	 *
	 * FIXME: bad interface, see note on MediaHandler::formatMetadata().
	 *
	 * @access private
	 *
	 * @param array $exif The array containing the EXIF data
	 * @return string
	 */
	function makeMetadataTable( $metadata ) {
		$r = wfMsg( 'metadata-help' ) . "\n\n";
		$r .= "{| id=mw_metadata class=mw_metadata\n";
		foreach ( $metadata as $type => $stuff ) {
			foreach ( $stuff as $v ) {
				$class = Sanitizer::escapeId( $v['id'] );
				if( $type == 'collapsed' ) {
					$class .= ' collapsable';
				}
				$r .= "|- class=\"$class\"\n";
				$r .= "!| {$v['name']}\n";
				$r .= "|| {$v['value']}\n";
			}
		}
		$r .= '|}';
		return $r;
	}

	/**
	 * Overloading Article's getContent method.
	 *
	 * Omit noarticletext if sharedupload; text will be fetched from the
	 * shared upload server if possible.
	 */
	function getContent() {
		if( $this->img && !$this->img->isLocal() && 0 == $this->getID() ) {
			return '';
		}
		return Article::getContent();
	}

	function openShowImage() {
		global $wgOut, $wgUser, $wgImageLimits, $wgRequest, $wgLang, $wgContLang;

		$full_url  = $this->img->getURL();
		$linkAttribs = false;
		$sizeSel = intval( $wgUser->getOption( 'imagesize') );
		if( !isset( $wgImageLimits[$sizeSel] ) ) {
			$sizeSel = User::getDefaultOption( 'imagesize' );

			// The user offset might still be incorrect, specially if
			// $wgImageLimits got changed (see bug #8858).
			if( !isset( $wgImageLimits[$sizeSel] ) ) {
				// Default to the first offset in $wgImageLimits
				$sizeSel = 0;
			}
		}
		$max = $wgImageLimits[$sizeSel];
		$maxWidth = $max[0];
		//XXMOD for fixed width new layout.  eventhough 800x600 is default 679 is max article width
		if ($maxWidth > 679)
			$maxWidth = 629;
		$maxHeight = $max[1];
		$sk = $wgUser->getSkin();
		$dirmark = $wgContLang->getDirMark();

		if ( $this->img->exists() ) {
			# image
			$page = $wgRequest->getIntOrNull( 'page' );
			if ( is_null( $page ) ) {
				$params = array();
				$page = 1;
			} else {
				$params = array( 'page' => $page );
			}
			$width_orig = $this->img->getWidth();
			$width = $width_orig;
			$height_orig = $this->img->getHeight();
			$height = $height_orig;
			$mime = $this->img->getMimeType();
			$showLink = false;
			$linkAttribs = array( 'href' => $full_url );
			$longDesc = $this->img->getLongDesc();

			wfRunHooks( 'ImageOpenShowImageInlineBefore', array( &$this , &$wgOut ) )	;

			if ( $this->img->allowInlineDisplay() ) {
				# image

				# "Download high res version" link below the image
				#$msgsize = wfMsgHtml('file-info-size', $width_orig, $height_orig, $sk->formatSize( $this->img->getSize() ), $mime );
				# We'll show a thumbnail of this image
				if ( $width > $maxWidth || $height > $maxHeight ) {
					# Calculate the thumbnail size.
					# First case, the limiting factor is the width, not the height.
					if ( $width / $height >= $maxWidth / $maxHeight ) {
						$height = round( $height * $maxWidth / $width);
						$width = $maxWidth;
						# Note that $height <= $maxHeight now.
					} else {
						$newwidth = floor( $width * $maxHeight / $height);
						$height = round( $height * $newwidth / $width );
						$width = $newwidth;
						# Note that $height <= $maxHeight now, but might not be identical
						# because of rounding.
					}
					$msgbig  = wfMsgHtml( 'show-big-image' );
					$msgsmall = wfMsgExt( 'show-big-image-thumb',
						array( 'parseinline' ), $wgLang->formatNum( $width ), $wgLang->formatNum( $height ) );
				} else {
					# Image is small enough to show full size on image page
					$msgbig = htmlspecialchars( $this->img->getName() );
					$msgsmall = wfMsgExt( 'file-nohires', array( 'parseinline' ) );
				}

				$params['width'] = $width;
				$thumbnail = $this->img->transform( $params );

				$anchorclose = "<br />";
				if( $this->img->mustRender() ) {
					$showLink = true;
				} else {
					$anchorclose .=
						$msgsmall .
						'<br />' . Xml::tags( 'a', $linkAttribs,  $msgbig ) . "$dirmark " . $longDesc;
				}

				if ( $this->img->isMultipage() ) {
					$wgOut->addHTML( '<table class="multipageimage"><tr><td>' );
				}

				if ( $thumbnail ) {
					$options = array(
						'alt' => $this->img->getTitle()->getPrefixedText(),
						'file-link' => true,
					);
					$thumb = $thumbnail->toHtml($options);
					$recent = wfTimestamp(TS_MW, time() - 3600);
					//XXXCHANGED: Due to the CDN works, don't show the CDN image on the image
					// page if it's been changed within the past hour, it needs some time
					// to update
					if ($this->img->timestamp > $recent)
						$thumb = preg_replace("@http://[a-z0-9]*.wikihow.com@im", "", $thumb);
					$wgOut->addHTML( '<div class="fullImageLink minor_section" id="file">' .
						$thumb . '</div>' );
				}

				if ( $this->img->isMultipage() ) {
					$count = $this->img->pageCount();

					if ( $page > 1 ) {
						$label = $wgOut->parse( wfMsg( 'imgmultipageprev' ), false );
						$link = $sk->makeKnownLinkObj( $this->mTitle, $label, 'page='. ($page-1) );
						$thumb1 = $sk->makeThumbLinkObj( $this->mTitle, $this->img, $link, $label, 'none',
							array( 'page' => $page - 1 ) );
					} else {
						$thumb1 = '';
					}

					if ( $page < $count ) {
						$label = wfMsg( 'imgmultipagenext' );
						$link = $sk->makeKnownLinkObj( $this->mTitle, $label, 'page='. ($page+1) );
						$thumb2 = $sk->makeThumbLinkObj( $this->mTitle, $this->img, $link, $label, 'none',
							array( 'page' => $page + 1 ) );
					} else {
						$thumb2 = '';
					}

					global $wgScript;
					$select = '<form name="pageselector" action="' .
						htmlspecialchars( $wgScript ) .
						'" method="get" onchange="document.pageselector.submit();">' .
						Xml::hidden( 'title', $this->getTitle()->getPrefixedDbKey() );
					$select .= $wgOut->parse( wfMsg( 'imgmultigotopre' ), false ) .
						' <select id="pageselector" name="page">';
					for ( $i=1; $i <= $count; $i++ ) {
						$select .= Xml::option( $wgLang->formatNum( $i ), $i,
							$i == $page );
					}
					$select .= '</select>' . $wgOut->parse( wfMsg( 'imgmultigotopost' ), false ) .
						'<input type="submit" value="' .
						htmlspecialchars( wfMsg( 'imgmultigo' ) ) . '"></form>';

					$wgOut->addHTML( '</td><td><div class="multipageimagenavbox">' .
						"$select<hr />$thumb1\n$thumb2<br clear=\"all\" /></div></td></tr></table>" );
				}
			} else {
				#if direct link is allowed but it's not a renderable image, show an icon.
				if ($this->img->isSafeFile()) {
					$icon= $this->img->iconThumb();

					$wgOut->addHTML( '<div class="fullImageLink minor_section" id="file">' .
					$icon->toHtml( array( 'desc-link' => true ) ) .
					'</div>' );
				}

				$showLink = true;
			}


			if ($showLink) {
				$filename = wfEscapeWikiText( $this->img->getName() );

				if (!$this->img->isSafeFile()) {
					$warning = wfMsgNoTrans( 'mediawarning' );
					$wgOut->addWikiText( <<<EOT
<div class="fullMedia">
<span class="dangerousLink">[[Media:$filename|$filename]]</span>$dirmark
<span class="fileInfo"> $longDesc</span>
</div>

<div class="mediaWarning">$warning</div>
EOT
						);
				} else {
					$wgOut->addWikiText( <<<EOT
<div class="fullMedia">
[[Media:$filename|$filename]]$dirmark <span class="fileInfo"> $longDesc</span>
</div>
EOT
						);
				}
			}

			if(!$this->img->isLocal()) {
				$this->printSharedImageText();
			}
		} else {
			# Image does not exist

			$title = SpecialPage::getTitleFor( 'Upload' );
			$link = $sk->makeKnownLinkObj($title, wfMsgHtml('noimage-linktext'),
				'wpDestFile=' . urlencode( $this->img->getName() ) );
			$wgOut->addHTML( wfMsgWikiHtml( 'noimage', $link ) );
		}
	}

	function printSharedImageText() {
		global $wgOut, $wgUser;

		$descUrl = $this->img->getDescriptionUrl();
		$descText = $this->img->getDescriptionText();
		$s = "<div class='sharedUploadNotice'>" . wfMsgWikiHtml("sharedupload");
		if ( $descUrl && !$descText) {
			$sk = $wgUser->getSkin();
			$link = $sk->makeExternalLink( $descUrl, wfMsg('shareduploadwiki-linktext') );
			$s .= " " . wfMsgWikiHtml('shareduploadwiki', $link);
		}
		$s .= "</div>";
		$wgOut->addHTML($s);

		if ( $descText ) {
			$this->mExtraDescription = $descText;
		}
	}

	function getUploadUrl() {
		$uploadTitle = SpecialPage::getTitleFor( 'Upload' );
		return $uploadTitle->getFullUrl( 'wpDestFile=' . urlencode( $this->img->getName() ) );
	}

	/**
	 * Print out the various links at the bottom of the image page, e.g. reupload,
	 * external editing (and instructions link) etc.
	 */
	function uploadLinksBox($writeIt = true) {
		global $wgUser, $wgOut, $wgTitle;

		if( !$this->img->isLocal() )
			return;

		$sk = $wgUser->getSkin();

		$html .= '<br /><ul>';

		# "Upload a new version of this file" link
		# Disabling upload a new version of this file link per Bug #585
		if(false && UploadForm::userCanReUpload($wgUser,$this->img->name) ) {
			$ulink = $sk->makeExternalLink( $this->getUploadUrl(), wfMsg( 'uploadnewversion-linktext' ) );
			$html .= "<li><div class='plainlinks'>{$ulink}</div></li>";
		}

		# External editing link
		//$elink = $sk->makeKnownLinkObj( $this->mTitle, wfMsgHtml( 'edit-externally' ), 'action=edit&externaledit=true&mode=file' );
		//$wgOut->addHtml( '<li>' . $elink . '<div>' . wfMsgWikiHtml( 'edit-externally-help' ) . '</div></li>' );
		
		//wikitext message
		$html .= '<li>' . wfMsg('image_instructions', $wgTitle->getFullText()) . '</li></ul>';
		
		if ($writeIt) {
			$wgOut->addHtml($html);
		}
		else {
			return $html;
		}
	}

	function closeShowImage()
	{
		# For overloading

	}

	/**
	 * If the page we've just displayed is in the "Image" namespace,
	 * we follow it with an upload history of the image and its usage.
	 */
	function imageHistory()
	{
		global $wgUser, $wgOut, $wgUseExternalEditor;

		$sk = $wgUser->getSkin();

		if ( $this->img->exists() ) {
			$list = new ImageHistoryList( $sk, $this->current );
			$file = $this->current;
			$dims = $file->getDimensionsString();
			$lineNum = 0;
			$s = $list->beginImageHistoryList() .
				$list->imageHistoryLine( true, wfTimestamp(TS_MW, $file->getTimestamp()),
					$this->mTitle->getDBkey(),  $file->getUser('id'),
					$file->getUser('text'), $file->getSize(), $file->getDescription(),
					$dims,
					$lineNum
				);
			$lineNum++;
			$hist = $this->img->getHistory();
			foreach( $hist as $file ) {
				$dims = $file->getDimensionsString();
				$s .= $list->imageHistoryLine( false, wfTimestamp(TS_MW, $file->getTimestamp()),
			  		$file->getArchiveName(), $file->getUser('id'),
			  		$file->getUser('text'), $file->getSize(), $file->getDescription(),
					$dims,
					$lineNum
				);
				$lineNum;
			}
			
			$s .= $list->endImageHistoryList();
			
			if( $wgUseExternalEditor ) {
				$s .= $this->uploadLinksBox(false);
			}
			
			$s .= '</div>';
			
			$s = '<div class="minor_section">'.$s.'</div>';
		} else { $s=''; }
		$wgOut->addHTML( $s );

		$this->img->resetHistory();	// free db resources
	}

	function imageLinks()
	{
		global $wgUser, $wgOut;

		$wgOut->addHTML( Xml::element( 'h2', array( 'id' => 'filelinks' ), wfMsg( 'imagelinks' ) ) . "\n" );

		$dbr = wfGetDB( DB_SLAVE );
		$page = $dbr->tableName( 'page' );
		$imagelinks = $dbr->tableName( 'imagelinks' );

		$sql = "SELECT page_namespace,page_title FROM $imagelinks,$page WHERE il_to=" .
		  $dbr->addQuotes( $this->mTitle->getDBkey() ) . " AND il_from=page_id";
		$sql = $dbr->limitResult($sql, 500, 0);
		$res = $dbr->query( $sql, "ImagePage::imageLinks" );

		if ( 0 == $dbr->numRows( $res ) ) {
			$wgOut->addHtml( '<p>' . wfMsg( "nolinkstoimage" ) . "</p>\n" );
			return;
		}
		$wgOut->addHTML( '<p>' . wfMsg( 'linkstoimage' ) .  "</p>\n<ul>" );

		$sk = $wgUser->getSkin();
		while ( $s = $dbr->fetchObject( $res ) ) {
			$name = Title::MakeTitle( $s->page_namespace, $s->page_title );
			$link = $sk->makeKnownLinkObj( $name, "" );
			$wgOut->addHTML( "<li>{$link}</li>\n" );
		}
		$wgOut->addHTML( "</ul>\n" );
	}

	/**
	 * Delete the file, or an earlier version of it
	 */
	public function delete() {
		if( !$this->img->exists() || !$this->img->isLocal() ) {
			// Standard article deletion
			Article::delete();
			return;
		}
		$deleter = new FileDeleteForm( $this->img );
		$deleter->execute();
	}

	/**
	 * Revert the file to an earlier version
	 */
	public function revert() {
		$reverter = new FileRevertForm( $this->img );
		$reverter->execute();
	}

	/**
	 * Override handling of action=purge
	 */
	function doPurge() {
		if( $this->img->exists() ) {
			wfDebug( "ImagePage::doPurge purging " . $this->img->getName() . "\n" );
			$update = new HTMLCacheUpdate( $this->mTitle, 'imagelinks' );
			$update->doUpdate();
			$this->img->upgradeRow();
			$this->img->purgeCache();
		} else {
			wfDebug( "ImagePage::doPurge no image\n" );
		}
		parent::doPurge();
	}

	/**
	 * Display an error with a wikitext description
	 */
	function showError( $description ) {
		global $wgOut;
		$wgOut->setPageTitle( wfMsg( "internalerror" ) );
		$wgOut->setRobotpolicy( "noindex,nofollow" );
		$wgOut->setArticleRelated( false );
		$wgOut->enableClientCache( false );
		$wgOut->addWikiText( $description );
	}

}

/**
 * Builds the image revision log shown on image pages
 *
 * @addtogroup Media
 */
class ImageHistoryList {

	protected $img, $skin, $title, $repo;

	public function __construct( $skin, $img ) {
		$this->skin = $skin;
		$this->img = $img;
		$this->title = $img->getTitle();
	}

	public function beginImageHistoryList() {
		global $wgOut, $wgUser, $wgTitle;
		//XXADDED
	        //$s = wfMsg('image_instructions', $wgTitle->getFullText());

		$s .= Xml::element( 'h2', array( 'id' => 'filehistory' ), wfMsg( 'filehist' ) )
			. '<div class="wh_block">'. $wgOut->parse( wfMsgNoTrans( 'filehist-help' ) )
			. Xml::openElement( 'table', array( 'class' => 'filehistory history_table' ) ) . "\n";
		return $s;
	}

	public function endImageHistoryList() {
		return "</table>\n";
	}

	/**
	 * Create one row of file history
	 *
	 * @param bool $iscur is this the current file version?
	 * @param string $timestamp timestamp of file version
	 * @param string $img filename
	 * @param int $user ID of uploading user
	 * @param string $usertext username of uploading user
	 * @param int $size size of file version
	 * @param string $description description of file version
	 * @param string $dims dimensions of file version
	 * @return string a HTML formatted table row
	 */
	public function imageHistoryLine( $iscur, $timestamp, $img, $user, $usertext, $size, $description, $dims, $lineNum ) {
		global $wgUser, $wgLang, $wgContLang;
		$local = $this->img->isLocal();
		if($lineNum %2 == 1)
			$rowClass = "";
		else
			$rowClass = "odd_line";
		$row = '<tr class="' . $rowClass . '">';

		// Reversion link/current indicator
		$row .= '<td>';
		if( $iscur ) {
			$row .= wfMsgHtml( 'filehist-current' );
		} elseif( $local && $wgUser->isLoggedIn() && $this->title->userCan( 'edit' ) ) {
			$q = array();
			$q[] = 'action=revert';
			$q[] = 'oldimage=' . urlencode( $img );
			$q[] = 'wpEditToken=' . urlencode( $wgUser->editToken( $img ) );
			$row .= $this->skin->makeKnownLinkObj(
				$this->title,
				wfMsgHtml( 'filehist-revert' ),
				implode( '&', $q )
			);
		}
		$row .= '</td>';

		// Date/time and image link
		$row .= '<td>';
		$url = $iscur ? $this->img->getUrl() : $this->img->getArchiveUrl( $img );
		$row .= Xml::element(
			'a',
			array( 'href' => $url ),
			$wgLang->timeAndDate( $timestamp, true )
		);
		$row .= '</td>';

		// Uploading user
		$row .= '<td>';
		if( $local ) {
			$row .= $this->skin->userLink( $user, $usertext ) . $this->skin->userToolLinks( $user, $usertext );
		} else {
			$row .= htmlspecialchars( $usertext );
		}
		$row .= '</td></tr>';
		$row .= '<tr class="' . $rowClass . '">';

		// Deletion link
		if( $local && $wgUser->isAllowed( 'delete' ) ) {
			$row .= '<td>';
			$q = array();
			$q[] = 'action=delete';
			if( !$iscur )
				$q[] = 'oldimage=' . urlencode( $img );
			$row .= $this->skin->makeKnownLinkObj(
				$this->title,
				wfMsgHtml( $iscur ? 'filehist-deleteall' : 'filehist-deleteone' ),
				implode( '&', $q )
			);
			$row .= '</td>';
		}
		else{
			$row .= '<td></td>';
		}

		// Image dimensions
		$row .= '<td>' . htmlspecialchars( $dims ) . 'px</td>';

		// File size
		$row .= '<td class="mw-imagepage-filesize">' . $this->skin->formatSize( $size ) . '</td>';

		$row .= '</tr><tr class="' . $rowClass . '">';
		// Comment
		$row .= '<td></td><td colspan="2">' . $this->skin->formatComment( $description, $this->title ) . '</td>';

		$row .= '</tr>';

		return "{$row}\n";
	}

}
