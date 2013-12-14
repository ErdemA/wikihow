<? global $wgLanguageCode; ?>
<div class="minor_section">
	<h2><?= wfMsg('pb-articlesstarted') ?> (<?=count($data)?>)</h2>
	<table class="pb-articles" id="pb-created">
	<thead>
		<tr>
			<th class="first pb-title"><?= wfMsg('pb-articlename') ?></th>
<? if($wgLanguageCode == "en") { ?>
			<th class="middle pb-star">Rising</th>
			<th class="middle pb-feature">Featured</th>
<? } ?>
			<th class="last pb-view"><?= wfMsg('pb-views') ?></th>
		</tr>
	</thead>
	<tbody>
	<? if ($data) : ?>
	<? foreach($data as $count => $row): ?>
		<? if($count >= $max ) break; ?>
		<tr>
			<td class="pb-title"><a href="/<?= $row->title->getPartialURL() ?>"><?= $row->title->getFullText() ?></a></td>
<? if($wgLanguageCode == "en") { ?>
			<td class="pb-star">
				<? if($row->rs): ?>
					<img src="<?= wfGetPad('/extensions/wikihow/profilebox/star-green.png') ?>" height="20px" width="20px"></td>
				<? else: ?>
					&nbsp;
				<? endif; ?>
			<td class="pb-feature">
				<? if($row->fa): ?>
				<img src="<?= wfGetPad('/extensions/wikihow/profilebox/star-blue.png') ?>" height="20px" width="20px"></td>
				<? else: ?>
					&nbsp;
				<? endif; ?>
			</td>
<? } ?>
			<td class="pb-view"><?= number_format($row->page_counter, 0, '',',') ?></td>
		</tr>
	<? endforeach ?>
	<? else: ?>
		<tr>
			<td colspan="4" align="center">
				<?= ($isOwner) ? wfMsg('pb-noarticles') : wfMsg('pb-noarticles-anon'); ?><br /><br />
			</td>
		</tr>
	<? endif; ?>
	</tbody>
</table>
	
	<? if(count($data) > $max): ?>
	<div class="pb-moreless">
		<a href="#" id="created_more" onclick="pbShow_articlesCreated('more'); return false;"><?= wfMsg('pb-viewmore') ?></a><a href="#" id="created_less" style="display:none;" onclick="pbShow_articlesCreated(); return false;"><?= wfMsg('pb-viewless') ?></a>
	</div>
	<? endif; ?>
</div>
