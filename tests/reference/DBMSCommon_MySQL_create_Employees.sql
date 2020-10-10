CREATE 
OR REPLACE TABLE `ns_unittests`.`Employees` (
  `id` bigint(20) NOT NULL, 
  `name` longtext, 
  `gender` enum('M', 'F'), 
  `salary` float(7, 2), 
  CONSTRAINT `pk_id` PRIMARY KEY (`id`)
)