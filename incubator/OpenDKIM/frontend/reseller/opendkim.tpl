<form action="opendkim.php" method="post" name="activate_customer" id="activate_customer">
	<label>
		<select name="admin_id">
			<option value="-1">{TR_OPENDKIM_SELECT_NAME_NONE}</option>
			<!-- BDP: opendkim_select_item -->
			<option value="{TR_OPENDKIM_SELECT_VALUE}">{TR_OPENDKIM_SELECT_NAME}</option>
			<!-- EDP: opendkim_select_item -->
		</select>
	</label>
	<input type="hidden" name="action" value="activate"/>
	
	<div class="buttons" style="display:inline">
		<input name="Submit" type="submit" value="{TR_SHOW}"/>
	</div>
</form>
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
		<td colspan="4">{TR_OPENDKIM_CUSTOMER}</td>
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
		<td><textarea id="keyid_{OPENDKIM_ID}" name="opendkim_key" style="width: 95%;height: 80px; resize: none;">{OPENDKIM_DOMAIN_KEY}</textarea></td>
	</tr>
	<!-- EDP: opendkim_domainkey_item -->
	</tbody>
</table>

<div class="buttons">
	<a style="color:#fff" class="deactivate_opendkim" href="opendkim.php?action=deactivate&amp;admin_id={OPENDKIM_CUSTOMER_ID}" title="{TR_DEACTIVATE_CUSTOMER_TOOLTIP}">
	{TR_OPENDKIM_DEACTIVATE_CUSTOMER}
	</a>
</div>
<br />
<!-- EDP: opendkim_customer_item -->

<div class="paginator">
	<!-- BDP: scroll_prev -->
	<a class="icon i_prev" href="opendkim.php?psi={PREV_PSI}" title="{TR_PREVIOUS}">{TR_PREVIOUS}</a>
	<!-- EDP: scroll_prev -->
	<!-- BDP: scroll_prev_gray -->
	<a class="icon i_prev_gray" href="#"></a>
	<!-- EDP: scroll_prev_gray -->
	<!-- BDP: scroll_next_gray -->
	<a class="icon i_next_gray" href="#"></a>
	<!-- EDP: scroll_next_gray -->
	<!-- BDP: scroll_next -->
	<a class="icon i_next" href="opendkim.php?psi={NEXT_PSI}" title="{TR_NEXT}">{TR_NEXT}</a>
	<!-- EDP: scroll_next -->
</div>

<script>
/*<![CDATA[*/
	$(document).ready(function(){
		$(".deactivate_opendkim").click(function(){
			return confirm("{DEACTIVATE_DOMAIN_ALERT}");
		});
	});

	$(function() {
		$( ".deactivate_opendkim" ).button();
	});
/*]]>*/
</script>
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
