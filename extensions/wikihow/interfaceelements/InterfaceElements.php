<?php

Class InterfaceElements {
	public function addBubbleTipToElement($element, $cookiePrefix, $text) {
		global $wgOut;

		$wgOut->addHTML(HtmlSnips::makeUrlTags('css', array('tipsbubble.css'), 'extensions/wikihow/interfaceelements', false));
		$wgOut->addScript(HtmlSnips::makeUrlTags('js', array('interfaceelements/tipsbubble.js', 'common/jquery.cookie.js'), 'extensions/wikihow', false));

		$tmpl = new EasyTemplate(dirname(__FILE__));

		$tmpl->set_vars(array('text' => $text));
		InterfaceElements::addJSVars(array('bubble_target_id' => $element, 'cookieName' => $cookiePrefix.'_b'));
		$wgOut->addHTML($tmpl->execute('TipsBubble.tmpl.php'));
	}

	public function addJSVars($data) {
		global $wgOut;
		$text = "";
		foreach($data as $key => $val) {
			$text = $text."var ".$key." = ".json_encode($val).";";
		}
		$wgOut->addInlineScript($text);
	}
}
