<?php

declare(strict_types=1);

namespace Database\Test\Feature\Fixtures;

use Clicalmani\Core\Support\Facades\DB;

/**
 * Crée les tables SQLite pour les tests Feature.
 * Utilise du SQL brut — pas de dépendance à Elegant/Entity.
 */
final class Schema
{
    public static function createAll(\PDO $pdo): void
    {
        // HasManyThrough : countries → users → posts
        $pdo->exec('CREATE TABLE IF NOT EXISTS `countries` (
            `id`   INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255)    NOT NULL
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS `users` (
            `id`         INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `country_id` INT(10) UNSIGNED NULL,
            `name`       VARCHAR(255) NOT NULL,
            `email`      VARCHAR(255) NULL UNIQUE,
            `age`        INT DEFAULT 0,
            `active`     INT DEFAULT 1,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE SET NULL
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS `posts` (
            `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `user_id`    INT UNSIGNED NOT NULL,
            `title`      VARCHAR(100)    NOT NULL,
            `body`       TEXT,
            `published`  INT UNSIGNED DEFAULT 0,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS `roles` (
            `id`   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS `role_user` (
            `user_id`     INT UNSIGNED NOT NULL,
            `role_id`     INT UNSIGNED NOT NULL,
            `assigned_at` DATETIME NULL,
            PRIMARY KEY (user_id, role_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS `soft_users` (
            `id`         INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `name`       VARCHAR(255)    NOT NULL,
            `email`      VARCHAR(255) NULL UNIQUE,
            `age`        INT UNSIGNED DEFAULT 0,
            `active`     INT UNSIGNED DEFAULT 1,
            `deleted_at` DATETIME NULL
        )');
    }

    public static function dropAll(\PDO $pdo): void
    {
        $pdo->query('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['role_user', 'roles', 'posts', 'users', 'soft_users'] as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `$table`");
        }
    }

    /**
     * Seed basique : 5 users, 3 posts par user, 2 roles.
     */
    public static function seed(\PDO $pdo): void
    {
        // Countries
        $pdo->exec("INSERT INTO countries (name) VALUES ('France'), ('USA'), ('Japan')");

        // Users
        $stmt = $pdo->prepare('INSERT INTO users (name, email, age, active) VALUES (?, ?, ?, ?)');
        for ($i = 1; $i <= 5; $i++) {
            $stmt->execute(["User $i", "user$i@example.com", 20 + $i, $i % 2]);
        }

        // Assigner un pays à chaque user
        $pdo->exec("UPDATE users SET country_id = 1 WHERE id IN (1, 2)");
        $pdo->exec("UPDATE users SET country_id = 2 WHERE id IN (3, 4)");
        // User 5 : country_id NULL

        // Posts
        $stmt = $pdo->prepare('INSERT INTO posts (user_id, title, body, published) VALUES (?, ?, ?, ?)');
        foreach (range(1, 4) as $userId) {
            foreach (range(1, 3) as $n) {
                $stmt->execute([$userId, "Post $n by User $userId", "Body...", $n % 2]);
            }
        }
        // User 5 : n'a pas d'article

        // Roles
        $pdo->exec("INSERT INTO roles (name) VALUES ('admin'), ('editor')");

        // Role_User
        $pdo->exec("INSERT INTO role_user (user_id, role_id) VALUES (1, 1), (1, 2), (2, 2)");

        $stmt = $pdo->prepare('INSERT INTO soft_users (`name`, `email`, `age`, `active`) VALUES (?, ?, ?, ?)');
        for ($i = 1; $i <= 5; $i++) {
            $stmt->execute(["SoftUser $i", "soft$i@example.com", 20 + $i, 1]);
        }
    }
}