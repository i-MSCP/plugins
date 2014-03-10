
<link href="/TemplateEditor/themes/default/assets/css/template_editor.css?v={TEMPLATE_EDITOR_ASSET_VERSION}" rel="stylesheet" type="text/css"/>
<link href="/TemplateEditor/themes/default/assets/css/multi-select.css?v={TEMPLATE_EDITOR_ASSET_VERSION}" rel="stylesheet" type="text/css"/>

<script src="/TemplateEditor/themes/default/assets/js/vendor/jquery.dform-1.1.0.js?v={TEMPLATE_EDITOR_ASSET_VERSION}"></script>
<script src="/TemplateEditor/themes/default/assets/js/vendor/jquery.multi-select.js?v={TEMPLATE_EDITOR_ASSET_VERSION}"></script>
<script src="/TemplateEditor/themes/default/assets/js/vendor/jquery.quicksearch.js?v={TEMPLATE_EDITOR_ASSET_VERSION}"></script>

<div class="flash_message" style="display: none;"></div>
<p class="hint" style="font-variant: small-caps;font-size: small;">{TR_HINT}</p>
<br />
<div class="message warning">
	<strong>{TR_WARNING}</strong>
</div>
<br />
<table class="datatable">
	<thead>
	<tr>
		<th style="width:20%;">{TR_NAME}</th>
		<th style="width:20%;">{TR_PARENT}</th>
		<th style="width:15%;">{TR_SCOPE}</th>
		<th style="width:10%;">{TR_STATUS}</th>
		<th style="width:15%;">{TR_ACTIONS}</th>
		<th style="width:20%;text-align:right;">
			<label>
				<select name="service_name">
					<option value="" disabled="disabled"{DSELECTED}>{TR_SERVICE_NAME}</option>
					<!-- BDP: service_name_option -->
					<option value="{SERVICE_NAME}{SELECTED}">{SHOW_SERVICE_NAME}</option>
					<!-- EDP: service_name_option -->
				</select>
			</label>
		</th>
	</tr>
	</thead>
	<tfoot>
	<tr>
		<td>{TR_NAME}</td>
		<td>{TR_PARENT}</td>
		<td>{TR_SCOPE}</td>
		<td>{TR_STATUS}</td>
		<td>{TR_ACTIONS}</td>
		<td style="text-align:right;">
			<label>
				<select name="service_name">
					<option value="" disabled="disabled" selected="selected">{TR_SERVICE_NAME}</option>
					<!-- BDP: service_name_option -->
					<option value="{SERVICE_NAME}" {SELECTED}>{SHOW_SERVICE_NAME}</option>
					<!-- EDP: service_name_option -->
				</select>
			</label>
		</td>
	</tr>
	</tfoot>
	<tbody>
	<tr>
		<td colspan="5" class="dataTables_empty">{TR_PROCESSING_DATA}</td>
	</tr>
	</tbody>
</table>
<div class="buttons">
	<input type="button" id="sync_default_templates" title="{TR_SYNC_TOOLTIP}" value="{TR_SYNC}" />
</div>
<div id="dialog" style="display: none;">
	<form id="dialog_frm"></form>
</div>

<script>
	var oTable;
	var oSelect;

	function doRequest(rType, action, data)
	{
		return $.ajax({
			dataType: "json",
			type: rType,
			url: "/admin/template_editor.php?action=" + action,
			data: data,
			timeout: 3000
		});
	}

	function templateDialog(title, action)
	{
		return $("#dialog").dialog({
			autoOpen: false,
			height: 650,
			width: 980,
			modal: true,
			title: title,
			buttons: {
				"{TR_SAVE}": function() {
					doRequest('POST', action, $("#dialog_frm").serialize()).done(function(data) {
						$("#dialog").dialog("close");
						flashMessage('success', data.message);
						oTable.fnDraw();
					});
				},
				"{TR_CANCEL}": function() { $(this).dialog("close"); }
			},
			open: function() {
				template = $(this).data('template');

				form = { html: [ ] };

				if(action == 'create_template') {
					form.html.push(
						{
							type : "div",
							html :
								'<table><tr><td><label for="name">{TR_NAME}</label></td>' +
									'<td><input type="text" id="name" name="name" maxlength="50" ' +
									'class="inputTitle" value="Custom '+ template.name +'"></td></tr></table>'
						}
					);
				}

				// Build textareas
				templateFileEntries = [];

				$.each(template.files, function(index, file) {
					var height = ( action == 'create_template' ? 387 : 457 );
					templateFileEntries.push({
						caption: file.name,
						id: file.id,
						html : {
							type: "textarea",
							css: {
								"font-size": "1.1em", "border-color": "#cccccc", "-webkit-box-shadow":"none",
								"-moz-box-shadow": "none", "box-shadow": "none", "width": "97.5%", "padding": "10px",
								"height": height+"px"
							},
							name: "files[" + file.name + "]", html: file.content
						}
					});
				});

				form.html.push({ type: "tabs", entries: templateFileEntries });
				form.html.push({ type: "hidden", name: "id", value: template.id });

				// Build form and enable tab support in textareas
				$("#dialog_frm").dform(form).find('textarea').keydown(function(e) {
					if(e.keyCode === 9) {
						var start = this.selectionStart;
						end = this.selectionEnd;
						var $this = $(this);
						$this.val($this.val().substring(0, start) + "\t" + $this.val().substring(end));
						this.selectionStart = this.selectionEnd = start + 1;
						return false;
					}
				});
			},
			close: function() {
				$("#dialog_frm").empty();
			}
		});
	}

	function adminsTemplatesDialog()
	{
		return $("#dialog").dialog({
			autoOpen: false,
			height: '370',
			width: 395,
			modal: true,
			title: '{TR_TEMPLATE_ASSIGNMENT}',
			buttons: {
				"{TR_SAVE}": function() {
					doRequest('POST', 'set_admins_templates', $("#dialog_frm").serialize()).done(function(data) {
						$("#dialog").dialog("close");
						flashMessage('success', data.message);
						oTable.fnDraw();
					});
				},
				"{TR_CANCEL}": function() { $(this).dialog("close"); }
			},
			open: function() {
				template = $(this).data('template');

				var form = { html: [ ] };

				// Build multiselect entries
				var optionEntries = { };

				$.each(template.admins_templates, function(index, entry) {
					optionEntries[entry.admin_id] = { html: entry.admin_name };

					if(entry.template_id != "0") {
						optionEntries[entry.admin_id].selected ="selected";
					}
				});

				form.html.push(
					{
						type: "select",
						id: "multiselect",
						name: "admins_templates[]",
						multiple : "multiple",
						options: optionEntries
					}
				);

				form.html.push({ type: "hidden", name: "id", value: template.id });

				// Build form
				$("#dialog_frm").dform(form).find("#multiselect").multiSelect({
					selectableHeader: '<input type="text" class="search-input" autocomplete="off" placeholder="Search">',
					selectionHeader: '<input type="text" class="search-input" autocomplete="off" placeholder="Search">',
					selectableFooter: '<div class="custom-header">Unassigned</div>',
					selectionFooter: '<div class="custom-header">Assigned</div>',
					afterInit: function(ms) {
						var that = this,
							$selectableSearch = that.$selectableUl.prev(),
							$selectionSearch = that.$selectionUl.prev(),
							selectableSearchString = "#" + that.$container.attr("id") + " .ms-elem-selectable:not(.ms-selected)",
							selectionSearchString = "#" + that.$container.attr("id")+ " .ms-elem-selection.ms-selected";

						that.qs1 = $selectableSearch.quicksearch(selectableSearchString).on("keydown", function(e){
							if (e.which === 40){
								that.$selectableUl.focus();
								return false;
							}
						});

						that.qs2 = $selectionSearch.quicksearch(selectionSearchString).on("keydown", function(e){
							if (e.which == 40){
								that.$selectionUl.focus();
								return false;
							}
						});
					},
					afterSelect: function(){
						this.qs1.cache();
						this.qs2.cache();
					},
					afterDeselect: function(){
						this.qs1.cache();
						this.qs2.cache();
					}
				});
			},
			close: function() {
				$("#dialog_frm").empty();
			}
		});
	}

	function flashMessage(type, message)
	{
		var flashMessage = $(".flash_message").text(message).addClass(type).show();
		setTimeout(function () { flashMessage.fadeOut(1000); }, 3000);
		setTimeout(function () { flashMessage.removeClass(type); }, 4000);
	}

	$(document).ready(function() {
		jQuery.fn.dataTableExt.oApi.fnProcessingIndicator = function (oSettings, onoff) {
			if (typeof(onoff) == "undefined") { onoff = true; }
			this.oApi._fnProcessingDisplay(oSettings, onoff);
		};

		oSelect = $("select[name=service_name]");
		oTable = $(".datatable").dataTable({
			oLanguage: {DATATABLE_TRANSLATIONS},
			iDisplayLength: 5,
			bProcessing: true,
			bServerSide: true,
			sAjaxSource: "/admin/template_editor.php?action=get_table",
			bStateSave: true,
			fnServerParams: function (aoData) { aoData.push( { name: "service_name", value: oSelect.val() } );},
			aoColumnDefs: [
				{ bSortable: false, bSearchable: false, aTargets: [ 4 ] },
				{ bSortable: false, bSearchable: false, aTargets: [ 5 ] }
			],
			aoColumns: [
				{ mData: "name" },
				{ mData: "parent_name" },
				{ mData: "scope" },
				{ mData: "status" },
				{ mData: "actions" },
				{ mData: "service_name" }
			],
			fnServerData: function (sSource, aoData, fnCallback) {
				$.ajax( {
					dataType: "json",
					type: "GET",
					url: sSource,
					data: aoData,
					success: fnCallback,
					timeout: 5000,
					error: function(xhr, textStatus, error) { oTable.fnProcessingIndicator(false); }
				}).done(function() {
					oTable.find("span").imscpTooltip({ extraClass: "tooltip_icon tooltip_notice" });
				});
			}
		});

		oSelect.change(function() {
			oSelect.val($(this).val());
			oTable.fnDraw();
		});

		oTable.on("click", "span[data-action]", function() {
			action = $(this).data('action');
			templateId = $(this).data('id');

			 switch (action) {
			 	case "create_template":
				case "edit_template":
					doRequest("GET", 'get_template', { id: templateId }).done( function(data) {
						templateDialog(
							(action == "create_template")
								? sprintf("{TR_NEW}", data.name) : sprintf("{TR_EDIT}", data.name),
							action
						).data({ template: data }).dialog("open");
					});

					break;
				case "delete_template":
					 if(confirm("{TR_DELETE_CONFIRM}")) {
						doRequest("POST", action, { id: templateId } ).done( function(data) {
							oTable.fnDraw(); flashMessage('success', data.message);
						});
					 }
			 		break;
				case 'set_admins_templates':
					 doRequest("GET", 'get_admins_templates', { id: templateId }).done( function(data) {
						 adminsTemplatesDialog().data({ template: data }).dialog("open");
					 });
					 break;
				case 'sync_template':
					 if(confirm("{TR_TSYNC_CONFIRM}")) {
					 	doRequest("POST", action, { id: templateId }).done(
							function(data) { oTable.fnDraw(); flashMessage('success', data.message); }
					 	);
					 }
					 break;
			 	default:
			 		alert("{TR_UNKNOWN_ACTION}");
			 }
		});

		$('#sync_default_templates').click(function() {
			doRequest("POST", 'sync_default_templates').done( function(data) {
				oTable.fnDraw(); flashMessage('success', data.message);
			});
		});

		$(document).ajaxStart(function() { oTable.fnProcessingIndicator(); });
		$(document).ajaxStop(function() { oTable.fnProcessingIndicator(false); });
		$(document).ajaxError(function(e, jqXHR, settings, exception) {
			if(jqXHR.responseJSON != "") {
				flashMessage("error", jqXHR.responseJSON.message);
			} else if(exception == "timeout") {
				flashMessage("error", {TR_REQUEST_TIMEOUT});
			} else {
				flashMessage("error", {TR_REQUEST_ERROR});
			}
		});
	});
</script>
