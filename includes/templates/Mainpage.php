<!-- Localized -->
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" >
<head>	
	<title>WikiHow</title>
	
	<link rel="stylesheet" href="skins/WikiHow/wh_style_current.css" type="text/css" />
	<script type="text/javascript" src="scripts/expando.js"><!-- expando js --></script>
    <!--[if lte IE 6]>

    <style type="text/css">
    BODY { color: #C00; }
    </style>
    <![endif]-->	

    <script type="text/javascript">
    var rcElements = new Array();
    var rcReset = true;
    var rcCurrent = 0;
    var rcElementCount = 0;
    var rcMaxDisplay = 3;
    var rcServertime;
    var dpointer = 0;
    var togglediv = true;
    var rcInterval = '';
    var rcReloadInterval = '';
    var gcInterval = '';

    var isFull = 0;
    var direction = 'down';
    var rcDefaultURL = '/Special:RCWidget';
    var rcPause = false;
    var rcLoadCounter = 0;
    var rcLoadCounterMax = 4;
    var rcwDebugFlag = false;

    function getNextSpot() {
	    if (dpointer >= rcMaxDisplay) {
		    return 0;
	    } else {
		    return dpointer;
	    }
    }

    function getRCElem(listid, type) {

	    if (typeof(rcElements) != "undefined") {
		    var elem;

		    var newelem = document.createElement('div');
		    var newid = getNextSpot();
		    var newdivid = 'welement'+newid;
		    newelem.setAttribute('id',newdivid);
		    newelem.style.display = "none";
            if (togglediv) {
                newelem.className = 'rounded_corners tan';
                togglediv = false;
		    } else {
			    newelem.className = 'toggleoff';
			    togglediv = true;
		    }

		    elem = "<div style='padding: 4px;'>";

		    elem += rcElements[ rcCurrent ].text + "<br />";
		    //elem += "<span style='color: #AAAAAA;font-size: 11px;'>" + rcElements[ rcCurrent ].ts +" ("+rcCurrent+")</span>";
		    elem += "<span style='color: #AAAAAA;'>" + rcElements[ rcCurrent ].ts + "</span>";
		    elem == "</div>";

		    newelem.innerHTML = elem;

		    dpointer = newid + 1;

		    if (direction == 'down'){
			    listid.insertBefore(newelem, listid.childNodes[0]);
		    } else {
			    listid.appendChild(newelem);
		    }

		    if (type == 'blind') {
			    if (direction == 'down') {
				    new Effect.SlideDown(newelem);
			    } else {
				    new Effect.BlindDown(newelem);
			    }
		    } else {
			    new Effect.Appear(newelem);
		    }

		    if (rcCurrent < rcElementCount) {
			    rcCurrent++;
		    } else {
			    rcCurrent = 0;
		    }

		    return newelem;
	    } else {
		    return "undefined";
	    }
    }

    function rcUpdate() {
	    if (rcPause) {
		    return false;
	    }

	    var listid = $('rcElement_list');
	    //$('teststatus').innerHTML = "Nodes: "+listid.childNodes.length;

	    if (isFull == rcMaxDisplay) {
		    var oldid = getNextSpot();
		    var olddivname = 'welement'+oldid;
		    var olddivid = $(olddivname);
    	
		    if (direction == 'down') {
			    new Effect.BlindUp(olddivid);
		    } else {
			    new Effect.SlideUp(olddivid);
		    }
		    //new Effect.BlindUp(olddivid),
		    //listid.removeChild(olddivid);
		    olddivid.setAttribute('id','rcw_deleteme');
	    }

	    elem = getRCElem(listid, 'blind');
	    if (isFull < rcMaxDisplay) { isFull++ }

    }

    /*var running = true;
    function rcTransport(obj) {
        var rcwScrollCookie = getCookie('rcScroll');

       if (running) {
            deleteCookie('rcScroll');
            rcStart();
            obj.className = "play";
            //obj.style.backgroundPosition = "0 -78px";
	    } else {
            setCookie('rcScroll','stop',1);
            rcStop();
            obj.className = "";
            //obj.style.backgroundPosition = "0 0";
       }
        
    }
    */

    var running = true;
    function rcTransport(obj) {
        var rcwScrollCookie = getCookie('rcScroll');

       if (running) {
            //deleteCookie('rcScroll');
            //rcStart();

            obj.className = "play";
            obj.style.backgroundPosition = "0 -78px";
            running = false;

        } else {
            //setCookie('rcScroll','stop',1);
            //rcStop();
            obj.className = "";
            obj.style.backgroundPosition = "0 0";
            running = true;
       }

    }
       

    function rcStop() {
	    clearInterval(rcInterval);
	    clearInterval(rcReloadInterval);
	    clearInterval(gcInterval);

	    rcInterval = '';
	    rcReloadInterval = '';
	    gcInterval = '';
	    rcGC();
    }

    function rcStart() {
	    rcUpdate();
	    rcLoadCounter = 0;
	    if (rcReloadInterval == '') { rcReloadInterval = setInterval('rcwReload()', rc_ReloadInterval); }
	    if (rcInterval == '') { rcInterval = setInterval('rcUpdate()', 3000); }
	    if (gcInterval == '') { gcInterval = setInterval('rcGC()', 30000); }
    }

    function rcwReadElements(nelem) {
	    var Current = 0;
	    var Elements = new Array();
	    var Servertime = 0;
	    var ElementCount = 0;

	    for (var i in nelem) {
		    if (typeof(i) != "undefined") {
			    if (i == 'servertime'){
				    Servertime = nelem[i];
			    } else {
				    Elements.push(nelem[i]);
				    ElementCount++;
			    }
		    }
	    }
    //	if ((Current < 20) && (ElementCount > 20)) { Current = 20; };
	    Current = 0;

	    rcServertime = Servertime;
	    rcElements = Elements;
	    rcElementCount = ElementCount;
	    rcCurrent = Current;
	    rcReset = true;
    }

    function rcwReload() {
	    if (rc_URL == '') { rc_URL = rcDefaultURL; }
	    rcLoadCounter++;

	    if (rcLoadCounter > rcLoadCounterMax) {
		    rcStop();
		    $('teststatus').innerHTML = "Reload Counter...Stopped:"+rcLoadCounter;
		    return true;
	    } else {
		    $('teststatus').innerHTML = "Reload Counter..."+rcLoadCounter;
	    }

	    new Ajax.Request(rc_URL, {
	    method: 'get',
	    onSuccess: function(transport) {

		    var json = transport.responseText.evalJSON(true);
		    rcwReadElements(json);

	    }

	    });
    }

    function rcwLoad() {
	    if (rc_URL == '') {
		    rc_URL = rcDefaultURL;
	    }
	    var listid = $('rcElement_list');
	    listid.style.height = (rcMaxDisplay * 65) + 'px';
	    if (rcwDebugFlag) { $('rcwDebug').style.display = 'block'; }

	    if (document.getElementById("rcElement_list")) {
		    Event.observe('rcElement_list', 'mouseover', function(e) {
			    rcPause = true;
		    });
		    Event.observe('rcElement_list', 'mouseout', function(e) {
			    rcPause = false;
		    });
	    }


	    new Ajax.Request(rc_URL, {
	    method: 'get',
	    onSuccess: function(transport) {

		    var json = transport.responseText.evalJSON(true);
		    rcwReadElements(json);

		    $('teststatus').innerHTML = "Nodes..."+listid.childNodes.length;
		    var rcwScrollCookie = getCookie('rcScroll');

		    if (!rcwScrollCookie) {
			    elem = getRCElem(listid, 'new');
			    if (isFull < rcMaxDisplay) { isFull++ }

			    rcStart();
		    } else {
			    for(i=0; i<rcMaxDisplay; i++) {
				    elem = getRCElem(listid, 'new');
				    if (isFull < rcMaxDisplay) { isFull++ }
			    }

			    rcStop();
		    }


	    }

	    });
    }

    function rcGC() {
       var listid = $('rcElement_list');
	    var listcontents = listid.getElementsByTagName('div');
	    var tmpHTML = $('teststatus').innerHTML;
	    $('teststatus').innerHTML = "Garbage collecting...";

	    for (i=0; i < listcontents.length; i++) {
		    if (listcontents[i].getAttribute('id') == 'rcw_deleteme') {
			    listid.removeChild( listcontents[i] );
		    }
	    }
	    $('teststatus').innerHTML = tmpHTML;
    }

    function setCookie(c_name,value,expiredays)
    {
    var exdate=new Date();
    exdate.setDate(exdate.getDate()+expiredays);
    document.cookie=c_name+ "=" +escape(value)+
    ((expiredays==null) ? "" : ";expires="+exdate.toGMTString());
    }

    function getCookie(c_name)
    {
    if (document.cookie.length>0)
      {
      c_start=document.cookie.indexOf(c_name + "=");
      if (c_start!=-1)
        {
        c_start=c_start + c_name.length+1;
        c_end=document.cookie.indexOf(";",c_start);
        if (c_end==-1) c_end=document.cookie.length;
        return unescape(document.cookie.substring(c_start,c_end));
        }
      }
    return "";
    }

    function deleteCookie( name ) {
    if ( getCookie( name ) ) document.cookie = name + "=" +
    ";expires=Thu, 01-Jan-1970 00:00:01 GMT";
    }

    </script>

</head>

<body onload="setup();">
  <div id="header">
    <div id="logo">
      <a href="http://www.wikihow.com/Main-Page"><img src="images/wikihow.png" width=
      "216" height="37" alt="wikiHow" id="wikiHow" name="wikiHow" /></a>

      <p>the how-to manual that you can edit</p>
    </div>

    <div id="bubbles">
      <div id="login">
        <a href="">Sign Up</a> <span>or</span> <a href="">Log In</a>
      </div>

      <div id="bonus_bubble"><img src="images/bonus_bubble_left.png" />

      <p><a href="">Help wikiHow improve by taking a brief survey?</a></p><img src=
      "images/bonus_bubble_right.png" /></div>

      <div id="head_bubble">
        <a href="" id="nav_home" title="Home" onmouseover="button_swap(this);"
        onmouseout="button_unswap(this);" class="on" name="nav_home">Home</a> <a href=""
        id="nav_articles" title="Articles" onmouseover="button_swap(this);" onmouseout=
        "button_unswap(this);" name="nav_articles">Articles</a> <a href="" id=
        "nav_community" title="Community" onmouseover="button_swap(this);" onmouseout=
        "button_unswap(this);" name="nav_community">Community</a> <a href="" id=
        "nav_profile" title="My Profile" onmouseover="button_swap(this);" onmouseout=
        "button_unswap(this);" name="nav_profile">My Profile</a>

        <form id="bubble_search" name="bubble_search">
          <input type="text" class="search_box" /> <input type="submit" value="Search"
          class="search_button" onmouseover="button_swap(this);" onmouseout=
          "button_unswap(this);" />
        </form>
      </div>
    </div>
  </div>

  <div id="main">
    <div id="message_box">
      Messages: <a href="">Someone has sent you fan mail</a> and <a href="">You have a
      talk page message</a>
    </div>

    <div id="actions_shell"><img src="skins/WikiHow/images/actions_top.png" width="978"
    height="14" alt="" />

    <div id="actions">
      <blockquote>
        <p>The world's largest how-to manual.</p>
      </blockquote>

      <div id="learn">
        <img src="skins/WikiHow/images/books.png" id="actionImg_book" alt="" name=
        "actionImg_book" />

        <div>
          <h2 id="header">Learn</h2>

          <h3 id="subHeader">Learn <strong>how to</strong> from one of our</h3>

          <h3 id="subHeader_blue">60,000 How-To Articles</h3>

          <h4><a href="#">Find your topic.</a></h4>

          <div id="actionLink">
            <a href="#">Explore Articles <img src="skins/WikiHow/images/actionArrow.png"
            alt="" /></a>
          </div>
        </div>
      </div>

      <div id="actions_spacer">
        &nbsp;
      </div>

      <div id="write">
        <img src="skins/WikiHow/images/pen.png" id="actionImg_pen" alt="" name=
        "actionImg_pen" />

        <div>
          <h2 id="header">Write</h2>

          <h3 id="subHeader">Share your <strong>how-to</strong> with<br />
          millions of people!</h3>

          <h4><a href="#">Write a how-to</a> <span id="brown">or</span> <a href=
          "#">answer requests.</a></h4>

          <div id="actionLink">
            <a href="#">Start Writing <img src="skins/WikiHow/images/actionArrow.png"
            alt="" /></a>
          </div>
        </div>
      </div>

      <div id="actions_spacer">
        &nbsp;
      </div>

      <div id="collaborate">
        <img src="skins/WikiHow/images/pencils.png" id="actionImg_pencils" alt="" name=
        "actionImg_pencils" />

        <div>
          <h2 id="header">Collaborate</h2>

          <h3 id="subHeader">Learn <strong>how to</strong> from one of our</h3>

          <h3 id="subHeader_blue">60,000 How-To Articles</h3>

          <h4><a href="#">Find your topic.</a></h4>

          <div id="actionLink">
            <a href="#">Join Now <img src="skins/WikiHow/images/actionArrow.png" alt=
            "" /></a>
          </div>
        </div>
      </div>
    </div><img src="skins/WikiHow/images/actions_bottom.png" width="978" height="19" alt=
    "" /></div>

    <div id="article_shell">
      <img src="images/article_top.png" width="679" height="10" alt="" />

      <div id="article">
        <div class="featured_articles_header" id="featuredArticles_header">
          <h1>Featured Articles</h1><a href="#"><img src=
          "skins/WikiHow/images/rssIcon.png" alt="" class="rss" id="rssIcon" name=
          "rssIcon" /> RSS</a>
        </div>

        <div class="featured_articles_inner" id="featuredArticles">
          <table class="featuredArticle_Table">
            <tr>
              <td>
                <div>
                  <a href='#'><img src="skins/WikiHow/tempImages/featureImage0.png" alt=
                  "" width="103" height="80" class="rounded_corners" /></a><br />
                  <a href='#'>Make Jello Cake</a>
                </div>
              </td>

              <td>
                <div>
                  <a href='#'><img src="skins/WikiHow/tempImages/featureImage1.png" alt=
                  "" width="103" height="80" class="rounded_corners" /></a><br />
                  <a href='#'>Make Artist Trading Cards</a>
                </div>
              </td>

              <td>
                <div>
                  <a href='#'><img src="skins/WikiHow/tempImages/featureImage2.png" alt=
                  "" width="103" height="80" class="rounded_corners" /></a><br />
                  <a href='#'>Make a Bikini Cover Up from a Towel</a>
                </div>
              </td>

              <td>
                <div>
                  <a href='#'><img src="skins/WikiHow/tempImages/featureImage3.png" alt=
                  "" width="103" height="80" class="rounded_corners" /></a><br />
                  <a href='#'>How to Improve Your Hiking Technique</a>
                </div>

                <div class="c1"></div>
              </td>

              <td>
                <div>
                  <a href='#'><img src="skins/WikiHow/tempImages/featureImage4.png" alt=
                  "" width="103" height="80" class="rounded_corners" /></a><br />
                  <a href='#'>Make Artist Trading Cards</a>
                </div>
              </td>
            </tr>

            <tr>
              <td>
                <div>
                  <a href='#'><img src="skins/WikiHow/tempImages/featureImage3.png" alt=
                  "" width="103" height="80" class="rounded_corners" /></a><br />
                  <a href='#'>How to Improve Your Hiking Technique</a>
                </div>
              </td>

              <td>
                <div>
                  <a href='#'><img src="skins/WikiHow/tempImages/featureImage4.png" alt=
                  "" width="103" height="80" class="rounded_corners" /></a><br />
                  <a href='#'>Make Artist Trading Cards</a>
                </div>
              </td>

              <td>
                <div>
                  <a href='#'><img src="skins/WikiHow/tempImages/featureImage0.png" alt=
                  "" width="103" height="80" class="rounded_corners" /></a><br />
                  <a href='#'>Make Jello Cake</a>
                </div>
              </td>

              <td>
                <div>
                  <a href='#'><img src="skins/WikiHow/tempImages/featureImage1.png" alt=
                  "" width="103" height="80" class="rounded_corners" /></a><br />
                  <a href='#'>Make Artist Trading Cards</a>
                </div>
              </td>

              <td>
                <div>
                  <a href='#'><img src="skins/WikiHow/tempImages/featureImage2.png" alt=
                  "" width="103" height="80" class="rounded_corners" /></a><br />
                  <a href='#'>Make a Bikini Cover Up from a Towel</a>
                </div>
              </td>
            </tr>
          </table><!-- id must be String:"hidden" + -n. 'n' is speed to open close -->

          <div id="hidden-8" class="hidden">
            <table class="featuredArticle_Table">
              <tr>
                <td>
                  <div>
                    <a href='#'><img src="skins/WikiHow/tempImages/featureImage0.png"
                    alt="" width="103" height="80" class="rounded_corners" /></a><br />
                    <a href='#'>Make Jello Cake</a>
                  </div>
                </td>

                <td>
                  <div>
                    <a href='#'><img src="skins/WikiHow/tempImages/featureImage1.png"
                    alt="" width="103" height="80" class="rounded_corners" /></a><br />
                    <a href='#'>Make Artist Trading Cards</a>
                  </div>
                </td>

                <td>
                  <div>
                    <a href='#'><img src="skins/WikiHow/tempImages/featureImage2.png"
                    alt="" width="103" height="80" class="rounded_corners" /></a><br />
                    <a href='#'>Make a Bikini Cover Up from a Towel</a>
                  </div>
                </td>

                <td>
                  <div>
                    <a href='#'><img src="skins/WikiHow/tempImages/featureImage3.png"
                    alt="" width="103" height="80" class="rounded_corners" /></a><br />
                    <a href='#'>How to Improve Your Hiking Technique</a>
                  </div>

                  <div class="c1"></div>
                </td>

                <td>
                  <div>
                    <a href='#'><img src="skins/WikiHow/tempImages/featureImage4.png"
                    alt="" width="103" height="80" class="rounded_corners" /></a><br />
                    <a href='#'>Make Artist Trading Cards</a>
                  </div>
                </td>
              </tr>

              <tr>
                <td>
                  <div>
                    <a href='#'><img src="skins/WikiHow/tempImages/featureImage3.png"
                    alt="" width="103" height="80" class="rounded_corners" /></a><br />
                    <a href='#'>How to Improve Your Hiking Technique</a>
                  </div>
                </td>

                <td>
                  <div>
                    <a href='#'><img src="skins/WikiHow/tempImages/featureImage4.png"
                    alt="" width="103" height="80" class="rounded_corners" /></a><br />
                    <a href='#'>Make Artist Trading Cards</a>
                  </div>
                </td>

                <td>
                  <div>
                    <a href='#'><img src="skins/WikiHow/tempImages/featureImage0.png"
                    alt="" width="103" height="80" class="rounded_corners" /></a><br />
                    <a href='#'>Make Jello Cake</a>
                  </div>
                </td>

                <td>
                  <div>
                    <a href='#'><img src="skins/WikiHow/tempImages/featureImage1.png"
                    alt="" width="103" height="80" class="rounded_corners" /></a><br />
                    <a href='#'>Make Artist Trading Cards</a>
                  </div>
                </td>

                <td>
                  <div>
                    <a href='#'><img src="skins/WikiHow/tempImages/featureImage2.png"
                    alt="" width="103" height="80" class="rounded_corners" /></a><br />
                    <a href='#'>Make a Bikini Cover Up from a Towel</a>
                  </div>
                </td>
              </tr>
            </table>
          </div>

          <div id="featuredNav">
            <a href="#">View Popular Articles</a><img src=
            "skins/WikiHow/images/actionArrow.png" alt="" /> <a href="#">View Random
            Article</a><img src="skins/WikiHow/images/actionArrow.png" alt="" />
            <span id="more"><a href="#" onclick="toggle(); return false;" id="toggle"
            name="toggle">See More Featured Articles</a><img src=
            "skins/WikiHow/images/arrowMore.png" id="moreOrLess" alt="" name=
            "moreOrLess" /></span>
          </div>
        </div>
      </div><img src="skins/WikiHow/images/article_bottom_wh.png" width="679" height="12"
      alt="" />

      <div id="category_shell"><img src="skins/WikiHow/images/article_top.png" width=
      "679" height="10" alt="" />

      <div id="article">
        <div class="category_header" id="category_header">
          <h1>Browse Articles by Category</h1>
        </div>

        <div class="category_inner" id="categoryInner">
          <table class="category_Table">
            <tr>
              <td>
                <div id="left">
                  <a href="#">Arts &amp; Entertainment</a>
                </div>
              </td>

              <td>
                <div id="right">
                  <a href="#">Holidays &amp; Tradition</a>
                </div>
              </td>
            </tr>

            <tr>
              <td>
                <div id="left">
                  <a href="#">Cars &amp; Other Vehicles</a>
                </div>
              </td>

              <td>
                <div id="right">
                  <a href="#">Personal Care &amp; Style</a>
                </div>
              </td>
            </tr>

            <tr>
              <td>
                <div id="left">
                  <a href="#">Computers &amp; Electronincs</a>
                </div>
              </td>

              <td>
                <div id="right">
                  <a href="#">Pets &amp; Animals</a>
                </div>
              </td>
            </tr>

            <tr>
              <td>
                <div id="left">
                  <a href="#">Education &amp; Communications</a>
                </div>
              </td>

              <td>
                <div id="right">
                  <a href="#">Philosophy &amp; Religion</a>
                </div>
              </td>
            </tr>

            <tr>
              <td>
                <div id="left">
                  <a href="#">Family Life</a>
                </div>
              </td>

              <td>
                <div id="right">
                  <a href="#">Relationships</a>
                </div>
              </td>
            </tr>

            <tr>
              <td>
                <div id="left">
                  <a href="#">Finance, Business &amp; Legal</a>
                </div>
              </td>

              <td>
                <div id="right">
                  <a href="#">Sports &amp; Fitness</a>
                </div>
              </td>
            </tr>

            <tr>
              <td>
                <div id="left">
                  <a href="#">Food &amp; Entertaining</a>
                </div>
              </td>

              <td>
                <div id="right">
                  <a href="#">Travel</a>
                </div>
              </td>
            </tr>

            <tr>
              <td>
                <div id="left">
                  <a href="#">Health</a>
                </div>
              </td>

              <td>
                <div id="right">
                  <a href="#">wikiHow</a>
                </div>
              </td>
            </tr>

            <tr>
              <td>
                <div id="left">
                  <a href="#">Hobbies &amp; Crafts</a>
                </div>
              </td>

              <td>
                <div id="right">
                  <a href="#">Work World</a>
                </div>
              </td>
            </tr>

            <tr>
              <td>
                <div id="left">
                  <a href="#">Hame &amp; Garden</a>
                </div>
              </td>

              <td>
                <div id="right">
                  <a href="#">Youth</a>
                </div>
              </td>
            </tr>

            <tr>
              <td>
                <div id="left"></div>
              </td>

              <td>
                <div id="right">
                  <a href="#">Other</a>
                </div>
              </td>
            </tr>
          </table>

          <div id="share_icons">
            <div>
              Share this Article
            </div><a href="" id="share_twitter" name="share_twitter"></a> <a href="" id=
            "share_stumbleupon" name="share_stumbleupon"></a> <a href="" id=
            "share_facebook" name="share_facebook"></a> <a href="" id="share_blogger"
            name="share_blogger"></a> <a href="" id="share_digg" name="share_digg"></a>
            <a href="" id="share_google" name="share_google"></a> <a href="" id=
            "share_delicious" name="share_delicious"></a><br class="clearall" />
          </div>
        </div>
      </div><img src="skins/WikiHow/images/article_bottom_wh.png" width="679" height="12"
      alt="" /></div>
    </div>

    <div id="sidebar">
    <div id="worldWide_shell">
      <div><img src="skins/WikiHow/images/worldWide_top.png" alt="" /></div>

      <div id="worldWide">
        <div id="languages">
          <h3>wikiHow Worldwide</h3>

          <p>wikiHow in other languages:<br/>
				<span>catal&agrave;, Espa&ntilde;ol, Deutsch, Fran&ccedil;ais,<br/>
				Nederlands, Portugu&ecirc;s.</span> You can<br/>
				also help start a new version of<br/>
				wikiHow in <span>your language.</span></p>
        </div>

        <div id="globe"><img src="skins/WikiHow/images/worldWide_globe.png" alt=
        "" /></div>
      </div>

      <div><img src="skins/WikiHow/images/worldWide_bottom.png" alt="" /></div>
    </div><img src="images/sidebar_top.png" />

    <div id="side_recent_changes" class="sidebox">
      <div id="rcwidget_divid">
        <h3>Recent Changes</h3>

        <div>
          <p><a href="">SoAndSo</a> left a message for <a href="">ThatOneGuy</a></p><em>2
          minutes ago</em>
        </div>

        <div class="rounded_corners tan">
          <p><a href="">SoAndSo</a> left a message for <a href="">ThatOneGuy</a></p><em>2
          minutes ago</em>
        </div>

        <div>
          <p><a href="">SoAndSo</a> left a message for <a href="">ThatOneGuy</a></p><em>2
          minutes ago</em>
        </div>

        <div class="rounded_corners tan">
          <p><a href="">SoAndSo</a> left a message for <a href="">ThatOneGuy</a></p><em>2
          minutes ago</em>
        </div>
      </div>

      <p class="bottom_link"><a href="">Want to join in?</a> <a href="" id=
      "play_pause_button" onmouseover="button_swap(this);" onmouseout=
      "button_unswap(this);" onclick="rcTransport(this);return false;" name=
      "play_pause_button"></a></p>
    </div><img src="images/sidebar_bottom_fold.png" /> <img src=
    "images/sidebar_top.png" />

    <div class="sidebox" id="side_nav">
      <h3 id="navigation_list_title"><a href=
      "javascript:sidenav_toggle('navigation_list',this);" id="href_navigation_list"
      name="href_navigation_list">- collapse</a> Navigation</h3>

      <ul id="navigation_list" class="c2">
        <li>+ <a href="">Community Portal</a></li>

        <li>+ <a href="">Recent Changes</a></li>

        <li>+ <a href="">Forums</a></li>

        <li>+ <a href="">Special Pages</a></li>

        <li>+ <a href="">Suggest a Topic</a></li>

        <li>+ <a href="">List Suggested Topics</a></li>
      </ul>

      <h3><a href="javascript:sidenav_toggle('editing_list',this);" id=
      "href_editing_list" name="href_editing_list">+ expand</a> Editing Tools</h3>

      <ul id="editing_list" class="c3">
        <li>+ <a href="">Find Free Photos</a></li>

        <li>+ <a href="">Upload Image</a></li>

        <li>+ <a href="">Manage Related wikiHows</a></li>

        <li>+ <a href="">What Links Here</a></li>

        <li>+ <a href="">Article Stats</a></li>

        <li>+ <a href="">Related Changes</a></li>
      </ul>

      <h3><a href="javascript:sidenav_toggle('my_pages_list',this);" id=
      "href_my_pages_list" name="href_my_pages_list">+ expand</a> My Pages</h3>

      <ul id="my_pages_list" class="c3">
        <li>+ <a href="">My Watchlist</a></li>

        <li>+ <a href="">My User Page</a></li>

        <li>+ <a href="">My Talk Page</a></li>

        <li>+ <a href="">My Drafts</a></li>

        <li>+ <a href="">My Contributions</a></li>

        <li>+ <a href="">My Fan Mail</a></li>

        <li>+ <a href="">My Preferences</a></li>

        <li>+ <a href="">Log Out</a></li>
      </ul>
    </div><img src="images/sidebar_bottom.png" /> <img src="images/sidebar_top.png" />

    <div id="side_related_articles" class="sidebox">
      <h3>New Articles</h3>
    </div><img src="images/sidebar_bottom_fold.png" /> <img src=
    "images/sidebar_top.png" />

    <div class="sidebox">
      <ul id="social_box">
        <li id="social_fb"><a href="">Join our Facebook Group</a></li>

        <li id="social_tw"><a href="">Follow us on Twitter</a></li>
      </ul>
    </div><img src="images/sidebar_bottom.png" /></div>
  </div><br class="clearall" />

  <div id="footer_shell">
    <div id="footer">
      <div id="footer_side">
        <img src="images/wikihow_footer.gif" width="133" height="22" alt="wikiHow" />

        <p id="footer_tag">the how-to manual that you can edit</p>

        <ul>
          <li class="top"><a href="">Home</a></li>

          <li><a href="">About</a></li>

          <li><a href="">wikiHow Help</a></li>

          <li><a href="">Tour</a></li>

          <li><a href="">Terms of Use</a></li>

          <li><a href="">RSS</a></li>

          <li><a href="">Site map</a></li>
        </ul>
      </div>

      <div id="footer_main">
        <form id="footer_search" name="footer_search">
          <input type="text" class="search_box" /> <input type="submit" value="Search"
          class="search_button" onmouseover="button_swap(this);" onmouseout=
          "button_unswap(this);" />
        </form>

        <h3>Explore Categories</h3>

        <ul>
          <li><a href="">Arts &amp; Entertainment</a></li>

          <li><a href="">Finance &amp; Business</a></li>

          <li><a href="">Home &amp; Garden</a></li>

          <li><a href="">Sports &amp; Fitness</a></li>

          <li><a href="">Cars &amp; Other Vehicles</a></li>

          <li><a href="">Food &amp; Entertaining</a></li>

          <li><a href="">Personal Care &amp; Style</a></li>

          <li><a href="">Travel</a></li>

          <li><a href="">Computers &amp; Electronics</a></li>

          <li><a href="">Health</a></li>

          <li><a href="">Pets &amp; Animals</a></li>

          <li><a href="">wikiHow</a></li>

          <li><a href="">Education &amp; Communications</a></li>

          <li><a href="">Hobbies &amp; Crafts</a></li>

          <li><a href="">Philosophy &amp; Religion</a></li>

          <li><a href="">Work World</a></li>

          <li><a href="">Family Life</a></li>

          <li><a href="">Holidays &amp; Traditions</a></li>

          <li><a href="">Relationships</a></li>

          <li><a href="">Youth</a></li>

          <li><a href="">Other</a></li>
        </ul>

        <div id="sub_footer">
          <p id="mediawiki_p"><a href=
          "../Powered-and-Inspired-by-Mediawiki/index.html"><img src=
          "images/logo_mediawiki.png" height='31' width='88' id="mediawiki" alt=
          "Mediawiki" name="mediawiki" /></a> <a href=
          "../Powered-and-Inspired-by-Mediawiki/index.html">Powered by Mediawiki.</a></p>

          <p id="carbon_neutral"><a href="../WikiHow_Carbon-Neutral/index.html" class=
          "imglink"><img src="images/logo_carbon_neutral.png" id="carbonneutral" alt=
          "Carbon Neutral Website" height='31' width='88' name="carbonneutral" /></a>
          wikiHow is a <a href="../WikiHow_Carbon-Neutral/index.html" class=
          "imglink">carbon neutral website</a></p>

          <p id="creative_commons"><a href=
          "http://www.wikihow.com/wikiHow:Creative-Commons" class="imglink"><img src=
          "images/logo_creative_commons.gif" height='31' width='88' id="creativecommons"
          alt="Creative Commons" name="creativecommons" /></a> All text shared under a
          <a href="http://www.wikihow.com/wikiHow:Creative-Commons">Creative Commons
          License</a>.</p>
        </div>
      </div><br class="clearall" />
    </div>
  </div>




<script type="text/javascript">
function button_click(obj) {
    if ((navigator.appName == "Microsoft Internet Explorer") && (navigator.appVersion < 7)) {
        return false;
    }
    
    //article tabs
    if (obj.id.indexOf("tab_") >= 0) {
        obj.style.color = "#4A3C31";
        obj.style.backgroundImage = "url(images/article_tab_bg_on.gif)";
    }
        
    if (obj.id == "play_pause_button") {
        if (obj.className.indexOf("play") >= 0) {
            obj.style.backgroundPosition = "0 -130px";
        }
        else {
            obj.style.backgroundPosition = "0 -52px";
        }
    }
    
    if (obj.className.indexOf("search_button") >= 0) {
        obj.style.backgroundPosition = "0 -29px";
    }
}

function button_swap(obj) {
    if ((navigator.appName == "Microsoft Internet Explorer") && (navigator.appVersion < 7)) {
        return false;
    }
        
    if (obj.id == "AdminOptions") {obj = document.getElementById("tab_admin")};
        
    //upper navigation tabs
    if (obj.id.indexOf("nav_") >= 0) {
        obj.style.color = "#FFFFFF";
        (obj.id == "nav_home") ? obj.style.backgroundPosition = "-102px -73px" : obj.style.backgroundPosition = "0 -73px";
    }    
    else if (obj.className.indexOf("button136") >= 0) {
        obj.style.backgroundPosition = "0 -38px";
    }
    else if (obj.id == "tab_admin") { //article admin tab
        obj.style.backgroundPosition = "0 -31px";
    }
    else if (obj.className.indexOf("search_button") >= 0) {
        obj.style.backgroundPosition = "0 -29px";
    }
    else if ((obj.id == "play_pause_button") && (obj.className.indexOf("play") >= 0)) {
        obj.style.backgroundPosition = "0 -104px";
    }    
    else {
        obj.style.backgroundPosition = "0 -26px";
    }

}

function button_unswap(obj) {
    if ((navigator.appName == "Microsoft Internet Explorer") && (navigator.appVersion < 7)) {
        return false;
    }
    
    if (obj.id == "AdminOptions") {obj = document.getElementById("tab_admin")};
    
    if (obj.id == "arrow_right") {
        obj.style.backgroundPosition = "-26px 0";
    }
    else if ((obj.id == "play_pause_button") && (obj.className.indexOf("play") >= 0)) {
        obj.style.backgroundPosition = "0 -78px";
    }
    else {
        obj.style.backgroundPosition = "0 0";
    }
    
    //upper navigation tabs
    if (obj.id.indexOf("nav_") >= 0) {
        obj.style.color = "#514239";
    }
        
    if (obj.className.indexOf("white_button") >= 0) {
        obj.style.color = "#018EAB";
    }
        
}

/*
function arrow_swap(obj) {
    if ((navigator.appName == "Microsoft Internet Explorer") && (navigator.appVersion < 7)) {
        return false;
    }
    if (obj.firstChild.src.indexOf("_left") >= 0) {
        obj.firstChild.src = "images/arrow_left_over.png";
    }
    else {
        obj.firstChild.src = "images/arrow_right_over.png";
    }
}

function arrow_unswap(obj) {
    if ((navigator.appName == "Microsoft Internet Explorer") && (navigator.appVersion < 7)) {
        return false;
    }
    if (obj.firstChild.src.indexOf("_left") >= 0) {
        obj.firstChild.src = "images/arrow_left.png";
    }
    else {
        obj.firstChild.src = "images/arrow_right.png";
    }
}
*/

//expand/collapse the nav menu
function sidenav_toggle(list_id) {
    var list;
    (document.all) ? list = eval("document.all." + list_id) : list = eval("document.getElementById(list_id)");
    
    if (list.style.display == "none") {
        list.style.display = "block";
        document.getElementById("href_" + list_id).innerHTML = "- collapse";
    }
    else {
        list.style.display = "none";
        document.getElementById("href_" + list_id).innerHTML = "+ expand";
    }
            
}


//do a scrolling reveal
function scroll_open(id,height,max_height) {
    document.getElementById(id).style.top = height + "px";
    document.getElementById(id).style.display = "block";
    document.getElementById(id).style.position = "relative";
    height += 1;
    if (height < max_height) {
        window.setTimeout("scroll_open('" + id + "'," + height + "," + max_height + ")",15);
    }
}

function findPos(obj) {
    var curleft = curtop = 0;
    if (obj.offsetParent) {
	    curleft = obj.offsetLeft
	    curtop = obj.offsetTop
	    while (obj = obj.offsetParent) {
		    curleft += obj.offsetLeft
		    curtop += obj.offsetTop
	    }
    }
    return [curleft,curtop];
}


function AdminTab(obj,bTab) {       
    var AdmOptions = document.getElementById("AdminOptions");
    
    if (AdmOptions.style.display !== "block") {
        //set position if on the tab
        if (bTab) { 
            var coords = findPos(obj);
            AdmOptions.style.left = (coords[0]) + "px"; 
            AdmOptions.style.top = (coords[1]) + "px"; 
        }
        //show it
        AdmOptions.style.display = "block";
    } 
    else { 
        //hide it
        AdmOptions.style.display = "none"; 
    }
}

function AdminCheck(obj,bOn) {
    if (bOn) {
        obj.style.background = "url(images/admin_check.gif) #F2ECDE no-repeat 65px 9px";
    }
    else {
        obj.style.background = "#F2ECDE";
    }
}

function doNothing() {
    return false;
}

</script>


</body>
</html>
