const animationDelay = 200;
const geoWidgetSrcId = 'inpostoc3-geowidget-source';
const geoWidgetStyleId = 'inpostoc3-geowidget-style';
const geoWidgetScriptSrc = '<script src=\"https:\/\/geowidget.easypack24.net\/js\/sdk-for-javascript.js\" id=\"' + geoWidgetSrcId + '\"><\/script>';
const geoWidgetStyleSrc = '<link rel=\"stylesheet\" href=\"https://geowidget.easypack24.net/css/easypack.css\" id=\"' + geoWidgetStyleId + '\" />';
const geoWidgetDefaultMapType = 'osm';
const geoWidgetDefaultLocale = ['pl', 'uk' , 'it'];
// https://dokumentacja-inpost.atlassian.net/wiki/spaces/PL/pages/7438409/Geowidget+v4+User+s+Guide+New

$(document).ready(function() {

  $(geoWidgetScriptSrc).appendTo('head');
  $(geoWidgetStyleSrc).insertAfter('#' + geoWidgetSrcId);

  

  $('[id*="-selected-point-"]').on('click', function() {
    var data = {};
    data.buttonElementId = $(this).attr('id');
    var items = $(this).attr('id').split('-');
    data.orderId = items[5];
    data.shipmentId = items[6];
    data.who = items[2];
    ( data.who == 'sender') ? data.isSender = true : data.isSender = false;
    data.service = {};
    data.service.id = $('#input-inpostoc3-service-' + data.orderId + '-' + data.shipmentId).val();
    try { 
      easyPackWidget(data)
    }
    catch (err) {
      alert(err);
      console.error(err);
      console.error(err.stack);
    }
  });

  // Cascade dependency - service -> available sending methods
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
      $("#input-inpostoc3-receiver-selected-point-" + items[3] + '-' + items[4] ).prop('required', true);
      $('#'+items[3] + '-' + items[4] +'-target-point').addClass('required');
    } else {
      $("#input-inpostoc3-receiver-selected-point-" + items[3] + '-' + items[4] ).prop('disabled', true);
      $("#input-inpostoc3-receiver-selected-point-" + items[3] + '-' + items[4] ).prop('required', false);
      $('#'+items[3] + '-' + items[4] +'-target-point').removeClass('required');
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
                $("#input-inpostoc3-sender-addr-building_number-"+ ending ).val(sObj["building_number"]);
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
      $("#input-inpostoc3-sender-selected-point-" + items[3] + '-' + items[4] ).prop('required', true);
      $('#'+items[3] + '-' + items[4] +'-dropoff-point').addClass('required');
    } else {
      $("#input-inpostoc3-sender-selected-point-" + items[4] + '-' + items[5] ).prop('disabled', true);
      $("#input-inpostoc3-sender-selected-point-" + items[3] + '-' + items[4] ).prop('required', false);
      $('#'+items[3] + '-' + items[4] +'-dropoff-point').removeClass('required');
    }
  });

  // make either address lines or street & building not required - 2 functions
  var $inputs_addr_line = $('[id*="-addr-line-1-"],[id*="-addr-line-2-"]');
  $inputs_addr_line.on('input', function () {
      // Set the required property of the other input to false if this input is not empty.
      var items = $(this).attr('id').split('-');
      var elid = {};
      elid.beginning = items[0] + '-' + items[1] + '-' + items[2];
      elid.end = items[6] + '-' + items[7];
      if ( $('#' + elid.beginning + '-addr-building_number-' + elid.end).val().length > 0  
          ||  $('#' + elid.beginning + '-addr-street-' + elid.end).val().length > 0
      ) {
        $('#' + elid.beginning + '-addr-building_number-' + elid.end).prop('required', true);
        $('#' + elid.beginning + '-addr_building_number_row-' + elid.end).addClass("required");
        $('#' + elid.beginning + '-addr-street-' + elid.end).prop('required', true);
        $('#' + elid.beginning + '-addr_street_row-' + elid.end).addClass("required");
      } else {
        $('#' + elid.beginning + '-addr-building_number-' + elid.end).prop('required', !$(this).val().length);
        $('#' + elid.beginning + '-addr_building_number_row-' + elid.end).removeClass("required");
        $('#' + elid.beginning + '-addr-street-' + elid.end).prop('required', !$(this).val().length);
        $('#' + elid.beginning + '-addr_street_row-' + elid.end).removeClass("required");
        $(this).prop('required', true);
        $('#'+ elid.beginning + '-' + items[3] + '_' + items[4] + '_' + items[5] + '_row-' + elid.end).addClass("required");
      }
  });

  var $inputs_street_buildingno = $('[id*="-addr-building_number-"],[id*="-addr-street-"]');
  $inputs_street_buildingno.on('input', function () {
    var items = $(this).attr('id').split('-');
    var elid = {};
    elid.beginning = items[0] + '-' + items[1] + '-' + items[2];
    elid.end = items[5] + '-' + items[6];
    $('#' + elid.beginning + '-addr-line-1-' + elid.end).prop('required', !$(this).val().length);
    $('#' + elid.beginning + '-addr_line_1_row-' + elid.end).removeClass("required");
    $('#' + elid.beginning + '-addr-line-2-' + elid.end).prop('required', !$(this).val().length);
    $('#' + elid.beginning + '-addr_line_2_row-' + elid.end).removeClass("required");
    $('#'+ elid.beginning + '-addr_building_number-' + elid.end).prop('required', true);
    $('#'+ elid.beginning + '-addr_building_number_row-' + elid.end).addClass("required");
    $('#'+ elid.beginning + '-addr_street-' + elid.end).prop('required', true);
    $('#'+ elid.beginning + '-addr_street_row-' + elid.end).addClass("required");
  });

  /*
  var $required_inputs = $(":input[required]");
  $required_inputs.on('input', function() {
    var formid = $(this).attr('form');
    console.log('#'+formid+' input[required]');
    $('#'+formid+' input[required]').each( function(i) {
     if ( $(this).val()=="" ) { console.log('o-o, empty' + $(this).attr('id') ); } 
    });
  });
  */

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
  
  data.mapInit.mapType = geoWidgetDefaultMapType;
  data.mapInit.searchType = geoWidgetDefaultMapType;
  data.countryIsoCode2 = $('#input-inpostoc3-' + data.who + '-addr-country_iso_code_2-' + data.orderId + '-' + data.shipmentId).val().toLowerCase();
  if ( !geoWidgetDefaultLocale.includes(data.countryIsoCode2) ) {
    console.log('element id: #input-inpostoc3-' + data.who + '-addr-country_iso_code_2-' + data.orderId + '-' + data.shipmentId);
    console.log('element val: ' + $('#input-inpostoc3-' + data.who + '-addr-country_iso_code_2-' + data.orderId + '-' + data.shipmentId).val());
    throw new GeoWidgetError('Incorrect defaultLocale for GeoWidget = "' + data.countryIsoCode2 + '".Allowed defaultLocale values = [' + geoWidgetDefaultLocale + ']');
  }
  data.mapInit.defaultLocale = data.countryIsoCode2;
  if ( !('selectedPoint' in data) ) {
    data.selectedPoint = {};
  }
  if ( $('#'+data.buttonElementId).val() ) {
    data.selectedPoint.name =  $('#'+data.buttonElementId).val();
  }
  if ( !('sendingMethod' in data) ) {
    data.sendingMethod = {};
  }
  data.sendingMethod.id = $('#input-inpostoc3-sending-method-' + data.orderId + '-' + data.shipmentId ).val();
  //console.log('#input-inpostoc3-sending-method-' + data.orderId + '-' + data.shipmentId + '.val():' + $('#input-inpostoc3-sending-method-' + data.orderId + '-' + data.shipmentId ).val() )
  $.ajax({
    url: 'index.php?route=extension/shipping/inpostoc3/sendingmethod&sending_method_id=' + data.sendingMethod.id + '&user_token=' + getUserToken(),
    type: 'get',
    error: function(xhr, ajaxOptions, thrownError) {
      console.error(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
    },
    success: function(retdata, status) {
      if ($.trim(retdata)){   
        $.each( retdata, function( smId, smObj ) {
          //console.log(smObj);
          if (data.sendingMethod.id = smObj.id ) {
            data.sendingMethod = smObj;
            ( data.sendingMethod.sending_method_identifier == 'parcel_locker' && data.isSender ) ? data.isDropOffPoint = true : data.isDropOffPoint = false;
            return false; //ought to be only one, yet if that's not the case as a rule of thumb - first entry taken, break the loop
          }
        }); 
        if ( !('points' in data) ) {
          data.mapInit.points = {};
        }
        if ( !('map' in data) ) {
          data.mapInit.map = {};
        }
        // TODO: points types and map initial types dependent on service selected
        data.mapInit.points.types = ['parcel_locker'];
        ( data.isDropOffPoint ) ? data.mapInit.points.functions = ['parcel_send'] : data.mapInit.points.functions = [];
        data.mapInit.map.initialTypes = ['parcel_locker'];
        
        //console.log('before init: '); 
       // console.log(data);
        // init
        easyPack.init(data.mapInit);
        // run modal 
        openModal(data);
        return;  
      }
    }
  });
}

function openModal(data) {
  //console.log('before calling modalMap:');
  //console.log(data);
  this.data = data; //make sure, that proper object is accessed in anonymous callback
  this.map = easyPack.modalMap(function(point, modal) {
    //console.log('inside anonymous callback this.data:');
    //console.log(this.data);
    modal.closeModal();
    //console.log(point);
    this.data.selectedPoint = point;
    $('#'+this.data.buttonElementId).val(this.data.selectedPoint.name);
  }.bind(this), // ...and bind it to make sure apporpriate this is in use
  { width: 500, height: 600 });
  this.map.searchLockerPoint(data.selectedPoint.name);
}


class GeoWidgetError extends Error {
  constructor(message) {
    super(message);
    this.name = "GeoWidgetError";
  }
}

GeoWidgetError.prototype.toString = function() {
  return this.name + ': ' + this.message;
}
