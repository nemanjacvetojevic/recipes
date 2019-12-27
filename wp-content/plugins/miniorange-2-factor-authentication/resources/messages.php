<?php class Mo2f_Messages{
	
		const LOGIN_ATTEMPTS_EXCEEDED = "User exceeded allowed login attempts.";
		const BLOCKED_BY_ADMIN = "Blocked by Admin";
		const IP_RANGE_BLOCKING = "IP Range Blocking";
		const FAILED_LOGIN_ATTEMPTS_FROM_NEW_IP = "Failed login attempts from new IP.";
		const LOGGED_IN_FROM_NEW_IP = "Logged in from new IP.";
		//content protection
		const CONTENT_PROTECTION_ENABLED		= "Your configuration for Content Protection has been saved.";
		

		public static function showMessage($message , $data=array())
		{
			$message = constant( "self::".$message );
		    foreach($data as $key => $value)
		    {
		        $message = str_replace("{{" . $key . "}}", $value , $message);
		    }
		    return $message;
		}

}?>