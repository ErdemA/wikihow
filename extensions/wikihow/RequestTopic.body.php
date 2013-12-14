<?php

class RequestTopic extends SpecialPage {

	function __construct() {
		global $wgHooks;
		SpecialPage::SpecialPage( 'RequestTopic' );
	}

	function uncategorizeRequest($article, $user, $reason) {
		global $wgContLang, $wgOut;

		// is the article brandnew
		$t = $article->getTitle();
		if ($t->getNamespace() == NS_MAIN) {
			$dbr = wfGetDB(DB_SLAVE);
			$r = Title::makeTitle(NS_ARTICLE_REQUEST, $t->getText() );
			if ($r->getArticleID() < 0) {
				$r = Title::makeTitle(NS_ARTICLE_REQUEST, EditPageWrapper::formatTitle($t->getText()));
			}

			if ($r->getArticleID() > 0) {
				$res = $dbr->select('revision',
					array('rev_id'),
					array('rev_page=' . $r->getArticleId()),
					"wfUncategorizeRequest",
					array('ORDER BY' => 'rev_id DESC')
				);
				$answered .= "[[" . $wgContLang->getNSText ( NS_CATEGORY ) . ":" . wfMsg('answered-requests') . "]]";
				$origcat = '';
				while ($row = $dbr->fetchObject($res)) {
					$rev = Revision::newFromId($row->rev_id);
					$text = $rev->getText();
					// does it match answered?
					if (strpos($text, $answered) === false) {
						preg_match('/\[\[' . $wgContLang->getNSText ( NS_CATEGORY ) . '[^\]]*\]\]/', $text, $matches);
						if (sizeof($matches[0]) > 0) {
							$origcat = $matches[0];
						}
						break;
					}
				}

				if ($origcat != null) {
					$revision = Revision::newFromTitle($r);
					$text = $revision->getText();
					if (strpos($text, wfMsg('answered-requests')) !== false) {
						$ra = new Article($r);
						$text = ereg_replace("[\[]+" . $wgContLang->getNSText ( NS_CATEGORY ) . "\:([- ]*[.]?[a-zA-Z0-9_/-?&%])*[]]+", "", $text);
						$text .= "\n$origcat";
						$ra->updateArticle($text, wfMsg('request-no-longer-answered'), true, false);
						$wgOut->redirect('');
					}
				}
				$dbr->freeResult($res);
			}
		}
		return true;
	}

	function notifyRequests($article, $user, $text, $summary, $p5, $p6, $p7) {
		global $wgContLang;
		require_once('Request.php');

		notifyRequester($article, $user, $user, $text, $summary);

		// is the article brandnew
		$t = $article->getTitle();
		if ($t->getNamespace() == NS_MAIN) {
			$dbr = wfGetDB(DB_SLAVE);
			$num_revisions = $dbr->selectField('revision', 'count(*)', array('rev_page=' . $article->getId()));
			if ($num_revisions == 1) {
				// new article
				$r = Title::makeTitle(NS_ARTICLE_REQUEST, $t->getText() );
				if ($r->getArticleID() < 0) {
					$r = Title::makeTitle(NS_ARTICLE_REQUEST, EditPageWrapper::formatTitle($t->getText()));
				}

				if ($r->getArticleID() > 0) {
					$revision = Revision::newFromTitle($r);
					$text = $revision->getText();
					if (strpos($text, wfMsg('answered-requests')) === false) {
						$ra = new Article($r);
						$text = ereg_replace("[\[]+Category\:([- ]*[.]?[a-zA-Z0-9_/-?&%])*[]]+", "", $text);
						$text .= "\n[[" . $wgContLang->getNSText ( NS_CATEGORY ) . ":" . wfMsg('answered-requests') . "]]";
						$ra->updateArticle($text, wfMsg('request-now-answered'), true, false);
					}
				}
			}
		}
		return true;
	}

	function getCategoryOptions($default = "") {
		global $wgUser;

		// only do this for logged in users
		$t = Title::newFromDBKey("WikiHow:" . wfMsg('requestcategories') );
		$r = Revision::newFromTitle($t);
		if (!$r) return '';
		$cat_array = split("\n", $r->getText());
		$s = "";
		foreach($cat_array as $line) {
			$line = trim($line);
			if ($line == "" || strpos($line, "[[") === 0) continue;
			$tokens = split(":", $line);
			$val = "";
			$val = trim($tokens[sizeof($tokens) - 1]);
			$s .= "<OPTION VALUE=\"" . $val . "\">" . $line . "</OPTION>\n";
		}
		$s = str_replace("\"$default\"", "\"$default\" SELECTED", $s);

		return $s;
	}

	function getForm ($hidden = false) {
		global $wgOut, $wgUser, $wgScriptPath, $wgLang;

		$topic =  $details =  $override = $name = $email = $category = "";

		if (isset($_POST['topic'])) {
			$topic = htmlspecialchars($_POST['topic']);
			$override = "<input type=hidden name=override value='yes'>";
		}
		if (isset($_POST['details'])) $details = htmlspecialchars($_POST['details']);
		if (isset($_POST['email'])) $email = htmlspecialchars($_POST['email']);
		if (isset($_POST['name'])) $name = htmlspecialchars($_POST['name']);
		if (isset($_POST['category'])) $category = $_POST['category'];

		$me = Title::newFromText("RequestTopic", NS_SPECIAL);
		$action = $me->getFullURL();

		$onsubmit = '';
		$dropdown = '';
		$categories = $this->getCategoryOptions();
		if ($categories != '') {
			$onsubmit = "return checkForm();";
			$dropdown = "<SELECT name=\"category\">
						<OPTION VALUE=\"\">" . wfMsg('categorizerequest') . ":</OPTION>
					{$categories}
						  </SELECT>";
		}

		// add the form HTML
		$wgOut->addHTML ( "
			<script type='text/javascript'>
				function checkForm() {
					if (document.requesttopic.category.value == '') {
						alert (\"" . htmlspecialchars(wfMsg('request_choose_category')) . "\");
						return false;
					}
					return true;
				}
			</script>

			<form id=\"requesttopic\" name=\"requesttopic\" method=\"post\" action=\"{$action}\" onsubmit='{$onsubmit}'>{$override}");

		if ($hidden) {
			$mainPageObj = Title::newMainPage();
			$wgOut->addHTML("<input type=hidden name=topic value=\"$topic\">
					<input type=hidden name=details value=\"$details\">
					<input type=hidden name=name value=\"$name\">
					<input type=hidden name=email value=\"$email\">
					<input type=hidden name=category value=\"$category\">
					<input tabindex='11' type='button' name=\"nosubmit\"
						value=\"".wfMsg('dont-submit')."\" class=\"btn\" onmouseover=\"this.className='btn btnhov'\" onmouseout=\"this.className='btn'\"
						onclick='window.location.href=\"" . $mainPageObj->escapeLocalURL() . "\"'/>
					".wfMsg('page-covers-request')." <br/><br/>
					<input tabindex='11' type='submit' name=\"submit\"
						value='".wfMsg('submit-anyway')."' class=\"btn\" onmouseover=\"this.className='btn btnhov'\" onmouseout=\"this.className='btn'\" >
					".wfMsg('request-unique-topic')."
					</form>
				");
			return;
		}
		$wgOut->addHTML("
				<table border=\"0\">
				<tr>
					<td><b>\"".wfMsg('howto','')." <input type=text size=\"40\" name=\"topic\" value=\"$topic\" >\"</font></td>
				</tr>
				<tr>
					  <td>
					{$dropdown}
				</td>
				</tr>
				<tr>
					<td colspan=\"4\"><br/><b>".wfMsg('optionalinformation').":</b></td>
				</tr>");

		// do this if the user isn't logged in
		$login = Title::makeTitle(NS_SPECIAL, "Userlogin");

		if ($wgUser->getID() <= 0) {
			$wgOut->addHTML ("
					<tr>
						<td colspan=\"4\" bgcolor=\"#cccccc\"  >
							<TABLE bgcolor=white width='100%'>
								<TR>
									<TD>
										<input type=checkbox name=login value=false checked=true>
										<FONT face=\"Arial, Helvetica, sans-serif\" size=2>
											".wfMsg('emailuponarticlewritten')."<br/>


										</FONT>
									</TD>
								</TR>
								<TR>
									<TD valign=top WIDTH='50%'>
										<TABLE cellpadding=2>
											<TR>
												<TD><FONT face=\"Arial, Helvetica, sans-serif\" size=2>".wfMsg('name').":</FONT></TD>
												<TD><input id=input type=\"text\" name=\"name\" value=\"$name\"></TD>
											</TR>
											<TR>
												<TD colspan=2><FONT face=\"Arial, Helvetica, sans-serif\" size=-2 color='#666666'>
													".wfMsg('optional-blank-anonymous')."
													</FONT>
												</TD>
											<TR>
												<TD><FONT face=\"Arial, Helvetica, sans-serif\" size=2>".wfMsg('email').":</font></TD>
												<TD><input id=input type=\"text\" name=\"email\" value=\"$email\"></TD>
											</TR>
											<TR>
												<TD colspan=2><FONT face=\"Arial, Helvetica, sans-serif\" size=-2 color='#666666'>
													".wfMsg('optional-email-notify')."
												</FONT>
												</TD>
										</TABLE>
					 </TD>
								</TR>
								<TR>
								<TD>
											 ".wfMsg('or-login-here',$login->getFullURL() ."?returnto=" . $wgLang->getNsText(NS_SPECIAL).":RequestTopic")."
											</TD>
								</TR>
							</TABLE>
						</td>
						</tr>");
		}

		$wgOut->addHTML ("<tr>
					<td colspan=\"4\"><br/>".wfMsg('additionaltopicdetails').":</td>
				</tr><tr>
					<td colspan=\"4\"><TEXTAREA rows=\"5\" cols=\"55\" name=\"details\">$details</TEXTAREA></td>
				</tr><tr>
					<TD colspan=\"4\"><INPUT type=\"submit\" value=\"".wfMsg('submit')."\"></td>
				</tr>
									<TR>
						<TD colspan\"2\"><br/><br/>
							".wfMsg('clickhere-requestedtopics',"$wgScriptPath/".$wgLang->getNsText(NS_SPECIAL).":ListRequestedTopics")."
						</TD>
						</TR>
				</table>
			</form>");
	}

	function execute($par) {
		global $wgUser, $wgOut, $wgLang, $wgTitle, $wgMemc, $wgDBname;
		global $wgRequest, $wgSitename, $wgLanguageCode, $IP;
		global $wgScript, $wgParser, $wgFilterCallback, $wgScriptPath;

		$fname = "wfSpecialRequestTopic";
		$action = "";

		if ($wgUser->isBlocked()) {
			$wgOut->blockedPage();
			return;
		}

		if (!$wgRequest->wasPosted()) {
			$wgOut->addHTML ("<b>" . wfMsg('lookingforhow') . "</b> <br/> " .wfMsg('requestarticleexdog')."<br/><br/> ");
			$this->getForm();
		} else {
			// this is a post, accept the POST data and create the
			// Request article
			$topic = $wgRequest->getVal('topic');
			$details = $wgRequest->getVal('details');

			if ($wgUser->getID() == 0 && preg_match("@http://@i", $details)) {
				$wgOut->addHTML("Error: anonymous users are not allowed to include links in requests.");
				return;
			}

			if (!isset($_POST['override']) && $wgLanguageCode == 'en') {
				$l = new LSearch();
				$titles = $l->googleSearchResultTitles($topic, 0,5);
				if (sizeof($titles) > 0) {
					$wgOut->addHTML(wfMsg('already-related-topics')."<br>
					<ul id=Things_You27ll_Need>");
					$count = 0;
					foreach ($titles as $t) {
						if ($count == 10) break;
						if ($t == null) continue;
						$wgOut->addHTML("<li style='margin-bottom: 0px'><a href=" . $t->getFullURL() . ">How to " . $t->getText() . "</a></li>");
						$count++;
					}
					$wgOut->addHTML("</ul>");
					$wgOut->addHTML(wfMsg('no-submit-existing-topic'));
					$this->getForm(true);
					return;
				}
			}

			// cut off extra ?'s or whatever
			if ($wgLanguageCode == 'en') {
				while (!ereg('[a-zA-Z0-9)\"]$', $topic)) {
					$topic = substr($topic, 0, strlen($topic) - 1);
				}
			}
			if ($wgLanguageCode == 'en') {
				require_once('EditPageWrapper.php');
				$topic = EditPageWrapper::formatTitle($topic);
			}
			$title = Title::newFromText($topic, NS_ARTICLE_REQUEST);

			$category = $wgRequest->getVal("category", "");
			if ($category == "") {
				$category = "Other";
			}

			$details .= "\n[[Category:$category Requests]]";

			// check if we can do this

			if ( $wgUser->isBlocked() ) {
				$wgOut->addWikiText(wfMsg('blocked-ip'));
				return;
			}
			if ( $wgUser->pingLimiter() ) {
				$wgOut->rateLimited();
				return;
			}

			if ($wgFilterCallback
				&& $wgFilterCallback( $title, $details, $tmp) )
			{
				// Error messages or other handling should be performed by
				// the filter function
				return;
			}

			// create a user
			$user = null;
			if ($wgUser->getID() == 0) {
				if ($wgRequest->getVal('email', null) ) {
					$user = WikihowUser::createTemporaryUser($wgRequest->getVal('name'), $wgRequest->getVal('email'));
					$wgUser = $user;
				}
			}

			if ($title->getArticleID() <= 0) {
				// not yet created. good.
				$article = new Article($title);
				$ret = $article->insertNewArticle($details, "", false, false, false, $user);
				wfRunHooks('ArticleSaveComplete', array(&$article, &$user, $details, "", false, false, NULL));

				//clear the redirect that is set by insertNewArticle
				$wgOut->redirect('');

				$options = ParserOptions::newFromUser( $wgUser );
				$wgParser->parse($details, $title, $options);

			} else {
				// TODO: what to do here? give error / warning? append details?
				// this question has already been asked, if you want to ask
				// a slightly different question, go here:
			}

			$wgOut->addWikiText(wfMsg('thank-you-requesting-topic'));
			$wgOut->returnToMain( false );
		}
	}

}

class ListRequestedTopics extends SpecialPage {

	function __construct() {
		SpecialPage::SpecialPage( 'ListRequestedTopics' );
	}

	function execute () {
		global $wgUser, $wgOut, $wgLang, $wgTitle, $wgMemc, $wgDBname;
		global $wgRequest, $wgSitename, $wgLanguageCode;
		global $wgScript, $wgScriptPath, $wgLang;

		$fname = "wfSpecialListRequestedTopics";

		$offset = $wgRequest->getText("offset", "0");
		$numPerPage = 50;
		$sql = "SELECT count(*) as A from page where page_namespace = 16 and page_is_redirect = 0;";
		$dbr =& wfGetDB( DB_SLAVE );

		$total = $dbr->selectField( 'page',
			'count(*)',
			array ('page_namespace=' . NS_USER_KUDOS,
					'page_is_redirect=0'
				)
			);

		$wgOut->addHTML(wfMsg('list_requested_topics') . "<br/><br/>");
		$wgOut->addHTML(wfMsg('request_topic_here') . "<br/><br/>");
		$wgOut->addWikiText(wfMsg('Browse_requests_category'));
		$wgOut->addHTML("<table style=\"padding-left: 20px; margin-top:30px;\" width=\"100%\" cellpadding=\"0\">");

		$res = $dbr->query ("SELECT p1.page_title as page_title, p1.page_touched as page_touched, p2.page_title as article_title FROM
				page p1 LEFT OUTER JOIN page p2 on p1.page_title=p2.page_title and p2.page_namespace = " . NS_MAIN .
				" WHERE p1.page_namespace = " . NS_ARTICLE_REQUEST .
				" AND p1.page_is_redirect = 0 ORDER BY page_touched desc LIMIT $offset, $numPerPage ");

		if ( 0 ==$dbr->numRows($res ) ) {
			$wgOut->addHTML("<tr><td colspan=\"4\">" . wfMsg('Requests_no_topics') . "<br/></td></tr>");
		}

		$parity = 0;
		$count = 0;
		$datestr = "";
		while ( $row = $dbr->fetchObject( $res ) ) {

			$year = substr($row->page_touched, 0, 4);
			$month = $wgLang->getMonthName(substr($row->page_touched, 4, 2));

			$str = "$month $year";

			if ($count == 0) {
				$wgOut->addHTML("<tr><td style=\"padding-left: 0px\"><b>$str</b></td>
						<TD></td>
					</tr>
					<tr><td colspan=\"3\">&nbsp;</td></tr>
					</tr>");
				$datestr = $str;
			}

			if ($datestr != $str) {
				$wgOut->addHTML("<tr><td colspan=\"3\">&nbsp;</td></tr>
						<tr><td style=\"padding-left: 0px\"><b>$str</b></td><td><font size=-2></td>
						<TD></td>
					</tr>
					<tr><td colspan=\"3\">&nbsp;</td></tr>
					</tr>");
				$datestr = $str;
			}

			$bgcolor = "#eeeeee";
			if ($count % 2 == 0) {
				$bgcolor = "#ffffff";
			}

			$request = Title::makeTitle( NS_ARTICLE_REQUEST, $row->page_title );
			$title = null;
			if ($row->article_title) {
				$title = Title::makeTitle( NS_MAIN, $row->page_title );
			}

			if (!$request) {
				continue;
			}

			$found = false;
			$sk = $wgUser->getSkin();
			if ($title) {
				// article is answered
				$found = true;
				$wgOut->addHTML( "<tr ><td bgcolor=\"$bgcolor\" width=\"60%\">");
				$wgOut->addHTML($sk->makeLinkObj($title, $title->getText()));
				$wgOut->addHTML("<sup><font color='#339900'>Answered!</font></sup>");
				$wgOut->addHTML("<TD bgcolor=\"$bgcolor\">" . $sk->makeLinkObj($title, wfMsg('requests_view_article')));
			} else {
				// article is NOT answered
				$wgOut->addHTML( "<tr ><td bgcolor=\"$bgcolor\" width=\"60%\">");
				$wgOut->addHTML($sk->makeBrokenLinkObj($request, $request->getText()));

				$wgOut->addHTML("<TD width=25% bgcolor=\"$bgcolor\"><a id='gatCreateArticle' href=\"$wgScript?title=" . $request->getDBKey() . "&action=edit&requested=" . $request->getDBKey() . "\">" . wfMsg('write_article') . "</a>");
			}

			if ($wgUser->isSysop()) {
				$wgOut->addHTML (" <br/>- " . $sk->makeLinkObj($request,wfMsg('delete'),"action=delete" ));
				if (!$found) {
					$wgOut->addHTML(" - " . $sk->makeLinkObj(SpecialPage::getTitleFor( 'Movepage' ),
						wfMsg( 'edit_title' ), 'target=' . $request->getPrefixedURL() ));
				}
			}
			$wgOut->addHTML(" </td> </tr> \n");

			$count++;
		}

		$dbr->freeResult($res);

		// next links
		$me = Title::makeTitle(NS_SPECIAL, "ListRequestedTopics");
		$wgOut->addHTML("<tr><td><br/><br/>");
		if ($offset > 0) {
			$wgOut->addHTML("(" . $sk->makeLinkObj($me, wfMsg('prevn', $numPerPage), "offset=". ($offset-$numPerPage) ) . ")");
		}
		if ($offset + $numPerPage < $total) {
			$offset += $numPerPage;
			$wgOut->addHTML(" (" . $sk->makeLinkObj($me, wfMsg('nextn', $numPerPage), "offset=$offset") . ")");
		}
		$wgOut->addHTML("</td><tr/></table><br/><br/>");
		$wgOut->returnToMain( false );
	}

}

