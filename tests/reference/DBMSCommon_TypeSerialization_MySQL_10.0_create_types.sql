CREATE 
OR REPLACE TABLE `ns_unittests`.`types` (
  `base` longtext NULL, 
  `binary` varbinary(255) NULL DEFAULT 'abc', 
  `boolean` boolean NULL DEFAULT TRUE, 
  `int` bigint(20) AUTO_INCREMENT, 
  `large_int` bigint(12) NULL DEFAULT 123456789012, 
  `small_int` smallint(3) UNSIGNED NULL, 
  `float` decimal(30, 2) NULL DEFAULT 1.23, 
  `timestamp` datetime NULL DEFAULT '2010-11-12 13:14:15', 
  `timestamp_tz` timestamp NULL DEFAULT '2010-11-12 13:14:15', 
  CONSTRAINT `pk_types` PRIMARY KEY (`int`)
)