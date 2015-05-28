
<link href="/CronJobs/themes/default/assets/css/cronjobs.css?v={CRONJOBS_ASSET_VERSION}" rel="stylesheet">
<div id="page">
	<p class="hint" style="font-variant: small-caps;font-size: small;">
		<?= self::escapeHtml(tr('List of resellers which are allowed to give cron job permissions to their customers.'));?>
	</p>
	<br/>
	<table class="datatable firstColFixed">
		<thead>
		<tr>
			<th><?= self::escapeHtml(tr('Reseller name'));?></th>
			<th><?= self::escapeHtml(tr('Cron jobs type'));?></th>
			<th><?= self::escapeHtml(tr('Cron jobs frequency'));?></th>
			<th><?= self::escapeHtml(tr('Status'));?></th>
			<th><?= self::escapeHtml(tr('Actions'));?></th>
		</tr>
		</thead>
		<tfoot>
		<tr>
			<td><?= self::escapeHtml(tr('Reseller name'));?></td>
			<td><?= self::escapeHtml(tr('Cron jobs type'));?></td>
			<td><?= self::escapeHtml(tr('Cron jobs frequency'));?></td>
			<td><?= self::escapeHtml(tr('Status'));?></td>
			<td><?= self::escapeHtml(tr('Actions'));?></td>
		</tr>
		</tfoot>
		<tbody>
		<tr>
			<td colspan="5"><?= self::escapeHtml(tr('Loading data...'));?></td>
		</tr>
		</tbody>
	</table>
	<div>
		<form name="cron_permissions_frm" id="cron_permissions_frm">
			<table class="firstColFixed">
				<thead>
				<tr>
					<th colspan="2"><?= self::escapeHtml(tr('Add / Edit cron job permissions'));?></th>
				</tr>
				</thead>
				<tbody>
				<tr>
					<td><label for="admin_name"><?= self::escapeHtml(tr('Reseller name'));?></label></td>
					<td><input type="text" name="admin_name" id="admin_name" placeholder="<?= self::escapeHtmlAttr(tr('Enter a reseller name'));?>"></td>
				</tr>
				<tr>
					<td>
						<label for="cron_permission_type">
							<?= self::escapeHtml(tr('Cron jobs type'));?>
							<span class="icon i_help" title="<?= self::escapeHtmlAttr(tr('Type of allowed cron jobs. Note that the Url cron jobs are always available, whatever the selected type.'));?>">&nbsp;</span>
						</label>
					</td>
					<td>
						<select name="cron_permission_type" id="cron_permission_type">
							<option value="url"><?= self::escapeHtml(tr('Url'));?></option>
							<!-- BDP: cron_permission_jailed -->
							<option value="jailed"><?= self::escapeHtml(tr('Jailed'));?></option>
							<!-- EDP: cron_permission_jailed -->
							<option value="full"><?= self::escapeHtml(tr('Full'));?></option>
						</select>
					</td>
				</tr>
				<tr>
					<td>
						<label for="cron_permission_frequency">
							<?= self::escapeHtml(tr('Cron jobs frequency'));?>
							<span class="icon i_help" title="<?= self::escapeHtmlAttr(tr('Minimum time interval between each cron job execution.'));?>">&nbsp;</span>
							<br>
							( <small><?= self::escapeHtml(tr('In minutes'));?></small> )
						</label>
					</td>
					<td>
						<input type="text" name="cron_permission_frequency" id="cron_permission_frequency" value="5" maxlength="5">
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<div class="buttons">
							<button data-action="add_cron_permissions"><?= self::escapeHtml(tr('Save'));?></button>
							<input type="hidden" id="cron_permission_id" name="cron_permission_id" value="0">
							<input type="hidden" id="cron_permission_admin_id" name="cron_permission_admin_id" value="0">
							<input type="reset" value="<?= self::escapeHtmlAttr(tr('Cancel'));?>">
						</div>
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
				url: "/admin/cronjobs_permissions?action=" + action,
				data: data,
				timeout: 5000
			});
		}

		jQuery.fn.dataTableExt.oApi.fnProcessingIndicator = function (settings, onoff) {
			if (typeof(onoff) == "undefined") {
				onoff = true;
			}

			this.oApi._fnProcessingDisplay(settings, onoff);
		};

		$dataTable = $(".datatable").dataTable({
			language: imscp_i18n.CronJobs.datatable,
			displayLength: 5,
			processing: true,
			serverSide: true,
			pagingType: "simple",
			ajaxSource: "/admin/cronjobs_permissions?action=get_cron_permissions_list",
			stateSave: true,
			sortMulti: false,
			order: [[ 1, "desc" ]],
			columnDefs: [
				{ sortable: false, searchable: false, targets: [ 4 ] }
			],
			columns: [
				{ data: "admin_name" },
				{ data: "cron_permission_type" },
				{ data: "cron_permission_frequency" },
				{ data: "cron_permission_status" },
				{ data: "cron_permission_actions" }
			],
			serverData: function (source, data, callback) {
				$.ajax({
					dataType: "json",
					type: "GET",
					url: source,
					data: data,
					success: callback,
					timeout: 3000
				}).done(function () {
					$dataTable.find("span").tooltip({ tooltipClass: "ui-tooltip-notice", track: true });
				}).fail(function(jqXHR) {
					$dataTable.fnProcessingIndicator(false);
					flashMessage('error', $.parseJSON(jqXHR.responseText).message);
				});
			}
		});

		$("#admin_name").autocomplete({
			source: "/admin/cronjobs_permissions?action=search_reseller",
			minLength: 1,
			delay: 500,
			autoFocus: true,
			change: function (event, ui) {
				if (!ui.item) {
					this.value = "";
					flashMessage("warning", "<?= self::escapeJs(tr('Unknown reseller. Please enter a valid reseller name.'));?>");
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
						case "add_cron_permissions":
							if($("#admin_name").val() != '') {
								doRequest("POST", action, $("#cron_permissions_frm").serialize()).done(
										function (data, textStatus, jqXHR) {
											$("input:reset").click();
											flashMessage((jqXHR.status == 200) ? "success" : "info", data.message);
											$dataTable.fnDraw();
										}
								);
							} else {
								flashMessage("error", "<?= self::escapeJs(tr('Please enter a reseller name.'));?>");
							}
							break;
						case "edit_cron_permissions":
							doRequest(
									"GET", "get_cron_permissions", { cron_permission_id: $(this).data("cron-permission-id") }
							).done(function (data) {
										$("#admin_name").val(data.admin_name).prop("readonly", true);
										$("#cron_permission_type").val(data.cron_permission_type);
										$("#cron_permission_frequency").val(data.cron_permission_frequency);
										$("#cron_permission_id").val(data.cron_permission_id);
										$("#cron_permission_admin_id").val(data.cron_permission_admin_id);
									});
							break;
						case "delete_cron_permissions":
							if (confirm("<?= self::escapeJs(tr('Are you sure you want to revoke the cron job permissions for this reseller?'));?>")) {
								doRequest(
										"POST", "delete_cron_permissions", {
											cron_permission_id: $(this).data('cron-permission-id'),
											cron_permission_admin_id: $(this).data('cron-permission-admin-id')
										}
								).done(function (data) {
											$dataTable.fnDraw();
											flashMessage("success", data.message);
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
