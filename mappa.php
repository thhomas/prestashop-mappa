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
				!$this->registerHook('actionPaymentConfirmation') ||
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
	
	public function hookActionPaymentConfirmation($params) {
		
		include_once(dirname(__FILE__).'/classes/OrderCoordinate.php');
		//$order = $this->getOrder();
		$order_address = $this->getOrderAddress($params['id_order']);
		
		$location = $this->getAddressCoordinate($order_address);
		
		$orderCoord = new OrderCoordinate();
		$orderCoord->latitude = $location['lat'];
		$orderCoord->longitude = $location['lng'];
		
		$orderCoord->add($params['id_order']);
		
	}
	

	public function getOrder($id_order) {
		$res = Db::getInstance()->executeS('
			SELECT ord.id_category, ord.id_product_comment_criterion
			FROM `'._DB_PREFIX_.'product_comment_criterion_category` pccc
			WHERE pccc.id_product_comment_criterion = '.(int)$id_order);
		$criterions = array();
		if ($res)
			foreach ($res AS $row)
				$criterions[] = (int)$row['id_category'];
				return $criterions;
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
		
		$result = curl_exec($curl);
		
		return json_decode($result, true)['results'][0]['geometry']['location'];
	}	
	
}