const animationDelay = 200;
const geoWidgetSrcId = 'inpostoc3-geowidget-source';
const geoWidgetStyleId = 'inpostoc3-geowidget-style';
const geoWidgetScriptSrc = '<script src=\"https:\/\/geowidget.easypack24.net\/js\/sdk-for-javascript.js\" id=\"' + geoWidgetSrcId + '\"><\/script>';
const geoWidgetStyleSrc = '<link rel=\"stylesheet\" href=\"https://geowidget.easypack24.net/css/easypack.css\" id=\"' + geoWidgetStyleId + '\" />';


$(document).ready(function() {  

  $(geoWidgetScriptSrc).appendTo('head');
  $(geoWidgetStyleSrc).insertAfter('#' + geoWidgetSrcId);

const ipoc3 = new InPostOC3('inpostoc3-geowidget');
try {
  window.easyPackAsyncInit = function() {
    easyPack.init(ipoc3.ePMP);
    //var map 
    var map = easyPack.mapWidget(ipoc3.geoWDivId, function(point) {
      handlePointSelection(point, ipoc3.geoWDivId);
      ipoc3.selectedPoint = point;
      postSelectedPoint();
    });
  };
} 
catch (err) {
  console.log('Can\'t init the EasyPack geoWidget');
  console.log(err);
}

$("#collapse-shipping-method").on('change', 'input[name="shipping_method"]:checked', function(e) {
  // if this is inpostoc3 selected
  //console.log($(this).attr('id'));
  if ( $(this).val().split(".")[0] === ipoc3.extensionName ) 
  {
    // if div with map exists and no point selected - just show it upon selection of shipping option
    if ( $('#' + ipoc3.geoWDivId).length && $('#'+ipoc3.geoWDivId + '-selected-point').length == 0 ) {
      $('#' + ipoc3.geoWDivId).show(animationDelay);
      // todo: if point selected, focus map around it
      if( 'name' in ipoc3.selectedPoint  ) {
        //console.log(ipoc3.selectedPoint);
      }
    } else {
      // if it doesn't, create the div, so the map can get rendered
      $(e.target).closest("div").append('<div id=\"' + ipoc3.geoWDivId + '\"></div>');
    }
  } 
  else 
  { 
    // if it's different shipping method selected, hide map
    $('#' + ipoc3.geoWDivId).hide(animationDelay);
  }
}); 

$(document).on('click' , '#'+ipoc3.geoWDivId + '-selected-point' , function() {
  if ( $('#'+ipoc3.geoWDivId).is(":visible")  ) {
      $('#'+ipoc3.geoWDivId).hide(animationDelay);
  } else {
    $('#'+ipoc3.geoWDivId).show(animationDelay);
  }
});
});


// class, as in future easyPackMapParams will depend on shipping address  
const InPostOC3 = class {
constructor(geoWidgetDivId = 'easypack-map',
             shippingExtName = 'inpostoc3') {

  this.geoWidgetDivId = geoWidgetDivId;
  this.shippingExtName = shippingExtName;
  this.easyPackMapParams = {
    instance: 'pl',
    mapType: 'osm',
    searchType: 'osm',
    points: {
      types: ['parcel_locker_only']
    },
    map: {
      initialTypes: ['parcel_locker_only']
    }
  };
  this.selectedPoint = {};
}

set geoWDivId(x) {
  this.geoWidgetDivId = x;
}

get geoWDivId() {
  return this.geoWidgetDivId;
}

get extensionName() {
    return this.shippingExtName;
}

get ePMP() {
  return this.easyPackMapParams;
}
}




function handlePointSelection(point, mapdivid) {
  //console.log('enterign callback with ajax');
  if( $('#'+ mapdivid +'-selected-point').length ){
    $('#'+ mapdivid +'-selected-point').text( ' ('+ point.name +')');
  } else{
  $('<span id=\"'+ mapdivid +'-selected-point\" style=\"cursor:pointer;color:blue;text-decoration:underline;\">('+ point.name +')</span>').insertBefore('#'+mapdivid)
  }
  // todo: change label value, so the backend controller can grab selected point upon POST
  $('#'+mapdivid).hide(animationDelay);
  // set point.name to a 'value' attribute of radio button in order to grab it later in backend
  // replace previously selected point with current selection
  var val = $('input:radio[name="shipping_method"]:checked').val();
  val = val.split('.');
  if ( val.length < 3 ) { val.push(point.name); }
  else {val[2] = point.name; }
  $('input:radio[name="shipping_method"]:checked').val(val.join("."));
}

function postSelectedPoint(){
  $.ajax({
    url: 'index.php?route=extension/shipping/inpostoc3/saveSelectedPoint',
    type: 'post',
    data: $('#collapse-shipping-method input[type=\'radio\']:checked'),
    dataType: 'json',
   success: function() {
      //console.log('index.php?route=extension/shipping/saveSelectedPoint called');
    },
    error: function(xhr, ajaxOptions, thrownError) {
      alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
    }
  });
}


