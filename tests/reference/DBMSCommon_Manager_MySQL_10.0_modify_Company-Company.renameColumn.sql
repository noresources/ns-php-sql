CREATE TABLE `ns_unittests`.`Employees_backup` (
  `id` bigint NOT NULL, 
  `name` varchar(191), 
  `gender` bigint, 
  `salary` decimal
)
;
INSERT INTO `ns_unittests`.`Employees_backup` (`id`, `name`, `gender`, `salary`) 
SELECT 
  `ns_unittests`.`Employees`.`id`, 
  `ns_unittests`.`Employees`.`name`, 
  `ns_unittests`.`Employees`.`gender`, 
  `ns_unittests`.`Employees`.`salary` 
FROM 
  `ns_unittests`.`Employees`
;
CREATE TABLE `ns_unittests`.`Tasks_backup` (
  `id` bigint NOT NULL, 
  `name` longtext, 
  `creationDateTime` timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL, 
  `priority` bigint, 
  `category` bigint, 
  `creator` bigint(20), 
  `assignedTo` bigint
)
;
INSERT INTO `ns_unittests`.`Tasks_backup` (
  `id`, `name`, `creationDateTime`, 
  `priority`, `category`, `creator`, 
  `assignedTo`
) 
SELECT 
  `ns_unittests`.`Tasks`.`id`, 
  `ns_unittests`.`Tasks`.`name`, 
  `ns_unittests`.`Tasks`.`creationDateTime`, 
  `ns_unittests`.`Tasks`.`priority`, 
  `ns_unittests`.`Tasks`.`category`, 
  `ns_unittests`.`Tasks`.`creator`, 
  `ns_unittests`.`Tasks`.`assignedTo` 
FROM 
  `ns_unittests`.`Tasks`
;
CREATE TABLE `ns_unittests`.`types_backup` (
  `base` longtext, `binary` longblob DEFAULT 'abc', 
  `boolean` bigint DEFAULT 1, `int` bigint NOT NULL, 
  `large_int` bigint DEFAULT 123456789012, 
  `small_int` bigint, `float` decimal DEFAULT 1.23, 
  `timestamp` datetime DEFAULT '2010-11-12 13:14:15', 
  `timestamp_tz` timestamp DEFAULT '2010-11-12 13:14:15' NOT NULL
)
;
INSERT INTO `ns_unittests`.`types_backup` (
  `base`, `binary`, `boolean`, `int`, 
  `large_int`, `small_int`, `float`, 
  `timestamp`, `timestamp_tz`
) 
SELECT 
  `ns_unittests`.`types`.`base`, 
  `ns_unittests`.`types`.`binary`, 
  `ns_unittests`.`types`.`boolean`, 
  `ns_unittests`.`types`.`int`, 
  `ns_unittests`.`types`.`large_int`, 
  `ns_unittests`.`types`.`small_int`, 
  `ns_unittests`.`types`.`float`, 
  `ns_unittests`.`types`.`timestamp`, 
  `ns_unittests`.`types`.`timestamp_tz` 
FROM 
  `ns_unittests`.`types`
;
DROP 
  INDEX `fk_creator` ON `ns_unittests`.`Tasks`
;
DROP 
  INDEX `index_employees_name` ON `ns_unittests`.`Employees`
;
DROP 
  TABLE `ns_unittests`.`Tasks`
;
DROP 
  TABLE `ns_unittests`.`Employees`
;
DROP 
  TABLE `ns_unittests`.`types`
;
CREATE TABLE `ns_unittests`.`Employees` (
  `id` bigint(20) NOT NULL, 
  `fullName` varchar(191), 
  `gender` enum('M', 'F'), 
  `salary` float(7, 2), 
  CONSTRAINT `pk_id` PRIMARY KEY (`id`)
)
;
INSERT INTO `ns_unittests`.`Employees` (
  `fullName`, `id`, `gender`, `salary`
) 
SELECT 
  `ns_unittests`.`Employees_backup`.`name`, 
  `ns_unittests`.`Employees_backup`.`id`, 
  `ns_unittests`.`Employees_backup`.`gender`, 
  `ns_unittests`.`Employees_backup`.`salary` 
FROM 
  `ns_unittests`.`Employees_backup`
;
DROP 
  TABLE `ns_unittests`.`Employees_backup`
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
INSERT INTO `ns_unittests`.`Tasks` (
  `id`, `name`, `creationDateTime`, 
  `priority`, `category`, `creator`, 
  `assignedTo`
) 
SELECT 
  `ns_unittests`.`Tasks_backup`.`id`, 
  `ns_unittests`.`Tasks_backup`.`name`, 
  `ns_unittests`.`Tasks_backup`.`creationDateTime`, 
  `ns_unittests`.`Tasks_backup`.`priority`, 
  `ns_unittests`.`Tasks_backup`.`category`, 
  `ns_unittests`.`Tasks_backup`.`creator`, 
  `ns_unittests`.`Tasks_backup`.`assignedTo` 
FROM 
  `ns_unittests`.`Tasks_backup`
;
DROP 
  TABLE `ns_unittests`.`Tasks_backup`
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
INSERT INTO `ns_unittests`.`types` (
  `base`, `binary`, `boolean`, `int`, 
  `large_int`, `small_int`, `float`, 
  `timestamp`, `timestamp_tz`
) 
SELECT 
  `ns_unittests`.`types_backup`.`base`, 
  `ns_unittests`.`types_backup`.`binary`, 
  `ns_unittests`.`types_backup`.`boolean`, 
  `ns_unittests`.`types_backup`.`int`, 
  `ns_unittests`.`types_backup`.`large_int`, 
  `ns_unittests`.`types_backup`.`small_int`, 
  `ns_unittests`.`types_backup`.`float`, 
  `ns_unittests`.`types_backup`.`timestamp`, 
  `ns_unittests`.`types_backup`.`timestamp_tz` 
FROM 
  `ns_unittests`.`types_backup`
;
DROP 
  TABLE `ns_unittests`.`types_backup`
;
CREATE INDEX `index_employees_name` ON `ns_unittests`.`Employees` (`fullName`)
;
