$(document).ready(function() {

  //jQuery starts with
  $('[id^="input-inpostoc3-service-"]').on('change',function() {
    console.log('new: ' + $(this).attr('id') + '  ' + $(this).val());
    var serviceId = $(this).val();
    var items = $(this).attr('id').split('-');
    var sending_method_eid = [];
    sending_method_eid[0] = 'input-inpostoc3-sending-method';
    sending_method_eid[1] = items[3] + '-' + items[4];
    console.log('join :' + sending_method_eid.join('-'));
    $("#" + sending_method_eid.join('-') ).empty();
    $("#" + sending_method_eid.join('-') ).append(
      "<option value=\"0\"> --- None --- </option>"
    );
    //console.log('index.php?route=extension/shipping/inpostoc3/sendingmethods&service_id=' + serviceId + '&user_token=' + getUserToken());
    $.ajax({
      url: 'index.php?route=extension/shipping/inpostoc3/sendingmethods&service_id=' + serviceId + '&user_token=' + getUserToken(),
      type: 'get',
      error: function(xhr, ajaxOptions, thrownError) {
        alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
      },
      success: function(data, status) {
        if ($.trim(data)){   
          //console.log("What follows is not blank: " + data[serviceId]);
          $.each( data, function( sId, serviceObj ) {
            $.each (serviceObj, function(smId, smObj) {
              $("#" + sending_method_eid.join('-') ).append(
                "<option value=\""+ smObj.sending_method_id +"\">"+ smObj.description +"</option>"
              );
            });
          });       
        }
      }
    });
  });

});


function getUserToken() {
  var url = window.location;
    var access_token = new URLSearchParams(url.search).get('user_token');
    return access_token;
}