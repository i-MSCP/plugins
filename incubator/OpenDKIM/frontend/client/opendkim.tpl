<!-- BDP: opendkim_customer_list -->
<!-- BDP: opendkim_customer_item -->
<table>
	<thead class="ui-widget-header">
	<tr>
		<th>{TR_OPENDKIM_DOMAIN_NAME}</th>
		<th>{TR_OPENDKIM_DOMAIN_KEY}</th>
		<th>{TR_OPENDKIM_KEY_STATUS}</th>
	</tr>
	</thead>
	<tfoot class="ui-widget-header">
	<tr>
		<th colspan="3">{TR_OPENDKIM_DOMAIN}</th>
	</tr>
	</tfoot>
	<tbody class="ui-widget-content">
	<!-- BDP: opendkim_domainkey_item -->
	<tr>
		<td>{OPENDKIM_DOMAIN_NAME}</td>
		<td><textarea id="keyid_{OPENDKIM_id}" name="opendkim_key" style="width: 95%;height: 80px; resize: none;">{OPENDKIM_DOMAIN_KEY}</textarea></td>
		<td>{OPENDKIM_KEY_STATUS}</td>
	</tr>
	<!-- EDP: opendkim_domainkey_item -->
	</tbody>
</table>
<br />
<!-- EDP: opendkim_customer_item -->
<!-- EDP: opendkim_customer_list -->

<!-- BDP: opendkim_no_customer_item -->
<table>
	<thead class="ui-widget-header">
	<tr>
		<th>{TR_OPENDKIM_DOMAIN_NAME}</th>
		<th>{TR_OPENDKIM_DOMAIN_KEY}</th>
		<th>{TR_OPENDKIM_KEY_STATUS}</th>
	</tr>
	</thead>
	<tfoot class="ui-widget-header">
	<tr>
		<th colspan="3">{TR_OPENDKIM_NO_DOMAIN}</th>
	</tr>
	</tfoot>
	<tbody class="ui-widget-content">
	<tr>
		<td colspan="3"><div class="message info">{OPENDKIM_NO_DOMAIN}</div></td>
	</tr>
	</tbody>
</table>
<!-- EDP: opendkim_no_customer_item -->
