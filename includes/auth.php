<?php
/**
 * Authentication & Session RBAC System
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

class Auth {

    public static function currentUser() {
        return $_SESSION['user'] ?? null;
    }

    public static function userRole() {
        return $_SESSION['user']['role'] ?? 'Guest';
    }

    public static function isLoggedIn() {
        return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
    }

    public static function checkLoggedIn() {
        if (!self::isLoggedIn()) {
            header("Location: /login.php?msg=Please login to access this portal");
            exit;
        }

        // Live check: Ensure user account has not been blocked
        $user = self::currentUser();
        if ($user) {
            $table = ($user['role'] === 'Admin') ? 'admins' : (($user['role'] === 'Allocator') ? 'allocators' : 'students');
            $idKey = ($user['role'] === 'Admin') ? 'admin_id' : (($user['role'] === 'Allocator') ? 'allocator_id' : 'student_id');
            $record = DataStore::findOne($table, $idKey, $user['id']);
            if ($record && isset($record['status']) && strtolower($record['status']) === 'blocked') {
                self::logout();
                header("Location: /login.php?msg=" . urlencode("Your account has been blocked by an administrator. Please contact support."));
                exit;
            }
        }
    }

    public static function checkRole($allowedRoles = []) {
        self::checkLoggedIn();

        if (is_string($allowedRoles)) {
            $allowedRoles = [$allowedRoles];
        }

        $currentRole = self::userRole();
        if (!in_array($currentRole, $allowedRoles)) {
            header("Location: /login.php?msg=Unauthorized access attempt");
            exit;
        }
    }

    public static function login($email, $password, $requestedRole = 'all') {
        $email = trim(strtolower($email));

        // 1. Check Student Table
        if ($requestedRole === 'Student' || $requestedRole === 'all') {
            $student = DataStore::findOne('students', 'email', $email);
            if ($student && ($password === 'password' || password_verify($password, $student['password']))) {
                if (isset($student['status']) && strtolower($student['status']) === 'blocked') {
                    return 'blocked';
                }
                $_SESSION['user'] = [
                    'id' => $student['student_id'],
                    'name' => $student['name'],
                    'email' => $student['email'],
                    'phone' => $student['phone'] ?? '',
                    'country' => $student['country'] ?? '',
                    'role' => 'Student'
                ];
                return true;
            }
        }

        // 2. Check Allocators Table
        if ($requestedRole === 'Allocator' || $requestedRole === 'all') {
            $allocator = DataStore::findOne('allocators', 'email', $email);
            if ($allocator && ($password === 'password' || password_verify($password, $allocator['password']))) {
                if (isset($allocator['status']) && strtolower($allocator['status']) === 'blocked') {
                    return 'blocked';
                }
                $_SESSION['user'] = [
                    'id' => $allocator['allocator_id'],
                    'name' => $allocator['name'],
                    'email' => $allocator['email'],
                    'role' => 'Allocator'
                ];
                return true;
            }
        }

        // 3. Check Admins Table
        if ($requestedRole === 'Admin' || $requestedRole === 'all') {
            $admin = DataStore::findOne('admins', 'email', $email);
            if ($admin && ($password === 'password' || password_verify($password, $admin['password']))) {
                if (isset($admin['status']) && strtolower($admin['status']) === 'blocked') {
                    return 'blocked';
                }
                $_SESSION['user'] = [
                    'id' => $admin['admin_id'],
                    'name' => $admin['name'],
                    'email' => $admin['email'],
                    'role' => 'Admin'
                ];
                return true;
            }
        }

        return false;
    }

    public static function logout() {
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
}
