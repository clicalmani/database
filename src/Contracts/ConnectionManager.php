<?php
namespace Clicalmani\Database\Contracts;

/**
 * Gère le cycle de vie de la connexion PDO :
 *  - Résolution de la configuration
 *  - Création paresseuse du PDO
 *  - Gestion des swaps (pile)
 *  - Fermeture
 */
final class ConnectionManager
{
    private ?\PDO $pdo = null;

    /** @var \PDO[] */
    private array $swapStack = [];

    private array $config = [];
    private string $defaultConnection = 'default';

    private const DRIVER_ALIASES = [
        'mysql'      => 'mysql',
        'mariadb'    => 'mysql',
        'pgsql'      => 'pgsql',
        'postgres'   => 'pgsql',
        'postgresql' => 'pgsql',
        'sqlite'     => 'sqlite',
        'sqlite3'    => 'sqlite',
    ];

    private const DEFAULT_PORTS = [
        'mysql' => 3306,
        'pgsql' => 5432,
    ];

    private string $basePath = '.';

    public function configure(array $config): void
    {
        $this->config = $config;
        $this->defaultConnection = $config['default'] ?? 'default';
        $this->basePath = rtrim($config['base_path'] ?? getcwd(), '/\\');
    }

    public function pdo(): \PDO
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }

        $config = $this->configFor($this->defaultConnection);

        return $this->pdo = $this->createPdo($config);
    }

    public function forConnection(string $name): \PDO
    {
        if (!isset($this->config['connections'][$name])) {
            throw new \InvalidArgumentException("Connexion '$name' non configurée.");
        }

        return $this->createPdo($this->configFor($name));
    }

    /**
     * Remplace temporairement la connexion active.
     * Empile le PDO précédent pour restauration.
     */
    public function swap(\PDO $pdo): void
    {
        $this->swapStack[] = $this->pdo;
        $this->pdo = $pdo;
    }

    public function restore(): void
    {
        if (empty($this->swapStack)) {
            throw new \RuntimeException("Aucun swap à restaurer.");
        }

        $this->pdo = array_pop($this->swapStack);
    }

    public function setPdo(\PDO $pdo): void
    {
        $this->pdo = $pdo;
    }

    public function close(): void
    {
        $this->pdo = null;
        $this->swapStack = [];
    }

    public function hasSwaps(): bool
    {
        return !empty($this->swapStack);
    }

    public function swapDepth(): int
    {
        return count($this->swapStack);
    }

    // -----------------------------------------------------------------
    //  Internes
    // -----------------------------------------------------------------

    private function configFor(string $name): array
    {
        $config = $this->config['connections'][$name] ?? null;

        if (!$config) {
            throw new \InvalidArgumentException("Connexion '$name' introuvable.");
        }

        if (!empty($config['url'])) {
            $fromUrl = $this->parseUrl($config['url']);
            unset($config['url']);

            // Les valeurs de l'URL sont prioritaires sur le reste
            $config = array_merge($config, $fromUrl);
        }

        return $config;
    }

    private function createPdo(array $config): \PDO
    {
        $dsn = $this->buildDsn($config);

        $pdo = new \PDO(
            $dsn,
            $config['username'] ?? null,
            $config['password'] ?? null,
            [
                \PDO::ATTR_PERSISTENT      => $config['driver'] !== 'sqlite' ? true: false,
                \PDO::ATTR_EMULATE_PREPARES => false,
                \PDO::ATTR_ERRMODE          => \PDO::ERRMODE_EXCEPTION,
            ]
        );

        if (($config['driver'] ?? null) === 'sqlite') {
            $pdo->exec('PRAGMA foreign_keys = ' . ($this->foreignKeysEnabled() ? 'ON' : 'OFF'));
        }

        return $pdo;
    }

    private function buildDsn(array $config): string
    {
        $driver = $config['driver'];
        
        // SQLite : chemin vers le fichier
        if ($driver === 'sqlite') {
            return 'sqlite:' . $config['database'];
        }

        $parts = [
            'host=' . $config['host'],
            'port=' . $config['port'],
            'dbname=' . $config['database'],
        ];

        if (!empty($config['charset'])) {
            $parts[] = 'charset=' . $config['charset'];
        }

        return $driver . ':' . implode(';', $parts);
    }

    private function parseUrl(string $url): array
    {
        // SQLite : pas de host, le chemin est le fichier
        if (preg_match('#^sqlite3?:#i', $url)) {
            return $this->parseSqliteUrl($url);
        }

        $parts = parse_url($url);

        if ($parts === false || !isset($parts['scheme'])) {
            throw new \InvalidArgumentException("URL de connexion invalide.");
        }

        $driver = self::DRIVER_ALIASES[$parts['scheme']] ?? $parts['scheme'];

        $config = [
            'driver'   => $driver,
            'host'     => $parts['host'] ?? 'localhost',
            'port'     => $parts['port'] ?? self::DEFAULT_PORTS[$driver] ?? null,
            'database' => isset($parts['path']) ? ltrim(rawurldecode($parts['path']), '/') : null,
            'username' => isset($parts['user']) ? rawurldecode($parts['user']) : null,
            'password' => isset($parts['pass']) ? rawurldecode($parts['pass']) : null,
        ];

        // Query string : ?charset=utf8mb4&... => options supplémentaires
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
            $config = array_merge($config, $query);
        }

        return array_filter($config, fn ($v) => $v !== null);
    }

    private function parseSqliteUrl(string $url): array
    {
        if (!preg_match('#^sqlite3?:(//)?(.*)$#i', $url, $m)) {
            throw new \InvalidArgumentException("URL SQLite invalide.");
        }

        $hasAuthority = $m[1] !== '';
        $path = $m[2];

        // Retire une éventuelle query string (?foo=bar)
        $path = explode('?', $path, 2)[0];

        // Forme "sqlite:///..." : le 3e slash est le séparateur, pas une racine
        if ($hasAuthority && str_starts_with($path, '/')) {
            $path = substr($path, 1);
        }

        $path = rawurldecode($path);

        if ($path === '') {
            throw new \InvalidArgumentException("Chemin SQLite manquant.");
        }

        if ($path === ':memory:') {
            return ['driver' => 'sqlite', 'database' => ':memory:'];
        }

        // Absolu : "/..." (Unix) ou "C:\..." / "C:/..." (Windows)
        $isAbsolute = str_starts_with($path, '/')
            || preg_match('#^[A-Za-z]:[\\\\/]#', $path) === 1;

        if (!$isAbsolute) {
            $base = $this->basePath !== '' ? $this->basePath : (string) getcwd();
            $path = $base . DIRECTORY_SEPARATOR . $path;
        }

        return ['driver' => 'sqlite', 'database' => $path];
    }

    private function foreignKeysEnabled(): bool
    {
        // $_ENV / $_SERVER : là où la plupart des chargeurs de .env (phpdotenv) écrivent.
        // getenv() : variables définies par le système ou putenv().
        $value = $_ENV['DB_FOREIGN_KEYS']
            ?? $_SERVER['DB_FOREIGN_KEYS']
            ?? getenv('DB_FOREIGN_KEYS');

        if ($value === false || $value === null || $value === '') {
            return true; // non défini => activé
        }

        // Valeur non reconnue (ex. "abc") => on reste sur le défaut (activé)
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
    }
}