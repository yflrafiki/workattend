<?php
session_start();
require_once 'db_config.php';

// Check if user is already logged in via device ID
if (isset($_COOKIE['attendance_device_id'])) {
    $device_id = $_COOKIE['attendance_device_id'];

    try {
        $stmt = $pdo->prepare("SELECT id, name, is_approved FROM users WHERE device_id = ?");
        $stmt->execute([$device_id]);

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            if ($user['is_approved']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['is_approved'] = 1;
                $_SESSION['device_id'] = $device_id;

                header("Location: scanner.php");
                exit();
            }
        }
    } catch (PDOException $e) {
        error_log("Auto-login error: " . $e->getMessage());
    }
}

// Get base URL for QR code
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$base_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);

// Get the HTML template
$template = file_get_contents('index.html');

// Inject PHP variables into HTML placeholders
$template = str_replace(
    ['{{BASE_URL}}', '{{HAS_COOKIE}}', '{{DEVICE_ID}}', '{{COMPANY_QR_URL}}'],
    [
        htmlspecialchars($base_url),
        isset($_COOKIE['attendance_device_id']) ? 'Yes' : 'No',
        isset($_COOKIE['attendance_device_id']) ? htmlspecialchars($_COOKIE['attendance_device_id']) : 'None',
        htmlspecialchars($base_url . '/scanner.php')
    ],
    $template
);

// Output the rendered page
echo $template;
