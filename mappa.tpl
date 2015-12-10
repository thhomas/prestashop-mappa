{addJsDef orders=$orders|escape:'html':'UTF-8'}
<div id="mappa">
	<div id="olmap" class="map"></div>
</div>
<script type="text/javascript">
	var ordersJson = '{$orders}';
</script>
<script type="text/javascript" src="/prestashop/modules/mappa/mappa.js"></script>