
<link href="/CronJobs/themes/default/assets/css/cronjobs.css?v={CRONJOBS_ASSET_VERSION}" rel="stylesheet"
	  type="text/css"/>

<div id="page">
	<div class="message info">
		Configuring cron jobs requires distinct knowledge of the crontab syntax on Unix based systems. More information
		about this topic can be obtained on the following webpage:
		<a target="_blank" href="http://www.unixgeeks.org/security/newbie/unix/cron-1.html">
			<strong>Newbie: Intro to cron</strong>
		</a>.
	</div>

	<table class="datatable">
		<thead>
		<tr>
			<th>Job Id</th>
			<th>Job Type</th>
			<th>Job TimeDate</th>
			<th>Job User</th>
			<th>Job Command</th>
			<th>Job Status</th>
			<th>Job Actions</th>
		</tr>
		</thead>
		<tfoot>
		<tr>
			<td style="width:20%">Job Id</td>
			<td style="width:10%">Job Type</td>
			<td style="width:10%">Job TimeDate</td>
			<td style="width:10%">Job User</td>
			<td style="width:40%">Job Command</td>
			<td style="width:10%">Job Status</td>
			<td style="width:10%">Job Actions</td>
		</tr>
		</tfoot>
		<tbody>
		<tr>
			<td colspan="7">Processing...</td>
		</tr>
		</tbody>
	</table>

	<form name="cron_job_frm" id="cron_job_frm">
		<table class="firstColFixed">
			<thead>
			<tr>
				<th colspan="2">Add / Edit Cron Job</th>
			</tr>
			</thead>
			<tbody>
			<tr>
				<td><label for="cron_job_minute">Minute</label></td>
				<td>
					<input type="text" name="cron_job_minute" id="cron_job_minute" value="*" />
					<div><small>Minutes (0-59) at which the cron job should be executed.</small></div>
				</td>
			</tr>
			<tr>
				<td><label for="cron_job_hour">Hour</label></td>
				<td>
					<input type="text" name="cron_job_hour" id="cron_job_hour" value="*" />
					<div><small>Hours (0-23) at which the cron job should be executed.</small></div>
				</td>
			</tr>
			<tr>
				<td><label for="cron_job_dmonth">Day of month</label></td>
				<td>
					<input type="text" name="cron_job_dmonth" id="cron_job_dmonth" value="*" />
					<div><small>Day of the month (1-31) in which the cron job should be executed.</small></div>
				</td>
			</tr>
			<tr>
				<td><label for="cron_job_month">Month</label></td>
				<td>
					<input type="text" name="cron_job_month" id="cron_job_month" value="*" />
					<div><small>Month (1-12, or jan-dec) in which the cron job should be executed.</small></div>
				</td>
			</tr>
			<tr>
				<td><label for="cron_job_dweek">Day of week</label></td>
				<td>
					<input type="text" name="cron_job_dweek" id="cron_job_dweek" value="*" />
					<div><small>Weekday (0-6 with Sunday = 0, or mon-sun) in which the cron job should be executed.</small></div>
				</td>
			</tr>
			<tr>
				<td><label for="cron_job_command">Command</label></td>
				<td>
					<input type="text" name="cron_job_command" id="cron_job_command" class="inputTitle" value=""
						   placeholder="Command to execute..."/>
				</td>
			</tr>
			<tr>
				<td><label for="cron_job_type">Job Type</label></td>
				<td>
					<select name="cron_job_type" id="cron_job_type">
						<!-- BDP: cron_permission_url -->
						<option value="url">Url</option>
						<!-- EDP: cron_permission_url -->
						<!-- BDP: cron_permission_jailed -->
						<option value="jailed">Jailed</option>
						<!-- EDP: cron_permission_jailed -->
						<!-- BDP: cron_permission_full -->
						<option value="full">Full</option>
						<!-- EDP: cron_permission_full -->
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2" style="text-align: right;">
					<button data-action="add_cron_job">Save</button>
					<input type="hidden" id="cron_job_id" name="cron_job_id" value="0" />
					<input type="reset" value="reset"/>
				</td>
			</tr>
			</tbody>
		</table>
	</form>
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
			url: "/client/cron_jobs?action=" + action,
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
			sAjaxSource: "/client/cron_jobs?action=get_cron_jobs_list",
			bStateSave: true,
			//aoColumnDefs: [
			//	{ bSortable: false, bSearchable: false, aTargets: [ 6 ] }
			//],
			aoColumns: [
				{ mData: "cron_job_id" },
				{ mData: "cron_job_type" },
				{ mData: "cron_job_timedate" },
				{ mData: "cron_job_user" },
				{ mData: "cron_job_command" },
				{ mData: "cron_job_status" },
				{ mData: "cron_job_actions" }
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

		$("input:reset").click(function () {
			$("#cron_job_minute, #cron_job_hour, #cron_job_dmonth, #cron_job_month, #cron_job_dweek").val("*");
			$("#cron_job_command").val("");
			$("#cron_job_type").val("url");
			$("#cron_job_id").val("0");
		});

		$("#page").on("click", "span[data-action],button", function (e) {
			e.preventDefault();

			action = $(this).data('action');

			switch (action) {
				case "add_cron_job":
					doRequest('POST', action, $("#cron_job_frm").serialize()).done(function (data) {
						$("input:reset").trigger("click");
						flashMessage('success', data.message);
						oTable.fnDraw();
					});
					break;
				case "edit_cron_job":
					doRequest(
						"GET", "get_cron_job", { cron_job_id: $(this).data('cron-job-id') }
					).done(function (data) {
							$("#cron_job_minute").val(data.cron_job_minute);
							$("#cron_job_hour").val(data.cron_job_hour);
							$("#cron_job_dmonth").val(data.cron_job_dmonth);
							$("#cron_job_month").val(data.cron_job_month);
							$("#cron_job_dweek").val(data.cron_job_dweek);
							$("#cron_job_user").val(data.cron_job_user);
							$("#cron_job_command").val(data.cron_job_command);
							$("#cron_job_type").val(data.cron_job_type);
							$("#cron_job_id").val(data.cron_job_id);
						});
					break;
				case "delete_cron_job":
					if (confirm("Are you sure you want to delete this cron job?")) {
						doRequest(
							"POST", 'delete_cron_job', { cron_job_id: $(this).data('cron-job-id') }
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
