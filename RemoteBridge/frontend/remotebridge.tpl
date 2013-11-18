
<!-- BDP: bridge_lists -->
<table>
	<thead>
	<tr>
		<th>{TR_BRIDGE_KEY}</th>
		<th>{TR_IP}</th>
		<th>{TR_STATUS}</th>
		<th>{TR_ACTION}</th>
	</tr>
	</thead>
	<tfoot>
	<tr>
		<td colspan="4">{TR_REMOTE_BRIDGES}</td>
	</tr>
	</tfoot>
	<tbody>
	<!-- BDP: bridge_list -->
	<tr>
		<td>{BRIDGE_KEY}</td>
		<td>{BRIDGE_IPADDRESS}</td>
		<td>{STATUS}</td>
		<td>
			<a href="{EDIT_LINK}" class="icon {EDIT_ICON}">{TR_EDIT}</a>
			<a href="{DELETE_LINK}" class="icon {DELETE_ICON}" onclick="return confirm_deletion('{BRIDGE_KEY}')">{TR_DELETE}</a>
		</td>
	</tr>
	<!-- EDP: bridge_list -->
	</tbody>
</table>
<!-- EDP: bridge_lists -->

<!-- BDP: add_bridgekey -->
<div class="buttons">
	<button id="add_remotebridge" value="Add remote bridge">{TR_ADD_BRIDGE}</button>
</div>
<!-- EDP: add_bridgekey -->

<div id="remotebridge_dialog">
	<form name="remotebridge_frm" id="remotebridge_frm" action="remotebridge.php" method="post" autocomplete="off">
		<table>
			<tr>
				<td><label for="bridge_key">{TR_BRIDGE_KEY}</label></td>
				<td>
					<input type="text" id="bridge_key" name="bridge_key" value="{BRIDGE_KEY}"{BRIDGE_KEY_READONLY}/>
				</td>
			</tr>
			<tr>
				<td><label for="bridge_ipaddress">{TR_BRIDGE_IPADDRESS}</label></td>
				<td>
					<input type="text" id="bridge_ipaddress" name="bridge_ipaddress" value="{BRIDGE_IPADDRESS}" autocomplete="off"/>
				</td>
			</tr>
		</table>
		<input type="hidden" name="bridge_id" value="{BRIDGE_ID}"/>
		<input type="hidden" name="action" value="{ACTION}"/>
	</form>
</div>

<script type="text/javascript">
	$(document).ready(function () {
		$('#remotebridge_dialog').dialog({
			bgiframe: true,
			title: '{TR_REMOTE_BRIDGE}',
			hide: 'blind',
			show: 'slide',
			focus:false,
			autoOpen: {REMOTEBRIDGE_DIALOG_OPEN},
			width: '650',
			modal: true,
			dialogClass: 'body',
			buttons: {
		"{TR_APPLY}": function () {
					$('#remotebridge_frm').submit();
				},
				"{TR_CANCEL}": function () {
					$('#remotebridge_frm').find("input[type=text]").val("");
					$("#remotebridge_name").attr("readonly", false);
					$(this).dialog("close");
				},
				"{TR_GENERATE_BRIDGEKEY}": function () {
					var chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz";
					var string_length = 30;
					var RandomKeyString = '';
					for (var i=0; i<string_length; i++) {
						var rnum = Math.floor(Math.random() * chars.length);
						RandomKeyString += chars.substring(rnum,rnum+1);
					}
					$('#bridge_key').val(RandomKeyString);
				}
			}
		});
		$('#add_remotebridge').button({ icons: { primary: 'ui-icon-gear'}}).click(function (e) {
			$('#remotebridge_dialog').dialog('open');
			return false;
		});
	});
			
	function confirm_deletion(remotebridge_name) {
		return confirm(sprintf('{TR_CONFIRM_DELETION}', remotebridge_name));
	}
</script>
