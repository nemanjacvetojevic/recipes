<?php

use FernleafSystems\Wordpress\Plugin\Shield\Modules;
use FernleafSystems\Wordpress\Services\Services;

/**
 * Referenced some of https://github.com/BenjaminNelan/PwnedPasswordChecker
 * Class ICWP_WPSF_Processor_UserManagement_Passwords
 */
class ICWP_WPSF_Processor_UserManagement_Passwords extends Modules\BaseShield\ShieldProcessor {

	public function run() {
		add_action( 'password_reset', [ $this, 'onPasswordReset' ], 100, 1 );
		add_filter( 'registration_errors', [ $this, 'checkPassword' ], 100, 3 );
		add_action( 'user_profile_update_errors', [ $this, 'checkPassword' ], 100, 3 );
		add_action( 'validate_password_reset', [ $this, 'checkPassword' ], 100, 3 );
	}

	/**
	 * @param string  $sUsername
	 * @param WP_User $oUser
	 */
	public function onWpLogin( $sUsername, $oUser ) {
		$this->captureLogin( $oUser );
	}

	/**
	 * @param string $sCookie
	 * @param int    $nExpire
	 * @param int    $nExpiration
	 * @param int    $nUserId
	 */
	public function onWpSetLoggedInCookie( $sCookie, $nExpire, $nExpiration, $nUserId ) {
		$this->captureLogin( Services::WpUsers()->getUserById( $nUserId ) );
	}

	/**
	 * @param WP_User $oUser
	 */
	private function captureLogin( $oUser ) {
		$sPassword = $this->getLoginPassword();

		if ( Services::Request()->isPost() && !$this->isLoginCaptured()
			 && $oUser instanceof WP_User && !empty( $sPassword ) ) {
			$this->setLoginCaptured();
			try {
				$this->applyPasswordChecks( $sPassword );
				$bFailed = false;
			}
			catch ( \Exception $oE ) {
				$bFailed = ( $oE->getCode() != 999 ); // We don't fail when the PWNED API is not available.
			}
			$this->setPasswordFailedFlag( $oUser, $bFailed );
		}
	}

	public function onWpLoaded() {
		if ( is_admin() && !Services::WpGeneral()->isAjax() && !Services::Request()->isPost()
			 && Services::WpUsers()->isUserLoggedIn() ) {
			$this->processExpiredPassword();
			$this->processFailedCheckPassword();
		}
	}

	/**
	 * @param WP_User $oUser
	 */
	public function onPasswordReset( $oUser ) {
		if ( $oUser instanceof WP_User && $oUser->ID > 0 ) {
			$oMeta = $this->getCon()->getUserMeta( $oUser );
			unset( $oMeta->pass_hash );
			$oMeta->pass_started_at = 0;
		}
	}

	private function processExpiredPassword() {
		/** @var \ICWP_WPSF_FeatureHandler_UserManagement $oMod */
		$oMod = $this->getMod();
		if ( $oMod->isPassExpirationEnabled() ) {
			$nPassStartedAt = (int)$this->getCon()->getCurrentUserMeta()->pass_started_at;
			if ( $nPassStartedAt > 0 ) {
				if ( Services::Request()->ts() - $nPassStartedAt > $oMod->getPassExpireTimeout() ) {
					$this->getCon()->fireEvent( 'pass_expired' );
					$this->redirectToResetPassword(
						sprintf( __( 'Your password has expired (after %s days).', 'wp-simple-firewall' ), $oMod->getPassExpireDays() )
					);
				}
			}
		}
	}

	private function processFailedCheckPassword() {
		/** @var \ICWP_WPSF_FeatureHandler_UserManagement $oFO */
		$oFO = $this->getMod();
		$oMeta = $this->getCon()->getCurrentUserMeta();

		$bPassCheckFailed = $oFO->isPassForceUpdateExisting()
							&& isset( $oMeta->pass_check_failed_at ) && $oMeta->pass_check_failed_at > 0;

		if ( $bPassCheckFailed ) {
			$this->redirectToResetPassword(
				__( "Your password doesn't meet requirements set by your security administrator.", 'wp-simple-firewall' )
			);
		}
	}

	/**
	 * IMPORTANT: User must be logged-in for this to work correctly
	 * We have a 2 minute delay between redirects because some custom user forms redirect to custom
	 * password reset pages. This prevents users following this flow.
	 * @param string $sMessage
	 * @uses wp_redirect()
	 */
	private function redirectToResetPassword( $sMessage ) {
		$nNow = Services::Request()->ts();

		$oMeta = $this->getCon()->getCurrentUserMeta();
		$nLastRedirect = (int)$oMeta->pass_reset_last_redirect_at;
		if ( $nNow - $nLastRedirect > MINUTE_IN_SECONDS*2 ) {

			$oMeta->pass_reset_last_redirect_at = $nNow;

			$oWpUsers = Services::WpUsers();
			$sAction = Services::Request()->query( 'action' );
			$oUser = $oWpUsers->getCurrentWpUser();
			if ( $oUser && ( !Services::WpGeneral()->isLoginUrl() || !in_array( $sAction, [ 'rp', 'resetpass' ] ) ) ) {

				$sMessage .= ' '.__( 'For your security, please use the password section below to update your password.', 'wp-simple-firewall' );
				$this->getMod()
					 ->setFlashAdminNotice( $sMessage, true, true );
				$this->getCon()->fireEvent( 'password_policy_force_change' );
				Services::Response()->redirect( $oWpUsers->getPasswordResetUrl( $oUser ) );
			}
		}
	}

	/**
	 * @param \WP_Error $oErrors
	 * @return \WP_Error
	 */
	public function checkPassword( $oErrors ) {
		$aExistingCodes = $oErrors->get_error_code();
		if ( empty( $aExistingCodes ) ) {
			$sPassword = $this->getLoginPassword();
			if ( !empty( $sPassword ) ) {
				try {
					$this->applyPasswordChecks( $sPassword );
					$bChecksPassed = true;
				}
				catch ( \Exception $oE ) {
					$bChecksPassed = ( $oE->getCode() === 999 );
				}

				if ( $bChecksPassed ) {
					if ( Services::WpUsers()->isUserLoggedIn() ) {
						$this->getCon()->getCurrentUserMeta()->pass_check_failed_at = 0;
					}
				}
				else {
					$sMessage = __( 'Your security administrator has imposed requirements for password quality.', 'wp-simple-firewall' )
								.'<br/>'.sprintf( __( 'Reason', 'wp-simple-firewall' ).': '.$oE->getMessage() );
					$oErrors->add( 'shield_password_policy', $sMessage );
					$this->getCon()->fireEvent( 'password_policy_block' );
				}
			}
		}

		return $oErrors;
	}

	/**
	 * @param string $sPassword
	 * @throws \Exception
	 */
	protected function applyPasswordChecks( $sPassword ) {
		/** @var \ICWP_WPSF_FeatureHandler_UserManagement $oMod */
		$oMod = $this->getMod();

		$this->testPasswordMeetsMinimumLength( $sPassword );
		$this->testPasswordMeetsMinimumStrength( $sPassword );
		if ( $oMod->isPassPreventPwned() ) {
			$this->sendRequestToPwnedRange( $sPassword );
		}
	}

	/**
	 * @param string $sPassword
	 * @return bool
	 * @throws \Exception
	 */
	protected function testPasswordMeetsMinimumStrength( $sPassword ) {
		/** @var ICWP_WPSF_FeatureHandler_UserManagement $oFO */
		$oFO = $this->getMod();
		$nMin = $oFO->getPassMinStrength();

		/**
		 * TODO: Upon upgrading minimum PHP 5.6, remove the older, and install newer as-is
		 */
		if ( Services::Data()->getPhpVersionIsAtLeast( '5.6' ) && extension_loaded( 'mbstring' ) ) {
			$aResults = ( new \ZxcvbnPhp56\Zxcvbn() )->passwordStrength( $sPassword );
		}
		else {
			$aResults = ( new \ZxcvbnPhp\Zxcvbn() )->passwordStrength( $sPassword );
		}

		$nScore = $aResults[ 'score' ];

		if ( $nMin > 0 && $nScore < $nMin ) {
			throw new \Exception( sprintf( "Password strength (%s) doesn't meet the minimum required strength (%s).",
				$oFO->getPassStrengthName( $nScore ), $oFO->getPassStrengthName( $nMin ) ) );
		}
		return true;
	}

	/**
	 * @param string $sPassword
	 * @return bool
	 * @throws \Exception
	 */
	protected function testPasswordMeetsMinimumLength( $sPassword ) {
		/** @var ICWP_WPSF_FeatureHandler_UserManagement $oFO */
		$oFO = $this->getMod();
		$nMin = $oFO->getPassMinLength();
		$nLength = strlen( $sPassword );
		if ( $nMin > 0 && $nLength < $nMin ) {
			throw new \Exception( sprintf( __( 'Password length (%s) too short (min: %s characters)', 'wp-simple-firewall' ), $nLength, $nMin ) );
		}
		return true;
	}

	/**
	 * @return bool
	 */
	protected function verifyApiAccess() {
		try {
			$this->sendRequestToPwnedRange( 'P@ssw0rd' );
		}
		catch ( \Exception $oE ) {
			return false;
		}
		return true;
	}

	/**
	 * @param string $sPass
	 * @return bool
	 * @throws \Exception
	 * @deprecated 8.4
	 */
	protected function sendRequestToPwned( $sPass ) {
		$oHttpReq = Services::HttpRequest();
		$bSuccess = $oHttpReq->get(
			sprintf( '%s/%s', $this->getOptions()->getDef( 'pwned_api_url_password_single' ), hash( 'sha1', $sPass ) ),
			[
				'headers' => [ 'user-agent' => sprintf( '%s WP Plugin-v%s', 'Shield', $this->getCon()->getVersion() ) ]
			]
		);

		$sError = '';
		if ( !$bSuccess ) {
			$sError = 'API request failed';
		}
		else {
			$nCode = $oHttpReq->lastResponse->getCode();
			if ( empty( $nCode ) ) {
				$sError = 'Unexpected Error: No response code available from the Response';
			}
			else if ( $nCode == 404 ) {
				// means that the password isn't on the pwned list. It's acceptable.
			}
			else if ( empty( $oHttpReq->lastResponse->body ) ) {
				$sError = 'Unexpected Error: The response from the Pwned API was empty';
			}
			else {
				// password pwned
				$nCount = intval( $oHttpReq->lastResponse[ 'body' ] );
				if ( $nCount == 0 ) {
					$sError = 'Unexpected Error: The API response could not be properly parsed.';
				}
				else {
					$sError = __( 'Please use a different password.', 'wp-simple-firewall' )
							  .' '.__( 'This password has already been pwned.', 'wp-simple-firewall' )
							  .' '.sprintf(
								  '(<a href="%s" target="_blank">%s</a>)',
								  'https://www.troyhunt.com/ive-just-launched-pwned-passwords-version-2/',
								  sprintf( __( '%s times', 'wp-simple-firewall' ), $nCount )
							  );
				}
			}
		}

		if ( !empty( $sError ) ) {
			throw new \Exception( '[Pwned Request] '.$sError );
		}

		return true;
	}

	/**
	 * @param string $sPass
	 * @return bool
	 * @throws \Exception
	 */
	private function sendRequestToPwnedRange( $sPass ) {
		$oHttpReq = Services::HttpRequest();

		$sPassHash = strtoupper( hash( 'sha1', $sPass ) );
		$sSubHash = substr( $sPassHash, 0, 5 );

		$bSuccess = $oHttpReq->get(
			sprintf( '%s/%s', $this->getOptions()->getDef( 'pwned_api_url_password_range' ), $sSubHash ),
			[
				'headers' => [ 'user-agent' => sprintf( '%s WP Plugin-v%s', 'Shield', $this->getCon()->getVersion() ) ]
			]
		);

		$sError = '';
		$nErrorCode = 2; // Default To Error
		if ( !$bSuccess ) {
			$sError = 'API request failed';
			$nErrorCode = 999; // We don't fail PWNED passwords on failed API requests.
		}
		else {
			$nHttpCode = $oHttpReq->lastResponse->getCode();
			if ( empty( $nHttpCode ) ) {
				$sError = 'Unexpected Error: No response code available from the Pwned API';
			}
			else if ( $nHttpCode != 200 ) {
				$sError = 'Unexpected Error: The response from the Pwned API was unexpected';
			}
			else if ( empty( $oHttpReq->lastResponse->body ) ) {
				$sError = 'Unexpected Error: The response from the Pwned API was empty';
			}
			else {
				$nPwnedCount = 0;
				foreach ( array_map( 'trim', explode( "\n", trim( $oHttpReq->lastResponse->body ) ) ) as $sRow ) {
					if ( $sSubHash.substr( strtoupper( $sRow ), 0, 35 ) == $sPassHash ) {
						$nPwnedCount = substr( $sRow, 36 );
						break;
					}
				}
				if ( $nPwnedCount > 0 ) {
					$sError = __( 'Please use a different password.', 'wp-simple-firewall' )
							  .'<br/>'.__( 'This password has been pwned.', 'wp-simple-firewall' )
							  .' '.sprintf(
								  '(<a href="%s" target="_blank">%s</a>)',
								  'https://www.troyhunt.com/ive-just-launched-pwned-passwords-version-2/',
								  sprintf( __( '%s times', 'wp-simple-firewall' ), $nPwnedCount )
							  );
				}
				else {
					// Success: Password is not pwned
					$nErrorCode = 0;
				}
			}
		}

		if ( $nErrorCode != 0 ) {
			throw new \Exception( '[Pwned Request] '.$sError, $nErrorCode );
		}

		return true;
	}

	/**
	 * @return string
	 */
	private function getLoginPassword() {
		$sPass = null;

		// Edd: edd_user_pass; Woo: password;
		foreach ( [ 'pwd', 'pass1' ] as $sKey ) {
			$sP = Services::Request()->post( $sKey );
			if ( !empty( $sP ) ) {
				$sPass = $sP;
				break;
			}
		}
		return $sPass;
	}

	/**
	 * @param WP_User $oUser
	 * @param bool    $bFailed
	 * @return $this
	 */
	private function setPasswordFailedFlag( $oUser, $bFailed = false ) {
		$oMeta = $this->getCon()->getUserMeta( $oUser );
		$oMeta->pass_check_failed_at = $bFailed ? Services::Request()->ts() : 0;
		return $this;
	}
}