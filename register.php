<?php
require_once 'db_config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $status = $_POST['status'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($name) || empty($email) || empty($phone) || empty($status) || empty($password)) {
        $_SESSION['error'] = "⚠️ Please fill in all fields!";
        header("Location: register.php");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "⚠️ Please enter a valid email address!";
        header("Location: register.php");
        exit();
    }

    if ($password !== $confirm_password) {
        $_SESSION['error'] = "⚠️ Passwords do not match!";
        header("Location: register.php");
        exit();
    }

    if (strlen($password) < 6) {
        $_SESSION['error'] = "⚠️ Password must be at least 6 characters long!";
        header("Location: register.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['error'] = "⚠️ This email is already registered!";
            header("Location: register.php");
            exit();
        }

        $device_id = 'device_' . uniqid() . '_' . bin2hex(random_bytes(8));
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, status, password_hash, device_id)
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $phone, $status, $password_hash, $device_id]);

        $_SESSION['success'] = "✅ Registration successful! Please wait for admin approval.";
        header("Location: register.php");
        exit();

    } catch (PDOException $e) {
        $_SESSION['error'] = "⚠️ System error. Please try again.";
        error_log("Registration error: " . $e->getMessage());
        header("Location: register.php");
        exit();
    }
}

// Load HTML template
$template = file_get_contents('register.html');

// Inject flash messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Inject previous input values
$fields = [
    '{{NAME}}' => htmlspecialchars($_POST['name'] ?? ''),
    '{{EMAIL}}' => htmlspecialchars($_POST['email'] ?? ''),
    '{{PHONE}}' => htmlspecialchars($_POST['phone'] ?? ''),
    '{{STATUS_SELECTED_STAFF}}' => (isset($_POST['status']) && $_POST['status'] == 'Staff') ? 'selected' : '',
    '{{STATUS_SELECTED_INTERN}}' => (isset($_POST['status']) && $_POST['status'] == 'Intern') ? 'selected' : '',
    '{{SUCCESS}}' => $success ? "<div class='alert alert-success'>$success</div>" : '',
    '{{ERROR}}' => $error ? "<div class='alert alert-danger'>$error</div>" : ''
];

// Replace placeholders
echo str_replace(array_keys($fields), array_values($fields), $template);
