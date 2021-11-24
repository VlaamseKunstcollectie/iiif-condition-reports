CREATE TABLE IF NOT EXISTS `inventory_numbers` (
  `inventory_number` VARCHAR(100) NOT NULL,
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`inventory_number`),
  KEY(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `datahub_data` (
  `id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `value` TEXT NOT NULL,
  PRIMARY KEY (`id`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `reports` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `base_id` INT UNSIGNED DEFAULT NULL,
  `inventory_id` INT UNSIGNED NOT NULL,
  `timestamp` TIMESTAMP NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `report_history` (
  `id` INT UNSIGNED NOT NULL,
  `previous_id` INT UNSIGNED NOT NULL,
  `sort_order` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`, `previous_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `report_data` (
  `id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `value` TEXT NOT NULL,
  PRIMARY KEY (`id`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `images` (
  `hash` CHAR(64) NOT NULL,
  `image` TEXT NOT NULL,
  `thumbnail` TEXT NOT NULL,
  PRIMARY KEY(`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `annotations` (
  `image` CHAR(64) NOT NULL,
  `report_id` INT UNSIGNED NOT NULL,
  `annotation_id` VARCHAR(255) NOT NULL,
  `annotation` LONGTEXT NOT NULL,
  PRIMARY KEY (`image`, `report_id`, `annotation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `deleted_annotations` (
  `image` CHAR(64) NOT NULL,
  `report_id` INT UNSIGNED NOT NULL,
  `annotation_id` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`image`, `report_id`, `annotation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `organisations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `alias` VARCHAR(255) DEFAULT NULL,
  `name` VARCHAR(255) NOT NULL,
  `role` VARCHAR(255) DEFAULT NULL,
  `logo` VARCHAR(255) DEFAULT NULL,
  `vat` VARCHAR(255) DEFAULT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  `postal` VARCHAR(255) DEFAULT NULL,
  `city` VARCHAR(255) DEFAULT NULL,
  `country` VARCHAR(255) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `website` VARCHAR(255) DEFAULT NULL,
  `phone` VARCHAR(255) DEFAULT NULL,
  `mobile` VARCHAR(255) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `representatives` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `organisation` INT UNSIGNED NOT NULL,
  `alias` VARCHAR(255) DEFAULT NULL,
  `name` VARCHAR(255) NOT NULL,
  `role` VARCHAR(255) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `phone` VARCHAR(255) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `iiif_manifests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `manifest_id` VARCHAR(255) NOT NULL,
  `data` LONGTEXT NOT NULL,
  PRIMARY KEY (`id`)
);

-- TODO: proper (and rights-free) images of frame and backside!
INSERT INTO images VALUES('350e868ce231caec24997fd00892a19495c3c822bdfc44f06e4b3574354344ec', '/annotation_images/1637361020124.jpg', '/annotation_images/1637361020124_thm.jpg');
INSERT INTO images VALUES('3c6dfddfdaf6d945069502fa4329f506ba9e222ab8c540bb67c80712387e536d', '/annotation_images/1637361403233.png', '/annotation_images/1637361403233_thm.jpg');
