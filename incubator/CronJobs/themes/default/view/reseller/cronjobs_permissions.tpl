<link href="/CronJobs/themes/default/assets/css/cronjobs.css?v={CRONJOBS_ASSET_VERSION}" rel="stylesheet"
	  type="text/css" xmlns="http://www.w3.org/1999/html"/>
<div id="page">
	<p class="hint">
		<?= self::escapeHtml(tr('List of customers which are allowed to add cron jobs.', true));?>
	</p>
	<br/>
	<table class="datatable firstColFixed">
		<thead>
		<tr>
			<th><?= self::escapeHtml(tr('Customer name', true));?></th>
			<th><?= self::escapeHtml(tr('Cron jobs type', true));?></th>
			<th><?= self::escapeHtml(tr('Max. cron jobs', true));?></th>
			<th><?= self::escapeHtml(tr('Cron jobs Frequency', true));?></th>
			<th><?= self::escapeHtml(tr('Status', true));?></th>
			<th><?= self::escapeHtml(tr('Actions', true));?></th>
		</tr>
		</thead>
		<tfoot>
		<tr>
			<td><?= self::escapeHtml(tr('Customer name', true));?></td>
			<td><?= self::escapeHtml(tr('Cron job type', true));?></td>
			<td><?= self::escapeHtml(tr('Max. cron jobs', true));?></td>
			<td><?= self::escapeHtml(tr('Cron jobs frequency', true));?></td>
			<td><?= self::escapeHtml(tr('Status', true));?></td>
			<td><?= self::escapeHtml(tr('Actions', true));?></td>
		</tr>
		</tfoot>
		<tbody>
		<tr>
			<td colspan="6"><?= self::escapeHtml(tr('Loading data...', true));?></td>
		</tr>
		</tbody>
	</table>
	<div>
		<form name="cron_permissions_frm" id="cron_permissions_frm">
			<table class="firstColFixed">
				<thead>
				<tr>
					<th colspan="2"><?= self::escapeHtml(tr('Add / Edit cron permissions', true));?></th>
				</tr>
				</thead>
				<tbody>
				<tr>
					<td><label for="admin_name"><?= self::escapeHtml(tr('Customer name', true));?></label></td>
					<td><input type="text" name="admin_name" id="admin_name" placeholder="<?= self::escapeHtmlAttr(tr('Enter a customer name', true));?>"></td>
				</tr>
				<tr>
					<td>
						<label for="cron_permission_type">
							<?= self::escapeHtml(tr('Cron jobs type', true));?>
							<span class="icon i_help" title="<?= self::escapeHtmlAttr(tr('Type of allowed cron jobs. Note that the Url cron jobs are always available, whatever the selected type.', true));?>">&nbsp;</span>
						</label>
					</td>
					<td>
						<select name="cron_permission_type" id="cron_permission_type">
							<option value="url"><?= self::escapeHtml(tr('Url', true));?></option>
							<!-- BDP: cron_permission_jailed -->
							<option value="jailed"><?= self::escapeHtml(tr('Jailed', true));?></option>
							<!-- EDP: cron_permission_jailed -->
							<!-- BDP: cron_permission_full -->
							<option value="full"><?= self::escapeHtml(tr('Full', true));?></option>
							<!-- EDP: cron_permission_full -->
						</select>
					</td>
				</tr>
				<tr>
					<td>
						<label for="cron_permission_max">
							<?= self::escapeHtml(tr('Max. cron jobs', true));?><br>
							( <small><?= self::escapeHtml(tr('0 for unlimited', true));?></small> )
						</label>
					</td>
					<td>
						<input type="text" name="cron_permission_max" id="cron_permission_max" value="0">
					</td>
				</tr>
				<tr>
					<td>
						<label for="cron_permission_frequency">
							<?= self::escapeHtml(tr('Cron jobs frequency', true));?><span class="icon i_help" title="<?= self::escapeHtmlAttr(tr('Minimum time interval between each cron job execution.', true));?>">&nbsp;</span>
							<br>
							( <small><?= self::escapeHtml(tr('In minutes', true));?></small> )
						</label>
					</td>
					<td>
						<input type="text" name="cron_permission_frequency" id="cron_permission_frequency" value="{CRON_PERMISSION_FREQUENCY}">
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<div class="buttons">
							<button data-action="add_cron_permissions"><?= self::escapeHtml(tr('Save', true));?></button>
							<input type="hidden" id="cron_permission_id" name="cron_permission_id" value="0">
							<input type="hidden" id="cron_permission_admin_id" name="cron_permission_admin_id" value="0">
							<input type="reset" value="<?= self::escapeHtmlAttr(tr('Cancel', true));?>">
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
				prependTo(".body").trigger('message_timeout'
			);
		}

		function doRequest(rType, action, data) {
			return $.ajax({
				dataType: "json",
				type: rType,
				url: "/reseller/cronjobs_permissions?action=" + action,
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
			language: {DATATABLE_TRANSLATIONS},
			displayLength: 5,
			processing: true,
			serverSide: true,
			pagingType: "simple",
			ajaxSource: "/reseller/cronjobs_permissions?action=get_cron_permissions_list",
			stateSave: true,
			columnDefs: [ { bSortable: false, bSearchable: false, aTargets: [ 5 ] } ],
			columns: [
				{ mData: "admin_name" },
				{ mData: "cron_permission_type" },
				{ mData: "cron_permission_max" },
				{ mData: "cron_permission_frequency" },
				{ mData: "cron_permission_status" },
				{ mData: "cron_permission_actions" }
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
			source: "/reseller/cronjobs_permissions?action=search_customer",
			minLength: 2,
			delay: 500,
			autoFocus: true,
			change: function (event, ui) {
				if (!ui.item) {
					this.value = "";
					flashMessage("warning", "<?= self::escapeJs(tr('Unknown customer. Please enter a valid customer name.', true));?>");
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
							flashMessage("error", "<?= self::escapeJs(tr('Please enter a customer name.', true));?>");
						}
						break;
					case "edit_cron_permissions":
						doRequest(
							"GET", "get_cron_permissions", { cron_permission_id: $(this).data("cron-permission-id") }
						).done(function (data) {
								$("#admin_name").val(data.admin_name).prop("readonly", true);
								$("#cron_permission_type").val(data.cron_permission_type);
								$("#cron_permission_max").val(data.cron_permission_max);
								$("#cron_permission_frequency").val(data.cron_permission_frequency);
								$("#cron_permission_id").val(data.cron_permission_id);
								$("#cron_permission_admin_id").val(data.cron_permission_admin_id);
							});
						break;
					case "delete_cron_permissions":
						if (confirm("<?= self::escapeJs(tr('Are you sure you want to revoke the cron permissions for this customer?', true));?>")) {
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
						flashMessage("error", "<?= self::escapeJs(tr('Unknown action.', true));?>");
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
					flashMessage("error", "<?= self::escapeJs(tr('Request Timeout: The server took too long to send the data.', true));?>");
				} else {
					flashMessage("error", "<?= self::escapeJs(tr('An unexpected error occurred. Please contact your administrator.', true));?>");
				}
			});
	});
</script>
