<?php
require_once 'db_config.php';
session_start();

// Check if user is logged in and approved
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_approved']) || $_SESSION['is_approved'] != 1) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$device_id = $_SESSION['device_id'];

// Check current attendance status
$today = date('Y-m-d');
$current_time = date('H:i:s');
$current_hour = date('H');
$current_minute = date('i');

try {
    $stmt = $pdo->prepare("SELECT time, departure_time FROM attendance WHERE user_id = ? AND date = ?");
    $stmt->execute([$user_id, $today]);
    $attendance_today = $stmt->fetch();

    $attendance_status = 'none'; // none, arrived, completed
    $arrival_time = null;
    $departure_time = null;

    if ($attendance_today) {
        $arrival_time = $attendance_today['time'];
        if ($attendance_today['departure_time']) {
            $attendance_status = 'completed';
            $departure_time = $attendance_today['departure_time'];
        } else {
            $attendance_status = 'arrived';
        }
    }
} catch(PDOException $e) {
    error_log("Attendance check error: " . $e->getMessage());
    $attendance_status = 'none';
}

$is_working_hours = ($current_hour >= 6 && $current_hour < 17);
$is_after_5pm = ($current_hour >= 17);
$can_mark_arrival = $is_working_hours && $attendance_status === 'none';
$can_mark_departure = $is_after_5pm && $attendance_status === 'arrived';
?>