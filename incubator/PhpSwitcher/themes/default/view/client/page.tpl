
<p class="hint" style="font-variant: small-caps;font-size: small;">{TR_HINT}</p>

<form name="php_switcher" action="/client/phpswitcher" method="post">
<table>
	<thead>
		<tr>
			<th>{TR_VERSION}</th>
		</tr>
	</thead>
	<tfoot>
	<tr>
		<td>{TR_VERSION}</td>
	</tr>
	</tfoot>
	<tbody>
		<tr>
			<td>
				<label>
					<select name="version_id">
						<!-- BDP: version_option -->
						<option value="{VERSION_ID}"{SELECTED}>{VERSION_NAME}</option>
						<!-- EDP: version_option -->
					</select>
				</label>
			</td>
		</tr>
	</tbody>
</table>
<div class="buttons">
	<input type="submit" value="{TR_UPDATE}">
</div>
</form>
