<?php

namespace FernleafSystems\Wordpress\Services\Utilities\Integrations\WpHashes\Hashes;

use FernleafSystems\Wordpress\Services\Utilities\Integrations\WpHashes;

abstract class Base extends WpHashes\ApiBase {

	const API_ENDPOINT = 'hashes/';
	const DEFAULT_HASH_ALGO = 'md5';
	const RESPONSE_DATA_KEY = 'hashes';
	const TYPE = '';

	/**
	 * @return string
	 */
	protected function getApiUrl() {
		$aData = array_filter( array_merge(
			[
				'type'    => false,
				'slug'    => false,
				'version' => false,
				'locale'  => false,
				'hash'    => false,
			],
			$this->getRequestVO()->getRawDataAsArray()
		) );
		return sprintf( '%s%s', parent::getApiUrl(), implode( '/', $aData ) );
	}

	/**
	 * @return RequestVO
	 */
	protected function newReqVO() {
		return new RequestVO();
	}

	/**
	 */
	protected function preQuery() {
		$oReq = $this->getRequestVO();
		if ( empty( $oReq->hash ) ) {
			$this->setHashAlgo( static::DEFAULT_HASH_ALGO );
		}
		if ( empty( $oReq->type ) ) {
			$this->setType( static::TYPE );
		}
	}

	/**
	 * @param string $sHashAlgo
	 * @return $this
	 */
	public function setHashAlgo( $sHashAlgo ) {
		$this->getRequestVO()->hash = $sHashAlgo;
		return $this;
	}

	/**
	 * @param string $sType
	 * @return $this
	 */
	public function setType( $sType ) {
		$this->getRequestVO()->type = $sType;
		return $this;
	}
}