<?php
/**
 * 
 * @author thomas
 *
 */
class OrderCoordinate extends ObjectModel {

	public	$id_order_coordinate;
	public	$id_order;
	public	$latitude;
	public	$longitude;
	public	$altitude;
	

	/**
	 * @see ObjectModel::$definition
	 */
	public static $definition = array(
			'table' => 'order_coordinate',
			'primary' => 'id_order_coordinate',
			'multilang' => true,
			'fields' => array(
					'id_order' =>	array('type' => self::TYPE_INT),
					'latitude' =>	array('type' => self::TYPE_FLOAT),
					'longitude' =>	array('type' => self::TYPE_FLOAT),
					'altitude' =>	array('type' => self::TYPE_FLOAT)
			)
	);

	protected $webserviceParameters = array();
	
	
	public function add($id_order) {

		$query = 'INSERT INTO `'._DB_PREFIX_.'order_coordinate` (
			`id_order`, `latitude`, `longitude`, `altitude`)
			VALUES('.(int)$id_order.','.(float)$this->latitude.','.(float)$this->longitude.',0)';
		Db::getInstance()->execute(trim($query));
	}
	
	public function getOrderState($id_order_state) {
		$res = Db::getInstance()->executeS('
			SELECT os.paid, os.shipped
			FROM `'._DB_PREFIX_.'order_state` os
			WHERE os.id_order_state= '.(int)$id_order_state);
		$order_states = array();
		if ($res)
			foreach ($res AS $row)
				$order_states[] = (int)$row;
			return $order_states;
	}
	
	public function updateCoord() {
		$query = 'UPDATE `'._DB_PREFIX_.'order_coordinate` ordcoord SET latitude='.(float)$this->latitude.', longitude='.(float)$this->longitude.'
							WHERE id_order_coordinate='.$this->id_order_coordinate;
		Db::getInstance()->execute(trim($query));
	}
	
	public function delete() {
		$query = 'DELETE FROM `'._DB_PREFIX_.'order_coordinate` WHERE id_order_coordinate='.(int)$this->id_order_coordinate;
		
		Db::getInstance()->execute(trim($query));
	}
	

}