<?php

namespace FernleafSystems\Wordpress\Plugin\Shield\Tables\Render;

use FernleafSystems\Wordpress\Plugin\Shield\Scans;

class ScanBase extends Base {

	/**
	 * @param array $aItem
	 * @return string
	 */
	public function column_path( $aItem ) {
		return sprintf( '<code>%s</code>', $aItem[ 'path' ] );
	}

	protected function extra_tablenav( $which ) {
		echo '';
	}

	/**
	 * @return string[]
	 */
	protected function get_table_classes() {
		return array_merge( parent::get_table_classes(), [ 'scan-table' ] );
	}

	/**
	 * @param string $sHref
	 * @return string
	 */
	protected function getActionButton_DownloadFile( $sHref ) {
		return $this->buildActionButton_Custom(
			__( 'Download', 'wp-simple-firewall' ),
			[ 'href-download', 'text-info' ],
			[ 'href-download' => $sHref ]
		);
	}

	/**
	 * @return array
	 */
	public function get_columns() {
		return [
			'path'       => __( 'File', 'wp-simple-firewall' ),
			'status'     => __( 'Status', 'wp-simple-firewall' ),
			'created_at' => __( 'Discovered', 'wp-simple-firewall' ),
		];
	}
}