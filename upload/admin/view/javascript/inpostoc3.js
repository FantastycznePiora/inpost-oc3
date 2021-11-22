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

  $('[id^="input-inpostoc3-select-sender-"]').on('change',function() {
    //console.log('new: ' + $(this).attr('id') + '  val: ' + $(this).val());
    if ( $(this).val() == 0 ) { return ;}
    var items = $(this).attr('id').split('-');
    //console.log('items: ' + items);
    var elements = { 
      "const" : "input-inpostoc3",
      "order_id" : items[4],
      "shipment_id" : items[5] 
    };
    var ending = items[5] + '-' + items[5];
    //console.log('shipment: ' + shipment["id"]);
    var sender_id = $(this).val();

    $.ajax(
      {
        url: 'index.php?route=extension/shipping/inpostoc3/senders&sender_id=' + sender_id + '&user_token=' + getUserToken(),
        type: 'get',
        error: function(xhr, ajaxOptions, thrownError) {
          alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        },
        success: function(data, status) {
          if ($.trim(data)){   
            $.each( data, function( saId, saObj ) {
              $.each (saObj, function(sId, sObj) {
                console.log(' sender_id + company name: ' + sObj["id"] + '  ' + sObj["company_name"]); 
                $("#input-inpostoc3-sender-name-"+ ending ).val(sObj["name"]);
                $("#input-inpostoc3-sender-company-name-"+ ending ).val(sObj["company_name"]);
                $("#input-inpostoc3-sender-first-name-"+ ending ).val(sObj["first_name"]);
                $("#input-inpostoc3-sender-last-name-"+ ending ).val(sObj["last_name"]);
                $("#input-inpostoc3-sender-email-"+ ending ).val(sObj["email"]);
                $("#input-inpostoc3-sender-phone]-"+ ending ).val(sObj["phone"]);
                $("#input-inpostoc3-sender-addr-street-"+ ending ).val(sObj["street"]);
                $("#input-inpostoc3-sender-addr-building-number-"+ ending ).val(sObj["building_number"]);
                $("#input-inpostoc3-sender-addr-line-1-"+ ending ).val(sObj["line1"]);
                $("#input-inpostoc3-sender-addr-line-2-"+ ending ).val(sObj["line2"]);
                $("#input-inpostoc3-sender-addr-city-"+ ending ).val(sObj["city"]);
                $("#input-inpostoc3-sender-addr-post-code-"+ ending ).val(sObj["post_code"]);
                $("#input-inpostoc3-sender-addr-country-code-"+ ending).val(sObj["country_iso_code_2"]);
            });       
            });
          }
        }
      });
    });
    

});

$(document).on('change', '[id^="input-inpostoc3-sending-method-"]', function() {
  if ( $(this).val() == 1 ) {
    $('[id^="input-inpostoc3-sender-selected-point-"]').prop('disabled', false);
  } else {
    $('[id^="input-inpostoc3-sender-selected-point-"]').prop('disabled', true);
  }
});


function getUserToken() {
  var url = window.location;
    var access_token = new URLSearchParams(url.search).get('user_token');
    return access_token;
}