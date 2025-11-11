<?php
// api/auth.php?action=login|logout|status
require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';
session_start();

$action = $_GET['action'] ?? ($_POST['action'] ?? null);
if (!$action) jsonResponse(['success' => false, 'message' => 'Missing action']);

try {
    switch ($action) {
        case 'status':
            if (!empty($_SESSION['user'])) {
                jsonResponse(['success' => true, 'data' => $_SESSION['user']]);
            } else {
                jsonResponse(['success' => true, 'data' => null]);
            }
            break;

        case 'login':
            $input = getJsonInput();
            $role = $input['role'] ?? ($input['type'] ?? null);
            if (!$role) jsonResponse(['success' => false, 'message' => 'role required']);

            if ($role === 'admin') {
                $username = trim($input['username'] ?? '');
                $password = $input['password'] ?? '';
                if (!$username || !$password) jsonResponse(['success' => false, 'message' => 'username and password required']);

                $stmt = $pdo->prepare("SELECT id, username, password_hash, display_name FROM admins WHERE username = ?");
                $stmt->execute([$username]);
                $admin = $stmt->fetch();
                if (!$admin || !password_verify($password, $admin['password_hash'])) {
                    jsonResponse(['success' => false, 'message' => 'Invalid admin credentials']);
                }

                $_SESSION['user'] = [
                    'role' => 'admin',
                    'id' => (int)$admin['id'],
                    'username' => $admin['username'],
                    'display_name' => $admin['display_name'] ?? $admin['username']
                ];
                jsonResponse(['success' => true, 'message' => 'Admin logged in', 'data' => $_SESSION['user']]);
            } elseif ($role === 'student' || $role === 'user') {
                $student_id = trim($input['student_id'] ?? '');
                if (!$student_id) jsonResponse(['success' => false, 'message' => 'student_id required']);

                $stmt = $pdo->prepare("SELECT student_id, name, email FROM students WHERE student_id = ?");
                $stmt->execute([$student_id]);
                $student = $stmt->fetch();
                if (!$student) {
                    jsonResponse(['success' => false, 'message' => 'Student not found']);
                }

                $_SESSION['user'] = [
                    'role' => 'user',
                    'student_id' => $student['student_id'],
                    'name' => $student['name'],
                    'email' => $student['email'] ?? null
                ];
                jsonResponse(['success' => true, 'message' => 'Student logged in', 'data' => $_SESSION['user']]);
            } else {
                jsonResponse(['success' => false, 'message' => 'Unknown role']);
            }
            break;

        case 'logout':
            session_unset();
            session_destroy();
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            jsonResponse(['success' => true, 'message' => 'Logged out']);
            break;

        default:
            jsonResponse(['success' => false, 'message' => 'Unknown action']);
    }
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}