		<script type="text/javascript">
			/*<![CDATA[*/
			$(document).ready(function () {
				$('#list_dialog').dialog({
					bgiframe: true,
					title: '{TR_MAIL_LIST}',
					hide: 'blind',
					show: 'slide',
					focus:false,
					autoOpen: {LIST_DIALOG_OPEN},
					width: '650',
					modal: true,
					dialogClass: 'body',
					buttons: {
						"{TR_APPLY}": function () {
							$('#list_frm').submit();
						},
						"{TR_CANCEL}": function () {
							$('#list_frm').find("input[type=text], input[type=password]").val("");
							$("#list_name").attr("readonly", false);
							$(this).dialog("close");
						}
					}
				});

				// PHP Editor settings button
				$('#add_list').button({ icons: { primary: 'ui-icon-gear'}}).click(function (e) {
					$('#list_dialog').dialog('open');
					return false;
				});
			});

			function confirm_deletion(list_name) {
				return confirm(sprintf('{TR_CONFIRM_DELETION}', list_name));
			}
			/*]]>*/
		</script>
		<!-- BDP: email_lists -->
		<table>
			<thead>
			<tr>
				<th colspan="4">{TR_MAIL_LISTS}</th>
			</tr>
			</thead>
			<tbody>
			<tr>
				<td><strong>Name</strong></td>
				<td><strong>URL</strong></td>
				<td><strong>Status</strong></td>
				<td><strong>Actions</strong></td>
			</tr>
			<!-- BDP: email_list -->
			<tr>
				<td>{LIST_NAME}</td>
				<td><a target="_blank" href="{LIST_URL}">{LIST_URL}</a></td>
				<td>{STATUS}</td>
				<td>
					<a href="{EDIT_LINK}" class="icon {EDIT_ICON}">{TR_EDIT}</a>
					<a href="{DELETE_LINK}" class="icon {DELETE_ICON}" onclick="return confirm_deletion('{LIST_NAME}')">{TR_DELETE}</a>
				</td>
			</tr>
			<!-- EDP: email_list -->
			</tbody>
			<tfoot>
			<tr>
				<th colspan="4">{TR_MAIL_LISTS}</th>
			</tr>
			</tfoot>
		</table>
		<!-- EDP: email_lists -->

		<div class="buttons">
			<button id="add_list" value="Add list">{TR_ADD_LIST}</button>
		</div>

		<div id="list_dialog">
			<form name="list_frm" id="list_frm" action="mailman.php" method="post" autocomplete="off">
				<table>
					<tr>
						<td><label for="list_name">{TR_LIST_NAME}</label></td>
						<td>
							<input type="text" id="list_name" name="list_name" value="{LIST_NAME}"{LIST_NAME_READONLY}/>
						</td>
					</tr>
					<tr>
						<td><label for="admin_email">{TR_ADMIN_EMAIL}</label></td>
						<td>
							<input type="text" id="admin_email" name="admin_email" value="{ADMIN_EMAIL}" autocomplete="off"/>
						</td>
					</tr>
					<tr>
						<td><label for="admin_password">{TR_ADMIN_PASSWORD}</label></td>
						<td><input type="password" id="admin_password" name="admin_password" value=""/></td>
					</tr>
					<tr>
						<td><label for="admin_password_confirm">{TR_ADMIN_PASSWORD_CONFIRM}</label></td>
						<td><input type="password" id="admin_password_confirm" name="admin_password_confirm" value=""/>
						</td>
					</tr>
				</table>
				<input type="hidden" name="list_id" value="{LIST_ID}"/>
				<input type="hidden" name="action" value="{ACTION}"/>
			</form>
		</div>
