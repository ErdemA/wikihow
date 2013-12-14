<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is a MediaWiki extension, it is not a valid entry point' );
}

$wgExtensionFunctions[] = 'wfSetupParserFunctions';
$wgExtensionCredits['parserhook'][] = array(
	'name' => 'ParserFunctions',
	'version' => '1.1.1',
	'url' => 'http://meta.wikimedia.org/wiki/ParserFunctions',
	'author' => 'Tim Starling',
	'description' => 'Enhance parser with logical functions',
	'descriptionmsg' => 'pfunc_desc',
);

$wgExtensionMessagesFiles['ParserFunctions'] = dirname(__FILE__) . '/ParserFunctions.i18n.php';
$wgHooks['LanguageGetMagic'][]       = 'wfParserFunctionsLanguageGetMagic';
$wgAutoloadClasses['ExtParserFunctions'] = dirname( __FILE__ ) . '/ParserFunctions.body.php';

function wfParserFunctionsLanguageGetMagic( &$magicWords, $langCode ) {
	require_once( dirname( __FILE__ ) . '/ParserFunctions.i18n.magic.php' );
	foreach( efParserFunctionsWords( $langCode ) as $word => $trans )
		$magicWords[$word] = $trans;
	return true;
}
function wfSetupParserFunctions() {
	global $wgParser, $wgExtParserFunctions, $wgHooks;

	$wgExtParserFunctions = new ExtParserFunctions;

	// Check for SFH_OBJECT_ARGS capability
	if ( defined( 'MW_SUPPORTS_PARSERFIRSTCALLINIT' ) ) {
		$wgHooks['ParserFirstCallInit'][] = array( &$wgExtParserFunctions, 'registerParser' );
	} else {
		if ( class_exists( 'StubObject' ) && !StubObject::isRealObject( $wgParser ) ) {
			$wgParser->_unstub();
		}
		$wgExtParserFunctions->registerParser( $wgParser );
	}

	$wgHooks['ParserClearState'][] = array( &$wgExtParserFunctions, 'clearState' );
}

$wgExpensiveParserFunctionLimit = 1000;  
