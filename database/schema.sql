-- HAVOC AGENCY – Datenbankschema (MariaDB ≥ 10.5 / MySQL ≥ 8)
-- Idempotent: kann beliebig oft ausgeführt werden (php bin/migrate.php oder Import in phpMyAdmin).
-- Datumsfelder 'date' bleiben Freitext (z.B. "24.05.2026"), sortiert wird in PHP.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `artists` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`          VARCHAR(255)  NOT NULL,
    `genre`         VARCHAR(255)  NOT NULL DEFAULT '',
    `bio`           TEXT          NULL,
    `bio_en`        TEXT          NULL,
    `image_url`     VARCHAR(1024) NOT NULL DEFAULT '',
    `spotify_url`   VARCHAR(1024) NOT NULL DEFAULT '',
    `youtube_url`   VARCHAR(1024) NOT NULL DEFAULT '',
    `website_url`   VARCHAR(1024) NOT NULL DEFAULT '',
    `instagram_url` VARCHAR(1024) NOT NULL DEFAULT '',
    `tiktok_url`    VARCHAR(1024) NOT NULL DEFAULT '',
    `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME      NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tour_dates` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `artist_id`  INT UNSIGNED NOT NULL,
    `date`       VARCHAR(64)   NOT NULL DEFAULT '',
    `city`       VARCHAR(255)  NOT NULL DEFAULT '',
    `venue`      VARCHAR(255)  NOT NULL DEFAULT '',
    `link`       VARCHAR(1024) NOT NULL DEFAULT '',
    `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME      NULL,
    PRIMARY KEY (`id`),
    KEY `tour_dates_artist_id` (`artist_id`),
    CONSTRAINT `tour_dates_artist_fk` FOREIGN KEY (`artist_id`) REFERENCES `artists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gallery` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `artist_id`  INT UNSIGNED NOT NULL,
    `image_url`  VARCHAR(1024) NOT NULL,
    `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME      NULL,
    PRIMARY KEY (`id`),
    KEY `gallery_artist_id` (`artist_id`),
    CONSTRAINT `gallery_artist_fk` FOREIGN KEY (`artist_id`) REFERENCES `artists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `news` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`      VARCHAR(255)  NOT NULL,
    `text`       TEXT          NULL,
    `image_url`  VARCHAR(1024) NOT NULL DEFAULT '',
    `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME      NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `team` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(255)  NOT NULL,
    `phone`      VARCHAR(64)   NOT NULL DEFAULT '',
    `email`      VARCHAR(255)  NOT NULL DEFAULT '',
    `hobbies`    TEXT          NULL,
    `bio`        TEXT          NULL,
    `image_url`  VARCHAR(1024) NOT NULL DEFAULT '',
    `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME      NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `agency_shows` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`      VARCHAR(255)  NOT NULL,
    `date`       VARCHAR(64)   NOT NULL DEFAULT '',
    `city`       VARCHAR(255)  NOT NULL DEFAULT '',
    `venue`      VARCHAR(255)  NOT NULL DEFAULT '',
    `ticket_url` VARCHAR(1024) NOT NULL DEFAULT '',
    `info_text`  TEXT          NULL,
    `image_url`  VARCHAR(1024) NOT NULL DEFAULT '',
    `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME      NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `messages` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(255) NOT NULL,
    `email`      VARCHAR(255) NOT NULL,
    `message`    TEXT         NOT NULL,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME     NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
