<?php

use FernleafSystems\Wordpress\Plugin\Shield;
use FernleafSystems\Wordpress\Services;

class ICWP_WPSF_Processor_HackProtect_Ptg extends ICWP_WPSF_Processor_HackProtect_ScanAssetsBase {

	const SCAN_SLUG = 'ptg';

	/**
	 * @var Shield\Scans\Ptg\Snapshots\Store
	 */
	private $oSnapshotPlugins;

	/**
	 * @var Shield\Scans\Ptg\Snapshots\Store
	 */
	private $oSnapshotThemes;

	/**
	 */
	public function run() {
		parent::run();
		/** @var \ICWP_WPSF_FeatureHandler_HackProtect $oFO */
		$oFO = $this->getMod();

		$this->initSnapshots();

		$bStoresExists = $this->getStore_Plugins()->getSnapStoreExists()
						 && $this->getStore_Themes()->getSnapStoreExists();

		// If a build is indicated as required and the store exists, mark them as built.
		if ( $oFO->isPtgBuildRequired() && $bStoresExists ) {
			$oFO->setPtgLastBuildAt();
		}
		else if ( !$bStoresExists ) {
			$oFO->setPtgLastBuildAt( 0 );
		}

		if ( $oFO->isPtgReadyToScan() ) {
			add_action( 'upgrader_process_complete', [ $this, 'updateSnapshotAfterUpgrade' ], 10, 2 );
			add_action( 'activated_plugin', [ $this, 'onActivatePlugin' ], 10 );
			add_action( 'deactivated_plugin', [ $this, 'onDeactivatePlugin' ], 10 );
			add_action( 'switch_theme', [ $this, 'onActivateTheme' ], 10, 0 );
		}

		if ( $oFO->isPtgReinstallLinks() ) {
			add_filter( 'plugin_action_links', [ $this, 'addActionLinkRefresh' ], 50, 2 );
			add_action( 'admin_footer', [ $this, 'printPluginReinstallDialogs' ] );
		}
	}

	/**
	 * @return bool
	 */
	public function isAvailable() {
		return $this->isEnabled() && !$this->isRestricted();
	}

	/**
	 * @return bool
	 */
	public function isRestricted() {
		return !$this->getMod()->isPremium();
	}

	/**
	 * @return bool
	 */
	public function isEnabled() {
		/** @var ICWP_WPSF_FeatureHandler_HackProtect $oFO */
		$oFO = $this->getMod();
		return $oFO->isPtgEnabled();
	}

	/**
	 * @return Shield\Scans\Wcf\Repair|mixed
	 */
	protected function getRepairer() {
		return new Shield\Scans\Ptg\Repair();
	}

	/**
	 * @param string $sContext
	 * @return Shield\Scans\Ptg\ScannerPlugins|Shield\Scans\Ptg\ScannerThemes
	 */
	protected function getContextScanner( $sContext = self::CONTEXT_PLUGINS ) {
		/** @var ICWP_WPSF_FeatureHandler_HackProtect $oFO */
		$oFO = $this->getMod();

		$oScanner = ( $sContext == self::CONTEXT_PLUGINS ) ?
			new Shield\Scans\Ptg\ScannerPlugins()
			: new Shield\Scans\Ptg\ScannerThemes();

		return $oScanner->setDepth( $oFO->getPtgDepth() )
						->setFileExts( $oFO->getPtgFileExtensions() );
	}

	/**
	 * @param array  $aLinks
	 * @param string $sPluginFile
	 * @return string[]
	 */
	public function addActionLinkRefresh( $aLinks, $sPluginFile ) {
		$oWpP = Services\Services::WpPlugins();

		$oPlgn = $oWpP->getPluginAsVo( $sPluginFile );
		if ( $oPlgn instanceof Services\Core\VOs\WpPluginVo && $oPlgn->isWpOrg() && !$oWpP->isUpdateAvailable( $sPluginFile ) ) {
			$sLinkTemplate = '<a href="javascript:void(0)">%s</a>';
			$aLinks[ 'icwp-reinstall' ] = sprintf(
				$sLinkTemplate,
				__( 'Re-Install', 'wp-simple-firewall' )
			);
		}

		return $aLinks;
	}

	/**
	 * @param string $sItem
	 * @return array|null
	 */
	public function getSnapshotItemMeta( $sItem ) {
		$aItem = null;
		if ( $this->getStore_Plugins()->itemExists( $sItem ) ) {
			$aItem = $this->getStore_Plugins()->getSnapItem( $sItem );
		}
		else if ( $this->getStore_Themes()->itemExists( $sItem ) ) {
			$aItem = $this->getStore_Themes()->getSnapItem( $sItem );
		}
		$aMeta = is_array( $aItem ) && !empty( $aItem[ 'meta' ] ) ? $aItem[ 'meta' ] : null;
		return $aMeta;
	}

	/**
	 * @param Shield\Scans\Ptg\ResultItem $oItem
	 * @return true
	 * @throws \Exception
	 */
	protected function assetAccept( $oItem ) {
		/** @var Shield\Scans\Ptg\ResultsSet $oRes */
		$oRes = $this->readScanResultsFromDb();
		// We ignore the item (so for WP.org plugins it wont flag up again)
		foreach ( $oRes->getItemsForSlug( $oItem->slug ) as $oItem ) {
			$this->itemIgnore( $oItem );
		}

		// we run it for both since it doesn't matter which context it's in, it'll be removed
		$this->updateItemInSnapshot( $oItem->slug );
		return true;
	}

	public function printPluginReinstallDialogs() {
		echo $this->getMod()->renderTemplate(
			'snippets/dialog_plugins_reinstall.twig',
			[
				'strings'     => [
					'are_you_sure'       => __( 'Are you sure?', 'wp-simple-firewll' ),
					'really_reinstall'   => __( 'Really Re-Install Plugin', 'wp-simple-firewll' ),
					'wp_reinstall'       => __( 'WordPress will now download and install the latest available version of this plugin.', 'wp-simple-firewll' ),
					'in_case'            => sprintf( '%s: %s',
						__( 'Note', 'wp-simple-firewall' ),
						__( 'In case of possible failure, it may be better to do this while the plugin is inactive.', 'wp-simple-firewll' )
					),
					'reinstall_first'    => __( 'Re-install first?', 'wp-simple-firewall' ),
					'corrupted'          => __( "This ensures files for this plugin haven't been corrupted in any way.", 'wp-simple-firewall' ),
					'choose'             => __( "You can choose to 'Activate Only' (not recommended), or close this message to cancel activation.", 'wp-simple-firewall' ),
					'editing_restricted' => __( 'Editing this option is currently restricted.', 'wp-simple-firewall' ),
					'download'           => sprintf(
						__( 'For best security practices, %s will download and re-install the latest available version of this plugin.', 'wp-simple-firewall' ),
						$this->getCon()->getHumanName()
					)
				],
				'js_snippets' => []
			],
			true
		);
	}

	/**
	 * @param string $sBaseName
	 */
	public function onActivatePlugin( $sBaseName ) {
		$this->updatePluginSnapshot( $sBaseName );
	}

	/**
	 * When activating a theme we completely rebuild the themes snapshot.
	 */
	public function onActivateTheme() {
		$this->snapshotThemes();
	}

	/**
	 * @param string $sBaseName
	 */
	public function onDeactivatePlugin( $sBaseName ) {
		// can't use update snapshot because active plugins setting hasn't been updated yet by WP
		$this->removeItemSnapshot( $sBaseName );
	}

	/**
	 * @param string $sBaseName
	 * @return bool
	 */
	public function reinstall( $sBaseName ) {
		$bSuccess = parent::reinstall( $sBaseName );
		if ( $bSuccess ) {
			$this->updateItemInSnapshot( $sBaseName );
		}
		$this->getCon()->fireEvent(
			static::SCAN_SLUG.'_item_repair_'.( $bSuccess ? 'success' : 'fail' ),
			[ 'audit' => [ 'fragment' => $sBaseName ] ]
		);
		return $bSuccess;
	}

	/**
	 * @param WP_Upgrader $oUpgrader
	 * @param array       $aInfo Upgrade/Install Information
	 */
	public function updateSnapshotAfterUpgrade( $oUpgrader, $aInfo ) {

		$sContext = '';
		$aSlugs = [];

		// Need to account for single and bulk updates. First bulk
		if ( !empty( $aInfo[ self::CONTEXT_PLUGINS ] ) ) {
			$sContext = self::CONTEXT_PLUGINS;
			$aSlugs = $aInfo[ $sContext ];
		}
		else if ( !empty( $aInfo[ self::CONTEXT_THEMES ] ) ) {
			$sContext = self::CONTEXT_THEMES;
			$aSlugs = $aInfo[ $sContext ];
		}
		else if ( !empty( $aInfo[ 'plugin' ] ) ) {
			$sContext = self::CONTEXT_PLUGINS;
			$aSlugs = [ $aInfo[ 'plugin' ] ];
		}
		else if ( !empty( $aInfo[ 'theme' ] ) ) {
			$sContext = self::CONTEXT_THEMES;
			$aSlugs = [ $aInfo[ 'theme' ] ];
		}
		else if ( isset( $aInfo[ 'action' ] ) && $aInfo[ 'action' ] == 'install' && isset( $aInfo[ 'type' ] )
				  && !empty( $oUpgrader->result[ 'destination_name' ] ) ) {

			if ( $aInfo[ 'type' ] == 'plugin' ) {
				$oWpPlugins = Services\Services::WpPlugins();
				$sPluginFile = $oWpPlugins->findPluginFileFromDirName( $oUpgrader->result[ 'destination_name' ] );
				if ( !empty( $sPluginFile ) && $oWpPlugins->isActive( $sPluginFile ) ) {
					$sContext = self::CONTEXT_PLUGINS;
					$aSlugs = [ $sPluginFile ];
				}
			}
			else if ( $aInfo[ 'type' ] == 'theme' ) {
				$sDir = $oUpgrader->result[ 'destination_name' ];
				if ( Services\Services::WpThemes()->isActive( $sDir ) ) {
					$sContext = self::CONTEXT_THEMES;
					$aSlugs = [ $sDir ];
				}
			}
		}

		// update snapshots
		if ( is_array( $aSlugs ) ) {
			foreach ( $aSlugs as $sSlug ) {
				$this->updateItemInSnapshot( $sSlug, $sContext );
			}
		}
	}

	/**
	 * Will also remove a plugin if it's found to be in-active
	 * Careful: Cannot use this for the activate and deactivate hooks as the WP option
	 * wont be updated
	 *
	 * @param string $sBaseName
	 */
	public function updatePluginSnapshot( $sBaseName ) {
		$oStore = $this->getStore_Plugins();

		if ( Services\Services::WpPlugins()->isActive( $sBaseName ) ) {
			try {
				$oStore->addSnapItem( $sBaseName, $this->buildSnapshotPlugin( $sBaseName ) )
					   ->save();
			}
			catch ( \Exception $oE ) {
			}
		}
		else {
			$this->removeItemSnapshot( $sBaseName );
		}
	}

	/**
	 * @param string $sSlug
	 * @return $this
	 */
	protected function removeItemSnapshot( $sSlug ) {
		if ( $this->getContextFromSlug( $sSlug ) == self::CONTEXT_PLUGINS ) {
			$oStore = $this->getStore_Plugins();
		}
		else {
			$oStore = $this->getStore_Themes();
		}

		try {
			$oStore->removeItemSnapshot( $sSlug )
				   ->save();
		}
		catch ( \Exception $oE ) {
		}

		/** @var Shield\Scans\Ptg\ResultsSet $oRes */
		$oRes = $this->readScanResultsFromDb();
		$this->deleteResultsSet( $oRes->getResultsSetForSlug( $sSlug ) );

		return $this;
	}

	/**
	 * @param string $sSlug
	 */
	public function updateThemeSnapshot( $sSlug ) {
		$oStore = $this->getStore_Themes();

		if ( Services\Services::WpThemes()->isActive( $sSlug, true ) ) {
			try {
				$oStore->addSnapItem( $sSlug, $this->buildSnapshotTheme( $sSlug ) )
					   ->save();
			}
			catch ( \Exception $oE ) {
			}
		}
		else {
			$this->removeItemSnapshot( $sSlug );
		}
	}

	/**
	 * Only snaps active.
	 *
	 * @param string $sSlug - the basename for plugin, or stylesheet for theme.
	 * @param string $sContext
	 * @return $this
	 */
	public function updateItemInSnapshot( $sSlug, $sContext = null ) {
		if ( empty( $sContext ) ) {
			$sContext = $this->getContextFromSlug( $sSlug );
		}

		if ( $sContext == self::CONTEXT_THEMES ) {
			$this->updateThemeSnapshot( $sSlug );
		}
		else if ( $sContext == self::CONTEXT_PLUGINS ) {
			$this->updatePluginSnapshot( $sSlug );
		}

		return $this;
	}

	/**
	 * When initiating snapshots, we must clean old results before creating a clean snapshot
	 */
	private function initSnapshots() {
		/** @var \ICWP_WPSF_FeatureHandler_HackProtect $oMod */
		$oMod = $this->getMod();

		$bPluginsRebuildReqd = $oMod->isPtgBuildRequired() || !$this->getStore_Plugins()->getSnapStoreExists();
		$bThemesRebuildReqd = $oMod->isPtgBuildRequired() || !$this->getStore_Themes()->getSnapStoreExists();

		if ( $bPluginsRebuildReqd || $bThemesRebuildReqd ) {
			// grab all the existing results
			$oDbH = $oMod->getDbHandler_ScanResults();
			/** @var Shield\Databases\Scanner\Select $oSel */
			$oSel = $oDbH->getQuerySelector();
			/** @var Shield\Databases\Scanner\EntryVO[] $aRes */
			$aRes = $oSel->filterByScan( static::SCAN_SLUG )->all();

			$oCleaner = ( new Shield\Scans\Ptg\ScanResults\Clean() )
				->setDbHandler( $oDbH )
				->setScanActionVO( $this->getScanActionVO() )
				->setWorkingResultsSet( $this->convertVosToResults( $aRes ) );

			if ( $bPluginsRebuildReqd ) {
				$oCleaner->forPlugins();
				$this->snapshotPlugins();
			}
			if ( $bThemesRebuildReqd ) {
				$oCleaner->forThemes();
				$this->snapshotThemes();
			}
		}

		if ( $oMod->isPtgRebuildSelfRequired() ) {
			// rebuilt self when the plugin itself upgrades
			$this->updatePluginSnapshot( $this->getCon()->getPluginBaseFile() );
			$oMod->setPtgRebuildSelfRequired( false );
		}

		if ( $oMod->isPtgUpdateStoreFormat() ) {
			( new Shield\Scans\Ptg\Snapshots\StoreFormatUpgrade() )
				->setStore( $this->getStore_Plugins() )->run()
				->setStore( $this->getStore_Themes() )->run();
			$oMod->setPtgUpdateStoreFormat( false );
		}
	}

	/**
	 * @param string $sBaseFile
	 * @return array
	 */
	private function buildSnapshotPlugin( $sBaseFile ) {
		$aPlugin = Services\Services::WpPlugins()->getPlugin( $sBaseFile );

		return [
			'meta'   => [
				'name'         => $aPlugin[ 'Name' ],
				'version'      => $aPlugin[ 'Version' ],
				'ts'           => Services\Services::Request()->ts(),
				'snap_version' => $this->getCon()->getVersion(),
			],
			'hashes' => $this->getContextScanner( self::CONTEXT_PLUGINS )->hashAssetFiles( $sBaseFile )
		];
	}

	/**
	 * @param string $sSlug
	 * @return array
	 */
	private function buildSnapshotTheme( $sSlug ) {
		$oTheme = Services\Services::WpThemes()->getTheme( $sSlug );

		return [
			'meta'   => [
				'name'         => $oTheme->get( 'Name' ),
				'version'      => $oTheme->get( 'Version' ),
				'ts'           => Services\Services::Request()->ts(),
				'snap_version' => $this->getCon()->getVersion(),
			],
			'hashes' => $this->getContextScanner( self::CONTEXT_THEMES )->hashAssetFiles( $sSlug )
		];
	}

	/**
	 * @return $this
	 */
	private function snapshotPlugins() {
		try {
			$oStore = $this->getStore_Plugins()
						   ->deleteSnapshots();
			foreach ( Services\Services::WpPlugins()->getActivePlugins() as $sBaseName ) {
				$oStore->addSnapItem( $sBaseName, $this->buildSnapshotPlugin( $sBaseName ) );
			}
			$oStore->save();
		}
		catch ( \Exception $oE ) {
		}
		return $this;
	}

	/**
	 * @return bool
	 */
	private function snapshotThemes() {
		$bSuccess = true;

		$oWpThemes = Services\Services::WpThemes();
		try {
			$oSnap = $this->getStore_Themes()
						  ->deleteSnapshots();

			$oActiveTheme = $oWpThemes->getCurrent();
			$aThemes = [
				$oActiveTheme->get_stylesheet() => $oActiveTheme
			];

			if ( $oWpThemes->isActiveThemeAChild() ) { // is child theme
				$oParent = $oWpThemes->getCurrentParent();
				$aThemes[ $oActiveTheme->get_template() ] = $oParent;
			}

			/** @var $oTheme WP_Theme */
			foreach ( $aThemes as $sSlug => $oTheme ) {
				$oSnap->addSnapItem( $sSlug, $this->buildSnapshotTheme( $sSlug ) );
			}
			$oSnap->save();
		}
		catch ( \Exception $oE ) {
			$bSuccess = false;
		}

		return $bSuccess;
	}

	/**
	 * @param $sContext
	 * @return Shield\Scans\Ptg\Snapshots\Store
	 */
	private function getStore( $sContext ) {
		return ( $sContext == self::CONTEXT_PLUGINS ) ? $this->getStore_Plugins() : $this->getStore_Themes();
	}

	/**
	 * @return Shield\Scans\Ptg\Snapshots\Store
	 */
	private function getStore_Plugins() {
		/** @var ICWP_WPSF_FeatureHandler_HackProtect $oFO */
		$oFO = $this->getMod();

		if ( !isset( $this->oSnapshotPlugins ) ) {
			try {
				$this->oSnapshotPlugins = ( new Shield\Scans\Ptg\Snapshots\Store() )
					->setStorePath( $oFO->getPtgSnapsBaseDir() )
					->setContext( self::CONTEXT_PLUGINS );
			}
			catch ( \Exception $oE ) {
			}
		}
		return $this->oSnapshotPlugins;
	}

	/**
	 * @return Shield\Scans\Ptg\Snapshots\Store
	 */
	private function getStore_Themes() {
		/** @var ICWP_WPSF_FeatureHandler_HackProtect $oFO */
		$oFO = $this->getMod();

		if ( !isset( $this->oSnapshotThemes ) ) {
			try {
				$this->oSnapshotThemes = ( new Shield\Scans\Ptg\Snapshots\Store() )
					->setStorePath( $oFO->getPtgSnapsBaseDir() )
					->setContext( self::CONTEXT_THEMES );
			}
			catch ( \Exception $oE ) {
			}
		}
		return $this->oSnapshotThemes;
	}

	/**
	 * @param Shield\Scans\Ptg\ResultsSet $oRes
	 */
	protected function runCronAutoRepair( $oRes ) {
		// no autorepair
	}

	/**
	 * @param Shield\Scans\Ptg\ResultsSet $oRes
	 * @return bool
	 */
	protected function runCronUserNotify( $oRes ) {
		$this->emailResults( $oRes );
		return true;
	}

	/**
	 * @param Shield\Scans\Ptg\ResultsSet $oRes
	 */
	protected function emailResults( $oRes ) {
		/** @var ICWP_WPSF_FeatureHandler_HackProtect $oFO */
		$oFO = $this->getMod();
		$oWpPlugins = Services\Services::WpPlugins();
		$oWpThemes = Services\Services::WpThemes();

		$aAllPlugins = [];
		foreach ( $oRes->getResultsForPluginsContext()->getUniqueSlugs() as $sBaseFile ) {
			$oP = $oWpPlugins->getPluginAsVo( $sBaseFile );
			if ( !empty( $oP ) ) {
				$sVersion = empty( $oP->Version ) ? '' : ': v'.ltrim( $oP->Version, 'v' );
				$aAllPlugins[] = sprintf( '%s%s', $oP->Name, $sVersion );
			}
		}

		$aAllThemes = [];
		foreach ( $oRes->getResultsForThemesContext()->getUniqueSlugs() as $sBaseFile ) {
			$oTheme = $oWpThemes->getTheme( $sBaseFile );
			if ( !empty( $oTheme ) ) {
				$sVersion = empty( $oTheme->version ) ? '' : ': v'.ltrim( $oTheme->version, 'v' );
				$aAllThemes[] = sprintf( '%s%s', $oTheme->get( 'Name' ), $sVersion );
			}
		}

		$sName = $this->getCon()->getHumanName();
		$sHomeUrl = Services\Services::WpGeneral()->getHomeUrl();

		$aContent = [
			sprintf( __( '%s has detected at least 1 Plugins/Themes have been modified on your site.', 'wp-simple-firewall' ), $sName ),
			'',
			sprintf( '<strong>%s</strong>', __( 'You will receive only 1 email notification about these changes in a 1 week period.', 'wp-simple-firewall' ) ),
			'',
			sprintf( '%s: %s', __( 'Site URL', 'wp-simple-firewall' ), sprintf( '<a href="%s" target="_blank">%s</a>', $sHomeUrl, $sHomeUrl ) ),
			'',
			__( 'Details of the problem items are below:', 'wp-simple-firewall' ),
		];

		if ( !empty( $aAllPlugins ) ) {
			$aContent[] = '';
			$aContent[] = sprintf( '<strong>%s</strong>', __( 'Modified Plugins:', 'wp-simple-firewall' ) );
			foreach ( $aAllPlugins as $sPlugins ) {
				$aContent[] = ' - '.$sPlugins;
			}
		}

		if ( !empty( $aAllThemes ) ) {
			$aContent[] = '';
			$aContent[] = sprintf( '<strong>%s</strong>', __( 'Modified Themes:', 'wp-simple-firewall' ) );
			foreach ( $aAllThemes as $sTheme ) {
				$aContent[] = ' - '.$sTheme;
			}
		}

		$aContent[] = $this->getScannerButtonForEmail();
		$aContent[] = '';

		$sTo = $oFO->getPluginDefaultRecipientAddress();
		$sEmailSubject = sprintf( '%s - %s', __( 'Warning', 'wp-simple-firewall' ), __( 'Plugins/Themes Have Been Altered', 'wp-simple-firewall' ) );
		$this->getEmailProcessor()
			 ->sendEmailWithWrap( $sTo, $sEmailSubject, $aContent );

		$this->getCon()->fireEvent(
			'ptg_alert_sent',
			[
				'audit' => [
					'to'  => $sTo,
					'via' => 'email',
				]
			]
		);
	}

	/**
	 * @return Shield\Scans\Ptg\ResultsSet
	 */
	public function scanPlugins() {
		return $this->runSnapshotScan( self::CONTEXT_PLUGINS );
	}

	/**
	 * @return Shield\Scans\Ptg\ResultsSet
	 */
	public function scanThemes() {
		return $this->runSnapshotScan( self::CONTEXT_THEMES );
	}

	/**
	 * @param string $sContext
	 * @return Shield\Scans\Ptg\ResultsSet
	 */
	private function runSnapshotScan( $sContext = self::CONTEXT_PLUGINS ) {
		$aSnapHashes = $this->getStore( $sContext )->getSnapDataHashesOnly();
		return $this->getContextScanner( $sContext )->run( $aSnapHashes );
	}

	/**
	 * Since we can't track site assets while the plugin is inactive, our snapshots and results
	 * are unreliable once the plugin has been deactivated.
	 */
	public function resetScan() {
		parent::resetScan();
		try {
			// clear the snapshots
			$this->getStore_Themes()->deleteSnapshots();
			$this->getStore_Plugins()->deleteSnapshots();
		}
		catch ( \Exception $oE ) {
		}
	}
}