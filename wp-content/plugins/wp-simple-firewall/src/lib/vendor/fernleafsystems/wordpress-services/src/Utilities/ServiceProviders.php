<?php

namespace FernleafSystems\Wordpress\Services\Utilities;

use FernleafSystems\Wordpress\Services\Services;

/**
 * Class ServiceProviders
 * @package FernleafSystems\Wordpress\Services\Utilities
 */
class ServiceProviders {

	const URL_IPS_STATUSCAKE = 'https://app.statuscake.com/Workfloor/Locations.php?format=json';
	const URL_IPS_CLOUDFLARE = 'https://www.cloudflare.com/ips-v%s';
	const URL_IPS_ICONTROLWP = 'https://serviceips.icontrolwp.com/';
	const URL_IPS_MANAGEWP = 'https://managewp.com/wp-content/uploads/2016/11/managewp-ips.txt';
	const URL_IPS_PINGDOM = 'https://my.pingdom.com/probes/ipv%s';
	const URL_IPS_UPTIMEROBOT = 'https://uptimerobot.com/inc/files/ips/IPv%s.txt';

	/**
	 * @return string[]
	 */
	public function getAllCrawlerUseragents() {
		return [
			'Applebot/',
			'baidu',
			'bingbot',
			'Googlebot',
			'APIs-Google',
			'AdsBot-Google',
			'Mediapartners-Google',
			'yandex.com/bots',
			'yahoo!'
		];
	}

	/**
	 * @return string[][]
	 */
	public function getIps_CloudFlare() {
		$oWp = Services::WpGeneral();

		$sStoreKey = $this->getPrefixedStoreKey( 'serviceips_cloudflare' );
		$aIps = $oWp->getTransient( $sStoreKey );
		if ( empty( $aIps ) ) {
			$aIps = [
				4 => $this->downloadServiceIps_Cloudflare( 4 ),
				6 => $this->downloadServiceIps_Cloudflare( 6 )
			];
			$oWp->setTransient( $sStoreKey, $aIps, WEEK_IN_SECONDS*4 );
		}
		return $aIps;
	}

	/**
	 * @param string $sIp
	 */
	private function addSucuriIp( $sIp ) {
		$aIps = $this->getIps_Sucuri( false );
		$nVersion = Services::IP()->getIpVersion( $sIp );
		if ( !in_array( $sIp, $aIps[ $nVersion ] ) ) {
			$aIps[ $nVersion ][] = $sIp;
			Services::WpGeneral()
					->setTransient(
						$this->getPrefixedStoreKey( 'serviceips_sucuri' ),
						$aIps,
						WEEK_IN_SECONDS*8
					);
		}
	}

	/**
	 * @param bool $bFlat
	 * @return string[][]
	 */
	public function getIps_Sucuri( $bFlat = false ) {
		$oWp = Services::WpGeneral();

		$sStoreKey = $this->getPrefixedStoreKey( 'serviceips_sucuri' );
		$aIps = $oWp->getTransient( $sStoreKey );
		if ( empty( $aIps ) ) {
			$aIps = [
				4 => [],
				6 => []
			];
			$oWp->setTransient( $sStoreKey, $aIps, WEEK_IN_SECONDS*8 );
		}
		return $bFlat ? array_merge( $aIps[ 4 ], $aIps[ 6 ] ) : $aIps;
	}

	/**
	 * @param string $sIp
	 * @return bool
	 */
	public function isIp_Sucuri( $sIp ) {
		$bIsSucuri = false;

		if ( Services::IP()->isViablePublicVisitorIp( $sIp ) ) {
			$aIps = $this->getIps_Sucuri( true );
			if ( in_array( $sIp, $aIps ) ) {
				$bIsSucuri = true;
			}
			else if ( $this->verifyIp_Sucuri( $sIp ) ) {
				$this->addSucuriIp( $sIp );
			}
		}

		return $bIsSucuri;
	}

	/**
	 * @return string[]
	 */
	public function getIps_CloudFlareV4() {
		$aIps = $this->getIps_CloudFlare();
		return $aIps[ 4 ];
	}

	/**
	 * @return string[]
	 */
	public function getIps_CloudFlareV6() {
		$aIps = $this->getIps_CloudFlare();
		return $aIps[ 6 ];
	}

	/**
	 * @return string[]
	 */
	public function getIps_DuckDuckGo() {
		return [ '107.20.237.51', '23.21.226.191', '107.21.1.8', '54.208.102.37' ];
	}

	/**
	 * @param bool $bFlat
	 * @return string[][]|string[]
	 */
	public function getIps_iControlWP( $bFlat = false ) {
		$oWp = Services::WpGeneral();

		$sStoreKey = $this->getPrefixedStoreKey( 'serviceips_icontrolwp' );
		$aIps = $oWp->getTransient( $sStoreKey );
		if ( !is_array( $aIps ) ) {
			$aIps = @json_decode( Services::HttpRequest()->getContent( self::URL_IPS_ICONTROLWP ), true );
			$aIps = is_array( $aIps ) ? $aIps : [ 4 => [], 6 => [] ];
			$oWp->setTransient( $sStoreKey, $aIps, WEEK_IN_SECONDS*2 );
		}
		return $bFlat ? array_merge( $aIps[ 4 ], $aIps[ 6 ] ) : $aIps;
	}

	/**
	 * @return string[]
	 */
	public function getIps_ManageWp() {
		$oWp = Services::WpGeneral();

		$sStoreKey = $this->getPrefixedStoreKey( 'serviceips_managewp' );
		$aIps = $oWp->getTransient( $sStoreKey );
		if ( empty( $aIps ) ) {
			$aIps = $this->downloadServiceIps_Standard( self::URL_IPS_MANAGEWP );
			$oWp->setTransient( $sStoreKey, $aIps, WEEK_IN_SECONDS*4 );
		}
		return $aIps;
	}

	/**
	 * @param bool $bFlat
	 * @return string[][]|string[]
	 */
	public function getIps_Pingdom( $bFlat = false ) {
		$oWp = Services::WpGeneral();

		$sStoreKey = $this->getPrefixedStoreKey( 'serviceips_pingdom' );
		$aIps = $oWp->getTransient( $sStoreKey );
		if ( empty( $aIps ) ) {
			$aIps = [
				4 => $this->downloadServiceIps_Pingdom( 4 ),
				6 => $this->downloadServiceIps_Pingdom( 6 )
			];
			$oWp->setTransient( $sStoreKey, $aIps, WEEK_IN_SECONDS*4 );
		}
		return $bFlat ? array_merge( $aIps[ 4 ], $aIps[ 6 ] ) : $aIps;
	}

	/**
	 * @return string[]
	 */
	public function getIps_Statuscake() {
		$oWp = Services::WpGeneral();

		$sStoreKey = $this->getPrefixedStoreKey( 'serviceips_statuscake' );
		$aIps = $oWp->getTransient( $sStoreKey );
		if ( !is_array( $aIps ) ) {
			$aIps = [];
			$aData = @json_decode( Services::HttpRequest()->getContent( self::URL_IPS_STATUSCAKE ), true );
			if ( is_array( $aData ) ) {
				$aIps = array_values( array_filter( array_map(
					function ( $aItem ) {
						return empty( $aItem[ 'ip' ] ) ? null : $aItem[ 'ip' ];
					},
					$aData
				) ) );
			}
			$oWp->setTransient( $sStoreKey, $aIps, WEEK_IN_SECONDS*4 );
		}
		return $aIps;
	}

	/**
	 * @param bool $bFlat - false for segregated IPv4 and IPv6
	 * @return string[][]|string[]
	 */
	public function getIps_UptimeRobot( $bFlat = false ) {
		$oWp = Services::WpGeneral();

		$sStoreKey = $this->getPrefixedStoreKey( 'serviceips_uptimerobot' );
		$aIps = $oWp->getTransient( $sStoreKey );
		if ( empty( $aIps ) ) {
			$aIps = [
				4 => $this->downloadServiceIps_UptimeRobot( 4 ),
				6 => $this->downloadServiceIps_UptimeRobot( 6 )
			];
			$oWp->setTransient( $sStoreKey, $aIps, WEEK_IN_SECONDS*4 );
		}
		return $bFlat ? array_merge( $aIps[ 4 ], $aIps[ 6 ] ) : $aIps;
	}

	/**
	 * @param string $sIp
	 * @param string $sUserAgent
	 * @return bool
	 */
	public function isIp_AppleBot( $sIp, $sUserAgent ) {
		$oWp = Services::WpGeneral();

		$sStoreKey = $this->getPrefixedStoreKey( 'serviceips_applebot' );
		$aIps = $oWp->getTransient( $sStoreKey );
		if ( !is_array( $aIps ) ) {
			$aIps = [];
		}

		if ( !in_array( $sIp, $aIps ) && $this->verifyIp_AppleBot( $sIp, $sUserAgent ) ) {
			$aIps[] = $sIp;
			$oWp->setTransient( $sStoreKey, $aIps, WEEK_IN_SECONDS*4 );
		}

		return in_array( $sIp, $aIps );
	}

	/**
	 * @param string $sIp
	 * @param string $sUserAgent
	 * @return bool
	 */
	public function isIp_BaiduBot( $sIp, $sUserAgent ) {
		$oWp = Services::WpGeneral();

		$sStoreKey = $this->getPrefixedStoreKey( 'serviceips_baidubot' );
		$aIps = $oWp->getTransient( $sStoreKey );
		if ( !is_array( $aIps ) ) {
			$aIps = [];
		}

		if ( !in_array( $sIp, $aIps ) && $this->verifyIp_BaiduBot( $sIp, $sUserAgent ) ) {
			$aIps[] = $sIp;
			$oWp->setTransient( $sStoreKey, $aIps, WEEK_IN_SECONDS*4 );
		}

		return in_array( $sIp, $aIps );
	}

	/**
	 * @param string $sIp
	 * @param string $sUserAgent
	 * @return bool
	 */
	public function isIp_BingBot( $sIp, $sUserAgent ) {
		$oWp = Services::WpGeneral();

		$sStoreKey = $this->getPrefixedStoreKey( 'serviceips_bingbot' );
		$aIps = $oWp->getTransient( $sStoreKey );
		if ( !is_array( $aIps ) ) {
			$aIps = [];
		}

		if ( !in_array( $sIp, $aIps ) && $this->verifyIp_BingBot( $sIp, $sUserAgent ) ) {
			$aIps[] = $sIp;
			$oWp->setTransient( $sStoreKey, $aIps, WEEK_IN_SECONDS*4 );
		}

		return in_array( $sIp, $aIps );
	}

	/**
	 * @param string $sIp
	 * @return bool
	 */
	public function isIp_Cloudflare( $sIp ) {
		$bIs = false;
		try {
			$oIp = Services::IP();
			$nVersion = $oIp->getIpVersion( $sIp );
			if ( in_array( $nVersion, [ 4, 6 ] ) ) {
				$bIs = $oIp->checkIp( $sIp, $this->getIps_CloudFlare()[ $nVersion ] );
			}
		}
		catch ( \Exception $oE ) {
		}
		return $bIs;
	}

	/**
	 * https://duckduckgo.com/duckduckbot
	 * @param string $sIp
	 * @param string $sUserAgent
	 * @return bool
	 */
	public function isIp_DuckDuckGoBot( $sIp, $sUserAgent ) {
		$bIsBot = false;
		// We check the useragent if available
		if ( is_null( $sUserAgent ) || stripos( $sUserAgent, 'DuckDuckBot' ) !== false ) {
			$bIsBot = in_array( $sIp, $this->getIps_DuckDuckGo() );
		}
		return $bIsBot;
	}

	/**
	 * @param string $sIp
	 * @param string $sAgent
	 * @return bool
	 */
	public function isIp_iControlWP( $sIp, $sAgent = null ) { //TODO: Agent
		$bIsBot = false;
		if ( is_null( $sAgent ) || stripos( $sAgent, 'iControlWPApp' ) !== false ) {
			$bIsBot = in_array( $sIp, $this->getIps_iControlWP( true ) );
		}
		return $bIsBot;
	}

	/**
	 * https://support.google.com/webmasters/answer/80553?hl=en
	 * @param string $sIp
	 * @param string $sUserAgent
	 * @return bool
	 */
	public function isIp_GoogleBot( $sIp, $sUserAgent ) {
		$oWp = Services::WpGeneral();

		$sStoreKey = $this->getPrefixedStoreKey( 'serviceips_googlebot' );
		$aIps = $oWp->getTransient( $sStoreKey );
		if ( !is_array( $aIps ) ) {
			$aIps = [];
		}

		if ( !in_array( $sIp, $aIps ) && $this->verifyIp_GoogleBot( $sIp, $sUserAgent ) ) {
			$aIps[] = $sIp;
			$oWp->setTransient( $sStoreKey, $aIps, WEEK_IN_SECONDS*4 );
		}

		return in_array( $sIp, $aIps );
	}

	/**
	 * @param string $sIp
	 * @param string $sAgent
	 * @return bool
	 */
	public function isIp_Statuscake( $sIp, $sAgent ) {
		return ( stripos( $sAgent, 'StatusCake' ) !== false )
			   && in_array( $sIp, $this->getIps_Statuscake() );
	}

	/**
	 * @param string $sIp
	 * @param string $sAgent
	 * @return bool
	 */
	public function isIp_Pingdom( $sIp, $sAgent ) {
		return ( stripos( $sAgent, 'pingdom.com' ) !== false )
			   && in_array( $sIp, $this->getIps_Pingdom( true ) );
	}

	/**
	 * @param string $sIp
	 * @param string $sAgent
	 * @return bool
	 */
	public function isIp_UptimeRobot( $sIp, $sAgent ) {
		return ( stripos( $sAgent, 'UptimeRobot' ) !== false )
			   && in_array( $sIp, $this->getIps_UptimeRobot( true ) );
	}

	/**
	 * https://yandex.com/support/webmaster/robot-workings/check-yandex-robots.html
	 * @param string $sIp
	 * @param string $sUserAgent
	 * @return bool
	 */
	public function isIp_YandexBot( $sIp, $sUserAgent ) {
		$oWp = Services::WpGeneral();

		$sStoreKey = $this->getPrefixedStoreKey( 'serviceips_yandexbot' );
		$aIps = $oWp->getTransient( $sStoreKey );
		if ( !is_array( $aIps ) ) {
			$aIps = [];
		}

		if ( !in_array( $sIp, $aIps ) && $this->verifyIp_YandexBot( $sIp, $sUserAgent ) ) {
			$aIps[] = $sIp;
			$oWp->setTransient( $sStoreKey, $aIps, WEEK_IN_SECONDS*4 );
		}

		return in_array( $sIp, $aIps );
	}

	/**
	 * https://yandex.com/support/webmaster/robot-workings/check-yandex-robots.html
	 * @param string $sIp
	 * @param string $sUserAgent
	 * @return bool
	 */
	public function isIp_YahooBot( $sIp, $sUserAgent ) {
		$oWp = Services::WpGeneral();

		$sStoreKey = $this->getPrefixedStoreKey( 'serviceips_yahoobot' );
		$aIps = $oWp->getTransient( $sStoreKey );
		if ( !is_array( $aIps ) ) {
			$aIps = [];
		}

		if ( !in_array( $sIp, $aIps ) && $this->verifyIp_YahooBot( $sIp, $sUserAgent ) ) {
			$aIps[] = $sIp;
			$oWp->setTransient( $sStoreKey, $aIps, WEEK_IN_SECONDS*4 );
		}

		return in_array( $sIp, $aIps );
	}

	/**
	 * https://support.apple.com/en-gb/HT204683
	 * https://discussions.apple.com/thread/7090135
	 * Apple IPs start with '17.'
	 * @param string $sIp
	 * @param string $sUserAgent
	 * @return bool
	 */
	private function verifyIp_AppleBot( $sIp, $sUserAgent = '' ) {
		return ( Services::IP()->getIpVersion( $sIp ) != 4 || strpos( $sIp, '17.' ) === 0 )
			   && $this->isIpOfBot( [ 'Applebot/' ], '#.*\.applebot.apple.com\.?$#i', $sIp, $sUserAgent );
	}

	/**
	 * @param string $sIp
	 * @param string $sUserAgent
	 * @return bool
	 */
	private function verifyIp_BaiduBot( $sIp, $sUserAgent = '' ) {
		return $this->isIpOfBot( [ 'baidu' ], '#.*\.crawl\.baidu\.(com|jp)\.?$#i', $sIp, $sUserAgent );
	}

	/**
	 * @param string $sIp
	 * @param string $sUserAgent
	 * @return bool
	 */
	private function verifyIp_BingBot( $sIp, $sUserAgent = '' ) {
		return $this->isIpOfBot( [ 'bingbot' ], '#.*\.search\.msn\.com\.?$#i', $sIp, $sUserAgent );
	}

	/**
	 * @param string $sIp
	 * @param string $sUserAgent
	 * @return bool
	 */
	private function verifyIp_GoogleBot( $sIp, $sUserAgent = '' ) {
		return $this->isIpOfBot(
			[ 'Googlebot', 'APIs-Google', 'AdsBot-Google', 'Mediapartners-Google' ],
			'#.*\.google(bot)?\.com\.?$#i', $sIp, $sUserAgent
		);
	}

	/**
	 * @param string $sIp
	 * @param string $sUserAgent
	 * @return bool
	 */
	private function verifyIp_YandexBot( $sIp, $sUserAgent = '' ) {
		return $this->isIpOfBot( [ 'yandex.com/bots' ], '#.*\.yandex?\.(com|ru|net)\.?$#i', $sIp, $sUserAgent );
	}

	/**
	 * @param string $sIp
	 * @param string $sUserAgent
	 * @return bool
	 */
	private function verifyIp_YahooBot( $sIp, $sUserAgent = '' ) {
		return $this->isIpOfBot( [ 'yahoo!' ], '#.*\.crawl\.yahoo\.net\.?$#i', $sIp, $sUserAgent );
	}

	/**
	 * @param string $sIp
	 * @return bool
	 */
	private function verifyIp_Sucuri( $sIp ) {
		$sHost = @gethostbyaddr( $sIp ); // returns the ip on failure
		return !empty( $sHost ) && ( $sHost != $sIp )
			   && preg_match( '#.*\.sucuri\.net\.?$#i', $sHost )
			   && gethostbyname( $sHost ) === $sIp;
	}

	/**
	 * Will test useragent, then attempt to resolve to hostname and back again
	 * https://www.elephate.com/detect-verify-crawlers/
	 * @param array  $aBotUserAgents
	 * @param string $sBotHostPattern
	 * @param string $sReqIp
	 * @param string $sReqUserAgent
	 * @return bool
	 */
	private function isIpOfBot( $aBotUserAgents, $sBotHostPattern, $sReqIp, $sReqUserAgent = '' ) {
		$bIsBot = false;

		$bCheckIpHost = is_null( $sReqUserAgent );
		if ( !$bCheckIpHost ) {
			$aBotUserAgents = array_map(
				function ( $sAgent ) {
					return preg_quote( $sAgent, '#' );
				},
				$aBotUserAgents
			);
			$bCheckIpHost = (bool)preg_match( sprintf( '#%s#i', implode( '|', $aBotUserAgents ) ), $sReqUserAgent );
		}

		if ( $bCheckIpHost ) {
			$sHost = @gethostbyaddr( $sReqIp ); // returns the ip on failure
			$bIsBot = !empty( $sHost ) && ( $sHost != $sReqIp )
					  && preg_match( $sBotHostPattern, $sHost )
					  && gethostbyname( $sHost ) === $sReqIp;
		}
		return $bIsBot;
	}

	/**
	 * @param int $sIpVersion
	 * @return string[]
	 */
	private function downloadServiceIps_Cloudflare( $sIpVersion = 4 ) {
		return $this->downloadServiceIps_Standard( self::URL_IPS_CLOUDFLARE, $sIpVersion );
	}

	/**
	 * @param int $sIpVersion
	 * @return string[]
	 */
	private function downloadServiceIps_Pingdom( $sIpVersion = 4 ) {
		return $this->downloadServiceIps_Standard( self::URL_IPS_PINGDOM, $sIpVersion );
	}

	/**
	 * @param int $sIpVersion
	 * @return string[]
	 */
	private function downloadServiceIps_UptimeRobot( $sIpVersion = 4 ) {
		return $this->downloadServiceIps_Standard( self::URL_IPS_UPTIMEROBOT, $sIpVersion );
	}

	/**
	 * @param string $sSourceUrl must have an sprintf %s placeholder
	 * @param int    $sIpVersion
	 * @return string[]
	 */
	private function downloadServiceIps_Standard( $sSourceUrl, $sIpVersion = null ) {
		if ( !is_null( $sIpVersion ) ) {
			if ( !in_array( (int)$sIpVersion, [ 4, 6 ] ) ) {
				$sIpVersion = 4;
			}
			$sSourceUrl = Services::HttpRequest()->getContent( sprintf( $sSourceUrl, $sIpVersion ) );
		}
		$sRaw = Services::HttpRequest()->getContent( $sSourceUrl );
		$aIps = empty( $sRaw ) ? [] : explode( "\n", $sRaw );
		return array_values( array_filter( array_map( 'trim', $aIps ) ) );
	}

	/**
	 * @param string $sKey
	 * @return string
	 */
	private function getPrefixedStoreKey( $sKey ) {
		return 'odp_'.$sKey;
	}
}