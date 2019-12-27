<?php 

function display_feedback_form() {
	if ( 'plugins.php' != basename( $_SERVER['PHP_SELF'] ) ) {
		return;
	}

	$setup_guide_link_std = "https://plugins.miniorange.com/guide-to-install-wordpress-2fa-standard-plugin";
	$setup_guide_link_prem = "https://plugins.miniorange.com/guide-to-install-wordpress-2fa-premium-plugin";
	$plugins = MO2f_Utility::get_all_plugins_installed();

	wp_enqueue_style( 'wp-pointer' );
	wp_enqueue_script( 'wp-pointer' );
	wp_enqueue_script( 'utils' );
	wp_enqueue_style( 'mo_2_factor_admin_plugins_page_style', plugins_url( "/../includes/css/mo2f_plugins_page.css?version=".MO2F_VERSION."", __FILE__ ) );

	$action 	  = 'install-plugin';
	$slug 		  = 'miniorange-google-authenticator';
	$install_link =  wp_nonce_url(
		add_query_arg( array( 'action' => $action, 'plugin' => $slug ), admin_url( 'update.php' ) ),
		$action.'_'.$slug
	); ?>

    </head>
    <body>


    <!-- The Modal -->
    <div id="myModal" class="mo2f_modal">

        <!-- Modal content -->
        <div class="mo2f_modal-content">
            <h3>Can you please take a minute to give us some feedback? </h3>

            <form name="f" method="post" action="" id="mo2f_feedback">
                <input type="hidden" name="mo2f_feedback" value="mo2f_feedback"/>
				<input type="hidden" name="mo2f_feedback_nonce"
						value="<?php echo wp_create_nonce( "mo2f-feedback-nonce" ) ?>"/>
                <div>
                    <p style="margin-left:2%">
                        <span id="link_id"></span>
						<?php
						$deactivate_reasons = array(
							"Temporary deactivation - Testing",
							"Did not want to create an account",
							"Upgrading to Standard / Premium",
							"Conflicts with other plugins",
							"Redirecting back to login page after Authentication",
							"Database Error",
							"Other Reasons:"
						);


						foreach ( $deactivate_reasons as $deactivate_reasons ) { ?>

                    <div class="radio" style="padding:1px;margin-left:2%">
                        <label style="font-weight:normal;font-size:14.6px" for="<?php echo $deactivate_reasons; ?>">
                            <input type="radio" name="deactivate_plugin" value="<?php echo $deactivate_reasons; ?>"
                                   required>
							<?php echo $deactivate_reasons; ?>
                        <?php if($deactivate_reasons == "Conflicts with other plugins"){ ?>
                            <div id="other_plugins_installed" style="padding:8px;">
		                        <?php  echo $plugins ; ?>
                            </div>
                        <?php } ?>

                        </label>
                    </div>


					<?php } ?>
                    <br>
                    <textarea id="query_feedback" name="query_feedback" rows="4" style="margin-left:2%" cols="50"
                              placeholder="Write your query here"></textarea>

                    <br><br>

                    <div class="mo2f_modal-footer">
                        <input type="submit" name="miniorange_feedback_submit"
                               class="button button-primary button-large" style="float:left" value="Submit"/>
                        <input type="button" name="miniorange_feedback_skip"
                               class="button button-primary button-large"  style="float:right" value="Skip" onclick="document.getElementById('mo2f_feedback_form_close').submit();"/>
                         </div>
                    <br><br>
                </div>
            </form>
            <form name="f" method="post" action="" id="mo2f_feedback_form_close">

                <input type="hidden" name="option" value="mo2f_skip_feedback"/>
				<input type="hidden" name="mo2f_skip_feedback_nonce"
						value="<?php echo wp_create_nonce( "mo2f-skip-feedback-nonce" ) ?>"/>
            </form>
            <form name="f" method="post" action="" id="mo2f_fix_database_error_form">

                <input type="hidden" name="option" value="mo2f_fix_database_error"/>
				<input type="hidden" name="mo2f_fix_database_error_nonce"
						value="<?php echo wp_create_nonce( "mo2f-fix-database-error-nonce" ) ?>"/>
            </form>
        </div>

    </div>

<script>

    function handledeactivateplugin(){
        jQuery('#mo2f_feedback_form_close').submit();
    }
    function mo2f_fix_database_error(){
        jQuery('#mo2f_fix_database_error_form').submit();
    }

    jQuery('#other_plugins_installed').hide();

    jQuery('a[aria-label="Deactivate miniOrange 2 Factor Authentication"]').click(function () {
        // Get the mo2f_modal
		<?php if(! get_option( 'mo2f_feedback_form' )){ ?>
        var mo2f_modal = document.getElementById('myModal');

        // Get the button that opens the mo2f_modal
        var btn = document.getElementById("myBtn");
        // Get the <span> element that closes the mo2f_modal
        var span = document.getElementsByClassName("mo2f_close")[0];


        mo2f_modal.style.display = "block";

        jQuery('input:radio[name="deactivate_plugin"]').click(function () {
            var reason = jQuery(this).val();
            jQuery('#query_feedback').removeAttr('required');
            if (reason == "Did not want to create an account") {
                jQuery('#other_plugins_installed').hide();
                jQuery('#query_feedback').attr("placeholder", "Write your query here.");
                jQuery('#link_id').html('<p style="background-color:#a3e8c2;padding:5px;">We have another 2FA plugin for Wordpress that is entirely on-premise. You can manage all your data within the plugin' +
                    ', without the need of creating an account with miniOrange. To get the plugin, ' +
                    '<a href="<?php echo $install_link?>" target="_blank" onclick="handledeactivateplugin()"><b>Install.</b></a></p>');
                jQuery('#link_id').show();
            }else if (reason == "Upgrading to Standard / Premium") {
                jQuery('#other_plugins_installed').hide();
                jQuery('#query_feedback').attr("placeholder", "Write your query here.");
                jQuery('#link_id').html('<p style="background-color:#a3e8c2;padding:5px;">Thanks for upgrading. For Standard plugin guide,' +
                    ' <a target="_blank" href="<?php echo $setup_guide_link_std; ?>" download><b>click here.</b></a> For Premium plugin guide, <a href="<?php echo $setup_guide_link_prem; ?>" download><b>click here.</b></a></p>');
                jQuery('#link_id').show();
            }else if(reason=="Database Error"){
            jQuery('#query_feedback').attr("placeholder", "Can you please mention the plugin name, and the issue?");
            jQuery('#link_id').html('<p style="background-color:#a3e8c2;padding:5px;">Please click on this link to fix the issue' +
                ', <a onclick="mo2f_fix_database_error();" style="cursor: pointer;"><b>Fix Database Error.</b></a></p>');
            jQuery('#link_id').show();
            }else if (reason == "Conflicts with other plugins") {
                jQuery('#query_feedback').attr("placeholder", "Can you please mention the plugin name, and the issue?");
                jQuery('#other_plugins_installed').show();
                jQuery('#link_id').hide();
            }else if (reason == "Other Reasons:") {
                jQuery('#other_plugins_installed').hide();
                jQuery('#query_feedback').attr("placeholder", "Can you let us know the reason for deactivation");
                jQuery('#query_feedback').prop('required', true);
                jQuery('#link_id').hide();
            }else{
                jQuery('#other_plugins_installed').hide();
                jQuery('#query_feedback').attr("placeholder", "Write your query here.");
                jQuery('#link_id').hide();
            }
        });

        // When the user clicks anywhere outside of the mo2f_modal, mo2f_close it
        window.onclick = function (event) {
            if (event.target == mo2f_modal) {
                mo2f_modal.style.display = "none";
            }
        }
        return false;
		<?php } ?>
    });
</script>  <?php
}

?>