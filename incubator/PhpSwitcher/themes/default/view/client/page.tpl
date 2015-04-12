
<div class="flash_message" style="display: none;"></div>
<p class="hint" style="font-variant: small-caps;font-size: small;">{TR_HINT}</p>
<br>
<form>
<table class="datatable">
	<thead>
	<tr>
		<th style="width: 20%">{TR_DOMAIN_NAME}</th>
		<th style="20%">{TR_VERSION}</th>
		<!-- BDP: phpinfo_header -->
		<th>{TR_VERSION_INFO}</th>
		<!-- EDP: phpinfo_header -->
		<th>{TR_STATUS}</th>
	</tr>
	</thead>
	<tfoot>
	<tr>
		<td>{TR_DOMAIN_NAME}</td>
		<td>{TR_VERSION}</td>
		<!-- BDP: phpinfo_footer -->
		<td>{TR_VERSION_INFO}</td>
		<!-- EDP: phpinfo_footer -->
		<td>{TR_STATUS}</td>
	</tr>
	</tfoot>
	<tbody>
	<!-- BDP: domain_php_version -->
	<tr>
		<td>
			{DOMAIN_NAME_UNICODE}
		</td>
		<td>
			<label>
				<select data-domain-name="{DOMAIN_NAME}" data-domain-type="{DOMAIN_TYPE}"
						data-current-version-id="{CURRENT_PHP_VERSION_ID}" name="version_id"{PHP_VERSION_DISABLED}>
					<!-- BDP: php_version_option -->
					<option value="{PHP_VERSION_ID}"{PHP_VERSION_SELECTED}>{PHP_VERSION_NAME}</option>
					<!-- EDP: php_version_option -->
				</select>
			</label>
		</td>
		<!-- BDP: phpinfo_body -->
		<td>
			<a href="/client/phpswitcher/phpinfo?version_id={CURRENT_PHP_VERSION_ID}">{TR_SHOW_INFO}</a>
		</td>
		<!-- EDP: phpinfo_body -->
		<td>
			{DOMAIN_STATUS}
		</td>
	</tr>
	<!-- EDP: domain_php_version -->
	</tbody>
</table>
</form>

<script>
	function flashMessage(type, message)
	{
		var flashMessage = $(".flash_message").text(message).addClass(type);
		setTimeout(function () { flashMessage.fadeOut(1000); }, 3000);
		setTimeout(function () { flashMessage.removeClass(type); }, 4000);
		flashMessage.show();
	}

	$(function() {
		var $dataTable = $(".datatable").dataTable({
			language: imscp_i18n.PhpSwitcher.datatable,
			displayLength: 5,
			stateSave: true,
			pagingType: "simple",
			columnDefs: [ { sortable: false, searchable: false, targets: [ 1, 2 ] } ]
		});

		<!-- BDP: phpinfo_js -->
		$dataTable.on("click", 'a', function (e) {
			var $dialog = $('<div id="dialog-phpinfo" style="overflow: hidden;"/>').append($('<iframe scrolling="auto" height="100%"/>').
					attr("src", $(this).attr("href"))).dialog(
					{
						width: "70%",
						height: $(window).height() * 60 / 100,
						autoOpen: false,
						modal: true,
						title: "PHP Info",
						buttons: [
							{
								text: imscp_i18n.PhpSwitcher.close, click: function () { $(this).dialog('close'); }
							}
						],
						close: function () { $(this).remove(); }
					}
			);

			$(window).resize(function () {
				$dialog.dialog("option", "position", { my: "center", at: "center", of: window });
			});

			$(window).scroll(function () {
				$dialog.dialog("option", "position", { my: "center", at: "center", of: window });
			});

			$dialog.dialog('open');

			return false;
		});
		<!-- EDP: phpinfo_js -->

		$('select').on('change', function() {
			var $select = $(this);
			var data = $select.serializeArray();
			data.push({ name: "domain_name", value: $select.data('domain-name')});
			data.push({ name: "domain_type", value: $select.data('domain-type')});

			$.post("/client/phpswitcher", data, null, 'json')
				.done(function() {
						$(".datatable select").attr('disabled', true);
						window.location.replace("/client/phpswitcher");
				})
				.fail(function(jqXHR) {
						$select.val($select.data('current-version-id'));
						flashMessage('error', jqXHR.responseJSON.message);
				});

			return false;
		});
	});
</script>
