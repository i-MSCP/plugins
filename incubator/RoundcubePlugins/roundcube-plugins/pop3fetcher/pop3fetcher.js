if (window.rcmail){
	rcmail.addEventListener('init', function (event){
		rcmail.addEventListener('plugin.edit_do_ok', pop3fetcher_edit_do_ok);
		rcmail.addEventListener('plugin.add_do_ok', pop3fetcher_add_do_ok);
		rcmail.addEventListener('plugin.delete_do_ok', pop3fetcher_delete_do_ok);
		rcmail.addEventListener('plugin.edit_do_error_connecting', pop3fetcher_edit_do_error_connecting);
		rcmail.addEventListener('plugin.add_do_error_connecting', pop3fetcher_add_do_error_connecting);
	});
}

function pop3fetcher_edit_do(){
	$(".button.pop3fetcher").hide();
	$("#btn_edit_do_loader").show();
	var params = {  '_edit_do': '1',
					'_pop3fetcher_id': $("#pop3fetcher_id").val(),
					'_pop3fetcher_email': $("#pop3fetcher_email").val(),
					'_pop3fetcher_username': $("#pop3fetcher_username").val(),
					'_pop3fetcher_password': $("#pop3fetcher_password").val(),
					'_pop3fetcher_provider': $("#pop3fetcher_provider").val(),
					'_pop3fetcher_serveraddress': $("#pop3fetcher_serveraddress").val(),
					'_pop3fetcher_serverport': $("#pop3fetcher_serverport").val(),
					'_pop3fetcher_ssl': $("#pop3fetcher_ssl").val(),
					'_pop3fetcher_leaveacopy': $("#pop3fetcher_leaveacopy").is(":checked"),
					'_pop3fetcher_testconnection': $("#pop3fetcher_testconnection").is(":checked"),
					'_pop3fetcher_defaultfolder': $("#pop3fetcher_defaultfolder").val()
	};
	rcmail.http_post('plugin.pop3fetcher', params);
}

function pop3fetcher_edit_do_ok(){
	window.location='?_task=settings&_action=edit-prefs&_section=pop3fetcher&_framed=1';
}

function pop3fetcher_edit_do_error_connecting(){
	$(".button.pop3fetcher").show();
	$("#btn_edit_do_loader").hide();
	alert(rcmail.gettext('pop3fetcher.account_unableconnect')+" "+$("#pop3fetcher_serveraddress").val()+":"+$("#pop3fetcher_serverport").val());
}

function pop3fetcher_add_do(){
	$(".button.pop3fetcher").hide();
	$("#btn_add_do_loader").show();
	var params = {  '_add_do': '1',
					'_pop3fetcher_id': $("#pop3fetcher_id").val(),
					'_pop3fetcher_email': $("#pop3fetcher_email").val(),
					'_pop3fetcher_username': $("#pop3fetcher_username").val(),
					'_pop3fetcher_password': $("#pop3fetcher_password").val(),
					'_pop3fetcher_provider': $("#pop3fetcher_provider").val(),
					'_pop3fetcher_serveraddress': $("#pop3fetcher_serveraddress").val(),
					'_pop3fetcher_serverport': $("#pop3fetcher_serverport").val(),
					'_pop3fetcher_ssl': $("#pop3fetcher_ssl").val(),
					'_pop3fetcher_leaveacopy': $("#pop3fetcher_leaveacopy").is(":checked"),
					'_pop3fetcher_testconnection': $("#pop3fetcher_testconnection").is(":checked"),
					'_pop3fetcher_import_old_messages': $("#pop3fetcher_import_old_messages").is(":checked"),
					'_pop3fetcher_defaultfolder': $("#pop3fetcher_defaultfolder").val()
	};
	rcmail.http_post('plugin.pop3fetcher', params);
}

function pop3fetcher_add_do_ok(){
	window.location='?_task=settings&_action=edit-prefs&_section=pop3fetcher&_framed=1';
}

function pop3fetcher_add_do_error_connecting(){
	$(".button.pop3fetcher").show();
	$("#btn_edit_do_loader").hide();
	alert(rcmail.gettext('pop3fetcher.account_unableconnect')+" "+$("#pop3fetcher_serveraddress").val()+":"+$("#pop3fetcher_serverport").val());
}

function pop3fetcher_delete_do(element, id){
	$(element).parents("tr").addClass("to_be_removed");
	var params = {  '_delete_do': '1',
					'_pop3fetcher_id': id
	};
	rcmail.http_post('plugin.pop3fetcher', params);
}

function pop3fetcher_delete_do_ok(){
	$(".to_be_removed").remove();
}

function load_pop3_providers(cur_selected_provider){
	$.each(providers, function(item){
		if(item==cur_selected_provider)
			$("#pop3fetcher_provider").append('<option value="'+item+'" selected>'+item+'</option>');
		else
			$("#pop3fetcher_provider").append('<option value="'+item+'">'+item+'</option>');
	});
	$("#pop3fetcher_provider").change(
		function(){
			
			$("#pop3fetcher_serveraddress").val(providers[this.value].serveraddress);
			$("#pop3fetcher_serverport").val(providers[this.value].serverport);
			$("#pop3fetcher_ssl option[value='"+providers[this.value].ssl+"']").attr("selected", "true");
		}
	);
}

function update_default_folder_name(name){
	if(name==""){
		$("#pop3fetcher_defaultfolder").find('option[value|="#AUTO_FOLDER#"]').remove();
	} else {
		if($("#pop3fetcher_defaultfolder").find('option[value|="#AUTO_FOLDER#"]').length==0)
			$("#pop3fetcher_defaultfolder").append('<option value="#AUTO_FOLDER#">'+name+'</option>');
		else
			$("#pop3fetcher_defaultfolder").find('option[value|="#AUTO_FOLDER#"]').html(name);
	}
}