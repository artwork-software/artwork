/*M!999999\- enable the sandbox mode */ 
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
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
  `note` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
DROP TABLE IF EXISTS `artist_residencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `artist_residencies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `civil_name` varchar(255) DEFAULT NULL,
  `phone_number` varchar(255) DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `accommodation_id` bigint(20) unsigned DEFAULT NULL,
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
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `artist_residencies_project_id_foreign` (`project_id`),
  KEY `artist_residencies_accommodation_id_foreign` (`accommodation_id`),
  CONSTRAINT `artist_residencies_accommodation_id_foreign` FOREIGN KEY (`accommodation_id`) REFERENCES `accommodations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `artist_residencies_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
DROP TABLE IF EXISTS `category_project`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `category_project` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `category_project_project_id_foreign` (`project_id`),
  KEY `category_project_category_id_foreign` (`category_id`),
  CONSTRAINT `category_project_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `category_project_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
DROP TABLE IF EXISTS `chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `chat_id` bigint(20) unsigned NOT NULL,
  `type` enum('text','audio','video') NOT NULL DEFAULT 'text',
  `sender_id` bigint(20) unsigned NOT NULL,
  `cipher_for_sender` longtext NOT NULL,
  `ciphers_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`ciphers_json`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chat_messages_chat_id_foreign` (`chat_id`),
  KEY `chat_messages_sender_id_foreign` (`sender_id`),
  CONSTRAINT `chat_messages_chat_id_foreign` FOREIGN KEY (`chat_id`) REFERENCES `chats` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  CONSTRAINT `columns_locked_by_foreign` FOREIGN KEY (`locked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `columns_table_id_foreign` FOREIGN KEY (`table_id`) REFERENCES `tables` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  CONSTRAINT `comments_project_file_id_foreign` FOREIGN KEY (`project_file_id`) REFERENCES `contracts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `comments_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
DROP TABLE IF EXISTS `components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `components` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` enum('TextArea','TextField','Checkbox','DropDown','Title','CalendarTab','ProjectStateComponent','ChecklistComponent','ProjectTeamComponent','ProjectGroupComponent','ProjectAttributesComponent','ShiftTab','RelevantDatesForShiftPlanningComponent','ShiftContactPersonsComponent','GeneralShiftInformationComponent','BudgetTab','ProjectBudgetDeadlineComponent','CommentTab','ProjectDocumentsComponent','ProjectTitleComponent','SeparatorComponent','CommentAllTab','ProjectAllDocumentsComponent','ChecklistAllComponent','BudgetInformations','BulkBody','ArtistResidenciesComponent','ProjectGroupDisplayComponent','GroupProjectDisplayComponent','DisclosureComponent') DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `special` tinyint(1) NOT NULL DEFAULT 0,
  `sidebar_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `permission_type` enum('allSeeAndEdit','allSeeSomeEdit','someSeeSomeEdit') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
DROP TABLE IF EXISTS `contracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contracts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `basename` varchar(255) NOT NULL,
  `contract_partner` varchar(255) NOT NULL,
  `amount` int(11) NOT NULL,
  `creator_id` bigint(20) unsigned DEFAULT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `contract_type_id` bigint(20) unsigned DEFAULT NULL,
  `company_type_id` bigint(20) unsigned DEFAULT NULL,
  `currency_id` bigint(20) unsigned DEFAULT NULL,
  `ksk_liable` tinyint(1) DEFAULT 0,
  `resident_abroad` tinyint(1) DEFAULT 0,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
DROP TABLE IF EXISTS `disclosure_components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `disclosure_components` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `disclosure_id` bigint(20) unsigned NOT NULL,
  `component_id` bigint(20) unsigned NOT NULL,
  `order` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disclosure_components_disclosure_id_foreign` (`disclosure_id`),
  KEY `disclosure_components_component_id_foreign` (`component_id`),
  CONSTRAINT `disclosure_components_component_id_foreign` FOREIGN KEY (`component_id`) REFERENCES `components` (`id`),
  CONSTRAINT `disclosure_components_disclosure_id_foreign` FOREIGN KEY (`disclosure_id`) REFERENCES `components` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  CONSTRAINT `events_declined_room_id_foreign` FOREIGN KEY (`declined_room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `events_event_status_id_foreign` FOREIGN KEY (`event_status_id`) REFERENCES `event_statuses` (`id`),
  CONSTRAINT `events_event_type_id_foreign` FOREIGN KEY (`event_type_id`) REFERENCES `event_types` (`id`),
  CONSTRAINT `events_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `events_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `events_series_id_foreign` FOREIGN KEY (`series_id`) REFERENCES `series_events` (`id`),
  CONSTRAINT `events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
DROP TABLE IF EXISTS `external_issues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `external_issues` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
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
DROP TABLE IF EXISTS `freelancers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `freelancers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `position` varchar(255) DEFAULT NULL,
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `genre_project`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `genre_project` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `genre_id` bigint(20) unsigned NOT NULL,
  `project_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  `color` varchar(255) NOT NULL DEFAULT '#333',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
DROP TABLE IF EXISTS `individual_times`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `individual_times` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `timeable_type` varchar(255) NOT NULL,
  `timeable_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `full_day` tinyint(1) NOT NULL DEFAULT 0,
  `working_time_minutes` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `individual_times_timeable_type_timeable_id_index` (`timeable_type`,`timeable_id`),
  KEY `individual_times_timeable_id_timeable_type_index` (`timeable_id`,`timeable_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
DROP TABLE IF EXISTS `inventory_article_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_article_statuses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `default` tinyint(1) NOT NULL DEFAULT 0,
  `deletable` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
DROP TABLE IF EXISTS `inventory_category_property_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_category_property_values` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `inventory_article_property_id` bigint(20) unsigned NOT NULL,
  `inventory_category_propertyable_type` varchar(255) NOT NULL,
  `inventory_category_propertyable_id` bigint(20) unsigned NOT NULL,
  `value` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_cat_prop_morph_idx` (`inventory_category_propertyable_type`,`inventory_category_propertyable_id`),
  KEY `inv_cat_prop_fk` (`inventory_article_property_id`),
  CONSTRAINT `inv_cat_prop_fk` FOREIGN KEY (`inventory_article_property_id`) REFERENCES `inventory_article_properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inventory_detailed_quantity_articles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_detailed_quantity_articles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `quantity` double(8,2) NOT NULL DEFAULT 0.00,
  `inventory_article_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `inventory_article_status_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  UNIQUE KEY `money_source_files_basename_unique` (`basename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
DROP TABLE IF EXISTS `money_source_task_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `money_source_task_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `money_source_user_pinned`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `money_source_user_pinned` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `money_source_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
DROP TABLE IF EXISTS `project_sector`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_sector` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sector_id` bigint(20) unsigned NOT NULL,
  `project_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
DROP TABLE IF EXISTS `project_states`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_states` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `color` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_tabs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_tabs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  `default` tinyint(1) NOT NULL DEFAULT 0,
  `order` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `projects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `color` varchar(255) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `artists` longtext NOT NULL DEFAULT '',
  `shift_description` longtext DEFAULT NULL,
  `number_of_participants` varchar(255) DEFAULT NULL,
  `key_visual_path` varchar(255) DEFAULT NULL,
  `law_size` enum('SMALL','BIG') NOT NULL DEFAULT 'SMALL',
  `live_music` tinyint(1) NOT NULL DEFAULT 0,
  `own_copyright` tinyint(1) NOT NULL DEFAULT 0,
  `collecting_society_id` bigint(20) unsigned DEFAULT NULL,
  `cost_center_id` bigint(20) unsigned DEFAULT NULL,
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
  KEY `projects_collecting_society_id_foreign` (`collecting_society_id`),
  KEY `projects_user_id_foreign` (`user_id`),
  CONSTRAINT `projects_collecting_society_id_foreign` FOREIGN KEY (`collecting_society_id`) REFERENCES `collecting_societies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `projects_cost_center_id_foreign` FOREIGN KEY (`cost_center_id`) REFERENCES `cost_centers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `projects_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  CONSTRAINT `rooms_area_id_foreign` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`),
  CONSTRAINT `rooms_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  CONSTRAINT `sage_assigned_data_column_cell_id_foreign` FOREIGN KEY (`column_cell_id`) REFERENCES `column_sub_position_row` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  CONSTRAINT `sage_not_assigned_data_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
DROP TABLE IF EXISTS `shift_plan_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_plan_comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `comment` text DEFAULT NULL,
  `date` date NOT NULL,
  `commentable_type` varchar(255) NOT NULL,
  `commentable_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shift_plan_comments_commentable_type_commentable_id_index` (`commentable_type`,`commentable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
DROP TABLE IF EXISTS `shift_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shift_id` bigint(20) unsigned NOT NULL,
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
  `shift_uuid` varchar(255) DEFAULT NULL,
  `event_start_day` date DEFAULT NULL,
  `event_end_day` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `committing_user_id` bigint(20) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `room_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shifts_committing_user_id_foreign` (`committing_user_id`),
  KEY `shifts_event_id_foreign` (`event_id`),
  KEY `shifts_craft_id_foreign` (`craft_id`),
  KEY `shifts_room_id_foreign` (`room_id`),
  CONSTRAINT `shifts_committing_user_id_foreign` FOREIGN KEY (`committing_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shifts_craft_id_foreign` FOREIGN KEY (`craft_id`) REFERENCES `crafts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shifts_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shifts_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shifts_freelancers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shifts_freelancers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shift_id` bigint(20) unsigned NOT NULL,
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
DROP TABLE IF EXISTS `shifts_service_providers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shifts_service_providers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shift_id` bigint(20) unsigned NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
DROP TABLE IF EXISTS `sub_position_rows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sub_position_rows` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sub_position_id` bigint(20) NOT NULL,
  `position` int(11) NOT NULL,
  `commented` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  KEY `sum_money_sources_sourceable_type_sourceable_id_index` (`sourceable_type`,`sourceable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  `project_artists` tinyint(1) NOT NULL DEFAULT 0,
  `show_qualifications` tinyint(1) NOT NULL DEFAULT 0,
  `shift_notes` tinyint(1) NOT NULL DEFAULT 0,
  `hide_unoccupied_rooms` tinyint(1) NOT NULL DEFAULT 0,
  `display_project_groups` tinyint(1) NOT NULL DEFAULT 0,
  `show_unplanned_events` tinyint(1) NOT NULL DEFAULT 0,
  `show_planned_events` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  `specific_event_types` tinyint(1) NOT NULL DEFAULT 0,
  `event_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`event_types`)),
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
  `show_crafts` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`show_crafts`)),
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_last_project_id_foreign` (`last_project_id`),
  CONSTRAINT `users_last_project_id_foreign` FOREIGN KEY (`last_project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  `type` enum('NOT_AVAILABLE','OFF_WORK') NOT NULL DEFAULT 'NOT_AVAILABLE',
  PRIMARY KEY (`id`),
  KEY `vacations_vacationer_type_vacationer_id_index` (`vacationer_type`,`vacationer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

/*M!999999\- enable the sandbox mode */ 
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0000_00_00_000000_create_websockets_statistics_entries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'2014_10_12_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'2014_10_12_100000_create_password_resets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2014_10_12_200000_add_two_factor_columns_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2019_08_19_000000_create_failed_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2019_12_14_000001_create_personal_access_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2022_03_22_103801_create_sessions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2022_03_22_104445_create_permission_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2022_03_23_104140_create_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2022_03_23_104607_create_general_settings',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2022_03_23_115733_create_invitations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2022_03_29_085525_create_departments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2022_03_29_095812_create_department_user_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2022_03_30_153338_create_department_invitation',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2022_04_05_184122_create_projects_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2022_04_06_164912_create_project_user_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2022_04_06_164932_create_department_project_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2022_04_09_174131_create_checklists_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2022_04_09_174150_create_tasks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2022_04_13_151419_create_sectors_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2022_04_13_152316_create_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2022_04_13_152908_create_genres_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2022_04_13_154105_create_comments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2022_04_20_123145_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2022_04_20_181011_create_checklist_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2022_04_20_193835_create_task_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2022_04_28_112434_create_project_files_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2022_05_01_165517_create_areas_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2022_05_01_165853_create_rooms_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2022_05_01_174257_create_room_user_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2022_05_05_115856_create_project_histories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2022_05_07_160829_create_room_files_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2022_06_02_170244_create_event_migration',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2022_06_02_170256_create_event_type_migration',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2022_07_14_145114_add_name_to_permissions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2022_07_21_143417_add_name_to_roles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2022_09_26_150150_create_room_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2022_09_26_150242_create_room_attributes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2022_09_28_174633_create_room_pivot_room_attribute_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2022_09_28_213800_create_room_room_category_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2022_09_28_230136_create_room_room_attribute_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2022_09_28_232916_create_adjoining_room_main_room_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2022_10_05_152226_create_category_project_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2022_10_05_152542_create_genre_project_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2022_10_05_152641_create_project_sector_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2022_10_06_143130_create_filters_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2022_10_06_143858_create_filter_room_category_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2022_10_06_143910_create_filter_room_attribute_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2022_10_06_143921_create_filter_room_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2022_10_06_143932_create_area_filter_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2022_10_06_151902_create_event_type_filter_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2022_11_03_151559_create_notifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2022_11_04_144728_create_schedulings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2022_11_12_095744_create_user_notification_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2022_11_17_092805_create_model_changes_history_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2022_11_23_111840_create_global_notifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2022_12_01_141757_create_contracts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2022_12_02_093524_create_money_sources_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2022_12_02_143629_create_contract_modules_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2022_12_06_100430_create_money_source_tasks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2022_12_06_102416_money_source_task_user',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2022_12_12_101513_add_is_group_to_projects',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2022_12_12_101612_create_project_groups_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2022_12_12_122033_create_main_positions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2022_12_12_122039_create_sub_positions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2022_12_12_122046_create_columns_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2022_12_12_122054_create_subposition_rows_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2022_12_12_122111_create_subposition_row_column_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2023_01_03_121754_add_done_column',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2023_01_04_134505_create_cell_comments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2023_01_08_211900_create_cost_centers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2023_01_08_221621_create_money_source_project_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2023_01_10_131058_create_main_position_verifieds_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2023_01_10_131124_create_sub_position_verifieds_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2023_01_12_112713_contract_users',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2023_01_16_131040_create_cell_calculations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2023_01_24_110810_add_is_locked_to_column',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2023_01_24_113233_add_is_fixed_to_main_sub_positions',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2023_01_25_124932_create_row_comments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2023_01_26_095821_create_tables_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2023_01_27_081831_create_task_user_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2023_02_09_133807_create_contract_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2023_02_09_133816_create_company_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2023_02_09_133832_create_collecting_societies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2023_02_10_102233_create_project_file_user_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2023_02_13_112448_create_money_source_files_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2023_02_21_122859_create_currencies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2023_02_28_105026_money_source_users',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2023_02_28_201544_create_sum_comments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2023_02_28_211619_create_subposition_sum_details_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2023_03_01_232445_create_main_position_details_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2023_03_02_001127_create_budget_sum_details_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2023_03_02_111024_task_template_user',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2023_03_02_163733_create_project_headlines_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2023_03_02_164201_create_project_project_headlines_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2023_03_05_121130_create_project_states_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2023_03_05_123414_checklist_user',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2023_03_05_123738_add_state_to_project',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2023_03_06_094500_checklist_template_user',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2023_03_08_175507_create_sum_money_sources_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2023_03_20_171307_create_sub_events_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2023_03_27_173407_create_user_calendar_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2023_03_28_132454_create_series_events_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2023_04_19_143802_create_event_comments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2023_04_19_144453_event_acception_system',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2023_05_17_084910_create_freelancers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2023_05_19_111757_create_service_providers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (108,'2023_05_21_130403_create_service_provider_contacts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (109,'2023_05_23_111659_create_user_vacations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (110,'2023_05_30_140454_create_crafts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (111,'2023_05_30_141014_craft_users',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (112,'2023_05_31_113905_create_time_lines_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (113,'2023_05_31_114242_create_shifts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (114,'2023_06_01_103851_create_project_shift_relevant_event_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (115,'2023_06_01_105354_project_shift_contacts',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (116,'2023_06_02_090101_shift_user',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (117,'2023_06_08_170152_create_shift_filters_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (118,'2023_06_08_171937_create_room_shift_filter_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (119,'2023_06_08_172127_create_event_type_shift_filter_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (120,'2023_06_13_111619_shifts_service_providers',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (121,'2023_06_13_111626_shifts_freelancers',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (122,'2023_07_19_103021_create_shift_presets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (123,'2023_07_20_104318_create_preset_time_lines_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (124,'2023_07_20_104335_create_preset_shifts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (125,'2023_08_02_123358_create_freelancer_vacations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (126,'2023_08_28_143719_add_has_column_locked',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (127,'2023_10_20_111807_create_user_calendar_filters_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (128,'2023_10_25_095803_create_user_shift_calendar_filters_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (129,'2023_11_01_123650_add_date_values_to_user_filter',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (130,'2023_11_01_123740_add_date_values_to_user_shift_filter',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (131,'2023_11_13_153110_create_user_commented_budget_items_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (132,'2023_11_14_133926_add_commented_column_to_columns_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (133,'2023_11_14_160205_add_position_to_cell_calculation',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (134,'2023_11_27_151632_add_budget_deadline_to_project_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (135,'2023_12_01_135600_vacation_morph',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (136,'2023_12_01_135605_migrate_vacation_data',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (137,'2023_12_05_164330_create_users_assigned_crafts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (138,'2023_12_06_155259_create_freelancer_assigned_crafts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (139,'2023_12_06_155405_create_service_provider_assigned_crafts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (140,'2023_12_06_161018_add_can_work_shifts_column_to_freelancers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (141,'2023_12_06_161115_add_can_work_shifts_column_to_service_providers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (142,'2023_12_10_164051_create_money_source_user_pinned',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (143,'2023_12_14_110905_create_money_source_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (144,'2023_12_14_145837_create_money_source_category_mapping_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (145,'2023_12_15_140632_drop_room_pivot_room_attribute_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (146,'2023_12_18_155832_create_money_source_reminders_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (147,'2024_01_10_102543_add_tooltip_text_to_roles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (148,'2024_01_10_171430_create_availabilities_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (149,'2024_01_11_112207_create_availability_series_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (150,'2024_01_11_142855_create_vacation_series_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (151,'2024_01_15_125139_create_permission_presets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (152,'2024_01_17_124826_add_business_email_to_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (153,'2024_01_17_174819_add_pinned_by_user',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (154,'2024_01_18_094505_change_creator_id_foreign_key_of_contracts',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (155,'2024_01_19_190741_create_vacation_conflicts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (156,'2024_01_19_190750_create_availabilities_conflicts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (157,'2024_01_22_145914_create_shift_qualifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (158,'2024_01_23_110431_addcommitting_user_id',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (159,'2024_01_23_112349_create_user_shift_qualifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (160,'2024_01_23_112403_create_freelancer_shift_qualifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (161,'2024_01_23_112418_create_service_provider_shift_qualifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (162,'2024_01_23_144516_remove_can_master_from_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (163,'2024_01_23_144626_remove_can_master_from_freelancers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (164,'2024_01_23_144654_remove_can_master_from_service_providers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (165,'2024_01_24_121210_create_shifts_qualifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (166,'2024_01_24_165618_add_shift_qualification_id_to_shift_user_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (167,'2024_01_24_165639_drop_is_master_column_from_shift_user_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (168,'2024_01_25_111250_drop_number_employees_from_shift_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (169,'2024_01_25_111312_drop_number_masters_from_shift_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (170,'2024_01_26_125022_add_shift_qualification_id_to_shift_freelancer_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (171,'2024_01_26_125036_add_shift_qualification_id_to_shift_service_provider_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (172,'2024_01_26_125256_drop_is_master_column_from_shift_freelancer_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (173,'2024_01_26_125405_drop_is_master_column_from_shift_service_provider_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (174,'2024_01_31_105414_drop_shift_count_column_in_shift_user_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (175,'2024_01_31_105645_add_shift_count_column_in_shift_user_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (176,'2024_01_31_105809_drop_shift_count_column_in_shifts_freelancers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (177,'2024_01_31_105901_add_shift_count_column_in_shifts_freelancers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (178,'2024_01_31_105959_drop_shift_count_column_in_shifts_service_providers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (179,'2024_01_31_110013_add_shift_count_column_in_shifts_service_providers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (180,'2024_01_31_174631_create_preset_shift_shifts_qualifications',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (181,'2024_01_31_175401_drop_number_employees_from_preset_shifts',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (182,'2024_01_31_175426_drop_number_masters_from_preset_shifts',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (183,'2024_02_01_174221_add_fields_to_project',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (184,'2024_02_02_124649_rename_preset_timelines_to_shift_preset_timelines',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (185,'2024_02_02_140227_add_foreign_key_cascade_on_delete_to_shift_preset_timelines_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (186,'2024_02_02_140719_add_foreign_key_cascade_on_delete_to_preset_shifts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (187,'2024_02_02_152022_rename_time_lines_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (188,'2024_02_08_081845_create_sage_not_assigned_data_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (189,'2024_02_13_092930_create_sage_api_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (190,'2024_02_15_134411_add_language_to_user',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (191,'2024_02_15_134838_create_sage_assigned_data_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (192,'2024_02_20_104815_create_sage_assigned_data_comments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (193,'2024_02_26_111815_add_deleted_at_to_sage_not_assigned_data_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (194,'2024_03_04_132452_add_deleted_at_to_event_comments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (195,'2024_03_04_134011_add_deleted_at_to_timelines_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (196,'2024_03_04_134433_add_deleted_at_to_shifts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (197,'2024_03_04_140422_budget_soft_delete',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (198,'2024_03_04_140534_comments_soft_delete',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (199,'2024_03_04_141529_tasks_soft_delete',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (200,'2024_03_04_145643_add_deleted_at_to_shift_user_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (201,'2024_03_04_145649_add_deleted_at_to_shifts_freelancers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (202,'2024_03_04_145656_add_deleted_at_to_shifts_service_providers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (203,'2024_03_04_145957_add_deleted_at_to_shifts_qualifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (204,'2024_03_04_161740_checklist_soft_delete',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (205,'2024_03_04_164745_project_history_soft_delete',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (206,'2024_03_04_165031_project_shift_contacts_soft_delete',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (207,'2024_03_04_165657_main_positions_soft_delete',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (208,'2024_03_04_165716_sub_positions_soft_delete',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (209,'2024_03_04_165803_columns_soft_delete',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (210,'2024_03_04_165828_sub_position_rows_soft_delete',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (211,'2024_03_04_165846_column_sub_position_row_soft_delete',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (212,'2024_03_04_165905_cell_comments_soft_delete',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (213,'2024_03_04_165923_main_position_verifieds_soft_delete',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (214,'2024_03_04_170004_sub_position_verifieds_soft_delete',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (215,'2024_03_04_170057_contract_user_soft_delete',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (216,'2024_03_04_170130_cell_calculations_soft_delete',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (217,'2024_03_04_170155_row_comments_soft_delete',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (218,'2024_03_04_170219_sum_comments_soft_delete',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (219,'2024_03_04_170239_subposition_sum_details_soft_delete',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (220,'2024_03_04_170353_main_position_details_soft_delete',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (221,'2024_03_04_170416_budget_sum_details_soft_delete',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (222,'2024_03_04_170445_project_headlines_soft_delete',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (223,'2024_03_04_172714_contracts_soft_delete',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (224,'2024_03_04_172954_sum_money_sources_soft_delete',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (225,'2024_03_05_113659_create_budget_column_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (226,'2024_03_05_161025_project_shift_relevant_event_types_soft_delete',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (227,'2024_03_05_162814_money_source_project_soft_delete',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (228,'2024_03_06_142303_add_account_management_global_to_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (229,'2024_03_06_164125_create_budget_management_accounts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (230,'2024_03_06_164131_create_budget_management_cost_units_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (231,'2024_03_08_110920_change_event_types_hex',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (232,'2024_03_16_221427_role_and_permission_upgrade',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (233,'2024_03_18_151535_add_translation_keys_to_permissions',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (234,'2024_03_18_151742_add_translation_keys_to_roles',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (235,'2024_03_19_090805_add_order_to_rooms',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (236,'2024_03_20_142144_create_day_services_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (237,'2024_03_22_111128_add_start_end_date_to_shifts',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (238,'2024_03_25_172059_create_components_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (239,'2024_03_25_172740_create_project_tabs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (240,'2024_03_26_095010_create_component_in_tabs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (241,'2024_03_26_095014_create_project_component_values_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (242,'2024_04_03_084428_remove_registration_and_entrance_columns_from_projects_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (243,'2024_04_04_115748_create_project_tab_sidebar_tabs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (244,'2024_04_04_115851_create_sidebar_tab_components_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (245,'2024_04_05_135855_add_sidebar_opend_to_user',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (246,'2024_04_09_143039_add_tab_id_to_checklists',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (247,'2024_04_09_153324_add_tab_id_to_comments',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (248,'2024_04_09_155349_create_component_user_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (249,'2024_04_09_155356_create_component_department_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (250,'2024_04_10_155618_add_tab_id_to_project_files',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (251,'2024_04_11_130738_remove_project_description',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (252,'2024_04_11_132433_drop_project_headlines_and_project_project_headlines_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (253,'2024_04_11_133732_add_zoom_factor_to_user',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (254,'2024_04_16_110629_add_is_account_for_revenue_to_budget_management_accounts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (255,'2024_04_18_092631_drop_project_histories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (256,'2024_04_22_144319_remove_nullable_from_event_types_table_hex_code_column',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (257,'2024_04_24_183202_update_model_type_columns',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (258,'2024_04_29_145519_add_color_to_project_settings',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (259,'2024_05_03_133600_add_page_title',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (260,'2024_05_14_123613_add_color_to_crafts',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (261,'2024_05_14_133202_add_days_to_notify_craft_if_shift_is_not_full',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (262,'2024_05_14_151558_add_compact_mode-to_user',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (263,'2024_05_14_160801_create_project_roles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (264,'2024_05_15_094437_add_project_roles_to_project_user',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (265,'2024_05_15_151235_add_dates_to_timeline',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (266,'2024_05_15_155844_add_earliest_and_lastest_dates_to_event',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (267,'2024_05_16_091643_add_date_to_shift_preset_timeline',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (268,'2024_05_16_141955_add_show_crafts_to_user',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (269,'2024_05_20_214705_add_default_room_flag',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (270,'2024_05_21_142310_add_selected_stepper_to_user',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (271,'2024_05_21_161755_create_user_shift_calendar_abos_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (272,'2024_05_23_153525_create_user_calendar_abos_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (273,'2024_05_27_014010_default_event_type',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (274,'2024_05_27_110756_change_comment_text_lenght',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (275,'2024_05_28_113603_set_calendar_abo_id_to_null',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (276,'2024_05_30_153410_create_shift_time_presets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (277,'2024_06_05_145110_create_day_serviceables_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (278,'2024_06_06_091610_set_indexes',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (279,'2024_06_18_141450_project_create_settings',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (280,'2024_06_19_231210_create_crafts_inventory_columns_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (281,'2024_06_19_231218_create_inventory_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (282,'2024_06_19_231804_create_craft_inventory_groups_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (283,'2024_06_19_231819_create_craft_inventory_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (284,'2024_06_19_231833_create_craft_inventory_item_cells_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (285,'2024_06_25_115435_create_craft_inventory_item_events_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (286,'2024_06_25_183020_change_timeline_date_and_time_columns_not_nullable',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (287,'2024_06_26_191510_create_user_inventory_filters_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (288,'2024_07_04_092435_add_checklist_style_to_user',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (289,'2024_07_10_091204_add_relevant_for_inventory_in_event_type',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (290,'2024_07_15_113807_add_at_a_glance_to_user',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (291,'2024_07_21_235923_revert_foreign_keys_on_subpositions',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (292,'2024_08_06_160842_remove_project_id_form_checklist',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (293,'2024_08_08_121449_add_private_value_to_checklists',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (294,'2024_08_12_162237_add_creator_to_project',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (295,'2024_08_12_201359_add_invitation_email_to_general_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (296,'2024_08_14_092617_add_bulk_edit_enum_to_components',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (297,'2024_08_14_203810_add_sent_in_summary_notification_flag_to_notifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (298,'2024_08_19_111739_add_bulk_event_create_component_if_not_exists',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (299,'2024_08_19_190917_add_notification_enums_last_sent_to_user_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (300,'2024_08_19_202541_set_user_notification_frequency_settings_to_daily_instead_of_immediately',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (301,'2024_08_26_144000_module_settings',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (302,'2024_08_28_204239_add_description_and_time_period_project_id_and_use_project_time_period_columns_to_user_calendar_settings',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (303,'2024_09_01_155245_add_user_project_management_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (304,'2024_09_04_115226_add_bulk_sort_id_to_user',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (305,'2024_09_04_131042_reset_indexes_from_2024_06_06_091610',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (306,'2024_09_04_152512_recreate_foreign_keys',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (307,'2024_09_05_115228_create_user_project_management_settings_defaults',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (308,'2024_09_13_105500_add_event_name_to_user_calendar_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (309,'2024_09_13_114012_add_show_notification_indicator_column_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (310,'2024_09_13_121436_add_sent_deadline_notification_today_to_tasks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (311,'2024_09_13_162128_create_user_user_management_settings_defaults',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (312,'2024_09_19_132618_add_user_worker_calendar_filters_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (313,'2024_09_26_151017_add_high_contrast_to_user_calendar_settings',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (314,'2024_10_04_095516_add_is_freelancer_to_user',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (315,'2024_10_04_145523_add_universally_applicable_boolean_to_craft',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (316,'2024_10_06_225653_set_idx_on_mch',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (317,'2024_10_07_034738_add_fk_to_shift_users',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (318,'2024_10_07_151113_add_craft_craft_abbreviation_to_shift_users',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (319,'2024_10_09_135824_add_type_to_vacations',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (320,'2024_10_10_111826_create_individual_times_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (321,'2024_10_15_153825_create_shift_plan_comments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (322,'2024_10_16_073818_holidays',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (323,'2024_10_17_150257_add_enums_to_shift_plan_user_sort_by',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (324,'2024_10_18_121723_add_sort_id_for_shiftplan_component',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (325,'2024_10_18_151518_update_shiftplan_user_sort_by_new_column',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (326,'2024_10_21_064828_fill_subdivisions',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (327,'2024_10_21_100901_add_drawer_height_value_to_user',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (328,'2024_10_23_161924_craftable',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (329,'2024_10_25_150013_add_all_craft_user_to_new_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (330,'2024_10_28_141634_add_expand_days_to_user_calendar_settings',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (331,'2024_10_30_150946_add_position_to_crafts',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (332,'2024_11_06_154456_add_color_to_holiday',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (333,'2024_11_07_134053_create_holiday_settings',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (334,'2024_11_11_113708_add_yearly-boolean_to_holiday',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (335,'2024_11_13_155111_add_sort_by_column_in_inventory',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (336,'2024_11_14_143229_add_checklist_filter_for_checklist',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (337,'2024_11_08_145927_add_craft_managers_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (338,'2024_11_15_144339_add_deletable_to_inventory_column',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (339,'2024_11_17_031036_add_file_types_to_settings',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (340,'2024_11_18_105313_create_craft_inventory_group_folders_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (341,'2024_11_19_101959_add_folder_id_to_inventory_item',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (342,'2024_11_19_105251_make_group_id_nullable_inventory_item',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (343,'2024_11_20_113923_add_is_developer_to_users_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (344,'2024_11_20_171135_craft_users_inventory',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (345,'2024_11_20_171817_inventory_planned_by_all_to_crafts',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (346,'2024_11_21_102240_event_settings',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (347,'2024_11_21_102623_create_event_statuses_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (348,'2024_11_21_135218_add_use_event_status_color_to_user_calendar_settings',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (349,'2024_11_21_163820_add_columns_to_sage_assigned_data_and_sage_not_assigned_data_and_make_kreditor_optional',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (350,'2024_11_22_145217_add_artist_residencies_enum_to_components',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (351,'2024_11_22_164455_create_artist_residencies_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (352,'2024_11_22_193801_shift_settings',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (353,'2024_11_22_224418_add_type_to_service_provider',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (354,'2024_11_23_165534_add_show_qualifications_to_user',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (355,'2024_11_25_132000_add_show_artists_to_project_create_settings',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (356,'2024_11_25_150451_add_artists_to_project_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (357,'2024_11_25_155711_add_project_artists_to_user_calendar_settings',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (358,'2024_11_28_150410_add_new_fields_to_users',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (359,'2024_12_02_063640_add_filesize_to_settings',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (360,'2024_12_03_150833_add-contract_file_size_and_mine_type',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (361,'2024_12_06_222859_update_shift_timeline_preset',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (362,'2024_12_09_102926_add_shift_timeline_preset_name',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (363,'2024_12_09_112038_create_preset_timeline_times_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (364,'2024_12_10_110619_add_column_position_to_columns_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (365,'2024_12_10_113428_add_table_table_column_order',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (366,'2024_12_18_112615_add_and_remove_values_form_shift_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (367,'2025_01_11_201052_add_setting_options_for_shift_plan',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (368,'2025_01_12_203502_add_from_to_in_timelines',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (369,'2025_01_13_145631_create_project_management_builders_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (370,'2025_01_21_203011_add_daily_view_to_user',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (371,'2025_01_28_161607_change_weekly_working_hours_to_float',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (372,'2025_01_22_114217_add_event_properties_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (373,'2025_01_22_115059_add_event_properties_column_to_user_calendar_filters',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (374,'2025_01_23_110615_add_event_properties_to_filters_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (375,'2025_01_23_110651_add_event_event_property_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (376,'2025_01_24_101718_make_event_audience_and_is_loud_nullable',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (377,'2025_01_31_212908_event_property_sub_event',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (378,'2025_02_03_115519_create_project_print_layouts_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (379,'2025_02_03_212940_create_print_layout_components_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (380,'2025_02_07_134749_add_default_to_project_tabs',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (381,'2025_02_10_191314_add_hide_unoccupied_rooms',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (382,'2025_02_10_203811_add_default_to_event_statuses',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (383,'2025_02_18_114302_general_calendar_settings',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (384,'2025_02_18_172709_add_last_project_id_in_user',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (385,'2025_02_18_220048_add_entities_per_page_to_user',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (386,'2025_02_22_175103_add_column_size_for_event_plan',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (387,'2025_02_25_195817_add_color_to_project',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (388,'2025_02_25_202334_add_project_group_display',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (389,'2025_02_27_110453_update_project_budget_for_project_groups',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (390,'2025_03_03_112156_add_relevant_for_project_period',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (391,'2025_03_03_120925_add_new_component_types_for_project_groups',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (392,'2025_03_13_211758_add_note_to_component',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (393,'2025_03_14_133227_add_type_for_disclosure',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (394,'2025_03_14_133537_create_disclosure_components_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (395,'2016_06_01_000001_create_oauth_auth_codes_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (396,'2016_06_01_000002_create_oauth_access_tokens_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (397,'2016_06_01_000003_create_oauth_refresh_tokens_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (398,'2016_06_01_000004_create_oauth_clients_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (399,'2016_06_01_000005_create_oauth_personal_access_clients_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (400,'2025_03_15_211655_create_inventory_categories_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (401,'2025_03_15_213546_create_inventory_sub_categories_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (402,'2025_03_15_232013_create_inventory_articles_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (403,'2025_03_17_103010_create_inventory_detailed_quantity_articles_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (404,'2025_03_17_103153_create_inventory_article_properties_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (405,'2025_03_17_103421_create_inventory_property_values_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (406,'2025_03_17_105851_inventory_category_property_values',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (407,'2025_03_20_161506_create_inventory_article_images_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (408,'2025_03_25_090538_create_manufacturers_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (409,'2025_03_27_203615_public_key_for_chat',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (410,'2025_03_29_212150_create_chats_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (411,'2025_03_29_212341_create_chat_users_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (412,'2025_03_29_212347_create_chat_messages_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (413,'2025_03_29_212430_create_chat_message_reads_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (414,'2025_03_29_221959_add_use_chat_to_user',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (415,'2025_04_02_101031_add_more_to_inventory_article',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (416,'2025_04_02_103624_add_more_softdeletes_to_inventory',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (417,'2025_04_02_150312_add_is_planning_event_to_events',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (418,'2025_04_03_135007_event_type_user',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (419,'2025_04_03_135155_add_verification_mode_and_more',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (420,'2025_04_03_135251_create_event_verifications_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (421,'2025_04_10_151013_add_show_unplanned_events',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (422,'2025_04_11_000000_add_show_planned_events',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (423,'2025_04_14_095153_add_select_values_to_properties',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (424,'2025_04_14_152328_add_relevant_for_disposition',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (425,'2025_04_14_154331_create_accommodations_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (426,'2025_04_15_112356_create_contacts_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (427,'2025_04_16_101844_change_service_provider_id_to_accommodation_id',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (428,'2025_04_29_090908_create_inventory_article_statuses_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (429,'2025_04_29_091702_inventory_article_status_values',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (430,'2025_04_29_142148_add_status_to_detailed_article',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (431,'2025_05_02_155814_add_collective_booking',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (432,'2025_05_13_085435_create_internal_issues_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (433,'2025_05_13_085649_create_internal_issue_files_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (434,'2025_05_13_090504_create_special_items_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (435,'2025_05_13_091950_create_external_issues_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (436,'2025_05_13_092318_create_external_issue_files_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (437,'2025_05_13_093250_issuable_inventory_article_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (438,'2025_05_18_235056_create_api_access_tokens_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (439,'2025_05_19_152447_internal_issue_responsible_users',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (440,'2025_05_23_143126_add_special_items_done',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (441,'2025_05_26_202230_create_material_sets_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (442,'2025_05_26_202241_create_material_set_items_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (443,'2025_05_27_141043_create_user_inventory_article_plan_filters_table',9);
