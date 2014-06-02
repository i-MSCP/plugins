
<!-- BDP: customer_list -->
<table>
	<thead>
	<tr>
		<th>{TR_KEY_STATUS}</th>
		<th>{TR_DOMAIN_NAME}</th>
		<th>{TR_DNS_NAME}</th>
		<th>{TR_DOMAIN_KEY}</th>
	</tr>
	</thead>
	<tbody>
	<!-- BDP: domainkey_item -->
	<tr>
		<td><div class="icon i_{STATUS_ICON}">{KEY_STATUS}<div></td>
		<td><label for="keyid_{OPENDKIM_ID}">{DOMAIN_NAME}</label></td>
		<td>{DNS_NAME}</td>
		<td>
			<textarea id="keyid_{OPENDKIM_ID}" name="opendkim_key"
					  style="width: 98%;height: 80px; resize: none;">{DOMAIN_KEY}</textarea>
		</td>
	</tr>
	<!-- EDP: domainkey_item -->
	</tbody>
</table>
<!-- EDP: customer_list -->
