
<!-- BDP: opendkim_customer_list -->
<!-- BDP: opendkim_customer_item -->
<table>
	<thead>
	<tr>
		<th>{TR_OPENDKIM_KEY_STATUS}</th>
		<th>{TR_OPENDKIM_DOMAIN_NAME}</th>
		<th>{TR_OPENDKIM_DNS_NAME}</th>
		<th>{TR_OPENDKIM_DOMAIN_KEY}</th>
	</tr>
	</thead>
	<tfoot>
	<tr>
		<td colspan="4">{TR_OPENDKIM_DOMAIN}</td>
	</tr>
	</tfoot>
	<tbody>
	<!-- BDP: opendkim_domainkey_item -->
	<tr>
		<td>
			<div class="icon i_{STATUS_ICON}">{OPENDKIM_KEY_STATUS}<div>
		</td>
		<td><label for="keyid_{OPENDKIM_ID}">{OPENDKIM_DOMAIN_NAME}</label></td>
		<td>{OPENDKIM_DNS_NAME}</td>
		<td><textarea id="keyid_{OPENDKIM_ID}" name="opendkim_key" style="width: 98%;height: 80px; resize: none;">{OPENDKIM_DOMAIN_KEY}</textarea></td>
	</tr>
	<!-- EDP: opendkim_domainkey_item -->
	</tbody>
</table>
<!-- EDP: opendkim_customer_item -->
<!-- EDP: opendkim_customer_list -->

<!-- BDP: opendkim_no_customer_item -->
<table>
	<thead>
	<tr>
		<th>{TR_OPENDKIM_KEY_STATUS}</th>
		<th>{TR_OPENDKIM_DOMAIN_NAME}</th>
		<th>{TR_OPENDKIM_DNS_NAME}</th>
		<th>{TR_OPENDKIM_DOMAIN_KEY}</th>
	</tr>
	</thead>
	<tfoot>
	<tr>
		<td colspan="4">{TR_OPENDKIM_NO_DOMAIN}</td>
	</tr>
	</tfoot>
	<tbody>
	<tr>
		<td colspan="4"><div class="message info">{OPENDKIM_NO_DOMAIN}</div></td>
	</tr>
	</tbody>
</table>
<!-- EDP: opendkim_no_customer_item -->
