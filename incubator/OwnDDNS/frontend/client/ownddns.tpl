<!-- BDP: ownddns_add_button -->
<div class="buttons" style="text-align:left;">
	<button id="add_ownddns_account" value="{TR_ADD_OWNDDNS_ACCOUNT}">{TR_ADD_OWNDDNS_ACCOUNT}</button>
</div>
<!-- EDP: ownddns_add_button -->
<!-- BDP: ownddns_account_list -->
<table>
	<thead>
	<tr>
		<th>{TR_OWNDDNS_ACCOUNT_STATUS}</th>
		<th>{TR_OWNDDNS_ACCOUNT_NAME}</th>
		<th>{TR_OWNDDNS_ACCOUNT_FQDN}</th>
		<th>{TR_POPUP_OWNDDNS_KEY}</th>
		<th>{TR_OWNDDNS_LAST_IP}</th>
		<th>{TR_OWNDDNS_LAST_UPDATE}</th>
		<th>{TR_OWNDDNS_ACCOUNT_ACTIONS}</th>
	</tr>
	</thead>
	<tfoot>
	<tr>
		<td colspan="7">{TR_OWNDDNS_ACCOUNT_AVAILABLE}</td>
	</tr>
	</tfoot>
	<tbody>
	<!-- BDP: ownddns_account_item -->
	<tr>
		<td>
			<div class="icon i_{STATUS_ICON}">{OWNDDNS_ACCOUNT_STATUS}<div>
		</td>
		<td>{OWNDDNS_ACCOUNT_NAME}</td>
		<td>{OWNDDNS_ACCOUNT_FQDN}</td>
		<td>{OWNDDNS_ACCOUNT_KEY}</td>
		<td>{OWNDDNS_LAST_IP}</td>
		<td>{OWNDDNS_LAST_UPDATE}</td>
		<td>
			<a class="icon i_edit" href="ownddns.php?action=edit&amp;ownddns_account_id={OWNDDNS_ACCOUNT_ID}">{TR_EDIT_ACCOUNT}</a>
			<a class="icon i_delete delete_ownddns_account" href="ownddns.php?action=delete&amp;ownddns_account_id={OWNDDNS_ACCOUNT_ID}">{TR_DELETE_ACCOUNT}</a>
		</td>
	</tr>
	<!-- EDP: ownddns_account_item -->
	</tbody>
</table>
<br />

<div class="paginator">
	<!-- BDP: scroll_prev -->
	<a class="icon i_prev" href="ownddns.php?psi={PREV_PSI}" title="{TR_PREVIOUS}">{TR_PREVIOUS}</a>
	<!-- EDP: scroll_prev -->
	<!-- BDP: scroll_prev_gray -->
	<a class="icon i_prev_gray" href="#"></a>
	<!-- EDP: scroll_prev_gray -->
	<!-- BDP: scroll_next_gray -->
	<a class="icon i_next_gray" href="#"></a>
	<!-- EDP: scroll_next_gray -->
	<!-- BDP: scroll_next -->
	<a class="icon i_next" href="ownddns.php?psi={NEXT_PSI}" title="{TR_NEXT}">{TR_NEXT}</a>
	<!-- EDP: scroll_next -->
</div>
<!-- EDP: ownddns_account_list -->
<!-- BDP: ownddns_no_account_item -->
<table>
	<thead>
	<tr>
		<th>{TR_OWNDDNS_ACCOUNT_STATUS}</th>
		<th>{TR_OWNDDNS_ACCOUNT_NAME}</th>
		<th>{TR_OWNDDNS_ACCOUNT_ACTIONS}</th>
	</tr>
	</thead>
	<tfoot>
	<tr>
		<td colspan="3">{TR_OWNDDNS_ACCOUNT_AVAILABLE}</td>
	</tr>
	</tfoot>
	<tbody>
	<tr>
		<td colspan="3"><div class="message info">{OWNDDNS_NO_ACCOUNT}</div></td>
	</tr>
	</tbody>
</table>
<!-- EDP: ownddns_no_account_item -->
<!-- BDP: ownddns_edit_account -->
<form action="ownddns.php?action=edit&amp;ownddns_account_id={OWNDDNS_ACCOUNT_ID}" method="post" name="edit_ownddns_account" id="edit_ownddns_account">
	<table class="firstColFixed">
		<thead>
		<tr>
			<th>{TR_OWNDDNS_ACCOUNT_NAME}</th>
			<th>{TR_OWNDDNS_ACCOUNT_FQDN}</th>
			<th>{TR_POPUP_OWNDDNS_KEY}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td><label for="max_accounts">{OWNDDNS_ACCOUNT_NAME_EDIT}</label></td>
			<td><label for="max_accounts">{OWNDDNS_ACCOUNT_FQDN_EDIT}</label></td>
			<td><input type="text" id="ownddns_key" name="ownddns_key" size="35" value="{OWNDDNS_KEY_EDIT}"{OWNDDNS_KEY_READONLY}/></td>
		</tr>
		</tbody>
	</table>
	
	<div class="buttons">
		<a class ="link_as_button" id="generate_ownddns_key" href="#">{TR_GENERATE_OWNDDNSKEY}</a>
		<input name="submit" type="submit" value="{TR_UPDATE}"/>
		<a class ="link_as_button" href="ownddns.php">{TR_CANCEL}</a>
	</div>
</form>
<script type="text/javascript">
	/*<![CDATA[*/
	$(document).ready(function () {
		$('#generate_ownddns_key').click(function (e) {
			var chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz";
			var string_length = 30;
			var RandomKeyString = '';
			for (var i=0; i<string_length; i++) {
				var rnum = Math.floor(Math.random() * chars.length);
				RandomKeyString += chars.substring(rnum,rnum+1);
			}
			$('#ownddns_key').val(RandomKeyString);
		});
	});
	/*]]>*/
</script>
<!-- EDP: ownddns_edit_account -->
<!-- BDP: ownddns_add_dialog -->
<div id="ownddns_add_dialog">
	<form name="ownddns_account_frm" id="ownddns_account_frm" action="ownddns.php" method="post" autocomplete="off">
		<table>
			<tr>
				<td><label for="ownddns_account_name">{TR_POPUP_OWNDDNS_ACCOUNT_NAME}</label></td>
				<td>
					<input type="text" id="ownddns_account_name" name="ownddns_account_name" value="{OWNDDNS_ACCOUNT_NAME_ADD}" maxlength="{MAX_ACCOUNT_NAME_LENGHT}" />
				</td>
				<td>
					<select name="ownddns_domain_id">
						<option value="-1">{TR_OWNDDNS_SELECT_NAME_NONE}</option>
						<!-- BDP: ownddns_select_item -->
						<option value="{TR_OWNDDNS_SELECT_VALUE}"{ACCOUNT_NAME_SELECTED}>{TR_OWNDDNS_SELECT_NAME}</option>
						<!-- EDP: ownddns_select_item -->
					</select>
				</td>
			</tr>
			<tr>
				<td><label for="ownddns_key">{TR_POPUP_OWNDDNS_KEY}</label></td>
				<td colspan="2">
					<input type="text" id="ownddns_key" name="ownddns_key" size="35" value="{OWNDDNS_KEY_ADD}"{OWNDDNS_KEY_READONLY}/>
				</td>
			</tr>
		</table>
		<input type="hidden" name="action" value="add"/>
	</form>
</div>

<script type="text/javascript">
	/*<![CDATA[*/
	$(document).ready(function () {
		$('#ownddns_add_dialog').dialog({
			bgiframe: true,
			title: '{TR_PAGE_TITLE_OWNDDNS_ADD}',
			hide: 'blind',
			show: 'slide',
			focus:false,
			autoOpen: {OWNDDNS_DIALOG_OPEN},
			width: '650',
			modal: true,
			dialogClass: 'body',
			buttons: {
				"{TR_ADD}": function () {
					$('#ownddns_account_frm').submit();
				},
				"{TR_CANCEL}": function () {
					$('#ownddns_account_frm').find("input[type=text]").val("");
					$(this).dialog("close");
				},
				"{TR_GENERATE_OWNDDNSKEY}": function () {
					var chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz";
					var string_length = 30;
					var RandomKeyString = '';
					for (var i=0; i<string_length; i++) {
						var rnum = Math.floor(Math.random() * chars.length);
						RandomKeyString += chars.substring(rnum,rnum+1);
					}
					$('#ownddns_key').val(RandomKeyString);
				}
			}
		});
		$('#add_ownddns_account').button({ icons: { primary: 'ui-icon-gear'}}).click(function (e) {
			$('#ownddns_add_dialog').dialog('open');
			return false;
		});
		
		$('#generate_ownddns_key').click(function (e) {
			var chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz";
			var string_length = 30;
			var RandomKeyString = '';
			for (var i=0; i<string_length; i++) {
				var rnum = Math.floor(Math.random() * chars.length);
				RandomKeyString += chars.substring(rnum,rnum+1);
			}
			$('#ownddns_key').val(RandomKeyString);
		});
		
		$(".delete_ownddns_account").click(function(){
			return confirm("{DELETE_ACCOUNT_ALERT}");
		});
	});
	/*]]>*/
</script>
<!-- EDP: ownddns_add_dialog -->
