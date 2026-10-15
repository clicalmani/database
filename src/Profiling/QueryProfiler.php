<?php

namespace Clicalmani\Database\Profiling;

use Clicalmani\Core\Support\Facades\DB;
use Clicalmani\Database\Events\QueryProfiled;

final class QueryProfiler
{
    private bool $enabled = false;

    // ─────────────────────────────────────────────────────────────
    // Activation globale (mode dev)
    // ─────────────────────────────────────────────────────────────

    public function enable(): self
    {
        if (! $this->enabled) {
            DB::connection()->getPdo()->exec('SET PROFILING = 1');
            $this->enabled = true;
        }

        return $this;
    }

    public function disable(): self
    {
        if ($this->enabled) {
            DB::connection()->getPdo()->exec('SET PROFILING = 0');
            $this->enabled = false;
        }

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function maybeEnable(bool $enable): void
    {
        if ($enable) $this->enable();
        else $this->disable();
    }

    // ─────────────────────────────────────────────────────────────
    // SHOW PROFILE
    // ─────────────────────────────────────────────────────────────

    /**
     * Profile une requête SQL (string) ou toutes les requêtes d'une closure
     * avec SHOW PROFILE, puis émet un événement QueryProfiled.
     *
     * Pour une closure, seul le profil de la dernière requête exécutée
     * est retourné (comportement de SHOW PROFILE).
     */
    public function profile(
        string|callable $query,
        array $bindings = [],
        string $connection = 'default'
    ): array {
        return is_callable($query) && ! is_string($query)
            ? $this->profileCallback($query, $connection)
            : $this->profileSql($query, $bindings, $connection);
    }

    private function profileSql(string $sql, array $bindings, string $connection): array
    {
        $pdo        = DB::connection($connection)->getPdo();
        $wasEnabled = $this->enabled;

        if (! $wasEnabled) {
            $pdo->exec('SET PROFILING = 1');
        }

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($bindings);
            $stmt->closeCursor();

            $profile = $this->fetchProfile($pdo);

            event(new QueryProfiled(
                sql:        $sql,
                bindings:   $bindings,
                duration:   array_sum(array_column($profile, 'Duration')),
                profile:    $profile,
                connection: $connection,
            ));

            return $profile;
        } finally {
            if (! $wasEnabled) {
                $pdo->exec('SET PROFILING = 0');
            }
        }
    }

    private function profileCallback(callable $callback, string $connection): array
    {
        $wasEnabled = $this->enabled;

        $this->enable();

        try {
            $callback();

            return $this->captureLast($connection);
        } finally {
            if (! $wasEnabled) {
                $this->disable();
            }
        }
    }

    /**
     * Récupère le profil de la dernière requête exécutée.
     */
    public function captureLast(string $connection = 'default'): array
    {
        $pdo     = DB::connection($connection)->getPdo();
        $profile = $this->fetchProfile($pdo);

        if (! empty($profile)) {
            event(new QueryProfiled(
                sql:        '',
                bindings:   [],
                duration:   array_sum(array_column($profile, 'Duration')),
                profile:    $profile,
                connection: $connection,
            ));
        }

        return $profile;
    }

    /**
     * Résumé lisible d'un profil (durées en ms).
     */
    public function summary(array $profile): array
    {
        $byPhase = [];

        foreach ($profile as $step) {
            $phase = $step['Status'] ?? 'unknown';
            $byPhase[$phase] = ($byPhase[$phase] ?? 0) + (float) ($step['Duration'] ?? 0);
        }

        arsort($byPhase);

        return [
            'total_ms' => round(array_sum($byPhase), 3),
            'phases'   => array_map(fn($ms) => round($ms, 3), $byPhase),
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // EXPLAIN
    // ─────────────────────────────────────────────────────────────

    /**
     * EXPLAIN sur un SQL ou sur toutes les requêtes d'une closure.
     *
     * - SQL     : retourne le plan (lignes EXPLAIN, ou nœuds ANALYZE).
     * - Closure : retourne une liste de { sql, bindings, plan }, une entrée
     *             par requête exécutée dans la closure.
     *
     * Avec $analyze = true, seules les requêtes SELECT sont analysées dans
     * une closure : EXPLAIN ANALYZE réexécute la requête, ce qui serait
     * dangereux pour un INSERT/UPDATE/DELETE déjà joué par la closure.
     */
    public function explain(
        string|callable $query,
        array $bindings = [],
        bool $analyze = false,
        string $connection = 'default'
    ): array {
        return is_callable($query) && ! is_string($query)
            ? $this->explainCallback($query, $analyze, $connection)
            : $this->explainSql($query, $bindings, $analyze, $connection);
    }

    private function explainSql(string $sql, array $bindings, bool $analyze, string $connection): array
    {
        $pdo = DB::connection($connection)->getPdo();

        $stmt = $pdo->prepare(($analyze ? 'EXPLAIN ANALYZE ' : 'EXPLAIN ') . $sql);
        $stmt->execute($bindings);

        if (! $analyze) {
            $plan = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            return $plan;
        }

        $tree = (string) $stmt->fetchColumn();
        $stmt->closeCursor();

        return $this->parseAnalyzeTree($tree);
    }

    private function explainCallback(callable $callback, bool $analyze, string $connection): array
    {
        $results = [];

        foreach ($this->captureQueries($callback, $connection) as $q) {
            $pattern = $analyze
                ? '/^\s*\(?\s*(select|with|table)\b/i'
                : '/^\s*(select|with|table|insert|update|delete|replace)\b/i';

            if (! preg_match($pattern, $q['sql'])) {
                continue;
            }

            $results[] = [
                'sql'      => $q['sql'],
                'bindings' => $q['bindings'],
                'plan'     => $this->explainSql($q['sql'], $q['bindings'], $analyze, $connection),
            ];
        }

        return $results;
    }

    /**
     * Exécute la closure et retourne les requêtes qu'elle a lancées.
     * À adapter à l'API de journalisation de votre couche DB.
     */
    private function captureQueries(callable $callback, string $connection): array
    {
        $queries = [];

        $stop = DB::listen(function ($sql, $bindings = []) use (&$queries) {
            $queries[] = ['sql' => $sql, 'bindings' => $bindings];
        });

        try {
            $callback();
        } finally {
            if (is_callable($stop)) {
                $stop(); // désinscrit l'écouteur si l'API le permet
            }
        }

        return $queries;
    }

    private function parseAnalyzeTree(string $tree): array
    {
        $steps = [];

        foreach (explode("\n", $tree) as $line) {
            if (! preg_match('/^(\s*)-> (.*)$/', $line, $m)) {
                continue;
            }

            $ms    = 0.0;
            $rows  = 0;
            $loops = 1;

            if (preg_match('/actual time=[\d.]+\.\.([\d.]+) rows=(\d+) loops=(\d+)/', $m[2], $t)) {
                $loops = (int) $t[3];
                $rows  = (int) $t[2];
                $ms    = (float) $t[1] * $loops;
            }

            $steps[] = [
                'Status'   => trim(preg_replace('/\s+\(cost=.*$/', '', $m[2])),
                'Duration' => $ms, // inclusif : contient le temps des enfants
                'Rows'     => $rows,
                'Loops'    => $loops,
                'Depth'    => intdiv(strlen($m[1]), 4),
            ];
        }

        return $steps;
    }

    // ─────────────────────────────────────────────────────────────

    private function fetchProfile(\PDO $pdo): array
    {
        $rows = $pdo->query('SHOW PROFILE')->fetchAll(\PDO::FETCH_ASSOC);

        // SHOW PROFILE renvoie des secondes : on normalise en millisecondes
        foreach ($rows as &$row) {
            $row['Duration'] = (float) $row['Duration'] * 1000;
        }

        return $rows;
    }
}