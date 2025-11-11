<?php
// api/stats.php?action=semesters|activity_attendance|student_hours
require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';

$action = $_GET['action'] ?? null;
if (!$action) jsonResponse(['success'=>false,'message'=>'Missing action']);

try {
    switch ($action) {
        case 'semesters':
            $stmt = $pdo->query("SELECT DISTINCT semester FROM activities WHERE semester IS NOT NULL AND semester <> '' ORDER BY semester DESC");
            $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
            jsonResponse(['success'=>true,'data'=>$rows]);
            break;

        case 'activity_attendance':
            $semester = $_GET['semester'] ?? null;
            if (!$semester) jsonResponse(['success'=>false,'message'=>'semester required']);
            $stmt = $pdo->prepare("SELECT a.id, a.title, a.hours, 
                (SELECT COUNT(*) FROM registrations r WHERE r.activity_id = a.id AND r.status = 'checked_in') AS attended
                FROM activities a
                WHERE a.semester = ?
                ORDER BY a.start_time ASC");
            $stmt->execute([$semester]);
            $rows = $stmt->fetchAll();
            jsonResponse(['success'=>true,'data'=>$rows]);
            break;

        case 'student_hours':
            $student_id = $_GET['student_id'] ?? null;
            if (!$student_id) jsonResponse(['success'=>false,'message'=>'student_id required']);
            $stmt = $pdo->prepare("SELECT a.id AS activity_id, a.title, a.hours, a.start_time, r.checked_in_at
                FROM registrations r
                JOIN activities a ON r.activity_id = a.id
                WHERE r.student_id = ? AND r.status = 'checked_in'
                ORDER BY a.start_time DESC");
            $stmt->execute([$student_id]);
            $rows = $stmt->fetchAll();
            $total = 0.0;
            $data = array_map(function($r) use (&$total){
                $hours = floatval($r['hours']);
                $total += $hours;
                return [
                    'activity_id' => $r['activity_id'],
                    'title' => $r['title'],
                    'hours' => $hours,
                    'date' => $r['start_time'],
                    'checked_in_at' => $r['checked_in_at']
                ];
            }, $rows);
            jsonResponse(['success'=>true,'data'=>$data, 'total_hours' => $total]);
            break;

        default:
            jsonResponse(['success'=>false,'message'=>'Unknown action']);
    }
} catch (Exception $e) {
    jsonResponse(['success'=>false,'message'=>'Error: ' . $e->getMessage()]);
}