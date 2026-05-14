mysqldump: [Warning] Using a password on the command line interface can be insecure.
-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: inventorysystem
-- ------------------------------------------------------
-- Server version	8.0.45

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_audit_user` (`user_id`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,1,'Logged in','2026-05-06 21:51:43'),(2,1,'Logged in','2026-05-06 22:14:52'),(3,1,'Logged in','2026-05-06 22:32:34'),(4,1,'Logged in','2026-05-07 06:44:21'),(5,1,'Logged in','2026-05-07 06:44:26'),(6,1,'Logged in','2026-05-07 07:10:39'),(7,1,'Logged in','2026-05-07 07:17:12'),(8,1,'Logged in','2026-05-07 07:54:59'),(9,1,'Logged in','2026-05-07 08:01:05'),(10,1,'Logged in','2026-05-07 09:28:06'),(11,1,'Admin logged in','2026-05-07 09:37:28'),(12,2,'Cashier login','2026-05-07 09:37:28'),(13,2,'Processed transaction','2026-05-07 09:37:28'),(14,3,'Cashier logout','2026-05-07 09:37:28'),(15,1,'Admin logged in','2026-05-07 09:46:24'),(16,2,'Cashier login','2026-05-07 09:46:24'),(17,2,'Processed transaction','2026-05-07 09:46:24'),(18,3,'Cashier logout','2026-05-07 09:46:24'),(19,1,'Logged in','2026-05-07 09:56:58'),(20,1,'Logged in','2026-05-07 11:27:57'),(21,2,'Logged in','2026-05-07 11:28:56'),(22,1,'Logged in','2026-05-07 11:31:34'),(23,2,'Logged in','2026-05-07 11:34:30'),(24,1,'Logged in','2026-05-07 22:02:38'),(25,3,'Logged in','2026-05-07 23:36:54'),(26,3,'Processed transaction #4 for ₱1192.8','2026-05-07 23:38:38'),(27,3,'Voided transaction #4','2026-05-07 23:39:06'),(28,3,'Processed transaction #5 for ₱114.24','2026-05-07 23:39:42'),(29,1,'Logged in','2026-05-07 23:45:50'),(30,2,'Logged in','2026-05-08 08:50:10'),(31,2,'Processed transaction #6 for ₱336','2026-05-08 09:03:14'),(32,1,'Logged in','2026-05-08 09:04:46'),(33,2,'Logged in','2026-05-08 13:53:23'),(34,2,'Ended shift with ₱450.24 in sales and 2 transactions','2026-05-08 14:34:03'),(35,3,'Logged in','2026-05-08 14:34:39'),(36,3,'Ended shift with ₱0 in sales and 0 transactions','2026-05-08 14:36:06'),(37,3,'Logged in','2026-05-08 14:37:03'),(38,3,'Processed transaction #7 for ₱840','2026-05-08 14:37:32'),(39,3,'Processed transaction #8 for ₱112','2026-05-08 14:37:52'),(40,3,'Voided transaction #8','2026-05-08 14:38:27'),(41,3,'Processed transaction #9 for ₱100.8','2026-05-08 14:49:02'),(42,3,'Voided transaction #9','2026-05-08 14:49:08'),(43,3,'Ended shift with ₱840 in sales and 1 transactions','2026-05-08 14:49:29'),(44,1,'Logged in','2026-05-10 21:29:02'),(45,1,'Logged in','2026-05-12 09:20:43'),(46,6,'Logged in','2026-05-12 09:21:34'),(47,6,'Processed transaction #10 for ₱53.76','2026-05-12 09:25:35'),(48,6,'Processed transaction #11 for ₱11.2','2026-05-12 09:26:19'),(49,6,'Voided transaction #11','2026-05-12 09:26:26'),(50,6,'Ended shift with ₱53.76 in sales and 1 transactions','2026-05-12 09:26:38'),(51,1,'Logged in','2026-05-12 09:26:52'),(52,6,'Logged in','2026-05-12 09:27:05'),(53,1,'Logged in','2026-05-12 09:27:13');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `CategoryID` int NOT NULL AUTO_INCREMENT,
  `CategoryName` varchar(100) NOT NULL,
  PRIMARY KEY (`CategoryID`),
  UNIQUE KEY `CategoryName` (`CategoryName`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Beverages'),(3,'Canned Goods'),(5,'Household'),(4,'Personal Care'),(2,'Snacks');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `CustomerID` int NOT NULL AUTO_INCREMENT,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  PRIMARY KEY (`CustomerID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (1,'Pedro','Penduko'),(2,'Ana','Garcia'),(3,'Luis','Santos');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `CategoryID` int DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `SKU` varchar(80) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `stock` int NOT NULL DEFAULT '0',
  `reorder_level` int NOT NULL DEFAULT '10',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `SKU` (`SKU`),
  KEY `fk_products_category` (`CategoryID`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`CategoryID`) REFERENCES `categories` (`CategoryID`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (2,NULL,'Tanduay','Food',NULL,120.00,15,10,'2026-05-06 22:12:10'),(3,NULL,'Mi Goreng','Food',NULL,12.00,70,10,'2026-05-07 06:46:39'),(4,NULL,'Milo','Food',NULL,10.00,8,10,'2026-05-07 06:46:56'),(5,NULL,'Safeguard','Soap',NULL,13.00,9,10,'2026-05-07 06:47:41'),(6,NULL,'Nescafe: Twin Pack','Food',NULL,12.00,2,10,'2026-05-07 06:48:44'),(7,NULL,'Palmolive','Shampoo',NULL,10.00,5,10,'2026-05-07 07:24:00'),(8,1,'Coca Cola 1.5L','Beverages','BEV001',75.00,44,10,'2026-05-07 09:36:31'),(9,1,'Pepsi 1.5L','Beverages','BEV002',70.00,40,10,'2026-05-07 09:36:31'),(10,2,'Piattos Cheese','Snacks','SNK001',25.00,100,20,'2026-05-07 09:36:31'),(11,2,'Nova Chips','Snacks','SNK002',20.00,80,20,'2026-05-07 09:36:31'),(12,3,'Sardines 555','Canned Goods','CAN001',22.00,60,15,'2026-05-07 09:36:31'),(13,3,'Corned Beef','Canned Goods','CAN002',35.00,50,15,'2026-05-07 09:36:31'),(14,4,'Shampoo Sunsilk','Personal Care','PC001',120.00,30,10,'2026-05-07 09:36:31'),(15,5,'Dishwashing Liquid','Household','HH001',90.00,22,5,'2026-05-07 09:36:31'),(24,NULL,'Pride','Soap',NULL,12.00,0,10,'2026-05-07 11:32:45');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `RoleID` int NOT NULL AUTO_INCREMENT,
  `RoleName` varchar(50) NOT NULL,
  `Permissions` text,
  PRIMARY KEY (`RoleID`),
  UNIQUE KEY `RoleName` (`RoleName`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'manager','all'),(2,'cashier','pos,view_products');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_logs`
--

DROP TABLE IF EXISTS `shift_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cashier_id` int NOT NULL,
  `shift_start` datetime NOT NULL,
  `shift_end` datetime DEFAULT NULL,
  `duration_seconds` int DEFAULT NULL,
  `total_sales` decimal(10,2) DEFAULT '0.00',
  `transaction_count` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_shift_cashier` (`cashier_id`),
  CONSTRAINT `fk_shift_cashier` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_logs`
--

LOCK TABLES `shift_logs` WRITE;
/*!40000 ALTER TABLE `shift_logs` DISABLE KEYS */;
INSERT INTO `shift_logs` VALUES (1,2,'2026-05-07 01:37:06','2026-05-07 09:37:06',28800,245.00,2),(2,3,'2026-05-07 03:37:06','2026-05-07 09:37:06',21600,200.00,1),(3,2,'2026-05-07 01:37:24','2026-05-07 09:37:24',28800,245.00,2),(4,3,'2026-05-07 03:37:24','2026-05-07 09:37:24',21600,200.00,1),(5,2,'2026-05-07 11:28:58','2026-05-08 06:34:03',68705,450.24,2),(6,3,'2026-05-08 14:34:03','2026-05-08 06:36:06',-28677,0.00,0),(7,3,'2026-05-08 14:36:06','2026-05-08 06:49:29',-27997,840.00,1),(8,6,'2026-05-08 14:49:29','2026-05-12 01:26:38',297429,53.76,1);
/*!40000 ALTER TABLE `shift_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_transactions`
--

DROP TABLE IF EXISTS `stock_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_transactions` (
  `TransactionID` int NOT NULL AUTO_INCREMENT,
  `ProductID` int NOT NULL,
  `SupplierID` int DEFAULT NULL,
  `StaffID` int NOT NULL,
  `QuantityAdded` int NOT NULL,
  `TransactionDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `CostPrice` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`TransactionID`),
  KEY `fk_st_product` (`ProductID`),
  KEY `fk_st_supplier` (`SupplierID`),
  KEY `fk_st_staff` (`StaffID`),
  CONSTRAINT `fk_st_product` FOREIGN KEY (`ProductID`) REFERENCES `products` (`id`),
  CONSTRAINT `fk_st_staff` FOREIGN KEY (`StaffID`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_st_supplier` FOREIGN KEY (`SupplierID`) REFERENCES `suppliers` (`SupplierID`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_transactions`
--

LOCK TABLES `stock_transactions` WRITE;
/*!40000 ALTER TABLE `stock_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers` (
  `SupplierID` int NOT NULL AUTO_INCREMENT,
  `SupplierName` varchar(150) NOT NULL,
  `ContactPerson` varchar(100) DEFAULT NULL,
  `Phone` varchar(30) DEFAULT NULL,
  `Address` text,
  PRIMARY KEY (`SupplierID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,'ABC Trading','Carlos Reyes','09171234567','Manila'),(2,'Fresh Goods Inc.','Ana Lopez','09987654321','Quezon City'),(3,'Best Supplies Co.','Mark Tan','09223334444','Makati');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transaction_items`
--

DROP TABLE IF EXISTS `transaction_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transaction_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `transaction_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `fk_ti_transaction` (`transaction_id`),
  KEY `fk_ti_product` (`product_id`),
  CONSTRAINT `fk_ti_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `fk_ti_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transaction_items`
--

LOCK TABLES `transaction_items` WRITE;
/*!40000 ALTER TABLE `transaction_items` DISABLE KEYS */;
INSERT INTO `transaction_items` VALUES (25,1,8,2,75.00),(26,2,10,2,25.00),(27,2,11,1,20.00),(28,3,13,2,35.00),(29,3,8,1,75.00),(32,5,3,1,12.00),(33,5,4,9,10.00),(34,6,3,25,12.00),(35,7,8,6,75.00),(36,7,15,3,90.00),(37,7,4,3,10.00),(40,10,3,4,12.00);
/*!40000 ALTER TABLE `transaction_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cashier_id` int NOT NULL,
  `CustomerID` int DEFAULT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payment_method` varchar(30) DEFAULT 'cash',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_txn_cashier` (`cashier_id`),
  KEY `fk_txn_customer` (`CustomerID`),
  CONSTRAINT `fk_txn_cashier` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_txn_customer` FOREIGN KEY (`CustomerID`) REFERENCES `customers` (`CustomerID`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions`
--

LOCK TABLES `transactions` WRITE;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
INSERT INTO `transactions` VALUES (1,2,1,150.00,'cash','2026-05-07 09:36:55'),(2,2,2,95.00,'gcash','2026-05-07 09:36:55'),(3,3,3,200.00,'cash','2026-05-07 09:36:55'),(5,3,NULL,114.24,'card','2026-05-07 23:39:42'),(6,2,NULL,336.00,'card','2026-05-08 09:03:14'),(7,3,NULL,840.00,'cash','2026-05-08 14:37:32'),(10,6,NULL,53.76,'cash','2026-05-12 09:25:35');
/*!40000 ALTER TABLE `transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `RoleID` int NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'cashier',
  `email` varchar(100) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `fk_users_role` (`RoleID`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`RoleID`) REFERENCES `roles` (`RoleID`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,1,'Admin User','admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','manager',NULL,'2026-05-06','2026-05-06 21:48:51'),(2,2,'Juan Dela Cruz','juan','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','cashier','juan@email.com','2026-05-07','2026-05-07 09:36:15'),(3,2,'Maria Santos','maria','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','cashier','maria@email.com','2026-05-07','2026-05-07 09:36:20'),(6,2,'Sanny','sanny','$2y$10$a25dj619tkVPktWrv5QzWOnTutqAIUrS.1ER2WQgwJhDfsvjem0Se','cashier',NULL,NULL,'2026-05-12 09:21:16');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-12 10:12:41
