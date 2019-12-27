<?php

require('miniorange_handler.php');
require('integrations/class_buddypress.php');
include ('miniorange_2_factor_network_security_view.php');
include('strong_password/class_miniorange_2fa_strong_password.php');
require('class_miniorange_2fa_network_security_content_protection.php');

class class_miniorange_2fa_network_security {

	function __construct(){
		add_option('mo2f_enable_brute_force',0);
		add_option('mo2f_ns_blocked_ip',0);
		add_option('mo2f_ns_whitelist_ip',0);
		$mo2f_buddypress       = new Mo2f_BuddyPress();
		add_action('bp_signup_validate', array($mo2f_buddypress, 'signup_errors'));

		add_action( 'mo2f_network_init',  array( $this, 'mo2f_network_init' ),5 );
		add_action( 'admin_init',  array( $this, 'mo2f_network_save_settings' ),5 );
		/*
			* Hooks added when user logs in. Both hooks one after successful login and one with unsuccessful login
			* */
		if( get_site_option('mo2f_activate_plugin') == 1) {
			if ( get_option( 'mo2f_enable_brute_force' ) ) {
				add_action( 'wp_login', array( $this, 'mo2f_ns_login_success' ) );
				add_action( 'wp_login_failed', array( $this, 'mo2f_ns_login_failed' ) );
			}
		}
		$mo2f_ns_config = new MO2f_Handler();
		
		//strong password file
		$mo2f_strong_password = new class_miniorange_2fa_strong_password();
		if($mo2f_ns_config->hasLoginCookie())
		{
			add_action('user_profile_update_errors', array( $mo2f_strong_password, 'validatePassword'), 0, 3 );
			add_action( 'woocommerce_save_account_details_errors', array( $mo2f_strong_password, 'woocommerce_password_edit_account' ),1,2 );
		}
		if(get_option('mo2f_disable_file_editing')) 
			define('DISALLOW_FILE_EDIT', true);
		add_filter( 'woocommerce_process_registration_errors', array($mo2f_strong_password,'woocommerce_password_protection'),1,4);
		add_filter( 'woocommerce_registration_errors', array($mo2f_strong_password,'woocommerce_password_registration_protection'),1,3);
		
		
		add_action('mo2f_network_create_db',array($mo2f_ns_config,'create_db'),5);
		add_action('mo2f_network_view_monitoring','mo2f_show_2_factor_user_login_reports',5,1);
		add_action('mo2f_network_view_ip_blocking','mo2f_show_2_factor_ip_block',5,1);
		add_action('mo2f_network_view_brute_force','mo2f_show_2_factor_login_security',5,1);
		add_action('mo2f_network_view_strong_password','mo2f_show_2_factor_strong_password',5,1);
		add_action('mo2f_network_view_content_protection','mo2f_show_2_factor_content_protection',5,1);
	}

	public function mo2f_network_init(){
			$userIp = MO2f_Utility::get_client_ipaddress();
			
			$mo2f_ns_config = new MO2f_Handler();
			$isIpBlocked = false;
			if ($mo2f_ns_config->is_whitelisted($userIp)) {

			} else if ($mo2f_ns_config->is_ip_blocked($userIp)) {
				$isIpBlocked = true;
			}
			if ($isIpBlocked) {
				require_once 'templates/403.php';
				exit();
			}

	}

	/*
     * Log information of user login
     */
	function mo2f_ns_login_success($username){
		$user = get_user_by( 'login', $username );
		update_user_meta($user->ID,'last_active_time',date('H:i:s'));
		if(!get_option('mo2f_enable_brute_force'))
			return;
		$mo2f_ns_config = new MO2f_Handler();
		$userIp = MO2f_Utility::get_client_ipaddress();

		$mo2f_ns_config->move_failed_transactions_to_past_failed($userIp);
		$mo2f_ns_config->add_transactions($userIp, $username, MO2f_Constants::LOGIN_TRANSACTION, MO2f_Constants::SUCCESS);
	}

	function mo2f_ns_login_failed($username){
		if(!get_option('mo2f_enable_brute_force'))
			return;

		$userIp = MO2f_Utility::get_client_ipaddress();
		if(empty($userIp))
			return;
		else if(empty($username))
			return;

		$mo2f_ns_config = new MO2f_Handler();
		$mo2f_ns_config->add_transactions($userIp, $username, MO2f_Constants::LOGIN_TRANSACTION, MO2f_Constants::FAILED);

		$isWhitelisted = $mo2f_ns_config->is_whitelisted($userIp);

		if(!$isWhitelisted){
			$failedAttempts = $mo2f_ns_config->get_failed_attempts_count($userIp);

			$allowedLoginAttepts = 5;
			if(get_option('mo2f_allwed_login_attempts'))
				$allowedLoginAttepts = get_option('mo2f_allwed_login_attempts');

			if($allowedLoginAttepts - $failedAttempts<=0){
				$mo2f_ns_config->block_ip($userIp, Mo2f_Messages::LOGIN_ATTEMPTS_EXCEEDED, false);
				require_once 'templates/403.php';
				exit();
			}else {
				if(get_option('mo2f_show_remaining_attempts')){
					global $error;
					$diff = $allowedLoginAttepts - $failedAttempts;
					$error = "<br>You have <b>".$diff."</b> attempts remaining.";
				}
			}
		}
	}

	function mo2f_network_save_settings(){
		global $user;
		global $Mo2fdbQueries;
		$user    = wp_get_current_user();
		$user_id = $user->ID;

		if ( current_user_can( 'manage_options' ) ) {
			
			if(isset($_POST['option']) and $_POST['option'] == "mo2f_enforce_strong_passsword"){
					update_option( 'mo2f_enforce_strong_passswords', isset( $_POST['mo2f_enforce_strong_passswords']) ? true : false);
					update_option( 'mo2f_message', 'Settings are saved successfully');
					do_action('mo_auth_show_success_message');
			}else if(isset($_POST['option']) and $_POST['option'] == "mo2f_enable_brute_force"){
					$enable_brute_force_protection = false;
					if(isset($_POST['mo2f_enable_brute_force_protection'])  && $_POST['mo2f_enable_brute_force_protection']=='1'){
						$enable_brute_force_protection = sanitize_text_field($_POST['mo2f_enable_brute_force_protection']);
						update_option( 'mo2f_message', 'Brute force protection is enabled.');
						do_action('mo_auth_show_success_message');
					}else {
						update_option( 'mo2f_message', 'Brute force protection is disabled.');
						do_action('mo_auth_show_error_message');
					}
					update_option( 'mo2f_enable_brute_force', $enable_brute_force_protection);
			}  else if(isset($_POST['option']) and $_POST['option'] == "mo2f_brute_force_configuration"){
						if($_POST['allwed_login_attempts']>0)
						{
							if($_POST['time_of_blocking_type']=='permanent'){
								update_option( 'mo2f_allwed_login_attempts', sanitize_text_field($_POST['allwed_login_attempts']));
								
								update_option( 'mo2f_time_of_blocking_type', sanitize_text_field($_POST['time_of_blocking_type']));
								if(isset($_POST['time_of_blocking_val']))
									update_option( 'mo2f_time_of_blocking_val', sanitize_text_field($_POST['time_of_blocking_val']));
								$show_remaining_attempts = false;
								if(isset($_POST['show_remaining_attempts'])  && $_POST['show_remaining_attempts'])
									$show_remaining_attempts = true;
								update_option( 'mo2f_show_remaining_attempts', $show_remaining_attempts);
								update_option( 'mo2f_message', 'Your configuration has been saved.');
								do_action('mo_auth_show_success_message');
							}else{
								update_option( 'mo2f_message', 'You will have to upgrade to our Standard/Premium plan to use this feature.');
								do_action('mo_auth_show_error_message');
							}

						}else{
							update_option( 'mo2f_message', 'Login Limit Should be more than or equal to 1.');
							do_action('mo_auth_show_error_message');
						}
			}else if(isset($_POST['option']) and $_POST['option']=='mo2f_manual_clear'){

					$mo2f_ns_config = new MO2f_Handler();
					$mo2f_ns_config->mo2f_clear_login_report();
					update_option( 'mo2f_message', "Login Reports have been successfully erased.");
					do_action('mo_auth_show_success_message');

			}else if(isset($_POST['option']) and $_POST['option'] == "mo2f_ns_manual_block_ip"){
					$reg = '/\b(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b/';
					if( MO2f_Utility::mo2f_check_empty_or_null( $_POST['ip'] )|| !preg_match($reg, $_POST['ip'])) {
						update_option( 'mo2f_message', 'Please enter valid IP address (e.g., 0.0.0.0 to 255.255.255.255).');
						do_action('mo_auth_show_error_message');
						return;
					} else{
						$ipAddress = sanitize_text_field( $_POST['ip'] );
						$mo2f_ns_config = new MO2f_Handler();
						$isWhitelisted = $mo2f_ns_config->is_whitelisted($ipAddress);
						if(!$isWhitelisted){
							if($mo2f_ns_config->is_ip_blocked($ipAddress)){
								update_option( 'mo2f_message', "IP Address is already in blocked IP's list.");
								do_action('mo_auth_show_error_message');
							} else{
								//add limit to number of blocks
								$no_of_blocks=get_option('mo2f_ns_blocked_ip');
								if($no_of_blocks<5){
									$mo2f_ns_config->block_ip($ipAddress, Mo2f_Messages::BLOCKED_BY_ADMIN, true);
									$no_of_blocks=$no_of_blocks+1;
									update_option('mo2f_ns_blocked_ip',$no_of_blocks);
									update_option( 'mo2f_message', 'IP Address is blocked permanently.');
									do_action('mo_auth_show_success_message');
								}else{
									update_option( 'mo2f_message', "You cannot Manually block more than 5 IP Addresses in Free plugin.");
									do_action('mo_auth_show_error_message');
								}

							}
						}else{
							update_option( 'mo2f_message', "IP Address is in Whitelisted IP's list. Please remove it from whitelisted list first.");
							do_action('mo_auth_show_error_message');
						}
					}
			} else if(isset($_POST['option']) and $_POST['option'] == "mo2f_ns_unblock_ip"){

					if( MO2f_Utility::mo2f_check_empty_or_null( $_POST['entryid'] )) {
						update_option( 'mo2f_message', 'Error processing your request. Please try again.');
						do_action('mo_auth_show_error_message');
						return;
					}else{
						$entryid = sanitize_text_field( $_POST['entryid'] );
						$mo2f_ns_config = new MO2f_Handler();
						$reason=$mo2f_ns_config->unblock_ip_entry($entryid);
						update_option( 'mo2f_message', 'IP has been unblocked.');
						do_action('mo_auth_show_success_message');
						if(strpos($reason, 'Blocked') !== false){
							$no_of_blocks=get_option('mo2f_ns_blocked_ip');
							update_option('mo2f_ns_blocked_ip',$no_of_blocks-1);
						}
					}
					} else if(isset($_POST['option']) and $_POST['option'] == "mo2f_ns_whitelist_ip"){
					$reg = '/\b(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b/';
					if( MO2f_Utility::mo2f_check_empty_or_null( $_POST['ip'] ) || !preg_match($reg, $_POST['ip'])) {
						update_option( 'mo2f_message', 'Please enter valid IP address (e.g., 0.0.0.0 to 255.255.255.255).');
						do_action('mo_auth_show_error_message');
						return;
					}else{
						$ipAddress = sanitize_text_field( $_POST['ip'] );
						$mo2f_ns_config = new MO2f_Handler();
						if($mo2f_ns_config->is_whitelisted($ipAddress)){
							update_option( 'mo2f_message', "IP Address is already in whitelisted IP's list.");
							do_action('mo_auth_show_error_message');
						} else{
							$no_of_whitelist=get_option('mo2f_ns_whitelist_ip');
							if($no_of_whitelist<5) {
								$mo2f_ns_config = new MO2f_Handler();
									if($mo2f_ns_config ->is_ip_blocked($ipAddress)){
										update_option( 'mo2f_message', "IP Address is in Blocked IP's list. Please remove it from blocked list first." );
										do_action('mo_auth_show_error_message');
									}
									else {
										$mo2f_ns_config->whitelist_ip( $ipAddress );
										update_option( 'mo2f_message', 'IP Address is whitelisted.' );
										do_action('mo_auth_show_success_message');
										$no_of_whitelist = $no_of_whitelist + 1;
										update_option( 'mo2f_ns_whitelist_ip', $no_of_whitelist );
									}
							}else{
								update_option( 'mo2f_message', "You cannot Whitelist more than 5 IP Addresses in Free plugin.");
								do_action('mo_auth_show_error_message');
							}
						}
					}
			} else if(isset($_POST['option']) and $_POST['option'] == "mo2f_ns_remove_whitelist"){

					if( MO2f_Utility::mo2f_check_empty_or_null( $_POST['entryid'] )) {
						update_option( 'mo2f_message', 'Error processing your request. Please try again.');
						do_action('mo_auth_show_error_message');
						return;
					}else{
						$entryid = sanitize_text_field( $_POST['entryid'] );
						$mo2f_ns_config = new MO2f_Handler();
						$mo2f_ns_config->remove_whitelist_entry($entryid);
						$no_of_whitelist=get_option('mo2f_ns_whitelist_ip');
						update_option('mo2f_ns_whitelist_ip',$no_of_whitelist-1);
						update_option( 'mo2f_message', "IP Address is removed from the whitelisted IP's list.");
						do_action('mo_auth_show_success_message');
					}
			}else if(isset($_POST['option']) and $_POST['option'] == 'mo2f_content_protection') {	
					isset($_POST['mo2f_protect_wp_config']) ? update_option('mo2f_protect_wp_config', $_POST['mo2f_protect_wp_config']) : update_option('mo2f_protect_wp_config '	,0);
					isset($_POST['mo2f_prevent_directory_browsing']) ? update_option('mo2f_prevent_directory_browsing', $_POST['mo2f_prevent_directory_browsing'])	: update_option('mo2f_prevent_directory_browsing',0);
					isset($_POST['mo2f_disable_file_editing']) ? update_option('mo2f_disable_file_editing', $_POST['mo2f_disable_file_editing']) : update_option('mo2f_disable_file_editing',0);
					isset($_POST['mo2f_htaccess_file']) ? update_option('mo2f_htaccess_file', $_POST['mo2f_htaccess_file']) : update_option('mo2f_htaccess_file',0);
					// isset($_POST['mo2f_wp_content_file']) ? update_option('mo2f_wp_content_file', $_POST['mo2f_wp_content_file']) : update_option('mo2f_wp_content_file',0);
					$mo2f_htaccess_handler = new mo2f_file_protection();
					$mo2f_htaccess_handler->mo2f_update_htaccess_configuration();
					update_option( 'mo2f_message', "Your configuration for Content Protection has been saved." );
					do_action('mo_auth_show_success_message');
			}
		}
	}
}
new class_miniorange_2fa_network_security;
