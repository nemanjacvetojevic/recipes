<?php

function mo2f_show_2_factor_ip_block($user) {
	$mo2f_ns_handler = new MO2f_Handler();
	$blockedips = $mo2f_ns_handler->get_blocked_ips();?>
	<div class="mo2f_table_layout" style="border:0px;">

		<h2>Manual Block IP's <p style="display: inline-block;color: #0085ba;font-size: 15px;">(Permanently)</p></h2>
		<form name="f" method="post" action="" id="manualblockipform" >
			<input type="hidden" name="option" value="mo2f_ns_manual_block_ip" />
			<table><tr><td>You can manually block an IP address here: </td>
					<td style="padding:0px 10px"><input class="mo2f_ns_table_textbox" type="text" name="ip"
					                                    required placeholder="xxx.xxx.xxx.xxx"  value=""<?php if(mo2f_is_customer_registered()){}else{ echo 'disabled';}?> /></td>
					<td><input type="submit" class="button button-primary button-large" value="Manual Block IP"<?php if(mo2f_is_customer_registered()){}else{ echo 'disabled';}?> /></td></tr></table>
		</form>
		<h2>Blocked IP's</h2>
		<table id="blockedips_table" class="display">
			<thead><tr><th width="15%">IP Address</th><th width="25%">Reason</th><th width="24%">Blocked Until</th><th width="24%">Blocked Date</th><th width="20%">Action</th></tr></thead>
			<tbody style="text-align: center;">
			<?php foreach($blockedips as $blockedip){
				echo "<tr><td>".$blockedip->ip_address."</td><td>".$blockedip->reason."</td><td>";
				if(date("Y",$blockedip->blocked_for_time)-date("Y",$blockedip->created_timestamp)==3) echo "<span class=redtext style='color:red;'>Permanently</span>"; else echo date("M j, Y, g:i:s a",$blockedip->blocked_for_time);
				echo "</td><td>".date("M j, Y, g:i:s a",$blockedip->created_timestamp)."</td><td><a onclick=unblockip('".$blockedip->id."') style=cursor:pointer;>Unblock IP</a></td></tr>";
			} ?>
			</tbody>
		</table>
        <br>
	</div>
	<form class="hidden" id="unblockipform" method="POST">
		<input type="hidden" name="option" value="mo2f_ns_unblock_ip" />
		<input type="hidden" name="entryid" value="" id="unblockipvalue" />
	</form>

	<hr >
	<?php $whitelisted_ips = $mo2f_ns_handler->get_whitelisted_ips(); ?>
	<div class="mo2f_table_layout" style="border:0px;">
		<h2>Whitelist IP's</h2>
		<form name="f" method="post" action="" id="whitelistipform">
			<input type="hidden" name="option" value="mo2f_ns_whitelist_ip" />
			<table><tr><td>Add new IP address to whitelist : </td>
					<td style="padding:0px 10px"><input class="mo2f_ns_table_textbox" type="text" name="ip"
					                                    required placeholder="xxx.xxx.xxx.xxx" value="" <?php if(mo2f_is_customer_registered()){}else{ echo 'disabled';}?>  /></td>
					<td><input type="submit" class="button button-primary button-large" value="Whitelist IP"<?php if(mo2f_is_customer_registered()){}else{ echo 'disabled';}?> /></td></tr></table>
		</form>
		<h2>Whitelisted IP's</h2>
		<table id="whitelistedips_table" class="display">
			<thead><tr><th width="30%">IP Address</th><th width="40%">Whitelisted Date</th><th width="30%">Remove from Whitelist</th></tr></thead>
			<tbody><?php foreach($whitelisted_ips as $whitelisted_ip){
				echo "<tr><td>".$whitelisted_ip->ip_address."</td><td>".date("M j, Y, g:i:s a",$whitelisted_ip->created_timestamp)."</td><td><a onclick=removefromwhitelist('".$whitelisted_ip->id."') style=cursor:pointer;>Remove</a></td></tr>";
			} ?></tbody>
		</table>
        <br>
	</div>

	<form class="hidden" id="removefromwhitelistform" method="POST">
		<input type="hidden" name="option" value="mo2f_ns_remove_whitelist" />
		<input type="hidden" name="entryid" value="" id="removefromwhitelistentry" />
	</form>

	<script>

        function unblockip(entryid){
            jQuery("#unblockipvalue").val(entryid);
            jQuery("#unblockipform").submit();
        }
        jQuery(document).ready(function() {
            jQuery('#blockedips_table').DataTable({
                "order": [[ 3, "desc" ]]
            });
        } );
        function removefromwhitelist(entryid){
            jQuery("#removefromwhitelistentry").val(entryid);
            jQuery("#removefromwhitelistform").submit();
        }
        jQuery(document).ready(function() {
            jQuery('#whitelistedips_table').DataTable({
                "order": [[ 1, "desc" ]]
            });
        } );
	</script>
<?php }
?>