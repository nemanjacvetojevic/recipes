<?php
/** Copyright (C) 2015  miniOrange

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>
 * @package 		miniOrange OAuth
 * @license		http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 *
 **/

class MO2f_Handler{


	function create_db(){
		global $wpdb;
		$tableName = $wpdb->prefix.MO2f_Constants::USER_TRANSCATIONS_TABLE;
		if($wpdb->get_var("show tables like '$tableName'") != $tableName)
		{
			$sql = "CREATE TABLE " . $tableName . " (
			`id` bigint NOT NULL AUTO_INCREMENT, `ip_address` mediumtext NOT NULL ,  `username` mediumtext NOT NULL ,
			`type` mediumtext NOT NULL , `url` mediumtext NOT NULL , `status` mediumtext NOT NULL , `created_timestamp` int, UNIQUE KEY id (id) );";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
		}
		$tableName = $wpdb->prefix.MO2f_Constants::BLOCKED_IPS_TABLE;
		if($wpdb->get_var("show tables like '$tableName'") != $tableName)
		{
			$sql = "CREATE TABLE " . $tableName . " (
			`id` int NOT NULL AUTO_INCREMENT, `ip_address` mediumtext NOT NULL , `reason` mediumtext, `blocked_for_time` int,
			`created_timestamp` int, UNIQUE KEY id (id) );";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
		}
		$tableName = $wpdb->prefix.MO2f_Constants::WHITELISTED_IPS_TABLE;
		if($wpdb->get_var("show tables like '$tableName'") != $tableName)
		{
			$sql = "CREATE TABLE " . $tableName . " (
			`id` int NOT NULL AUTO_INCREMENT, `ip_address` mediumtext NOT NULL , `created_timestamp` int, UNIQUE KEY id (id) );";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
		}
		$tableName = $wpdb->prefix.MO2f_Constants::EMAIL_SENT_AUDIT;
		if($wpdb->get_var("show tables like '$tableName'") != $tableName)
		{
			$sql = "CREATE TABLE " . $tableName . " (
			`id` int NOT NULL AUTO_INCREMENT, `ip_address` mediumtext NOT NULL , `username` mediumtext NOT NULL, `reason` mediumtext, `created_timestamp` int, UNIQUE KEY id (id) );";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
		}
	}

	function is_ip_blocked($ipAddress){
		if(empty($ipAddress))
			return false;
		global $wpdb;

		$myrows = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix.MO2f_Constants::BLOCKED_IPS_TABLE." where ip_address = '".$ipAddress."'" );
		if($myrows){
			if(count($myrows)>0){
				$time_of_blocking = $myrows[0]->blocked_for_time;
				$currenttime = current_time( 'timestamp' );
				if($currenttime < $time_of_blocking){
					return true;
				} else{ //premium
					$wpdb->query( "DELETE FROM ".$wpdb->prefix.MO2f_Constants::BLOCKED_IPS_TABLE." WHERE ip_address = '".$ipAddress."'");
					$wpdb->query( "UPDATE ".$wpdb->prefix.MO2f_Constants::USER_TRANSCATIONS_TABLE." SET status='".MO2f_Constants::PAST_FAILED."' WHERE ip_address = '".$ipAddress."' AND status='".MO2f_Constants::FAILED."'");
				}
			}
		}
		return false;
	}

	function block_ip($ipAddress, $reason, $permenently){
		if(empty($ipAddress))
			return;
		if($this->is_ip_blocked($ipAddress))
			return;
		$blocked_for_time = null;
		$blocking_type = get_option('mo2f_time_of_blocking_type');

		$time_of_blocking_val = 3;
		if(!$permenently)
		{
			if(get_option('mo2f_time_of_blocking_val'))
				$time_of_blocking_val = get_option('mo2f_time_of_blocking_val');
			if($blocking_type=="months")
				$blocked_for_time = current_time( 'timestamp' )+$time_of_blocking_val * 30 * 24 * 60 * 60;
			else if($blocking_type=="days")
				$blocked_for_time = current_time( 'timestamp' )+$time_of_blocking_val * 24 * 60 * 60;
			else if($blocking_type=="hours")
				$blocked_for_time = current_time( 'timestamp' )+$time_of_blocking_val * 60 * 60;
			else if($blocking_type=="minutes")
				$blocked_for_time = current_time( 'timestamp' )+$time_of_blocking_val * 60;
			else
				$blocked_for_time = current_time( 'timestamp' )+3* 365 * 24 * 60 * 60;
		}else
		{
			$blocked_for_time = current_time( 'timestamp' )+$time_of_blocking_val * 365 * 24 * 60 * 60;
		}

		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix.MO2f_Constants::BLOCKED_IPS_TABLE,
			array(
				'ip_address' => $ipAddress,
				'reason' => $reason,
				'blocked_for_time' => $blocked_for_time,
				'created_timestamp' => current_time( 'timestamp' )
			)
		);
	}

	function unblock_ip_entry($entryid){
		global $wpdb;
		$myrows = $wpdb->get_results( "SELECT ip_address FROM ".$wpdb->prefix.MO2f_Constants::BLOCKED_IPS_TABLE." where id=".$entryid );
		if(count($myrows)>0){
		$ip=$wpdb->get_var( "SELECT ip_address FROM ".$wpdb->prefix.MO2f_Constants::BLOCKED_IPS_TABLE." where id=".$entryid );
		$reason=$wpdb->get_var( "SELECT reason FROM ".$wpdb->prefix.MO2f_Constants::BLOCKED_IPS_TABLE." where id=".$entryid );
		}
		
		$wpdb->query(
			"UPDATE ".$wpdb->prefix.MO2f_Constants::USER_TRANSCATIONS_TABLE." SET status='".MO2f_Constants::PAST_FAILED."'
			WHERE ip_address = '".$ip."' AND status='".MO2f_Constants::FAILED."'"
		);
		$wpdb->query(
			"DELETE FROM ".$wpdb->prefix.MO2f_Constants::BLOCKED_IPS_TABLE."
			 WHERE id = ".$entryid
		);
		return $reason;

	}
	public static function get_current_url()
	{
		$protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
		$url	   = $protocol . $_SERVER['HTTP_HOST']  . $_SERVER['REQUEST_URI'];
		return $url;
	}

	function get_blocked_ips(){
		global $wpdb;
		$myrows = $wpdb->get_results( "SELECT id, ip_address, reason, blocked_for_time, created_timestamp FROM ".$wpdb->prefix.MO2f_Constants::BLOCKED_IPS_TABLE );
		return $myrows;
	}

	function is_whitelisted($ipAddress){
		if(empty($ipAddress))
			return false;
		global $wpdb;
		$user_count = $wpdb->get_var( "SELECT COUNT(*) FROM ".$wpdb->prefix.MO2f_Constants::WHITELISTED_IPS_TABLE." where ip_address = '".$ipAddress."'" );
		if($user_count)
			$user_count = intval($user_count);
		if($user_count>0)
			return true;
		return false;
	}

	function whitelist_ip($ipAddress){

		if(empty($ipAddress))
			return;
		if($this->is_whitelisted($ipAddress))
			return;
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix.MO2f_Constants::WHITELISTED_IPS_TABLE,
			array(
				'ip_address' => $ipAddress,
				'created_timestamp' => current_time( 'timestamp' )
			)
		);
	}

	function remove_whitelist_entry($entryid){
		global $wpdb;
		$wpdb->query(
			"DELETE FROM ".$wpdb->prefix.MO2f_Constants::WHITELISTED_IPS_TABLE."
			 WHERE id = ".$entryid
		);
	}

	function get_whitelisted_ips(){
		global $wpdb;
		$myrows = $wpdb->get_results( "SELECT id, ip_address, created_timestamp FROM ".$wpdb->prefix.MO2f_Constants::WHITELISTED_IPS_TABLE );
		return $myrows;
	}


	function add_transactions($ipAddress, $username, $type, $status,$url=null){
		global $wpdb;
		if ($username == '') {
			$username = "-";
		}
		$url=is_null($url) ? '' : $url;
		$wpdb->insert(
			$wpdb->prefix.MO2f_Constants::USER_TRANSCATIONS_TABLE,
			array(
				'ip_address' => $ipAddress,
				'username' => $username,
				'type' => $type,
				'status' => $status,
				'url'  =>$url,
				'created_timestamp' => current_time( 'timestamp' )
			)
		);
	}
	
	public static function is_validPassword($errors, $username, $password){
		
		$enforceStrongPasswds = get_option('mo2f_enforce_strong_passswords');
		if ($enforceStrongPasswds && !MO2f_Handler::mo2f_isStrongPasswd($password, $username)) {
			$errors->add('pass', __('Please choose a stronger password. Try including numbers, symbols, and a mix of upper and lowercase letters and remove common words.'));
			return $errors;
		}
		
		return $errors;
		
	}
	function mo2f_clear_login_report() {
		global $wpdb;
		$wpdb->query("DELETE FROM " . $wpdb->prefix . MO2f_Constants::USER_TRANSCATIONS_TABLE . " WHERE Status='".MO2f_Constants::SUCCESS."' or Status= '".MO2f_Constants::PAST_FAILED."'  OR Status='".MO2f_Constants::FAILED."'");

	}
	function mo2f_clear_error_report() {
		global $wpdb;
		$wpdb->query( "DELETE FROM " . $wpdb->prefix . MO2f_Constants::USER_TRANSCATIONS_TABLE . " WHERE Status='".MO2f_Constants::ACCESS_DENIED."' " );
	}

	function get_all_transactions() {
		global $wpdb;
		$myrows = $wpdb->get_results("SELECT ip_address, username, type, status, created_timestamp,url FROM " . $wpdb->prefix . MO2f_Constants::USER_TRANSCATIONS_TABLE . " order by id desc limit 5000");
		return $myrows;
	}

	function move_failed_transactions_to_past_failed($ipAddress){
		global $wpdb;
		$wpdb->query(
			"UPDATE ".$wpdb->prefix.MO2f_Constants::USER_TRANSCATIONS_TABLE." SET status='".MO2f_Constants::PAST_FAILED."'
			WHERE ip_address = '".$ipAddress."' AND status='".MO2f_Constants::FAILED."'"
		);
	}

	function delete_last_transaction($ipAddress, $username, $type, $status) {
		global $wpdb;
		$wpdb->query(
			"DELETE FROM ".$wpdb->prefix.MO2f_Constants::USER_TRANSCATIONS_TABLE." 
			WHERE ip_address = '".$ipAddress."' AND status='".$status."' AND username='".$username."' AND type='".$type."' order by created_timestamp desc limit 1"
		);
	}


	function delete_all_transactions(){


		global $wpdb;
		$wpdb->query("DELETE FROM ".$wpdb->prefix.MO2f_Constants::USER_TRANSCATIONS_TABLE." WHERE Status='success' or Status= 'pastfailed'  OR Status='failed'");

	}

	function get_failed_attempts_count($ipAddress){
		global $wpdb;

		$user_count = $wpdb->get_var( "SELECT COUNT(*) FROM ".$wpdb->prefix.MO2f_Constants::USER_TRANSCATIONS_TABLE." where ip_address = '".$ipAddress."'
		AND status = '".MO2f_Constants::FAILED."'" );
		if($user_count){
			$user_count = intval($user_count);
			return $user_count;
		}
		return 0;
	}
	
	//strong password
	//check if user is logged in
	
	public static function hasLoginCookie(){
		if(isset($_COOKIE)){
			if(is_array($_COOKIE)){
				foreach($_COOKIE as $key => $val){
					if(strpos($key, 'wordpress_logged_in') === 0){
						return true;
					}
				}
			}
		}
		return false;
	}
	
	public static function mo2f_isStrongPasswd($passwd, $username ) {
		$strength = 0;
				
		if(strlen( trim( $passwd ) )  < 5)
			return false;
		
		if(strtolower( $passwd ) == strtolower( $username ) )
			return false;
		
		if(preg_match('/(?:password|passwd|mypass|wordpress)/i', $passwd)){
			return false;
		}
		if($num = preg_match_all( "/\d/", $passwd, $matches) ){
			$strength += ((int)$num * 10);
		}
		if ( preg_match( "/[a-z]/", $passwd ) )
			$strength += 26;
		if ( preg_match( "/[A-Z]/", $passwd ) )
			$strength += 26;
		if ($num = preg_match_all( "/[^a-zA-Z0-9]/", $passwd, $matches)){
			$strength += (31 * (int)$num);

		}
		if($strength > 60){
			return true;
		}
	}

} ?>
