<?php 
class WHVid {
   
	//const S3_DOMAIN = 'http://wikivideo.s3.amazonaws.com/';
	const S3_DOMAIN_DEV = 'http://d2mnwthlgvr25v.cloudfront.net/'; //wikivideo-prod-test
	const S3_DOMAIN_PROD = 'http://d5kh2btv85w9n.cloudfront.net/'; //wikivideo-prod
	const NUM_DIR_LEVELS = 2;

	public static function setParserFunction () { 
		# Setup parser hook
		global $wgParser;
		$wgParser->setFunctionHook( 'whvid', 'WHVid::parserFunction' );
		return true;    
	}

    public static function languageGetMagic( &$magicWords ) {
		$magicWords['whvid'] = array( 0, 'whvid' );
        return true;
    }

    public static function parserFunction($parser, $vid=null, $img=null) {
		global $wgTitle, $wgContLang;
		wfLoadExtensionMessages('WHVid');

        if ($vid === null || $img === null) {
			return '<div class="errorbox">'.wfMsg('missing-params').'</div>';
		}

        $vid = htmlspecialchars($vid);
		$divId = "whvid-" . md5($vid . mt_rand(1,1000));
		$vidUrl = self::getVidUrl($vid);

		$imgTitle = Title::newFromURL($img, NS_IMAGE);
		$imgUrl = null;
		if ($imgTitle) {
			$imgFile = RepoGroup::singleton()->findFile($imgTitle);
			$smallImgUrl = '';
			$largeImgUrl = '';
			if ($imgFile) {
				$width = 550;
				$height = 309;
				$thumb = $imgFile->getThumbnail($width, $height);
				$largeImgUrl = wfGetPad($thumb->getUrl());

				$width = 240;
				//$height = 135;
				$thumb = $imgFile->getThumbnail($width);
				$smallImgUrl = wfGetPad($thumb->getUrl());
			}
		}

		return $parser->insertStripItem(wfMsgForContent('embed-html', $divId, $vidUrl, $largeImgUrl, $smallImgUrl));
    }

	public static function getVidDirPath($filename) {
		return FileRepo::getHashPathForLevel($filename, self::NUM_DIR_LEVELS);
	}

	public static function getVidFilePath($filename) {
		return self::getVidDirPath($filename) . $filename;
	}

	public static function getVidUrl($filename) {
		if (IS_PROD_EN_SITE || IS_PROD_INTL_SITE) {
			$domain = self::S3_DOMAIN_PROD;
		} else {
			$domain = self::S3_DOMAIN_DEV;
		}
		return $domain . self::getVidFilePath($filename);
	}

}
