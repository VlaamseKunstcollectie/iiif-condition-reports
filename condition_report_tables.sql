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

CREATE TABLE IF NOT EXISTS user (
    `id` INT AUTO_INCREMENT NOT NULL,
    `email` VARCHAR(180) NOT NULL,
    `full_name` VARCHAR(255) NOT NULL,
    `roles` JSON NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    UNIQUE INDEX UNIQ_8D93D649E7927C74 (email),
    PRIMARY KEY(id)
);

CREATE TABLE IF NOT EXISTS reset_password_request (
    `id` INT AUTO_INCREMENT NOT NULL,
    `user_id` INT NOT NULL,
    `selector` VARCHAR(20) NOT NULL,
    `hashed_token` VARCHAR(100) NOT NULL,
    `requested_at` DATETIME NOT NULL,
    `expires_at` DATETIME NOT NULL,
    INDEX IDX_7CE748AA76ED395 (`user_id`),
    PRIMARY KEY(`id`)
);

INSERT INTO images VALUES('1a05c8366de4f7a23edffd0c72bc76bb89646799a6a85b4d8fe8f1fa142262fd', '/annotation_images/frame.svg', '/annotation_images/frame_150px.svg') ON DUPLICATE KEY UPDATE image=VALUES(image), thumbnail=VALUES(thumbnail);
INSERT INTO images VALUES('227518a708732010213d949b38822c15ae14ee1c956d6e9d3b1b9d9a1c5d4954', '/annotation_images/frame_back.svg', '/annotation_images.frame_back_150px.svg') ON DUPLICATE KEY UPDATE image=VALUES(image), thumbnail=VALUES(thumbnail);
