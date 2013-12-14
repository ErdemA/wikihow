<?
if (!defined('MEDIAWIKI')) die();

global $IP;
require_once("$IP/skins/WikiHowSkin.php");

class Slider extends UnlistedSpecialPage {

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'Slider' );
	}
	
	public function getBox() {
		global $wgTitle;
		
		//G+ follow slider
		return self::getBox_07();
	}
	
	//original slider
	public function getBox_01() {
		global $wgOut, $wgLanguageCode, $wgServer, $wgTitle;
		wfLoadExtensionMessages('Slider');

        // Remove background for non-english sites. Unfortunate, but bg image has English in it.
        $slider_thanks_intl = "";
        if ($wgLanguageCode != 'en') {
            $slider_thanks_intl = "class='slider_thanks_intl'";
        }

		$url = urlencode($wgServer . "/" . $wgTitle->getPrefixedURL());
		
		$theBox = "<div id='sliderbox'>
						<div id='slider_thanks' $slider_thanks_intl>
							<a href='#' id='slider_close_button'>x</a>
							<div class='tta_plus1'><g:plusone size='tall'></g:plusone></div>
							<div class='tta_text'>
								<p class='tta_first'>".wfMsg('slider-text')."</p>
								<p class='slider_subtext_plus1'>".wfMsg('slider-sub-text-plusone')."</p>
							</div>
						</div>
					</div>";

		return $theBox;
	}
	
	//wikihow logo slider
	public function getBox_02() {
		wfLoadExtensionMessages('Slider');
		
		$theBox = "<div id='sliderbox'>
						<div id='slider_thanks_02'>
							<a href='#' id='slider_close_button'>x</a>
							<div class='tta_plus1_02'><g:plusone size='tall'></g:plusone></div>
							<div class='tta_text_02'>
								<p class='tta_first'>".wfMsg('slider-text')."</p>
								<p class='slider_subtext_plus1'>".wfMsg('slider-sub-text-plusone')."</p>
							</div>
						</div>
					</div>";

		return $theBox;
	}
	
	//circle slider
	public function getBox_03() {
		wfLoadExtensionMessages('Slider');
		
		$theBox = "<div id='sliderbox' class='sliderbox_03'>
							<div id='slider_thanks_outer_03'>
						<div id='slider_thanks_03'>
								<a href='#' id='slider_close_button'>x</a>
								<div class='tta_text_03'>
									<p class='tta_first_03'>".wfMsg('slider-text')."</p>
									<p class='slider_subtext_plus1'>".wfMsg('slider-sub-text-plusone')."</p>
								</div>
								<div class='tta_plus1_03'><g:plusone size='tall'></g:plusone></div>
							</div>
						</div>
					</div>";

		return $theBox;
	}
	
	/* Note: this box CANNOT be used without adding caching in WikihowShare::getPinterestImage($wgTitle)
	//circle slider [pinterest]
	public function getBox_04() {
		global $wgServer, $wgTitle;
		wfLoadExtensionMessages('Slider');

		$url = urlencode($wgServer . "/" . $wgTitle->getPrefixedURL());
		$img = urlencode(WikihowShare::getPinterestImage($wgTitle));
		$desc = urlencode(wfMsg('Pinterest_text', $wgTitle->getText())); 
		
		$theBox = "<div id='sliderbox' class='sliderbox_03'>
							<div id='slider_thanks_outer_03'>
						<div id='slider_thanks_03'>
								<a href='#' id='slider_close_button'>x</a>
								<div class='tta_text_03'>
									<p class='tta_first_03'>".wfMsg('slider-text')."</p>
									<p class='slider_subtext_pinit'>".wfMsg('slider-sub-text-pinit')."</p>
								</div>
								<a href='http://pinterest.com/pin/create/button/?url=" . $url . "&media=" . $img . "&description=" . $desc . "' class='pin-it-button' count-layout='vertical'>Pin It</a>
							</div>
						</div>
					</div>";

		return $theBox;
	}
	*/
	
	//circle slider
	public function getBox_05() {
		wfLoadExtensionMessages('Slider');
		
		$ar = new ArticleRatingDesktopView();
		$body =  $ar->getHtml();
		$theBox = "<div id='sliderbox' class='sliderbox_rating'>
							<div id='slider_thanks_02'>
									<a href='#' id='slider_close_button'>x</a>
									<div class='tta_text_05'>
										<p class='tta_first_03'>".wfMsg('slider-ra-text')."</p>
										<p class='slider_subtext_plus1'>".wfMsg('slider-ra-subtext')."</p>
									</div>
									$body
							</div>
					</div>";

		return $theBox;
	}
	
	//circle slider that follows our G+ page
	public function getBox_06() {
		wfLoadExtensionMessages('Slider');
		
		$theBox = "<div id='sliderbox' class='sliderbox_06'>
							<div id='slider_thanks_outer_03'>
						<div id='slider_thanks_06'>
								<a href='#' id='slider_close_button'>x</a>
								<div class='tta_text_06'>
									<p class='tta_first_03'>".wfMsg('slider-text')."</p>
									<p class='slider_subtext_plus1'>".wfMsg('slider-sub-text-plusone-site')."</p>
								</div>
								<div class='tta_plus1_03'><div class='g-plus' data-width='100' data-href='https://plus.google.com/102818024478962731382' data-rel='publisher'></div></div>
							</div>
						</div>
					</div>";

		return $theBox;
	}
	
	//circle slider with official G+ Follow Button
	public function getBox_07() {
		wfLoadExtensionMessages('Slider');
		
		$theBox = "<div id='sliderbox' class='sliderbox_06'>
							<div id='slider_thanks_outer_03'>
						<div id='slider_thanks_06'>
								<a href='#' id='slider_close_button'>x</a>
								<div class='tta_text_06'>
									<p class='tta_first_03'>".wfMsg('slider-text')."</p>
									<p class='slider_subtext_plus1'>".wfMsg('slider-sub-text-plusone-site')."</p>
								</div>
								<div class='tta_plus1_03'><div class='g-follow' data-annotation='vertical-bubble' data-height='20' data-href='https://plus.google.com/102818024478962731382' data-rel='publisher'></div></div>
							</div>
						</div>
					</div>";

		return $theBox;
	}
	
	/**
	 * EXECUTE
	 **/
	function execute ($par = '') {	
		global $wgOut, $wgRequest;
		$wgOut->setArticleBodyOnly(true);
		
		//log it to the database
		if ($wgRequest->getVal('action')) {
			$wgOut->addHTML($res);
			return;
		}
	}
	
}
