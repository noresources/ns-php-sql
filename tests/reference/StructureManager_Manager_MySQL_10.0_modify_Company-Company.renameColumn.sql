CREATE TABLE `ns_unittests`.`Employees_backup` (
  `id` bigint NOT NULL, 
  `name` varchar(191), 
  `gender` enum('M', 'F') NULL, 
  `salary` float(7, 2) NULL
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
CREATE TABLE `ns_unittests`.`Hierarchy_backup` (
  `managerId` bigint NOT NULL, `manageeId` bigint NOT NULL
)
;
INSERT INTO `ns_unittests`.`Hierarchy_backup` (`managerId`, `manageeId`) 
SELECT 
  `ns_unittests`.`Hierarchy`.`managerId`, 
  `ns_unittests`.`Hierarchy`.`manageeId` 
FROM 
  `ns_unittests`.`Hierarchy`
;
CREATE TABLE `ns_unittests`.`Tasks_backup` (
  `id` bigint NOT NULL, 
  `name` varchar(32) NULL, 
  `creationDateTime` datetime NULL DEFAULT CURRENT_TIMESTAMP, 
  `priority` bigint NULL, 
  `category` bigint NULL, 
  `creator` bigint NULL, 
  `assignedTo` bigint NULL
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
DROP 
  INDEX `index_employees_name` ON `ns_unittests`.`Employees`
;
DROP 
  TABLE `ns_unittests`.`Hierarchy`
;
DROP 
  TABLE `ns_unittests`.`Tasks`
;
DROP 
  TABLE `ns_unittests`.`Employees`
;
CREATE TABLE `ns_unittests`.`Employees` (
  `id` bigint(20) NOT NULL, 
  `fullName` varchar(191), 
  `gender` enum('M', 'F') NULL, 
  `salary` float(7, 2) NULL, 
  CONSTRAINT `pk_id` PRIMARY KEY (`id`)
)
;
INSERT INTO `ns_unittests`.`Employees` (`id`, `gender`, `salary`) 
SELECT 
  `ns_unittests`.`Employees_backup`.`id`, 
  `ns_unittests`.`Employees_backup`.`gender`, 
  `ns_unittests`.`Employees_backup`.`salary` 
FROM 
  `ns_unittests`.`Employees_backup`
;
DROP 
  TABLE `ns_unittests`.`Employees_backup`
;
CREATE TABLE `ns_unittests`.`Hierarchy` (
  `managerId` bigint(20) NOT NULL, 
  `manageeId` bigint(20) NOT NULL, 
  PRIMARY KEY (`managerId`, `manageeId`), 
  CONSTRAINT `hierarchy_managerId_foreignkey` FOREIGN KEY (`managerId`) REFERENCES `Employees` (`id`) ON UPDATE CASCADE ON DELETE CASCADE, 
  FOREIGN KEY (`manageeId`) REFERENCES `Employees` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
)
;
INSERT INTO `ns_unittests`.`Hierarchy` (`managerId`, `manageeId`) 
SELECT 
  `ns_unittests`.`Hierarchy_backup`.`managerId`, 
  `ns_unittests`.`Hierarchy_backup`.`manageeId` 
FROM 
  `ns_unittests`.`Hierarchy_backup`
;
DROP 
  TABLE `ns_unittests`.`Hierarchy_backup`
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
CREATE INDEX `index_employees_name` ON `ns_unittests`.`Employees` (`fullName`)
;
