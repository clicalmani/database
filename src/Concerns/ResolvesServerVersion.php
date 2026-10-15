<?php
namespace Clicalmani\Database\Concerns;

use Clicalmani\Core\Support\Facades\DB;

trait ResolvesServerVersion
{
    private static ?array $serverInfo = null;

    /**
     * Détecte la version du serveur une seule fois.
     *
     * @return array{version: string, is_mariadb: bool}
     */
    protected function serverInfo(): array
    {
        if (self::$serverInfo === null) {
            $version = DB::getPdo()->getAttribute(\PDO::ATTR_SERVER_VERSION) ?? '0.0.0';

            self::$serverInfo = [
                'version'    => $version,
                'is_mariadb' => stripos($version, 'mariadb') !== false,
            ];
        }

        return self::$serverInfo;
    }

    /**
     * MySQL 8.0.19+ ou MariaDB ne supportent PAS toujours la syntaxe AS alias.
     * On retourne true uniquement pour MySQL 8.0.19+.
     */
    protected function supportsAliasUpsert(): bool
    {
        $info = $this->serverInfo();

        if ($info['is_mariadb']) {
            return false; // MariaDB utilise VALUES(col)
        }

        return version_compare($info['version'], '8.0.19', '>=');
    }

    /**
     * Réinitialise le cache (utile en test).
     */
    public static function resetServerInfoCache(): void
    {
        self::$serverInfo = null;
    }
}