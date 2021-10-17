CREATE DATABASE `ns_unittests`
;
CREATE TABLE `ns_unittests`.`Employees` (
  `id` bigint(20) NOT NULL, 
  `name` varchar(191), 
  `gender` enum('M', 'F'), 
  `salary` float(7, 2), 
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
  `name` varchar(32), 
  `creationDateTime` timestamp DEFAULT CURRENT_TIMESTAMP, 
  `priority` bigint, 
  `category` bigint, 
  `creator` bigint DEFAULT NULL, 
  `assignedTo` bigint DEFAULT NULL, 
  CONSTRAINT `pk_tid` PRIMARY KEY (`id`), 
  CONSTRAINT `fk_creator` FOREIGN KEY (`creator`) REFERENCES `Employees` (`id`) ON UPDATE CASCADE ON DELETE CASCADE, 
  FOREIGN KEY (`assignedTo`) REFERENCES `Employees` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
)
;
CREATE TABLE `ns_unittests`.`types` (
  `base` longtext, 
  `binary` varbinary(255) DEFAULT 'abc', 
  `boolean` boolean DEFAULT TRUE, 
  `int` bigint(20) AUTO_INCREMENT, 
  `large_int` bigint(12) DEFAULT 123456789012, 
  `small_int` smallint(3) UNSIGNED, 
  `float` decimal(30, 2) DEFAULT 1.23, 
  `timestamp` datetime DEFAULT '2010-11-12 13:14:15', 
  `timestamp_tz` timestamp DEFAULT '2010-11-12 13:14:15', 
  CONSTRAINT `pk_types` PRIMARY KEY (`int`)
)
;
