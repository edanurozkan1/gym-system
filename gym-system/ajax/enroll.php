<?php
require_once '../db/connection.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Giriş yapmanız gerekiyor.']);
    exit;
}

$user_id     = $_SESSION['user_id'];
$schedule_id = (int)($_POST['schedule_id'] ?? 0);
$action      = $_POST['action'] ?? '';

if ($schedule_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz ders ID.']);
    exit;
}


if ($action === 'enroll') {
 
    if (!hasActiveMembership($conn, $user_id)) {
        echo json_encode(['success' => false, 'message' => 'Derse kayıt için aktif üyeliğiniz olmalıdır.']);
        exit;
    }

    
    $cap = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT s.current_enrolled, c.max_capacity
        FROM schedule s JOIN classes c ON s.class_id=c.class_id
        WHERE s.schedule_id=$schedule_id
    "));

    if (!$cap) {
        echo json_encode(['success' => false, 'message' => 'Ders bulunamadı.']);
        exit;
    }

    if ($cap['current_enrolled'] >= $cap['max_capacity']) {
        echo json_encode(['success' => false, 'message' => 'Bu ders dolmuş.']);
        exit;
    }

    $existing = mysqli_prepare($conn, "SELECT enrollment_id FROM enrollments WHERE user_id=? AND schedule_id=? AND status='active'");
    mysqli_stmt_bind_param($existing, 'ii', $user_id, $schedule_id);
    mysqli_stmt_execute($existing);
    mysqli_stmt_store_result($existing);

    if (mysqli_stmt_num_rows($existing) > 0) {
        echo json_encode(['success' => false, 'message' => 'Bu derse zaten kayıtlısınız.']);
        exit;
    }

    
    $stmt = mysqli_prepare($conn, "INSERT INTO enrollments (user_id, schedule_id, status) VALUES (?,?,'active')");
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $schedule_id);

    if (mysqli_stmt_execute($stmt)) {
       
        mysqli_query($conn, "UPDATE schedule SET current_enrolled = current_enrolled + 1 WHERE schedule_id=$schedule_id");
        echo json_encode(['success' => true, 'message' => '✓ Derse başarıyla kayıt oldunuz!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Kayıt sırasında hata oluştu.']);
    }
}


elseif ($action === 'cancel') {
    $stmt = mysqli_prepare($conn, "UPDATE enrollments SET status='cancelled' WHERE user_id=? AND schedule_id=? AND status='active'");
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $schedule_id);

    if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
        
        mysqli_query($conn, "UPDATE schedule SET current_enrolled = GREATEST(current_enrolled - 1, 0) WHERE schedule_id=$schedule_id");
        echo json_encode(['success' => true, 'message' => 'Kayıt iptal edildi.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'İptal işlemi başarısız.']);
    }
}

else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz işlem.']);
}
?>
