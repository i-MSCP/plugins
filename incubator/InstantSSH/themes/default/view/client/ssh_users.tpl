
<link href="/InstantSSH/themes/default/assets/css/instant_ssh.css?v={INSTANT_SSH_ASSET_VERSION}" rel="stylesheet" type="text/css"/>
<div id="page">
	<p class="hint" style="font-variant: small-caps;font-size: small;margin-bottom: 10px;">
		<?= self::escapeHtml(tr('This is the list of SSH users associated with your account.', true));?>
	</p>
	<table class="datatable">
		<thead>
		<tr>
			<th><?= self::escapeHtml(tr('SSH user', true))?></th>
			<th><?= self::escapeHtml(tr('Key fingerprint', true))?></th>
			<th><?= self::escapeHtml(tr('Status', true))?></th>
			<th><?= self::escapeHtml(tr('Actions', true))?></th>
		</tr>
		</thead>
		<tfoot>
		<tr>
			<td><?= self::escapeHtml(tr('SSH user', true))?></td>
			<td><?= self::escapeHtml(tr('Key fingerprint', true))?></td>
			<td><?= self::escapeHtml(tr('Status', true))?></td>
			<td><?= self::escapeHtml(tr('Actions', true))?></td>
		</tr>
		</tfoot>
		<tbody>
		<tr>
			<td colspan="4"><?= self::escapeHtml(tr('Processing...', true));?></td>
		</tr>
		</tbody>
	</table>
	<form name="ssh_user_frm" id="ssh_user_frm" method="post" enctype="application/x-www-form-urlencoded">
		<table>
			<thead>
			<tr>
				<th colspan="2"><?= self::escapeHtml(tr('Add / Edit SSH user', true)) ;?></th>
			</tr>
			</thead>
			<tbody>
			<tr>
				<td style="width:20%;">
					<label for="ssh_user_name"><?= self::escapeHtml(tr('Username', true));?></label>
					<span style="float: right" id="ssh_username_prefix"><strong>{SSH_USERNAME_PREFIX}</strong></span>
				</td>
				<td>
					<input type="text" class="inputTitle" name="ssh_user_name" id="ssh_user_name" value="" maxlength="8" placeholder="<?= self::escapeHtmlAttr(tr('Enter an username', true));?>">
				</td>
			</tr>
			<!-- BDP: ssh_password_field_block -->
			<tr>
				<td><label for="ssh_user_password"><?= self::escapeHtml(tr('Password', true));?></label></td>
				<td><input type="text" class="inputTitle" name="ssh_user_password" id="ssh_user_password" value="" maxlength="32" placeholder="<?= self::escapeHtmlAttr(tr('Enter a password', true));?>"></td>
			</tr>
			<tr>
				<td><label for="ssh_user_password"><?= self::escapeHtml(tr('Password confirmation', true));?></label></td>
				<td><input type="text" class="inputTitle" name="ssh_user_password_confirmation" id="ssh_user_password_confirmation" value="" maxlength="32" placeholder="<?= self::escapeHtmlAttr(tr('Confirm the password', true));?>"></td>
			</tr>
			<!-- EDP: ssh_password_field_block -->
			<tr>
				<td>
					<label for="ssh_user_key">
						<?= tr('SSH key');?>
						<span class="icon i_help" title="<?= self::escapeHtmlAttr(tr('Supported RSA key formats are PKCS#1, openSSH and XML Signature.', true));?>">&nbsp;</span>
					</label>
				</td>
				<td>
					<textarea style="height:70px" name="ssh_user_key" id="ssh_user_key" placeholder="<?= self::escapeHtmlAttr(tr('Enter your SSH key', true));?>"></textarea>
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
					<textarea style="height: 45px" name="ssh_user_auth_options" id="ssh_user_auth_options" placeholder="<?= self::escapeHtmlAttr(tr('Enter authentication option(s)', true));?>">{DEFAULT_AUTH_OPTIONS}</textarea>
				</td>
			</tr>
			<!-- EDP: ssh_auth_options_block -->
			<tr>
				<td colspan="2">
					<table>
						<tr>
							<!-- BDP: ssh_password_key_info_block -->
							<td>
								<div style="color:#666666;padding:15px 0 15px 60px;background: url(/themes/default/assets/images/messages/info.png) no-repeat 5px 50%;">
									<ul style="list-style-type:none">
									<li><?= self::escapeHtml(tr("You can provide either a password, an SSH key or both. However, it's recommended to prefer key-based authentication.", true));?></li>
									<li>
										<?= self::escapeHtml(tr('You can generate your rsa key pair by running the following command:', true))?>
										<span style="color:#404040;background-color:#ffffff;border:1px solid #cccccc;padding: 2px 5px;font-weight: bold;">ssh-keygen -t rsa -C user@domain.tld</span>
									</li>
									</ul>
								</div>
							</td>
							<!-- EDP: ssh_password_key_info_block -->
							<td style="text-align: right">
								<button id="action" data-action="add_ssh_user"><?= self::escapeHtml(tr('Save'));?></button>
								<input type="hidden" name="ssh_user_id" id="ssh_user_id" value="0">
								<input type="reset" value="<?= self::escapeHtmlAttr(tr('Cancel', true)) ;?>" />
							</tr>
					</table>
				</td>
			</tr>
			</tbody>
		</table>
	</form>
</div>
<script>
	var oTable;

	function doRequest(rType, action, data) {
		return $.ajax({
			dataType: "json",
			type: rType,
			url: "/client/ssh_users?action=" + action,
			data: data,
			timeout: 3000
		});
	}

	function flashMessage(type, message) {
		$('<div />',
			{
				"class": type,
				"html": $.parseHTML(message),
				"hide": true
			}
		).prependTo(".body").trigger('message_timeout');
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
			sAjaxSource: "/client/ssh_users?action=get_ssh_users",
			bStateSave: true,
			pagingType: "simple",
			aoColumnDefs: [
				{ bSortable: false, bSearchable: false, aTargets: [ 3 ] }
			],
			aoColumns: [
				{ mData: "ssh_user_name" },
				{ mData: "ssh_user_key_fingerprint" },
				{ mData: "ssh_user_status" },
				{ mData: "ssh_user_actions" }
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
						alert(xhr.status);
						oTable.fnProcessingIndicator(false);
					}
				}).done(function () {
					oTable.find("span").imscpTooltip({ extraClass: "tooltip_icon tooltip_notice" });
				});
			}
		});

		var $page = $("#page");

		$page.on("click", "input:reset,span[data-action]", function () {
			$("#ssh_user_id").val("0");
			$("#ssh_username_prefix").show();
			$("#ssh_user_name").prop("readonly", false);
			$("#ssh_user_password").val("");
			$("#ssh_user_key").prop("readonly", false);
		});

		$page.on("click", "span[data-action], button", function (e) {
			e.preventDefault();

			action = $(this).data('action');
			sshUserName = $(this).data('ssh-user-name');
			sshUserId = $(this).data('ssh-user-id');

			switch (action) {
				case "add_ssh_user":
					doRequest('POST', "add_ssh_user", $("#ssh_user_frm").serialize()).done(
						function (data, textStatus, jqXHR) {
							$("input:reset").trigger("click");
							flashMessage((jqXHR.status == 200) ? "success" : "info", data.message);
							oTable.fnDraw();
						}
					);
					break;
				case "edit_ssh_user":
					doRequest('GET', "get_ssh_user", { ssh_user_id: sshUserId }).done(function (data) {
						$("#ssh_user_id").val(data.ssh_user_id);
						$("#ssh_username_prefix").hide();
						$("#ssh_user_name").val(data.ssh_user_name).prop("readonly", true);
						$("#ssh_user_password").val("");
						$("#ssh_user_auth_options").val(data.ssh_user_auth_options);
						$("#ssh_user_key").val(data.ssh_user_key);
					});
					break;
				case "delete_ssh_user":
					if (confirm("<?= self::escapeJs(tr('Are you sure you want to delete this SSH user?', true));?>")) {
						doRequest("POST", action, { ssh_user_id: sshUserId }).done(
							function (data) {
								oTable.fnDraw();
								flashMessage('success', data.message);
							}
						);
					}
					break;
				default:
					alert("<?= self::escapeJs(tr('Unknown action', true));?>");
			}
		});

		$(document).ajaxStart(function () { oTable.fnProcessingIndicator(); });
		$(document).ajaxStop(function () { oTable.fnProcessingIndicator(false); });
		$(document).ajaxError(function (e, jqXHR, settings, exception) {
			if (jqXHR.status == 403) {
				window.location.href = '/index.php';
			} else if (jqXHR.responseJSON != "") {
				flashMessage("error", jqXHR.responseJSON.message);
			} else if (exception == "timeout") {
				flashMessage("error", "<?= self::escapeJs(tr('Request Timeout: The server took too long to send the data.', true));?>");
			} else {
				flashMessage("error", "<?= self::escapeHtmlAttr(tr('An unexpected error occurred.', true));?>");
			}
		});
	});
</script>
