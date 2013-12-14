<div id="introimagenav">
	<h4>
    	<span style='float:right'><a class="button white_button_100" href="#" onclick='introImageAdder.getArticle(); return false;' onmouseout="button_unswap(this);" onmouseover="button_swap(this);" >skip</a></span>
        <span class='introimagearticle'>Article:</span> <a href="<?= $titlelink ?>" target="_blank" id="article_title"><?= $title ?></a>
    </h4>
    <a href='#' id="iia_introlink"><div id="iia_more" class="off"></div>View article introduction</a> | <a href='<?= $titlelink ?>' target="_blank">Open article in new window</a>
	<div id="iia_articleintro"><?= $intro ?></div>
	<form id="eiu-image-search" action="#" onsubmit="easyImageUpload.loadImages('current', jQuery('#search_image_query').val(), 1); return false;">
	<table><tr><td><?=wfMsg('iia-eiu-terms')?></td><td>	
		<input type='text' class="search_box" id='search_image_query' value='<?= htmlspecialchars($searchterms, ENT_QUOTES) ?>' size='30' style="float:left;"> 
    	<input type='submit' class="search_button iia_search_button" value='<?= wfMsg('iia-eiu-find') ?>' onmouseout='button_unswap(this);' onmouseover='button_swap(this);'>
		</td></tr></table>
	</form>
	<div class="clearall"></div>
</div><!--end introimagenav-->
<div id="article_tabs">
<!--
    <a onmousedown="button_click(this);" class="on" title="Flickr" id="eiu-flickr-link" href="#" onclick="easyImageUpload.cssSetFindTabsWeight(false); easyImageUpload.loadImages('flickr', jQuery('#search_image_query').val(), 1); return false;"><?= wfMsg('eiu-flickr') ?></a>
    <a onmousedown="button_click(this);" title="Wikimedia" id="eiu-this-wiki-link" href="#" onclick="easyImageUpload.cssSetFindTabsWeight(true); easyImageUpload.loadImages('wiki', jQuery('#search_image_query').val(), 1); return false;"><?= wfMsg('eiu-wikimedia') ?></a>
-->
</div>
<div id="article_tabs_line"></div>
<div id='eiu_recently_uploaded'>
</div><!--end eiu_recently_uploaded-->
