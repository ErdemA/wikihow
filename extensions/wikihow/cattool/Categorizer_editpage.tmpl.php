<div id="cat_head_outer">
	<div id="cat_spinner">
		<img src="/extensions/wikihow/rotate.gif" alt="" />
	</div>
	<div id="cat_head">
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
<div id="cat_tree"><?=$tree?></div>
<div id="cat_ui">
	<div id="cat_search_outer">
		<div id="cat_search_box">
			<input id="cat_search" class="search_input" />
			<input type="button"  id="cat_search_button" class="cat_search_button button primary" value="Search"></input>
		</div>
	</div>
	<br />
	<div class="wh_block">
		<div id='cat_breadcrumbs_outer'>
			<ul id='cat_breadcrumbs' class='ui-corner-all'></ul>
			<a class="cat_breadcrumb_add button secondary" href="#">Add</a>
			<div class="cat_subcats_outer">
				<div><b class="whb"><?=$cat_subcats_label?></b></div>
				<div class="cat_subcats cat_multicolumn">
					<ul id="cat_subcats_list"></ul>
				</div>
			</div>
		</div>
		<div id="cat_options">
			<a href="#" class="button primary disabled" id="cat_save_editpage">Update Categories</a>
			<a href="#" class="button secondary" id="cat_cancel">Cancel</a>
		</div>
		<br />
	</div>
</div>
