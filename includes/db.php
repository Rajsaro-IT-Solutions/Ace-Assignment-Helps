<?php
/**
 * DataStore Database Engine (Pure AWS RDS MySQL)
 * Exclusively powered by MySQL for high reliability, ACID compliance, and concurrency.
 */

class DataStore {
    private static $pdo = null;

    private static $dbHost = 'database-1.c1o0ygcs2cex.ap-south-1.rds.amazonaws.com';
    private static $dbPort = 3306;
    private static $dbUser = 'admin';
    private static $dbPass = 'Marwal#1627';
    private static $dbName = 'aceassignmenthelp_db';

    public static function getPdo() {
        if (self::$pdo === null) {
            try {
                $dsn = "mysql:host=" . self::$dbHost . ";port=" . self::$dbPort . ";dbname=" . self::$dbName . ";charset=utf8mb4";
                self::$pdo = new PDO($dsn, self::$dbUser, self::$dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 5
                ]);
            } catch (Throwable $e) {
                error_log("MySQL Connection Failed: " . $e->getMessage());
                self::$pdo = null;
                throw new Exception("Unable to connect to MySQL database: " . $e->getMessage());
            }
        }
        return self::$pdo;
    }

    /**
     * Collision-proof Unique ID Generator
     * Finds the maximum existing numeric suffix in the database and increments it.
     */
    public static function generateNextId($collectionName, $idKey, $prefix = '', $pad = 3, $startAt = 1) {
        $pdo = self::getPdo();
        if (!$pdo) {
            throw new Exception("Database connection unavailable.");
        }

        if ($prefix !== '') {
            $stmt = $pdo->prepare("SELECT `$idKey` FROM `$collectionName` WHERE `$idKey` LIKE ?");
            $stmt->execute([$prefix . '%']);
        } else {
            $stmt = $pdo->query("SELECT `$idKey` FROM `$collectionName`");
        }
        $existingRows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $maxNum = $startAt - 1;
        $existingMap = [];

        foreach ($existingRows as $val) {
            $val = (string)$val;
            $existingMap[$val] = true;
            $numPart = ($prefix !== '' && strpos($val, $prefix) === 0) ? substr($val, strlen($prefix)) : $val;
            if (is_numeric($numPart)) {
                $n = (int)$numPart;
                if ($n > $maxNum) {
                    $maxNum = $n;
                }
            }
        }

        do {
            $maxNum++;
            $candidate = ($pad > 0) ? ($prefix . sprintf('%0' . $pad . 'd', $maxNum)) : ($prefix . $maxNum);
        } while (isset($existingMap[$candidate]));

        return $candidate;
    }

    private static function formatRecordFromDb($collectionName, $row) {
        if (!$row) return null;

        if ($collectionName === 'experts' && isset($row['subjects'])) {
            if (is_string($row['subjects'])) {
                $decoded = json_decode($row['subjects'], true);
                $row['subjects'] = is_array($decoded) ? $decoded : array_map('trim', explode(',', $row['subjects']));
            }
        }

        if ($collectionName === 'courses' && isset($row['topics'])) {
            if (is_string($row['topics'])) {
                $decoded = json_decode($row['topics'], true);
                $row['topics'] = is_array($decoded) ? $decoded : array_map('trim', explode(',', $row['topics']));
            }
        }

        if ($collectionName === 'support_tickets' && isset($row['replies'])) {
            if (is_string($row['replies'])) {
                $decoded = json_decode($row['replies'], true);
                $row['replies'] = is_array($decoded) ? $decoded : [];
            }
        }

        if ($collectionName === 'notifications') {
            if (isset($row['notification_id'])) {
                $row['id'] = $row['notification_id'];
            }
            $row['is_read'] = (bool)($row['is_read'] ?? false);
        }

        if ($collectionName === 'files') {
            $row['is_internal'] = (bool)($row['is_internal'] ?? false);
        }

        return $row;
    }

    public static function getCollection($collectionName) {
        $pdo = self::getPdo();
        if (!$pdo) return [];

        try {
            $stmt = $pdo->query("SELECT * FROM `$collectionName` ORDER BY id DESC");
            $rows = $stmt->fetchAll();
            $result = [];
            foreach ($rows as $row) {
                $result[] = self::formatRecordFromDb($collectionName, $row);
            }
            return $result;
        } catch (Throwable $e) {
            error_log("DataStore::getCollection error ($collectionName): " . $e->getMessage());
            return [];
        }
    }

    public static function findOne($collectionName, $key, $value) {
        $pdo = self::getPdo();
        if (!$pdo) return null;

        try {
            $sqlKey = $key;
            if ($collectionName === 'notifications' && $key === 'id') {
                $sqlKey = 'notification_id';
            }
            $stmt = $pdo->prepare("SELECT * FROM `$collectionName` WHERE `$sqlKey` = ? LIMIT 1");
            $stmt->execute([$value]);
            $row = $stmt->fetch();
            if ($row) {
                return self::formatRecordFromDb($collectionName, $row);
            }
        } catch (Throwable $e) {
            error_log("DataStore::findOne error ($collectionName, $key): " . $e->getMessage());
        }
        return null;
    }

    public static function filter($collectionName, callable $callback) {
        $items = self::getCollection($collectionName);
        return array_values(array_filter($items, $callback));
    }

    public static function insert($collectionName, $record) {
        $pdo = self::getPdo();
        if (!$pdo) {
            throw new Exception("Cannot insert: Database is not connected.");
        }

        $dbRecord = $record;
        if ($collectionName === 'experts' && isset($dbRecord['subjects']) && is_array($dbRecord['subjects'])) {
            $dbRecord['subjects'] = json_encode($dbRecord['subjects']);
        }
        if ($collectionName === 'courses' && isset($dbRecord['topics']) && is_array($dbRecord['topics'])) {
            $dbRecord['topics'] = json_encode($dbRecord['topics']);
        }
        if ($collectionName === 'support_tickets' && isset($dbRecord['replies']) && is_array($dbRecord['replies'])) {
            $dbRecord['replies'] = json_encode($dbRecord['replies']);
        }
        if ($collectionName === 'notifications' && isset($dbRecord['id'])) {
            $dbRecord['notification_id'] = $dbRecord['id'];
            unset($dbRecord['id']);
        }

        // Filter to only columns that actually exist in the table
        static $columnsCache = [];
        if (!isset($columnsCache[$collectionName])) {
            try {
                $colStmt = $pdo->query("SHOW COLUMNS FROM `$collectionName`");
                $columnsCache[$collectionName] = array_column($colStmt->fetchAll(), 'Field');
            } catch (Throwable $ignore) {
                $columnsCache[$collectionName] = null;
            }
        }
        if (!empty($columnsCache[$collectionName])) {
            $validCols = array_flip($columnsCache[$collectionName]);
            $dbRecord = array_intersect_key($dbRecord, $validCols);
        }

        $fields = array_keys($dbRecord);
        $placeholders = array_fill(0, count($fields), '?');

        $sql = "INSERT INTO `$collectionName` (`" . implode("`, `", $fields) . "`) VALUES (" . implode(", ", $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($dbRecord));

        return $record;
    }

    public static function update($collectionName, $key, $value, $updates) {
        $pdo = self::getPdo();
        if (!$pdo) {
            throw new Exception("Cannot update: Database is not connected.");
        }

        $dbUpdates = $updates;
        if ($collectionName === 'experts' && isset($dbUpdates['subjects']) && is_array($dbUpdates['subjects'])) {
            $dbUpdates['subjects'] = json_encode($dbUpdates['subjects']);
        }
        if ($collectionName === 'courses' && isset($dbUpdates['topics']) && is_array($dbUpdates['topics'])) {
            $dbUpdates['topics'] = json_encode($dbUpdates['topics']);
        }
        if ($collectionName === 'support_tickets' && isset($dbUpdates['replies']) && is_array($dbUpdates['replies'])) {
            $dbUpdates['replies'] = json_encode($dbUpdates['replies']);
        }

        // Filter to only columns that actually exist in the table
        static $updateColsCache = [];
        if (!isset($updateColsCache[$collectionName])) {
            try {
                $colStmt = $pdo->query("SHOW COLUMNS FROM `$collectionName`");
                $updateColsCache[$collectionName] = array_column($colStmt->fetchAll(), 'Field');
            } catch (Throwable $ignore) {
                $updateColsCache[$collectionName] = null;
            }
        }
        if (!empty($updateColsCache[$collectionName])) {
            $validCols = array_flip($updateColsCache[$collectionName]);
            $dbUpdates = array_intersect_key($dbUpdates, $validCols);
        }

        $setParts = [];
        $params = [];
        foreach ($dbUpdates as $k => $v) {
            $setParts[] = "`$k` = ?";
            $params[] = $v;
        }
        $params[] = $value;

        $sqlKey = $key;
        if ($collectionName === 'notifications' && $key === 'id') {
            $sqlKey = 'notification_id';
        }

        $sql = "UPDATE `$collectionName` SET " . implode(", ", $setParts) . " WHERE `$sqlKey` = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public static function delete($collectionName, $key, $value) {
        $pdo = self::getPdo();
        if (!$pdo) {
            throw new Exception("Cannot delete: Database is not connected.");
        }

        $sqlKey = $key;
        if ($collectionName === 'notifications' && $key === 'id') {
            $sqlKey = 'notification_id';
        }

        $stmt = $pdo->prepare("DELETE FROM `$collectionName` WHERE `$sqlKey` = ?");
        return $stmt->execute([$value]);
    }

    public static function getSetting($key, $default = '') {
        $row = self::findOne('settings', 'setting_key', $key);
        return ($row && isset($row['setting_value']) && $row['setting_value'] !== '') ? $row['setting_value'] : $default;
    }

    public static function setSetting($key, $value) {
        $existing = self::findOne('settings', 'setting_key', $key);
        if ($existing) {
            return self::update('settings', 'setting_key', $key, ['setting_value' => $value]);
        } else {
            return self::insert('settings', ['setting_key' => $key, 'setting_value' => $value]);
        }
    }

    public static function purgeAssignment($assignment_id) {
        $assignment_id = trim((string)$assignment_id);
        if (!$assignment_id) return false;

        // 1. Delete associated physical files on disk
        $files = self::filter('files', function($f) use ($assignment_id) {
            return isset($f['assignment_id']) && (string)$f['assignment_id'] === $assignment_id;
        });
        foreach ($files as $f) {
            if (!empty($f['path'])) {
                $filePath = __DIR__ . '/../' . ltrim($f['path'], '/');
                if (file_exists($filePath) && is_file($filePath)) {
                    @unlink($filePath);
                }
            }
        }

        // 2. MySQL purge across all related tables
        $pdo = self::getPdo();
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("DELETE FROM `assignments` WHERE `assignment_id` = ?");
                $stmt->execute([$assignment_id]);
            } catch (Throwable $e) {}

            $tables = ['payments', 'files', 'allocation', 'notes', 'support_tickets'];
            foreach ($tables as $tbl) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM `$tbl` WHERE `assignment_id` = ?");
                    $stmt->execute([$assignment_id]);
                } catch (Throwable $e) {}
            }

            try {
                $stmt = $pdo->prepare("DELETE FROM `notifications` WHERE `message` LIKE ? OR `title` LIKE ?");
                $stmt->execute(['%' . $assignment_id . '%', '%' . $assignment_id . '%']);
            } catch (Throwable $e) {}
        }

        return true;
    }
}
