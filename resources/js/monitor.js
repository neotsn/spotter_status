/**
 * Created by @neotsn on 5/18/2014.
 */

function monitor_profile_links () {
  $('.btn_show_location_selector').unbind().on('click', function () {
    var user_id = $(this).attr('data-userid')
    get_location_selector(user_id)
  })

  $('#disconnect_service').unbind().on('click', function () {
    var user_id = $(this).attr('data-userid')
    $('#disconnect_dialog').dialog({
      resizable: false,
      height: 300,
      width: 500,
      modal: true,
      buttons: {
        'Disconnect Service': function () {
          $.ajax({
            type: 'GET',
            // url: "ajax_handler.php",
            url: '/ajax/disconnect-service',
            data: {
              // mode: "disconnectService",
              user_id: user_id
            }
          }).done(function () {
            parent.location.reload()
            $(this).dialog('close')
          })
        },
        'Cancel': function () {
          $(this).dialog('close')
        }
      }
    })
  })

  $('.profile_show_full_statement').unbind().on('click', function () {
    var button_text,
      advisory_container = $('.profile_full_statement'),
      button = $(this)

    advisory_container.slideToggle({
      done: function () {
        button_text = ($(this).is(':visible') ? 'Hide ' : 'Show ') + 'Cached Advisory'
        button.text(button_text)
      }
    })

  })
}

function monitor_location_selector_dialog () {
  // Listen for state-menu changes on the container
  $('#locations_dialog').on('change', '#state_selector', function () {
    $.ajax({
      type: 'GET',
      // url: "ajax_handler.php",
      url: '/ajax/location-options-for-state',
      data: {
        // mode: "getLocationOptionsForState",
        state: $(this).val()
      }
    }).done(function (response) {
      $('#location_selector_container').html(response)
    })
  })
}

function get_location_selector (user_id) {
  $.ajax({
    type: 'GET',
    // url: "ajax_handler.php",
    url: '/ajax/location-selector',
    data: {
      // mode: "getLocationSelector",
      user_id: user_id
    }
  }).done(function (response) {
    $('#locations_dialog').html(response).dialog({
      //height: 300,
      width: 600,
      title: 'Change Your Location',
      modal: true,
      buttons: {
        'Save': function () {
          $.ajax({
            type: 'POST',
            // url: "ajax_handler.php",
            url: '/ajax/save-location',
            data: {
              // mode: "saveLocation",
              location_id: $('select[name="location_id"]').val(),
              user_id: user_id
            }
          }).done(function () {
            parent.location.reload()
          })
        },
        'Cancel': function () {
          $(this).dialog('close')
        }
      }
    })
  })
}