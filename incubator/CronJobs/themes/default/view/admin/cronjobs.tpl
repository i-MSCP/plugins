
<link href="/CronJobs/themes/default/assets/css/cronjobs.css?v={CRONJOBS_ASSET_VERSION}" rel="stylesheet" type="text/css"/>

<div id="page">
	<p class="hint" style="font-variant: small-caps;font-size: small;">
		<?= self::escapeHtml(tr('This is the interface from which you can add your own cron jobs. This interface is for administrators only. Customers have their own interface which is more restricted.', true));?>
	</p>
	<br />
	<div class="message info">
		<?=
			self::escapeHtml(
				tr(
					'Configuring cron jobs requires distinct knowledge of the crontab syntax on Unix based systems. More information about this topic can be obtained on the following webpage:', true
				)
			) . ' <a target="_blank" href="http://www.unixgeeks.org/security/newbie/unix/cron-1.html"><strong>' . self::escapeHtml(tr('Newbie: Intro to cron', true)) . '</strong></a>';
		?>
	</div>
	<table class="datatable">
		<thead>
		<tr>
			<th><?= self::escapeHtml(tr('Id', true));?></th>
			<th><?= self::escapeHtml(tr('Type', true));?></th>
			<th><?= self::escapeHtml(tr('Time/Date', true));?></th>
			<th><?= self::escapeHtml(tr('User', true));?></th>
			<th><?= self::escapeHtml(tr('Command', true));?></th>
			<th><?= self::escapeHtml(tr('Status', true));?></th>
			<th><?= self::escapeHtml(tr('Actions', true));?></th>
		</tr>
		</thead>
		<tfoot>
		<tr>
			<td style="width:5%"><?= self::escapeHtml(tr('Id', true));?></td>
			<td style="width:5%"><?= self::escapeHtml(tr('Type', true));?></td>
			<td style="width:10%"><?= self::escapeHtml(tr('Time/Date', true));?></td>
			<td style="width:10%"><?= self::escapeHtml(tr('User', true));?></td>
			<td style="width:45%"><?= self::escapeHtml(tr('Command', true));?></td>
			<td style="width:15%"><?= self::escapeHtml(tr('Status', true));?></td>
			<td style="width:10%"><?= self::escapeHtml(tr('Actions', true));?></td>
		</tr>
		</tfoot>
		<tbody>
		<tr>
			<td colspan="7"><?= self::escapeHtml(tr('Processing...', true));?></td>
		</tr>
		</tbody>
	</table>
	<form name="cron_job_frm" id="cron_job_frm">
		<table class="firstColFixed">
			<thead>
			<tr>
				<th colspan="2"><?= self::escapeHtml(tr('Add / Edit Cron job', true));?></th>
			</tr>
			</thead>
			<tbody>
			<tr>
				<td><label for="cron_job_minute"><?= self::escapeHtml(tr('Minute', true));?></label></td>
				<td>
					<input type="text" name="cron_job_minute" id="cron_job_minute" value="*" />
					<div><small><?= self::escapeHtml(tr('Minutes (0-59) at which the cron job should be executed.', true));?></small></div>
				</td>
			</tr>
			<tr>
				<td><label for="cron_job_hour"><?= self::escapeHtml(tr('Hour', true));?></label></td>
				<td>
					<input type="text" name="cron_job_hour" id="cron_job_hour" value="*" />
					<div><small><?= self::escapeHtml(tr('Hours (0-23) at which the cron job should be executed.', true));?></small></div>
				</td>
			</tr>
			<tr>
				<td><label for="cron_job_dmonth"><?= self::escapeHtml(tr('Day of month', true));?></label></td>
				<td>
					<input type="text" name="cron_job_dmonth" id="cron_job_dmonth" value="*" />
					<div><small><?= self::escapeHtml(tr('Day of the month (1-31) in which the cron job should be executed.', true));?></small></div>
				</td>
			</tr>
			<tr>
				<td><label for="cron_job_month"><?= self::escapeHtml(tr('Month', true));?></label></td>
				<td>
					<input type="text" name="cron_job_month" id="cron_job_month" value="*" />
					<div><small><?= self::escapeHtml(tr('Month (1-12, or jan-dec) in which the cron job should be executed.', true));?></small></div>
				</td>
			</tr>
			<tr>
				<td><label for="cron_job_dweek"><?= self::escapeHtml(tr('Day of week', true));?></label></td>
				<td>
					<input type="text" name="cron_job_dweek" id="cron_job_dweek" value="*" />
					<div><small><?= self::escapeHtml(tr('Weekday (0-6 with Sunday = 0, or mon-sun) in which the cron job should be executed.', true));?></small></div>
				</td>
			</tr>
			<tr>
				<td><label for="cron_job_user"><?= self::escapeHtml(tr('User', true));?></label></td>
				<td>
					<input type="text" name="cron_job_user" id="cron_job_user" value="root" />
					<div><small><?= self::escapeHtml(tr('User under which command must be executed.', true));?></small></div>
				</td>
			</tr>
			<tr>
				<td><label for="cron_job_command"><?= self::escapeHtml(tr('Command', true));?></label></td>
				<td>
					<input type="text" name="cron_job_command" id="cron_job_command" class="inputTitle" value="" placeholder="<?= self::escapeHtmlAttr(tr('Command to execute...', true));?>"/>
				</td>
			</tr>
			<tr>
				<td><label for="cron_job_type"><?= self::escapeHtml(tr('Type', true));?></label></td>
				<td>
					<select name="cron_job_type" id="cron_job_type">
						<option value="url"><?= self::escapeHtml(tr('Url', true));?></option>
						<!-- BDP: cron_job_jailed -->
						<option value="jailed"><?= self::escapeHtml(tr('Jailed', true));?></option>
						<!-- EDP: cron_job_jailed -->
						<option value="full"><?= self::escapeHtml(tr('Full', true));?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2" style="text-align: right;">
					<button data-action="add_cron_job"><?= self::escapeHtml(tr('Save', true));?></button>
					<input type="hidden" id="cron_job_id" name="cron_job_id" value="0" />
					<input type="reset" value="<?= self::escapeHtmlAttr(tr('Cancel', true));?>"/>
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
			url: "/admin/cronjobs?action=" + action,
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
			sAjaxSource: "/admin/cronjobs?action=get_cronjobs_list",
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

		var $page = $("#page");

		$page.on("click", "input:reset,span[data-action]", function () {
			$("#cron_job_minute, #cron_job_hour, #cron_job_dmonth, #cron_job_month, #cron_job_dweek").val("*");
			$("#cron_job_user").val("root");
			$("#cron_job_command").val("");
			$("#cron_job_type").val("{CRON_JOB_DEFAULT_TYPE}");
			$("#cron_job_id").val("0");
		});

		$page.on("click", "span[data-action],button", function (e) {
			e.preventDefault();

			action = $(this).data('action');

			switch (action) {
				case "add_cronjob":
					doRequest('POST', action, $("#cron_job_frm").serialize()).done(function (data) {
						$("input:reset").trigger("click");
						flashMessage('success', data.message);
						oTable.fnDraw();
					});
					break;
				case "edit_cronjob":
					doRequest(
						"GET", "get_cronjob", { cron_job_id: $(this).data('cron-job-id') }
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
				case "delete_cronjob":
					if (confirm("<?= self::escapeJs(tr('Are you sure you want to delete this cron job?', true));?>")) {
						doRequest(
							"POST", 'delete_cronjob', { cron_job_id: $(this).data('cron-job-id') }
						).done(function (data) {
								oTable.fnDraw();
								flashMessage('success', data.message);
							});
					}
					break;
				default:
					flashMessage('error', "<?= self::escapeJs(tr('Unknown action', true));?>");
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
				flashMessage("error", "<?= self::escapeJs(tr('Request Timeout: The server took too long to send the data.', true));?>");
			} else {
				flashMessage("error", "<?= self::escapeJs(tr('An unexpected error occurred.', true));?>");
			}
		});
	});
</script>
