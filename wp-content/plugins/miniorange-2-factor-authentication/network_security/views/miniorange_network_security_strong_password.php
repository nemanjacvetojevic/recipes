<?php
function mo2f_show_2_factor_strong_password($user){?>
<div class="mo2f_table_layout" style="border:0px;">
	<h3><?php echo __('Enforce Strong Password','miniorange-2-factor-authentication');?></h3><hr>
					<p>The feature is used to Enable Strongs passwords based on the options provided. You can choose your min and max length. There is also  options increase the complexity and security of the passwords. </p>
				<form name="f" method="post" action="" id="strongpassword" >
					<input type="checkbox" name="mo2f_enforce_strong_passswords" style="margin-left: 4%;" value="true" <?php checked( get_option('mo2f_enforce_strong_passswords') == 1 ); 
							if(mo2f_is_customer_registered()){}else{ echo 'disabled';} ?> /><span ><b>Enforce Strong Password</b> </span><br><br>
				<div style="margin-left: 4%;" >
				<span style="color:red;">[Enterprise Features]</span><br><b><?php echo mo2f_lt('Min Length:');?></b>		
			              <input type="text" class="mo2f_table_textbox" style="width:7% !important;margin-left: 22.3%;" name="mo2f_pass_min_length" value="<?php echo get_option('mo2f_pass_min_length'); ?>"  disabled />
			            </div>
						<div style="margin-left: 4%;" ><b><?php echo mo2f_lt('Max Length:');?></b>
			              <input type="text" class="mo2f_table_textbox" style="width:7% !important;margin-left: 22%;" name="mo2f_pass_max_length" value="<?php echo get_option('mo2f_pass_max_length'); ?>" disabled />
			            </div>
						<br>
						<span style="margin-left: 4%;"><b>Password Policy:</b></span>
						<div style="margin-left: 31.5%;">
							<input type="checkbox" name="mo2f_pass_lower_case" style="margin-left: 2%;" value="true" <?php checked( get_option('mo2f_pass_lower_case') == true ); 
							?> disabled /><span style="margin-left:4%;"><b>Require Lowercase letter</b> </span><br>
							<input type="checkbox" name="mo2f_pass_upper_case" style="margin-left: 2%;" value="true" <?php checked( get_option('mo2f_pass_upper_case') == 1 ); 
							?> disabled /><span style="margin-left:4%;"><b>Require Uppercase letter</b> </span><br>
							<input type="checkbox" name="mo2f_pass_number" style="margin-left: 2%;" value="true" <?php checked( get_option('mo2f_pass_number') == 1 ); 
							?> disabled /><span style="margin-left:4%;"><b>Require Number (0-9)</b> </span><br>
							<input type="checkbox" name="mo2f_pass_symbol" style="margin-left: 2%;" value="true" <?php checked( get_option('mo2f_pass_symbol') == 1 ); 
							?> disabled /><span style="margin-left:4%;"><b>Require Symbol (e.g. !@#$%^&*.-_)</b> </span>
						</div>
						<br>
						<input type="submit" style="margin-left:4%;" class="button button-primary button-large" value="Save" <?php if(mo2f_is_customer_registered()){}else{ echo 'disabled';}?> />
						<input type="hidden" name="option" value="mo2f_enforce_strong_passsword" />
					</form>
					<br>
	</div>
<?php }

?>