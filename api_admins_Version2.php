<?php
// api/admins.php?action=list|create|delete
require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';
session_start();

$action = $_GET['action'] ?? ($_POST['action'] ?? null);
if (!$action) jsonResponse(['success' => false, 'message' => 'Missing action']);

function require_admin() {
    if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
        jsonResponse(['success' => false, 'message' => 'Unauthorized: admin required']);
    }
}

try {
    switch ($action) {
        case 'list':
            require_admin();
            $stmt = $pdo->query("SELECT id, username, display_name, created_at FROM admins ORDER BY id ASC");
            $rows = $stmt->fetchAll();
            jsonResponse(['success' => true, 'data' => $rows]);
            break;

        case 'create':
            require_admin();
            $input = getJsonInput();
            $username = trim($input['username'] ?? '');
            $password = $input['password'] ?? '';
            $display_name = trim($input['display_name'] ?? '');
            if (!$username || !$password) jsonResponse(['success' => false, 'message' => 'username and password required']);

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) jsonResponse(['success' => false, 'message' => 'username already exists']);

            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admins (username, password_hash, display_name) VALUES (?, ?, ?)");
            $stmt->execute([$username, $password_hash, $display_name]);
            jsonResponse(['success' => true, 'message' => 'Admin created', 'id' => $pdo->lastInsertId()]);
            break;

        case 'delete':
            require_admin();
            $input = getJsonInput();
            $id = intval($input['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'message' => 'id required']);

            if (!empty($_SESSION['user']['id']) && intval($_SESSION['user']['id']) === $id) {
                jsonResponse(['success' => false, 'message' => 'Cannot delete currently logged-in admin']);
            }

            $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
            $stmt->execute([$id]);
            if ($stmt->rowCount() > 0) {
                jsonResponse(['success' => true, 'message' => 'Admin deleted']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Admin not found']);
            }
            break;

        default:
            jsonResponse(['success' => false, 'message' => 'Unknown action']);
    }
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}