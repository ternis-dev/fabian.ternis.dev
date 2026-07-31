<?php

namespace App\Services;

use PDO;
use PDOException;
use Throwable;

class DatabaseService
{
    protected ?PDO $pdo = null;
    protected string $driver = 'sqlite';
    protected bool $isFallback = false;
    protected string $sqlitePath;
    protected ?string $lastError = null;

    /**
     * Initialize the DatabaseService.
     *
     * Will attempt to connect to MySQL if credentials are set.
     * If MySQL credentials are missing or connection fails, falls back to SQLite at storage/db.sqlite.
     *
     * @param array|null $config Optional custom database config
     */
    public function __construct(?array $config = null)
    {
        $defaultSqlitePath = __DIR__ . '/../../storage/db.sqlite';
        $this->sqlitePath = $config['sqlite_path'] ?? env('DB_SQLITE_PATH', $defaultSqlitePath);

        $driverChoice = strtolower($config['driver'] ?? env('DB_CONNECTION', env('DB_DRIVER', 'mysql')));

        if ($driverChoice === 'mysql') {
            $host     = $config['host']     ?? env('DB_HOST', '127.0.0.1');
            $port     = $config['port']     ?? env('DB_PORT', '3306');
            $database = $config['database'] ?? env('DB_DATABASE');
            $username = $config['username'] ?? env('DB_USERNAME', env('DB_USER'));
            $password = $config['password'] ?? env('DB_PASSWORD', env('DB_PASS'));
            $charset  = $config['charset']  ?? env('DB_CHARSET', 'utf8mb4');

            // Only attempt MySQL if database and username credentials are specified
            if (!empty($database) && !empty($username)) {
                try {
                    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
                    $options = [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ];

                    $this->pdo = new PDO($dsn, $username, (string)$password, $options);
                    $this->driver = 'mysql';
                    $this->isFallback = false;
                    return;
                } catch (Throwable $e) {
                    // Connection failed — record error and mark as fallback to SQLite
                    $this->lastError = $e->getMessage();
                    $this->isFallback = true;
                }
            } else {
                $this->isFallback = true;
            }
        }

        // Fallback or explicit SQLite connection
        $this->connectSqlite();
    }

    /**
     * Establish SQLite connection.
     */
    protected function connectSqlite(): void
    {
        $dir = dirname($this->sqlitePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $dsn = 'sqlite:' . $this->sqlitePath;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        try {
            $this->pdo = new PDO($dsn, null, null, $options);
            $this->pdo->exec('PRAGMA foreign_keys = ON;');
            $this->driver = 'sqlite';
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            throw new PDOException("Database connection failed for both MySQL and SQLite fallback: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Get underlying PDO instance.
     *
     * @return PDO
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Get active database driver name ('mysql' or 'sqlite').
     *
     * @return string
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * Check if connection fell back to SQLite due to missing/failed MySQL config.
     *
     * @return bool
     */
    public function isFallback(): bool
    {
        return $this->isFallback;
    }

    /**
     * Get the SQLite database file path.
     *
     * @return string
     */
    public function getSqlitePath(): string
    {
        return $this->sqlitePath;
    }

    /**
     * Get last connection error message if fallback occurred.
     *
     * @return string|null
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Execute a SQL query with parameter binding.
     *
     * @param string $sql
     * @param array $params
     * @return \PDOStatement
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch a single row from a query.
     *
     * @param string $sql
     * @param array $params
     * @return array|null
     */
    public function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * Fetch all rows from a query.
     *
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Execute a statement (INSERT, UPDATE, DELETE) and return success status.
     *
     * @param string $sql
     * @param array $params
     * @return bool
     */
    public function execute(string $sql, array $params = []): bool
    {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Get the last inserted ID.
     *
     * @param string|null $name
     * @return string|false
     */
    public function lastInsertId(?string $name = null): string|false
    {
        return $this->pdo->lastInsertId($name);
    }

    /**
     * Execute a closure within a database transaction.
     *
     * @param callable $callback
     * @return mixed
     * @throws Throwable
     */
    public function transaction(callable $callback): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $callback($this);
            $this->pdo->commit();
            return $result;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
