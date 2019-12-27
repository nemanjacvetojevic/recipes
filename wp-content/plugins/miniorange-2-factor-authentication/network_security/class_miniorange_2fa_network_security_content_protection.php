<?php 
Class mo2f_file_protection{
	function mo2f_update_htaccess_configuration(){
		$base = dirname(dirname(dirname(dirname(dirname(__FILE__)))));
		$htaccesspath = $base.DIRECTORY_SEPARATOR.".htaccess";
		if(!file_exists($htaccesspath)){
			$f = fopen($base.DIRECTORY_SEPARATOR.".htaccess", "a");
			fwrite($f, "# BEGIN WordPress\r\n<IfModule mod_rewrite.c>\r\nRewriteEngine On\r\nRewriteBase /\r\nRewriteRule ^index\.php$ - [L]\r\nRewriteCond %{REQUEST_FILENAME} !-f\r\nRewriteCond %{REQUEST_FILENAME} !-d\r\nRewriteRule . /index.php [L]\r\n</IfModule>\r\n# END WordPress");
			fclose($f);
		}
		$this->mo2f_change_wp_config_protection($htaccesspath);
		$this->mo2f_change_content_protection($htaccesspath);
		$this->mo2f_change_htaccess_file($htaccesspath);
		// $this->mo2f_change_content_file($base);
	}
	
	function mo2f_change_wp_config_protection($htaccesspath){
		$contents = file_get_contents($htaccesspath);
		if (strpos($contents, "\r\n<files wp-config.php>\r\norder allow,deny\r\ndeny from all\r\n</files>") !== false){
			if(!get_option('mo2f_protect_wp_config')){
				$contents = str_replace("\r\n<files wp-config.php>\r\norder allow,deny\r\ndeny from all\r\n</files>", '', $contents);
				file_put_contents($htaccesspath, $contents);
			}
		} else{
			if(get_option('mo2f_protect_wp_config')){
				$f = fopen($htaccesspath, "a");
				fwrite($f, "\r\n<files wp-config.php>\r\norder allow,deny\r\ndeny from all\r\n</files>");
				fclose($f);
			}
		}
	}
	
	function mo2f_change_content_protection($htaccesspath){
		$contents = file_get_contents($htaccesspath);
		if (strpos($contents, "\nOptions All -Indexes") !== false){
			if(!get_option('mo2f_prevent_directory_browsing')){
				$contents = str_replace("\nOptions All -Indexes", '', $contents);
				file_put_contents($htaccesspath, $contents);
			}
		} else {
			if(get_option('mo2f_prevent_directory_browsing')){
				$f = fopen($htaccesspath, "a");
				fwrite($f, "\nOptions All -Indexes");
				fclose($f);
			}
		}
	}

	function mo2f_change_htaccess_file($htaccesspath){
		$contents = file_get_contents($htaccesspath);
		if (strpos($contents, "\r\n<files ~ \"^.*\.([Hh][Tt][Aa])\">\r\norder allow,deny\r\ndeny from all\r\nsatisfy all\r\n</files>") !== false) {
			if(!get_option('mo2f_htaccess_file')){
				$contents = str_replace("\r\n<files ~ \"^.*\.([Hh][Tt][Aa])\">\r\norder allow,deny\r\ndeny from all\r\nsatisfy all\r\n</files>", '', $contents);
				file_put_contents($htaccesspath, $contents);
			}
		} else {
			if(get_option('mo2f_htaccess_file')){
				$f = fopen($htaccesspath, "a");
				fwrite($f, "\r\n<files ~ \"^.*\.([Hh][Tt][Aa])\">\r\norder allow,deny\r\ndeny from all\r\nsatisfy all\r\n</files>");
				fclose($f);
			}
		}	
	}

	function mo2f_change_content_file($base){
		$base = $base;
		$htaccesspath = $base.DIRECTORY_SEPARATOR.".htaccess";
		if(file_exists($htaccesspath)){
			unlink($htaccesspath);
		} else{
			$f = fopen($base.DIRECTORY_SEPARATOR.".htaccess","a");
			fwrite($f, "# BEGIN WordPress\r\norder deny,allow\r\nDeny from all\r\n<files ~ \".(xml|css|jpe?g|png|gif|js)$\">\r\n</files>\r\n# END WordPress");
			fclose($f);
		}
	}
}
?>
