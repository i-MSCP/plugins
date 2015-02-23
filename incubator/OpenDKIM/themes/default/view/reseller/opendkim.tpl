
<!-- BDP: select_list -->
<form name="activate_customer" action="opendkim.php" method="post">
	<label>
		<select name="admin_id">
			<option value="-1">{TR_SELECT_NAME}</option>
			<!-- BDP: select_item -->
			<option value="{SELECT_VALUE}">{SELECT_NAME}</option>
			<!-- EDP: select_item -->
		</select>
	</label>
	<div class="buttons" style="display:inline">
		<input type="hidden" name="action" value="activate">
		<input name="submit" type="submit" value="{TR_ACTIVATE_ACTION}">
	</div>
</form>
<!-- EDP: select_list -->

<!-- BDP: customer_list -->
<!-- BDP: customer_item -->
<table>
	<thead>
	<tr>
		<th>{TR_STATUS}</th>
		<th>{TR_DOMAIN_NAME}</th>
		<th>{TR_DNS_NAME}</th>
		<th>{TR_DOMAIN_KEY}</th>
	</tr>
	</thead>
	<tfoot>
	<tr>
		<td colspan="4">{TR_CUSTOMER}</td>
	</tr>
	</tfoot>
	<tbody>
	<!-- BDP: key_item -->
	<tr>
		<td><div class="icon i_{STATUS_ICON}">{KEY_STATUS}<div></td>
		<td><label for="keyid_{OPENDKIM_ID}">{DOMAIN_NAME}</label></td>
		<td>{DNS_NAME}</td>
		<td><textarea id="keyid_{OPENDKIM_ID}" name="opendkim_key" style="width: 95%;height: 80px; resize: none;">{DOMAIN_KEY}</textarea></td>
	</tr>
	<!-- EDP: key_item -->
	</tbody>
</table>

<div class="buttons">
	<a style="color:#fff" class="link_as_button" href="opendkim.php?action=deactivate&amp;admin_id={CUSTOMER_ID}">
		{TR_DEACTIVATE}
	</a>
</div>
<br/>
<!-- EDP: customer_item -->

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
	$(function () {
		$(".link_as_button").on('click', function () {
			return confirm("{DEACTIVATE_DOMAIN_ALERT}");
		});
	});
</script>
<!-- EDP: customer_list -->
