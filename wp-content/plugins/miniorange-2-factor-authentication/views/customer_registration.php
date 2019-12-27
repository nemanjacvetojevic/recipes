<?php 

function display_customer_registration_forms($user){

	global $Mo2fdbQueries;
	$mo2f_current_registration_status = $Mo2fdbQueries->get_user_detail( 'mo_2factor_user_registration_status', $user->ID);
	$mo2f_message              = get_option( 'mo2f_message' );
	?>

	<div id="smsAlertModal" class="mo2f_modal mo2f_modal_inner fade" role="dialog" data-backdrop="static" data-keyboard="false" >
		<div class="mo2f_modal-dialog" style="margin-left:30%;">
			<!-- Modal content-->
			<div class="login mo_customer_validation-modal-content" style="width:660px !important; margin-top:0%;">
				<div class="mo2f_modal-header">
					<button type="button" id="mo2f_registration_closed" class="mo2f_close" data-dismiss="modal">&times;</button>
					<h2 class="mo2f_modal-title">You are just one step away from setting up 2FA.</h2>
				</div>
				<div class="mo2f_modal-body">
					<span style="color:green;cursor: pointer;float:right;" onclick="show_content();">Why Register with miniOrange?</span><br>
				<div id="mo2f_register" style="background-color:#f1f1f1;padding: 1px 4px 1px 14px;" hidden>
				<p>miniOrange Two Factor plugin uses highly secure miniOrange APIs to communicate with the plugin. To keep this communication secure, we ask you to register and assign you API keys specific to your account.
				This way your account and users can be only accessed by API keys assigned to you. Also, you can use the same account on multiple applications and your users do not have to maintain multiple accounts or 2-factors.</p>
                </div>
					<?php if ( $mo2f_message ) { ?>
                    <div style="padding:5px;">
                        <div class="alert alert-info" style="margin-bottom:0px;padding:3px;">
                            <p style="font-size:15px;margin-left: 2%;"><?php echo $mo2f_message; ?></p>
                        </div>
                    </div>
					<?php }
					if(in_array($mo2f_current_registration_status, array("REGISTRATION_STARTED", "MO_2_FACTOR_OTP_DELIVERED_SUCCESS", "MO_2_FACTOR_OTP_DELIVERED_FAILURE", "MO_2_FACTOR_VERIFY_CUSTOMER"))){
                    mo2f_show_registration_screen($user); }
					?>
				</div>
			</div>
		</div>
	</div>

    <form name="f" method="post" action="" class="mo2f_registration_closed_form">
		<input type="hidden" name="mo2f_registration_closed_nonce"
						value="<?php echo wp_create_nonce( "mo2f-registration-closed-nonce" ) ?>"/>
        <input type="hidden" name="option" value="mo2f_registration_closed"/>
    </form>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js"></script>

<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/js/bootstrap.min.js"></script>

    <script>
		function show_content() {
            jQuery('#mo2f_register').slideToggle();
        }
        jQuery(function () {
            jQuery('#smsAlertModal').modal();
        });

        jQuery('#mo2f_registration_closed').click(function () {
            jQuery('.mo2f_registration_closed_form').submit();
        });


	</script>

	<?php
}
?>