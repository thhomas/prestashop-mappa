if(center === undefined) {
  center = [267540.85, 5873745.79]
}

if(zoom === undefined) {
  zoom = 4
}

var map = new ol.Map({
target: 'olmap',
layers: [
  new ol.layer.Tile({
    //source: new ol.source.MapQuest({layer: 'sat'})
    //source: new ol.source.Stamen({layer: 'watercolor'})
	source: new ol.source.OSM()
  })],
  view: new ol.View({
	center: center,
	zoom: zoom
  })
});

var orders=JSON.parse(ordersJson);
var features = [];
for(var i=0; i<orders.length; i++) {
	var coordinates = [parseFloat(orders[i].longitude), parseFloat(orders[i].latitude)];
	features[i] = new ol.Feature(new ol.geom.Point(ol.proj.transform(coordinates, 'EPSG:4326', 'EPSG:3857')));
}

var source = new ol.source.Vector({
	features: features
});

/*this.olLayer = new ol.layer.Tile({
  title: this.title,
  source: new ol.source.XYZ({
    url: 'http://api.tiles.mapbox.com/v4/thhomas.mm528k4p/{z}/{x}/{y}.png?access_token=pk.eyJ1IjoidGhob21hcyIsImEiOiJoS2tBb3lRIn0.Q76NgVSZUo4RO-F8HKIXOQ',
    crossOrigin: 'anonymous',
    attributions: [new ol.Attribution({
      html: '<a href="https://www.mapbox.com/about/maps/" target="_blank">Maps &copy; Mapbox &copy;</a>'
    })],
  })
});*/


var clusterSource = new ol.source.Cluster({
  distance: 40,
  source: source
});

var styleCache = {};
var clusters = new ol.layer.Vector({
  source: clusterSource,
  style: function(feature, resolution) {
    var size = feature.get('features').length;
    var style = styleCache[size];
    if (!style) {
      style = [new ol.style.Style({
        image: new ol.style.Circle({
          radius: 10,
          stroke: new ol.style.Stroke({
            color: '#fff'
          }),
          fill: new ol.style.Fill({
            color: '#3399CC'
          })
        }),
        text: new ol.style.Text({
          text: size.toString(),
          fill: new ol.style.Fill({
            color: '#fff'
          })
        })
      })];
      styleCache[size] = style;
    }
    return style;
  }
});

map.addLayer(clusters);