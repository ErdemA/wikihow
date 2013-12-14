<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Method Editor Tool',
	'author' => 'Bebeth Steudel',
	'description' => '',
);

$wgSpecialPages['MethodEditor'] = 'MethodEditor';
$wgAutoloadClasses['MethodEditor'] = dirname(__FILE__) . '/MethodEditor.body.php';
$wgExtensionMessagesFiles['MethodEditor'] = dirname(__FILE__) . '/MethodEditor.i18n.php';

$wgLogTypes[] = 'methedit';
$wgLogNames['methedit'] = 'methedit';
$wgLogHeaders['methedit'] = 'methedit';