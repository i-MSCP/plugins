
<label>
	<select name="service_name" id="service_name">
		<option value="" selected="selected" disabled="disabled">{TR_SERVICE_NAME}</option>
		<!-- BDP: service_name -->
		<option value="{SERVICE_NAME}">{SERVICE_NAME}</option>
		<!-- BDP: service_name -->
	</select>
</label>

<div id="service_templates">
	<table class="datatable">
		<thead>
		<tr>
			<th>{TR_TEMPLATE_NAME}</th>
			<th>{TR_TEMPLATE_DEFAULT}</th>
			<th>{TR_ACTIONS}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td>{TEMPLATE_NAME}</td>
			<td><input type="checkbox" name="{TEMPLATE_GROUP}[]" value="{TEMPLATE_ID}"></td>
			<td>
				<a href="template_editor.php?action=new">{TR_NEW}</a>
				<a href="template_editor.php?action=edit">{TR_EDIT}</a>
				<a href="template_editor.php?action=delete">{TR_DELETE}</a>
			</td>
		</tr>
		</tbody>
	</table>
</div>
