-- MySQL dump 10.13  Distrib 8.0.35, for Linux (x86_64)
--
-- Host: localhost    Database: biglist_test
-- ------------------------------------------------------
-- Server version	8.0.35

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
-- Table structure for table `wn_activity_log`
--

DROP TABLE IF EXISTS `wn_activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_activity_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `log_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `causer_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `causer_id` bigint unsigned DEFAULT NULL,
  `properties` json DEFAULT NULL,
  `batch_uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject` (`subject_type`,`subject_id`),
  KEY `causer` (`causer_type`,`causer_id`),
  KEY `wn_activity_log_log_name_index` (`log_name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_activity_log`
--

LOCK TABLES `wn_activity_log` WRITE;
/*!40000 ALTER TABLE `wn_activity_log` DISABLE KEYS */;
INSERT INTO `wn_activity_log` VALUES (1,'default','created','Wncms\\Models\\User','created',1,NULL,NULL,'{\"attributes\": {\"username\": \"admin\"}}',NULL,'2025-05-04 00:21:31','2025-05-04 00:21:31'),(2,'default','created','Wncms\\Models\\User','created',2,NULL,NULL,'{\"attributes\": {\"username\": \"manager\"}}',NULL,'2025-05-04 00:21:33','2025-05-04 00:21:33');
/*!40000 ALTER TABLE `wn_activity_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_advertisements`
--

DROP TABLE IF EXISTS `wn_advertisements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_advertisements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `website_id` bigint unsigned NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expired_at` datetime DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cta_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cta_text_2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url_2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text_color` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `background_color` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` text COLLATE utf8mb4_unicode_ci,
  `style` text COLLATE utf8mb4_unicode_ci,
  `position` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wn_advertisements_website_id_foreign` (`website_id`),
  CONSTRAINT `wn_advertisements_website_id_foreign` FOREIGN KEY (`website_id`) REFERENCES `wn_websites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_advertisements`
--

LOCK TABLES `wn_advertisements` WRITE;
/*!40000 ALTER TABLE `wn_advertisements` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_advertisements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_banners`
--

DROP TABLE IF EXISTS `wn_banners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_banners` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `website_id` bigint unsigned NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `external_thumbnail` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order` int DEFAULT NULL,
  `contact` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `positions` json DEFAULT NULL,
  `expired_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wn_banners_website_id_foreign` (`website_id`),
  CONSTRAINT `wn_banners_website_id_foreign` FOREIGN KEY (`website_id`) REFERENCES `wn_websites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_banners`
--

LOCK TABLES `wn_banners` WRITE;
/*!40000 ALTER TABLE `wn_banners` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_banners` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_cards`
--

DROP TABLE IF EXISTS `wn_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_cards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` decimal(10,2) DEFAULT NULL,
  `plan_id` bigint unsigned DEFAULT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `redeemed_at` timestamp NULL DEFAULT NULL,
  `expired_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wn_cards_code_unique` (`code`),
  KEY `wn_cards_plan_id_foreign` (`plan_id`),
  KEY `wn_cards_product_id_foreign` (`product_id`),
  KEY `wn_cards_user_id_foreign` (`user_id`),
  CONSTRAINT `wn_cards_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `wn_plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wn_cards_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `wn_products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wn_cards_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `wn_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_cards`
--

LOCK TABLES `wn_cards` WRITE;
/*!40000 ALTER TABLE `wn_cards` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_cards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_channels`
--

DROP TABLE IF EXISTS `wn_channels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_channels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wn_channels_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_channels`
--

LOCK TABLES `wn_channels` WRITE;
/*!40000 ALTER TABLE `wn_channels` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_channels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_clicks`
--

DROP TABLE IF EXISTS `wn_clicks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_clicks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `clickable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `clickable_id` bigint unsigned NOT NULL,
  `channel_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parameters` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wn_clicks_clickable_type_clickable_id_index` (`clickable_type`,`clickable_id`),
  KEY `wn_clicks_channel_id_foreign` (`channel_id`),
  CONSTRAINT `wn_clicks_channel_id_foreign` FOREIGN KEY (`channel_id`) REFERENCES `wn_channels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_clicks`
--

LOCK TABLES `wn_clicks` WRITE;
/*!40000 ALTER TABLE `wn_clicks` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_clicks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_comments`
--

DROP TABLE IF EXISTS `wn_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_comments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `commentable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `commentable_id` bigint unsigned NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'visible',
  `user_id` bigint unsigned DEFAULT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `content` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wn_comments_commentable_type_commentable_id_index` (`commentable_type`,`commentable_id`),
  KEY `wn_comments_user_id_foreign` (`user_id`),
  KEY `wn_comments_parent_id_foreign` (`parent_id`),
  CONSTRAINT `wn_comments_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `wn_comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wn_comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `wn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_comments`
--

LOCK TABLES `wn_comments` WRITE;
/*!40000 ALTER TABLE `wn_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_contact_form_option_relationship`
--

DROP TABLE IF EXISTS `wn_contact_form_option_relationship`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_contact_form_option_relationship` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint unsigned NOT NULL,
  `option_id` bigint unsigned NOT NULL,
  `order` int NOT NULL,
  `is_required` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wn_contact_form_option_relationship_form_id_foreign` (`form_id`),
  KEY `wn_contact_form_option_relationship_option_id_foreign` (`option_id`),
  CONSTRAINT `wn_contact_form_option_relationship_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `wn_contact_forms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wn_contact_form_option_relationship_option_id_foreign` FOREIGN KEY (`option_id`) REFERENCES `wn_contact_form_options` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_contact_form_option_relationship`
--

LOCK TABLES `wn_contact_form_option_relationship` WRITE;
/*!40000 ALTER TABLE `wn_contact_form_option_relationship` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_contact_form_option_relationship` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_contact_form_options`
--

DROP TABLE IF EXISTS `wn_contact_form_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_contact_form_options` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `placeholder` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `default_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `options` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_contact_form_options`
--

LOCK TABLES `wn_contact_form_options` WRITE;
/*!40000 ALTER TABLE `wn_contact_form_options` DISABLE KEYS */;
INSERT INTO `wn_contact_form_options` VALUES (1,'name','text','姓名','輸入你的名字',NULL,NULL,'2025-05-04 00:21:36','2025-05-04 00:21:36'),(2,'email','text','Email','輸入你的Email',NULL,NULL,'2025-05-04 00:21:36','2025-05-04 00:21:36'),(3,'message','textarea','訊息','輸入你要查詢的內容',NULL,NULL,'2025-05-04 00:21:36','2025-05-04 00:21:36'),(4,'type','select','是次查詢的目的',NULL,NULL,'售前查詢\r\n商務合作\r\n技術支援\r\n帳務查詢','2025-05-04 00:21:36','2025-05-04 00:21:36');
/*!40000 ALTER TABLE `wn_contact_form_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_contact_form_submissions`
--

DROP TABLE IF EXISTS `wn_contact_form_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_contact_form_submissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `website_id` bigint unsigned DEFAULT NULL,
  `contact_form_id` bigint unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unread',
  `content` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wn_contact_form_submissions_website_id_foreign` (`website_id`),
  KEY `wn_contact_form_submissions_contact_form_id_foreign` (`contact_form_id`),
  CONSTRAINT `wn_contact_form_submissions_contact_form_id_foreign` FOREIGN KEY (`contact_form_id`) REFERENCES `wn_contact_forms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `wn_contact_form_submissions_website_id_foreign` FOREIGN KEY (`website_id`) REFERENCES `wn_websites` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_contact_form_submissions`
--

LOCK TABLES `wn_contact_form_submissions` WRITE;
/*!40000 ALTER TABLE `wn_contact_form_submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_contact_form_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_contact_forms`
--

DROP TABLE IF EXISTS `wn_contact_forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_contact_forms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `success_action` text COLLATE utf8mb4_unicode_ci,
  `fail_action` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_contact_forms`
--

LOCK TABLES `wn_contact_forms` WRITE;
/*!40000 ALTER TABLE `wn_contact_forms` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_contact_forms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_coupons`
--

DROP TABLE IF EXISTS `wn_coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_coupons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fixed',
  `discount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `minimum_amount` decimal(10,2) DEFAULT NULL,
  `maximum_amount` decimal(10,2) DEFAULT NULL,
  `limit` int NOT NULL DEFAULT '1',
  `used` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wn_coupons_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_coupons`
--

LOCK TABLES `wn_coupons` WRITE;
/*!40000 ALTER TABLE `wn_coupons` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_coupons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_credit_transactions`
--

DROP TABLE IF EXISTS `wn_credit_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_credit_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `credit_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wn_credit_transactions_user_id_foreign` (`user_id`),
  CONSTRAINT `wn_credit_transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `wn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_credit_transactions`
--

LOCK TABLES `wn_credit_transactions` WRITE;
/*!40000 ALTER TABLE `wn_credit_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_credit_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_credits`
--

DROP TABLE IF EXISTS `wn_credits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_credits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wn_credits_user_id_type_unique` (`user_id`,`type`),
  CONSTRAINT `wn_credits_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `wn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_credits`
--

LOCK TABLES `wn_credits` WRITE;
/*!40000 ALTER TABLE `wn_credits` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_credits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_discounts`
--

DROP TABLE IF EXISTS `wn_discounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_discounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('percentage','fixed') COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_discounts`
--

LOCK TABLES `wn_discounts` WRITE;
/*!40000 ALTER TABLE `wn_discounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_discounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_domain_aliases`
--

DROP TABLE IF EXISTS `wn_domain_aliases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_domain_aliases` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `website_id` bigint unsigned DEFAULT NULL,
  `domain` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wn_domain_aliases_website_id_foreign` (`website_id`),
  CONSTRAINT `wn_domain_aliases_website_id_foreign` FOREIGN KEY (`website_id`) REFERENCES `wn_websites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_domain_aliases`
--

LOCK TABLES `wn_domain_aliases` WRITE;
/*!40000 ALTER TABLE `wn_domain_aliases` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_domain_aliases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_extra_attributes`
--

DROP TABLE IF EXISTS `wn_extra_attributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_extra_attributes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `model_attributes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_extra_attributes`
--

LOCK TABLES `wn_extra_attributes` WRITE;
/*!40000 ALTER TABLE `wn_extra_attributes` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_extra_attributes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_failed_jobs`
--

DROP TABLE IF EXISTS `wn_failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wn_failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_failed_jobs`
--

LOCK TABLES `wn_failed_jobs` WRITE;
/*!40000 ALTER TABLE `wn_failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_faqs`
--

DROP TABLE IF EXISTS `wn_faqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_faqs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `website_id` bigint unsigned NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `question` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `answer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order` int DEFAULT NULL,
  `is_pinned` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wn_faqs_website_id_foreign` (`website_id`),
  CONSTRAINT `wn_faqs_website_id_foreign` FOREIGN KEY (`website_id`) REFERENCES `wn_websites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_faqs`
--

LOCK TABLES `wn_faqs` WRITE;
/*!40000 ALTER TABLE `wn_faqs` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_faqs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_job_batches`
--

DROP TABLE IF EXISTS `wn_job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_job_batches`
--

LOCK TABLES `wn_job_batches` WRITE;
/*!40000 ALTER TABLE `wn_job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_jobs`
--

DROP TABLE IF EXISTS `wn_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `wn_jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_jobs`
--

LOCK TABLES `wn_jobs` WRITE;
/*!40000 ALTER TABLE `wn_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_links`
--

DROP TABLE IF EXISTS `wn_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_links` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `external_thumbnail` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clicks` int DEFAULT '0',
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order` int DEFAULT NULL,
  `color` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_pinned` tinyint(1) DEFAULT '0',
  `expired_at` datetime DEFAULT NULL,
  `tracking_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slogan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `background` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_recommended` tinyint(1) DEFAULT '0',
  `hit_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wn_links_tracking_code_index` (`tracking_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_links`
--

LOCK TABLES `wn_links` WRITE;
/*!40000 ALTER TABLE `wn_links` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_media`
--

DROP TABLE IF EXISTS `wn_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_media` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `collection_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `disk` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `conversions_disk` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` bigint unsigned NOT NULL,
  `manipulations` json NOT NULL,
  `custom_properties` json NOT NULL,
  `generated_conversions` json NOT NULL,
  `responsive_images` json NOT NULL,
  `order_column` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wn_media_uuid_unique` (`uuid`),
  KEY `wn_media_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `wn_media_order_column_index` (`order_column`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_media`
--

LOCK TABLES `wn_media` WRITE;
/*!40000 ALTER TABLE `wn_media` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_media` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_menu_items`
--

DROP TABLE IF EXISTS `wn_menu_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_menu_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `menu_id` bigint unsigned NOT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_new_window` tinyint(1) NOT NULL DEFAULT '0',
  `is_mega_menu` tinyint(1) NOT NULL DEFAULT '0',
  `order` int NOT NULL DEFAULT '0',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wn_menu_items_menu_id_foreign` (`menu_id`),
  KEY `wn_menu_items_parent_id_foreign` (`parent_id`),
  CONSTRAINT `wn_menu_items_menu_id_foreign` FOREIGN KEY (`menu_id`) REFERENCES `wn_menus` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wn_menu_items_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `wn_menu_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_menu_items`
--

LOCK TABLES `wn_menu_items` WRITE;
/*!40000 ALTER TABLE `wn_menu_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_menu_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_menus`
--

DROP TABLE IF EXISTS `wn_menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_menus` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `website_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wn_menus_website_id_foreign` (`website_id`),
  CONSTRAINT `wn_menus_website_id_foreign` FOREIGN KEY (`website_id`) REFERENCES `wn_websites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_menus`
--

LOCK TABLES `wn_menus` WRITE;
/*!40000 ALTER TABLE `wn_menus` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_menus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_migrations`
--

DROP TABLE IF EXISTS `wn_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_migrations`
--

LOCK TABLES `wn_migrations` WRITE;
/*!40000 ALTER TABLE `wn_migrations` DISABLE KEYS */;
INSERT INTO `wn_migrations` VALUES (1,'0001_01_01_000010_create_users_table',1),(2,'0001_01_01_000020_create_jobs_table',1),(3,'0001_01_01_000030_create_permission_tables',1),(4,'0001_01_01_000040_create_activity_log_table',1),(5,'0001_01_01_000050_add_event_column_to_activity_log_table',1),(6,'0001_01_01_000060_add_batch_uuid_column_to_activity_log_table',1),(7,'0001_01_01_000070_create_media_table',1),(8,'0001_01_01_000080_create_websites_table',1),(9,'0001_01_01_000081_create_user_website_table',1),(10,'0001_01_01_000090_create_theme_options_table',1),(11,'0001_01_01_000110_create_banners_table',1),(12,'0001_01_01_000120_create_traffics_table',1),(13,'0001_01_01_000121_create_traffic_summaries_table',1),(14,'0001_01_01_000130_create_settings_table',1),(15,'0001_01_01_000140_create_posts_table',1),(16,'0001_01_01_000141_create_post_website_table',1),(17,'0001_01_01_000150_create_menus_table',1),(18,'0001_01_01_000160_create_menu_items_table',1),(19,'0001_01_01_000170_create_comments_table',1),(20,'0001_01_01_000180_create_records_table',1),(21,'0001_01_01_000190_create_search_keywords_table',1),(22,'0001_01_01_000210_create_contact_forms_table',1),(23,'0001_01_01_000220_create_contact_form_submissions_table',1),(24,'0001_01_01_000230_create_contact_form_options_table',1),(25,'0001_01_01_000240_create_contact_form_option_relationship_table',1),(26,'0001_01_01_000250_create_pages_table',1),(27,'0001_01_01_000260_create_page_templates_table',1),(28,'0001_01_01_000290_create_advertisements_table',1),(29,'0001_01_01_000310_create_domain_aliases_table',1),(30,'0001_01_01_000320_create_themes_table',1),(31,'0001_01_01_000330_create_faqs_table',1),(32,'0001_01_01_000340_create_extra_attributes_table',1),(33,'2024_09_09_000000_create_tag_tables',1),(34,'2024_09_09_000001_create_tag_keywords_table',1),(35,'2024_09_09_000002_create_translations_table',1),(36,'2024_09_19_035752_create_plugins_table',1),(37,'2024_11_18_032542_create_payment_gateways_table',1),(38,'2024_11_18_032543_create_plans_table',1),(39,'2024_11_18_032557_create_products_table',1),(40,'2024_11_18_032620_create_discounts_table',1),(41,'2024_11_18_032650_create_credits_table',1),(42,'2024_11_18_032746_create_credit_transactions_table',1),(43,'2024_11_18_032757_create_cards_table',1),(44,'2024_11_18_032760_create_coupons_table',1),(45,'2024_11_18_032848_create_orders_table',1),(46,'2024_11_18_032857_create_order_items_table',1),(47,'2024_11_18_032916_create_transactions_table',1),(48,'2024_11_18_032920_create_prices_table',1),(49,'2024_11_18_032928_create_subscriptions_table',1),(50,'2025_01_04_012316_create_links_table',1),(51,'2025_04_05_000001_create_channels_table',1),(52,'2025_04_05_000002_create_clicks_table',1),(53,'2025_04_05_000003_create_parameters_table',1);
/*!40000 ALTER TABLE `wn_migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_model_has_permissions`
--

DROP TABLE IF EXISTS `wn_model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `wn_model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `wn_permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_model_has_permissions`
--

LOCK TABLES `wn_model_has_permissions` WRITE;
/*!40000 ALTER TABLE `wn_model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_model_has_roles`
--

DROP TABLE IF EXISTS `wn_model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `wn_model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `wn_roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_model_has_roles`
--

LOCK TABLES `wn_model_has_roles` WRITE;
/*!40000 ALTER TABLE `wn_model_has_roles` DISABLE KEYS */;
INSERT INTO `wn_model_has_roles` VALUES (1,'Wncms\\Models\\User',1),(2,'Wncms\\Models\\User',1),(3,'Wncms\\Models\\User',2);
/*!40000 ALTER TABLE `wn_model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_order_items`
--

DROP TABLE IF EXISTS `wn_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_order_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `order_itemable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_itemable_id` bigint unsigned NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wn_order_items_order_id_foreign` (`order_id`),
  KEY `wn_order_items_order_itemable_type_order_itemable_id_index` (`order_itemable_type`,`order_itemable_id`),
  CONSTRAINT `wn_order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `wn_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_order_items`
--

LOCK TABLES `wn_order_items` WRITE;
/*!40000 ALTER TABLE `wn_order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_orders`
--

DROP TABLE IF EXISTS `wn_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending_payment',
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coupon_id` bigint unsigned DEFAULT NULL,
  `original_amount` decimal(10,2) DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nickname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tel` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_gateway_id` bigint unsigned DEFAULT NULL,
  `tracking_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remark` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wn_orders_slug_unique` (`slug`),
  KEY `wn_orders_user_id_foreign` (`user_id`),
  KEY `wn_orders_coupon_id_foreign` (`coupon_id`),
  KEY `wn_orders_payment_gateway_id_foreign` (`payment_gateway_id`),
  CONSTRAINT `wn_orders_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `wn_coupons` (`id`) ON DELETE SET NULL,
  CONSTRAINT `wn_orders_payment_gateway_id_foreign` FOREIGN KEY (`payment_gateway_id`) REFERENCES `wn_payment_gateways` (`id`) ON DELETE SET NULL,
  CONSTRAINT `wn_orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `wn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_orders`
--

LOCK TABLES `wn_orders` WRITE;
/*!40000 ALTER TABLE `wn_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_page_templates`
--

DROP TABLE IF EXISTS `wn_page_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_page_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `page_id` bigint unsigned NOT NULL,
  `theme_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `template_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` json NOT NULL,
  `order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wn_page_templates_page_id_foreign` (`page_id`),
  CONSTRAINT `wn_page_templates_page_id_foreign` FOREIGN KEY (`page_id`) REFERENCES `wn_pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_page_templates`
--

LOCK TABLES `wn_page_templates` WRITE;
/*!40000 ALTER TABLE `wn_page_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_page_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_pages`
--

DROP TABLE IF EXISTS `wn_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_pages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `website_id` bigint unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `visibility` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'public',
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'plain',
  `blade_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci,
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_locked` tinyint(1) NOT NULL DEFAULT '0',
  `options` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wn_pages_slug_unique` (`slug`),
  KEY `wn_pages_user_id_foreign` (`user_id`),
  KEY `wn_pages_website_id_foreign` (`website_id`),
  KEY `wn_pages_title_index` (`title`),
  CONSTRAINT `wn_pages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `wn_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wn_pages_website_id_foreign` FOREIGN KEY (`website_id`) REFERENCES `wn_websites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_pages`
--

LOCK TABLES `wn_pages` WRITE;
/*!40000 ALTER TABLE `wn_pages` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_parameters`
--

DROP TABLE IF EXISTS `wn_parameters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_parameters` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_parameters`
--

LOCK TABLES `wn_parameters` WRITE;
/*!40000 ALTER TABLE `wn_parameters` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_parameters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_password_reset_tokens`
--

DROP TABLE IF EXISTS `wn_password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_password_reset_tokens`
--

LOCK TABLES `wn_password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `wn_password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_payment_gateways`
--

DROP TABLE IF EXISTS `wn_payment_gateways`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_payment_gateways` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client_secret` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endpoint` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attributes` json DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wn_payment_gateways_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_payment_gateways`
--

LOCK TABLES `wn_payment_gateways` WRITE;
/*!40000 ALTER TABLE `wn_payment_gateways` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_payment_gateways` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_permissions`
--

DROP TABLE IF EXISTS `wn_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wn_permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=328 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_permissions`
--

LOCK TABLES `wn_permissions` WRITE;
/*!40000 ALTER TABLE `wn_permissions` DISABLE KEYS */;
INSERT INTO `wn_permissions` VALUES (1,'advertisement_list','web','2025-05-04 00:18:52','2025-05-04 00:18:52'),(2,'advertisement_index','web','2025-05-04 00:18:53','2025-05-04 00:18:53'),(3,'advertisement_show','web','2025-05-04 00:18:53','2025-05-04 00:18:53'),(4,'advertisement_create','web','2025-05-04 00:18:54','2025-05-04 00:18:54'),(5,'advertisement_clone','web','2025-05-04 00:18:54','2025-05-04 00:18:54'),(6,'advertisement_bulk_create','web','2025-05-04 00:18:54','2025-05-04 00:18:54'),(7,'advertisement_edit','web','2025-05-04 00:18:55','2025-05-04 00:18:55'),(8,'advertisement_bulk_edit','web','2025-05-04 00:18:55','2025-05-04 00:18:55'),(9,'advertisement_delete','web','2025-05-04 00:18:56','2025-05-04 00:18:56'),(10,'advertisement_bulk_delete','web','2025-05-04 00:18:56','2025-05-04 00:18:56'),(11,'banner_list','web','2025-05-04 00:18:57','2025-05-04 00:18:57'),(12,'banner_index','web','2025-05-04 00:18:57','2025-05-04 00:18:57'),(13,'banner_show','web','2025-05-04 00:18:58','2025-05-04 00:18:58'),(14,'banner_create','web','2025-05-04 00:18:58','2025-05-04 00:18:58'),(15,'banner_clone','web','2025-05-04 00:18:59','2025-05-04 00:18:59'),(16,'banner_bulk_create','web','2025-05-04 00:18:59','2025-05-04 00:18:59'),(17,'banner_edit','web','2025-05-04 00:18:59','2025-05-04 00:18:59'),(18,'banner_bulk_edit','web','2025-05-04 00:19:00','2025-05-04 00:19:00'),(19,'banner_delete','web','2025-05-04 00:19:00','2025-05-04 00:19:00'),(20,'banner_bulk_delete','web','2025-05-04 00:19:01','2025-05-04 00:19:01'),(21,'contact_form_list','web','2025-05-04 00:19:02','2025-05-04 00:19:02'),(22,'contact_form_index','web','2025-05-04 00:19:03','2025-05-04 00:19:03'),(23,'contact_form_show','web','2025-05-04 00:19:04','2025-05-04 00:19:04'),(24,'contact_form_create','web','2025-05-04 00:19:05','2025-05-04 00:19:05'),(25,'contact_form_clone','web','2025-05-04 00:19:06','2025-05-04 00:19:06'),(26,'contact_form_bulk_create','web','2025-05-04 00:19:07','2025-05-04 00:19:07'),(27,'contact_form_edit','web','2025-05-04 00:19:07','2025-05-04 00:19:07'),(28,'contact_form_bulk_edit','web','2025-05-04 00:19:07','2025-05-04 00:19:07'),(29,'contact_form_delete','web','2025-05-04 00:19:08','2025-05-04 00:19:08'),(30,'contact_form_bulk_delete','web','2025-05-04 00:19:08','2025-05-04 00:19:08'),(31,'contact_form_option_list','web','2025-05-04 00:19:09','2025-05-04 00:19:09'),(32,'contact_form_option_index','web','2025-05-04 00:19:09','2025-05-04 00:19:09'),(33,'contact_form_option_show','web','2025-05-04 00:19:10','2025-05-04 00:19:10'),(34,'contact_form_option_create','web','2025-05-04 00:19:10','2025-05-04 00:19:10'),(35,'contact_form_option_clone','web','2025-05-04 00:19:10','2025-05-04 00:19:10'),(36,'contact_form_option_bulk_create','web','2025-05-04 00:19:11','2025-05-04 00:19:11'),(37,'contact_form_option_edit','web','2025-05-04 00:19:12','2025-05-04 00:19:12'),(38,'contact_form_option_bulk_edit','web','2025-05-04 00:19:12','2025-05-04 00:19:12'),(39,'contact_form_option_delete','web','2025-05-04 00:19:12','2025-05-04 00:19:12'),(40,'contact_form_option_bulk_delete','web','2025-05-04 00:19:13','2025-05-04 00:19:13'),(41,'contact_form_submission_list','web','2025-05-04 00:19:13','2025-05-04 00:19:13'),(42,'contact_form_submission_index','web','2025-05-04 00:19:14','2025-05-04 00:19:14'),(43,'contact_form_submission_show','web','2025-05-04 00:19:14','2025-05-04 00:19:14'),(44,'contact_form_submission_create','web','2025-05-04 00:19:15','2025-05-04 00:19:15'),(45,'contact_form_submission_clone','web','2025-05-04 00:19:15','2025-05-04 00:19:15'),(46,'contact_form_submission_bulk_create','web','2025-05-04 00:19:15','2025-05-04 00:19:15'),(47,'contact_form_submission_edit','web','2025-05-04 00:19:16','2025-05-04 00:19:16'),(48,'contact_form_submission_bulk_edit','web','2025-05-04 00:19:16','2025-05-04 00:19:16'),(49,'contact_form_submission_delete','web','2025-05-04 00:19:17','2025-05-04 00:19:17'),(50,'contact_form_submission_bulk_delete','web','2025-05-04 00:19:17','2025-05-04 00:19:17'),(51,'menu_list','web','2025-05-04 00:19:18','2025-05-04 00:19:18'),(52,'menu_index','web','2025-05-04 00:19:18','2025-05-04 00:19:18'),(53,'menu_show','web','2025-05-04 00:19:18','2025-05-04 00:19:18'),(54,'menu_create','web','2025-05-04 00:19:19','2025-05-04 00:19:19'),(55,'menu_clone','web','2025-05-04 00:19:19','2025-05-04 00:19:19'),(56,'menu_bulk_create','web','2025-05-04 00:19:20','2025-05-04 00:19:20'),(57,'menu_edit','web','2025-05-04 00:19:20','2025-05-04 00:19:20'),(58,'menu_bulk_edit','web','2025-05-04 00:19:21','2025-05-04 00:19:21'),(59,'menu_delete','web','2025-05-04 00:19:21','2025-05-04 00:19:21'),(60,'menu_bulk_delete','web','2025-05-04 00:19:22','2025-05-04 00:19:22'),(61,'page_list','web','2025-05-04 00:19:22','2025-05-04 00:19:22'),(62,'page_index','web','2025-05-04 00:19:23','2025-05-04 00:19:23'),(63,'page_show','web','2025-05-04 00:19:23','2025-05-04 00:19:23'),(64,'page_create','web','2025-05-04 00:19:24','2025-05-04 00:19:24'),(65,'page_clone','web','2025-05-04 00:19:24','2025-05-04 00:19:24'),(66,'page_bulk_create','web','2025-05-04 00:19:25','2025-05-04 00:19:25'),(67,'page_edit','web','2025-05-04 00:19:25','2025-05-04 00:19:25'),(68,'page_bulk_edit','web','2025-05-04 00:19:26','2025-05-04 00:19:26'),(69,'page_delete','web','2025-05-04 00:19:26','2025-05-04 00:19:26'),(70,'page_bulk_delete','web','2025-05-04 00:19:27','2025-05-04 00:19:27'),(71,'permission_list','web','2025-05-04 00:19:27','2025-05-04 00:19:27'),(72,'permission_index','web','2025-05-04 00:19:27','2025-05-04 00:19:27'),(73,'permission_show','web','2025-05-04 00:19:28','2025-05-04 00:19:28'),(74,'permission_create','web','2025-05-04 00:19:28','2025-05-04 00:19:28'),(75,'permission_clone','web','2025-05-04 00:19:29','2025-05-04 00:19:29'),(76,'permission_bulk_create','web','2025-05-04 00:19:29','2025-05-04 00:19:29'),(77,'permission_edit','web','2025-05-04 00:19:30','2025-05-04 00:19:30'),(78,'permission_bulk_edit','web','2025-05-04 00:19:31','2025-05-04 00:19:31'),(79,'permission_delete','web','2025-05-04 00:19:31','2025-05-04 00:19:31'),(80,'permission_bulk_delete','web','2025-05-04 00:19:31','2025-05-04 00:19:31'),(81,'post_list','web','2025-05-04 00:19:32','2025-05-04 00:19:32'),(82,'post_index','web','2025-05-04 00:19:33','2025-05-04 00:19:33'),(83,'post_show','web','2025-05-04 00:19:33','2025-05-04 00:19:33'),(84,'post_create','web','2025-05-04 00:19:34','2025-05-04 00:19:34'),(85,'post_clone','web','2025-05-04 00:19:34','2025-05-04 00:19:34'),(86,'post_bulk_create','web','2025-05-04 00:19:35','2025-05-04 00:19:35'),(87,'post_edit','web','2025-05-04 00:19:35','2025-05-04 00:19:35'),(88,'post_bulk_edit','web','2025-05-04 00:19:36','2025-05-04 00:19:36'),(89,'post_delete','web','2025-05-04 00:19:36','2025-05-04 00:19:36'),(90,'post_bulk_delete','web','2025-05-04 00:19:37','2025-05-04 00:19:37'),(91,'search_keyword_list','web','2025-05-04 00:19:37','2025-05-04 00:19:37'),(92,'search_keyword_index','web','2025-05-04 00:19:38','2025-05-04 00:19:38'),(93,'search_keyword_show','web','2025-05-04 00:19:38','2025-05-04 00:19:38'),(94,'search_keyword_create','web','2025-05-04 00:19:38','2025-05-04 00:19:38'),(95,'search_keyword_clone','web','2025-05-04 00:19:39','2025-05-04 00:19:39'),(96,'search_keyword_bulk_create','web','2025-05-04 00:19:39','2025-05-04 00:19:39'),(97,'search_keyword_edit','web','2025-05-04 00:19:40','2025-05-04 00:19:40'),(98,'search_keyword_bulk_edit','web','2025-05-04 00:19:40','2025-05-04 00:19:40'),(99,'search_keyword_delete','web','2025-05-04 00:19:41','2025-05-04 00:19:41'),(100,'search_keyword_bulk_delete','web','2025-05-04 00:19:41','2025-05-04 00:19:41'),(101,'setting_list','web','2025-05-04 00:19:42','2025-05-04 00:19:42'),(102,'setting_index','web','2025-05-04 00:19:42','2025-05-04 00:19:42'),(103,'setting_show','web','2025-05-04 00:19:43','2025-05-04 00:19:43'),(104,'setting_create','web','2025-05-04 00:19:43','2025-05-04 00:19:43'),(105,'setting_clone','web','2025-05-04 00:19:43','2025-05-04 00:19:43'),(106,'setting_bulk_create','web','2025-05-04 00:19:44','2025-05-04 00:19:44'),(107,'setting_edit','web','2025-05-04 00:19:44','2025-05-04 00:19:44'),(108,'setting_bulk_edit','web','2025-05-04 00:19:45','2025-05-04 00:19:45'),(109,'setting_delete','web','2025-05-04 00:19:45','2025-05-04 00:19:45'),(110,'setting_bulk_delete','web','2025-05-04 00:19:46','2025-05-04 00:19:46'),(111,'record_list','web','2025-05-04 00:19:46','2025-05-04 00:19:46'),(112,'record_index','web','2025-05-04 00:19:46','2025-05-04 00:19:46'),(113,'record_show','web','2025-05-04 00:19:47','2025-05-04 00:19:47'),(114,'record_create','web','2025-05-04 00:19:47','2025-05-04 00:19:47'),(115,'record_clone','web','2025-05-04 00:19:48','2025-05-04 00:19:48'),(116,'record_bulk_create','web','2025-05-04 00:19:48','2025-05-04 00:19:48'),(117,'record_edit','web','2025-05-04 00:19:49','2025-05-04 00:19:49'),(118,'record_bulk_edit','web','2025-05-04 00:19:49','2025-05-04 00:19:49'),(119,'record_delete','web','2025-05-04 00:19:49','2025-05-04 00:19:49'),(120,'record_bulk_delete','web','2025-05-04 00:19:50','2025-05-04 00:19:50'),(121,'role_list','web','2025-05-04 00:19:50','2025-05-04 00:19:50'),(122,'role_index','web','2025-05-04 00:19:51','2025-05-04 00:19:51'),(123,'role_show','web','2025-05-04 00:19:51','2025-05-04 00:19:51'),(124,'role_create','web','2025-05-04 00:19:52','2025-05-04 00:19:52'),(125,'role_clone','web','2025-05-04 00:19:52','2025-05-04 00:19:52'),(126,'role_bulk_create','web','2025-05-04 00:19:53','2025-05-04 00:19:53'),(127,'role_edit','web','2025-05-04 00:19:53','2025-05-04 00:19:53'),(128,'role_bulk_edit','web','2025-05-04 00:19:53','2025-05-04 00:19:53'),(129,'role_delete','web','2025-05-04 00:19:54','2025-05-04 00:19:54'),(130,'role_bulk_delete','web','2025-05-04 00:19:54','2025-05-04 00:19:54'),(131,'tag_list','web','2025-05-04 00:19:55','2025-05-04 00:19:55'),(132,'tag_index','web','2025-05-04 00:19:55','2025-05-04 00:19:55'),(133,'tag_show','web','2025-05-04 00:19:56','2025-05-04 00:19:56'),(134,'tag_create','web','2025-05-04 00:19:56','2025-05-04 00:19:56'),(135,'tag_clone','web','2025-05-04 00:19:57','2025-05-04 00:19:57'),(136,'tag_bulk_create','web','2025-05-04 00:19:57','2025-05-04 00:19:57'),(137,'tag_edit','web','2025-05-04 00:19:57','2025-05-04 00:19:57'),(138,'tag_bulk_edit','web','2025-05-04 00:19:58','2025-05-04 00:19:58'),(139,'tag_delete','web','2025-05-04 00:19:58','2025-05-04 00:19:58'),(140,'tag_bulk_delete','web','2025-05-04 00:19:59','2025-05-04 00:19:59'),(141,'tag_keyword_list','web','2025-05-04 00:19:59','2025-05-04 00:19:59'),(142,'tag_keyword_index','web','2025-05-04 00:20:00','2025-05-04 00:20:00'),(143,'tag_keyword_show','web','2025-05-04 00:20:01','2025-05-04 00:20:01'),(144,'tag_keyword_create','web','2025-05-04 00:20:01','2025-05-04 00:20:01'),(145,'tag_keyword_clone','web','2025-05-04 00:20:02','2025-05-04 00:20:02'),(146,'tag_keyword_bulk_create','web','2025-05-04 00:20:02','2025-05-04 00:20:02'),(147,'tag_keyword_edit','web','2025-05-04 00:20:03','2025-05-04 00:20:03'),(148,'tag_keyword_bulk_edit','web','2025-05-04 00:20:03','2025-05-04 00:20:03'),(149,'tag_keyword_delete','web','2025-05-04 00:20:04','2025-05-04 00:20:04'),(150,'tag_keyword_bulk_delete','web','2025-05-04 00:20:04','2025-05-04 00:20:04'),(151,'user_list','web','2025-05-04 00:20:04','2025-05-04 00:20:04'),(152,'user_index','web','2025-05-04 00:20:05','2025-05-04 00:20:05'),(153,'user_show','web','2025-05-04 00:20:05','2025-05-04 00:20:05'),(154,'user_create','web','2025-05-04 00:20:06','2025-05-04 00:20:06'),(155,'user_clone','web','2025-05-04 00:20:06','2025-05-04 00:20:06'),(156,'user_bulk_create','web','2025-05-04 00:20:06','2025-05-04 00:20:06'),(157,'user_edit','web','2025-05-04 00:20:07','2025-05-04 00:20:07'),(158,'user_bulk_edit','web','2025-05-04 00:20:07','2025-05-04 00:20:07'),(159,'user_delete','web','2025-05-04 00:20:08','2025-05-04 00:20:08'),(160,'user_bulk_delete','web','2025-05-04 00:20:08','2025-05-04 00:20:08'),(161,'website_list','web','2025-05-04 00:20:08','2025-05-04 00:20:08'),(162,'website_index','web','2025-05-04 00:20:09','2025-05-04 00:20:09'),(163,'website_show','web','2025-05-04 00:20:10','2025-05-04 00:20:10'),(164,'website_create','web','2025-05-04 00:20:10','2025-05-04 00:20:10'),(165,'website_clone','web','2025-05-04 00:20:10','2025-05-04 00:20:10'),(166,'website_bulk_create','web','2025-05-04 00:20:11','2025-05-04 00:20:11'),(167,'website_edit','web','2025-05-04 00:20:11','2025-05-04 00:20:11'),(168,'website_bulk_edit','web','2025-05-04 00:20:12','2025-05-04 00:20:12'),(169,'website_delete','web','2025-05-04 00:20:12','2025-05-04 00:20:12'),(170,'website_bulk_delete','web','2025-05-04 00:20:13','2025-05-04 00:20:13'),(171,'faq_list','web','2025-05-04 00:20:13','2025-05-04 00:20:13'),(172,'faq_index','web','2025-05-04 00:20:14','2025-05-04 00:20:14'),(173,'faq_show','web','2025-05-04 00:20:14','2025-05-04 00:20:14'),(174,'faq_create','web','2025-05-04 00:20:14','2025-05-04 00:20:14'),(175,'faq_clone','web','2025-05-04 00:20:15','2025-05-04 00:20:15'),(176,'faq_bulk_create','web','2025-05-04 00:20:15','2025-05-04 00:20:15'),(177,'faq_edit','web','2025-05-04 00:20:16','2025-05-04 00:20:16'),(178,'faq_bulk_edit','web','2025-05-04 00:20:16','2025-05-04 00:20:16'),(179,'faq_delete','web','2025-05-04 00:20:17','2025-05-04 00:20:17'),(180,'faq_bulk_delete','web','2025-05-04 00:20:17','2025-05-04 00:20:17'),(181,'package_list','web','2025-05-04 00:20:18','2025-05-04 00:20:18'),(182,'package_index','web','2025-05-04 00:20:18','2025-05-04 00:20:18'),(183,'package_show','web','2025-05-04 00:20:19','2025-05-04 00:20:19'),(184,'package_create','web','2025-05-04 00:20:19','2025-05-04 00:20:19'),(185,'package_clone','web','2025-05-04 00:20:20','2025-05-04 00:20:20'),(186,'package_bulk_create','web','2025-05-04 00:20:20','2025-05-04 00:20:20'),(187,'package_edit','web','2025-05-04 00:20:20','2025-05-04 00:20:20'),(188,'package_bulk_edit','web','2025-05-04 00:20:21','2025-05-04 00:20:21'),(189,'package_delete','web','2025-05-04 00:20:21','2025-05-04 00:20:21'),(190,'package_bulk_delete','web','2025-05-04 00:20:22','2025-05-04 00:20:22'),(191,'link_list','web','2025-05-04 00:20:22','2025-05-04 00:20:22'),(192,'link_index','web','2025-05-04 00:20:22','2025-05-04 00:20:22'),(193,'link_show','web','2025-05-04 00:20:23','2025-05-04 00:20:23'),(194,'link_create','web','2025-05-04 00:20:23','2025-05-04 00:20:23'),(195,'link_clone','web','2025-05-04 00:20:24','2025-05-04 00:20:24'),(196,'link_bulk_create','web','2025-05-04 00:20:24','2025-05-04 00:20:24'),(197,'link_edit','web','2025-05-04 00:20:25','2025-05-04 00:20:25'),(198,'link_bulk_edit','web','2025-05-04 00:20:25','2025-05-04 00:20:25'),(199,'link_delete','web','2025-05-04 00:20:26','2025-05-04 00:20:26'),(200,'link_bulk_delete','web','2025-05-04 00:20:26','2025-05-04 00:20:26'),(201,'card_list','web','2025-05-04 00:20:27','2025-05-04 00:20:27'),(202,'card_index','web','2025-05-04 00:20:27','2025-05-04 00:20:27'),(203,'card_show','web','2025-05-04 00:20:27','2025-05-04 00:20:27'),(204,'card_create','web','2025-05-04 00:20:28','2025-05-04 00:20:28'),(205,'card_clone','web','2025-05-04 00:20:28','2025-05-04 00:20:28'),(206,'card_bulk_create','web','2025-05-04 00:20:29','2025-05-04 00:20:29'),(207,'card_edit','web','2025-05-04 00:20:29','2025-05-04 00:20:29'),(208,'card_bulk_edit','web','2025-05-04 00:20:30','2025-05-04 00:20:30'),(209,'card_delete','web','2025-05-04 00:20:30','2025-05-04 00:20:30'),(210,'card_bulk_delete','web','2025-05-04 00:20:31','2025-05-04 00:20:31'),(211,'credit_list','web','2025-05-04 00:20:31','2025-05-04 00:20:31'),(212,'credit_index','web','2025-05-04 00:20:32','2025-05-04 00:20:32'),(213,'credit_show','web','2025-05-04 00:20:32','2025-05-04 00:20:32'),(214,'credit_create','web','2025-05-04 00:20:33','2025-05-04 00:20:33'),(215,'credit_clone','web','2025-05-04 00:20:33','2025-05-04 00:20:33'),(216,'credit_bulk_create','web','2025-05-04 00:20:34','2025-05-04 00:20:34'),(217,'credit_edit','web','2025-05-04 00:20:34','2025-05-04 00:20:34'),(218,'credit_bulk_edit','web','2025-05-04 00:20:35','2025-05-04 00:20:35'),(219,'credit_delete','web','2025-05-04 00:20:35','2025-05-04 00:20:35'),(220,'credit_bulk_delete','web','2025-05-04 00:20:36','2025-05-04 00:20:36'),(221,'credit_transaction_list','web','2025-05-04 00:20:36','2025-05-04 00:20:36'),(222,'credit_transaction_index','web','2025-05-04 00:20:37','2025-05-04 00:20:37'),(223,'credit_transaction_show','web','2025-05-04 00:20:37','2025-05-04 00:20:37'),(224,'credit_transaction_create','web','2025-05-04 00:20:38','2025-05-04 00:20:38'),(225,'credit_transaction_clone','web','2025-05-04 00:20:38','2025-05-04 00:20:38'),(226,'credit_transaction_bulk_create','web','2025-05-04 00:20:38','2025-05-04 00:20:38'),(227,'credit_transaction_edit','web','2025-05-04 00:20:39','2025-05-04 00:20:39'),(228,'credit_transaction_bulk_edit','web','2025-05-04 00:20:39','2025-05-04 00:20:39'),(229,'credit_transaction_delete','web','2025-05-04 00:20:40','2025-05-04 00:20:40'),(230,'credit_transaction_bulk_delete','web','2025-05-04 00:20:40','2025-05-04 00:20:40'),(231,'order_list','web','2025-05-04 00:20:41','2025-05-04 00:20:41'),(232,'order_index','web','2025-05-04 00:20:41','2025-05-04 00:20:41'),(233,'order_show','web','2025-05-04 00:20:42','2025-05-04 00:20:42'),(234,'order_create','web','2025-05-04 00:20:42','2025-05-04 00:20:42'),(235,'order_clone','web','2025-05-04 00:20:43','2025-05-04 00:20:43'),(236,'order_bulk_create','web','2025-05-04 00:20:43','2025-05-04 00:20:43'),(237,'order_edit','web','2025-05-04 00:20:44','2025-05-04 00:20:44'),(238,'order_bulk_edit','web','2025-05-04 00:20:44','2025-05-04 00:20:44'),(239,'order_delete','web','2025-05-04 00:20:44','2025-05-04 00:20:44'),(240,'order_bulk_delete','web','2025-05-04 00:20:45','2025-05-04 00:20:45'),(241,'plan_list','web','2025-05-04 00:20:45','2025-05-04 00:20:45'),(242,'plan_index','web','2025-05-04 00:20:46','2025-05-04 00:20:46'),(243,'plan_show','web','2025-05-04 00:20:46','2025-05-04 00:20:46'),(244,'plan_create','web','2025-05-04 00:20:47','2025-05-04 00:20:47'),(245,'plan_clone','web','2025-05-04 00:20:47','2025-05-04 00:20:47'),(246,'plan_bulk_create','web','2025-05-04 00:20:48','2025-05-04 00:20:48'),(247,'plan_edit','web','2025-05-04 00:20:48','2025-05-04 00:20:48'),(248,'plan_bulk_edit','web','2025-05-04 00:20:49','2025-05-04 00:20:49'),(249,'plan_delete','web','2025-05-04 00:20:49','2025-05-04 00:20:49'),(250,'plan_bulk_delete','web','2025-05-04 00:20:50','2025-05-04 00:20:50'),(251,'price_list','web','2025-05-04 00:20:50','2025-05-04 00:20:50'),(252,'price_index','web','2025-05-04 00:20:51','2025-05-04 00:20:51'),(253,'price_show','web','2025-05-04 00:20:51','2025-05-04 00:20:51'),(254,'price_create','web','2025-05-04 00:20:52','2025-05-04 00:20:52'),(255,'price_clone','web','2025-05-04 00:20:52','2025-05-04 00:20:52'),(256,'price_bulk_create','web','2025-05-04 00:20:52','2025-05-04 00:20:52'),(257,'price_edit','web','2025-05-04 00:20:53','2025-05-04 00:20:53'),(258,'price_bulk_edit','web','2025-05-04 00:20:53','2025-05-04 00:20:53'),(259,'price_delete','web','2025-05-04 00:20:54','2025-05-04 00:20:54'),(260,'price_bulk_delete','web','2025-05-04 00:20:54','2025-05-04 00:20:54'),(261,'product_list','web','2025-05-04 00:20:55','2025-05-04 00:20:55'),(262,'product_index','web','2025-05-04 00:20:55','2025-05-04 00:20:55'),(263,'product_show','web','2025-05-04 00:20:56','2025-05-04 00:20:56'),(264,'product_create','web','2025-05-04 00:20:56','2025-05-04 00:20:56'),(265,'product_clone','web','2025-05-04 00:20:57','2025-05-04 00:20:57'),(266,'product_bulk_create','web','2025-05-04 00:20:57','2025-05-04 00:20:57'),(267,'product_edit','web','2025-05-04 00:20:58','2025-05-04 00:20:58'),(268,'product_bulk_edit','web','2025-05-04 00:20:58','2025-05-04 00:20:58'),(269,'product_delete','web','2025-05-04 00:20:58','2025-05-04 00:20:58'),(270,'product_bulk_delete','web','2025-05-04 00:20:59','2025-05-04 00:20:59'),(271,'payment_gateway_list','web','2025-05-04 00:20:59','2025-05-04 00:20:59'),(272,'payment_gateway_index','web','2025-05-04 00:21:00','2025-05-04 00:21:00'),(273,'payment_gateway_show','web','2025-05-04 00:21:00','2025-05-04 00:21:00'),(274,'payment_gateway_create','web','2025-05-04 00:21:01','2025-05-04 00:21:01'),(275,'payment_gateway_clone','web','2025-05-04 00:21:01','2025-05-04 00:21:01'),(276,'payment_gateway_bulk_create','web','2025-05-04 00:21:02','2025-05-04 00:21:02'),(277,'payment_gateway_edit','web','2025-05-04 00:21:03','2025-05-04 00:21:03'),(278,'payment_gateway_bulk_edit','web','2025-05-04 00:21:03','2025-05-04 00:21:03'),(279,'payment_gateway_delete','web','2025-05-04 00:21:03','2025-05-04 00:21:03'),(280,'payment_gateway_bulk_delete','web','2025-05-04 00:21:04','2025-05-04 00:21:04'),(281,'subscription_list','web','2025-05-04 00:21:04','2025-05-04 00:21:04'),(282,'subscription_index','web','2025-05-04 00:21:05','2025-05-04 00:21:05'),(283,'subscription_show','web','2025-05-04 00:21:05','2025-05-04 00:21:05'),(284,'subscription_create','web','2025-05-04 00:21:06','2025-05-04 00:21:06'),(285,'subscription_clone','web','2025-05-04 00:21:06','2025-05-04 00:21:06'),(286,'subscription_bulk_create','web','2025-05-04 00:21:07','2025-05-04 00:21:07'),(287,'subscription_edit','web','2025-05-04 00:21:07','2025-05-04 00:21:07'),(288,'subscription_bulk_edit','web','2025-05-04 00:21:08','2025-05-04 00:21:08'),(289,'subscription_delete','web','2025-05-04 00:21:08','2025-05-04 00:21:08'),(290,'subscription_bulk_delete','web','2025-05-04 00:21:09','2025-05-04 00:21:09'),(291,'transaction_list','web','2025-05-04 00:21:09','2025-05-04 00:21:09'),(292,'transaction_index','web','2025-05-04 00:21:10','2025-05-04 00:21:10'),(293,'transaction_show','web','2025-05-04 00:21:10','2025-05-04 00:21:10'),(294,'transaction_create','web','2025-05-04 00:21:11','2025-05-04 00:21:11'),(295,'transaction_clone','web','2025-05-04 00:21:11','2025-05-04 00:21:11'),(296,'transaction_bulk_create','web','2025-05-04 00:21:12','2025-05-04 00:21:12'),(297,'transaction_edit','web','2025-05-04 00:21:12','2025-05-04 00:21:12'),(298,'transaction_bulk_edit','web','2025-05-04 00:21:13','2025-05-04 00:21:13'),(299,'transaction_delete','web','2025-05-04 00:21:14','2025-05-04 00:21:14'),(300,'transaction_bulk_delete','web','2025-05-04 00:21:14','2025-05-04 00:21:14'),(301,'analytics_index','web','2025-05-04 00:21:15','2025-05-04 00:21:15'),(302,'cache_clear','web','2025-05-04 00:21:15','2025-05-04 00:21:15'),(303,'cache_flush','web','2025-05-04 00:21:16','2025-05-04 00:21:16'),(304,'contact_form_submission_export','web','2025-05-04 00:21:16','2025-05-04 00:21:16'),(305,'post_bulk_clone','web','2025-05-04 00:21:17','2025-05-04 00:21:17'),(306,'post_bulk_set_websites','web','2025-05-04 00:21:18','2025-05-04 00:21:18'),(307,'post_bulk_sync_tags','web','2025-05-04 00:21:18','2025-05-04 00:21:18'),(308,'post_generate_demo_posts','web','2025-05-04 00:21:19','2025-05-04 00:21:19'),(309,'tag_create_type','web','2025-05-04 00:21:20','2025-05-04 00:21:20'),(310,'tag_import_csv','web','2025-05-04 00:21:20','2025-05-04 00:21:20'),(311,'upload_image','web','2025-05-04 00:21:21','2025-05-04 00:21:21'),(312,'upload_video','web','2025-05-04 00:21:21','2025-05-04 00:21:21'),(313,'user_api_show','web','2025-05-04 00:21:21','2025-05-04 00:21:21'),(314,'user_api_update','web','2025-05-04 00:21:22','2025-05-04 00:21:22'),(315,'user_profile_show','web','2025-05-04 00:21:22','2025-05-04 00:21:22'),(316,'user_profile_update','web','2025-05-04 00:21:23','2025-05-04 00:21:23'),(317,'user_record_show','web','2025-05-04 00:21:23','2025-05-04 00:21:23'),(318,'user_security_show','web','2025-05-04 00:21:24','2025-05-04 00:21:24'),(319,'theme_index','web','2025-05-04 00:21:25','2025-05-04 00:21:25'),(320,'theme_upload','web','2025-05-04 00:21:25','2025-05-04 00:21:25'),(321,'theme_delete','web','2025-05-04 00:21:26','2025-05-04 00:21:26'),(322,'plugin_index','web','2025-05-04 00:21:26','2025-05-04 00:21:26'),(323,'plugin_upload','web','2025-05-04 00:21:27','2025-05-04 00:21:27'),(324,'plugin_activate','web','2025-05-04 00:21:27','2025-05-04 00:21:27'),(325,'plugin_deactivate','web','2025-05-04 00:21:28','2025-05-04 00:21:28'),(326,'plugin_delete','web','2025-05-04 00:21:28','2025-05-04 00:21:28'),(327,'credit_recharge','web','2025-05-04 00:21:29','2025-05-04 00:21:29');
/*!40000 ALTER TABLE `wn_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_plans`
--

DROP TABLE IF EXISTS `wn_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `free_trial_duration` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wn_plans_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_plans`
--

LOCK TABLES `wn_plans` WRITE;
/*!40000 ALTER TABLE `wn_plans` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_plugins`
--

DROP TABLE IF EXISTS `wn_plugins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_plugins` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `plugin_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `author` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1.0.0',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'inactive',
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wn_plugins_plugin_id_unique` (`plugin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_plugins`
--

LOCK TABLES `wn_plugins` WRITE;
/*!40000 ALTER TABLE `wn_plugins` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_plugins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_post_website`
--

DROP TABLE IF EXISTS `wn_post_website`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_post_website` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint unsigned NOT NULL,
  `website_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wn_post_website_post_id_foreign` (`post_id`),
  KEY `wn_post_website_website_id_foreign` (`website_id`),
  CONSTRAINT `wn_post_website_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `wn_posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wn_post_website_website_id_foreign` FOREIGN KEY (`website_id`) REFERENCES `wn_websites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_post_website`
--

LOCK TABLES `wn_post_website` WRITE;
/*!40000 ALTER TABLE `wn_post_website` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_post_website` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_posts`
--

DROP TABLE IF EXISTS `wn_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_posts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `website_id` bigint unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `visibility` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'public',
  `external_thumbnail` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `excerpt` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` text COLLATE utf8mb4_unicode_ci,
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order` int DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` decimal(9,3) DEFAULT NULL,
  `is_pinned` tinyint(1) NOT NULL DEFAULT '0',
  `is_recommended` tinyint(1) NOT NULL DEFAULT '0',
  `is_dmca` tinyint(1) NOT NULL DEFAULT '0',
  `published_at` datetime NOT NULL,
  `expired_at` datetime DEFAULT NULL,
  `source` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ref_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wn_posts_slug_unique` (`slug`),
  KEY `wn_posts_user_id_foreign` (`user_id`),
  KEY `wn_posts_website_id_foreign` (`website_id`),
  CONSTRAINT `wn_posts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `wn_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wn_posts_website_id_foreign` FOREIGN KEY (`website_id`) REFERENCES `wn_websites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_posts`
--

LOCK TABLES `wn_posts` WRITE;
/*!40000 ALTER TABLE `wn_posts` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_prices`
--

DROP TABLE IF EXISTS `wn_prices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_prices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `priceable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `priceable_id` bigint unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `duration` int DEFAULT NULL,
  `duration_unit` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attributes` json DEFAULT NULL,
  `is_lifetime` tinyint(1) NOT NULL DEFAULT '0',
  `stock` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wn_prices_priceable_type_priceable_id_index` (`priceable_type`,`priceable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_prices`
--

LOCK TABLES `wn_prices` WRITE;
/*!40000 ALTER TABLE `wn_prices` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_prices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_products`
--

DROP TABLE IF EXISTS `wn_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int DEFAULT NULL,
  `is_variable` tinyint(1) NOT NULL DEFAULT '0',
  `attributes` json DEFAULT NULL,
  `variants` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_products`
--

LOCK TABLES `wn_products` WRITE;
/*!40000 ALTER TABLE `wn_products` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_records`
--

DROP TABLE IF EXISTS `wn_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sub_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `detail` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_records`
--

LOCK TABLES `wn_records` WRITE;
/*!40000 ALTER TABLE `wn_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_role_has_permissions`
--

DROP TABLE IF EXISTS `wn_role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `wn_role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `wn_role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `wn_permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wn_role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `wn_roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_role_has_permissions`
--

LOCK TABLES `wn_role_has_permissions` WRITE;
/*!40000 ALTER TABLE `wn_role_has_permissions` DISABLE KEYS */;
INSERT INTO `wn_role_has_permissions` VALUES (1,1),(2,1),(3,1),(4,1),(5,1),(6,1),(7,1),(8,1),(9,1),(10,1),(11,1),(12,1),(13,1),(14,1),(15,1),(16,1),(17,1),(18,1),(19,1),(20,1),(21,1),(22,1),(23,1),(24,1),(25,1),(26,1),(27,1),(28,1),(29,1),(30,1),(31,1),(32,1),(33,1),(34,1),(35,1),(36,1),(37,1),(38,1),(39,1),(40,1),(41,1),(42,1),(43,1),(44,1),(45,1),(46,1),(47,1),(48,1),(49,1),(50,1),(51,1),(52,1),(53,1),(54,1),(55,1),(56,1),(57,1),(58,1),(59,1),(60,1),(61,1),(62,1),(63,1),(64,1),(65,1),(66,1),(67,1),(68,1),(69,1),(70,1),(71,1),(72,1),(73,1),(74,1),(75,1),(76,1),(77,1),(78,1),(79,1),(80,1),(81,1),(82,1),(83,1),(84,1),(85,1),(86,1),(87,1),(88,1),(89,1),(90,1),(91,1),(92,1),(93,1),(94,1),(95,1),(96,1),(97,1),(98,1),(99,1),(100,1),(101,1),(102,1),(103,1),(104,1),(105,1),(106,1),(107,1),(108,1),(109,1),(110,1),(111,1),(112,1),(113,1),(114,1),(115,1),(116,1),(117,1),(118,1),(119,1),(120,1),(121,1),(122,1),(123,1),(124,1),(125,1),(126,1),(127,1),(128,1),(129,1),(130,1),(131,1),(132,1),(133,1),(134,1),(135,1),(136,1),(137,1),(138,1),(139,1),(140,1),(141,1),(142,1),(143,1),(144,1),(145,1),(146,1),(147,1),(148,1),(149,1),(150,1),(151,1),(152,1),(153,1),(154,1),(155,1),(156,1),(157,1),(158,1),(159,1),(160,1),(161,1),(162,1),(163,1),(164,1),(165,1),(166,1),(167,1),(168,1),(169,1),(170,1),(171,1),(172,1),(173,1),(174,1),(175,1),(176,1),(177,1),(178,1),(179,1),(180,1),(181,1),(182,1),(183,1),(184,1),(185,1),(186,1),(187,1),(188,1),(189,1),(190,1),(191,1),(192,1),(193,1),(194,1),(195,1),(196,1),(197,1),(198,1),(199,1),(200,1),(201,1),(202,1),(203,1),(204,1),(205,1),(206,1),(207,1),(208,1),(209,1),(210,1),(211,1),(212,1),(213,1),(214,1),(215,1),(216,1),(217,1),(218,1),(219,1),(220,1),(221,1),(222,1),(223,1),(224,1),(225,1),(226,1),(227,1),(228,1),(229,1),(230,1),(231,1),(232,1),(233,1),(234,1),(235,1),(236,1),(237,1),(238,1),(239,1),(240,1),(241,1),(242,1),(243,1),(244,1),(245,1),(246,1),(247,1),(248,1),(249,1),(250,1),(251,1),(252,1),(253,1),(254,1),(255,1),(256,1),(257,1),(258,1),(259,1),(260,1),(261,1),(262,1),(263,1),(264,1),(265,1),(266,1),(267,1),(268,1),(269,1),(270,1),(271,1),(272,1),(273,1),(274,1),(275,1),(276,1),(277,1),(278,1),(279,1),(280,1),(281,1),(282,1),(283,1),(284,1),(285,1),(286,1),(287,1),(288,1),(289,1),(290,1),(291,1),(292,1),(293,1),(294,1),(295,1),(296,1),(297,1),(298,1),(299,1),(300,1),(301,1),(302,1),(303,1),(304,1),(305,1),(306,1),(307,1),(308,1),(309,1),(310,1),(311,1),(312,1),(313,1),(314,1),(315,1),(316,1),(317,1),(318,1),(319,1),(320,1),(321,1),(322,1),(323,1),(324,1),(325,1),(326,1),(327,1),(1,2),(2,2),(3,2),(4,2),(5,2),(6,2),(7,2),(8,2),(9,2),(10,2),(11,2),(12,2),(13,2),(14,2),(15,2),(16,2),(17,2),(18,2),(19,2),(20,2),(21,2),(22,2),(23,2),(24,2),(25,2),(26,2),(27,2),(28,2),(29,2),(30,2),(31,2),(32,2),(33,2),(34,2),(35,2),(36,2),(37,2),(38,2),(39,2),(40,2),(41,2),(42,2),(43,2),(44,2),(45,2),(46,2),(47,2),(48,2),(49,2),(50,2),(51,2),(52,2),(53,2),(54,2),(55,2),(56,2),(57,2),(58,2),(59,2),(60,2),(61,2),(62,2),(63,2),(64,2),(65,2),(66,2),(67,2),(68,2),(69,2),(70,2),(71,2),(72,2),(73,2),(74,2),(75,2),(76,2),(77,2),(78,2),(79,2),(80,2),(81,2),(82,2),(83,2),(84,2),(85,2),(86,2),(87,2),(88,2),(89,2),(90,2),(91,2),(92,2),(93,2),(94,2),(95,2),(96,2),(97,2),(98,2),(99,2),(100,2),(101,2),(102,2),(103,2),(104,2),(105,2),(106,2),(107,2),(108,2),(109,2),(110,2),(111,2),(112,2),(113,2),(114,2),(115,2),(116,2),(117,2),(118,2),(119,2),(120,2),(121,2),(122,2),(123,2),(124,2),(125,2),(126,2),(127,2),(128,2),(129,2),(130,2),(131,2),(132,2),(133,2),(134,2),(135,2),(136,2),(137,2),(138,2),(139,2),(140,2),(141,2),(142,2),(143,2),(144,2),(145,2),(146,2),(147,2),(148,2),(149,2),(150,2),(151,2),(152,2),(153,2),(154,2),(155,2),(156,2),(157,2),(158,2),(159,2),(160,2),(161,2),(162,2),(163,2),(164,2),(165,2),(166,2),(167,2),(168,2),(169,2),(170,2),(171,2),(172,2),(173,2),(174,2),(175,2),(176,2),(177,2),(178,2),(179,2),(180,2),(181,2),(182,2),(183,2),(184,2),(185,2),(186,2),(187,2),(188,2),(189,2),(190,2),(191,2),(192,2),(193,2),(194,2),(195,2),(196,2),(197,2),(198,2),(199,2),(200,2),(201,2),(202,2),(203,2),(204,2),(205,2),(206,2),(207,2),(208,2),(209,2),(210,2),(211,2),(212,2),(213,2),(214,2),(215,2),(216,2),(217,2),(218,2),(219,2),(220,2),(221,2),(222,2),(223,2),(224,2),(225,2),(226,2),(227,2),(228,2),(229,2),(230,2),(231,2),(232,2),(233,2),(234,2),(235,2),(236,2),(237,2),(238,2),(239,2),(240,2),(241,2),(242,2),(243,2),(244,2),(245,2),(246,2),(247,2),(248,2),(249,2),(250,2),(251,2),(252,2),(253,2),(254,2),(255,2),(256,2),(257,2),(258,2),(259,2),(260,2),(261,2),(262,2),(263,2),(264,2),(265,2),(266,2),(267,2),(268,2),(269,2),(270,2),(271,2),(272,2),(273,2),(274,2),(275,2),(276,2),(277,2),(278,2),(279,2),(280,2),(281,2),(282,2),(283,2),(284,2),(285,2),(286,2),(287,2),(288,2),(289,2),(290,2),(291,2),(292,2),(293,2),(294,2),(295,2),(296,2),(297,2),(298,2),(299,2),(300,2),(301,2),(302,2),(303,2),(304,2),(305,2),(306,2),(307,2),(308,2),(309,2),(310,2),(311,2),(312,2),(313,2),(314,2),(315,2),(316,2),(317,2),(318,2),(319,2),(320,2),(321,2),(322,2),(323,2),(324,2),(325,2),(326,2),(327,2);
/*!40000 ALTER TABLE `wn_role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_roles`
--

DROP TABLE IF EXISTS `wn_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wn_roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_roles`
--

LOCK TABLES `wn_roles` WRITE;
/*!40000 ALTER TABLE `wn_roles` DISABLE KEYS */;
INSERT INTO `wn_roles` VALUES (1,'superadmin','web','2025-05-04 00:18:51','2025-05-04 00:18:51'),(2,'admin','web','2025-05-04 00:18:52','2025-05-04 00:18:52'),(3,'manager','web','2025-05-04 00:18:52','2025-05-04 00:18:52'),(4,'member','web','2025-05-04 00:18:52','2025-05-04 00:18:52'),(5,'suspended','web','2025-05-04 00:18:52','2025-05-04 00:18:52');
/*!40000 ALTER TABLE `wn_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_search_keywords`
--

DROP TABLE IF EXISTS `wn_search_keywords`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_search_keywords` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `website_id` bigint unsigned DEFAULT NULL,
  `keyword` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `locale` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `count` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wn_search_keywords_website_id_foreign` (`website_id`),
  CONSTRAINT `wn_search_keywords_website_id_foreign` FOREIGN KEY (`website_id`) REFERENCES `wn_websites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_search_keywords`
--

LOCK TABLES `wn_search_keywords` WRITE;
/*!40000 ALTER TABLE `wn_search_keywords` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_search_keywords` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_sessions`
--

DROP TABLE IF EXISTS `wn_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `wn_sessions_user_id_index` (`user_id`),
  KEY `wn_sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_sessions`
--

LOCK TABLES `wn_sessions` WRITE;
/*!40000 ALTER TABLE `wn_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_settings`
--

DROP TABLE IF EXISTS `wn_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wn_settings_key_unique` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_settings`
--

LOCK TABLES `wn_settings` WRITE;
/*!40000 ALTER TABLE `wn_settings` DISABLE KEYS */;
INSERT INTO `wn_settings` VALUES (1,'version','4.99.4.2',NULL,'2025-05-04 00:21:34','2025-05-04 00:21:34'),(2,'check_beta_functions','0',NULL,'2025-05-04 00:21:34','2025-05-04 00:21:34'),(3,'data_cache_time','3600',NULL,'2025-05-04 00:21:35','2025-05-04 00:21:35'),(4,'live_data_cache_time',NULL,NULL,'2025-05-04 00:21:35','2025-05-04 00:21:35'),(5,'active_models','[\"Advertisement\",\"Menu\",\"Page\",\"Post\",\"Setting\",\"Tag\",\"User\",\"Website\"]',NULL,'2025-05-04 00:21:35','2025-05-04 00:21:35'),(6,'request_timeout','60',NULL,'2025-05-04 00:21:35','2025-05-04 00:21:35'),(7,'cache_view_count','1',NULL,'2025-05-04 00:21:35','2025-05-04 00:21:35'),(8,'enable_cache','1',NULL,'2025-05-04 00:21:35','2025-05-04 00:21:35'),(9,'system_name','WNCMS',NULL,'2025-05-04 00:21:36','2025-05-04 00:21:36'),(10,'multi_website','0',NULL,'2025-05-04 00:21:36','2025-05-04 00:21:36');
/*!40000 ALTER TABLE `wn_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_subscriptions`
--

DROP TABLE IF EXISTS `wn_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `plan_id` bigint unsigned DEFAULT NULL,
  `price_id` bigint unsigned DEFAULT NULL,
  `subscribed_at` timestamp NOT NULL,
  `expired_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wn_subscriptions_user_id_foreign` (`user_id`),
  KEY `wn_subscriptions_plan_id_foreign` (`plan_id`),
  KEY `wn_subscriptions_price_id_foreign` (`price_id`),
  CONSTRAINT `wn_subscriptions_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `wn_plans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `wn_subscriptions_price_id_foreign` FOREIGN KEY (`price_id`) REFERENCES `wn_prices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `wn_subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `wn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_subscriptions`
--

LOCK TABLES `wn_subscriptions` WRITE;
/*!40000 ALTER TABLE `wn_subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_tag_keywords`
--

DROP TABLE IF EXISTS `wn_tag_keywords`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_tag_keywords` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tag_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wn_tag_keywords_tag_id_foreign` (`tag_id`),
  CONSTRAINT `wn_tag_keywords_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `wn_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_tag_keywords`
--

LOCK TABLES `wn_tag_keywords` WRITE;
/*!40000 ALTER TABLE `wn_tag_keywords` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_tag_keywords` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_taggables`
--

DROP TABLE IF EXISTS `wn_taggables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_taggables` (
  `tag_id` bigint unsigned NOT NULL,
  `taggable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `taggable_id` bigint unsigned NOT NULL,
  UNIQUE KEY `wn_taggables_tag_id_taggable_id_taggable_type_unique` (`tag_id`,`taggable_id`,`taggable_type`),
  KEY `wn_taggables_taggable_type_taggable_id_index` (`taggable_type`,`taggable_id`),
  CONSTRAINT `wn_taggables_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `wn_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_taggables`
--

LOCK TABLES `wn_taggables` WRITE;
/*!40000 ALTER TABLE `wn_taggables` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_taggables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_tags`
--

DROP TABLE IF EXISTS `wn_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_column` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wn_tags_type_slug_unique` (`type`,`slug`),
  KEY `wn_tags_parent_id_foreign` (`parent_id`),
  KEY `wn_tags_type_name_index` (`type`,`name`),
  KEY `wn_tags_order_column_index` (`order_column`),
  CONSTRAINT `wn_tags_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `wn_tags` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_tags`
--

LOCK TABLES `wn_tags` WRITE;
/*!40000 ALTER TABLE `wn_tags` DISABLE KEYS */;
INSERT INTO `wn_tags` VALUES (1,NULL,'未分類','uncategorized','post_category',NULL,NULL,NULL,'2025-05-04 00:21:33','2025-05-04 00:21:33');
/*!40000 ALTER TABLE `wn_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_theme_options`
--

DROP TABLE IF EXISTS `wn_theme_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_theme_options` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `website_id` bigint unsigned NOT NULL,
  `theme` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wn_theme_options_website_id_theme_key_unique` (`website_id`,`theme`,`key`),
  CONSTRAINT `wn_theme_options_website_id_foreign` FOREIGN KEY (`website_id`) REFERENCES `wn_websites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_theme_options`
--

LOCK TABLES `wn_theme_options` WRITE;
/*!40000 ALTER TABLE `wn_theme_options` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_theme_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_themes`
--

DROP TABLE IF EXISTS `wn_themes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_themes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `theme_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `author` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1.0.0',
  `demo_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `author_created_at` datetime DEFAULT NULL,
  `author_updated_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wn_themes_theme_id_unique` (`theme_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_themes`
--

LOCK TABLES `wn_themes` WRITE;
/*!40000 ALTER TABLE `wn_themes` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_themes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_traffic_summaries`
--

DROP TABLE IF EXISTS `wn_traffic_summaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_traffic_summaries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `website_id` bigint unsigned DEFAULT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `period` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `count` int DEFAULT NULL,
  `is_recorded` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wn_traffic_summaries_website_id_foreign` (`website_id`),
  CONSTRAINT `wn_traffic_summaries_website_id_foreign` FOREIGN KEY (`website_id`) REFERENCES `wn_websites` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_traffic_summaries`
--

LOCK TABLES `wn_traffic_summaries` WRITE;
/*!40000 ALTER TABLE `wn_traffic_summaries` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_traffic_summaries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_traffics`
--

DROP TABLE IF EXISTS `wn_traffics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_traffics` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `website_id` bigint unsigned DEFAULT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `geo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referrer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wn_traffics_website_id_foreign` (`website_id`),
  CONSTRAINT `wn_traffics_website_id_foreign` FOREIGN KEY (`website_id`) REFERENCES `wn_websites` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_traffics`
--

LOCK TABLES `wn_traffics` WRITE;
/*!40000 ALTER TABLE `wn_traffics` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_traffics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_transactions`
--

DROP TABLE IF EXISTS `wn_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ref_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_fraud` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wn_transactions_order_id_foreign` (`order_id`),
  CONSTRAINT `wn_transactions_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `wn_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_transactions`
--

LOCK TABLES `wn_transactions` WRITE;
/*!40000 ALTER TABLE `wn_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_translations`
--

DROP TABLE IF EXISTS `wn_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_translations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `translatable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `translatable_id` bigint unsigned NOT NULL,
  `field` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `locale` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wn_translations_translatable_type_translatable_id_index` (`translatable_type`,`translatable_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_translations`
--

LOCK TABLES `wn_translations` WRITE;
/*!40000 ALTER TABLE `wn_translations` DISABLE KEYS */;
INSERT INTO `wn_translations` VALUES (1,'Wncms\\Models\\Tag',1,'name','zh_TW','未分類','2025-05-04 00:21:34','2025-05-04 00:21:34'),(2,'Wncms\\Models\\Tag',1,'name','zh_CN','未分类','2025-05-04 00:21:34','2025-05-04 00:21:34'),(3,'Wncms\\Models\\Tag',1,'name','en','Uncategorized','2025-05-04 00:21:34','2025-05-04 00:21:34'),(4,'Wncms\\Models\\Tag',1,'name','ja','未分類','2025-05-04 00:21:34','2025-05-04 00:21:34');
/*!40000 ALTER TABLE `wn_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_user_website`
--

DROP TABLE IF EXISTS `wn_user_website`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_user_website` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `website_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wn_user_website_user_id_foreign` (`user_id`),
  KEY `wn_user_website_website_id_foreign` (`website_id`),
  CONSTRAINT `wn_user_website_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `wn_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wn_user_website_website_id_foreign` FOREIGN KEY (`website_id`) REFERENCES `wn_websites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_user_website`
--

LOCK TABLES `wn_user_website` WRITE;
/*!40000 ALTER TABLE `wn_user_website` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_user_website` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_users`
--

DROP TABLE IF EXISTS `wn_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nickname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `api_token` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `referrer_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wn_users_email_unique` (`email`),
  UNIQUE KEY `wn_users_api_token_unique` (`api_token`),
  KEY `wn_users_referrer_id_foreign` (`referrer_id`),
  CONSTRAINT `wn_users_referrer_id_foreign` FOREIGN KEY (`referrer_id`) REFERENCES `wn_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_users`
--

LOCK TABLES `wn_users` WRITE;
/*!40000 ALTER TABLE `wn_users` DISABLE KEYS */;
INSERT INTO `wn_users` VALUES (1,NULL,NULL,NULL,'admin','admin@demo.com','2025-05-04 00:21:30',NULL,'$2y$12$mkpzCYYODsHLHIARaSUgCOmVdJfCrFd6lmq0ipm2c8J05.d5sQUaO','8a97d0c3-e5ea-487c-b851-0fbe0cc4f56a',NULL,'2025-05-04 00:21:31','2025-05-04 00:21:31',NULL),(2,NULL,NULL,NULL,'manager','manager@demo.com','2025-05-04 00:21:33',NULL,'$2y$12$7CWUxC6qQJZ3bryy4KXbI.qu4ssvL9xiZZ68nuvAu6n.RYxLpvDXu','fd780473-bb67-45e5-868d-82f588e9f102',NULL,'2025-05-04 00:21:33','2025-05-04 00:21:33',NULL);
/*!40000 ALTER TABLE `wn_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wn_websites`
--

DROP TABLE IF EXISTS `wn_websites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wn_websites` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `domain` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `site_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `site_logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `site_favicon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `site_slogan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `site_seo_keywords` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `site_seo_description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `theme` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_verification` text COLLATE utf8mb4_unicode_ci,
  `head_code` text COLLATE utf8mb4_unicode_ci,
  `body_code` text COLLATE utf8mb4_unicode_ci,
  `analytics` text COLLATE utf8mb4_unicode_ci,
  `license` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `enabled_page_cache` tinyint(1) NOT NULL DEFAULT '0',
  `enabled_data_cache` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wn_websites_domain_unique` (`domain`),
  KEY `wn_websites_user_id_foreign` (`user_id`),
  CONSTRAINT `wn_websites_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `wn_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wn_websites`
--

LOCK TABLES `wn_websites` WRITE;
/*!40000 ALTER TABLE `wn_websites` DISABLE KEYS */;
/*!40000 ALTER TABLE `wn_websites` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-05-03 16:38:40
