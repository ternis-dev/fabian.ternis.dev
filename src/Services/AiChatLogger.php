<?php

namespace App\Services;

use Throwable;

/**
 * AiChatLogger
 *
 * Persists every AI chat request+response to:
 *   - DB (MySQL or SQLite, via the shared DatabaseService)
 *   - JSON file at storage/ai_logs/<session_id>.json
 *
 * Tables auto-created on first use:
 *   - ai_chat_sessions  – one row per browser session (session_id uuid, model, timestamps)
 *   - ai_chat_requests  – one row per API call (linked to session, stores full messages + reply)
 */
class AiChatLogger
{
    protected DatabaseService $db;
    protected string $logDir;

    public function __construct(?DatabaseService $db = null, ?string $logDir = null)
    {
        $this->db     = $db ?? db();
        $this->logDir = $logDir ?? __DIR__ . '/../../storage/ai_logs';

        $this->ensureSchema();
        $this->ensureLogDir();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Public API
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Ensure a session row exists and return the session_id.
     * If the session doesn't exist yet it is created.
     *
     * @param string      $sessionId  UUID generated on the client side
     * @param string      $model      Model slug used for this session
     * @param string|null $ip         Client IP (nullable for privacy)
     * @return string  The session_id
     */
    public function upsertSession(string $sessionId, string $model, ?string $ip = null): string
    {
        try {
            $existing = $this->db->fetch(
                'SELECT id FROM ai_chat_sessions WHERE session_id = ?',
                [$sessionId]
            );

            if (!$existing) {
                $this->db->execute(
                    'INSERT INTO ai_chat_sessions (session_id, model, ip_hash, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?)',
                    [
                        $sessionId,
                        $model,
                        $ip ? hash('sha256', $ip) : null,   // hash for privacy
                        date('Y-m-d H:i:s'),
                        date('Y-m-d H:i:s'),
                    ]
                );
            } else {
                // Update model (user may have changed it) + touch updated_at
                $this->db->execute(
                    'UPDATE ai_chat_sessions SET model = ?, updated_at = ? WHERE session_id = ?',
                    [$model, date('Y-m-d H:i:s'), $sessionId]
                );
            }
        } catch (Throwable $e) {
            error_log('[AiChatLogger] upsertSession error: ' . $e->getMessage());
        }

        return $sessionId;
    }

    /**
     * Log a single request+response pair.
     *
     * @param string  $sessionId   Browser session UUID
     * @param string  $model       Model slug
     * @param array   $messages    Full messages array sent to the API
     * @param string  $reply       The assistant's reply text
     * @param bool    $success     Whether the API call succeeded
     * @param float   $durationMs  Round-trip duration in milliseconds
     * @param int     $httpStatus  HTTP status returned by hackclub AI
     */
    public function logRequest(
        string $sessionId,
        string $model,
        array  $messages,
        string $reply,
        bool   $success,
        float  $durationMs = 0.0,
        int    $httpStatus = 200
    ): void {
        $now = date('Y-m-d H:i:s');

        try {
            $this->db->execute(
                'INSERT INTO ai_chat_requests
                    (session_id, model, messages_json, reply, success, http_status, duration_ms, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $sessionId,
                    $model,
                    json_encode($messages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    $reply,
                    $success ? 1 : 0,
                    $httpStatus,
                    round($durationMs, 2),
                    $now,
                ]
            );
        } catch (Throwable $e) {
            error_log('[AiChatLogger] logRequest DB error: ' . $e->getMessage());
        }

        $this->writeJson($sessionId, $model, $messages, $reply, $success, $durationMs, $httpStatus, $now);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Create tables if they don't exist yet.
     * Uses portable SQL that works on both MySQL and SQLite.
     */
    protected function ensureSchema(): void
    {
        try {
            $driver = $this->db->getDriver();

            if ($driver === 'mysql') {
                $this->db->execute("
                    CREATE TABLE IF NOT EXISTS ai_chat_sessions (
                        id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        session_id  VARCHAR(64)  NOT NULL UNIQUE,
                        model       VARCHAR(128) NOT NULL,
                        ip_hash     VARCHAR(64)  NULL,
                        created_at  DATETIME     NOT NULL,
                        updated_at  DATETIME     NOT NULL,
                        INDEX idx_session_id (session_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                ");

                $this->db->execute("
                    CREATE TABLE IF NOT EXISTS ai_chat_requests (
                        id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        session_id   VARCHAR(64)  NOT NULL,
                        model        VARCHAR(128) NOT NULL,
                        messages_json TEXT        NOT NULL,
                        reply        TEXT         NOT NULL,
                        success      TINYINT(1)   NOT NULL DEFAULT 1,
                        http_status  SMALLINT     NOT NULL DEFAULT 200,
                        duration_ms  FLOAT        NOT NULL DEFAULT 0,
                        created_at   DATETIME     NOT NULL,
                        INDEX idx_req_session (session_id),
                        FOREIGN KEY (session_id) REFERENCES ai_chat_sessions(session_id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                ");
            } else {
                // SQLite
                $this->db->execute("
                    CREATE TABLE IF NOT EXISTS ai_chat_sessions (
                        id         INTEGER PRIMARY KEY AUTOINCREMENT,
                        session_id TEXT    NOT NULL UNIQUE,
                        model      TEXT    NOT NULL,
                        ip_hash    TEXT,
                        created_at TEXT    NOT NULL,
                        updated_at TEXT    NOT NULL
                    )
                ");

                $this->db->execute("
                    CREATE TABLE IF NOT EXISTS ai_chat_requests (
                        id            INTEGER PRIMARY KEY AUTOINCREMENT,
                        session_id    TEXT    NOT NULL,
                        model         TEXT    NOT NULL,
                        messages_json TEXT    NOT NULL,
                        reply         TEXT    NOT NULL,
                        success       INTEGER NOT NULL DEFAULT 1,
                        http_status   INTEGER NOT NULL DEFAULT 200,
                        duration_ms   REAL    NOT NULL DEFAULT 0,
                        created_at    TEXT    NOT NULL,
                        FOREIGN KEY (session_id) REFERENCES ai_chat_sessions(session_id) ON DELETE CASCADE
                    )
                ");
            }
        } catch (Throwable $e) {
            error_log('[AiChatLogger] ensureSchema error: ' . $e->getMessage());
        }
    }

    /**
     * Make sure the JSON log directory exists.
     */
    protected function ensureLogDir(): void
    {
        if (!is_dir($this->logDir)) {
            @mkdir($this->logDir, 0755, true);
        }
    }

    /**
     * Append a single request entry to <logDir>/<session_id>.json.
     * The file contains a JSON array of log entries (one per request).
     */
    protected function writeJson(
        string $sessionId,
        string $model,
        array  $messages,
        string $reply,
        bool   $success,
        float  $durationMs,
        int    $httpStatus,
        string $timestamp
    ): void {
        $file = $this->logDir . '/' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $sessionId) . '.json';

        // Read existing entries (or start fresh)
        $entries = [];
        if (file_exists($file)) {
            $raw = @file_get_contents($file);
            if ($raw !== false) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $entries = $decoded;
                }
            }
        }

        $entries[] = [
            'timestamp'   => $timestamp,
            'session_id'  => $sessionId,
            'model'       => $model,
            'success'     => $success,
            'http_status' => $httpStatus,
            'duration_ms' => round($durationMs, 2),
            'messages'    => $messages,
            'reply'       => $reply,
        ];

        @file_put_contents(
            $file,
            json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );
    }
}
