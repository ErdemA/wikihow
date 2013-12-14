<?php

class QuickNoteEdit extends UnlistedSpecialPage {
        function __construct() {
                UnlistedSpecialPage::UnlistedSpecialPage( 'QuickNoteEdit' );
        }

	function getQNTemplates() {

		$tb1 = "{{subst:Quicknote_Button1|[[ARTICLE]]}}";
		$tb2 = "{{subst:Quicknote_Button2|[[ARTICLE]]}}";
		$tb3 = "{{subst:Quicknote_Button3|[[ARTICLE]]}}";
	
		$tb1_ary = array();
		$tb2_ary = array();
		$tb3_ary = array();
	
		$tmpl = wfMsg('Quicknote_Templates');
		$tmpls = preg_split('/\n/', $tmpl);
		foreach ($tmpls as $item) {
			if ( preg_match('/^qnButton1=/', $item) ) {
				list($key,$value) = split("=",$item);
				array_push($tb1_ary, $value ) ;
			} else if ( preg_match('/^qnButton2=/', $item) ) {
				list($key,$value) = split("=",$item);
				array_push($tb2_ary, $value ) ;
			} else if ( preg_match('/^qnButton3=/', $item) ) {
				list($key,$value) = split("=",$item);
				array_push($tb3_ary, $value ) ;
			}
		}
	
		if (count($tb1_ary) > 0 ){ $tb1 = $tb1_ary[rand(0,(count($tb1_ary) - 1) )]; }
		if (count($tb2_ary) > 0 ){ $tb2 = $tb2_ary[rand(0,(count($tb2_ary) - 1) )]; }
		if (count($tb3_ary) > 0 ){ $tb3 = $tb3_ary[rand(0,(count($tb3_ary) - 1) )]; }
	
		return array($tb1, $tb2, $tb3);
	}

	function displayQuickNoteButtons($id=''){
		global $wgLanguageCode;

		// INTL: Only give these buttons to english site
		if ($wgLanguageCode != 'en') {
			return "";
		}

		list($tb1, $tb2, $tb3) = QuickNoteEdit::getQNTemplates();

		$start1 = strpos($tb1, "{{subst:") + strlen("{{subst:");
		$end1 = strpos($tb1, "|") - strlen("{{subst:");
		$tp1 = substr($tb1, $start1, $end1);
		$template = Title::makeTitle(NS_TEMPLATE, $tp1);

		$r = Revision::newFromTitle($template);
		$tb1_message = $r->getText();
		$tb1_message = preg_replace('/<noinclude>(.*?)<\/noinclude>/is', '', $tb1_message);
		$tb1_message = str_replace("\n", "\\n", $tb1_message);
		$tb1_message = str_replace("'", "\'", $tb1_message);
		$start3 = strpos($tb3, "{{subst:") + strlen("{{subst:");
		$end3 = strpos($tb3, "|") - strlen("{{subst:");
		$tp3 = substr($tb3, $start3, $end3);
		$template = Title::makeTitle(NS_TEMPLATE, $tp3);
		$r = Revision::newFromTitle($template);
		$tb3_message = $r->getText();
		$tb3_message = preg_replace('/<noinclude>(.*?)<\/noinclude>/is', '', $tb3_message);
		$tb3_message = str_replace("\n", "\\n", $tb3_message);
		$tb3_message = str_replace("'", "\'", $tb3_message);

		$buttons = "<div><br /><input tabindex='1' class='button secondary' type='button' value='" . wfMsg('Quicknote_Button1') . "' onclick=\"checkThumbsUp();qnButtons('postcommentForm_" . $id . "', '" . $tb1_message . "')\" />
		 <input tabindex='3' class='button secondary' type='button' value='" . wfMsg('Quicknote_Button3') . "' onclick=\"qnButtons('postcommentForm_" . $id . "', '" . $tb3_message . "')\" /></div>";

		return $buttons;
	}

	function displayQuickNote($forQG = false) {
		global $wgServer, $wgTitle;

		$id = rand(0, 10000);
		$newpage = $wgTitle->getArticleId() == 0 ? "true" : "false";

		$quickNoteButtons = self::displayQuickNoteButtons($id);

		$display = self::getJSMsgs();
		if (IS_PROD_EN_SITE) {
			$display .= "
<script type='text/javascript' src='" . wfGetPad('/extensions/min/f/extensions/wikihow/quicknote.js,/extensions/Postcomment/postcomment.js&rev=') . WH_SITEREV . "'></script> ";
		}
		else {
			$display .= "
<script type='text/javascript' src='/extensions/wikihow/quicknote.js?rev=". WH_SITEREV . "' ></script>
<script type='text/javascript' src='/extensions/Postcomment/postcomment.js?rev=" . WH_SITEREV . "'></script> ";
		}

		if ($forQG) {
			$qnMsgBody = wfMsg('Quicknote_MsgBody_forQG');
			$qn_template_buttons = '';
			$qn_thumbsup = '';
		}
		else {
			$qnMsgBody = wfMsg('Quicknote_MsgBody');
			$qn_template_buttons = 	wfMsg('Quicknote_Instructions1').
									"<span id='qnote_buttons'>" . $quickNoteButtons . "</span><br />";
			$qn_thumbsup = "<div id='qn_thumbsup' style='margin-top:10px;'><input type='checkbox' name='qn_thumbs_check'/> Give thumbs up too</div>";
		}

		$display .= "
	<div id='quicknotecontent'>

<script type='text/javascript'>
 var gPreviewText = '<br/>Generating Preview...';
 var gPreviewURL = '" . $wgServer . "/Special:PostcommentPreview';
 var gPostURL = '" . $wgServer . "/Special:Postcomment';
 var gPreviewMsg = 'Preview Message:';
 var gNewpage = " . $newpage . ";
 var qnMsgBody = '" . $qnMsgBody . "';
 if (screen.height < 701) {
	 document.getElementById('modalContainer').style.top = '1%';
 }
</script>

	 <div id='qnEditorInfo'></div>

	 <form id='postcommentForm_" . $id . "' name='postcommentForm_" . $id . "' method='POST' action='" . $wgServer . "/Special:Postcomment' target='_blank' 
		 onsubmit=\"return qnSend('postcomment_newmsg_" . $id . "', this);\">
		 <input id='qnTarget' name='target' type='hidden' value=''/>

		 <br />" .wfMsg('Quicknote_Instructions2') ."<br />

		 <textarea tabindex='4' id='comment_text' name='comment_text' cols=40 rows=8 onkeyup='qnCountchars(this);'></textarea>
		 <div id='qnCharcount' ></div>
		 ".$qn_template_buttons."
		 ".$qn_thumbsup."
		 <br />
		 <input tabindex='5' type='submit' value='". wfMsg('qn_post_button') . "' class='button buttonright primary' id='postcommentbutton_" . $id . "' />
		 <a href='#' tabindex='6' class='button buttonright secondary' onclick='$(\"#dialog-box\").dialog(\"close\");'>" . wfMsg('qn_cancel_link') . "</a><br class='clearall' />
	 </form>
</div> \n";
		return $display;
	}

	function getQuickNoteLinkMultiple ($title, $users) {
		wfLoadExtensionMessages('QuickNoteEdit');
		$stats = array();
		$regdates = array();	
		$contribs = array();
		$names = array();
		foreach ($users as $u) {
			if (!$u) continue;
			$u->load();
        	$regdate = $u->getRegistration();
		 	if ($regdate) {
				$ts = wfTimestamp(TS_UNIX, $regdate);
				$regdates[] = date('M j, Y', $ts);
			} else if ($u->getID() == 0) {
				$regdates[] = "n/a";
			} else {
				$regdates[] = "or before 2006";
			}
        	$contribs[] = number_format($u->getEditCount(), 0, "", ",");
			$names[] 	= $u->getName();
		}
#print_r($users);
       	$link = "<a href='#' id='qn_button' onclick=\"return initQuickNote('".urlencode($title->getPrefixedText())
				."','".implode($names, "|") 
				."', '".implode($contribs, "|") 
				."', '".implode($regdates, "|") ."');\">" . wfMsg('quicknote_button') . "</a>";
		#echo $link; exit;
		return $link;	
	}

    function getQuickNoteLink ($title, $userId, $userText, $editor  = null) {
    	if (!$editor) {
			$editor = User::newFromId( $userId );
            $editor->loadFromId();
        }
        $regdate = $editor->getRegistration();
        if ($regdate != "") {
       		$ts = wfTimestamp(TS_UNIX, $regdate);
            $regdate = date('M j, Y', $ts);
        }
        $contrib = number_format(WikihowUser::getAuthorStats($userText), 0, "", ",");
        return "<a href='' id='qn_button' onclick=\"return initQuickNote('".urlencode($title->getPrefixedText())."','".$userText."','".$contrib."','".$regdate."') ;\">quick note</a>";
    }
	
    function getQuickNoteDiffButton ($t, $u, $diffid, $oldid) {
		wfLoadExtensionMessages('QuickNoteEdit');
		
		if (!$u) continue;
		$u->load();
		$regdate = $u->getRegistration();
		if ($regdate) {
			$ts = wfTimestamp(TS_UNIX, $regdate);
			$regdate = date('M j, Y', $ts);
		} else if ($u->getID() == 0) {
			$regdate = "n/a";
		} else {
			$regdate = "or before 2006";
		}
		$contrib = number_format($u->getEditCount(), 0, "", ",");
		$name = $u->getName();
		
		$article = $t->getPrefixedText();
		
		//build up the diff link
		$difflink = '/index.php?article='.$article.'&diff='.$diffid.'&oldid='.$oldid;
		$difflink = wfExpandUrl($difflink);
		$difflink = urlencode($difflink);

       	$link = "<a href='' class='button secondary' id='qn_button' ".
				"onclick=\"return initQuickNote(".
				"'".$article."','".$name ."', '".$contrib."', '".$regdate."','".$difflink."') ;\">". 
				wfMsg('quicknote_button') . "</a>";
		
		return $link;	
    }

	
	function displayQuickEdit() {
		global $wgTitle;

		wfLoadExtensionMessages('QuickNoteEdit');
		$display = "
<style type='text/css' media='all'>/*<![CDATA[*/ @import '" . wfGetPad('/extensions/min/f/extensions/wikihow/popupEdit.css,skins/WikiHow/articledialog.css&rev=') . WH_SITEREV . "'; /*]]>*/</style>
<script type='text/javascript' src='" . wfGetPad('/extensions/min/f/extensions/wikihow/popupEdit.js?') . WH_SITEREV . "'></script> 
<script type='text/javascript'>
 var gAutoSummaryText = '" .  wfMsg('Quickedit-summary') . "'
 var gQuickEditComplete = '" .  wfMsg('Quickedit-complete') . "'
</script>
<div id='editModalPage'>
 <div class='editModalBackground' id='editModalBackground'></div>
 <div class='editModalContainer' id='editModalContainer'>
	<div class='modalContent'>
	 <div class='editModalTitle'>
		<a onclick=\"document.getElementById('editModalPage').style.display = 'none';\" id='modal_x'>".wfMsg('quicknote_close')."</a>
		<h2>".wfMsg('quickedit_title')."</h2>
	</div>
	 <div class='editModalBody wh_block'>
		 <div id='article_contents'>
		 </div>
	 </div>
	 <br />
	 </div><!--end modalContent-->
 </div>
</div>\n";
		return $display;
	}

	function displayQuickEdit2() {
		global $wgTitle;

		$display = "
<script type='text/javascript'>
 var gAutoSummaryText = '" .  wfMsg('Quickedit-summary') . "'
 var gQuickEditComplete = '" .  wfMsg('Quickedit-complete') . "'
</script>
 <div class='editModalBody'>
	 <div id='article_contents' style='width:580px;height:460px;overflow:auto'>
	 </div>
</div>\n";
		return $display;
	}

	function displayQuickNote2() {
		global $wgServer, $wgTitle;

		$id = rand(0, 10000);
		$newpage = $wgTitle->getArticleId() == 0 ? "true" : "false";
		list($tb1, $tb2, $tb3) = QuickNoteEdit::getQNTemplates();

		$display = self::getJSMsgs();
		$display .= "


<script type='text/javascript'>
 var gPreviewText = '<br/>Generating Preview...';
 var gPreviewURL = '" . $wgServer . "/Special:PostcommentPreview';
 var gPostURL = '" . $wgServer . "/Special:Postcomment';
 var gPreviewMsg = 'Preview Message:';
 var gNewpage = " . $newpage . ";
 var qnMsgBody = '" . wfMsg('Quicknote_MsgBody') . "';
 if (screen.height < 701) {
	 document.getElementById('modalContainer').style.top = '1%';
 }
</script>
	 <div id=qnEditorInfo></div><br />

	 <form id='postcommentForm_" . $id . "' name='postcommentForm_" . $id . "' method='POST' action='" . $wgServer . "/Special:Postcomment' target='_blank' 
		 onsubmit=\"return qnSend('postcomment_newmsg_" . $id . "', document.postcommentForm_" . $id . ");\">
		 <input id='qnTarget' name='target' type='hidden' value=''/>

		 <?echo wfMsg('Quicknote_Instructions1'); ?><br /><br />

		 <input tabindex='1' type='button' value='" . wfMsg('Quicknote_Button1') . "' onclick=\"qnButtons('postcommentForm_" . $id . "', '" . $tb1 . "')\" />
		 <input tabindex='2' type='button' value='" . wfMsg('Quicknote_Button2') . "' onclick=\"qnButtons('postcommentForm_" . $id . "', '" . $tb2 . "')\" />
		 <input tabindex='3' type='button' value='" . wfMsg('Quicknote_Button3') . "' onclick=\"qnButtons('postcommentForm_" . $id . "', '" . $tb3 . "')\" /><br /><br />

		 <?echo wfMsg('Quicknote_Instructions2'); ?><br /><br />

		 <textarea tabindex='4' id='comment_text' name='comment_text' cols=40 rows=8 onkeyup='qnCountchars(this);'></textarea>
		 <div id='qnCharcount' ></div>
		 <br />

		 <input tabindex='5' type='submit' value='" . wfMsg('qn_post_button') . "' cl1ass='btn' id='postcommentbutton_" . $id . "' style='font-size: 110%; font-weight:bold'/>
		 <input tabindex='6' type='button' value='" . wfMsg('qn_cancel_link') . "' onclick='return qnClose();' />
	 </form> \n";
		return $display;
	}

	function display() {
		$display = "
<style type='text/css' media='all'>/*<![CDATA[*/ @import '" . wfGetPad('/extensions/min/f/extensions/wikihow/popupEdit.css,/extensions/wikihow/quicknote.css,/extensions/wikihow/winpop.css&rev=') . WH_SITEREV . "'; /*]]>*/</style>
<script type='text/javascript' src='" . wfGetPad('/extensions/min/f/extensions/wikihow/popupEdit.js,/extensions/wikihow/quicknote.js,/extensions/Postcomment/postcomment.js,/extensions/wikihow/winpop.js&rev=') . WH_SITEREV . "'></script> 
<script type='text/javascript'>
	function initQuickNote(qnArticle, qnUser, contrib, regdate) {
		popModal('/Special:QuickNoteEdit/quicknote', 600, 480);
		initQuickNote2( qnArticle, qnUser, contrib, regdate );

	}
	function initPopupEdit(editURL) {
		popModal('/Special:QuickNoteEdit/quickedit', 600, 180);
		initPopupEdit2(editURL);
	}
</script>\n";
		return $display;

	}

	function execute ($par ) {
		global $wgUser, $wgOut, $wgRequest;
		wfLoadExtensionMessages('QuickNoteEdit');

		$wgOut->setArticleBodyOnly(true);
		if ($par == 'quickedit') {
			$wgOut->addHTML( $this->displayQuickedit2() );
		} else if ($par == 'quicknote') {
			$wgOut->addHTML( $this->displayQuicknote2() );
		} else if( $par == 'quicknotebuttons'){
			$wgOut->addHTML( $this->displayQuickNoteButtons() );
		}
	}

	function getJSMsgs() {
		$langKeys = array('qn_note_for');
		return Wikihow_i18n::genJSMsgs($langKeys);
	}
}
				
