```
CREATE TABLE `datahub_data` (           
  `id` varchar(100) NOT NULL,           
  `name` varchar(100) NOT NULL,         
  `value` text NOT NULL,                
  PRIMARY KEY (`id`,`name`)             
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `reports` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `inventory_number` varchar(100) NOT NULL,
  `last_modified` TIMESTAMP NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

CREATE TABLE `report_data` (           
  `id` INT UNSIGNED NOT NULL,           
  `name` varchar(100) NOT NULL,         
  `value` text NOT NULL,                
  PRIMARY KEY (`id`,`name`)             
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
