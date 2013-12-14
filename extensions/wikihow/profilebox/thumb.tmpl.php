<div class="minor_section">
	<h2><?= wfMsg('pb-thumbedupedits') ?></h2>
	<table class='pb-articles' id='pb-thumbed'>
		<thead>
			<tr>
				<th class='first pb-title'><?= wfMsg('pb-articlename') ?></th>
				<th class='last pb-view'><?= wfMsg('date') ?></th>
			</tr>
		</thead>
		<tbody>
		<? if ($data) : ?>
			<? foreach($data as $count => $item): ?>
			<? if($count >= $max ) break; ?>
			<tr>
				<td class='pb-title'><a href='/<?= $item->title->getPartialURL() ?>'><?= $item->title->getFullText()?></a></td>
				<td class='pb-view'><?= $item->text ?></td>
			</tr>
			<? endforeach; ?>
			<? else: ?>
				<tr>
					<td colspan="2" align="center">
						<?= ($isOwner) ? wfMsg('pb-nothumbs') : wfMsg('pb-noarticles-anon'); ?><br /><br />
					</td>
				</tr>
		<? endif; ?>
		</tbody>
	</table>
	
	<? if(count($data) > $max): ?>
	<div class="pb-moreless">
		<a href='#' id='thumbed_more' onclick='pbShow_Thumbed("more"); return false;'>View more &raquo;</a><a href='#' id='thumbed_less' style='display:none;' onClick='pbShow_Thumbed(); return false;'>&laquo; View Less</a>
	</div>
	<? endif; ?>
</div>
