<?php

# Loader for spam blacklist feature
# Include this from LocalSettings.php

if ( defined( 'MEDIAWIKI' ) ) {

global $wgFilterCallback, $wgPreSpamFilterCallback;
global $wgSpamBlacklistFiles;

global $wgSpamBlacklistSettings;
$wgValidateBlacklist = true;
$wgValidateErrorOffset = 0;
$wgSpamBlacklistFiles = false;
$wgSpamBlacklistSettings = array();

if ( $wgFilterCallback ) {
	$wgPreSpamFilterCallback = $wgFilterCallback;
} else {
	$wgPreSpamFilterCallback = false;
}
$wgFilterCallback = 'wfSpamBlacklistLoader';
$wgExtensionFunctions[] = 'wfSpamBlacklist';

$wgExtensionCredits['other'][] = array(
	'name' => 'SpamBlacklist',
	'author' => 'Tim Starling',
	'url' => 'http://meta.wikimedia.org/wiki/SpamBlacklist_extension',
);

function wfSpamBlacklist() {
	global $wgHooks, $wgValidateBlacklist, $wgMessageCache;
    $wgMessageCache->addMessages(
    array(
            'invalidspamexpression' => 'An error occurred. The spam blacklist or whiltelist you are trying to save is invalid. 
										Please check your additions and try again.',
            'invalidspamexpression_title' => 'Error saving blacklist/whitelist',
        )
    );
    if ($wgValidateBlacklist) {
        $wgHooks['ArticleSave'][] = 'wfValidateSpamblacklist'; 
	}
}


function wfSpamBlacklistLoader( &$title, $text, $section ) {
	require_once( "SpamBlacklist_body.php" );
	static $spamObj = false;
	global $wgSpamBlacklistFiles, $wgSpamBlacklistSettings, $wgPreSpamFilterCallback;

	if ( $spamObj === false ) {
		$spamObj = new SpamBlacklist( $wgSpamBlacklistSettings );
		if ( $wgSpamBlacklistFiles ) {
			$spamObj->files = $wgSpamBlacklistFiles;
			$spamObj->previousFilter = $wgPreSpamFilterCallback;
		}
	}
	return $spamObj->filter( $title, $text, $section );
}

function wfValidateSpamblacklist(&$article, &$user, &$text) {
	require_once( "SpamBlacklist_body.php" );
    global $wgSpamBlacklistFiles, $wgDBname, $wgOut, $wgValidateErrorOffset;
    $t = $article->getTitle();
    foreach ( $wgSpamBlacklistFiles as $fileName ) {
        if ( preg_match( '/^DB: (\w*) (.*)$/', $fileName, $matches ) ) {
            if ( $wgDBname == $matches[1] && $t && $t->getPrefixedDBkey() == $matches[2] ) {
                $lines = split("\n", $text);
                $regexes = SpamBlacklist::buildRegexes($lines);
                foreach ($regexes as $regex) {
                    if (preg_match($regex, "adfasdfasdfasdf") === false) {
                        $wgOut->errorPage('invalidspamexpression_title', 'invalidspamexpression');
                        return false;
                    }
                }
             }
        }
    }
    //check the white list
    if ($t->getPrefixedDBKey() == 'MediaWiki:Spam-whitelist') {
        $lines = split("\n", $text);
        $regexes = SpamBlacklist::buildRegexes($lines);
        foreach ($regexes as $regex) {
            if (preg_match($regex, "adfasdfasdfasdf") === false) {
                $wgOut->errorPage('invalidspamexpression_title', 'invalidspamexpression');
                return false;
             }
         }
    }
    return true;
}
} # End invocation guard
?>
