<?php
/**
 * Created by PhpStorm.
 * User: mittal
 * Date: 04-04-2019
 * Time: 13:45
 */
function mo2f_show_2_factor_user_login_reports($user){
	$mo2f_handler = new MO2f_Handler();
	$style = "none";
	$message = "Show Advanced Search";
	$usertranscations = $mo2f_handler->get_all_transactions();
	?>
    <div class="mo2f_table_layout" style="border:0px;">

        <form name="f" method="post" action="" id="manualblockipform" >
            <input type="hidden" name="option" value="mo2f_manual_clear" id="clear_all" />
            <div style="display: inline-flex;width: 100%;">
                <div style="float: left;width: 50%;">
                    <h2>
                        User login Transactions Report
                    </h2>
                </div>
                <div style="width: 50%;">
                    <input type="submit" style="margin: 1em 0;float: right;" class="button button-primary button-large" value="Clear Login Reports" <?php if(mo2f_is_customer_registered()){}else{ echo 'disabled';}?> />
                </div>
            </div>
        </form>

        <table id="reports_table" class="display" cellspacing="0" width="100%">
            <thead>
            <tr>
                <th>IP Address</th>
                <th>Username</th>
                <th>User Action</th>
                <th>Status</th>
                <th>Created Date</th>
            </tr>
            </thead>
            <tbody style="text-align: center;">
			<?php foreach($usertranscations as $usertranscation){
					echo "<tr><td>" . $usertranscation->ip_address . "</td><td>" . $usertranscation->username . "</td><td>" . $usertranscation->type . "</td><td>";
					if ( $usertranscation->status == MO2f_Constants::FAILED || $usertranscation->status == MO2f_Constants::PAST_FAILED ) {
						echo "<span style=color:red>" . MO2f_Constants::FAILED . "</span>";
					} else if ( $usertranscation->status == MO2f_Constants::SUCCESS ) {
						echo "<span style=color:green>" . MO2f_Constants::SUCCESS . "</span>";
					} else {
						echo "N/A";
					}
					echo "</td><td>" . date( "M j, Y, g:i:s a", $usertranscation->created_timestamp ) . "</td></tr>";
			} ?>
            </tbody>
            <br>
        </table>
        <br>
    </div>
    <br>
    <script>
        jQuery(document).ready(function() {
            jQuery('#reports_table').DataTable({
                "order": [[ 4, "desc" ]]
            });
        } );
        function showAdvancedSearch(){
            var x = document.getElementById('mo2f_advanced_search_div');
            if (x.style.display === 'none') {
                x.style.display = 'block';
                document.getElementById('advanced_search_settings').innerHTML = "Hide Advanced Search";
            } else {
                x.style.display = 'none';
                document.getElementById('advanced_search_settings').innerHTML = "Show Advanced Search";
                //document.getElementById('mo2f_hide_advanced_search').submit();
            }
        }
    </script>
	<?php
}

?>