
<link href="/CronJobs/themes/default/assets/css/cronjobs.css?v={CRONJOBS_ASSET_VERSION}" rel="stylesheet" type="text/css"/>

<div id="page">
	<p class="hint" style="font-variant: small-caps;font-size: small;">
		This is the list of resellers which are allowed to give cron permissions to their customers.
	</p>

	<br/>

	<table class="datatable firstColFixed">
		<thead>
		<tr>
			<th>Reseller Name</th>
			<th>Cron Job Type</th>
			<th>Cron Job Frequency</th>
			<th>Status</th>
			<th>Actions</th>
		</tr>
		</thead>
		<tfoot>
		<tr>
			<td>Reseller Name</td>
			<td>Cron Job Type</td>
			<td>Cron Job Frequency</td>
			<td>Status</td>
			<td>Actions</td>
		</tr>
		</tfoot>
		<tbody>
		<tr>
			<td colspan="4">Processing...</td>
		</tr>
		</tbody>
	</table>

	<div id="permissions_dialog">
		<form name="cron_permissions_frm" id="cron_permissions_frm" method="post" enctype="application/x-www-form-urlencoded">
			<table class="firstColFixed">
				<thead>
				<tr>
					<th colspan="2">Add / Edit Cron Permissions</th>
				</tr>
				</thead>
				<tbody>
				<tr>
					<td><label for="admin_name">Reseller name</label></td>
					<td><input type="text" name="admin_name" id="admin_name" placeholder="Enter a reseller name"></td>
				</tr>
				<tr>
					<td>
						<label for="cron_permission_type">
							Job type <span class="icon i_help" title="Allowed job types">&nbsp;</span>
						</label>
					</td>
					<td>
						<select name="cron_permission_type" id="cron_permission_type">
							<option value="url">Url</option>
							<!-- BDP: cron_permission_jailed -->
							<option value="jailed">Jailed</option>
							<!-- EDP: cron_permission_jailed -->
							<option value="full">Full</option>
						</select>
					</td>
				</tr>
				<tr>
					<td>
						<label for="cron_permission_frequency">
							Job frequency <span class="icon i_help" title="Minimum time interval between each job execution">&nbsp;</span>
							<span style="display: block">(In minutes)</span>
						</label>
					</td>
					<td>
						<input type="text" name="cron_permission_frequency" id="cron_permission_frequency" value="5"/>
					</td>
				</tr>
				<tr>
					<td colspan="2" style="text-align: right;">
						<button data-action="add_cron_permissions">Save</button>
						<input type="hidden" id="cron_permission_id" name="cron_permission_id" value="0"/>
						<input type="reset" value="reset"/>
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
		$('<div />', { "class": 'flash_message ' + type, "text": message, "hide": true }).prependTo("#page")
			.hide().fadeIn('fast').delay(5000).fadeOut('normal', function() { $(this).remove(); });
	}

	function doRequest(rType, action, data) {
		return $.ajax({
			dataType: "json",
			type: rType,
			url: "/admin/cron_permissions?action=" + action,
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
			"pagingType": "simple",
			sAjaxSource: "/admin/cron_permissions?action=get_cron_permissions_list",
			bStateSave: true,
			aoColumnDefs: [
				{ bSortable: false, bSearchable: false, aTargets: [ 3, 4 ] }
			],
			aoColumns: [
				{ mData: "admin_name" },
				{ mData: "cron_permission_type" },
				{ mData: "cron_permission_frequency" },
				{ mData: "cron_permission_status" },
				{ mData: "cron_permission_actions" }
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
			source: "/admin/cron_permissions?action=search_reseller",
			minLength: 2,
			delay: 500,
			autoFocus: true,
			change: function (event, ui) {
				if (!ui.item) {
					this.value = '';
					flashMessage("warning", "Unknown reseller. Please enter a valid reseller name.");
				}
			}
		});

		$("input:reset").click(function () {
			$("#admin_name").prop('readonly', false);
			$("#cron_permission_type").val("url");
			$("#cron_permission_frequency").val("5");
			$("#cron_permission_id").val("0");
		});

		$("#page").on("click", "span[data-action],button", function (e) {
			e.preventDefault();

			action = $(this).data('action');

			switch (action) {
				case "add_cron_permissions":
					doRequest('POST', action, $("#cron_permissions_frm").serialize()).done(function (data) {
						$("input:reset").trigger("click");
						flashMessage('success', data.message);
						oTable.fnDraw();
					});
					break;
				case "edit_cron_permissions":
					doRequest(
						"GET", "get_cron_permissions", { cron_permission_id: $(this).data('cron-permission-id') }
					).done(function (data) {
						$("#admin_name").val(data.admin_name).prop('readonly', true);
						$("#cron_permission_type").val(data.cron_permission_type);
						$("#cron_permission_frequency").val(data.cron_permission_frequency);
						$("#cron_permission_id").val(data.cron_permission_id);
					});
					break;
				case "delete_cron_permissions":
					if (confirm("Are you sure you want to revoke cron permissions for this reseller?")) {
						doRequest(
							"POST", 'delete_cron_permissions', { cron_permission_id: $(this).data('cron-permission-id') }
						).done(function (data) {
							oTable.fnDraw();
							flashMessage('success', data.message);
						});
					}
					break;
				default:
					flashMessage('error', "Unknown Action");
			}
		});

		$(document).ajaxStart(function () { oTable.fnProcessingIndicator();});
		$(document).ajaxStop(function () { oTable.fnProcessingIndicator(false);});
		$(document).ajaxError(function (e, jqXHR, settings, exception) {
			if(jqXHR.status == 403) {
				window.location.href = '/index.php';
			} else if (jqXHR.responseJSON != "") {
				flashMessage("error", jqXHR.responseJSON.message);
			} else if (exception == "timeout") {
				flashMessage("error", "Request Timeout: The server took too long to send the data.");
			} else {
				flashMessage("error", "An unexpected error occurred.");
			}
		});
	});
</script>
