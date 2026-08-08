<?php

declare(strict_types=1);

namespace Kodhe\Framework\Profiler\Collectors;

use Kodhe\Framework\Profiler\Contracts\CollectorInterface;

/**
 * Database Collector
 * 
 * Collects database query information
 */
class DatabaseCollector implements CollectorInterface
{
    protected object $ci;
    protected ?array $databases = null;
    protected int $queryToggleCount = 25;

    public function setDependencies(object $ci): void
    {
        $this->ci = $ci;
    }

    public function setQueryToggleCount(int $count): void
    {
        $this->queryToggleCount = $count;
    }

    public function collect(): array
    {
        if ($this->databases !== null) {
            return $this->databases;
        }

        $dbs = [];

        // Find all database instances in CI
        foreach (get_object_vars($this->ci) as $name => $cobject) {
            if (is_object($cobject)) {
                if ($cobject instanceof \Kodhe\Framework\Database\DB) {
                    $dbs[get_class($this->ci) . ':$' . $name] = $cobject;
                } elseif ($cobject instanceof \CI_Model) {
                    foreach (get_object_vars($cobject) as $mname => $mobject) {
                        if ($mobject instanceof \Kodhe\Framework\Database\DB) {
                            $dbs[get_class($cobject) . ':$' . $mname] = $mobject;
                        }
                    }
                }
            }
        }

        $result = [];
        foreach ($dbs as $name => $db) {
            $queries = $db->queries ?? [];
            $queryTimes = $db->query_times ?? [];
            $totalTime = array_sum($queryTimes);

            $result[$name] = [
                'database' => $db->database ?? '',
                'connection_name' => $name,
                'queries' => $queries,
                'query_times' => $queryTimes,
                'query_count' => count($queries),
                'total_time' => $totalTime,
                'hide_queries' => count($queries) > $this->queryToggleCount
            ];
        }

        $this->databases = $result;
        return $this->databases;
    }

    public function hasData(): bool
    {
        if ($this->databases !== null) {
            return !empty($this->databases);
        }

        $this->collect();
        return !empty($this->databases);
    }

    public function getSectionName(): string
    {
        return 'queries';
    }

    public function getDatabases(): array
    {
        if ($this->databases === null) {
            $this->collect();
        }
        return $this->databases;
    }

    public function getDatabaseCount(): int
    {
        if ($this->databases === null) {
            $this->collect();
        }
        return count($this->databases);
    }
}
