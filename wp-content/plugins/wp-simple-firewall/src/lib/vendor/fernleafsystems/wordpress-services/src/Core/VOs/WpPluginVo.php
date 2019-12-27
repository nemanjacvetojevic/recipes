<?php

namespace FernleafSystems\Wordpress\Services\Core\VOs;

use FernleafSystems\Utilities\Data\Adapter\StdClassAdapter;
use FernleafSystems\Wordpress\Services\Services;
use FernleafSystems\Wordpress\Services\Utilities\WpOrg\Plugin;

/**
 * Class WpPluginVo
 * @package FernleafSystems\Wordpress\Services\Core\VOs
 * @property string                  Name
 * @property string                  Version
 * @property string                  Description
 * @property string                  PluginURI
 * @property string                  Author
 * @property string                  AuthorURI
 * @property string                  TextDomain
 * @property string                  DomainPath
 * @property bool                    Network
 * @property string                  Title
 * @property string                  AuthorName
 * Extended Properties:
 * @property string                  $id
 * @property string                  $slug
 * @property string                  $plugin
 * @property string                  $new_version
 * @property string                  $url
 * @property string                  $package the update package URL
 * Custom Properties:
 * @property string                  $file
 * @property bool                    $active
 * @property bool                    $svn_uses_tags
 * @property Plugin\VOs\PluginInfoVO $wp_info
 */
class WpPluginVo {

	use StdClassAdapter {
		__get as __adapterGet;
		__set as __adapterSet;
	}

	/**
	 * WpPluginVo constructor.
	 * @param string $sBaseFile
	 * @throws \Exception
	 */
	public function __construct( $sBaseFile ) {
		$oWpPlugins = Services::WpPlugins();
		$aPlug = $oWpPlugins->getPlugin( $sBaseFile );
		if ( empty( $aPlug ) ) {
			throw new \Exception( sprintf( 'Plugin file %s does not exist', $sBaseFile ) );
		}
		$this->applyFromArray( array_merge( $aPlug, $oWpPlugins->getExtendedData( $sBaseFile ) ) );
		$this->file = $sBaseFile;
		$this->active = $oWpPlugins->isActive( $sBaseFile );
	}

	/**
	 * @param string $sProperty
	 * @return mixed
	 */
	public function __get( $sProperty ) {

		$mVal = $this->__adapterGet( $sProperty );

		switch ( $sProperty ) {

			case 'svn_uses_tags':
				if ( is_null( $mVal ) ) {
					$mVal = ( new Plugin\Versions() )
						->setWorkingSlug( $this->slug )
						->exists( $this->Version, true );
					$this->svn_uses_tags = $mVal;
				}
				break;

			case 'wp_info':
				if ( is_null( $mVal ) ) {
					try {
						$mVal = ( new Plugin\Api() )
							->setWorkingSlug( $this->slug )
							->getInfo();
					}
					catch ( \Exception $oE ) {
						$mVal = false;
					}
					$this->wp_info = $mVal;
				}
				break;

			default:
				break;
		}

		return $mVal;
	}

	/**
	 * @return string
	 */
	public function getInstallDir() {
		return wp_normalize_path( trailingslashit( dirname( path_join( WP_PLUGIN_DIR, $this->file ) ) ) );
	}

	/**
	 * @return bool
	 */
	public function hasUpdate() {
		return !empty( $this->new_version ) && version_compare( $this->new_version, $this->Version, '>' );
	}

	/**
	 * @return bool
	 */
	public function isWpOrg() {
		return isset( $this->id ) ? strpos( $this->id, 'w.org/' ) === 0 : false;
	}
}