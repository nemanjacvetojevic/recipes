<?php
/** miniOrange enables user to log in through mobile authentication as an additional layer of security over password.
 * Copyright (C) 2015  miniOrange
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 * @package        miniOrange OAuth
 * @license        http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

/**
 * This library is miniOrange Authentication Service.
 * Contains Request Calls to Customer service.
 **/

include_once dirname( __FILE__ ) . '/mo2f_api.php';

class Customer_Setup {

	public $email;
	public $phone;
	public $customerKey;
	public $transactionId;

	private $auth_mode = 2;	//  miniorange test or not
	private $https_mode = false; // website http or https

    function check_customer() {
        $url = MO_HOST_NAME . "/moas/rest/customer/check-if-exists";
        $email = get_option( "mo2f_email" );
		$mo2fApi= new Mo2f_Api();
        $fields = array (
            'email' => $email
        );
        $field_string = json_encode ( $fields );

        $headers = array("Content-Type"=>"application/json","charset"=>"UTF-8","Authorization"=>"Basic");

        $response = $mo2fApi->make_curl_call( $url, $field_string );
        return $response;

    }

	function send_email_alert( $email, $phone, $message ) {

		$url = MO_HOST_NAME . '/moas/api/notify/send';
	
		$mo2fApi= new Mo2f_Api();
		$customerKey = "16555";
		$apiKey      = "fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq";

		$currentTimeInMillis = $mo2fApi->get_timestamp();
		$stringToHash        = $customerKey . $currentTimeInMillis . $apiKey;
		$hashValue           = hash( "sha512", $stringToHash );
		$fromEmail           = $email;
		$subject             = "WordPress 2FA Plugin Feedback - " . $email;

		global $user;
		$user                       = wp_get_current_user();
		$is_nc_with_1_user          = get_option( 'mo2f_is_NC' ) && get_option( 'mo2f_is_NNC' );
		$is_ec_with_1_user          = ! get_option( 'mo2f_is_NC' );


		$customer_feature = "";

		if ( $is_ec_with_1_user ) {
			$customer_feature = "V1";
		}else if ( $is_nc_with_1_user ) {
			$customer_feature = "V3";
		}

		$query = '[WordPress 2 Factor Authentication Plugin: ' . $customer_feature . ' - V '.MO2F_VERSION.']: ' . $message;

		$content = '<div >First Name :' . $user->user_firstname . '<br><br>Last  Name :' . $user->user_lastname . '   <br><br>Company :<a href="' . $_SERVER['SERVER_NAME'] . '" target="_blank" >' . $_SERVER['SERVER_NAME'] . '</a><br><br>Phone Number :' . $phone . '<br><br>Email :<a href="mailto:' . $fromEmail . '" target="_blank">' . $fromEmail . '</a><br><br>Query :' . $query . '</div>';

		$fields       = array(
			'customerKey' => $customerKey,
			'sendEmail'   => true,
			'email'       => array(
				'customerKey' => $customerKey,
				'fromEmail'   => $fromEmail,
				'fromName'    => 'Xecurify',
				'toEmail'     => '2fasupport@xecurify.com',
				'toName'      => '2fasupport@xecurify.com',
				'subject'     => $subject,
				'content'     => $content
			),
		);
		$field_string = json_encode( $fields );

        $headers = $mo2fApi->get_http_header_array();

        $response = $mo2fApi->make_curl_call( $url, $field_string, $headers );
        return $response;


	}

    function create_customer() {
        global $Mo2fdbQueries;
        if ( ! MO2f_Utility::is_curl_installed() ) {
            $message = 'Please enable curl extension. <a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_help">Click here</a> for the steps to enable curl or check Help & Troubleshooting.';

            return json_encode( array( "status" => 'ERROR', "message" => $message ) );
        }

        $url = MO_HOST_NAME . '/moas/rest/customer/add';
		$mo2fApi= new Mo2f_Api();
        global $user;
        $user        = wp_get_current_user();
        $this->email = get_option( 'mo2f_email' );
        $this->phone = $Mo2fdbQueries->get_user_detail( 'mo2f_user_phone', $user->ID );
        $password    = get_option( 'mo2f_password' );
        $company     = get_option( 'mo2f_admin_company' ) != '' ? get_option( 'mo2f_admin_company' ) : $_SERVER['SERVER_NAME'];

        $fields       = array(
            'companyName'     => $company,
            'areaOfInterest'  => 'WordPress 2 Factor Authentication Plugin',
            'productInterest' => 'API_2FA',
            'email'           => $this->email,
            'phone'           => $this->phone,
            'password'        => $password
        );
        $field_string = json_encode( $fields );
        $headers = array("Content-Type"=>"application/json","charset"=>"UTF-8","Authorization"=>"Basic");

        $content = $mo2fApi->make_curl_call( $url, $field_string );

        return $content;
    }


    function get_customer_key() {
		if ( ! MO2f_Utility::is_curl_installed() ) {
			$message = 'Please enable curl extension. <a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_help">Click here</a> for the steps to enable curl or check Help & Troubleshooting.';

			return json_encode( array( "status" => 'ERROR', "message" => $message ) );
		}

		$url      = MO_HOST_NAME . "/moas/rest/customer/key";
		
		$email    = get_option( "mo2f_email" );
		$password = get_option( "mo2f_password" );
		$mo2fApi= new Mo2f_Api();
		$fields       = array(
			'email'    => $email,
			'password' => $password
		);
		$field_string = json_encode( $fields );
        $headers = array("Content-Type"=>"application/json","charset"=>"UTF-8","Authorization"=>"Basic");

        $content = $mo2fApi->make_curl_call( $url, $field_string );

		return $content;
	}


    function send_otp_token( $uKey, $authType, $cKey, $apiKey ) {
		if ( ! MO2f_Utility::is_curl_installed() ) {
			$message = 'Please enable curl extension. <a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_help">Click here</a> for the steps to enable curl or check Help & Troubleshooting.';

			return json_encode( array( "status" => 'ERROR', "message" => $message ) );
		}

		$url = MO_HOST_NAME . '/moas/api/auth/challenge';
		$mo2fApi= new Mo2f_Api();
		/* The customer Key provided to you */
		$customerKey = $cKey;

		/* The customer API Key provided to you */
		$apiKey = $apiKey;

		/* Current time in milliseconds since midnight, January 1, 1970 UTC. */
		$currentTimeInMillis = $mo2fApi->get_timestamp();

		/* Creating the Hash using SHA-512 algorithm */
		$stringToHash = $customerKey . $currentTimeInMillis . $apiKey;
		$hashValue    = hash( "sha512", $stringToHash );

        $headers = $mo2fApi->get_http_header_array();

		$fields = '';
		if ( $authType == 'EMAIL' || $authType == 'OUT OF BAND EMAIL' ) {
			$fields = array(
				'customerKey'     => $customerKey,
				'email'           => $uKey,
				'authType'        => $authType,
				'transactionName' => 'WordPress 2 Factor Authentication Plugin'
			);
		} else if ( $authType == 'SMS' ) {
			$authType = "SMS";
			$fields   = array(
				'customerKey' => $customerKey,
				'phone'       => $uKey,
				'authType'    => $authType
			);
		} else {
			$fields = array(
				'customerKey'     => $customerKey,
				'username'        => $uKey,
				'authType'        => $authType,
				'transactionName' => 'WordPress 2 Factor Authentication Plugin'
			);
		}

		$field_string = json_encode( $fields );

        $args = array(
        'method' => 'POST',
        'body' => $field_string,
        'timeout' => '5',
        'redirection' => '5',
        'httpversion' => '1.0',
        'blocking' => true,
        'headers' => $headers
        );
        $content = $mo2fApi->make_curl_call( $url, $field_string, $headers );
		return $content;
	}


	function get_customer_transactions( $cKey, $apiKey ) {

		$url = MO_HOST_NAME . '/moas/rest/customer/license';

		$customerKey = $cKey;
		$apiKey      = $apiKey;
		$mo2fApi= new Mo2f_Api();
		$currentTimeInMillis = $mo2fApi->get_timestamp();
		$stringToHash = $customerKey . $currentTimeInMillis . $apiKey;
		$hashValue    = hash( "sha512", $stringToHash );

		$fields = '';
		$fields = array(
			'customerId'      => $customerKey,
			'applicationName' => 'wp_2fa',
			'licenseType'     => 'DEMO'
		);

		$field_string = json_encode( $fields );

        $headers = $mo2fApi->get_http_header_array();

        $content = $mo2fApi->make_curl_call( $url, $field_string, $headers );

		return $content;
	}


	function validate_otp_token( $authType, $username, $transactionId, $otpToken, $cKey, $customerApiKey ) {
		if ( ! MO2f_Utility::is_curl_installed() ) {
			$message = 'Please enable curl extension. <a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_help">Click here</a> for the steps to enable curl or check Help & Troubleshooting.';

			return json_encode( array( "status" => 'ERROR', "message" => $message ) );
		}

		$url = MO_HOST_NAME . '/moas/api/auth/validate';
		$mo2fApi= new Mo2f_Api();
		/* The customer Key provided to you */
		$customerKey = $cKey;

		/* The customer API Key provided to you */
		$apiKey = $customerApiKey;

		/* Current time in milliseconds since midnight, January 1, 1970 UTC. */
		$currentTimeInMillis = $mo2fApi->get_timestamp();

		/* Creating the Hash using SHA-512 algorithm */
		$stringToHash = $customerKey . $currentTimeInMillis . $apiKey;
		$hashValue    = hash( "sha512", $stringToHash );

        $headers = $mo2fApi->get_http_header_array();
		$fields = '';
		if ( $authType == 'SOFT TOKEN' || $authType == 'GOOGLE AUTHENTICATOR' ) {
			/*check for soft token*/
			$fields = array(
				'customerKey' => $customerKey,
				'username'    => $username,
				'token'       => $otpToken,
				'authType'    => $authType
			);
		} else if ( $authType == 'KBA' ) {
			$fields = array(
				'txId'    => $transactionId,
				'answers' => array(
					array(
						'question' => $otpToken[0],
						'answer'   => $otpToken[1]
					),
					array(
						'question' => $otpToken[2],
						'answer'   => $otpToken[3]
					)
				)
			);
		} else {
			//*check for otp over sms/email
			$fields = array(
				'txId'  => $transactionId,
				'token' => $otpToken
			);
		}
		$field_string = json_encode( $fields );


        $content = $mo2fApi->make_curl_call( $url, $field_string, $headers );

		return $content;
	}

	function submit_contact_us( $q_email, $q_phone, $query ) {
		if ( ! MO2f_Utility::is_curl_installed() ) {
			$message = 'Please enable curl extension. <a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_help">Click here</a> for the steps to enable curl or check Help & Troubleshooting.';

			return json_encode( array( "status" => 'ERROR', "message" => $message ) );
		}

		$url = MO_HOST_NAME . "/moas/rest/customer/contact-us";
		global $user;
		$user                       = wp_get_current_user();
		$is_nc_with_1_user          = get_option( 'mo2f_is_NC' ) && get_option( 'mo2f_is_NNC' );
		$is_ec_with_1_user          = ! get_option( 'mo2f_is_NC' );

		$mo2fApi= new Mo2f_Api();
		$customer_feature = "";

		if ( $is_ec_with_1_user ) {
			$customer_feature = "V1";
		} else if ( $is_nc_with_1_user ) {
			$customer_feature = "V3";
		}

		$query        = '[WordPress 2 Factor Authentication Plugin: ' . $customer_feature . ' - V '.MO2F_VERSION.']: ' . $query;
		$fields       = array(
			'firstName' => $user->user_firstname,
			'lastName'  => $user->user_lastname,
			'company'   => $_SERVER['SERVER_NAME'],
			'email'     => $q_email,
			'ccEmail' => '2fasupport@xecurify.com',
			'phone'     => $q_phone,
			'query'     => $query
		);
		$field_string = json_encode( $fields );

        $headers = array("Content-Type"=>"application/json","charset"=>"UTF-8","Authorization"=>"Basic");
		
        $content = $mo2fApi->make_curl_call( $url, $field_string );

		return true;
	}

}


?>