const animationDelay = 200;
const geoWidgetSrcId = 'inpostoc3-geowidget-source';
const geoWidgetStyleId = 'inpostoc3-geowidget-style';
const geoWidgetScriptSrc = '<script src=\"https:\/\/geowidget.easypack24.net\/js\/sdk-for-javascript.js\" id=\"' + geoWidgetSrcId + '\"><\/script>';
const geoWidgetStyleSrc = '<link rel=\"stylesheet\" href=\"https://geowidget.easypack24.net/css/easypack.css\" id=\"' + geoWidgetStyleId + '\" />';
const geoWidgetDefaultMapType = 'osm';
// use ajv or other JSONSchema based validator in future, share schema between catalog and admin
/* const geoWidgetAllowedParameters = {
  "defaultLocale": ['pl','uk'],
  "locale": ['pl','uk','it'],
  "mapType": ['osm','google'],
  "searchType" : ['osm', 'google'],
  "points" : {
    "types": ['pop', 'parcel_locker', 'parcel_locker_only'],  # Options: parcel_locker_only, parcel_locker, pop    
    "allowedToolTips": ['pok', 'pop'],
    "functions": ['parcel','parcel_send','parcel_collect'] //avail. functions: parcel, parcel_send, parcel_collect
  }, 
  "map" : {
     "googleKey": '', // required to use Google Maps API
      "useGeolocation": true, // ['true','false'], default: true
      "initialZoom": 13, // default: 13
    "detailsMinZoom": 15, // minimum zoom after marker click
    autocompleteZoom: 14,
    visiblePointsMinZoom: 13,
    "defaultLocation": [52.229807, 21.011595],
    "initialTypes": ['pop', 'parcel_locker', 'parcel_locker_only'], // which type should be selected by default. Options: parcel_locker_only, parcel_locker, pop
  },
  "paymentFilter": {
    "visible": ['true','false'], //default: false zezwala na wyświetlenie filtra płatnosć w paczkomacie
    "defaultEnabled": ['true','false'], //default: false, włączony filtr dla płatności w paczkomacie już przy inicjalizacji
    "showOnlyWithPayment": ['true','false'] //default: false, wymusza pokazywanie obiektów tylko z płatnością w paczkomacie
  }
}; // https://dokumentacja-inpost.atlassian.net/wiki/spaces/PL/pages/7438409/Geowidget+v4+User+s+Guide+New
*/

$(document).ready(function() {

  $(geoWidgetScriptSrc).appendTo('head');
  $(geoWidgetStyleSrc).insertAfter('#' + geoWidgetSrcId);

  

  $('[id^="input-inpostoc3-sender-selected-point-"]').on('click', function() {
    var data = {};
    data.buttonElementId = $(this).attr('id');
    var items = $(this).attr('id').split('-');
    data.orderId = items[5];
    data.shipmentId = items[6];
    data.countryIsoCode2 = $('#input-inpostoc3-sender-addr-country-code-' + data.orderId + '-' + data.shipmentId).text().toLowerCase();
    console.log(easyPackWidget(data));
    //openModal(data);
  });
  
  //jQuery starts with
  $('[id^="input-inpostoc3-service-"]').on('change',function() {
    //console.log('new: ' + $(this).attr('id') + '  ' + $(this).val());
    var serviceId = $(this).val();
    var items = $(this).attr('id').split('-');
    var sending_method_eid = [];
    sending_method_eid[0] = 'input-inpostoc3-sending-method';
    sending_method_eid[1] = items[3] + '-' + items[4];
    //console.log('join :' + sending_method_eid.join('-'));
    $("#" + sending_method_eid.join('-') ).empty();
    $("#" + sending_method_eid.join('-') ).append(
      "<option value=\"0\"> --- None --- </option>"
    );
    $("#" + sending_method_eid.join('-') +" option[value=\"0\"]").attr('selected','selected').change(); // imitate actual selection to trigger events
    // console.log("input-inpostoc3-receiver-selected-point-" + items[3] + '-' + items[4]);
    if ( $(this).val() == 1 ) {
      $("#input-inpostoc3-receiver-selected-point-" + items[3] + '-' + items[4] ).prop('disabled', false);
    } else {
      $("#input-inpostoc3-receiver-selected-point-" + items[3] + '-' + items[4] ).prop('disabled', true);
    }
    
    //console.log('index.php?route=extension/shipping/inpostoc3/sendingmethodsforservice&service_id=' + serviceId + '&user_token=' + getUserToken());
    $.ajax({
      url: 'index.php?route=extension/shipping/inpostoc3/sendingmethodsforservice&service_id=' + serviceId + '&user_token=' + getUserToken(),
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
    var ending = items[4] + '-' + items[5];
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
    
  $(document).on('change', '[id^="input-inpostoc3-sending-method-"]', function() {
    var items = $(this).attr('id').split('-');
    if ( $(this).val() == 1 ) {
      $("#input-inpostoc3-sender-selected-point-" + items[4] + '-' + items[5] ).prop('disabled', false);
    } else {
      $("#input-inpostoc3-sender-selected-point-" + items[4] + '-' + items[5] ).prop('disabled', true);
    }
  });

});




function getUserToken() {
  var url = window.location;
    var access_token = new URLSearchParams(url.search).get('user_token');
    return access_token;
}

function reloadJs(src) {
  src = $('script[src$="' + src + '"]').attr("src");
  $('script[src$="' + src + '"]').remove();
  $('<script/>').attr('src', src).appendTo('head');
}

function easyPackWidget(data) {
  if ( !('mapInit' in data) ) {
    data.mapInit = {};
  }
  data.mapInit.defaultLocale = data.countryIsoCode2;
  data.mapInit.mapType = geoWidgetDefaultMapType;
  data.mapInit.searchType = geoWidgetDefaultMapType;

  if ( !('sendingMethod' in data) ) {
    data.sendingMethod = {};
  }
  data.sendingMethod.id = $('#input-inpostoc3-sending-method-' + data.orderId + '-' + data.shipmentId ).val();
  //console.log('#input-inpostoc3-sending-method-' + data.orderId + '-' + data.shipmentId + '.val():' + $('#input-inpostoc3-sending-method-' + data.orderId + '-' + data.shipmentId ).val() )
  $.ajax({
    url: 'index.php?route=extension/shipping/inpostoc3/sendingmethod&sending_method_id=' + data.sendingMethod.id + '&user_token=' + getUserToken(),
    type: 'get',
    error: function(xhr, ajaxOptions, thrownError) {
      alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
    },
    success: function(retdata, status) {
      if ($.trim(retdata)){   
        //console.log("What follows is not blank: " + data[serviceId]);
        $.each( retdata, function( smId, smObj ) {
          console.log(smObj);
          if (data.sendingMethod.id = smObj.id ) {
            data.sendingMethod = smObj;
            if ( data.sendingMethod.sending_method_identifier == 'parcel_locker' ) {
              data.isDropOffPoint = true
            }
            if ( !('points' in data) ) {
              data.mapInit.points = {};
            }
            data.mapInit.points.types = ['parcel_locker'];
            if (data.isDropOffPoint) {
              data.mapInit.points.functions = ['parcel_send'];
            } else {
              data.mapInit.points.functions = [];
            }
            if ( !('map' in data) ) {
              data.mapInit.map = {};
            }
            data.mapInit.map.initialTypes = ['parcel_locker'];
            return false; //ought to be only one, yet if that's not the case as a rule of thumb - first entry taken, break the loop
          }
        });  
        console.log('before init: '); 
        console.log(data);
        // init
        easyPack.init(data.mapInit);

        // run modal 
        openModal(data);
        return data;    
      }
    }
  });
}

function openModal(data) {
  easyPack.modalMap(function(point, modal) {
    modal.closeModal();
    console.log(point);
    if ( !('selectedPoint' in data) ) {
      data.selectedPoint = {};
    }
    data.selectedPoint = point;
  }, { width: 500, height: 600 });
}
