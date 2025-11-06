<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company QR Code - Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .qr-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin: 20px 0;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 text-center">
                <div class="card mt-5">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-qrcode me-2"></i>
                            Company Attendance QR Code
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="qr-container">
                            <h5 class="text-primary mb-3">Scan to Mark Attendance</h5>
                            
                            <!-- Company QR Code -->
                            <div class="border p-3 bg-white rounded mb-3">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?data=ATTENDANCE_QR_CODE_2024&amp;size=300x300" 
                                     alt="Company QR Code" class="img-fluid">
                            </div>
                            
                            <div class="mt-3 p-3 bg-success text-white rounded">
                                <h6 class="mb-2">
                                    <i class="fas fa-info-circle me-2"></i>
                                    For Approved Users Only
                                </h6>
                                <p class="mb-0">
                                    <small>Scan this QR code with the attendance app to mark your attendance</small>
                                </p>
                            </div>
                        </div>
                        
                        <p class="text-muted mt-3">
                            <strong>How it works:</strong><br>
                            1. Approved user scans this QR code<br>
                            2. System automatically checks location<br>
                            3. If at company (5.697855, -0.176617), marks attendance<br>
                            4. Shows confirmation message immediately
                        </p>

                        <!-- Direct Access for Approved Users -->
                        <div class="mt-4">
                            <a href="scanner.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-hand-point-up me-2"></i>Mark Attendance Directly
                            </a>
                            <p class="text-muted mt-2">
                                <small>For approved users - click to mark attendance using your current location</small>
                            </p>
                        </div>
                        
                        <div class="mt-3">
                            <a href="dashboard.php" class="btn btn-success me-2">
                                <i class="fas fa-tachometer-alt me-2"></i>View Dashboard
                            </a>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-home me-2"></i>Home
                            </a>
                        </div>

                        <!-- Current Status -->
                        <div class="mt-4 p-3 bg-info text-white rounded">
                            <h6><i class="fas fa-clock me-2"></i>Current Status</h6>
                            <div class="row text-center">
                                <div class="col-6">
                                    <small>Date: <?php echo date('M j, Y'); ?></small>
                                </div>
                                <div class="col-6">
                                    <small>Time: <?php echo date('g:i A'); ?></small>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small>
                                    <?php 
                                    $current_hour = date('H');
                                    if ($current_hour >= 6 && $current_hour < 17) {
                                        echo '<span class="badge bg-success">Within Working Hours</span>';
                                    } else {
                                        echo '<span class="badge bg-warning">Outside Working Hours</span>';
                                    }
                                    ?>
                                </small>
                            </div>
                            <div class="mt-2">
                                <small>
                                    Company Location: 5.697855, -0.176617
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>