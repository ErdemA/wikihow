<?php
/**
 * @addtogroup Templates
 */
if( !defined( 'MEDIAWIKI' ) ) die( -1 );

/** */
require_once( 'includes/SkinTemplate.php' );

/**
 * HTML template for Special:Userlogin form
 * @addtogroup Templates
 */
class UserloginTemplate extends QuickTemplate {
	function execute() {
	?>
<div id="userloginForm" class="userloginForm">

	<table border="0" width="100%"><tr><td rowspan="2">
<form name="userlogin" method="post" action="<?php $this->text('action') ?>">
	<div id="userloginprompt"><?php  $this->msgWiki('loginprompt') ?></div>
	<table> 
		<tr>
			<td class="mw-label" style="width:50px"><label for='wpName1'><?php $this->msg('username_or_email') ?></label></td>
			<td class="mw-input">
				<div style="position:relative">
					<input type='text' class='loginText input_med' name="wpName" id="wpName1"
					value="<?php $this->text('name') ?>" size='20' />
					<?php if ( $this->data['messagetype'] == 'error_username' ): ?>
						<div class="mw-error-bottom mw-error" id="wpName1_error">
							<div class="mw-error-top">
								<?php $this->html('message') ?>
							</div>
						</div>
					<?php endif; ?>
					<input type="hidden" id="wpName1_showhide" />
				</div>
			</td>
		</tr>
		<tr>
			<td class="mw-label"><label for='wpPassword1'><?php $this->msg('yourpassword') ?></label></td>
			<td class="mw-input">
				<div style="position:relative;">
					<input type='password' class='loginPassword input_med' name="wpPassword" id="wpPassword1"
						value="" size='20' />
					<?php if ( $this->data['messagetype'] == 'error_password' ): ?>
						<div class="mw-error-bottom mw-error" id="wpPassword1_error">
							<div class="mw-error-top">
								<?php $this->html('message') ?>
							</div>
						</div>
					<?php endif; ?>
					<input type="hidden" id="wpPassword1_showhide" />
				</div>
			</td>
		</tr>
	<?php if( $this->data['usedomain'] ) {
		$doms = "";
		foreach( $this->data['domainnames'] as $dom ) {
			$doms .= "<option>" . htmlspecialchars( $dom ) . "</option>";
		}
	?>
		<tr>
			<td class="mw-label"><?php $this->msg( 'yourdomainname' ) ?></td>
			<td class="mw-input">
				<select name="wpDomain" value="<?php $this->text( 'domain' ) ?>">
					<?php echo $doms ?>
				</select>
			</td>
		</tr>
	<?php } ?>
		<tr>
			<td class="mw-input"></td>
			<td>
			<? if( $this->data['useemail'] && $this->data['canreset']) { ?>
				<a href="/Special:LoginReminder" id="forgot_pwd"><?php global $wgLanguageCode; if($wgLanguageCode == "en") { ?>Forgot password? <?php } else { ?><?= wfMsg('forgot_pwd')?><?php } ?></a>
			<? } ?>
			</td>
		</tr>
		<tr>
			<td style="vertical-align: bottom;" class="mw-label"><?php if($this->data['header'] != ""): ?>
				<label style="display:block; padding-bottom: 35px">Security</label>
				<?php endif; ?>
			</td>
			<td><?php $this->html('header'); /* pre-table point for form plugins... */ ?></td>
		</tr>
		<tr>
			<td class="mw-submit"></td>
			<td style="padding-top:5px">
				<table cellpadding="0" cellspacing="0">
					<tr>
						<td>
							<input type='submit' class="button primary submit_button" name="wpLoginattempt" id="wpLoginattempt" value="<?php $this->msg('login') ?>" />
						</td>
						<td>
							<input type='checkbox' name="wpRemember" value="1" id="wpRemember" checked="checked" /> <label for="wpRemember"><?php $this->msg('remembermypassword') ?></label>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>

</td>
<td valign="top">
	<div id="userlogin_alt_logins">
		<div class="headline">Log in via</div>
		<?=UserLoginBox::getSocialLogin()?>
	</div>
</td>
</tr>
</table>

</div>
<div id="loginend"><?php $this->msgWiki( 'loginend' ); ?></div>
<?php

	}
}

/**
 * @addtogroup Templates
 */
class UsercreateTemplate extends QuickTemplate {
	function execute() {
		global $wgMinimalPasswordLength;
		echo '<script type="text/javascript"> passwordtooshort="' . rawurlencode(wfMsg( 'passwordtooshort', $wgMinimalPasswordLength )) . '"; badretype = "' . rawurlencode(wfMsg( 'badretype' )) . '";</script>';
?>
<div class="userlogin">
<form name="userCreate" id="userCreate" method="post" action="<?php $this->text('action') ?>">
	<table>
		<tr>
			<td class="mw-label"><label for='wpName'><?php $this->msg('create_yourname') ?></label></td>
			<td class="mw-input">
				<div style="position:relative">
					<input type='text' class='loginText input_med' name="wpName" id="wpName"
						value="<?php $this->text('name') ?>" size='20' tabindex='2'/>
					<img src="<?= wfGetPad('/skins/WikiHow/images/exclamation.png'); ?>" id="wpName_mark" class="wpMark" />
					<div class="mw-error-bottom mw-error" id="wpName_error" <?php if ( $this->data['messagetype'] != 'error_username' ) echo 'style="display:none;"' ?>>
						<div class="mw-error-top">
							<?php if ( $this->data['messagetype'] == 'error_username' ): ?>
								<?php $this->html('message') ?>
							<?php endif; ?>
						</div>
					</div>
					<div class="mw-error-bottom mw-info" id="wpName_info" style="display:none">
						<div class="mw-error-top">
							<?php echo wfMsg('info_username') ?>
						</div>
					</div>
					<input type="hidden" id="wpName_showhide" />
				</div>
			</td>
		</tr>
		<tr>
			<td></td>
			<td class="userlogin_option_row">
				<input type='checkbox' id='wpUseRealNameAsDisplay' name='wpUseRealNameAsDisplay' tabindex='3' <? if ($this->data['userealname']) { ?>checked='checked'<? } ?> />
				<label for="wpUseRealNameAsDisplay"><?php $this->msg('user_real_name_display'); ?></label>
			</td>
	   </tr>	   
	   <tr id='real_name_row' <?php if( $this->data['userealname'] ) { ?>style="display:none;"<? } ?>>
			<td class="mw-label"><label for='wpRealName'><?php $this->msgHtml('create_yourrealname') ?></label></td>
			<td class="mw-input">
				<div style="position:relative">
					<input type='text' class='loginText input_med' name="wpRealName" id="wpRealName" tabindex="4" value="<?php $this->text('realname') ?>" size='20' />
					<div class="mw-error-bottom mw-info" id="wpRealName_info">
						<div class="mw-error-top">
							<?php $this->msgWiki('info_realname') ?>
						</div>
					</div>
				</div>
			</td>
		</tr>
		<tr>
			<td class="mw-label"><label for='wpPassword2'><?php $this->msg('yourpassword') ?></label></td>
			<td class="mw-input">
				<div style="position:relative">
					<input type='password' class='loginPassword input_med' name="wpPassword" id="wpPassword2"
						tabindex="5"
						value="" size='20' />
					<img src="<?= wfGetPad('/skins/WikiHow/images/exclamation.png'); ?>" height="30" id="wpPassword2_mark" class="wpMark" />
					<div class="mw-error-bottom mw-error" id="wpPassword2_error" <?php if ( $this->data['messagetype'] != 'error_password' ) echo 'style="display:none;"' ?>>
						<div class="mw-error-top">
							<?php if ( $this->data['messagetype'] == 'error_password' ): ?>
								<?php $this->html('message') ?>
							<?php endif; ?>
						</div>
					</div>
					<input type="hidden" id="wpPassword2_showhide" />
				</div>
			</td>
		</tr>
	<?php if( $this->data['usedomain'] ) {
		$doms = "";
		foreach( $this->data['domainnames'] as $dom ) {
			$doms .= "<option>" . htmlspecialchars( $dom ) . "</option>";
		}
	?>
		<tr>
			<td class="mw-label"><?php $this->msg( 'yourdomainname' ) ?></td>
			<td class="mw-input">
				<select name="wpDomain" value="<?php $this->text( 'age' ) ?>" tabindex="6">
					<?php echo $doms ?>
				</select>
			</td>
		</tr>
	<?php } ?>
		<tr>
			<td class="mw-label"><label for='wpRetype'><?php $this->msgHtml('yourpasswordagain') ?></label></td>
			<td class="mw-input">
				<div style="position:relative">
					<input type='password' class='loginPassword input_med' name="wpRetype" id="wpRetype" tabindex="7" value="" size='20' />
					<img src="<?= wfGetPad('/skins/WikiHow/images/exclamation.png'); ?>" height="30" id="wpRetype_mark" class="wpMark" />
					<div class="mw-error-bottom mw-error" id="wpRetype_error" style="display:none;">
						<div class="mw-error-top">
						</div>
					</div>
				</div>
			</td>
		</tr>
		<?php if( $this->data['useemail'] ) { ?>
		<tr>
			<td class="mw-label" ><label for='wpEmail'><?php $this->msgHtml('create_youremail') ?></label></td>
			<td class="mw-input" >
				<div style="position:relative">
					<input type='text' class='loginText input_med' name="wpEmail" id="wpEmail" tabindex="9" value="<?php $this->text('email') ?>" size='20' />
					<div class="mw-error-bottom mw-error mw-info" id="wpEmail_error">
						<div class="mw-error-top">
							<?php $this->msgWiki( 'emailforlost' ); ?>
						</div>
					</div>
					<div class="mw-error-bottom mw-info" id="wpEmail_info">
						<div class="mw-error-top">
							<?php $this->msgHtml('info_email') ?>
						</div>
					</div>
					<input type="hidden" id="wpEmail_showhide" />
				</div>
			</td>
		</tr>
		<?php } ?>
		<tr>
			<td class="mw-label" style="vertical-align:bottom;"><label style="display:block; padding-bottom: 28px">Security</label></td>
			<td class="mw-input">
				<div class="userlogin_captcha">
					<?php $this->html('header'); /* pre-table point for form plugins... */ ?>
					<?php if ( $this->data['messagetype'] == 'error_captcha' ): ?>
						<div class="mw-error-bottom mw-error" id="wpCaptchaWord_error">
							<div class="mw-error-top">
								<h4>Error</h4>
								<?php $this->html('message') ?>
							</div>
						</div>
					<?php endif; ?>
					<div class="mw-error-bottom mw-info" id="wpCaptchaWord_info">
						<div class="mw-error-top">
							<?php echo wfMsg('info_captcha') ?>
						</div>
					</div>
					<input type="hidden" id="wpCaptchaWord_showhide" />
				</div>
			</td>
		</tr>
		<tr>
			<td></td>
			<td class="mw-submit">
				<input type='submit' name="wpCreateaccount" id="wpCreateaccount" tabindex="12" class="button primary submit_button"
					value="<?php $this->msg('createaccount') ?>" />
					<input type='checkbox' name="wpRemember" value="1" id="wpRemember" checked="checked" tabindex='11'/>
					<label for="wpRemember"><?php $this->msg('remembermypassword') ?></label>
				<div class="captcha_fineprint"><?php $this->msgWiki( 'fancycaptcha-createaccount' ) ?></div>
			</td>
		</tr>
	</table>
<?php 
    /*if( $this->data['useemail'] ) {
           echo '<div id="login-emailforlost">';
           $this->msgWiki( 'emailforlost' );
            echo '</div>';
        }*/

if( @$this->haveData( 'uselang' ) ) { ?><input type="hidden" name="uselang" value="<?php $this->text( 'uselang' ); ?>" /><?php } ?>
</form>
</div>
<div id="signupend"><?php $this->msgWiki( 'signupend' ); ?></div>
<?php

	}
}

?>
