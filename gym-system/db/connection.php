<?php


define('DB_HOST', 'localhost');
define('DB_USER', 'root');        
define('DB_PASS', '');            
define('DB_NAME', 'gym_system');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die('<div style="color:red;padding:20px;font-family:sans-serif;">
        <h2>Veritabanı Bağlantı Hatası!</h2>
        <p>' . mysqli_connect_error() . '</p>
        <p>db/connection.php dosyasında DB_USER ve DB_PASS değerlerini düzenleyin.</p>
    </div>');
}

mysqli_set_charset($conn, 'utf8mb4');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isMember() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'member';
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function sanitize($conn, $data) {
    return mysqli_real_escape_string($conn, trim($data));
}

function hasActiveMembership($conn, $user_id) {
    $uid = (int)$user_id;
    $result = mysqli_query($conn, "SELECT payment_id FROM payments 
        WHERE user_id=$uid AND status='active' AND expiry_date >= CURDATE() LIMIT 1");
    return mysqli_num_rows($result) > 0;
}
?>
