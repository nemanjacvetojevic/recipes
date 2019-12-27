<?php

function mo2f_display_test_2fa_notification( $user ) {
	global $Mo2fdbQueries;
	$mo2f_configured_2FA_method = $Mo2fdbQueries->get_user_detail( 'mo2f_configured_2FA_method', $user->ID );

?>
    <!DOCTYPE html>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <div id="twoFAtestAlertModal" class="mo2f_modal mo2f_modal_inner fade" role="dialog">
        <div class="mo2f_modal-dialog">
            <!-- Modal content-->
            <div class="login mo_customer_validation-modal-content" style="width:660px !important;">
                <div class="mo2f_modal-header">
                    <button type="button" class="mo2f_close" data-dismiss="modal">&times;</button>
                    <h2 class="mo2f_modal-title" style="font-family: Roboto,Helvetica,Arial,sans-serif;">2FA Setup Successful.</h2>
                </div>
                <div class="mo2f_modal-body">
                    <p style="font-size:14px;"><b><?php echo $mo2f_configured_2FA_method; ?> </b> has been set as your 2-factor authentication method.
                        <br><br>Please test the login flow once with 2nd factor in another browser or in an incognito window of the
                        same browser to ensure you don't get locked out of your site.</p>
                </div>
                <div class="mo2f_modal-footer">
                    <button type="button" class="button button-primary" data-dismiss="modal">Got it!</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        jQuery(function () {
            jQuery('#twoFAtestAlertModal').modal('toggle');
        });
    </script>

<?php }
?>