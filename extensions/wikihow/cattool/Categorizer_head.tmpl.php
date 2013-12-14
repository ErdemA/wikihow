<div id="cat_head_outer" class="tool_header">
	<h1>Can you help categorize this article?</h1>
	<p id="cat_help" class='tool_help'>Need Help? <a href="<?=$cat_help_url?>" target="_blank">Learn How to Categorize</a>.</p>
	<div id="cat_spinner">
		<img src="/extensions/wikihow/rotate.gif" alt="" />
	</div>
	<div id="cat_head">
		<div id="cat_aid"><?=$pageId?></div>
		<h1 id="cat_title"><a href="<?=$titleUrl?>" target="_blank"><?=$title?></a></h1>
		<div id="cat_list_header">
			<b>Currently in:</b>
			<span id="cat_list">
				<? 
				$nodisplay = "";
				if (!empty($cats)) { 
					echo $cats;
					$nodisplay = "style = 'display: none'";
				} 
				?>
				<span id='cat_none' <?=$nodisplay?>>Search below to add categories.</span>
			</span>
		</div>
	</div>
</div>
<div id="cat_notify">A maximum of two categories can be assigned.</div>
