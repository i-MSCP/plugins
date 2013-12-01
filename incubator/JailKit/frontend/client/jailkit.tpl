
<!-- BDP: jailkit_add_button -->
<div class="buttons" style="text-align:left;">
	<button id="add_jailkit_login" value="{TR_ADD_JAILKIT_LOGIN}">{TR_ADD_JAILKIT_LOGIN}</button>
</div>
<!-- EDP: jailkit_add_button -->

<!-- BDP: jailkit_login_list -->
<table class="firstColFixed">
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
			<a href="jailkit.php?action=change&login_id={JAILKIT_LOGIN_ID}"
			   onclick="return action_status('{JAILKIT_USER_NAME}');"
			   class="icon i_{STATUS_ICON}">{JAILKIT_LOGIN_STATUS}</a>
			<!-- EDP: jailkit_action_status_link -->

			<!-- BDP: jailkit_action_status_static -->
			<span class="icon i_{STATUS_ICON}">{JAILKIT_LOGIN_STATUS}</span>
			<!-- EDP: jailkit_action_status_static -->
		</td>
		<td>{JAILKIT_USER_NAME}</td>
		<td>
			<!-- BDP: jailkit_action_links -->
			<a class="icon i_edit"
			   href="jailkit.php?action=edit&amp;login_id={JAILKIT_LOGIN_ID}">{TR_EDIT_LOGINNAME}</a>
			<a class="icon i_delete delete_sshlogin"
			   href="jailkit.php?action=delete&amp;login_id={JAILKIT_LOGIN_ID}">{TR_DELETE_LOGINNAME}</a>
			<!-- EDP: jailkit_action_links -->
		</td>
	</tr>
	<!-- EDP: jailkit_login_item -->
	<!-- BDP: jailkit_login_item_disabled -->
	<tr>
		<td>
			<div class="icon i_{STATUS_ICON}">{JAILKIT_LOGIN_STATUS}
				<div>
		</td>
		<td colspan="2">{JAILKIT_USER_NAME}</td>
		<td>&nbsp;</td>
	</tr>
	<!-- EDP: jailkit_login_item_disabled -->
	</tbody>
</table>
<br/>

<div class="paginator">
	<!-- BDP: scroll_prev -->
	<a class="icon i_prev" href="jailkit.php?psi={PREV_PSI}" title="{TR_PREVIOUS}">{TR_PREVIOUS}</a>
	<!-- EDP: scroll_prev -->
	<!-- BDP: scroll_prev_gray -->
	<a class="icon i_prev_gray" href="#"></a>
	<!-- EDP: scroll_prev_gray -->
	<!-- BDP: scroll_next_gray -->
	<a class="icon i_next_gray" href="#"></a>
	<!-- EDP: scroll_next_gray -->
	<!-- BDP: scroll_next -->
	<a class="icon i_next" href="jailkit.php?psi={NEXT_PSI}" title="{TR_NEXT}">{TR_NEXT}</a>
	<!-- EDP: scroll_next -->
</div>
<!-- EDP: jailkit_login_list -->

<!-- BDP: jailkit_no_login_item -->
<table>
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
	<tr>
		<td colspan="3">
			<div class="message info">{JAILKIT_NO_LOGIN}</div>
		</td>
	</tr>
	</tbody>
</table>
<!-- EDP: jailkit_no_login_item -->

<!-- BDP: jailkit_edit_login -->
<form action="jailkit.php?action=edit&amp;login_id={JAILKIT_LOGIN_ID}" method="post" name="edit_ssh_login"
	  id="edit_ssh_login">
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="2">{TR_EDIT_SSH_USER}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td><label for="ssh_login_name">{TR_SSH_USERNAME}</label></td>
			<td>
				<input type="text" id="ssh_login_name" name="ssh_login_name" value="{JAILKIT_USERNAME}"
					   disabled="disabled"/>
			</td>
		</tr>
		<tr>
			<td><label for="ssh_login_pass">{TR_SSH_PASSWORD}</label></td>
			<td><input type="password" id="ssh_login_pass" name="ssh_login_pass" value="" autocomplete="off"/></td>
		</tr>
		<tr>
			<td><label for="ssh_login_pass_confirm">{TR_SSH_PASSWORD_CONFIRM}</label></td>
			<td>
				<input type="password" id="ssh_login_pass_confirm" name="ssh_login_pass_confirm" value=""
					   autocomplete="off"/>
			</td>
		</tr>
		</tbody>
	</table>

	<div class="buttons">
		<input name="submit" type="submit" value="{TR_UPDATE}"/>
		<a class="link_as_button" href="jailkit.php">{TR_CANCEL}</a>
	</div>
</form>
<!-- EDP: jailkit_edit_login -->

<!-- BDP: jailkit_add_dialog -->
<div id="jailkit_add_dialog">
	<form name="jailkit_login_frm" id="jailkit_login_frm" action="jailkit.php" method="post" autocomplete="off">
		<table>
			<tr>
				<td><label for="ssh_login_name">{TR_SSH_USERNAME}</label></td>
				<td><strong>jk_</strong></td>
				<td>
					<input type="text" id="ssh_login_name" name="ssh_login_name" value="{JAILKIT_USERNAME}"
						   maxlength="13"/>
				</td>
			</tr>
			<tr>
				<td><label for="ssh_login_pass">{TR_SSH_PASSWORD}</label></td>
				<td>&nbsp;</td>
				<td>
					<input type="password" id="ssh_login_pass" name="ssh_login_pass" value=""
						   autocomplete="off"/>
				</td>
			</tr>
			<tr>
				<td><label for="ssh_login_pass_confirm">{TR_SSH_PASSWORD_CONFIRM}</label></td>
				<td>&nbsp;</td>
				<td>
					<input type="password" id="ssh_login_pass_confirm" name="ssh_login_pass_confirm" value=""
						   autocomplete="off"/>
				</td>
			</tr>
		</table>
		<input type="hidden" name="action" value="add"/>
	</form>
</div>

<script type="text/javascript">
	$(document).ready(function () {
		$('#jailkit_add_dialog').dialog({
			bgiframe: true,
			title: '{TR_DIALOG_TITLE_ADD}',
			hide: 'blind',
			show: 'slide',
			focus: false,
			autoOpen: {JAILKIT_DIALOG_OPEN},
			width: '650',
			modal: true,
			dialogClass: 'body',
			buttons: {
				"{TR_ADD}": function () {
					$('#jailkit_login_frm').submit();
				},
				"{TR_CANCEL}": function () {
					$('#jailkit_login_frm').find("input[type=text]").val("");
					$(this).dialog("close");
				}
			}
		});
		$('#add_jailkit_login').button({ icons: { primary: 'ui-icon-gear'}}).click(function (e) {
			$('#jailkit_add_dialog').dialog('open');
			return false;
		});

		$(".delete_sshlogin").click(function () {
			return confirm("{DELETE_LOGIN_ALERT}");
		});
	});

	function action_status(login_name) {
		return confirm(sprintf("{DISABLE_LOGIN_ALERT}", login_name));
	}
</script>
<!-- EDP: jailkit_add_dialog -->
