
<!-- BDP: jailkit_login_list -->
<table class="firstColFixed datatable">
	<thead>
	<tr>
		<th>{TR_JAILKIT_LOGIN_STATUS}</th>
		<th>{TR_JAILKIT_USERNAME}</th>
		<th>{TR_JAILKIT_LOGIN_ACTIONS}</th>
	</tr>
	</thead>
	<tfoot>
	<tr>
		<td colspan="3">{TR_JAILKIT_LOGIN_AVAILABLE}</td>
	</tr>
	</tfoot>
	<tbody>
	<!-- BDP: jailkit_login_item -->
	<tr>
		<td>
			<!-- BDP: jailkit_action_status_link -->
			<a href="ssh_users.php?action=change&amp;login_id={JAILKIT_LOGIN_ID}"
			   class="icon i_{STATUS_ICON} change_action" data-change-alert="{TR_CHANGE_ALERT}"
			   title="{TR_CHANGE_ACTION_TOOLTIP}">{JAILKIT_LOGIN_STATUS}</a>
			<!-- EDP: jailkit_action_status_link -->

			<!-- BDP: jailkit_action_status_static -->
			<span class="icon i_{STATUS_ICON}" title="{JAILKIT_LOGIN_STATUS}">{JAILKIT_LOGIN_STATUS}</span>
			<!-- EDP: jailkit_action_status_static -->
		</td>
		<td>{JAILKIT_USER_NAME}</td>
		<td>
			<!-- BDP: jailkit_action_links -->
			<span class="icon i_edit edit_action clickable" data-login-id="{JAILKIT_LOGIN_ID}"
				  data-login-name="{JAILKIT_USER_NAME}" title="{TR_EDIT}">{TR_EDIT}</span>

			<a class="icon i_delete delete_action"
			   href="ssh_users.php?action=delete&amp;login_id={JAILKIT_LOGIN_ID}" title="{TR_DELETE}">{TR_DELETE}</a>
			<!-- EDP: jailkit_action_links -->
		</td>
	</tr>
	<!-- EDP: jailkit_login_item -->
	</tbody>
</table>
<!-- EDP: jailkit_login_list -->

<div class="buttons">
	<!-- BDP: jailkit_add_button -->
	<label>
		<button id="add_action">{TR_ADD_JAILKIT_LOGIN}</button>
	</label>
	<!-- EDP: jailkit_add_button -->
	<a class="link_as_button" href="domains_manage.php">{TR_CANCEL}</a>
</div>

<!-- BDP: jailkit_dialog -->
<div id="jailkit_dialog">
	<form name="jailkit_login_frm" id="jailkit_login_frm" action="ssh_users.php" method="post" autocomplete="off">
		<table>
			<tr>
				<td><label for="ssh_login_name">{TR_SSH_USERNAME}</label></td>
				<td><span id="ssh_login_name_prefix"><strong>jk_</strong></span></td>
				<td>
					<input type="text" id="ssh_login_name" name="ssh_login_name" value="{JAILKIT_USERNAME}"
						   maxlength="13"/>
				</td>
			</tr>
			<tr>
				<td><label for="ssh_login_pass">{TR_SSH_PASSWORD}</label></td>
				<td>&nbsp;</td>
				<td><input type="password" id="ssh_login_pass" name="ssh_login_pass" value=""/></td>
			</tr>
			<tr>
				<td><label for="ssh_login_pass_confirm">{TR_SSH_PASSWORD_CONFIRM}</label></td>
				<td>&nbsp;</td>
				<td><input type="password" id="ssh_login_pass_confirm" name="ssh_login_pass_confirm" value=""/></td>
			</tr>
		</table>
		<input type="hidden" id="login_id" name="login_id" value="{LOGIN_ID}"/>
		<input type="hidden" id="action" name="action" value="{ACTION}"/>
	</form>
</div>

<script type="text/javascript">
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
			title: "{TR_DIALOG_ADD_TITLE}",
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
					text: "{TR_DIALOG_ADD}",
					click: function () {
						if ($("#action").val() == 'add') {
							$("#login_id").prop('disabled', true);
						} else {
							$("#login_id").prop('disabled', false);
						}

						$("#jailkit_login_frm").submit();
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

		if ($("#action").val() == 'edit') {
			$("#ssh_login_name_prefix").hide();
			$("#ssh_login_name").prop('disabled', true);
			$("#dialog_submit_button").button("option", "label", "{TR_DIALOG_EDIT}");
			dialog.dialog("option", "title", "{TR_DIALOG_EDIT_TITLE}");
		}

		$("#add_action").click(function () {
			$("#jailkit_login_frm").find("input").val("").prop("disabled", false);
			$("#dialog_submit_button").button("option", "label", "{TR_DIALOG_ADD}");
			$("#ssh_login_name_prefix").show();
			$("#action").val('add');
			dialog.dialog("option", "title", "{TR_DIALOG_ADD_TITLE}").dialog("open");
		});

		$('.edit_action').click(function () {
			$("#ssh_login_name_prefix").hide();
			$("#ssh_login_name").val($(this).data("login-name")).prop('disabled', true);
			$("#login_id").val($(this).data("login-id"));
			$("#action").val('edit');
			$('#dialog_submit_button').button("option", "label", "{TR_DIALOG_EDIT}");
			dialog.dialog("option", "title", "{TR_DIALOG_EDIT_TITLE}").dialog("open");
		});

		$(".delete_action").click(function () {
			return confirm("{DELETE_LOGIN_ALERT}");
		});

		$(".change_action").click(function () {
			return confirm($(this).data('change-alert'));
		});
	});
</script>
<!-- EDP: jailkit_dialog -->
