<?php

use FernleafSystems\Wordpress\Plugin\Shield\Databases\Traffic;
use FernleafSystems\Wordpress\Plugin\Shield\Modules\BaseShield\ShieldProcessor;
use FernleafSystems\Wordpress\Plugin\Shield\Modules\Traffic\Options;
use FernleafSystems\Wordpress\Services\Services;

class ICWP_WPSF_Processor_TrafficLogger extends ShieldProcessor {

	public function onModuleShutdown() {
		if ( $this->getIfLogRequest() ) {
			$this->logTraffic();
		}
		parent::onModuleShutdown();
	}

	/**
	 * @return bool
	 */
	protected function getIfLogRequest() {
		/** @var ICWP_WPSF_FeatureHandler_Traffic $oMod */
		$oMod = $this->getMod();
		/** @var Options $oOpts */
		$oOpts = $this->getOptions();
		$oWp = Services::WpGeneral();
		$bLoggedIn = Services::WpUsers()->isUserLoggedIn();
		return parent::getIfLogRequest()
			   && !$this->getCon()->isPluginDeleting()
			   && ( $oOpts->getMaxEntries() > 0 )
			   && ( !$this->isCustomExcluded() )
			   && ( $oMod->isIncluded_Simple() || count( Services::Request()->getRawRequestParams( false ) ) > 0 )
			   && ( $oMod->isIncluded_LoggedInUser() || !$bLoggedIn )
			   && ( $oMod->isIncluded_Ajax() || !$oWp->isAjax() )
			   && ( $oMod->isIncluded_Cron() || !$oWp->isCron() )
			   && (
				   $bLoggedIn || // only run these service IP checks if not logged in.
				   (
					   ( $oMod->isIncluded_Search() || !$this->isServiceIp_Search() )
					   && ( $oMod->isIncluded_Uptime() || !$this->isServiceIp_Uptime() )
				   )
			   );
	}

	/**
	 * @return bool
	 */
	protected function isCustomExcluded() {
		/** @var \ICWP_WPSF_FeatureHandler_Traffic $oMod */
		$oMod = $this->getMod();
		$oReq = Services::Request();

		$sAgent = $oReq->getUserAgent();
		$sPath = $oReq->getPath().( empty( $_GET ) ? '' : '?'.http_build_query( $_GET ) );

		$bExcluded = false;
		foreach ( $oMod->getCustomExclusions() as $sExcl ) {
			if ( stripos( $sAgent, $sExcl ) !== false || stripos( $sPath, $sExcl ) !== false ) {
				$bExcluded = true;
			}
		}
		return $bExcluded;
	}

	/**
	 * @return bool
	 */
	protected function isServiceIp_Search() {
		$oSP = $this->loadServiceProviders();

		$sIp = Services::IP()->getRequestIp();
		$sAgent = (string)Services::Request()->getUserAgent();
		return $oSP->isIp_GoogleBot( $sIp, $sAgent )
			   || $oSP->isIp_BingBot( $sIp, $sAgent )
			   || $oSP->isIp_DuckDuckGoBot( $sIp, $sAgent )
			   || $oSP->isIp_YandexBot( $sIp, $sAgent )
			   || $oSP->isIp_BaiduBot( $sIp, $sAgent )
			   || $oSP->isIp_YahooBot( $sIp, $sAgent )
			   || $oSP->isIp_AppleBot( $sIp, $sAgent );
	}

	/**
	 * @return bool
	 */
	protected function isServiceIp_Uptime() {
		$oSP = $this->loadServiceProviders();

		$sIp = Services::IP()->getRequestIp();
		$sAgent = (string)Services::Request()->getUserAgent();
		return $oSP->isIp_Statuscake( $sIp, $sAgent )
			   || $oSP->isIp_UptimeRobot( $sIp, $sAgent )
			   || $oSP->isIp_Pingdom( $sIp, $sAgent );
	}

	protected function logTraffic() {
		/** @var \ICWP_WPSF_FeatureHandler_Traffic $oMod */
		$oMod = $this->getMod();
		$oReq = Services::Request();
		/** @var Traffic\Handler $oDbh */
		$oDbh = $oMod->getDbHandler_Traffic();

		// For multisites that are separated by sub-domains we also show the host.
		$sLeadingPath = Services::WpGeneral()->isMultisite_SubdomainInstall() ? $oReq->getHost() : '';

		/** @var Traffic\EntryVO $oEntry */
		$oEntry = $oDbh->getVo();

		$oEntry->rid = $this->getCon()->getShortRequestId();
		$oEntry->uid = Services::WpUsers()->getCurrentWpUserId();
		$oEntry->ip = inet_pton( Services::IP()->getRequestIp() );
		$oEntry->verb = $oReq->getMethod();
		$oEntry->path = $sLeadingPath.$oReq->getPath().( empty( $_GET ) ? '' : '?'.http_build_query( $_GET ) );
		$oEntry->code = http_response_code();
		$oEntry->ua = $oReq->getUserAgent();
		$oEntry->trans = $oMod->getIfIpTransgressed() ? 1 : 0;

		$oDbh->getQueryInserter()
			 ->insert( $oEntry );
	}
}