<!-- BDP: bridge_lists -->
<table>
	<thead class="ui-widget-header">
		<tr>
			<th colspan="4">{TR_REMOTE_BRIDGES}</th>
		</tr>
	</thead>
	<tfoot class="ui-widget-header">
		<tr>
			<th colspan="4">{TR_REMOTE_BRIDGES}</th>
		</tr>
	</tfoot>
	<tbody class="ui-widget-content">
		<tr>
			<td><strong>{TR_BRIDGE_KEY}</strong></td>
			<td><strong>{TR_IP}</strong></td>
			<td><strong>{TR_STATUS}</strong></td>
			<td><strong>{TR_ACTION}</strong></td>
		</tr>
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

<!-- BDP: bridge_downloads -->
<br />
<table>
	<thead class="ui-widget-header">
		<tr>
			<th colspan="2">{TR_BRIDGE_DOWNLOADS}</th>
		</tr>
	</thead>
	<tfoot class="ui-widget-header">
		<tr>
			<th colspan="2">{TR_BRIDGE_DOWNLOADS}</th>
		</tr>
	</tfoot>
	<tbody class="ui-widget-content">
	<tr>
		<td><strong>{TR_BRIDGE_DOWNLOAD_DESCRIPTION}</strong></td>
		<td><strong>{TR_BRIDGE_DOWNLOAD_FILE}</strong></td>
	</tr>
	<!-- BDP: bridge_download_item -->
	<tr>
		<td>{BRIDGE_DOWNLOAD_DESCRIPTION}</td>
		<td>
			<div class="buttons" style="text-align:left">
				<a style="color:#fff" class="download" href="remotebridge_downloads.php?getfile={BRIDGE_DOWNLOAD_FILE}">
					{BRIDGE_DOWNLOAD_FILE}
				</a>
			</div>
		</td>
	</tr>
	<!-- EDP: bridge_download_item -->
	</tbody>
</table>
<!-- EDP: bridge_downloads -->

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
	/*<![CDATA[*/
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
	
	$(function() { $( ".download" ).button();});
	/*]]>*/
</script>
