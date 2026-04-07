-- Database Backup
-- Database: prashop_db
-- Date: 2026-04-07 02:40:28
SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `status` enum('active','empty') NOT NULL DEFAULT 'active',
  `price` decimal(15,2) NOT NULL,
  `discount` int(11) DEFAULT 0,
  `stock` int(11) NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT 'nb530.png',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `products` VALUES ('10', 'New Balance 530', 'Casual', 'active', '100000.00', '0', '20', 'uploads/product_699ed1000fa9b_1772015872.png', '2026-02-25 10:44:46');
INSERT INTO `products` VALUES ('15', 'New Balance 574', 'Casual', 'active', '150000.00', '0', '16', 'uploads/product_699ed11e968f0_1772015902.png', '2026-02-25 17:38:22');
INSERT INTO `products` VALUES ('16', 'New Balance 2002R', 'Casual', 'active', '100000.00', '0', '16', 'uploads/product_699ed13bae8da_1772015931.png', '2026-02-25 17:38:51');
INSERT INTO `products` VALUES ('17', 'Kaos Boxy', 'Fashion', 'active', '253000.00', '15', '8', 'uploads/product_699f888a52618_1772062858.png', '2026-02-26 06:37:16');
INSERT INTO `products` VALUES ('18', 'Jersey vintage', 'Sports', 'active', '150000.00', '0', '8', 'uploads/product_699f889fc956c_1772062879.png', '2026-02-26 06:38:50');
INSERT INTO `products` VALUES ('19', 'Baggy Jeans', 'Fashion', 'active', '145000.00', '0', '16', 'uploads/product_699fc02c102a8_1772077100.png', '2026-02-26 10:38:20');
INSERT INTO `products` VALUES ('20', 'Eiger Equator 45L', 'Sports', 'active', '789000.00', '30', '20', 'uploads/product_699fc0ee4c184_1772077294.png', '2026-02-26 10:41:34');
INSERT INTO `products` VALUES ('21', 'Antarestar Series Pangrango', 'Sports', 'active', '560000.00', '15', '43', 'uploads/product_699fc1d21b68c_1772077522.png', '2026-02-26 10:45:22');
INSERT INTO `products` VALUES ('22', 'Tenda Enigma', 'Sports', 'active', '650000.00', '25', '12', 'uploads/product_699fc27a98d38_1772077690.png', '2026-02-26 10:48:10');
INSERT INTO `products` VALUES ('23', 'Jaket Antarestar Manusela', 'Sports', 'active', '180000.00', '0', '22', 'uploads/product_699fc68f9571a_1772078735.png', '2026-02-26 11:05:35');
INSERT INTO `products` VALUES ('24', 'Raket  Felet Nano Fatex', 'Sports', 'active', '150000.00', '0', '12', 'uploads/product_699fd320e8c05_1772081952.png', '2026-02-26 11:59:12');
INSERT INTO `products` VALUES ('25', 'Topi MLB Indians Claveland', 'Fashion', 'active', '24000.00', '0', '12', 'uploads/product_699fd4ef11781_1772082415.png', '2026-02-26 12:06:55');
INSERT INTO `products` VALUES ('26', 'Raw Denim -Driftcolny', 'Fashion', 'active', '340000.00', '24', '45', 'uploads/product_699fd5595cdda_1772082521.png', '2026-02-26 12:08:41');
INSERT INTO `products` VALUES ('27', 'Knitwear Vobia Clasic France', 'Fashion', 'active', '298000.00', '0', '45', 'uploads/product_699fd67d241ea_1772082813.png', '2026-02-26 12:13:33');
INSERT INTO `products` VALUES ('28', 'Salomon X Force', 'Sports', 'active', '1200000.00', '0', '34', 'uploads/product_699fd780b1879_1772083072.png', '2026-02-26 12:17:52');
INSERT INTO `products` VALUES ('29', 'Ortuseight HYPERBLAST Evo', 'Running', 'active', '600000.00', '34', '24', 'uploads/product_699fd84c6918e_1772083276.png', '2026-02-26 12:21:16');
INSERT INTO `products` VALUES ('30', 'New Balance Fuelcell', 'Running', 'active', '400000.00', '10', '20', 'uploads/product_699fd901263c2_1772083457.png', '2026-02-26 12:24:17');
INSERT INTO `products` VALUES ('31', 'The Nort Face Summit Superior', 'Running', 'active', '780000.00', '40', '76', 'uploads/product_699fd9b467e41_1772083636.png', '2026-02-26 12:27:16');
INSERT INTO `products` VALUES ('32', 'Sleeping Bag Antarestar Series Merbabu', 'Sports', 'active', '230000.00', '0', '65', 'uploads/product_699fda80450fa_1772083840.png', '2026-02-26 12:30:40');
INSERT INTO `products` VALUES ('33', 'Eiger Diaro 19L', 'Sports', 'active', '230000.00', '0', '32', 'uploads/product_699fdaae8ad32_1772083886.png', '2026-02-26 12:31:26');

DROP TABLE IF EXISTS `transaction_details`;
CREATE TABLE `transaction_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(100) DEFAULT NULL,
  `price` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transaction_id` (`transaction_id`),
  CONSTRAINT `transaction_details_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `transaction_details` VALUES ('7', '8', '15', 'New Balance 574', '100000', '1');
INSERT INTO `transaction_details` VALUES ('8', '9', '15', 'New Balance 574', '100000', '1');
INSERT INTO `transaction_details` VALUES ('9', '10', '16', 'New Balance 2002R', '55000', '3');
INSERT INTO `transaction_details` VALUES ('10', '11', '10', 'New Balance 530', '100000', '1');
INSERT INTO `transaction_details` VALUES ('11', '12', '10', 'New Balance 530', '100000', '1');
INSERT INTO `transaction_details` VALUES ('12', '13', '17', 'Kaos Boxy', '215050', '1');
INSERT INTO `transaction_details` VALUES ('13', '14', '10', 'New Balance 530', '100000', '1');
INSERT INTO `transaction_details` VALUES ('14', '15', '10', 'New Balance 530', '100000', '1');
INSERT INTO `transaction_details` VALUES ('15', '16', '10', 'New Balance 530', '100000', '1');
INSERT INTO `transaction_details` VALUES ('16', '17', '18', 'Jersey vintage', '150000', '1');
INSERT INTO `transaction_details` VALUES ('17', '18', '18', 'Jersey vintage', '150000', '1');
INSERT INTO `transaction_details` VALUES ('18', '19', '17', 'Kaos Boxy', '215050', '1');
INSERT INTO `transaction_details` VALUES ('19', '20', '15', 'New Balance 574', '80000', '1');
INSERT INTO `transaction_details` VALUES ('20', '21', '23', 'Jaket Antarestar Manusela', '180000', '1');
INSERT INTO `transaction_details` VALUES ('21', '22', '19', 'Baggy Jeans', '145000', '1');

DROP TABLE IF EXISTS `transactions`;
CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `payment_method` enum('cod','transfer') DEFAULT 'cod',
  `address` text DEFAULT NULL,
  `proof_path` varchar(255) DEFAULT NULL,
  `total_amount` int(11) DEFAULT NULL,
  `status` enum('pending','paid','shipped','completed','cancelled') DEFAULT 'pending',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `transactions` VALUES ('22', '3', 'junet', '+6208776278881', 'mail@gmail.com', 'cod', '', NULL, '145000', 'shipped', '2026-04-07 02:31:57');

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','petugas','user') DEFAULT 'user',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` VALUES ('1', 'admin', 'admin123', 'admin@prashop.com', NULL, NULL, 'admin');
INSERT INTO `users` VALUES ('3', 'dahlan', '123', 'cnmantap91@gmail.com', 'cihuymantap', '08776278881', 'user');
INSERT INTO `users` VALUES ('10', 'ucup', '1234', 'yyagaming@gmail.com', NULL, NULL, 'petugas');


SET FOREIGN_KEY_CHECKS=1;
