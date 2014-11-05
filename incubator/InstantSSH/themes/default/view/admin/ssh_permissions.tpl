
<link href="/InstantSSH/themes/default/assets/css/instant_ssh.css?v={INSTANT_SSH_ASSET_VERSION}" rel="stylesheet" type="text/css"/>
<div id="page">
	<p class="hint" style="font-variant: small-caps;font-size: small;">
		<?= self::escapeHtml(tr('This is the list of customers which are allowed to add their SSH keys to login on the system using SSH.', true));?>
	</p>
	<br/>
	<table class="datatable firstColFixed">
		<thead>
		<tr>
			<th><?= self::escapeHtml(tr('Customer name', true));?></th>
			<th><?= self::escapeHtml(tr('Max Keys', true));?></th>
			<th><?= self::escapeHtml(tr('Authentication options', true));?></th>
			<th><?= self::escapeHtml(tr('Restricted shell', true));?></th>
			<th><?= self::escapeHtml(tr('Status', true));?></th>
			<th><?= self::escapeHtml(tr('Actions', true));?></th>
		</tr>
		</thead>
		<tfoot>
		<tr>
			<td><?= self::escapeHtml(tr('Customer name', true));?></td>
			<td><?= self::escapeHtml(tr('Max Keys', true));?></td>
			<td><?= self::escapeHtml(tr('Authentication options', true));?></td>
			<td><?= self::escapeHtml(tr('Restricted shell', true));?></td>
			<td><?= self::escapeHtml(tr('Status', true));?></td>
			<td><?= self::escapeHtml(tr('Actions', true));?></td>
		</tr>
		</tfoot>
		<tbody>
		<tr>
			<td colspan="6"><?= self::escapeHtml(tr('Processing...', true));?></td>
		</tr>
		</tbody>
	</table>
	<div>
		<form name="ssh_permissions_frm" id="ssh_permissions_frm" method="post" enctype="application/x-www-form-urlencoded">
			<table class="firstColFixed">
				<thead>
				<tr>
					<th colspan="2"><?= self::escapeHtml(tr('Add / Edit SSH Permissions', true));?></th>
				</tr>
				</thead>
				<tbody>
				<tr>
					<td><label for="admin_name"><?= self::escapeHtml(tr('Customer name', true));?></label></td>
					<td><input type="text" name="admin_name" id="admin_name" placeholder="<?= self::escapeHtmlAttr(tr('Enter a customer name', true));?>"></td>
				</tr>
				<tr>
					<td>
						<label for="ssh_permission_max_keys">
							<?= self::escapeHtml(tr('Maximum number of SSH keys', true));?><br>
							(<small><?= self::escapeHtml(tr('0 for unlimited', true));?>)</small>
						</label>
					</td>
					<td>
						<input type="text" name="ssh_permission_max_keys" id="ssh_permission_max_keys" placeholder="<?= self::escapeHtmlAttr(tr('Enter a number', true));?>" value="0"/>
					</td>
				</tr>
				<tr>
					<td>
						<label for="ssh_permission_auth_options">
							<?= self::escapeHtml(tr('Can edit authentication options', true));?>
							<span class="icon i_help" title="<?= self::escapeHtmlAttr(tr('See man authorized_keys for further details about authentication options.', true));?>">&nbsp;</span>
						</label>
					</td>
					<td>
						<input type="checkbox" name="ssh_permission_auth_options" id="ssh_permission_auth_options" value="1"/>
					</td>
				</tr>
				<tr>
					<td>
						<label for="ssh_permission_jailed_shell">
							<?= tr('Restricted shell');?>
							<span class="icon i_help" title="<?= self::escapeHtmlAttr(tr('Does the shell access must be provided in restricted environment (recommended)?', true));?>">&nbsp;</span>
						</label>
					</td>
					<td>
						<input type="checkbox" name="ssh_permission_jailed_shell" id="ssh_permission_jailed_shell" value="1" />
					</td>
				</tr>
				<tr>
					<td colspan="2" style="text-align: right;">
						<button data-action="add_ssh_permissions"><?= self::escapeHtml(tr('Save', true));?></button>
						<input type="hidden" id="ssh_permission_id" name="ssh_permission_id" value="0"/>
						<input type="reset" value="<?= self::escapeHtml(tr('Cancel', true));?>"/>
					</td>
				</tr>
				</tbody>
			</table>
		</form>
	</div>
</div>
<script>
	var oTable;

	function flashMessage(type, message) {
		$('<div />', { "class": "flash_message " + type, "text": message, "hide": true }).prependTo("#page")
			.hide().fadeIn("fast").delay(5000).fadeOut("normal", function() { $(this).remove(); });
	}

	function doRequest(rType, action, data) {
		return $.ajax({
			dataType: "json",
			type: rType,
			url: "/admin/ssh_permissions?action=" + action,
			data: data,
			timeout: 5000
		});
	}

	$(document).ready(function () {
		jQuery.fn.dataTableExt.oApi.fnProcessingIndicator = function (oSettings, onoff) {
			if (typeof(onoff) == "undefined") {
				onoff = true;
			}

			this.oApi._fnProcessingDisplay(oSettings, onoff);
		};

		oTable = $(".datatable").dataTable({
			oLanguage: {DATATABLE_TRANSLATIONS},
			iDisplayLength: 5,
			bProcessing: true,
			bServerSide: true,
			pagingType: "simple",
			sAjaxSource: "/admin/ssh_permissions?action=get_ssh_permissions_list",
			bStateSave: true,
			aoColumnDefs: [ { bSortable: false, bSearchable: false, aTargets: [ 5 ] } ],
			aoColumns: [
				{ mData: "admin_name" },
				{ mData: "ssh_permission_max_keys" },
				{ mData: "ssh_permission_auth_options" },
				{ mData: "ssh_permission_jailed_shell" },
				{ mData: "ssh_permission_status" },
				{ mData: "ssh_permission_actions" }
			],
			fnServerData: function (sSource, aoData, fnCallback) {
				$.ajax({
					dataType: "json",
					type: "GET",
					url: sSource,
					data: aoData,
					success: fnCallback,
					timeout: 3000,
					error: function (xhr, textStatus, error) {
						oTable.fnProcessingIndicator(false);
					}
				}).done(function () {
					oTable.find("span").imscpTooltip({ extraClass: "tooltip_icon tooltip_notice" });
				});
			}
		});

		$("#admin_name").autocomplete({
			source: "/admin/ssh_permissions?action=search_customer",
			minLength: 2,
			delay: 500,
			autoFocus: true,
			change: function (event, ui) {
				if (!ui.item) {
					this.value = '';
					flashMessage("warning", "<?= self::escapeJs(tr('Unknown customer. Please enter a valid customer name.', true));?>");
				}
			}
		});

		var $page = $("#page");

		$page.on("click", "input:reset,span[data-action]", function () {
			$("#admin_name").prop("readonly", false).val("");
			$("#ssh_permission_max_keys").val("0");
			$("#ssh_permission_auth_options").prop("checked", false);
			$("#ssh_permission_jailed_shell").prop("checked", false);
			$("#ssh_permission_id").val("0");
		});

		$page.on("click", "span[data-action],button", function (e) {
			e.preventDefault();

			action = $(this).data('action');

			switch (action) {
				case "add_ssh_permissions":
					if($("#admin_name").val() != '') {
						doRequest('POST', action, $("#ssh_permissions_frm").serialize()).done(function (data) {
							$("input:reset").trigger("click");
							flashMessage('success', data.message);
							oTable.fnDraw();
						});
					}
					break;
				case "edit_ssh_permissions":
					doRequest(
						"GET", "get_ssh_permissions", { ssh_permission_id: $(this).data("ssh-permission-id") }
					).done(function (data) {
							$("#admin_name").val(data.admin_name).prop("readonly", true);
							$("#ssh_permission_max_keys").val(data.ssh_permission_max_keys);
							$("#ssh_permission_auth_options").prop("checked", (data.ssh_permission_auth_options > 0));
							$("#ssh_permission_jailed_shell").prop("checked", (data.ssh_permission_jailed_shell > 0));
							$("#ssh_permission_id").val(data.ssh_permission_id);
						});
					break;
				case "delete_ssh_permissions":
					if (confirm("<?= self::escapeJs(tr('Are you sure you want to revoke SSH permissions for this customer?', true));?>")) {
						doRequest(
							"POST", "delete_ssh_permissions", { ssh_permission_id: $(this).data("ssh-permission-id") }
						).done(function (data) {
							oTable.fnDraw();
							flashMessage("success", data.message);
						});
					}
					break;
				default:
					flashMessage("error", "<?= self::escapeJs(tr('Unknown action.', true));?>");
			}
		});

		$(document).ajaxStart(function () { oTable.fnProcessingIndicator();});
		$(document).ajaxStop(function () { oTable.fnProcessingIndicator(false);});
		$(document).ajaxError(function (e, jqXHR, settings, exception) {
			if(jqXHR.status == 403) {
				window.location.href = "/index.php";
			} else if (jqXHR.responseJSON != "") {
				flashMessage("error", jqXHR.responseJSON.message);
			} else if (exception == "timeout") {
				flashMessage("error", "<?= self::escapeJs(tr('Request Timeout: The server took too long to send the data.', true));?>");
			} else {
				flashMessage("error", "<?= self::escapeJs(tr('An unexpected error occurred.', true));?>");
			}
		});
	});
</script>
