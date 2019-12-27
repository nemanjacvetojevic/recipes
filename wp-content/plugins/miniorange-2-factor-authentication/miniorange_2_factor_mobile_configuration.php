<?php

include dirname( __FILE__ ) . '/views/configure_google_authenticator';
include dirname( __FILE__ ) . '/views/configure_authy_authenticator';
include dirname( __FILE__ ) . '/views/configure_miniorange_authenticator';
include dirname( __FILE__ ) . '/views/configure_kba_questions';
include dirname( __FILE__ ) . '/views/configure_otp_over_sms';
include dirname( __FILE__ ) . '/views/test_miniorange_qr_code_authentication';
include dirname( __FILE__ ) . '/views/test_miniorange_soft_token';
include dirname( __FILE__ ) . '/views/test_miniorange_push_notification';
include dirname( __FILE__ ) . '/views/test_otp_over_sms';
include dirname( __FILE__ ) . '/views/test_kba_security_questions';
include dirname( __FILE__ ) . '/views/test_email_verification';
include dirname( __FILE__ ) . '/views/test_google_authy_authenticator';


function mo2f_update_and_sync_user_two_factor( $user_id, $userinfo ) {
	global $Mo2fdbQueries;
	$mo2f_second_factor = isset( $userinfo['authType'] ) && ! empty( $userinfo['authType'] ) ? $userinfo['authType'] : 'NONE';

	if ( $mo2f_second_factor == 'OUT OF BAND EMAIL' ) {
		$Mo2fdbQueries->update_user_details( $user_id, array( 'mo2f_EmailVerification_config_status' => true ) );
	} else if ( $mo2f_second_factor == 'SMS' ) {
		$phone_num = $userinfo['phone'];
		$Mo2fdbQueries->update_user_details( $user_id, array( 'mo2f_OTPOverSMS_config_status' => true ) );
		$_SESSION['user_phone'] = $phone_num;
	} else if ( in_array( $mo2f_second_factor, array(
		'SOFT TOKEN',
		'MOBILE AUTHENTICATION',
		'PUSH NOTIFICATIONS'
	) ) ) {
		$Mo2fdbQueries->update_user_details( $user_id, array(
			'mo2f_miniOrangeSoftToken_config_status'            => true,
			'mo2f_miniOrangeQRCodeAuthentication_config_status' => true,
			'mo2f_miniOrangePushNotification_config_status'     => true
		) );
	} else if ( $mo2f_second_factor == 'KBA' ) {
		$Mo2fdbQueries->update_user_details( $user_id, array( 'mo2f_SecurityQuestions_config_status' => true ) );
	} else if ( $mo2f_second_factor == 'GOOGLE AUTHENTICATOR' ) {
		$app_type = get_user_meta( $user_id, 'mo2f_external_app_type', true );

		if ( $app_type == 'Google Authenticator' ) {
			$Mo2fdbQueries->update_user_details( $user_id, array(
				'mo2f_GoogleAuthenticator_config_status' => true
			) );
			update_user_meta( $user_id, 'mo2f_external_app_type', 'Google Authenticator' );
		} else if ( $app_type == 'Authy Authenticator' ) {
			$Mo2fdbQueries->update_user_details( $user_id, array(
				'mo2f_AuthyAuthenticator_config_status' => true
			) );
			update_user_meta( $user_id, 'mo2f_external_app_type', 'Authy Authenticator' );
		} else {
			$Mo2fdbQueries->update_user_details( $user_id, array(
				'mo2f_GoogleAuthenticator_config_status' => true
			) );

			update_user_meta( $user_id, 'mo2f_external_app_type', 'Google Authenticator' );
		}
	}

	return $mo2f_second_factor;
}

function mo2f_get_activated_second_factor( $user ) {
	global $Mo2fdbQueries;
	$user_registration_status = $Mo2fdbQueries->get_user_detail( 'mo_2factor_user_registration_status', $user->ID );
	$is_customer_registered   = $Mo2fdbQueries->get_user_detail( 'user_registration_with_miniorange', $user->ID ) == 'SUCCESS' ? true : false;
	$useremail                = $Mo2fdbQueries->get_user_detail( 'mo2f_user_email', $user->ID );

	if ( $user_registration_status == 'MO_2_FACTOR_SUCCESS' ) {
		//checking this option for existing users
		$Mo2fdbQueries->update_user_details( $user->ID, array( 'mobile_registration_status' => true ) );
		$mo2f_second_factor = 'MOBILE AUTHENTICATION';

		return $mo2f_second_factor;
	} else if ( $user_registration_status == 'MO_2_FACTOR_INITIALIZE_TWO_FACTOR' ) {
		return 'NONE';
	} else {
		//for new users
		if ( $user_registration_status == 'MO_2_FACTOR_PLUGIN_SETTINGS' && $is_customer_registered ) {
			$enduser  = new Two_Factor_Setup();
			$userinfo = json_decode( $enduser->mo2f_get_userinfo( $useremail ), true );

			if ( json_last_error() == JSON_ERROR_NONE ) {
				if ( $userinfo['status'] == 'ERROR' ) {
					update_option( 'mo2f_message', Mo2fConstants:: langTranslate( $userinfo['message'] ) );
					$mo2f_second_factor = 'NONE';
				} else if ( $userinfo['status'] == 'SUCCESS' ) {
					$mo2f_second_factor = mo2f_update_and_sync_user_two_factor( $user->ID, $userinfo );
				} else if ( $userinfo['status'] == 'FAILED' ) {
					$mo2f_second_factor = 'NONE';
					update_option( 'mo2f_message', Mo2fConstants:: langTranslate( "ACCOUNT_REMOVED" ) );
				} else {
					$mo2f_second_factor = 'NONE';
				}
			} else {
				update_option( 'mo2f_message', Mo2fConstants:: langTranslate( "INVALID_REQ" ) );
				$mo2f_second_factor = 'NONE';
			}
		} else {
			$mo2f_second_factor = 'NONE';
		}

		return $mo2f_second_factor;
	}
}

function mo_2factor_is_curl_installed() {
	if ( in_array( 'curl', get_loaded_extensions() ) ) {
		return 1;
	} else {
		return 0;
	}
}

function show_user_welcome_page( $user ) {
	?>
    <form name="f" method="post" action="">
        <div class="mo2f_table_layout">
            <div>
                <center>
                    <p style="font-size:17px;"><?php echo mo2f_lt( 'A new security system has been enabled to better protect your account. Please configure your Two-Factor Authentication method by setting up your account.' ); ?></p>
                </center>
            </div>
            <div id="panel1">
                <table class="mo2f_settings_table">

                    <tr>
                        <td>
                            <center>
                                <div class="alert-box"><input type="email" autofocus="true" name="mo_useremail"
                                                              style="width:48%;text-align: center;height: 40px;font-size:18px;border-radius:5px;"
                                                              required
                                                              placeholder="<?php echo mo2f_lt( 'Email' ); ?>"
                                                              value="<?php echo $user->user_email; ?>"/></div>
                            </center>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <center>
                                <p><?php echo mo2f_lt( 'Please enter a valid email id that you have access to. You will be able to move forward after verifying an OTP that we will be sending to this email' ); ?>
                                    .</p></center>
                        </td>
                    </tr>
                    <tr>
                        <td><input type="hidden" name="miniorange_user_reg_nonce"
                                   value="<?php echo wp_create_nonce( 'miniorange-2-factor-user-reg-nonce' ); ?>"/>
                            <center><input type="submit" name="miniorange_get_started" id="miniorange_get_started"
                                           class="button button-primary button-large extra-large"
                                           value="<?php echo mo2f_lt( 'Get Started' ); ?>"/>
                            </center>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </form>
	<?php
}

function mo2f_show_user_otp_validation_page() {
	?>
    <!-- Enter otp -->

    <div class="mo2f_table_layout">
        <h3><?php echo mo2f_lt( 'Validate OTP' ); ?></h3>
        <hr>
        <div id="panel1">
            <table class="mo2f_settings_table">
                <form name="f" method="post" id="mo_2f_otp_form" action="">
                    <input type="hidden" name="option" value="mo_2factor_validate_user_otp"/>
					<input type="hidden" name="mo_2factor_validate_user_otp_nonce"
                   value="<?php echo wp_create_nonce( "mo-2factor-validate-user-otp-nonce" ) ?>"/>
                    <tr>
                        <td>
                            <b><font color="#FF0000">*</font><?php echo mo2f_lt( 'Enter OTP:' ); ?>
                            </b></td>
                        <td colspan="2"><input class="mo2f_table_textbox" autofocus="true" type="text" name="otp_token"
                                               required
                                               placeholder="<?php echo mo2f_lt( 'Enter OTP' ); ?>"
                                               style="width:95%;"/></td>
                        <td>
                            <a href="#resendotplink"><?php echo mo2f_lt( 'Resend OTP ?' ); ?></a>
                        </td>
                    </tr>

                    <tr>
                        <td>&nbsp;</td>
                        <td style="width:17%">
                            <input type="submit" name="submit"
                                   value="<?php echo mo2f_lt( 'Validate OTP' ); ?>"
                                   class="button button-primary button-large"/></td>

                </form>
                <form name="f" method="post" action="">
                    <td>
                        <input type="hidden" name="option" value="mo_2factor_backto_user_registration"/>
						<input type="hidden" name="mo_2factor_backto_user_registration_nonce"
                   value="<?php echo wp_create_nonce( "mo-2factor-backto-user-registration-nonce" ) ?>"/>
                        <input type="submit" name="mo2f_goback" id="mo2f_goback"
                               value="<?php echo mo2f_lt( 'Back' ); ?>"
                               class="button button-primary button-large"/></td>
                </form>
                </td>
                </tr>
                <form name="f" method="post" action="" id="resend_otp_form">
                    <input type="hidden" name="option" value="mo_2factor_resend_user_otp"/>
					<input type="hidden" name="mo_2factor_resend_user_otp_nonce"
                   value="<?php echo wp_create_nonce( "mo-2factor-resend-user-otp-nonce" ) ?>"/>
                </form>

            </table>
        </div>
        <div>
            <script>
                jQuery('a[href=\"#resendotplink\"]').click(function (e) {
                    jQuery('#resend_otp_form').submit();
                });
            </script>

            <br><br>
        </div>


    </div>

	<?php
}

function mo2f_show_instruction_to_allusers( $user, $mo2f_second_factor ) {
	global $Mo2fdbQueries;

	$user_registration_status = $Mo2fdbQueries->get_user_detail( 'mo_2factor_user_registration_status', $user->ID );
	$user_email               = $Mo2fdbQueries->get_user_detail( 'mo2f_user_email', $user->ID );
	if ( $mo2f_second_factor == 'GOOGLE AUTHENTICATOR' ) {

		$app_type = get_user_meta( $user->ID, 'mo2f_external_app_type', true );
		if ( $app_type == 'Google Authenticator' ) {
			$mo2f_second_factor = 'Google Authenticator';
		} else if ( $app_type == 'Authy Authenticator' ) {
			$mo2f_second_factor = 'Authy Authenticator';
		} else {
			$mo2f_second_factor = 'Google Authenticator';
			update_user_meta( $user->ID, 'mo2f_external_app_type', $mo2f_second_factor );

		}
	} else {
		$mo2f_second_factor = MO2f_Utility::mo2f_decode_2_factor( $mo2f_second_factor, "servertowpdb" );
	}
	?>
	<?php if ( current_user_can( 'manage_options' ) == false ) { ?>
        <div><?php } ?>

    <div class="mo2f_table_layout" style="width: 100%;border:0px;">

        <h3><?php echo mo2f_lt( 'Your Profile' ); ?></h3>
        <table border="1"
               style="background-color:#FFFFFF; border:1px solid #CCCCCC; border-collapse: collapse; padding:0px 0px 0px 10px; margin:2px; width:100%">
			<?php if ( current_user_can( 'manage_options' ) && get_option( 'mo2f_miniorange_admin' ) == $user->ID ) { ?>
                <tr>
                    <td style="width:45%; padding: 10px;">
                        <b>miniOrange <?php echo mo2f_lt( 'Customer Email' ); ?></b>
                    </td>
                    <td style="width:55%; padding: 10px;"><?php echo get_option( 'mo2f_email' ); ?></td>
                </tr>
                <tr>
                    <td style="width:45%; padding: 10px;">
                        <b><?php echo mo2f_lt( 'Customer ID' ); ?></b></td>
                    <td style="width:55%; padding: 10px;"><?php echo get_option( 'mo2f_customerKey' ); ?></td>
                </tr>


				<?php
			} else {
				?>
                <tr>
                    <td style="width:45%; padding: 10px;">
                        <b><?php echo mo2f_lt( 'User Email Registered with miniOrange' ); ?></b></td>

                    <td style="width:55%; padding: 10px;"><?php echo $user_email ?></td>
                </tr>
			<?php } ?>

            <tr>
                <td style="width:45%; padding: 10px;">
                    <b><?php echo mo2f_lt( 'Activated 2nd Factor' ); ?></b></td>
                <td style="width:55%; padding: 10px;"><?php echo $mo2f_second_factor; ?>
                </td>
            </tr>

            <tr>
                <td style="width:45%; padding: 10px;">
                    <b><?php echo mo2f_lt( 'Wordpress user who has 2 factor enabled' ); ?></b>
                </td>
                <td style="width:55%; padding: 10px;"><?php echo $user->user_login; ?>
                </td>
            </tr>

			<?php if ( current_user_can( 'manage_options' ) && get_option( 'mo2f_miniorange_admin' ) == $user->ID ) { ?>
                <tr style="height:40px;">
                    <td style="border-right-color:white;" colspan="2"><a
                                target="_blank"
                                href="https://login.xecurify.com/moas/idp/resetpassword"><b>&nbsp; <?php echo mo2f_lt( 'Click Here' ); ?>
                        </a> <?php echo mo2f_lt( " to reset your miniOrange account's password." ); ?></b>
                    </td>

                </tr>
			<?php } ?>

        </table>
        <br>

    <a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=proxy_setup"
                   id="mo2f_tab5"><?php echo mo2f_lt( 'Click here' ); ?></a><?php echo mo2f_lt( ' if you need to setup a Proxy.' ); ?>
    </div>
	<?php
}

function mo2f_show_registration_screen($user){
	global $Mo2fdbQueries;
	$mo2f_current_registration_status = $Mo2fdbQueries->get_user_detail( 'mo_2factor_user_registration_status', $user->ID);

	if(in_array($mo2f_current_registration_status, array("MO_2_FACTOR_OTP_DELIVERED_SUCCESS", "MO_2_FACTOR_OTP_DELIVERED_FAILURE"))){
		mo2f_show_otp_validation_page( $user );
	}else if($mo2f_current_registration_status == "MO_2_FACTOR_VERIFY_CUSTOMER"){
		mo2f_show_verify_password_page();
    }else if($mo2f_current_registration_status == "REGISTRATION_STARTED"){
		mo2f_show_registration_page( $user );
	}
}

function mo2f_show_2FA_configuration_screen( $user, $selected2FAmethod ) {

	switch ( $selected2FAmethod ) {
		case "Google Authenticator":
			Miniorange_Authentication::mo2f_get_GA_parameters($user);
			mo2f_configure_google_authenticator( $user );
			break;
		case "Authy Authenticator":
			mo2f_configure_authy_authenticator( $user );
			break;
		case "Security Questions":
			mo2f_configure_for_mobile_suppport_kba( $user );
			break;
		case "Email Verification":
			mo2f_configure_for_mobile_suppport_kba( $user );
			break;
		case "OTP Over SMS":
			mo2f_configure_otp_over_sms( $user );
			break;
		case "miniOrange Soft Token":
			mo2f_configure_miniorange_authenticator( $user );
			break;
		case "miniOrange QR Code Authentication":
			mo2f_configure_miniorange_authenticator( $user );
			break;
		case "miniOrange Push Notification":
			mo2f_configure_miniorange_authenticator( $user );
			break;
	}

}

function mo2f_show_2FA_test_screen( $user, $selected2FAmethod ) {

	switch ( $selected2FAmethod ) {
		case "miniOrange QR Code Authentication":
			mo2f_test_miniorange_qr_code_authentication( $user );
			break;
		case "miniOrange Push Notification":
			mo2f_test_miniorange_push_notification( $user );
			break;
		case "miniOrange Soft Token":
			mo2f_test_miniorange_soft_token( $user );
			break;
		case "Email Verification":
			mo2f_test_email_verification();
			break;
		case "OTP Over SMS":
			mo2f_test_otp_over_sms( $user );
			break;
		case "Security Questions":
			mo2f_test_kba_security_questions( $user );
			break;
		default:
			mo2f_test_google_authy_authenticator( $user, $selected2FAmethod );
	}

}

function mo2f_method_display_name($user,$mo2f_second_factor){
	
	if ( $mo2f_second_factor == 'GOOGLE AUTHENTICATOR' ) {
		$app_type = get_user_meta( $user->ID, 'mo2f_external_app_type', true );

		if ( $app_type == 'Google Authenticator' ) {
			$selectedMethod = 'Google Authenticator';
		} else if ( $app_type == 'Authy Authenticator' ) {
			$selectedMethod = 'Authy Authenticator';
		} else {
			$selectedMethod = 'Google Authenticator';
			update_user_meta( $user->ID, 'mo2f_external_app_type', $selectedMethod );
		}
	} else {
		$selectedMethod = MO2f_Utility::mo2f_decode_2_factor( $mo2f_second_factor, "servertowpdb" );
	}
	return $selectedMethod;

}

function mo2f_video_guide(){?>
	<div style="margin:4%;">
		<span style="font-weight:bold;font-size:18px;">How to configure Two Factor Method?</span>
		<p>You can configure any method of your choice. We will give a demo on how we can configure Google Authenticator.
		<div style="margin:2%;">
			<iframe width="560" height="315" src="https://www.youtube.com/embed/vVGXjedIaGs" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
		</div>
	</div>
<?php }

function mo2f_select_2_factor_method( $user, $mo2f_second_factor ) {
	global $Mo2fdbQueries;

	$is_customer_admin_registered = get_option( 'mo_2factor_admin_registration_status' );
	$configured_2FA_method        = $Mo2fdbQueries->get_user_detail( 'mo2f_configured_2FA_method', $user->ID );

	if ( $mo2f_second_factor == 'GOOGLE AUTHENTICATOR' ) {
		$app_type = get_user_meta( $user->ID, 'mo2f_external_app_type', true );

		if ( $app_type == 'Google Authenticator' ) {
			$selectedMethod = 'Google Authenticator';
		} else if ( $app_type == 'Authy Authenticator' ) {
			$selectedMethod = 'Authy Authenticator';
		} else {
			$selectedMethod = 'Google Authenticator';
			update_user_meta( $user->ID, 'mo2f_external_app_type', $selectedMethod );
		}
		$testMethod=$selectedMethod;
	} else {
		$selectedMethod = MO2f_Utility::mo2f_decode_2_factor( $mo2f_second_factor, "servertowpdb" );
		$testMethod=$selectedMethod;
	}
				
	if($testMethod=='NONE'){
				$testMethod = "Not Configured"; 
		}
	if ( $selectedMethod !== 'NONE' ) {
		$Mo2fdbQueries->update_user_details( $user->ID, array(
			'mo2f_configured_2FA_method'                                         => $selectedMethod,
			'mo2f_' . str_replace( ' ', '', $selectedMethod ) . '_config_status' => true
		) );
		update_option('mo2f_configured_2_factor_method', $selectedMethod);
	}

	if ( $configured_2FA_method == "OTP Over SMS" ) {
		update_option( 'mo2f_show_sms_transaction_message', 1 );
	} else {
		update_option( 'mo2f_show_sms_transaction_message', 0 );
	} 
	$is_customer_admin          = current_user_can( 'manage_options' ) && get_option( 'mo2f_miniorange_admin' ) == $user->ID;
	$can_display_admin_features = ! $is_customer_admin_registered || $is_customer_admin ? true : false;

	$is_customer_registered = $Mo2fdbQueries->get_user_detail( 'user_registration_with_miniorange', $user->ID ) == 'SUCCESS' ? true : false;
	if ( get_user_meta( $user->ID, 'configure_2FA', true ) ) {

		$current_selected_method = get_user_meta( $user->ID, 'mo2f_2FA_method_to_configure', true ); ?>
        <div class="mo2f_setup_2_factor_tab">
			<?php mo2f_show_2FA_configuration_screen( $user, $current_selected_method ); ?>
        </div>
	<?php } else if ( get_user_meta( $user->ID, 'test_2FA', true ) ) {

		$current_selected_method = get_user_meta( $user->ID, 'mo2f_2FA_method_to_test', true ); ?>
        <div class="mo2f_setup_2_factor_tab">
			<?php mo2f_show_2FA_test_screen( $user, $current_selected_method ); ?>
        </div>
	<?php }else if ( get_user_meta( $user->ID, 'register_account', true ) && $can_display_admin_features ) {
		display_customer_registration_forms( $user ); ?>

	<?php } else {
		$is_NC = get_option( 'mo2f_is_NC' );

		?>
        <div style="width:93%;">
            <?php

			$free_plan_existing_user = array(
				"Email Verification",
				"OTP Over SMS",
				"Security Questions",
				"miniOrange QR Code Authentication",
				"miniOrange Soft Token",
				"miniOrange Push Notification",
				"Google Authenticator",
				"Authy Authenticator"

			);

			$free_plan_new_user = array(
				"Google Authenticator",
				"Security Questions",
				"miniOrange Soft Token",
				"miniOrange QR Code Authentication",
				"miniOrange Push Notification"
			);

			$standard_plan_existing_user = array(
			        "",
				"OTP Over Email",
				"OTP Over SMS and Email"
			);

			$standard_plan_new_user = array(
			        "",
				"Email Verification",
				"OTP Over SMS",
				"OTP Over Email",
				"OTP Over SMS and Email",
				"Authy Authenticator"
			);

			$premium_plan = array(
				"Hardware Token"
			);


			$free_plan_methods_existing_user     = array_chunk( $free_plan_existing_user, 3 );
			$free_plan_methods_new_user          = array_chunk( $free_plan_new_user, 3 );
			$standard_plan_methods_existing_user = array_chunk( $standard_plan_existing_user, 3 );
			$standard_plan_methods_new_user      = array_chunk( $standard_plan_new_user, 3 );
			$premium_plan_methods_existing_user  = array_chunk( array_merge( $standard_plan_existing_user, $premium_plan ), 3 );
			$premium_plan_methods_new_user       = array_chunk( array_merge( $standard_plan_new_user, $premium_plan ), 3 );
			?>
            <div class="mo2f_setup_2factor_tab">

                <div>

                    <div>
                        <a class="mo2f_view_free_plan_auth_methods" onclick="show_free_plan_auth_methods()">
                            <img src="<?php echo plugins_url( 'includes/images/right-arrow.png"', __FILE__ ); ?>"
                                 class="mo2f_2factor_heading_images" style="margin-top: 2px;"/>
                            <p class="mo2f_heading_style" style="padding:0px;"><?php echo mo2f_lt( 'Authentication methods' ); ?>
								<?php if ( $can_display_admin_features ) { ?>
                                    <span style="color:limegreen">( <?php echo mo2f_lt( 'Current Plan' ); ?> )</span>
								<?php } ?>
								<button class="button button-primary button-large" id="test" style="float:right;" onclick="testAuthenticationMethod('<?php echo $selectedMethod; ?>');"
								<?php echo $is_customer_registered && ( $selectedMethod != 'NONE' ) ? "" : " disabled "; ?>>Test : <?php echo $testMethod;?> 
								</button>
                            </p>
                        </a>
						

                    </div>
					<?php 
				if ( in_array( $selectedMethod, array(
					"Google Authenticator",
					"miniOrange Soft Token",
					"Authy Authenticator"
				) ) ) { ?>
                    <div style="float:right;">
                        <form name="f" method="post" action="" id="mo2f_enable_2FA_on_login_page_form">
                            <input type="hidden" name="option" value="mo2f_enable_2FA_on_login_page_option"/>
							<input type="hidden" name="mo2f_enable_2FA_on_login_page_option_nonce"
							value="<?php echo wp_create_nonce( "mo2f-enable-2FA-on-login-page-option-nonce" ) ?>"/>

                            <input type="checkbox" id="mo2f_enable_2fa_prompt_on_login_page"
                                   name="mo2f_enable_2fa_prompt_on_login_page"
                                   value="1" <?php checked( get_option( 'mo2f_enable_2fa_prompt_on_login_page' ) == 1 );

							if ( ! in_array( $Mo2fdbQueries->get_user_detail( 'mo_2factor_user_registration_status', $user->ID ), array(
								'MO_2_FACTOR_PLUGIN_SETTINGS',
								'MO_2_FACTOR_INITIALIZE_TWO_FACTOR'
							) ) ) {
								echo 'disabled';
							} ?> onChange="this.form.submit()"/>
							<?php echo mo2f_lt( 'Enable 2FA prompt on the WP Login Page' ); ?>
                        </form>
                    </div>
                    <br><br>
					<?php
				}

					 echo mo2f_create_2fa_form( $user, "free_plan", $is_NC ? $free_plan_methods_new_user : $free_plan_methods_existing_user, $can_display_admin_features ); ?>

                </div>
                <hr>
				<?php if ( $can_display_admin_features ) { ?>
                    <div id="mo2f_standard_plan">
                        <a class="mo2f_view_standard_plan_auth_methods" onclick="show_standard_plan_auth_methods()">
                            <img src="<?php echo plugins_url( 'includes/images/right-arrow.png"', __FILE__ ); ?>"
                                 class="mo2f_2factor_heading_images"/>
                            <p class="mo2f_heading_style"><span > <?php echo mo2f_lt( 'Standard plan - Authentication methods' ); ?>
                                *</span></p>
                        </a>
						<?php echo mo2f_create_2fa_form( $user, "standard_plan", $is_NC ? $standard_plan_methods_new_user : $standard_plan_methods_existing_user ); ?>
                    </div>
                    <hr>
                    <div>
                       <span id="mo2f_premium_plan"> <a class="mo2f_view_premium_plan_auth_methods" onclick="show_premium_auth_methods()">
                            <img src="<?php echo plugins_url( 'includes/images/right-arrow.png"', __FILE__ ); ?>"
                                 class="mo2f_2factor_heading_images"/>
                            <p class="mo2f_heading_style"><?php echo mo2f_lt( 'Premium plan - Authentication methods' ); ?>
                                    *</span></p>
                        </a>
						<?php echo mo2f_create_2fa_form( $user, "premium_plan", $is_NC ? $premium_plan_methods_new_user : $premium_plan_methods_existing_user ); ?>

                    </div>
                    <hr>

                    <br>
                    <p>
                        * <?php echo mo2f_lt( 'These authentication methods are available in the STANDARD and PREMIUM plans' ); ?>
                        . <a
                                href="admin.php?page=miniOrange_2_factor_settings&mo2f_tab=mo2f_pricing"><?php echo mo2f_lt( 'Click here' ); ?></a> <?php echo mo2f_lt( 'to learn more' ) ?>
                        .</a></p>
				<?php } ?>
                <form name="f" method="post" action="" id="mo2f_2factor_test_authentication_method_form">
                    <input type="hidden" name="option" value="mo_2factor_test_authentication_method"/>
                    <input type="hidden" name="mo2f_configured_2FA_method_test" id="mo2f_configured_2FA_method_test"/>
					<input type="hidden" name="mo_2factor_test_authentication_method_nonce"
							value="<?php echo wp_create_nonce( "mo-2factor-test-authentication-method-nonce" ) ?>"/>
                </form>

                <form name="f" method="post" action="" id="mo2f_2factor_resume_flow_driven_setup_form">
                    <input type="hidden" name="option" value="mo_2factor_resume_flow_driven_setup"/>
					<input type="hidden" name="mo_2factor_resume_flow_driven_setup_nonce"
							value="<?php echo wp_create_nonce( "mo-2factor-resume-flow-driven-setup-nonce" ) ?>"/>
                </form>

            </div>
        </div>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
        <script>

            function restart_tour() {
                tour.restart();
            }

            var tour = new Tour({
                name: "tour",
                steps: [
                    {
                        element: "#GoogleAuthenticator_thumbnail_2_factor",
                        title: "Google Authenticator Method",
                        content: "Select the authentication method you wish to configure, for example Google Authenticator.",
                        backdrop:'body',
                        backdropPadding:'6'
                    }, {
                        element: "#GoogleAuthenticator_configuration",
                          title: "Configure Second Factor",
                        content: "Click here to Configure Google Authenticator Method on your phone.",
                        backdrop:'body',
                        backdropPadding:'6'
                    }, {
                        element: "#mo2f_selected_method",
                        title: "Selected Authentication Method",
                        content: "After the configuration, Google Authenticator will be set as your 2FA method.",
                        onPrev:function(tour){
                            jQuery("#mo2f_free_plan_auth_methods").show();
                            jQuery("#mo2f_standard_plan_auth_methods").hide();
                            jQuery("#mo2f_premium_plan_auth_methods").hide();
                        },
                        backdrop:'body',
                        backdropPadding:'6'
                    }
                ,{
                        element: "#test",
                        title: "Test Configured Method",
                        content: "Please test the 2FA method you configured, to ensure it works.",
                        backdrop:'body',
                        backdropPadding:'6'
						 
                    }
                    
					, {
                        element: "#mo2f_need_help",
                        title: "Need Any Help?",
                        content: "Click here to reach us anytime you need any help with the plugin.",
                        backdrop:'body',
						placement:'bottom',
                        backdropPadding:'6',
						onNext: function(){
							 mo2f_opensupport();
						 }
                        }
					
                ,{
                        element: "#mo2f_upgrade",
                        title: "Premium Plans",
                        content: "For the Standard & Premium features we provide, click here to view & upgrade.",
                        placement: 'bottom',
                    backdrop:'body',
                    backdropPadding:'6'
                    }
                ,
                    {
                        element: "#mo2f_restart_tour",
                        title: "Restart Tour",
                        content: "Click here to restart the tour whenever you wish.",
                        backdrop:'body',
                        backdropPadding:'6'
                    }


                ]});

            // Initialize the tour
            tour.init();

            // Start the tour
            tour.start();


            function configureOrSet2ndFactor_free_plan(authMethod, action) {
                jQuery('#mo2f_configured_2FA_method_free_plan').val(authMethod);
                jQuery('#mo2f_selected_action_free_plan').val(action);
                jQuery('#mo2f_save_free_plan_auth_methods_form').submit();
            }

            function testAuthenticationMethod(authMethod) {
                jQuery('#mo2f_configured_2FA_method_test').val(authMethod);
                jQuery('#loading_image').show();

                jQuery('#mo2f_2factor_test_authentication_method_form').submit();
            }

            function resumeFlowDrivenSetup() {
                jQuery('#mo2f_2factor_resume_flow_driven_setup_form').submit();
            }

            jQuery("#mo2f_standard_plan_auth_methods").hide();

            function show_standard_plan_auth_methods() {
                jQuery("#mo2f_standard_plan_auth_methods").slideToggle(1000);
                jQuery("#mo2f_free_plan_auth_methods").hide();
                jQuery("#mo2f_premium_plan_auth_methods").hide();
            }

            function show_free_plan_auth_methods() {
                jQuery("#mo2f_free_plan_auth_methods").slideToggle(1000);
                jQuery("#mo2f_standard_plan_auth_methods").hide();
                jQuery("#mo2f_premium_plan_auth_methods").hide();
            }

            jQuery("#mo2f_premium_plan_auth_methods").hide();

            function show_premium_auth_methods() {
                jQuery("#mo2f_free_plan_auth_methods").hide();
                jQuery("#mo2f_standard_plan_auth_methods").hide();
                jQuery("#mo2f_premium_plan_auth_methods").slideToggle(1000);
            }

            jQuery("#how_to_configure_2fa").hide();

            function show_how_to_configure_2fa() {
                jQuery("#how_to_configure_2fa").slideToggle(700);
            }

        </script>
	<?php } ?>

	<?php
}

function mo2f_create_2fa_form( $user, $category, $auth_methods, $can_display_admin_features='' ) {
	global $Mo2fdbQueries;
	$all_two_factor_methods = array(
		"miniOrange QR Code Authentication",
		"miniOrange Soft Token",
		"miniOrange Push Notification",
		"Google Authenticator",
		"Security Questions",
		"Authy Authenticator",
		"Email Verification",
		"OTP Over SMS",
		"OTP Over Email",
		"OTP Over SMS and Email",
		"Hardware Token"
	);

	$two_factor_methods_descriptions = array(
	        ""=>"<b>All methods in the FREE Plan in addition to the following methods.</b>",
		"miniOrange QR Code Authentication" => "Scan the QR code from the account in your miniOrange Authenticator App to login.",
		"miniOrange Soft Token"             => "Enter the soft token from the account in your miniOrange Authenticator App to login.",
		"miniOrange Push Notification"      => "Accept a push notification in your miniOrange Authenticator App to login.",
		"Google Authenticator"              => "Enter the soft token from the account in your <b>Google/Authy/LastPass Authenticator App</b> to login.",
		"Security Questions"                => "Answer the three security questions you had set, to login.",
		"Authy Authenticator"               => "Enter the soft token from the account in your Authy Authenticator App to login.",
		"Email Verification"                => "Accept the verification link sent to your email to login.",
		"OTP Over SMS"                      => "Enter the One Time Passcode sent to your phone to login.",
		"OTP Over Email"                    => "Enter the One Time Passcode sent to your email to login.",
		"OTP Over SMS and Email"            => "Enter the One Time Passcode sent to your phone and email to login.",
		"Hardware Token"                    => "Enter the One Time Passcode on your Hardware Token to login."
	);

	$two_factor_methods_EC = array_slice( $all_two_factor_methods, 0, 8 );
	$two_factor_methods_NC = array_slice( $all_two_factor_methods, 0, 5 );

	$is_customer_registered = $Mo2fdbQueries->get_user_detail( 'user_registration_with_miniorange', $user->ID ) == 'SUCCESS' ? true : false;

	$can_user_configure_2fa_method = $can_display_admin_features || ( !$can_display_admin_features && $is_customer_registered );
	$is_NC = get_option( 'mo2f_is_NC' );
	$is_EC = ! $is_NC;

	$form = '';
	$form .= '<form name="f" method="post" action="" id="mo2f_save_' . $category . '_auth_methods_form">
                        <div id="mo2f_' . $category . '_auth_methods" style="background-color: #f1f1f1;">
                            <br>
                            <table class="mo2f_auth_methods_table">';

	for ( $i = 0; $i < count( $auth_methods ); $i ++ ) {

		$form .= '<tr>';
		for ( $j = 0; $j < count( $auth_methods[ $i ] ); $j ++ ) {
			$auth_method             = $auth_methods[ $i ][ $j ];
			$auth_method_abr         = str_replace( ' ', '', $auth_method );
			$configured_auth_method  = $Mo2fdbQueries->get_user_detail( 'mo2f_configured_2FA_method', $user->ID );
			$is_auth_method_selected = ( $configured_auth_method == $auth_method ? true : false );

			$is_auth_method_av = false;
			if ( ( $is_EC && in_array( $auth_method, $two_factor_methods_EC ) ) ||
			     ( $is_NC && in_array( $auth_method, $two_factor_methods_NC ) ) ) {
				$is_auth_method_av = true;
			}


			$thumbnail_height = $is_auth_method_av && $category == 'free_plan' ? 190 : 160;
            $is_image = $auth_method == "" ? 0 :1;

            $form .= '<td>
                         <div class="mo2f_thumbnail" id="'.$auth_method_abr.'_thumbnail_2_factor" style="height:' . $thumbnail_height . 'px;border-color:#ddd;">
                          <div><div>
                        <div style="width: 80px; float:left;">';

            if($is_image){
	            $form .= '<img src="' . plugins_url( "includes/images/authmethods/" . $auth_method_abr . ".png", __FILE__ ) . '" style="width: 85px;height: 85px !important; padding: 20px; line-height: 80px;" />';
            }

            $form .= '</div>
                        <div style="width:200px; padding:20px;font-size:14px;overflow: hidden;"><b>' . $auth_method .
			         '</b><br>
                        <p style="padding:0px; padding-left:0px;"> ' . $two_factor_methods_descriptions[ $auth_method ] . '</p>
                        
                        </div>
                        </div>
                        </div>';

			if ( $is_auth_method_av && $category == 'free_plan' ) {
				$is_auth_method_configured = $Mo2fdbQueries->get_user_detail( 'mo2f_' . $auth_method_abr . '_config_status', $user->ID );

				$form .= '<div style="height:40px;width:100%;position: absolute;bottom: 0;background-color:';
				$form .= $is_auth_method_selected ? '#48b74b' : '#8daddc';

				$form .= ';color:white">';
				if ( $auth_method != "Email Verification" ) {
					$form .= '<div class="mo2f_configure_2_factor">
                              <button type="button" id="'.$auth_method_abr.'_configuration" class="mo2f_configure_set_2_factor" onclick="configureOrSet2ndFactor_' . $category . '(\'' . $auth_method_abr . '\', \'configure2factor\');"';
					$form .= $can_user_configure_2fa_method ? "" : " disabled ";
					$form .= '>';
					$form .= $is_auth_method_configured ? 'Reconfigure' : 'Configure';
					$form .= '</button></div>';
				}
				if ( $is_auth_method_configured && ! $is_auth_method_selected ) {
					$form .= '<div class="mo2f_set_2_factor">
                               <button type="button" id="'.$auth_method_abr.'_set_2_factor" class="mo2f_configure_set_2_factor" onclick="configureOrSet2ndFactor_' . $category . '(\'' . $auth_method_abr . '\', \'select2factor\');"';
					$form .= $can_user_configure_2fa_method ? "" : " disabled ";
					$form .= '>Set as 2-factor</button>
                              </div>';
				}

				$form .= '</div>';

			}
			$form .= '</div></div></td>';
		}

		$form .= '</tr>';
	}


	$form .= '</table>';
     if( $category!="free_plan")
	     $form .= '<div style="background-color: #f1f1f1;padding:10px">
                            <p style="font-size:16px;margin-left: 1%">In addition to these authentication methods, for other features in this plan, <a href="admin.php?page=miniOrange_2_factor_settings&mo2f_tab=mo2f_pricing"><i>Click here.</i></a></p>
                 </div>';

     $form .= '</div> <input type="hidden" name="miniorange_save_form_auth_methods_nonce"
                   value="'. wp_create_nonce( "miniorange-save-form-auth-methods-nonce" ) .'"/>
                <input type="hidden" name="option" value="mo2f_save_' . $category . '_auth_methods" />
                <input type="hidden" name="mo2f_configured_2FA_method_' . $category . '" id="mo2f_configured_2FA_method_' . $category . '" />
                <input type="hidden" name="mo2f_selected_action_' . $category . '" id="mo2f_selected_action_' . $category . '" />
                </form>';

	return $form;
}



function show_2_factor_pricing_page( $user ) {
	global $Mo2fdbQueries;

	$is_NC = get_option( 'mo2f_is_NC' );

	$is_customer_registered = $Mo2fdbQueries->get_user_detail( 'user_registration_with_miniorange', $user->ID ) == 'SUCCESS' ? true : false;

	$mo2f_feature_set = array(
		"Authentication Methods",
		"No. of Users",
		"Language Translation Support",
		"Login with Username + password + 2FA",
		"Login with Username + 2FA (skip password)",
		"Backup Methods",
		"Multi-Site Support",
		"User role based redirection after Login",
		"Add custom Security Questions (KBA)",
		"Customize account name in Google Authenticator app",
		"Brute Force Protection",
		"Blocking IP",
		"Monitoring",
		"Strong Password",
		"File Protection",
		"Enable 2FA for specific User Roles",
		"Enable 2FA for specific Users",
		"Choose specific authentication methods for Users",
		"Prompt for 2FA Registration for Users at login",
		"One Time Email Verification for Users during 2FA Registration",
		"Enable Security Questions as backup for Users during 2FA registration",
		"App Specific Password to login from mobile Apps",
		"Support"
	);


	$two_factor_methods = array(
		"miniOrange QR Code Authentication",
		"miniOrange Soft Token",
		"miniOrange Push Notification",
		"Google Authenticator",
		"Security Questions",
		"Authy Authenticator",
		"Email Verification",
		"OTP Over SMS",
		"OTP Over Email",
		"OTP Over SMS and Email",
		"Hardware Token"
	);

	$two_factor_methods_EC          = array_slice( $two_factor_methods, 0, 7 );

	$mo2f_feature_set_with_plans_NC = array(
		"Authentication Methods"                                                => array(
			array_slice( $two_factor_methods, 0, 5 ),
			array_slice( $two_factor_methods, 0, 10 ),
			array_slice( $two_factor_methods, 0, 11 ),
			array_slice( $two_factor_methods, 0, 11 )
		),
		"No. of Users"                                                          => array(
			"1",
			"User Based Pricing",
			"User Based Pricing",
			"User Based Pricing"
		),
		"Language Translation Support"                                          => array( true, true, true, true ),
		"Login with Username + password + 2FA"                                  => array( true, true, true, true ),
		"Login with Username + 2FA (skip password)"                             => array( false, true, true, true ),
		"Backup Methods"                                                        => array(
			false,
			"KBA",
			array( "KBA", "OTP Over Email", "Backup Codes" ),
			array( "KBA", "OTP Over Email", "Backup Codes" )
		),
		"Multi-Site Support"                                                    => array( false, true, true, true ),
		"User role based redirection after Login"                               => array( false, true, true, true ),
		"Add custom Security Questions (KBA)"                                   => array( false, true, true, true ),
		"Add custom Security Questions (KBA)"                                   => array( false, true, true, true ),
		"Customize account name in Google Authenticator app"                    => array( false, true, true, true ),
		"Brute Force Protection"												=> array( true, false, false, true ),
		"Blocking IP"															=> array( true, false, false, true ),
		"Monitoring"															=> array( true, false, false, true ),
		"Strong Password"														=> array( true, false, false, true ),
		"File Protection"														=> array( true, false, false, true ),
		"Enable 2FA for specific User Roles"                                    => array( false, false, true, true ),
		"Enable 2FA for specific Users"                                         => array( false, false, true, true ),
		"Choose specific authentication methods for Users"                      => array( false, false, true, true ),
		"Prompt for 2FA Registration for Users at login"                        => array( false, false, true, true ),
		"One Time Email Verification for Users during 2FA Registration"         => array( false, false, true, true ),
		"Enable Security Questions as backup for Users during 2FA registration" => array( false, false, true, true ),
		"App Specific Password to login from mobile Apps"                       => array( false, false, true, true ),
		"Support"                                                               => array(
			"Basic Support by Email",
			"Priority Support by Email",
			array( "Priority Support by Email", "Priority Support with GoTo meetings" ),
			array( "Priority Support by Email", "Priority Support with GoTo meetings" )
		),

	);

	$mo2f_feature_set_with_plans_EC = array(
		"Authentication Methods"                                                => array(
			array_slice( $two_factor_methods, 0, 8 ),
			array_slice( $two_factor_methods, 0, 10 ),
			array_slice( $two_factor_methods, 0, 11 ),
			array_slice( $two_factor_methods, 0, 11 )
		),
		"No. of Users"                                                          => array(
			"1",
			"User Based Pricing",
			"User Based Pricing",
			"User Based Pricing"
		),
		"Language Translation Support"                                          => array( true, true, true, true ),
		"Login with Username + password + 2FA"                                  => array( true, true, true, true ),
		"Login with Username + 2FA (skip password)"                             => array( true, true, true, true ),
		"Backup Methods"                                                        => array(
			"KBA",
			"KBA",
			array( "KBA", "OTP Over Email", "Backup Codes" ),
			array( "KBA", "OTP Over Email", "Backup Codes" )
		),
		"Multi-Site Support"                                                    => array( false, true, true, true ),
		"Brute Force Protection"												=> array( true, false, false, true ),
		"Blocking IP"															=> array( true, false, false, true ),
		"Monitoring"															=> array( true, false, false, true ),
		"Strong Password"														=> array( true, false, false, true ),
		"File Protection"														=> array( true, false, false, true ),
		"User role based redirection after Login"                               => array( false, true, true, true ),
		"Add custom Security Questions (KBA)"                                   => array( false, true, true, true ),
		"Customize account name in Google Authenticator app"                    => array( false, true, true, true ),
		"Enable 2FA for specific User Roles"                                    => array( false, false, true, true ),
		"Enable 2FA for specific Users"                                         => array( false, false, true, true ),
		"Choose specific authentication methods for Users"                      => array( false, false, true, true ),
		"Prompt for 2FA Registration for Users at login"                        => array( false, false, true, true ),
		"One Time Email Verification for Users during 2FA Registration"         => array( false, false, true, true ),
		"Enable Security Questions as backup for Users during 2FA registration" => array( false, false, true, true ),
		"App Specific Password to login from mobile Apps"                       => array( false, false, true, true ),
		"Support"                                                               => array(
			"Basic Support by Email",
			"Priority Support by Email",
			array( "Priority Support by Email", "Priority Support with GoTo meetings" ),
			array( "Priority Support by Email", "Priority Support with GoTo meetings" )
		),

	);

	$mo2f_addons           = array(
		"RBA & Trusted Devices Management Add-on",
		"Personalization Add-on",
		"Short Codes Add-on"
	);
	$mo2f_addons_plan_name = array(
		"RBA & Trusted Devices Management Add-on" => "wp_2fa_addon_rba",
		"Personalization Add-on"                  => "wp_2fa_addon_personalization",
		"Short Codes Add-on"                      => "wp_2fa_addon_shortcode"
	);


	$mo2f_addons_with_features = array(
		"Personalization Add-on"                  => array(
			"Custom UI of 2FA popups",
			"Custom Email and SMS Templates",
			"Customize 'powered by' Logo",
			"Customize Plugin Icon",
			"Customize Plugin Name",
			
		),
		"RBA & Trusted Devices Management Add-on" => array(
			"Remember Device",
			"Set Device Limit for the users to login",
		 "IP Restriction: Limit users to login from specific IPs"
		),
		"Short Codes Add-on"                      => array(
			"Option to turn on/off 2-factor by user",
			"Option to configure the Google Authenticator and Security Questions by user",
			"Option to 'Enable Remember Device' from a custom login form",
			"On-Demand ShortCodes for specific fuctionalities ( like for enabling 2FA for specific pages)"
		)
	);
	?>
	<br>
    <div class="mo2f_licensing_plans" style="border:0px;">
	
        <table class="table mo_table-bordered mo_table-striped">
            <thead>
            <tr class="mo2f_licensing_plans_tr">
                <th width="20%">
                    <h3>Features \ Plans</h3></th>
                <th class="text-center" width="20%"><h3>Free</h3>

                    <p class="mo2f_licensing_plans_plan_desc">Basic 2FA for Small Scale Web Businesses</p><br>
					<span style='color:red;font-size:18px;'>(Current Plan)</span>
					</th>
                <th class="text-center" width="20%"><h3>Standard</h3>

                    <p class="mo2f_licensing_plans_plan_desc">Intermediate 2FA for Medium Scale Web Businesses with
                        basic support</p><span>
						<?php echo mo2f_yearly_standard_pricing(); ?>

						<?php echo mo2f_sms_cost();
						if( $is_customer_registered) {
						?>
                            <h4 class="mo2f_pricing_sub_header" style="padding-bottom:8px !important;"><button
                                        class="button button-primary button-large"
                                        onclick="mo2f_upgradeform('wp_2fa_basic_plan')" >Upgrade</button></h4>
                        <?php }else{ ?>

                            <h4 class="mo2f_pricing_sub_header" style="padding-bottom:8px !important;"><button
                                    class="button button-primary button-large"
                                    onclick="mo2f_register_and_upgradeform('wp_2fa_basic_plan')" >Upgrade</button></h4>
                        <?php } ?>
                            <br>
				</span></h3>
                </th>

                <th class="text-center" width="20%"><h3>Premium</h3>

                    <p class="mo2f_licensing_plans_plan_desc" style="margin:16px 0 16px 0	">Advanced and Intuitive
                        2FA for Large Scale Web businesses with enterprise-grade support</p><span>
                    <?php echo mo2f_yearly_premium_pricing(); ?>
						<?php echo mo2f_sms_cost();
                        if( $is_customer_registered) {
						?>
                            <h4 class="mo2f_pricing_sub_header" style="padding-bottom:8px !important;"><button
                                        class="button button-primary button-large"
                                        onclick="mo2f_upgradeform('wp_2fa_premium_plan')" >Upgrade</button></h4>
		                <?php }else{ ?>

                            <h4 class="mo2f_pricing_sub_header" style="padding-bottom:8px !important;"><button
                                        class="button button-primary button-large"
                                        onclick="mo2f_register_and_upgradeform('wp_2fa_premium_plan')" >Upgrade</button></h4>
		                <?php } ?>
                        <br>
				</span>
                </th>
                <th class="text-center" width="25%"><h3>Enterprise</h3>
					
                    <p class="mo2f_licensing_plans_plan_desc" style="margin:16px 0 16px 0;">One stop security solution with 2fa and Network security for Large Web businesses.</p><span>
                    <a class="button button-primary button-large" href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_support">Contact Us</a>

                        <br>
				</span>
                </th>

            </tr>
            </thead>
            <tbody class="mo_align-center mo-fa-icon">
			<?php for ( $i = 0; $i < count( $mo2f_feature_set ); $i ++ ) { ?>
                <tr>
                    <td><?php
						$feature_set = $mo2f_feature_set[ $i ];

						echo $feature_set;
						?>
					</td>
					<?php if ( $is_NC ) {
						$f_feature_set_with_plan = $mo2f_feature_set_with_plans_NC[ $feature_set ];
					} else {
						$f_feature_set_with_plan = $mo2f_feature_set_with_plans_EC[ $feature_set ];
					}
					?>
                    <td><?php
						if ( is_array( $f_feature_set_with_plan[0] ) ) {
							echo mo2f_create_li( $f_feature_set_with_plan[0] );
						} else {
							if ( gettype( $f_feature_set_with_plan[0] ) == "boolean" ) {
								echo mo2f_get_binary_equivalent( $f_feature_set_with_plan[0] );
							} else {
								echo $f_feature_set_with_plan[0];
							}
						} ?>
                    </td>
                    <td><?php
						if ( is_array( $f_feature_set_with_plan[1] ) ) {
							echo mo2f_create_li( $f_feature_set_with_plan[1] );
						} else {
							if ( gettype( $f_feature_set_with_plan[1] ) == "boolean" ) {
								echo mo2f_get_binary_equivalent( $f_feature_set_with_plan[1] );
							} else {
								echo $f_feature_set_with_plan[1];
							}
						} ?>
                    </td>
                    <td><?php
						if ( is_array( $f_feature_set_with_plan[2] ) ) {
							echo mo2f_create_li( $f_feature_set_with_plan[2] );
						} else {
							if ( gettype( $f_feature_set_with_plan[2] ) == "boolean" ) {
								echo mo2f_get_binary_equivalent( $f_feature_set_with_plan[2] );
							} else {
								echo $f_feature_set_with_plan[2];
							}
						} ?>
                    </td>
					<td><?php
						if ( is_array( $f_feature_set_with_plan[3] ) ) {
							echo mo2f_create_li( $f_feature_set_with_plan[3] );
						} else {
							if ( gettype( $f_feature_set_with_plan[3] ) == "boolean" ) {
								echo mo2f_get_binary_equivalent( $f_feature_set_with_plan[3] );
							} else {
								echo $f_feature_set_with_plan[3];
							}
						} ?>
                    </td>
                </tr>
			<?php } ?>

            <tr>
                <td><b>Add-Ons</b></td>
				<?php if ( $is_NC ) { ?>
                    <td><b>Purchase Separately</b></td>
				<?php } else { ?>
                    <td><b>NA</b></td>
				<?php } ?>
                <td><b>Purchase Separately</b></td>
                <td><b>Included</b></td>
                <td><b>Included</b></td>
            </tr>
			<?php for ( $i = 0; $i < count( $mo2f_addons ); $i ++ ) { ?>
                <tr>
                    <td><?php echo $mo2f_addons[ $i ]; ?> <?php for ( $j = 0; $j < $i + 1; $j ++ ) { ?>*<?php } ?>
                    </td>
					<?php if ( $is_NC ) { ?>
                        <td>
                            <button class="button button-primary button-small" style="cursor:pointer"
                                    onclick="mo2f_upgradeform('<?php echo $mo2f_addons_plan_name[ $mo2f_addons[ $i ] ]; ?>')" <?php echo $is_customer_registered ? "" : " disabled " ?> >
                                Purchase
                            </button>
                            
                        </td>
					<?php } else { ?>
                        <td><b>NA</b></td>
					<?php } ?>
                    <td>
                        <button class="button button-primary button-small" style="cursor:pointer"
                                onclick="mo2f_upgradeform('<?php echo $mo2f_addons_plan_name[ $mo2f_addons[ $i ] ]; ?>')" <?php echo $is_customer_registered ? "" : " disabled " ?> >
                            Purchase
                        </button>
                    </td>
                    <td><i class='fa fa-check'></i></td>
                    <td><i class='fa fa-check'></i></td>
                </tr>
			<?php } ?>

            </tbody>
        </table>
        <br>
        <div style="padding:10px;">
			<?php for ( $i = 0; $i < count( $mo2f_addons ); $i ++ ) {
				$f_feature_set_of_addons = $mo2f_addons_with_features[ $mo2f_addons[ $i ] ];
				for ( $j = 0; $j < $i + 1; $j ++ ) { ?>*<?php } ?>
                <b><?php echo $mo2f_addons[ $i ]; ?> Features</b>
                <br>
                <ol>
					<?php for ( $k = 0; $k < count( $f_feature_set_of_addons ); $k ++ ) { ?>
                        <li><?php echo $f_feature_set_of_addons[ $k ]; ?></li>
					<?php } ?>
                </ol>

                <hr><br>
			<?php } ?>
            <b>**** SMS Charges</b>
            <p><?php echo mo2f_lt( 'If you wish to choose OTP Over SMS / OTP Over SMS and Email as your authentication method,
                    SMS transaction prices & SMS delivery charges apply and they depend on country. SMS validity is for lifetime.' ); ?></p>
            <hr>
            <br>
            <div>
                <h2>Note</h2>
                <ol class="mo2f_licensing_plans_ol">
                    <li><?php echo mo2f_lt( 'The plugin works with many of the default custom login forms (like Woocommerce / Theme My Login), however if you face any issues with your custom login form, contact us and we will help you with it.' ); ?></li>
                </ol>
            </div>

            <br>
            <hr>
            <br>
            <div>
                <h2>Steps to upgrade to the Premium Plan</h2>
                <ol class="mo2f_licensing_plans_ol">
                    <li><?php echo mo2f_lt( 'Click on \'Upgrade\' button of your preferred plan above.' ); ?></li>
                    <li><?php echo mo2f_lt( ' You will be redirected to the miniOrange Console. Enter your miniOrange username and password, after which you will be redirected to the payment page.' ); ?></li>

                    <li><?php echo mo2f_lt( 'Select the number of users you wish to upgrade for, and any add-ons if you wish to purchase, and make the payment.' ); ?></li>
                    <li><?php echo mo2f_lt( 'After making the payment, you can find the Standard/Premium plugin to download from the \'License\' tab in the left navigation bar of the miniOrange Console.' ); ?></li>
                    <li><?php echo mo2f_lt( 'Download the premium plugin from the miniOrange Console.' ); ?></li>
                    <li><?php echo mo2f_lt( 'In the Wordpress dashboard, uninstall the free plugin and install the premium plugin downloaded.' ); ?></li>
                    <li><?php echo mo2f_lt( 'Login to the premium plugin with the miniOrange account you used to make the payment, after this your users will be able to set up 2FA.' ); ?></li>
                </ol>
            </div>
            <div>
                <h2>Note</h2>
                <ul class="mo2f_licensing_plans_ol">
                    <li><?php echo mo2f_lt( 'There is no license key required to activate the Standard/Premium Plugins. You will have to just login with the miniOrange Account you used to make the purchase.' ); ?></li>
                </ul>
            </div>

            <br>
            <hr>
            <br>
            <div>
                <h2>Refund Policy</h2>
                <p class="mo2f_licensing_plans_ol"><?php echo mo2f_lt( 'At miniOrange, we want to ensure you are 100% happy with your purchase. If the premium plugin you purchased is not working as advertised and you\'ve attempted to resolve any issues with our support team, which couldn\'t get resolved then we will refund the whole amount within 10 days of the purchase.' ); ?>
                </p>
            </div>
            <br>
            <hr>
            <br>
            <div>
                <h2>Privacy Policy</h2>
                <p class="mo2f_licensing_plans_ol"><a
                            href="https://www.miniorange.com/2-factor-authentication-for-wordpress-gdpr">Click Here</a>
                    to read our Privacy Policy.
                </p>
            </div>
            <br>
            <hr>
            <br>
            <div>
                <h2>Contact Us</h2>
                <p class="mo2f_licensing_plans_ol"><?php echo mo2f_lt( 'If you have any doubts regarding the licensing plans, you can mail us at' ); ?>
                    <a href="mailto:info@xecurify.com"><i>info@xecurify.com</i></a> <?php echo mo2f_lt( 'or submit a query using the support form.' ); ?>
                </p>
            </div>
            <br>
            <hr>
            <br>

            <form class="mo2f_display_none_forms" id="mo2fa_loginform"
                  action="<?php echo MO_HOST_NAME . '/moas/login'; ?>"
                  target="_blank" method="post">
                <input type="email" name="username" value="<?php echo get_option( 'mo2f_email' ); ?>"/>
                <input type="text" name="redirectUrl"
                       value="<?php echo MO_HOST_NAME . '/moas/initializepayment'; ?>"/>
                <input type="text" name="requestOrigin" id="requestOrigin"/>
            </form>

            <form class="mo2f_display_none_forms" id="mo2fa_register_to_upgrade_form"
                   method="post">
                <input type="hidden" name="requestOrigin" />
                <input type="hidden" name="mo2fa_register_to_upgrade_nonce"
                       value="<?php echo wp_create_nonce( 'miniorange-2-factor-user-reg-to-upgrade-nonce' ); ?>"/>
            </form>

            <script>

                function mo2f_upgradeform(planType) {
                    jQuery('#requestOrigin').val(planType);
                    jQuery('#mo2fa_loginform').submit();
                }

                function mo2f_register_and_upgradeform(planType) {
                    jQuery('#requestOrigin').val(planType);
                    jQuery('input[name="requestOrigin"]').val(planType);
                    jQuery('#mo2fa_register_to_upgrade_form').submit();
                }
            </script>

            <style>#mo2f_support_table {
                    display: none;
                }

            </style>
        </div>
    </div>

<?php }

function mo2f_create_li( $mo2f_array ) {
	$html_ol = '<ul>';
	foreach ( $mo2f_array as $element ) {
		$html_ol .= "<li>" . $element . "</li>";
	}
	$html_ol .= '</ul>';

	return $html_ol;
}

function mo2f_sms_cost() {
	?>
    <p class="mo2f_pricing_text" id="mo2f_sms_cost"
       title="<?php echo mo2f_lt( '(Only applicable if OTP over SMS is your preferred authentication method.)' ); ?>"><?php echo mo2f_lt( 'SMS Cost' ); ?>
        ****<br/>
        <select id="mo2f_sms" class="form-control" style="border-radius:5px;width:200px;">
            <option><?php echo mo2f_lt( '$5 per 100 OTP + SMS delivery charges' ); ?></option>
            <option><?php echo mo2f_lt( '$15 per 500 OTP + SMS delivery charges' ); ?></option>
            <option><?php echo mo2f_lt( '$22 per 1k OTP + SMS delivery charges' ); ?></option>
            <option><?php echo mo2f_lt( '$30 per 5k OTP + SMS delivery charges' ); ?></option>
            <option><?php echo mo2f_lt( '$40 per 10k OTP + SMS delivery charges' ); ?></option>
            <option><?php echo mo2f_lt( '$90 per 50k OTP + SMS delivery charges' ); ?></option>
        </select>
    </p>
	<?php
}

function mo2f_yearly_standard_pricing() {
	?>

    <p class="mo2f_pricing_text"
       id="mo2f_yearly_sub"><?php echo __( 'Yearly Subscription Fees', 'miniorange-2-factor-authentication' ); ?>

        <select id="mo2f_yearly" class="form-control" style="border-radius:5px;width:200px;">
            <option> <?php echo mo2f_lt( '1 - 2 users - $5 per year' ); ?> </option>
            <option> <?php echo mo2f_lt( '3 - 5 users - $20 per year' ); ?> </option>
            <option> <?php echo mo2f_lt( '6 - 50 users - $30 per year' ); ?> </option>
            <option> <?php echo mo2f_lt( '51 - 100 users - $49 per year' ); ?> </option>
            <option> <?php echo mo2f_lt( '101 - 500 users - $99 per year' ); ?> </option>
            <option> <?php echo mo2f_lt( '501 - 1000 users - $199 per year' ); ?> </option>
            <option> <?php echo mo2f_lt( '1001 - 5000 users - $299 per year' ); ?> </option>
            <option> <?php echo mo2f_lt( '5001 -  10000 users - $499 per year' ); ?></option>
            <option> <?php echo mo2f_lt( '10001 - 20000 users - $799 per year' ); ?> </option>
        </select>
    </p>
	<?php
}

function mo2f_yearly_premium_pricing() {
	?>

    <p class="mo2f_pricing_text"
       id="mo2f_yearly_sub"><?php echo __( 'Yearly Subscription Fees', 'miniorange-2-factor-authentication' ); ?>

        <select id="mo2f_yearly" class="form-control" style="border-radius:5px;width:200px;">
            <option> <?php echo mo2f_lt( '1 - 5 users - $30 per year' ); ?> </option>
            <option> <?php echo mo2f_lt( '6 - 50 users - $99 per year' ); ?> </option>
            <option> <?php echo mo2f_lt( '51 - 100 users - $199 per year' ); ?> </option>
            <option> <?php echo mo2f_lt( '101 - 500 users - $349 per year' ); ?> </option>
            <option> <?php echo mo2f_lt( '501 - 1000 users - $499 per year' ); ?> </option>
            <option> <?php echo mo2f_lt( '1001 - 5000 users - $799 per year' ); ?> </option>
            <option> <?php echo mo2f_lt( '5001 -  10000 users - $999 per year ' ); ?></option>
            <option> <?php echo mo2f_lt( '10001 - 20000 users - $1449 per year' ); ?> </option>
        </select>
    </p>
	<?php
}

function mo2f_get_binary_equivalent( $mo2f_var ) {

	switch ( $mo2f_var ) {
		case 1:
			return "<i class='fa fa-check'></i>";
		case 0:
			return "";
		default:
			return $mo2f_var;
	}
} ?>
