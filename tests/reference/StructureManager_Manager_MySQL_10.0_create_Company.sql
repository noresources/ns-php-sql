CREATE DATABASE `ns_unittests`
;
CREATE TABLE `ns_unittests`.`Employees` (
  `id` bigint(20) NOT NULL, 
  `name` varchar(191), 
  `gender` enum('M', 'F') NULL, 
  `salary` float(7, 2) NULL, 
  CONSTRAINT `pk_id` PRIMARY KEY (`id`)
)
;
CREATE INDEX `index_employees_name` ON `ns_unittests`.`Employees` (`name`)
;
CREATE TABLE `ns_unittests`.`Hierarchy` (
  `managerId` bigint(20) NOT NULL, 
  `manageeId` bigint(20) NOT NULL, 
  PRIMARY KEY (`managerId`, `manageeId`), 
  CONSTRAINT `hierarchy_managerId_foreignkey` FOREIGN KEY (`managerId`) REFERENCES `Employees` (`id`) ON UPDATE CASCADE ON DELETE CASCADE, 
  FOREIGN KEY (`manageeId`) REFERENCES `Employees` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
)
;
CREATE TABLE `ns_unittests`.`Tasks` (
  `id` bigint(20) AUTO_INCREMENT, 
  `name` varchar(32) NULL, 
  `creationDateTime` datetime NULL DEFAULT CURRENT_TIMESTAMP, 
  `priority` bigint NULL, 
  `category` bigint NULL, 
  `creator` bigint(20) NULL DEFAULT NULL, 
  `assignedTo` bigint(20) NULL DEFAULT NULL, 
  CONSTRAINT `pk_tid` PRIMARY KEY (`id`), 
  CONSTRAINT `fk_creator` FOREIGN KEY (`creator`) REFERENCES `Employees` (`id`) ON UPDATE CASCADE ON DELETE CASCADE, 
  FOREIGN KEY (`assignedTo`) REFERENCES `Employees` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
)
;
CREATE TABLE `ns_unittests`.`types` (
  `base` longtext NULL, 
  `binary` varbinary(255) NULL DEFAULT 'abc', 
  `boolean` boolean NULL DEFAULT TRUE, 
  `int` bigint(20) AUTO_INCREMENT, 
  `large_int` bigint(12) NULL DEFAULT 123456789012, 
  `small_int` smallint(3) UNSIGNED NULL, 
  `float` double NULL DEFAULT 1.23456, 
  `fixed_precision` double(18, 2) NULL DEFAULT 1.23, 
  `timestamp` datetime NULL DEFAULT '2010-11-12 12:14:15', 
  `timestamp_tz` datetime NULL DEFAULT '2010-11-12 12:14:15', 
  CONSTRAINT `pk_types` PRIMARY KEY (`int`)
)
;
