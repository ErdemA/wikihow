<?php
/**
 * See deferred.txt
 */

/**
 *
 */
class SquidUpdate {
	var $urlArr, $mMaxTitles;

	function __construct( $urlArr = Array(), $maxTitles = false ) {
		global $wgMaxSquidPurgeTitles;
		if ( $maxTitles === false ) {
			$this->mMaxTitles = $wgMaxSquidPurgeTitles;
		} else {
			$this->mMaxTitles = $maxTitles;
		}
		if ( count( $urlArr ) > $this->mMaxTitles ) {
			$urlArr = array_slice( $urlArr, 0, $this->mMaxTitles );
		}
		$this->urlArr = $urlArr;
	}

	static function newFromLinksTo( &$title ) {
		$fname = 'SquidUpdate::newFromLinksTo';
		wfProfileIn( $fname );

		# Get a list of URLs linking to this page
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select( array( 'links', 'page' ),
			array( 'page_namespace', 'page_title' ),
			array(
				'pl_namespace' => $title->getNamespace(),
				'pl_title'     => $title->getDBkey(),
				'pl_from=page_id' ),
			$fname );
		$blurlArr = $title->getSquidURLs();
		if ( $dbr->numRows( $res ) <= $this->mMaxTitles ) {
			while ( $BL = $dbr->fetchObject ( $res ) )
			{
				$tobj = Title::makeTitle( $BL->page_namespace, $BL->page_title ) ;
				$blurlArr[] = $tobj->getInternalURL();
			}
		}
		$dbr->freeResult ( $res ) ;

		wfProfileOut( $fname );
		return new SquidUpdate( $blurlArr );
	}

	static function newFromTitles( &$titles, $urlArr = array() ) {
		global $wgMaxSquidPurgeTitles;
		if ( count( $titles ) > $wgMaxSquidPurgeTitles ) {
			$titles = array_slice( $titles, 0, $wgMaxSquidPurgeTitles );
		}
		foreach ( $titles as $title ) {
			$urlArr[] = $title->getInternalURL();
		}
		return new SquidUpdate( $urlArr );
	}

	static function newSimplePurge( &$title ) {
		$urlArr = $title->getSquidURLs();
		return new SquidUpdate( $urlArr );
	}

	function doUpdate() {
		SquidUpdate::purge( $this->urlArr );
	}

	/* Purges a list of Squids defined in $wgSquidServers.
	$urlArr should contain the full URLs to purge as values
	(example: $urlArr[] = 'http://my.host/something')
	XXX report broken Squids per mail or log */

static function logit($str) {
//error_log("$str\n", 3, '/tmp/purge.txt');
}

	static function purge( $urlArr ) {
		global $wgSquidServers, $wgHTCPMulticastAddress, $wgHTCPPort;

self::logit('urls: ' . preg_replace('@\s+@', ' ', print_r($urlArr,true)));
		/*if ( (@$wgSquidServers[0]) == 'echo' ) {
			echo implode("<br />\n", $urlArr) . "<br />\n";
			return;
		}*/
		
		if( empty( $urlArr ) ) {
			return;
		}

		// hack - ignore the stupid user kudos pages that take up so much time!
		$good = array();
		foreach ($urlArr as $u) {
			if (!preg_match("@User_kudos@", $u)) {
				$good[] = $u;
			}
		}
		$urlArr = $good; 
		// end hack

		if ( $wgHTCPMulticastAddress && $wgHTCPPort ) {
			return SquidUpdate::HTCPPurge( $urlArr );
		}

		$fname = 'SquidUpdate::purge';
		wfProfileIn( $fname );

		//wfDebug(date("r") . wfBacktrace());
		wfDebug(date("r") . " PURGE starting " . print_r($urlArr, true) . "\n");
		$maxsocketspersquid = 8; //  socket cap per Squid
		$urlspersocket = 400; // 400 seems to be a good tradeoff, opening a socket takes a while
		$firsturl = SquidUpdate::expand( $urlArr[0] );
		unset($urlArr[0]);
		$urlArr = array_values($urlArr);
		$sockspersq =  max(ceil(count($urlArr) / $urlspersocket ),1);
		if ($sockspersq == 1) {
			/* the most common case */
			$urlspersocket = count($urlArr);
			wfDebug(date("r") . " PURGE setting urlspersocket to the count of urlArr\n");
		} elseif ($sockspersq > $maxsocketspersquid ) {
			$urlspersocket = ceil(count($urlArr) / $maxsocketspersquid);
			$sockspersq = $maxsocketspersquid;
			wfDebug(date("r") . " PURGE setting urlspersocket to the ceil " . count($urlArr) . " and $maxsocketspersquid\n");
		}
		$totalsockets = count($wgSquidServers) * $sockspersq;
		$sockets = array();
		$isFastlyPurge = array();

		/* this sets up the sockets and tests the first socket for each server. */
		for ($ss=0;$ss < count($wgSquidServers);$ss++) {
			$failed = false;
			$so = 0;
			while ($so < $sockspersq && !$failed) {
				if ($so == 0) {
					/* first socket for this server, do the tests */
					@list($server, $port) = explode(':', $wgSquidServers[$ss]);
					if (!isset($port)) $port = 80;
					$isFastly = $server == WH_FASTLY_API_SERVER;				
					#$this->debug("Opening socket to $server:$port");
					$error = $errstr = false;
					$socket = @fsockopen($server, $port, $error, $errstr, 3);
					#$this->debug("\n");
					if (!$socket) {
						$failed = true;
						$totalsockets -= $sockspersq;
					} else {
						// tmp testing by reuben -- june 7 2013. see purge below too. this may
						// become permanent
						if (false // !$isFastly
							|| !preg_match('@^https?://([^/]+)(/.*)$@', $firsturl, $m))
						{
							$msg = "PURGE $firsturl HTTP/1.0\r\n".
								"Connection: Keep-Alive\r\n\r\n";
						} else {
							$host = $m[1];
							$loc = $m[2];
							$msg = "PURGE $loc HTTP/1.0\r\n".
								"Host: $host\r\n".
								"Accept: */*\r\n".
								"Connection: Keep-Alive\r\n\r\n";
						}
self::logit('req (' . $wgSquidServers[$ss] . ', ' . $ss . ') : ' . preg_replace('@\s+@m', ' ', $msg));
						wfDebug(date('r') . " PURGE -- first request $firsturl\n");
						wfDebug(date('r') . " req headers: " . $msg);
						@fputs($socket,$msg);
						#$this->debug("...");
						$res = @fread($socket,512);
						#$this->debug("\n");
						/* Squid only returns http headers with 200 or 404 status,
						if there's more returned something's wrong 
						Check separately for Varnish and Fastly responses
						b/c each is different.
						 */
						wfDebug(date('r') . " PURGE result: " . print_r($res, true) . "\n");
						$isVarnishOK = (strpos($res, '200 Purged') !== false);
						$isFastlyOK = (strpos($res, '"status": "ok"') !== false);
						if (!$isVarnishOK && !$isFastlyOK) {
							fclose($socket);
							$failed = true;
							$totalsockets -= $sockspersq;
						} else {
							@stream_set_blocking($socket,false);
							$sockets[] = $socket;
							$isFastlyPurge[] = $isFastly;
						}
					}
				} else {
					/* open the remaining sockets for this server */		
					list($server, $port) = explode(':', $wgSquidServers[$ss]);
					if(!isset($port)) $port = 80;
					$isFastlyPurge[$so+1] = $server == WH_FASTLY_API_SERVER;
					$sockets[$so+1] = @fsockopen($server, $port, $error, $errstr, 2);
					@stream_set_blocking($sockets[$so+1],false);
				}
				$so++;
			}
		}

		wfDebug(date("r") . " PURGE about to do the heavy lifting urlspersocket $urlspersocket\n"); 

		if ($urlspersocket > 0) {
			/* now do the heavy lifting. The fread() relies on Squid returning only the headers */
			for ($r=0;$r < $urlspersocket;$r++) {
				for ($s=0;$s < $totalsockets;$s++) {
					if($r != 0) {
						$res = '';
						$esc = 0;
						while (strlen($res) < 100 && $esc < 200) {
							$res .= @fread($sockets[$s],512);
							$esc++;
							usleep(20);
						}
					}
					$urindex = $r + $urlspersocket * ($s - $sockspersq * floor($s / $sockspersq));
					$url = SquidUpdate::expand( $urlArr[$urindex] );
					// tmp testing by reuben -- this may become permanent
					if (false // !$isFastlyPurge[$s]
						|| !preg_match('@^https?://([^/]+)(/.*)$@', $url, $m))
					{
						$msg = "PURGE $url HTTP/1.0\r\n".
							"Connection: Keep-Alive\r\n\r\n";
					} else {
						$host = $m[1];
						$loc = $m[2];
						$msg = "PURGE $loc HTTP/1.0\r\n".
							"Host: $host\r\n".
							"Accept: */*\r\n".
							"Connection: Keep-Alive\r\n\r\n";
					}
self::logit('req (' . $s . ') : ' . preg_replace('@\s+@m', ' ', $msg));
					wfDebug(date('r') . " PURGE -- request $url\n");
					wfDebug(date('r') . " req headers: " . $msg);
					#$this->debug($msg);
					@fputs($sockets[$s],$msg);
					#$this->debug("\n");
				}
			}
		}
		#$this->debug("Reading response...");
		foreach ($sockets as $i => $socket) {
			$res = '';
			$esc = 0;
			while (strlen($res) < 100 && $esc < 200) {
				$res .= @fread($socket,1024);
				$esc++;
				usleep(20);
			}

self::logit('resp (' . $i . ') : ' . preg_replace('@\s+@m', ' ', $res));
			wfDebug(date('r') . " PURGE response: $res\n");
			@fclose($socket);
		}
		#$this->debug("\n");
		wfProfileOut( $fname );
	}

	static function HTCPPurge( $urlArr ) {
		global $wgHTCPMulticastAddress, $wgHTCPMulticastTTL, $wgHTCPPort;
		$fname = 'SquidUpdate::HTCPPurge';
		wfProfileIn( $fname );

		$htcpOpCLR = 4;                 // HTCP CLR

		// FIXME PHP doesn't support these socket constants (include/linux/in.h)
		if( !defined( "IPPROTO_IP" ) ) {
			define( "IPPROTO_IP", 0 );
			define( "IP_MULTICAST_LOOP", 34 );
			define( "IP_MULTICAST_TTL", 33 );
		}

		// pfsockopen doesn't work because we need set_sock_opt
	        $conn = socket_create( AF_INET, SOCK_DGRAM, SOL_UDP );
		if ( $conn ) {
			// Set socket options
			socket_set_option( $conn, IPPROTO_IP, IP_MULTICAST_LOOP, 0 );
			if ( $wgHTCPMulticastTTL != 1 )
				socket_set_option( $conn, IPPROTO_IP, IP_MULTICAST_TTL,
					$wgHTCPMulticastTTL );

			foreach ( $urlArr as $url ) {
				if( !is_string( $url ) ) {
					wfDebugDieBacktrace( 'Bad purge URL' );
				}
				$url = SquidUpdate::expand( $url );
				
				// Construct a minimal HTCP request diagram
				// as per RFC 2756
				// Opcode 'CLR', no response desired, no auth
				$htcpTransID = rand();

				$htcpSpecifier = pack( 'na4na*na8n',
					4, 'HEAD', strlen( $url ), $url,
					8, 'HTTP/1.0', 0 );

				$htcpDataLen = 8 + 2 + strlen( $htcpSpecifier );
				$htcpLen = 4 + $htcpDataLen + 2;

				// Note! Squid gets the bit order of the first
				// word wrong, wrt the RFC. Apparently no other
				// implementation exists, so adapt to Squid
				$htcpPacket = pack( 'nxxnCxNxxa*n',
					$htcpLen, $htcpDataLen, $htcpOpCLR,
					$htcpTransID, $htcpSpecifier, 2);

				// Send out
				wfDebug( "Purging URL $url via HTCP\n" );
				socket_sendto( $conn, $htcpPacket, $htcpLen, 0,
					$wgHTCPMulticastAddress, $wgHTCPPort );
			}
		} else {
			$errstr = socket_strerror( socket_last_error() );
			wfDebug( "SquidUpdate::HTCPPurge(): Error opening UDP socket: $errstr\n" );
		}
		wfProfileOut( $fname );
	}

	function debug( $text ) {
		global $wgDebugSquid;
		if ( $wgDebugSquid ) {
			wfDebug( $text );
		}
	}
	
	/**
	 * Expand local URLs to fully-qualified URLs using the internal protocol
	 * and host defined in $wgInternalServer. Input that's already fully-
	 * qualified will be passed through unchanged.
	 *
	 * This is used to generate purge URLs that may be either local to the
	 * main wiki or include a non-native host, such as images hosted on a
	 * second internal server.
	 *
	 * Client functions should not need to call this.
	 *
	 * @return string
	 */
	static function expand( $url ) {
		global $wgInternalServer;
		if( $url != '' && $url{0} == '/' ) {
			return $wgInternalServer . $url;
		}
		return $url;
	}
}

