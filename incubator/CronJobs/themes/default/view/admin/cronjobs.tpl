
<link href="/CronJobs/themes/default/assets/css/cronjobs.css?v={CRONJOBS_ASSET_VERSION}" rel="stylesheet" type="text/css"/>

<div id="page">
	<p class="hint" style="font-variant: small-caps;font-size: small;">
		<?= self::escapeHtml(tr('This is the interface from which you can add your own cron jobs. This interface is for administrators only. Customers have their own interface which is more restricted.', true));?>
	</p>
	<br />
	<div class="message static_info">
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
		<tbody>
		<tr>
			<td colspan="7" style="background-color: #b0def5">
				<div class="buttons">
					<button data-action="add_cronjob_dialog"><?= self::escapeHtml(tr('Add new cron job', true));?></button>
				</div>
			</td>
		</tr>
		</tbody>
	</table>
	<div id="dialog_frm">
		<form name="cron_job_frm" id="cron_job_frm">
			<table class="firstColFixed">
				<tbody>
				<tr>
					<td><label for="cron_job_notification"><?= self::escapeHtml(tr('Cron notifications', true));?></label></td>
					<td>
						<input type="text" name="cron_job_notification" id="cron_job_notification" value="{DEFAULT_EMAIL_NOTIFICATION}">
						<div><small><?= self::escapeHtml(tr('Email to which cron notifications must be sent. Leave blank to disable notifications.', true));?></small></div>
					</td>
				</tr>
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
						<input type="text" name="cron_job_command" id="cron_job_command" class="inputTitle" placeholder="<?= self::escapeHtmlAttr(tr('Command to execute...', true));?>"/>
					</td>
				</tr>
				<tr>
					<td><label for="cron_job_type"><?= self::escapeHtml(tr('Type', true));?></label></td>
					<td>
						<select name="cron_job_type" id="cron_job_type">
							<option value="url"><?= self::escapeHtml(tr('Url', true));?></option>
							<option value="full"><?= self::escapeHtml(tr('Full', true));?></option>
						</select>
					</td>
				</tr>
				</tbody>
			</table>
			<input type="hidden" id="cron_job_id" name="cron_job_id" value="0" />
		</form>
	</div>
</div>
<script>
	$(function() {
		var oTable, dialog, flashMessageTarget;

		function flashMessage(type, message) {
			target = (flashMessageTarget) ? flashMessageTarget : ".body";
			$("<div>", { "class": "flash_message " + type, "html": $.parseHTML(message), "hide": true }).prependTo(target).trigger('message_timeout');
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
			aoColumnDefs: [
				{ bSortable: false, bSearchable: false, aTargets: [ 5, 6 ] }
			],
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
					error: function () {
						oTable.fnProcessingIndicator(false);
					}
				}).done(function () {
					if(jQuery.fn.imscpTooltip) {
						oTable.find("span").imscpTooltip({ extraClass: "tooltip_icon tooltip_notice" });
					} else {
						oTable.find("span").tooltip({ tooltipClass: "ui-tooltip-notice", track: true });
					}
				});
			}
		});

		dialog = $("#dialog_frm").dialog({
			autoOpen: false,
			show: "blind",
			hide: "blind",
			height: "auto",
			width: "50%",
			modal: true,
			title: "<?= self::escapeJs(tr('Add / Edit Cron job', true));?>",
			buttons: [
				{
					text: "<?= self::escapeJs(tr('Save', true));?>",
					"data-action": "add_cronjob"
				},
				{
					text: "<?= self::escapeJs(tr('Cancel', true));?>",
					click: function () {
						dialog.dialog("close");
					}
				}
			],
			open: function() {
				flashMessageTarget = "#dialog_frm";
			},
			close: function() {
				$("form")[0].reset();
				$("#dialog_frm .flash_message").remove();
				flashMessageTarget = undefined;
			}
		});

		$("body").
			on("resets", "form", function () { $("input:hidden").val("0"); }).
			on("click", "span[data-action]", function () { $("form")[0].reset(); }).
			on("click", "span[data-action],button[data-action]", function (e) {
				$("button").blur();
				e.preventDefault();

				var action = $(this).data('action');

				switch (action) {
					case "add_cronjob_dialog":
						dialog.dialog("open");
						break;
					case "add_cronjob":
						doRequest('POST', action, $("#cron_job_frm").serialize()).done(function (data) {
							dialog.dialog("close");
							flashMessage('success', data.message);
							oTable.fnDraw();
						});
						break;
					case "edit_cronjob":
						doRequest(
							"GET", "get_cronjob", { cron_job_id: $(this).data('cron-job-id') }
						).done(function (data) {
							$("#cron_job_notification").val(data.cron_job_notification);
							$("#cron_job_minute").val(data.cron_job_minute);
							$("#cron_job_hour").val(data.cron_job_hour);
							$("#cron_job_dmonth").val(data.cron_job_dmonth);
							$("#cron_job_month").val(data.cron_job_month);
							$("#cron_job_dweek").val(data.cron_job_dweek);
							$("#cron_job_user").val(data.cron_job_user);
							$("#cron_job_command").val(data.cron_job_command);
							$("#cron_job_type").val(data.cron_job_type);
							$("#cron_job_id").val(data.cron_job_id);
							dialog.dialog("open");
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

		$(document).
			ajaxStart(function () { oTable.fnProcessingIndicator(); }).
			ajaxStop(function () { oTable.fnProcessingIndicator(false); }).
			ajaxError(function (e, jqXHR, settings, exception) {
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
