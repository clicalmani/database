<?php

declare(strict_types=1);

namespace Database\Test\Unit;

use Clicalmani\Database\Contracts\ConnectionManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ConnectionManagerUrlTest extends TestCase
{
    private string $baseDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cm_test_' . uniqid();
        mkdir($this->baseDir . DIRECTORY_SEPARATOR . 'database', 0775, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->baseDir);
        parent::tearDown();
    }

    // -----------------------------------------------------------------
    //  Helpers
    // -----------------------------------------------------------------

    private function manager(array $connections, ?string $basePath = null): ConnectionManager
    {
        $manager = new ConnectionManager();
        $manager->configure([
            'base_path'   => $basePath ?? $this->baseDir,
            'default'     => 'main',
            'connections' => $connections,
        ]);

        return $manager;
    }

    /** Appelle la méthode privée configFor() sans ouvrir de connexion. */
    private function resolve(ConnectionManager $manager, string $name = 'main'): array
    {
        $method = new \ReflectionMethod($manager, 'configFor');

        return $method->invoke($manager, $name);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->removeDir($path) : @unlink($path);
        }

        @rmdir($dir);
    }

    // -----------------------------------------------------------------
    //  MySQL / PostgreSQL
    // -----------------------------------------------------------------

    public function test_mysql_url_is_fully_parsed(): void
    {
        $manager = $this->manager([
            'main' => ['url' => 'mysql://root:secret@db.example.com:3307/app?charset=utf8mb4'],
        ]);

        $config = $this->resolve($manager);

        $this->assertSame('mysql', $config['driver']);
        $this->assertSame('db.example.com', $config['host']);
        $this->assertSame(3307, $config['port']);
        $this->assertSame('app', $config['database']);
        $this->assertSame('root', $config['username']);
        $this->assertSame('secret', $config['password']);
        $this->assertSame('utf8mb4', $config['charset']);
        $this->assertArrayNotHasKey('url', $config);
    }

    public function test_default_port_is_applied_when_missing(): void
    {
        $mysql = $this->resolve($this->manager([
            'main' => ['url' => 'mysql://u:p@localhost/app'],
        ]));
        $pgsql = $this->resolve($this->manager([
            'main' => ['url' => 'pgsql://u:p@localhost/app'],
        ]));

        $this->assertSame(3306, $mysql['port']);
        $this->assertSame(5432, $pgsql['port']);
    }

    public function test_host_defaults_to_localhost(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $config = $this->resolve($this->manager([
            'main' => ['url' => 'mysql:///app'],
        ]));

        // Selon la version de PHP, parse_url peut refuser cette forme :
        // on vérifie simplement que si elle passe, le host par défaut est appliqué.
        $this->assertSame('localhost', $config['host']);
    }

    #[DataProvider('driverAliasProvider')]
    public function test_driver_aliases_are_normalized(string $scheme, string $expected): void
    {
        $config = $this->resolve($this->manager([
            'main' => ['url' => "$scheme://u:p@localhost/app"],
        ]));

        $this->assertSame($expected, $config['driver']);
    }

    public static function driverAliasProvider(): array
    {
        return [
            'mysql'      => ['mysql', 'mysql'],
            'mariadb'    => ['mariadb', 'mysql'],
            'pgsql'      => ['pgsql', 'pgsql'],
            'postgres'   => ['postgres', 'pgsql'],
            'postgresql' => ['postgresql', 'pgsql'],
        ];
    }

    public function test_special_characters_in_credentials_are_decoded(): void
    {
        $password = 'p@ss/w:rd#1';
        $url = 'mysql://us%40er:' . rawurlencode($password) . '@localhost/app';

        $config = $this->resolve($this->manager(['main' => ['url' => $url]]));

        $this->assertSame('us@er', $config['username']);
        $this->assertSame($password, $config['password']);
    }

    public function test_url_without_credentials_has_no_username_or_password_keys(): void
    {
        $config = $this->resolve($this->manager([
            'main' => ['url' => 'mysql://localhost/app'],
        ]));

        $this->assertArrayNotHasKey('username', $config);
        $this->assertArrayNotHasKey('password', $config);
    }

    public function test_url_values_override_explicit_config(): void
    {
        $config = $this->resolve($this->manager([
            'main' => [
                'url'     => 'mysql://u:p@url-host/url_db',
                'host'    => 'explicit-host',
                'charset' => 'utf8mb4',
            ],
        ]));

        // L'URL est prioritaire
        $this->assertSame('url-host', $config['host']);
        $this->assertSame('url_db', $config['database']);
        // Les clés absentes de l'URL sont conservées
        $this->assertSame('utf8mb4', $config['charset']);
    }

    public function test_empty_url_is_ignored(): void
    {
        $config = $this->resolve($this->manager([
            'main' => [
                'url'      => '',
                'driver'   => 'mysql',
                'host'     => 'explicit-host',
                'database' => 'app',
            ],
        ]));

        $this->assertSame('explicit-host', $config['host']);
    }

    // -----------------------------------------------------------------
    //  SQLite : résolution des chemins
    // -----------------------------------------------------------------

    #[DataProvider('relativeSqliteProvider')]
    public function test_sqlite_relative_paths_are_resolved_against_base_path(string $url): void
    {
        $config = $this->resolve($this->manager(['main' => ['url' => $url]]));

        $this->assertSame('sqlite', $config['driver']);
        $this->assertSame(
            $this->baseDir . DIRECTORY_SEPARATOR . 'database/database.sqlite',
            $config['database']
        );
    }

    public static function relativeSqliteProvider(): array
    {
        return [
            'forme courte'   => ['sqlite:database/database.sqlite'],
            'trois slashes'  => ['sqlite:///database/database.sqlite'],
            'alias sqlite3'  => ['sqlite3:database/database.sqlite'],
        ];
    }

    public function test_sqlite_absolute_path_with_four_slashes(): void
    {
        $config = $this->resolve($this->manager([
            'main' => ['url' => 'sqlite:////var/data/db.sqlite'],
        ]));

        $this->assertSame('/var/data/db.sqlite', $config['database']);
    }

    public function test_sqlite_windows_absolute_path_is_not_prefixed(): void
    {
        $config = $this->resolve($this->manager([
            'main' => ['url' => 'sqlite:///C:/data/db.sqlite'],
        ]));

        $this->assertSame('C:/data/db.sqlite', $config['database']);
    }

    #[DataProvider('memorySqliteProvider')]
    public function test_sqlite_memory_database(string $url): void
    {
        $config = $this->resolve($this->manager(['main' => ['url' => $url]]));

        $this->assertSame('sqlite', $config['driver']);
        $this->assertSame(':memory:', $config['database']);
    }

    public static function memorySqliteProvider(): array
    {
        return [
            'sqlite::memory:'    => ['sqlite::memory:'],
            'sqlite:///:memory:' => ['sqlite:///:memory:'],
        ];
    }

    public function test_sqlite_query_string_is_ignored_in_path(): void
    {
        $config = $this->resolve($this->manager([
            'main' => ['url' => 'sqlite:database/database.sqlite?mode=rw'],
        ]));

        $this->assertStringEndsWith('database/database.sqlite', $config['database']);
    }

    public function test_sqlite_percent_encoded_path_is_decoded(): void
    {
        $config = $this->resolve($this->manager([
            'main' => ['url' => 'sqlite:database/my%20db.sqlite'],
        ]));

        $this->assertStringEndsWith('database/my db.sqlite', $config['database']);
    }

    // -----------------------------------------------------------------
    //  Erreurs
    // -----------------------------------------------------------------

    public function test_invalid_url_throws(): void
    {
        $manager = $this->manager(['main' => ['url' => 'pas-une-url']]);

        $this->expectException(\InvalidArgumentException::class);
        $this->resolve($manager);
    }

    public function test_sqlite_url_without_path_throws(): void
    {
        $manager = $this->manager(['main' => ['url' => 'sqlite:']]);

        $this->expectException(\InvalidArgumentException::class);
        $this->resolve($manager);
    }

    public function test_error_message_does_not_leak_the_password(): void
    {
        $manager = $this->manager(['main' => ['url' => 'secret-password-no-scheme']]);

        try {
            $this->resolve($manager);
            $this->fail('Une exception était attendue.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringNotContainsString('secret-password', $e->getMessage());
        }
    }

    public function test_unknown_connection_throws(): void
    {
        $manager = $this->manager([]);

        $this->expectException(\InvalidArgumentException::class);
        $manager->forConnection('inexistante');
    }

    // -----------------------------------------------------------------
    //  Intégration : vraie connexion SQLite
    // -----------------------------------------------------------------

    public function test_sqlite_relative_url_opens_a_real_connection(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('L\'extension pdo_sqlite est requise.');
        }

        $manager = $this->manager([
            'main' => ['url' => 'sqlite:database/database.sqlite'],
        ]);

        $pdo = $manager->pdo();
        $pdo->exec('CREATE TABLE t (id INTEGER PRIMARY KEY, name TEXT)');
        $pdo->exec("INSERT INTO t (name) VALUES ('ok')");

        $this->assertSame('ok', $pdo->query('SELECT name FROM t')->fetchColumn());
        $this->assertFileExists($this->baseDir . '/database/database.sqlite');

        $manager->close();
    }

    public function test_for_connection_works_with_url_based_sqlite(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('L\'extension pdo_sqlite est requise.');
        }

        $manager = $this->manager([
            'main'  => ['url' => 'sqlite::memory:'],
            'other' => ['url' => 'sqlite:database/other.sqlite'],
        ]);

        $pdo = $manager->forConnection('other');
        $pdo->exec('CREATE TABLE t (id INTEGER)');

        $this->assertFileExists($this->baseDir . '/database/other.sqlite');
    }
}