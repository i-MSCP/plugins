
<!-- BDP: jailkit_list -->
<!-- BDP: jailkit_select_items -->
<form action="ssh_accounts.php" method="post" name="activate_customer" id="activate_customer">
	<label>
		<select name="admin_id">
			<option value="-1">{TR_JAILKIT_SELECT_NAME_NONE}</option>
			<!-- BDP: jailkit_select_item -->
			<option value="{TR_JAILKIT_SELECT_VALUE}">{TR_JAILKIT_SELECT_NAME}</option>
			<!-- EDP: jailkit_select_item -->
		</select>
	</label>
	<div class="buttons" style="display:inline-block;">
		<input type="hidden" name="action" value="activate"/>
		<input name="Submit" type="submit" value="{TR_SELECT_ACTION}" title="{TR_SELECT_ACTION_TOOLTIP}"/>
	</div>
</form>
<!-- EDP: jailkit_select_items -->
<!-- BDP: jailkit_customer_list -->
<table class="firstColFixed datatable">
	<thead>
	<tr>
		<th>{TR_JAILKIT_STATUS}</th>
		<th>{TR_JAILKIT_CUSTOMER_NAME}</th>
		<th>{TR_JAILKIT_LOGIN_LIMIT}</th>
		<th>{TR_JAILKIT_ACTIONS}</th>
	</tr>
	</thead>
	<tbody>
	<!-- BDP: jailkit_customer_item -->
	<tr>
		<td>
			<!-- BDP: jailkit_action_status_link -->
			<a href="?action={CHANGE_ACTION}&admin_id={JAILKIT_ADMIN_ID}"
			   class="icon i_{STATUS_ICON} change_action" data-change-alert="{TR_CHANGE_ALERT}"
			   title="{TR_CHANGE_ACTION_TOOLTIP}">{JAILKIT_STATUS}</a>
			<!-- EDP: jailkit_action_status_link -->
			<!-- BDP: jailkit_action_status_static -->
			<span class="icon i_{STATUS_ICON}" title="{JAILKIT_STATUS}">{JAILKIT_STATUS}</span>
			<!-- EDP: jailkit_action_status_static -->
		</td>
		<td>{JAILKIT_CUSTOMER_NAME}</td>
		<td>{JAILKIT_LOGIN_LIMIT}</td>
		<td>
			<!-- BDP: jailkit_action_links -->
			<span class="icon i_edit edit_action clickable" data-admin-id="{JAILKIT_ADMIN_ID}"
				  title="{TR_EDIT_TOOLTIP}">{TR_EDIT}</span>
			<a class="icon i_delete delete_action"
			   href="ssh_accounts.php?action=deactivate&amp;admin_id={JAILKIT_ADMIN_ID}"
			   title="{TR_DEACTIVATE_TOOLTIP}">{TR_DELETE_JAIL}</a>
			<!-- EDP: jailkit_action_links -->
		</td>
	</tr>
	<!-- EDP: jailkit_customer_item -->
	</tbody>
</table>
<!-- EDP: jailkit_customer_list -->

<!-- BDP: jailkit_edit_dialog -->
<div id="jailkit_dialog">
	<form name="jailkit_edit_frm" id="jailkit_edit_frm" action="ssh_accounts.php" method="post">
		<table class="firstColFixed">
			<tbody>
			<tr>
				<td><label for="max_logins">{TR_MAX_SSH_USERS}</label></td>
				<td><input type="text" name="max_logins" id="max_logins" value="{MAX_LOGINS}"/></td>
			</tr>
			</tbody>
		</table>
		<input type="hidden" id="admin_id" name="admin_id" value="{JAILKIT_EDIT_ADMIN_ID}"/>
		<input type="hidden" id="action" name="action" value="edit"/>
	</form>
</div>
<!-- EDP: jailkit_edit_dialog -->

<!-- BDP: jailkit_js -->
<script>
	$(document).ready(function () {
		$('.datatable').dataTable(
			{
				"oLanguage": {DATATABLE_TRANSLATIONS},
				"iDisplayLength": 5,
				"bStateSave": true
			}
		);

		$dialogOpen = {JAILKIT_DIALOG_OPEN};

		var dialog = $("#jailkit_dialog").dialog({
			title: "{TR_DIALOG_TITLE}",
			hide: "blind",
			show: "slide",
			autoOpen: $dialogOpen,
			minHeight: 300,
			minWidth: 650,
			modal: true,
			open: function () {
				if ($dialogOpen) $(".error.flash_message").prependTo("#jailkit_dialog");
			},
			close: function () {
				$(".error.flash_message").remove();
			},
			buttons: {
				"submit_button": {
					id: "dialog_submit_button",
					text: "{TR_DIALOG_EDIT}",
					click: function () {
						if ($("#action").val() == 'add') {
							$("#login_id").prop('disabled', true);
						} else {
							$("#login_id").prop('disabled', false);
						}

						$("#jailkit_edit_frm").submit();
					}
				},
				"cancel_button": {
					text: "{TR_DIALOG_CANCEL}",
					click: function () {
						$(this).dialog("close");
					}
				}
			}
		});

		$('.edit_action').click(function () {
			$("#admin_id").val($(this).data("admin-id"));
			dialog.dialog("open");
		});

		$(".change_action").click(function () {
			return confirm($(this).data('change-alert'));
		});

		$(".delete_action").click(function () {
			return confirm("{DEACTIVATE_CUSTOMER_ALERT}");
		});
	});
</script>
<!-- EDP: jailkit_js -->
<!-- EDP: jailkit_list -->
