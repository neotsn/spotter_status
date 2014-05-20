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
