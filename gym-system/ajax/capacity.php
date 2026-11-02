<?php
require_once '../db/connection.php';
header('Content-Type: application/json');

$schedule_id = (int)($_GET['schedule_id'] ?? 0);

if ($schedule_id <= 0) {
    echo json_encode(['error' => 'Geçersiz ID']);
    exit;
}

$result = mysqli_query($conn, "
    SELECT s.current_enrolled, c.max_capacity
    FROM schedule s
    JOIN classes c ON s.class_id = c.class_id
    WHERE s.schedule_id = $schedule_id
");

$data = mysqli_fetch_assoc($result);

if ($data) {
    echo json_encode([
        'current' => (int)$data['current_enrolled'],
        'max'     => (int)$data['max_capacity'],
        'pct'     => $data['max_capacity'] > 0 ? round(($data['current_enrolled'] / $data['max_capacity']) * 100) : 0
    ]);
} else {
    echo json_encode(['error' => 'Ders bulunamadı']);
}
?>
