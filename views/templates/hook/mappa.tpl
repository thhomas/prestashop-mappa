{addJsDef orders=$orders|escape:'html':'UTF-8'}
<div id="mappa">
	<div id="olmap" class="map"></div>
</div>
<script type="text/javascript">
	var ordersJson = '{$orders}';
	var latitude_center = '{$latitude_center}';
  var longitude_center = '{$longitude_center}';
  var zoom = '{$zoom}';
	var center = ol.proj.transform([parseFloat(longitude_center), parseFloat(latitude)], 'EPSG:4326', 'EPSG:3857');
</script>
<script type="text/javascript" src="{$base_dir}/modules/mappa/mappa.js"></script>