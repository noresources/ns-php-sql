CREATE TABLE `ns_unittests`.`types` (
  `base` varchar(191), 
  `binary` varbinary(255) DEFAULT 'abc', 
  `boolean` boolean DEFAULT TRUE, 
  `int` bigint(20) DEFAULT 3, 
  `large_int` bigint(12), 
  `small_int` tinyint(3), 
  `float` decimal DEFAULT 1.23, 
  `timestamp` datetime DEFAULT '2010-11-12 13:14:15', 
  `timestamp_tz` datetime DEFAULT '2010-11-12 13:14:15', 
  CONSTRAINT `pk_types` PRIMARY KEY (`base`, `int`)
)