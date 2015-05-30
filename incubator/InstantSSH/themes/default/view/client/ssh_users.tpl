
<link href="/InstantSSH/themes/default/assets/css/instant_ssh.css?v={INSTANT_SSH_ASSET_VERSION}" rel="stylesheet">
<div id="page">
	<p class="hint">
		<?= self::escapeHtml(tr('This is the list of SSH users associated with your account.'));?>
	</p>
	<table class="datatable">
		<thead>
		<tr>
			<th><?= self::escapeHtml(tr('SSH user'))?></th>
			<th><?= self::escapeHtml(tr('Key fingerprint'))?></th>
			<th><?= self::escapeHtml(tr('Status'))?></th>
			<th><?= self::escapeHtml(tr('Actions'))?></th>
		</tr>
		</thead>
		<tfoot>
		<tr>
			<td><?= self::escapeHtml(tr('SSH user'))?></td>
			<td><?= self::escapeHtml(tr('Key fingerprint'))?></td>
			<td><?= self::escapeHtml(tr('Status'))?></td>
			<td><?= self::escapeHtml(tr('Actions'))?></td>
		</tr>
		</tfoot>
		<tbody>
		<tr>
			<td colspan="4"><?= self::escapeHtml(tr('Processing...'));?></td>
		</tr>
		</tbody>
	</table>
	<form name="ssh_user_frm" id="ssh_user_frm" method="post" enctype="application/x-www-form-urlencoded">
		<table>
			<thead>
			<tr>
				<th colspan="2"><?= self::escapeHtml(tr('Add / Edit SSH user')) ;?></th>
			</tr>
			</thead>
			<tbody>
			<tr>
				<td style="width:20%;">
					<label for="ssh_user_name"><?= self::escapeHtml(tr('Username'));?></label>
					<span style="float:right;" id="ssh_username_prefix"><strong>{SSH_USERNAME_PREFIX}</strong></span>
				</td>
				<td>
					<input type="text" name="ssh_user_name" id="ssh_user_name" maxlength="8" placeholder="<?= self::escapeHtmlAttr(tr('Enter an username'));?>">
				</td>
			</tr>
			<!-- BDP: ssh_password_field_block -->
			<tr>
				<td><label for="password"><?= self::escapeHtml(tr('Password'));?></label></td>
				<td><input type="password" class="pwd_generator" name="ssh_user_password" id="password" maxlength="32" placeholder="<?= self::escapeHtmlAttr(tr('Enter a password'));?>"></td>
			</tr>
			<tr>
				<td><label for="cpassword"><?= self::escapeHtml(tr('Password confirmation'));?></label></td>
				<td><input type="password" name="ssh_user_cpassword" id="cpassword" maxlength="32" placeholder="<?= self::escapeHtmlAttr(tr('Confirm the password'));?>"></td>
			</tr>
			<!-- EDP: ssh_password_field_block -->
			<tr>
				<td>
					<label for="ssh_user_key">
						<?= tr('SSH key');?>
						<span class="icon i_help" title="<?= self::escapeHtmlAttr(tr('Supported RSA key formats are PKCS#1, openSSH and XML Signature.'));?>">&nbsp;</span>
					</label>
				</td>
				<td>
					<textarea style="height:70px" name="ssh_user_key" id="ssh_user_key" placeholder="<?= self::escapeHtmlAttr(tr('Enter your SSH key'));?>"></textarea>
				</td>
			</tr>
			<!-- BDP: ssh_auth_options_block -->
			<tr>
				<td>
					<label for="ssh_user_auth_options">
						<?= tr('Authentication options');?>
						<span class="icon i_help" title="{TR_ALLOWED_OPTIONS}">&nbsp;</span>
					</label>
				</td>
				<td>
					<textarea style="height: 45px" name="ssh_user_auth_options" id="ssh_user_auth_options" placeholder="<?= self::escapeHtmlAttr(tr('Enter authentication options'));?>">{DEFAULT_AUTH_OPTIONS}</textarea>
				</td>
			</tr>
			<!-- EDP: ssh_auth_options_block -->
			<tr>
				<td colspan="2">
					<table>
						<tr>
							<!-- BDP: ssh_password_key_info_block -->
							<td style="background: #ffffff;">
								<div style="color:#666666;padding:15px 0 15px 45px;background: url(/themes/default/assets/images/messages/info.png) no-repeat 5px 50%;">
									<ul style="list-style-type:none;">
									<li><?= self::escapeHtml(tr("You can provide either a password, an SSH key or both. However, it's recommended to prefer key-based authentication."));?></li>
									<li>
										<?= self::escapeHtml(tr('On Linux, you can generate your rsa key pair by running the following command:'))?>
										<span class="disabled" style="border:1px solid #cccccc;padding: 2px 5px;">ssh-keygen -t rsa -C user@domain.tld</span>
									</li>
									</ul>
								</div>
							</td>
							<!-- EDP: ssh_password_key_info_block -->
							<td style="background:#ffffff;text-align: right;">
								<div id="actions">
									<button id="action" data-action="add_ssh_user"><?= self::escapeHtml(tr('Save'));?></button>
									<input type="hidden" name="ssh_user_id" id="ssh_user_id" value="0">
									<input type="reset" value="<?= self::escapeHtmlAttr(tr('Cancel')) ;?>">
								</div>
							</tr>
					</table>
				</td>
			</tr>
			</tbody>
		</table>
	</form>
</div>
<script>
	$(function() {
		var $dataTable;

		function flashMessage(type, message) {
			$("<div>", { "class": type, "html": $.parseHTML(message), "hide": true }).
				prependTo(".body").trigger('message_timeout');
		}

		function doRequest(rType, action, data) {
			return $.ajax({
				dataType: "json",
				type: rType,
				url: "/client/ssh_users?action=" + action,
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
			language: imscp_i18n.InstantSSH.dataTable,
			displayLength: 5,
			processing: true,
			serverSide: true,
			ajaxSource: "/client/ssh_users?action=get_ssh_users",
			stateSave: true,
			pagingType: "simple",
			columnDefs: [
				{ sortable: false, searchable: false, targets: [ 3 ] }
			],
			aoColumns: [
				{ data: "ssh_user_name" },
				{ data: "ssh_user_key_fingerprint" },
				{ data: "ssh_user_status" },
				{ data: "ssh_user_actions" }
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

		$("#page").
			on("click", "input:reset", function () {
				$("#ssh_user_name").prop("readonly", false);
				$("#ssh_username_prefix").show();
				$("input:hidden").val("0");
			}).
			on("click", "span[data-action]", function () { $("input:reset").click(); }).
			on("click", "span[data-action], #actions button", function (e) {
				e.preventDefault();

				var action = $(this).data('action');
				var sshUserName = $(this).data('ssh-user-name');
				var sshUserId = $(this).data('ssh-user-id');

				switch (action) {
					case "add_ssh_user":
						doRequest('POST', "add_ssh_user", $("#ssh_user_frm").serialize()).done(
							function (data, textStatus, jqXHR) {
								$("input:reset").trigger("click");
								flashMessage((jqXHR.status == 200) ? "success" : "info", data.message);
								$dataTable.fnDraw();
							}
						);
						break;
					case "edit_ssh_user":
						doRequest('GET', "get_ssh_user", { ssh_user_id: sshUserId, ssh_user_name: sshUserName }).done(
							function (data) {
								$("#ssh_user_id").val(data.ssh_user_id);
								$("#ssh_username_prefix").hide();
								$("#ssh_user_name").val(data.ssh_user_name).prop("readonly", true);
								$("#password, #cpassword").val("");
								$("#ssh_user_auth_options").val(data.ssh_user_auth_options);
								$("#ssh_user_key").val(data.ssh_user_key);
							}
						);
						break;
					case "delete_ssh_user":
						if (confirm("<?= self::escapeJs(tr('Are you sure you want to delete this SSH user?'));?>")) {
							doRequest("POST", action, { ssh_user_id: sshUserId, ssh_user_name: sshUserName }).done(
								function (data) {
									$dataTable.fnDraw();
									flashMessage('success', data.message);
								}
							);
						}
						break;
					default:
						alert("<?= self::escapeJs(tr('Unknown action.'));?>");
				}
			});

		$(document).
			ajaxStart(function () { $dataTable.fnProcessingIndicator(); }).
			ajaxStop(function () { $dataTable.fnProcessingIndicator(false); }).
			ajaxError(function (e, jqXHR, settings, exception) {
				if (jqXHR.status == 403) {
					window.location.replace("/index.php");
				} else if (jqXHR.responseJSON !== "undefined") {
					flashMessage("error", jqXHR.responseJSON.message);
				} else if (exception == "timeout") {
					flashMessage("error", "<?= self::escapeJs(tr('Request Timeout: The server took too long to send the data.'));?>");
				} else {
					flashMessage("error", "<?= self::escapeHtmlAttr(tr('An unexpected error occurred.'));?>");
				}
			});
	});
</script>
