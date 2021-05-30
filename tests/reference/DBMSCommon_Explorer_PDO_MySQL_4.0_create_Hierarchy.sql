CREATE 
OR REPLACE TABLE `ns_unittests`.`Hierarchy` (
  `managerId` bigint(20) NOT NULL, 
  `manageeId` bigint(20) NOT NULL, 
  PRIMARY KEY (`managerId`, `manageeId`), 
  CONSTRAINT `hierarchy_managerId_foreignkey` FOREIGN KEY (`managerId`) REFERENCES `Employees` (`id`) ON UPDATE CASCADE ON DELETE CASCADE, 
  FOREIGN KEY (`manageeId`) REFERENCES `Employees` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
)