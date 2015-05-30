
<link href="/CronJobs/themes/default/assets/css/cronjobs.css?v={CRONJOBS_ASSET_VERSION}" rel="stylesheet">
<div id="page">
	<p class="hint">
		<?= self::escapeHtml(tr('This is the interface from which you can add your cron jobs.'));?>
	</p>
	<br />
	<div class="static_warning">
		<?=
			self::escapeHtml(
				tr('Configuring cron jobs requires distinct knowledge of the crontab syntax on Unix based systems. More information about this topic can be obtained on the following webpage:')
			) . ' <a target="_blank" href="http://www.unixgeeks.org/security/newbie/unix/cron-1.html"><strong>' . self::escapeHtml(tr('Newbie: Intro to cron')) . '</strong></a>';
		?>
	</div>
	<table class="datatable">
		<thead>
		<tr>
			<th class="columnMark">&nbsp;</th>
			<th class="columnDate">m</th>
			<th class="columnDate">h</th>
			<th class="columnDate">D</th>
			<th class="columnDate">M</th>
			<th class="columnDate">DoW</th>
			<th class="columnUser"><?= self::escapeHtml(tr('User'));?></th>
			<th class="columnType"><?= self::escapeHtml(tr('Type'));?></th>
			<th class="columnCommand"><?= self::escapeHtml(tr('Command'));?></th>
			<th class="columnStatus"><?= self::escapeHtml(tr('Status'));?></th>
			<th class="columnActions"><?= self::escapeHtml(tr('Actions'));?></th>
		</tr>
		</thead>
		<tfoot>
		<tr>
			<td class="columnMark">&nbsp;</td>
			<td class="columnDate">m</td>
			<td class="columnDate">h</td>
			<td class="columnDate">D</td>
			<td class="columnDate">M</td>
			<td class="columnDate">DoW</td>
			<td class="columnUser"><?= self::escapeHtml(tr('User'));?></td>
			<td class="columnType"><?= self::escapeHtml(tr('Type'));?></td>
			<td class="columnCommand"><?= self::escapeHtml(tr('Command'));?></td>
			<td class="columnStatus"><?= self::escapeHtml(tr('Status'));?></td>
			<td class="columnActions"><?= self::escapeHtml(tr('Actions'));?></td>
		</tr>
		</tfoot>
		<tbody>
		<tr>
			<td colspan="11"><?= self::escapeHtml(tr('Loading data...'));?></td>
		</tr>
		</tbody>
		<tbody>
		<tr>
			<td colspan="11" style="background-color: #b0def5">
				<div class="buttons" style="float: right">
					<button data-action="add_cronjob_dialog">
						<strong><?= self::escapeHtml(tr('Add cron job'));?></strong>
					</button>
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
					<td><label for="cron_job_notification"><?= self::escapeHtml(tr('Email'));?></label></td>
					<td>
						<input type="text" name="cron_job_notification" id="cron_job_notification" value="{DEFAULT_EMAIL_NOTIFICATION}">
						<div>
							<small><?= self::escapeHtml(tr('Email to which cron notifications must be sent if any. Leave blank to disable notifications.'));?></small>
						</div>
					</td>
				</tr>
				<tr>
					<td><label for="cron_job_minute"><?= self::escapeHtml(tr('Minute'));?></label></td>
					<td>
						<input type="text" name="cron_job_minute" id="cron_job_minute" value="*">
						<div><small><?= self::escapeHtml(tr('Minute at which the cron job must be executed.'));?></small></div>
					</td>
				</tr>
				<tr>
					<td><label for="cron_job_hour"><?= self::escapeHtml(tr('Hour'));?></label></td>
					<td>
						<input type="text" name="cron_job_hour" id="cron_job_hour" value="*">
						<div><small><?= self::escapeHtml(tr('Hour at which the cron job must be executed.'));?></small></div>
					</td>
				</tr>
				<tr>
					<td><label for="cron_job_dmonth"><?= self::escapeHtml(tr('Day of month'));?></label></td>
					<td>
						<input type="text" name="cron_job_dmonth" id="cron_job_dmonth" value="*">
						<div><small><?= self::escapeHtml(tr('Day of the month at which the cron job must be executed.'));?></small></div>
					</td>
				</tr>
				<tr>
					<td><label for="cron_job_month"><?= self::escapeHtml(tr('Month'));?></label></td>
					<td>
						<input type="text" name="cron_job_month" id="cron_job_month" value="*">
						<div><small><?= self::escapeHtml(tr('Month at which the cron job must be executed.'));?></small></div>
					</td>
				</tr>
				<tr>
					<td><label for="cron_job_dweek"><?= self::escapeHtml(tr('Day of week'));?></label></td>
					<td>
						<input type="text" name="cron_job_dweek" id="cron_job_dweek" value="*">
						<div><small><?= self::escapeHtml(tr('Day of the week at which the cron job must be executed.'));?></small></div>
					</td>
				</tr>
				<tr>
					<td><label for="cron_job_command"><?= self::escapeHtml(tr('Command'));?></label></td>
					<td>
						<input type="text" name="cron_job_command" id="cron_job_command" class="inputTitle" placeholder="<?= self::escapeHtmlAttr(tr('Command to execute...'));?>">
					</td>
				</tr>
				<tr>
					<td>
						<label for="cron_job_type">
							<?= self::escapeHtml(tr('Command type'));?>
							<span class="icon i_help" title="<?= self::escapeHtmlAttr(tr('Url commands are run via GNU Wget while shell commands are run via shell command interpreter (eg. Dash, Bash...).'));?>">&nbsp;</span>
						</label>
					</td>
					<td>
						<select name="cron_job_type" id="cron_job_type">
							<option value="url"><?= self::escapeHtml(tr('Url'));?></option>
							<!-- BDP: cron_job_shell_type_block -->
							<option value="{CRON_JOB_SHELL_TYPE}"><?= self::escapeHtml(tr('Shell'));?></option>
							<!-- EDP: cron_job_shell_type_block -->
						</select>
					</td>
				</tr>
				</tbody>
			</table>
			<input type="hidden" id="cron_job_id" name="cron_job_id" value="0" tabindex="-1">
		</form>
		<div class="static_info">
			<ul>
				<li>
					<?=
						self::escapeHtml(
							tr(
								'You can learn more about the syntax by reading:', true
							)
						) . ' <a target="_blank" href="http://www.unixgeeks.org/security/newbie/unix/cron-1.html"><strong>' . self::escapeHtml(tr('Newbie: Intro to cron')) . '</strong></a>';
					?>
				</li>
				<li><?= self::escapeHtml(tr('When using a shortcut in the minute time field, all other time/date fields are ignored.'));?></li>
				<li><?= self::escapeHtml(tr('The available shortcuts are: @reboot, @yearly, @annually, @monthly, @weekly, @daily, @midnight and @hourly'));?></li>
				<li><?= self::escapeHtml(tr('Minimum time interval between each cron job execution: %s', '{CRON_PERMISSION_FREQUENCY}'));?></li>
			</ul>
		</div>
	</div>
</div>
<script>
	$(function() {
		var $dataTable, $dialog, flashMessagesTarget;

		function flashMessage(type, message) {
			var target = (flashMessagesTarget) ? flashMessagesTarget : ".body";
			$("<div>", { "class": "flash_message " + type, "html": $.parseHTML(message), "hide": true }).
				prependTo(target).trigger('message_timeout');
		}

		function doRequest(rType, action, data) {
			return $.ajax({
				dataType: "json",  type: rType,  url: "/client/cronjobs?action=" + action,  data: data,  timeout: 5000
			});
		}

		function handleTimedateInputs(val) {
			var $els = $("#cron_job_hour,#cron_job_dmonth,#cron_job_month,#cron_job_dweek");

			if($.inArray(val, ['@reboot', '@yearly', '@annually', '@monthly', '@weekly', '@daily', '@midnight', '@hourly']) >= 0) {
				$els.val("").prop("readonly", true).attr('tabindex', -1);
			} else {
				$els.prop("readonly", false);
				$els.each(function() {
					if($(this).val() === '') {
						$(this).val("*");
					}
				});

				$(":input:visible").each(function(i,e) { $(e).attr("tabindex", i); });
			}
		}

		jQuery.fn.dataTableExt.oApi.fnProcessingIndicator = function (settings, onoff) {
			if (typeof(onoff) == "undefined") {
				onoff = true;
			}

			this.oApi._fnProcessingDisplay(settings, onoff);
		};

		$dataTable = $(".datatable").dataTable({
			language: imscp_i18n.CronJobs.dataTable,
			displayLength: 5,
			processing: true,
			serverSide: true,
			pagingType: "simple",
			ajaxSource: "/client/cronjobs?action=get_cronjobs_list",
			stateSave: true,
			sortMulti: false,
			order: [[ 1, "desc" ]],
			columnDefs: [
				{ sortable: false, searchable: false, targets: [ 0, 10 ] }, { "class": "columnMark", targets: [ 0 ] },
				{ "class": "columnDate", targets: [ 1, 3, 4, 5 ] }, { "class": "columnUser", targets: [ 6 ] },
				{ "class": "columnType", targets: [ 7 ] }, { "class": "columnCommand", targets: [ 8 ] },
				{ "class": "columnStatus", targets: [ 9 ] }, { "class": "columnActions", targets: [ 10 ] }
			],
			columns: [
				{ data: "cron_job_disable_enable" }, { data: "cron_job_minute" }, { data: "cron_job_hour" },
				{ data: "cron_job_dmonth" }, { data: "cron_job_month" }, { data: "cron_job_dweek" },
				{ data: "cron_job_user" }, { data: "cron_job_type" }, { data: "cron_job_command" },
				{ data: "cron_job_status" }, { data: "cron_job_actions" }
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

		$dialog = $("#dialog_frm").dialog({
			autoOpen: false,
			show: "blind",
			hide: "blind",
			height: "auto",
			width: "50%",
			modal: true,
			title: "<?= self::escapeJs(tr('Add / Edit Cron job'));?>",
			buttons: [
				{
					text: "<?= self::escapeJs(tr('Save'));?>",
					"data-action": "add_cronjob",
					click: function() { }
				},
				{
					text: "<?= self::escapeJs(tr('Cancel'));?>",
					click: function () {
						$dialog.dialog("close");
					}
				}
			],
			open: function() {
				flashMessagesTarget = "#dialog_frm";
			},
			close: function() {
				$("form")[0].reset();
				$("#dialog_frm.flash_message").remove();
				flashMessagesTarget = undefined;
			}
		});

		$("body").
			on('keyup', "#cron_job_minute", function() { handleTimedateInputs($(this).val()); }).
			on("reset", "form", function () {
				$("input:hidden").val("0");
				$("#cron_job_hour,#cron_job_dmonth,#cron_job_month,#cron_job_dweek").prop("readonly", false);
				$(":input:visible").each(function(i,e) { $(e).attr("tabindex", i); });
			}).
			on("click", "span[data-action]", function () { $("form")[0].reset(); }).
			on("click", "span[data-action],button[data-action],input[data-action]", function (e) {
				var $instance = $dataTable.find("span").tooltip("instance");
				if($instance) $instance.destroy();

				$("button").blur();
				e.preventDefault();

				var action = $(this).data('action');

				switch (action) {
					case "add_cronjob_dialog":
						$dialog.dialog("open");
						break;
					case "add_cronjob":
						doRequest('POST', action, $("#cron_job_frm").serialize()).done(function (data) {
							$dialog.dialog("close");
							flashMessagesTarget = undefined;
							flashMessage('success', data.message);
							$dataTable.fnDraw();
						});
						break;
					case "edit_cronjob":
						doRequest("GET", "get_cronjob", { cron_job_id: $(this).data('cron-job-id') }).done(
							function (data) {
								$("#cron_job_notification").val(data.cron_job_notification);
								var $cronJobMinute = $("#cron_job_minute").val(data.cron_job_minute);
								$("#cron_job_hour").val(data.cron_job_hour);
								$("#cron_job_dmonth").val(data.cron_job_dmonth);
								$("#cron_job_month").val(data.cron_job_month);
								$("#cron_job_dweek").val(data.cron_job_dweek);
								$("#cron_job_command").val(data.cron_job_command);
								$("#cron_job_type").val(data.cron_job_type);
								$("#cron_job_id").val(data.cron_job_id);
								handleTimedateInputs($cronJobMinute.val());
								$dialog.dialog("open");
						});
						break;
					case "disable_cronjob":
					case "enable_cronjob":
						doRequest("POST", action, { cron_job_id: $(this).data('cron-job-id') }).done(function (data) {
							$dataTable.fnDraw();
							flashMessage('success', data.message);
						});
						break;
					case "delete_cronjob":
						if (confirm("<?= self::escapeJs(tr('Are you sure you want to delete this cron job?'));?>")) {
							doRequest("POST", action, { cron_job_id: $(this).data('cron-job-id') }).done(
								function (data) {
									$dataTable.fnDraw();
									flashMessage('success', data.message);
							});
						}
						break;
					default:
						flashMessage('error', "<?= self::escapeJs(tr('Unknown action.'));?>");
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
					flashMessage("error", "<?= self::escapeJs(tr('An unexpected error occurred. Please contact your reseller.'));?>");
				}
			});
	});
</script>
