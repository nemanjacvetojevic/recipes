<?php

class class_miniorange_2fa_strong_password {
	
	function __construct(){
		
			add_option( 'mo2f_enforce_strong_passswords', false);
		
	}
	
	public static function woocommerce_password_protection($errors, $username, $password, $email) {
		if ($password == false) { return $errors; }
		if ($errors->get_error_data("pass")) { return $errors; }
		
		$enforceStrongPasswds = get_option('mo2f_enforce_strong_passswords');

		if ($enforceStrongPasswds && !MO2f_Handler::mo2f_isStrongPasswd($password, $username)) {
			$errors->add('pass', __('Please choose a stronger password. Try including numbers, symbols, and a mix of upper and lowercase letters and remove common words.'));
			return $errors;
		}
		
		return $errors;
	}
		public static function validatePassword($errors, $update, $userData){
		$password = (isset($_POST['pass1']) && trim($_POST['pass1'])) ? $_POST['pass1'] : false;
		$password=($password==false)?(isset($_POST['password_1'])?$_POST['password_1']:false):$password ;
		$user_id = isset($userData->ID) ? $userData->ID : false;
		$username = isset($_POST["user_login"]) ? $_POST["user_login"] : isset($userData->user_login)?$userData->user_login:$userData->user_email;
		
		if ($password == false) { return $errors; }
		if ($errors->get_error_data("pass")) { return $errors; }
		
		$enforceStrongPasswds = get_option('mo2f_enforce_strong_passswords');
		if ($enforceStrongPasswds && !MO2f_Handler::mo2f_isStrongPasswd($password, $username)) {
			$errors->add('pass', __('Please choose a stronger password. Try including numbers, symbols, and a mix of upper and lowercase letters and remove common words.'));
			return $errors;
		}
		
		return $errors;
	}
	public static function woocommerce_password_registration_protection($errors, $username, $email) {
		if(get_option( 'woocommerce_registration_generate_password' )=='yes')
			return $errors;
		$password=$_POST['account_password'];
		return MO2f_Handler::is_validPassword($errors, $username, $password);
		
	}
	
	public static function woocommerce_password_edit_account($errors, $user) {
		
		$password=$_POST['password_1'];
		$user =get_userdata($user->ID);
		$username=$user->user_login;
		$enforceStrongPasswds = get_option('mo2f_enforce_strong_passswords');

		if ($enforceStrongPasswds && !MO2f_Handler::mo2f_isStrongPasswd($password, $username)) {
			$errors->add('pass', __('Please choose a stronger password. Try including numbers, symbols, and a mix of upper and lowercase letters and remove common words.'));
			return $errors;
		}
	}
}
	
?>