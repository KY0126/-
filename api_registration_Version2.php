<?php
// api/registration.php?action=list|register|cancel
require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? null);
if (!$action) jsonResponse(['success'=>false,'message'=>'Missing action']);

try {
    switch ($action) {
        case 'list':
            $student_id = $_GET['student_id'] ?? null;
            if (!$student_id) jsonResponse(['success'=>false,'message'=>'student_id required']);
            $stmt = $pdo->prepare("SELECT r.*, a.title, a.start_time, a.end_time, a.location_name 
                FROM registrations r JOIN activities a ON r.activity_id = a.id
                WHERE r.student_id = ? AND r.status != 'cancelled' ORDER BY a.start_time");
            $stmt->execute([$student_id]);
            $rows = $stmt->fetchAll();
            $data = array_map(function($r){
                return [
                    'id' => $r['id'],
                    'activity_id' => $r['activity_id'],
                    'title' => $r['title'],
                    'start_time' => $r['start_time'],
                    'end_time' => $r['end_time'],
                    'location_name' => $r['location_name'],
                    'status' => $r['status'],
                    'qr_token' => $r['qr_token'],
                    'registered_at' => $r['registered_at']
                ];
            }, $rows);
            jsonResponse(['success'=>true,'data'=>$data]);
            break;

        case 'register':
            $input = getJsonInput();
            $student_id = trim($input['student_id'] ?? '');
            $activity_id = intval($input['activity_id'] ?? 0);
            if (!$student_id || !$activity_id) jsonResponse(['success'=>false,'message'=>'student_id and activity_id required']);

            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("SELECT * FROM activities WHERE id = ? FOR UPDATE");
                $stmt->execute([$activity_id]);
                $activity = $stmt->fetch();
                if (!$activity) {
                    $pdo->rollBack();
                    jsonResponse(['success'=>false,'message'=>'Activity not found']);
                }

                $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM registrations WHERE activity_id = ? AND status IN ('registered','checked_in')");
                $stmt->execute([$activity_id]);
                $cnt = intval($stmt->fetchColumn());

                if ($activity['capacity'] > 0 && $cnt >= intval($activity['capacity'])) {
                    $pdo->rollBack();
                    jsonResponse(['success'=>false,'message'=>'名額已滿']);
                }

                $stmt = $pdo->prepare("SELECT * FROM registrations WHERE activity_id = ? AND student_id = ? AND status != 'cancelled'");
                $stmt->execute([$activity_id, $student_id]);
                if ($stmt->fetch()) {
                    $pdo->rollBack();
                    jsonResponse(['success'=>false,'message'=>'已報名過此活動']);
                }

                $qr_token = generateUuidV4();
                $stmt = $pdo->prepare("INSERT INTO registrations (activity_id, student_id, status, qr_token) VALUES (?, ?, 'registered', ?)");
                $stmt->execute([$activity_id, $student_id, $qr_token]);
                $regId = $pdo->lastInsertId();

                $pdo->commit();

                jsonResponse(['success'=>true,'message'=>'已報名','qr_token'=>$qr_token, 'registration_id'=>$regId, 'activity_title'=>$activity['title']]);
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $e;
            }
            break;

        case 'cancel':
            $input = getJsonInput();
            $student_id = trim($input['student_id'] ?? '');
            $activity_id = intval($input['activity_id'] ?? 0);
            if (!$student_id || !$activity_id) jsonResponse(['success'=>false,'message'=>'student_id and activity_id required']);

            $stmt = $pdo->prepare("UPDATE registrations SET status = 'cancelled' WHERE activity_id = ? AND student_id = ? AND status != 'cancelled'");
            $stmt->execute([$activity_id, $student_id]);
            if ($stmt->rowCount() > 0) {
                jsonResponse(['success'=>true,'message'=>'已取消']);
            } else {
                jsonResponse(['success'=>false,'message'=>'找不到可取消的報名']);
            }
            break;

        default:
            jsonResponse(['success'=>false,'message'=>'Unknown action']);
    }
} catch (Exception $e) {
    jsonResponse(['success'=>false,'message'=>'Error: ' . $e->getMessage()]);
}