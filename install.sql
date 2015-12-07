
CREATE TABLE IF NOT EXISTS `PREFIX_order_coordinate` (
  `id_order_coordinate` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_order` int(11) UNSIGNED NOT NULL ,
  `latitude` float NOT NULL ,
  `longitude` float NOT NULL ,
  `altitude` float NOT NULL ,
  PRIMARY KEY ( `id_order_coordinate` , `id_order` )
) ENGINE=ENGINE_TYPE  DEFAULT CHARSET=utf8;
