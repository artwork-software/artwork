-- MariaDB dump 10.19  Distrib 10.11.16-MariaDB, for debian-linux-gnu (aarch64)
--
-- Host: localhost    Database: db
-- ------------------------------------------------------
-- Server version	10.11.16-MariaDB-ubu2204-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `accommodation_accommodation_room_type`
--

DROP TABLE IF EXISTS `accommodation_accommodation_room_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `accommodation_accommodation_room_type` (
                                                         `accommodation_id` bigint(20) unsigned NOT NULL,
                                                         `crm_contact_id` bigint(20) unsigned DEFAULT NULL,
                                                         `accommodation_room_type_id` bigint(20) unsigned NOT NULL,
                                                         `cost_per_night` decimal(8,2) NOT NULL DEFAULT 0.00,
                                                         PRIMARY KEY (`accommodation_id`,`accommodation_room_type_id`),
                                                         KEY `fk_acc_room_type` (`accommodation_room_type_id`),
                                                         KEY `accommodation_accommodation_room_type_crm_contact_id_foreign` (`crm_contact_id`),
                                                         CONSTRAINT `accommodation_accommodation_room_type_crm_contact_id_foreign` FOREIGN KEY (`crm_contact_id`) REFERENCES `crm_contacts` (`id`) ON DELETE SET NULL,
                                                         CONSTRAINT `fk_acc_room_acc` FOREIGN KEY (`accommodation_id`) REFERENCES `accommodations` (`id`) ON DELETE CASCADE,
                                                         CONSTRAINT `fk_acc_room_type` FOREIGN KEY (`accommodation_room_type_id`) REFERENCES `accommodation_room_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accommodation_accommodation_room_type`
--

LOCK TABLES `accommodation_accommodation_room_type` WRITE;
/*!40000 ALTER TABLE `accommodation_accommodation_room_type` DISABLE KEYS */;
/*!40000 ALTER TABLE `accommodation_accommodation_room_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accommodation_room_types`
--

DROP TABLE IF EXISTS `accommodation_room_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `accommodation_room_types` (
                                            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                            `name` varchar(255) NOT NULL,
                                            `created_at` timestamp NULL DEFAULT NULL,
                                            `updated_at` timestamp NULL DEFAULT NULL,
                                            PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accommodation_room_types`
--

LOCK TABLES `accommodation_room_types` WRITE;
/*!40000 ALTER TABLE `accommodation_room_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `accommodation_room_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accommodations`
--

DROP TABLE IF EXISTS `accommodations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `accommodations` (
                                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                  `profile_image` varchar(255) DEFAULT NULL,
                                  `name` varchar(255) NOT NULL DEFAULT 'Neue Unterkunft',
                                  `email` varchar(255) DEFAULT NULL,
                                  `phone_number` varchar(255) DEFAULT NULL,
                                  `street` varchar(255) DEFAULT NULL,
                                  `zip_code` varchar(255) DEFAULT NULL,
                                  `location` varchar(255) DEFAULT NULL,
                                  `note` text DEFAULT NULL,
                                  `created_at` timestamp NULL DEFAULT NULL,
                                  `updated_at` timestamp NULL DEFAULT NULL,
                                  `crm_contact_id` bigint(20) unsigned DEFAULT NULL,
                                  PRIMARY KEY (`id`),
                                  KEY `accommodations_crm_contact_id_foreign` (`crm_contact_id`),
                                  CONSTRAINT `accommodations_crm_contact_id_foreign` FOREIGN KEY (`crm_contact_id`) REFERENCES `crm_contacts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accommodations`
--

LOCK TABLES `accommodations` WRITE;
/*!40000 ALTER TABLE `accommodations` DISABLE KEYS */;
/*!40000 ALTER TABLE `accommodations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `activity_log`
--

DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_log` (
                                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                `log_name` varchar(255) DEFAULT NULL,
                                `description` text NOT NULL,
                                `subject_type` varchar(255) DEFAULT NULL,
                                `event` varchar(255) DEFAULT NULL,
                                `subject_id` bigint(20) unsigned DEFAULT NULL,
                                `causer_type` varchar(255) DEFAULT NULL,
                                `causer_id` bigint(20) unsigned DEFAULT NULL,
                                `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`properties`)),
                                `batch_uuid` char(36) DEFAULT NULL,
                                `created_at` timestamp NULL DEFAULT NULL,
                                `updated_at` timestamp NULL DEFAULT NULL,
                                PRIMARY KEY (`id`),
                                KEY `subject` (`subject_type`,`subject_id`),
                                KEY `causer` (`causer_type`,`causer_id`),
                                KEY `activity_log_log_name_index` (`log_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_log`
--

LOCK TABLES `activity_log` WRITE;
/*!40000 ALTER TABLE `activity_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `adjoining_room_main_room`
--

DROP TABLE IF EXISTS `adjoining_room_main_room`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `adjoining_room_main_room` (
                                            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                            `main_room_id` bigint(20) unsigned NOT NULL,
                                            `adjoining_room_id` bigint(20) unsigned NOT NULL,
                                            `created_at` timestamp NULL DEFAULT NULL,
                                            `updated_at` timestamp NULL DEFAULT NULL,
                                            PRIMARY KEY (`id`),
                                            KEY `adjoining_room_main_room_adjoining_room_id_foreign` (`adjoining_room_id`),
                                            KEY `adjoining_room_main_room_main_room_id_foreign` (`main_room_id`),
                                            CONSTRAINT `adjoining_room_main_room_adjoining_room_id_foreign` FOREIGN KEY (`adjoining_room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
                                            CONSTRAINT `adjoining_room_main_room_main_room_id_foreign` FOREIGN KEY (`main_room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `adjoining_room_main_room`
--

LOCK TABLES `adjoining_room_main_room` WRITE;
/*!40000 ALTER TABLE `adjoining_room_main_room` DISABLE KEYS */;
/*!40000 ALTER TABLE `adjoining_room_main_room` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `api_access_tokens`
--

DROP TABLE IF EXISTS `api_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_access_tokens` (
                                     `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                     `passport_token_id` text NOT NULL,
                                     `access_token` text NOT NULL,
                                     `created_at` timestamp NULL DEFAULT NULL,
                                     `updated_at` timestamp NULL DEFAULT NULL,
                                     PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_access_tokens`
--

LOCK TABLES `api_access_tokens` WRITE;
/*!40000 ALTER TABLE `api_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `api_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `api_log`
--

DROP TABLE IF EXISTS `api_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_log` (
                           `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                           `token_id` bigint(20) unsigned NOT NULL,
                           `api_key` text NOT NULL,
                           `url` varchar(255) NOT NULL,
                           `method` varchar(255) NOT NULL,
                           `ip` varchar(255) NOT NULL,
                           `payload` longtext DEFAULT NULL,
                           `user_agent` varchar(255) NOT NULL,
                           `created_at` timestamp NULL DEFAULT NULL,
                           `updated_at` timestamp NULL DEFAULT NULL,
                           PRIMARY KEY (`id`),
                           KEY `api_log_token_id_index` (`token_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_log`
--

LOCK TABLES `api_log` WRITE;
/*!40000 ALTER TABLE `api_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `api_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `area_filter`
--

DROP TABLE IF EXISTS `area_filter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `area_filter` (
                               `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                               `area_id` int(11) NOT NULL,
                               `filter_id` int(11) NOT NULL,
                               `created_at` timestamp NULL DEFAULT NULL,
                               `updated_at` timestamp NULL DEFAULT NULL,
                               PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `area_filter`
--

LOCK TABLES `area_filter` WRITE;
/*!40000 ALTER TABLE `area_filter` DISABLE KEYS */;
/*!40000 ALTER TABLE `area_filter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `areas`
--

DROP TABLE IF EXISTS `areas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `areas` (
                         `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                         `name` varchar(255) NOT NULL,
                         `created_at` timestamp NULL DEFAULT NULL,
                         `updated_at` timestamp NULL DEFAULT NULL,
                         `deleted_at` timestamp NULL DEFAULT NULL,
                         PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `areas`
--

LOCK TABLES `areas` WRITE;
/*!40000 ALTER TABLE `areas` DISABLE KEYS */;
/*!40000 ALTER TABLE `areas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `artist_residencies`
--

DROP TABLE IF EXISTS `artist_residencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `artist_residencies` (
                                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                      `artist_id` bigint(20) unsigned DEFAULT NULL,
                                      `artist_crm_contact_id` bigint(20) unsigned DEFAULT NULL,
                                      `accommodation_id` bigint(20) unsigned DEFAULT NULL,
                                      `accommodation_crm_contact_id` bigint(20) unsigned DEFAULT NULL,
                                      `project_id` bigint(20) unsigned DEFAULT NULL,
                                      `arrival_date` date DEFAULT NULL,
                                      `arrival_time` time DEFAULT NULL,
                                      `departure_date` date DEFAULT NULL,
                                      `departure_time` time DEFAULT NULL,
                                      `days` int(11) DEFAULT NULL,
                                      `type_of_room` varchar(255) DEFAULT NULL,
                                      `cost_per_night` double(8,2) DEFAULT NULL,
                                      `daily_allowance` double(8,2) DEFAULT NULL,
                                      `additional_daily_allowance` double(8,2) DEFAULT NULL,
                                      `breakfast_count` int(11) NOT NULL DEFAULT 0,
                                      `breakfast_deduction_per_day` double NOT NULL DEFAULT 5.6,
                                      `description` text DEFAULT NULL,
                                      `do_not_save_artist` tinyint(1) NOT NULL DEFAULT 0,
                                      `name` varchar(255) DEFAULT NULL,
                                      `first_name` varchar(255) DEFAULT NULL,
                                      `last_name` varchar(255) DEFAULT NULL,
                                      `phone_number` varchar(255) DEFAULT NULL,
                                      `position` varchar(255) DEFAULT NULL,
                                      `crm_property_overrides` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`crm_property_overrides`)),
                                      `created_at` timestamp NULL DEFAULT NULL,
                                      `updated_at` timestamp NULL DEFAULT NULL,
                                      PRIMARY KEY (`id`),
                                      KEY `artist_residencies_project_id_foreign` (`project_id`),
                                      KEY `artist_residencies_accommodation_id_foreign` (`accommodation_id`),
                                      KEY `fk_artist_residencies_artist` (`artist_id`),
                                      KEY `artist_residencies_artist_crm_contact_id_foreign` (`artist_crm_contact_id`),
                                      KEY `artist_residencies_accommodation_crm_contact_id_foreign` (`accommodation_crm_contact_id`),
                                      CONSTRAINT `artist_residencies_accommodation_crm_contact_id_foreign` FOREIGN KEY (`accommodation_crm_contact_id`) REFERENCES `crm_contacts` (`id`) ON DELETE SET NULL,
                                      CONSTRAINT `artist_residencies_accommodation_id_foreign` FOREIGN KEY (`accommodation_id`) REFERENCES `accommodations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
                                      CONSTRAINT `artist_residencies_artist_crm_contact_id_foreign` FOREIGN KEY (`artist_crm_contact_id`) REFERENCES `crm_contacts` (`id`) ON DELETE SET NULL,
                                      CONSTRAINT `artist_residencies_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
                                      CONSTRAINT `fk_artist_residencies_artist` FOREIGN KEY (`artist_id`) REFERENCES `artists` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `artist_residencies`
--

LOCK TABLES `artist_residencies` WRITE;
/*!40000 ALTER TABLE `artist_residencies` DISABLE KEYS */;
/*!40000 ALTER TABLE `artist_residencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `artists`
--

DROP TABLE IF EXISTS `artists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `artists` (
                           `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                           `name` varchar(255) NOT NULL,
                           `first_name` varchar(255) DEFAULT NULL,
                           `last_name` varchar(255) DEFAULT NULL,
                           `phone_number` varchar(255) DEFAULT NULL,
                           `position` varchar(255) DEFAULT NULL,
                           `created_at` timestamp NULL DEFAULT NULL,
                           `updated_at` timestamp NULL DEFAULT NULL,
                           `crm_contact_id` bigint(20) unsigned DEFAULT NULL,
                           PRIMARY KEY (`id`),
                           KEY `artists_crm_contact_id_foreign` (`crm_contact_id`),
                           CONSTRAINT `artists_crm_contact_id_foreign` FOREIGN KEY (`crm_contact_id`) REFERENCES `crm_contacts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `artists`
--

LOCK TABLES `artists` WRITE;
/*!40000 ALTER TABLE `artists` DISABLE KEYS */;
/*!40000 ALTER TABLE `artists` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `availabilities`
--

DROP TABLE IF EXISTS `availabilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `availabilities` (
                                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                  `available_type` varchar(255) NOT NULL,
                                  `available_id` bigint(20) unsigned NOT NULL,
                                  `start_time` time DEFAULT NULL,
                                  `end_time` time DEFAULT NULL,
                                  `date` date NOT NULL,
                                  `full_day` tinyint(1) NOT NULL DEFAULT 0,
                                  `comment` varchar(20) DEFAULT NULL,
                                  `is_series` tinyint(1) NOT NULL DEFAULT 0,
                                  `series_id` bigint(20) unsigned DEFAULT NULL,
                                  `created_at` timestamp NULL DEFAULT NULL,
                                  `updated_at` timestamp NULL DEFAULT NULL,
                                  PRIMARY KEY (`id`),
                                  KEY `availabilities_available_type_available_id_index` (`available_type`,`available_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `availabilities`
--

LOCK TABLES `availabilities` WRITE;
/*!40000 ALTER TABLE `availabilities` DISABLE KEYS */;
/*!40000 ALTER TABLE `availabilities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `availabilities_conflicts`
--

DROP TABLE IF EXISTS `availabilities_conflicts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `availabilities_conflicts` (
                                            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                            `availability_id` bigint(20) unsigned NOT NULL,
                                            `shift_id` bigint(20) unsigned NOT NULL,
                                            `user_name` varchar(255) NOT NULL,
                                            `date` date NOT NULL,
                                            `start_time` time NOT NULL,
                                            `end_time` time NOT NULL,
                                            `created_at` timestamp NULL DEFAULT NULL,
                                            `updated_at` timestamp NULL DEFAULT NULL,
                                            PRIMARY KEY (`id`),
                                            KEY `availabilities_conflicts_availability_id_foreign` (`availability_id`),
                                            KEY `availabilities_conflicts_shift_id_foreign` (`shift_id`),
                                            CONSTRAINT `availabilities_conflicts_availability_id_foreign` FOREIGN KEY (`availability_id`) REFERENCES `availabilities` (`id`) ON DELETE CASCADE,
                                            CONSTRAINT `availabilities_conflicts_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `availabilities_conflicts`
--

LOCK TABLES `availabilities_conflicts` WRITE;
/*!40000 ALTER TABLE `availabilities_conflicts` DISABLE KEYS */;
/*!40000 ALTER TABLE `availabilities_conflicts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `availability_series`
--

DROP TABLE IF EXISTS `availability_series`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `availability_series` (
                                       `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                       `frequency` varchar(20) NOT NULL,
                                       `end_date` date NOT NULL,
                                       `created_at` timestamp NULL DEFAULT NULL,
                                       `updated_at` timestamp NULL DEFAULT NULL,
                                       PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `availability_series`
--

LOCK TABLES `availability_series` WRITE;
/*!40000 ALTER TABLE `availability_series` DISABLE KEYS */;
/*!40000 ALTER TABLE `availability_series` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `budget_column_settings`
--

DROP TABLE IF EXISTS `budget_column_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `budget_column_settings` (
                                          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                          `column_position` smallint(5) unsigned NOT NULL,
                                          `column_name` text NOT NULL,
                                          `created_at` timestamp NULL DEFAULT NULL,
                                          `updated_at` timestamp NULL DEFAULT NULL,
                                          PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `budget_column_settings`
--

LOCK TABLES `budget_column_settings` WRITE;
/*!40000 ALTER TABLE `budget_column_settings` DISABLE KEYS */;
INSERT INTO `budget_column_settings` VALUES
                                         (1,0,'KTO','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                         (2,1,'KST','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                         (3,2,'Position','2026-04-21 06:09:24','2026-04-21 06:09:24');
/*!40000 ALTER TABLE `budget_column_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `budget_management_accounts`
--

DROP TABLE IF EXISTS `budget_management_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `budget_management_accounts` (
                                              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                              `account_number` varchar(255) NOT NULL,
                                              `title` varchar(255) NOT NULL,
                                              `is_account_for_revenue` tinyint(1) NOT NULL DEFAULT 0,
                                              `created_at` timestamp NULL DEFAULT NULL,
                                              `updated_at` timestamp NULL DEFAULT NULL,
                                              `deleted_at` timestamp NULL DEFAULT NULL,
                                              PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `budget_management_accounts`
--

LOCK TABLES `budget_management_accounts` WRITE;
/*!40000 ALTER TABLE `budget_management_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `budget_management_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `budget_management_cost_units`
--

DROP TABLE IF EXISTS `budget_management_cost_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `budget_management_cost_units` (
                                                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                                `cost_unit_number` varchar(255) NOT NULL,
                                                `title` varchar(255) NOT NULL,
                                                `created_at` timestamp NULL DEFAULT NULL,
                                                `updated_at` timestamp NULL DEFAULT NULL,
                                                `deleted_at` timestamp NULL DEFAULT NULL,
                                                PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `budget_management_cost_units`
--

LOCK TABLES `budget_management_cost_units` WRITE;
/*!40000 ALTER TABLE `budget_management_cost_units` DISABLE KEYS */;
/*!40000 ALTER TABLE `budget_management_cost_units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `budget_sum_details`
--

DROP TABLE IF EXISTS `budget_sum_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `budget_sum_details` (
                                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                      `type` varchar(255) NOT NULL,
                                      `column_id` bigint(20) unsigned NOT NULL,
                                      `created_at` timestamp NULL DEFAULT NULL,
                                      `updated_at` timestamp NULL DEFAULT NULL,
                                      `deleted_at` timestamp NULL DEFAULT NULL,
                                      PRIMARY KEY (`id`),
                                      KEY `budget_sum_details_column_id_index` (`column_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `budget_sum_details`
--

LOCK TABLES `budget_sum_details` WRITE;
/*!40000 ALTER TABLE `budget_sum_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `budget_sum_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
                              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                              `name` varchar(255) NOT NULL,
                              `color` varchar(255) DEFAULT NULL,
                              `created_at` timestamp NULL DEFAULT NULL,
                              `updated_at` timestamp NULL DEFAULT NULL,
                              `deleted_at` timestamp NULL DEFAULT NULL,
                              PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `category_project`
--

DROP TABLE IF EXISTS `category_project`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `category_project` (
                                    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                    `project_id` bigint(20) unsigned NOT NULL,
                                    `category_id` bigint(20) unsigned NOT NULL,
                                    `is_main` tinyint(1) NOT NULL DEFAULT 0,
                                    `created_at` timestamp NULL DEFAULT NULL,
                                    `updated_at` timestamp NULL DEFAULT NULL,
                                    PRIMARY KEY (`id`),
                                    KEY `category_project_project_id_foreign` (`project_id`),
                                    KEY `category_project_category_id_foreign` (`category_id`),
                                    CONSTRAINT `category_project_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
                                    CONSTRAINT `category_project_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `category_project`
--

LOCK TABLES `category_project` WRITE;
/*!40000 ALTER TABLE `category_project` DISABLE KEYS */;
/*!40000 ALTER TABLE `category_project` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cell_calculations`
--

DROP TABLE IF EXISTS `cell_calculations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cell_calculations` (
                                     `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                     `cell_id` bigint(20) unsigned NOT NULL,
                                     `name` varchar(255) DEFAULT NULL,
                                     `value` bigint(20) DEFAULT NULL,
                                     `description` varchar(255) DEFAULT NULL,
                                     `position` int(11) NOT NULL DEFAULT 0,
                                     `created_at` timestamp NULL DEFAULT NULL,
                                     `updated_at` timestamp NULL DEFAULT NULL,
                                     `deleted_at` timestamp NULL DEFAULT NULL,
                                     PRIMARY KEY (`id`),
                                     KEY `cell_calculations_cell_id_foreign` (`cell_id`),
                                     CONSTRAINT `cell_calculations_cell_id_foreign` FOREIGN KEY (`cell_id`) REFERENCES `column_sub_position_row` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cell_calculations`
--

LOCK TABLES `cell_calculations` WRITE;
/*!40000 ALTER TABLE `cell_calculations` DISABLE KEYS */;
/*!40000 ALTER TABLE `cell_calculations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cell_comments`
--

DROP TABLE IF EXISTS `cell_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cell_comments` (
                                 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                 `column_cell_id` bigint(20) NOT NULL,
                                 `user_id` bigint(20) unsigned NOT NULL,
                                 `description` text NOT NULL,
                                 `created_at` timestamp NULL DEFAULT NULL,
                                 `updated_at` timestamp NULL DEFAULT NULL,
                                 `deleted_at` timestamp NULL DEFAULT NULL,
                                 PRIMARY KEY (`id`),
                                 KEY `cell_comments_column_cell_id_index` (`column_cell_id`),
                                 KEY `cell_comments_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cell_comments`
--

LOCK TABLES `cell_comments` WRITE;
/*!40000 ALTER TABLE `cell_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `cell_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat_message_reads`
--

DROP TABLE IF EXISTS `chat_message_reads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_message_reads` (
                                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                      `message_id` bigint(20) unsigned NOT NULL,
                                      `user_id` bigint(20) unsigned NOT NULL,
                                      `read_at` timestamp NOT NULL,
                                      `created_at` timestamp NULL DEFAULT NULL,
                                      `updated_at` timestamp NULL DEFAULT NULL,
                                      PRIMARY KEY (`id`),
                                      KEY `chat_message_reads_message_id_foreign` (`message_id`),
                                      KEY `chat_message_reads_user_id_foreign` (`user_id`),
                                      CONSTRAINT `chat_message_reads_message_id_foreign` FOREIGN KEY (`message_id`) REFERENCES `chat_messages` (`id`) ON DELETE CASCADE,
                                      CONSTRAINT `chat_message_reads_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_message_reads`
--

LOCK TABLES `chat_message_reads` WRITE;
/*!40000 ALTER TABLE `chat_message_reads` DISABLE KEYS */;
/*!40000 ALTER TABLE `chat_message_reads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat_messages`
--

DROP TABLE IF EXISTS `chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_messages` (
                                 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                 `chat_id` bigint(20) unsigned NOT NULL,
                                 `type` enum('text','audio','video') NOT NULL DEFAULT 'text',
                                 `sender_id` bigint(20) unsigned NOT NULL,
                                 `created_at` timestamp NULL DEFAULT NULL,
                                 `updated_at` timestamp NULL DEFAULT NULL,
                                 `message` longtext NOT NULL,
                                 PRIMARY KEY (`id`),
                                 KEY `chat_messages_chat_id_foreign` (`chat_id`),
                                 KEY `chat_messages_sender_id_foreign` (`sender_id`),
                                 CONSTRAINT `chat_messages_chat_id_foreign` FOREIGN KEY (`chat_id`) REFERENCES `chats` (`id`) ON DELETE CASCADE,
                                 CONSTRAINT `chat_messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_messages`
--

LOCK TABLES `chat_messages` WRITE;
/*!40000 ALTER TABLE `chat_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `chat_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat_users`
--

DROP TABLE IF EXISTS `chat_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_users` (
                              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                              `chat_id` bigint(20) unsigned NOT NULL,
                              `user_id` bigint(20) unsigned NOT NULL,
                              `created_at` timestamp NULL DEFAULT NULL,
                              `updated_at` timestamp NULL DEFAULT NULL,
                              PRIMARY KEY (`id`),
                              KEY `chat_users_chat_id_foreign` (`chat_id`),
                              KEY `chat_users_user_id_foreign` (`user_id`),
                              CONSTRAINT `chat_users_chat_id_foreign` FOREIGN KEY (`chat_id`) REFERENCES `chats` (`id`) ON DELETE CASCADE,
                              CONSTRAINT `chat_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_users`
--

LOCK TABLES `chat_users` WRITE;
/*!40000 ALTER TABLE `chat_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `chat_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chats`
--

DROP TABLE IF EXISTS `chats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chats` (
                         `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                         `name` varchar(255) DEFAULT NULL,
                         `is_group` tinyint(1) NOT NULL DEFAULT 0,
                         `is_archived` tinyint(1) NOT NULL DEFAULT 0,
                         `is_favorite` tinyint(1) NOT NULL DEFAULT 0,
                         `is_muted` tinyint(1) NOT NULL DEFAULT 0,
                         `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
                         `created_by` bigint(20) unsigned NOT NULL,
                         `created_at` timestamp NULL DEFAULT NULL,
                         `updated_at` timestamp NULL DEFAULT NULL,
                         PRIMARY KEY (`id`),
                         KEY `chats_created_by_foreign` (`created_by`),
                         CONSTRAINT `chats_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chats`
--

LOCK TABLES `chats` WRITE;
/*!40000 ALTER TABLE `chats` DISABLE KEYS */;
/*!40000 ALTER TABLE `chats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `checklist_template_user`
--

DROP TABLE IF EXISTS `checklist_template_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `checklist_template_user` (
                                           `checklist_template_id` bigint(20) unsigned NOT NULL,
                                           `user_id` bigint(20) unsigned NOT NULL,
                                           `created_at` timestamp NULL DEFAULT NULL,
                                           `updated_at` timestamp NULL DEFAULT NULL,
                                           KEY `checklist_template_user_checklist_template_id_foreign` (`checklist_template_id`),
                                           KEY `checklist_template_user_user_id_foreign` (`user_id`),
                                           CONSTRAINT `checklist_template_user_checklist_template_id_foreign` FOREIGN KEY (`checklist_template_id`) REFERENCES `checklist_templates` (`id`) ON DELETE CASCADE,
                                           CONSTRAINT `checklist_template_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `checklist_template_user`
--

LOCK TABLES `checklist_template_user` WRITE;
/*!40000 ALTER TABLE `checklist_template_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `checklist_template_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `checklist_templates`
--

DROP TABLE IF EXISTS `checklist_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `checklist_templates` (
                                       `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                       `name` varchar(255) NOT NULL,
                                       `user_id` bigint(20) unsigned DEFAULT NULL,
                                       `created_at` timestamp NULL DEFAULT NULL,
                                       `updated_at` timestamp NULL DEFAULT NULL,
                                       PRIMARY KEY (`id`),
                                       KEY `checklist_templates_user_id_foreign` (`user_id`),
                                       CONSTRAINT `checklist_templates_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `checklist_templates`
--

LOCK TABLES `checklist_templates` WRITE;
/*!40000 ALTER TABLE `checklist_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `checklist_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `checklist_user`
--

DROP TABLE IF EXISTS `checklist_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `checklist_user` (
                                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                  `checklist_id` bigint(20) unsigned NOT NULL,
                                  `user_id` bigint(20) unsigned NOT NULL,
                                  `created_at` timestamp NULL DEFAULT NULL,
                                  `updated_at` timestamp NULL DEFAULT NULL,
                                  PRIMARY KEY (`id`),
                                  KEY `checklist_user_checklist_id_foreign` (`checklist_id`),
                                  KEY `checklist_user_user_id_foreign` (`user_id`),
                                  CONSTRAINT `checklist_user_checklist_id_foreign` FOREIGN KEY (`checklist_id`) REFERENCES `checklists` (`id`) ON DELETE CASCADE,
                                  CONSTRAINT `checklist_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `checklist_user`
--

LOCK TABLES `checklist_user` WRITE;
/*!40000 ALTER TABLE `checklist_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `checklist_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `checklists`
--

DROP TABLE IF EXISTS `checklists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `checklists` (
                              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                              `name` varchar(255) NOT NULL,
                              `project_id` bigint(20) unsigned DEFAULT NULL,
                              `user_id` bigint(20) unsigned DEFAULT NULL,
                              `tab_id` int(11) DEFAULT NULL,
                              `created_at` timestamp NULL DEFAULT NULL,
                              `updated_at` timestamp NULL DEFAULT NULL,
                              `deleted_at` timestamp NULL DEFAULT NULL,
                              `private` tinyint(1) NOT NULL DEFAULT 0,
                              PRIMARY KEY (`id`),
                              KEY `checklists_user_id_foreign` (`user_id`),
                              KEY `checklists_project_id_foreign` (`project_id`),
                              CONSTRAINT `checklists_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
                              CONSTRAINT `checklists_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `checklists`
--

LOCK TABLES `checklists` WRITE;
/*!40000 ALTER TABLE `checklists` DISABLE KEYS */;
/*!40000 ALTER TABLE `checklists` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `collecting_societies`
--

DROP TABLE IF EXISTS `collecting_societies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `collecting_societies` (
                                        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                        `name` varchar(255) NOT NULL,
                                        `color` varchar(255) DEFAULT NULL,
                                        `created_at` timestamp NULL DEFAULT NULL,
                                        `updated_at` timestamp NULL DEFAULT NULL,
                                        `deleted_at` timestamp NULL DEFAULT NULL,
                                        PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `collecting_societies`
--

LOCK TABLES `collecting_societies` WRITE;
/*!40000 ALTER TABLE `collecting_societies` DISABLE KEYS */;
/*!40000 ALTER TABLE `collecting_societies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `column_sub_position_row`
--

DROP TABLE IF EXISTS `column_sub_position_row`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `column_sub_position_row` (
                                           `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                           `column_id` bigint(20) unsigned NOT NULL,
                                           `sub_position_row_id` bigint(20) unsigned NOT NULL,
                                           `value` varchar(255) DEFAULT NULL,
                                           `commented` tinyint(1) NOT NULL DEFAULT 0,
                                           `linked_money_source_id` bigint(20) unsigned DEFAULT NULL,
                                           `linked_type` varchar(255) DEFAULT NULL,
                                           `verified_value` varchar(255) DEFAULT NULL,
                                           `created_at` timestamp NULL DEFAULT NULL,
                                           `updated_at` timestamp NULL DEFAULT NULL,
                                           `deleted_at` timestamp NULL DEFAULT NULL,
                                           PRIMARY KEY (`id`),
                                           KEY `column_sub_position_row_column_id_foreign` (`column_id`),
                                           KEY `column_sub_position_row_sub_position_row_id_foreign` (`sub_position_row_id`),
                                           KEY `column_sub_position_row_linked_money_source_id_foreign` (`linked_money_source_id`),
                                           CONSTRAINT `column_sub_position_row_column_id_foreign` FOREIGN KEY (`column_id`) REFERENCES `columns` (`id`) ON DELETE CASCADE,
                                           CONSTRAINT `column_sub_position_row_linked_money_source_id_foreign` FOREIGN KEY (`linked_money_source_id`) REFERENCES `money_sources` (`id`) ON DELETE CASCADE,
                                           CONSTRAINT `column_sub_position_row_sub_position_row_id_foreign` FOREIGN KEY (`sub_position_row_id`) REFERENCES `sub_position_rows` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `column_sub_position_row`
--

LOCK TABLES `column_sub_position_row` WRITE;
/*!40000 ALTER TABLE `column_sub_position_row` DISABLE KEYS */;
/*!40000 ALTER TABLE `column_sub_position_row` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `columns`
--

DROP TABLE IF EXISTS `columns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `columns` (
                           `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                           `table_id` bigint(20) unsigned NOT NULL,
                           `name` varchar(255) DEFAULT NULL,
                           `subName` varchar(255) DEFAULT NULL,
                           `type` varchar(255) DEFAULT NULL,
                           `position` tinyint(3) unsigned NOT NULL,
                           `linked_first_column` int(11) DEFAULT NULL,
                           `linked_second_column` int(11) DEFAULT NULL,
                           `color` varchar(255) NOT NULL DEFAULT 'whiteColumn',
                           `locked_by` bigint(20) unsigned DEFAULT NULL,
                           `created_at` timestamp NULL DEFAULT NULL,
                           `updated_at` timestamp NULL DEFAULT NULL,
                           `is_locked` tinyint(1) NOT NULL DEFAULT 0,
                           `commented` tinyint(1) NOT NULL DEFAULT 0,
                           `deleted_at` timestamp NULL DEFAULT NULL,
                           `relevant_for_project_groups` tinyint(1) NOT NULL DEFAULT 0,
                           PRIMARY KEY (`id`),
                           KEY `columns_table_id_foreign` (`table_id`),
                           KEY `columns_locked_by_foreign` (`locked_by`),
                           KEY `columns_linked_first_column_index` (`linked_first_column`),
                           KEY `columns_linked_second_column_index` (`linked_second_column`),
                           CONSTRAINT `columns_locked_by_foreign` FOREIGN KEY (`locked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
                           CONSTRAINT `columns_table_id_foreign` FOREIGN KEY (`table_id`) REFERENCES `tables` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `columns`
--

LOCK TABLES `columns` WRITE;
/*!40000 ALTER TABLE `columns` DISABLE KEYS */;
/*!40000 ALTER TABLE `columns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `comments` (
                            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                            `text` text NOT NULL,
                            `project_id` bigint(20) unsigned DEFAULT NULL,
                            `project_file_id` bigint(20) unsigned DEFAULT NULL,
                            `money_source_file_id` bigint(20) unsigned DEFAULT NULL,
                            `contract_id` bigint(20) unsigned DEFAULT NULL,
                            `user_id` bigint(20) unsigned DEFAULT NULL,
                            `tab_id` int(11) DEFAULT NULL,
                            `created_at` timestamp NULL DEFAULT NULL,
                            `updated_at` timestamp NULL DEFAULT NULL,
                            `deleted_at` timestamp NULL DEFAULT NULL,
                            PRIMARY KEY (`id`),
                            KEY `comments_project_id_foreign` (`project_id`),
                            KEY `comments_user_id_foreign` (`user_id`),
                            KEY `comments_project_file_id_foreign` (`project_file_id`),
                            KEY `comments_contract_id_index` (`contract_id`),
                            KEY `comments_money_source_file_id_index` (`money_source_file_id`),
                            CONSTRAINT `comments_project_file_id_foreign` FOREIGN KEY (`project_file_id`) REFERENCES `contracts` (`id`) ON DELETE SET NULL,
                            CONSTRAINT `comments_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
                            CONSTRAINT `comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comments`
--

LOCK TABLES `comments` WRITE;
/*!40000 ALTER TABLE `comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `committed_shift_changes`
--

DROP TABLE IF EXISTS `committed_shift_changes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `committed_shift_changes` (
                                           `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                           `craft_id` bigint(20) unsigned DEFAULT NULL,
                                           `shift_id` bigint(20) unsigned DEFAULT NULL,
                                           `subject_type` varchar(255) NOT NULL,
                                           `subject_id` bigint(20) unsigned NOT NULL,
                                           `change_type` varchar(50) NOT NULL,
                                           `field_changes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`field_changes`)),
                                           `affected_user_type` varchar(255) DEFAULT NULL,
                                           `affected_user_id` bigint(20) unsigned DEFAULT NULL,
                                           `changed_by_user_id` bigint(20) unsigned DEFAULT NULL,
                                           `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
                                           `acknowledged_at` timestamp NULL DEFAULT NULL,
                                           `acknowledged_by_user_id` bigint(20) unsigned DEFAULT NULL,
                                           `created_at` timestamp NULL DEFAULT NULL,
                                           `updated_at` timestamp NULL DEFAULT NULL,
                                           PRIMARY KEY (`id`),
                                           KEY `committed_shift_changes_shift_id_foreign` (`shift_id`),
                                           KEY `committed_shift_changes_changed_by_user_id_foreign` (`changed_by_user_id`),
                                           KEY `committed_shift_changes_acknowledged_by_user_id_foreign` (`acknowledged_by_user_id`),
                                           KEY `committed_shift_changes_craft_id_shift_id_index` (`craft_id`,`shift_id`),
                                           KEY `committed_shift_changes_subject_type_subject_id_index` (`subject_type`,`subject_id`),
                                           KEY `committed_shift_changes_changed_at_index` (`changed_at`),
                                           KEY `committed_shift_changes_acknowledged_at_index` (`acknowledged_at`),
                                           KEY `committed_shift_changes_aff_user_idx` (`affected_user_type`,`affected_user_id`),
                                           CONSTRAINT `committed_shift_changes_acknowledged_by_user_id_foreign` FOREIGN KEY (`acknowledged_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
                                           CONSTRAINT `committed_shift_changes_changed_by_user_id_foreign` FOREIGN KEY (`changed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
                                           CONSTRAINT `committed_shift_changes_craft_id_foreign` FOREIGN KEY (`craft_id`) REFERENCES `crafts` (`id`) ON DELETE SET NULL,
                                           CONSTRAINT `committed_shift_changes_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `committed_shift_changes`
--

LOCK TABLES `committed_shift_changes` WRITE;
/*!40000 ALTER TABLE `committed_shift_changes` DISABLE KEYS */;
/*!40000 ALTER TABLE `committed_shift_changes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `company_types`
--

DROP TABLE IF EXISTS `company_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_types` (
                                 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                 `name` varchar(255) NOT NULL,
                                 `color` varchar(255) DEFAULT NULL,
                                 `created_at` timestamp NULL DEFAULT NULL,
                                 `updated_at` timestamp NULL DEFAULT NULL,
                                 `deleted_at` timestamp NULL DEFAULT NULL,
                                 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company_types`
--

LOCK TABLES `company_types` WRITE;
/*!40000 ALTER TABLE `company_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `company_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compensation_day_offs`
--

DROP TABLE IF EXISTS `compensation_day_offs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `compensation_day_offs` (
                                         `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                         `user_id` bigint(20) unsigned NOT NULL,
                                         `violation_id` bigint(20) unsigned DEFAULT NULL,
                                         `value` decimal(2,1) NOT NULL,
                                         `deadline` date NOT NULL,
                                         `granted_date` date DEFAULT NULL,
                                         `granted_by` bigint(20) unsigned DEFAULT NULL,
                                         `granted_at` datetime DEFAULT NULL,
                                         `reason` text DEFAULT NULL,
                                         `for_holiday` tinyint(1) NOT NULL DEFAULT 0,
                                         `created_at` timestamp NULL DEFAULT NULL,
                                         `updated_at` timestamp NULL DEFAULT NULL,
                                         PRIMARY KEY (`id`),
                                         KEY `compensation_day_offs_granted_by_foreign` (`granted_by`),
                                         KEY `compensation_day_offs_user_id_granted_date_index` (`user_id`,`granted_date`),
                                         KEY `compensation_day_offs_user_id_granted_at_index` (`user_id`,`granted_at`),
                                         KEY `compensation_day_offs_deadline_index` (`deadline`),
                                         KEY `compensation_day_offs_violation_id_foreign` (`violation_id`),
                                         CONSTRAINT `compensation_day_offs_granted_by_foreign` FOREIGN KEY (`granted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
                                         CONSTRAINT `compensation_day_offs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
                                         CONSTRAINT `compensation_day_offs_violation_id_foreign` FOREIGN KEY (`violation_id`) REFERENCES `shift_rule_violations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compensation_day_offs`
--

LOCK TABLES `compensation_day_offs` WRITE;
/*!40000 ALTER TABLE `compensation_day_offs` DISABLE KEYS */;
/*!40000 ALTER TABLE `compensation_day_offs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `component_department`
--

DROP TABLE IF EXISTS `component_department`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `component_department` (
                                        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                        `department_id` bigint(20) unsigned NOT NULL,
                                        `component_id` bigint(20) unsigned NOT NULL,
                                        `can_write` tinyint(1) DEFAULT NULL,
                                        `created_at` timestamp NULL DEFAULT NULL,
                                        `updated_at` timestamp NULL DEFAULT NULL,
                                        PRIMARY KEY (`id`),
                                        KEY `component_department_department_id_foreign` (`department_id`),
                                        KEY `component_department_component_id_foreign` (`component_id`),
                                        CONSTRAINT `component_department_component_id_foreign` FOREIGN KEY (`component_id`) REFERENCES `components` (`id`) ON DELETE CASCADE,
                                        CONSTRAINT `component_department_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `component_department`
--

LOCK TABLES `component_department` WRITE;
/*!40000 ALTER TABLE `component_department` DISABLE KEYS */;
/*!40000 ALTER TABLE `component_department` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `component_in_tabs`
--

DROP TABLE IF EXISTS `component_in_tabs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `component_in_tabs` (
                                     `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                     `project_tab_id` bigint(20) unsigned NOT NULL,
                                     `component_id` bigint(20) unsigned NOT NULL,
                                     `order` int(11) NOT NULL,
                                     `scope` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`scope`)),
                                     `note` text DEFAULT NULL,
                                     `created_at` timestamp NULL DEFAULT NULL,
                                     `updated_at` timestamp NULL DEFAULT NULL,
                                     PRIMARY KEY (`id`),
                                     KEY `component_in_tabs_project_tab_id_foreign` (`project_tab_id`),
                                     KEY `component_in_tabs_component_id_foreign` (`component_id`),
                                     CONSTRAINT `component_in_tabs_component_id_foreign` FOREIGN KEY (`component_id`) REFERENCES `components` (`id`) ON DELETE CASCADE,
                                     CONSTRAINT `component_in_tabs_project_tab_id_foreign` FOREIGN KEY (`project_tab_id`) REFERENCES `project_tabs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `component_in_tabs`
--

LOCK TABLES `component_in_tabs` WRITE;
/*!40000 ALTER TABLE `component_in_tabs` DISABLE KEYS */;
INSERT INTO `component_in_tabs` VALUES
                                    (1,1,26,1,'[]',NULL,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                    (2,1,27,2,'[]',NULL,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                    (3,1,28,3,'[]',NULL,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                    (4,1,29,4,'[]',NULL,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                    (5,1,30,5,'[]',NULL,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                    (6,1,31,6,'[]',NULL,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                    (7,1,16,7,'[1]',NULL,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                    (8,2,24,1,'[]',NULL,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                    (9,3,6,1,'[3]',NULL,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                    (10,4,8,1,'[]',NULL,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                    (11,5,12,1,'[]',NULL,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                    (12,6,14,1,'[6]',NULL,'2026-04-21 06:09:24','2026-04-21 06:09:24');
/*!40000 ALTER TABLE `component_in_tabs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `component_user`
--

DROP TABLE IF EXISTS `component_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `component_user` (
                                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                  `user_id` bigint(20) unsigned NOT NULL,
                                  `component_id` bigint(20) unsigned NOT NULL,
                                  `can_write` tinyint(1) DEFAULT NULL,
                                  `created_at` timestamp NULL DEFAULT NULL,
                                  `updated_at` timestamp NULL DEFAULT NULL,
                                  PRIMARY KEY (`id`),
                                  KEY `component_user_user_id_foreign` (`user_id`),
                                  KEY `component_user_component_id_foreign` (`component_id`),
                                  CONSTRAINT `component_user_component_id_foreign` FOREIGN KEY (`component_id`) REFERENCES `components` (`id`) ON DELETE CASCADE,
                                  CONSTRAINT `component_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `component_user`
--

LOCK TABLES `component_user` WRITE;
/*!40000 ALTER TABLE `component_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `component_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `components`
--

DROP TABLE IF EXISTS `components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `components` (
                              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                              `name` varchar(255) NOT NULL,
                              `type` varchar(255) DEFAULT NULL,
                              `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
                              `special` tinyint(1) NOT NULL DEFAULT 0,
                              `sidebar_enabled` tinyint(1) NOT NULL DEFAULT 1,
                              `permission_type` enum('allSeeAndEdit','allSeeSomeEdit','someSeeSomeEdit') DEFAULT NULL,
                              `created_at` timestamp NULL DEFAULT NULL,
                              `updated_at` timestamp NULL DEFAULT NULL,
                              PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `components`
--

LOCK TABLES `components` WRITE;
/*!40000 ALTER TABLE `components` DISABLE KEYS */;
INSERT INTO `components` VALUES
                             (1,'Project Status','ProjectStateComponent','[]',1,1,'allSeeAndEdit','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                             (2,'Project Group','ProjectGroupComponent','[]',1,1,'allSeeAndEdit','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                             (3,'Project Team','ProjectTeamComponent','[]',1,1,'allSeeAndEdit','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                             (4,'Project Attributes','ProjectAttributesComponent','[]',1,1,'allSeeAndEdit','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                             (5,'Calendar','CalendarTab','[]',1,0,'allSeeAndEdit','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                             (6,'Checklist','ChecklistComponent','[]',1,0,'allSeeAndEdit','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                             (7,'All Checklists','ChecklistAllComponent','[]',1,0,NULL,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                             (8,'Shift Tab','ShiftTab','[]',1,0,'allSeeAndEdit','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                             (9,'Relevant Dates For Shift Planning','RelevantDatesForShiftPlanningComponent','[]',1,1,'allSeeAndEdit','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                             (10,'Shift Contact Persons','ShiftContactPersonsComponent','[]',1,1,'allSeeAndEdit','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                             (11,'General Shift Information','GeneralShiftInformationComponent','[]',1,1,'allSeeAndEdit','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                             (12,'Budget','BudgetTab','[]',1,0,'allSeeAndEdit','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                             (13,'Project Budget Deadline','ProjectBudgetDeadlineComponent','[]',1,1,'allSeeAndEdit','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                             (14,'Comment Tab','CommentTab','[]',1,0,'allSeeAndEdit','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                             (15,'All Comment Tab','CommentAllTab','[]',1,0,'allSeeAndEdit','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                             (16,'Project Documents','ProjectDocumentsComponent','[]',1,0,'allSeeAndEdit','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                             (17,'All Project Documents','ProjectAllDocumentsComponent','[]',1,0,'allSeeAndEdit','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                             (18,'Project Title','ProjectTitleComponent','[]',1,1,'allSeeAndEdit','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                             (19,'Separator 10 Pixel','SeparatorComponent','{\"height\":\"10\",\"showLine\":true}',0,1,'allSeeAndEdit','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                             (20,'Budget Informations','BudgetInformations','[]',1,1,'allSeeAndEdit','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                             (21,'Project group display component','ProjectGroupDisplayComponent','[]',0,1,'allSeeAndEdit','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                             (22,'Component Subprojects','GroupProjectDisplayComponent','[]',1,1,'allSeeAndEdit','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                             (23,'Artist Name Display Component','ArtistNameDisplayComponent','[]',1,1,'allSeeAndEdit','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                             (24,'Bulk Event Create','BulkBody','[]',1,0,'allSeeAndEdit','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                             (25,'Artist residencies','ArtistResidenciesComponent','[]',1,0,'allSeeAndEdit','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                             (26,'Short Description','Title','{\"title\":\"Short Description\",\"title_size\":\"15\"}',0,1,'allSeeAndEdit','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                             (27,'Short Description','TextArea','{\"label\":\"\",\"text\":\"\",\"placeholder\":\"Short Description\"}',0,1,'allSeeAndEdit','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                             (28,'Website-Text','Title','{\"title\":\"Website-Text\",\"title_size\":\"15\"}',0,1,'allSeeAndEdit','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                             (29,'Website-Text','TextArea','{\"label\":\"\",\"text\":\"\",\"placeholder\":\"Website-Text\"}',0,1,'allSeeAndEdit','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                             (30,'ÖA','Title','{\"title\":\"\\u00d6A\",\"title_size\":\"15\"}',0,1,'allSeeAndEdit','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                             (31,'ÖA','TextArea','{\"label\":\"\",\"text\":\"\",\"placeholder\":\"\\u00d6A\"}',0,1,'allSeeAndEdit','2026-04-21 06:09:24','2026-04-21 06:09:24');
/*!40000 ALTER TABLE `components` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contacts`
--

DROP TABLE IF EXISTS `contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contacts` (
                            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                            `contactable_type` varchar(255) NOT NULL,
                            `contactable_id` bigint(20) unsigned NOT NULL,
                            `name` varchar(255) DEFAULT NULL,
                            `street` varchar(255) DEFAULT NULL,
                            `zip_code` varchar(255) DEFAULT NULL,
                            `location` varchar(255) DEFAULT NULL,
                            `email` varchar(255) DEFAULT NULL,
                            `phone` varchar(255) DEFAULT NULL,
                            `mobile` varchar(255) DEFAULT NULL,
                            `fax` varchar(255) DEFAULT NULL,
                            `is_primary` tinyint(1) NOT NULL DEFAULT 0,
                            `created_at` timestamp NULL DEFAULT NULL,
                            `updated_at` timestamp NULL DEFAULT NULL,
                            PRIMARY KEY (`id`),
                            KEY `contacts_contactable_type_contactable_id_index` (`contactable_type`,`contactable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contacts`
--

LOCK TABLES `contacts` WRITE;
/*!40000 ALTER TABLE `contacts` DISABLE KEYS */;
/*!40000 ALTER TABLE `contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contract_department`
--

DROP TABLE IF EXISTS `contract_department`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contract_department` (
                                       `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                       `contract_id` bigint(20) unsigned NOT NULL,
                                       `department_id` bigint(20) unsigned NOT NULL,
                                       `created_at` timestamp NULL DEFAULT NULL,
                                       `updated_at` timestamp NULL DEFAULT NULL,
                                       PRIMARY KEY (`id`),
                                       UNIQUE KEY `contract_department_contract_id_department_id_unique` (`contract_id`,`department_id`),
                                       KEY `contract_department_department_id_foreign` (`department_id`),
                                       CONSTRAINT `contract_department_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE,
                                       CONSTRAINT `contract_department_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contract_department`
--

LOCK TABLES `contract_department` WRITE;
/*!40000 ALTER TABLE `contract_department` DISABLE KEYS */;
/*!40000 ALTER TABLE `contract_department` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contract_modules`
--

DROP TABLE IF EXISTS `contract_modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contract_modules` (
                                    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                    `name` varchar(255) NOT NULL,
                                    `basename` varchar(255) NOT NULL,
                                    `deleted_at` timestamp NULL DEFAULT NULL,
                                    `created_at` timestamp NULL DEFAULT NULL,
                                    `updated_at` timestamp NULL DEFAULT NULL,
                                    PRIMARY KEY (`id`),
                                    UNIQUE KEY `contract_modules_basename_unique` (`basename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contract_modules`
--

LOCK TABLES `contract_modules` WRITE;
/*!40000 ALTER TABLE `contract_modules` DISABLE KEYS */;
/*!40000 ALTER TABLE `contract_modules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contract_types`
--

DROP TABLE IF EXISTS `contract_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contract_types` (
                                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                  `name` varchar(255) NOT NULL,
                                  `color` varchar(255) DEFAULT NULL,
                                  `created_at` timestamp NULL DEFAULT NULL,
                                  `updated_at` timestamp NULL DEFAULT NULL,
                                  `deleted_at` timestamp NULL DEFAULT NULL,
                                  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contract_types`
--

LOCK TABLES `contract_types` WRITE;
/*!40000 ALTER TABLE `contract_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `contract_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contract_user`
--

DROP TABLE IF EXISTS `contract_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contract_user` (
                                 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                 `contract_id` bigint(20) NOT NULL,
                                 `user_id` bigint(20) NOT NULL,
                                 `created_at` timestamp NULL DEFAULT NULL,
                                 `updated_at` timestamp NULL DEFAULT NULL,
                                 `deleted_at` timestamp NULL DEFAULT NULL,
                                 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contract_user`
--

LOCK TABLES `contract_user` WRITE;
/*!40000 ALTER TABLE `contract_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `contract_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contracts`
--

DROP TABLE IF EXISTS `contracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contracts` (
                             `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                             `name` varchar(255) NOT NULL,
                             `basename` varchar(255) NOT NULL,
                             `contract_partner` varchar(255) NOT NULL,
                             `amount` int(11) DEFAULT NULL,
                             `creator_id` bigint(20) unsigned DEFAULT NULL,
                             `project_id` bigint(20) unsigned DEFAULT NULL,
                             `description` varchar(255) DEFAULT NULL,
                             `contract_state` varchar(255) DEFAULT NULL,
                             `contract_state_comment` text DEFAULT NULL,
                             `contract_type_id` bigint(20) unsigned DEFAULT NULL,
                             `company_type_id` bigint(20) unsigned DEFAULT NULL,
                             `currency_id` bigint(20) unsigned DEFAULT NULL,
                             `ksk_liable` tinyint(1) DEFAULT 0,
                             `ksk_amount` decimal(10,2) DEFAULT NULL,
                             `ksk_reason` text DEFAULT NULL,
                             `resident_abroad` tinyint(1) DEFAULT 0,
                             `foreign_tax` tinyint(1) NOT NULL DEFAULT 0,
                             `foreign_tax_amount` decimal(10,2) DEFAULT NULL,
                             `foreign_tax_city` varchar(255) DEFAULT NULL,
                             `foreign_tax_country` varchar(255) DEFAULT NULL,
                             `foreign_tax_reason` text DEFAULT NULL,
                             `reverse_charge_amount` decimal(10,2) DEFAULT NULL,
                             `deadline_date` date DEFAULT NULL,
                             `is_freed` tinyint(1) DEFAULT 0,
                             `has_power_of_attorney` tinyint(1) DEFAULT 0,
                             `created_at` timestamp NULL DEFAULT NULL,
                             `updated_at` timestamp NULL DEFAULT NULL,
                             `deleted_at` timestamp NULL DEFAULT NULL,
                             PRIMARY KEY (`id`),
                             KEY `contracts_creator_id_foreign` (`creator_id`),
                             KEY `contracts_project_id_foreign` (`project_id`),
                             KEY `contracts_currency_id_foreign` (`currency_id`),
                             KEY `contracts_contract_type_id_foreign` (`contract_type_id`),
                             KEY `contracts_company_type_id_foreign` (`company_type_id`),
                             CONSTRAINT `contracts_company_type_id_foreign` FOREIGN KEY (`company_type_id`) REFERENCES `company_types` (`id`),
                             CONSTRAINT `contracts_contract_type_id_foreign` FOREIGN KEY (`contract_type_id`) REFERENCES `contract_types` (`id`),
                             CONSTRAINT `contracts_creator_id_foreign` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
                             CONSTRAINT `contracts_currency_id_foreign` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`),
                             CONSTRAINT `contracts_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contracts`
--

LOCK TABLES `contracts` WRITE;
/*!40000 ALTER TABLE `contracts` DISABLE KEYS */;
/*!40000 ALTER TABLE `contracts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cost_centers`
--

DROP TABLE IF EXISTS `cost_centers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cost_centers` (
                                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                `name` varchar(255) NOT NULL,
                                `created_at` timestamp NULL DEFAULT NULL,
                                `updated_at` timestamp NULL DEFAULT NULL,
                                `deleted_at` timestamp NULL DEFAULT NULL,
                                PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cost_centers`
--

LOCK TABLES `cost_centers` WRITE;
/*!40000 ALTER TABLE `cost_centers` DISABLE KEYS */;
/*!40000 ALTER TABLE `cost_centers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `craft_inventory_categories`
--

DROP TABLE IF EXISTS `craft_inventory_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `craft_inventory_categories` (
                                              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                              `craft_id` bigint(20) unsigned NOT NULL,
                                              `name` text NOT NULL,
                                              `order` smallint(6) NOT NULL,
                                              `created_at` timestamp NULL DEFAULT NULL,
                                              `updated_at` timestamp NULL DEFAULT NULL,
                                              `deleted_at` timestamp NULL DEFAULT NULL,
                                              PRIMARY KEY (`id`),
                                              KEY `craft_inventory_categories_craft_id_foreign` (`craft_id`),
                                              CONSTRAINT `craft_inventory_categories_craft_id_foreign` FOREIGN KEY (`craft_id`) REFERENCES `crafts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `craft_inventory_categories`
--

LOCK TABLES `craft_inventory_categories` WRITE;
/*!40000 ALTER TABLE `craft_inventory_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `craft_inventory_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `craft_inventory_group_folders`
--

DROP TABLE IF EXISTS `craft_inventory_group_folders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `craft_inventory_group_folders` (
                                                 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                                 `name` varchar(255) NOT NULL,
                                                 `order` smallint(6) NOT NULL DEFAULT 0,
                                                 `craft_inventory_group_id` bigint(20) unsigned NOT NULL,
                                                 `created_at` timestamp NULL DEFAULT NULL,
                                                 `updated_at` timestamp NULL DEFAULT NULL,
                                                 PRIMARY KEY (`id`),
                                                 KEY `craft_inventory_group_folders_craft_inventory_group_id_foreign` (`craft_inventory_group_id`),
                                                 CONSTRAINT `craft_inventory_group_folders_craft_inventory_group_id_foreign` FOREIGN KEY (`craft_inventory_group_id`) REFERENCES `craft_inventory_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `craft_inventory_group_folders`
--

LOCK TABLES `craft_inventory_group_folders` WRITE;
/*!40000 ALTER TABLE `craft_inventory_group_folders` DISABLE KEYS */;
/*!40000 ALTER TABLE `craft_inventory_group_folders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `craft_inventory_groups`
--

DROP TABLE IF EXISTS `craft_inventory_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `craft_inventory_groups` (
                                          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                          `craft_inventory_category_id` bigint(20) unsigned NOT NULL,
                                          `name` text NOT NULL,
                                          `order` smallint(6) NOT NULL,
                                          `created_at` timestamp NULL DEFAULT NULL,
                                          `updated_at` timestamp NULL DEFAULT NULL,
                                          `deleted_at` timestamp NULL DEFAULT NULL,
                                          PRIMARY KEY (`id`),
                                          KEY `craft_inventory_groups_craft_inventory_category_id_foreign` (`craft_inventory_category_id`),
                                          CONSTRAINT `craft_inventory_groups_craft_inventory_category_id_foreign` FOREIGN KEY (`craft_inventory_category_id`) REFERENCES `craft_inventory_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `craft_inventory_groups`
--

LOCK TABLES `craft_inventory_groups` WRITE;
/*!40000 ALTER TABLE `craft_inventory_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `craft_inventory_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `craft_inventory_item_cells`
--

DROP TABLE IF EXISTS `craft_inventory_item_cells`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `craft_inventory_item_cells` (
                                              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                              `crafts_inventory_column_id` bigint(20) unsigned NOT NULL,
                                              `craft_inventory_item_id` bigint(20) unsigned NOT NULL,
                                              `cell_value` text NOT NULL,
                                              `created_at` timestamp NULL DEFAULT NULL,
                                              `updated_at` timestamp NULL DEFAULT NULL,
                                              `deleted_at` timestamp NULL DEFAULT NULL,
                                              PRIMARY KEY (`id`),
                                              KEY `craft_inventory_item_cells_crafts_inventory_column_id_foreign` (`crafts_inventory_column_id`),
                                              KEY `craft_inventory_item_cells_craft_inventory_item_id_foreign` (`craft_inventory_item_id`),
                                              CONSTRAINT `craft_inventory_item_cells_craft_inventory_item_id_foreign` FOREIGN KEY (`craft_inventory_item_id`) REFERENCES `craft_inventory_items` (`id`) ON DELETE CASCADE,
                                              CONSTRAINT `craft_inventory_item_cells_crafts_inventory_column_id_foreign` FOREIGN KEY (`crafts_inventory_column_id`) REFERENCES `crafts_inventory_columns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `craft_inventory_item_cells`
--

LOCK TABLES `craft_inventory_item_cells` WRITE;
/*!40000 ALTER TABLE `craft_inventory_item_cells` DISABLE KEYS */;
/*!40000 ALTER TABLE `craft_inventory_item_cells` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `craft_inventory_item_events`
--

DROP TABLE IF EXISTS `craft_inventory_item_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `craft_inventory_item_events` (
                                               `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                               `craft_inventory_item_id` bigint(20) unsigned NOT NULL,
                                               `event_id` bigint(20) unsigned NOT NULL,
                                               `quantity` int(11) NOT NULL,
                                               `comment` varchar(255) DEFAULT NULL,
                                               `start` datetime DEFAULT NULL,
                                               `end` datetime DEFAULT NULL,
                                               `is_all_day` tinyint(1) NOT NULL DEFAULT 0,
                                               `user_id` bigint(20) unsigned NOT NULL,
                                               `created_at` timestamp NULL DEFAULT NULL,
                                               `updated_at` timestamp NULL DEFAULT NULL,
                                               PRIMARY KEY (`id`),
                                               KEY `craft_inventory_item_events_craft_inventory_item_id_foreign` (`craft_inventory_item_id`),
                                               KEY `craft_inventory_item_events_event_id_foreign` (`event_id`),
                                               KEY `craft_inventory_item_events_user_id_foreign` (`user_id`),
                                               CONSTRAINT `craft_inventory_item_events_craft_inventory_item_id_foreign` FOREIGN KEY (`craft_inventory_item_id`) REFERENCES `craft_inventory_items` (`id`) ON DELETE CASCADE,
                                               CONSTRAINT `craft_inventory_item_events_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
                                               CONSTRAINT `craft_inventory_item_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `craft_inventory_item_events`
--

LOCK TABLES `craft_inventory_item_events` WRITE;
/*!40000 ALTER TABLE `craft_inventory_item_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `craft_inventory_item_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `craft_inventory_items`
--

DROP TABLE IF EXISTS `craft_inventory_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `craft_inventory_items` (
                                         `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                         `craft_inventory_group_id` bigint(20) unsigned DEFAULT NULL,
                                         `order` smallint(6) NOT NULL,
                                         `created_at` timestamp NULL DEFAULT NULL,
                                         `updated_at` timestamp NULL DEFAULT NULL,
                                         `deleted_at` timestamp NULL DEFAULT NULL,
                                         `craft_inventory_group_folder_id` bigint(20) unsigned DEFAULT NULL,
                                         PRIMARY KEY (`id`),
                                         KEY `craft_inventory_items_craft_inventory_group_folder_id_foreign` (`craft_inventory_group_folder_id`),
                                         KEY `craft_inventory_items_craft_inventory_group_id_foreign` (`craft_inventory_group_id`),
                                         CONSTRAINT `craft_inventory_items_craft_inventory_group_folder_id_foreign` FOREIGN KEY (`craft_inventory_group_folder_id`) REFERENCES `craft_inventory_group_folders` (`id`) ON DELETE SET NULL,
                                         CONSTRAINT `craft_inventory_items_craft_inventory_group_id_foreign` FOREIGN KEY (`craft_inventory_group_id`) REFERENCES `craft_inventory_groups` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `craft_inventory_items`
--

LOCK TABLES `craft_inventory_items` WRITE;
/*!40000 ALTER TABLE `craft_inventory_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `craft_inventory_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `craft_managers`
--

DROP TABLE IF EXISTS `craft_managers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `craft_managers` (
                                  `craft_id` bigint(20) unsigned NOT NULL,
                                  `craft_manager_type` varchar(255) NOT NULL,
                                  `craft_manager_id` bigint(20) unsigned NOT NULL,
                                  KEY `craft_managers_craft_id_foreign` (`craft_id`),
                                  KEY `craft_managers_craft_manager_type_craft_manager_id_index` (`craft_manager_type`,`craft_manager_id`),
                                  CONSTRAINT `craft_managers_craft_id_foreign` FOREIGN KEY (`craft_id`) REFERENCES `crafts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `craft_managers`
--

LOCK TABLES `craft_managers` WRITE;
/*!40000 ALTER TABLE `craft_managers` DISABLE KEYS */;
/*!40000 ALTER TABLE `craft_managers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `craft_shift_qualification`
--

DROP TABLE IF EXISTS `craft_shift_qualification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `craft_shift_qualification` (
                                             `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                             `craft_id` bigint(20) unsigned NOT NULL,
                                             `shift_qualification_id` bigint(20) unsigned NOT NULL,
                                             `created_at` timestamp NULL DEFAULT NULL,
                                             `updated_at` timestamp NULL DEFAULT NULL,
                                             PRIMARY KEY (`id`),
                                             UNIQUE KEY `uq_craft_shift_qual` (`craft_id`,`shift_qualification_id`),
                                             KEY `fk_csq_shift_qual` (`shift_qualification_id`),
                                             CONSTRAINT `fk_csq_craft` FOREIGN KEY (`craft_id`) REFERENCES `crafts` (`id`) ON DELETE CASCADE,
                                             CONSTRAINT `fk_csq_shift_qual` FOREIGN KEY (`shift_qualification_id`) REFERENCES `shift_qualifications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `craft_shift_qualification`
--

LOCK TABLES `craft_shift_qualification` WRITE;
/*!40000 ALTER TABLE `craft_shift_qualification` DISABLE KEYS */;
/*!40000 ALTER TABLE `craft_shift_qualification` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `craft_users`
--

DROP TABLE IF EXISTS `craft_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `craft_users` (
                               `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                               `craft_id` bigint(20) unsigned NOT NULL,
                               `user_id` bigint(20) unsigned NOT NULL,
                               `created_at` timestamp NULL DEFAULT NULL,
                               `updated_at` timestamp NULL DEFAULT NULL,
                               PRIMARY KEY (`id`),
                               KEY `craft_users_user_id_foreign` (`user_id`),
                               KEY `craft_users_craft_id_foreign` (`craft_id`),
                               CONSTRAINT `craft_users_craft_id_foreign` FOREIGN KEY (`craft_id`) REFERENCES `crafts` (`id`) ON DELETE CASCADE,
                               CONSTRAINT `craft_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `craft_users`
--

LOCK TABLES `craft_users` WRITE;
/*!40000 ALTER TABLE `craft_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `craft_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `craft_users_inventory`
--

DROP TABLE IF EXISTS `craft_users_inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `craft_users_inventory` (
                                         `craft_id` bigint(20) unsigned NOT NULL,
                                         `user_id` bigint(20) unsigned NOT NULL,
                                         `created_at` timestamp NULL DEFAULT NULL,
                                         `updated_at` timestamp NULL DEFAULT NULL,
                                         KEY `craft_users_inventory_craft_id_foreign` (`craft_id`),
                                         KEY `craft_users_inventory_user_id_foreign` (`user_id`),
                                         CONSTRAINT `craft_users_inventory_craft_id_foreign` FOREIGN KEY (`craft_id`) REFERENCES `crafts` (`id`) ON DELETE CASCADE,
                                         CONSTRAINT `craft_users_inventory_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `craft_users_inventory`
--

LOCK TABLES `craft_users_inventory` WRITE;
/*!40000 ALTER TABLE `craft_users_inventory` DISABLE KEYS */;
/*!40000 ALTER TABLE `craft_users_inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `craftables`
--

DROP TABLE IF EXISTS `craftables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `craftables` (
                              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                              `craft_id` bigint(20) unsigned NOT NULL,
                              `craftable_type` varchar(255) NOT NULL,
                              `craftable_id` bigint(20) unsigned NOT NULL,
                              `created_at` timestamp NULL DEFAULT NULL,
                              `updated_at` timestamp NULL DEFAULT NULL,
                              PRIMARY KEY (`id`),
                              KEY `craftables_craft_id_foreign` (`craft_id`),
                              KEY `craftables_craftable_type_craftable_id_index` (`craftable_type`,`craftable_id`),
                              CONSTRAINT `craftables_craft_id_foreign` FOREIGN KEY (`craft_id`) REFERENCES `crafts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `craftables`
--

LOCK TABLES `craftables` WRITE;
/*!40000 ALTER TABLE `craftables` DISABLE KEYS */;
/*!40000 ALTER TABLE `craftables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crafts`
--

DROP TABLE IF EXISTS `crafts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `crafts` (
                          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                          `name` varchar(255) NOT NULL,
                          `abbreviation` varchar(255) NOT NULL,
                          `assignable_by_all` tinyint(1) NOT NULL DEFAULT 1,
                          `created_at` timestamp NULL DEFAULT NULL,
                          `updated_at` timestamp NULL DEFAULT NULL,
                          `color` varchar(255) NOT NULL DEFAULT '#ffffff',
                          `notify_days` int(11) NOT NULL DEFAULT 0,
                          `universally_applicable` tinyint(1) NOT NULL DEFAULT 0,
                          `position` int(11) NOT NULL DEFAULT 0,
                          `inventory_planned_by_all` tinyint(1) NOT NULL DEFAULT 1,
                          PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crafts`
--

LOCK TABLES `crafts` WRITE;
/*!40000 ALTER TABLE `crafts` DISABLE KEYS */;
/*!40000 ALTER TABLE `crafts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crafts_inventory_columns`
--

DROP TABLE IF EXISTS `crafts_inventory_columns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `crafts_inventory_columns` (
                                            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                            `name` text NOT NULL,
                                            `type` smallint(6) NOT NULL,
                                            `type_options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`type_options`)),
                                            `background_color` varchar(255) NOT NULL,
                                            `created_at` timestamp NULL DEFAULT NULL,
                                            `updated_at` timestamp NULL DEFAULT NULL,
                                            `deleted_at` timestamp NULL DEFAULT NULL,
                                            `deletable` tinyint(1) NOT NULL DEFAULT 1,
                                            `order` smallint(6) NOT NULL DEFAULT 0,
                                            PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crafts_inventory_columns`
--

LOCK TABLES `crafts_inventory_columns` WRITE;
/*!40000 ALTER TABLE `crafts_inventory_columns` DISABLE KEYS */;
INSERT INTO `crafts_inventory_columns` VALUES
                                           (1,'Name',0,'[]','','2026-04-21 06:09:24','2026-04-21 06:09:24',NULL,0,1),
                                           (2,'Anzahl',0,'[]','','2026-04-21 06:09:24','2026-04-21 06:09:24',NULL,0,2),
                                           (3,'Letzte Änderung',99,'[]','','2026-04-21 06:09:24','2026-04-21 06:09:24',NULL,0,3);
/*!40000 ALTER TABLE `crafts_inventory_columns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_contact_type_property`
--

DROP TABLE IF EXISTS `crm_contact_type_property`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_contact_type_property` (
                                             `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                             `crm_contact_type_id` bigint(20) unsigned NOT NULL,
                                             `crm_property_id` bigint(20) unsigned NOT NULL,
                                             `sort_order` int(11) NOT NULL DEFAULT 0,
                                             `is_required` tinyint(1) NOT NULL DEFAULT 0,
                                             `show_in_list` tinyint(1) NOT NULL DEFAULT 0,
                                             `is_filterable` tinyint(1) NOT NULL DEFAULT 0,
                                             PRIMARY KEY (`id`),
                                             UNIQUE KEY `crm_type_property_unique` (`crm_contact_type_id`,`crm_property_id`),
                                             KEY `crm_contact_type_property_crm_property_id_foreign` (`crm_property_id`),
                                             CONSTRAINT `crm_contact_type_property_crm_contact_type_id_foreign` FOREIGN KEY (`crm_contact_type_id`) REFERENCES `crm_contact_types` (`id`) ON DELETE CASCADE,
                                             CONSTRAINT `crm_contact_type_property_crm_property_id_foreign` FOREIGN KEY (`crm_property_id`) REFERENCES `crm_properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_contact_type_property`
--

LOCK TABLES `crm_contact_type_property` WRITE;
/*!40000 ALTER TABLE `crm_contact_type_property` DISABLE KEYS */;
/*!40000 ALTER TABLE `crm_contact_type_property` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_contact_types`
--

DROP TABLE IF EXISTS `crm_contact_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_contact_types` (
                                     `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                     `name` varchar(255) NOT NULL,
                                     `slug` varchar(255) NOT NULL,
                                     `icon` varchar(255) DEFAULT NULL,
                                     `color` varchar(255) DEFAULT NULL,
                                     `is_system` tinyint(1) NOT NULL DEFAULT 0,
                                     `is_active` tinyint(1) NOT NULL DEFAULT 1,
                                     `sort_order` int(11) NOT NULL DEFAULT 0,
                                     `created_at` timestamp NULL DEFAULT NULL,
                                     `updated_at` timestamp NULL DEFAULT NULL,
                                     `deleted_at` timestamp NULL DEFAULT NULL,
                                     PRIMARY KEY (`id`),
                                     UNIQUE KEY `crm_contact_types_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_contact_types`
--

LOCK TABLES `crm_contact_types` WRITE;
/*!40000 ALTER TABLE `crm_contact_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `crm_contact_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_contacts`
--

DROP TABLE IF EXISTS `crm_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_contacts` (
                                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                `crm_contact_type_id` bigint(20) unsigned NOT NULL,
                                `display_name` varchar(255) NOT NULL,
                                `profile_image` varchar(255) DEFAULT NULL,
                                `is_active` tinyint(1) NOT NULL DEFAULT 1,
                                `created_at` timestamp NULL DEFAULT NULL,
                                `updated_at` timestamp NULL DEFAULT NULL,
                                `deleted_at` timestamp NULL DEFAULT NULL,
                                `entity_type` varchar(255) DEFAULT NULL,
                                `entity_id` bigint(20) unsigned DEFAULT NULL,
                                PRIMARY KEY (`id`),
                                KEY `crm_contacts_crm_contact_type_id_foreign` (`crm_contact_type_id`),
                                KEY `crm_contacts_entity_type_entity_id_index` (`entity_type`,`entity_id`),
                                CONSTRAINT `crm_contacts_crm_contact_type_id_foreign` FOREIGN KEY (`crm_contact_type_id`) REFERENCES `crm_contact_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_contacts`
--

LOCK TABLES `crm_contacts` WRITE;
/*!40000 ALTER TABLE `crm_contacts` DISABLE KEYS */;
/*!40000 ALTER TABLE `crm_contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_properties`
--

DROP TABLE IF EXISTS `crm_properties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_properties` (
                                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                  `crm_property_group_id` bigint(20) unsigned NOT NULL,
                                  `name` varchar(255) NOT NULL,
                                  `type` varchar(255) NOT NULL,
                                  `select_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`select_values`)),
                                  `tooltip_text` varchar(255) DEFAULT NULL,
                                  `is_system` tinyint(1) NOT NULL DEFAULT 0,
                                  `sort_order` int(11) NOT NULL DEFAULT 0,
                                  `created_at` timestamp NULL DEFAULT NULL,
                                  `updated_at` timestamp NULL DEFAULT NULL,
                                  PRIMARY KEY (`id`),
                                  KEY `crm_properties_crm_property_group_id_foreign` (`crm_property_group_id`),
                                  CONSTRAINT `crm_properties_crm_property_group_id_foreign` FOREIGN KEY (`crm_property_group_id`) REFERENCES `crm_property_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_properties`
--

LOCK TABLES `crm_properties` WRITE;
/*!40000 ALTER TABLE `crm_properties` DISABLE KEYS */;
/*!40000 ALTER TABLE `crm_properties` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_property_group_permissions`
--

DROP TABLE IF EXISTS `crm_property_group_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_property_group_permissions` (
                                                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                                  `crm_property_group_id` bigint(20) unsigned NOT NULL,
                                                  `permissionable_type` varchar(255) NOT NULL,
                                                  `permissionable_id` bigint(20) unsigned NOT NULL,
                                                  `can_view` tinyint(1) NOT NULL DEFAULT 0,
                                                  `can_edit` tinyint(1) NOT NULL DEFAULT 0,
                                                  `created_at` timestamp NULL DEFAULT NULL,
                                                  `updated_at` timestamp NULL DEFAULT NULL,
                                                  PRIMARY KEY (`id`),
                                                  KEY `crm_property_group_permissions_crm_property_group_id_foreign` (`crm_property_group_id`),
                                                  KEY `crm_pgp_permissionable_index` (`permissionable_type`,`permissionable_id`),
                                                  CONSTRAINT `crm_property_group_permissions_crm_property_group_id_foreign` FOREIGN KEY (`crm_property_group_id`) REFERENCES `crm_property_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_property_group_permissions`
--

LOCK TABLES `crm_property_group_permissions` WRITE;
/*!40000 ALTER TABLE `crm_property_group_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `crm_property_group_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_property_groups`
--

DROP TABLE IF EXISTS `crm_property_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_property_groups` (
                                       `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                       `name` varchar(255) NOT NULL,
                                       `icon` varchar(255) DEFAULT NULL,
                                       `color` varchar(255) DEFAULT NULL,
                                       `is_confidential` tinyint(1) NOT NULL DEFAULT 0,
                                       `sort_order` int(11) NOT NULL DEFAULT 0,
                                       `is_system` tinyint(1) NOT NULL DEFAULT 0,
                                       `created_at` timestamp NULL DEFAULT NULL,
                                       `updated_at` timestamp NULL DEFAULT NULL,
                                       PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_property_groups`
--

LOCK TABLES `crm_property_groups` WRITE;
/*!40000 ALTER TABLE `crm_property_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `crm_property_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_property_values`
--

DROP TABLE IF EXISTS `crm_property_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_property_values` (
                                       `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                       `crm_contact_id` bigint(20) unsigned NOT NULL,
                                       `crm_property_id` bigint(20) unsigned NOT NULL,
                                       `value` text DEFAULT NULL,
                                       `created_at` timestamp NULL DEFAULT NULL,
                                       `updated_at` timestamp NULL DEFAULT NULL,
                                       PRIMARY KEY (`id`),
                                       UNIQUE KEY `crm_contact_property_unique` (`crm_contact_id`,`crm_property_id`),
                                       KEY `crm_property_values_crm_property_id_foreign` (`crm_property_id`),
                                       CONSTRAINT `crm_property_values_crm_contact_id_foreign` FOREIGN KEY (`crm_contact_id`) REFERENCES `crm_contacts` (`id`) ON DELETE CASCADE,
                                       CONSTRAINT `crm_property_values_crm_property_id_foreign` FOREIGN KEY (`crm_property_id`) REFERENCES `crm_properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_property_values`
--

LOCK TABLES `crm_property_values` WRITE;
/*!40000 ALTER TABLE `crm_property_values` DISABLE KEYS */;
/*!40000 ALTER TABLE `crm_property_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `currencies`
--

DROP TABLE IF EXISTS `currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `currencies` (
                              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                              `name` varchar(255) NOT NULL,
                              `color` varchar(255) DEFAULT NULL,
                              `created_at` timestamp NULL DEFAULT NULL,
                              `updated_at` timestamp NULL DEFAULT NULL,
                              `deleted_at` timestamp NULL DEFAULT NULL,
                              PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `currencies`
--

LOCK TABLES `currencies` WRITE;
/*!40000 ALTER TABLE `currencies` DISABLE KEYS */;
/*!40000 ALTER TABLE `currencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `day_serviceables`
--

DROP TABLE IF EXISTS `day_serviceables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `day_serviceables` (
                                    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                    `day_service_id` bigint(20) unsigned NOT NULL,
                                    `date` date NOT NULL,
                                    `day_serviceable_type` varchar(255) NOT NULL,
                                    `day_serviceable_id` bigint(20) unsigned NOT NULL,
                                    `created_at` timestamp NULL DEFAULT NULL,
                                    `updated_at` timestamp NULL DEFAULT NULL,
                                    PRIMARY KEY (`id`),
                                    KEY `day_serviceables_day_service_id_foreign` (`day_service_id`),
                                    KEY `day_serviceables_day_serviceable_type_day_serviceable_id_index` (`day_serviceable_type`,`day_serviceable_id`),
                                    CONSTRAINT `day_serviceables_day_service_id_foreign` FOREIGN KEY (`day_service_id`) REFERENCES `day_services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `day_serviceables`
--

LOCK TABLES `day_serviceables` WRITE;
/*!40000 ALTER TABLE `day_serviceables` DISABLE KEYS */;
/*!40000 ALTER TABLE `day_serviceables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `day_services`
--

DROP TABLE IF EXISTS `day_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `day_services` (
                                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                `name` varchar(255) NOT NULL,
                                `icon` varchar(255) NOT NULL,
                                `hex_color` varchar(255) NOT NULL,
                                `created_at` timestamp NULL DEFAULT NULL,
                                `updated_at` timestamp NULL DEFAULT NULL,
                                PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `day_services`
--

LOCK TABLES `day_services` WRITE;
/*!40000 ALTER TABLE `day_services` DISABLE KEYS */;
/*!40000 ALTER TABLE `day_services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `department_invitation`
--

DROP TABLE IF EXISTS `department_invitation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `department_invitation` (
                                         `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                         `invitation_id` int(11) NOT NULL,
                                         `department_id` int(11) NOT NULL,
                                         `created_at` timestamp NULL DEFAULT NULL,
                                         `updated_at` timestamp NULL DEFAULT NULL,
                                         PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `department_invitation`
--

LOCK TABLES `department_invitation` WRITE;
/*!40000 ALTER TABLE `department_invitation` DISABLE KEYS */;
/*!40000 ALTER TABLE `department_invitation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `department_project`
--

DROP TABLE IF EXISTS `department_project`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `department_project` (
                                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                      `project_id` bigint(20) unsigned NOT NULL,
                                      `department_id` bigint(20) unsigned NOT NULL,
                                      `created_at` timestamp NULL DEFAULT NULL,
                                      `updated_at` timestamp NULL DEFAULT NULL,
                                      PRIMARY KEY (`id`),
                                      KEY `department_project_project_id_foreign` (`project_id`),
                                      KEY `department_project_department_id_foreign` (`department_id`),
                                      CONSTRAINT `department_project_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
                                      CONSTRAINT `department_project_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `department_project`
--

LOCK TABLES `department_project` WRITE;
/*!40000 ALTER TABLE `department_project` DISABLE KEYS */;
/*!40000 ALTER TABLE `department_project` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `department_user`
--

DROP TABLE IF EXISTS `department_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `department_user` (
                                   `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                   `user_id` int(11) NOT NULL,
                                   `department_id` int(11) NOT NULL,
                                   `created_at` timestamp NULL DEFAULT NULL,
                                   `updated_at` timestamp NULL DEFAULT NULL,
                                   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `department_user`
--

LOCK TABLES `department_user` WRITE;
/*!40000 ALTER TABLE `department_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `department_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
                               `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                               `name` varchar(255) NOT NULL,
                               `created_at` timestamp NULL DEFAULT NULL,
                               `updated_at` timestamp NULL DEFAULT NULL,
                               `svg_name` varchar(255) NOT NULL,
                               PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `disclosure_components`
--

DROP TABLE IF EXISTS `disclosure_components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `disclosure_components` (
                                         `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                         `disclosure_id` bigint(20) unsigned NOT NULL,
                                         `component_id` bigint(20) unsigned NOT NULL,
                                         `order` int(11) NOT NULL,
                                         `scope` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`scope`)),
                                         `created_at` timestamp NULL DEFAULT NULL,
                                         `updated_at` timestamp NULL DEFAULT NULL,
                                         PRIMARY KEY (`id`),
                                         KEY `disclosure_components_disclosure_id_foreign` (`disclosure_id`),
                                         KEY `disclosure_components_component_id_foreign` (`component_id`),
                                         CONSTRAINT `disclosure_components_component_id_foreign` FOREIGN KEY (`component_id`) REFERENCES `components` (`id`),
                                         CONSTRAINT `disclosure_components_disclosure_id_foreign` FOREIGN KEY (`disclosure_id`) REFERENCES `components` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `disclosure_components`
--

LOCK TABLES `disclosure_components` WRITE;
/*!40000 ALTER TABLE `disclosure_components` DISABLE KEYS */;
/*!40000 ALTER TABLE `disclosure_components` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_requests`
--

DROP TABLE IF EXISTS `document_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_requests` (
                                     `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                     `requester_id` bigint(20) unsigned NOT NULL,
                                     `requested_id` bigint(20) unsigned DEFAULT NULL,
                                     `project_id` bigint(20) unsigned DEFAULT NULL,
                                     `contract_id` bigint(20) unsigned DEFAULT NULL,
                                     `status` enum('open','in_progress','completed') NOT NULL DEFAULT 'open',
                                     `contract_partner` varchar(255) DEFAULT NULL,
                                     `crm_contact_id` bigint(20) unsigned DEFAULT NULL,
                                     `contract_value` decimal(10,2) DEFAULT NULL,
                                     `ksk_liable` tinyint(1) NOT NULL DEFAULT 0,
                                     `ksk_amount` decimal(10,2) DEFAULT NULL,
                                     `ksk_reason` text DEFAULT NULL,
                                     `foreign_tax` tinyint(1) NOT NULL DEFAULT 0,
                                     `foreign_tax_amount` decimal(10,2) DEFAULT NULL,
                                     `foreign_tax_city` varchar(255) DEFAULT NULL,
                                     `foreign_tax_country` varchar(255) DEFAULT NULL,
                                     `foreign_tax_reason` text DEFAULT NULL,
                                     `reverse_charge_amount` decimal(10,2) DEFAULT NULL,
                                     `deadline_date` date DEFAULT NULL,
                                     `contract_type_id` bigint(20) unsigned DEFAULT NULL,
                                     `company_type_id` bigint(20) unsigned DEFAULT NULL,
                                     `comment` text DEFAULT NULL,
                                     `contract_state` varchar(255) DEFAULT NULL,
                                     `contract_state_comment` text DEFAULT NULL,
                                     `created_at` timestamp NULL DEFAULT NULL,
                                     `updated_at` timestamp NULL DEFAULT NULL,
                                     `deleted_at` timestamp NULL DEFAULT NULL,
                                     PRIMARY KEY (`id`),
                                     KEY `document_requests_requester_id_foreign` (`requester_id`),
                                     KEY `document_requests_requested_id_foreign` (`requested_id`),
                                     KEY `document_requests_project_id_foreign` (`project_id`),
                                     KEY `document_requests_contract_id_foreign` (`contract_id`),
                                     KEY `document_requests_contract_type_id_foreign` (`contract_type_id`),
                                     KEY `document_requests_company_type_id_foreign` (`company_type_id`),
                                     KEY `document_requests_crm_contact_id_foreign` (`crm_contact_id`),
                                     CONSTRAINT `document_requests_company_type_id_foreign` FOREIGN KEY (`company_type_id`) REFERENCES `company_types` (`id`) ON DELETE SET NULL,
                                     CONSTRAINT `document_requests_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE SET NULL,
                                     CONSTRAINT `document_requests_contract_type_id_foreign` FOREIGN KEY (`contract_type_id`) REFERENCES `contract_types` (`id`) ON DELETE SET NULL,
                                     CONSTRAINT `document_requests_crm_contact_id_foreign` FOREIGN KEY (`crm_contact_id`) REFERENCES `crm_contacts` (`id`) ON DELETE SET NULL,
                                     CONSTRAINT `document_requests_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
                                     CONSTRAINT `document_requests_requested_id_foreign` FOREIGN KEY (`requested_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
                                     CONSTRAINT `document_requests_requester_id_foreign` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_requests`
--

LOCK TABLES `document_requests` WRITE;
/*!40000 ALTER TABLE `document_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `document_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_comments`
--

DROP TABLE IF EXISTS `event_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_comments` (
                                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                  `event_id` bigint(20) unsigned NOT NULL,
                                  `user_id` bigint(20) unsigned NOT NULL,
                                  `comment` text NOT NULL,
                                  `is_admin_comment` tinyint(1) NOT NULL DEFAULT 0,
                                  `created_at` timestamp NULL DEFAULT NULL,
                                  `updated_at` timestamp NULL DEFAULT NULL,
                                  `deleted_at` timestamp NULL DEFAULT NULL,
                                  PRIMARY KEY (`id`),
                                  KEY `event_comments_user_id_foreign` (`user_id`),
                                  KEY `event_comments_event_id_foreign` (`event_id`),
                                  CONSTRAINT `event_comments_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
                                  CONSTRAINT `event_comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_comments`
--

LOCK TABLES `event_comments` WRITE;
/*!40000 ALTER TABLE `event_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_event_property`
--

DROP TABLE IF EXISTS `event_event_property`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_event_property` (
                                        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                        `event_id` bigint(20) unsigned NOT NULL,
                                        `event_property_id` bigint(20) unsigned NOT NULL,
                                        PRIMARY KEY (`id`),
                                        KEY `event_event_property_event_id_foreign` (`event_id`),
                                        KEY `event_event_property_event_property_id_foreign` (`event_property_id`),
                                        CONSTRAINT `event_event_property_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
                                        CONSTRAINT `event_event_property_event_property_id_foreign` FOREIGN KEY (`event_property_id`) REFERENCES `event_properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_event_property`
--

LOCK TABLES `event_event_property` WRITE;
/*!40000 ALTER TABLE `event_event_property` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_event_property` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_properties`
--

DROP TABLE IF EXISTS `event_properties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_properties` (
                                    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                    `name` varchar(255) NOT NULL,
                                    `icon` varchar(255) NOT NULL,
                                    `created_at` timestamp NULL DEFAULT NULL,
                                    `updated_at` timestamp NULL DEFAULT NULL,
                                    `deleted_at` timestamp NULL DEFAULT NULL,
                                    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_properties`
--

LOCK TABLES `event_properties` WRITE;
/*!40000 ALTER TABLE `event_properties` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_properties` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_property_sub_event`
--

DROP TABLE IF EXISTS `event_property_sub_event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_property_sub_event` (
                                            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                            `sub_event_id` bigint(20) unsigned NOT NULL,
                                            `event_property_id` bigint(20) unsigned NOT NULL,
                                            PRIMARY KEY (`id`),
                                            KEY `event_property_sub_event_sub_event_id_foreign` (`sub_event_id`),
                                            KEY `event_property_sub_event_event_property_id_foreign` (`event_property_id`),
                                            CONSTRAINT `event_property_sub_event_event_property_id_foreign` FOREIGN KEY (`event_property_id`) REFERENCES `event_properties` (`id`) ON DELETE CASCADE,
                                            CONSTRAINT `event_property_sub_event_sub_event_id_foreign` FOREIGN KEY (`sub_event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_property_sub_event`
--

LOCK TABLES `event_property_sub_event` WRITE;
/*!40000 ALTER TABLE `event_property_sub_event` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_property_sub_event` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_statuses`
--

DROP TABLE IF EXISTS `event_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_statuses` (
                                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                  `name` varchar(255) NOT NULL,
                                  `color` varchar(255) NOT NULL,
                                  `order` smallint(6) NOT NULL,
                                  `created_at` timestamp NULL DEFAULT NULL,
                                  `updated_at` timestamp NULL DEFAULT NULL,
                                  `default` tinyint(1) NOT NULL DEFAULT 0,
                                  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_statuses`
--

LOCK TABLES `event_statuses` WRITE;
/*!40000 ALTER TABLE `event_statuses` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_statuses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_type_filter`
--

DROP TABLE IF EXISTS `event_type_filter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_type_filter` (
                                     `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                     `event_type_id` bigint(20) unsigned NOT NULL,
                                     `filter_id` bigint(20) unsigned NOT NULL,
                                     `created_at` timestamp NULL DEFAULT NULL,
                                     `updated_at` timestamp NULL DEFAULT NULL,
                                     PRIMARY KEY (`id`),
                                     KEY `event_type_filter_event_type_id_foreign` (`event_type_id`),
                                     KEY `event_type_filter_filter_id_foreign` (`filter_id`),
                                     CONSTRAINT `event_type_filter_event_type_id_foreign` FOREIGN KEY (`event_type_id`) REFERENCES `event_types` (`id`) ON DELETE CASCADE,
                                     CONSTRAINT `event_type_filter_filter_id_foreign` FOREIGN KEY (`filter_id`) REFERENCES `filters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_type_filter`
--

LOCK TABLES `event_type_filter` WRITE;
/*!40000 ALTER TABLE `event_type_filter` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_type_filter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_type_shift_filter`
--

DROP TABLE IF EXISTS `event_type_shift_filter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_type_shift_filter` (
                                           `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                           `event_type_id` bigint(20) unsigned NOT NULL,
                                           `shift_filter_id` bigint(20) unsigned NOT NULL,
                                           `created_at` timestamp NULL DEFAULT NULL,
                                           `updated_at` timestamp NULL DEFAULT NULL,
                                           PRIMARY KEY (`id`),
                                           KEY `event_type_shift_filter_event_type_id_foreign` (`event_type_id`),
                                           KEY `event_type_shift_filter_shift_filter_id_foreign` (`shift_filter_id`),
                                           CONSTRAINT `event_type_shift_filter_event_type_id_foreign` FOREIGN KEY (`event_type_id`) REFERENCES `event_types` (`id`) ON DELETE CASCADE,
                                           CONSTRAINT `event_type_shift_filter_shift_filter_id_foreign` FOREIGN KEY (`shift_filter_id`) REFERENCES `shift_filters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_type_shift_filter`
--

LOCK TABLES `event_type_shift_filter` WRITE;
/*!40000 ALTER TABLE `event_type_shift_filter` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_type_shift_filter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_type_user`
--

DROP TABLE IF EXISTS `event_type_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_type_user` (
                                   `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                   `event_type_id` bigint(20) unsigned NOT NULL,
                                   `user_id` bigint(20) unsigned NOT NULL,
                                   PRIMARY KEY (`id`),
                                   KEY `event_type_user_event_type_id_foreign` (`event_type_id`),
                                   KEY `event_type_user_user_id_foreign` (`user_id`),
                                   CONSTRAINT `event_type_user_event_type_id_foreign` FOREIGN KEY (`event_type_id`) REFERENCES `event_types` (`id`) ON DELETE CASCADE,
                                   CONSTRAINT `event_type_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_type_user`
--

LOCK TABLES `event_type_user` WRITE;
/*!40000 ALTER TABLE `event_type_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_type_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_types`
--

DROP TABLE IF EXISTS `event_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_types` (
                               `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                               `name` varchar(255) NOT NULL,
                               `hex_code` varchar(255) NOT NULL,
                               `svg_name` varchar(255) DEFAULT NULL,
                               `project_mandatory` tinyint(1) NOT NULL,
                               `individual_name` tinyint(1) NOT NULL,
                               `abbreviation` varchar(255) NOT NULL,
                               `relevant_for_shift` tinyint(1) NOT NULL DEFAULT 0,
                               `fallback_type` tinyint(1) NOT NULL DEFAULT 0,
                               `created_at` timestamp NULL DEFAULT NULL,
                               `updated_at` timestamp NULL DEFAULT NULL,
                               `relevant_for_inventory` tinyint(1) NOT NULL DEFAULT 0,
                               `relevant_for_project_period` tinyint(1) NOT NULL DEFAULT 0,
                               `verification_mode` varchar(255) NOT NULL DEFAULT 'none',
                               `specific_verifier_id` bigint(20) unsigned DEFAULT NULL,
                               PRIMARY KEY (`id`),
                               KEY `event_types_specific_verifier_id_foreign` (`specific_verifier_id`),
                               CONSTRAINT `event_types_specific_verifier_id_foreign` FOREIGN KEY (`specific_verifier_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_types`
--

LOCK TABLES `event_types` WRITE;
/*!40000 ALTER TABLE `event_types` DISABLE KEYS */;
INSERT INTO `event_types` VALUES
    (1,'Blocker','#A7A6B1',NULL,0,1,'BL',0,0,NULL,NULL,0,0,'none',NULL);
/*!40000 ALTER TABLE `event_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_verifications`
--

DROP TABLE IF EXISTS `event_verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_verifications` (
                                       `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                       `uuid` char(36) NOT NULL,
                                       `verifier_type` varchar(255) NOT NULL,
                                       `verifier_id` bigint(20) unsigned NOT NULL,
                                       `event_id` bigint(20) unsigned NOT NULL,
                                       `request_user_id` bigint(20) unsigned NOT NULL,
                                       `status` varchar(255) NOT NULL DEFAULT 'pending',
                                       `rejection_reason` text DEFAULT NULL,
                                       `created_at` timestamp NULL DEFAULT NULL,
                                       `updated_at` timestamp NULL DEFAULT NULL,
                                       PRIMARY KEY (`id`),
                                       KEY `event_verifications_verifier_type_verifier_id_index` (`verifier_type`,`verifier_id`),
                                       KEY `event_verifications_event_id_foreign` (`event_id`),
                                       KEY `event_verifications_request_user_id_foreign` (`request_user_id`),
                                       CONSTRAINT `event_verifications_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
                                       CONSTRAINT `event_verifications_request_user_id_foreign` FOREIGN KEY (`request_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_verifications`
--

LOCK TABLES `event_verifications` WRITE;
/*!40000 ALTER TABLE `event_verifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_verifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `events` (
                          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                          `name` varchar(255) DEFAULT NULL,
                          `eventName` varchar(255) DEFAULT NULL,
                          `description` longtext DEFAULT NULL,
                          `start_time` timestamp NULL DEFAULT NULL,
                          `end_time` timestamp NULL DEFAULT NULL,
                          `earliest_start_datetime` datetime DEFAULT NULL,
                          `latest_end_datetime` datetime DEFAULT NULL,
                          `occupancy_option` tinyint(1) NOT NULL DEFAULT 0,
                          `audience` tinyint(1) DEFAULT 0,
                          `is_loud` tinyint(1) DEFAULT 0,
                          `allDay` tinyint(1) NOT NULL DEFAULT 0,
                          `event_type_id` bigint(20) unsigned NOT NULL,
                          `room_id` bigint(20) unsigned DEFAULT NULL,
                          `declined_room_id` bigint(20) unsigned DEFAULT NULL,
                          `user_id` bigint(20) unsigned DEFAULT NULL,
                          `project_id` bigint(20) unsigned DEFAULT NULL,
                          `is_series` tinyint(1) NOT NULL DEFAULT 0,
                          `series_id` bigint(20) unsigned DEFAULT NULL,
                          `created_at` timestamp NULL DEFAULT NULL,
                          `updated_at` timestamp NULL DEFAULT NULL,
                          `deleted_at` timestamp NULL DEFAULT NULL,
                          `accepted` tinyint(1) NOT NULL DEFAULT 0,
                          `option_string` varchar(255) DEFAULT NULL,
                          `event_status_id` bigint(20) unsigned DEFAULT NULL,
                          `is_planning` tinyint(1) NOT NULL DEFAULT 0,
                          PRIMARY KEY (`id`),
                          KEY `events_event_type_id_foreign` (`event_type_id`),
                          KEY `events_declined_room_id_foreign` (`declined_room_id`),
                          KEY `events_room_id_foreign` (`room_id`),
                          KEY `events_user_id_foreign` (`user_id`),
                          KEY `events_project_id_foreign` (`project_id`),
                          KEY `events_series_id_foreign` (`series_id`),
                          KEY `events_start_time_index` (`start_time`),
                          KEY `events_end_time_index` (`end_time`),
                          KEY `events_event_status_id_foreign` (`event_status_id`),
                          KEY `idx_events_room_start` (`room_id`,`start_time`),
                          KEY `idx_events_room_end` (`room_id`,`end_time`),
                          KEY `idx_events_start` (`start_time`),
                          KEY `idx_events_end` (`end_time`),
                          KEY `idx_events_type` (`event_type_id`),
                          KEY `idx_events_status` (`event_status_id`),
                          KEY `idx_events_planning` (`is_planning`),
                          KEY `idx_events_deleted_at` (`deleted_at`),
                          CONSTRAINT `events_declined_room_id_foreign` FOREIGN KEY (`declined_room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL,
                          CONSTRAINT `events_event_status_id_foreign` FOREIGN KEY (`event_status_id`) REFERENCES `event_statuses` (`id`),
                          CONSTRAINT `events_event_type_id_foreign` FOREIGN KEY (`event_type_id`) REFERENCES `event_types` (`id`),
                          CONSTRAINT `events_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
                          CONSTRAINT `events_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL,
                          CONSTRAINT `events_series_id_foreign` FOREIGN KEY (`series_id`) REFERENCES `series_events` (`id`),
                          CONSTRAINT `events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `events`
--

LOCK TABLES `events` WRITE;
/*!40000 ALTER TABLE `events` DISABLE KEYS */;
/*!40000 ALTER TABLE `events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `external_issue_files`
--

DROP TABLE IF EXISTS `external_issue_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `external_issue_files` (
                                        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                        `external_issue_id` bigint(20) unsigned NOT NULL,
                                        `file_path` varchar(255) NOT NULL,
                                        `original_name` varchar(255) NOT NULL,
                                        `created_at` timestamp NULL DEFAULT NULL,
                                        `updated_at` timestamp NULL DEFAULT NULL,
                                        PRIMARY KEY (`id`),
                                        KEY `external_issue_files_external_issue_id_foreign` (`external_issue_id`),
                                        CONSTRAINT `external_issue_files_external_issue_id_foreign` FOREIGN KEY (`external_issue_id`) REFERENCES `external_issues` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `external_issue_files`
--

LOCK TABLES `external_issue_files` WRITE;
/*!40000 ALTER TABLE `external_issue_files` DISABLE KEYS */;
/*!40000 ALTER TABLE `external_issue_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `external_issues`
--

DROP TABLE IF EXISTS `external_issues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `external_issues` (
                                   `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                   `name` varchar(255) DEFAULT NULL,
                                   `material_value` decimal(10,2) NOT NULL,
                                   `issued_by_id` bigint(20) unsigned DEFAULT NULL,
                                   `received_by_id` bigint(20) unsigned DEFAULT NULL,
                                   `issue_date` date NOT NULL,
                                   `return_date` date NOT NULL,
                                   `return_remarks` text DEFAULT NULL,
                                   `external_name` varchar(255) NOT NULL,
                                   `external_address` varchar(255) DEFAULT NULL,
                                   `external_email` varchar(255) DEFAULT NULL,
                                   `external_phone` varchar(255) DEFAULT NULL,
                                   `created_at` timestamp NULL DEFAULT NULL,
                                   `updated_at` timestamp NULL DEFAULT NULL,
                                   `special_items_done` tinyint(1) NOT NULL DEFAULT 0,
                                   PRIMARY KEY (`id`),
                                   KEY `external_issues_issued_by_id_foreign` (`issued_by_id`),
                                   KEY `external_issues_received_by_id_foreign` (`received_by_id`),
                                   CONSTRAINT `external_issues_issued_by_id_foreign` FOREIGN KEY (`issued_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
                                   CONSTRAINT `external_issues_received_by_id_foreign` FOREIGN KEY (`received_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `external_issues`
--

LOCK TABLES `external_issues` WRITE;
/*!40000 ALTER TABLE `external_issues` DISABLE KEYS */;
/*!40000 ALTER TABLE `external_issues` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
                               `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                               `uuid` varchar(255) NOT NULL,
                               `connection` text NOT NULL,
                               `queue` text NOT NULL,
                               `payload` longtext NOT NULL,
                               `exception` longtext NOT NULL,
                               `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
                               PRIMARY KEY (`id`),
                               UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `filter_room`
--

DROP TABLE IF EXISTS `filter_room`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `filter_room` (
                               `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                               `filter_id` int(11) NOT NULL,
                               `room_id` int(11) NOT NULL,
                               `created_at` timestamp NULL DEFAULT NULL,
                               `updated_at` timestamp NULL DEFAULT NULL,
                               PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `filter_room`
--

LOCK TABLES `filter_room` WRITE;
/*!40000 ALTER TABLE `filter_room` DISABLE KEYS */;
/*!40000 ALTER TABLE `filter_room` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `filter_room_attribute`
--

DROP TABLE IF EXISTS `filter_room_attribute`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `filter_room_attribute` (
                                         `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                         `filter_id` int(11) NOT NULL,
                                         `room_attribute_id` int(11) NOT NULL,
                                         `created_at` timestamp NULL DEFAULT NULL,
                                         `updated_at` timestamp NULL DEFAULT NULL,
                                         PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `filter_room_attribute`
--

LOCK TABLES `filter_room_attribute` WRITE;
/*!40000 ALTER TABLE `filter_room_attribute` DISABLE KEYS */;
/*!40000 ALTER TABLE `filter_room_attribute` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `filter_room_category`
--

DROP TABLE IF EXISTS `filter_room_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `filter_room_category` (
                                        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                        `filter_id` int(11) NOT NULL,
                                        `room_category_id` int(11) NOT NULL,
                                        `created_at` timestamp NULL DEFAULT NULL,
                                        `updated_at` timestamp NULL DEFAULT NULL,
                                        PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `filter_room_category`
--

LOCK TABLES `filter_room_category` WRITE;
/*!40000 ALTER TABLE `filter_room_category` DISABLE KEYS */;
/*!40000 ALTER TABLE `filter_room_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `filters`
--

DROP TABLE IF EXISTS `filters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `filters` (
                           `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                           `name` varchar(255) NOT NULL,
                           `user_id` bigint(20) unsigned NOT NULL,
                           `adjoiningNoAudience` tinyint(1) DEFAULT 0,
                           `adjoiningNotLoud` tinyint(1) DEFAULT 0,
                           `allDayFree` tinyint(1) DEFAULT 0,
                           `showAdjoiningRooms` tinyint(1) DEFAULT 0,
                           `eventProperties` longtext DEFAULT NULL,
                           `created_at` timestamp NULL DEFAULT NULL,
                           `updated_at` timestamp NULL DEFAULT NULL,
                           PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `filters`
--

LOCK TABLES `filters` WRITE;
/*!40000 ALTER TABLE `filters` DISABLE KEYS */;
/*!40000 ALTER TABLE `filters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `freelancer_assigned_crafts`
--

DROP TABLE IF EXISTS `freelancer_assigned_crafts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `freelancer_assigned_crafts` (
                                              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                              `freelancer_id` bigint(20) unsigned NOT NULL,
                                              `craft_id` bigint(20) unsigned NOT NULL,
                                              PRIMARY KEY (`id`),
                                              KEY `freelancer_assigned_crafts_freelancer_id_foreign` (`freelancer_id`),
                                              KEY `freelancer_assigned_crafts_craft_id_foreign` (`craft_id`),
                                              CONSTRAINT `freelancer_assigned_crafts_craft_id_foreign` FOREIGN KEY (`craft_id`) REFERENCES `crafts` (`id`),
                                              CONSTRAINT `freelancer_assigned_crafts_freelancer_id_foreign` FOREIGN KEY (`freelancer_id`) REFERENCES `freelancers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `freelancer_assigned_crafts`
--

LOCK TABLES `freelancer_assigned_crafts` WRITE;
/*!40000 ALTER TABLE `freelancer_assigned_crafts` DISABLE KEYS */;
/*!40000 ALTER TABLE `freelancer_assigned_crafts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `freelancer_shift_qualifications`
--

DROP TABLE IF EXISTS `freelancer_shift_qualifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `freelancer_shift_qualifications` (
                                                   `freelancer_id` bigint(20) unsigned NOT NULL,
                                                   `shift_qualification_id` bigint(20) unsigned NOT NULL,
                                                   `created_at` timestamp NULL DEFAULT NULL,
                                                   `updated_at` timestamp NULL DEFAULT NULL,
                                                   KEY `freelancer_shift_qualifications_freelancer_id_foreign` (`freelancer_id`),
                                                   KEY `freelancer_shift_qualifications_shift_qualification_id_foreign` (`shift_qualification_id`),
                                                   CONSTRAINT `freelancer_shift_qualifications_freelancer_id_foreign` FOREIGN KEY (`freelancer_id`) REFERENCES `freelancers` (`id`) ON DELETE CASCADE,
                                                   CONSTRAINT `freelancer_shift_qualifications_shift_qualification_id_foreign` FOREIGN KEY (`shift_qualification_id`) REFERENCES `shift_qualifications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `freelancer_shift_qualifications`
--

LOCK TABLES `freelancer_shift_qualifications` WRITE;
/*!40000 ALTER TABLE `freelancer_shift_qualifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `freelancer_shift_qualifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `freelancer_vacations`
--

DROP TABLE IF EXISTS `freelancer_vacations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `freelancer_vacations` (
                                        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                        `freelancer_id` bigint(20) unsigned NOT NULL,
                                        `from` date NOT NULL,
                                        `until` date NOT NULL,
                                        `created_at` timestamp NULL DEFAULT NULL,
                                        `updated_at` timestamp NULL DEFAULT NULL,
                                        PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `freelancer_vacations`
--

LOCK TABLES `freelancer_vacations` WRITE;
/*!40000 ALTER TABLE `freelancer_vacations` DISABLE KEYS */;
/*!40000 ALTER TABLE `freelancer_vacations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `freelancers`
--

DROP TABLE IF EXISTS `freelancers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `freelancers` (
                               `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                               `position` varchar(255) DEFAULT NULL,
                               `business` varchar(255) DEFAULT NULL,
                               `profile_image` varchar(255) DEFAULT NULL,
                               `first_name` varchar(255) NOT NULL DEFAULT 'Neuer',
                               `last_name` varchar(255) NOT NULL DEFAULT 'Freelancer',
                               `work_name` varchar(255) DEFAULT NULL,
                               `work_description` varchar(255) DEFAULT NULL,
                               `email` varchar(255) DEFAULT NULL,
                               `phone_number` varchar(255) DEFAULT NULL,
                               `street` varchar(255) DEFAULT NULL,
                               `zip_code` varchar(255) DEFAULT NULL,
                               `location` varchar(255) DEFAULT NULL,
                               `note` varchar(500) DEFAULT NULL,
                               `salary_per_hour` int(11) DEFAULT NULL,
                               `salary_description` longtext DEFAULT NULL,
                               `created_at` timestamp NULL DEFAULT NULL,
                               `updated_at` timestamp NULL DEFAULT NULL,
                               `can_work_shifts` tinyint(1) NOT NULL DEFAULT 0,
                               `crm_contact_id` bigint(20) unsigned DEFAULT NULL,
                               PRIMARY KEY (`id`),
                               KEY `freelancers_crm_contact_id_foreign` (`crm_contact_id`),
                               CONSTRAINT `freelancers_crm_contact_id_foreign` FOREIGN KEY (`crm_contact_id`) REFERENCES `crm_contacts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `freelancers`
--

LOCK TABLES `freelancers` WRITE;
/*!40000 ALTER TABLE `freelancers` DISABLE KEYS */;
/*!40000 ALTER TABLE `freelancers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `genre_project`
--

DROP TABLE IF EXISTS `genre_project`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `genre_project` (
                                 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                 `genre_id` bigint(20) unsigned NOT NULL,
                                 `is_main` tinyint(1) NOT NULL DEFAULT 0,
                                 `project_id` bigint(20) unsigned NOT NULL,
                                 `created_at` timestamp NULL DEFAULT NULL,
                                 `updated_at` timestamp NULL DEFAULT NULL,
                                 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `genre_project`
--

LOCK TABLES `genre_project` WRITE;
/*!40000 ALTER TABLE `genre_project` DISABLE KEYS */;
/*!40000 ALTER TABLE `genre_project` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `genres`
--

DROP TABLE IF EXISTS `genres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `genres` (
                          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                          `name` varchar(255) NOT NULL,
                          `color` varchar(255) DEFAULT NULL,
                          `created_at` timestamp NULL DEFAULT NULL,
                          `updated_at` timestamp NULL DEFAULT NULL,
                          `deleted_at` timestamp NULL DEFAULT NULL,
                          PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `genres`
--

LOCK TABLES `genres` WRITE;
/*!40000 ALTER TABLE `genres` DISABLE KEYS */;
/*!40000 ALTER TABLE `genres` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `global_notifications`
--

DROP TABLE IF EXISTS `global_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `global_notifications` (
                                        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                        `title` varchar(255) NOT NULL,
                                        `description` longtext NOT NULL,
                                        `image_name` varchar(255) DEFAULT NULL,
                                        `created_by` bigint(20) NOT NULL,
                                        `expiration_date` datetime DEFAULT NULL,
                                        `created_at` timestamp NULL DEFAULT NULL,
                                        `updated_at` timestamp NULL DEFAULT NULL,
                                        PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `global_notifications`
--

LOCK TABLES `global_notifications` WRITE;
/*!40000 ALTER TABLE `global_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `global_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `global_qualifiables`
--

DROP TABLE IF EXISTS `global_qualifiables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `global_qualifiables` (
                                       `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                       `global_qualification_id` bigint(20) unsigned NOT NULL,
                                       `qualifiable_id` bigint(20) unsigned NOT NULL,
                                       `qualifiable_type` varchar(255) NOT NULL,
                                       `created_at` timestamp NULL DEFAULT NULL,
                                       `updated_at` timestamp NULL DEFAULT NULL,
                                       PRIMARY KEY (`id`),
                                       UNIQUE KEY `gq_qualifiable_unique` (`global_qualification_id`,`qualifiable_id`,`qualifiable_type`),
                                       CONSTRAINT `fk_gq_qualification` FOREIGN KEY (`global_qualification_id`) REFERENCES `global_qualifications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `global_qualifiables`
--

LOCK TABLES `global_qualifiables` WRITE;
/*!40000 ALTER TABLE `global_qualifiables` DISABLE KEYS */;
/*!40000 ALTER TABLE `global_qualifiables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `global_qualifications`
--

DROP TABLE IF EXISTS `global_qualifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `global_qualifications` (
                                         `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                         `name` varchar(255) NOT NULL,
                                         `icon` varchar(255) NOT NULL,
                                         `created_at` timestamp NULL DEFAULT NULL,
                                         `updated_at` timestamp NULL DEFAULT NULL,
                                         PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `global_qualifications`
--

LOCK TABLES `global_qualifications` WRITE;
/*!40000 ALTER TABLE `global_qualifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `global_qualifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `holidays`
--

DROP TABLE IF EXISTS `holidays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `holidays` (
                            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                            `name` varchar(255) NOT NULL,
                            `date` date NOT NULL,
                            `end_date` date DEFAULT NULL,
                            `rota` int(11) DEFAULT NULL,
                            `country` varchar(255) DEFAULT NULL,
                            `remote_identifier` varchar(255) DEFAULT NULL,
                            `from_api` tinyint(1) NOT NULL DEFAULT 0,
                            `yearly` tinyint(1) NOT NULL DEFAULT 0,
                            `treatAsSpecialDay` tinyint(1) NOT NULL DEFAULT 1,
                            `color` varchar(255) NOT NULL DEFAULT '#333',
                            `created_at` timestamp NULL DEFAULT NULL,
                            `updated_at` timestamp NULL DEFAULT NULL,
                            PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `holidays`
--

LOCK TABLES `holidays` WRITE;
/*!40000 ALTER TABLE `holidays` DISABLE KEYS */;
/*!40000 ALTER TABLE `holidays` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `holidays_subdivisions`
--

DROP TABLE IF EXISTS `holidays_subdivisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `holidays_subdivisions` (
                                         `holiday_id` bigint(20) unsigned NOT NULL,
                                         `subdivision_id` bigint(20) unsigned NOT NULL,
                                         KEY `holidays_subdivisions_holiday_id_foreign` (`holiday_id`),
                                         KEY `holidays_subdivisions_subdivision_id_foreign` (`subdivision_id`),
                                         CONSTRAINT `holidays_subdivisions_holiday_id_foreign` FOREIGN KEY (`holiday_id`) REFERENCES `holidays` (`id`),
                                         CONSTRAINT `holidays_subdivisions_subdivision_id_foreign` FOREIGN KEY (`subdivision_id`) REFERENCES `subdivisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `holidays_subdivisions`
--

LOCK TABLES `holidays_subdivisions` WRITE;
/*!40000 ALTER TABLE `holidays_subdivisions` DISABLE KEYS */;
/*!40000 ALTER TABLE `holidays_subdivisions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `individual_time_series`
--

DROP TABLE IF EXISTS `individual_time_series`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `individual_time_series` (
                                          `uuid` char(36) NOT NULL,
                                          `title` varchar(255) DEFAULT NULL,
                                          `start_date` date NOT NULL,
                                          `end_date` date NOT NULL,
                                          `frequency` varchar(255) NOT NULL DEFAULT 'weekly',
                                          `interval` int(10) unsigned NOT NULL DEFAULT 1,
                                          `weekdays` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`weekdays`)),
                                          `created_by` bigint(20) unsigned DEFAULT NULL,
                                          `created_at` timestamp NULL DEFAULT NULL,
                                          `updated_at` timestamp NULL DEFAULT NULL,
                                          PRIMARY KEY (`uuid`),
                                          KEY `individual_time_series_created_by_foreign` (`created_by`),
                                          CONSTRAINT `individual_time_series_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `individual_time_series`
--

LOCK TABLES `individual_time_series` WRITE;
/*!40000 ALTER TABLE `individual_time_series` DISABLE KEYS */;
/*!40000 ALTER TABLE `individual_time_series` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `individual_times`
--

DROP TABLE IF EXISTS `individual_times`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `individual_times` (
                                    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                    `series_uuid` char(36) DEFAULT NULL,
                                    `timeable_type` varchar(255) NOT NULL,
                                    `timeable_id` bigint(20) unsigned NOT NULL,
                                    `title` varchar(255) DEFAULT NULL,
                                    `start_time` time DEFAULT NULL,
                                    `end_time` time DEFAULT NULL,
                                    `start_date` date NOT NULL,
                                    `end_date` date NOT NULL,
                                    `full_day` tinyint(1) NOT NULL DEFAULT 0,
                                    `working_time_minutes` int(11) DEFAULT NULL,
                                    `break_minutes` int(11) DEFAULT 0,
                                    `created_at` timestamp NULL DEFAULT NULL,
                                    `updated_at` timestamp NULL DEFAULT NULL,
                                    PRIMARY KEY (`id`),
                                    KEY `individual_times_timeable_type_timeable_id_index` (`timeable_type`,`timeable_id`),
                                    KEY `individual_times_timeable_id_timeable_type_index` (`timeable_id`,`timeable_type`),
                                    KEY `individual_times_series_uuid_index` (`series_uuid`),
                                    CONSTRAINT `individual_times_series_uuid_foreign` FOREIGN KEY (`series_uuid`) REFERENCES `individual_time_series` (`uuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `individual_times`
--

LOCK TABLES `individual_times` WRITE;
/*!40000 ALTER TABLE `individual_times` DISABLE KEYS */;
/*!40000 ALTER TABLE `individual_times` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `internal_issue_files`
--

DROP TABLE IF EXISTS `internal_issue_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `internal_issue_files` (
                                        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                        `internal_issue_id` bigint(20) unsigned NOT NULL,
                                        `file_path` varchar(255) NOT NULL,
                                        `original_name` varchar(255) NOT NULL,
                                        `created_at` timestamp NULL DEFAULT NULL,
                                        `updated_at` timestamp NULL DEFAULT NULL,
                                        PRIMARY KEY (`id`),
                                        KEY `internal_issue_files_internal_issue_id_foreign` (`internal_issue_id`),
                                        CONSTRAINT `internal_issue_files_internal_issue_id_foreign` FOREIGN KEY (`internal_issue_id`) REFERENCES `internal_issues` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `internal_issue_files`
--

LOCK TABLES `internal_issue_files` WRITE;
/*!40000 ALTER TABLE `internal_issue_files` DISABLE KEYS */;
/*!40000 ALTER TABLE `internal_issue_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `internal_issue_responsible_users`
--

DROP TABLE IF EXISTS `internal_issue_responsible_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `internal_issue_responsible_users` (
                                                    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                                    `internal_issue_id` bigint(20) unsigned NOT NULL,
                                                    `user_id` bigint(20) unsigned NOT NULL,
                                                    `created_at` timestamp NULL DEFAULT NULL,
                                                    `updated_at` timestamp NULL DEFAULT NULL,
                                                    PRIMARY KEY (`id`),
                                                    UNIQUE KEY `int_iss_res_user_unique` (`internal_issue_id`,`user_id`),
                                                    KEY `internal_issue_responsible_users_user_id_foreign` (`user_id`),
                                                    CONSTRAINT `internal_issue_responsible_users_internal_issue_id_foreign` FOREIGN KEY (`internal_issue_id`) REFERENCES `internal_issues` (`id`) ON DELETE CASCADE,
                                                    CONSTRAINT `internal_issue_responsible_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `internal_issue_responsible_users`
--

LOCK TABLES `internal_issue_responsible_users` WRITE;
/*!40000 ALTER TABLE `internal_issue_responsible_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `internal_issue_responsible_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `internal_issues`
--

DROP TABLE IF EXISTS `internal_issues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `internal_issues` (
                                   `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                   `name` varchar(255) NOT NULL,
                                   `project_id` bigint(20) unsigned DEFAULT NULL,
                                   `start_date` date NOT NULL,
                                   `start_time` time NOT NULL DEFAULT '00:00:00',
                                   `end_date` date NOT NULL,
                                   `end_time` time NOT NULL DEFAULT '23:59:00',
                                   `room_id` bigint(20) unsigned DEFAULT NULL,
                                   `notes` text DEFAULT NULL,
                                   `special_items_done` tinyint(1) NOT NULL DEFAULT 0,
                                   `created_at` timestamp NULL DEFAULT NULL,
                                   `updated_at` timestamp NULL DEFAULT NULL,
                                   PRIMARY KEY (`id`),
                                   KEY `internal_issues_project_id_foreign` (`project_id`),
                                   KEY `internal_issues_room_id_foreign` (`room_id`),
                                   CONSTRAINT `internal_issues_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
                                   CONSTRAINT `internal_issues_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `internal_issues`
--

LOCK TABLES `internal_issues` WRITE;
/*!40000 ALTER TABLE `internal_issues` DISABLE KEYS */;
/*!40000 ALTER TABLE `internal_issues` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_article_filter_presets`
--

DROP TABLE IF EXISTS `inventory_article_filter_presets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_article_filter_presets` (
                                                    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                                    `user_id` bigint(20) unsigned NOT NULL,
                                                    `inventory_category_id` bigint(20) unsigned DEFAULT NULL,
                                                    `inventory_sub_category_id` bigint(20) unsigned DEFAULT NULL,
                                                    `name` varchar(80) NOT NULL,
                                                    `is_default` tinyint(1) NOT NULL DEFAULT 0,
                                                    `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters`)),
                                                    `tag_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tag_ids`)),
                                                    `created_at` timestamp NULL DEFAULT NULL,
                                                    `updated_at` timestamp NULL DEFAULT NULL,
                                                    PRIMARY KEY (`id`),
                                                    KEY `inventory_article_filter_presets_user_id_index` (`user_id`),
                                                    KEY `inventory_article_filter_presets_is_default_index` (`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_article_filter_presets`
--

LOCK TABLES `inventory_article_filter_presets` WRITE;
/*!40000 ALTER TABLE `inventory_article_filter_presets` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_article_filter_presets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_article_filter_states`
--

DROP TABLE IF EXISTS `inventory_article_filter_states`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_article_filter_states` (
                                                   `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                                   `user_id` bigint(20) unsigned NOT NULL,
                                                   `inventory_category_id` bigint(20) unsigned DEFAULT NULL,
                                                   `inventory_sub_category_id` bigint(20) unsigned DEFAULT NULL,
                                                   `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters`)),
                                                   `tag_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tag_ids`)),
                                                   `created_at` timestamp NULL DEFAULT NULL,
                                                   `updated_at` timestamp NULL DEFAULT NULL,
                                                   PRIMARY KEY (`id`),
                                                   UNIQUE KEY `inv_article_filter_state_unique` (`user_id`,`inventory_category_id`,`inventory_sub_category_id`),
                                                   KEY `inventory_article_filter_states_user_id_index` (`user_id`),
                                                   KEY `inventory_article_filter_states_inventory_category_id_index` (`inventory_category_id`),
                                                   KEY `inventory_article_filter_states_inventory_sub_category_id_index` (`inventory_sub_category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_article_filter_states`
--

LOCK TABLES `inventory_article_filter_states` WRITE;
/*!40000 ALTER TABLE `inventory_article_filter_states` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_article_filter_states` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_article_images`
--

DROP TABLE IF EXISTS `inventory_article_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_article_images` (
                                            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                            `inventory_article_id` bigint(20) unsigned NOT NULL,
                                            `image` varchar(255) NOT NULL,
                                            `is_main_image` tinyint(1) NOT NULL DEFAULT 0,
                                            `order` int(10) unsigned NOT NULL DEFAULT 0,
                                            `created_at` timestamp NULL DEFAULT NULL,
                                            `updated_at` timestamp NULL DEFAULT NULL,
                                            `deleted_at` timestamp NULL DEFAULT NULL,
                                            PRIMARY KEY (`id`),
                                            KEY `inventory_article_images_inventory_article_id_foreign` (`inventory_article_id`),
                                            CONSTRAINT `inventory_article_images_inventory_article_id_foreign` FOREIGN KEY (`inventory_article_id`) REFERENCES `inventory_articles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_article_images`
--

LOCK TABLES `inventory_article_images` WRITE;
/*!40000 ALTER TABLE `inventory_article_images` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_article_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_article_inventory_tag`
--

DROP TABLE IF EXISTS `inventory_article_inventory_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_article_inventory_tag` (
                                                   `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                                   `inventory_article_id` bigint(20) unsigned NOT NULL,
                                                   `inventory_tag_id` bigint(20) unsigned NOT NULL,
                                                   `created_at` timestamp NULL DEFAULT NULL,
                                                   `updated_at` timestamp NULL DEFAULT NULL,
                                                   PRIMARY KEY (`id`),
                                                   UNIQUE KEY `inventory_article_tag_unique` (`inventory_article_id`,`inventory_tag_id`),
                                                   KEY `inventory_article_inventory_tag_inventory_tag_id_foreign` (`inventory_tag_id`),
                                                   CONSTRAINT `inventory_article_inventory_tag_inventory_article_id_foreign` FOREIGN KEY (`inventory_article_id`) REFERENCES `inventory_articles` (`id`) ON DELETE CASCADE,
                                                   CONSTRAINT `inventory_article_inventory_tag_inventory_tag_id_foreign` FOREIGN KEY (`inventory_tag_id`) REFERENCES `inventory_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_article_inventory_tag`
--

LOCK TABLES `inventory_article_inventory_tag` WRITE;
/*!40000 ALTER TABLE `inventory_article_inventory_tag` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_article_inventory_tag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_article_properties`
--

DROP TABLE IF EXISTS `inventory_article_properties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_article_properties` (
                                                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                                `name` varchar(255) NOT NULL,
                                                `tooltip_text` text DEFAULT NULL,
                                                `type` varchar(255) NOT NULL DEFAULT 'string',
                                                `select_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`select_values`)),
                                                `is_filterable` tinyint(1) NOT NULL DEFAULT 0,
                                                `show_in_list` tinyint(1) NOT NULL DEFAULT 0,
                                                `is_required` tinyint(1) NOT NULL DEFAULT 0,
                                                `is_deletable` tinyint(1) NOT NULL DEFAULT 1,
                                                `created_at` timestamp NULL DEFAULT NULL,
                                                `updated_at` timestamp NULL DEFAULT NULL,
                                                `across_articles` tinyint(1) NOT NULL DEFAULT 0,
                                                `individual_value` tinyint(1) NOT NULL DEFAULT 0,
                                                PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_article_properties`
--

LOCK TABLES `inventory_article_properties` WRITE;
/*!40000 ALTER TABLE `inventory_article_properties` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_article_properties` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_article_status_values`
--

DROP TABLE IF EXISTS `inventory_article_status_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_article_status_values` (
                                                   `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                                   `inventory_article_status_id` bigint(20) unsigned NOT NULL,
                                                   `inventory_article_id` bigint(20) unsigned NOT NULL,
                                                   `value` varchar(255) NOT NULL,
                                                   `created_at` timestamp NULL DEFAULT NULL,
                                                   `updated_at` timestamp NULL DEFAULT NULL,
                                                   PRIMARY KEY (`id`),
                                                   KEY `inventory_article_status_values_inventory_article_id_foreign` (`inventory_article_id`),
                                                   CONSTRAINT `inventory_article_status_values_inventory_article_id_foreign` FOREIGN KEY (`inventory_article_id`) REFERENCES `inventory_articles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_article_status_values`
--

LOCK TABLES `inventory_article_status_values` WRITE;
/*!40000 ALTER TABLE `inventory_article_status_values` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_article_status_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_article_statuses`
--

DROP TABLE IF EXISTS `inventory_article_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_article_statuses` (
                                              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                              `name` varchar(255) NOT NULL,
                                              `color` varchar(255) DEFAULT NULL COMMENT 'Color of the article status',
                                              `order` int(11) NOT NULL DEFAULT 1,
                                              `default` tinyint(1) NOT NULL DEFAULT 0,
                                              `deletable` tinyint(1) NOT NULL DEFAULT 1,
                                              `created_at` timestamp NULL DEFAULT NULL,
                                              `updated_at` timestamp NULL DEFAULT NULL,
                                              PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_article_statuses`
--

LOCK TABLES `inventory_article_statuses` WRITE;
/*!40000 ALTER TABLE `inventory_article_statuses` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_article_statuses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_articles`
--

DROP TABLE IF EXISTS `inventory_articles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_articles` (
                                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                      `name` varchar(255) NOT NULL,
                                      `description` varchar(255) DEFAULT NULL,
                                      `quantity` double(8,2) NOT NULL DEFAULT 0.00,
                                      `is_detailed_quantity` tinyint(1) NOT NULL DEFAULT 0,
                                      `inventory_category_id` bigint(20) unsigned NOT NULL,
                                      `inventory_sub_category_id` bigint(20) unsigned DEFAULT NULL,
                                      `created_at` timestamp NULL DEFAULT NULL,
                                      `updated_at` timestamp NULL DEFAULT NULL,
                                      `deleted_at` timestamp NULL DEFAULT NULL,
                                      PRIMARY KEY (`id`),
                                      KEY `inventory_articles_inventory_category_id_foreign` (`inventory_category_id`),
                                      KEY `inventory_articles_inventory_sub_category_id_foreign` (`inventory_sub_category_id`),
                                      CONSTRAINT `inventory_articles_inventory_category_id_foreign` FOREIGN KEY (`inventory_category_id`) REFERENCES `inventory_categories` (`id`) ON DELETE CASCADE,
                                      CONSTRAINT `inventory_articles_inventory_sub_category_id_foreign` FOREIGN KEY (`inventory_sub_category_id`) REFERENCES `inventory_sub_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_articles`
--

LOCK TABLES `inventory_articles` WRITE;
/*!40000 ALTER TABLE `inventory_articles` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_articles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_categories`
--

DROP TABLE IF EXISTS `inventory_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_categories` (
                                        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                        `name` varchar(255) NOT NULL,
                                        `created_at` timestamp NULL DEFAULT NULL,
                                        `updated_at` timestamp NULL DEFAULT NULL,
                                        PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_categories`
--

LOCK TABLES `inventory_categories` WRITE;
/*!40000 ALTER TABLE `inventory_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_category_property_values`
--

DROP TABLE IF EXISTS `inventory_category_property_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_category_property_values` (
                                                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                                      `inventory_article_property_id` bigint(20) unsigned NOT NULL,
                                                      `inventory_category_propertyable_type` varchar(255) NOT NULL,
                                                      `inventory_category_propertyable_id` bigint(20) unsigned NOT NULL,
                                                      `value` varchar(255) DEFAULT NULL,
                                                      `position` int(10) unsigned NOT NULL DEFAULT 0,
                                                      `created_at` timestamp NULL DEFAULT NULL,
                                                      `updated_at` timestamp NULL DEFAULT NULL,
                                                      PRIMARY KEY (`id`),
                                                      KEY `inv_cat_prop_morph_idx` (`inventory_category_propertyable_type`,`inventory_category_propertyable_id`),
                                                      KEY `inv_cat_prop_fk` (`inventory_article_property_id`),
                                                      CONSTRAINT `inv_cat_prop_fk` FOREIGN KEY (`inventory_article_property_id`) REFERENCES `inventory_article_properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_category_property_values`
--

LOCK TABLES `inventory_category_property_values` WRITE;
/*!40000 ALTER TABLE `inventory_category_property_values` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_category_property_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_detailed_quantity_articles`
--

DROP TABLE IF EXISTS `inventory_detailed_quantity_articles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_detailed_quantity_articles` (
                                                        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                                        `type_number` char(36) DEFAULT NULL,
                                                        `name` varchar(255) NOT NULL,
                                                        `description` varchar(255) DEFAULT NULL,
                                                        `quantity` double(8,2) NOT NULL DEFAULT 0.00,
                                                        `inventory_article_id` bigint(20) unsigned NOT NULL,
                                                        `created_at` timestamp NULL DEFAULT NULL,
                                                        `updated_at` timestamp NULL DEFAULT NULL,
                                                        `deleted_at` timestamp NULL DEFAULT NULL,
                                                        `inventory_article_status_id` bigint(20) unsigned DEFAULT NULL,
                                                        PRIMARY KEY (`id`),
                                                        UNIQUE KEY `inventory_detailed_quantity_articles_type_number_unique` (`type_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_detailed_quantity_articles`
--

LOCK TABLES `inventory_detailed_quantity_articles` WRITE;
/*!40000 ALTER TABLE `inventory_detailed_quantity_articles` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_detailed_quantity_articles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_management_user_filters`
--

DROP TABLE IF EXISTS `inventory_management_user_filters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_management_user_filters` (
                                                     `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                                     `user_id` bigint(20) unsigned NOT NULL,
                                                     `filter` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`filter`)),
                                                     PRIMARY KEY (`id`),
                                                     KEY `inventory_management_user_filters_user_id_foreign` (`user_id`),
                                                     CONSTRAINT `inventory_management_user_filters_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_management_user_filters`
--

LOCK TABLES `inventory_management_user_filters` WRITE;
/*!40000 ALTER TABLE `inventory_management_user_filters` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_management_user_filters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_property_values`
--

DROP TABLE IF EXISTS `inventory_property_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_property_values` (
                                             `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                             `inventory_article_property_id` bigint(20) unsigned NOT NULL,
                                             `inventory_propertyable_type` varchar(255) NOT NULL,
                                             `inventory_propertyable_id` bigint(20) unsigned NOT NULL,
                                             `value` varchar(255) NOT NULL,
                                             `created_at` timestamp NULL DEFAULT NULL,
                                             `updated_at` timestamp NULL DEFAULT NULL,
                                             PRIMARY KEY (`id`),
                                             KEY `inv_prop_val_morph_idx` (`inventory_propertyable_type`,`inventory_propertyable_id`),
                                             KEY `inv_art_prop_fk` (`inventory_article_property_id`),
                                             CONSTRAINT `inv_art_prop_fk` FOREIGN KEY (`inventory_article_property_id`) REFERENCES `inventory_article_properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_property_values`
--

LOCK TABLES `inventory_property_values` WRITE;
/*!40000 ALTER TABLE `inventory_property_values` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_property_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_sub_categories`
--

DROP TABLE IF EXISTS `inventory_sub_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_sub_categories` (
                                            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                            `name` varchar(255) NOT NULL,
                                            `inventory_category_id` bigint(20) unsigned NOT NULL,
                                            `created_at` timestamp NULL DEFAULT NULL,
                                            `updated_at` timestamp NULL DEFAULT NULL,
                                            PRIMARY KEY (`id`),
                                            KEY `inventory_sub_categories_inventory_category_id_foreign` (`inventory_category_id`),
                                            CONSTRAINT `inventory_sub_categories_inventory_category_id_foreign` FOREIGN KEY (`inventory_category_id`) REFERENCES `inventory_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_sub_categories`
--

LOCK TABLES `inventory_sub_categories` WRITE;
/*!40000 ALTER TABLE `inventory_sub_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_sub_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_tag_department`
--

DROP TABLE IF EXISTS `inventory_tag_department`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_tag_department` (
                                            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                            `inventory_tag_id` bigint(20) unsigned NOT NULL,
                                            `department_id` bigint(20) unsigned NOT NULL,
                                            `created_at` timestamp NULL DEFAULT NULL,
                                            `updated_at` timestamp NULL DEFAULT NULL,
                                            PRIMARY KEY (`id`),
                                            UNIQUE KEY `inventory_tag_department_unique` (`inventory_tag_id`,`department_id`),
                                            KEY `inventory_tag_department_department_id_foreign` (`department_id`),
                                            CONSTRAINT `inventory_tag_department_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
                                            CONSTRAINT `inventory_tag_department_inventory_tag_id_foreign` FOREIGN KEY (`inventory_tag_id`) REFERENCES `inventory_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_tag_department`
--

LOCK TABLES `inventory_tag_department` WRITE;
/*!40000 ALTER TABLE `inventory_tag_department` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_tag_department` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_tag_groups`
--

DROP TABLE IF EXISTS `inventory_tag_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_tag_groups` (
                                        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                        `name` varchar(255) NOT NULL,
                                        `position` int(10) unsigned NOT NULL DEFAULT 0,
                                        `created_at` timestamp NULL DEFAULT NULL,
                                        `updated_at` timestamp NULL DEFAULT NULL,
                                        PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_tag_groups`
--

LOCK TABLES `inventory_tag_groups` WRITE;
/*!40000 ALTER TABLE `inventory_tag_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_tag_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_tag_user`
--

DROP TABLE IF EXISTS `inventory_tag_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_tag_user` (
                                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                      `inventory_tag_id` bigint(20) unsigned NOT NULL,
                                      `user_id` bigint(20) unsigned NOT NULL,
                                      `created_at` timestamp NULL DEFAULT NULL,
                                      `updated_at` timestamp NULL DEFAULT NULL,
                                      PRIMARY KEY (`id`),
                                      UNIQUE KEY `inventory_tag_user_unique` (`inventory_tag_id`,`user_id`),
                                      KEY `inventory_tag_user_user_id_foreign` (`user_id`),
                                      CONSTRAINT `inventory_tag_user_inventory_tag_id_foreign` FOREIGN KEY (`inventory_tag_id`) REFERENCES `inventory_tags` (`id`) ON DELETE CASCADE,
                                      CONSTRAINT `inventory_tag_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_tag_user`
--

LOCK TABLES `inventory_tag_user` WRITE;
/*!40000 ALTER TABLE `inventory_tag_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_tag_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_tags`
--

DROP TABLE IF EXISTS `inventory_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_tags` (
                                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                  `name` varchar(255) NOT NULL,
                                  `color` varchar(7) NOT NULL DEFAULT '#000000',
                                  `has_restricted_permissions` tinyint(1) NOT NULL DEFAULT 0,
                                  `permission_mode` varchar(255) NOT NULL DEFAULT 'restricted_edit',
                                  `inventory_tag_group_id` bigint(20) unsigned DEFAULT NULL,
                                  `position` int(10) unsigned NOT NULL DEFAULT 0,
                                  `created_at` timestamp NULL DEFAULT NULL,
                                  `updated_at` timestamp NULL DEFAULT NULL,
                                  PRIMARY KEY (`id`),
                                  KEY `inventory_tags_inventory_tag_group_id_foreign` (`inventory_tag_group_id`),
                                  CONSTRAINT `inventory_tags_inventory_tag_group_id_foreign` FOREIGN KEY (`inventory_tag_group_id`) REFERENCES `inventory_tag_groups` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_tags`
--

LOCK TABLES `inventory_tags` WRITE;
/*!40000 ALTER TABLE `inventory_tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_user_filters`
--

DROP TABLE IF EXISTS `inventory_user_filters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_user_filters` (
                                          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                          `user_id` bigint(20) unsigned NOT NULL,
                                          `category_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`category_ids`)),
                                          `sub_category_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sub_category_ids`)),
                                          `property_filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`property_filters`)),
                                          `tag_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tag_ids`)),
                                          `created_at` timestamp NULL DEFAULT NULL,
                                          `updated_at` timestamp NULL DEFAULT NULL,
                                          PRIMARY KEY (`id`),
                                          KEY `inventory_user_filters_user_id_foreign` (`user_id`),
                                          CONSTRAINT `inventory_user_filters_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_user_filters`
--

LOCK TABLES `inventory_user_filters` WRITE;
/*!40000 ALTER TABLE `inventory_user_filters` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_user_filters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invitations`
--

DROP TABLE IF EXISTS `invitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invitations` (
                               `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                               `email` varchar(255) NOT NULL,
                               `token` varchar(255) NOT NULL,
                               `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`permissions`)),
                               `roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`roles`)),
                               `created_at` timestamp NULL DEFAULT NULL,
                               `updated_at` timestamp NULL DEFAULT NULL,
                               PRIMARY KEY (`id`),
                               UNIQUE KEY `invitations_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invitations`
--

LOCK TABLES `invitations` WRITE;
/*!40000 ALTER TABLE `invitations` DISABLE KEYS */;
/*!40000 ALTER TABLE `invitations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `issuable_inventory_article`
--

DROP TABLE IF EXISTS `issuable_inventory_article`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `issuable_inventory_article` (
                                              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                              `issuable_type` varchar(255) NOT NULL,
                                              `issuable_id` bigint(20) unsigned NOT NULL,
                                              `inventory_article_id` bigint(20) unsigned NOT NULL,
                                              `quantity` int(11) NOT NULL DEFAULT 1,
                                              `created_at` timestamp NULL DEFAULT NULL,
                                              `updated_at` timestamp NULL DEFAULT NULL,
                                              PRIMARY KEY (`id`),
                                              KEY `issuable_inventory_article_issuable_type_issuable_id_index` (`issuable_type`,`issuable_id`),
                                              KEY `issuable_inventory_article_inventory_article_id_foreign` (`inventory_article_id`),
                                              CONSTRAINT `issuable_inventory_article_inventory_article_id_foreign` FOREIGN KEY (`inventory_article_id`) REFERENCES `inventory_articles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `issuable_inventory_article`
--

LOCK TABLES `issuable_inventory_article` WRITE;
/*!40000 ALTER TABLE `issuable_inventory_article` DISABLE KEYS */;
/*!40000 ALTER TABLE `issuable_inventory_article` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
                        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                        `queue` varchar(255) NOT NULL,
                        `payload` longtext NOT NULL,
                        `attempts` tinyint(3) unsigned NOT NULL,
                        `reserved_at` int(10) unsigned DEFAULT NULL,
                        `available_at` int(10) unsigned NOT NULL,
                        `created_at` int(10) unsigned NOT NULL,
                        PRIMARY KEY (`id`),
                        KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `link_list_templates`
--

DROP TABLE IF EXISTS `link_list_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `link_list_templates` (
                                       `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                       `name` varchar(255) NOT NULL,
                                       `entries` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Array of {display: string} objects' CHECK (json_valid(`entries`)),
                                       `created_by` bigint(20) unsigned DEFAULT NULL,
                                       `created_at` timestamp NULL DEFAULT NULL,
                                       `updated_at` timestamp NULL DEFAULT NULL,
                                       PRIMARY KEY (`id`),
                                       KEY `link_list_templates_created_by_foreign` (`created_by`),
                                       CONSTRAINT `link_list_templates_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `link_list_templates`
--

LOCK TABLES `link_list_templates` WRITE;
/*!40000 ALTER TABLE `link_list_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `link_list_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `main_position_details`
--

DROP TABLE IF EXISTS `main_position_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `main_position_details` (
                                         `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                         `main_position_id` bigint(20) unsigned NOT NULL,
                                         `column_id` bigint(20) unsigned NOT NULL,
                                         `created_at` timestamp NULL DEFAULT NULL,
                                         `updated_at` timestamp NULL DEFAULT NULL,
                                         `deleted_at` timestamp NULL DEFAULT NULL,
                                         PRIMARY KEY (`id`),
                                         KEY `main_position_details_main_position_id_index` (`main_position_id`),
                                         KEY `main_position_details_column_id_index` (`column_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `main_position_details`
--

LOCK TABLES `main_position_details` WRITE;
/*!40000 ALTER TABLE `main_position_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `main_position_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `main_position_verifieds`
--

DROP TABLE IF EXISTS `main_position_verifieds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `main_position_verifieds` (
                                           `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                           `main_position_id` bigint(20) NOT NULL,
                                           `requested_by` int(11) NOT NULL,
                                           `requested` int(11) NOT NULL,
                                           `created_at` timestamp NULL DEFAULT NULL,
                                           `updated_at` timestamp NULL DEFAULT NULL,
                                           `deleted_at` timestamp NULL DEFAULT NULL,
                                           PRIMARY KEY (`id`),
                                           KEY `main_position_verifieds_main_position_id_index` (`main_position_id`),
                                           KEY `main_position_verifieds_requested_by_index` (`requested_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `main_position_verifieds`
--

LOCK TABLES `main_position_verifieds` WRITE;
/*!40000 ALTER TABLE `main_position_verifieds` DISABLE KEYS */;
/*!40000 ALTER TABLE `main_position_verifieds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `main_positions`
--

DROP TABLE IF EXISTS `main_positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `main_positions` (
                                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                  `table_id` bigint(20) NOT NULL,
                                  `type` enum('BUDGET_TYPE_COST','BUDGET_TYPE_EARNING') NOT NULL,
                                  `position` int(11) NOT NULL,
                                  `name` varchar(255) DEFAULT NULL,
                                  `is_verified` enum('BUDGET_VERIFIED_TYPE_NOT_VERIFIED','BUDGET_VERIFIED_TYPE_CLOSED','BUDGET_VERIFIED_TYPE_REQUESTED') NOT NULL DEFAULT 'BUDGET_VERIFIED_TYPE_NOT_VERIFIED',
                                  `created_at` timestamp NULL DEFAULT NULL,
                                  `updated_at` timestamp NULL DEFAULT NULL,
                                  `is_fixed` tinyint(1) NOT NULL DEFAULT 0,
                                  `deleted_at` timestamp NULL DEFAULT NULL,
                                  PRIMARY KEY (`id`),
                                  KEY `main_positions_table_id_index` (`table_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `main_positions`
--

LOCK TABLES `main_positions` WRITE;
/*!40000 ALTER TABLE `main_positions` DISABLE KEYS */;
/*!40000 ALTER TABLE `main_positions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `manufacturers`
--

DROP TABLE IF EXISTS `manufacturers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `manufacturers` (
                                 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                 `name` varchar(255) NOT NULL,
                                 `address` varchar(255) DEFAULT NULL,
                                 `website` varchar(255) DEFAULT NULL,
                                 `customer_number` varchar(255) DEFAULT NULL,
                                 `contact_person` varchar(255) DEFAULT NULL,
                                 `phone` varchar(255) DEFAULT NULL,
                                 `email` varchar(255) DEFAULT NULL,
                                 `created_at` timestamp NULL DEFAULT NULL,
                                 `updated_at` timestamp NULL DEFAULT NULL,
                                 `crm_contact_id` bigint(20) unsigned DEFAULT NULL,
                                 PRIMARY KEY (`id`),
                                 KEY `manufacturers_crm_contact_id_foreign` (`crm_contact_id`),
                                 CONSTRAINT `manufacturers_crm_contact_id_foreign` FOREIGN KEY (`crm_contact_id`) REFERENCES `crm_contacts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `manufacturers`
--

LOCK TABLES `manufacturers` WRITE;
/*!40000 ALTER TABLE `manufacturers` DISABLE KEYS */;
/*!40000 ALTER TABLE `manufacturers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `material_set_items`
--

DROP TABLE IF EXISTS `material_set_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `material_set_items` (
                                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                      `material_set_id` bigint(20) unsigned NOT NULL,
                                      `inventory_article_id` bigint(20) unsigned NOT NULL,
                                      `quantity` int(11) NOT NULL DEFAULT 1,
                                      `created_at` timestamp NULL DEFAULT NULL,
                                      `updated_at` timestamp NULL DEFAULT NULL,
                                      PRIMARY KEY (`id`),
                                      KEY `material_set_items_material_set_id_foreign` (`material_set_id`),
                                      KEY `material_set_items_inventory_article_id_foreign` (`inventory_article_id`),
                                      CONSTRAINT `material_set_items_inventory_article_id_foreign` FOREIGN KEY (`inventory_article_id`) REFERENCES `inventory_articles` (`id`) ON DELETE CASCADE,
                                      CONSTRAINT `material_set_items_material_set_id_foreign` FOREIGN KEY (`material_set_id`) REFERENCES `material_sets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `material_set_items`
--

LOCK TABLES `material_set_items` WRITE;
/*!40000 ALTER TABLE `material_set_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `material_set_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `material_sets`
--

DROP TABLE IF EXISTS `material_sets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `material_sets` (
                                 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                 `name` varchar(255) NOT NULL,
                                 `description` text DEFAULT NULL,
                                 `created_at` timestamp NULL DEFAULT NULL,
                                 `updated_at` timestamp NULL DEFAULT NULL,
                                 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `material_sets`
--

LOCK TABLES `material_sets` WRITE;
/*!40000 ALTER TABLE `material_sets` DISABLE KEYS */;
/*!40000 ALTER TABLE `material_sets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
                              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                              `migration` varchar(255) NOT NULL,
                              `batch` int(11) NOT NULL,
                              PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=622 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES
                             (1,'0000_00_00_000000_create_websockets_statistics_entries_table',1),
                             (2,'2014_10_12_000000_create_users_table',1),
                             (3,'2014_10_12_100000_create_password_resets_table',1),
                             (4,'2014_10_12_200000_add_two_factor_columns_to_users_table',1),
                             (5,'2019_08_19_000000_create_failed_jobs_table',1),
                             (6,'2019_12_14_000001_create_personal_access_tokens_table',1),
                             (7,'2022_03_22_103801_create_sessions_table',1),
                             (8,'2022_03_22_104445_create_permission_tables',1),
                             (9,'2022_03_23_104140_create_settings_table',1),
                             (11,'2022_03_23_115733_create_invitations_table',1),
                             (12,'2022_03_29_085525_create_departments_table',1),
                             (13,'2022_03_29_095812_create_department_user_table',1),
                             (14,'2022_03_30_153338_create_department_invitation',1),
                             (15,'2022_04_05_184122_create_projects_table',1),
                             (16,'2022_04_06_164912_create_project_user_table',1),
                             (17,'2022_04_06_164932_create_department_project_table',1),
                             (18,'2022_04_09_174131_create_checklists_table',1),
                             (19,'2022_04_09_174150_create_tasks_table',1),
                             (20,'2022_04_13_151419_create_sectors_table',1),
                             (21,'2022_04_13_152316_create_categories_table',1),
                             (22,'2022_04_13_152908_create_genres_table',1),
                             (23,'2022_04_13_154105_create_comments_table',1),
                             (24,'2022_04_20_123145_create_jobs_table',1),
                             (25,'2022_04_20_181011_create_checklist_templates_table',1),
                             (26,'2022_04_20_193835_create_task_templates_table',1),
                             (27,'2022_04_28_112434_create_project_files_table',1),
                             (28,'2022_05_01_165517_create_areas_table',1),
                             (29,'2022_05_01_165853_create_rooms_table',1),
                             (30,'2022_05_01_174257_create_room_user_table',1),
                             (31,'2022_05_05_115856_create_project_histories_table',1),
                             (32,'2022_05_07_160829_create_room_files_table',1),
                             (33,'2022_06_02_170244_create_event_migration',1),
                             (34,'2022_06_02_170256_create_event_type_migration',1),
                             (35,'2022_07_14_145114_add_name_to_permissions_table',1),
                             (36,'2022_07_21_143417_add_name_to_roles_table',1),
                             (37,'2022_09_26_150150_create_room_categories_table',1),
                             (38,'2022_09_26_150242_create_room_attributes_table',1),
                             (39,'2022_09_28_174633_create_room_pivot_room_attribute_table',1),
                             (40,'2022_09_28_213800_create_room_room_category_table',1),
                             (41,'2022_09_28_230136_create_room_room_attribute_table',1),
                             (42,'2022_09_28_232916_create_adjoining_room_main_room_table',1),
                             (43,'2022_10_05_152226_create_category_project_table',1),
                             (44,'2022_10_05_152542_create_genre_project_table',1),
                             (45,'2022_10_05_152641_create_project_sector_table',1),
                             (46,'2022_10_06_143130_create_filters_table',1),
                             (47,'2022_10_06_143858_create_filter_room_category_table',1),
                             (48,'2022_10_06_143910_create_filter_room_attribute_table',1),
                             (49,'2022_10_06_143921_create_filter_room_table',1),
                             (50,'2022_10_06_143932_create_area_filter_table',1),
                             (51,'2022_10_06_151902_create_event_type_filter_table',1),
                             (52,'2022_11_03_151559_create_notifications_table',1),
                             (53,'2022_11_04_144728_create_schedulings_table',1),
                             (54,'2022_11_12_095744_create_user_notification_settings_table',1),
                             (55,'2022_11_17_092805_create_model_changes_history_table',1),
                             (56,'2022_11_23_111840_create_global_notifications_table',1),
                             (57,'2022_12_01_141757_create_contracts_table',1),
                             (58,'2022_12_02_093524_create_money_sources_table',1),
                             (59,'2022_12_02_143629_create_contract_modules_table',1),
                             (60,'2022_12_06_100430_create_money_source_tasks_table',1),
                             (61,'2022_12_06_102416_money_source_task_user',1),
                             (62,'2022_12_12_101513_add_is_group_to_projects',1),
                             (63,'2022_12_12_101612_create_project_groups_table',1),
                             (64,'2022_12_12_122033_create_main_positions_table',1),
                             (65,'2022_12_12_122039_create_sub_positions_table',1),
                             (66,'2022_12_12_122046_create_columns_table',1),
                             (67,'2022_12_12_122054_create_subposition_rows_table',1),
                             (68,'2022_12_12_122111_create_subposition_row_column_table',1),
                             (69,'2023_01_03_121754_add_done_column',1),
                             (70,'2023_01_04_134505_create_cell_comments_table',1),
                             (71,'2023_01_08_211900_create_cost_centers_table',1),
                             (72,'2023_01_08_221621_create_money_source_project_table',1),
                             (73,'2023_01_10_131058_create_main_position_verifieds_table',1),
                             (74,'2023_01_10_131124_create_sub_position_verifieds_table',1),
                             (75,'2023_01_12_112713_contract_users',1),
                             (76,'2023_01_16_131040_create_cell_calculations_table',1),
                             (77,'2023_01_24_110810_add_is_locked_to_column',1),
                             (78,'2023_01_24_113233_add_is_fixed_to_main_sub_positions',1),
                             (79,'2023_01_25_124932_create_row_comments_table',1),
                             (80,'2023_01_26_095821_create_tables_table',1),
                             (81,'2023_01_27_081831_create_task_user_table',1),
                             (82,'2023_02_09_133807_create_contract_types_table',1),
                             (83,'2023_02_09_133816_create_company_types_table',1),
                             (84,'2023_02_09_133832_create_collecting_societies_table',1),
                             (85,'2023_02_10_102233_create_project_file_user_table',1),
                             (86,'2023_02_13_112448_create_money_source_files_table',1),
                             (87,'2023_02_21_122859_create_currencies_table',1),
                             (88,'2023_02_28_105026_money_source_users',1),
                             (89,'2023_02_28_201544_create_sum_comments_table',1),
                             (90,'2023_02_28_211619_create_subposition_sum_details_table',1),
                             (91,'2023_03_01_232445_create_main_position_details_table',1),
                             (92,'2023_03_02_001127_create_budget_sum_details_table',1),
                             (93,'2023_03_02_111024_task_template_user',1),
                             (94,'2023_03_02_163733_create_project_headlines_table',1),
                             (95,'2023_03_02_164201_create_project_project_headlines_table',1),
                             (96,'2023_03_05_121130_create_project_states_table',1),
                             (97,'2023_03_05_123414_checklist_user',1),
                             (98,'2023_03_05_123738_add_state_to_project',1),
                             (99,'2023_03_06_094500_checklist_template_user',1),
                             (100,'2023_03_08_175507_create_sum_money_sources_table',1),
                             (101,'2023_03_20_171307_create_sub_events_table',1),
                             (102,'2023_03_27_173407_create_user_calendar_settings_table',1),
                             (103,'2023_03_28_132454_create_series_events_table',1),
                             (104,'2023_04_19_143802_create_event_comments_table',1),
                             (105,'2023_04_19_144453_event_acception_system',1),
                             (106,'2023_05_17_084910_create_freelancers_table',1),
                             (107,'2023_05_19_111757_create_service_providers_table',1),
                             (108,'2023_05_21_130403_create_service_provider_contacts_table',1),
                             (109,'2023_05_23_111659_create_user_vacations_table',1),
                             (110,'2023_05_30_140454_create_crafts_table',1),
                             (111,'2023_05_30_141014_craft_users',1),
                             (112,'2023_05_31_113905_create_time_lines_table',1),
                             (113,'2023_05_31_114242_create_shifts_table',1),
                             (114,'2023_06_01_103851_create_project_shift_relevant_event_types_table',1),
                             (115,'2023_06_01_105354_project_shift_contacts',1),
                             (116,'2023_06_02_090101_shift_user',1),
                             (117,'2023_06_08_170152_create_shift_filters_table',1),
                             (118,'2023_06_08_171937_create_room_shift_filter_table',1),
                             (119,'2023_06_08_172127_create_event_type_shift_filter_table',1),
                             (120,'2023_06_13_111619_shifts_service_providers',1),
                             (121,'2023_06_13_111626_shifts_freelancers',1),
                             (122,'2023_07_19_103021_create_shift_presets_table',1),
                             (123,'2023_07_20_104318_create_preset_time_lines_table',1),
                             (124,'2023_07_20_104335_create_preset_shifts_table',1),
                             (125,'2023_08_02_123358_create_freelancer_vacations_table',1),
                             (126,'2023_08_28_143719_add_has_column_locked',1),
                             (127,'2023_10_20_111807_create_user_calendar_filters_table',1),
                             (128,'2023_10_25_095803_create_user_shift_calendar_filters_table',1),
                             (129,'2023_11_01_123650_add_date_values_to_user_filter',1),
                             (130,'2023_11_01_123740_add_date_values_to_user_shift_filter',1),
                             (131,'2023_11_13_153110_create_user_commented_budget_items_settings_table',1),
                             (132,'2023_11_14_133926_add_commented_column_to_columns_table',1),
                             (133,'2023_11_14_160205_add_position_to_cell_calculation',1),
                             (134,'2023_11_27_151632_add_budget_deadline_to_project_table',1),
                             (135,'2023_12_01_135600_vacation_morph',1),
                             (136,'2023_12_01_135605_migrate_vacation_data',1),
                             (137,'2023_12_05_164330_create_users_assigned_crafts_table',1),
                             (138,'2023_12_06_155259_create_freelancer_assigned_crafts_table',1),
                             (139,'2023_12_06_155405_create_service_provider_assigned_crafts_table',1),
                             (140,'2023_12_06_161018_add_can_work_shifts_column_to_freelancers_table',1),
                             (141,'2023_12_06_161115_add_can_work_shifts_column_to_service_providers_table',1),
                             (142,'2023_12_10_164051_create_money_source_user_pinned',1),
                             (143,'2023_12_14_110905_create_money_source_categories_table',1),
                             (144,'2023_12_14_145837_create_money_source_category_mapping_table',1),
                             (145,'2023_12_15_140632_drop_room_pivot_room_attribute_table',1),
                             (146,'2023_12_18_155832_create_money_source_reminders_table',1),
                             (147,'2024_01_10_102543_add_tooltip_text_to_roles_table',1),
                             (148,'2024_01_10_171430_create_availabilities_table',1),
                             (149,'2024_01_11_112207_create_availability_series_table',1),
                             (150,'2024_01_11_142855_create_vacation_series_table',1),
                             (151,'2024_01_15_125139_create_permission_presets_table',1),
                             (152,'2024_01_17_124826_add_business_email_to_settings_table',1),
                             (153,'2024_01_17_174819_add_pinned_by_user',1),
                             (154,'2024_01_18_094505_change_creator_id_foreign_key_of_contracts',1),
                             (155,'2024_01_19_190741_create_vacation_conflicts_table',1),
                             (156,'2024_01_19_190750_create_availabilities_conflicts_table',1),
                             (157,'2024_01_22_145914_create_shift_qualifications_table',1),
                             (158,'2024_01_23_110431_addcommitting_user_id',1),
                             (159,'2024_01_23_112349_create_user_shift_qualifications_table',1),
                             (160,'2024_01_23_112403_create_freelancer_shift_qualifications_table',1),
                             (161,'2024_01_23_112418_create_service_provider_shift_qualifications_table',1),
                             (162,'2024_01_23_144516_remove_can_master_from_users_table',1),
                             (163,'2024_01_23_144626_remove_can_master_from_freelancers_table',1),
                             (164,'2024_01_23_144654_remove_can_master_from_service_providers_table',1),
                             (165,'2024_01_24_121210_create_shifts_qualifications_table',1),
                             (166,'2024_01_24_165618_add_shift_qualification_id_to_shift_user_table',1),
                             (167,'2024_01_24_165639_drop_is_master_column_from_shift_user_table',1),
                             (168,'2024_01_25_111250_drop_number_employees_from_shift_table',1),
                             (169,'2024_01_25_111312_drop_number_masters_from_shift_table',1),
                             (170,'2024_01_26_125022_add_shift_qualification_id_to_shift_freelancer_table',1),
                             (171,'2024_01_26_125036_add_shift_qualification_id_to_shift_service_provider_table',1),
                             (172,'2024_01_26_125256_drop_is_master_column_from_shift_freelancer_table',1),
                             (173,'2024_01_26_125405_drop_is_master_column_from_shift_service_provider_table',1),
                             (174,'2024_01_31_105414_drop_shift_count_column_in_shift_user_table',1),
                             (175,'2024_01_31_105645_add_shift_count_column_in_shift_user_table',1),
                             (176,'2024_01_31_105809_drop_shift_count_column_in_shifts_freelancers_table',1),
                             (177,'2024_01_31_105901_add_shift_count_column_in_shifts_freelancers_table',1),
                             (178,'2024_01_31_105959_drop_shift_count_column_in_shifts_service_providers_table',1),
                             (179,'2024_01_31_110013_add_shift_count_column_in_shifts_service_providers_table',1),
                             (180,'2024_01_31_174631_create_preset_shift_shifts_qualifications',1),
                             (181,'2024_01_31_175401_drop_number_employees_from_preset_shifts',1),
                             (182,'2024_01_31_175426_drop_number_masters_from_preset_shifts',1),
                             (183,'2024_02_01_174221_add_fields_to_project',1),
                             (184,'2024_02_02_124649_rename_preset_timelines_to_shift_preset_timelines',1),
                             (185,'2024_02_02_140227_add_foreign_key_cascade_on_delete_to_shift_preset_timelines_table',1),
                             (186,'2024_02_02_140719_add_foreign_key_cascade_on_delete_to_preset_shifts_table',1),
                             (187,'2024_02_02_152022_rename_time_lines_table',1),
                             (188,'2024_02_08_081845_create_sage_not_assigned_data_table',1),
                             (189,'2024_02_13_092930_create_sage_api_settings_table',1),
                             (190,'2024_02_15_134411_add_language_to_user',1),
                             (191,'2024_02_15_134838_create_sage_assigned_data_table',1),
                             (192,'2024_02_20_104815_create_sage_assigned_data_comments_table',1),
                             (193,'2024_02_26_111815_add_deleted_at_to_sage_not_assigned_data_table',1),
                             (194,'2024_03_04_132452_add_deleted_at_to_event_comments_table',1),
                             (195,'2024_03_04_134011_add_deleted_at_to_timelines_table',1),
                             (196,'2024_03_04_134433_add_deleted_at_to_shifts_table',1),
                             (197,'2024_03_04_140422_budget_soft_delete',1),
                             (198,'2024_03_04_140534_comments_soft_delete',1),
                             (199,'2024_03_04_141529_tasks_soft_delete',1),
                             (200,'2024_03_04_145643_add_deleted_at_to_shift_user_table',1),
                             (201,'2024_03_04_145649_add_deleted_at_to_shifts_freelancers_table',1),
                             (202,'2024_03_04_145656_add_deleted_at_to_shifts_service_providers_table',1),
                             (203,'2024_03_04_145957_add_deleted_at_to_shifts_qualifications_table',1),
                             (204,'2024_03_04_161740_checklist_soft_delete',1),
                             (205,'2024_03_04_164745_project_history_soft_delete',1),
                             (206,'2024_03_04_165031_project_shift_contacts_soft_delete',1),
                             (207,'2024_03_04_165657_main_positions_soft_delete',1),
                             (208,'2024_03_04_165716_sub_positions_soft_delete',1),
                             (209,'2024_03_04_165803_columns_soft_delete',1),
                             (210,'2024_03_04_165828_sub_position_rows_soft_delete',1),
                             (211,'2024_03_04_165846_column_sub_position_row_soft_delete',1),
                             (212,'2024_03_04_165905_cell_comments_soft_delete',1),
                             (213,'2024_03_04_165923_main_position_verifieds_soft_delete',1),
                             (214,'2024_03_04_170004_sub_position_verifieds_soft_delete',1),
                             (215,'2024_03_04_170057_contract_user_soft_delete',1),
                             (216,'2024_03_04_170130_cell_calculations_soft_delete',1),
                             (217,'2024_03_04_170155_row_comments_soft_delete',1),
                             (218,'2024_03_04_170219_sum_comments_soft_delete',1),
                             (219,'2024_03_04_170239_subposition_sum_details_soft_delete',1),
                             (220,'2024_03_04_170353_main_position_details_soft_delete',1),
                             (221,'2024_03_04_170416_budget_sum_details_soft_delete',1),
                             (222,'2024_03_04_170445_project_headlines_soft_delete',1),
                             (223,'2024_03_04_172714_contracts_soft_delete',1),
                             (224,'2024_03_04_172954_sum_money_sources_soft_delete',1),
                             (225,'2024_03_05_113659_create_budget_column_settings_table',1),
                             (226,'2024_03_05_161025_project_shift_relevant_event_types_soft_delete',1),
                             (227,'2024_03_05_162814_money_source_project_soft_delete',1),
                             (228,'2024_03_06_142303_add_account_management_global_to_settings_table',1),
                             (229,'2024_03_06_164125_create_budget_management_accounts_table',1),
                             (230,'2024_03_06_164131_create_budget_management_cost_units_table',1),
                             (231,'2024_03_08_110920_change_event_types_hex',1),
                             (232,'2024_03_16_221427_role_and_permission_upgrade',1),
                             (233,'2024_03_18_151535_add_translation_keys_to_permissions',1),
                             (234,'2024_03_18_151742_add_translation_keys_to_roles',1),
                             (235,'2024_03_19_090805_add_order_to_rooms',1),
                             (236,'2024_03_20_142144_create_day_services_table',1),
                             (237,'2024_03_22_111128_add_start_end_date_to_shifts',1),
                             (238,'2024_03_25_172059_create_components_table',1),
                             (239,'2024_03_25_172740_create_project_tabs_table',1),
                             (240,'2024_03_26_095010_create_component_in_tabs_table',1),
                             (241,'2024_03_26_095014_create_project_component_values_table',1),
                             (242,'2024_04_03_084428_remove_registration_and_entrance_columns_from_projects_table',1),
                             (243,'2024_04_04_115748_create_project_tab_sidebar_tabs_table',1),
                             (244,'2024_04_04_115851_create_sidebar_tab_components_table',1),
                             (245,'2024_04_05_135855_add_sidebar_opend_to_user',1),
                             (246,'2024_04_09_143039_add_tab_id_to_checklists',1),
                             (247,'2024_04_09_153324_add_tab_id_to_comments',1),
                             (248,'2024_04_09_155349_create_component_user_table',1),
                             (249,'2024_04_09_155356_create_component_department_table',1),
                             (250,'2024_04_10_155618_add_tab_id_to_project_files',1),
                             (251,'2024_04_11_130738_remove_project_description',1),
                             (252,'2024_04_11_132433_drop_project_headlines_and_project_project_headlines_table',1),
                             (253,'2024_04_11_133732_add_zoom_factor_to_user',1),
                             (254,'2024_04_16_110629_add_is_account_for_revenue_to_budget_management_accounts_table',1),
                             (255,'2024_04_18_092631_drop_project_histories_table',1),
                             (256,'2024_04_22_144319_remove_nullable_from_event_types_table_hex_code_column',1),
                             (257,'2024_04_24_183202_update_model_type_columns',1),
                             (258,'2024_04_29_145519_add_color_to_project_settings',1),
                             (260,'2024_05_14_123613_add_color_to_crafts',1),
                             (261,'2024_05_14_133202_add_days_to_notify_craft_if_shift_is_not_full',1),
                             (262,'2024_05_14_151558_add_compact_mode-to_user',1),
                             (263,'2024_05_14_160801_create_project_roles_table',1),
                             (264,'2024_05_15_094437_add_project_roles_to_project_user',1),
                             (265,'2024_05_15_151235_add_dates_to_timeline',1),
                             (266,'2024_05_15_155844_add_earliest_and_lastest_dates_to_event',1),
                             (267,'2024_05_16_091643_add_date_to_shift_preset_timeline',1),
                             (268,'2024_05_16_141955_add_show_crafts_to_user',1),
                             (269,'2024_05_20_214705_add_default_room_flag',1),
                             (270,'2024_05_21_142310_add_selected_stepper_to_user',1),
                             (271,'2024_05_21_161755_create_user_shift_calendar_abos_table',1),
                             (272,'2024_05_23_153525_create_user_calendar_abos_table',1),
                             (273,'2024_05_27_014010_default_event_type',1),
                             (274,'2024_05_27_110756_change_comment_text_lenght',1),
                             (275,'2024_05_28_113603_set_calendar_abo_id_to_null',1),
                             (276,'2024_05_30_153410_create_shift_time_presets_table',1),
                             (277,'2024_06_05_145110_create_day_serviceables_table',1),
                             (278,'2024_06_06_091610_set_indexes',1),
                             (280,'2024_06_19_231210_create_crafts_inventory_columns_table',1),
                             (281,'2024_06_19_231218_create_inventory_categories_table',1),
                             (282,'2024_06_19_231804_create_craft_inventory_groups_table',1),
                             (283,'2024_06_19_231819_create_craft_inventory_items_table',1),
                             (284,'2024_06_19_231833_create_craft_inventory_item_cells_table',1),
                             (285,'2024_06_25_115435_create_craft_inventory_item_events_table',1),
                             (286,'2024_06_25_183020_change_timeline_date_and_time_columns_not_nullable',1),
                             (287,'2024_06_26_191510_create_user_inventory_filters_table',1),
                             (288,'2024_07_04_092435_add_checklist_style_to_user',1),
                             (289,'2024_07_10_091204_add_relevant_for_inventory_in_event_type',1),
                             (290,'2024_07_15_113807_add_at_a_glance_to_user',1),
                             (291,'2024_07_21_235923_revert_foreign_keys_on_subpositions',1),
                             (292,'2024_08_06_160842_remove_project_id_form_checklist',1),
                             (293,'2024_08_08_121449_add_private_value_to_checklists',1),
                             (294,'2024_08_12_162237_add_creator_to_project',1),
                             (295,'2024_08_12_201359_add_invitation_email_to_general_settings_table',1),
                             (296,'2024_08_14_092617_add_bulk_edit_enum_to_components',1),
                             (297,'2024_08_14_203810_add_sent_in_summary_notification_flag_to_notifications_table',1),
                             (298,'2024_08_19_111739_add_bulk_event_create_component_if_not_exists',1),
                             (299,'2024_08_19_190917_add_notification_enums_last_sent_to_user_table',1),
                             (300,'2024_08_19_202541_set_user_notification_frequency_settings_to_daily_instead_of_immediately',1),
                             (302,'2024_08_28_204239_add_description_and_time_period_project_id_and_use_project_time_period_columns_to_user_calendar_settings',1),
                             (303,'2024_09_01_155245_add_user_project_management_settings_table',1),
                             (304,'2024_09_04_115226_add_bulk_sort_id_to_user',1),
                             (305,'2024_09_04_131042_reset_indexes_from_2024_06_06_091610',1),
                             (306,'2024_09_04_152512_recreate_foreign_keys',1),
                             (307,'2024_09_05_115228_create_user_project_management_settings_defaults',1),
                             (308,'2024_09_13_105500_add_event_name_to_user_calendar_settings_table',1),
                             (309,'2024_09_13_114012_add_show_notification_indicator_column_to_users_table',1),
                             (310,'2024_09_13_121436_add_sent_deadline_notification_today_to_tasks_table',1),
                             (311,'2024_09_13_162128_create_user_user_management_settings_defaults',1),
                             (312,'2024_09_19_132618_add_user_worker_calendar_filters_table',1),
                             (313,'2024_09_26_151017_add_high_contrast_to_user_calendar_settings',1),
                             (314,'2024_10_04_095516_add_is_freelancer_to_user',1),
                             (315,'2024_10_04_145523_add_universally_applicable_boolean_to_craft',1),
                             (316,'2024_10_06_225653_set_idx_on_mch',1),
                             (317,'2024_10_07_034738_add_fk_to_shift_users',1),
                             (318,'2024_10_07_151113_add_craft_craft_abbreviation_to_shift_users',1),
                             (319,'2024_10_09_135824_add_type_to_vacations',1),
                             (320,'2024_10_10_111826_create_individual_times_table',1),
                             (321,'2024_10_15_153825_create_shift_plan_comments_table',1),
                             (322,'2024_10_16_073818_holidays',1),
                             (323,'2024_10_17_150257_add_enums_to_shift_plan_user_sort_by',1),
                             (324,'2024_10_18_121723_add_sort_id_for_shiftplan_component',1),
                             (325,'2024_10_18_151518_update_shiftplan_user_sort_by_new_column',1),
                             (326,'2024_10_21_064828_fill_subdivisions',1),
                             (327,'2024_10_21_100901_add_drawer_height_value_to_user',1),
                             (328,'2024_10_23_161924_craftable',1),
                             (329,'2024_10_25_150013_add_all_craft_user_to_new_table',1),
                             (330,'2024_10_28_141634_add_expand_days_to_user_calendar_settings',1),
                             (331,'2024_10_30_150946_add_position_to_crafts',1),
                             (332,'2024_11_06_154456_add_color_to_holiday',1),
                             (334,'2024_11_11_113708_add_yearly-boolean_to_holiday',1),
                             (335,'2024_11_13_155111_add_sort_by_column_in_inventory',1),
                             (336,'2024_11_14_143229_add_checklist_filter_for_checklist',1),
                             (337,'2024_11_08_145927_add_craft_managers_table',2),
                             (338,'2024_11_15_144339_add_deletable_to_inventory_column',2),
                             (339,'2024_11_17_031036_add_file_types_to_settings',2),
                             (340,'2024_11_18_105313_create_craft_inventory_group_folders_table',2),
                             (341,'2024_11_19_101959_add_folder_id_to_inventory_item',2),
                             (342,'2024_11_19_105251_make_group_id_nullable_inventory_item',2),
                             (343,'2024_11_20_113923_add_is_developer_to_users_table',2),
                             (344,'2024_11_20_171135_craft_users_inventory',2),
                             (345,'2024_11_20_171817_inventory_planned_by_all_to_crafts',2),
                             (347,'2024_11_21_102623_create_event_statuses_table',2),
                             (348,'2024_11_21_135218_add_use_event_status_color_to_user_calendar_settings',2),
                             (349,'2024_11_21_163820_add_columns_to_sage_assigned_data_and_sage_not_assigned_data_and_make_kreditor_optional',2),
                             (350,'2024_11_22_145217_add_artist_residencies_enum_to_components',2),
                             (351,'2024_11_22_164455_create_artist_residencies_table',2),
                             (353,'2024_11_22_224418_add_type_to_service_provider',2),
                             (354,'2024_11_23_165534_add_show_qualifications_to_user',3),
                             (356,'2024_11_25_150451_add_artists_to_project_table',3),
                             (357,'2024_11_25_155711_add_project_artists_to_user_calendar_settings',3),
                             (358,'2024_11_28_150410_add_new_fields_to_users',3),
                             (359,'2024_12_02_063640_add_filesize_to_settings',3),
                             (360,'2024_12_03_150833_add-contract_file_size_and_mine_type',3),
                             (361,'2024_12_06_222859_update_shift_timeline_preset',3),
                             (362,'2024_12_09_102926_add_shift_timeline_preset_name',3),
                             (363,'2024_12_09_112038_create_preset_timeline_times_table',3),
                             (364,'2024_12_10_110619_add_column_position_to_columns_table',3),
                             (365,'2024_12_10_113428_add_table_table_column_order',3),
                             (366,'2024_12_18_112615_add_and_remove_values_form_shift_table',4),
                             (367,'2025_01_11_201052_add_setting_options_for_shift_plan',4),
                             (368,'2025_01_12_203502_add_from_to_in_timelines',4),
                             (369,'2025_01_13_145631_create_project_management_builders_table',5),
                             (370,'2025_01_21_203011_add_daily_view_to_user',5),
                             (371,'2025_01_28_161607_change_weekly_working_hours_to_float',5),
                             (372,'2025_01_22_114217_add_event_properties_table',6),
                             (373,'2025_01_22_115059_add_event_properties_column_to_user_calendar_filters',6),
                             (374,'2025_01_23_110615_add_event_properties_to_filters_table',6),
                             (375,'2025_01_23_110651_add_event_event_property_table',6),
                             (376,'2025_01_24_101718_make_event_audience_and_is_loud_nullable',6),
                             (377,'2025_01_31_212908_event_property_sub_event',6),
                             (378,'2025_02_03_115519_create_project_print_layouts_table',6),
                             (379,'2025_02_03_212940_create_print_layout_components_table',6),
                             (380,'2025_02_07_134749_add_default_to_project_tabs',6),
                             (381,'2025_02_10_191314_add_hide_unoccupied_rooms',6),
                             (382,'2025_02_10_203811_add_default_to_event_statuses',6),
                             (384,'2025_02_18_172709_add_last_project_id_in_user',7),
                             (385,'2025_02_18_220048_add_entities_per_page_to_user',7),
                             (386,'2025_02_22_175103_add_column_size_for_event_plan',7),
                             (387,'2025_02_25_195817_add_color_to_project',8),
                             (388,'2025_02_25_202334_add_project_group_display',8),
                             (389,'2025_02_27_110453_update_project_budget_for_project_groups',8),
                             (390,'2025_03_03_112156_add_relevant_for_project_period',8),
                             (391,'2025_03_03_120925_add_new_component_types_for_project_groups',8),
                             (392,'2025_03_13_211758_add_note_to_component',8),
                             (393,'2025_03_14_133227_add_type_for_disclosure',8),
                             (394,'2025_03_14_133537_create_disclosure_components_table',8),
                             (395,'2016_06_01_000001_create_oauth_auth_codes_table',9),
                             (396,'2016_06_01_000002_create_oauth_access_tokens_table',9),
                             (397,'2016_06_01_000003_create_oauth_refresh_tokens_table',9),
                             (398,'2016_06_01_000004_create_oauth_clients_table',9),
                             (399,'2016_06_01_000005_create_oauth_personal_access_clients_table',9),
                             (400,'2025_03_15_211655_create_inventory_categories_table',9),
                             (401,'2025_03_15_213546_create_inventory_sub_categories_table',9),
                             (402,'2025_03_15_232013_create_inventory_articles_table',9),
                             (403,'2025_03_17_103010_create_inventory_detailed_quantity_articles_table',9),
                             (404,'2025_03_17_103153_create_inventory_article_properties_table',9),
                             (405,'2025_03_17_103421_create_inventory_property_values_table',9),
                             (406,'2025_03_17_105851_inventory_category_property_values',9),
                             (407,'2025_03_20_161506_create_inventory_article_images_table',9),
                             (408,'2025_03_25_090538_create_manufacturers_table',9),
                             (409,'2025_03_27_203615_public_key_for_chat',9),
                             (410,'2025_03_29_212150_create_chats_table',9),
                             (411,'2025_03_29_212341_create_chat_users_table',9),
                             (412,'2025_03_29_212347_create_chat_messages_table',9),
                             (413,'2025_03_29_212430_create_chat_message_reads_table',9),
                             (414,'2025_03_29_221959_add_use_chat_to_user',9),
                             (415,'2025_04_02_101031_add_more_to_inventory_article',9),
                             (416,'2025_04_02_103624_add_more_softdeletes_to_inventory',9),
                             (417,'2025_04_02_150312_add_is_planning_event_to_events',9),
                             (418,'2025_04_03_135007_event_type_user',9),
                             (419,'2025_04_03_135155_add_verification_mode_and_more',9),
                             (420,'2025_04_03_135251_create_event_verifications_table',9),
                             (421,'2025_04_10_151013_add_show_unplanned_events',9),
                             (422,'2025_04_11_000000_add_show_planned_events',9),
                             (423,'2025_04_14_095153_add_select_values_to_properties',9),
                             (424,'2025_04_14_152328_add_relevant_for_disposition',9),
                             (425,'2025_04_14_154331_create_accommodations_table',9),
                             (426,'2025_04_15_112356_create_contacts_table',9),
                             (427,'2025_04_16_101844_change_service_provider_id_to_accommodation_id',9),
                             (428,'2025_04_29_090908_create_inventory_article_statuses_table',9),
                             (429,'2025_04_29_091702_inventory_article_status_values',9),
                             (430,'2025_04_29_142148_add_status_to_detailed_article',9),
                             (431,'2025_05_02_155814_add_collective_booking',9),
                             (432,'2025_05_13_085435_create_internal_issues_table',9),
                             (433,'2025_05_13_085649_create_internal_issue_files_table',9),
                             (434,'2025_05_13_090504_create_special_items_table',9),
                             (435,'2025_05_13_091950_create_external_issues_table',9),
                             (436,'2025_05_13_092318_create_external_issue_files_table',9),
                             (437,'2025_05_13_093250_issuable_inventory_article_table',9),
                             (438,'2025_05_18_235056_create_api_access_tokens_table',9),
                             (439,'2025_05_19_152447_internal_issue_responsible_users',9),
                             (440,'2025_05_23_143126_add_special_items_done',9),
                             (441,'2025_05_26_202230_create_material_sets_table',9),
                             (442,'2025_05_26_202241_create_material_set_items_table',9),
                             (443,'2025_05_27_141043_create_user_inventory_article_plan_filters_table',9),
                             (444,'2022_03_23_104607_create_general_settings',10),
                             (445,'2024_05_03_133600_add_page_title',10),
                             (446,'2024_06_18_141450_project_create_settings',10),
                             (447,'2024_06_20_000000_add_is_planning_to_project_states_table',10),
                             (448,'2024_08_26_144000_module_settings',10),
                             (449,'2024_11_07_134053_create_holiday_settings',10),
                             (450,'2024_11_21_102240_event_settings',10),
                             (451,'2024_11_22_193801_shift_settings',10),
                             (452,'2024_11_25_132000_add_show_artists_to_project_create_settings',10),
                             (453,'2025_02_18_114302_general_calendar_settings',10),
                             (454,'2025_06_09_215711_add_new_shift_values_to_shift_workers',10),
                             (455,'2025_06_10_000000_initialize_missing_general_settings',10),
                             (456,'2025_06_13_084831_create_user_contracts_table',10),
                             (457,'2025_06_13_084922_create_user_work_time_patterns_table',10),
                             (458,'2025_06_13_115653_add_treat_as_special_day_to_holidays_table',10),
                             (459,'2025_06_16_144030_create_user_work_times_table',10),
                             (460,'2025_06_24_085530_create_user_contract_assigns_table',10),
                             (461,'2025_06_24_103919_create_work_time_bookings_table',10),
                             (462,'2025_06_24_124731_add_work_time_balance_to_user',10),
                             (463,'2025_06_24_130343_add_night_start_end_time',10),
                             (464,'2025_07_08_155545_create_work_time_change_requests_table',10),
                             (465,'2025_07_12_002001_add_until_from_to_worktime_pattern',10),
                             (466,'2025_07_15_073038_playing_time_window',10),
                             (467,'2025_07_15_081003_create_shift_commit_workflow_users_table',10),
                             (468,'2025_07_15_132136_create_shift_commit_workflow_requests_table',10),
                             (469,'2025_07_25_102033_add_marked_as_done_to_projects',10),
                             (470,'2025_07_27_000001_create_workflow_definitions_table',10),
                             (471,'2025_07_27_000002_create_workflow_definition_configs_table',10),
                             (472,'2025_07_27_000003_create_workflow_instances_table',10),
                             (473,'2025_07_27_000004_create_workflow_instance_data_table',10),
                             (474,'2025_07_27_000005_create_workflow_logs_table',10),
                             (475,'2025_07_27_000006_create_workflow_rules_table',10),
                             (476,'2025_07_27_000007_create_workflow_rule_assignments_table',10),
                             (477,'2025_07_27_000008_create_workflow_rule_violations_table',10),
                             (478,'2025_07_27_000009_create_workflow_rule_contract_assignments_table',10),
                             (479,'2025_07_27_000010_create_workflow_rule_user_notifications_table',10),
                             (480,'2025_07_27_000011_add_notify_on_violation_to_workflow_rules_table',10),
                             (481,'2025_07_29_134221_create_user_filters_table',10),
                             (482,'2025_07_30_133110_create_user_filter_templates_table',10),
                             (483,'2025_08_01_133009_update_component_type_enum_new',10),
                             (484,'2025_08_05_222512_create_shift_rules_table',10),
                             (485,'2025_08_05_222545_create_shift_rule_contract_assignments_table',10),
                             (486,'2025_08_05_222601_create_shift_rule_user_notifications_table',10),
                             (487,'2025_08_05_222649_create_shift_rule_violations_table',10),
                             (488,'2025_08_05_234203_drop_workflow_rule_violations_table_if_exists',10),
                             (489,'2025_08_19_095145_add_new_article_properties',10),
                             (490,'2025_08_19_103411_add_position_to_category_properties',10),
                             (491,'2025_08_20_104700_add_color_to_article_status',10),
                             (492,'2025_08_21_092042_change_and_remove_chat_cryption',10),
                             (493,'2025_08_21_131417_add_chat_position_to_user',10),
                             (494,'2025_08_24_212520_change_trigger_type_to_string_in_shift_rules_table',10),
                             (495,'2025_08_25_081138_add_name_to_extern_material_issue',10),
                             (496,'2025_08_26_000000_create_inventory_user_filters_table',10),
                             (497,'2025_09_03_000001_change_component_type_to_string',10),
                             (498,'2025_09_04_111032_create_accommodation_room_types_table',10),
                             (499,'2025_09_04_112141_accommodation_accommodation_room_type',10),
                             (500,'2025_09_04_145843_create_artists_table',10),
                             (501,'2025_09_04_150536_drop_column_in_artist_residency',10),
                             (502,'2025_09_08_144807_add_order_to_inventory_status',10),
                             (503,'2025_09_12_115205_optimize_calendar_indexes',10),
                             (504,'2025_09_15_172540_add_cost_per_night_to_accommodation_room_type_pivot_table',10),
                             (505,'2025_09_18_145306_add_chat_push_notification_add_disable',10),
                             (506,'2025_09_23_085417_hide_unoccupied_days_to_settings',10),
                             (507,'2025_09_23_124750_add_is_time_preset_open_to_user',10),
                             (508,'2025_09_24_124822_create_single_shift_presets_table',10),
                             (509,'2025_09_24_125358_single_shift_preset_qualifications',10),
                             (510,'2025_10_02_101252_add_uuid_to_detailed_quantity_articles',10),
                             (511,'2025_10_09_121127_add_to_general_settings_standard_values_event',10),
                             (512,'2025_10_20_113551_create_product_baskets_table',10),
                             (513,'2025_10_20_113619_create_product_basket_articles_table',10),
                             (514,'2025_10_23_134346_fix_accomodation_field',10),
                             (515,'2025_11_04_090008_add_project_id_to_shifts',10),
                             (516,'2025_11_05_142739_create_global_qualifications_table',10),
                             (517,'2025_11_05_143620_add_global_qualification_to_user',10),
                             (518,'2025_11_06_113828_shift_global_qualifaction',10),
                             (519,'2025_11_06_151226_craft_shift_qualification',10),
                             (520,'2025_11_10_000001_create_shift_qualifiables_table',10),
                             (521,'2025_11_10_151641_create_shift_groups_table',10),
                             (522,'2025_11_10_155036_add_shift_group_id',10),
                             (523,'2025_11_11_112531_add_check_if_user_in_more_than_one_shift_group',10),
                             (524,'2025_11_11_121248_add_show_shift_group_in_user_calendar_settings',10),
                             (525,'2025_11_17_120009_create_activity_log_table',10),
                             (526,'2025_11_17_120010_add_event_column_to_activity_log_table',10),
                             (527,'2025_11_17_120011_add_batch_uuid_column_to_activity_log_table',10),
                             (528,'2025_11_17_133641_create_shift_plan_requests_table',10),
                             (529,'2025_11_17_133653_create_shift_plan_request_changes_table',10),
                             (530,'2025_11_17_133702_create_committed_shift_changes_table',10),
                             (531,'2025_11_24_120000_create_shift_plan_request_shifts_table',10),
                             (532,'2025_11_26_081048_create_inventory_tag_groups_table',10),
                             (533,'2025_11_26_081056_create_inventory_tags_table',10),
                             (534,'2025_11_26_081257_inventory_tag_department',10),
                             (535,'2025_11_26_081309_inventory_tag_user',10),
                             (536,'2025_11_26_081322_inventory_article_inventory_tag',10),
                             (537,'2025_11_27_100059_create_individual_time_series_table',10),
                             (538,'2025_11_27_100621_add_series_uuid_to_individual_times_table',10),
                             (539,'2025_12_01_181651_add_scope_to_disclosure_components_table',10),
                             (540,'2025_12_03_155511_add_break_minutes_to_individual_times_table',10),
                             (541,'2025_12_04_163949_add_inventory_grid_layout_to_users_table',10),
                             (542,'2025_12_11_113708_add_show_timeline_in_user_calendar_settings',10),
                             (543,'2025_12_12_131400_add_tag_ids_to_inventory_user_filters_table',10),
                             (544,'2025_12_12_160633_change_vacation_type_column',10),
                             (545,'2025_12_17_101000_create_shift_preset_groups_table',10),
                             (546,'2025_12_17_143918_shift_preset_group_assignments',10),
                             (547,'2025_12_18_120000_add_order_to_sub_position_rows_table',10),
                             (548,'2025_12_19_123700_update_component_type_enum_add_project_basic_data_display_component',10),
                             (549,'2026_01_11_150555_create_shift_workers_table',10),
                             (550,'2026_01_12_084226_add_users_and_departments_to_tab',10),
                             (551,'2026_01_12_113445_create_inventory_article_filter_states_table',10),
                             (552,'2026_01_12_113513_create_inventory_article_filter_presets_table',10),
                             (553,'2026_01_18_221658_api_log',10),
                             (554,'2026_01_19_144236_add_opend_crafts_to_user',10),
                             (555,'2026_01_24_211152_add_rejection_data_to_shift_plan_requests',10),
                             (556,'2026_01_24_214828_add_rejected_shifts_to_shift_plan_requests',10),
                             (557,'2026_01_26_100620_add_workflow_rejection_reason_to_shift_workers_table',10),
                             (558,'2026_01_27_105138_add_additional_fields_to_contracts_table',10),
                             (559,'2026_01_27_105200_create_document_requests_table',10),
                             (560,'2026_01_27_160000_make_amount_nullable_in_contracts_table',10),
                             (561,'2026_01_28_183600_create_user_contract_filters_table',10),
                             (562,'2026_01_29_110500_create_contract_department_table',10),
                             (563,'2026_02_02_180500_create_link_list_templates_table',10),
                             (564,'2026_02_02_193700_create_pdf_export_user_filters_table',10),
                             (565,'2026_02_05_142000_sync_missing_notification_settings_for_users',10),
                             (566,'2026_02_11_104631_remove_title_and_description_from_document_requests_table',10),
                             (567,'2026_02_11_160000_add_city_and_country_to_contracts_and_document_requests',10),
                             (568,'2026_02_11_170000_add_contract_state_fields_to_contracts_and_document_requests',10),
                             (569,'2026_02_11_173200_make_requested_id_nullable_in_document_requests',10),
                             (570,'2026_02_13_120000_add_event_start_time_to_general_settings',10),
                             (571,'2026_02_15_183158_missing_indexes',10),
                             (572,'2026_02_16_143818_add_request_end_date_to_work_time_change_requests_table',10),
                             (573,'2026_02_17_143000_rename_event_type_fields_to_craft_fields_in_user_shift_calendar_abos',10),
                             (574,'2026_02_18_124800_add_business_to_users_table',10),
                             (575,'2026_02_20_144400_add_created_by_to_shift_plan_comments_and_vacations',10),
                             (576,'2026_02_24_145800_add_show_only_not_fully_staffed_shifts_to_user_calendar_settings',10),
                             (577,'2026_02_25_103000_create_user_daily_view_calendar_settings_table',10),
                             (578,'2026_02_25_120000_add_breakfast_deduction_to_general_settings',10),
                             (579,'2026_02_25_120000_add_breakfast_fields_to_artist_residencies',10),
                             (580,'2026_02_25_130000_add_letterhead_fields_to_general_settings',10),
                             (581,'2026_02_26_110700_replace_copyright_with_gema_on_projects',10),
                             (582,'2026_02_26_124800_add_do_not_save_artist_to_artist_residencies',10),
                             (583,'2026_02_26_140800_create_user_budget_account_display_settings_table',10),
                             (584,'2026_03_02_100000_add_manual_and_compensation_fields_to_shift_rule_violations_table',10),
                             (585,'2026_03_02_125200_add_is_main_to_project_attribute_pivot_tables',10),
                             (586,'2026_03_02_142100_add_use_main_category_color_to_calendar_settings',10),
                             (587,'2026_03_03_093200_ensure_is_main_on_project_attribute_pivot_tables',10),
                             (588,'2026_03_04_100000_add_calendar_abo_show_all_shifts',10),
                             (589,'2026_03_04_120000_add_show_description_in_bulk_to_users_table',10),
                             (590,'2026_03_04_150000_create_user_shift_list_view_settings_table',10),
                             (591,'2026_03_05_000000_add_detailed_shift_overview_to_user_shift_list_view_settings_table',10),
                             (592,'2026_03_05_100000_fix_resolved_by_foreign_key_on_shift_rule_violations',10),
                             (593,'2026_03_05_120000_add_event_all_day_default_to_general_settings',10),
                             (594,'2026_03_13_000000_create_compensation_day_offs_table',10),
                             (595,'2026_03_13_000001_remove_compensation_granted_fields_from_shift_rule_violations',10),
                             (596,'2026_03_13_000002_make_violation_id_nullable_on_compensation_day_offs',10),
                             (597,'2026_03_14_000000_add_ignore_reason_to_shift_rule_violations',10),
                             (598,'2026_03_15_000000_add_crm_to_module_settings',10),
                             (599,'2026_03_15_000001_create_crm_contact_types_table',10),
                             (600,'2026_03_15_000002_create_crm_contacts_table',10),
                             (601,'2026_03_15_000003_create_crm_property_groups_table',10),
                             (602,'2026_03_15_000004_create_crm_property_group_permissions_table',10),
                             (603,'2026_03_15_000005_create_crm_properties_table',10),
                             (604,'2026_03_15_000006_create_crm_contact_type_property_table',10),
                             (605,'2026_03_15_000007_create_crm_property_values_table',10),
                             (606,'2026_03_15_000008_add_crm_contact_id_to_existing_tables',10),
                             (607,'2026_03_15_000009_move_property_booleans_to_pivot',10),
                             (608,'2026_03_15_000010_rename_and_add_name_fields',10),
                             (609,'2026_03_15_000011_add_crm_property_overrides_to_artist_residencies',10),
                             (610,'2026_03_16_000001_add_crm_contact_id_to_document_requests',10),
                             (611,'2026_03_17_000000_add_show_project_team_names_to_users_table',10),
                             (612,'2026_03_19_113420_add_show_user_overview_to_user_calendar_settings_table',10),
                             (613,'2026_03_30_000001_add_crm_contact_id_to_manufacturers_accommodations_artists',10),
                             (614,'2026_03_30_100000_add_entity_morph_to_crm_contacts',10),
                             (615,'2026_04_01_000000_create_user_shift_plan_settings_tables',10),
                             (616,'2026_04_02_120000_add_per_diem_export_counter_to_general_settings',10),
                             (617,'2026_04_13_000000_remove_name_property_and_assign_email_to_all_crm_types',10),
                             (618,'2026_04_16_000000_add_for_holiday_to_compensation_day_offs_table',10),
                             (619,'2026_04_16_100000_add_default_compensation_days_to_shift_rules_table',10),
                             (620,'2026_04_16_120000_add_inventory_detailed_articles_always_quantity_one',10),
                             (621,'2026_04_16_200000_add_default_compensation_deadline_days_to_shift_rules_table',10);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_changes_history`
--

DROP TABLE IF EXISTS `model_changes_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_changes_history` (
                                         `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                         `model_id` bigint(20) unsigned NOT NULL,
                                         `model_type` varchar(255) NOT NULL,
                                         `before_changes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`before_changes`)),
                                         `after_changes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`after_changes`)),
                                         `changes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`changes`)),
                                         `change_type` enum('created','updated','deleted','restored','forceDeleted') NOT NULL,
                                         `changer_type` varchar(255) DEFAULT NULL,
                                         `changer_id` bigint(20) unsigned DEFAULT NULL,
                                         `stack_trace` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`stack_trace`)),
                                         `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                                         PRIMARY KEY (`id`),
                                         KEY `model_changes_history_model_id_model_type_index` (`model_id`,`model_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_changes_history`
--

LOCK TABLES `model_changes_history` WRITE;
/*!40000 ALTER TABLE `model_changes_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_changes_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
                                         `permission_id` bigint(20) unsigned NOT NULL,
                                         `model_type` varchar(255) NOT NULL,
                                         `model_id` bigint(20) unsigned NOT NULL,
                                         PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
                                         KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
                                         CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
                                   `role_id` bigint(20) unsigned NOT NULL,
                                   `model_type` varchar(255) NOT NULL,
                                   `model_id` bigint(20) unsigned NOT NULL,
                                   PRIMARY KEY (`role_id`,`model_id`,`model_type`),
                                   KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
                                   CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `money_source_categories`
--

DROP TABLE IF EXISTS `money_source_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `money_source_categories` (
                                           `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                           `name` varchar(255) NOT NULL,
                                           `created_at` timestamp NULL DEFAULT NULL,
                                           `updated_at` timestamp NULL DEFAULT NULL,
                                           PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `money_source_categories`
--

LOCK TABLES `money_source_categories` WRITE;
/*!40000 ALTER TABLE `money_source_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `money_source_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `money_source_category_mappings`
--

DROP TABLE IF EXISTS `money_source_category_mappings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `money_source_category_mappings` (
                                                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                                  `money_source_id` bigint(20) unsigned NOT NULL,
                                                  `money_source_category_id` bigint(20) unsigned NOT NULL,
                                                  PRIMARY KEY (`id`),
                                                  KEY `money_source_category_mappings_money_source_id_foreign` (`money_source_id`),
                                                  KEY `money_source_category_mappings_money_source_category_id_foreign` (`money_source_category_id`),
                                                  CONSTRAINT `money_source_category_mappings_money_source_category_id_foreign` FOREIGN KEY (`money_source_category_id`) REFERENCES `money_source_categories` (`id`),
                                                  CONSTRAINT `money_source_category_mappings_money_source_id_foreign` FOREIGN KEY (`money_source_id`) REFERENCES `money_sources` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `money_source_category_mappings`
--

LOCK TABLES `money_source_category_mappings` WRITE;
/*!40000 ALTER TABLE `money_source_category_mappings` DISABLE KEYS */;
/*!40000 ALTER TABLE `money_source_category_mappings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `money_source_files`
--

DROP TABLE IF EXISTS `money_source_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `money_source_files` (
                                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                      `name` varchar(255) NOT NULL,
                                      `basename` varchar(255) NOT NULL,
                                      `money_source_id` bigint(20) unsigned NOT NULL,
                                      `deleted_at` timestamp NULL DEFAULT NULL,
                                      `created_at` timestamp NULL DEFAULT NULL,
                                      `updated_at` timestamp NULL DEFAULT NULL,
                                      PRIMARY KEY (`id`),
                                      UNIQUE KEY `money_source_files_basename_unique` (`basename`),
                                      KEY `money_source_files_money_source_id_index` (`money_source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `money_source_files`
--

LOCK TABLES `money_source_files` WRITE;
/*!40000 ALTER TABLE `money_source_files` DISABLE KEYS */;
/*!40000 ALTER TABLE `money_source_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `money_source_project`
--

DROP TABLE IF EXISTS `money_source_project`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `money_source_project` (
                                        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                        `money_source_id` bigint(20) unsigned NOT NULL,
                                        `project_id` bigint(20) unsigned NOT NULL,
                                        `created_at` timestamp NULL DEFAULT NULL,
                                        `updated_at` timestamp NULL DEFAULT NULL,
                                        `deleted_at` timestamp NULL DEFAULT NULL,
                                        PRIMARY KEY (`id`),
                                        KEY `money_source_project_money_source_id_index` (`money_source_id`),
                                        KEY `money_source_project_project_id_index` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `money_source_project`
--

LOCK TABLES `money_source_project` WRITE;
/*!40000 ALTER TABLE `money_source_project` DISABLE KEYS */;
/*!40000 ALTER TABLE `money_source_project` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `money_source_reminders`
--

DROP TABLE IF EXISTS `money_source_reminders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `money_source_reminders` (
                                          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                          `money_source_id` bigint(20) unsigned NOT NULL,
                                          `type` enum('expiration','threshold') NOT NULL,
                                          `value` int(11) NOT NULL,
                                          `notification_created` tinyint(1) NOT NULL DEFAULT 0,
                                          PRIMARY KEY (`id`),
                                          KEY `money_source_reminders_money_source_id_foreign` (`money_source_id`),
                                          CONSTRAINT `money_source_reminders_money_source_id_foreign` FOREIGN KEY (`money_source_id`) REFERENCES `money_sources` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `money_source_reminders`
--

LOCK TABLES `money_source_reminders` WRITE;
/*!40000 ALTER TABLE `money_source_reminders` DISABLE KEYS */;
/*!40000 ALTER TABLE `money_source_reminders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `money_source_task_user`
--

DROP TABLE IF EXISTS `money_source_task_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `money_source_task_user` (
                                          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                          `task_id` bigint(20) NOT NULL,
                                          `user_id` bigint(20) NOT NULL,
                                          `created_at` timestamp NULL DEFAULT NULL,
                                          `updated_at` timestamp NULL DEFAULT NULL,
                                          PRIMARY KEY (`id`),
                                          KEY `money_source_task_user_task_id_index` (`task_id`),
                                          KEY `money_source_task_user_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `money_source_task_user`
--

LOCK TABLES `money_source_task_user` WRITE;
/*!40000 ALTER TABLE `money_source_task_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `money_source_task_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `money_source_tasks`
--

DROP TABLE IF EXISTS `money_source_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `money_source_tasks` (
                                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                      `money_source_id` bigint(20) NOT NULL,
                                      `name` varchar(255) NOT NULL,
                                      `description` text DEFAULT NULL,
                                      `deadline` datetime DEFAULT NULL,
                                      `creator` bigint(20) NOT NULL,
                                      `created_at` timestamp NULL DEFAULT NULL,
                                      `updated_at` timestamp NULL DEFAULT NULL,
                                      `done` tinyint(1) NOT NULL DEFAULT 0,
                                      PRIMARY KEY (`id`),
                                      KEY `money_source_tasks_money_source_id_index` (`money_source_id`),
                                      KEY `money_source_tasks_creator_index` (`creator`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `money_source_tasks`
--

LOCK TABLES `money_source_tasks` WRITE;
/*!40000 ALTER TABLE `money_source_tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `money_source_tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `money_source_user_pinned`
--

DROP TABLE IF EXISTS `money_source_user_pinned`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `money_source_user_pinned` (
                                            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                            `money_source_id` bigint(20) NOT NULL,
                                            `user_id` bigint(20) NOT NULL,
                                            `created_at` timestamp NULL DEFAULT NULL,
                                            `updated_at` timestamp NULL DEFAULT NULL,
                                            PRIMARY KEY (`id`),
                                            KEY `money_source_user_pinned_money_source_id_index` (`money_source_id`),
                                            KEY `money_source_user_pinned_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `money_source_user_pinned`
--

LOCK TABLES `money_source_user_pinned` WRITE;
/*!40000 ALTER TABLE `money_source_user_pinned` DISABLE KEYS */;
/*!40000 ALTER TABLE `money_source_user_pinned` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `money_source_users`
--

DROP TABLE IF EXISTS `money_source_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `money_source_users` (
                                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                      `money_source_id` bigint(20) unsigned NOT NULL,
                                      `user_id` bigint(20) unsigned NOT NULL,
                                      `competent` tinyint(1) NOT NULL DEFAULT 0,
                                      `write_access` tinyint(1) NOT NULL DEFAULT 0,
                                      `created_at` timestamp NULL DEFAULT NULL,
                                      `updated_at` timestamp NULL DEFAULT NULL,
                                      PRIMARY KEY (`id`),
                                      KEY `money_source_users_money_source_id_index` (`money_source_id`),
                                      KEY `money_source_users_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `money_source_users`
--

LOCK TABLES `money_source_users` WRITE;
/*!40000 ALTER TABLE `money_source_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `money_source_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `money_sources`
--

DROP TABLE IF EXISTS `money_sources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `money_sources` (
                                 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                 `creator_id` bigint(20) NOT NULL,
                                 `name` varchar(255) NOT NULL,
                                 `amount` double(25,2) NOT NULL DEFAULT 0.00,
                                 `source_name` varchar(255) DEFAULT NULL,
                                 `start_date` date DEFAULT NULL,
                                 `end_date` date DEFAULT NULL,
                                 `funding_start_date` date DEFAULT NULL,
                                 `funding_end_date` date DEFAULT NULL,
                                 `users` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`users`)),
                                 `pinned_by_users` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pinned_by_users`)),
                                 `group_id` bigint(20) DEFAULT NULL,
                                 `description` text DEFAULT NULL,
                                 `is_group` tinyint(1) NOT NULL DEFAULT 0,
                                 `created_at` timestamp NULL DEFAULT NULL,
                                 `updated_at` timestamp NULL DEFAULT NULL,
                                 PRIMARY KEY (`id`),
                                 KEY `money_sources_creator_id_index` (`creator_id`),
                                 KEY `money_sources_group_id_index` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `money_sources`
--

LOCK TABLES `money_sources` WRITE;
/*!40000 ALTER TABLE `money_sources` DISABLE KEYS */;
/*!40000 ALTER TABLE `money_sources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_settings`
--

DROP TABLE IF EXISTS `notification_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_settings` (
                                         `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                         `user_id` bigint(20) unsigned NOT NULL,
                                         `group_type` varchar(255) NOT NULL,
                                         `type` varchar(255) NOT NULL,
                                         `title` varchar(255) NOT NULL,
                                         `description` mediumtext NOT NULL,
                                         `frequency` varchar(255) NOT NULL DEFAULT 'daily',
                                         `enabled_email` tinyint(1) NOT NULL DEFAULT 1,
                                         `enabled_push` tinyint(1) NOT NULL DEFAULT 1,
                                         `created_at` timestamp NULL DEFAULT NULL,
                                         `updated_at` timestamp NULL DEFAULT NULL,
                                         PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_settings`
--

LOCK TABLES `notification_settings` WRITE;
/*!40000 ALTER TABLE `notification_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `notification_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
                                 `id` char(36) NOT NULL,
                                 `type` varchar(255) NOT NULL,
                                 `groupType` varchar(255) DEFAULT NULL,
                                 `notifiable_type` varchar(255) NOT NULL,
                                 `notifiable_id` bigint(20) unsigned NOT NULL,
                                 `data` text NOT NULL,
                                 `read_at` timestamp NULL DEFAULT NULL,
                                 `created_at` timestamp NULL DEFAULT NULL,
                                 `updated_at` timestamp NULL DEFAULT NULL,
                                 `sent_in_summary` tinyint(1) NOT NULL DEFAULT 0,
                                 PRIMARY KEY (`id`),
                                 KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oauth_access_tokens`
--

DROP TABLE IF EXISTS `oauth_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_access_tokens` (
                                       `id` varchar(100) NOT NULL,
                                       `user_id` bigint(20) unsigned DEFAULT NULL,
                                       `client_id` bigint(20) unsigned NOT NULL,
                                       `name` varchar(255) DEFAULT NULL,
                                       `scopes` text DEFAULT NULL,
                                       `revoked` tinyint(1) NOT NULL,
                                       `created_at` timestamp NULL DEFAULT NULL,
                                       `updated_at` timestamp NULL DEFAULT NULL,
                                       `expires_at` datetime DEFAULT NULL,
                                       PRIMARY KEY (`id`),
                                       KEY `oauth_access_tokens_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oauth_access_tokens`
--

LOCK TABLES `oauth_access_tokens` WRITE;
/*!40000 ALTER TABLE `oauth_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `oauth_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oauth_auth_codes`
--

DROP TABLE IF EXISTS `oauth_auth_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_auth_codes` (
                                    `id` varchar(100) NOT NULL,
                                    `user_id` bigint(20) unsigned NOT NULL,
                                    `client_id` bigint(20) unsigned NOT NULL,
                                    `scopes` text DEFAULT NULL,
                                    `revoked` tinyint(1) NOT NULL,
                                    `expires_at` datetime DEFAULT NULL,
                                    PRIMARY KEY (`id`),
                                    KEY `oauth_auth_codes_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oauth_auth_codes`
--

LOCK TABLES `oauth_auth_codes` WRITE;
/*!40000 ALTER TABLE `oauth_auth_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `oauth_auth_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oauth_clients`
--

DROP TABLE IF EXISTS `oauth_clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_clients` (
                                 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                 `user_id` bigint(20) unsigned DEFAULT NULL,
                                 `name` varchar(255) NOT NULL,
                                 `secret` varchar(100) DEFAULT NULL,
                                 `provider` varchar(255) DEFAULT NULL,
                                 `redirect` text NOT NULL,
                                 `personal_access_client` tinyint(1) NOT NULL,
                                 `password_client` tinyint(1) NOT NULL,
                                 `revoked` tinyint(1) NOT NULL,
                                 `created_at` timestamp NULL DEFAULT NULL,
                                 `updated_at` timestamp NULL DEFAULT NULL,
                                 PRIMARY KEY (`id`),
                                 KEY `oauth_clients_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oauth_clients`
--

LOCK TABLES `oauth_clients` WRITE;
/*!40000 ALTER TABLE `oauth_clients` DISABLE KEYS */;
/*!40000 ALTER TABLE `oauth_clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oauth_personal_access_clients`
--

DROP TABLE IF EXISTS `oauth_personal_access_clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_personal_access_clients` (
                                                 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                                 `client_id` bigint(20) unsigned NOT NULL,
                                                 `created_at` timestamp NULL DEFAULT NULL,
                                                 `updated_at` timestamp NULL DEFAULT NULL,
                                                 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oauth_personal_access_clients`
--

LOCK TABLES `oauth_personal_access_clients` WRITE;
/*!40000 ALTER TABLE `oauth_personal_access_clients` DISABLE KEYS */;
/*!40000 ALTER TABLE `oauth_personal_access_clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oauth_refresh_tokens`
--

DROP TABLE IF EXISTS `oauth_refresh_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_refresh_tokens` (
                                        `id` varchar(100) NOT NULL,
                                        `access_token_id` varchar(100) NOT NULL,
                                        `revoked` tinyint(1) NOT NULL,
                                        `expires_at` datetime DEFAULT NULL,
                                        PRIMARY KEY (`id`),
                                        KEY `oauth_refresh_tokens_access_token_id_index` (`access_token_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oauth_refresh_tokens`
--

LOCK TABLES `oauth_refresh_tokens` WRITE;
/*!40000 ALTER TABLE `oauth_refresh_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `oauth_refresh_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
                                   `email` varchar(255) NOT NULL,
                                   `token` varchar(255) NOT NULL,
                                   `created_at` timestamp NULL DEFAULT NULL,
                                   KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pdf_export_user_filters`
--

DROP TABLE IF EXISTS `pdf_export_user_filters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pdf_export_user_filters` (
                                           `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                           `user_id` bigint(20) unsigned NOT NULL,
                                           `name` varchar(80) NOT NULL,
                                           `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters`)),
                                           `created_at` timestamp NULL DEFAULT NULL,
                                           `updated_at` timestamp NULL DEFAULT NULL,
                                           PRIMARY KEY (`id`),
                                           KEY `pdf_export_user_filters_user_id_index` (`user_id`),
                                           CONSTRAINT `pdf_export_user_filters_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pdf_export_user_filters`
--

LOCK TABLES `pdf_export_user_filters` WRITE;
/*!40000 ALTER TABLE `pdf_export_user_filters` DISABLE KEYS */;
/*!40000 ALTER TABLE `pdf_export_user_filters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permission_presets`
--

DROP TABLE IF EXISTS `permission_presets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permission_presets` (
                                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                      `name` varchar(255) NOT NULL,
                                      `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`permissions`)),
                                      `created_at` timestamp NULL DEFAULT NULL,
                                      `updated_at` timestamp NULL DEFAULT NULL,
                                      PRIMARY KEY (`id`),
                                      UNIQUE KEY `permission_presets_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permission_presets`
--

LOCK TABLES `permission_presets` WRITE;
/*!40000 ALTER TABLE `permission_presets` DISABLE KEYS */;
INSERT INTO `permission_presets` VALUES
                                     (1,'Standard User','[1,2,6,8]','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                     (2,'Vertrags- & Dokumentenadmin','[9,14]','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                     (3,'Budgetadmin','[28,15]','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                     (4,'Disponent*in','[18]','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                     (5,'Finanzierungsquellenadmin','[12,13]','2026-04-21 06:09:24','2026-04-21 06:09:24');
/*!40000 ALTER TABLE `permission_presets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
                               `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                               `name` varchar(255) NOT NULL,
                               `translation_key` varchar(255) DEFAULT NULL,
                               `guard_name` varchar(255) NOT NULL,
                               `created_at` timestamp NULL DEFAULT NULL,
                               `updated_at` timestamp NULL DEFAULT NULL,
                               `name_de` varchar(255) DEFAULT NULL,
                               `group` varchar(255) DEFAULT NULL,
                               `tooltipText` longtext DEFAULT NULL,
                               `checked` tinyint(1) DEFAULT NULL,
                               `tooltipKey` varchar(255) DEFAULT NULL,
                               PRIMARY KEY (`id`),
                               UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES
                              (1,'view projects','Read permissions for all projects','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Leserechte für alle Projekte','Projects','Nutzer*in darf sämtliche Projekte einsehen –\n                sowohl die Projektdetails als auch die Belegungen im Kalender.',0,'User can view all projects including project details and calendar bookings.'),
                              (2,'create and edit own project','Create and edit own projects','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Eigene Projekte anlegen & bearbeiten','Projects','Nutzer*in darf Projekte anlegen, bearbeiten & löschen –\n                dadurch ist er/sie automatisch Projektadmin des neu angelegten Projekts.',0,'User can create, edit, and delete projects, automatically becoming project admin of the created project.'),
                              (3,'write projects','Write permissions for all projects','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Schreibrechte für alle Projekte','Projects','Nutzer*in hat auf alle Projekte Projektadmin-Rechte, auch wenn er/sie nicht zum Projektteam gehört.',0,'User has project admin rights on all projects, even if not part of the project team.'),
                              (4,'delete projects','Delete permissions for all projects','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Löschrecht für alle Projekte','Projects','Nutzer*in darf alle Projekte löschen, auch wenn er/sie nicht zum Projektteam gehört.',0,'User can delete all projects, even if not part of the project team.'),
                              (5,'management projects','Project management','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Projektleitung sein','Projects','User darf in Projekten Projektleitung sein.',0,'User can act as project manager within projects.'),
                              (6,'request room occupancy','Request room bookings','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Raumbelegungen anfragen','Room bookings','Nutzer*in darf Raumbelegungs-Anfragen für die eigenen Projekte stellen und die eigenen Anfragen editieren & löschen.',0,'User can request room bookings for their own projects and edit or delete their own requests.'),
                              (7,'create events without request','Schedule events without request','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Termine fest planen','Room bookings','Ein User mit diesem Recht darf Termine ohne Anfrage direkt fest planen in allen Räumen',0,'A user with this permission can schedule events directly without a request in all rooms'),
                              (8,'can see and download contract modules','Allowed to view & download contract components','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Darf Vertragsbausteine einsehen & runterladen','Documents & Budget','Nutzer*in darf Vertragsbausteine einsehen und runterladen.',0,'User is allowed to view and download contract components.'),
                              (9,'view edit upload contracts','Manage contract components','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Vertragsbausteine verwalten','Documents & Budget','Darf Vertragsbausteine hochladen und löschen.',0,'User is allowed to upload and delete contract components.'),
                              (10,'can create document requests','Create document requests','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Dokumentenanfragen erstellen','Documents & Budget','Nutzer*in darf Dokumentenanfragen erstellen und an andere Nutzer*innen zuweisen.',0,'User is allowed to create document requests and assign them to other users.'),
                              (11,'can edit document requests','Edit document requests','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Dokumentenanfragen bearbeiten','Documents & Budget','Nutzer*in darf Dokumentenanfragen bearbeiten und den Status ändern.',0,'User is allowed to edit document requests and change their status.'),
                              (12,'view edit add money_sources','Create and manage funding sources','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Finanzierungsquellen anlegen und verwalten','Documents & Budget','User darf eigene Finanzierungsquellen anlegen und zur Einsicht & Verwaltung von Finanzierungsquellen eingeladen werden.',0,'User is allowed to create their own funding sources and be invited for viewing & managing funding sources.'),
                              (13,'can edit and delete money sources','Has write and delete rights on all funding sources','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Hat auf alle Finanzierungsquellen Schreib- und Löschrechte','Documents & Budget','Darf auf alle Finanzierungsquellen Schreib- und Löschrechte ausüben.',0,'User has write and delete rights on all funding sources.'),
                              (14,'can see, edit and delete project contracts and docs','Allowed to view, edit, and delete all budget documents & contracts from all projects','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Darf alle Budget-Dokumente & Verträge von allen\n                Projekten einsehen, bearbeiten und löschen','Documents & Budget','Nutzer*in darf alle Budget-Dokumente & Verträge von allen Projekten einsehen, bearbeiten und löschen.',0,'User is allowed to view, edit, and delete all budget documents & contracts from all projects.'),
                              (15,'can add and remove verified states','Allowed to remove any verification or fixed statuses and unlock locked columns','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Darf zusätzlich sämtliche verifizierungs-,\n                oder festgeschriebenen Status aufheben oder gesperrte Spalten entsperren.','Documents & Budget','Nutzer*in darf zusätzlich sämtliche verifizierungs-, oder festgeschriebenen Status aufheben oder gesperrte Spalten entsperren.',0,'User is allowed to remove any verification or fixed statuses and unlock locked columns.'),
                              (16,'change tool settings','Edit tool settings','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Tooleinstellungen editieren','System settings','Nutzer*in darf die Grundeinstellungen des\n                Tools editieren und z.B. Logos austauschen, Impressum definieren etc.',0,'User is allowed to edit the basic settings of the tool, such as replacing logos, defining legal notice, etc.'),
                              (17,'teammanagement','Team management','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Teamverwaltung','System settings','Nutzer*in darf Teams (Abteilungen) im\n                System anlegen, editieren & löschen. Diese Teams können anschließend z.B. Projekten zugeordnet werden.',0,'User can create, edit, and delete teams (departments) in the system. These teams can then be assigned to projects, for example.'),
                              (18,'create, delete and update rooms','Room management','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Raumverwaltung','System settings','Nutzer*in darf Räume erstellen, löschen und bearbeiten.',0,'User can create, delete, and edit rooms.'),
                              (19,'change project settings','Define system settings for projects','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Systemeinstellungen für Projekte definieren','System settings','Nutzer*in darf in den Systemeinstellungen Projektkategorien, Genres & Bereiche definieren, bearbeiten & löschen.',0,'User can define, edit, and delete project categories, genres, and areas in the system settings.'),
                              (20,'change event settings','Define system settings for appointments','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Systemeinstellungen für Termine definieren','System settings','Nutzer*in darf in den Systemeinstellungen\n                Termintypen definieren, editieren & löschen.',0,'User can define, edit, and delete types of appointments in the system settings.'),
                              (21,'admin checklistTemplates','Checklist template administration','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Verwaltung von Checklisten-Vorlagen','System settings','Nutzer*in darf Checklisten-Vorlagen erstellen, bearbeiten & löschen. Alle Vorlagen können anschließend von allen anderen Usern verwendet werden.',0,'User can create, edit, and delete checklist templates. All templates can then be used by all other users.'),
                              (22,'change system notification','Manage system notifications','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Systemnachrichten verwalten','System settings','Nutzer*in darf Systemnachrichten anlegen, editieren und löschen. Diese Benachrichtigungen werden allen Usern angezeigt.',0,'User can create, edit, and delete system notifications. These notifications are displayed to all users.'),
                              (23,'can manage workers','Employee management','web','2026-04-21 06:09:24','2026-04-21 06:09:24','MA-Verwaltung','Employee settings','Darf MA Seiten bearbeiten, aber die User nicht einladen.',0,'User can edit employee pages but cannot invite users.'),
                              (24,'view budget templates','View budget templates','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Budgetvorlagen einsehen','Documents & Budget','User can view budget templates.',0,'User can view budget templates.'),
                              (25,'can plan shifts','Shift planner','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Schichtplaner','Shifts','Darf MA Seiten nicht anlegen aber die User verplanen.',0,'User cannot create employee pages but can schedule users.'),
                              (26,'edit budget templates','Edit budget templates','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Budgetvorlagen bearbeiten','Documents & Budget','User can edit budget templates.',0,'User can edit budget templates.'),
                              (27,'can manage all project budgets without docs','Global budget access without document viewing','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Globaler Budgetzugriff ohne Dokumenteneinsicht','Documents & Budget','Hat auf alle Projekte Budgetzugriff, d.h. kann die Budgetplanung von allen Projekten einsehen ohne dabei die Dokumente sehen zu können.',0,'User has budget access to all projects, meaning they can view budget planning of all projects without accessing the documents.'),
                              (28,'can manage global project budgets','Global budget access with document viewing','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Globaler Budgetzugriff mit Dokumenteneinsicht','Documents & Budget','Hat auf alle Projekte Budgetzugriff, d.h. kann die Budgetplanung von allen Projekten einsehen und kann auch alle Dokumente der Projekte sehen.',0,'User has budget access to all projects, meaning they can view both budget planning and documents of all projects.'),
                              (29,'can view shift plan','View shift plan','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Schichtplan einsehen','Shifts','Darf den globalen Schichtplan einsehen',0,'User can view the global shift plan.'),
                              (30,'can commit shifts','Lock shift plans','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Dienstpläne festschreiben','Shifts','Darf Dienstpläne festschreiben',0,'User can lock shift plans.'),
                              (31,'can edit external users conditions','Manage external employee conditions','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Externe Mitarbeiterkonditionen verwalten','Employee settings','Darf die Konditionen von externen Mitarbeitern sehen und bearbeiten',0,'User can view and edit the conditions of external employees.'),
                              (32,'can view project sage data','View project-related Sage data','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Projektbezogene Sage-Daten einsehen','Interfaces','Nutzer*innen mit diesem Recht können projektbezogene, nicht zugewiesene Datensätze von Sage sehen.',0,'Users with this permission can view project-related unassigned Sage data records.'),
                              (33,'can view global sage data','View global Sage data','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Globale Sage-Daten einsehen','Interfaces','Nutzer*innen mit diesem Recht können globale, nicht zugewiesene Datensätze von Sage sehen.',0,'Users with this permission can view global unassigned Sage data records.'),
                              (34,'can use checklists','Use to-dos','web','2026-04-21 06:09:24','2026-04-21 06:09:24','To-dos nutzen','To-dos','Erlaubt Erstellen von Listen und To-dos im allgemeinen Bereich (Übersichtsseite) und auf Projektebene, sofern durch To-do-Komponente nicht weiter eingeschränkt.',0,'Allows the creation of lists and to-dos in the general area (overview page) and at project level, unless further restricted by the to-do component.'),
                              (35,'can edit checklist','Manage to-dos','web','2026-04-21 06:09:24','2026-04-21 06:09:24','To-dos verwalten','To-dos','Erlaubt zudem das Löschen aller Listen, unabhängig davon wer sie erstellt hat',0,'Also allows you to delete all lists, regardless of who created them'),
                              (36,'can manage availability','Manually manage availabilities','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Verfügbarkeiten manuell verwalten','Shifts','Stelle die Verfügbarkeiten des Nutzer*innen ein',0,'Set the availability of the user'),
                              (37,'can create events when creating a project','Create events when creating a new project','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Termine einrichten bei neuem Projekt','Projects','Erstelle Termine, wenn ein neues Projekt erstellt wird',0,'Create events when a new project is created'),
                              (38,'can manage inventory stock','Manage inventory stock','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Inventar-Bestand verwalten','Inventory','Erlaubt das Anlegen, Bearbeiten und Löschen von Inventar-Beständen',0,'Allows the creation, editing and deletion of inventory stocks'),
                              (39,'can plan inventory','Inventory planner','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Inventarplaner','Inventory','Erlaubt die Planung von Inventar',0,'Allows the planning of inventory'),
                              (40,'can view private user info','View private contact details','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Private Kontaktdaten einsehen','Employee settings','Darf private Kontaktdaten von Nutzer*innen einsehen',0,'Can view private contact details of users'),
                              (41,'can see planning calendar','View and plan in the planning calendar','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Planungskalender einsehen und Planen','Event management','Ein User mit diesem Recht darf den Planungskalender einsehen und darin planen',0,'A user with this permission can view the planning calendar and plan within it'),
                              (42,'can edit planning calendar','Edit, delete and confirm scheduled events','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Geplante Termine bearbeiten, löschen und bestätigen','Event management','Ein User mit diesem Recht kann geplante Termine bearbeiten, löschen und bestätigen',0,'A user with this permission can edit, delete and confirm scheduled events'),
                              (43,'set.create_edit','Create & edit sets','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Sets anlegen & bearbeiten','Inventory','Erlaubt das Erstellen und Bearbeiten von Sets',0,'Allows creating and editing sets'),
                              (44,'set.delete','Delete sets','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Sets löschen','Inventory','Erlaubt das Löschen von Sets',0,'Allows deleting sets'),
                              (45,'inventory.create_edit','Create & edit inventory','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Inventar anlegen & bearbeiten','Inventory','Erlaubt das Anlegen und Bearbeiten von Inventar',0,'Allows creating and editing inventory'),
                              (46,'inventory.delete','Delete inventory','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Inventar löschen','Inventory','Erlaubt das Löschen von Inventar',0,'Allows deleting inventory'),
                              (47,'inventory.disposition','Inventory disposition','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Inventardisposition','Inventory','Erlaubt die Disposition und Verwaltung des Inventars',0,'Allows disposition and management of inventory'),
                              (48,'can view material issue log','View material issue log','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Logbuch Materialausgaben einsehen','Inventory','Erlaubt das Einsehen des Logbuchs aller Materialausgabe-Änderungen',0,'Allows viewing the log of all material issue changes'),
                              (49,'shift.settings_view_edit','View and edit shift settings','web','2026-04-21 06:09:24','2026-04-21 06:09:24','Schichteinstellungen einsehen und bearbeiten','Shifts','Erlaubt das Einsehen und Bearbeiten der Schichteinstellungen',0,'Allows viewing and editing shift settings'),
                              (50,'can view crm','View CRM','web','2026-04-21 06:09:24','2026-04-21 06:09:24','CRM einsehen','CRM','Erlaubt den Zugriff auf das CRM-Modul und die Kontaktübersicht.',0,'Allows access to the CRM module and the contact overview.'),
                              (51,'crm manager','Manage CRM','web','2026-04-21 06:09:24','2026-04-21 06:09:24','CRM verwalten','CRM','Erlaubt das Verwalten von Kontakttypen, Eigenschaftsgruppen und Eigenschaften im CRM.',0,'Allows managing contact types, property groups, and properties in the CRM.');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
                                          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                          `tokenable_type` varchar(255) NOT NULL,
                                          `tokenable_id` bigint(20) unsigned NOT NULL,
                                          `name` varchar(255) NOT NULL,
                                          `token` varchar(64) NOT NULL,
                                          `abilities` text DEFAULT NULL,
                                          `last_used_at` timestamp NULL DEFAULT NULL,
                                          `created_at` timestamp NULL DEFAULT NULL,
                                          `updated_at` timestamp NULL DEFAULT NULL,
                                          PRIMARY KEY (`id`),
                                          UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
                                          KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `preset_shift_shifts_qualifications`
--

DROP TABLE IF EXISTS `preset_shift_shifts_qualifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `preset_shift_shifts_qualifications` (
                                                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                                      `preset_shift_id` bigint(20) unsigned NOT NULL,
                                                      `shift_qualification_id` bigint(20) unsigned NOT NULL,
                                                      `value` smallint(5) unsigned DEFAULT NULL,
                                                      `created_at` timestamp NULL DEFAULT NULL,
                                                      `updated_at` timestamp NULL DEFAULT NULL,
                                                      PRIMARY KEY (`id`),
                                                      KEY `preset_shift_shifts_qualifications_preset_shift_id_foreign` (`preset_shift_id`),
                                                      KEY `shift_qualification_id_foreign` (`shift_qualification_id`),
                                                      CONSTRAINT `preset_shift_shifts_qualifications_preset_shift_id_foreign` FOREIGN KEY (`preset_shift_id`) REFERENCES `preset_shifts` (`id`) ON DELETE CASCADE,
                                                      CONSTRAINT `shift_qualification_id_foreign` FOREIGN KEY (`shift_qualification_id`) REFERENCES `shift_qualifications` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `preset_shift_shifts_qualifications`
--

LOCK TABLES `preset_shift_shifts_qualifications` WRITE;
/*!40000 ALTER TABLE `preset_shift_shifts_qualifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `preset_shift_shifts_qualifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `preset_shifts`
--

DROP TABLE IF EXISTS `preset_shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `preset_shifts` (
                                 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                 `shift_preset_id` bigint(20) unsigned NOT NULL,
                                 `start` time NOT NULL,
                                 `end` time NOT NULL,
                                 `break_minutes` int(11) NOT NULL DEFAULT 0,
                                 `craft_id` bigint(20) unsigned NOT NULL,
                                 `description` varchar(255) DEFAULT NULL,
                                 `created_at` timestamp NULL DEFAULT NULL,
                                 `updated_at` timestamp NULL DEFAULT NULL,
                                 PRIMARY KEY (`id`),
                                 KEY `preset_shifts_shift_preset_id_foreign` (`shift_preset_id`),
                                 CONSTRAINT `preset_shifts_shift_preset_id_foreign` FOREIGN KEY (`shift_preset_id`) REFERENCES `shift_presets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `preset_shifts`
--

LOCK TABLES `preset_shifts` WRITE;
/*!40000 ALTER TABLE `preset_shifts` DISABLE KEYS */;
/*!40000 ALTER TABLE `preset_shifts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `preset_timeline_times`
--

DROP TABLE IF EXISTS `preset_timeline_times`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `preset_timeline_times` (
                                         `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                         `preset_timeline_id` bigint(20) unsigned NOT NULL,
                                         `start` time DEFAULT NULL,
                                         `end` time DEFAULT NULL,
                                         `description` varchar(255) DEFAULT NULL,
                                         `created_at` timestamp NULL DEFAULT NULL,
                                         `updated_at` timestamp NULL DEFAULT NULL,
                                         PRIMARY KEY (`id`),
                                         KEY `preset_timeline_times_preset_timeline_id_foreign` (`preset_timeline_id`),
                                         CONSTRAINT `preset_timeline_times_preset_timeline_id_foreign` FOREIGN KEY (`preset_timeline_id`) REFERENCES `shift_preset_timelines` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `preset_timeline_times`
--

LOCK TABLES `preset_timeline_times` WRITE;
/*!40000 ALTER TABLE `preset_timeline_times` DISABLE KEYS */;
/*!40000 ALTER TABLE `preset_timeline_times` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `print_layout_components`
--

DROP TABLE IF EXISTS `print_layout_components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `print_layout_components` (
                                           `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                           `project_print_layout_id` bigint(20) unsigned NOT NULL,
                                           `component_id` bigint(20) unsigned NOT NULL,
                                           `type` varchar(255) NOT NULL DEFAULT 'body',
                                           `position` int(11) NOT NULL,
                                           `row` int(11) NOT NULL,
                                           `created_at` timestamp NULL DEFAULT NULL,
                                           `updated_at` timestamp NULL DEFAULT NULL,
                                           PRIMARY KEY (`id`),
                                           KEY `print_layout_components_project_print_layout_id_foreign` (`project_print_layout_id`),
                                           KEY `print_layout_components_component_id_foreign` (`component_id`),
                                           CONSTRAINT `print_layout_components_component_id_foreign` FOREIGN KEY (`component_id`) REFERENCES `components` (`id`),
                                           CONSTRAINT `print_layout_components_project_print_layout_id_foreign` FOREIGN KEY (`project_print_layout_id`) REFERENCES `project_print_layouts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `print_layout_components`
--

LOCK TABLES `print_layout_components` WRITE;
/*!40000 ALTER TABLE `print_layout_components` DISABLE KEYS */;
/*!40000 ALTER TABLE `print_layout_components` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_basket_articles`
--

DROP TABLE IF EXISTS `product_basket_articles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_basket_articles` (
                                           `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                           `product_basket_id` bigint(20) unsigned NOT NULL,
                                           `article_id` bigint(20) unsigned NOT NULL,
                                           `quantity` int(11) NOT NULL DEFAULT 1,
                                           `created_at` timestamp NULL DEFAULT NULL,
                                           `updated_at` timestamp NULL DEFAULT NULL,
                                           PRIMARY KEY (`id`),
                                           KEY `product_basket_articles_product_basket_id_foreign` (`product_basket_id`),
                                           KEY `product_basket_articles_article_id_foreign` (`article_id`),
                                           CONSTRAINT `product_basket_articles_article_id_foreign` FOREIGN KEY (`article_id`) REFERENCES `inventory_articles` (`id`) ON DELETE CASCADE,
                                           CONSTRAINT `product_basket_articles_product_basket_id_foreign` FOREIGN KEY (`product_basket_id`) REFERENCES `product_baskets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_basket_articles`
--

LOCK TABLES `product_basket_articles` WRITE;
/*!40000 ALTER TABLE `product_basket_articles` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_basket_articles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_baskets`
--

DROP TABLE IF EXISTS `product_baskets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_baskets` (
                                   `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                   `user_id` bigint(20) unsigned NOT NULL,
                                   `name` varchar(255) NOT NULL DEFAULT 'Standard',
                                   `created_at` timestamp NULL DEFAULT NULL,
                                   `updated_at` timestamp NULL DEFAULT NULL,
                                   PRIMARY KEY (`id`),
                                   KEY `product_baskets_user_id_foreign` (`user_id`),
                                   CONSTRAINT `product_baskets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_baskets`
--

LOCK TABLES `product_baskets` WRITE;
/*!40000 ALTER TABLE `product_baskets` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_baskets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_component_values`
--

DROP TABLE IF EXISTS `project_component_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_component_values` (
                                            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                            `project_id` bigint(20) unsigned NOT NULL,
                                            `component_id` bigint(20) unsigned NOT NULL,
                                            `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
                                            `created_at` timestamp NULL DEFAULT NULL,
                                            `updated_at` timestamp NULL DEFAULT NULL,
                                            PRIMARY KEY (`id`),
                                            KEY `project_component_values_project_id_foreign` (`project_id`),
                                            KEY `project_component_values_component_id_foreign` (`component_id`),
                                            CONSTRAINT `project_component_values_component_id_foreign` FOREIGN KEY (`component_id`) REFERENCES `components` (`id`) ON DELETE CASCADE,
                                            CONSTRAINT `project_component_values_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_component_values`
--

LOCK TABLES `project_component_values` WRITE;
/*!40000 ALTER TABLE `project_component_values` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_component_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_file_user`
--

DROP TABLE IF EXISTS `project_file_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_file_user` (
                                     `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                     `project_file_id` bigint(20) NOT NULL,
                                     `user_id` bigint(20) NOT NULL,
                                     `created_at` timestamp NULL DEFAULT NULL,
                                     `updated_at` timestamp NULL DEFAULT NULL,
                                     PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_file_user`
--

LOCK TABLES `project_file_user` WRITE;
/*!40000 ALTER TABLE `project_file_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_file_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_files`
--

DROP TABLE IF EXISTS `project_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_files` (
                                 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                 `tab_id` int(11) DEFAULT NULL,
                                 `name` varchar(255) NOT NULL,
                                 `basename` varchar(255) NOT NULL,
                                 `project_id` bigint(20) unsigned NOT NULL,
                                 `deleted_at` timestamp NULL DEFAULT NULL,
                                 `created_at` timestamp NULL DEFAULT NULL,
                                 `updated_at` timestamp NULL DEFAULT NULL,
                                 PRIMARY KEY (`id`),
                                 UNIQUE KEY `project_files_basename_unique` (`basename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_files`
--

LOCK TABLES `project_files` WRITE;
/*!40000 ALTER TABLE `project_files` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_groups`
--

DROP TABLE IF EXISTS `project_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_groups` (
                                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                  `project_id` bigint(20) NOT NULL,
                                  `group_id` bigint(20) NOT NULL,
                                  `created_at` timestamp NULL DEFAULT NULL,
                                  `updated_at` timestamp NULL DEFAULT NULL,
                                  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_groups`
--

LOCK TABLES `project_groups` WRITE;
/*!40000 ALTER TABLE `project_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_management_builders`
--

DROP TABLE IF EXISTS `project_management_builders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_management_builders` (
                                               `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                               `name` varchar(255) NOT NULL,
                                               `order` smallint(6) NOT NULL,
                                               `is_active` tinyint(1) NOT NULL DEFAULT 1,
                                               `type` varchar(255) NOT NULL,
                                               `deletable` tinyint(1) NOT NULL DEFAULT 1,
                                               `component_id` bigint(20) unsigned DEFAULT NULL,
                                               `created_at` timestamp NULL DEFAULT NULL,
                                               `updated_at` timestamp NULL DEFAULT NULL,
                                               PRIMARY KEY (`id`),
                                               KEY `project_management_builders_component_id_foreign` (`component_id`),
                                               CONSTRAINT `project_management_builders_component_id_foreign` FOREIGN KEY (`component_id`) REFERENCES `components` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_management_builders`
--

LOCK TABLES `project_management_builders` WRITE;
/*!40000 ALTER TABLE `project_management_builders` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_management_builders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_print_layouts`
--

DROP TABLE IF EXISTS `project_print_layouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_print_layouts` (
                                         `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                         `name` varchar(255) NOT NULL,
                                         `description` varchar(255) DEFAULT NULL,
                                         `is_default` tinyint(1) NOT NULL DEFAULT 0,
                                         `columns_header` int(11) NOT NULL DEFAULT 1,
                                         `columns_footer` int(11) NOT NULL DEFAULT 1,
                                         `columns_body` int(11) NOT NULL DEFAULT 1,
                                         `notes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '{"header":[],"footer":[]}' CHECK (json_valid(`notes`)),
                                         `order` int(11) NOT NULL,
                                         `is_active` tinyint(1) NOT NULL DEFAULT 1,
                                         `user_id` bigint(20) unsigned NOT NULL,
                                         `permission` varchar(255) NOT NULL DEFAULT 'allCanPrint',
                                         `created_at` timestamp NULL DEFAULT NULL,
                                         `updated_at` timestamp NULL DEFAULT NULL,
                                         PRIMARY KEY (`id`),
                                         KEY `project_print_layouts_user_id_foreign` (`user_id`),
                                         CONSTRAINT `project_print_layouts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_print_layouts`
--

LOCK TABLES `project_print_layouts` WRITE;
/*!40000 ALTER TABLE `project_print_layouts` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_print_layouts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_roles`
--

DROP TABLE IF EXISTS `project_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_roles` (
                                 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                 `name` varchar(255) NOT NULL,
                                 `created_at` timestamp NULL DEFAULT NULL,
                                 `updated_at` timestamp NULL DEFAULT NULL,
                                 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_roles`
--

LOCK TABLES `project_roles` WRITE;
/*!40000 ALTER TABLE `project_roles` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_sector`
--

DROP TABLE IF EXISTS `project_sector`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_sector` (
                                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                  `sector_id` bigint(20) unsigned NOT NULL,
                                  `is_main` tinyint(1) NOT NULL DEFAULT 0,
                                  `project_id` bigint(20) unsigned NOT NULL,
                                  `created_at` timestamp NULL DEFAULT NULL,
                                  `updated_at` timestamp NULL DEFAULT NULL,
                                  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_sector`
--

LOCK TABLES `project_sector` WRITE;
/*!40000 ALTER TABLE `project_sector` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_sector` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_shift_contacts`
--

DROP TABLE IF EXISTS `project_shift_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_shift_contacts` (
                                          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                          `project_id` bigint(20) unsigned NOT NULL,
                                          `user_id` bigint(20) unsigned NOT NULL,
                                          `created_at` timestamp NULL DEFAULT NULL,
                                          `updated_at` timestamp NULL DEFAULT NULL,
                                          `deleted_at` timestamp NULL DEFAULT NULL,
                                          PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_shift_contacts`
--

LOCK TABLES `project_shift_contacts` WRITE;
/*!40000 ALTER TABLE `project_shift_contacts` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_shift_contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_shift_relevant_event_types`
--

DROP TABLE IF EXISTS `project_shift_relevant_event_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_shift_relevant_event_types` (
                                                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                                      `project_id` bigint(20) unsigned NOT NULL,
                                                      `event_type_id` bigint(20) unsigned NOT NULL,
                                                      `created_at` timestamp NULL DEFAULT NULL,
                                                      `updated_at` timestamp NULL DEFAULT NULL,
                                                      `deleted_at` timestamp NULL DEFAULT NULL,
                                                      PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_shift_relevant_event_types`
--

LOCK TABLES `project_shift_relevant_event_types` WRITE;
/*!40000 ALTER TABLE `project_shift_relevant_event_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_shift_relevant_event_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_states`
--

DROP TABLE IF EXISTS `project_states`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_states` (
                                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                  `name` varchar(255) NOT NULL,
                                  `color` varchar(255) NOT NULL,
                                  `is_planning` tinyint(1) DEFAULT 0,
                                  `created_at` timestamp NULL DEFAULT NULL,
                                  `updated_at` timestamp NULL DEFAULT NULL,
                                  `deleted_at` timestamp NULL DEFAULT NULL,
                                  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_states`
--

LOCK TABLES `project_states` WRITE;
/*!40000 ALTER TABLE `project_states` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_states` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_tab_sidebar_tabs`
--

DROP TABLE IF EXISTS `project_tab_sidebar_tabs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_tab_sidebar_tabs` (
                                            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                            `project_tab_id` bigint(20) unsigned NOT NULL,
                                            `name` varchar(255) NOT NULL,
                                            `order` int(11) NOT NULL,
                                            `created_at` timestamp NULL DEFAULT NULL,
                                            `updated_at` timestamp NULL DEFAULT NULL,
                                            PRIMARY KEY (`id`),
                                            KEY `project_tab_sidebar_tabs_project_tab_id_foreign` (`project_tab_id`),
                                            CONSTRAINT `project_tab_sidebar_tabs_project_tab_id_foreign` FOREIGN KEY (`project_tab_id`) REFERENCES `project_tabs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_tab_sidebar_tabs`
--

LOCK TABLES `project_tab_sidebar_tabs` WRITE;
/*!40000 ALTER TABLE `project_tab_sidebar_tabs` DISABLE KEYS */;
INSERT INTO `project_tab_sidebar_tabs` VALUES
                                           (1,1,'Projektinformationen',1,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                           (2,2,'Projektinformationen',1,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                           (3,3,'Projektinformationen',1,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                           (4,4,'Schichtinformationen',1,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                           (5,5,'Budgetinformationen',1,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                           (6,6,'Projektinformationen',1,'2026-04-21 06:09:24','2026-04-21 06:09:24');
/*!40000 ALTER TABLE `project_tab_sidebar_tabs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_tab_visible_departments`
--

DROP TABLE IF EXISTS `project_tab_visible_departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_tab_visible_departments` (
                                                   `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                                   `project_tab_id` bigint(20) unsigned NOT NULL,
                                                   `department_id` bigint(20) unsigned NOT NULL,
                                                   `created_at` timestamp NULL DEFAULT NULL,
                                                   `updated_at` timestamp NULL DEFAULT NULL,
                                                   PRIMARY KEY (`id`),
                                                   UNIQUE KEY `ptvd_tab_dept_uq` (`project_tab_id`,`department_id`),
                                                   KEY `ptvd_dept_fk` (`department_id`),
                                                   CONSTRAINT `ptvd_dept_fk` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
                                                   CONSTRAINT `ptvd_tab_fk` FOREIGN KEY (`project_tab_id`) REFERENCES `project_tabs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_tab_visible_departments`
--

LOCK TABLES `project_tab_visible_departments` WRITE;
/*!40000 ALTER TABLE `project_tab_visible_departments` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_tab_visible_departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_tab_visible_users`
--

DROP TABLE IF EXISTS `project_tab_visible_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_tab_visible_users` (
                                             `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                             `project_tab_id` bigint(20) unsigned NOT NULL,
                                             `user_id` bigint(20) unsigned NOT NULL,
                                             `created_at` timestamp NULL DEFAULT NULL,
                                             `updated_at` timestamp NULL DEFAULT NULL,
                                             PRIMARY KEY (`id`),
                                             UNIQUE KEY `ptvu_tab_user_uq` (`project_tab_id`,`user_id`),
                                             KEY `ptvu_user_fk` (`user_id`),
                                             CONSTRAINT `ptvu_tab_fk` FOREIGN KEY (`project_tab_id`) REFERENCES `project_tabs` (`id`) ON DELETE CASCADE,
                                             CONSTRAINT `ptvu_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_tab_visible_users`
--

LOCK TABLES `project_tab_visible_users` WRITE;
/*!40000 ALTER TABLE `project_tab_visible_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_tab_visible_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_tabs`
--

DROP TABLE IF EXISTS `project_tabs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_tabs` (
                                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                `name` varchar(30) NOT NULL,
                                `default` tinyint(1) NOT NULL DEFAULT 0,
                                `visible_for_all` tinyint(1) NOT NULL DEFAULT 1,
                                `order` int(11) NOT NULL,
                                `created_at` timestamp NULL DEFAULT NULL,
                                `updated_at` timestamp NULL DEFAULT NULL,
                                PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_tabs`
--

LOCK TABLES `project_tabs` WRITE;
/*!40000 ALTER TABLE `project_tabs` DISABLE KEYS */;
INSERT INTO `project_tabs` VALUES
                               (1,'Project Information',0,1,1,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                               (2,'Schedule',0,1,2,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                               (3,'Checklists',0,1,3,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                               (4,'Shifts',0,1,4,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                               (5,'Budget',0,1,5,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                               (6,'Comments',0,1,6,'2026-04-21 06:09:24','2026-04-21 06:09:24');
/*!40000 ALTER TABLE `project_tabs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_user`
--

DROP TABLE IF EXISTS `project_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_user` (
                                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                `project_id` int(11) NOT NULL,
                                `user_id` int(11) NOT NULL,
                                `access_budget` tinyint(1) NOT NULL DEFAULT 0,
                                `is_manager` tinyint(1) NOT NULL DEFAULT 0,
                                `can_write` tinyint(1) NOT NULL DEFAULT 0,
                                `delete_permission` tinyint(1) NOT NULL DEFAULT 0,
                                `created_at` timestamp NULL DEFAULT NULL,
                                `updated_at` timestamp NULL DEFAULT NULL,
                                `roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`roles`)),
                                PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_user`
--

LOCK TABLES `project_user` WRITE;
/*!40000 ALTER TABLE `project_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `projects` (
                            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                            `name` varchar(255) NOT NULL,
                            `color` varchar(255) DEFAULT NULL,
                            `icon` varchar(255) DEFAULT NULL,
                            `marked_as_done` tinyint(1) NOT NULL DEFAULT 0,
                            `artists` longtext NOT NULL DEFAULT '',
                            `shift_description` longtext DEFAULT NULL,
                            `number_of_participants` varchar(255) DEFAULT NULL,
                            `key_visual_path` varchar(255) DEFAULT NULL,
                            `cost_center_id` bigint(20) unsigned DEFAULT NULL,
                            `gema` tinyint(1) NOT NULL DEFAULT 0,
                            `cost_center_description` varchar(255) DEFAULT NULL,
                            `created_at` timestamp NULL DEFAULT NULL,
                            `updated_at` timestamp NULL DEFAULT NULL,
                            `deleted_at` timestamp NULL DEFAULT NULL,
                            `is_group` tinyint(1) NOT NULL DEFAULT 0,
                            `state` int(11) DEFAULT NULL,
                            `budget_deadline` date DEFAULT NULL,
                            `pinned_by_users` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pinned_by_users`)),
                            `user_id` bigint(20) unsigned DEFAULT NULL,
                            PRIMARY KEY (`id`),
                            KEY `projects_cost_center_id_foreign` (`cost_center_id`),
                            KEY `projects_user_id_foreign` (`user_id`),
                            CONSTRAINT `projects_cost_center_id_foreign` FOREIGN KEY (`cost_center_id`) REFERENCES `cost_centers` (`id`) ON DELETE SET NULL,
                            CONSTRAINT `projects_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projects`
--

LOCK TABLES `projects` WRITE;
/*!40000 ALTER TABLE `projects` DISABLE KEYS */;
/*!40000 ALTER TABLE `projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
                                        `permission_id` bigint(20) unsigned NOT NULL,
                                        `role_id` bigint(20) unsigned NOT NULL,
                                        PRIMARY KEY (`permission_id`,`role_id`),
                                        KEY `role_has_permissions_role_id_foreign` (`role_id`),
                                        CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
                                        CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
                         `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                         `name` varchar(255) NOT NULL,
                         `translation_key` varchar(255) DEFAULT NULL,
                         `guard_name` varchar(255) NOT NULL,
                         `created_at` timestamp NULL DEFAULT NULL,
                         `updated_at` timestamp NULL DEFAULT NULL,
                         `name_de` varchar(255) DEFAULT NULL,
                         `tooltipText` longtext DEFAULT NULL,
                         `tooltipKey` varchar(255) DEFAULT NULL,
                         PRIMARY KEY (`id`),
                         UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES
    (1,'artwork admin','artwork admin','web','2026-04-21 06:09:24','2026-04-21 06:09:24','artwork-Admin','Der Admin hat alle Berechtigungen im System\n                und kann somit alles sehen und bearbeiten.','The admin has all permissions in the system and can therefore see and edit everything.');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `room_attributes`
--

DROP TABLE IF EXISTS `room_attributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_attributes` (
                                   `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                   `name` varchar(255) NOT NULL,
                                   `created_at` timestamp NULL DEFAULT NULL,
                                   `updated_at` timestamp NULL DEFAULT NULL,
                                   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `room_attributes`
--

LOCK TABLES `room_attributes` WRITE;
/*!40000 ALTER TABLE `room_attributes` DISABLE KEYS */;
/*!40000 ALTER TABLE `room_attributes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `room_categories`
--

DROP TABLE IF EXISTS `room_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_categories` (
                                   `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                   `name` varchar(255) NOT NULL,
                                   `created_at` timestamp NULL DEFAULT NULL,
                                   `updated_at` timestamp NULL DEFAULT NULL,
                                   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `room_categories`
--

LOCK TABLES `room_categories` WRITE;
/*!40000 ALTER TABLE `room_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `room_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `room_files`
--

DROP TABLE IF EXISTS `room_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_files` (
                              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                              `name` varchar(255) NOT NULL,
                              `basename` varchar(255) NOT NULL,
                              `room_id` bigint(20) unsigned NOT NULL,
                              `deleted_at` timestamp NULL DEFAULT NULL,
                              `created_at` timestamp NULL DEFAULT NULL,
                              `updated_at` timestamp NULL DEFAULT NULL,
                              PRIMARY KEY (`id`),
                              UNIQUE KEY `room_files_basename_unique` (`basename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `room_files`
--

LOCK TABLES `room_files` WRITE;
/*!40000 ALTER TABLE `room_files` DISABLE KEYS */;
/*!40000 ALTER TABLE `room_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `room_room_attribute`
--

DROP TABLE IF EXISTS `room_room_attribute`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_room_attribute` (
                                       `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                       `room_id` int(11) NOT NULL,
                                       `room_attribute_id` int(11) NOT NULL,
                                       `created_at` timestamp NULL DEFAULT NULL,
                                       `updated_at` timestamp NULL DEFAULT NULL,
                                       PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `room_room_attribute`
--

LOCK TABLES `room_room_attribute` WRITE;
/*!40000 ALTER TABLE `room_room_attribute` DISABLE KEYS */;
/*!40000 ALTER TABLE `room_room_attribute` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `room_room_category`
--

DROP TABLE IF EXISTS `room_room_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_room_category` (
                                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                      `room_id` int(11) NOT NULL,
                                      `room_category_id` int(11) NOT NULL,
                                      `created_at` timestamp NULL DEFAULT NULL,
                                      `updated_at` timestamp NULL DEFAULT NULL,
                                      PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `room_room_category`
--

LOCK TABLES `room_room_category` WRITE;
/*!40000 ALTER TABLE `room_room_category` DISABLE KEYS */;
/*!40000 ALTER TABLE `room_room_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `room_shift_filter`
--

DROP TABLE IF EXISTS `room_shift_filter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_shift_filter` (
                                     `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                     `shift_filter_id` int(11) NOT NULL,
                                     `room_id` int(11) NOT NULL,
                                     `created_at` timestamp NULL DEFAULT NULL,
                                     `updated_at` timestamp NULL DEFAULT NULL,
                                     PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `room_shift_filter`
--

LOCK TABLES `room_shift_filter` WRITE;
/*!40000 ALTER TABLE `room_shift_filter` DISABLE KEYS */;
/*!40000 ALTER TABLE `room_shift_filter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `room_user`
--

DROP TABLE IF EXISTS `room_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_user` (
                             `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                             `room_id` int(11) NOT NULL,
                             `user_id` int(11) NOT NULL,
                             `is_admin` tinyint(1) NOT NULL DEFAULT 0,
                             `can_request` tinyint(1) NOT NULL DEFAULT 0,
                             `created_at` timestamp NULL DEFAULT NULL,
                             `updated_at` timestamp NULL DEFAULT NULL,
                             PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `room_user`
--

LOCK TABLES `room_user` WRITE;
/*!40000 ALTER TABLE `room_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `room_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rooms`
--

DROP TABLE IF EXISTS `rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rooms` (
                         `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                         `name` varchar(255) NOT NULL,
                         `description` longtext DEFAULT NULL,
                         `order` int(11) NOT NULL,
                         `temporary` tinyint(1) NOT NULL DEFAULT 0,
                         `everyone_can_book` tinyint(1) NOT NULL DEFAULT 0,
                         `fallback_room` tinyint(1) NOT NULL DEFAULT 0,
                         `start_date` timestamp NULL DEFAULT NULL,
                         `end_date` timestamp NULL DEFAULT NULL,
                         `area_id` bigint(20) unsigned NOT NULL,
                         `user_id` bigint(20) unsigned NOT NULL,
                         `position` int(11) NOT NULL DEFAULT 0,
                         `created_at` timestamp NULL DEFAULT NULL,
                         `updated_at` timestamp NULL DEFAULT NULL,
                         `deleted_at` timestamp NULL DEFAULT NULL,
                         `relevant_for_disposition` tinyint(1) NOT NULL DEFAULT 1,
                         PRIMARY KEY (`id`),
                         KEY `rooms_area_id_foreign` (`area_id`),
                         KEY `rooms_user_id_foreign` (`user_id`),
                         KEY `idx_rooms_disposition` (`relevant_for_disposition`),
                         KEY `idx_rooms_position` (`position`),
                         CONSTRAINT `rooms_area_id_foreign` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`),
                         CONSTRAINT `rooms_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rooms`
--

LOCK TABLES `rooms` WRITE;
/*!40000 ALTER TABLE `rooms` DISABLE KEYS */;
/*!40000 ALTER TABLE `rooms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `row_comments`
--

DROP TABLE IF EXISTS `row_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `row_comments` (
                                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                `sub_position_row_id` bigint(20) NOT NULL,
                                `user_id` bigint(20) unsigned NOT NULL,
                                `description` text NOT NULL,
                                `created_at` timestamp NULL DEFAULT NULL,
                                `updated_at` timestamp NULL DEFAULT NULL,
                                `deleted_at` timestamp NULL DEFAULT NULL,
                                PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `row_comments`
--

LOCK TABLES `row_comments` WRITE;
/*!40000 ALTER TABLE `row_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `row_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sage_api_settings`
--

DROP TABLE IF EXISTS `sage_api_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sage_api_settings` (
                                     `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                     `host` longtext NOT NULL,
                                     `endpoint` longtext NOT NULL,
                                     `user` longtext NOT NULL,
                                     `password` longtext NOT NULL,
                                     `bookingDate` date DEFAULT NULL,
                                     `fetchTime` varchar(255) DEFAULT NULL,
                                     `enabled` tinyint(1) NOT NULL,
                                     `created_at` timestamp NULL DEFAULT NULL,
                                     `updated_at` timestamp NULL DEFAULT NULL,
                                     PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sage_api_settings`
--

LOCK TABLES `sage_api_settings` WRITE;
/*!40000 ALTER TABLE `sage_api_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `sage_api_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sage_assigned_data`
--

DROP TABLE IF EXISTS `sage_assigned_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sage_assigned_data` (
                                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                      `column_cell_id` bigint(20) unsigned DEFAULT NULL,
                                      `parent_booking_id` bigint(20) unsigned DEFAULT NULL,
                                      `sage_id` bigint(20) unsigned NOT NULL,
                                      `tan` bigint(20) unsigned NOT NULL,
                                      `periode` bigint(20) unsigned NOT NULL,
                                      `kto_haben` varchar(255) NOT NULL,
                                      `kreditor` varchar(255) NOT NULL,
                                      `buchungstext` varchar(255) NOT NULL,
                                      `buchungsbetrag` decimal(12,2) NOT NULL,
                                      `belegnummer` varchar(255) NOT NULL,
                                      `belegdatum` varchar(255) NOT NULL,
                                      `kto_soll` varchar(255) NOT NULL,
                                      `sa_kto` varchar(255) NOT NULL,
                                      `kst_traeger` varchar(255) NOT NULL,
                                      `kst_stelle` varchar(255) NOT NULL,
                                      `buchungsdatum` varchar(255) NOT NULL,
                                      `is_collective_booking` tinyint(1) NOT NULL DEFAULT 0,
                                      `created_at` timestamp NULL DEFAULT NULL,
                                      `updated_at` timestamp NULL DEFAULT NULL,
                                      PRIMARY KEY (`id`),
                                      KEY `sage_assigned_data_column_cell_id_foreign` (`column_cell_id`),
                                      KEY `sage_assigned_data_parent_booking_id_index` (`parent_booking_id`),
                                      KEY `sage_assigned_data_sage_id_index` (`sage_id`),
                                      CONSTRAINT `sage_assigned_data_column_cell_id_foreign` FOREIGN KEY (`column_cell_id`) REFERENCES `column_sub_position_row` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sage_assigned_data`
--

LOCK TABLES `sage_assigned_data` WRITE;
/*!40000 ALTER TABLE `sage_assigned_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `sage_assigned_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sage_assigned_data_comments`
--

DROP TABLE IF EXISTS `sage_assigned_data_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sage_assigned_data_comments` (
                                               `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                               `user_id` bigint(20) unsigned DEFAULT NULL,
                                               `sage_assigned_data_id` bigint(20) unsigned NOT NULL,
                                               `comment` longtext NOT NULL,
                                               `created_at` timestamp NULL DEFAULT NULL,
                                               `updated_at` timestamp NULL DEFAULT NULL,
                                               PRIMARY KEY (`id`),
                                               KEY `sage_assigned_data_comments_user_id_foreign` (`user_id`),
                                               KEY `sage_assigned_data_comments_sage_assigned_data_id_foreign` (`sage_assigned_data_id`),
                                               CONSTRAINT `sage_assigned_data_comments_sage_assigned_data_id_foreign` FOREIGN KEY (`sage_assigned_data_id`) REFERENCES `sage_assigned_data` (`id`) ON DELETE CASCADE,
                                               CONSTRAINT `sage_assigned_data_comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sage_assigned_data_comments`
--

LOCK TABLES `sage_assigned_data_comments` WRITE;
/*!40000 ALTER TABLE `sage_assigned_data_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `sage_assigned_data_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sage_not_assigned_data`
--

DROP TABLE IF EXISTS `sage_not_assigned_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sage_not_assigned_data` (
                                          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                          `project_id` bigint(20) unsigned DEFAULT NULL,
                                          `parent_booking_id` bigint(20) unsigned DEFAULT NULL,
                                          `sage_id` bigint(20) unsigned NOT NULL,
                                          `tan` bigint(20) unsigned NOT NULL,
                                          `periode` bigint(20) unsigned NOT NULL,
                                          `kto_haben` varchar(255) NOT NULL,
                                          `kreditor` varchar(255) NOT NULL,
                                          `buchungstext` varchar(255) NOT NULL,
                                          `buchungsbetrag` decimal(12,2) NOT NULL,
                                          `belegnummer` varchar(255) NOT NULL,
                                          `belegdatum` varchar(255) NOT NULL,
                                          `kto_soll` varchar(255) NOT NULL,
                                          `sa_kto` varchar(255) NOT NULL,
                                          `kst_traeger` varchar(255) NOT NULL,
                                          `kst_stelle` varchar(255) NOT NULL,
                                          `buchungsdatum` varchar(255) NOT NULL,
                                          `is_collective_booking` tinyint(1) NOT NULL DEFAULT 0,
                                          `created_at` timestamp NULL DEFAULT NULL,
                                          `updated_at` timestamp NULL DEFAULT NULL,
                                          `deleted_at` timestamp NULL DEFAULT NULL,
                                          PRIMARY KEY (`id`),
                                          KEY `sage_not_assigned_data_project_id_foreign` (`project_id`),
                                          KEY `sage_not_assigned_data_parent_booking_id_index` (`parent_booking_id`),
                                          KEY `sage_not_assigned_data_sage_id_index` (`sage_id`),
                                          CONSTRAINT `sage_not_assigned_data_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sage_not_assigned_data`
--

LOCK TABLES `sage_not_assigned_data` WRITE;
/*!40000 ALTER TABLE `sage_not_assigned_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `sage_not_assigned_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schedulings`
--

DROP TABLE IF EXISTS `schedulings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `schedulings` (
                               `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                               `count` int(11) DEFAULT 0,
                               `user_id` int(11) NOT NULL,
                               `type` varchar(255) NOT NULL,
                               `model_id` int(11) DEFAULT NULL,
                               `model` varchar(255) DEFAULT NULL,
                               `created_at` timestamp NULL DEFAULT NULL,
                               `updated_at` timestamp NULL DEFAULT NULL,
                               PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schedulings`
--

LOCK TABLES `schedulings` WRITE;
/*!40000 ALTER TABLE `schedulings` DISABLE KEYS */;
/*!40000 ALTER TABLE `schedulings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sectors`
--

DROP TABLE IF EXISTS `sectors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sectors` (
                           `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                           `name` varchar(255) NOT NULL,
                           `color` varchar(255) DEFAULT NULL,
                           `created_at` timestamp NULL DEFAULT NULL,
                           `updated_at` timestamp NULL DEFAULT NULL,
                           `deleted_at` timestamp NULL DEFAULT NULL,
                           PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sectors`
--

LOCK TABLES `sectors` WRITE;
/*!40000 ALTER TABLE `sectors` DISABLE KEYS */;
/*!40000 ALTER TABLE `sectors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `series_events`
--

DROP TABLE IF EXISTS `series_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `series_events` (
                                 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                 `frequency_id` int(11) NOT NULL,
                                 `end_date` datetime NOT NULL,
                                 `created_at` timestamp NULL DEFAULT NULL,
                                 `updated_at` timestamp NULL DEFAULT NULL,
                                 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `series_events`
--

LOCK TABLES `series_events` WRITE;
/*!40000 ALTER TABLE `series_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `series_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_provider_assigned_crafts`
--

DROP TABLE IF EXISTS `service_provider_assigned_crafts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_provider_assigned_crafts` (
                                                    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                                    `service_provider_id` bigint(20) unsigned NOT NULL,
                                                    `craft_id` bigint(20) unsigned NOT NULL,
                                                    PRIMARY KEY (`id`),
                                                    KEY `service_provider_assigned_crafts_service_provider_id_foreign` (`service_provider_id`),
                                                    KEY `service_provider_assigned_crafts_craft_id_foreign` (`craft_id`),
                                                    CONSTRAINT `service_provider_assigned_crafts_craft_id_foreign` FOREIGN KEY (`craft_id`) REFERENCES `crafts` (`id`),
                                                    CONSTRAINT `service_provider_assigned_crafts_service_provider_id_foreign` FOREIGN KEY (`service_provider_id`) REFERENCES `service_providers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_provider_assigned_crafts`
--

LOCK TABLES `service_provider_assigned_crafts` WRITE;
/*!40000 ALTER TABLE `service_provider_assigned_crafts` DISABLE KEYS */;
/*!40000 ALTER TABLE `service_provider_assigned_crafts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_provider_shift_qualifications`
--

DROP TABLE IF EXISTS `service_provider_shift_qualifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_provider_shift_qualifications` (
                                                         `service_provider_id` bigint(20) unsigned NOT NULL,
                                                         `shift_qualification_id` bigint(20) unsigned NOT NULL,
                                                         `created_at` timestamp NULL DEFAULT NULL,
                                                         `updated_at` timestamp NULL DEFAULT NULL,
                                                         KEY `service_provider_id_foreign` (`service_provider_id`),
                                                         KEY `service_provider_id_shift_qualification_foreign` (`shift_qualification_id`),
                                                         CONSTRAINT `service_provider_id_foreign` FOREIGN KEY (`service_provider_id`) REFERENCES `service_providers` (`id`) ON DELETE CASCADE,
                                                         CONSTRAINT `service_provider_id_shift_qualification_foreign` FOREIGN KEY (`shift_qualification_id`) REFERENCES `shift_qualifications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_provider_shift_qualifications`
--

LOCK TABLES `service_provider_shift_qualifications` WRITE;
/*!40000 ALTER TABLE `service_provider_shift_qualifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `service_provider_shift_qualifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_providers`
--

DROP TABLE IF EXISTS `service_providers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_providers` (
                                     `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                     `profile_image` varchar(255) DEFAULT NULL,
                                     `provider_name` varchar(255) NOT NULL DEFAULT 'Neuer Dienstleister',
                                     `work_name` varchar(255) DEFAULT NULL,
                                     `work_description` varchar(255) DEFAULT NULL,
                                     `email` varchar(255) DEFAULT NULL,
                                     `phone_number` varchar(255) DEFAULT NULL,
                                     `street` varchar(255) DEFAULT NULL,
                                     `zip_code` varchar(255) DEFAULT NULL,
                                     `location` varchar(255) DEFAULT NULL,
                                     `note` varchar(500) DEFAULT NULL,
                                     `salary_per_hour` int(11) DEFAULT NULL,
                                     `salary_description` longtext DEFAULT NULL,
                                     `created_at` timestamp NULL DEFAULT NULL,
                                     `updated_at` timestamp NULL DEFAULT NULL,
                                     `can_work_shifts` tinyint(1) NOT NULL DEFAULT 0,
                                     `type_of_provider` varchar(255) NOT NULL DEFAULT 'work',
                                     `crm_contact_id` bigint(20) unsigned DEFAULT NULL,
                                     PRIMARY KEY (`id`),
                                     KEY `service_providers_crm_contact_id_foreign` (`crm_contact_id`),
                                     CONSTRAINT `service_providers_crm_contact_id_foreign` FOREIGN KEY (`crm_contact_id`) REFERENCES `crm_contacts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_providers`
--

LOCK TABLES `service_providers` WRITE;
/*!40000 ALTER TABLE `service_providers` DISABLE KEYS */;
/*!40000 ALTER TABLE `service_providers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
                            `id` varchar(255) NOT NULL,
                            `user_id` bigint(20) unsigned DEFAULT NULL,
                            `ip_address` varchar(45) DEFAULT NULL,
                            `user_agent` text DEFAULT NULL,
                            `payload` text NOT NULL,
                            `last_activity` int(11) NOT NULL,
                            PRIMARY KEY (`id`),
                            KEY `sessions_user_id_index` (`user_id`),
                            KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
                            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                            `group` varchar(255) NOT NULL,
                            `name` varchar(255) NOT NULL,
                            `locked` tinyint(1) NOT NULL,
                            `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
                            `created_at` timestamp NULL DEFAULT NULL,
                            `updated_at` timestamp NULL DEFAULT NULL,
                            PRIMARY KEY (`id`),
                            KEY `settings_group_index` (`group`)
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES
                           (1,'general','company_name',0,'\"DTH\"','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (2,'general','setup_finished',0,'false','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (3,'general','big_logo_path',0,'\"\\/logo\\/artwork_logo_big.svg\"','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (4,'general','small_logo_path',0,'\"\\/logo\\/artwork_logo_small.svg\"','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (5,'general','banner_path',0,'\"\\/banner\\/default_banner.svg\"','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (6,'general','impressum_link',0,'\"\"','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (7,'general','business_name',0,'\"Unsere Organisation\"','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (8,'general','privacy_link',0,'\"\"','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (9,'general','email_footer',0,'\"\"','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (10,'general','page_title',0,'\"Artwork\"','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (11,'project','attributes',0,'true','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (12,'project','state',0,'true','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (13,'project','managers',0,'true','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (14,'project','cost_center',0,'true','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (15,'project','budget_deadline',0,'true','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (16,'module_settings','projects',0,'true','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (17,'module_settings','room_assignment',0,'true','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (18,'module_settings','shift_plan',0,'true','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (19,'module_settings','inventory',0,'true','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (20,'module_settings','tasks',0,'true','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (21,'module_settings','sources_of_funding',0,'true','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (22,'module_settings','users',0,'true','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (23,'module_settings','contracts',0,'true','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (24,'holiday','subdivisions',0,'[]','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (25,'holiday','public_holidays',0,'true','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (26,'holiday','school_holidays',0,'true','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (27,'general','enable_status',0,'false','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (28,'shift-settings','use_first_name_for_sort',0,'false','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (29,'project','show_artists',0,'true','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (30,'calendar','start',0,'\"00:00\"','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (31,'calendar','end',0,'\"08:00\"','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (32,'general','invitation_email',0,'\"\"','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (33,'general','business_email',0,'\"\"','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (34,'general','budget_account_management_global',0,'false','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (35,'general','allowed_project_file_mimetypes',0,'[\"*\"]','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (36,'general','allowed_room_file_mimetypes',0,'[\"*\"]','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (37,'general','allowed_branding_file_mimetypes',0,'[\"*\"]','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (38,'general','allowed_contract_file_mimetypes',0,'[\"*\"]','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (39,'general','allowed_project_file_size',0,'150','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (40,'general','allowed_room_file_size',0,'150','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (41,'general','allowed_branding_file_size',0,'150','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (42,'general','allowed_contract_file_size',0,'150','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (43,'general','start_night_time',0,'\"22:00\"','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (44,'general','end_night_time',0,'\"06:00\"','2026-04-21 06:08:24','2026-04-21 06:08:24'),
                           (45,'general','playing_time_window_start',0,'\"\"','2026-04-21 06:08:25','2026-04-21 06:08:25'),
                           (46,'general','playing_time_window_end',0,'\"\"','2026-04-21 06:08:25','2026-04-21 06:08:25'),
                           (47,'general','shift_commit_workflow_enabled',0,'false','2026-04-21 06:08:25','2026-04-21 06:08:25'),
                           (48,'general','event_time_length_minutes',0,'60','2026-04-21 06:08:25','2026-04-21 06:08:25'),
                           (49,'general','warn_multiple_assignments',0,'false','2026-04-21 06:08:25','2026-04-21 06:08:25'),
                           (50,'general','event_start_time',0,'\"09:00\"','2026-04-21 06:08:26','2026-04-21 06:08:26'),
                           (51,'general','breakfast_deduction_per_day',0,'5.6','2026-04-21 06:08:26','2026-04-21 06:08:26'),
                           (52,'general','letterhead_name',0,'\"\"','2026-04-21 06:08:26','2026-04-21 06:08:26'),
                           (53,'general','letterhead_street',0,'\"\"','2026-04-21 06:08:26','2026-04-21 06:08:26'),
                           (54,'general','letterhead_zip_code',0,'\"\"','2026-04-21 06:08:26','2026-04-21 06:08:26'),
                           (55,'general','letterhead_city',0,'\"\"','2026-04-21 06:08:26','2026-04-21 06:08:26'),
                           (56,'general','letterhead_email',0,'\"\"','2026-04-21 06:08:26','2026-04-21 06:08:26'),
                           (57,'shift-settings','calendar_abo_show_all_shifts',0,'false','2026-04-21 06:08:26','2026-04-21 06:08:26'),
                           (58,'general','event_all_day_default',0,'false','2026-04-21 06:08:26','2026-04-21 06:08:26'),
                           (59,'module_settings','crm',0,'true','2026-04-21 06:08:26','2026-04-21 06:08:26'),
                           (60,'general','per_diem_export_counter',0,'0','2026-04-21 06:08:26','2026-04-21 06:08:26'),
                           (61,'general','inventory_detailed_articles_always_quantity_one',0,'false','2026-04-21 06:08:26','2026-04-21 06:08:26');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_commit_workflow_requests`
--

DROP TABLE IF EXISTS `shift_commit_workflow_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_commit_workflow_requests` (
                                                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                                  `requested_by_id` bigint(20) unsigned NOT NULL,
                                                  `start_date` date NOT NULL,
                                                  `end_date` date NOT NULL,
                                                  `approved_by_id` bigint(20) unsigned DEFAULT NULL,
                                                  `declined_by_id` bigint(20) unsigned DEFAULT NULL,
                                                  `status` varchar(255) NOT NULL DEFAULT 'pending',
                                                  `reason` text DEFAULT NULL,
                                                  `created_at` timestamp NULL DEFAULT NULL,
                                                  `updated_at` timestamp NULL DEFAULT NULL,
                                                  PRIMARY KEY (`id`),
                                                  KEY `shift_commit_workflow_requests_requested_by_id_foreign` (`requested_by_id`),
                                                  KEY `shift_commit_workflow_requests_approved_by_id_foreign` (`approved_by_id`),
                                                  KEY `shift_commit_workflow_requests_declined_by_id_foreign` (`declined_by_id`),
                                                  CONSTRAINT `shift_commit_workflow_requests_approved_by_id_foreign` FOREIGN KEY (`approved_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
                                                  CONSTRAINT `shift_commit_workflow_requests_declined_by_id_foreign` FOREIGN KEY (`declined_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
                                                  CONSTRAINT `shift_commit_workflow_requests_requested_by_id_foreign` FOREIGN KEY (`requested_by_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_commit_workflow_requests`
--

LOCK TABLES `shift_commit_workflow_requests` WRITE;
/*!40000 ALTER TABLE `shift_commit_workflow_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_commit_workflow_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_commit_workflow_users`
--

DROP TABLE IF EXISTS `shift_commit_workflow_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_commit_workflow_users` (
                                               `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                               `user_id` bigint(20) unsigned NOT NULL,
                                               `created_at` timestamp NULL DEFAULT NULL,
                                               `updated_at` timestamp NULL DEFAULT NULL,
                                               PRIMARY KEY (`id`),
                                               KEY `shift_commit_workflow_users_user_id_foreign` (`user_id`),
                                               CONSTRAINT `shift_commit_workflow_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_commit_workflow_users`
--

LOCK TABLES `shift_commit_workflow_users` WRITE;
/*!40000 ALTER TABLE `shift_commit_workflow_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_commit_workflow_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_filters`
--

DROP TABLE IF EXISTS `shift_filters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_filters` (
                                 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                 `name` varchar(255) NOT NULL,
                                 `user_id` bigint(20) unsigned NOT NULL,
                                 `created_at` timestamp NULL DEFAULT NULL,
                                 `updated_at` timestamp NULL DEFAULT NULL,
                                 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_filters`
--

LOCK TABLES `shift_filters` WRITE;
/*!40000 ALTER TABLE `shift_filters` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_filters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_global_qualifications`
--

DROP TABLE IF EXISTS `shift_global_qualifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_global_qualifications` (
                                               `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                               `shift_id` bigint(20) unsigned NOT NULL,
                                               `global_qualification_id` bigint(20) unsigned NOT NULL,
                                               `quantity` int(11) NOT NULL DEFAULT 0,
                                               `created_at` timestamp NULL DEFAULT NULL,
                                               `updated_at` timestamp NULL DEFAULT NULL,
                                               PRIMARY KEY (`id`),
                                               UNIQUE KEY `sgq_shift_qual_uq` (`shift_id`,`global_qualification_id`),
                                               KEY `fk_sgq_gq` (`global_qualification_id`),
                                               CONSTRAINT `fk_sgq_gq` FOREIGN KEY (`global_qualification_id`) REFERENCES `global_qualifications` (`id`) ON DELETE CASCADE,
                                               CONSTRAINT `fk_sgq_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_global_qualifications`
--

LOCK TABLES `shift_global_qualifications` WRITE;
/*!40000 ALTER TABLE `shift_global_qualifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_global_qualifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_groups`
--

DROP TABLE IF EXISTS `shift_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_groups` (
                                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                `name` varchar(255) NOT NULL,
                                `color` varchar(255) NOT NULL DEFAULT '#9E1C60',
                                `icon` varchar(255) DEFAULT NULL,
                                `created_at` timestamp NULL DEFAULT NULL,
                                `updated_at` timestamp NULL DEFAULT NULL,
                                PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_groups`
--

LOCK TABLES `shift_groups` WRITE;
/*!40000 ALTER TABLE `shift_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_plan_comments`
--

DROP TABLE IF EXISTS `shift_plan_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_plan_comments` (
                                       `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                       `comment` text DEFAULT NULL,
                                       `date` date NOT NULL,
                                       `created_by` bigint(20) unsigned DEFAULT NULL,
                                       `commentable_type` varchar(255) NOT NULL,
                                       `commentable_id` bigint(20) unsigned NOT NULL,
                                       `created_at` timestamp NULL DEFAULT NULL,
                                       `updated_at` timestamp NULL DEFAULT NULL,
                                       PRIMARY KEY (`id`),
                                       KEY `shift_plan_comments_commentable_type_commentable_id_index` (`commentable_type`,`commentable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_plan_comments`
--

LOCK TABLES `shift_plan_comments` WRITE;
/*!40000 ALTER TABLE `shift_plan_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_plan_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_plan_request_changes`
--

DROP TABLE IF EXISTS `shift_plan_request_changes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_plan_request_changes` (
                                              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                              `shift_plan_request_id` bigint(20) unsigned NOT NULL,
                                              `subject_type` varchar(255) NOT NULL,
                                              `subject_id` bigint(20) unsigned NOT NULL,
                                              `change_type` varchar(50) NOT NULL,
                                              `field_changes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`field_changes`)),
                                              `affected_user_id` bigint(20) unsigned DEFAULT NULL,
                                              `changed_by_user_id` bigint(20) unsigned DEFAULT NULL,
                                              `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
                                              `created_at` timestamp NULL DEFAULT NULL,
                                              `updated_at` timestamp NULL DEFAULT NULL,
                                              PRIMARY KEY (`id`),
                                              KEY `shift_plan_request_changes_affected_user_id_foreign` (`affected_user_id`),
                                              KEY `shift_plan_request_changes_changed_by_user_id_foreign` (`changed_by_user_id`),
                                              KEY `shift_plan_request_changes_shift_plan_request_id_index` (`shift_plan_request_id`),
                                              KEY `shift_plan_request_changes_subject_type_subject_id_index` (`subject_type`,`subject_id`),
                                              KEY `shift_plan_request_changes_changed_at_index` (`changed_at`),
                                              CONSTRAINT `shift_plan_request_changes_affected_user_id_foreign` FOREIGN KEY (`affected_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
                                              CONSTRAINT `shift_plan_request_changes_changed_by_user_id_foreign` FOREIGN KEY (`changed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
                                              CONSTRAINT `shift_plan_request_changes_shift_plan_request_id_foreign` FOREIGN KEY (`shift_plan_request_id`) REFERENCES `shift_plan_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_plan_request_changes`
--

LOCK TABLES `shift_plan_request_changes` WRITE;
/*!40000 ALTER TABLE `shift_plan_request_changes` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_plan_request_changes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_plan_request_shifts`
--

DROP TABLE IF EXISTS `shift_plan_request_shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_plan_request_shifts` (
                                             `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                             `shift_plan_request_id` bigint(20) unsigned NOT NULL,
                                             `shift_id` bigint(20) unsigned NOT NULL,
                                             `snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`snapshot`)),
                                             `created_at` timestamp NULL DEFAULT NULL,
                                             `updated_at` timestamp NULL DEFAULT NULL,
                                             PRIMARY KEY (`id`),
                                             UNIQUE KEY `shift_plan_request_shifts_shift_plan_request_id_shift_id_unique` (`shift_plan_request_id`,`shift_id`),
                                             KEY `shift_plan_request_shifts_shift_id_foreign` (`shift_id`),
                                             KEY `shift_plan_request_shifts_shift_plan_request_id_index` (`shift_plan_request_id`),
                                             CONSTRAINT `shift_plan_request_shifts_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE,
                                             CONSTRAINT `shift_plan_request_shifts_shift_plan_request_id_foreign` FOREIGN KEY (`shift_plan_request_id`) REFERENCES `shift_plan_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_plan_request_shifts`
--

LOCK TABLES `shift_plan_request_shifts` WRITE;
/*!40000 ALTER TABLE `shift_plan_request_shifts` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_plan_request_shifts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_plan_requests`
--

DROP TABLE IF EXISTS `shift_plan_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_plan_requests` (
                                       `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                       `craft_id` bigint(20) unsigned NOT NULL,
                                       `week_number` tinyint(3) unsigned NOT NULL,
                                       `year` smallint(5) unsigned NOT NULL,
                                       `status` varchar(20) NOT NULL DEFAULT 'pending',
                                       `requested_by_user_id` bigint(20) unsigned NOT NULL,
                                       `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
                                       `reviewed_by_user_id` bigint(20) unsigned DEFAULT NULL,
                                       `reviewed_at` timestamp NULL DEFAULT NULL,
                                       `review_comment` text DEFAULT NULL,
                                       `created_at` timestamp NULL DEFAULT NULL,
                                       `updated_at` timestamp NULL DEFAULT NULL,
                                       `rejected_days` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`rejected_days`)),
                                       `rejected_shifts` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`rejected_shifts`)),
                                       PRIMARY KEY (`id`),
                                       KEY `shift_plan_requests_requested_by_user_id_foreign` (`requested_by_user_id`),
                                       KEY `shift_plan_requests_reviewed_by_user_id_foreign` (`reviewed_by_user_id`),
                                       KEY `shift_plan_requests_craft_id_year_week_number_index` (`craft_id`,`year`,`week_number`),
                                       KEY `shift_plan_requests_status_index` (`status`),
                                       CONSTRAINT `shift_plan_requests_craft_id_foreign` FOREIGN KEY (`craft_id`) REFERENCES `crafts` (`id`) ON DELETE CASCADE,
                                       CONSTRAINT `shift_plan_requests_requested_by_user_id_foreign` FOREIGN KEY (`requested_by_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
                                       CONSTRAINT `shift_plan_requests_reviewed_by_user_id_foreign` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_plan_requests`
--

LOCK TABLES `shift_plan_requests` WRITE;
/*!40000 ALTER TABLE `shift_plan_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_plan_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_preset_group_assignments`
--

DROP TABLE IF EXISTS `shift_preset_group_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_preset_group_assignments` (
                                                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                                  `shift_preset_group_id` bigint(20) unsigned NOT NULL,
                                                  `single_shift_preset_id` bigint(20) unsigned NOT NULL,
                                                  `created_at` timestamp NULL DEFAULT NULL,
                                                  `updated_at` timestamp NULL DEFAULT NULL,
                                                  PRIMARY KEY (`id`),
                                                  KEY `shift_preset_group_assignments_shift_preset_group_id_foreign` (`shift_preset_group_id`),
                                                  KEY `shift_preset_group_assignments_single_shift_preset_id_foreign` (`single_shift_preset_id`),
                                                  CONSTRAINT `shift_preset_group_assignments_shift_preset_group_id_foreign` FOREIGN KEY (`shift_preset_group_id`) REFERENCES `shift_preset_groups` (`id`) ON DELETE CASCADE,
                                                  CONSTRAINT `shift_preset_group_assignments_single_shift_preset_id_foreign` FOREIGN KEY (`single_shift_preset_id`) REFERENCES `single_shift_presets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_preset_group_assignments`
--

LOCK TABLES `shift_preset_group_assignments` WRITE;
/*!40000 ALTER TABLE `shift_preset_group_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_preset_group_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_preset_groups`
--

DROP TABLE IF EXISTS `shift_preset_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_preset_groups` (
                                       `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                       `name` varchar(255) NOT NULL,
                                       `created_at` timestamp NULL DEFAULT NULL,
                                       `updated_at` timestamp NULL DEFAULT NULL,
                                       PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_preset_groups`
--

LOCK TABLES `shift_preset_groups` WRITE;
/*!40000 ALTER TABLE `shift_preset_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_preset_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_preset_timelines`
--

DROP TABLE IF EXISTS `shift_preset_timelines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_preset_timelines` (
                                          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                          `name` varchar(255) NOT NULL,
                                          `description` varchar(255) DEFAULT NULL,
                                          `created_at` timestamp NULL DEFAULT NULL,
                                          `updated_at` timestamp NULL DEFAULT NULL,
                                          PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_preset_timelines`
--

LOCK TABLES `shift_preset_timelines` WRITE;
/*!40000 ALTER TABLE `shift_preset_timelines` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_preset_timelines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_presets`
--

DROP TABLE IF EXISTS `shift_presets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_presets` (
                                 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                 `name` varchar(255) NOT NULL,
                                 `created_at` timestamp NULL DEFAULT NULL,
                                 `updated_at` timestamp NULL DEFAULT NULL,
                                 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_presets`
--

LOCK TABLES `shift_presets` WRITE;
/*!40000 ALTER TABLE `shift_presets` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_presets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_qualifiables`
--

DROP TABLE IF EXISTS `shift_qualifiables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_qualifiables` (
                                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                      `shift_qualification_id` bigint(20) unsigned NOT NULL,
                                      `qualifiable_id` bigint(20) unsigned NOT NULL,
                                      `qualifiable_type` varchar(255) NOT NULL,
                                      `craft_id` bigint(20) unsigned DEFAULT NULL,
                                      `created_at` timestamp NULL DEFAULT NULL,
                                      `updated_at` timestamp NULL DEFAULT NULL,
                                      PRIMARY KEY (`id`),
                                      UNIQUE KEY `shift_qualifiable_unique` (`shift_qualification_id`,`qualifiable_id`,`qualifiable_type`,`craft_id`),
                                      KEY `shift_qualifiables_craft_id_foreign` (`craft_id`),
                                      CONSTRAINT `shift_qualifiables_craft_id_foreign` FOREIGN KEY (`craft_id`) REFERENCES `crafts` (`id`) ON DELETE SET NULL,
                                      CONSTRAINT `shift_qualifiables_shift_qualification_id_foreign` FOREIGN KEY (`shift_qualification_id`) REFERENCES `shift_qualifications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_qualifiables`
--

LOCK TABLES `shift_qualifiables` WRITE;
/*!40000 ALTER TABLE `shift_qualifiables` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_qualifiables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_qualifications`
--

DROP TABLE IF EXISTS `shift_qualifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_qualifications` (
                                        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                        `icon` varchar(255) NOT NULL,
                                        `name` varchar(255) NOT NULL,
                                        `available` tinyint(1) NOT NULL,
                                        `created_at` timestamp NULL DEFAULT NULL,
                                        `updated_at` timestamp NULL DEFAULT NULL,
                                        PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_qualifications`
--

LOCK TABLES `shift_qualifications` WRITE;
/*!40000 ALTER TABLE `shift_qualifications` DISABLE KEYS */;
INSERT INTO `shift_qualifications` VALUES
                                       (1,'user-icon','Mitarbeiter',1,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                       (2,'academic-cap-icon','Meister',1,'2026-04-21 06:09:24','2026-04-21 06:09:24');
/*!40000 ALTER TABLE `shift_qualifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_rule_contract_assignments`
--

DROP TABLE IF EXISTS `shift_rule_contract_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_rule_contract_assignments` (
                                                   `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                                   `shift_rule_id` bigint(20) unsigned NOT NULL,
                                                   `contract_id` bigint(20) unsigned NOT NULL,
                                                   `created_at` timestamp NULL DEFAULT NULL,
                                                   `updated_at` timestamp NULL DEFAULT NULL,
                                                   PRIMARY KEY (`id`),
                                                   UNIQUE KEY `shift_rule_contract_assignments_shift_rule_id_contract_id_unique` (`shift_rule_id`,`contract_id`),
                                                   KEY `shift_rule_contract_assignments_contract_id_foreign` (`contract_id`),
                                                   CONSTRAINT `shift_rule_contract_assignments_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `user_contracts` (`id`) ON DELETE CASCADE,
                                                   CONSTRAINT `shift_rule_contract_assignments_shift_rule_id_foreign` FOREIGN KEY (`shift_rule_id`) REFERENCES `shift_rules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_rule_contract_assignments`
--

LOCK TABLES `shift_rule_contract_assignments` WRITE;
/*!40000 ALTER TABLE `shift_rule_contract_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_rule_contract_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_rule_user_notifications`
--

DROP TABLE IF EXISTS `shift_rule_user_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_rule_user_notifications` (
                                                 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                                 `shift_rule_id` bigint(20) unsigned NOT NULL,
                                                 `user_id` bigint(20) unsigned NOT NULL,
                                                 `created_at` timestamp NULL DEFAULT NULL,
                                                 `updated_at` timestamp NULL DEFAULT NULL,
                                                 PRIMARY KEY (`id`),
                                                 UNIQUE KEY `shift_rule_user_notifications_shift_rule_id_user_id_unique` (`shift_rule_id`,`user_id`),
                                                 KEY `shift_rule_user_notifications_user_id_foreign` (`user_id`),
                                                 CONSTRAINT `shift_rule_user_notifications_shift_rule_id_foreign` FOREIGN KEY (`shift_rule_id`) REFERENCES `shift_rules` (`id`) ON DELETE CASCADE,
                                                 CONSTRAINT `shift_rule_user_notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_rule_user_notifications`
--

LOCK TABLES `shift_rule_user_notifications` WRITE;
/*!40000 ALTER TABLE `shift_rule_user_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_rule_user_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_rule_violations`
--

DROP TABLE IF EXISTS `shift_rule_violations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_rule_violations` (
                                         `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                         `shift_rule_id` bigint(20) unsigned NOT NULL,
                                         `shift_id` bigint(20) unsigned DEFAULT NULL,
                                         `user_id` bigint(20) unsigned NOT NULL,
                                         `violation_date` date NOT NULL,
                                         `violation_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`violation_data`)),
                                         `reason` text DEFAULT NULL,
                                         `ignore_reason` text DEFAULT NULL,
                                         `is_manual` tinyint(1) NOT NULL DEFAULT 0,
                                         `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
                                         `compensation_days` decimal(4,1) DEFAULT NULL,
                                         `compensation_deadline` date DEFAULT NULL,
                                         `compensation_reason` text DEFAULT NULL,
                                         `parent_violation_id` bigint(20) unsigned DEFAULT NULL,
                                         `severity` enum('warning','error') NOT NULL DEFAULT 'warning',
                                         `status` enum('active','resolved','ignored') NOT NULL DEFAULT 'active',
                                         `resolved_at` timestamp NULL DEFAULT NULL,
                                         `resolved_by` bigint(20) unsigned DEFAULT NULL,
                                         `created_at` timestamp NULL DEFAULT NULL,
                                         `updated_at` timestamp NULL DEFAULT NULL,
                                         PRIMARY KEY (`id`),
                                         KEY `shift_rule_violations_shift_rule_id_foreign` (`shift_rule_id`),
                                         KEY `shift_rule_violations_shift_id_violation_date_index` (`shift_id`,`violation_date`),
                                         KEY `shift_rule_violations_user_id_violation_date_index` (`user_id`,`violation_date`),
                                         KEY `shift_rule_violations_created_by_user_id_foreign` (`created_by_user_id`),
                                         KEY `shift_rule_violations_parent_violation_id_foreign` (`parent_violation_id`),
                                         KEY `shift_rule_violations_resolved_by_foreign` (`resolved_by`),
                                         CONSTRAINT `shift_rule_violations_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
                                         CONSTRAINT `shift_rule_violations_parent_violation_id_foreign` FOREIGN KEY (`parent_violation_id`) REFERENCES `shift_rule_violations` (`id`) ON DELETE SET NULL,
                                         CONSTRAINT `shift_rule_violations_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
                                         CONSTRAINT `shift_rule_violations_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE,
                                         CONSTRAINT `shift_rule_violations_shift_rule_id_foreign` FOREIGN KEY (`shift_rule_id`) REFERENCES `shift_rules` (`id`) ON DELETE CASCADE,
                                         CONSTRAINT `shift_rule_violations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_rule_violations`
--

LOCK TABLES `shift_rule_violations` WRITE;
/*!40000 ALTER TABLE `shift_rule_violations` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_rule_violations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_rules`
--

DROP TABLE IF EXISTS `shift_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_rules` (
                               `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                               `name` varchar(255) NOT NULL,
                               `description` text DEFAULT NULL,
                               `trigger_type` varchar(255) NOT NULL,
                               `individual_number_value` decimal(8,2) NOT NULL,
                               `warning_color` varchar(7) NOT NULL DEFAULT '#ff0000',
                               `default_compensation_days` decimal(3,1) DEFAULT NULL,
                               `default_compensation_deadline_days` int(10) unsigned DEFAULT NULL,
                               `is_active` tinyint(1) NOT NULL DEFAULT 1,
                               `created_at` timestamp NULL DEFAULT NULL,
                               `updated_at` timestamp NULL DEFAULT NULL,
                               `deleted_at` timestamp NULL DEFAULT NULL,
                               PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_rules`
--

LOCK TABLES `shift_rules` WRITE;
/*!40000 ALTER TABLE `shift_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_time_presets`
--

DROP TABLE IF EXISTS `shift_time_presets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_time_presets` (
                                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                      `name` varchar(255) NOT NULL,
                                      `start_time` time NOT NULL,
                                      `end_time` time NOT NULL,
                                      `break_time` int(11) NOT NULL DEFAULT 0,
                                      `created_at` timestamp NULL DEFAULT NULL,
                                      `updated_at` timestamp NULL DEFAULT NULL,
                                      PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_time_presets`
--

LOCK TABLES `shift_time_presets` WRITE;
/*!40000 ALTER TABLE `shift_time_presets` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_time_presets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_user`
--

DROP TABLE IF EXISTS `shift_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_user` (
                              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                              `shift_id` bigint(20) unsigned NOT NULL,
                              `short_description` varchar(255) DEFAULT NULL,
                              `start_date` date DEFAULT NULL,
                              `end_date` date DEFAULT NULL,
                              `start_time` time DEFAULT NULL,
                              `end_time` time DEFAULT NULL,
                              `craft_abbreviation` varchar(255) DEFAULT NULL,
                              `user_id` bigint(20) unsigned NOT NULL,
                              `shift_qualification_id` bigint(20) unsigned NOT NULL,
                              `shift_count` bigint(20) unsigned NOT NULL DEFAULT 1,
                              `created_at` timestamp NULL DEFAULT NULL,
                              `updated_at` timestamp NULL DEFAULT NULL,
                              `deleted_at` timestamp NULL DEFAULT NULL,
                              PRIMARY KEY (`id`),
                              KEY `shift_user_shift_qualification_id_foreign` (`shift_qualification_id`),
                              KEY `shift_user_shift_id_foreign` (`shift_id`),
                              KEY `shift_user_user_id_foreign` (`user_id`),
                              CONSTRAINT `shift_user_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE,
                              CONSTRAINT `shift_user_shift_qualification_id_foreign` FOREIGN KEY (`shift_qualification_id`) REFERENCES `shift_qualifications` (`id`),
                              CONSTRAINT `shift_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_user`
--

LOCK TABLES `shift_user` WRITE;
/*!40000 ALTER TABLE `shift_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_workers`
--

DROP TABLE IF EXISTS `shift_workers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_workers` (
                                 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                 `shift_id` bigint(20) unsigned NOT NULL,
                                 `employable_type` varchar(255) NOT NULL,
                                 `employable_id` bigint(20) unsigned NOT NULL,
                                 `shift_qualification_id` bigint(20) unsigned NOT NULL,
                                 `shift_count` bigint(20) unsigned NOT NULL DEFAULT 1,
                                 `craft_abbreviation` varchar(255) DEFAULT NULL,
                                 `short_description` varchar(255) DEFAULT NULL,
                                 `start_date` date DEFAULT NULL,
                                 `end_date` date DEFAULT NULL,
                                 `start_time` time DEFAULT NULL,
                                 `end_time` time DEFAULT NULL,
                                 `workflow_rejection_reason` text DEFAULT NULL,
                                 `created_at` timestamp NULL DEFAULT NULL,
                                 `updated_at` timestamp NULL DEFAULT NULL,
                                 `deleted_at` timestamp NULL DEFAULT NULL,
                                 PRIMARY KEY (`id`),
                                 KEY `shift_workers_employable_type_employable_id_index` (`employable_type`,`employable_id`),
                                 KEY `shift_workers_shift_id_index` (`shift_id`),
                                 KEY `shift_workers_shift_qualification_id_index` (`shift_qualification_id`),
                                 CONSTRAINT `shift_workers_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE,
                                 CONSTRAINT `shift_workers_shift_qualification_id_foreign` FOREIGN KEY (`shift_qualification_id`) REFERENCES `shift_qualifications` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_workers`
--

LOCK TABLES `shift_workers` WRITE;
/*!40000 ALTER TABLE `shift_workers` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_workers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shifts`
--

DROP TABLE IF EXISTS `shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shifts` (
                          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                          `event_id` bigint(20) unsigned DEFAULT NULL,
                          `start_date` date DEFAULT NULL,
                          `end_date` date DEFAULT NULL,
                          `start` time NOT NULL,
                          `end` time NOT NULL,
                          `break_minutes` int(11) NOT NULL DEFAULT 0,
                          `craft_id` bigint(20) unsigned NOT NULL,
                          `description` varchar(255) DEFAULT NULL,
                          `is_committed` tinyint(1) NOT NULL DEFAULT 0,
                          `in_workflow` tinyint(1) NOT NULL DEFAULT 0,
                          `current_request_id` bigint(20) unsigned DEFAULT NULL,
                          `workflow_rejection_reason` text DEFAULT NULL,
                          `shift_uuid` varchar(255) DEFAULT NULL,
                          `event_start_day` date DEFAULT NULL,
                          `event_end_day` date DEFAULT NULL,
                          `created_at` timestamp NULL DEFAULT NULL,
                          `updated_at` timestamp NULL DEFAULT NULL,
                          `committing_user_id` bigint(20) unsigned DEFAULT NULL,
                          `deleted_at` timestamp NULL DEFAULT NULL,
                          `room_id` bigint(20) unsigned DEFAULT NULL,
                          `project_id` bigint(20) unsigned DEFAULT NULL,
                          `shift_group_id` bigint(20) unsigned DEFAULT NULL,
                          PRIMARY KEY (`id`),
                          KEY `shifts_committing_user_id_foreign` (`committing_user_id`),
                          KEY `shifts_event_id_foreign` (`event_id`),
                          KEY `shifts_craft_id_foreign` (`craft_id`),
                          KEY `shifts_room_id_foreign` (`room_id`),
                          KEY `shifts_project_id_foreign` (`project_id`),
                          KEY `shifts_shift_group_id_foreign` (`shift_group_id`),
                          KEY `shifts_current_request_id_foreign` (`current_request_id`),
                          CONSTRAINT `shifts_committing_user_id_foreign` FOREIGN KEY (`committing_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
                          CONSTRAINT `shifts_craft_id_foreign` FOREIGN KEY (`craft_id`) REFERENCES `crafts` (`id`) ON DELETE CASCADE,
                          CONSTRAINT `shifts_current_request_id_foreign` FOREIGN KEY (`current_request_id`) REFERENCES `shift_plan_requests` (`id`) ON DELETE SET NULL,
                          CONSTRAINT `shifts_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL,
                          CONSTRAINT `shifts_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
                          CONSTRAINT `shifts_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL,
                          CONSTRAINT `shifts_shift_group_id_foreign` FOREIGN KEY (`shift_group_id`) REFERENCES `shift_groups` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shifts`
--

LOCK TABLES `shifts` WRITE;
/*!40000 ALTER TABLE `shifts` DISABLE KEYS */;
/*!40000 ALTER TABLE `shifts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shifts_freelancers`
--

DROP TABLE IF EXISTS `shifts_freelancers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shifts_freelancers` (
                                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                      `shift_id` bigint(20) unsigned NOT NULL,
                                      `short_description` varchar(255) DEFAULT NULL,
                                      `start_date` date DEFAULT NULL,
                                      `end_date` date DEFAULT NULL,
                                      `start_time` time DEFAULT NULL,
                                      `end_time` time DEFAULT NULL,
                                      `craft_abbreviation` varchar(255) DEFAULT NULL,
                                      `freelancer_id` bigint(20) unsigned NOT NULL,
                                      `shift_qualification_id` bigint(20) unsigned NOT NULL,
                                      `shift_count` bigint(20) unsigned NOT NULL DEFAULT 1,
                                      `created_at` timestamp NULL DEFAULT NULL,
                                      `updated_at` timestamp NULL DEFAULT NULL,
                                      `deleted_at` timestamp NULL DEFAULT NULL,
                                      PRIMARY KEY (`id`),
                                      KEY `shifts_freelancers_shift_qualification_id_foreign` (`shift_qualification_id`),
                                      KEY `shift_freelancers_shift_id_foreign` (`shift_id`),
                                      CONSTRAINT `shift_freelancers_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE,
                                      CONSTRAINT `shifts_freelancers_shift_qualification_id_foreign` FOREIGN KEY (`shift_qualification_id`) REFERENCES `shift_qualifications` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shifts_freelancers`
--

LOCK TABLES `shifts_freelancers` WRITE;
/*!40000 ALTER TABLE `shifts_freelancers` DISABLE KEYS */;
/*!40000 ALTER TABLE `shifts_freelancers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shifts_qualifications`
--

DROP TABLE IF EXISTS `shifts_qualifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shifts_qualifications` (
                                         `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                         `shift_id` bigint(20) unsigned NOT NULL,
                                         `shift_qualification_id` bigint(20) unsigned NOT NULL,
                                         `value` smallint(5) unsigned DEFAULT NULL,
                                         `created_at` timestamp NULL DEFAULT NULL,
                                         `updated_at` timestamp NULL DEFAULT NULL,
                                         `deleted_at` timestamp NULL DEFAULT NULL,
                                         PRIMARY KEY (`id`),
                                         KEY `shifts_qualifications_shift_id_foreign` (`shift_id`),
                                         KEY `shifts_qualifications_shift_qualification_id_foreign` (`shift_qualification_id`),
                                         CONSTRAINT `shifts_qualifications_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE,
                                         CONSTRAINT `shifts_qualifications_shift_qualification_id_foreign` FOREIGN KEY (`shift_qualification_id`) REFERENCES `shift_qualifications` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shifts_qualifications`
--

LOCK TABLES `shifts_qualifications` WRITE;
/*!40000 ALTER TABLE `shifts_qualifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `shifts_qualifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shifts_service_providers`
--

DROP TABLE IF EXISTS `shifts_service_providers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shifts_service_providers` (
                                            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                            `shift_id` bigint(20) unsigned NOT NULL,
                                            `short_description` varchar(255) DEFAULT NULL,
                                            `start_date` date DEFAULT NULL,
                                            `end_date` date DEFAULT NULL,
                                            `start_time` time DEFAULT NULL,
                                            `end_time` time DEFAULT NULL,
                                            `craft_abbreviation` varchar(255) DEFAULT NULL,
                                            `service_provider_id` bigint(20) unsigned NOT NULL,
                                            `shift_qualification_id` bigint(20) unsigned NOT NULL,
                                            `shift_count` bigint(20) unsigned NOT NULL DEFAULT 1,
                                            `created_at` timestamp NULL DEFAULT NULL,
                                            `updated_at` timestamp NULL DEFAULT NULL,
                                            `deleted_at` timestamp NULL DEFAULT NULL,
                                            PRIMARY KEY (`id`),
                                            KEY `shifts_service_providers_shift_qualification_id_foreign` (`shift_qualification_id`),
                                            KEY `shift_service_providers_shift_id_foreign` (`shift_id`),
                                            CONSTRAINT `shift_service_providers_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE,
                                            CONSTRAINT `shifts_service_providers_shift_qualification_id_foreign` FOREIGN KEY (`shift_qualification_id`) REFERENCES `shift_qualifications` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shifts_service_providers`
--

LOCK TABLES `shifts_service_providers` WRITE;
/*!40000 ALTER TABLE `shifts_service_providers` DISABLE KEYS */;
/*!40000 ALTER TABLE `shifts_service_providers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sidebar_tab_components`
--

DROP TABLE IF EXISTS `sidebar_tab_components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sidebar_tab_components` (
                                          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                          `project_tab_sidebar_id` bigint(20) unsigned NOT NULL,
                                          `component_id` bigint(20) unsigned NOT NULL,
                                          `order` int(11) NOT NULL,
                                          `created_at` timestamp NULL DEFAULT NULL,
                                          `updated_at` timestamp NULL DEFAULT NULL,
                                          PRIMARY KEY (`id`),
                                          KEY `sidebar_tab_components_project_tab_sidebar_id_foreign` (`project_tab_sidebar_id`),
                                          KEY `sidebar_tab_components_component_id_foreign` (`component_id`),
                                          CONSTRAINT `sidebar_tab_components_component_id_foreign` FOREIGN KEY (`component_id`) REFERENCES `components` (`id`) ON DELETE CASCADE,
                                          CONSTRAINT `sidebar_tab_components_project_tab_sidebar_id_foreign` FOREIGN KEY (`project_tab_sidebar_id`) REFERENCES `project_tab_sidebar_tabs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sidebar_tab_components`
--

LOCK TABLES `sidebar_tab_components` WRITE;
/*!40000 ALTER TABLE `sidebar_tab_components` DISABLE KEYS */;
INSERT INTO `sidebar_tab_components` VALUES
                                         (1,1,3,1,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                         (2,1,19,2,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                         (3,1,4,3,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                         (4,2,3,1,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                         (5,2,19,2,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                         (6,2,4,3,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                         (7,3,3,1,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                         (8,3,19,2,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                         (9,3,4,3,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                         (10,4,9,1,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                         (11,4,19,2,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                         (12,4,10,3,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                         (13,4,19,4,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                         (14,4,11,5,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                         (15,5,20,1,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                         (16,6,3,1,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                         (17,6,19,2,'2026-04-21 06:09:24','2026-04-21 06:09:24'),
                                         (18,6,4,3,'2026-04-21 06:09:24','2026-04-21 06:09:24');
/*!40000 ALTER TABLE `sidebar_tab_components` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `single_shift_preset_qualifications`
--

DROP TABLE IF EXISTS `single_shift_preset_qualifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `single_shift_preset_qualifications` (
                                                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                                      `single_shift_preset_id` bigint(20) unsigned NOT NULL,
                                                      `shift_qualification_id` bigint(20) unsigned NOT NULL,
                                                      `quantity` int(11) NOT NULL DEFAULT 0,
                                                      `created_at` timestamp NULL DEFAULT NULL,
                                                      `updated_at` timestamp NULL DEFAULT NULL,
                                                      PRIMARY KEY (`id`),
                                                      UNIQUE KEY `sspq_unique` (`single_shift_preset_id`,`shift_qualification_id`),
                                                      KEY `sspq_qual_fk` (`shift_qualification_id`),
                                                      CONSTRAINT `sspq_preset_fk` FOREIGN KEY (`single_shift_preset_id`) REFERENCES `single_shift_presets` (`id`) ON DELETE CASCADE,
                                                      CONSTRAINT `sspq_qual_fk` FOREIGN KEY (`shift_qualification_id`) REFERENCES `shift_qualifications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `single_shift_preset_qualifications`
--

LOCK TABLES `single_shift_preset_qualifications` WRITE;
/*!40000 ALTER TABLE `single_shift_preset_qualifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `single_shift_preset_qualifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `single_shift_presets`
--

DROP TABLE IF EXISTS `single_shift_presets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `single_shift_presets` (
                                        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                        `name` varchar(255) NOT NULL,
                                        `start_time` time NOT NULL,
                                        `end_time` time NOT NULL,
                                        `break_duration` int(11) NOT NULL DEFAULT 0,
                                        `craft_id` bigint(20) unsigned DEFAULT NULL,
                                        `description` text DEFAULT NULL,
                                        `created_at` timestamp NULL DEFAULT NULL,
                                        `updated_at` timestamp NULL DEFAULT NULL,
                                        PRIMARY KEY (`id`),
                                        KEY `single_shift_presets_craft_id_foreign` (`craft_id`),
                                        CONSTRAINT `single_shift_presets_craft_id_foreign` FOREIGN KEY (`craft_id`) REFERENCES `crafts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `single_shift_presets`
--

LOCK TABLES `single_shift_presets` WRITE;
/*!40000 ALTER TABLE `single_shift_presets` DISABLE KEYS */;
/*!40000 ALTER TABLE `single_shift_presets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `special_items`
--

DROP TABLE IF EXISTS `special_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `special_items` (
                                 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                 `issuable_type` varchar(255) NOT NULL,
                                 `issuable_id` bigint(20) unsigned NOT NULL,
                                 `name` varchar(255) NOT NULL,
                                 `quantity` int(11) NOT NULL,
                                 `description` text DEFAULT NULL,
                                 `inventory_category_id` bigint(20) unsigned DEFAULT NULL,
                                 `inventory_sub_category_id` bigint(20) unsigned DEFAULT NULL,
                                 `created_at` timestamp NULL DEFAULT NULL,
                                 `updated_at` timestamp NULL DEFAULT NULL,
                                 PRIMARY KEY (`id`),
                                 KEY `special_items_issuable_type_issuable_id_index` (`issuable_type`,`issuable_id`),
                                 KEY `special_items_inventory_category_id_foreign` (`inventory_category_id`),
                                 KEY `special_items_inventory_sub_category_id_foreign` (`inventory_sub_category_id`),
                                 CONSTRAINT `special_items_inventory_category_id_foreign` FOREIGN KEY (`inventory_category_id`) REFERENCES `inventory_categories` (`id`) ON DELETE SET NULL,
                                 CONSTRAINT `special_items_inventory_sub_category_id_foreign` FOREIGN KEY (`inventory_sub_category_id`) REFERENCES `inventory_sub_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `special_items`
--

LOCK TABLES `special_items` WRITE;
/*!40000 ALTER TABLE `special_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `special_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sub_events`
--

DROP TABLE IF EXISTS `sub_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sub_events` (
                              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                              `event_id` bigint(20) unsigned NOT NULL,
                              `eventName` varchar(255) DEFAULT NULL,
                              `description` longtext DEFAULT NULL,
                              `start_time` timestamp NULL DEFAULT NULL,
                              `end_time` timestamp NULL DEFAULT NULL,
                              `audience` tinyint(1) DEFAULT 0,
                              `is_loud` tinyint(1) DEFAULT 0,
                              `allDay` tinyint(1) DEFAULT 0,
                              `event_type_id` bigint(20) unsigned NOT NULL,
                              `user_id` bigint(20) unsigned NOT NULL,
                              `deleted_at` timestamp NULL DEFAULT NULL,
                              `created_at` timestamp NULL DEFAULT NULL,
                              `updated_at` timestamp NULL DEFAULT NULL,
                              PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sub_events`
--

LOCK TABLES `sub_events` WRITE;
/*!40000 ALTER TABLE `sub_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `sub_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sub_position_rows`
--

DROP TABLE IF EXISTS `sub_position_rows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sub_position_rows` (
                                     `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                     `sub_position_id` bigint(20) NOT NULL,
                                     `position` int(11) NOT NULL,
                                     `order` int(10) unsigned NOT NULL DEFAULT 0,
                                     `commented` tinyint(1) NOT NULL DEFAULT 0,
                                     `created_at` timestamp NULL DEFAULT NULL,
                                     `updated_at` timestamp NULL DEFAULT NULL,
                                     `deleted_at` timestamp NULL DEFAULT NULL,
                                     PRIMARY KEY (`id`),
                                     KEY `sub_position_rows_sub_position_id_order_index` (`sub_position_id`,`order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sub_position_rows`
--

LOCK TABLES `sub_position_rows` WRITE;
/*!40000 ALTER TABLE `sub_position_rows` DISABLE KEYS */;
/*!40000 ALTER TABLE `sub_position_rows` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sub_position_verifieds`
--

DROP TABLE IF EXISTS `sub_position_verifieds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sub_position_verifieds` (
                                          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                          `sub_position_id` bigint(20) NOT NULL,
                                          `requested_by` int(11) NOT NULL,
                                          `requested` int(11) NOT NULL,
                                          `created_at` timestamp NULL DEFAULT NULL,
                                          `updated_at` timestamp NULL DEFAULT NULL,
                                          `deleted_at` timestamp NULL DEFAULT NULL,
                                          PRIMARY KEY (`id`),
                                          KEY `sub_position_verifieds_sub_position_id_index` (`sub_position_id`),
                                          KEY `sub_position_verifieds_requested_by_index` (`requested_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sub_position_verifieds`
--

LOCK TABLES `sub_position_verifieds` WRITE;
/*!40000 ALTER TABLE `sub_position_verifieds` DISABLE KEYS */;
/*!40000 ALTER TABLE `sub_position_verifieds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sub_positions`
--

DROP TABLE IF EXISTS `sub_positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sub_positions` (
                                 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                 `name` varchar(255) DEFAULT NULL,
                                 `position` int(11) NOT NULL,
                                 `main_position_id` bigint(20) NOT NULL,
                                 `is_verified` enum('BUDGET_VERIFIED_TYPE_NOT_VERIFIED','BUDGET_VERIFIED_TYPE_CLOSED','BUDGET_VERIFIED_TYPE_REQUESTED') NOT NULL DEFAULT 'BUDGET_VERIFIED_TYPE_NOT_VERIFIED',
                                 `created_at` timestamp NULL DEFAULT NULL,
                                 `updated_at` timestamp NULL DEFAULT NULL,
                                 `is_fixed` tinyint(1) NOT NULL DEFAULT 0,
                                 `deleted_at` timestamp NULL DEFAULT NULL,
                                 PRIMARY KEY (`id`),
                                 KEY `sub_positions_main_position_id_index` (`main_position_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sub_positions`
--

LOCK TABLES `sub_positions` WRITE;
/*!40000 ALTER TABLE `sub_positions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sub_positions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subdivisions`
--

DROP TABLE IF EXISTS `subdivisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `subdivisions` (
                                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                `name` varchar(255) NOT NULL,
                                `code` varchar(255) NOT NULL,
                                `country_code` varchar(255) NOT NULL,
                                `created_at` timestamp NULL DEFAULT NULL,
                                `updated_at` timestamp NULL DEFAULT NULL,
                                PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subdivisions`
--

LOCK TABLES `subdivisions` WRITE;
/*!40000 ALTER TABLE `subdivisions` DISABLE KEYS */;
INSERT INTO `subdivisions` VALUES
                               (1,'Brandenburg','BB','DE','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                               (2,'Berlin','BE','DE','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                               (3,'Baden-Württemberg','BW','DE','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                               (4,'Bayern','BY','DE','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                               (5,'Bremen','HB','DE','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                               (6,'Hessen','HE','DE','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                               (7,'Hamburg','HH','DE','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                               (8,'Mecklenburg-Vorpommern','MV','DE','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                               (9,'Mecklenburg-Vorpommern Allgemeinbildende Schulen','MV-ABS','DE','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                               (10,'Mecklenburg-Vorpommern Berufsbildende Schulen','MV-BBS','DE','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                               (11,'Niedersachsen','NI','DE','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                               (12,'Nordrhein-Westfalen','NW','DE','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                               (13,'Rheinland-Pfalz','RP','DE','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                               (14,'Schleswig-Holstein','SH','DE','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                               (15,'Saarland','SL','DE','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                               (16,'Sachsen','SN','DE','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                               (17,'Sachsen-Anhalt','ST','DE','2026-04-21 06:09:24','2026-04-21 06:09:24'),
                               (18,'Thüringen','TH','DE','2026-04-21 06:09:24','2026-04-21 06:09:24');
/*!40000 ALTER TABLE `subdivisions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subposition_sum_details`
--

DROP TABLE IF EXISTS `subposition_sum_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `subposition_sum_details` (
                                           `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                           `sub_position_id` bigint(20) unsigned NOT NULL,
                                           `column_id` bigint(20) unsigned NOT NULL,
                                           `created_at` timestamp NULL DEFAULT NULL,
                                           `updated_at` timestamp NULL DEFAULT NULL,
                                           `deleted_at` timestamp NULL DEFAULT NULL,
                                           PRIMARY KEY (`id`),
                                           KEY `subposition_sum_details_sub_position_id_index` (`sub_position_id`),
                                           KEY `subposition_sum_details_column_id_index` (`column_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subposition_sum_details`
--

LOCK TABLES `subposition_sum_details` WRITE;
/*!40000 ALTER TABLE `subposition_sum_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `subposition_sum_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sum_comments`
--

DROP TABLE IF EXISTS `sum_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sum_comments` (
                                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                `commentable_type` varchar(255) NOT NULL,
                                `commentable_id` bigint(20) unsigned NOT NULL,
                                `comment` text NOT NULL,
                                `user_id` bigint(20) unsigned NOT NULL,
                                `created_at` timestamp NULL DEFAULT NULL,
                                `updated_at` timestamp NULL DEFAULT NULL,
                                `deleted_at` timestamp NULL DEFAULT NULL,
                                PRIMARY KEY (`id`),
                                KEY `sum_comments_commentable_type_commentable_id_index` (`commentable_type`,`commentable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sum_comments`
--

LOCK TABLES `sum_comments` WRITE;
/*!40000 ALTER TABLE `sum_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `sum_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sum_money_sources`
--

DROP TABLE IF EXISTS `sum_money_sources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sum_money_sources` (
                                     `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                     `sourceable_type` varchar(255) NOT NULL,
                                     `sourceable_id` bigint(20) unsigned NOT NULL,
                                     `money_source_id` bigint(20) DEFAULT NULL,
                                     `linked_type` varchar(255) DEFAULT NULL,
                                     `created_at` timestamp NULL DEFAULT NULL,
                                     `updated_at` timestamp NULL DEFAULT NULL,
                                     `deleted_at` timestamp NULL DEFAULT NULL,
                                     PRIMARY KEY (`id`),
                                     KEY `sum_money_sources_sourceable_type_sourceable_id_index` (`sourceable_type`,`sourceable_id`),
                                     KEY `sum_money_sources_money_source_id_index` (`money_source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sum_money_sources`
--

LOCK TABLES `sum_money_sources` WRITE;
/*!40000 ALTER TABLE `sum_money_sources` DISABLE KEYS */;
/*!40000 ALTER TABLE `sum_money_sources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `table_column_orders`
--

DROP TABLE IF EXISTS `table_column_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `table_column_orders` (
                                       `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                       `display_text` tinytext NOT NULL,
                                       `position` tinyint(3) unsigned NOT NULL,
                                       `created_at` timestamp NULL DEFAULT NULL,
                                       `updated_at` timestamp NULL DEFAULT NULL,
                                       PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `table_column_orders`
--

LOCK TABLES `table_column_orders` WRITE;
/*!40000 ALTER TABLE `table_column_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `table_column_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tables`
--

DROP TABLE IF EXISTS `tables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tables` (
                          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                          `name` varchar(255) DEFAULT NULL,
                          `is_template` tinyint(1) NOT NULL DEFAULT 0,
                          `project_id` bigint(20) DEFAULT NULL,
                          `created_at` timestamp NULL DEFAULT NULL,
                          `updated_at` timestamp NULL DEFAULT NULL,
                          `deleted_at` timestamp NULL DEFAULT NULL,
                          PRIMARY KEY (`id`),
                          KEY `tables_project_id_index` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tables`
--

LOCK TABLES `tables` WRITE;
/*!40000 ALTER TABLE `tables` DISABLE KEYS */;
/*!40000 ALTER TABLE `tables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `task_template_user`
--

DROP TABLE IF EXISTS `task_template_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `task_template_user` (
                                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                      `task_template_id` bigint(20) unsigned NOT NULL,
                                      `user_id` bigint(20) unsigned NOT NULL,
                                      `created_at` timestamp NULL DEFAULT NULL,
                                      `updated_at` timestamp NULL DEFAULT NULL,
                                      PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task_template_user`
--

LOCK TABLES `task_template_user` WRITE;
/*!40000 ALTER TABLE `task_template_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `task_template_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `task_templates`
--

DROP TABLE IF EXISTS `task_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `task_templates` (
                                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                  `name` varchar(255) NOT NULL,
                                  `description` longtext DEFAULT NULL,
                                  `done` tinyint(1) NOT NULL DEFAULT 0,
                                  `checklist_template_id` bigint(20) unsigned NOT NULL,
                                  `created_at` timestamp NULL DEFAULT NULL,
                                  `updated_at` timestamp NULL DEFAULT NULL,
                                  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task_templates`
--

LOCK TABLES `task_templates` WRITE;
/*!40000 ALTER TABLE `task_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `task_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `task_user`
--

DROP TABLE IF EXISTS `task_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `task_user` (
                             `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                             `user_id` bigint(20) unsigned NOT NULL,
                             `task_id` bigint(20) unsigned NOT NULL,
                             `created_at` timestamp NULL DEFAULT NULL,
                             `updated_at` timestamp NULL DEFAULT NULL,
                             PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task_user`
--

LOCK TABLES `task_user` WRITE;
/*!40000 ALTER TABLE `task_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `task_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tasks` (
                         `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                         `name` varchar(255) NOT NULL,
                         `description` longtext DEFAULT NULL,
                         `done` tinyint(1) NOT NULL,
                         `deadline` timestamp NULL DEFAULT NULL,
                         `done_at` timestamp NULL DEFAULT NULL,
                         `order` int(11) NOT NULL,
                         `checklist_id` bigint(20) unsigned DEFAULT NULL,
                         `user_id` bigint(20) unsigned DEFAULT NULL,
                         `contract_id` bigint(20) unsigned DEFAULT NULL,
                         `created_at` timestamp NULL DEFAULT NULL,
                         `updated_at` timestamp NULL DEFAULT NULL,
                         `deleted_at` timestamp NULL DEFAULT NULL,
                         `sent_deadline_notification` tinyint(1) NOT NULL DEFAULT 1,
                         PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasks`
--

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `timelines`
--

DROP TABLE IF EXISTS `timelines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `timelines` (
                             `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                             `start_date` date NOT NULL,
                             `end_date` date NOT NULL,
                             `event_id` bigint(20) unsigned NOT NULL,
                             `start` time NOT NULL,
                             `end` time NOT NULL,
                             `description` varchar(255) DEFAULT NULL,
                             `created_at` timestamp NULL DEFAULT NULL,
                             `updated_at` timestamp NULL DEFAULT NULL,
                             `deleted_at` timestamp NULL DEFAULT NULL,
                             `start_or_end` tinyint(1) NOT NULL DEFAULT 0,
                             PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `timelines`
--

LOCK TABLES `timelines` WRITE;
/*!40000 ALTER TABLE `timelines` DISABLE KEYS */;
/*!40000 ALTER TABLE `timelines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_budget_account_display_settings`
--

DROP TABLE IF EXISTS `user_budget_account_display_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_budget_account_display_settings` (
                                                        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                                        `user_id` bigint(20) unsigned NOT NULL,
                                                        `show_number` tinyint(1) NOT NULL DEFAULT 1,
                                                        PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_budget_account_display_settings`
--

LOCK TABLES `user_budget_account_display_settings` WRITE;
/*!40000 ALTER TABLE `user_budget_account_display_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_budget_account_display_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_calendar_abos`
--

DROP TABLE IF EXISTS `user_calendar_abos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_calendar_abos` (
                                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                      `user_id` bigint(20) unsigned NOT NULL,
                                      `calendar_abo_id` varchar(255) DEFAULT NULL,
                                      `date_range` tinyint(1) NOT NULL DEFAULT 0,
                                      `start_date` date DEFAULT NULL,
                                      `end_date` date DEFAULT NULL,
                                      `specific_event_types` tinyint(1) NOT NULL DEFAULT 0,
                                      `event_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`event_types`)),
                                      `specific_rooms` tinyint(1) NOT NULL DEFAULT 0,
                                      `selected_rooms` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`selected_rooms`)),
                                      `specific_areas` tinyint(1) NOT NULL DEFAULT 0,
                                      `selected_areas` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`selected_areas`)),
                                      `enable_notification` tinyint(1) NOT NULL DEFAULT 0,
                                      `notification_time` int(11) DEFAULT NULL,
                                      `notification_time_unit` enum('minutes','hours','days') DEFAULT NULL,
                                      `created_at` timestamp NULL DEFAULT NULL,
                                      `updated_at` timestamp NULL DEFAULT NULL,
                                      PRIMARY KEY (`id`),
                                      UNIQUE KEY `user_calendar_abos_calendar_abo_id_unique` (`calendar_abo_id`),
                                      KEY `user_calendar_abos_user_id_foreign` (`user_id`),
                                      CONSTRAINT `user_calendar_abos_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_calendar_abos`
--

LOCK TABLES `user_calendar_abos` WRITE;
/*!40000 ALTER TABLE `user_calendar_abos` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_calendar_abos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_calendar_filters`
--

DROP TABLE IF EXISTS `user_calendar_filters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_calendar_filters` (
                                         `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                         `user_id` bigint(20) unsigned NOT NULL,
                                         `start_date` date DEFAULT NULL,
                                         `end_date` date DEFAULT NULL,
                                         `is_loud` tinyint(1) NOT NULL DEFAULT 0,
                                         `is_not_loud` tinyint(1) NOT NULL DEFAULT 0,
                                         `adjoining_not_loud` tinyint(1) NOT NULL DEFAULT 0,
                                         `has_audience` tinyint(1) NOT NULL DEFAULT 0,
                                         `has_no_audience` tinyint(1) NOT NULL DEFAULT 0,
                                         `adjoining_no_audience` tinyint(1) NOT NULL DEFAULT 0,
                                         `show_free_rooms` tinyint(1) NOT NULL DEFAULT 0,
                                         `show_adjoining_rooms` tinyint(1) NOT NULL DEFAULT 0,
                                         `all_day_free` tinyint(1) NOT NULL DEFAULT 0,
                                         `event_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`event_types`)),
                                         `rooms` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`rooms`)),
                                         `areas` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`areas`)),
                                         `room_attributes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`room_attributes`)),
                                         `room_categories` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`room_categories`)),
                                         `event_properties` longtext DEFAULT NULL,
                                         `created_at` timestamp NULL DEFAULT NULL,
                                         `updated_at` timestamp NULL DEFAULT NULL,
                                         PRIMARY KEY (`id`),
                                         KEY `user_calendar_filters_user_id_foreign` (`user_id`),
                                         CONSTRAINT `user_calendar_filters_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_calendar_filters`
--

LOCK TABLES `user_calendar_filters` WRITE;
/*!40000 ALTER TABLE `user_calendar_filters` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_calendar_filters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_calendar_settings`
--

DROP TABLE IF EXISTS `user_calendar_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_calendar_settings` (
                                          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                          `user_id` bigint(20) unsigned NOT NULL,
                                          `project_status` tinyint(1) NOT NULL DEFAULT 0,
                                          `options` tinyint(1) NOT NULL DEFAULT 0,
                                          `project_management` tinyint(1) NOT NULL DEFAULT 0,
                                          `repeating_events` tinyint(1) NOT NULL DEFAULT 0,
                                          `work_shifts` tinyint(1) NOT NULL DEFAULT 0,
                                          `description` tinyint(1) NOT NULL DEFAULT 0,
                                          `use_project_time_period` tinyint(1) NOT NULL DEFAULT 0,
                                          `time_period_project_id` int(11) NOT NULL DEFAULT 0,
                                          `event_name` tinyint(1) NOT NULL DEFAULT 1,
                                          `created_at` timestamp NULL DEFAULT NULL,
                                          `updated_at` timestamp NULL DEFAULT NULL,
                                          `high_contrast` tinyint(1) NOT NULL DEFAULT 0,
                                          `expand_days` tinyint(1) NOT NULL DEFAULT 0,
                                          `use_event_status_color` tinyint(1) NOT NULL DEFAULT 0,
                                          `use_main_category_color` tinyint(1) NOT NULL DEFAULT 0,
                                          `project_artists` tinyint(1) NOT NULL DEFAULT 0,
                                          `show_qualifications` tinyint(1) NOT NULL DEFAULT 0,
                                          `shift_notes` tinyint(1) NOT NULL DEFAULT 0,
                                          `hide_unoccupied_rooms` tinyint(1) NOT NULL DEFAULT 0,
                                          `display_project_groups` tinyint(1) NOT NULL DEFAULT 0,
                                          `show_unplanned_events` tinyint(1) NOT NULL DEFAULT 0,
                                          `show_planned_events` tinyint(1) NOT NULL DEFAULT 0,
                                          `hide_unoccupied_days` tinyint(1) NOT NULL DEFAULT 0,
                                          `show_shift_group_tag` tinyint(1) NOT NULL DEFAULT 0,
                                          `show_timeline` tinyint(1) NOT NULL DEFAULT 0,
                                          `show_only_not_fully_staffed_shifts` tinyint(1) NOT NULL DEFAULT 0,
                                          `show_user_overview` tinyint(1) NOT NULL DEFAULT 1,
                                          PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_calendar_settings`
--

LOCK TABLES `user_calendar_settings` WRITE;
/*!40000 ALTER TABLE `user_calendar_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_calendar_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_commented_budget_items_settings`
--

DROP TABLE IF EXISTS `user_commented_budget_items_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_commented_budget_items_settings` (
                                                        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                                        `user_id` bigint(20) unsigned NOT NULL,
                                                        `exclude` tinyint(1) NOT NULL,
                                                        PRIMARY KEY (`id`),
                                                        UNIQUE KEY `user_commented_budget_items_settings_user_id_unique` (`user_id`),
                                                        CONSTRAINT `user_commented_budget_items_settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_commented_budget_items_settings`
--

LOCK TABLES `user_commented_budget_items_settings` WRITE;
/*!40000 ALTER TABLE `user_commented_budget_items_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_commented_budget_items_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_contract_assigns`
--

DROP TABLE IF EXISTS `user_contract_assigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_contract_assigns` (
                                         `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                         `user_id` bigint(20) unsigned NOT NULL,
                                         `user_contract_id` bigint(20) unsigned DEFAULT NULL,
                                         `free_full_days_per_week` int(11) NOT NULL DEFAULT 0,
                                         `free_half_days_per_week` int(11) NOT NULL DEFAULT 0,
                                         `special_day_rule_active` tinyint(1) NOT NULL DEFAULT 0,
                                         `compensation_period` int(11) NOT NULL DEFAULT 0,
                                         `free_sundays_per_season` int(11) NOT NULL DEFAULT 0,
                                         `days_off_first_26_weeks` double NOT NULL DEFAULT 0,
                                         `created_at` timestamp NULL DEFAULT NULL,
                                         `updated_at` timestamp NULL DEFAULT NULL,
                                         PRIMARY KEY (`id`),
                                         KEY `user_contract_assigns_user_id_foreign` (`user_id`),
                                         KEY `user_contract_assigns_user_contract_id_foreign` (`user_contract_id`),
                                         CONSTRAINT `user_contract_assigns_user_contract_id_foreign` FOREIGN KEY (`user_contract_id`) REFERENCES `user_contracts` (`id`) ON DELETE SET NULL,
                                         CONSTRAINT `user_contract_assigns_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_contract_assigns`
--

LOCK TABLES `user_contract_assigns` WRITE;
/*!40000 ALTER TABLE `user_contract_assigns` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_contract_assigns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_contract_filters`
--

DROP TABLE IF EXISTS `user_contract_filters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_contract_filters` (
                                         `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                         `user_id` bigint(20) unsigned NOT NULL,
                                         `ksk_liable` tinyint(1) NOT NULL DEFAULT 0,
                                         `foreign_tax` tinyint(1) NOT NULL DEFAULT 0,
                                         `date_from` date DEFAULT NULL,
                                         `date_to` date DEFAULT NULL,
                                         `legal_form_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`legal_form_ids`)),
                                         `contract_type_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`contract_type_ids`)),
                                         `created_at` timestamp NULL DEFAULT NULL,
                                         `updated_at` timestamp NULL DEFAULT NULL,
                                         PRIMARY KEY (`id`),
                                         KEY `user_contract_filters_user_id_foreign` (`user_id`),
                                         CONSTRAINT `user_contract_filters_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_contract_filters`
--

LOCK TABLES `user_contract_filters` WRITE;
/*!40000 ALTER TABLE `user_contract_filters` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_contract_filters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_contracts`
--

DROP TABLE IF EXISTS `user_contracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_contracts` (
                                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                  `name` varchar(255) NOT NULL,
                                  `free_full_days_per_week` int(10) unsigned NOT NULL DEFAULT 0,
                                  `free_half_days_per_week` int(10) unsigned NOT NULL DEFAULT 0,
                                  `special_day_rule_active` tinyint(1) NOT NULL DEFAULT 0,
                                  `compensation_period` int(10) unsigned NOT NULL DEFAULT 0,
                                  `description` text DEFAULT NULL,
                                  `free_sundays_per_season` int(10) unsigned NOT NULL DEFAULT 0,
                                  `days_off_first_26_weeks` decimal(5,2) NOT NULL DEFAULT 0.00,
                                  `created_at` timestamp NULL DEFAULT NULL,
                                  `updated_at` timestamp NULL DEFAULT NULL,
                                  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_contracts`
--

LOCK TABLES `user_contracts` WRITE;
/*!40000 ALTER TABLE `user_contracts` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_contracts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_daily_view_calendar_settings`
--

DROP TABLE IF EXISTS `user_daily_view_calendar_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_daily_view_calendar_settings` (
                                                     `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                                     `user_id` bigint(20) unsigned NOT NULL,
                                                     `project_status` tinyint(1) NOT NULL DEFAULT 0,
                                                     `options` tinyint(1) NOT NULL DEFAULT 0,
                                                     `project_management` tinyint(1) NOT NULL DEFAULT 0,
                                                     `repeating_events` tinyint(1) NOT NULL DEFAULT 0,
                                                     `work_shifts` tinyint(1) NOT NULL DEFAULT 0,
                                                     `description` tinyint(1) NOT NULL DEFAULT 0,
                                                     `use_project_time_period` tinyint(1) NOT NULL DEFAULT 0,
                                                     `time_period_project_id` int(11) NOT NULL DEFAULT 0,
                                                     `event_name` tinyint(1) NOT NULL DEFAULT 1,
                                                     `created_at` timestamp NULL DEFAULT NULL,
                                                     `updated_at` timestamp NULL DEFAULT NULL,
                                                     `high_contrast` tinyint(1) NOT NULL DEFAULT 0,
                                                     `expand_days` tinyint(1) NOT NULL DEFAULT 0,
                                                     `use_event_status_color` tinyint(1) NOT NULL DEFAULT 0,
                                                     `use_main_category_color` tinyint(1) NOT NULL DEFAULT 0,
                                                     `project_artists` tinyint(1) NOT NULL DEFAULT 0,
                                                     `show_qualifications` tinyint(1) NOT NULL DEFAULT 0,
                                                     `shift_notes` tinyint(1) NOT NULL DEFAULT 0,
                                                     `hide_unoccupied_rooms` tinyint(1) NOT NULL DEFAULT 0,
                                                     `display_project_groups` tinyint(1) NOT NULL DEFAULT 0,
                                                     `show_unplanned_events` tinyint(1) NOT NULL DEFAULT 0,
                                                     `show_planned_events` tinyint(1) NOT NULL DEFAULT 0,
                                                     `hide_unoccupied_days` tinyint(1) NOT NULL DEFAULT 0,
                                                     `show_shift_group_tag` tinyint(1) NOT NULL DEFAULT 0,
                                                     `show_timeline` tinyint(1) NOT NULL DEFAULT 0,
                                                     `show_only_not_fully_staffed_shifts` tinyint(1) NOT NULL DEFAULT 0,
                                                     PRIMARY KEY (`id`),
                                                     KEY `user_daily_view_calendar_settings_user_id_foreign` (`user_id`),
                                                     CONSTRAINT `user_daily_view_calendar_settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_daily_view_calendar_settings`
--

LOCK TABLES `user_daily_view_calendar_settings` WRITE;
/*!40000 ALTER TABLE `user_daily_view_calendar_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_daily_view_calendar_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_filter_templates`
--

DROP TABLE IF EXISTS `user_filter_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_filter_templates` (
                                         `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                         `name` varchar(255) NOT NULL,
                                         `user_id` bigint(20) unsigned NOT NULL,
                                         `filter_type` varchar(255) NOT NULL DEFAULT 'calendar_filter',
                                         `event_type_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`event_type_ids`)),
                                         `room_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`room_ids`)),
                                         `area_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`area_ids`)),
                                         `room_attribute_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`room_attribute_ids`)),
                                         `room_category_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`room_category_ids`)),
                                         `event_property_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`event_property_ids`)),
                                         `craft_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`craft_ids`)),
                                         `created_at` timestamp NULL DEFAULT NULL,
                                         `updated_at` timestamp NULL DEFAULT NULL,
                                         PRIMARY KEY (`id`),
                                         KEY `user_filter_templates_user_id_foreign` (`user_id`),
                                         CONSTRAINT `user_filter_templates_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_filter_templates`
--

LOCK TABLES `user_filter_templates` WRITE;
/*!40000 ALTER TABLE `user_filter_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_filter_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_filters`
--

DROP TABLE IF EXISTS `user_filters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_filters` (
                                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                `user_id` bigint(20) unsigned NOT NULL,
                                `start_date` date DEFAULT NULL,
                                `end_date` date DEFAULT NULL,
                                `filter_type` varchar(255) NOT NULL DEFAULT 'calendar_filter',
                                `event_type_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`event_type_ids`)),
                                `room_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`room_ids`)),
                                `area_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`area_ids`)),
                                `room_attribute_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`room_attribute_ids`)),
                                `room_category_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`room_category_ids`)),
                                `event_property_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`event_property_ids`)),
                                `craft_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`craft_ids`)),
                                `created_at` timestamp NULL DEFAULT NULL,
                                `updated_at` timestamp NULL DEFAULT NULL,
                                PRIMARY KEY (`id`),
                                UNIQUE KEY `user_filter_unique` (`user_id`,`filter_type`),
                                CONSTRAINT `user_filters_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_filters`
--

LOCK TABLES `user_filters` WRITE;
/*!40000 ALTER TABLE `user_filters` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_filters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_inventory_article_plan_filters`
--

DROP TABLE IF EXISTS `user_inventory_article_plan_filters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_inventory_article_plan_filters` (
                                                       `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                                       `user_id` bigint(20) unsigned NOT NULL,
                                                       `start_date` datetime DEFAULT NULL,
                                                       `end_date` datetime DEFAULT NULL,
                                                       `created_at` timestamp NULL DEFAULT NULL,
                                                       `updated_at` timestamp NULL DEFAULT NULL,
                                                       PRIMARY KEY (`id`),
                                                       KEY `user_inventory_article_plan_filters_user_id_foreign` (`user_id`),
                                                       CONSTRAINT `user_inventory_article_plan_filters_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_inventory_article_plan_filters`
--

LOCK TABLES `user_inventory_article_plan_filters` WRITE;
/*!40000 ALTER TABLE `user_inventory_article_plan_filters` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_inventory_article_plan_filters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_project_management_settings`
--

DROP TABLE IF EXISTS `user_project_management_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_project_management_settings` (
                                                    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                                    `user_id` bigint(20) unsigned NOT NULL,
                                                    `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`settings`)),
                                                    `created_at` timestamp NULL DEFAULT NULL,
                                                    `updated_at` timestamp NULL DEFAULT NULL,
                                                    PRIMARY KEY (`id`),
                                                    KEY `user_project_management_settings_user_id_foreign` (`user_id`),
                                                    CONSTRAINT `user_project_management_settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_project_management_settings`
--

LOCK TABLES `user_project_management_settings` WRITE;
/*!40000 ALTER TABLE `user_project_management_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_project_management_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_shift_calendar_abos`
--

DROP TABLE IF EXISTS `user_shift_calendar_abos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_shift_calendar_abos` (
                                            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                            `user_id` bigint(20) unsigned NOT NULL,
                                            `calendar_abo_id` varchar(255) DEFAULT NULL,
                                            `date_range` tinyint(1) NOT NULL DEFAULT 0,
                                            `start_date` date DEFAULT NULL,
                                            `end_date` date DEFAULT NULL,
                                            `specific_crafts` tinyint(1) NOT NULL DEFAULT 0,
                                            `craft_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`craft_ids`)),
                                            `enable_notification` tinyint(1) NOT NULL DEFAULT 0,
                                            `notification_time` int(11) DEFAULT NULL,
                                            `notification_time_unit` enum('minutes','hours','days') DEFAULT NULL,
                                            `created_at` timestamp NULL DEFAULT NULL,
                                            `updated_at` timestamp NULL DEFAULT NULL,
                                            PRIMARY KEY (`id`),
                                            UNIQUE KEY `user_shift_calendar_abos_calendar_abo_id_unique` (`calendar_abo_id`),
                                            KEY `user_shift_calendar_abos_user_id_foreign` (`user_id`),
                                            CONSTRAINT `user_shift_calendar_abos_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_shift_calendar_abos`
--

LOCK TABLES `user_shift_calendar_abos` WRITE;
/*!40000 ALTER TABLE `user_shift_calendar_abos` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_shift_calendar_abos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_shift_calendar_filters`
--

DROP TABLE IF EXISTS `user_shift_calendar_filters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_shift_calendar_filters` (
                                               `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                               `user_id` bigint(20) unsigned NOT NULL,
                                               `start_date` date DEFAULT NULL,
                                               `end_date` date DEFAULT NULL,
                                               `event_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`event_types`)),
                                               `rooms` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`rooms`)),
                                               `created_at` timestamp NULL DEFAULT NULL,
                                               `updated_at` timestamp NULL DEFAULT NULL,
                                               PRIMARY KEY (`id`),
                                               KEY `user_shift_calendar_filters_user_id_foreign` (`user_id`),
                                               CONSTRAINT `user_shift_calendar_filters_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_shift_calendar_filters`
--

LOCK TABLES `user_shift_calendar_filters` WRITE;
/*!40000 ALTER TABLE `user_shift_calendar_filters` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_shift_calendar_filters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_shift_list_view_settings`
--

DROP TABLE IF EXISTS `user_shift_list_view_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_shift_list_view_settings` (
                                                 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                                 `user_id` bigint(20) unsigned NOT NULL,
                                                 `show_qualifications` tinyint(1) NOT NULL DEFAULT 0,
                                                 `shift_notes` tinyint(1) NOT NULL DEFAULT 0,
                                                 `show_shift_group_tag` tinyint(1) NOT NULL DEFAULT 0,
                                                 `show_fully_staffed_shifts` tinyint(1) NOT NULL DEFAULT 0,
                                                 `detailed_shift_overview` tinyint(1) NOT NULL DEFAULT 0,
                                                 `created_at` timestamp NULL DEFAULT NULL,
                                                 `updated_at` timestamp NULL DEFAULT NULL,
                                                 PRIMARY KEY (`id`),
                                                 KEY `user_shift_list_view_settings_user_id_foreign` (`user_id`),
                                                 CONSTRAINT `user_shift_list_view_settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_shift_list_view_settings`
--

LOCK TABLES `user_shift_list_view_settings` WRITE;
/*!40000 ALTER TABLE `user_shift_list_view_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_shift_list_view_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_shift_plan_daily_settings`
--

DROP TABLE IF EXISTS `user_shift_plan_daily_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_shift_plan_daily_settings` (
                                                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                                  `user_id` bigint(20) unsigned NOT NULL,
                                                  `project_status` tinyint(1) NOT NULL DEFAULT 0,
                                                  `options` tinyint(1) NOT NULL DEFAULT 0,
                                                  `project_management` tinyint(1) NOT NULL DEFAULT 0,
                                                  `repeating_events` tinyint(1) NOT NULL DEFAULT 0,
                                                  `work_shifts` tinyint(1) NOT NULL DEFAULT 0,
                                                  `description` tinyint(1) NOT NULL DEFAULT 0,
                                                  `use_project_time_period` tinyint(1) NOT NULL DEFAULT 0,
                                                  `time_period_project_id` int(11) NOT NULL DEFAULT 0,
                                                  `event_name` tinyint(1) NOT NULL DEFAULT 1,
                                                  `created_at` timestamp NULL DEFAULT NULL,
                                                  `updated_at` timestamp NULL DEFAULT NULL,
                                                  `high_contrast` tinyint(1) NOT NULL DEFAULT 0,
                                                  `expand_days` tinyint(1) NOT NULL DEFAULT 0,
                                                  `use_event_status_color` tinyint(1) NOT NULL DEFAULT 0,
                                                  `use_main_category_color` tinyint(1) NOT NULL DEFAULT 0,
                                                  `project_artists` tinyint(1) NOT NULL DEFAULT 0,
                                                  `show_qualifications` tinyint(1) NOT NULL DEFAULT 0,
                                                  `shift_notes` tinyint(1) NOT NULL DEFAULT 0,
                                                  `hide_unoccupied_rooms` tinyint(1) NOT NULL DEFAULT 0,
                                                  `display_project_groups` tinyint(1) NOT NULL DEFAULT 0,
                                                  `show_unplanned_events` tinyint(1) NOT NULL DEFAULT 0,
                                                  `show_planned_events` tinyint(1) NOT NULL DEFAULT 0,
                                                  `hide_unoccupied_days` tinyint(1) NOT NULL DEFAULT 0,
                                                  `show_shift_group_tag` tinyint(1) NOT NULL DEFAULT 0,
                                                  `show_timeline` tinyint(1) NOT NULL DEFAULT 0,
                                                  `show_only_not_fully_staffed_shifts` tinyint(1) NOT NULL DEFAULT 0,
                                                  PRIMARY KEY (`id`),
                                                  KEY `user_shift_plan_daily_settings_user_id_foreign` (`user_id`),
                                                  CONSTRAINT `user_shift_plan_daily_settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_shift_plan_daily_settings`
--

LOCK TABLES `user_shift_plan_daily_settings` WRITE;
/*!40000 ALTER TABLE `user_shift_plan_daily_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_shift_plan_daily_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_shift_plan_settings`
--

DROP TABLE IF EXISTS `user_shift_plan_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_shift_plan_settings` (
                                            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                            `user_id` bigint(20) unsigned NOT NULL,
                                            `project_status` tinyint(1) NOT NULL DEFAULT 0,
                                            `options` tinyint(1) NOT NULL DEFAULT 0,
                                            `project_management` tinyint(1) NOT NULL DEFAULT 0,
                                            `repeating_events` tinyint(1) NOT NULL DEFAULT 0,
                                            `work_shifts` tinyint(1) NOT NULL DEFAULT 0,
                                            `description` tinyint(1) NOT NULL DEFAULT 0,
                                            `use_project_time_period` tinyint(1) NOT NULL DEFAULT 0,
                                            `time_period_project_id` int(11) NOT NULL DEFAULT 0,
                                            `event_name` tinyint(1) NOT NULL DEFAULT 1,
                                            `created_at` timestamp NULL DEFAULT NULL,
                                            `updated_at` timestamp NULL DEFAULT NULL,
                                            `high_contrast` tinyint(1) NOT NULL DEFAULT 0,
                                            `expand_days` tinyint(1) NOT NULL DEFAULT 0,
                                            `use_event_status_color` tinyint(1) NOT NULL DEFAULT 0,
                                            `use_main_category_color` tinyint(1) NOT NULL DEFAULT 0,
                                            `project_artists` tinyint(1) NOT NULL DEFAULT 0,
                                            `show_qualifications` tinyint(1) NOT NULL DEFAULT 0,
                                            `shift_notes` tinyint(1) NOT NULL DEFAULT 0,
                                            `hide_unoccupied_rooms` tinyint(1) NOT NULL DEFAULT 0,
                                            `display_project_groups` tinyint(1) NOT NULL DEFAULT 0,
                                            `show_unplanned_events` tinyint(1) NOT NULL DEFAULT 0,
                                            `show_planned_events` tinyint(1) NOT NULL DEFAULT 0,
                                            `hide_unoccupied_days` tinyint(1) NOT NULL DEFAULT 0,
                                            `show_shift_group_tag` tinyint(1) NOT NULL DEFAULT 0,
                                            `show_timeline` tinyint(1) NOT NULL DEFAULT 0,
                                            `show_only_not_fully_staffed_shifts` tinyint(1) NOT NULL DEFAULT 0,
                                            `show_user_overview` tinyint(1) NOT NULL DEFAULT 1,
                                            PRIMARY KEY (`id`),
                                            KEY `user_shift_plan_settings_user_id_foreign` (`user_id`),
                                            CONSTRAINT `user_shift_plan_settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_shift_plan_settings`
--

LOCK TABLES `user_shift_plan_settings` WRITE;
/*!40000 ALTER TABLE `user_shift_plan_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_shift_plan_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_shift_qualifications`
--

DROP TABLE IF EXISTS `user_shift_qualifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_shift_qualifications` (
                                             `user_id` bigint(20) unsigned NOT NULL,
                                             `shift_qualification_id` bigint(20) unsigned NOT NULL,
                                             `created_at` timestamp NULL DEFAULT NULL,
                                             `updated_at` timestamp NULL DEFAULT NULL,
                                             KEY `user_shift_qualifications_user_id_foreign` (`user_id`),
                                             KEY `user_shift_qualifications_shift_qualification_id_foreign` (`shift_qualification_id`),
                                             CONSTRAINT `user_shift_qualifications_shift_qualification_id_foreign` FOREIGN KEY (`shift_qualification_id`) REFERENCES `shift_qualifications` (`id`) ON DELETE CASCADE,
                                             CONSTRAINT `user_shift_qualifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_shift_qualifications`
--

LOCK TABLES `user_shift_qualifications` WRITE;
/*!40000 ALTER TABLE `user_shift_qualifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_shift_qualifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_user_management_settings`
--

DROP TABLE IF EXISTS `user_user_management_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_user_management_settings` (
                                                 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                                 `user_id` bigint(20) unsigned NOT NULL,
                                                 `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`settings`)),
                                                 `created_at` timestamp NULL DEFAULT NULL,
                                                 `updated_at` timestamp NULL DEFAULT NULL,
                                                 PRIMARY KEY (`id`),
                                                 KEY `user_user_management_settings_user_id_foreign` (`user_id`),
                                                 CONSTRAINT `user_user_management_settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_user_management_settings`
--

LOCK TABLES `user_user_management_settings` WRITE;
/*!40000 ALTER TABLE `user_user_management_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_user_management_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_vacations`
--

DROP TABLE IF EXISTS `user_vacations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_vacations` (
                                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                  `user_id` bigint(20) unsigned NOT NULL,
                                  `from` date NOT NULL,
                                  `until` date NOT NULL,
                                  `created_at` timestamp NULL DEFAULT NULL,
                                  `updated_at` timestamp NULL DEFAULT NULL,
                                  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_vacations`
--

LOCK TABLES `user_vacations` WRITE;
/*!40000 ALTER TABLE `user_vacations` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_vacations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_work_time_patterns`
--

DROP TABLE IF EXISTS `user_work_time_patterns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_work_time_patterns` (
                                           `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                           `name` varchar(255) NOT NULL,
                                           `description` text DEFAULT NULL,
                                           `monday` time NOT NULL DEFAULT '00:00:00',
                                           `tuesday` time NOT NULL DEFAULT '00:00:00',
                                           `wednesday` time NOT NULL DEFAULT '00:00:00',
                                           `thursday` time NOT NULL DEFAULT '00:00:00',
                                           `friday` time NOT NULL DEFAULT '00:00:00',
                                           `saturday` time NOT NULL DEFAULT '00:00:00',
                                           `sunday` time NOT NULL DEFAULT '00:00:00',
                                           `created_at` timestamp NULL DEFAULT NULL,
                                           `updated_at` timestamp NULL DEFAULT NULL,
                                           PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_work_time_patterns`
--

LOCK TABLES `user_work_time_patterns` WRITE;
/*!40000 ALTER TABLE `user_work_time_patterns` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_work_time_patterns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_work_times`
--

DROP TABLE IF EXISTS `user_work_times`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_work_times` (
                                   `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                   `user_id` bigint(20) unsigned NOT NULL,
                                   `work_time_pattern_id` bigint(20) unsigned DEFAULT NULL,
                                   `monday` time DEFAULT NULL,
                                   `tuesday` time DEFAULT NULL,
                                   `wednesday` time DEFAULT NULL,
                                   `thursday` time DEFAULT NULL,
                                   `friday` time DEFAULT NULL,
                                   `saturday` time DEFAULT NULL,
                                   `sunday` time DEFAULT NULL,
                                   `created_at` timestamp NULL DEFAULT NULL,
                                   `updated_at` timestamp NULL DEFAULT NULL,
                                   `valid_from` date DEFAULT NULL,
                                   `valid_until` date DEFAULT NULL,
                                   `is_active` tinyint(1) NOT NULL DEFAULT 0,
                                   PRIMARY KEY (`id`),
                                   KEY `user_work_times_user_id_foreign` (`user_id`),
                                   KEY `user_work_times_work_time_pattern_id_foreign` (`work_time_pattern_id`),
                                   CONSTRAINT `user_work_times_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
                                   CONSTRAINT `user_work_times_work_time_pattern_id_foreign` FOREIGN KEY (`work_time_pattern_id`) REFERENCES `user_work_time_patterns` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_work_times`
--

LOCK TABLES `user_work_times` WRITE;
/*!40000 ALTER TABLE `user_work_times` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_work_times` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_worker_shift_plan_filters`
--

DROP TABLE IF EXISTS `user_worker_shift_plan_filters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_worker_shift_plan_filters` (
                                                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                                  `user_id` bigint(20) unsigned NOT NULL,
                                                  `start_date` date DEFAULT NULL,
                                                  `end_date` date DEFAULT NULL,
                                                  `created_at` timestamp NULL DEFAULT NULL,
                                                  `updated_at` timestamp NULL DEFAULT NULL,
                                                  PRIMARY KEY (`id`),
                                                  KEY `user_worker_shift_plan_filters_user_id_foreign` (`user_id`),
                                                  CONSTRAINT `user_worker_shift_plan_filters_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_worker_shift_plan_filters`
--

LOCK TABLES `user_worker_shift_plan_filters` WRITE;
/*!40000 ALTER TABLE `user_worker_shift_plan_filters` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_worker_shift_plan_filters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
                         `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                         `first_name` varchar(255) NOT NULL,
                         `last_name` varchar(255) NOT NULL,
                         `chat_public_key` longtext DEFAULT NULL,
                         `work_name` varchar(255) DEFAULT NULL,
                         `email` varchar(255) NOT NULL,
                         `work_time_balance` int(11) NOT NULL DEFAULT 0 COMMENT 'The balance of work time in minutes for the user',
                         `show_crafts` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`show_crafts`)),
                         `opened_crafts` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`opened_crafts`)),
                         `show_qualifications` longtext NOT NULL DEFAULT '[]',
                         `shift_plan_user_sort_by_id` varchar(255) DEFAULT NULL,
                         `sort_type_shift_tab` varchar(255) DEFAULT NULL,
                         `email_verified_at` timestamp NULL DEFAULT NULL,
                         `phone_number` varchar(255) DEFAULT NULL,
                         `password` varchar(255) NOT NULL,
                         `two_factor_secret` text DEFAULT NULL,
                         `two_factor_recovery_codes` text DEFAULT NULL,
                         `position` varchar(255) DEFAULT NULL,
                         `business` varchar(255) DEFAULT NULL,
                         `description` longtext DEFAULT NULL,
                         `work_description` longtext DEFAULT NULL,
                         `toggle_hints` tinyint(1) NOT NULL DEFAULT 1,
                         `opened_checklists` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`opened_checklists`)),
                         `opened_areas` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`opened_areas`)),
                         `remember_token` varchar(100) DEFAULT NULL,
                         `bulk_column_size` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '{"1":146,"2":146,"3":146,"4":146,"5":146,"6":308}' CHECK (json_valid(`bulk_column_size`)),
                         `show_description_in_bulk` tinyint(1) NOT NULL DEFAULT 0,
                         `show_project_team_names` tinyint(1) NOT NULL DEFAULT 0,
                         `checklist_style` varchar(255) NOT NULL DEFAULT 'list',
                         `is_sidebar_opened` tinyint(1) NOT NULL DEFAULT 1,
                         `current_team_id` bigint(20) unsigned DEFAULT NULL,
                         `profile_photo_path` varchar(2048) DEFAULT NULL,
                         `temporary` tinyint(1) NOT NULL DEFAULT 0,
                         `employStart` date DEFAULT NULL,
                         `employEnd` date DEFAULT NULL,
                         `can_work_shifts` tinyint(1) NOT NULL DEFAULT 0,
                         `weekly_working_hours` double DEFAULT 40,
                         `salary_per_hour` int(11) DEFAULT NULL,
                         `salary_description` longtext DEFAULT NULL,
                         `drawer_height` int(11) NOT NULL DEFAULT 400,
                         `bulk_sort_id` int(11) NOT NULL DEFAULT 0,
                         `language` varchar(255) NOT NULL DEFAULT 'de',
                         `created_at` timestamp NULL DEFAULT NULL,
                         `updated_at` timestamp NULL DEFAULT NULL,
                         `zoom_factor` double(8,2) NOT NULL DEFAULT 1.00,
                         `compact_mode` tinyint(1) NOT NULL DEFAULT 0,
                         `goto_mode` varchar(255) NOT NULL DEFAULT 'day',
                         `at_a_glance` tinyint(1) NOT NULL DEFAULT 0,
                         `notification_enums_last_sent_dates` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`notification_enums_last_sent_dates`)),
                         `show_notification_indicator` tinyint(1) NOT NULL DEFAULT 0,
                         `is_freelancer` tinyint(1) NOT NULL DEFAULT 0,
                         `inventory_sort_column_id` int(11) DEFAULT NULL,
                         `inventory_sort_direction` varchar(4) DEFAULT NULL,
                         `inventory_grid_layout` tinyint(1) NOT NULL DEFAULT 1,
                         `checklist_has_projects` tinyint(1) NOT NULL DEFAULT 0,
                         `checklist_no_projects` tinyint(1) NOT NULL DEFAULT 0,
                         `checklist_private_checklists` tinyint(1) NOT NULL DEFAULT 0,
                         `checklist_no_private_checklists` tinyint(1) NOT NULL DEFAULT 0,
                         `checklist_completed_tasks` tinyint(1) NOT NULL DEFAULT 0,
                         `checklist_show_without_tasks` tinyint(1) NOT NULL DEFAULT 1,
                         `is_developer` tinyint(1) NOT NULL DEFAULT 0,
                         `pronouns` varchar(255) DEFAULT NULL,
                         `email_private` tinyint(1) NOT NULL DEFAULT 0,
                         `phone_private` tinyint(1) NOT NULL DEFAULT 0,
                         `daily_view` tinyint(1) NOT NULL DEFAULT 0,
                         `last_project_id` bigint(20) unsigned DEFAULT NULL,
                         `entities_per_page` int(11) NOT NULL DEFAULT 10,
                         `use_chat` tinyint(1) NOT NULL DEFAULT 0,
                         `chat_popup_position` varchar(20) NOT NULL DEFAULT 'bottom-right' COMMENT 'Position des Chat-Popups (bottom-right, bottom-left, top-right, top-left)',
                         `chat_push_notification` tinyint(1) NOT NULL DEFAULT 1,
                         `is_time_preset_open` tinyint(1) NOT NULL DEFAULT 0,
                         `crm_contact_id` bigint(20) unsigned DEFAULT NULL,
                         PRIMARY KEY (`id`),
                         UNIQUE KEY `users_email_unique` (`email`),
                         KEY `users_last_project_id_foreign` (`last_project_id`),
                         KEY `users_crm_contact_id_foreign` (`crm_contact_id`),
                         CONSTRAINT `users_crm_contact_id_foreign` FOREIGN KEY (`crm_contact_id`) REFERENCES `crm_contacts` (`id`) ON DELETE SET NULL,
                         CONSTRAINT `users_last_project_id_foreign` FOREIGN KEY (`last_project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_assigned_crafts`
--

DROP TABLE IF EXISTS `users_assigned_crafts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_assigned_crafts` (
                                         `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                         `user_id` bigint(20) unsigned NOT NULL,
                                         `craft_id` bigint(20) unsigned NOT NULL,
                                         PRIMARY KEY (`id`),
                                         KEY `users_assigned_crafts_user_id_foreign` (`user_id`),
                                         KEY `users_assigned_crafts_craft_id_foreign` (`craft_id`),
                                         CONSTRAINT `users_assigned_crafts_craft_id_foreign` FOREIGN KEY (`craft_id`) REFERENCES `crafts` (`id`),
                                         CONSTRAINT `users_assigned_crafts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_assigned_crafts`
--

LOCK TABLES `users_assigned_crafts` WRITE;
/*!40000 ALTER TABLE `users_assigned_crafts` DISABLE KEYS */;
/*!40000 ALTER TABLE `users_assigned_crafts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vacation_conflicts`
--

DROP TABLE IF EXISTS `vacation_conflicts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vacation_conflicts` (
                                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                      `vacation_id` bigint(20) unsigned NOT NULL,
                                      `shift_id` bigint(20) unsigned NOT NULL,
                                      `user_name` varchar(255) NOT NULL,
                                      `date` date NOT NULL,
                                      `start_time` time NOT NULL,
                                      `end_time` time NOT NULL,
                                      `created_at` timestamp NULL DEFAULT NULL,
                                      `updated_at` timestamp NULL DEFAULT NULL,
                                      PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vacation_conflicts`
--

LOCK TABLES `vacation_conflicts` WRITE;
/*!40000 ALTER TABLE `vacation_conflicts` DISABLE KEYS */;
/*!40000 ALTER TABLE `vacation_conflicts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vacation_series`
--

DROP TABLE IF EXISTS `vacation_series`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vacation_series` (
                                   `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                   `frequency` varchar(20) NOT NULL,
                                   `end_date` date NOT NULL,
                                   `created_at` timestamp NULL DEFAULT NULL,
                                   `updated_at` timestamp NULL DEFAULT NULL,
                                   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vacation_series`
--

LOCK TABLES `vacation_series` WRITE;
/*!40000 ALTER TABLE `vacation_series` DISABLE KEYS */;
/*!40000 ALTER TABLE `vacation_series` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vacations`
--

DROP TABLE IF EXISTS `vacations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vacations` (
                             `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                             `vacationer_type` varchar(255) NOT NULL,
                             `vacationer_id` bigint(20) unsigned NOT NULL,
                             `start_time` time DEFAULT NULL,
                             `end_time` time DEFAULT NULL,
                             `date` date NOT NULL,
                             `full_day` tinyint(1) NOT NULL DEFAULT 0,
                             `comment` varchar(20) DEFAULT NULL,
                             `is_series` tinyint(1) NOT NULL DEFAULT 0,
                             `series_id` int(11) DEFAULT NULL,
                             `created_at` timestamp NULL DEFAULT NULL,
                             `updated_at` timestamp NULL DEFAULT NULL,
                             `type` varchar(255) NOT NULL,
                             `created_by` bigint(20) unsigned DEFAULT NULL,
                             PRIMARY KEY (`id`),
                             KEY `vacations_vacationer_type_vacationer_id_index` (`vacationer_type`,`vacationer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vacations`
--

LOCK TABLES `vacations` WRITE;
/*!40000 ALTER TABLE `vacations` DISABLE KEYS */;
/*!40000 ALTER TABLE `vacations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `websockets_statistics_entries`
--

DROP TABLE IF EXISTS `websockets_statistics_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `websockets_statistics_entries` (
                                                 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                                                 `app_id` varchar(255) NOT NULL,
                                                 `peak_connection_count` int(11) NOT NULL,
                                                 `websocket_message_count` int(11) NOT NULL,
                                                 `api_message_count` int(11) NOT NULL,
                                                 `created_at` timestamp NULL DEFAULT NULL,
                                                 `updated_at` timestamp NULL DEFAULT NULL,
                                                 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `websockets_statistics_entries`
--

LOCK TABLES `websockets_statistics_entries` WRITE;
/*!40000 ALTER TABLE `websockets_statistics_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `websockets_statistics_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `work_time_bookings`
--

DROP TABLE IF EXISTS `work_time_bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `work_time_bookings` (
                                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                      `user_id` bigint(20) unsigned NOT NULL,
                                      `booker_id` bigint(20) unsigned DEFAULT NULL,
                                      `name` varchar(255) DEFAULT NULL,
                                      `comment` text DEFAULT NULL,
                                      `booking_day` date DEFAULT NULL,
                                      `booking_weekday` int(11) DEFAULT NULL,
                                      `wanted_working_hours` int(11) NOT NULL DEFAULT 0,
                                      `worked_hours` int(11) NOT NULL DEFAULT 0,
                                      `is_special_day` tinyint(1) NOT NULL DEFAULT 0,
                                      `nightly_working_hours` int(11) NOT NULL DEFAULT 0,
                                      `work_time_balance_change` int(11) NOT NULL DEFAULT 0,
                                      `created_at` timestamp NULL DEFAULT NULL,
                                      `updated_at` timestamp NULL DEFAULT NULL,
                                      PRIMARY KEY (`id`),
                                      KEY `work_time_bookings_user_id_foreign` (`user_id`),
                                      KEY `work_time_bookings_booker_id_foreign` (`booker_id`),
                                      CONSTRAINT `work_time_bookings_booker_id_foreign` FOREIGN KEY (`booker_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
                                      CONSTRAINT `work_time_bookings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `work_time_bookings`
--

LOCK TABLES `work_time_bookings` WRITE;
/*!40000 ALTER TABLE `work_time_bookings` DISABLE KEYS */;
/*!40000 ALTER TABLE `work_time_bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `work_time_change_requests`
--

DROP TABLE IF EXISTS `work_time_change_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `work_time_change_requests` (
                                             `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                             `user_id` bigint(20) unsigned NOT NULL,
                                             `request_start_time` time NOT NULL,
                                             `request_end_time` time NOT NULL,
                                             `request_end_date` date DEFAULT NULL,
                                             `shift_id` bigint(20) unsigned DEFAULT NULL,
                                             `craft_id` bigint(20) unsigned DEFAULT NULL,
                                             `status` varchar(255) NOT NULL DEFAULT 'pending',
                                             `request_comment` text DEFAULT NULL,
                                             `decline_comment` text DEFAULT NULL,
                                             `requested_by` bigint(20) unsigned DEFAULT NULL,
                                             `approved_by` bigint(20) unsigned DEFAULT NULL,
                                             `declined_by` bigint(20) unsigned DEFAULT NULL,
                                             `created_at` timestamp NULL DEFAULT NULL,
                                             `updated_at` timestamp NULL DEFAULT NULL,
                                             PRIMARY KEY (`id`),
                                             KEY `work_time_change_requests_user_id_foreign` (`user_id`),
                                             KEY `work_time_change_requests_shift_id_foreign` (`shift_id`),
                                             KEY `work_time_change_requests_craft_id_foreign` (`craft_id`),
                                             KEY `work_time_change_requests_requested_by_foreign` (`requested_by`),
                                             KEY `work_time_change_requests_approved_by_foreign` (`approved_by`),
                                             KEY `work_time_change_requests_declined_by_foreign` (`declined_by`),
                                             CONSTRAINT `work_time_change_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
                                             CONSTRAINT `work_time_change_requests_craft_id_foreign` FOREIGN KEY (`craft_id`) REFERENCES `crafts` (`id`) ON DELETE SET NULL,
                                             CONSTRAINT `work_time_change_requests_declined_by_foreign` FOREIGN KEY (`declined_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
                                             CONSTRAINT `work_time_change_requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
                                             CONSTRAINT `work_time_change_requests_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE SET NULL,
                                             CONSTRAINT `work_time_change_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `work_time_change_requests`
--

LOCK TABLES `work_time_change_requests` WRITE;
/*!40000 ALTER TABLE `work_time_change_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `work_time_change_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workflow_definition_configs`
--

DROP TABLE IF EXISTS `workflow_definition_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflow_definition_configs` (
                                               `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                               `workflow_definition_id` bigint(20) unsigned NOT NULL,
                                               `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`config`)),
                                               `deprecated_at` timestamp NULL DEFAULT NULL,
                                               `created_at` timestamp NULL DEFAULT NULL,
                                               `updated_at` timestamp NULL DEFAULT NULL,
                                               PRIMARY KEY (`id`),
                                               KEY `workflow_definition_config_index` (`workflow_definition_id`,`deprecated_at`),
                                               CONSTRAINT `workflow_definition_configs_workflow_definition_id_foreign` FOREIGN KEY (`workflow_definition_id`) REFERENCES `workflow_definitions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflow_definition_configs`
--

LOCK TABLES `workflow_definition_configs` WRITE;
/*!40000 ALTER TABLE `workflow_definition_configs` DISABLE KEYS */;
/*!40000 ALTER TABLE `workflow_definition_configs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workflow_definitions`
--

DROP TABLE IF EXISTS `workflow_definitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflow_definitions` (
                                        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                        `name` varchar(255) NOT NULL,
                                        `description` text DEFAULT NULL,
                                        `type` varchar(255) NOT NULL,
                                        `is_active` tinyint(1) NOT NULL DEFAULT 1,
                                        `max_instances` int(11) DEFAULT NULL,
                                        `created_at` timestamp NULL DEFAULT NULL,
                                        `updated_at` timestamp NULL DEFAULT NULL,
                                        `deleted_at` timestamp NULL DEFAULT NULL,
                                        PRIMARY KEY (`id`),
                                        UNIQUE KEY `workflow_definitions_name_type_unique` (`name`,`type`),
                                        KEY `workflow_definitions_type_is_active_index` (`type`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflow_definitions`
--

LOCK TABLES `workflow_definitions` WRITE;
/*!40000 ALTER TABLE `workflow_definitions` DISABLE KEYS */;
/*!40000 ALTER TABLE `workflow_definitions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workflow_instance_data`
--

DROP TABLE IF EXISTS `workflow_instance_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflow_instance_data` (
                                          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                          `workflow_instance_id` bigint(20) unsigned NOT NULL,
                                          `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
                                          `deprecated_at` timestamp NULL DEFAULT NULL,
                                          `created_at` timestamp NULL DEFAULT NULL,
                                          `updated_at` timestamp NULL DEFAULT NULL,
                                          PRIMARY KEY (`id`),
                                          KEY `workflow_instance_data_index` (`workflow_instance_id`,`deprecated_at`),
                                          CONSTRAINT `workflow_instance_data_workflow_instance_id_foreign` FOREIGN KEY (`workflow_instance_id`) REFERENCES `workflow_instances` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflow_instance_data`
--

LOCK TABLES `workflow_instance_data` WRITE;
/*!40000 ALTER TABLE `workflow_instance_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `workflow_instance_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workflow_instances`
--

DROP TABLE IF EXISTS `workflow_instances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflow_instances` (
                                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                      `workflow_definition_config_id` bigint(20) unsigned NOT NULL,
                                      `subject_type` varchar(255) NOT NULL,
                                      `subject_id` bigint(20) unsigned NOT NULL,
                                      `current_place` varchar(255) DEFAULT NULL,
                                      `completed_at` timestamp NULL DEFAULT NULL,
                                      `created_at` timestamp NULL DEFAULT NULL,
                                      `updated_at` timestamp NULL DEFAULT NULL,
                                      `deleted_at` timestamp NULL DEFAULT NULL,
                                      PRIMARY KEY (`id`),
                                      KEY `workflow_instances_subject_type_subject_id_index` (`subject_type`,`subject_id`),
                                      KEY `workflow_instance_completed_at_index` (`workflow_definition_config_id`,`completed_at`),
                                      KEY `workflow_instances_current_place_index` (`current_place`),
                                      CONSTRAINT `workflow_instances_workflow_definition_config_id_foreign` FOREIGN KEY (`workflow_definition_config_id`) REFERENCES `workflow_definition_configs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflow_instances`
--

LOCK TABLES `workflow_instances` WRITE;
/*!40000 ALTER TABLE `workflow_instances` DISABLE KEYS */;
/*!40000 ALTER TABLE `workflow_instances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workflow_logs`
--

DROP TABLE IF EXISTS `workflow_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflow_logs` (
                                 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                 `workflow_instance_id` bigint(20) unsigned NOT NULL,
                                 `transition` varchar(255) DEFAULT NULL,
                                 `from_place` varchar(255) DEFAULT NULL,
                                 `to_place` varchar(255) NOT NULL,
                                 `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
                                 `triggered_at` timestamp NOT NULL,
                                 `created_at` timestamp NULL DEFAULT NULL,
                                 `updated_at` timestamp NULL DEFAULT NULL,
                                 PRIMARY KEY (`id`),
                                 KEY `workflow_log_index` (`workflow_instance_id`,`triggered_at`),
                                 CONSTRAINT `workflow_logs_workflow_instance_id_foreign` FOREIGN KEY (`workflow_instance_id`) REFERENCES `workflow_instances` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflow_logs`
--

LOCK TABLES `workflow_logs` WRITE;
/*!40000 ALTER TABLE `workflow_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `workflow_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workflow_rule_assignments`
--

DROP TABLE IF EXISTS `workflow_rule_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflow_rule_assignments` (
                                             `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                             `workflow_rule_id` bigint(20) unsigned NOT NULL,
                                             `subject_type` varchar(255) NOT NULL,
                                             `subject_id` bigint(20) unsigned NOT NULL,
                                             `assigned_at` timestamp NOT NULL,
                                             `assigned_by` bigint(20) unsigned DEFAULT NULL,
                                             `created_at` timestamp NULL DEFAULT NULL,
                                             `updated_at` timestamp NULL DEFAULT NULL,
                                             PRIMARY KEY (`id`),
                                             UNIQUE KEY `workflow_rule_assignment_unique` (`workflow_rule_id`,`subject_type`,`subject_id`),
                                             KEY `workflow_rule_assignment_index` (`workflow_rule_id`,`subject_type`,`subject_id`),
                                             KEY `workflow_rule_assignments_subject_type_subject_id_index` (`subject_type`,`subject_id`),
                                             CONSTRAINT `workflow_rule_assignments_workflow_rule_id_foreign` FOREIGN KEY (`workflow_rule_id`) REFERENCES `workflow_rules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflow_rule_assignments`
--

LOCK TABLES `workflow_rule_assignments` WRITE;
/*!40000 ALTER TABLE `workflow_rule_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `workflow_rule_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workflow_rule_contract_assignments`
--

DROP TABLE IF EXISTS `workflow_rule_contract_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflow_rule_contract_assignments` (
                                                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                                      `workflow_rule_id` bigint(20) unsigned NOT NULL,
                                                      `contract_id` bigint(20) unsigned NOT NULL,
                                                      `created_at` timestamp NULL DEFAULT NULL,
                                                      `updated_at` timestamp NULL DEFAULT NULL,
                                                      PRIMARY KEY (`id`),
                                                      UNIQUE KEY `rule_contract_unique` (`workflow_rule_id`,`contract_id`),
                                                      KEY `rule_assignments_rule_idx` (`workflow_rule_id`),
                                                      KEY `rule_assignments_contract_idx` (`contract_id`),
                                                      CONSTRAINT `workflow_rule_contract_assignments_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `user_contracts` (`id`) ON DELETE CASCADE,
                                                      CONSTRAINT `workflow_rule_contract_assignments_workflow_rule_id_foreign` FOREIGN KEY (`workflow_rule_id`) REFERENCES `workflow_rules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflow_rule_contract_assignments`
--

LOCK TABLES `workflow_rule_contract_assignments` WRITE;
/*!40000 ALTER TABLE `workflow_rule_contract_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `workflow_rule_contract_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workflow_rule_user_notifications`
--

DROP TABLE IF EXISTS `workflow_rule_user_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflow_rule_user_notifications` (
                                                    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                                    `workflow_rule_id` bigint(20) unsigned NOT NULL,
                                                    `user_id` bigint(20) unsigned NOT NULL,
                                                    `created_at` timestamp NULL DEFAULT NULL,
                                                    `updated_at` timestamp NULL DEFAULT NULL,
                                                    PRIMARY KEY (`id`),
                                                    UNIQUE KEY `rule_user_notification_unique` (`workflow_rule_id`,`user_id`),
                                                    KEY `rule_notifications_rule_idx` (`workflow_rule_id`),
                                                    KEY `rule_notifications_user_idx` (`user_id`),
                                                    CONSTRAINT `workflow_rule_user_notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
                                                    CONSTRAINT `workflow_rule_user_notifications_workflow_rule_id_foreign` FOREIGN KEY (`workflow_rule_id`) REFERENCES `workflow_rules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflow_rule_user_notifications`
--

LOCK TABLES `workflow_rule_user_notifications` WRITE;
/*!40000 ALTER TABLE `workflow_rule_user_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `workflow_rule_user_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workflow_rules`
--

DROP TABLE IF EXISTS `workflow_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflow_rules` (
                                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                  `name` varchar(255) NOT NULL,
                                  `description` text DEFAULT NULL,
                                  `trigger_type` varchar(255) NOT NULL,
                                  `individual_number_value` decimal(10,2) DEFAULT NULL,
                                  `warning_color` varchar(7) NOT NULL DEFAULT '#ff0000',
                                  `is_active` tinyint(1) NOT NULL DEFAULT 1,
                                  `configuration` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`configuration`)),
                                  `notify_on_violation` tinyint(1) NOT NULL DEFAULT 0,
                                  `created_at` timestamp NULL DEFAULT NULL,
                                  `updated_at` timestamp NULL DEFAULT NULL,
                                  `deleted_at` timestamp NULL DEFAULT NULL,
                                  PRIMARY KEY (`id`),
                                  KEY `workflow_rule_index` (`trigger_type`,`is_active`),
                                  KEY `workflow_rules_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflow_rules`
--

LOCK TABLES `workflow_rules` WRITE;
/*!40000 ALTER TABLE `workflow_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `workflow_rules` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-21  6:10:06
