<?php

namespace FernleafSystems\Wordpress\Plugin\Shield\Scans\Base;

use FernleafSystems\Wordpress\Plugin\Shield;
use FernleafSystems\Wordpress\Services\Services;

abstract class BaseBuildScanAction {

	use Shield\Modules\ModConsumer,
		Shield\Scans\Common\ScanActionConsumer;

	/**
	 * @throws \Exception
	 */
	public function build() {
		/** @var BaseScanActionVO $oAction */
		$oAction = $this->getScanActionVO();
		if ( !$oAction instanceof BaseScanActionVO ) {
			throw new \Exception( 'Scan Action VO not provided.' );
		}
		if ( empty( $oAction->scan ) ) {
			throw new \Exception( 'Scan Slug not provided.' );
		}

		$this->setCustomFields();
		$this->buildScanItems();
		$this->setStandardFields();
	}

	/**
	 * @throws \Exception
	 */
	protected function buildScanItems() {
		/** @var BaseScanActionVO $oAction */
		$oAction = $this->getScanActionVO();
		$this->buildItems();
		$oAction->total_items = count( $oAction->items );
	}

	/**
	 */
	abstract protected function buildItems();

	/**
	 */
	protected function setStandardFields() {
		/** @var BaseScanActionVO $oAction */
		$oAction = $this->getScanActionVO();
		if ( empty( $oAction->created_at ) ) {
			$oAction->created_at = Services::Request()->ts();
			$oAction->started_at = 0;
			$oAction->finished_at = 0;
		}
	}

	/**
	 */
	protected function setCustomFields() {
	}
}