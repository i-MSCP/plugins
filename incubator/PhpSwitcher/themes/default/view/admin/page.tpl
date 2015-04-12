
<div class="flash_message" style="display: none;"></div>
<div id="page">
	<table class="datatable">
		<thead>
		<tr>
			<th style="width:20%;">{TR_NAME}</th>
			<th style="width:20%;">{TR_BINARY}</th>
			<th style="width:30%;">{TR_STATUS}</th>
			<th style="width:10%;">{TR_ACTIONS}</th>
		</tr>
		</thead>
		<tfoot>
		<tr>
			<td>{TR_NAME}</td>
			<td>{TR_BINARY}</td>
			<td>{TR_STATUS}</td>
			<td>{TR_ACTIONS}</td>
		</tr>
		</tfoot>
		<tbody>
		<tr>
			<td colspan="6" class="dataTables_empty">{TR_PROCESSING_DATA}</td>
		</tr>
		</tbody>
		<tbody>
		<tr>
			<td colspan="4" bgcolor="#f0f8ff">
				<div class="buttons">
					<button data-action="add" title="{TR_ADD_NEW_VERSION_TOOLTIP}">{TR_ADD_NEW_VERSION}</button>
					<!-- BDP: phpinfo_button -->
					<button id="phpinfo" data-action="phpinfo" title="{TR_REGENERATE_PHPINFO_TOOLTIP}">{TR_REGENERATE_PHPINFO}</button>
					<!-- EDP: phpinfo_button -->
				</div>
			</td>
		</tr>
		</tbody>
	</table>
</div>

<div id="php_dialog" style="display: none;">
	<form id="php_frm">
		<table class="firstColFixed">
			<tr>
				<td><label for="version_name">{TR_NAME}</label></td>
				<td><input type="text" name="version_name" id="version_name" maxlength="30" value=""></td>
			</tr>
			<tr>
				<td><label for="version_binary_path">{TR_BINARY_PATH}</label></td>
				<td><input type="text" name="version_binary_path" id="version_binary_path" maxlength="255" value=""></td>
			</tr>
		</table>
		<input type="hidden" name="version_id" id="version_id" value="">
	</form>
</div>

<script>
	var $dataTable;

	function doRequest(rType, action, data)
	{
		return $.ajax({
			dataType: "json",
			type: rType,
			url: "/admin/phpswitcher?action=" + action,
			data: data,
			timeout: 3000
		});
	}

	function createDialog(title, action)
	{
		return $("#php_dialog").dialog({
			autoOpen: false,
			height: "auto",
			width: 550,
			modal: true,
			title: title,
			buttons: [
				{
					text: imscp_i18n.PhpSwitcher.save,
					click: function() {
						doRequest('POST', action, $("#php_frm").serialize()).done(function(data) {
							$("#php_dialog").dialog("close");
							flashMessage('success', data.message);
							$dataTable.fnDraw();
						});
					}
				},
				{
					text: imscp_i18n.PhpSwitcher.cancel,
					click: function() {
						$(this).dialog("close");
					}
				}

			],
			open: function() {
				if(action == "edit") {
					frm = $("#php_frm");
					$.each($(this).data("data"), function(k,v) { $("#" + k, frm).val(v);})
				}
			},
			close: function() {
				$("#php_frm").get(0).reset()
			}
		});
	}

	function flashMessage(type, message)
	{
		var flashMessage = $(".flash_message").text(message).addClass(type);

		setTimeout(function () { flashMessage.fadeOut(1000); }, 3000);
		setTimeout(function () { flashMessage.removeClass(type); }, 4000);

		flashMessage.show();
	}

	$(document).ready(function() {
		jQuery.fn.dataTableExt.oApi.fnProcessingIndicator = function (oSettings, onoff) {
			if (typeof(onoff) == "undefined") { onoff = true; }
			this.oApi._fnProcessingDisplay(oSettings, onoff);
		};

		$dataTable = $(".datatable").dataTable({
			language: imscp_i18n.PhpSwitcher.dataTable,
			displayLength: 5,
			processing: true,
			serverSide: true,
			pagingType: "simple",
			ajaxSource: "/admin/phpswitcher?action=table",
			stateSave: true,
			columnDefs: [ { sortable: false, searchable: false, targets: [ 3 ] } ],
			columns: [
				{ mData: "version_name" },
				{ mData: "version_binary_path" },
				{ mData: "version_status" },
				{ mData: "actions" }
			],
			serverData: function (sSource, aoData, fnCallback) {
				$.ajax( {
					dataType: "json",
					type: "GET",
					url: sSource,
					data: aoData,
					success: fnCallback,
					timeout: 5000,
					//error: function(xhr, textStatus, error) { $dataTable.fnProcessingIndicator(false); }
				}).done(function() {
					$dataTable.find("span").tooltip({ tooltipClass: "ui-tooltip-notice", track: true });

					if(!$dataTable.fnGetData(0)) {
						$("#phpinfo").hide();
					}
				}).fail(function(jqXHR) {
					$dataTable.fnProcessingIndicator(false);
					//flashMessage('error', $.parseJSON(jqXHR.responseText).message);
					flashMessage('error', jqXHR.responseJSON.message);

				});
			}
		});

		$("#page").on("click", "span[data-action], button", function() {
			$("button").blur();

			var action = $(this).data("action");
			var versionName = $(this).data("version-name");
			var versionId = $(this).data("version-id");

			 switch (action) {
			 	case "add":
					createDialog(
						imscp_i18n.PhpSwitcher.add, action).data({ version_id: versionId, version_name: versionName }
					).dialog("open");
					break;
				case "edit":
					doRequest("GET", "get", { version_id: versionId, version_name: versionName }).done(
						function(data) {
							createDialog(sprintf(imscp_i18n.PhpSwitcher.edit, versionName), action).data({ data: data }).dialog("open");
						}
					);

					break;
				 case "delete":
					 if(confirm(imscp_i18n.PhpSwitcher.delete_confirm)) {
						 doRequest( "POST", action, { version_id: versionId, version_name: versionName } ).done(
						 	function(data) { $dataTable.fnDraw(); flashMessage("success", data.message); }
						 );
					 }
					 break;
				 <!-- BDP: phpinfo_js -->
				 case "phpinfo":
						 if(confirm(imscp_i18n.PhpSwitcher.phpinfo_confirm)) {
							 doRequest("POST", action, { }).done(
									 function(data) { $dataTable.fnDraw(); flashMessage("success", data.message); }
							 );
						 }
					 break;
				 <!-- EDP: phpinfo_js -->
			 	default:
			 		alert(imscp_i18n.PhpSwitcher.unknown_action);
			 }
		});

		$(document).
			ajaxStart(function() { $dataTable.fnProcessingIndicator(); }) .
			ajaxStop(function() { $dataTable.fnProcessingIndicator(false); }).
			ajaxError(function(e, jqXHR, settings, exception) {
				if(jqXHR.responseJSON != "") {
					flashMessage("error", jqXHR.responseJSON.message);
				} else if(exception == "timeout") {
					flashMessage("error", imscp_i18n.PhpSwitcher.request_timeout);
				} else {
					flashMessage("error", imscp_i18n.PhpSwitcher.request_error);
				}
			});
		});
</script>
