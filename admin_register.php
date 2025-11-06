<?php
session_start();
require_once 'db_config.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged_in'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($name) || empty($username) || empty($password) || empty($confirm_password)) {
        $error = "All fields except email are required!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long!";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = "Username can only contain letters, numbers, and underscores!";
    } else {
        try {
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM admin WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->rowCount() > 0) {
                $error = "Username already exists! Please choose a different one.";
            } else {
                // Check if email exists (if provided)
                if (!empty($email)) {
                    $stmt = $pdo->prepare("SELECT id FROM admin WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->rowCount() > 0) {
                        $error = "Email already registered!";
                    }
                }

                if (empty($error)) {
                    // Hash password and create admin account
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    $stmt = $pdo->prepare("INSERT INTO admin (name, username, email, password, role) VALUES (?, ?, ?, ?, 'admin')");
                    $result = $stmt->execute([$name, $username, $email, $hashed_password]);
                    
                    if ($result) {
                        $success = "Admin account created successfully! You can now login.";
                    } else {
                        $error = "Failed to create admin account. Please try again.";
                    }
                }
            }
        } catch(PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>