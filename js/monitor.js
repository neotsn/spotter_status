/**
 * Created by Chris on 5/18/2014.
 */

function monitor_offices_list() {
	$('span[class^="nws_office_city"]').unbind().on('click', function () {
		var thisClass = $(this).attr('class');
		var officeId = $(this).attr('data-id');

		if (thisClass == "nws_office_city_selected") {
			// Currently selected, remove selection and remove input element for it
			$('input[name="offices\\[' + officeId + '\\]"]').remove();
			$(this).addClass('nws_office_city').removeClass('nws_office_city_selected');
		} else {
			var hiddenOfficeIdField = $('<input type="hidden" />').val(officeId).attr('name', 'offices[' + officeId + ']');
			$("#offices_input_container").append(hiddenOfficeIdField);
			$(this).removeClass('nws_office_city').addClass('nws_office_city_selected');
		}
	});
}

function monitor_profile_links() {
	$("#edit_offices").unbind().on('click', function () {
		var user_id = $(this).attr('data-userid');
		get_office_list(user_id);
	});

	$(".add_offices").unbind().on('click', function () {
		var user_id = $(this).attr('data-userid');
		get_office_list(user_id);
	});

	$("#disconnect_service").unbind().on('click', function () {
		var user_id = $(this).attr('data-userid');
		$("#disconnect_dialog").dialog({
			resizable: false,
			height: 300,
			width: 500,
			modal: true,
			buttons: {
				"Disconnect Service": function () {
					$.ajax({
						type: "GET",
						url: "ajax_handler.php",
						data: {
							mode: "disconnectService",
							user_id: user_id
						}
					}).done(function () {
						parent.location.reload();
						$(this).dialog("close");
					});
				},
				Cancel: function () {
					$(this).dialog("close");
				}
			}
		});
	});
}

function get_office_list(user_id) {
	$.ajax({
		type: "GET",
		url: "ajax_handler.php",
		data: {
			mode: "getOfficelist",
			user_id: user_id
		}
	}).done(function (response) {
		$("#offices_dialog").html(response).dialog({
			height: window.innerHeight - 200,
			width: window.innerWidth - 200,
			modal: true,
			buttons: {
				"Save": function () {
					var offices = [];
					$('input[name^="offices\\["]').each(function () {
						offices.push($(this).val());
					});

					$.ajax({
						type: "POST",
						url: "ajax_handler.php",
						data: {
							mode: "saveOfficelist",
							offices: offices,
							user_id: user_id
						}
					}).done(function () {
						parent.location.reload();
					});
				},
				Cancel: function () {
					$(this).dialog("close");
				}
			}
		});
	});
}