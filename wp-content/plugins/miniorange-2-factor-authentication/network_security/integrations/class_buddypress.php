<?php 

class Mo2f_BuddyPress{

	public static function signup_errors() {
		if (!isset($_POST['signup_username']))
			return;

		$user_name = $_POST['signup_username'];
		$error = "";
		global $bp;
		// making sure we are in the registration page
		if ( !function_exists('bp_is_current_component') || !bp_is_current_component('register') ) {
			return;
		}

		if (get_option('mo2f_enable_brute_force')) {
			$userIp = MO2f_Utility::get_client_ipaddress();
			$mo2f_ns_config = new MO2f_Handler();
			$mo2f_ns_config->add_transactions($userIp, $user_name, MO2f_Constants::REGISTRATION_TRANSACTION, MO2f_Constants::FAILED);

			$isWhitelisted = $mo2f_ns_config->is_whitelisted($userIp);
			if(!$isWhitelisted){
				$failedAttempts = $mo2f_ns_config->get_failed_attempts_count($userIp);


				$allowedLoginAttepts = 5;
				if(get_option('mo2f_allwed_login_attempts'))
					$allowedLoginAttepts = get_option('mo2f_allwed_login_attempts');


				if($allowedLoginAttepts - $failedAttempts<=0){
					$mo2f_ns_config->block_ip($userIp, Mo2f_Messages::LOGIN_ATTEMPTS_EXCEEDED, false);
					require_once '../templates/403.php';
					exit();
				}else {
					if(get_option('mo2f_show_remaining_attempts')){
						$diff = $allowedLoginAttepts - $failedAttempts;
						$error = "<br>You have <b>".$diff."</b> attempts remaining.";
					}
				}
			}
		}
		bp_core_add_message($error, 'error');
	}
}?>