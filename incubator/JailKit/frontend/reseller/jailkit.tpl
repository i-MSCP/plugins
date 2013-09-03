<!-- BDP: jailkit_list -->
<form action="jailkit.php" method="post" name="activate_domain" id="activate_domain">
	<label>
		<select name="domain_id">
			<option value="-1">{TR_JAILKIT_SELECT_NAME_NONE}</option>
			<!-- BDP: jailkit_select_item -->
			<option value="{TR_JAILKIT_SELECT_VALUE}">{TR_JAILKIT_SELECT_NAME}</option>
			<!-- EDP: jailkit_select_item -->
		</select>
	</label>
	<input type="hidden" name="action" value="activate"/>
	
	<div class="buttons" style="display:inline">
		<input name="Submit" type="submit" value="{TR_SHOW}"/>
	</div>
</form>
<!-- BDP: jailkit_customer_list -->
<table>
	<thead>
	<tr>
		<th>{TR_JAILKIT_STATUS}</th>
		<th>{TR_JAILKIT_DOMAIN_NAME}</th>
		<th>{TR_JAILKIT_LOGIN_LIMIT}</th>
		<th>{TR_JAILKIT_ACTIONS}</th>
	</tr>
	</thead>
	<tfoot>
	<tbody>
	<!-- BDP: jailkit_customer_item -->
	<tr>
		<td>
			<a href="#" onclick="action_status('{JAILKIT_DOMAIN_ID}', '{JAILKIT_DOMAIN_NAME}'); return false;"
				class="icon i_{STATUS_ICON}">{JAILKIT_STATUS}</a>
		</td>
		<td>{JAILKIT_DOMAIN_NAME}</td>
		<td>{JAILKIT_LOGIN_LIMIT}</td>
		<td>
			<a class="icon i_edit" href="jailkit.php?action=edit&amp;domain_id={JAILKIT_DOMAIN_ID}">{TR_EDIT_JAIL}</a>
			<a class="icon i_delete deactivate_jailkit" href="jailkit.php?action=deactivate&amp;domain_id={JAILKIT_DOMAIN_ID}">{TR_DELETE_JAIL}</a>
		</td>
	</tr>
	<!-- EDP: jailkit_customer_item -->
	</tbody>
</table>
<br />

<div class="paginator">
	<!-- BDP: scroll_prev -->
	<a class="icon i_prev" href="jailkit.php?psi={PREV_PSI}" title="{TR_PREVIOUS}">{TR_PREVIOUS}</a>
	<!-- EDP: scroll_prev -->
	<!-- BDP: scroll_prev_gray -->
	<a class="icon i_prev_gray" href="#"></a>
	<!-- EDP: scroll_prev_gray -->
	<!-- BDP: scroll_next_gray -->
	<a class="icon i_next_gray" href="#"></a>
	<!-- EDP: scroll_next_gray -->
	<!-- BDP: scroll_next -->
	<a class="icon i_next" href="jailkit.php?psi={NEXT_PSI}" title="{TR_NEXT}">{TR_NEXT}</a>
	<!-- EDP: scroll_next -->
</div>
<!-- EDP: jailkit_customer_list -->

<!-- BDP: jailkit_no_customer_item -->
<table>
	<thead>
	<tr>
		<th>{TR_JAILKIT_STATUS}</th>
		<th>{TR_JAILKIT_DOMAIN_NAME}</th>
		<th>{TR_JAILKIT_ACTIONS}</th>
	</tr>
	</thead>
	<tfoot>
	<tr>
		<td colspan="3">{TR_JAILKIT_NO_DOMAIN}</td>
	</tr>
	</tfoot>
	<tbody>
	<tr>
		<td colspan="3"><div class="message info">{JAILKIT_NO_DOMAIN}</div></td>
	</tr>
	</tbody>
</table>
<!-- EDP: jailkit_no_customer_item -->

<script>
/*<![CDATA[*/
	$(document).ready(function(){
		$(".deactivate_jailkit").click(function(){
			return confirm("{DEACTIVATE_DOMAIN_ALERT}");
		});
	});
	
	function action_status(dom_id, dmn_name) {
		if (!confirm(sprintf("{DISABLE_DOMAIN_ALERT}", dmn_name))) {
			return false;
		}

		location = ("jailkit.php?action=change&domain_id=" + dom_id);
	}
/*]]>*/
</script>
<!-- EDP: jailkit_list -->

<!-- BDP: jailkit_edit -->
<form action="jailkit.php?action=edit&amp;domain_id={JAILKIT_DOMAIN_ID}" method="post" name="edit_jail" id="edit_jail">
	<table class="firstColFixed">
		<thead>
		<tr>
			<th>{TR_JAIL_LIMITS}</th>
			<th>{TR_LIMIT_VALUE}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td><label for="max_logins">{TR_MAX_LOGINS_LIMIT}</label></td>
			<td><input type="text" name="max_logins" id="max_logins" value="{MAX_LOGINS}"/></td>
		</tr>
		</tbody>
	</table>
	
	<div class="buttons">
		<input name="submit" type="submit" value="{TR_UPDATE}"/>
		<input name="cancel" type="button"
			   onclick="MM_goToURL('parent','/reseller/jailkit.php');return document.MM_returnValue" value="{TR_CANCEL}"/>
	</div>
</form>
<!-- EDP: jailkit_edit -->
