<?php

namespace FernleafSystems\Wordpress\Plugin\Shield\Modules\SecurityAdmin;

use FernleafSystems\Wordpress\Plugin\Shield;

class AjaxHandler extends Shield\Modules\Base\AjaxHandlerShield {

	/**
	 * @param string $sAction
	 * @return array
	 */
	protected function processAjaxAction( $sAction ) {

		switch ( $sAction ) {
			case 'sec_admin_check':
				$aResponse = $this->ajaxExec_SecAdminCheck();
				break;

			case 'sec_admin_login':
			case 'restricted_access':
				$aResponse = $this->ajaxExec_SecAdminLogin();
				break;

			case 'sec_admin_login_box':
				$aResponse = $this->ajaxExec_SecAdminLoginBox();
				break;

			default:
				$aResponse = parent::processAjaxAction( $sAction );
		}

		return $aResponse;
	}

	/**
	 * @return array
	 */
	private function ajaxExec_SecAdminCheck() {
		/** @var \ICWP_WPSF_FeatureHandler_AdminAccessRestriction $oMod */
		$oMod = $this->getMod();
		return [
			'timeleft' => $oMod->getSecAdminTimeLeft(),
			'success'  => $oMod->isSecAdminSessionValid()
		];
	}

	/**
	 * @return array
	 */
	private function ajaxExec_SecAdminLogin() {
		/** @var \ICWP_WPSF_FeatureHandler_AdminAccessRestriction $oMod */
		$oMod = $this->getMod();
		$bSuccess = false;
		$sHtml = '';

		if ( $oMod->testSecAccessKeyRequest() ) {

			if ( $oMod->setSecurityAdminStatusOnOff( true ) ) {
				$bSuccess = true;
				$sMsg = __( 'Security Admin Access Key Accepted.', 'wp-simple-firewall' )
						.' '.__( 'Please wait', 'wp-simple-firewall' ).' ...';
			}
			else {
				$sMsg = __( 'Failed to process key - you may need to re-login to WordPress.', 'wp-simple-firewall' );
			}
		}
		else {
			/** @var \ICWP_WPSF_Processor_Ips $oIpPro */
			$oIpPro = $this->getCon()
						   ->getModule_IPs()
						   ->getProcessor();
			$nRemaining = $oIpPro->getRemainingTransgressions() - 1;
			$sMsg = __( 'Security access key incorrect.', 'wp-simple-firewall' ).' ';
			if ( $nRemaining > 0 ) {
				$sMsg .= sprintf( __( 'Attempts remaining: %s.', 'wp-simple-firewall' ), $nRemaining );
			}
			else {
				$sMsg .= __( "No attempts remaining.", 'wp-simple-firewall' );
			}
			$sHtml = $this->renderAdminAccessAjaxLoginForm( $sMsg );
		}

		return [
			'success'     => $bSuccess,
			'page_reload' => true,
			'message'     => $sMsg,
			'html'        => $sHtml,
		];
	}

	/**
	 * @return array
	 */
	private function ajaxExec_SecAdminLoginBox() {
		return [
			'success' => 'true',
			'html'    => $this->renderAdminAccessAjaxLoginForm()
		];
	}

	/**
	 * @param string $sMessage
	 * @return string
	 */
	private function renderAdminAccessAjaxLoginForm( $sMessage = '' ) {
		/** @var \ICWP_WPSF_FeatureHandler_AdminAccessRestriction $oMod */
		$oMod = $this->getMod();

		$aData = [
			'ajax'    => [
				'sec_admin_login' => json_encode( $oMod->getSecAdminLoginAjaxData() )
			],
			'strings' => [
				'access_message' => empty( $sMessage ) ? __( 'Enter your Security Admin Access Key', 'wp-simple-firewall' ) : $sMessage
			]
		];
		return $oMod->renderTemplate( 'snippets/admin_access_login', $aData );
	}
}