<form name="userlogin" class="userlogin" method="post" action="<?=$action_url?>">
	
	<h3><?= wfMsg('log_in_via') ?></h3>
	<?=$social_buttons?>
		
	<div class="userlogin_inputs">
		<h3><?= wfMsg('login') ?></h3>
		<input type='text' class='loginText input_med' name="wpName" id="wpName1<?=$suffix?>" value="Username" size='20' />
		<input type="hidden" id="wpName1_showhide<?=$suffix?>" /><br />
		
		<input type='password' class='loginPassword input_med' name="wpPassword" id="wpPassword1<?=$suffix?>" value="" size='20' />
		<input type="hidden" id="wpPassword1_showhide<?=$suffix?>" />
	</div>

	<input type='submit' class="button primary login_button" name="wpLoginattempt" id="wpLoginattempt" value="<?= wfMsg('login') ?>" />

	<div class="userlogin_remember">
		<input type='checkbox' name="wpRemember" value="1" id="wpRemember<?=$suffix?>" checked="checked" /> 
		<label for="wpRemember<?=$suffix?>"><?= wfMsg('remember_me') ?></label>
	</div>
	
	<div class="userlogin_links">
		<a href="/Special:LoginReminder" id="forgot_pwd<?=$suffix?>"><?= wfMsg('forgot_pwd')?></a>
		<a href="/Special:Userlogin?type=signup"><?= wfMsg('nologinlink')?></a>
	</div>
</form>
