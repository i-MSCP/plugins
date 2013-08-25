<form action="opendkim.php" method="post" name="activate_domain" id="activate_domain">
	<select name="domain_id">
		<option value="-1">{TR_OPENDKIM_SELECT_NAME_NONE}</option>
		<!-- BDP: opendkim_select_item -->
		<option value="{TR_OPENDKIM_SELECT_VALUE}">{TR_OPENDKIM_SELECT_NAME}</option>
		<!-- EDP: opendkim_select_item -->
	</select>
	<input type="hidden" name="action" value="activate"/>
	
	<div class="buttons" style="display:inline">
		<input name="Submit" type="submit" value="{TR_SHOW}"/>
	</div>
</form>
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

<div class="buttons">
	<a style="color:#fff" class="deactivate_opendkim" href="opendkim.php?action=deactivate&amp;domain_id={OPENDKIM_DOMAIN_ID}" title="{TR_DEACTIVATE_DOMAIN_TOOLTIP}">
	{TR_OPENDKIM_DEACTIVATE_DOMAIN}
	</a>
</div>
<br />
<!-- EDP: opendkim_customer_item -->

<div class="paginator">
	<!-- BDP: scroll_next_gray -->
	<a class="icon i_next_gray" href="#">&nbsp;</a>
	<!-- EDP: scroll_next_gray -->

	<!-- BDP: scroll_next -->
	<a class="icon i_next" href="opendkim.php?psi={NEXT_PSI}" title="{TR_NEXT}">{TR_NEXT}</a>
	<!-- EDP: scroll_next -->

	<!-- BDP: scroll_prev -->
	<a class="icon i_prev" href="opendkim.php?psi={PREV_PSI}" title="{TR_PREVIOUS}">{TR_PREVIOUS}</a>
	<!-- EDP: scroll_prev -->

	<!-- BDP: scroll_prev_gray -->
	<a class="icon i_prev_gray" href="#">&nbsp;</a>
	<!-- EDP: scroll_prev_gray -->
</div>
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

<script>
/*<![CDATA[*/
	$(document).ready(function(){
		$(".deactivate_opendkim").click(function(){
			if (!confirm("{DEACTIVATE_DOMAIN_ALERT}")){
				return false;
			}
		});
	});

	$(function() {
		$( ".deactivate_opendkim" ).button();
	});
/*]]>*/
</script>