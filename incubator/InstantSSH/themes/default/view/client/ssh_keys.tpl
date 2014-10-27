
<link href="/InstantSSH/themes/default/assets/css/instant_ssh.css?v={INSTANT_SSH_ASSET_VERSION}" rel="stylesheet" type="text/css"/>
<div id="page">
	<p class="hint" style="font-variant: small-caps;font-size: small;">
		<?= self::escapeHtml(tr('This is the list of SSH public keys associated with your account. Remove any keys that you do not recognize.', true));?>
	</p>
	<br/>
	<div class="info">
		<?= self::escapeHtml(tr('You can generate your rsa key pair by running the following command: %s', true, 'ssh-keygen -t rsa -C user@domain.tld', true))?>
	</div>
	<table class="datatable">
		<thead>
		<tr>
			<th><?= self::escapeHtml(tr('Name', true))?></th>
			<th><?= self::escapeHtml(tr('Fingerprint', true))?></th>
			<th><?= self::escapeHtml(tr('User', true))?></th>
			<th><?= self::escapeHtml(tr('Status', true))?></th>
			<th><?= self::escapeHtml(tr('Actions', true))?></th>
		</tr>
		</thead>
		<tfoot>
		<tr>
			<td><?= self::escapeHtml(tr('Name', true))?></td>
			<td><?= self::escapeHtml(tr('Fingerprint', true))?></td>
			<td><?= self::escapeHtml(tr('User', true))?></td>
			<td><?= self::escapeHtml(tr('Status', true))?></td>
			<td><?= self::escapeHtml(tr('Actions', true))?></td>
		</tr>
		</tfoot>
		<tbody>
		<tr>
			<td colspan="5"><?= self::escapeHtml(tr('Processing...', true));?></td>
		</tr>
		</tbody>
	</table>
	<form name="ssh_key_frm" id="ssh_key_frm" method="post" enctype="application/x-www-form-urlencoded">
		<table>
			<thead>
			<tr>
				<th colspan="2">{TR_DYN_ACTIONS}</th>
			</tr>
			</thead>
			<tbody>
			<tr>
				<td>
					<label for="ssh_key_name">
						<?= self::escapeHtml(tr('SSH Key name', true));?>
						<span class="icon i_help" title="<?= self::escapeHtmlAttr(tr('Arbitrary name which allow you to retrieve your SSH key.', true));?>">&nbsp;</span>
					</label>
				</td>
				<td>
					<input type="text" class="inputTitle" name="ssh_key_name" id="ssh_key_name" value="" maxlength="255" placeholder="<?= self::escapeHtmlAttr(tr('Enter a key name', true));?>">
				</td>
			</tr>
			<!-- BDP: ssh_auth_options_block -->
			<tr>
				<td>
					<label for="ssh_auth_options">
						<?= tr('Authentication options');?>
						<span class="icon i_help" title="{TR_ALLOWED_OPTIONS}">&nbsp;</span>
					</label>
				</td>
				<td>
					<textarea style="height: 45px" name="ssh_auth_options" id="ssh_auth_options">{DEFAULT_AUTH_OPTIONS}</textarea>
				</td>
			</tr>
			<!-- EDP: ssh_auth_options_block -->
			<tr>
				<td>
					<label for="ssh_key">
						<?= tr('SSH Key');?>
						<span class="icon i_help" title="<?= self::escapeHtmlAttr(tr('Supported RSA key formats are PKCS#1, openSSH and XML Signature.', true));?>">&nbsp;</span>
					</label>
				</td>
				<td>
					<textarea style="height: 90px" name="ssh_key" id="ssh_key" placeholder="<?= self::escapeHtmlAttr(tr('Enter a key', true));?>"></textarea>
				</td>
			</tr>
			<tr>
				<td colspan="2" style="text-align: right;">
					<button id="action" data-action="add_ssh_key"><?= self::escapeHtml(tr('Save'));?></button>
					<input type="hidden" id="ssh_key_id" name="ssh_key_id" value="0">
					<input type="reset" value="{TR_RESET_BUTTON_LABEL}"/>
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
			url: "/client/ssh_keys?action=" + action,
			data: data,
			timeout: 5000
		});
	}

	function flashMessage(type, message) {
		$('<div />',
			{
				"class": 'flash_message ' + type,
				"text": message,
				"hide": true
			}
		).prependTo("#page").hide().fadeIn('fast').delay(3000).fadeOut('normal', function () {
				$(this).remove();
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
			sAjaxSource: "/client/ssh_keys?action=get_ssh_keys",
			bStateSave: true,
			pagingType: "simple",
			aoColumnDefs: [
				{ bSortable: false, bSearchable: false, aTargets: [ 4 ] }
			],
			aoColumns: [
				{ mData: "ssh_key_name" },
				{ mData: "ssh_key_fingerprint" },
				{ mData: "admin_sys_name" },
				{ mData: "ssh_key_status" },
				{ mData: "ssh_key_actions" }
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
			$("#ssh_key_id").val("0");
			$("#ssh_key_name").prop("readonly", false);
			$("#ssh_key").prop("readonly", false);
			$("#action").show();
		});

		$page.on("click", "span[data-action], button", function (e) {
			e.preventDefault();

			action = $(this).data('action');
			sshKeyName = $(this).data('ssh-key-name');
			sshKeyId = $(this).data('ssh-key-id');

			switch (action) {
				case "add_ssh_key":
					doRequest('POST', "add_ssh_key", $("#ssh_key_frm").serialize()).done(function (data) {
						$("input:reset").trigger("click");
						flashMessage('success', data.message);
						oTable.fnDraw();
					});
					break;
				<!-- BDP: ssh_show_action -->
				case "show_ssh_key":
				<!-- EDP: ssh_show_action -->
				<!-- BDP: ssh_edit_action -->
				case "edit_ssh_key":
				<!-- EDP: ssh_edit_action -->
					doRequest('GET', "get_ssh_key", { ssh_key_id: sshKeyId }).done(function (data) {
						if(action != 'edit_ssh_key') {
							$("#action").hide();
						}

						$("#ssh_key_id").val(data.ssh_key_id);
						$("#ssh_key_name").val(data.ssh_key_name).prop("readonly", true);
						$("#ssh_auth_options").val(data.ssh_auth_options);
						$("#ssh_key").val(data.ssh_key).prop('readonly', true);
					});
					break;
				case "delete_ssh_key":
					if (confirm("<?= self::escapeJs(tr('Are you sure you want to delete this SSH key? Be aware that this will destroy all your SSH sessions.', true));?>")) {
						doRequest("POST", action, { ssh_key_id: sshKeyId }).done(
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
