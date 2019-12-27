<?php

namespace FernleafSystems\Wordpress\Services\Core;

use FernleafSystems\Wordpress\Services\Services;

/**
 * Class CoreFileHashes
 * @package FernleafSystems\Wordpress\Services\Core
 */
class CoreFileHashes {

	/**
	 * @var array
	 */
	private $aHashes;

	/**
	 * @return array
	 */
	public function getHashes() {
		if ( !isset( $this->aHashes ) ) {
			$aHash = Services::WpGeneral()->getCoreChecksums();
			$this->aHashes = is_array( $aHash ) ? $aHash : [];
		}
		return $this->aHashes;
	}

	/**
	 * @param string $sFile
	 * @return string|null
	 */
	public function getFileHash( $sFile ) {
		$sNorm = $this->getFileFragment( $sFile );
		return $this->isCoreFile( $sNorm ) ? $this->getHashes()[ $sNorm ] : null;
	}

	/**
	 * @param string $sFile
	 * @return string
	 */
	public function getFileFragment( $sFile ) {
		return Services::WpFs()->getPathRelativeToAbsPath( $sFile );
	}

	/**
	 * @param string $sFile
	 * @return string
	 */
	public function getAbsolutePathFromFragment( $sFile ) {
		return wp_normalize_path( path_join( ABSPATH, $this->getFileFragment( $sFile ) ) );
	}

	/**
	 * @param string $sFile
	 * @return bool
	 */
	public function isCoreFile( $sFile ) {
		return array_key_exists( $this->getFileFragment( $sFile ), $this->getHashes() );
	}

	/**
	 * @return bool
	 */
	public function isReady() {
		return ( count( $this->getHashes() ) > 0 );
	}
}