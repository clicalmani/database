<?php

declare(strict_types=1);

namespace Database\Test\Feature\Fixtures;

/**
 * Tables supplémentaires pour tester les relations avancées.
 */
final class SchemaAdvanced
{
    public static function createAll(\PDO $pdo): void
    {
        // Suppliers d'abord (référencé par accounts)
        $pdo->exec('CREATE TABLE IF NOT EXISTS `suppliers` (
            `id`   INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255)    NOT NULL
        )');

        // Mechanics (référencé par cars)
        $pdo->exec('CREATE TABLE IF NOT EXISTS `mechanics` (
            `id`   INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255)    NOT NULL
        )');

        // HasOne : users → profiles
        $pdo->exec('CREATE TABLE IF NOT EXISTS `profiles` (
            `id`       INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `user_id`  INT(10) UNSIGNED NOT NULL,
            `bio`      TEXT,
            `avatar`   VARCHAR(255),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )');

        // MorphOne/MorphMany : images + comments polymorphiques
        $pdo->exec('CREATE TABLE IF NOT EXISTS `images` (
            `id`             INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `imageable_id`   INT(10) UNSIGNED NOT NULL,
            `imageable_type` VARCHAR(255)    NOT NULL,
            `url`            VARCHAR(255)    NOT NULL
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS `comments` (
            `id`              INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `commentable_id`  INT(10) UNSIGNED NOT NULL,
            `commentable_type` VARCHAR(255)   NOT NULL,
            `body`            TEXT    NOT NULL,
            `author_id`       INT(10) UNSIGNED,
            FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
        )');

        // MorphToMany : tags polymorphiques
        $pdo->exec('CREATE TABLE IF NOT EXISTS `tags` (
            `id`   INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255)    NOT NULL UNIQUE
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS `taggables` (
            `tag_id`      INT(10) UNSIGNED NOT NULL,
            `taggable_id` INT(10) UNSIGNED NOT NULL,
            `taggable_type` VARCHAR(255)  NOT NULL,
            PRIMARY KEY (tag_id, taggable_id, taggable_type)
        )');

        // HasOneThrough : suppliers → users → accounts
        $pdo->exec('CREATE TABLE IF NOT EXISTS suppliers (
            `id`   INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255)    NOT NULL
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS `accounts` (
            `id`          INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `supplier_id` INT(10) UNSIGNED NOT NULL,
            `user_id`     INT(10) UNSIGNED NOT NULL,
            `account_number` VARCHAR(255) NOT NULL,
            FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )');

        // Mécaniciens → voitures
        $pdo->exec('CREATE TABLE IF NOT EXISTS `mechanics` (
            `id`   INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255)    NOT NULL
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS `cars` (
            `id`          INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `mechanic_id` INT(10) UNSIGNED NOT NULL,
            `brand`       VARCHAR(255)    NOT NULL,
            FOREIGN KEY (mechanic_id) REFERENCES mechanics(id) ON DELETE CASCADE
        )');
    }

    public static function dropAll(\PDO $pdo): void
    {
        $pdo->query('SET FOREIGN_KEY_CHECKS = 0');

        foreach ([
            'taggables', 'tags',
            'comments',
            'images',
            'profiles',
            'cars',
            'mechanics',
            'accounts',
            'suppliers',
        ] as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `$table`");
        }

        $pdo->query('SET FOREIGN_KEY_CHECKS = 1');
    }
}