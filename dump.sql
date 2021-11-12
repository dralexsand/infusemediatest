CREATE SCHEMA `siteinfo_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE `siteinfo_db`.`visitors` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `ip_address` VARCHAR(30),
  `user_agent` VARCHAR(256),
  `view_date` TIMESTAMP,
  `page_url` VARCHAR(2048),
  `views_count` INT,
  PRIMARY KEY (`id`));