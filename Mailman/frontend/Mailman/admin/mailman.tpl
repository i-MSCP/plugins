<script type="text/javascript">
	/*<![CDATA[*/
	$(document).ready(function() {
		$('#add_list_dialog').dialog({
			bgiframe:true,
			hide:'blind', show:'slide', focus:false, autoOpen:false, width:'650', modal:true, dialogClass:'body',
			buttons:{ 'Close':function(){ $(this).dialog('{TR_ACTION}');}}
		});

		// PHP Editor settings button
		$('#add_list').button({ icons:{ primary:'ui-icon-gear'}}).click(function(e){
			$('#add_list_dialog').dialog('open');
			return false;
		});
	});
	/*]]>*/
</script>
<!-- BDP: mailing_lists -->

	<table>
		<thead>
		<tr>
			<th colspan="3">{TR_MAIL_LISTS}</th>
		</tr>
		</thead>
		<tbody>
		<!-- BDP: mailing_list -->
		<tr>
			<td><a href="{LIST_URL}">{NAME}</a></td>
			<td>{STATUS}</td>
			<td>
				<a href="settings.php?plugin=mailman&action=edit" class="icon i_edit">{TR_EDIT}</a>
				<a href="settings.php?plugin=mailman&action=delete" class="icon i_delete">{TR_DELETE}</a>
			</td>
		</tr>
		<!-- EDP: mailing_list -->
		</tbody>
		<tfoot>
		<tr>
			<th colspan="3">{TR_MAIL_LISTS}</th>
		</tr>
		</tfoot>
	</table>
<!-- EDP: mailing_lists -->
<div class="buttons">
	 <button id="add_list" value="Add list">{TR_ADD_LIST}</button>
</div>

<div id="add_list_dialog">
	<form name="add_list" action="settings.php?plugin=mailman" method="post">
		<table>
			<thead>
			<tr>
				<th colspan="2">{TR_MAIL_LIST}</th>
			</tr>
			</thead>
			<tbody>
			<tr>
				<td><label for="list">{TR_LIST}</label></td>
				<td><input type="text" id="list" name="list" value="{LIST}" /></td>
			</tr>
			<tr>
				<td><label for="admin_name">{TR_ADMIN_NAME}</label></td>
				<td><input type="text" id="admin_name" name="admin_name" value="{ADMIN_NAME}" /></td>
			</tr>
			<tr>
				<td><label for="admin_password">{TR_ADMIN_PASSWORD}</label></td>
				<td><input type="password" id="admin_password" name="admin_password" value="" /></td>
			</tr>
			<tr>
				<td><label for="list_url">{TR_URL}</label></td>
				<td><input type="text" id="list_url" name="list_url" value="{LIST_URL}" /></td>
			</tr>
			</tbody>
		</table>
	</form>
</div>
