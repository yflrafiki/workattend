<?php
require_once 'db_config.php';
session_start();

// If user is already logged in and approved, redirect to scanner
if (isset($_SESSION['user_id']) && isset($_SESSION['is_approved']) && $_SESSION['is_approved'] == 1) {
    header("Location: scanner.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "⚠️ Please enter email and password!";
        header("Location: login.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT id, name, email, password_hash, is_approved, device_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() === 0) {
            $_SESSION['error'] = "❌ Invalid email or password!";
            header("Location: login.php");
            exit();
        }

        $user = $stmt->fetch();
        
        if (!password_verify($password, $user['password_hash'])) {
            $_SESSION['error'] = "❌ Invalid email or password!";
            header("Location: login.php");
            exit();
        }

        if (!$user['is_approved']) {
            $_SESSION['error'] = "⏳ Your account is pending approval. Please wait for admin approval.";
            header("Location: login.php");
            exit();
        }

        // Login successful - set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['is_approved'] = $user['is_approved'];
        $_SESSION['device_id'] = $user['device_id'];

        // Set device cookie for persistent login
        setcookie('attendance_device_id', $user['device_id'], time() + (365 * 24 * 60 * 60), '/');

        $_SESSION['success'] = "✅ Login successful!";
        header("Location: scanner.php");
        exit();
        
    } catch(PDOException $e) {
        $_SESSION['error'] = "⚠️ System error. Please try again.";
        error_log("Login error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card mt-5">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Login to Attendance System</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" required
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>
                        
                        <div class="text-center mt-3">
                            <a href="register.php">Don't have an account? Register</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>