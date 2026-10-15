<?php
namespace Clicalmani\Database\Concerns;

final class SqlBinder
{
    private const TOKEN = '~(\'(?:[^\'\\\\]|\\\\.|\'\')*\'|"(?:[^"\\\\]|\\\\.|"")*"|`[^`]*`|::|\?|:\w+)~s';

    /**
     * Prépare le SQL pour PDO : les Raw sont injectés dans le SQL et retirés des bindings.
     *
     * @return array{0: string, 1: array}  [sql, bindings]
     */
    public static function resolve(string $sql, array $bindings): array
    {
        $out = [];

        $sql = self::walk($sql, $bindings, function (mixed $value, string $token) use (&$out): string {
            if ($token === '?') {
                $out[] = $value;
            } else {
                $out[$token] = $value;
            }
            return $token; // le placeholder est conservé
        });

        return [$sql, $out];
    }

    /**
     * SQL complet avec les valeurs injectées. À réserver au debug / logs,
     * jamais pour exécuter une requête.
     */
    public static function interpolate(string $sql, array $bindings, ?PDO $pdo = null): string
    {
        return self::walk($sql, $bindings, function (mixed $value) use ($pdo): string {
            return match (true) {
                $value === null          => 'NULL',
                is_bool($value)          => $value ? '1' : '0',
                is_int($value),
                is_float($value)         => (string) $value,
                default                  => $pdo
                    ? $pdo->quote((string) $value)
                    : "'" . str_replace("'", "''", (string) $value) . "'",
            };
        });
    }

    private static function walk(string $sql, array $bindings, callable $handler): string
    {
        $pos = 0;

        return preg_replace_callback(self::TOKEN, function (array $m) use ($bindings, &$pos, $handler): string {
            $token = $m[0];

            if ($token === '?') {
                if (!array_key_exists($pos, $bindings)) {
                    throw new \InvalidArgumentException("Binding positionnel manquant (index $pos).");
                }
                $key = $pos++;
            } elseif ($token[0] === ':' && $token !== '::') {
                $name = substr($token, 1);
                if (array_key_exists($name, $bindings)) {
                    $key = $name;
                } elseif (array_key_exists($token, $bindings)) {
                    $key = $token;
                } else {
                    throw new \InvalidArgumentException("Binding nommé manquant : $token");
                }
            } else {
                return $token; // chaîne, identifiant ou ::
            }

            $value = $bindings[$key];

            return $value instanceof Raw ? $value->getValue() : $handler($value, $token);
        }, $sql) ?? throw new \RuntimeException('Erreur lors de l\'analyse du SQL.');
    }
}