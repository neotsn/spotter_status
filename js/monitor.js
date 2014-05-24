/**
 * Created by Chris on 5/18/2014.
 */

function monitor_fetch_report_button() {
	$("#fetch_report").unbind().on('click', function () {
		var nws_office = $("#nws_office").val();

		$.ajax({
			type: "GET",
			url: "ajax_handler.php",
			data: {
				mode: "getOutlook",
				nws_office: nws_office
			}
		}).done(function (msg) {
			$("#nws_outlook").html(msg);
		});
	});
}

function monitor_setup_form() {
	$('span[class^="nws_office_city"]').unbind().on('click', function () {
		var thisClass = $(this).attr('class');
		var officeId = $(this).attr('data-id');

		if (thisClass == "nws_office_city_selected") {
			// Currently selected, remove selection and remove input element for it
			$('input[name="offices\\[' + officeId + '\\]"]').remove();
			$(this).addClass('nws_office_city').removeClass('nws_office_city_selected');
		} else {
			var hiddenOfficeIdField = $('<input type="hidden" />').val(officeId).attr('name', 'offices[' + officeId + ']');
			$("#setup_offices_container").append(hiddenOfficeIdField);
			$(this).removeClass('nws_office_city').addClass('nws_office_city_selected');
		}
	});
}
