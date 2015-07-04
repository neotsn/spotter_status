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
	$(".btn_show_location_selector").unbind().on('click', function () {
		var user_id = $(this).attr('data-userid');
		get_location_selector(user_id);
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
				"Cancel": function () {
					$(this).dialog("close");
				}
			}
		});
	});
}

function monitor_location_selector_dialog() {
	// Listen for state-menu changes on the container
	$('#locations_dialog').on('change', '#state_selector', function () {
		$.ajax({
			type: "GET",
			url: "ajax_handler.php",
			data: {
				mode: "getLocationOptionsForState",
				state: $(this).val()
			}
		}).done(function (response) {
			$('#location_selector_container').html(response);
		});
	})
}

function get_location_selector(user_id) {
	$.ajax({
		type: "GET",
		url: "ajax_handler.php",
		data: {
			mode: "getLocationSelector",
			user_id: user_id
		}
	}).done(function (response) {
		$("#locations_dialog").html(response).dialog({
			//height: 300,
			width: 600,
			title: "Change Your Location",
			modal: true,
			buttons: {
				"Save": function () {
					$.ajax({
						type: "POST",
						url: "ajax_handler.php",
						data: {
							mode: "saveLocation",
							location_id: $('select[name="location_id"]').val(),
							user_id: user_id
						}
					}).done(function () {
						parent.location.reload();
					});
				},
				"Cancel": function () {
					$(this).dialog("close");
				}
			}
		});
	});
}