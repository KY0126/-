<?php
// api/checkin.php?action=checkin
require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? null);
if ($action !== 'checkin') jsonResponse(['success'=>false,'message'=>'Missing or invalid action']);

$input = getJsonInput();
try {
    if (!empty($input['token'])) {
        $token = $input['token'];
        $stmt = $pdo->prepare("SELECT * FROM registrations WHERE qr_token = ?");
        $stmt->execute([$token]);
        $reg = $stmt->fetch();
        if (!$reg) jsonResponse(['success'=>false,'message'=>'無效的 QR token']);
    } else {
        $activity_id = intval($input['activity_id'] ?? 0);
        $student_id = trim($input['student_id'] ?? '');
        if (!$activity_id || !$student_id) jsonResponse(['success'=>false,'message'=>'token 或 activity_id + student_id required']);
        $stmt = $pdo->prepare("SELECT * FROM registrations WHERE activity_id = ? AND student_id = ? AND status != 'cancelled'");
        $stmt->execute([$activity_id, $student_id]);
        $reg = $stmt->fetch();
        if (!$reg) jsonResponse(['success'=>false,'message'=>'找不到報名紀錄']);
    }

    if ($reg['status'] === 'checked_in') {
        jsonResponse(['success'=>true,'message'=>'已簽到']);
    }

    $stmt = $pdo->prepare("UPDATE registrations SET status = 'checked_in', checked_in_at = NOW() WHERE id = ?");
    $stmt->execute([$reg['id']]);

    jsonResponse(['success'=>true,'message'=>'簽到成功', 'registration_id' => $reg['id']]);
} catch (Exception $e) {
    jsonResponse(['success'=>false,'message'=>'Error: ' . $e->getMessage()]);
}