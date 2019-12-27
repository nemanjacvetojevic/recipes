<?php

namespace FernleafSystems\Wordpress\Plugin\Shield\Modules\IPs\BotTrack;

use FernleafSystems\Wordpress\Plugin\Shield;
use FernleafSystems\Wordpress\Plugin\Shield\Modules\IPs;
use FernleafSystems\Wordpress\Services\Services;

abstract class Base {

	use Shield\Modules\ModConsumer;
	const OPT_KEY = '';

	public function run() {
		$this->process();
	}

	protected function doTransgression() {
		/** @var IPs\Options $oOpts */
		$oOpts = $this->getOptions();

		if ( $oOpts->isTrackOptImmediateBlock( static::OPT_KEY ) ) {
			$bCount = PHP_INT_MAX;
		}
		else if ( $oOpts->isTrackOptTransgression( static::OPT_KEY ) ) {
			$bCount = $oOpts->isTrackOptDoubleTransgression( static::OPT_KEY ) ? 2 : 1;
		}
		else {
			$bCount = 0;
		}

		$this->getCon()
			 ->fireEvent(
				 'bot'.static::OPT_KEY,
				 [
					 'audit'         => $this->getAuditData(),
					 'offense_count' => $bCount
				 ]
			 );
	}

	/**
	 * @return array
	 */
	protected function getAuditData() {
		return [
			'path' => Services::Request()->getPath()
		];
	}

	abstract protected function process();
}
