<?php
/**
* 
* 
* 
* 
* 
*/

if (!defined('_PS_VERSION_'))
	exit;

class Mappa extends Module {

	const INSTALL_SQL_FILE = 'install.sql';
	
	public function __construct() {
		$this->name = 'mappa';
		$this->tab = 'Test';
	    $this->version = 1.0;
	    $this->author = 'Thomas Tilak';
	    $this->need_instance = 0;
	    
	    parent::__construct();

	    $this->displayName = $this->l('Mappa');
	    $this->description = $this->l('Display order on map.');
	}

	public function install() {
		if (!parent::install() ||
				!$this->registerHook('actionOrderStatusUpdate') ||
				!$this->installDb() ||
				!$this->addAdminTab())
			return false;
			return true;
	}
	
	public function uninstall() {
		if (!parent::uninstall() ||
				!$this->uninstallDb() ||
				!$this->removeAdminTab())
			return false;
			return true;
	}
	

	public function addAdminTab() {
		// création de l'onglet
		$tab = new Tab();
		foreach(Language::getLanguages(false) as $lang)
			$tab->name[(int) $lang['id_lang']] = 'Mappa';
			// Nom du controller sans le mot clé "Controller"
			$tab->class_name = 'AdminMappa';
			$tab->module = $this->name;
			$tab->id_parent = 15;
			if (!$tab->save())
				return false;
				return true;
	}
	
	
	public function removeAdminTab() {
		$classNames = array('admin_mappa' => 'AdminMappa');
		$return = true;
		foreach ($classNames as $key => $className) {
			$tab = new Tab(Tab::getIdFromClassName($className));
			$return &= $tab->delete();
		}
		return $return;
	}
	
	
	public function installDb() {

		if (!file_exists(dirname(__FILE__).'/'.self::INSTALL_SQL_FILE))
			return false;
		else if (!$sql = file_get_contents(dirname(__FILE__).'/'.self::INSTALL_SQL_FILE))
			return false;
		$sql = str_replace(array('PREFIX_', 'ENGINE_TYPE'), array(_DB_PREFIX_, _MYSQL_ENGINE_), $sql);
		$sql = preg_split("/;\s*[\r\n]+/", trim($sql));
		
		foreach ($sql as $query)
			if (!Db::getInstance()->execute(trim($query)))
				return false;
		return true;
	}
	

	public function uninstallDb() {
		return Db::getInstance()->execute('
			DROP TABLE IF EXISTS
			`'._DB_PREFIX_.'order_coordinate`');
	}
	
	public function hookActionOrderStatusUpdate($params) {
		
		include_once(dirname(__FILE__).'/classes/OrderCoordinate.php');
		print_r($params);
		$order = $this->getOrder();
		$order_address = $this->getOrderAddress($params['id_order']);
		
		$orderCoord = $this->getOrderCoordFromOrder($params['id_order']);
		d($orderCoord);

		$location = $this->getAddressCoordinate($order_address);
		if(orderCoord == False) {
			$orderCoord = new OrderCoordinate();
			$orderCoord->latitude = $location['lat'];
			$orderCoord->longitude = $location['lng'];
			
			$orderCoord->add($params['id_order']);
		} else {
			$orderCoord->latitude = $location['lat'];
			$orderCoord->longitude = $location['lng'];
			$orderCoord->updateCoord();
		}
		
		
		
	}
	
	public function getOrderCoordFromOrder($id_order) {
		$orderCoordFromDb = Db::getInstance()->getRow('
			SELECT ordcoord.id_order_coordinate, ordcoord.latitude, ordcoord.longitude, ordcoord.altitude
			FROM `'._DB_PREFIX_.'order_coordinate` ordcoord
			WHERE ordcoord.id_order= '.(int)$id_order);
		if($orderCoordFromDb == False) {
			$orderCoord = False;
		} else {
			$orderCoord = new OrderCoordinate();
			$orderCoord->id_order = $id_order;
			$orderCoord->latitude = $orderCoordFromDb['latitude'];
			$orderCoord->longitude = $orderCoordFromDb['longitude'];
			$orderCoord->altitude = $orderCoordFromDb['altitude'];
		}
		return $orderCoord;
	}
	
	public function getOrderAddress($id_order) {
		$id_address = Db::getInstance()->getValue('
			SELECT ord.id_address_delivery
			FROM `'._DB_PREFIX_.'orders` ord
			WHERE ord.id_order = '.(int)$id_order);
		$address = Db::getInstance()->getRow('
			SELECT addr.address1, addr.address2, addr.postcode, addr.city
			FROM `'._DB_PREFIX_.'address` addr
			WHERE addr.id_address = '.(int)$id_address);
		
		return $address;
	}
	
	public function getAddressCoordinate($address) {
		$address1 = $address['address1'];
		$address2 = $address['address2'];
		$postcode = $address['postcode'];
		$city = $address['city'];
		
		$curl = curl_init();
		$location = curl_escape($curl, $address1.','.$city);
		$req = "https://maps.googleapis.com/maps/api/geocode/json?address={$location}";
		$curl = curl_init($req);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_PROXY, 'http://proxy.ign.fr:3128');
		
		$result = curl_exec($curl);
		
		return json_decode($result, true)['results'][0]['geometry']['location'];
	}	
	
}