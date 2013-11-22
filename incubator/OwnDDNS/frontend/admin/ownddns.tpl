<form name="editFrm" method="post" action="ownddns.php?action=change">
<table class="firstColFixed">
	<thead>
	<tr>
		<th colspan="2">{TR_OWNDDNS_SETTINGS}</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td>{TR_OWNDDNS_DEBUG}</td>
		<td>
			<div class="radio">
				<input type="radio" name="debug" id="debug_yes" value="yes" {OWNDDNS_DEBUG_YES} />
				<label for="debug_yes">{TR_YES}</label>
				<input type="radio" name="debug" id="debug_no" value="no" {OWNDDNS_DEBUG_NO}/>
				<label for="debug_no">{TR_NO}</label>
			</div>
		</td>
	</tr>
	<tr>
		<td>{TR_OWNDDNS_BASE64}</td>
		<td>
			<div class="radio">
				<input type="radio" name="use_base64_encoding" id="use_base64_encoding_yes" value="yes" {OWNDDNS_BASE64_YES} />
				<label for="use_base64_encoding_yes">{TR_YES}</label>
				<input type="radio" name="use_base64_encoding" id="use_base64_encoding_no" value="no" {OWNDDNS_BASE64_NO}/>
				<label for="use_base64_encoding_no">{TR_NO}</label>
			</div>
		</td>
	</tr>
	<tr>
		<td><label for="max_allowed_accounts">{TR_MAX_ALLOWED_ACCOUNTS}</label></td>
		<td><input type="text" name="max_allowed_accounts" id="max_allowed_accounts" value="{MAX_ALLOWED_ACCOUNTS}"/></td>
	</tr>
	<tr>
		<td><label for="max_accounts_lenght">{TR_MAX_ACCOUNTS_LENGHT}</label></td>
		<td><input type="text" name="max_accounts_lenght" id="max_accounts_lenght" value="{MAX_ACCOUNTS_LENGHT}"/></td>
	</tr>
	<tr>
		<td><label for="update_repeat_time">{TR_UPDATE_REPEAT_TIME}</label></td>
		<td><input type="text" name="update_repeat_time" id="update_repeat_time" value="{MAX_UPDATE_REPEAT_TIME}"/></td>
	</tr>
	</tbody>
</table>

<div class="buttons">
	<input name="submit" type="submit" value="{TR_UPDATE}"/>
	<a class ="link_as_button" href="ownddns.php">{TR_CANCEL}</a>
</div>
</form>
