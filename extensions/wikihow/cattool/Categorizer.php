<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgSpecialPages['Categorizer'] = 'Categorizer';
$wgAutoloadClasses['Categorizer'] = dirname( __FILE__ ) . '/Categorizer.body.php';
$wgExtensionMessagesFiles['Categorizer'] = dirname(__FILE__) . '/Categorizer.i18n.php';
