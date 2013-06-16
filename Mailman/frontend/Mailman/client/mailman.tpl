		<script type="text/javascript">
			/*<![CDATA[*/
			$(document).ready(function () {
				$('#add_list_dialog').dialog({
					bgiframe: true,
					title: '{TR_MAIL_LISTS}',
					hide: 'blind',
					show: 'slide',
					//focus:false,
					autoOpen: false,
					width: '650',
					modal: true,
					dialogClass: 'body',
					buttons: {
						"{TR_ACTION}": function () {
							$('#add_list_frm').submit();
						},
						"Cancel": function () {
							$(this).dialog("close");
						}
					}
				});

				// PHP Editor settings button
				$('#add_list').button({ icons: { primary: 'ui-icon-gear'}}).click(function (e) {
					$('#add_list_dialog').dialog('open');
					return false;
				});
			});
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
				<td>{LIST}</td>
				<td><a href="{LIST_URL}">{LIST_URL}</a></td>
				<td>{STATUS}</td>
				<td>
					<a href="{EDIT_LINK}" class="icon {EDIT_ICON}">{TR_EDIT}</a>
					<a href="{DELETE_LINK}" class="icon {DELETE_ICON}">{TR_DELETE}</a>
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

		<div id="add_list_dialog">
			<form name="add_list_frm" id="add_list_frm" action="mailman.php" method="post" autocomplete="off">
				<table>
					<tr>
						<td><label for="list">{TR_LIST}</label></td>
						<td><input type="text" id="list" name="list" value="{LIST}"/></td>
					</tr>
					<tr>
						<td><label for="admin_email">{TR_ADMIN_EMAIL}</label></td>
						<td><input type="text" id="admin_email" name="admin_email" value="{ADMIN_EMAIL}"
								   autocomplete="off"/></td>
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
				<input type="hidden" name="id" value="{ID}"/>
				<input type="hidden" name="action" value="add"/>
			</form>
		</div>
