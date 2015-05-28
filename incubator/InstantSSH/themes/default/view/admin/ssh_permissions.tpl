
<link href="/InstantSSH/themes/default/assets/css/instant_ssh.css?v={INSTANT_SSH_ASSET_VERSION}" rel="stylesheet">
<div id="page">
	<p class="hint">
		<?= self::escapeHtml(tr('This is the list of resellers which are allowed to give SSH permissions to their customers.'));?>
	</p>
	<br/>
	<table class="datatable firstColFixed">
		<thead>
		<tr>
			<th><?= self::escapeHtml(tr('Reseller name'));?></th>
			<th><?= self::escapeHtml(tr('Can edit authentication options'));?></th>
			<th><?= self::escapeHtml(tr('Restricted shell'));?></th>
			<th><?= self::escapeHtml(tr('Status'));?></th>
			<th><?= self::escapeHtml(tr('Actions'));?></th>
		</tr>
		</thead>
		<tfoot>
		<tr>
			<td><?= self::escapeHtml(tr('Reseller name'));?></td>
			<td><?= self::escapeHtml(tr('Can edit authentication options'));?></td>
			<td><?= self::escapeHtml(tr('Restricted shell'));?></td>
			<td><?= self::escapeHtml(tr('Status'));?></td>
			<td><?= self::escapeHtml(tr('Actions'));?></td>
		</tr>
		</tfoot>
		<tbody>
		<tr>
			<td colspan="5"><?= self::escapeHtml(tr('Processing...'));?></td>
		</tr>
		</tbody>
		<tbody>
		<tr>
			<td colspan="5" style="background-color: #b0def5">
				<div class="buttons">
					<button data-action="rebuild_jails"><?= self::escapeHtml(tr('Rebuild Jails'));?></button>
				</div>
			</td>
		</tr>
		</tbody>
	</table>
	<div>
		<form name="ssh_permissions_frm" id="ssh_permissions_frm">
			<table class="firstColFixed">
				<thead>
				<tr>
					<th colspan="2"><?= self::escapeHtml(tr('Add / Edit SSH Permissions'));?></th>
				</tr>
				</thead>
				<tbody>
				<tr>
					<td><label for="admin_name"><?= self::escapeHtml(tr('Reseller name'));?></label></td>
					<td><input type="text" name="admin_name" id="admin_name" placeholder="<?= self::escapeHtmlAttr(tr('Enter a reseller name'));?>"></td>
				</tr>
				<tr>
					<td>
						<label for="ssh_permission_auth_options">
							<?= self::escapeHtml(tr('Can edit authentication options'));?>
							<span class="icon i_help" title="<?= self::escapeHtmlAttr(tr('See man authorized_keys for further details about authentication options.'));?>">&nbsp;</span>
						</label>
					</td>
					<td>
						<input type="checkbox" name="ssh_permission_auth_options" id="ssh_permission_auth_options" value="1">
					</td>
				</tr>
				<tr>
					<td>
						<label for="ssh_permission_jailed_shell">
							<?= self::escapeHtml(tr('Restricted shell'));?>
							<span class="icon i_help" title="<?= self::escapeHtmlAttr(tr('Does the shell access have to be provided in restricted environment (recommended)?'));?>">&nbsp;</span>
						</label>
					</td>
					<td>
						<input type="checkbox" name="ssh_permission_jailed_shell" id="ssh_permission_jailed_shell" value="1" checked="checked">
					</td>
				</tr>
				<tr>
					<td colspan="2" style="text-align: right;">
						<button data-action="add_ssh_permissions"><?= self::escapeHtml(tr('Save'));?></button>
						<input type="hidden" id="ssh_permission_id" name="ssh_permission_id" value="0">
						<input type="hidden" id="ssh_permission_admin_id" name="ssh_permission_admin_id" value="0">
						<input type="reset" value="<?= self::escapeHtml(tr('Cancel'));?>">
					</td>
				</tr>
				</tbody>
			</table>
		</form>
	</div>
</div>
<script>
	$(function() {
		var $dataTable;

		function flashMessage(type, message) {
			$("<div>", { "class": "flash_message " + type, "html": $.parseHTML(message), "hide": true }).
				prependTo(".body").trigger('message_timeout');
		}

		function doRequest(rType, action, data) {
			return $.ajax({
				dataType: "json",
				type: rType,
				url: "/admin/ssh_permissions?action=" + action,
				data: data,
				timeout: 3000
			});
		}

		jQuery.fn.dataTableExt.oApi.fnProcessingIndicator = function (oSettings, onoff) {
			if (typeof(onoff) == "undefined") {
				onoff = true;
			}

			this.oApi._fnProcessingDisplay(oSettings, onoff);
		};

		$dataTable = $(".datatable").dataTable({
			language: imscp_i18n.InstantSSH.datatable,
			displayLength: 5,
			processing: true,
			serverSide: true,
			pagingType: "simple",
			ajaxSource: "/admin/ssh_permissions?action=get_ssh_permissions_list",
			stateSave: true,
			columnDefs: [ { sortable: false, searchable: false, targets: [ 4 ] } ],
			columns: [
				{ data: "admin_name" },
				{ data: "ssh_permission_auth_options" },
				{ data: "ssh_permission_jailed_shell" },
				{ data: "ssh_permission_status" },
				{ data: "ssh_permission_actions" }
			],
			serverData: function (sSource, aoData, fnCallback) {
				$.ajax({
					dataType: "json",
					type: "GET",
					url: sSource,
					data: aoData,
					success: fnCallback,
					timeout: 3000
				}).done(function () {
					if(jQuery.fn.imscpTooltip) {
						$dataTable.find("span").imscpTooltip({ extraClass: "tooltip_icon tooltip_notice" });
					} else {
						$dataTable.find("span").tooltip({ tooltipClass: "ui-tooltip-notice", track: true });
					}
				}).fail(function(jqXHR) {
					$dataTable.fnProcessingIndicator(false);
					flashMessage('error', $.parseJSON(jqXHR.responseText).message);
				});
			}
		});

		$("#admin_name").autocomplete({
			source: "/admin/ssh_permissions?action=search_reseller",
			minLength: 1,
			delay: 500,
			autoFocus: true,
			change: function (event, ui) {
				if (!ui.item) {
					this.value = "";
					flashMessage("error", "<?= self::escapeJs(tr('Unknown reseller. Please enter a valid reseller name.'));?>");
				}
			}
		});

		$("#page").
			on("click", "input:reset", function () { $("#admin_name").prop("readonly", false); $("input:hidden").val("0"); }).
			on("click", "span[data-action]", function () { $("input:reset").click(); }).
			on("click", "span[data-action],button", function (e) {
				e.preventDefault();

				var action = $(this).data("action");

				switch (action) {
					case "add_ssh_permissions":
						if($("#admin_name").val() != '') {
							doRequest("POST", action, $("#ssh_permissions_frm").serialize()).done(
								function (data, textStatus, jqXHR) {
									$("input:reset").click();
									flashMessage((jqXHR.status == 200) ? "success" : "info", data.message);
									$dataTable.fnDraw();
								}
							);
						} else if(!$(".flash_message").length) {
							flashMessage("error", "<?= self::escapeJs(tr('You must enter a reseller name.'));?>")
						}
						break;
					case "edit_ssh_permissions":
						doRequest(
							"GET",
							"get_ssh_permissions",
							{
								ssh_permission_id: $(this).data("ssh-permission-id"),
								ssh_permission_admin_id: $(this).data("ssh-permission-admin-id"),
								admin_name: $(this).data("admin-name")
							}
						).done(function (data) {
								$("#admin_name").val(data.admin_name).prop("readonly", true);
								$("#ssh_permission_auth_options").prop("checked", (data.ssh_permission_auth_options > 0));
								$("#ssh_permission_jailed_shell").prop("checked", (data.ssh_permission_jailed_shell > 0));
								$("#ssh_permission_id").val(data.ssh_permission_id);
								$("#ssh_permission_admin_id").val(data.ssh_permission_admin_id);
							});
						break;
					case "delete_ssh_permissions":
						if (confirm("<?= self::escapeJs(tr('Are you sure you want to revoke SSH permissions for this reseller?'));?>")) {
							doRequest(
								"POST",
								"delete_ssh_permissions",
								{
									ssh_permission_id: $(this).data("ssh-permission-id"),
									ssh_permission_admin_id: $(this).data("ssh-permission-admin-id"),
									admin_name: $(this).data("admin-name")
								}
							).done(function (data) {
								$dataTable.fnDraw();
								flashMessage("success", data.message);
							});
						}
						break;
					case "rebuild_jails":
						if (confirm("<?= self::escapeJs(tr('Are you sure you want to schedule rebuild of all jails?'));?>")) {
							doRequest(
								"POST", "rebuild_jails", { }
							).done(function (data) {
								if(data.redirect) {
									window.location.replace(data.redirect);
								} else {
									flashMessage("info", data.message);
								}
							});
						}
						break;
					default:
						flashMessage("error", "<?= self::escapeJs(tr('Unknown action.'));?>");
				}
			});

		$(document).
			ajaxStart(function () { $dataTable.fnProcessingIndicator(); }).
			ajaxStop(function () { $dataTable.fnProcessingIndicator(false); }).
			ajaxError(function (e, jqXHR, settings, exception) {
				if(jqXHR.status == 403) {
					window.location.replace("/index.php");
				} else if(jqXHR.status == 409) {
					flashMessage("warning", jqXHR.responseJSON.message);
				} else if (jqXHR.responseJSON !== "undefined") {
					flashMessage("error", jqXHR.responseJSON.message);
				} else if (exception == "timeout") {
					flashMessage("error", "<?= self::escapeJs(tr('Request Timeout: The server took too long to send the data.'));?>");
				} else {
					flashMessage("error", "<?= self::escapeJs(tr('An unexpected error occurred.'));?>");
				}
			});
	});
</script>
