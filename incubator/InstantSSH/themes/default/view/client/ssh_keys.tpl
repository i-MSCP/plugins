<link href="/InstantSSH/themes/default/assets/css/instant_ssh.css?v={INSTANT_SSH_ASSET_VERSION}" rel="stylesheet"
	  type="text/css"/>

<div class="flash_message" style="display: none;"></div>

<div id="page">
	<p class="hint" style="font-variant: small-caps;font-size: small;">
		This is the list of SSH public keys associated with your account. Remove any keys that you do not recognize.
	</p>
	<br/>

	<div class="info">
		You can generate your rsa key pair using the following command:
		<strong>ssh-keygen -t rsa -C user@domain.tld</strong>
	</div>

	<table class="datatable">
		<thead>
		<tr>
			<th>Key Name</th>
			<th>Key Fingerprint</th>
			<th>Key User</th>
			<th>Key Status</th>
			<th>Key Actions</th>
		</tr>
		</thead>
		<tfoot>
		<tr>
			<td>Key Name</td>
			<td>Key Fingerprint</td>
			<td>Key User</td>
			<td>KeyStatus</td>
			<td>Key Actions</td>
		</tr>
		</tfoot>
		<tbody>
		<tr>
			<td colspan="3">Processing...</td>
		</tr>
		</tbody>
	</table>

	<form name="ssh_key_frm" id="ssh_key_frm" method="post" enctype="application/x-www-form-urlencoded">
		<table>
			<thead>
			<tr>
				<th colspan="2">{TR_DYN_ACTIONS} SSH Key</th>
			</tr>
			</thead>
			<tbody>
			<tr>
				<td>
					<label for="ssh_key_name">
						SSH Key name
						<span class="icon i_help"
							  title="Arbitrary name which allow you to retrieve your SSH key">&nbsp;</span>
					</label>
				</td>
				<td>
					<input type="text" class="inputTitle" name="ssh_key_name" id="ssh_key_name" value="" maxlength="255"
						   placeholder="Enter a key name">
				</td>
			</tr>
			<!-- BDP: ssh_auth_options_block -->
			<tr>
				<td>
					<label for="ssh_auth_options">
						Authentication options
						<span class="icon i_help" title="{TR_ALLOWED_OPTIONS}">&nbsp;</span>
					</label>
				</td>
				<td>
					<textarea style="height: 45px" name="ssh_auth_options"
							  id="ssh_auth_options">{DEFAULT_AUTH_OPTIONS}</textarea>
				</td>
			</tr>
			<!-- EDP: ssh_auth_options_block -->
			<tr>
				<td>
					<label for="ssh_key">
						SSH Key
						<span class="icon i_help"
							  title="Supported RSA key formats are PKCS#1, openSSH and XML Signature">&nbsp;</span>
					</label>
				</td>
				<td>
					<textarea style="height: 90px" name="ssh_key" id="ssh_key" placeholder="Enter a key"></textarea>
				</td>
			</tr>
			<tr>
				<td colspan="2" style="text-align: right;">
					<!-- BDP: ssh_key_save_button_block -->
					<button data-action="add_ssh_key">Save</button>
					<!-- EDP: ssh_key_save_button_block -->
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
				//"style": "position:absolute;width:50%;left:50%;margin-left:-25%;z-index:3000"
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

		$("input:reset").click(function () {
			$("#ssh_key_id").val("0");
			$("#ssh_key_name").prop("readonly", false);
			$("#ssh_key").prop("readonly", false);
		});

		$("#page").on("click", "span[data-action], button", function (e) {
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
				case "show_ssh_key":
				case "edit_ssh_key":
					doRequest('GET', "get_ssh_key", { ssh_key_id: sshKeyId }).done(function (data) {
						$("#ssh_key_id").val(data.ssh_key_id);
						$("#ssh_key_name").val(data.ssh_key_name).prop("readonly", true);
						$("#ssh_auth_options").val(data.ssh_auth_options);
						$("#ssh_key").val(data.ssh_key).prop('readonly', true);
					});
					break;
				case "delete_ssh_key":
					if (confirm("Are you sure you want to delete this SSH key?")) {
						doRequest("POST", action, { ssh_key_id: sshKeyId }).done(
							function (data) {
								oTable.fnDraw();
								flashMessage('success', data.message);
							}
						);
					}
					break;
				default:
					alert("Unknown Action");
			}
		});

		$(document).ajaxStart(function () {
			oTable.fnProcessingIndicator();
		});
		$(document).ajaxStop(function () {
			oTable.fnProcessingIndicator(false);
		});
		$(document).ajaxError(function (e, jqXHR, settings, exception) {
			if (jqXHR.status == 403) {
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
