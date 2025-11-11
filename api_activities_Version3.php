<?php
// api/activities.php?action=list|get|create|update|delete|events
require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';
session_start();

$action = $_GET['action'] ?? ($_POST['action'] ?? null);
if (!$action) jsonResponse(['success' => false, 'message' => 'Missing action']);

function require_admin_for_mutation() {
    if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
        jsonResponse(['success' => false, 'message' => 'Unauthorized: admin required']);
    }
}

try {
    switch ($action) {
        case 'list':
            $stmt = $pdo->query("SELECT a.*, 
                (SELECT COUNT(*) FROM registrations r WHERE r.activity_id = a.id AND r.status IN ('registered','checked_in')) AS registered
                FROM activities a ORDER BY a.start_time ASC");
            $data = $stmt->fetchAll();
            jsonResponse(['success' => true, 'data' => $data]);
            break;

        case 'get':
            $id = intval($_GET['id'] ?? 0);
            if (!$id) jsonResponse(['success'=>false,'message'=>'Missing id']);
            $stmt = $pdo->prepare("SELECT a.*, 
                (SELECT COUNT(*) FROM registrations r WHERE r.activity_id = a.id AND r.status IN ('registered','checked_in')) AS registered
                FROM activities a WHERE a.id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if (!$row) jsonResponse(['success'=>false,'message'=>'Activity not found']);
            jsonResponse(['success'=>true,'data'=>$row]);
            break;

        case 'create':
            require_admin_for_mutation();
            $input = getJsonInput();
            $title = trim($input['title'] ?? '');
            $start = sanitizeDateTime($input['start_time'] ?? '');
            $end = sanitizeDateTime($input['end_time'] ?? null);
            $capacity = max(0, intval($input['capacity'] ?? 0));
            if ($title === '' || !$start) jsonResponse(['success'=>false,'message'=>'title and start_time required']);

            $stmt = $pdo->prepare("INSERT INTO activities
                (title, description, start_time, end_time, location_name, lat, lng, capacity, semester, hours)
                VALUES (:title, :description, :start_time, :end_time, :location_name, :lat, :lng, :capacity, :semester, :hours)");
            $stmt->execute([
                ':title'=>$title,
                ':description'=>$input['description'] ?? null,
                ':start_time'=>$start,
                ':end_time'=>$end,
                ':location_name'=>$input['location_name'] ?? null,
                ':lat'=> $input['lat'] ?? null,
                ':lng'=> $input['lng'] ?? null,
                ':capacity'=>$capacity,
                ':semester'=>$input['semester'] ?? null,
                ':hours'=> isset($input['hours']) ? floatval($input['hours']) : 0
            ]);
            $newId = $pdo->lastInsertId();
            jsonResponse(['success'=>true,'message'=>'已建立','id'=>$newId]);
            break;

        case 'update':
            require_admin_for_mutation();
            $input = getJsonInput();
            $id = intval($input['activity_id'] ?? 0);
            if (!$id) jsonResponse(['success'=>false,'message'=>'activity_id required']);
            $fields = [];
            $params = [':id'=>$id];
            if (isset($input['title'])) { $fields[] = 'title = :title'; $params[':title'] = $input['title']; }
            if (isset($input['description'])) { $fields[] = 'description = :description'; $params[':description'] = $input['description']; }
            if (isset($input['start_time'])) { $fields[] = 'start_time = :start_time'; $params[':start_time'] = sanitizeDateTime($input['start_time']); }
            if (isset($input['end_time'])) { $fields[] = 'end_time = :end_time'; $params[':end_time'] = sanitizeDateTime($input['end_time']); }
            if (isset($input['location_name'])) { $fields[] = 'location_name = :location_name'; $params[':location_name'] = $input['location_name']; }
            if (isset($input['lat'])) { $fields[] = 'lat = :lat'; $params[':lat'] = $input['lat']; }
            if (isset($input['lng'])) { $fields[] = 'lng = :lng'; $params[':lng'] = $input['lng']; }
            if (isset($input['capacity'])) { $fields[] = 'capacity = :capacity'; $params[':capacity'] = intval($input['capacity']); }
            if (isset($input['semester'])) { $fields[] = 'semester = :semester'; $params[':semester'] = $input['semester']; }
            if (isset($input['hours'])) { $fields[] = 'hours = :hours'; $params[':hours'] = floatval($input['hours']); }

            if (empty($fields)) jsonResponse(['success'=>false,'message'=>'No fields to update']);
            $sql = "UPDATE activities SET " . implode(', ', $fields) . " WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            jsonResponse(['success'=>true,'message'=>'已更新']);
            break;

        case 'delete':
            require_admin_for_mutation();
            $input = getJsonInput();
            $id = intval($input['id'] ?? ($_POST['id'] ?? 0));
            if (!$id) jsonResponse(['success'=>false,'message'=>'id required']);
            $stmt = $pdo->prepare("DELETE FROM activities WHERE id = ?");
            $stmt->execute([$id]);
            jsonResponse(['success'=>true,'message'=>'已刪除']);
            break;

        case 'events':
            $start = $_GET['start'] ?? null;
            $end = $_GET['end'] ?? null;
            $sql = "SELECT id, title, start_time AS start, end_time AS end, semester, hours, location_name FROM activities WHERE 1=1";
            $params = [];
            if ($start) { $sql .= " AND start_time >= :start"; $params[':start'] = $start; }
            if ($end) { $sql .= " AND start_time <= :end"; $params[':end'] = $end; }
            $sql .= " ORDER BY start_time";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();
            $events = array_map(function($r){
                return [
                    'id' => $r['id'],
                    'title' => $r['title'],
                    'start' => $r['start'],
                    'end' => $r['end'],
                    'extendedProps' => [
                        'semester' => $r['semester'],
                        'hours' => $r['hours'],
                        'location' => $r['location_name']
                    ]
                ];
            }, $rows);
            jsonResponse(['success'=>true,'data'=>$events]);
            break;

        default:
            jsonResponse(['success'=>false,'message'=>'Unknown action']);
    }
} catch (Exception $e) {
    jsonResponse(['success'=>false,'message'=>'Error: ' . $e->getMessage()]);
}