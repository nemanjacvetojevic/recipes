<?php

function mo2f_show_2_factor_login_security($current_user){
	?>
    <div class="mo2f_table_layout" style="border:0px;">
        <!-- Brute Force Configuration -->
        <h3>Login Protection </h3>
        <div class="mo2f_advanced_options_note" style="font-style:Italic;padding:2%;width: 80%;"><?php echo __('A Brute Force Attack is repeated attempts at guessing your username and password to gain access to your WordPress admin. In a brute force attack, automated software is used to generate a large number of consecutive guesses which can be random or leaked passwords data. Limiting the the attempts and blocking the IP can protect your accounts and website from these attacks. ');?></div>
        <form id="mo2f_enable_brute_force_form" method="post" action="">
            <input type="hidden" name="option" value="mo2f_enable_brute_force">
            <br><input type="checkbox" name="mo2f_enable_brute_force_protection" value="1"  <?php if(get_option('mo2f_enable_brute_force')) echo "checked"; if(mo2f_is_customer_registered()){}else{ echo 'disabled';}?> <?php if(get_option('mo2f_enable_brute_force')) echo "checked";?> onchange="document.getElementById('mo2f_enable_brute_force_form').submit();"> Enable the Brute Force Protection
        </form>
        <br>
		<?php if(get_option('mo2f_enable_brute_force')){
			$allwed_login_attempts = 10;
			$time_of_blocking_type = "permanent";
			$time_of_blocking_val = 3;
			if(get_option('mo2f_allwed_login_attempts'))
				$allwed_login_attempts = get_option('mo2f_allwed_login_attempts');
			else
				update_option('mo2f_allwed_login_attempts', $allwed_login_attempts);
			if(get_option('mo2f_time_of_blocking_type'))
				$time_of_blocking_type = get_option('mo2f_time_of_blocking_type');
			if(get_option('mo2f_time_of_blocking_val'))
				$time_of_blocking_val = get_option('mo2f_time_of_blocking_val');
			?>
            <form id="mo2f_enable_brute_force_form" method="post" action="">
                <input type="hidden" name="option" value="mo2f_brute_force_configuration">
                <table class="mo2f_ns_settings_table" style="width:80%;">
                    <tr>
                        <td>Allowed login attempts before blocking an IP  : </td>
                        <td><input class="mo2f_ns_table_textbox" style="width: 65%;" type="number" id="allwed_login_attempts" name="allwed_login_attempts" required placeholder="10" value="<?php echo $allwed_login_attempts;?>" min="1"<?php if(mo2f_is_customer_registered()){}else{ echo 'disabled';}?>/></td>
                    </tr>
                    <tr>
                        <td>Time period for which IP should be blocked  : </td>
                        <td>
                            <select id="time_of_blocking_type" name="time_of_blocking_type" style="width:65%;"<?php if(mo2f_is_customer_registered() ){}else{ echo 'disabled';}?>onchange="if((this.value)!='permanent')document.getElementById('time_of_blocking_val').style.display='block';else document.getElementById('time_of_blocking_val').style.display='none';" >
                                <option value="permanent" <?php if($time_of_blocking_type=="permanent") echo "selected";?>>Permanently</option>
                                <option value="months" disabled >Months (Standard/Premium Feature)</option>
                                <option value="days" disabled >Days (Standard/Premium Feature)</option>
                                <option value="minutes" disabled >Minutes (Standard/Premium Feature)</option>
                            </select>
                        </td>
                    </tr>
                    <tr style="height: 28px;">
                        <td>Show remaining login attempts to user : </td>
                        <td><input type="checkbox" name="show_remaining_attempts" <?php if(get_option('mo2f_show_remaining_attempts')) echo "checked";?> <?php if(mo2f_is_customer_registered()){}else{ echo 'disabled';}?>/></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td><input type="submit" name="submit" style="width:100px;" value="Save" class="button button-primary button-large"<?php if(mo2f_is_customer_registered()){}else{ echo 'disabled';}?>></td>
                    </tr>
                </table>
                <br>
            </form>
		<?php } ?>
    </div>

    <br>
    <script>
		<?php if (!mo2f_is_customer_registered()) { ?>
        jQuery( document ).ready(function() {
            //jQuery(".mo2f_table_layout :input").prop("disabled", true);
            jQuery(".mo2f_table_layout :input[type=text]").val("");
            jQuery(".mo2f_table_layout :input[type=url]").val("");
        });
		<?php } ?>

        jQuery("#time_of_blocking_type").change(function() {
            if(jQuery(this).val()=="permanent")
                jQuery("#time_of_blocking_val").addClass("hidden");
            else
                jQuery("#time_of_blocking_val").removeClass("hidden");
        });
    </script>
	<?php
}

?>