-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： localhost
-- 產生時間： 2025 年 09 月 08 日 02:35
-- 伺服器版本： 8.0.35
-- PHP 版本： 8.1.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫： `wncms_package`
--

-- --------------------------------------------------------

--
-- 資料表結構 `wn_activity_log`
--

DROP TABLE IF EXISTS `wn_activity_log`;
CREATE TABLE `wn_activity_log` (
  `id` bigint UNSIGNED NOT NULL,
  `log_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint UNSIGNED DEFAULT NULL,
  `causer_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `causer_id` bigint UNSIGNED DEFAULT NULL,
  `properties` json DEFAULT NULL,
  `batch_uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_advertisements`
--

DROP TABLE IF EXISTS `wn_advertisements`;
CREATE TABLE `wn_advertisements` (
  `id` bigint UNSIGNED NOT NULL,
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
  `contact` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_cards`
--

DROP TABLE IF EXISTS `wn_cards`;
CREATE TABLE `wn_cards` (
  `id` bigint UNSIGNED NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` decimal(10,2) DEFAULT NULL,
  `plan_id` bigint UNSIGNED DEFAULT NULL,
  `product_id` bigint UNSIGNED DEFAULT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `redeemed_at` timestamp NULL DEFAULT NULL,
  `expired_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_channels`
--

DROP TABLE IF EXISTS `wn_channels`;
CREATE TABLE `wn_channels` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_clicks`
--

DROP TABLE IF EXISTS `wn_clicks`;
CREATE TABLE `wn_clicks` (
  `id` bigint UNSIGNED NOT NULL,
  `channel_id` bigint UNSIGNED DEFAULT NULL,
  `ip` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parameters` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `clickable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `clickable_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_comments`
--

DROP TABLE IF EXISTS `wn_comments`;
CREATE TABLE `wn_comments` (
  `id` bigint UNSIGNED NOT NULL,
  `commentable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `commentable_id` bigint UNSIGNED NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'visible',
  `user_id` bigint UNSIGNED NOT NULL,
  `parent_id` bigint UNSIGNED DEFAULT NULL,
  `content` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_contact_forms`
--

DROP TABLE IF EXISTS `wn_contact_forms`;
CREATE TABLE `wn_contact_forms` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `success_action` text COLLATE utf8mb4_unicode_ci,
  `fail_action` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_contact_form_options`
--

DROP TABLE IF EXISTS `wn_contact_form_options`;
CREATE TABLE `wn_contact_form_options` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `placeholder` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `default_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `options` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_contact_form_option_relationship`
--

DROP TABLE IF EXISTS `wn_contact_form_option_relationship`;
CREATE TABLE `wn_contact_form_option_relationship` (
  `id` bigint UNSIGNED NOT NULL,
  `form_id` bigint UNSIGNED NOT NULL,
  `option_id` bigint UNSIGNED NOT NULL,
  `order` int NOT NULL,
  `is_required` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_contact_form_submissions`
--

DROP TABLE IF EXISTS `wn_contact_form_submissions`;
CREATE TABLE `wn_contact_form_submissions` (
  `id` bigint UNSIGNED NOT NULL,
  `contact_form_id` bigint UNSIGNED DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unread',
  `content` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_coupons`
--

DROP TABLE IF EXISTS `wn_coupons`;
CREATE TABLE `wn_coupons` (
  `id` bigint UNSIGNED NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_credits`
--

DROP TABLE IF EXISTS `wn_credits`;
CREATE TABLE `wn_credits` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_credit_transactions`
--

DROP TABLE IF EXISTS `wn_credit_transactions`;
CREATE TABLE `wn_credit_transactions` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `credit_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_discounts`
--

DROP TABLE IF EXISTS `wn_discounts`;
CREATE TABLE `wn_discounts` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('percentage','fixed') COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_domain_aliases`
--

DROP TABLE IF EXISTS `wn_domain_aliases`;
CREATE TABLE `wn_domain_aliases` (
  `id` bigint UNSIGNED NOT NULL,
  `website_id` bigint UNSIGNED DEFAULT NULL,
  `domain` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_failed_jobs`
--

DROP TABLE IF EXISTS `wn_failed_jobs`;
CREATE TABLE `wn_failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_faqs`
--

DROP TABLE IF EXISTS `wn_faqs`;
CREATE TABLE `wn_faqs` (
  `id` bigint UNSIGNED NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `question` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `answer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order` int DEFAULT NULL,
  `is_pinned` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_jobs`
--

DROP TABLE IF EXISTS `wn_jobs`;
CREATE TABLE `wn_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_job_batches`
--

DROP TABLE IF EXISTS `wn_job_batches`;
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
  `finished_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_links`
--

DROP TABLE IF EXISTS `wn_links`;
CREATE TABLE `wn_links` (
  `id` bigint UNSIGNED NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_media`
--

DROP TABLE IF EXISTS `wn_media`;
CREATE TABLE `wn_media` (
  `id` bigint UNSIGNED NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint UNSIGNED NOT NULL,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `collection_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `disk` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `conversions_disk` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` bigint UNSIGNED NOT NULL,
  `manipulations` json NOT NULL,
  `custom_properties` json NOT NULL,
  `generated_conversions` json NOT NULL,
  `responsive_images` json NOT NULL,
  `order_column` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_menus`
--

DROP TABLE IF EXISTS `wn_menus`;
CREATE TABLE `wn_menus` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_menu_items`
--

DROP TABLE IF EXISTS `wn_menu_items`;
CREATE TABLE `wn_menu_items` (
  `id` bigint UNSIGNED NOT NULL,
  `menu_id` bigint UNSIGNED NOT NULL,
  `parent_id` bigint UNSIGNED DEFAULT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_migrations`
--

DROP TABLE IF EXISTS `wn_migrations`;
CREATE TABLE `wn_migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_model_has_permissions`
--

DROP TABLE IF EXISTS `wn_model_has_permissions`;
CREATE TABLE `wn_model_has_permissions` (
  `permission_id` bigint UNSIGNED NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_model_has_roles`
--

DROP TABLE IF EXISTS `wn_model_has_roles`;
CREATE TABLE `wn_model_has_roles` (
  `role_id` bigint UNSIGNED NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_model_has_websites`
--

DROP TABLE IF EXISTS `wn_model_has_websites`;
CREATE TABLE `wn_model_has_websites` (
  `website_id` bigint UNSIGNED NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_orders`
--

DROP TABLE IF EXISTS `wn_orders`;
CREATE TABLE `wn_orders` (
  `id` bigint UNSIGNED NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending_payment',
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coupon_id` bigint UNSIGNED DEFAULT NULL,
  `original_amount` decimal(10,2) DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nickname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tel` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_gateway_id` bigint UNSIGNED DEFAULT NULL,
  `tracking_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remark` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_order_items`
--

DROP TABLE IF EXISTS `wn_order_items`;
CREATE TABLE `wn_order_items` (
  `id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED NOT NULL,
  `order_itemable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_itemable_id` bigint UNSIGNED NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_pages`
--

DROP TABLE IF EXISTS `wn_pages`;
CREATE TABLE `wn_pages` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `visibility` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'public',
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'plain',
  `blade_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci,
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_locked` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_page_templates`
--

DROP TABLE IF EXISTS `wn_page_templates`;
CREATE TABLE `wn_page_templates` (
  `id` bigint UNSIGNED NOT NULL,
  `page_id` bigint UNSIGNED NOT NULL,
  `theme_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `template_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` json NOT NULL,
  `order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_parameters`
--

DROP TABLE IF EXISTS `wn_parameters`;
CREATE TABLE `wn_parameters` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_password_reset_tokens`
--

DROP TABLE IF EXISTS `wn_password_reset_tokens`;
CREATE TABLE `wn_password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_payment_gateways`
--

DROP TABLE IF EXISTS `wn_payment_gateways`;
CREATE TABLE `wn_payment_gateways` (
  `id` bigint UNSIGNED NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_permissions`
--

DROP TABLE IF EXISTS `wn_permissions`;
CREATE TABLE `wn_permissions` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_plans`
--

DROP TABLE IF EXISTS `wn_plans`;
CREATE TABLE `wn_plans` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `free_trial_duration` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_plugins`
--

DROP TABLE IF EXISTS `wn_plugins`;
CREATE TABLE `wn_plugins` (
  `id` bigint UNSIGNED NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_posts`
--

DROP TABLE IF EXISTS `wn_posts`;
CREATE TABLE `wn_posts` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_prices`
--

DROP TABLE IF EXISTS `wn_prices`;
CREATE TABLE `wn_prices` (
  `id` bigint UNSIGNED NOT NULL,
  `priceable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `priceable_id` bigint UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `duration` int DEFAULT NULL,
  `duration_unit` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attributes` json DEFAULT NULL,
  `is_lifetime` tinyint(1) NOT NULL DEFAULT '0',
  `stock` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_products`
--

DROP TABLE IF EXISTS `wn_products`;
CREATE TABLE `wn_products` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int DEFAULT NULL,
  `is_variable` tinyint(1) NOT NULL DEFAULT '0',
  `properties` json DEFAULT NULL,
  `variants` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_records`
--

DROP TABLE IF EXISTS `wn_records`;
CREATE TABLE `wn_records` (
  `id` bigint UNSIGNED NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sub_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `detail` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_roles`
--

DROP TABLE IF EXISTS `wn_roles`;
CREATE TABLE `wn_roles` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_role_has_permissions`
--

DROP TABLE IF EXISTS `wn_role_has_permissions`;
CREATE TABLE `wn_role_has_permissions` (
  `permission_id` bigint UNSIGNED NOT NULL,
  `role_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_search_keywords`
--

DROP TABLE IF EXISTS `wn_search_keywords`;
CREATE TABLE `wn_search_keywords` (
  `id` bigint UNSIGNED NOT NULL,
  `keyword` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `locale` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `count` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_sessions`
--

DROP TABLE IF EXISTS `wn_sessions`;
CREATE TABLE `wn_sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_settings`
--

DROP TABLE IF EXISTS `wn_settings`;
CREATE TABLE `wn_settings` (
  `id` bigint UNSIGNED NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_subscriptions`
--

DROP TABLE IF EXISTS `wn_subscriptions`;
CREATE TABLE `wn_subscriptions` (
  `id` bigint UNSIGNED NOT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `user_id` bigint UNSIGNED NOT NULL,
  `plan_id` bigint UNSIGNED DEFAULT NULL,
  `price_id` bigint UNSIGNED DEFAULT NULL,
  `subscribed_at` timestamp NOT NULL,
  `expired_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_taggables`
--

DROP TABLE IF EXISTS `wn_taggables`;
CREATE TABLE `wn_taggables` (
  `tag_id` bigint UNSIGNED NOT NULL,
  `taggable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `taggable_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_tags`
--

DROP TABLE IF EXISTS `wn_tags`;
CREATE TABLE `wn_tags` (
  `id` bigint UNSIGNED NOT NULL,
  `parent_id` bigint UNSIGNED DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_column` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_tag_keywords`
--

DROP TABLE IF EXISTS `wn_tag_keywords`;
CREATE TABLE `wn_tag_keywords` (
  `id` bigint UNSIGNED NOT NULL,
  `tag_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_themes`
--

DROP TABLE IF EXISTS `wn_themes`;
CREATE TABLE `wn_themes` (
  `id` bigint UNSIGNED NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_theme_options`
--

DROP TABLE IF EXISTS `wn_theme_options`;
CREATE TABLE `wn_theme_options` (
  `id` bigint UNSIGNED NOT NULL,
  `website_id` bigint UNSIGNED NOT NULL,
  `theme` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_traffics`
--

DROP TABLE IF EXISTS `wn_traffics`;
CREATE TABLE `wn_traffics` (
  `id` bigint UNSIGNED NOT NULL,
  `website_id` bigint UNSIGNED DEFAULT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `geo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referrer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_traffic_summaries`
--

DROP TABLE IF EXISTS `wn_traffic_summaries`;
CREATE TABLE `wn_traffic_summaries` (
  `id` bigint UNSIGNED NOT NULL,
  `website_id` bigint UNSIGNED DEFAULT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `period` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `count` int DEFAULT NULL,
  `is_recorded` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_transactions`
--

DROP TABLE IF EXISTS `wn_transactions`;
CREATE TABLE `wn_transactions` (
  `id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ref_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_fraud` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_translations`
--

DROP TABLE IF EXISTS `wn_translations`;
CREATE TABLE `wn_translations` (
  `id` bigint UNSIGNED NOT NULL,
  `translatable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `translatable_id` bigint UNSIGNED NOT NULL,
  `field` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `locale` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_users`
--

DROP TABLE IF EXISTS `wn_users`;
CREATE TABLE `wn_users` (
  `id` bigint UNSIGNED NOT NULL,
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
  `referrer_id` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_user_website`
--

DROP TABLE IF EXISTS `wn_user_website`;
CREATE TABLE `wn_user_website` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `website_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `wn_websites`
--

DROP TABLE IF EXISTS `wn_websites`;
CREATE TABLE `wn_websites` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `wn_activity_log`
--
ALTER TABLE `wn_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject` (`subject_type`,`subject_id`),
  ADD KEY `causer` (`causer_type`,`causer_id`),
  ADD KEY `wn_activity_log_log_name_index` (`log_name`);

--
-- 資料表索引 `wn_advertisements`
--
ALTER TABLE `wn_advertisements`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `wn_cards`
--
ALTER TABLE `wn_cards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wn_cards_code_unique` (`code`),
  ADD KEY `wn_cards_plan_id_foreign` (`plan_id`),
  ADD KEY `wn_cards_user_id_foreign` (`user_id`),
  ADD KEY `wn_cards_product_id_foreign` (`product_id`);

--
-- 資料表索引 `wn_channels`
--
ALTER TABLE `wn_channels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wn_channels_slug_unique` (`slug`);

--
-- 資料表索引 `wn_clicks`
--
ALTER TABLE `wn_clicks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wn_clicks_channel_id_foreign` (`channel_id`),
  ADD KEY `wn_clicks_clickable_type_clickable_id_index` (`clickable_type`,`clickable_id`);

--
-- 資料表索引 `wn_comments`
--
ALTER TABLE `wn_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wn_comments_commentable_type_commentable_id_index` (`commentable_type`,`commentable_id`),
  ADD KEY `wn_comments_user_id_foreign` (`user_id`),
  ADD KEY `wn_comments_parent_id_foreign` (`parent_id`);

--
-- 資料表索引 `wn_contact_forms`
--
ALTER TABLE `wn_contact_forms`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `wn_contact_form_options`
--
ALTER TABLE `wn_contact_form_options`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `wn_contact_form_option_relationship`
--
ALTER TABLE `wn_contact_form_option_relationship`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wn_contact_form_option_relationship_form_id_foreign` (`form_id`),
  ADD KEY `wn_contact_form_option_relationship_option_id_foreign` (`option_id`);

--
-- 資料表索引 `wn_contact_form_submissions`
--
ALTER TABLE `wn_contact_form_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wn_contact_form_submissions_contact_form_id_foreign` (`contact_form_id`);

--
-- 資料表索引 `wn_coupons`
--
ALTER TABLE `wn_coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wn_coupons_code_unique` (`code`);

--
-- 資料表索引 `wn_credits`
--
ALTER TABLE `wn_credits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wn_credits_user_id_type_unique` (`user_id`,`type`);

--
-- 資料表索引 `wn_credit_transactions`
--
ALTER TABLE `wn_credit_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wn_credit_transactions_user_id_foreign` (`user_id`);

--
-- 資料表索引 `wn_discounts`
--
ALTER TABLE `wn_discounts`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `wn_domain_aliases`
--
ALTER TABLE `wn_domain_aliases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wn_domain_aliases_website_id_foreign` (`website_id`);

--
-- 資料表索引 `wn_failed_jobs`
--
ALTER TABLE `wn_failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wn_failed_jobs_uuid_unique` (`uuid`);

--
-- 資料表索引 `wn_faqs`
--
ALTER TABLE `wn_faqs`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `wn_jobs`
--
ALTER TABLE `wn_jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wn_jobs_queue_index` (`queue`);

--
-- 資料表索引 `wn_job_batches`
--
ALTER TABLE `wn_job_batches`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `wn_links`
--
ALTER TABLE `wn_links`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wn_links_tracking_code_index` (`tracking_code`);

--
-- 資料表索引 `wn_media`
--
ALTER TABLE `wn_media`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wn_media_uuid_unique` (`uuid`),
  ADD KEY `wn_media_model_type_model_id_index` (`model_type`,`model_id`),
  ADD KEY `wn_media_order_column_index` (`order_column`);

--
-- 資料表索引 `wn_menus`
--
ALTER TABLE `wn_menus`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `wn_menu_items`
--
ALTER TABLE `wn_menu_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wn_menu_items_menu_id_foreign` (`menu_id`),
  ADD KEY `wn_menu_items_parent_id_foreign` (`parent_id`);

--
-- 資料表索引 `wn_migrations`
--
ALTER TABLE `wn_migrations`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `wn_model_has_permissions`
--
ALTER TABLE `wn_model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- 資料表索引 `wn_model_has_roles`
--
ALTER TABLE `wn_model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- 資料表索引 `wn_model_has_websites`
--
ALTER TABLE `wn_model_has_websites`
  ADD PRIMARY KEY (`website_id`,`model_id`,`model_type`),
  ADD KEY `wn_model_has_websites_model_type_model_id_index` (`model_type`,`model_id`);

--
-- 資料表索引 `wn_orders`
--
ALTER TABLE `wn_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wn_orders_slug_unique` (`slug`),
  ADD KEY `wn_orders_user_id_foreign` (`user_id`),
  ADD KEY `wn_orders_coupon_id_foreign` (`coupon_id`),
  ADD KEY `wn_orders_payment_gateway_id_foreign` (`payment_gateway_id`);

--
-- 資料表索引 `wn_order_items`
--
ALTER TABLE `wn_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wn_order_items_order_id_foreign` (`order_id`),
  ADD KEY `wn_order_items_order_itemable_type_id_index` (`order_itemable_type`,`order_itemable_id`);

--
-- 資料表索引 `wn_pages`
--
ALTER TABLE `wn_pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wn_pages_slug_unique` (`slug`),
  ADD KEY `wn_pages_user_id_foreign` (`user_id`),
  ADD KEY `wn_pages_title_index` (`title`);

--
-- 資料表索引 `wn_page_templates`
--
ALTER TABLE `wn_page_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wn_page_templates_page_id_foreign` (`page_id`);

--
-- 資料表索引 `wn_parameters`
--
ALTER TABLE `wn_parameters`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `wn_password_reset_tokens`
--
ALTER TABLE `wn_password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- 資料表索引 `wn_payment_gateways`
--
ALTER TABLE `wn_payment_gateways`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wn_payment_gateways_slug_unique` (`slug`);

--
-- 資料表索引 `wn_permissions`
--
ALTER TABLE `wn_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wn_permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- 資料表索引 `wn_plans`
--
ALTER TABLE `wn_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wn_plans_slug_unique` (`slug`);

--
-- 資料表索引 `wn_plugins`
--
ALTER TABLE `wn_plugins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wn_plugins_plugin_id_unique` (`plugin_id`);

--
-- 資料表索引 `wn_posts`
--
ALTER TABLE `wn_posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wn_posts_slug_unique` (`slug`),
  ADD KEY `wn_posts_user_id_foreign` (`user_id`);

--
-- 資料表索引 `wn_prices`
--
ALTER TABLE `wn_prices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wn_prices_priceable_type_priceable_id_index` (`priceable_type`,`priceable_id`);

--
-- 資料表索引 `wn_products`
--
ALTER TABLE `wn_products`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `wn_records`
--
ALTER TABLE `wn_records`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `wn_roles`
--
ALTER TABLE `wn_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wn_roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- 資料表索引 `wn_role_has_permissions`
--
ALTER TABLE `wn_role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `wn_role_has_permissions_role_id_foreign` (`role_id`);

--
-- 資料表索引 `wn_search_keywords`
--
ALTER TABLE `wn_search_keywords`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `wn_sessions`
--
ALTER TABLE `wn_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wn_sessions_user_id_index` (`user_id`),
  ADD KEY `wn_sessions_last_activity_index` (`last_activity`);

--
-- 資料表索引 `wn_settings`
--
ALTER TABLE `wn_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wn_settings_key_unique` (`key`);

--
-- 資料表索引 `wn_subscriptions`
--
ALTER TABLE `wn_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wn_subscriptions_user_id_foreign` (`user_id`),
  ADD KEY `wn_subscriptions_plan_id_foreign` (`plan_id`),
  ADD KEY `wn_subscriptions_price_id_foreign` (`price_id`);

--
-- 資料表索引 `wn_taggables`
--
ALTER TABLE `wn_taggables`
  ADD UNIQUE KEY `wn_taggables_tag_id_taggable_id_taggable_type_unique` (`tag_id`,`taggable_id`,`taggable_type`),
  ADD KEY `wn_taggables_taggable_type_taggable_id_index` (`taggable_type`,`taggable_id`);

--
-- 資料表索引 `wn_tags`
--
ALTER TABLE `wn_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wn_tags_type_slug_unique` (`type`,`slug`),
  ADD KEY `wn_tags_parent_id_foreign` (`parent_id`),
  ADD KEY `wn_tags_type_name_index` (`type`,`name`),
  ADD KEY `wn_tags_order_column_index` (`order_column`);

--
-- 資料表索引 `wn_tag_keywords`
--
ALTER TABLE `wn_tag_keywords`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wn_tag_keywords_tag_id_foreign` (`tag_id`);

--
-- 資料表索引 `wn_themes`
--
ALTER TABLE `wn_themes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wn_themes_theme_id_unique` (`theme_id`);

--
-- 資料表索引 `wn_theme_options`
--
ALTER TABLE `wn_theme_options`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wn_theme_options_website_id_theme_key_unique` (`website_id`,`theme`,`key`);

--
-- 資料表索引 `wn_traffics`
--
ALTER TABLE `wn_traffics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wn_traffics_website_id_foreign` (`website_id`);

--
-- 資料表索引 `wn_traffic_summaries`
--
ALTER TABLE `wn_traffic_summaries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wn_traffic_summaries_website_id_foreign` (`website_id`);

--
-- 資料表索引 `wn_transactions`
--
ALTER TABLE `wn_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wn_transactions_order_id_foreign` (`order_id`);

--
-- 資料表索引 `wn_translations`
--
ALTER TABLE `wn_translations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wn_translations_translatable_type_translatable_id_index` (`translatable_type`,`translatable_id`);

--
-- 資料表索引 `wn_users`
--
ALTER TABLE `wn_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wn_users_api_token_unique` (`api_token`),
  ADD UNIQUE KEY `wn_users_email_unique` (`email`),
  ADD KEY `wn_users_referrer_id_foreign` (`referrer_id`);

--
-- 資料表索引 `wn_user_website`
--
ALTER TABLE `wn_user_website`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wn_user_website_user_id_foreign` (`user_id`),
  ADD KEY `wn_user_website_website_id_foreign` (`website_id`);

--
-- 資料表索引 `wn_websites`
--
ALTER TABLE `wn_websites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wn_websites_domain_unique` (`domain`),
  ADD KEY `wn_websites_user_id_foreign` (`user_id`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_activity_log`
--
ALTER TABLE `wn_activity_log`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_advertisements`
--
ALTER TABLE `wn_advertisements`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_cards`
--
ALTER TABLE `wn_cards`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_channels`
--
ALTER TABLE `wn_channels`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_clicks`
--
ALTER TABLE `wn_clicks`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_comments`
--
ALTER TABLE `wn_comments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_contact_forms`
--
ALTER TABLE `wn_contact_forms`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_contact_form_options`
--
ALTER TABLE `wn_contact_form_options`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_contact_form_option_relationship`
--
ALTER TABLE `wn_contact_form_option_relationship`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_contact_form_submissions`
--
ALTER TABLE `wn_contact_form_submissions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_coupons`
--
ALTER TABLE `wn_coupons`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_credits`
--
ALTER TABLE `wn_credits`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_credit_transactions`
--
ALTER TABLE `wn_credit_transactions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_discounts`
--
ALTER TABLE `wn_discounts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_domain_aliases`
--
ALTER TABLE `wn_domain_aliases`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_failed_jobs`
--
ALTER TABLE `wn_failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_faqs`
--
ALTER TABLE `wn_faqs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_jobs`
--
ALTER TABLE `wn_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_links`
--
ALTER TABLE `wn_links`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_media`
--
ALTER TABLE `wn_media`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_menus`
--
ALTER TABLE `wn_menus`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_menu_items`
--
ALTER TABLE `wn_menu_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_migrations`
--
ALTER TABLE `wn_migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_orders`
--
ALTER TABLE `wn_orders`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_order_items`
--
ALTER TABLE `wn_order_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_pages`
--
ALTER TABLE `wn_pages`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_page_templates`
--
ALTER TABLE `wn_page_templates`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_parameters`
--
ALTER TABLE `wn_parameters`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_payment_gateways`
--
ALTER TABLE `wn_payment_gateways`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_permissions`
--
ALTER TABLE `wn_permissions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_plans`
--
ALTER TABLE `wn_plans`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_plugins`
--
ALTER TABLE `wn_plugins`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_posts`
--
ALTER TABLE `wn_posts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_prices`
--
ALTER TABLE `wn_prices`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_products`
--
ALTER TABLE `wn_products`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_records`
--
ALTER TABLE `wn_records`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_roles`
--
ALTER TABLE `wn_roles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_search_keywords`
--
ALTER TABLE `wn_search_keywords`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_settings`
--
ALTER TABLE `wn_settings`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_subscriptions`
--
ALTER TABLE `wn_subscriptions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_tags`
--
ALTER TABLE `wn_tags`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_tag_keywords`
--
ALTER TABLE `wn_tag_keywords`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_themes`
--
ALTER TABLE `wn_themes`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_theme_options`
--
ALTER TABLE `wn_theme_options`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_traffics`
--
ALTER TABLE `wn_traffics`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_traffic_summaries`
--
ALTER TABLE `wn_traffic_summaries`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_transactions`
--
ALTER TABLE `wn_transactions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_translations`
--
ALTER TABLE `wn_translations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_users`
--
ALTER TABLE `wn_users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_user_website`
--
ALTER TABLE `wn_user_website`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `wn_websites`
--
ALTER TABLE `wn_websites`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `wn_cards`
--
ALTER TABLE `wn_cards`
  ADD CONSTRAINT `wn_cards_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `wn_plans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wn_cards_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `wn_products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wn_cards_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `wn_users` (`id`) ON DELETE SET NULL;

--
-- 資料表的限制式 `wn_clicks`
--
ALTER TABLE `wn_clicks`
  ADD CONSTRAINT `wn_clicks_channel_id_foreign` FOREIGN KEY (`channel_id`) REFERENCES `wn_channels` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `wn_comments`
--
ALTER TABLE `wn_comments`
  ADD CONSTRAINT `wn_comments_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `wn_comments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wn_comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `wn_users` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `wn_contact_form_option_relationship`
--
ALTER TABLE `wn_contact_form_option_relationship`
  ADD CONSTRAINT `wn_contact_form_option_relationship_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `wn_contact_forms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wn_contact_form_option_relationship_option_id_foreign` FOREIGN KEY (`option_id`) REFERENCES `wn_contact_form_options` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `wn_contact_form_submissions`
--
ALTER TABLE `wn_contact_form_submissions`
  ADD CONSTRAINT `wn_contact_form_submissions_contact_form_id_foreign` FOREIGN KEY (`contact_form_id`) REFERENCES `wn_contact_forms` (`id`) ON DELETE SET NULL;

--
-- 資料表的限制式 `wn_credits`
--
ALTER TABLE `wn_credits`
  ADD CONSTRAINT `wn_credits_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `wn_users` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `wn_credit_transactions`
--
ALTER TABLE `wn_credit_transactions`
  ADD CONSTRAINT `wn_credit_transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `wn_users` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `wn_domain_aliases`
--
ALTER TABLE `wn_domain_aliases`
  ADD CONSTRAINT `wn_domain_aliases_website_id_foreign` FOREIGN KEY (`website_id`) REFERENCES `wn_websites` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `wn_menu_items`
--
ALTER TABLE `wn_menu_items`
  ADD CONSTRAINT `wn_menu_items_menu_id_foreign` FOREIGN KEY (`menu_id`) REFERENCES `wn_menus` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wn_menu_items_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `wn_menu_items` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `wn_model_has_permissions`
--
ALTER TABLE `wn_model_has_permissions`
  ADD CONSTRAINT `wn_model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `wn_permissions` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `wn_model_has_roles`
--
ALTER TABLE `wn_model_has_roles`
  ADD CONSTRAINT `wn_model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `wn_roles` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `wn_model_has_websites`
--
ALTER TABLE `wn_model_has_websites`
  ADD CONSTRAINT `wn_model_has_websites_website_id_foreign` FOREIGN KEY (`website_id`) REFERENCES `wn_websites` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `wn_orders`
--
ALTER TABLE `wn_orders`
  ADD CONSTRAINT `wn_orders_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `wn_coupons` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `wn_orders_payment_gateway_id_foreign` FOREIGN KEY (`payment_gateway_id`) REFERENCES `wn_payment_gateways` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `wn_orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `wn_users` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `wn_order_items`
--
ALTER TABLE `wn_order_items`
  ADD CONSTRAINT `wn_order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `wn_orders` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `wn_pages`
--
ALTER TABLE `wn_pages`
  ADD CONSTRAINT `wn_pages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `wn_users` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `wn_page_templates`
--
ALTER TABLE `wn_page_templates`
  ADD CONSTRAINT `wn_page_templates_page_id_foreign` FOREIGN KEY (`page_id`) REFERENCES `wn_pages` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `wn_posts`
--
ALTER TABLE `wn_posts`
  ADD CONSTRAINT `wn_posts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `wn_users` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `wn_role_has_permissions`
--
ALTER TABLE `wn_role_has_permissions`
  ADD CONSTRAINT `wn_role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `wn_permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wn_role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `wn_roles` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `wn_subscriptions`
--
ALTER TABLE `wn_subscriptions`
  ADD CONSTRAINT `wn_subscriptions_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `wn_plans` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `wn_subscriptions_price_id_foreign` FOREIGN KEY (`price_id`) REFERENCES `wn_prices` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `wn_subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `wn_users` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `wn_taggables`
--
ALTER TABLE `wn_taggables`
  ADD CONSTRAINT `wn_taggables_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `wn_tags` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `wn_tags`
--
ALTER TABLE `wn_tags`
  ADD CONSTRAINT `wn_tags_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `wn_tags` (`id`) ON DELETE SET NULL;

--
-- 資料表的限制式 `wn_tag_keywords`
--
ALTER TABLE `wn_tag_keywords`
  ADD CONSTRAINT `wn_tag_keywords_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `wn_tags` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `wn_theme_options`
--
ALTER TABLE `wn_theme_options`
  ADD CONSTRAINT `wn_theme_options_website_id_foreign` FOREIGN KEY (`website_id`) REFERENCES `wn_websites` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `wn_traffics`
--
ALTER TABLE `wn_traffics`
  ADD CONSTRAINT `wn_traffics_website_id_foreign` FOREIGN KEY (`website_id`) REFERENCES `wn_websites` (`id`) ON DELETE SET NULL;

--
-- 資料表的限制式 `wn_traffic_summaries`
--
ALTER TABLE `wn_traffic_summaries`
  ADD CONSTRAINT `wn_traffic_summaries_website_id_foreign` FOREIGN KEY (`website_id`) REFERENCES `wn_websites` (`id`) ON DELETE SET NULL;

--
-- 資料表的限制式 `wn_transactions`
--
ALTER TABLE `wn_transactions`
  ADD CONSTRAINT `wn_transactions_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `wn_orders` (`id`) ON DELETE SET NULL;

--
-- 資料表的限制式 `wn_users`
--
ALTER TABLE `wn_users`
  ADD CONSTRAINT `wn_users_referrer_id_foreign` FOREIGN KEY (`referrer_id`) REFERENCES `wn_users` (`id`) ON DELETE SET NULL;

--
-- 資料表的限制式 `wn_user_website`
--
ALTER TABLE `wn_user_website`
  ADD CONSTRAINT `wn_user_website_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `wn_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wn_user_website_website_id_foreign` FOREIGN KEY (`website_id`) REFERENCES `wn_websites` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `wn_websites`
--
ALTER TABLE `wn_websites`
  ADD CONSTRAINT `wn_websites_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `wn_users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
