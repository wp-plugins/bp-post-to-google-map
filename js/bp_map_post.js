var infowindow;
jQuery(function(){

	if(jQuery("input#bp_map_show").is(':checked'))
		jQuery("#bp_map_post_opts").show();  // checked
	else
		jQuery("#bp_map_post_opts").hide();  // unchecked
	
	jQuery('input#bp_map_show').click(function(){
			jQuery('#bp_map_post_opts').toggle(this.checked);
	});
	
	if(jQuery('#bpmap-canvas').html()=='Loading...'){
		loadMap();
	}
});

function initMap() {
	/**necessary to track infowindows and close them**/
	google.maps.Map.prototype.markers = new Array();

	google.maps.Map.prototype.addMarker = function(marker) {
		this.markers[this.markers.length] = marker;
	  };
		
	google.maps.Map.prototype.getMarkers = function() {
		return this.markers
	  };
		
	google.maps.Map.prototype.clearMarkers = function() {
		if(infowindow) {
		  infowindow.close();
		}
	}
	
	var centerMap = new google.maps.LatLng(bpOpts.centerlat,bpOpts.centerlng);
	
	var mapOptions = {
		zoom: bpOpts.zoom*1,
		center: centerMap
	};
	
	var map = new google.maps.Map(document.getElementById('bpmap-canvas'),
	 mapOptions);


	var data = {
		'action': 'bp_map_data',
		'dataType': 'JSON'
	};
	jQuery.post(ajaxurl, data, function(str) {
		//console.log("Sent String:",str);
		if(str.substring(str.length-1,str.length)=='0'){
			str = str.substring(0, str.length - 1);
		}
		str = JSON.parse(str);

		for(var i=0; i< str.length; i++){
			var latlng = new google.maps.LatLng(str[i]['lat'], str[i]['lng']);
			map.addMarker(bpAddMarker(map,latlng, str[i]['title'], str[i]['html']));	
		}
		//console.log(map.getMarkers()); 

	});
}

function loadMap() {
  var script = document.createElement('script');
  script.type = 'text/javascript';
  script.src = 'https://maps.googleapis.com/maps/api/js?v=3.exp' +
      '&signed_in=true&callback=initMap';
  document.body.appendChild(script);
}

function bpAddMarker(map,location,title,html){
	var marker = new google.maps.Marker({
		position: location,
		map: map,
		title: title	
	});
	google.maps.event.addListener(marker, 'click', function() {
		if(infowindow){infowindow.close();}
      infowindow = new google.maps.InfoWindow({content: html});
      infowindow.open(map, marker);
  	});
	
	return marker;

}
