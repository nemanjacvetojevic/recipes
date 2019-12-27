<?php

namespace FernleafSystems\Wordpress\Services\Utilities\Licenses;

use FernleafSystems\Wordpress\Services\Services;

/**
 * Class EddLicenseVO
 * @package FernleafSystems\Wordpress\Services\Utilities\Licenses
 * @property int    $activations_left
 * @property string $customer_email
 * @property string $checksum
 * @property string $customer_name
 * @property string $item_name
 * @property int    $last_request_at
 * @property int    $last_verified_at
 * @property int    $license_limit
 * @property int    $site_count
 * @property string $license
 * @property string $payment_id
 * @property bool   $success
 * @property string $error
 */
class EddLicenseVO {

	use \FernleafSystems\Utilities\Data\Adapter\StdClassAdapter;

	/**
	 * @return int
	 */
	public function getExpiresAt() {
		$sTime = $this->getParam( 'expires' );
		return ( $sTime == 'lifetime' ) ? PHP_INT_MAX : strtotime( $sTime );
	}

	/**
	 * @return bool
	 */
	public function isExpired() {
		return ( $this->getExpiresAt() < Services::Request()->ts() );
	}

	/**
	 * @return bool
	 */
	public function isValid() {
		return ( $this->isReady() && $this->success && !$this->isExpired() && $this->license == 'valid' );
	}

	/**
	 * @return bool
	 */
	public function hasError() {
		return !empty( $this->error );
	}

	/**
	 * @return bool
	 */
	public function hasChecksum() {
		return !empty( $this->checksum );
	}

	/**
	 * @return bool
	 */
	public function isReady() {
		return $this->hasChecksum();
	}

	/**
	 * @param bool $bAddRandom
	 * @return $this
	 */
	public function updateLastVerifiedAt( $bAddRandom = false ) {
		$nRandom = $bAddRandom ? rand( -6, 18 )*HOUR_IN_SECONDS : 0;
		return $this->setParam( 'last_verified_at', $this->last_request_at + $nRandom );
	}
}