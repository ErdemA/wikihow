<h3><?= wfMsg('browsecategories') ?></h3>
<ul id="hp_categories">
	<?php foreach($categories as $cat => $info): ?>
		<li class="cat_icon <?=$info->icon?>"><a href="<?=$info->url?>"><?= $cat ?></a></li>
	<? endforeach ?>
</ul>
