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

    $this->displayName = $this->l('Mappa');
    $this->description = $this->l('Display order on map.');

    // Message show when you wan to delete the module
    $this->confirmUninstall = $this->l('Are you sure you want to delete this module ?');
    
    parent::__construct();
	}

	public function install() {
		if (!parent::install() ||
				!$this->registerHook('actionOrderStatusUpdate') ||
				!$this->registerHook('displayHomeTab') ||
				!$this->installDb() ||
				!$this->addAdminTab() ||
				!Configuration::updateValue('MAPPA_NAME', 'Mappa'))
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
		$id_parent = Tab::getIdFromClassName('AdminParentOrders');
		$adminTab = new Tab();
		$languages = Language::getLanguages(true);
		$adminTab->name = array();
		foreach($languages as $lang) {
			$adminTab->name[$lang['id_lang']] = (('fr' == $lang['iso_code']) ? 'Carte des commandes': 'Order map');
		}
		$adminTab->class_name = 'AdminMappa';
		$adminTab->module = $this->name;
		$adminTab->id_parent = $id_parent;
		$adminTab->active = true;
		$adminTab->position = Tab::getNbTabs($id_parent);
		
		if (!$adminTab->save())
			return false;
		Configuration::updateValue('ADMIN_TAB_MODULE_MAPPA', $adminTab->id);
			return true;
	}
	
	
	public function removeAdminTab() {
		$adminTabId = Configuration::get('ADMIN_TAB_MODULE_MAPPA');
		
		if(Tab::existsInDatabase($adminTabId, Tab::$definition['table'])) {
			$adminTab = new Tab($adminTabId);
			if(!$adminTab->delete()) 
				return false;
			return Configuration::deleteByName('ADMIN_TAB_MODULE_MAPPA');
		}
		
		return true;
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
	
	public function hookDisplayHomeTab($params) {
		$this->context->smarty->assign(
			array(
					'my_module_name' => Configuration::get('Mappa'),
					'my_module_link' => $this->context->link->getModuleLink('mappa', 'display')
			)
		);
		$this->context->smarty->assign('orders', json_encode($this->getOrderCoordinates()));
		
		$this->context->controller->addCSS('http://openlayers.org/en/v3.11.2/css/ol.css', 'all');
		$this->context->controller->addJS('http://openlayers.org/en/v3.11.2/build/ol.js', 'all');
		//$this->context->controller->addJS($this->_path.'mappa.js', 'all');

		return $this->display(__FILE__, 'views/template/hook/mappa.tpl');
	}
	
	public function hookActionOrderStatusUpdate($params) {
		
		include_once(dirname(__FILE__).'/classes/OrderCoordinate.php');
		$id_order = $params['id_order'];
		if($params['newOrderStatus']->paid == 1) {		
			$order_address = $this->getOrderAddress($params['id_order']);
			$location = $this->getAddressCoordinate($order_address);
			$orderCoord = new OrderCoordinate();
			$orderCoord->latitude = $location['lat'];
			$orderCoord->longitude = $location['lng'];
			
			$orderCoord->add($id_order);
		} else {
			$orderCoord = $this->getOrderCoordFromOrder($id_order);
			$orderCoord->delete();
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
			$orderCoord->id_order_coordinate = $orderCoordFromDb['id_order_coordinate'];
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
		
		$result = curl_exec($curl);
		
		return json_decode($result, true)['results'][0]['geometry']['location'];
	}	
	
	public function getOrderCoordinates() {
		$address = Db::getInstance()->executeS('
			SELECT latitude, longitude
			FROM `'._DB_PREFIX_.'order_coordinate`');
		
		return $address;
	}
	

	public function getContent() {
    	$output = null;
 
	    if (Tools::isSubmit('submit'.$this->name)) {
	        $mappa_name = strval(Tools::getValue('MAPPA_NAME'));
	        if (!$mappa_name
	          || empty($mappa_name)
	          || !Validate::isGenericName($mappa_name))
	            $output .= $this->displayError($this->l('Invalid Configuration value'));
	        else
	        {
	            Configuration::updateValue('MAPPA_NAME', $mappa_name);
	            $output .= $this->displayConfirmation($this->l('Settings updated'));
	        }
	    }
	    return $output.$this->displayForm();
	}
	
	public function displayForm() {
		// Get default language
		$default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
		 
		// Init Fields form array
		$fields_form[0]['form'] = array(
				'legend' => array(
						'title' => $this->l('Settings'),
				),
				'input' => array(
						array(
								'type' => 'text',
								'label' => $this->l('Configuration value'),
								'name' => 'MAPPA_NAME',
								'size' => 20,
								'required' => true
						)
				),
				'submit' => array(
						'title' => $this->l('Save'),
						'class' => 'button'
				)
		);
		 
		$helper = new HelperForm();
		 
		// Module, token and currentIndex
		$helper->module = $this;
		$helper->name_controller = $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
		 
		// Language
		$helper->default_form_language = $default_lang;
		$helper->allow_employee_form_lang = $default_lang;
		 
		// Title and toolbar
		$helper->title = $this->displayName;
		$helper->show_toolbar = true;        // false -> remove toolbar
		$helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
		$helper->submit_action = 'submit'.$this->name;
		$helper->toolbar_btn = array(
				'save' =>
				array(
						'desc' => $this->l('Save'),
						'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
						'&token='.Tools::getAdminTokenLite('AdminModules'),
				),
				'back' => array(
						'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
						'desc' => $this->l('Back to list')
				)
		);
		 
		// Load current value
		$helper->fields_value['MAPPA_NAME'] = Configuration::get('MAPPA_NAME');
		 
		return $helper->generateForm($fields_form);
	}
	

}