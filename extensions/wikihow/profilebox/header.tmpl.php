<div class="pb_userdata">
	<span><?= $pb_display_name ?></span>
<? if($pb_display_show): ?>
	<div id="profileBoxID">
	<? if($pb_showlive): ?>
		<?= wfMsg('pb-livesin', "<span>" . $pb_live . "</span>") ?>,<br />
	<? endif; ?>
	<?= wfMsg("pb-beenonwikihow", "<span>" . $pb_regdate . "</span>") ?>
	<? if($pb_showwork): ?>
	<div><?= wfMsg("pb-website") ?> <a href="<?= $pb_work ?>" rel="nofollow"><?= $pb_work ?></a></div> 
	<? endif; ?>
	</div>
<? endif ?>	
	<div class="pb_contact"><a href="<?=$pb_email_url?>">E-mail <?= $pb_user_name ?></a></div>
</div>
<? if(($pb_display_show) && ($pb_aboutme)): ?>
<div class="pb_aboutme" id="pb_aboutme"><?= $pb_aboutme ?></div>
<? endif; ?>
<?= $pb_social ?>
