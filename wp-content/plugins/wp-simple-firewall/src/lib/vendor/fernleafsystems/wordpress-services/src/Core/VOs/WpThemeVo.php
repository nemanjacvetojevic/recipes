<?php

namespace FernleafSystems\Wordpress\Services\Core\VOs;

use FernleafSystems\Utilities\Data\Adapter\StdClassAdapter;
use FernleafSystems\Wordpress\Services\Services;
use FernleafSystems\Wordpress\Services\Utilities\WpOrg\Theme;

/**
 * Class WpThemeVo
 * @package FernleafSystems\Wordpress\Services\Core\VOs
 * @property string                      $theme        - the stylesheet
 * @property string                      $stylesheet   - the stylesheet
 * @property \WP_Theme                   $wp_theme
 * @property Theme\VOs\ThemeInfoVO|false $wp_info      - wp.org theme info
 * @property string                      $new_version
 * @property string                      $url
 * @property string                      $package
 * @property string                      $requires
 * @property string                      $requires_php
 * @property string                      $version
 * @property bool                        $active
 * @property bool                        $is_child
 * @property bool                        $is_parent
 */
class WpThemeVo {

	use StdClassAdapter {
		__get as __adapterGet;
		__set as __adapterSet;
	}

	/**
	 * WpPluginVo constructor.
	 * @param string $sStylesheet - the name of the theme folder.
	 * @throws \Exception
	 */
	public function __construct( $sStylesheet ) {
		$oWpTheme = Services::WpThemes();
		$oT = $oWpTheme->getTheme( $sStylesheet );
		if ( empty( $oT ) ) {
			throw new \Exception( sprintf( 'Theme file %s does not exist', $sStylesheet ) );
		}

		$this->applyFromArray( $oWpTheme->getExtendedData( $sStylesheet ) );
		$this->wp_theme = $oT;
		$this->stylesheet = $sStylesheet;
		$this->active = $oWpTheme->isActive( $sStylesheet );
		$this->is_child = $this->active && $oWpTheme->isActiveThemeAChild();
		$this->is_parent = !$this->active && $oWpTheme->isActiveParent( $sStylesheet );
	}

	/**
	 * @param string $sProperty
	 * @return mixed
	 */
	public function __get( $sProperty ) {

		$mVal = $this->__adapterGet( $sProperty );

		switch ( $sProperty ) {

			case 'version':
				if ( is_null( $mVal ) ) {
					$mVal = $this->wp_theme->get( 'Version' );
					$this->version = $mVal;
				}
				break;

			case 'wp_info':
				if ( is_null( $mVal ) ) {
					try {
						$mVal = ( new Theme\Api() )
							->setWorkingSlug( $this->stylesheet )
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
		return wp_normalize_path( trailingslashit( $this->wp_theme->get_stylesheet_directory() ) );
	}

	/**
	 * @return bool
	 */
	public function hasUpdate() {
		return !empty( $this->new_version )
			   && version_compare( $this->new_version, $this->version, '>' );
	}

	/**
	 * @return bool
	 */
	public function isWpOrg() {
		$this->wp_info;
		return !empty( $this->wp_info );
	}
}