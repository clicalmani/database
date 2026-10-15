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

    public function configure(array $config): void
    {
        $this->config = $config;
        $this->defaultConnection = $config['default'] ?? 'default';
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

        return $config;
    }

    private function createPdo(array $config): \PDO
    {
        $dsn = $this->buildDsn($config);

        return new \PDO(
            $dsn,
            $config['username'] ?? null,
            $config['password'] ?? null,
            [
                \PDO::ATTR_PERSISTENT      => true,
                \PDO::ATTR_EMULATE_PREPARES => false,
                \PDO::ATTR_ERRMODE          => \PDO::ERRMODE_EXCEPTION,
            ]
        );
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
}