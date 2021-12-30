CREATE 
OR REPLACE TABLE `ns_unittests`.`Employees` (
  `id` bigint(20) NOT NULL, 
  `name` varchar(191), 
<<<<<<< HEAD
  `gender` enum('M', 'F'), 
  `salary` float(7, 2), 
=======
  `gender` enum('M', 'F') NULL, 
  `salary` float(7, 2) NULL, 
>>>>>>> e7f9091a... UnittestConnectionManagerTrait
  CONSTRAINT `pk_id` PRIMARY KEY (`id`)
)