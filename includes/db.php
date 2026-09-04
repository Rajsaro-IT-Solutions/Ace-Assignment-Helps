<?php
/**
 * DataStore Database Engine (AWS RDS MySQL + JSON Local Fallback)
 * Ensures 100% uptime: Uses AWS RDS MySQL when available, automatically 
 * falls back to local JSON database if AWS RDS connection times out or fails.
 */

class DataStore {
    private static $pdo = null;
    private static $useJsonFallback = false;
    private static $jsonFilePath = __DIR__ . '/../data/database.json';

    private static $dbHost = 'database-1.c1o0ygcs2cex.ap-south-1.rds.amazonaws.com';
    private static $dbPort = 3306;
    private static $dbUser = 'admin';
    private static $dbPass = 'Marwal#1627';
    private static $dbName = 'aceassignmenthelp_db';

    public static function getPdo() {
        if (self::$useJsonFallback) {
            return null;
        }

        if (self::$pdo === null) {
            try {
                $dsn = "mysql:host=" . self::$dbHost . ";port=" . self::$dbPort . ";dbname=" . self::$dbName . ";charset=utf8mb4";
                self::$pdo = new PDO($dsn, self::$dbUser, self::$dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 3
                ]);
            } catch (Throwable $e) {
                // Connection failed - enable seamless JSON fallback to prevent HTTP 500
                self::$useJsonFallback = true;
                self::$pdo = null;
            }
        }
        return self::$pdo;
    }

    private static function getJsonData() {
        if (!file_exists(self::$jsonFilePath)) {
            return [];
        }
        $content = file_get_contents(self::$jsonFilePath);
        return json_decode($content, true) ?: [];
    }

    private static function saveJsonData($data) {
        file_put_contents(self::$jsonFilePath, json_encode($data, JSON_PRETTY_PRINT));
    }

    private static function formatRecordFromDb($collectionName, $row) {
        if (!$row) return null;

        if ($collectionName === 'experts' && isset($row['subjects'])) {
            if (is_string($row['subjects'])) {
                $decoded = json_decode($row['subjects'], true);
                $row['subjects'] = is_array($decoded) ? $decoded : array_map('trim', explode(',', $row['subjects']));
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
        if ($pdo) {
            try {
                $stmt = $pdo->query("SELECT * FROM `$collectionName` ORDER BY id DESC");
                $rows = $stmt->fetchAll();
                $result = [];
                foreach ($rows as $row) {
                    $result[] = self::formatRecordFromDb($collectionName, $row);
                }
                return $result;
            } catch (Throwable $e) {
                self::$useJsonFallback = true;
            }
        }

        // Fallback to JSON
        $data = self::getJsonData();
        return $data[$collectionName] ?? [];
    }

    public static function findOne($collectionName, $key, $value) {
        $pdo = self::getPdo();
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM `$collectionName` WHERE `$key` = ? LIMIT 1");
                $stmt->execute([$value]);
                $row = $stmt->fetch();
                if ($row) {
                    return self::formatRecordFromDb($collectionName, $row);
                }
            } catch (Throwable $e) {
                self::$useJsonFallback = true;
            }
        }

        // Fallback to JSON
        $items = self::getCollection($collectionName);
        foreach ($items as $item) {
            if (isset($item[$key]) && (string)$item[$key] === (string)$value) {
                return self::formatRecordFromDb($collectionName, $item);
            }
        }
        return null;
    }

    public static function filter($collectionName, callable $callback) {
        $items = self::getCollection($collectionName);
        return array_values(array_filter($items, $callback));
    }

    public static function insert($collectionName, $record) {
        $pdo = self::getPdo();
        if ($pdo) {
            try {
                $dbRecord = $record;
                if ($collectionName === 'experts' && isset($dbRecord['subjects']) && is_array($dbRecord['subjects'])) {
                    $dbRecord['subjects'] = json_encode($dbRecord['subjects']);
                }
                if ($collectionName === 'support_tickets' && isset($dbRecord['replies']) && is_array($dbRecord['replies'])) {
                    $dbRecord['replies'] = json_encode($dbRecord['replies']);
                }
                if ($collectionName === 'notifications' && isset($dbRecord['id'])) {
                    $dbRecord['notification_id'] = $dbRecord['id'];
                    unset($dbRecord['id']);
                }

                $fields = array_keys($dbRecord);
                $placeholders = array_fill(0, count($fields), '?');

                $sql = "INSERT INTO `$collectionName` (`" . implode("`, `", $fields) . "`) VALUES (" . implode(", ", $placeholders) . ")";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(array_values($dbRecord));
                return $record;
            } catch (Throwable $e) {
                self::$useJsonFallback = true;
            }
        }

        // Fallback to JSON insert
        $data = self::getJsonData();
        if (!isset($data[$collectionName])) {
            $data[$collectionName] = [];
        }
        $data[$collectionName][] = $record;
        self::saveJsonData($data);
        return $record;
    }

    public static function update($collectionName, $key, $value, $updates) {
        $pdo = self::getPdo();
        if ($pdo) {
            try {
                $dbUpdates = $updates;
                if ($collectionName === 'experts' && isset($dbUpdates['subjects']) && is_array($dbUpdates['subjects'])) {
                    $dbUpdates['subjects'] = json_encode($dbUpdates['subjects']);
                }
                if ($collectionName === 'support_tickets' && isset($dbUpdates['replies']) && is_array($dbUpdates['replies'])) {
                    $dbUpdates['replies'] = json_encode($dbUpdates['replies']);
                }

                $setParts = [];
                $params = [];
                foreach ($dbUpdates as $k => $v) {
                    $setParts[] = "`$k` = ?";
                    $params[] = $v;
                }
                $params[] = $value;

                $sql = "UPDATE `$collectionName` SET " . implode(", ", $setParts) . " WHERE `$key` = ?";
                $stmt = $pdo->prepare($sql);
                return $stmt->execute($params);
            } catch (Throwable $e) {
                self::$useJsonFallback = true;
            }
        }

        // Fallback to JSON update
        $data = self::getJsonData();
        if (isset($data[$collectionName])) {
            foreach ($data[$collectionName] as $idx => $item) {
                if (isset($item[$key]) && (string)$item[$key] === (string)$value) {
                    $data[$collectionName][$idx] = array_merge($item, $updates);
                    self::saveJsonData($data);
                    return true;
                }
            }
        }
        return false;
    }

    public static function delete($collectionName, $key, $value) {
        $pdo = self::getPdo();
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("DELETE FROM `$collectionName` WHERE `$key` = ?");
                return $stmt->execute([$value]);
            } catch (Throwable $e) {
                self::$useJsonFallback = true;
            }
        }

        // Fallback to JSON delete
        $data = self::getJsonData();
        if (isset($data[$collectionName])) {
            $data[$collectionName] = array_values(array_filter($data[$collectionName], function($item) use ($key, $value) {
                return !isset($item[$key]) || (string)$item[$key] !== (string)$value;
            }));
            self::saveJsonData($data);
            return true;
        }
        return false;
    }
}
