<?php
function mo_2_factor_register( $user ) {
	global $Mo2fdbQueries;
	if ( mo_2factor_is_curl_installed() == 0 ) { ?>
        <p style="color:red;">(<?php echo mo2f_lt( 'Warning:' ); ?> 
                <a href="http://php.net/manual/en/curl.installation.php" target="_blank"><?php echo mo2f_lt( 'PHP CURL extension' ); ?></a> <?php echo mo2f_lt( 'is not installed or disabled' ); ?>)
            </p>
		<?php
	}

	if ( version_compare( PHP_VERSION, '5.3.0' ) < 0 ) {
		?>
      <p style="color:red;"><b><span style="font-size:18px;">(<?php echo mo2f_lt( 'Warning:' ); ?></span></b> <?php echo mo2f_lt( 'Your current PHP version is ' ); ?><?php echo PHP_VERSION; ?> . <?php echo mo2f_lt( 'Some of the functionality of the plugin may not work in this version of PHP. Please upgrade your PHP version to 5.3.0 or above.' ); ?>
                
            <br> <?php echo mo2f_lt( 'You can also write us by submitting a query on the right hand side in our ' ); ?>
            <b><?php echo mo2f_lt( 'Support Section' ); ?></b>. )</p>
		<?php
	}
	$is_customer_admin          = true;
	$is_customer_admin_registered = get_option( 'mo_2factor_admin_registration_status' );
	if($is_customer_admin_registered)
	    $is_customer_admin          = current_user_can( 'manage_options' ) && get_option( 'mo2f_miniorange_admin' ) == $user->ID;
	$can_display_admin_features = ! $is_customer_admin_registered || $is_customer_admin ? true : false;
	
	$default_tab  = (!$is_customer_admin) ? '2factor_setup' : 'mobile_configure';
	
	$mo2f_active_tab                     = isset( $_GET['mo2f_tab'] ) ? $_GET['mo2f_tab'] : $default_tab ;
	$mo_2factor_user_registration_status = $Mo2fdbQueries->get_user_detail( 'mo_2factor_user_registration_status', $user->ID );
    $account_tab_name = ( in_array( $mo_2factor_user_registration_status, array('MO_2_FACTOR_INITIALIZE_MOBILE_REGISTRATION','MO_2_FACTOR_PLUGIN_SETTINGS') ) ) ? mo2f_lt( 'User Profile' ) : mo2f_lt( 'Account Setup' );
    $mo2f_sub_active_tab = isset( $_GET['mo2f_sub_tab'] ) ? $_GET['mo2f_sub_tab'] : $default_tab ; 
	
	$mo2fa_tab='mo2f_ns';
	
	if($mo2f_active_tab=="mobile_configure"||$mo2f_active_tab=="mo2f_support"||$mo2f_active_tab=="mo2f_custom_form"||$mo2f_active_tab=="mo2f_addon"||$mo2f_active_tab=="2factor_setup"||		$mo2f_active_tab=="mo2f_login"||$mo2f_active_tab=="proxy_setup"||$mo2f_active_tab=="mo2f_video_guide"){
			$mo2fa_tab='2fa';
	}   
	$session_variables = array( 'mo2f_google_auth', 'mo2f_authy_keys', 'mo2f_mobile_support' );
		 if($mo2f_active_tab=='mobile_configure'){
			$mo2f_second_factor = mo2f_get_activated_second_factor( $user );
			$selected_method=mo2f_method_display_name($user,$mo2f_second_factor);
			if($selected_method=='NONE'){
				$selected_method = "Not Configured"; 
			}
			$selected_method='<span id="mo2f_selected_method" style="font-size:14px;color:darkorange;"> - '.$selected_method.'</span>';		
		}
	
	?>
	
    <div class="wrap" >
        <div style="display:block;font-size:23px;line-height:29px;">
			<div >
			<button id="mo2f_need_help" class="need-help-button" data-show="false" onclick="mo2f_opensupport()" ><?php echo mo2f_lt( 'NEED HELP?' ); ?></span>         </button>
				
					<a id="mo2f_restart_tour" class="add-new-h2" style="background-color: #006799;color: white;width: 15%;border: 1px solid #006799;left: 70.5%;top:0px;
					" onclick="restart_tour();"
					   ><?php echo mo2f_lt( 'RESTART TOUR' ); ?></a>
					
					
			</div>
				
        </div>
    </div>
	 <div id="messages"></div>
	 <?php echo  mo2f_fixed_support();?>
	 
	
    <div class="mo2f_container">
        <div style="display: inline-flex; width: 100%;">
		
    	<div class="tab" style="height:54px;margin-top: 9px;border-bottom: 0px;text-align:center;border-radius: 15px 0px 0px 0px;width: 15%;">
    		<div><img style="float:left;margin-left:8px;width: 43px;height: 45px;padding-top: 5px;" src="<?php echo plugins_url( 'includes/images/logo.png"', __FILE__ ); ?>"></div>
    		<br>
    		<span style="font-size:20px;color:white;float:left;">miniOrange</span>
    	</div>
	
    	   <div id="tab" class="nav-tab-wrapper" style="border: 0px;width: 85%;">
                <div class="" style="display: inline-flex;width: 100%;">
				 
                   <a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mobile_configure" style="text-align: center;height:54px;width:50%;margin: 0;line-height: 3;" class="nav-tab <?php echo $mo2fa_tab=='2fa' ? 'nav-tab-active' : ''; ?>" id="mo2f_tab3"><?php echo mo2f_lt( 'Two Factor Authentication');  if($mo2f_active_tab=='mobile_configure'){echo $selected_method;} ?></a>
                    <?php if ( $can_display_admin_features ) { ?>
                       
                    <a  href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_network&amp;mo2f_sub_tab=mo2f_limit"
                        style="text-align: center;height:54px;width:50%;margin: 0;line-height: 3;" class="nav-tab <?php echo $mo2f_active_tab == 'mo2f_network' ? 'nav-tab-active' : ''; ?>"
                        id="mo2f_tab4"><?php echo mo2f_lt( 'Website Security' ); ?></a>
					<a  href="admin.php?page=miniOrange_2_factor_settings&mo2f_tab=mo2f_pricing"
                        style="text-align: center;width:33%;margin:0;line-height: 3;" class="nav-tab mo2f_orange"
                        id="mo2f_tab4"><?php echo mo2f_lt( 'Upgrade Plans' ); ?></a><?php } ?>
                </div>
            </div>
        </div>
	   <div >
		<?php if ( $mo2f_active_tab != 'mo2f_pricing'){?>
		<div class="tab" style="min-height:395px;border-radius: 0px 0px 0px 15px; height: 445px">
			<span class="tooltiptext"></span>
			<?php if ( $mo2fa_tab=='2fa' && $mo2f_active_tab != 'mo2f_pricing') {?>
				<a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mobile_configure" class="tablinks <?php echo $mo2f_active_tab == 'mobile_configure' ? 'active' : ''; ?>" id="mo2f_tab3"><?php echo mo2f_lt( 'Setup Two-Factor' ); ?></a>
				<?php if ( $can_display_admin_features ) { ?>
					<?php if ( get_option( 'mo2f_is_NC' ) ) { ?>
						<a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_addon&amp;mo2f_sub_tab=mo2f_sub_tab_rba" class="tablinks <?php echo $mo2f_active_tab == 'mo2f_addon' ? 'active' : ''; ?>" id="mo2f_tab4"><?php echo mo2f_lt( 'Add-ons' ); ?></a><?php }
					if( ! get_option('mo2f_is_NC') )  { ?>
					<a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_login"
					   class="tablinks <?php echo $mo2f_active_tab == 'mo2f_login' ? 'active' : ''; ?>"
					   id="mo2f_tab2"><?php echo mo2f_lt( 'Login Options' ); ?></a>
					<?php } ?>
					<a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_custom_form"
					   class="tablinks <?php echo $mo2f_active_tab == 'mo2f_custom_form' ? '    active' : ''; ?>"
					   id="mo2f_tab7"><?php echo mo2f_lt( 'Custom Login Form' ) ; ?></a>
				   <a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_support"
					   class="tablinks <?php echo $mo2f_active_tab == 'mo2f_support' ? '    active' : ''; ?>"
					   id="mo2f_tab7"><?php echo mo2f_lt( 'Support' ) ; ?></a>
				   <a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_video_guide"
					   class="tablinks <?php echo $mo2f_active_tab == 'mo2f_video_guide' ? '    active' : ''; ?>"
					   id="mo2f_tab8"><?php echo mo2f_lt( 'Video Guide' ) ; ?></a>
					<a id="mo2f_forum" class="tablinks" href="https://wordpress.org/support/plugin/miniorange-2-factor-authentication" target="_blank" ><?php echo mo2f_lt( 'WP Forum' ); ?></a>
					<a id="mo2f_monav_faq" class="tablinks" href="https://faq.miniorange.com/kb/two-factor-authentication"
						target="_blank" ><?php echo mo2f_lt( 'FAQ' ); ?></a>
				    <a id="mo2f_account" href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=2factor_setup"
					   class="tablinks <?php echo $mo2f_active_tab == '2factor_setup' ? 'active' : ''; ?>"
					  > <?php echo $account_tab_name; ?></a>
					    

				  
                    <?php }//mo2f_network 
                } else if ( $mo2fa_tab=='mo2f_ns' && $mo2f_active_tab != 'mo2f_pricing'){ ?>
                    <a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_network&amp;mo2f_sub_tab=mo2f_limit" class="tablinks <?php echo $mo2f_sub_active_tab == 'mo2f_limit' ? '  active' : ''; ?>" id="mo2f_tab7"><?php echo mo2f_lt( 'Login Protection' ) ; ?></a>
                   
                        <a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_network&amp;mo2f_sub_tab=show_2_factor_ip_block" class="tablinks <?php echo $mo2f_sub_active_tab == 'show_2_factor_ip_block' ? 'active' : ''; ?>" id="mo2f_tab4"><?php echo mo2f_lt( 'IP Blocking' ); ?></a>
                    <a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_network&amp;mo2f_sub_tab=mo2f_monitor" class="tablinks <?php echo $mo2f_sub_active_tab == 'mo2f_monitor' ? 'active' : ''; ?>" id="mo2f_tab3"><?php echo mo2f_lt( 'Monitoring' ); ?></a>  
					<a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_network&amp;mo2f_sub_tab=mo2f_strong_password" class="tablinks <?php echo $mo2f_sub_active_tab == 'mo2f_strong_password' ? '  active' : ''; ?>" id="mo2f_tab7"><?php echo mo2f_lt( 'Strong Password' ) ; ?></a>
					<a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_network&amp;mo2f_sub_tab=mo2f_content_protection" class="tablinks <?php echo $mo2f_sub_active_tab == 'mo2f_content_protection' ? '  active' : ''; ?>" id="mo2f_tab8"><?php echo mo2f_lt( 'File Protection' ) ; ?></a>
					  <a id="mo2f_account" href="admin.php?page=miniOrange_2_factor_settings&mo2f_tab=mo2f_network&mo2f_sub_tab=2factor_setup"
					   class="tablinks <?php echo $mo2f_sub_active_tab == '2factor_setup' ? 'active' : ''; ?>"
					  > <?php echo $account_tab_name; ?></a>
					  <a id="mo2f_forum" class="tablinks" href="https://wordpress.org/support/plugin/miniorange-2-factor-authentication" target="_blank" ><?php echo mo2f_lt( 'WP Forum' ); ?></a>
					<a id="mo2f_monav_faq" class="tablinks" href="https://faq.miniorange.com/kb/two-factor-authentication"
						target="_blank" ><?php echo mo2f_lt( 'FAQ' ); ?></a>
                    <?php 
                } ?>
            </div>
				<?php }?>
		   <div id="mo2f_left_navigation" class="tabcontent" <?php if ( $mo2f_active_tab == 'mo2f_pricing'){echo 'style="width: 100%;"';}?>>
               
                            <?php
							
                            /* to update the status of existing customers for adding their user registration status */
                            if ( get_option( 'mo_2factor_admin_registration_status' ) == 'MO_2_FACTOR_CUSTOMER_REGISTERED_SUCCESS' && get_option( 'mo2f_miniorange_admin' ) == $user->ID ) {
                                $Mo2fdbQueries->update_user_details( $user->ID, array( 'user_registration_with_miniorange' => 'SUCCESS' ) );
                            }
                            /* ----------------------------------------- */
                            $session_variables = array( 'mo2f_google_auth', 'mo2f_authy_keys', 'mo2f_mobile_support' );
							if ( $mo2f_active_tab == 'mobile_configure' ) {
                                mo2f_select_2_factor_method( $user, $mo2f_second_factor );
                            } else if ( $mo2f_active_tab == 'mo2f_video_guide' ) {
                                mo2f_video_guide();
                            } else if ( $can_display_admin_features && $mo2f_sub_active_tab == 'mo2f_limit' ) {
                                MO2f_Utility::unset_session_variables( $session_variables );
								do_action('mo2f_network_view_brute_force',$user);
//                              show_2_factor_login_security($user);
                            } else if ( $can_display_admin_features && $mo2f_sub_active_tab == 'mo2f_strong_password' ) {
                                MO2f_Utility::unset_session_variables( $session_variables );
								do_action('mo2f_network_view_strong_password',$user);
//                              show_2_factor_login_security($user);
                            } else if ( $can_display_admin_features && ($mo2f_sub_active_tab == 'mo2f_content_protection')) {
								MO2f_Utility::unset_session_variables( $session_variables );
							  //  show_2_factor_ip_block($user);
								do_action('mo2f_network_view_content_protection',$user);
							} else if ( $can_display_admin_features && ($mo2f_sub_active_tab == 'show_2_factor_ip_block' ||$mo2f_sub_active_tab == 'mo2f_ip' )) {
                                MO2f_Utility::unset_session_variables( $session_variables );
                              //  show_2_factor_ip_block($user);
								do_action('mo2f_network_view_ip_blocking',$user);
                            } else if ( $can_display_admin_features && $mo2f_active_tab == 'mo2f_support' ) {
                                MO2f_Utility::unset_session_variables( $session_variables );
                                mo2f_support();
                            } else if ( $can_display_admin_features && $mo2f_active_tab == 'proxy_setup' ) {
                                MO2f_Utility::unset_session_variables( $session_variables );
                                show_2_factor_proxy_setup( $user );
                            }else if ( $can_display_admin_features && $mo2f_sub_active_tab == 'mo2f_monitor' ) {
                                MO2f_Utility::unset_session_variables( $session_variables );
                                //show_2_factor_user_login_reports( $user );
								do_action('mo2f_network_view_monitoring',$user);
                            }else if ( $can_display_admin_features && $mo2f_active_tab == 'mo2f_login' ) {
                                MO2f_Utility::unset_session_variables( $session_variables );
                                show_2_factor_login_settings( $user );
                            }else if ( $can_display_admin_features && $mo2f_active_tab == 'mo2f_custom_form' ) {
                                show_2_factor_custom_form( $user );
                            } else if ( $can_display_admin_features && $mo2f_active_tab == 'mo2f_addon' ) {
                                MO2f_Utility::unset_session_variables( $session_variables );
                                show_2_factor_addons( $user );
                                do_action( 'mo2f_new_addon' );
                            } else if ( $can_display_admin_features && $mo2f_active_tab == 'mo2f_pricing' ) {
                                MO2f_Utility::unset_session_variables( $session_variables );
                                show_2_factor_pricing_page( $user );
                            }else {
                                MO2f_Utility::unset_session_variables( $session_variables );
								

                                if ( get_option( 'mo_2factor_admin_registration_status' ) == 'MO_2_FACTOR_CUSTOMER_REGISTERED_SUCCESS' && get_option( 'mo2f_miniorange_admin' ) != $user->ID ) {
                                    if ( in_array( $mo_2factor_user_registration_status, array(
                                        'MO_2_FACTOR_OTP_DELIVERED_SUCCESS',
                                        'MO_2_FACTOR_OTP_DELIVERED_FAILURE'
                                    ) ) ) {
                                        mo2f_show_user_otp_validation_page();  // OTP over email validation page
                                    } else if ( $mo_2factor_user_registration_status == 'MO_2_FACTOR_INITIALIZE_MOBILE_REGISTRATION' ) {  //displaying user profile
                                        $mo2f_second_factor = mo2f_get_activated_second_factor( $user );
                                        mo2f_show_instruction_to_allusers( $user, $mo2f_second_factor );
                                    } else if ( $mo_2factor_user_registration_status == 'MO_2_FACTOR_PLUGIN_SETTINGS' ) {
                                        $mo2f_second_factor = mo2f_get_activated_second_factor( $user );
                                        mo2f_show_instruction_to_allusers( $user, $mo2f_second_factor );  //displaying user profile
                                    } else {
                                        show_user_welcome_page( $user );  //Landing page for additional admin for registration
                                    }
                                } else {
                                    if ( in_array( $mo_2factor_user_registration_status, array(
                                        'MO_2_FACTOR_OTP_DELIVERED_SUCCESS',
                                        'MO_2_FACTOR_OTP_DELIVERED_FAILURE'
                                    ) ) ) {
                                        mo2f_show_otp_validation_page( $user );  // OTP over email validation page for admin
                                    } else if ( $mo_2factor_user_registration_status == 'MO_2_FACTOR_INITIALIZE_MOBILE_REGISTRATION' ) {  //displaying user profile
                                        $mo2f_second_factor = mo2f_get_activated_second_factor( $user );
                                        mo2f_show_instruction_to_allusers( $user, $mo2f_second_factor );
                                    }  else if ( $mo_2factor_user_registration_status == 'MO_2_FACTOR_PLUGIN_SETTINGS' ) {
										$mo2f_second_factor = mo2f_get_activated_second_factor( $user );
										mo2f_show_instruction_to_allusers( $user, $mo2f_second_factor );  //displaying user profile

									} else if ( $mo_2factor_user_registration_status == 'MO_2_FACTOR_VERIFY_CUSTOMER' ) {
                                        mo2f_show_verify_password_page();  //verify password page
                                    } else if ( ! mo2f_is_customer_registered() ) {
                                        delete_option( 'password_mismatch' );
                                        mo2f_show_registration_page( $user ); //new registration page
                                    }
                                }
					}
					?>
                        
            </div>
        </div>
        </div>
	<?php
}

 function mo2f_fixed_support(){
            global $user;
            global $Mo2fdbQueries;
            $user       = wp_get_current_user();
            $email      = $Mo2fdbQueries->get_user_detail( 'mo2f_user_email', $user->ID );
            $phone      = $Mo2fdbQueries->get_user_detail( 'mo2f_user_phone', $user->ID );
            $user_email = $email ? $email : $user->user_email;
            $user_phone = $phone != 'false' ? $phone : '';?>
	 <style>
        .mo2f_backdrop{
            top: 0;
            left: 0;
            position: fixed;
            width: 100% !important;
            background-color: #000 !important;
            opacity: 0.96 !important;
            height: 100% !important;
            z-index: 99999;
        }
   </style>
	<div class="mo2f_backdrop" id="mo2f_backdrop" hidden>
    <div class="mo2f_support_layout" id="mo2f_support_layout" hidden>

        <h3><?php echo mo2f_lt( 'Support' ); ?>
            <a id="mo2f_wp_forum" class="add-new-h2" href="https://wordpress.org/support/plugin/miniorange-2-factor-authentication"
               target="_blank"  style="float:right"><?php echo mo2f_lt( 'Ask questions on the WP Forum' ); ?></a>
            <a id="mo2f_mo_faq" class="add-new-h2" href="https://faq.miniorange.com/kb/two-factor-authentication"
           target="_blank" style="float:right"><?php echo mo2f_lt( 'FAQ' ); ?></a>
        </h3>
        <hr width="100%">
        <br>
        <form name="f" method="post" action="">
            <div><?php echo mo2f_lt( 'Shoot us a query and we will get back to you.' ); ?> </div>
            <br>
            <div><?php echo mo2f_lt( 'Have a look at these FAQ\'s to see if your question has been answered already! ' ); ?>
                <a href="https://faq.miniorange.com/kb/two-factor-authentication" target="_blank"><b>Frequently Asked
                        Questions.</b></a>
            </div>

            <br>
            <div>
                <table style="width:95%;">
                    <tr>
                        <td>
                            <input type="email" class="mo2f_table_textbox" id="EMAIL_MANDATORY" name="EMAIL_MANDATORY"
                                   value="<?php echo $user_email ?>"
                                   placeholder="Enter your email" required="true"/>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="text" class="mo2f_table_textbox" style="width:100% !important;"
                                   name="mo2f_query_phone" id="mo2f_query_phone"
                                   value="<?php echo $user_phone; ?>"
                                   placeholder="Enter your phone"/>
                        </td>

                    </tr>
                    <tr>
                        <td>
                            <textarea id="query" name="query" cols="52" rows="7"
                                      style="resize: vertical;width:100%;height:143px;"
                                      onkeyup="mo2f_valid(this)" onblur="mo2f_valid(this)" onkeypress="mo2f_valid(this)"
                                      placeholder="<?php echo mo2f_lt( 'Your query here...' ); ?>"></textarea>
                        </td>
                    </tr>
                </table>
            </div>
            <br>
            <input type="hidden" name="option" value="mo_2factor_send_query"/>
			<input type="hidden" name="mo_2factor_send_query_nonce"
						value="<?php echo wp_create_nonce( "mo-2factor-send-query-nonce" ) ?>"/>
            <input type="submit" name="send_query" id="send_query"
                   value="<?php echo mo2f_lt( 'Submit Query' ); ?>"
                   style="float:left;" class="button button-primary button-large"/>
           <button type="button" class="button button-primary button-large" style="margin-left:2%;" onclick="mo2f_closesupport()">Close</button>
            <br><br>
        </form>
        <br>
    </div>
    <br>
</div>
    <script>
        jQuery("#mo2f_query_phone").intlTelInput();
         var mo2f_backdrop = document.getElementById('mo2f_backdrop');
      // hide if clicked in
        window.onclick = function (event) {
            if (event.target == mo2f_backdrop) {
            mo2f_closesupport();
            }
        }
        function mo2f_opensupport() {

                  document.getElementById("mo2f_backdrop").style.display = "block";
                 jQuery("#mo2f_support_layout").slideToggle(400, function() {
                  document.getElementById("mo2f_support_layout").style.display = "block";
            });
        }

        function mo2f_closesupport() {
                 jQuery("#mo2f_support_layout").slideToggle(400, function() {
                     document.getElementById("mo2f_support_layout").style.display = "none";
                     document.getElementById("mo2f_backdrop").style.display = "none";
              });
        }
    </script>
        <?php
    }

function show_2_factor_enforce_password() {    
        ?>

        <div class="mo2f_image_container" style="background: white;border:0px;"><br>
            <div style="text-align: center;"><b ><span class="impt">*</span><?php echo mo2f_lt( 'This Features are only enable for Premium/Standard Users to use this features upgrade to  ' ); ?><a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_pricing"" target="_blank"><b style="text-decoration: underline;color: red">Premium/Standard</b> </a>Plan.
            </b></div><br>
            <img style="float:left;width:100%; opacity: 0.7;" src="<?php echo plugins_url( 'includes/images/enforce_password.png"', __FILE__ ); ?>">
            
        </div>

      <?php }

function mo2f_show_registration_page( $user ) {
	global $Mo2fdbQueries;
	$mo2f_active_tab  = isset( $_GET['mo2f_tab'] ) ? $_GET['mo2f_tab'] : '';
	$mo2f_active_sub_tab  = isset( $_GET['mo2f_sub_tab'] ) ? $_GET['mo2f_sub_tab'] : '';//mo2f_sub_tab=2factor_setup
	$is_registration = ($mo2f_active_tab =='2factor_setup'||$mo2f_active_sub_tab=='2factor_setup') ? true : false;
	
	?>
    <!--Register with miniOrange-->
    <form name="f" method="post" action="">
        <input type="hidden" name="option" value="mo_auth_register_customer"/>
        <input type="hidden" name="miniorange_register_customer_nonce"
               value="<?php echo wp_create_nonce( "miniorange-register-customer-nonce" ) ?>"/>
        <div <?php if($is_registration) { ?>class="mo2f_proxy_setup" style="width:100%;"<?php } ?>>
			<?php if($is_registration) { ?>
                <h3><span><?php echo mo2f_lt( 'Register with miniOrange' ); ?></span></h3><hr>
			<?php } ?>
            <div id="panel1">
                <br>
                <div><?php echo mo2f_lt( 'Already have an account?' ) . '&nbsp;&nbsp;<a style="font-weight:bold; color:limegreen" href="#mo2f_account_exist">' . mo2f_lt( 'SIGN IN' ) ?></a></div>
                
                <table class="mo2f_settings_table" style="border-collapse: separate; border-spacing: 0 1em;">
                    <tr>

                        <td style="width:30%"><b><span class="impt">*</span><?php echo mo2f_lt( 'Email :' ); ?></b></td>
                        <td style="width:70%"><input class="mo2f_table_textbox" type="email" name="email" required
                                                     value="<?php if ( get_option( 'mo2f_email' ) ) {
							                             echo get_option( 'mo2f_email' );
						                             } else {
							                             echo $user->user_email;
						                             } ?>"/></td>
                    </tr>
                    <tr>
                        <td ><b><span class="impt">*</span><?php echo mo2f_lt( 'Password :' ); ?></b></td>
                        <td rowspan="2"><input class="mo2f_table_textbox"  type="password" required name="password" pattern="^[(\w)*(!@#$.%^&*\-_)*]+$" title="Password length between 6 - 15 characters. Only following symbols (!@#.$%^&*) should be present."/><label style="font-size:11px;color:red;">(Minimum 6 and Maximum 15 characters should be present. Only following symbols ()!@#.$%^&* are allowed.)</label><br></td>

                    </tr>
                    <tr ><td></td><td></td></tr>
                    <tr>
                        <td><b><span class="impt">*</span><?php echo mo2f_lt( 'Confirm Password :' ); ?></b></td>
                        <td><input class="mo2f_table_textbox" type="password" required name="confirmPassword" pattern="^[(\w)*(!@#$.%^&*\-_)*]+$" title="Password length between 6 - 15 characters. Only following symbols (!@#.$%^&*) should be present." /></td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td><input type="submit" name="submit" style="float:right;"
                                   value="<?php echo mo2f_lt( 'Continue' ); ?>"
                                   class="button button-primary button-large"/></td>
                    </tr>
                </table>
            </div>
        </div>
    </form>
    <form name="f" method="post" action="" class="mo2f_verify_customerform">
        <input type="hidden" name="option" value="mo2f_goto_verifycustomer">
        <input type="hidden" name="mo2f_goto_verifycustomer_nonce"
               value="<?php echo wp_create_nonce( "mo2f-goto-verifycustomer-nonce" ) ?>"/>
    </form>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js"></script>

    <script>
		
        jQuery('a[href=\"#mo2f_account_exist\"]').click(function (e) {
            jQuery('.mo2f_verify_customerform').submit();
        });
		
		
		
    </script>
	<?php
}


function mo2f_show_otp_validation_page( $user ) {
	global $Mo2fdbQueries;
	$phone = $Mo2fdbQueries->get_user_detail( 'mo2f_user_phone', $user->ID );
	?>
    <!-- Enter otp -->

    <div>
        <div>
            <table  style="border-collapse: separate; border-spacing: 0 1em;">
                <form name="f" method="post" id="mo_2f_otp_form" action="">
				<input type="hidden" name="mo_2factor_validate_otp_nonce"
						value="<?php echo wp_create_nonce( "mo-2factor-validate-otp-nonce" ) ?>"/>
                    <input type="hidden" name="option" value="mo_2factor_validate_otp"/>
                    <tr>
                        <td><b><font color="#FF0000">*</font><?php echo mo2f_lt( 'Enter OTP:' ); ?></b></td>
                        <td colspan="2"><input class="mo2f_table_textbox" autofocus="true" type="text" name="otp_token"
                                               required placeholder="<?php echo mo2f_lt( 'Enter OTP' ); ?>"
                                               style="width:95%;"/></td>
                        <td><a href="#resendotplink"><?php echo mo2f_lt( 'Resend OTP ?' ); ?></a></td>
                    </tr>

                    <tr>
                        <td>&nbsp;</td>
                        <td style="width:17%">
                            <input type="submit" name="submit" value="<?php echo mo2f_lt( 'Validate' ); ?>"
                                   class="button button-primary button-large"/></td>

                </form>
                <form name="f" method="post" action="">
                    <td>
                        <input type="hidden" name="option" value="mo_2factor_gobackto_registration_page"/>
                        <input type="submit" name="mo2f_goback" id="mo2f_goback"
                               value="<?php echo mo2f_lt( 'Back' ); ?>" class="button button-primary button-large"/>
						   <input type="hidden" name="mo_2factor_gobackto_registration_page_nonce"
						value="<?php echo wp_create_nonce( "mo-2factor-gobackto-registration-page-nonce" ) ?>"/>
                    </td>
                </form>
                </td>
                </tr>
                <form name="f" method="post" action="" id="resend_otp_form">
				<input type="hidden" name="mo_2factor_resend_otp_nonce"
						value="<?php echo wp_create_nonce( "mo-2factor-resend-otp-nonce" ) ?>"/>
                    <input type="hidden" name="option" value="mo_2factor_resend_otp"/>
                </form>

            </table>
            <br>
        </div>
        <div>
            <script>
                jQuery("#phone").intlTelInput();
                jQuery('a[href=\"#resendotplink\"]').click(function (e) {
                    jQuery('#resend_otp_form').submit();
                });
                jQuery('a[href=\"#resendsmsotplink\"]').click(function (e) {
                    jQuery('#phone_verification').submit();
                });
            </script>

            <br><br>
        </div>


    </div>

	<?php
}
function mo2f_rba_description($mo2f_user_email) {?>
  <div id="mo2f_rba_addon">
    <?php if ( get_option( 'mo2f_rba_installed' ) ) { ?>
        <a href="<?php echo admin_url(); ?>plugins.php" id="mo2f_activate_rba_addon"
           class="button button-primary button-large"
           style="float:right; margin-top:2%;"><?php echo __( 'Activate Plugin', 'miniorange-2-factor-authentication' ); ?></a>
    <?php } ?>
    <?php if ( ! get_option( 'mo2f_rba_purchased' ) ) { ?>  
        <a onclick="mo2f_addonform('wp_2fa_addon_rba')" id="mo2f_purchase_rba_addon"
           class="button button-primary button-large"
           style="float:right;"><?php echo __( 'Purchase', 'miniorange-2-factor-authentication' ); ?></a><?php } ?>
    <div id="mo2f_rba_addon_hide"><h3 id="toggle_rba_description"
                                      class="mo2f_pointer"><?php echo __( 'Description', 'miniorange-2-factor-authentication' ); ?> </h3>
        <p id="rba_description" style="margin:2% 2% 2% 4%">
            <?php echo __( 'This Add-On helps you in remembering the device, in which case you will not be prompted for the 2-factor authentication
            if you login from the remembered device again. You can also decide the number of devices that can be remembered. Users can also be restricted access to the site based on the IP address they are logging in from.', 'miniorange-2-factor-authentication' ); ?>
        </p>
        <br>
        <div id="mo2f_hide_rba_content">

            <div class="mo2f_box">
                <h3><?php echo __( 'Remember Device', 'miniorange-2-factor-authentication' ); ?></h3>
                <hr>
                <p id="mo2f_hide_rba_content"><?php echo __( 'With this feature, User would get an option to remember the personal device where Two Factor is not required. Every time the user logs in with the same device it detects                     the saved device so he will directly login without being prompted for the 2nd factor. If user logs in from new device he will be prompted with 2nd                          Factor.', 'miniorange-2-factor-authentication' ); ?>

                </p>
            </div>
            <br><br>
            <div class="mo2f_box">
                <h3><?php echo __( 'Limit Number Of Device', 'miniorange-2-factor-authentication' ); ?></h3>
                <hr>
                <p><?php echo __( 'With this feature, the admin can restrict the number of devices from which the user can access the website. If the device limit is exceeded the admin can set three actions where it can allow the users to login, deny the access or challenge the user for authentication.', 'miniorange-2-factor-authentication' ); ?>
                </p>

            </div>
            <br><br>
            <div class="mo2f_box">
                <h3><?php echo __( 'IP Restriction: Limit users to login from specific IPs', 'miniorange-2-factor-authentication' ); ?></h3>
                <hr>
                <p><?php echo __( 'The Admin can enable IP restrictions for the users. It will provide additional security to the accounts and perform different action to the accounts only from the listed IP Ranges. If user tries to access with a restricted IP, Admin can set three action: Allow, challenge or deny. Depending upon the action it will allow the user to login, challenge(prompt) for authentication or deny the access.', 'miniorange-2-factor-authentication' ); ?>
				
            </div>
			<br>
        </div>

    </div>
    <div id="mo2f_rba_addon_show">
	<?php	$x = apply_filters( 'mo2f_rba', "rba" );?>

	</div>
    </div>

        <form style="display:none;" id="mo2fa_loginform"
              action="<?php echo MO_HOST_NAME . '/moas/login'; ?>"
              target="_blank" method="post">
            <input type="email" name="username" value="<?php echo $mo2f_user_email; ?>"/>
            <input type="text" name="redirectUrl"
                   value="<?php echo MO_HOST_NAME . '/moas/initializepayment'; ?>"/>
            <input type="text" name="requestOrigin" id="requestOrigin"/>
        </form>
        <script>
            function mo2f_addonform(planType) {
                jQuery('#requestOrigin').val(planType);
                jQuery('#mo2fa_loginform').submit();
            }
        </script>
    <?php
}

function show_2_factor_addons( $current_user ) {
	global $Mo2fdbQueries;
	$mo2f_user_email     = $Mo2fdbQueries->get_user_detail( 'mo2f_user_email', $current_user->ID );
	$mo2f_active_sub_sub_tab = isset( $_GET['mo2f_sub_sub_tab'] ) ? $_GET['mo2f_sub_sub_tab'] : 'rba'; ?>
    <div class="mo2f_table_layout" style="border:0px;width: 80%;">
            <?php //echo mo2f_check_if_registered_with_miniorange( $current_user ); ?>
        <div class="mo2f_vertical-submenu" style="text-align:justify;">
            <a id="defaultOpen" class="nav-tab"  onclick="openPage('rba', this, '#4CAF50')" 
            ><?php echo __( 'Remember Device', 'miniorange-2-factor-authentication' ); ?></a>
            <a id="onclickOpen" class="nav-tab"  onclick="openPage('personal', this, '#4CAF50')" ><?php echo __( 'Customize login Popups', 'miniorange-2-factor-authentication' ); ?></a>
            <a id="onclick" class="nav-tab"  onclick="openPage('shortcode', this, '#4CAF50')" ><?php echo __( 'Shortcode', 'miniorange-2-factor-authentication' ); ?></a>
        </div>
        <br><br><br><br>
        <div class="mo2f_addon_spacing">
            <div id="rba" class="mo2f_addon">
              <?php mo2f_rba_description($mo2f_user_email); ?>
            </div>

            <div id="personal" class="mo2f_addon">
              <?php mo2f_personalization_description($mo2f_user_email);?>
              <br>
            </div>

            <div id="shortcode" class="mo2f_addon">
                <?php mo2f_shortcode_description($mo2f_user_email);?>
                <br>
            </div>
        </div>
    </div>
    <script>
        jQuery(document).ready(function()
        {
            sessionStorage.setItem("code", "rba");
        });
        function openPage(pageName,elmnt,color) {
          var i, tabcontent, tablinks;
          tabcontent = document.getElementsByClassName("mo2f_addon");
          for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
          }
          tablinks = document.getElementsByClassName("nav-tab");
          for (i = 0; i < tablinks.length; i++) {
            tablinks[i].style.backgroundColor = "";
          }
          document.getElementById(pageName).style.display = "block";
          elmnt.style.backgroundColor = color;
          sessionStorage.setItem("code", pageName);
        }

        // Get the element with id="defaultOpen" and click on it
        if(sessionStorage.getItem("code")=='personal')
            document.getElementById("onclickOpen").click();
        else if(sessionStorage.getItem("code")=='shortcode')
            document.getElementById("onclick").click();
        else
            document.getElementById("defaultOpen").click();
    </script>
	<?php
}

function mo2f_personalization_description($mo2f_user_email) {?>
	<div id="mo2f_custom_addon">
        <?php if ( get_option( 'mo2f_personalization_installed' ) ) { ?>
            <a href="<?php echo admin_url(); ?>plugins.php" id="mo2f_activate_custom_addon"
                       class="button button-primary button-large"
                       style="float:right; margin-top:2%;"><?php echo __( 'Activate Plugin', 'miniorange-2-factor-authentication' ); ?></a>
                <?php } ?>
        <?php if ( ! get_option( 'mo2f_personalization_purchased' ) ) { ?>  <a
                        onclick="mo2f_addonform('wp_2fa_addon_shortcode')" id="mo2f_purchase_custom_addon"
                        class="button button-primary button-large"
                        style="float:right;"><?php echo __( 'Purchase', 'miniorange-2-factor-authentication' ); ?></a>
                <?php } ?>
        <div id="mo2f_custom_addon_hide">
            <h3 id="toggle_personalization_description" class="mo2f_pointer">
   <?php echo __( 'Description', 'miniorange-2-factor-authentication' ); ?> </h3>
    <p id="custom_description" style="margin:2% 2% 2% 4%">
        <?php echo __( 'This Add-On helps you modify and redesign the login screen\'s UI, and various customizations in the plugin dashboard.
        Along with customizing the plugin Icon and name, you can also customize the email and sms templates you and your users receive during authentication.', 'miniorange-2-factor-authentication' ); ?>
    </p>
    <br>
    <div id="mo2f_hide_custom_content">
        <div class="mo2f_box">
            <h3><?php echo __( 'Customize Plugin Icon', 'miniorange-2-factor-authentication' ); ?></h3>
            <hr>
            <p>
                <?php echo __( 'With this feature, you can customize the plugin icon in the dashboard which is useful when you want your                     custom logo to be displayed to the users.', 'miniorange-2-factor-authentication' ); ?>
            </p>
            <br>
            <h3><?php echo __( 'Customize Plugin Name', 'miniorange-2-factor-authentication' ); ?></h3>
            <hr>
            <p>
                <?php echo __( 'With this feature, you can customize the name of the plugin in the dashboard.', 'miniorange-2-factor-authentication' ); ?>
            </p>

        </div>
        <br>
        <div class="mo2f_box">
            <h3><?php echo __( 'Customize UI of Login Pop up\'s', 'miniorange-2-factor-authentication' ); ?></h3>
            <hr>
            <p>
                <?php echo __( 'With this feature, you can customize the login pop-ups during two factor authentication according to the theme of                 your website.', 'miniorange-2-factor-authentication' ); ?>
            </p>
        </div>

        <br>
        <div class="mo2f_box">
            <h3><?php echo __( 'Custom Email and SMS Templates', 'miniorange-2-factor-authentication' ); ?></h3>
            <hr>

            <p><?php echo __( 'You can change the templates for Email and SMS which user receives during authentication.', 'miniorange-2-factor-authentication' ); ?></p>

        </div>
    </div></div>
	
	 <div id="mo2f_custom_addon_show"><?php $x = apply_filters( 'mo2f_custom', "custom"); ?></div> 
    </div> 
    
    <?php
}

function mo2f_shortcode_description($mo2f_user_email) {
    ?>
	<div id="mo2f_Shortcode_addon_hide">
        <?php if ( get_option( 'mo2f_shortcode_installed' ) ) { ?>
            <a href="<?php echo admin_url(); ?>plugins.php" id="mo2f_activate_shortcode_addon"
                           class="button button-primary button-large" style="float:right; margin-top:2%;"><?php echo __( 'Activate
                        Plugin', 'miniorange-2-factor-authentication' ); ?></a>
        <?php } if ( ! get_option( 'mo2f_shortcode_purchased' ) ) { ?>
                   <a onclick="mo2f_addonform('wp_2fa_addon_personalization')" id="mo2f_purchase_shortcode_addon"
                           class="button button-primary button-large"
                           style="float:right;"><?php echo __( 'Purchase', 'miniorange-2-factor-authentication' ); ?></a>
        <?php } ?>
        <h3 id="toggle_shortcode_description" class="mo2f_pointer">
			<div id="shortcode" class="description">
        
                        <?php echo __( 'Description ', 'miniorange-2-factor-authentication' ); ?></h3>
                    <p id="shortcode_description" style="margin:2% 2% 2% 4%">
        <?php echo __( 'A shortcode is a WordPress-specific code that lets you do things with very little effort. Shortcodes can embed
        ugly code in just one line. You can use these shortcodes on any custom page. Just include the shortcode on your page and boom!', 'miniorange-2-factor-authentication' ); ?>
    </p>
    <br>

		<div id="mo2f_hide_shortcode_content" class="mo2f_box">

			<h3><?php echo __( 'List of Shortcodes', 'miniorange-2-factor-authentication' ); ?>:</h3>
			<hr>

			<ol style="margin-left:2%">
				<li><b><?php echo __( 'Enable Two Factor: ', 'miniorange-2-factor-authentication' ); ?></b> <?php echo __( 'This shortcode provides
								an option to turn on/off 2-factor by user.', 'miniorange-2-factor-authentication' ); ?></li>
				<li>
					<b><?php echo __( 'Enable Reconfiguration: ', 'miniorange-2-factor-authentication' ); ?></b> <?php echo __( 'This shortcode provides an option to configure the Google Authenticator and Security Questions by user.', 'miniorange-2-factor-authentication' ); ?>
				</li>
				<li>
					<b><?php echo __( 'Enable Remember Device: ', 'miniorange-2-factor-authentication' ); ?></b> <?php echo __( ' This shortcode provides
								\'Enable Remember Device\' from your custom login form.', 'miniorange-2-factor-authentication' ); ?>
				</li>
			</ol>
		
		</div>
		
		<div id="mo2f_Shortcode_addon_show"><?php $x = apply_filters( 'mo2f_shortcode', "shortcode" ); ?></div>
        </div>
   
    <br>
    </div>
    
</div>
<form style="display:none;" id="mo2fa_loginform"
              action="<?php echo MO_HOST_NAME . '/moas/login'; ?>"
              target="_blank" method="post">
            <input type="email" name="username" value="<?php echo $mo2f_user_email; ?>"/>
            <input type="text" name="redirectUrl"
                   value="<?php echo MO_HOST_NAME . '/moas/initializepayment'; ?>"/>
            <input type="text" name="requestOrigin" id="requestOrigin"/>
        </form>
        <script>
            function mo2f_addonform(planType) {
                jQuery('#requestOrigin').val(planType);
                jQuery('#mo2fa_loginform').submit();
            }</script>
    <?php
}

function show_rba_content() {

	$paid_rba = 1;
	$str      = "rba";
	if ( $paid_rba ) {
		$x = apply_filters( 'mo2f_rba', $str );
	}
	
}

function show_shortcode_content() {

	$paid_shortcode = 1;
	$str            = "shortcode";
	if ( $paid_shortcode ) {
		$x = apply_filters( 'mo2f_shortcode', $str );
		
	}
}

function show_custom_content() {

	$paid_custom = 1;
	$str         = "custom";
	if ( $paid_custom ) {
		$x = apply_filters( 'mo2f_custom', $str );
	}
	?>


	<?php
}

function show_2_factor_proxy_setup( $user ) {
	global $Mo2fdbQueries;
	?>
    <div class="mo2f_proxy_setup">
        <h3>Proxy Settings</h3>
        <hr>
        <br>
        <div style="float:right;">
            <form name="f" method="post" action="" id="mo2f_disable_proxy_setup_form">
                <input type="hidden" name="option" value="mo2f_disable_proxy_setup_option"/>
				<input type="hidden" name="mo2f_disable_proxy_setup_option_nonce"
						value="<?php echo wp_create_nonce( "mo2f-disable-proxy-setup-option-nonce" ) ?>"/>

                <input type="submit" name="submit" style="float:right"
                       value="<?php echo mo2f_lt( 'Reset Proxy Settings' ); ?>"
                       class="button button-primary button-large"

					<?php if ( $Mo2fdbQueries->get_user_detail( 'mo_2factor_user_registration_status', $user->ID ) != 'MO_2_FACTOR_PLUGIN_SETTINGS' || ! get_option( 'mo2f_proxy_host' ) ) {
						echo 'disabled';
					 } ?>  />

            </form>
        </div>
        <br><br>
        <form name="f" method="post" action="">
            <input type="hidden" name="option" value="mo2f_save_proxy_settings"/>
			<input type="hidden" name="mo2f_save_proxy_settings_nonce"
                   value="<?php echo wp_create_nonce( "mo2f-save-proxy-settings-nonce" ) ?>"/>
            <table class="mo2f_settings_table">
                <tr>

                    <td style="width:30%"><b><span class="impt">*</span><?php echo mo2f_lt( 'Proxy Host Name: ' ); ?>
                        </b></td>
                    <td style="width:70%"><input class="mo2f_table_textbox" type="text" name="proxyHost" required
                                                 value="<?php echo get_option( 'mo2f_proxy_host' ); ?>"/></td>
                </tr>
                <tr>

                    <td style="width:30%"><b><span class="impt">*</span><?php echo mo2f_lt( 'Port Number: ' ); ?></b>
                    </td>
                    <td style="width:70%"><input class="mo2f_table_textbox" type="number" name="portNumber" required
                                                 value="<?php echo get_option( 'mo2f_port_number' ); ?>"/></td>
                </tr>
                <tr>

                    <td style="width:30%"><b><?php echo mo2f_lt( 'Username: ' ); ?></b></td>
                    <td style="width:70%"><input class="mo2f_table_textbox" type="text" name="proxyUsername"
                                                 value="<?php echo get_option( 'mo2f_proxy_username' ); ?>"/></td>
                </tr>
                <tr>

                    <td style="width:30%"><b><?php echo mo2f_lt( 'Password: ' ); ?></b></td>
                    <td style="width:70%"><input class="mo2f_table_textbox" type="password" name="proxyPass"
                                                 value="<?php echo get_option( 'mo2f_proxy_password' ); ?>"/></td>
                </tr>

                <tr>

                    <td>&nbsp;</td>
                    <td><input type="submit" name="submit" style="float:right"
                               value="<?php echo mo2f_lt( 'Save Settings' ); ?>"
                               class="button button-primary button-large"
							<?php if ( $Mo2fdbQueries->get_user_detail( 'mo_2factor_user_registration_status', $user->ID ) != 'MO_2_FACTOR_PLUGIN_SETTINGS' ) {
								echo 'disabled';
							} ?> /></td>
                </tr>

            </table>
    </div>
    </form>
<?php }
function show_2_factor_custom_form($user){?>
	<div style="margin:2% 2% 0% 2%;">
		<span style="font-weight:bold;font-size:18px;">Custom Login Forms</span>
		<p>We support most of the login forms present on the wordpress. And our plugin is tested with almost all the forms like Woocommerce, Ultimate Member, Restrict Content Pro and so on.</p>
		<ul>
			<li><b>Woocommerce</b></li>
			<li><b>Ultimate Member</b></li>
			<li><b>Restrict Content Pro</b></li>
			<li><b>My Theme Login</b></li>
			<li><b>User Registration</b></li>
			<li><b>Custom Login Page Customizer | LoginPress</b></li>
			<li><b>Admin Custom Login</b></li>
			<li><b>RegistrationMagic – Custom Registration Forms and User Login</b></li>
		</ul>
		<p>And many more which are not mentioned here.</p>
		
		<p style="font-size:15px">If there is any custom login form where Two Factor is not initiated you can get let us know so that we can add support for it. You can reach us through our <a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_support"><?php echo mo2f_lt( 'Support' ) ; ?></a>.</p>
	</div>


<?php
}
function show_2_factor_login_settings( $user ) {
	global $Mo2fdbQueries;
	$roles = get_editable_roles();

	$mo_2factor_user_registration_status = $Mo2fdbQueries->get_user_detail( 'mo_2factor_user_registration_status', $user->ID );
	?>


	<?php if ( get_option( 'mo2f_is_NC' ) ) { ?>
    <div class="mo2f_advanced_options_EC" style="width: 85%;border: 0px;">
			<?php echo get_standard_premium_options( $user ); ?>
        </div>
	<?php } else {

		$mo2f_active_tab = '2factor_setup';
		?>

        <div class="mo2f_advanced_options_EC" style="width: 85%;border: 0px;">

            <div id="mo2f_login_options">
                <a href="#standard_premium_options" style="float:right">Show Standard/Premium
                    Features</a></h3>

                <form name="f" id="login_settings_form" method="post" action="">
                    <input type="hidden" name="option" value="mo_auth_login_settings_save"/>
					<input type="hidden" name="mo_auth_login_settings_save_nonce"
						value="<?php echo wp_create_nonce( "mo-auth-login-settings-save-nonce" ) ?>"/>
                    <div class="row">
                        <h3 style="padding:10px;"><?php echo mo2f_lt( 'Select Login Screen Options' ); ?>

                    </div>
                    <hr>
                    <br>


                    <div style="margin-left: 2%;">
                        <input type="radio" name="mo2f_login_option" value="1"
							<?php checked( get_option( 'mo2f_login_option' ) );
							if ( $mo_2factor_user_registration_status == 'MO_2_FACTOR_PLUGIN_SETTINGS' ) {
							} else {
								echo 'disabled';
							} ?> />
						<?php echo mo2f_lt( 'Login with password + 2nd Factor ' ); ?>
                        <i>(<?php echo mo2f_lt( 'Default & Recommended' ); ?>)&nbsp;&nbsp;</i>

                        <br><br>

                        <div style="margin-left:6%;">
                            <input type="checkbox" id="mo2f_remember_device" name="mo2f_remember_device"
                                   value="1" <?php checked( get_option( 'mo2f_remember_device' ) == 1 );
							if ( $mo_2factor_user_registration_status == 'MO_2_FACTOR_PLUGIN_SETTINGS' ) {
							} else {
								echo 'disabled';
							} ?> />Enable
                            '<b><?php echo mo2f_lt( 'Remember device' ); ?></b>' <?php echo mo2f_lt( 'option ' ); ?><br>

                            <div class="mo2f_advanced_options_note"><p style="padding:5px;">
                                    <i><?php echo mo2f_lt( ' Checking this option will display an option ' ); ?>
                                        '<b><?php echo mo2f_lt( 'Remember this device' ); ?></b>'<?php echo mo2f_lt( 'on 2nd factor screen. In the next login from the same device, user will bypass 2nd factor, i.e. user will be logged in through username + password only.' ); ?>
                                    </i></p></div>
                        </div>

                        <br>

                        <input type="radio" name="mo2f_login_option" value="0"
							<?php checked( ! get_option( 'mo2f_login_option' ) );
							if ( $mo_2factor_user_registration_status == 'MO_2_FACTOR_PLUGIN_SETTINGS' ) {
							} else {
								echo 'disabled';
							} ?> />
						<?php echo mo2f_lt( 'Login with 2nd Factor only ' ); ?>
                        <i>(<?php echo mo2f_lt( 'No password required.' ); ?>)</i> &nbsp;<a class="btn btn-link"
                                                                                            data-toggle="collapse"
                                                                                            id="showpreview1"
                                                                                            href="#preview9"
                                                                                            aria-expanded="false"><?php echo mo2f_lt( 'See preview' ); ?></a>
                        <br>
                        <div class="mo2f_collapse" id="preview9" style="height:300px;">
                            <center><br>
                                <img style="height:300px;"
                                     src="https://login.xecurify.com/moas/images/help/login-help-1.png">
                            </center>
                        </div>
                        <div class="mo2f_advanced_options_note"><p style="padding:5px;">
                                <i><?php echo mo2f_lt( 'Checking this option will add login with your phone button below default login form. Click above link to see the preview.' ); ?></i>
                            </p></div>
                        <div id="loginphonediv" hidden><br>
                            <input type="checkbox" id="mo2f_login_with_username_and_2factor"
                                   name="mo2f_login_with_username_and_2factor"
                                   value="1" <?php checked( get_option( 'mo2f_enable_login_with_2nd_factor' ) == 1 );
							if ( $mo_2factor_user_registration_status == 'MO_2_FACTOR_PLUGIN_SETTINGS' ) {
							} else {
								echo 'disabled';
							} ?> />
							<?php echo mo2f_lt( '	I want to hide default login form.' ); ?> &nbsp;<a
                                    class="btn btn-link"
                                    data-toggle="collapse"
                                    href="#preview8"
                                    aria-expanded="false"><?php echo mo2f_lt( 'See preview' ); ?></a>
                            <br>
                            <div class="mo2f_collapse" id="preview8" style="height:300px;">
                                <center><br>
                                    <img style="height:300px;"
                                         src="https://login.xecurify.com/moas/images/help/login-help-3.png">
                                </center>
                            </div>
                            <br>
                            <div class="mo2f_advanced_options_note"><p style="padding:5px;">
                                    <i><?php echo mo2f_lt( 'Checking this option will hide default login form and just show login with your phone. Click above link to see the preview.' ); ?></i>
                                </p></div>
                        </div>
                        <br>
                    </div>
                    <div>
                        <h3 style="padding:10px;"><?php echo mo2f_lt( 'Backup Methods' ); ?></h3></div>
                    <hr>
                    <br>
                    <div style="margin-left: 2%">
                        <input type="checkbox" id="mo2f_forgotphone" name="mo2f_forgotphone"
                               value="1" <?php checked( get_option( 'mo2f_enable_forgotphone' ) == 1 );
						if ( $mo_2factor_user_registration_status == 'MO_2_FACTOR_PLUGIN_SETTINGS' ) {
						} else {
							echo 'disabled';
						} ?> />
						<?php echo mo2f_lt( 'Enable Forgot Phone.' ); ?>

                        <div class="mo2f_advanced_options_note"><p style="padding:5px;">
                                <i><?php echo mo2f_lt( 'This option will provide you an alternate way of logging in to your site in case you are unable to login with your primary authentication method.' ); ?></i>
                            </p></div>
                        <br>

                    </div>
                    <div>
                        <h3 style="padding:10px;">XML-RPC <?php echo mo2f_lt( 'Settings' ); ?></h3></div>
                    <hr>
                    <br>
                    <div style="margin-left: 2%">
                        <input type="checkbox" id="mo2f_enable_xmlrpc" name="mo2f_enable_xmlrpc"
                               value="1" <?php checked( get_option( 'mo2f_enable_xmlrpc' ) == 1 );
						if ( $mo_2factor_user_registration_status == 'MO_2_FACTOR_PLUGIN_SETTINGS' ) {
						} else {
							echo 'disabled';
						} ?> />
						<?php echo mo2f_lt( 'Enable XML-RPC Login.' ); ?>
                        <div class="mo2f_advanced_options_note"><p style="padding:5px;">
                                <i><?php echo mo2f_lt( 'Enabling this option will decrease your overall login security. Users will be able to login through external applications which support XML-RPC without authenticating from miniOrange. ' ); ?>
                                    <b><?php echo mo2f_lt( 'Please keep it unchecked.' ); ?></b></i></p></div>

                    </div>

                    <br><br>
                    <div style="float:right;padding:10px;">
                        <input type="submit" name="submit" value="<?php echo mo2f_lt( 'Save Settings' ); ?>"
                               class="button button-primary button-large" <?php
						if ( $mo_2factor_user_registration_status == 'MO_2_FACTOR_PLUGIN_SETTINGS' ) {
						} else {
							echo 'disabled';
						} ?> />
                    </div>
                    <br></form>
                <br>
                <br>
                <hr>
            </div>

			<?php echo get_standard_premium_options( $user ); ?>
        </div>

		<?php
	} ?>

    <script>

        if (jQuery("input[name=mo2f_login_option]:radio:checked").val() == 0) {
            jQuery('#loginphonediv').show();
        }
        jQuery("input[name=mo2f_login_option]:radio").change(function () {
            if (this.value == 1) {
                jQuery('#loginphonediv').hide();
            } else {
                jQuery('#loginphonediv').show();
            }
        });


        function show_backup_options() {
            jQuery("#backup_options").slideToggle(700);
            jQuery("#login_options").hide();
            jQuery("#customizations").hide();
            jQuery("#customizations_prem").hide();
            jQuery("#backup_options_prem").hide();
            jQuery("#inline_registration_options").hide();
        }

        function show_customizations() {
            jQuery("#login_options").hide();
            jQuery("#inline_registration_options").hide();
            jQuery("#backup_options").hide();
            jQuery("#customizations_prem").hide();
            jQuery("#backup_options_prem").hide();
            jQuery("#customizations").slideToggle(700);

        }

        jQuery("#backup_options_prem").hide();

        function show_backup_options_prem() {
            jQuery("#backup_options_prem").slideToggle(700);
            jQuery("#login_options").hide();
            jQuery("#customizations").hide();
            jQuery("#customizations_prem").hide();
            jQuery("#inline_registration_options").hide();
            jQuery("#backup_options").hide();
        }

        jQuery("#login_options").hide();

        function show_login_options() {
            jQuery("#inline_registration_options").hide();
            jQuery("#customizations").hide();
            jQuery("#backup_options").hide();
            jQuery("#backup_options_prem").hide();
            jQuery("#customizations_prem").hide();
            jQuery("#login_options").slideToggle(700);
        }

        jQuery("#inline_registration_options").hide();

        function show_inline_registration_options() {
            jQuery("#login_options").hide();
            jQuery("#customizations").hide();
            jQuery("#backup_options").hide();
            jQuery("#backup_options_prem").hide();
            jQuery("#customizations_prem").hide();
            jQuery("#inline_registration_options").slideToggle(700);

        }

        jQuery("#customizations_prem").hide();

        function show_customizations_prem() {
            jQuery("#inline_registration_options").hide();
            jQuery("#login_options").hide();
            jQuery("#customizations").hide();
            jQuery("#backup_options").hide();
            jQuery("#backup_options_prem").hide();
            jQuery("#customizations_prem").slideToggle(700);

        }

        function showLoginOptions() {
            jQuery("#mo2f_login_options").show();
        }

        function showLoginOptions() {
            jQuery("#mo2f_login_options").show();
        }


    </script>


	<?php
}

function get_standard_premium_options( $user ) {
	$is_NC = get_option( 'mo2f_is_NC' );

	?>
	<div >
		<div id="standard_premium_options" style="text-align: center;">
			<p style="font-size:22px;color:darkorange;padding:10px;"><?php echo mo2f_lt( 'Features in the Standard Plan' ); ?></p>

		</div>

		<hr>
		<?php if ( $is_NC ) { ?>
			<div>
				<a class="mo2f_view_backup_options" onclick="show_backup_options()">
					<img src="<?php echo plugins_url( 'includes/images/right-arrow.png"', __FILE__ ); ?>"
						 class="mo2f_advanced_options_images"/>

					<p class="mo2f_heading_style"><?php echo mo2f_lt( 'Backup Options' ); ?></p>
				</a>

			</div>
			<div id="backup_options" style="margin-left: 5%;">

				<div class="mo2f_advanced_options_note"><p style="padding:5px;">
						<i><?php echo mo2f_lt( 'Use these backup options to login to your site in case your 
								phone is lost / not accessible or if you are not able to login using your primary 
								authentication method.' ); ?></i></p></div>

				<ol class="mo2f_ol">
					<li><?php echo mo2f_lt( 'KBA (Security Questions)' ); ?></li>
				</ol>

			</div>
		<?php } ?>

		<div>
			<a class="mo2f_view_customizations" onclick="show_customizations()">
				<img src="<?php echo plugins_url( 'includes/images/right-arrow.png"', __FILE__ ); ?>"
					 class="mo2f_advanced_options_images"/>

				<p class="mo2f_heading_style"><?php echo mo2f_lt( 'Customizations' ); ?></p>
			</a>
		</div>


		<div id="customizations" style="margin-left: 5%;">

			<p style="font-size:15px;font-weight:bold">1. <?php echo mo2f_lt( 'Login Screen Options' ); ?></p>
			<div>
				<ul style="margin-left:4%" class="mo2f_ol">
					<li><?php echo mo2f_lt( 'Login with Wordpress username/password and 2nd Factor' ); ?> <a
								class="btn btn-link" data-toggle="collapse" id="showpreview1" href="#preview7"
								aria-expanded="false">[ <?php echo mo2f_lt( 'See Preview' ); ?>
							]</a>
						<div class="mo2f_collapse" id="preview7" style="height:300px;">
							<center><br>
								<img style="height:300px;"
									 src="https://login.xecurify.com/moas/images/help/login-help-1.png">
							</center>

						</div>
					</li>
					<li><?php echo mo2f_lt( 'Login with Wordpress username and 2nd Factor only' ); ?> <a
								class="btn btn-link" data-toggle="collapse" id="showpreview2" href="#preview6"
								aria-expanded="false">[ <?php echo mo2f_lt( 'See Preview' ); ?>
							]</a>
						<br>
						<div class="mo2f_collapse" id="preview6" style="height:300px;">
							<center><br>
								<img style="height:300px;"
									 src="https://login.xecurify.com/moas/images/help/login-help-3.png">
							</center>
						</div>
						<br>
					</li>
				</ul>


			</div>
			<br>
			<p style="font-size:15px;font-weight:bold">2. <?php echo mo2f_lt( 'Custom Redirect URLs' ); ?></p>
			<p style="margin-left:4%"><?php echo mo2f_lt( 'Enable Custom Relay state URL\'s (based on user roles in Wordpress) to which the users
				will get redirected to, after the 2-factor authentication' ); ?>'.</p>


			<br>
			<p style="font-size:15px;font-weight:bold">3. <?php echo mo2f_lt( 'Custom Security Questions (KBA)' ); ?></p>
			<div id="mo2f_customKBAQuestions1">
				<p style="margin-left:4%"><?php echo mo2f_lt( 'Add up to 16 Custom Security Questions for Knowledge based authentication (KBA).
					You also have the option to select how many standard and custom questions should be shown to the
					users' ); ?>.</p>

			</div>
			<br>
			<p style="font-size:15px;font-weight:bold">
				4. <?php echo mo2f_lt( 'Custom account name in Google Authenticator App' ); ?></p>
			<div id="mo2f_editGoogleAuthenticatorAccountName1">

				<p style="margin-left:4%"><?php echo mo2f_lt( 'Customize the Account name in the Google Authenticator App' ); ?>
					.</p>

			</div>
			<br>
		</div>
		<div id="standard_premium_options" style="text-align: center;">
			<p style="font-size:22px;color:darkorange;padding:10px;"><?php echo mo2f_lt( 'Features in the Premium Plan' ); ?></p>

		</div>
		<hr>
		<div>
			<a class="mo2f_view_customizations_prem" onclick="show_customizations_prem()">
				<img src="<?php echo plugins_url( 'includes/images/right-arrow.png"', __FILE__ ); ?>"
					 class="mo2f_advanced_options_images"/>

				<p class="mo2f_heading_style"><?php echo mo2f_lt( 'Customizations' ); ?></p>
			</a>
		</div>


		<div id="customizations_prem" style="margin-left: 5%;">

			<p style="font-size:15px;font-weight:bold">1. <?php echo mo2f_lt( 'Login Screen Options' ); ?></p>
			<div>
				<ul style="margin-left:4%" class="mo2f_ol">
					<li><?php echo mo2f_lt( 'Login with Wordpress username/password and 2nd Factor' ); ?> <a
								class="btn btn-link" data-toggle="collapse" id="showpreview1" href="#preview3"
								aria-expanded="false">[ <?php echo mo2f_lt( 'See Preview' ); ?>
							]</a>
						<div class="mo2f_collapse" id="preview3" style="height:300px;">
							<center><br>
								<img style="height:300px;"
									 src="https://login.xecurify.com/moas/images/help/login-help-1.png">
							</center>

						</div>
						<br></li>
					<li><?php echo mo2f_lt( 'Login with Wordpress username and 2nd Factor only' ); ?> <a
								class="btn btn-link" data-toggle="collapse" id="showpreview2" href="#preview4"
								aria-expanded="false">[ <?php echo mo2f_lt( 'See Preview' ); ?>
							]</a>
						<br>
						<div class="mo2f_collapse" id="preview4" style="height:300px;">
							<center><br>
								<img style="height:300px;"
									 src="https://login.xecurify.com/moas/images/help/login-help-3.png">
							</center>
						</div>
						<br>
					</li>
				</ul>


			</div>
			<br>
			<p style="font-size:15px;font-weight:bold">2. <?php echo mo2f_lt( 'Custom Redirect URLs' ); ?></p>
			<p style="margin-left:4%"><?php echo mo2f_lt( 'Enable Custom Relay state URL\'s (based on user roles in Wordpress) to which the users
				will get redirected to, after the 2-factor authentication' ); ?>'.</p>


			<br>
			<p style="font-size:15px;font-weight:bold">3. <?php echo mo2f_lt( 'Custom Security Questions (KBA)' ); ?></p>
			<div id="mo2f_customKBAQuestions1">
				<p style="margin-left:4%"><?php echo mo2f_lt( 'Add up to 16 Custom Security Questions for Knowledge based authentication (KBA).
					You also have the option to select how many standard and custom questions should be shown to the
					users' ); ?>.</p>

			</div>
			<br>
			<p style="font-size:15px;font-weight:bold">
				4. <?php echo mo2f_lt( 'Custom account name in Google Authenticator App' ); ?></p>
			<div id="mo2f_editGoogleAuthenticatorAccountName1">

				<p style="margin-left:4%"><?php echo mo2f_lt( 'Customize the Account name in the Google Authenticator App' ); ?>
					.</p>

			</div>
			<br>
		</div>
		<div>
			<a class="mo2f_view_backup_options_prem" onclick="show_backup_options_prem()">
				<img src="<?php echo plugins_url( 'includes/images/right-arrow.png"', __FILE__ ); ?>"
					 class="mo2f_advanced_options_images"/>

				<p class="mo2f_heading_style"><?php echo mo2f_lt( 'Backup Options' ); ?></p>
			</a>

		</div>
		<div id="backup_options_prem" style="margin-left: 5%;">

			<div class="mo2f_advanced_options_note"><p style="padding:5px;">
					<i><?php echo mo2f_lt( 'Use these backup options to login to your site in case your 
								phone is lost / not accessible or if you are not able to login using your primary 
								authentication method.' ); ?></i></p></div>

			<ol class="mo2f_ol">
				<li><?php echo mo2f_lt( 'KBA (Security Questions)' ); ?></li>
				<li><?php echo mo2f_lt( 'OTP Over Email' ); ?></li>
				<li><?php echo mo2f_lt( 'Backup Codes' ); ?></li>
			</ol>

		</div>


		<div>
			<a class="mo2f_view_inline_registration_options" onclick="show_inline_registration_options()">
				<img src="<?php echo plugins_url( 'includes/images/right-arrow.png"', __FILE__ ); ?>"
					 class="mo2f_advanced_options_images"/>
				<p class="mo2f_heading_style"><?php echo mo2f_lt( 'Inline Registration Options' ); ?></p>
			</a>
		</div>


		<div id="inline_registration_options" style="margin-left: 5%;">

			<div class="mo2f_advanced_options_note"><p style="padding:5px;">
					<i><?php echo mo2f_lt( 'Inline Registration is the registration process the users go through the first time they
								setup 2FA.' ); ?><br>
						<?php echo mo2f_lt( 'If Inline Registration is enabled by the admin for the users, the next time
								the users login to the website, they will be prompted to set up the 2FA of their choice by
								creating an account with miniOrange.' ); ?>


					</i></p></div>


			<p style="font-size:15px;font-weight:bold"><?php echo mo2f_lt( 'Features' ) ?>:</p>
			<ol style="margin-left: 5%" class="mo2f_ol">
				<li><?php echo mo2f_lt( 'Invoke 2FA Registration & Setup for Users during first-time login (Inline Registration)' ); ?>
				</li>

				<li><?php echo mo2f_lt( 'Verify Email address of User during Inline Registration' ); ?></li>
				<li><?php echo mo2f_lt( 'Remove Knowledge Based Authentication(KBA) setup during inline registration' ); ?></li>
				<li><?php echo mo2f_lt( 'Enable 2FA for specific Roles' ); ?></li>
				<li><?php echo mo2f_lt( 'Enable specific 2FA methods to Users during Inline Registration' ); ?>:
					<ul style="padding-top:10px;">
						<li style="margin-left: 5%;">
							1. <?php echo mo2f_lt( 'Show specific 2FA methods to All Users' ); ?></li>
						<li style="margin-left: 5%;">
							2. <?php echo mo2f_lt( 'Show specific 2FA methods to Users based on their roles' ); ?></li>
					</ul>
				</li>
			</ol>
		</div>


		<div>
			<a class="mo2f_view_login_options" onclick="show_login_options()">
				<img src="<?php echo plugins_url( 'includes/images/right-arrow.png"', __FILE__ ); ?>"
					 class="mo2f_advanced_options_images"/>
				<p class="mo2f_heading_style"><?php echo mo2f_lt( 'User Login Options' ); ?></p>
			</a>
		</div>

		<div id="login_options" style="margin-left: 5%;">

			<div class="mo2f_advanced_options_note"><p style="padding:5px;">
					<i><?php echo mo2f_lt( 'These are the options customizable for your users.' ); ?>


					</i></p></div>

			<ol style="margin-left: 5%" class="mo2f_ol">
				<li><?php echo mo2f_lt( 'Enable 2FA during login for specific users on your site' ); ?>.</li>

				<li><?php echo mo2f_lt( 'Enable login from external apps that support XML-RPC. (eg. Wordpress App)' ); ?>
					<br>
					<div class="mo2f_advanced_options_note"><p style="padding:5px;">
							<i><?php echo mo2f_lt( 'Use the Password generated in the 2FA plugin to login to your Wordpress Site from
										any application that supports XML-RPC.' ); ?>


							</i></p></div>


				<li><?php echo mo2f_lt( 'Enable KBA (Security Questions) as 2FA for Users logging in to the site from mobile
				phones.' ); ?>
				</li>


			</ol>
			<br>
		</div>
	</div>
	<?php
}

function mo2f_show_verify_password_page() {
	$mo2f_active_tab  = isset( $_GET['mo2f_tab'] ) ? $_GET['mo2f_tab'] : '';
	$mo2f_active_sub_tab  = isset( $_GET['mo2f_sub_tab'] ) ? $_GET['mo2f_sub_tab'] : '';//mo2f_sub_tab=2factor_setup
	$is_registration = ($mo2f_active_tab =='2factor_setup'||$mo2f_active_sub_tab=='2factor_setup') ? true : false;
	
	// $is_registration = $is_registration?(($mo2f_active_sub_tab=='2factor_setup')? true :false):false;
	?>
    <!--Verify password with miniOrange-->
    <form name="f" method="post" action="">
        <input type="hidden" name="option" value="mo_auth_verify_customer"/>
		<input type="hidden" name="miniorange_verify_customer_nonce"
                   value="<?php echo wp_create_nonce( "miniorange-verify-customer-nonce" ) ?>"/>
				   
        <div <?php if($is_registration) { ?>class="mo2f_proxy_setup" <?php } ?>>
	        <?php if($is_registration) { ?>
                <h2><?php echo mo2f_lt( 'Sign In to your miniOrange Account' ); ?></h2><hr>
            <?php } ?>
            <div id="panel1">
                <p><a style="float:right;font-weight:bold; color:orange" target="_blank"
                            href="https://login.xecurify.com/moas/idp/resetpassword"><?php echo mo2f_lt( 'FORGOT PASSWORD?' ); ?></a>
                </p>
                <br>
                <table class="mo2f_settings_table">
                    <tr>
                        <td><b><font color="#FF0000">*</font><?php echo mo2f_lt( 'Email:' ); ?></b></td>
                        <td><input class="mo2f_table_textbox" type="email" name="email" id="email" required
                                   value="<?php echo get_option( 'mo2f_email' ); ?>"/></td>
                    </tr>
                    <tr>
                        <td><b><font color="#FF0000">*</font><?php echo mo2f_lt( 'Password:' ); ?></b></td>
                        <td><input class="mo2f_table_textbox" type="password" name="password" required/></td>
                    </tr>

                    <tr>
                        <td colspan="2">&nbsp;</td>
                    </tr>

                    <tr>
                        <td>&nbsp;</td>
                        <td>
                            <input type="button" name="mo2f_goback" id="mo2f_go_back"
                                   value="<?php echo mo2f_lt( 'Back' ); ?>" class="button button-primary button-large"/>

                            <input type="submit" name="submit" value="<?php echo mo2f_lt( 'Submit' ); ?>"
                                   class="button button-primary button-large"/></td>

                    </tr>

                </table>

            </div>
            <br><br>
        </div>
    </form>
    <form name="f" method="post" action="" id="gobackform">
        <input type="hidden" name="option" value="mo_2factor_gobackto_registration_page"/>
		<input type="hidden" name="mo_2factor_gobackto_registration_page_nonce"
						value="<?php echo wp_create_nonce( "mo-2factor-gobackto-registration-page-nonce" ) ?>"/>
    </form>
	
    <script>
        jQuery('#mo2f_go_back').click(function () {
            jQuery('#gobackform').submit();
        });

    </script>

<?php }
?>
