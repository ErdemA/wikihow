<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
$wgExtensionMessagesFiles['Misc'] = dirname(__FILE__) . '/Misc.i18n.php';
$wgAutoloadClasses['Misc'] = dirname(__FILE__) . '/Misc.body.php';

//$wgHooks['IsTrustedProxy'][] = array('Misc::checkCloudFlareProxy');
$wgHooks['IsTrustedProxy'][] = array('Misc::checkFastlyProxy');

$wgHooks['ArticleConfirmDelete'][] = array('Misc::getDeleteReasonFromCode');

function checkFastlyProxy() {
	$value = isset($_SERVER[WH_FASTLY_HEADER_NAME]) ? $_SERVER[WH_FASTLY_HEADER_NAME] : '';
	return $value == WH_FASTLY_HEADER_VALUE;
}

function decho($name, $value = "", $html = true) {
	$lineEnd = "<br>\n";
	if (!$html) {
		$lineEnd = "\n";
	}
	if (is_string($value)) {
		echo "$name: $value";
	} else if ((!is_array($value) || !is_object($value)) && method_exists($value, '__toString')) {
		print_r("$name: $value");
	} else {
		echo "$name: ";
		print_r($value);
		echo $lineEnd;
	}

	echo $lineEnd;
}

/* Recursively converts the parameter (an object) to an array with the same data */
// Reuben note: used in AbuseFilter
function wfObjectToArray( $object, $recursive = true ) {                                                    
	$array = array();
	foreach ( get_object_vars($object) as $key => $value ) {
		if ( is_object($value) && $recursive ) {
			$value = wfObjectToArray( $value );
		}

		$array[$key] = $value;
	}

	return $array;
}

// Reuben note: Used by Drafts
function wfGenerateToken( $salt = '' ) {
	$salt = serialize($salt);

	return md5( mt_rand( 0, 0x7fffffff ) . $salt );
}   

// Generate a link to our external CDN
function wfGetPad($relurl = '') {
	global $wgServer, $wgIsDomainTest, $wgRequest, $wgSSLsite, $wgIsStageHost;

	$isCanonicalServer = $wgServer == 'http://www.wikihow.com' ||
		$wgServer == 'http://m.wikihow.com' ||
		$wgIsDomainTest;
	$isCachedCopy = $wgRequest && $wgRequest->getVal('c') == 't';

	// Special case for www.wikihow.com urls being requested for international
	if (!IS_PROD_EN_SITE && preg_match('@http://www.wikihow.com@', $relurl)) {
		$relurl = str_replace('http://www.wikihow.com', '', $relurl);
	} else {
		// Don't translate CDN URLs in 4 cases:
		//  (1) if the URL is non-relative (starts with http://),
		//  (2) if the hostname of the machine doesn't end in .wikihow.com and
		//  (3) the site is being served via SSL/https (to get around 
		//      mixed content issues with chrome)
		//  (4) if the image being requested is from an international server
		if (preg_match('@^https?://@i', $relurl) ||
			$isCachedCopy ||
			(!$isCanonicalServer &&
			 (!preg_match('@\.wikihow\.com$@', @$_ENV['HOSTNAME']) ||
			  $wgSSLsite ||
			  $wgIsStageHost ||
			  !IS_PROD_EN_SITE)))
		{
			return $relurl;
		}
	}

	$numPads = 3;
	// Mask out sign or upper bits to make 32- and 64-bit machines produce
	// uniform results.
	$crc = crc32($relurl) & 0x7fffffff;
	$pad = ($crc % $numPads) + 1;
	$prefix = 'pad';

	return "http://{$prefix}{$pad}.whstatic.com{$relurl}";
	// Code to send half of the requests to one CDN then half to the other
	/*
	global $wgTitle, $wgLanguageCode;
	if ($wgLanguageCode != 'en') {
		return "http://{$prefix}{$pad}.whstatic.com{$relurl}";
	} elseif (preg_match('@^/images/(.*)$@', $relurl, $m)) {
		$rest = $m[1];
		$title = $wgTitle && strlen($wgTitle->getText()) > 0 ? $wgTitle->getText() : 'Z';
		if (ord($title{0}) <= ord('D')) {
			return "http://d1cu6f3ciowfok.cloudfront.net/images_en/" . $rest;
		} else {
			return "http://{$prefix}{$pad}.whstatic.com{$relurl}";
		}
	}
	return $relurl;
	*/
}

/* function wfStrr_replace($text, $find, $replace) {
	$i = strrpos($text, $find);
	if ($i === false)
		return $text;
	#echo $text . "\n--------\n" . substr($text, 0, $i) . "\n--------\n" . substr($text, $i+strlen($find));
	$s = substr($text, 0, $i)
			. $replace
			. substr($text, $i+strlen($find));  
	#echo "\n---------\n\n{$s}\n"; exit;
	return $s;
} */

/**
 * Returns unified plain-text diff of two texts.
 * Useful for machine processing of diffs.
 * @param $before String: the text before the changes.
 * @param $after String: the text after the changes.
 * @param $params String: command-line options for the diff command.
 * @return String: unified diff of $before and $after
 */
// Reuben note: used in AbuseFilter
function wfDiff( $before, $after, $params = '-u' ) {
	if ($before == $after) {
		return '';
	}

	global $wgDiff;

	# This check may also protect against code injection in
	# case of broken installations.
	if( !file_exists( $wgDiff ) ){
		wfDebug( "diff executable not found\n" );
		$diffs = new Diff( explode( "\n", $before ), explode( "\n", $after ) );
		$format = new UnifiedDiffFormatter();
		return $format->format( $diffs );
	}

	# Make temporary files
	$td = wfTempDir();
	$oldtextFile = fopen( $oldtextName = tempnam( $td, 'merge-old-' ), 'w' );
	$newtextFile = fopen( $newtextName = tempnam( $td, 'merge-your-' ), 'w' );

	fwrite( $oldtextFile, $before ); fclose( $oldtextFile );
	fwrite( $newtextFile, $after ); fclose( $newtextFile );

	// Get the diff of the two files
	$cmd = "$wgDiff " . $params . ' ' .wfEscapeShellArg( $oldtextName, $newtextName );

	$h = popen( $cmd, 'r' );

	$diff = '';

	do {
		$data = fread( $h, 8192 );
		if ( strlen( $data ) == 0 ) {
			break;
		}
		$diff .= $data;
	} while ( true );

	// Clean up
	pclose( $h );
	unlink( $oldtextName );
	unlink( $newtextName );

	// Kill the --- and +++ lines. They're not useful.
	$diff_lines = explode( "\n", $diff );
	if (strpos( $diff_lines[0], '---' ) === 0) {
		unset($diff_lines[0]);
	}
	if (strpos( $diff_lines[1], '+++' ) === 0) {
		unset($diff_lines[1]);
	}

	$diff = implode( "\n", $diff_lines );

	return $diff;
}

/*
 * Function written by Travis. Takes a date (in a string format such that
 * php's strtotime() function will work with it) or a unix timestamp
 * (if you pass in $isUnixTimestamp == true) and converts to format
 * "x Days/Seconds/Minutes Ago" format relative to current date. 
 */
function wfTimeAgo($date, $isUnixTimestamp = false) {
	// INTL: Use the internationalized time function based off the original wfTimeAgo
	return Misc::getDTDifferenceString($date, $isUnixTimestamp);
}

function wfFlattenArrayCategoryKeys($arg, &$results = array()) {
	if (is_array($arg)) {
		foreach ($arg as $a=>$p) {
			$results[] = $a;
			if (is_array($p)) {
			   wfFlattenArrayCategoryKeys($p, $results);
			}                                                                                               
		}
	}
	return $results;
}

