-- CREATE TABLE QUERIES
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `is_vegetarian` tinyint(4) DEFAULT NULL,
  `latitude` double(9,6) NOT NULL,
  `longitude` double(9,6) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `food_order`;
CREATE TABLE `food_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `restaurant_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('Completed','Cancelled','Claimed') NOT NULL DEFAULT 'Completed',
  `total_price` float NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `food_order_restaurant_id_fk` (`restaurant_id`),
  KEY `food_order_user_id_fk` (`user_id`),
  KEY `status` (`status`),
  CONSTRAINT `food_order_restaurant_id_fk` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurant` (`id`),
  CONSTRAINT `food_order_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `food_order_details`;
CREATE TABLE `food_order_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `food_order_id` int(11) NOT NULL,
  `restaurant_menu_items_id` int(11) NOT NULL,
  `status` enum('Completed','Cancelled','Claimed') NOT NULL DEFAULT 'Completed',
  `price` double NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `food_order_details_restaurant_menu_items_id_fk` (`restaurant_menu_items_id`),
  KEY `fod_status` (`status`),
  KEY `food_order_details_food_order_id_fk` (`food_order_id`),
  CONSTRAINT `food_order_details_food_order_id_fk` FOREIGN KEY (`food_order_id`) REFERENCES `food_order` (`id`),
  CONSTRAINT `food_order_details_restaurant_menu_items_id_fk` FOREIGN KEY (`restaurant_menu_items_id`) REFERENCES `restaurant_menu_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `restaurant`;
CREATE TABLE `restaurant` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `latitude` double(9,6) NOT NULL,
  `longitude` double(9,6) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `latitude` (`latitude`,`longitude`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `restaurant_menu_items`;
CREATE TABLE `restaurant_menu_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `restaurant_id` int(11) NOT NULL,
  `menu_items` varchar(200) NOT NULL,
  `is_vegetarian` tinyint(4) DEFAULT NULL,
  `is_sensitive` tinyint(4) DEFAULT NULL,
  `price` float DEFAULT NULL,
  `discounted_price` float NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `restaurant_id_fk` (`restaurant_id`),
  CONSTRAINT `restaurant_id_fk` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurant` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `claimed_order`;
CREATE TABLE `claimed_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `total_amount` int(11) NOT NULL,
  `payment_status` varchar(20) NOT NULL DEFAULT 'Success',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `claimed_order_details`;
CREATE TABLE `claimed_order_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `claimed_order_id` int(11) NOT NULL,
  `food_order_details_id` int(11) NOT NULL,
  `price` double NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Stored Procedure

DELIMITER $$
DROP PROCEDURE IF EXISTS ListCancelledOrdersByUserId;
CREATE PROCEDURE ListCancelledOrdersByUserId (IN IS_VEG INT, IN U_LATITUDE DOUBLE, IN U_LONGITUDE DOUBLE)
BEGIN
	SELECT fo.id as food_order_id, fod.id as food_order_details_id,r.name as restaurant_name, rmi.menu_items,r.name,rmi.discounted_price,(6371 * acos(cos(radians(U_LATITUDE)) * cos(radians(r.latitude)) * cos(radians(r.longitude) - radians(U_LONGITUDE)) + sin(radians(U_LATITUDE)) * sin(radians(r.latitude)))) AS distance 
    FROM food_order fo 
    join food_order_details fod on fod.food_order_id = fo.id 
    join restaurant r on fo.restaurant_id = r.id 
    join restaurant_menu_items rmi on fod.restaurant_menu_items_id = rmi.id 
where 
	(IS_VEG = 0 OR rmi.is_vegetarian = 1)
    and rmi.is_sensitive = 0
    and fod.status = "Cancelled"
    having distance <=3;
END$$
DELIMITER ;
