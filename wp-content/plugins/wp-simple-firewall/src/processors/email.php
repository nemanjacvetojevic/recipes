<?php

use FernleafSystems\Wordpress\Plugin\Shield\Modules;
use FernleafSystems\Wordpress\Services\Services;

class ICWP_WPSF_Processor_Email extends Modules\BaseShield\ShieldProcessor {

	const Slug = 'email';

	/**
	 * @var string
	 */
	static protected $sModeFile_EmailThrottled;

	/**
	 * @var int
	 */
	static protected $nThrottleInterval = 1;

	/**
	 * @var int
	 */
	protected $nEmailThrottleLimit;

	/**
	 * @var int
	 */
	protected $nEmailThrottleTime;

	/**
	 * @var int
	 */
	protected $nEmailThrottleCount;

	/**
	 * @var boolean
	 */
	protected $bEmailIsThrottled;

	public function init() {
		parent::init();
		self::$sModeFile_EmailThrottled = path_join( __DIR__, '/../mode.email_throttled' );
	}

	public function run() {
	}

	/**
	 * @return array
	 */
	protected function getEmailHeader() {
		return [
			__( 'Hi !', 'wp-simple-firewall' ),
			'',
		];
	}

	/**
	 * @return array
	 */
	protected function getEmailFooter() {
		$oWp = Services::WpGeneral();
		$sUrl = [
			'',
			sprintf( __( 'Email sent from the %s Plugin v%s, on %s.', 'wp-simple-firewall' ),
				$this->getCon()->getHumanName(),
				$this->getCon()->getVersion(),
				$oWp->getHomeUrl()
			),
			__( 'Note: Email delays are caused by website hosting and email providers.', 'wp-simple-firewall' ),
			sprintf( __( 'Time Sent: %s', 'wp-simple-firewall' ), $oWp->getTimeStampForDisplay() )
		];

		return apply_filters( 'icwp_shield_email_footer', $sUrl );
	}

	/**
	 * Wraps up a message with header and footer
	 * @param string $sAddress
	 * @param string $sSubject
	 * @param array  $aMessage
	 * @return boolean
	 */
	public function sendEmailWithWrap( $sAddress = '', $sSubject = '', $aMessage = [] ) {
		return $this->send(
			$sAddress,
			wp_specialchars_decode( sprintf( '[%s] %s', Services::WpGeneral()->getSiteName(), $sSubject ) ),
			'<html>'.implode( "<br />", array_merge( $this->getEmailHeader(), $aMessage, $this->getEmailFooter() ) ).'</html>'
		);
	}

	/**
	 * @param string $sAddress
	 * @param string $sSubject
	 * @param string $sMessageBody
	 * @return bool
	 * @uses wp_mail
	 */
	public function send( $sAddress = '', $sSubject = '', $sMessageBody = '' ) {
		$this->updateEmailThrottle();
		if ( $this->bEmailIsThrottled ) {
			return true;
		}

		$this->emailFilters( true );
		$bSuccess = wp_mail(
			$this->verifyEmailAddress( $sAddress ),
			$sSubject,
			$sMessageBody
		);
		$this->emailFilters( false );

		return $bSuccess;
	}

	/**
	 * @param $bAdd - true to add, false to remove
	 */
	protected function emailFilters( $bAdd ) {
		if ( $bAdd ) {
			add_filter( 'wp_mail_from', [ $this, 'setMailFrom' ], 100 );
			add_filter( 'wp_mail_from_name', [ $this, 'setMailFromName' ], 100 );
			add_filter( 'wp_mail_content_type', [ $this, 'setMailContentType' ], 100, 0 );
		}
		else {
			remove_filter( 'wp_mail_from', [ $this, 'setMailFrom' ], 100 );
			remove_filter( 'wp_mail_from_name', [ $this, 'setMailFromName' ], 100 );
			remove_filter( 'wp_mail_content_type', [ $this, 'setMailContentType' ], 100 );
		}
	}

	/**
	 * @return string
	 */
	public function setMailContentType() {
		return 'text/html';
	}

	/**
	 * @param string $sFrom
	 * @return string
	 */
	public function setMailFrom( $sFrom ) {
		$oDP = Services::Data();
		$sProposedFrom = apply_filters( 'icwp_shield_from_email', '' );
		if ( $oDP->validEmail( $sProposedFrom ) ) {
			$sFrom = $sProposedFrom;
		}
		// We help out by trying to correct any funky "from" addresses
		// So, at the very least, we don't fail on this for our emails.
		if ( !$oDP->validEmail( $sFrom ) ) {
			$aUrlParts = @parse_url( Services::WpGeneral()->getWpUrl() );
			if ( !empty( $aUrlParts[ 'host' ] ) ) {
				$sProposedFrom = 'wordpress@'.$aUrlParts[ 'host' ];
				if ( $oDP->validEmail( $sProposedFrom ) ) {
					$sFrom = $sProposedFrom;
				}
			}
		}
		return $sFrom;
	}

	/**
	 * @param string $sFromName
	 * @return string
	 */
	public function setMailFromName( $sFromName ) {
		$sProposedFromName = apply_filters( 'icwp_shield_from_email_name', '' );
		if ( !empty( $sProposedFromName ) ) {
			$sFromName = $sProposedFromName;
		}
		else {
			$sFromName = sprintf( '%s - %s',
				Services::WpGeneral()->getSiteName(),
				$this->getCon()->getHumanName()
			);
		}
		return $sFromName;
	}

	/**
	 * Will send email to the default recipient setup in the object.
	 * @param string $sEmailSubject
	 * @param array  $aMessage
	 * @return boolean
	 */
	public function sendEmail( $sEmailSubject, $aMessage ) {
		return $this->sendEmailWithWrap( null, $sEmailSubject, $aMessage );
	}

	/**
	 * Whether we're throttled is dependent on 2 signals.  The time interval has changed, or the there's a file
	 * system object telling us we're throttled.
	 * The file system object takes precedence.
	 * @return bool
	 */
	protected function updateEmailThrottle() {
		$nNow = Services::Request()->ts();

		// Throttling Is Effectively Off
		if ( $this->getThrottleLimit() <= 0 ) {
			$this->setThrottledFile( false );
			return $this->bEmailIsThrottled;
		}

		// Check that there is an email throttle file. If it exists and its modified time is greater than the 
		// current $this->m_nEmailThrottleTime it suggests another process has touched the file and updated it
		// concurrently. So, we update our $this->m_nEmailThrottleTime accordingly.
		if ( is_file( self::$sModeFile_EmailThrottled ) ) {
			$nModifiedTime = filemtime( self::$sModeFile_EmailThrottled );
			if ( $nModifiedTime > $this->nEmailThrottleTime ) {
				$this->nEmailThrottleTime = $nModifiedTime;
			}
		}

		if ( !isset( $this->nEmailThrottleTime ) || $this->nEmailThrottleTime > $nNow ) {
			$this->nEmailThrottleTime = $nNow;
		}
		if ( !isset( $this->nEmailThrottleCount ) ) {
			$this->nEmailThrottleCount = 0;
		}

		// If $nNow is greater than throttle interval (1s) we turn off the file throttle and reset the count
		$nDiff = $nNow - $this->nEmailThrottleTime;
		if ( $nDiff > self::$nThrottleInterval ) {
			$this->nEmailThrottleTime = $nNow;
			$this->nEmailThrottleCount = 1;    //we set to 1 assuming that this was called because we're about to send, or have just sent, an email.
			$this->setThrottledFile( false );
		}
		else if ( is_file( self::$sModeFile_EmailThrottled ) || ( $this->nEmailThrottleCount >= $this->getThrottleLimit() ) ) {
			$this->setThrottledFile( true );
		}
		else {
			$this->nEmailThrottleCount++;
		}
		return true;
	}

	public function setThrottledFile( $infOn = false ) {

		$this->bEmailIsThrottled = $infOn;

		if ( $infOn && !is_file( self::$sModeFile_EmailThrottled ) && function_exists( 'touch' ) ) {
			@touch( self::$sModeFile_EmailThrottled );
		}
		else if ( !$infOn && is_file( self::$sModeFile_EmailThrottled ) ) {
			@unlink( self::$sModeFile_EmailThrottled );
		}
	}

	/**
	 * @param string $sEmail
	 * @return string
	 */
	public function verifyEmailAddress( $sEmail = '' ) {
		return Services::Data()->validEmail( $sEmail ) ? $sEmail : Services::WpGeneral()->getSiteAdminEmail();
	}

	public function getThrottleLimit() {
		if ( empty( $this->nEmailThrottleLimit ) ) {
			$this->nEmailThrottleLimit = $this->getOption( 'send_email_throttle_limit' );
		}
		return $this->nEmailThrottleLimit;
	}
}