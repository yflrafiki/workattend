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
                // User is approved and logged in - redirect to scanner
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['is_approved'] = 1;
                $_SESSION['device_id'] = $device_id;
                
                header("Location: scanner.php");
                exit();
            }
        }
    } catch(PDOException $e) {
        error_log("Auto-login error: " . $e->getMessage());
        // Continue to show the index page even if there's an error
    }
}

// Get base URL for QR code
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$base_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0d6efd" />
    <script>
        if ("serviceWorker" in navigator) {
            navigator.serviceWorker.register("service-worker.js")
                .then(() => console.log("Service Worker registered!"))
                .catch((err) => console.log("Service Worker registration failed:", err));
        }
    </script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
        .feature-card {
            transition: transform 0.3s ease;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .step-number {
            width: 40px;
            height: 40px;
            background: #0d6efd;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
        }
        .qr-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin: 20px 0;
        }
        .scanner-section {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border-radius: 15px;
        }
        .qr-code {
            border: 3px solid #0d6efd;
            border-radius: 10px;
            padding: 10px;
            background: white;
            display: inline-block;
        }
        .debug-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <!-- Welcome Card -->
                <div class="card welcome-card mt-4">
                    <div class="card-body text-center py-5">
                        <h1 class="display-4 mb-3">🏢 Company Attendance System</h1>
                        <p class="lead mb-4">Smart QR-based attendance tracking with location verification</p>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <a href="register.php" class="btn btn-light btn-lg w-100">
                                    <i class="fas fa-user-plus me-2"></i>Create Account
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="login.php" class="btn btn-outline-light btn-lg w-100">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login
                                </a>
                            </div>
                        </div>
                        
                        <!-- Debug info for testing -->
                        <div class="debug-info text-start mt-3">
                            <strong>Debug Info:</strong><br>
                            Base URL: <?php echo $base_url; ?><br>
                            Has Device Cookie: <?php echo isset($_COOKIE['attendance_device_id']) ? 'Yes' : 'No'; ?><br>
                            Device ID: <?php echo isset($_COOKIE['attendance_device_id']) ? $_COOKIE['attendance_device_id'] : 'None'; ?>
                        </div>
                    </div>
                </div>

                <!-- QR Code Scanner Section -->
                <div class="card scanner-section mt-4">
                    <div class="card-body text-center py-4">
                        <h3 class="mb-4">
                            <i class="fas fa-qrcode me-2"></i>Quick Attendance Scanner
                        </h3>
                        
                        <div class="qr-container">
                            <h5 class="text-dark mb-3">Scan to Mark Attendance</h5>
                            <?php
                            $company_qr_url = $base_url . '/scanner.php';
                            ?>
                            <div class="qr-code mb-3">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo urlencode($company_qr_url); ?>" 
                                     alt="QR Code" class="img-fluid">
                            </div>
                            <p class="text-muted mb-0">
                                <small>Scan this code to open the attendance scanner</small>
                            </p>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="alert alert-light text-dark">
                                    <i class="fas fa-mobile-alt me-2 text-primary"></i>
                                    <strong>For Approved Users:</strong><br>
                                    <small>Scan QR code → Automatic attendance</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-light text-dark">
                                    <i class="fas fa-user-clock me-2 text-warning"></i>
                                    <strong>For New Users:</strong><br>
                                    <small>Register first → Wait for approval</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- How It Works -->
                <div class="card mt-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-play-circle me-2"></i>How It Works</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-start">
                                    <div class="step-number">1</div>
                                    <div>
                                        <h5>Create Account</h5>
                                        <p class="text-muted mb-0">Register with your details and wait for admin approval</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-start">
                                    <div class="step-number">2</div>
                                    <div>
                                        <h5>Get Approved</h5>
                                        <p class="text-muted mb-0">Admin will approve your account (usually within 24 hours)</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-start">
                                    <div class="step-number">3</div>
                                    <div>
                                        <h5>Access Scanner</h5>
                                        <p class="text-muted mb-0">Login and access the attendance scanner</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-start">
                                    <div class="step-number">4</div>
                                    <div>
                                        <h5>Mark Attendance</h5>
                                        <p class="text-muted mb-0">Click button to mark arrival/departure automatically</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Features -->
                <div class="row mt-4">
                    <div class="col-md-4 mb-3">
                        <div class="card feature-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-qrcode fa-3x text-primary mb-3"></i>
                                <h5>QR Code Access</h5>
                                <p class="text-muted">Quick access to scanner via QR code</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card feature-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-map-marker-alt fa-3x text-success mb-3"></i>
                                <h5>Location Verification</h5>
                                <p class="text-muted">Ensures attendance is marked from company location</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card feature-card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-clock fa-3x text-warning mb-3"></i>
                                <h5>Automatic Tracking</h5>
                                <p class="text-muted">Automatic arrival and departure time recording</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="row mt-4">
                    <div class="col-md-6 mb-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <i class="fas fa-user-check fa-2x text-success mb-3"></i>
                                <h5>Already Approved?</h5>
                                <p class="text-muted">Login to access the scanner directly</p>
                                <a href="login.php" class="btn btn-success w-100">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login Now
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <i class="fas fa-cog fa-2x text-secondary mb-3"></i>
                                <h5>Administrator</h5>
                                <p class="text-muted">Access the admin panel</p>
                                <a href="admin_login.php" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-cog me-2"></i>Admin Panel
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Working Hours Info -->
                <div class="alert alert-info mt-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle fa-2x me-3"></i>
                        <div>
                            <strong>Working Hours:</strong> 6:00 AM - 5:00 PM<br>
                            <small class="text-muted">Attendance can only be marked during working hours with location verification</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>