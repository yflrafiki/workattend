<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_user'])) {
        $user_id = $_POST['user_id'];
        $stmt = $pdo->prepare("UPDATE users SET is_approved = 1, approved_by = ?, approved_at = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id'], $user_id]);
        $_SESSION['success'] = "User approved successfully!";
    } elseif (isset($_POST['reject_user'])) {
        $user_id = $_POST['user_id'];
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND is_approved = 0");
        $stmt->execute([$user_id]);
        $_SESSION['success'] = "User rejected and removed!";
    } elseif (isset($_POST['bulk_approve'])) {
        $user_ids = $_POST['user_ids'] ?? [];
        if (!empty($user_ids)) {
            $placeholders = str_repeat('?,', count($user_ids) - 1) . '?';
            $stmt = $pdo->prepare("UPDATE users SET is_approved = 1, approved_by = ?, approved_at = NOW() WHERE id IN ($placeholders)");
            $stmt->execute(array_merge([$_SESSION['admin_id']], $user_ids));
            $_SESSION['success'] = count($user_ids) . " users approved successfully!";
        }
    }
    header("Location: user_approval.php");
    exit();
}

// Get pending and approved users
$pending_users = [];
$approved_users = [];

try {
    $stmt = $pdo->prepare("SELECT id, name, email, phone, status, created_at FROM users WHERE is_approved = 0 ORDER BY created_at DESC");
    $stmt->execute();
    $pending_users = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT u.id, u.name, u.email, u.phone, u.status, u.approved_at, a.name as approved_by_name 
                          FROM users u 
                          LEFT JOIN admins a ON u.approved_by = a.id 
                          WHERE u.is_approved = 1 
                          ORDER BY u.approved_at DESC");
    $stmt->execute();
    $approved_users = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("User approval error: " . $e->getMessage());
}
?>