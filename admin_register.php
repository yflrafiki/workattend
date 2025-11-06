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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .register-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
        }
        .password-strength {
            height: 5px;
            margin-top: 5px;
            border-radius: 5px;
        }
        .weak { background-color: #dc3545; width: 33%; }
        .medium { background-color: #ffc107; width: 66%; }
        .strong { background-color: #28a745; width: 100%; }
    </style>
</head>
<body class="bg-light">
    <div class="container register-container">
        <div class="card shadow">
            <div class="card-header bg-primary text-white text-center">
                <h3><i class="fas fa-user-shield me-2"></i>Admin Registration</h3>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $success; ?>
                        <div class="mt-2">
                            <a href="admin_login.php" class="btn btn-success btn-sm">Go to Login</a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!$success): ?>
                <form method="POST" id="registerForm">
                    <div class="mb-3">
                        <label for="name" class="form-label">
                            <i class="fas fa-user me-1"></i>Full Name
                        </label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                               required autofocus>
                    </div>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">
                            <i class="fas fa-at me-1"></i>Username
                        </label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                               required pattern="[a-zA-Z0-9_]+" title="Letters, numbers, and underscores only">
                        <div class="form-text">Only letters, numbers, and underscores allowed</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope me-1"></i>Email (Optional)
                        </label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock me-1"></i>Password
                        </label>
                        <input type="password" class="form-control" id="password" name="password" 
                               required minlength="6">
                        <div class="password-strength" id="passwordStrength"></div>
                        <div class="form-text">Minimum 6 characters</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">
                            <i class="fas fa-lock me-1"></i>Confirm Password
                        </label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        <div class="form-text" id="passwordMatch"></div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-user-plus me-2"></i>Create Admin Account
                        </button>
                    </div>
                </form>
                <?php endif; ?>
                
                <div class="text-center mt-4">
                    <p class="mb-2">Already have an account?</p>
                    <a href="admin_login.php" class="btn btn-outline-primary">
                        <i class="fas fa-sign-in-alt me-2"></i>Login Here
                    </a>
                    <a href="index.php" class="btn btn-outline-secondary ms-2">
                        <i class="fas fa-home me-2"></i>Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrength');
            let strength = 0;
            
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/\d/)) strength++;
            if (password.match(/[^a-zA-Z\d]/)) strength++;
            
            strengthBar.className = 'password-strength';
            if (password.length === 0) {
                strengthBar.style.width = '0';
            } else if (strength <= 1) {
                strengthBar.className += ' weak';
            } else if (strength <= 3) {
                strengthBar.className += ' medium';
            } else {
                strengthBar.className += ' strong';
            }
        });
        
        // Password match validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            const matchText = document.getElementById('passwordMatch');
            
            if (confirmPassword === '') {
                matchText.innerHTML = '';
            } else if (password === confirmPassword) {
                matchText.innerHTML = '<span style="color: green;">✓ Passwords match</span>';
            } else {
                matchText.innerHTML = '<span style="color: red;">✗ Passwords do not match</span>';
            }
        });
        
        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
                return false;
            }
        });
    </script>
</body>
</html>