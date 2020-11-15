CREATE 
OR REPLACE TABLE `ns_unittests`.`types` (
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