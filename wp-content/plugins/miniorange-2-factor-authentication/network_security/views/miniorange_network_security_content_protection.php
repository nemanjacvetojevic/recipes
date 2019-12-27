<?php
function mo2f_show_2_factor_content_protection($current_user){
	 	$protect_wp_config  = get_option('mo2f_protect_wp_config')                    ? "checked" : "";
        $protect_wp_uploads = get_option('mo2f_prevent_directory_browsing')           ? "checked" : "";
        $disable_file_editing = get_option('mo2f_disable_file_editing')               ? "checked" : ""; 
		
        $plugin_editor        = get_site_url().'/wp-admin/plugin-editor.php';
        $wp_config          = get_site_url().'/wp-config.php';
        $wp_uploads         = get_site_url().'/wp-content/uploads';
        $htaccess_file = get_option('mo2f_htaccess_file') ? "checked" : "";
        // $wp_content_file = get_option('mo2f_wp_content_file');
        ?>
            <div class="mo2f_table_layout" style="border:0px;">
                <h3>Content Protection</h3>
                <form id="mo2f_content_protection" method="post" action="">
                    <input type="hidden" name="option" value="mo2f_content_protection">
                    <p><input type="checkbox"  name="mo2f_protect_wp_config" <?php echo $protect_wp_config;?> value="1" <?php  if(mo2f_is_customer_registered()){}else{ echo 'disabled';}?> > <b>Protect your wp-config.php file</b> &nbsp;&nbsp;<a href="<?php echo $wp_config?>" target="_blank" style="text-decoration:none">( Test it )</a></p>
                    <p>Your WordPress wp-config.php file contains your information like database username and password and it's very important to prevent anyone to access contents of your wp-config.php file.</p>
                    <p><input type="checkbox"  name="mo2f_prevent_directory_browsing" <?php echo $protect_wp_uploads;?>  value="1" <?php if(mo2f_is_customer_registered()){}else{ echo 'disabled';}?>> <b>Prevent Directory Browsing</b> &nbsp;&nbsp; <span style="color:green;font-weight:bold;">(Recommended)</span> &nbsp;&nbsp; <a href="<?php echo $wp_uploads; ?>" target="_blank" style="text-decoration:none">( Test it )</a></p>
                    <p>Prevent access to user from browsing directory contents like images, pdf's and other data from URL e.g. http://website-name.com/wp-content/uploads</p>
                    <p><input type="checkbox"  name="mo2f_disable_file_editing" <?php echo $disable_file_editing; ?> value="1" <?php if(mo2f_is_customer_registered()){}else{ echo 'disabled';}?>> <b>Disable File Editing from WP Dashboard (Themes and plugins)</b> &nbsp;&nbsp;<a href="<?php echo $plugin_editor?>" target="_blank" style="text-decoration:none">( Test it )</a></p>
                    <p>The WordPress Dashboard by default allows administrators to edit PHP files, such as plugin and theme files. This is often the first tool an attacker will use if able to login, since it allows code execution.</p>
                    <p><input type="checkbox"  name="mo2f_htaccess_file" <?php echo $htaccess_file; ?> value="1" <?php if(mo2f_is_customer_registered()){}else{ echo 'disabled';}?>> <b>Protect your .htaccess file</b> &nbsp;&nbsp;<span style="color:green;font-weight:bold;">(Recommended)</span></p>
                    <p>.htaccess has the ability to control your whole website. It is important to first protect this file from unauthorized users.By enabling this you can restrict access to unauthorized users.</p>
                    
                    <br><input type="submit" name="submit" style="width:100px;" value="Save" class="button button-primary button-large"<?php if(mo2f_is_customer_registered()){}else{ echo 'disabled';} ?>>
       		</form>	<br>

			</div>
	<?php }
    ?>