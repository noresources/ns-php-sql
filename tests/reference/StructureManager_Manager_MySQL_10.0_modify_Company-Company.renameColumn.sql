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
DROP 
  INDEX `index_employees_name` ON `ns_unittests`.`Employees`
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
CREATE INDEX `index_employees_name` ON `ns_unittests`.`Employees` (`fullName`)
;
